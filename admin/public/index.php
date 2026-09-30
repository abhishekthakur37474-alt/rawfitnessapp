<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require dirname(__DIR__, 2) . '/backend/src/bootstrap.php';
require dirname(__DIR__) . '/src/Csrf.php';
require dirname(__DIR__) . '/src/Admin.php';

use App\Repositories\GovIdRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\PackageRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Schema;

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = '';
if (str_starts_with($rawPath, '/admin')) {
    $base = '/admin';
    $path = substr($rawPath, strlen('/admin')) ?: '/';
} else {
    $path = $rawPath;
}
$path = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$error = null;
$params = [];

try {
    $pdo = Database::pdo($config['db']);
    Schema::migrate($pdo, $config);
} catch (Throwable $e) {
    $pdo = null;
    $error = 'Database unavailable.';
}

if ($path === '/logout') {
    $_SESSION = [];
    session_destroy();
    Admin::redirect($base . '/login');
}

if ($method === 'POST' && $path === '/login' && $pdo) {
    if (!Csrf::check()) {
        $error = 'Invalid session. Refresh and try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM admin_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            Admin::redirect($base . '/');
        }
        $error = 'Invalid email or password.';
    }
}

$loggedIn = !empty($_SESSION['admin_id']);

if (!$loggedIn && $path !== '/login') {
    Admin::redirect($base . '/login');
}

if ($loggedIn && $path === '/login') {
    Admin::redirect($base . '/');
}

$title = 'Dashboard';
$view = dirname(__DIR__) . '/views/dashboard.php';

if ($loggedIn && $pdo) {
    $stats = Admin::stats($pdo);

    // ---------- Members ----------
    if ($method === 'POST' && preg_match('#^/members/(\d+)/gov-id$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/members/' . (int) $params[1]);
        }
        $memberId = (int) $params[1];
        $govRepo = new GovIdRepository($pdo);
        $gov = $govRepo->latestForUser($memberId);
        $action = (string) ($_POST['action'] ?? '');
        if (!$gov) {
            Admin::flash('danger', 'No government ID on file.');
        } elseif ($action === 'approve') {
            $govRepo->setStatus((int) $gov['id'], 'verified', null);
            Admin::flash('success', 'Government ID approved.');
        } elseif ($action === 'reject') {
            $reason = trim((string) ($_POST['reason'] ?? ''));
            if ($reason === '') {
                Admin::flash('danger', 'Please provide a rejection reason.');
            } else {
                $govRepo->setStatus((int) $gov['id'], 'rejected', $reason);
                Admin::flash('success', 'Government ID rejected.');
            }
        }
        Admin::redirect($base . '/members/' . $memberId);
    }

    if ($method === 'POST' && preg_match('#^/members/(\d+)/membership$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/members/' . (int) $params[1]);
        }
        $memberId = (int) $params[1];
        $packageId = (int) ($_POST['package_id'] ?? 0);
        $package = (new PackageRepository($pdo))->find($packageId);
        if (!$package) {
            Admin::flash('danger', 'Select a valid package.');
            Admin::redirect($base . '/members/' . $memberId);
        }
        $start = trim((string) ($_POST['start_date'] ?? '')) ?: date('Y-m-d');
        try {
            $start = (new DateTimeImmutable($start))->format('Y-m-d');
        } catch (Throwable $e) {
            $start = date('Y-m-d');
        }
        $duration = max(1, (int) $package['duration_days']);
        $end = (new DateTimeImmutable($start))->modify('+' . $duration . ' days')->format('Y-m-d');
        $amount = max(0.0, (float) $package['price'] - (float) $package['discount']);
        if (isset($_POST['amount']) && is_numeric($_POST['amount']) && (float) $_POST['amount'] > 0) {
            $amount = round((float) $_POST['amount'], 2);
        }
        $paid = isset($_POST['paid_amount']) && is_numeric($_POST['paid_amount'])
            ? round((float) $_POST['paid_amount'], 2)
            : $amount;
        $paid = max(0.0, min($paid, $amount));
        $mode = in_array($_POST['mode'] ?? '', ['cash', 'upi', 'card', 'online'], true)
            ? (string) $_POST['mode']
            : 'cash';
        $txn = trim((string) ($_POST['txn_ref'] ?? '')) ?: null;

        $memRepo = new MembershipRepository($pdo);
        $membershipId = $memRepo->create($memberId, $packageId, $start, $end, $amount, 0, $amount, 'active');
        $memRepo->cancelOthers($memberId, $membershipId);
        if ($paid > 0) {
            (new PaymentRepository($pdo))->create($memberId, $membershipId, $paid, $mode, $txn, 'success');
            $memRepo->recalcTotals($membershipId);
        }
        $close = $pdo->prepare('UPDATE membership_requests SET status = "approved" WHERE user_id = ? AND status = "pending"');
        $close->execute([$memberId]);
        Admin::flash('success', 'Membership assigned.');
        Admin::redirect($base . '/members/' . $memberId);
    }

    if ($method === 'POST' && preg_match('#^/members/(\d+)/payment$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/members/' . (int) $params[1]);
        }
        $memberId = (int) $params[1];
        $stmt = $pdo->prepare(
            'SELECT * FROM memberships WHERE user_id = ? AND status = "active"
             ORDER BY end_date DESC, id DESC LIMIT 1'
        );
        $stmt->execute([$memberId]);
        $membership = $stmt->fetch();
        $amount = isset($_POST['amount']) && is_numeric($_POST['amount'])
            ? round((float) $_POST['amount'], 2)
            : 0.0;
        if (!$membership) {
            Admin::flash('danger', 'This member has no active membership.');
        } elseif ($amount <= 0) {
            Admin::flash('danger', 'Enter a valid payment amount.');
        } else {
            $mode = in_array($_POST['mode'] ?? '', ['cash', 'upi', 'card', 'online'], true)
                ? (string) $_POST['mode']
                : 'cash';
            $txn = trim((string) ($_POST['txn_ref'] ?? '')) ?: null;
            $paymentId = (new PaymentRepository($pdo))->create(
                $memberId,
                (int) $membership['id'],
                $amount,
                $mode,
                $txn,
                'success'
            );
            (new MembershipRepository($pdo))->recalcTotals((int) $membership['id']);
            Admin::flash('success', 'Payment recorded.');
            Admin::redirect($base . '/receipts/' . $paymentId);
        }
        Admin::redirect($base . '/members/' . $memberId);
    }

    if ($method === 'GET' && preg_match('#^/members/(\d+)$#', $path, $params)) {
        $memberId = (int) $params[1];
        $user = (new UserRepository($pdo))->findById($memberId);
        if (!$user) {
            Admin::flash('danger', 'Member not found.');
            Admin::redirect($base . '/members');
        }
        $govRepo = new GovIdRepository($pdo);
        $gov = $govRepo->latestForUser($memberId);
        $memRepo = new MembershipRepository($pdo);
        $member = $user;
        $govId = $gov ? $govRepo->public($gov, $config['base_url']) : null;
        $current = $memRepo->currentPayload($memberId);
        $membershipHistory = $memRepo->historyForUser($memberId);
        $payments = (new PaymentRepository($pdo))->forUser($memberId);
        $packages = (new PackageRepository($pdo))->all(true);
        $title = 'Member · ' . ($user['member_id'] ?? '');
        $view = dirname(__DIR__) . '/views/member_view.php';
    } elseif ($method === 'GET' && $path === '/members') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $where = '';
        $vals = [];
        if ($q !== '') {
            $where = 'WHERE u.name LIKE ? OR u.mobile LIKE ? OR u.member_id LIKE ?';
            $vals = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
        }
        $count = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
        $count->execute($vals);
        $total = (int) $count->fetchColumn();
        $sql = "SELECT u.*,
                    (SELECT m.status FROM memberships m WHERE m.user_id = u.id ORDER BY m.end_date DESC, m.id DESC LIMIT 1) AS m_status,
                    (SELECT m.end_date FROM memberships m WHERE m.user_id = u.id ORDER BY m.end_date DESC, m.id DESC LIMIT 1) AS m_end
                FROM users u $where
                ORDER BY u.id DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
        $members = $stmt->fetchAll();
        $search = $q;
        $pageNo = $page;
        $pageCount = max(1, (int) ceil($total / $perPage));
        $memberTotal = $total;
        $title = 'Members';
        $view = dirname(__DIR__) . '/views/members.php';
    }

    // ---------- Packages ----------
    elseif ($method === 'POST' && preg_match('#^/packages/(\d+)/(delete|toggle)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/packages');
        }
        $id = (int) $params[1];
        $repo = new PackageRepository($pdo);
        if ($params[2] === 'delete') {
            $repo->delete($id);
            Admin::flash('success', 'Package deleted.');
        } else {
            $package = $repo->find($id);
            $repo->setActive($id, (int) ($package['is_active'] ?? 0) !== 1);
            Admin::flash('success', 'Package status updated.');
        }
        Admin::redirect($base . '/packages');
    } elseif ($method === 'POST' && preg_match('#^/packages/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/packages');
        }
        $id = (int) $params[1];
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'duration_days' => max(1, (int) ($_POST['duration_days'] ?? 30)),
            'price' => round((float) ($_POST['price'] ?? 0), 2),
            'discount' => round((float) ($_POST['discount'] ?? 0), 2),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'branch_id' => ($_POST['branch_id'] ?? '') !== '' ? (int) $_POST['branch_id'] : null,
            'is_active' => (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0,
        ];
        if ($data['name'] === '') {
            Admin::flash('danger', 'Package name is required.');
            Admin::redirect($base . '/packages/' . $id . '/edit');
        }
        (new PackageRepository($pdo))->update($id, $data);
        Admin::flash('success', 'Package updated.');
        Admin::redirect($base . '/packages');
    } elseif ($method === 'POST' && $path === '/packages') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/packages/new');
        }
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'duration_days' => max(1, (int) ($_POST['duration_days'] ?? 30)),
            'price' => round((float) ($_POST['price'] ?? 0), 2),
            'discount' => round((float) ($_POST['discount'] ?? 0), 2),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'branch_id' => ($_POST['branch_id'] ?? '') !== '' ? (int) $_POST['branch_id'] : null,
            'is_active' => (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0,
        ];
        if ($data['name'] === '') {
            Admin::flash('danger', 'Package name is required.');
            Admin::redirect($base . '/packages/new');
        }
        (new PackageRepository($pdo))->create($data);
        Admin::flash('success', 'Package created.');
        Admin::redirect($base . '/packages');
    } elseif ($method === 'GET' && preg_match('#^/packages/(\d+)/edit$#', $path, $params)) {
        $package = (new PackageRepository($pdo))->find((int) $params[1]);
        if (!$package) {
            Admin::flash('danger', 'Package not found.');
            Admin::redirect($base . '/packages');
        }
        $branches = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        $title = 'Edit package';
        $view = dirname(__DIR__) . '/views/package_form.php';
    } elseif ($method === 'GET' && $path === '/packages/new') {
        $package = null;
        $branches = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        $title = 'New package';
        $view = dirname(__DIR__) . '/views/package_form.php';
    } elseif ($method === 'GET' && $path === '/packages') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $packages = (new PackageRepository($pdo))->search($q !== '' ? $q : null);
        $search = $q;
        $title = 'Packages';
        $view = dirname(__DIR__) . '/views/packages.php';
    }

    // ---------- Receipts ----------
    elseif ($method === 'GET' && preg_match('#^/receipts/(\d+)$#', $path, $params)) {
        $payment = (new PaymentRepository($pdo))->find((int) $params[1]);
        if (!$payment) {
            Admin::flash('danger', 'Receipt not found.');
            Admin::redirect($base . '/members');
        }
        $member = (new UserRepository($pdo))->findById((int) $payment['user_id']);
        $gymName = $config['app_name'];
        $title = 'Receipt ' . ($payment['receipt_no'] ?? '');
        $view = dirname(__DIR__) . '/views/receipt.php';
    }
}

$flashes = Admin::takeFlashes();
require dirname(__DIR__) . '/views/layout.php';
