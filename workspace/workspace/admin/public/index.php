<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require dirname(__DIR__, 2) . '/backend/src/bootstrap.php';
require dirname(__DIR__) . '/src/Csrf.php';
require dirname(__DIR__) . '/src/Admin.php';
require dirname(__DIR__) . '/src/GymAdmin.php';

use App\Repositories\AnnouncementRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\BannerRepository;
use App\Repositories\BranchRepository;
use App\Repositories\DietPlanRepository;
use App\Repositories\EventRepository;
use App\Repositories\FacilityRepository;
use App\Repositories\GovIdRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PackageRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TrainerRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkoutPlanRepository;
use App\Services\OneSignalService;
use App\Support\Database;
use App\Support\Media;
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
    $config = (new SettingRepository($pdo))->overlay($config);
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

    $parseWorkoutDays = static function (): array {
        $input = $_POST['days'] ?? [];
        $days = [];
        if (!is_array($input)) {
            return $days;
        }
        foreach ($input as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $title = trim((string) ($raw['title'] ?? ''));
            $notes = trim((string) ($raw['notes'] ?? ''));
            $exercises = [];
            $rawExercises = $raw['exercises'] ?? [];
            if (is_array($rawExercises)) {
                foreach ($rawExercises as $ex) {
                    if (!is_array($ex)) {
                        continue;
                    }
                    $name = trim((string) ($ex['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $exercises[] = [
                        'name' => $name,
                        'sets' => trim((string) ($ex['sets'] ?? '')),
                        'reps' => trim((string) ($ex['reps'] ?? '')),
                        'rest' => trim((string) ($ex['rest'] ?? '')),
                        'notes' => trim((string) ($ex['notes'] ?? '')),
                        'image' => trim((string) ($ex['image'] ?? '')),
                    ];
                }
            }
            if ($title === '' && $exercises === []) {
                continue;
            }
            $days[] = ['title' => $title, 'notes' => $notes, 'exercises' => $exercises];
        }
        return $days;
    };

    $parseDietMeals = static function (): array {
        $input = $_POST['meals'] ?? [];
        $meals = [];
        if (!is_array($input)) {
            return $meals;
        }
        foreach ($input as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $type = trim((string) ($raw['meal_type'] ?? ''));
            $items = trim((string) ($raw['items'] ?? ''));
            if ($type === '' && $items === '') {
                continue;
            }
            $calories = ($raw['calories'] ?? '') !== '' && is_numeric($raw['calories'])
                ? (int) $raw['calories']
                : null;
            $meals[] = [
                'meal_type' => $type !== '' ? $type : 'Meal',
                'items' => $items,
                'calories' => $calories,
                'time' => trim((string) ($raw['time'] ?? '')),
            ];
        }
        return $meals;
    };

    $workoutLevels = ['beginner', 'intermediate', 'advanced', 'all'];

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
        $attendance = (new AttendanceRepository($pdo))->forUser($memberId);
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

    // ---------- Workout plans ----------
    elseif ($method === 'POST' && preg_match('#^/workout-plans/(\d+)/(delete|toggle)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/workout-plans');
        }
        $id = (int) $params[1];
        $repo = new WorkoutPlanRepository($pdo);
        if ($params[2] === 'delete') {
            $repo->delete($id);
            Admin::flash('success', 'Workout plan deleted.');
        } else {
            $plan = $repo->find($id);
            $repo->setActive($id, (int) ($plan['is_active'] ?? 0) !== 1);
            Admin::flash('success', 'Workout plan status updated.');
        }
        Admin::redirect($base . '/workout-plans');
    } elseif ($method === 'POST' && preg_match('#^/workout-plans/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/workout-plans');
        }
        $id = (int) $params[1];
        $repo = new WorkoutPlanRepository($pdo);
        $existing = $repo->find($id);
        if (!$existing) {
            Admin::flash('danger', 'Workout plan not found.');
            Admin::redirect($base . '/workout-plans');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Plan title is required.');
            Admin::redirect($base . '/workout-plans/' . $id . '/edit');
        }
        $image = $existing['image'] ?? null;
        if (!empty($_POST['remove_image'])) {
            $image = null;
        }
        $stored = Admin::storeImage($_FILES['image'] ?? [], 'workouts', $config);
        if ($stored !== null) {
            $image = $stored;
        }
        $category = trim((string) ($_POST['category'] ?? ''));
        $repo->update($id, [
            'title' => $titleInput,
            'image' => $image,
            'category' => $category !== '' ? $category : null,
            'level' => in_array($_POST['level'] ?? '', $workoutLevels, true) ? (string) $_POST['level'] : 'all',
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $repo->replaceSchedule($id, $parseWorkoutDays());
        Admin::flash('success', 'Workout plan updated.');
        Admin::redirect($base . '/workout-plans');
    } elseif ($method === 'POST' && $path === '/workout-plans') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/workout-plans/new');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Plan title is required.');
            Admin::redirect($base . '/workout-plans/new');
        }
        $category = trim((string) ($_POST['category'] ?? ''));
        $repo = new WorkoutPlanRepository($pdo);
        $id = $repo->create([
            'title' => $titleInput,
            'image' => Admin::storeImage($_FILES['image'] ?? [], 'workouts', $config),
            'category' => $category !== '' ? $category : null,
            'level' => in_array($_POST['level'] ?? '', $workoutLevels, true) ? (string) $_POST['level'] : 'all',
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $repo->replaceSchedule($id, $parseWorkoutDays());
        Admin::flash('success', 'Workout plan created.');
        Admin::redirect($base . '/workout-plans');
    } elseif ($method === 'GET' && preg_match('#^/workout-plans/(\d+)/edit$#', $path, $params)) {
        $repo = new WorkoutPlanRepository($pdo);
        $plan = $repo->find((int) $params[1]);
        if (!$plan) {
            Admin::flash('danger', 'Workout plan not found.');
            Admin::redirect($base . '/workout-plans');
        }
        $days = $repo->schedule((int) $plan['id']);
        $imageUrl = Media::url($plan['image'] ?? null, (string) $config['base_url']);
        $title = 'Edit workout plan';
        $view = dirname(__DIR__) . '/views/workout_plan_form.php';
    } elseif ($method === 'GET' && $path === '/workout-plans/new') {
        $plan = null;
        $days = [];
        $imageUrl = null;
        $title = 'New workout plan';
        $view = dirname(__DIR__) . '/views/workout_plan_form.php';
    } elseif ($method === 'GET' && $path === '/workout-plans') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $repo = new WorkoutPlanRepository($pdo);
        $plans = $repo->all(false, null, $q !== '' ? $q : null, $perPage, ($page - 1) * $perPage);
        $total = $repo->countAll(false, null, $q !== '' ? $q : null);
        $search = $q;
        $pageNo = $page;
        $pageCount = max(1, (int) ceil($total / $perPage));
        $planTotal = $total;
        $title = 'Workout plans';
        $view = dirname(__DIR__) . '/views/workout_plans.php';
    }

    // ---------- Diet plans ----------
    elseif ($method === 'POST' && preg_match('#^/diet-plans/(\d+)/(delete|toggle)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/diet-plans');
        }
        $id = (int) $params[1];
        $repo = new DietPlanRepository($pdo);
        if ($params[2] === 'delete') {
            $repo->delete($id);
            Admin::flash('success', 'Diet plan deleted.');
        } else {
            $plan = $repo->find($id);
            $repo->setActive($id, (int) ($plan['is_active'] ?? 0) !== 1);
            Admin::flash('success', 'Diet plan status updated.');
        }
        Admin::redirect($base . '/diet-plans');
    } elseif ($method === 'POST' && preg_match('#^/diet-plans/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/diet-plans');
        }
        $id = (int) $params[1];
        $repo = new DietPlanRepository($pdo);
        $existing = $repo->find($id);
        if (!$existing) {
            Admin::flash('danger', 'Diet plan not found.');
            Admin::redirect($base . '/diet-plans');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Plan title is required.');
            Admin::redirect($base . '/diet-plans/' . $id . '/edit');
        }
        $image = $existing['image'] ?? null;
        if (!empty($_POST['remove_image'])) {
            $image = null;
        }
        $stored = Admin::storeImage($_FILES['image'] ?? [], 'diets', $config);
        if ($stored !== null) {
            $image = $stored;
        }
        $category = trim((string) ($_POST['category'] ?? ''));
        $calories = ($_POST['calories'] ?? '') !== '' && is_numeric($_POST['calories'])
            ? (int) $_POST['calories']
            : null;
        $repo->update($id, [
            'title' => $titleInput,
            'image' => $image,
            'category' => $category !== '' ? $category : null,
            'calories' => $calories,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $repo->replaceMeals($id, $parseDietMeals());
        Admin::flash('success', 'Diet plan updated.');
        Admin::redirect($base . '/diet-plans');
    } elseif ($method === 'POST' && $path === '/diet-plans') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/diet-plans/new');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Plan title is required.');
            Admin::redirect($base . '/diet-plans/new');
        }
        $category = trim((string) ($_POST['category'] ?? ''));
        $calories = ($_POST['calories'] ?? '') !== '' && is_numeric($_POST['calories'])
            ? (int) $_POST['calories']
            : null;
        $repo = new DietPlanRepository($pdo);
        $id = $repo->create([
            'title' => $titleInput,
            'image' => Admin::storeImage($_FILES['image'] ?? [], 'diets', $config),
            'category' => $category !== '' ? $category : null,
            'calories' => $calories,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $repo->replaceMeals($id, $parseDietMeals());
        Admin::flash('success', 'Diet plan created.');
        Admin::redirect($base . '/diet-plans');
    } elseif ($method === 'GET' && preg_match('#^/diet-plans/(\d+)/edit$#', $path, $params)) {
        $repo = new DietPlanRepository($pdo);
        $plan = $repo->find((int) $params[1]);
        if (!$plan) {
            Admin::flash('danger', 'Diet plan not found.');
            Admin::redirect($base . '/diet-plans');
        }
        $meals = $repo->mealsFor((int) $plan['id']);
        $imageUrl = Media::url($plan['image'] ?? null, (string) $config['base_url']);
        $title = 'Edit diet plan';
        $view = dirname(__DIR__) . '/views/diet_plan_form.php';
    } elseif ($method === 'GET' && $path === '/diet-plans/new') {
        $plan = null;
        $meals = [];
        $imageUrl = null;
        $title = 'New diet plan';
        $view = dirname(__DIR__) . '/views/diet_plan_form.php';
    } elseif ($method === 'GET' && $path === '/diet-plans') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $repo = new DietPlanRepository($pdo);
        $plans = $repo->all(false, null, $q !== '' ? $q : null, $perPage, ($page - 1) * $perPage);
        $total = $repo->countAll(false, null, $q !== '' ? $q : null);
        $search = $q;
        $pageNo = $page;
        $pageCount = max(1, (int) ceil($total / $perPage));
        $planTotal = $total;
        $title = 'Diet plans';
        $view = dirname(__DIR__) . '/views/diet_plans.php';
    }

    $crudToggleDelete = static function (
        string $prefix,
        object $repo,
        string $label
    ) use ($method, $path, $base): bool {
        if ($method !== 'POST' || !preg_match('#^' . $prefix . '/(\d+)/(delete|toggle)$#', $path, $m)) {
            return false;
        }
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . $prefix);
        }
        $id = (int) $m[1];
        if ($m[2] === 'delete') {
            $repo->delete($id);
            Admin::flash('success', $label . ' deleted.');
        } else {
            $row = $repo->find($id);
            $repo->setActive($id, (int) ($row['is_active'] ?? 0) !== 1);
            Admin::flash('success', $label . ' status updated.');
        }
        Admin::redirect($base . $prefix);
    };

    $branchRepo = new BranchRepository($pdo);
    $facilityRepo = new FacilityRepository($pdo);
    $trainerRepo = new TrainerRepository($pdo);
    $eventRepo = new EventRepository($pdo);
    $annRepo = new AnnouncementRepository($pdo);
    $bannerRepo = new BannerRepository($pdo);
    $attRepo = new AttendanceRepository($pdo);
    $branchOptions = $branchRepo->all(false);

    if ($crudToggleDelete('/branches', $branchRepo, 'Branch')) {
    } elseif ($method === 'POST' && preg_match('#^/branches/(\d+)/photos/(\d+)/delete$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
        } else {
            $branchRepo->deletePhoto((int) $params[2]);
            Admin::flash('success', 'Photo removed.');
        }
        Admin::redirect($base . '/branches/' . (int) $params[1] . '/edit');
    } elseif ($method === 'POST' && preg_match('#^/branches/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/branches');
        }
        $id = (int) $params[1];
        $data = GymAdmin::branchPayload();
        if ($data['name'] === '') {
            Admin::flash('danger', 'Branch name is required.');
            Admin::redirect($base . '/branches/' . $id . '/edit');
        }
        $branchRepo->update($id, $data);
        $branchRepo->replaceTimings($id, GymAdmin::parseTimings());
        $branchRepo->replaceFacilities($id, $_POST['facility_ids'] ?? []);
        $branchRepo->upsertParking($id, GymAdmin::parkingPayload());
        $stored = Admin::storeImage($_FILES['photo'] ?? [], 'branches', $config);
        if ($stored !== null) {
            $branchRepo->addPhoto($id, $stored);
        }
        Admin::flash('success', 'Branch updated.');
        Admin::redirect($base . '/branches');
    } elseif ($method === 'POST' && $path === '/branches') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/branches/new');
        }
        $data = GymAdmin::branchPayload();
        if ($data['name'] === '') {
            Admin::flash('danger', 'Branch name is required.');
            Admin::redirect($base . '/branches/new');
        }
        $id = $branchRepo->create($data);
        $branchRepo->replaceTimings($id, GymAdmin::parseTimings());
        $branchRepo->replaceFacilities($id, $_POST['facility_ids'] ?? []);
        $branchRepo->upsertParking($id, GymAdmin::parkingPayload());
        Admin::flash('success', 'Branch created.');
        Admin::redirect($base . '/branches/' . $id . '/edit');
    } elseif ($method === 'GET' && preg_match('#^/branches/(\d+)/edit$#', $path, $params)) {
        $branch = $branchRepo->find((int) $params[1]);
        if (!$branch) {
            Admin::flash('danger', 'Branch not found.');
            Admin::redirect($base . '/branches');
        }
        $timings = $branchRepo->timings((int) $branch['id']);
        $photos = $branchRepo->photos((int) $branch['id']);
        $facilities = $facilityRepo->all();
        $selectedFacilities = $branchRepo->facilityIds((int) $branch['id']);
        $parking = $branchRepo->parking((int) $branch['id']);
        $title = 'Edit branch';
        $view = dirname(__DIR__) . '/views/branch_form.php';
    } elseif ($method === 'GET' && $path === '/branches/new') {
        $branch = null;
        $timings = [];
        $photos = [];
        $facilities = $facilityRepo->all();
        $selectedFacilities = [];
        $parking = null;
        $title = 'New branch';
        $view = dirname(__DIR__) . '/views/branch_form.php';
    } elseif ($method === 'GET' && $path === '/branches') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $branches = $branchRepo->all(false, $q !== '' ? $q : null);
        $search = $q;
        $title = 'Branches';
        $view = dirname(__DIR__) . '/views/branches.php';
    } elseif ($method === 'POST' && preg_match('#^/facilities/(\d+)/delete$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
        } else {
            $facilityRepo->delete((int) $params[1]);
            Admin::flash('success', 'Facility deleted.');
        }
        Admin::redirect($base . '/facilities');
    } elseif ($method === 'POST' && preg_match('#^/facilities/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/facilities');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Admin::flash('danger', 'Facility name is required.');
            Admin::redirect($base . '/facilities/' . (int) $params[1] . '/edit');
        }
        $facilityRepo->update((int) $params[1], [
            'name' => $name,
            'icon' => trim((string) ($_POST['icon'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Facility updated.');
        Admin::redirect($base . '/facilities');
    } elseif ($method === 'POST' && $path === '/facilities') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/facilities/new');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Admin::flash('danger', 'Facility name is required.');
            Admin::redirect($base . '/facilities/new');
        }
        $facilityRepo->create([
            'name' => $name,
            'icon' => trim((string) ($_POST['icon'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Facility created.');
        Admin::redirect($base . '/facilities');
    } elseif ($method === 'GET' && preg_match('#^/facilities/(\d+)/edit$#', $path, $params)) {
        $facility = $facilityRepo->find((int) $params[1]);
        if (!$facility) {
            Admin::flash('danger', 'Facility not found.');
            Admin::redirect($base . '/facilities');
        }
        $title = 'Edit facility';
        $view = dirname(__DIR__) . '/views/facility_form.php';
    } elseif ($method === 'GET' && $path === '/facilities/new') {
        $facility = null;
        $title = 'New facility';
        $view = dirname(__DIR__) . '/views/facility_form.php';
    } elseif ($method === 'GET' && $path === '/facilities') {
        $facilities = $facilityRepo->all();
        $title = 'Facilities';
        $view = dirname(__DIR__) . '/views/facilities.php';
    } elseif ($crudToggleDelete('/trainers', $trainerRepo, 'Trainer')) {
    } elseif ($method === 'POST' && preg_match('#^/trainers/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/trainers');
        }
        $id = (int) $params[1];
        $existing = $trainerRepo->find($id);
        if (!$existing) {
            Admin::flash('danger', 'Trainer not found.');
            Admin::redirect($base . '/trainers');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Admin::flash('danger', 'Trainer name is required.');
            Admin::redirect($base . '/trainers/' . $id . '/edit');
        }
        $image = $existing['image'] ?? null;
        if (!empty($_POST['remove_image'])) {
            $image = null;
        }
        $stored = Admin::storeImage($_FILES['image'] ?? [], 'trainers', $config);
        if ($stored !== null) {
            $image = $stored;
        }
        $trainerRepo->update($id, [
            'branch_id' => GymAdmin::nullableBranchId(),
            'name' => $name,
            'image' => $image,
            'role' => trim((string) ($_POST['role'] ?? '')) ?: null,
            'bio' => trim((string) ($_POST['bio'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $trainerRepo->replaceCertifications($id, GymAdmin::parseCerts());
        Admin::flash('success', 'Trainer updated.');
        Admin::redirect($base . '/trainers');
    } elseif ($method === 'POST' && $path === '/trainers') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/trainers/new');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Admin::flash('danger', 'Trainer name is required.');
            Admin::redirect($base . '/trainers/new');
        }
        $id = $trainerRepo->create([
            'branch_id' => GymAdmin::nullableBranchId(),
            'name' => $name,
            'image' => Admin::storeImage($_FILES['image'] ?? [], 'trainers', $config),
            'role' => trim((string) ($_POST['role'] ?? '')) ?: null,
            'bio' => trim((string) ($_POST['bio'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        $trainerRepo->replaceCertifications($id, GymAdmin::parseCerts());
        Admin::flash('success', 'Trainer created.');
        Admin::redirect($base . '/trainers');
    } elseif ($method === 'GET' && preg_match('#^/trainers/(\d+)/edit$#', $path, $params)) {
        $trainer = $trainerRepo->find((int) $params[1]);
        if (!$trainer) {
            Admin::flash('danger', 'Trainer not found.');
            Admin::redirect($base . '/trainers');
        }
        $certs = $trainerRepo->certifications((int) $trainer['id']);
        $imageUrl = Media::url($trainer['image'] ?? null, (string) $config['base_url']);
        $branches = $branchOptions;
        $title = 'Edit trainer';
        $view = dirname(__DIR__) . '/views/trainer_form.php';
    } elseif ($method === 'GET' && $path === '/trainers/new') {
        $trainer = null;
        $certs = [];
        $imageUrl = null;
        $branches = $branchOptions;
        $title = 'New trainer';
        $view = dirname(__DIR__) . '/views/trainer_form.php';
    } elseif ($method === 'GET' && $path === '/trainers') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $trainers = $trainerRepo->all(false, null, $q !== '' ? $q : null);
        $search = $q;
        $title = 'Trainers';
        $view = dirname(__DIR__) . '/views/trainers.php';
    } elseif ($crudToggleDelete('/events', $eventRepo, 'Event')) {
    } elseif ($method === 'POST' && preg_match('#^/events/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/events');
        }
        $id = (int) $params[1];
        $existing = $eventRepo->find($id);
        if (!$existing) {
            Admin::flash('danger', 'Event not found.');
            Admin::redirect($base . '/events');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Event title is required.');
            Admin::redirect($base . '/events/' . $id . '/edit');
        }
        $image = $existing['image'] ?? null;
        if (!empty($_POST['remove_image'])) {
            $image = null;
        }
        $stored = Admin::storeImage($_FILES['image'] ?? [], 'events', $config);
        if ($stored !== null) {
            $image = $stored;
        }
        $eventRepo->update($id, [
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => $titleInput,
            'image' => $image,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'location' => trim((string) ($_POST['location'] ?? '')) ?: null,
            'starts_at' => GymAdmin::datetimeOrNull('starts_at'),
            'ends_at' => GymAdmin::datetimeOrNull('ends_at'),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Event updated.');
        Admin::redirect($base . '/events');
    } elseif ($method === 'POST' && $path === '/events') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/events/new');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Event title is required.');
            Admin::redirect($base . '/events/new');
        }
        $eventRepo->create([
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => $titleInput,
            'image' => Admin::storeImage($_FILES['image'] ?? [], 'events', $config),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'location' => trim((string) ($_POST['location'] ?? '')) ?: null,
            'starts_at' => GymAdmin::datetimeOrNull('starts_at'),
            'ends_at' => GymAdmin::datetimeOrNull('ends_at'),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Event created.');
        Admin::redirect($base . '/events');
    } elseif ($method === 'GET' && preg_match('#^/events/(\d+)/edit$#', $path, $params)) {
        $event = $eventRepo->find((int) $params[1]);
        if (!$event) {
            Admin::flash('danger', 'Event not found.');
            Admin::redirect($base . '/events');
        }
        $imageUrl = Media::url($event['image'] ?? null, (string) $config['base_url']);
        $branches = $branchOptions;
        $title = 'Edit event';
        $view = dirname(__DIR__) . '/views/event_form.php';
    } elseif ($method === 'GET' && $path === '/events/new') {
        $event = null;
        $imageUrl = null;
        $branches = $branchOptions;
        $title = 'New event';
        $view = dirname(__DIR__) . '/views/event_form.php';
    } elseif ($method === 'GET' && $path === '/events') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $events = $eventRepo->all(false, null, $q !== '' ? $q : null);
        $search = $q;
        $title = 'Events';
        $view = dirname(__DIR__) . '/views/events.php';
    } elseif ($crudToggleDelete('/announcements', $annRepo, 'Announcement')) {
    } elseif ($method === 'POST' && preg_match('#^/announcements/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/announcements');
        }
        $id = (int) $params[1];
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Title is required.');
            Admin::redirect($base . '/announcements/' . $id . '/edit');
        }
        $annRepo->update($id, [
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => $titleInput,
            'body' => trim((string) ($_POST['body'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
            'published_at' => GymAdmin::datetimeOrNull('published_at') ?? date('Y-m-d H:i:s'),
        ]);
        Admin::flash('success', 'Announcement updated.');
        Admin::redirect($base . '/announcements');
    } elseif ($method === 'POST' && $path === '/announcements') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/announcements/new');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        if ($titleInput === '') {
            Admin::flash('danger', 'Title is required.');
            Admin::redirect($base . '/announcements/new');
        }
        $annRepo->create([
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => $titleInput,
            'body' => trim((string) ($_POST['body'] ?? '')) ?: null,
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
            'published_at' => GymAdmin::datetimeOrNull('published_at') ?? date('Y-m-d H:i:s'),
        ]);
        Admin::flash('success', 'Announcement created.');
        Admin::redirect($base . '/announcements');
    } elseif ($method === 'GET' && preg_match('#^/announcements/(\d+)/edit$#', $path, $params)) {
        $announcement = $annRepo->find((int) $params[1]);
        if (!$announcement) {
            Admin::flash('danger', 'Announcement not found.');
            Admin::redirect($base . '/announcements');
        }
        $branches = $branchOptions;
        $title = 'Edit announcement';
        $view = dirname(__DIR__) . '/views/announcement_form.php';
    } elseif ($method === 'GET' && $path === '/announcements/new') {
        $announcement = null;
        $branches = $branchOptions;
        $title = 'New announcement';
        $view = dirname(__DIR__) . '/views/announcement_form.php';
    } elseif ($method === 'GET' && $path === '/announcements') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $announcements = $annRepo->all(false, null, $q !== '' ? $q : null);
        $search = $q;
        $title = 'Announcements';
        $view = dirname(__DIR__) . '/views/announcements.php';
    } elseif ($crudToggleDelete('/banners', $bannerRepo, 'Banner')) {
    } elseif ($method === 'POST' && preg_match('#^/banners/(\d+)$#', $path, $params)) {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/banners');
        }
        $id = (int) $params[1];
        $existing = $bannerRepo->find($id);
        if (!$existing) {
            Admin::flash('danger', 'Banner not found.');
            Admin::redirect($base . '/banners');
        }
        $image = $existing['image'] ?? null;
        $stored = Admin::storeImage($_FILES['image'] ?? [], 'banners', $config);
        if ($stored !== null) {
            $image = $stored;
        }
        if (!$image) {
            Admin::flash('danger', 'Banner image is required.');
            Admin::redirect($base . '/banners/' . $id . '/edit');
        }
        $bannerRepo->update($id, [
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => trim((string) ($_POST['title'] ?? '')) ?: null,
            'image' => $image,
            'link_type' => trim((string) ($_POST['link_type'] ?? '')) ?: null,
            'link_value' => trim((string) ($_POST['link_value'] ?? '')) ?: null,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Banner updated.');
        Admin::redirect($base . '/banners');
    } elseif ($method === 'POST' && $path === '/banners') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/banners/new');
        }
        $image = Admin::storeImage($_FILES['image'] ?? [], 'banners', $config);
        if (!$image) {
            Admin::flash('danger', 'Banner image is required.');
            Admin::redirect($base . '/banners/new');
        }
        $bannerRepo->create([
            'branch_id' => GymAdmin::nullableBranchId(),
            'title' => trim((string) ($_POST['title'] ?? '')) ?: null,
            'image' => $image,
            'link_type' => trim((string) ($_POST['link_type'] ?? '')) ?: null,
            'link_value' => trim((string) ($_POST['link_value'] ?? '')) ?: null,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'is_active' => (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
        ]);
        Admin::flash('success', 'Banner created.');
        Admin::redirect($base . '/banners');
    } elseif ($method === 'GET' && preg_match('#^/banners/(\d+)/edit$#', $path, $params)) {
        $banner = $bannerRepo->find((int) $params[1]);
        if (!$banner) {
            Admin::flash('danger', 'Banner not found.');
            Admin::redirect($base . '/banners');
        }
        $imageUrl = Media::url($banner['image'] ?? null, (string) $config['base_url']);
        $branches = $branchOptions;
        $title = 'Edit banner';
        $view = dirname(__DIR__) . '/views/banner_form.php';
    } elseif ($method === 'GET' && $path === '/banners/new') {
        $banner = null;
        $imageUrl = null;
        $branches = $branchOptions;
        $title = 'New banner';
        $view = dirname(__DIR__) . '/views/banner_form.php';
    } elseif ($method === 'GET' && $path === '/banners') {
        $banners = $bannerRepo->all(false);
        $title = 'Banners';
        $view = dirname(__DIR__) . '/views/banners.php';
    } elseif ($method === 'POST' && $path === '/attendance') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/attendance');
        }
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId < 1) {
            Admin::flash('danger', 'Select a member.');
            Admin::redirect($base . '/attendance');
        }
        $checkIn = GymAdmin::datetimeOrNull('check_in') ?? date('Y-m-d H:i:s');
        $attRepo->create([
            'user_id' => $userId,
            'branch_id' => GymAdmin::nullableBranchId(),
            'check_in' => $checkIn,
            'check_out' => GymAdmin::datetimeOrNull('check_out'),
            'source' => 'manual',
        ]);
        Admin::flash('success', 'Attendance recorded.');
        Admin::redirect($base . '/attendance');
    } elseif ($method === 'GET' && $path === '/attendance') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $branchFilter = ($_GET['branch_id'] ?? '') !== '' ? (int) $_GET['branch_id'] : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $records = $attRepo->adminList(null, $branchFilter, $q !== '' ? $q : null, $perPage, ($page - 1) * $perPage);
        $total = $attRepo->adminCount(null, $branchFilter, $q !== '' ? $q : null);
        $search = $q;
        $pageNo = $page;
        $pageCount = max(1, (int) ceil($total / $perPage));
        $attTotal = $total;
        $branches = $branchOptions;
        $members = $pdo->query('SELECT id, member_id, name, mobile FROM users ORDER BY id DESC LIMIT 200')->fetchAll();
        $title = 'Attendance';
        $view = dirname(__DIR__) . '/views/attendance.php';
    } elseif ($method === 'POST' && $path === '/notifications') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/notifications');
        }
        $titleInput = trim((string) ($_POST['title'] ?? ''));
        $bodyInput = trim((string) ($_POST['body'] ?? ''));
        if ($titleInput === '' || $bodyInput === '') {
            Admin::flash('danger', 'Title and body are required.');
            Admin::redirect($base . '/notifications');
        }
        $audience = (string) ($_POST['audience'] ?? 'all');
        if (!in_array($audience, ['all', 'member', 'branch'], true)) {
            $audience = 'all';
        }
        $type = (string) ($_POST['type'] ?? 'general');
        if (!in_array($type, ['general', 'membership', 'event', 'announcement'], true)) {
            $type = 'general';
        }
        $userId = (int) ($_POST['user_id'] ?? 0);
        $branchId = (int) ($_POST['branch_id'] ?? 0);
        $linkValue = trim((string) ($_POST['link_value'] ?? ''));
        $data = ['type' => $type];
        if ($linkValue !== '') {
            $data['id'] = $linkValue;
        }
        $image = Admin::storeImage($_FILES['image'] ?? [], 'notifications', $config);
        $imageUrl = $image ? Media::url($image, (string) $config['base_url']) : null;
        $externalIds = [];
        if ($audience === 'member') {
            if ($userId < 1) {
                Admin::flash('danger', 'Select a member.');
                Admin::redirect($base . '/notifications');
            }
            $member = (new UserRepository($pdo))->findById($userId);
            if (!$member) {
                Admin::flash('danger', 'Member not found.');
                Admin::redirect($base . '/notifications');
            }
            $externalIds[] = (string) $member['member_id'];
        }
        if ($audience === 'branch' && $branchId < 1) {
            Admin::flash('danger', 'Select a branch.');
            Admin::redirect($base . '/notifications');
        }
        $push = new OneSignalService($config, $pdo);
        $result = $push->dispatch(
            audience: $audience,
            title: $titleInput,
            body: $bodyInput,
            data: $data,
            userId: $audience === 'member' ? $userId : null,
            branchId: $audience === 'branch' ? $branchId : null,
            image: $image,
            imageUrl: $imageUrl,
            externalIds: $externalIds,
            type: $type,
        );
        if (!empty($result['ok'])) {
            Admin::flash('success', 'Notification sent.');
        } else {
            Admin::flash('danger', 'Saved to log, but OneSignal failed: ' . ($result['error'] ?? 'check settings'));
        }
        Admin::redirect($base . '/notifications');
    } elseif ($method === 'GET' && $path === '/notifications') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $notifRepo = new NotificationRepository($pdo);
        $notifications = $notifRepo->adminList($q !== '' ? $q : null, $perPage, ($page - 1) * $perPage);
        $total = $notifRepo->adminCount($q !== '' ? $q : null);
        $search = $q;
        $pageNo = $page;
        $pageCount = max(1, (int) ceil($total / $perPage));
        $notifTotal = $total;
        $members = $pdo->query('SELECT id, member_id, name, mobile FROM users ORDER BY id DESC LIMIT 300')->fetchAll();
        $branches = $branchOptions;
        $title = 'Notifications';
        $view = dirname(__DIR__) . '/views/notifications.php';
    } elseif ($method === 'POST' && $path === '/settings') {
        if (!Csrf::check()) {
            Admin::flash('danger', 'Invalid session. Try again.');
            Admin::redirect($base . '/settings');
        }
        $settingRepo = new SettingRepository($pdo);
        $keys = [
            'app_name',
            'contact_phone',
            'contact_whatsapp',
            'contact_email',
            'onesignal_app_id',
            'onesignal_rest_api_key',
            'otp_provider',
            'otp_apitxt_base_url',
            'otp_apitxt_api_key',
            'otp_apitxt_sender',
            'terms_url',
            'privacy_url',
        ];
        foreach ($keys as $key) {
            $settingRepo->set($key, trim((string) ($_POST[$key] ?? '')));
        }
        Admin::flash('success', 'Settings saved.');
        Admin::redirect($base . '/settings');
    } elseif ($method === 'GET' && $path === '/settings') {
        $settings = (new SettingRepository($pdo))->all();
        $title = 'Settings';
        $view = dirname(__DIR__) . '/views/settings.php';
    }
}

$flashes = Admin::takeFlashes();
require dirname(__DIR__) . '/views/layout.php';
