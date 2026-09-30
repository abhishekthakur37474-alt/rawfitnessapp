<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\BranchController;
use App\Controllers\DeviceController;
use App\Controllers\DietPlanController;
use App\Controllers\GymFeedController;
use App\Controllers\MembershipController;
use App\Controllers\NotificationController;
use App\Controllers\PackageController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Controllers\TrainerController;
use App\Controllers\WorkoutPlanController;
use App\Middleware\JwtAuth;
use App\Repositories\SettingRepository;
use App\Services\ExpiryReminderService;
use App\Services\OneSignalService;
use App\Services\OtpService;
use App\Support\Cors;
use App\Support\Database;
use App\Support\Http;
use App\Support\Json;
use App\Support\Schema;

Cors::apply($config['cors_origins']);

$method = Http::method();
$path = Http::path();
$override = strtoupper((string) (
    $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
    ?? $_POST['_method']
    ?? ''
));
if ($method === 'POST' && $override === 'PUT') {
    $method = 'PUT';
}
$jwt = new JwtAuth($config['jwt']);

try {
    $pdo = Database::pdo($config['db']);
    Schema::migrate($pdo, $config);
    $config = (new SettingRepository($pdo))->overlay($config);
} catch (\Throwable $e) {
    if ($method === 'GET' && $path === '/health') {
        Json::fail('Database unavailable', 503, ['error' => $e->getMessage()]);
    }
    Json::fail('Database unavailable', 503);
}

if ($method === 'GET' && $path === '/health') {
    Json::ok('Raw Fitness API ready', [
        'app' => $config['app_name'],
        'phase' => 5,
        'time' => date('c'),
    ]);
}

if ($method === 'GET' && $path === '/cron/expiry-reminders') {
    $given = (string) ($_GET['key'] ?? '');
    $expected = (string) ($config['cron_key'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        Json::fail('Forbidden', 403);
    }
    $push = new OneSignalService($config, $pdo);
    $result = (new ExpiryReminderService($pdo, $push))->run();
    Json::ok('Reminders processed', $result);
}

$otp = new OtpService($config['otp']);
$auth = new AuthController($pdo, $config, $jwt, $otp);

if ($method === 'POST' && $path === '/auth/send-otp') {
    $auth->sendOtp();
}
if ($method === 'POST' && $path === '/auth/verify-otp') {
    $auth->verifyOtp();
}

$claims = $jwt->user();
$userId = (int) ($claims['sub'] ?? 0);
if ($userId < 1) {
    Json::fail('Unauthorized', 401);
}

$profile = new ProfileController($pdo, $config, $userId);
$device = new DeviceController($pdo, $userId);

if ($method === 'GET' && $path === '/profile') {
    $profile->show();
}
if (($method === 'PUT' || $method === 'POST') && ($path === '/profile' || $path === '/profile/update')) {
    $profile->update();
}
if ($method === 'POST' && $path === '/profile/onboarding') {
    $profile->onboarding();
}
if ($method === 'GET' && $path === '/profile/gov-id') {
    $profile->govIdShow();
}
if ($method === 'POST' && $path === '/profile/gov-id') {
    $profile->govIdStore();
}
if ($method === 'POST' && $path === '/device/onesignal-subscription') {
    $device->saveOneSignal();
}

$packages = new PackageController($pdo);
$membership = new MembershipController($pdo, $config, $userId);
$payments = new PaymentController($pdo, $config, $userId);
$params = [];

if ($method === 'GET' && $path === '/packages') {
    $packages->index();
}
if ($method === 'GET' && $path === '/membership/current') {
    $membership->current();
}
if ($method === 'GET' && $path === '/membership/history') {
    $membership->history();
}
if ($method === 'POST' && $path === '/membership/renew') {
    $membership->renew();
}
if ($method === 'GET' && $path === '/payments/history') {
    $payments->history();
}
if ($method === 'POST' && $path === '/payments/initiate') {
    $payments->initiate();
}
if ($method === 'GET' && Http::match('/receipts/{id}', $path, $params)) {
    $payments->receipt((int) $params['id']);
}

$workoutPlans = new WorkoutPlanController($pdo, $config);
$dietPlans = new DietPlanController($pdo, $config);

if ($method === 'GET' && $path === '/workout-plans') {
    $workoutPlans->index();
}
if ($method === 'GET' && Http::match('/workout-plans/{id}', $path, $params)) {
    $workoutPlans->show((int) $params['id']);
}
if ($method === 'GET' && $path === '/diet-plans') {
    $dietPlans->index();
}
if ($method === 'GET' && Http::match('/diet-plans/{id}', $path, $params)) {
    $dietPlans->show((int) $params['id']);
}

$branches = new BranchController($pdo, $config);
$trainers = new TrainerController($pdo, $config);
$feed = new GymFeedController($pdo, $config);
$attendance = new AttendanceController($pdo, $config, $userId);

if ($method === 'GET' && $path === '/branches') {
    $branches->index();
}
if ($method === 'GET' && Http::match('/branches/{id}', $path, $params)) {
    $branches->show((int) $params['id']);
}
if ($method === 'GET' && $path === '/trainers') {
    $trainers->index();
}
if ($method === 'GET' && Http::match('/trainers/{id}', $path, $params)) {
    $trainers->show((int) $params['id']);
}
if ($method === 'GET' && $path === '/events') {
    $feed->events();
}
if ($method === 'GET' && $path === '/announcements') {
    $feed->announcements();
}
if ($method === 'GET' && $path === '/banners') {
    $feed->banners();
}
if ($method === 'GET' && $path === '/attendance') {
    $attendance->history();
}
if ($method === 'POST' && $path === '/attendance/checkin') {
    $attendance->checkin();
}

$notifications = new NotificationController($pdo, $config, $userId);

if ($method === 'GET' && $path === '/notifications') {
    $notifications->index();
}
if ($method === 'GET' && $path === '/notifications/unread-count') {
    $notifications->unreadCount();
}
if ($method === 'POST' && $path === '/notifications/read-all') {
    $notifications->markAllRead();
}
if ($method === 'POST' && Http::match('/notifications/{id}/read', $path, $params)) {
    $notifications->markRead((int) $params['id']);
}

Json::fail('Not found', 404);
