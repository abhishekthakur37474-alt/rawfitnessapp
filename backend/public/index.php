<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DeviceController;
use App\Controllers\ProfileController;
use App\Middleware\JwtAuth;
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
} catch (\Throwable $e) {
    if ($method === 'GET' && $path === '/health') {
        Json::fail('Database unavailable', 503, ['error' => $e->getMessage()]);
    }
    Json::fail('Database unavailable', 503);
}

if ($method === 'GET' && $path === '/health') {
    Json::ok('Raw Fitness API ready', [
        'app' => $config['app_name'],
        'phase' => 1,
        'time' => date('c'),
    ]);
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

Json::fail('Not found', 404);
