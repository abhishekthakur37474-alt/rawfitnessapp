<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require dirname(__DIR__, 2) . '/backend/src/bootstrap.php';
require dirname(__DIR__) . '/src/Csrf.php';

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
$memberCount = 0;

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
    header('Location: ' . $base . '/login');
    exit;
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
            header('Location: ' . $base . '/');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

$loggedIn = !empty($_SESSION['admin_id']);

if (!$loggedIn && $path !== '/login') {
    header('Location: ' . $base . '/login');
    exit;
}

if ($loggedIn && $path === '/login') {
    header('Location: ' . $base . '/');
    exit;
}

if ($loggedIn && $pdo) {
    $memberCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

$title = 'Dashboard';
$view = dirname(__DIR__) . '/views/dashboard.php';

if ($path === '/login') {
    $title = 'Admin Login';
    $view = dirname(__DIR__) . '/views/login.php';
}

require dirname(__DIR__) . '/views/layout.php';
