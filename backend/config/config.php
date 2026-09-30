<?php

return [
    'app_name' => 'Raw Fitness',
    'env' => 'production',
    'debug' => false,
    'timezone' => 'Asia/Kolkata',
    'base_url' => rtrim(getenv('APP_BASE_URL') ?: 'https://demoabhishek.ifree.page', '/'),
    'cors_origins' => ['*'],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'rawfitness-jwt-change-in-prod-2026',
        'algo' => 'HS256',
        'ttl_seconds' => 60 * 60 * 24 * 30,
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: 'sql312.infinityfree.com',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'if0_43051926_rawfitness',
        'user' => getenv('DB_USER') ?: 'if0_43051926',
        'pass' => getenv('DB_PASS') ?: 'nyJsXdI5NyN',
        'charset' => 'utf8mb4',
    ],
    'otp' => [
        'provider' => 'apitxt',
        'expiry_minutes' => 5,
        'rate_limit_per_hour' => 5,
        'length' => 6,
        'dev_return_otp' => true,
        'apitxt' => [
            'base_url' => getenv('APITXT_BASE_URL') ?: '',
            'api_key' => getenv('APITXT_API_KEY') ?: '',
            'sender' => getenv('APITXT_SENDER') ?: 'RAWFIT',
        ],
    ],
    'onesignal' => [
        'app_id' => getenv('ONESIGNAL_APP_ID') ?: '',
        'rest_api_key' => getenv('ONESIGNAL_REST_API_KEY') ?: '',
        'api_url' => 'https://api.onesignal.com/notifications',
    ],
    'uploads' => [
        'dir' => dirname(__DIR__) . '/public/uploads',
        'public_path' => '/uploads',
        'max_bytes' => 5 * 1024 * 1024,
        'allowed_mime' => ['image/jpeg', 'image/png'],
        'allowed_ext' => ['jpg', 'jpeg', 'png'],
    ],
    'admin' => [
        'seed_email' => 'admin@rawfitness.local',
        'seed_password' => 'Admin@123',
        'seed_name' => 'Gym Admin',
    ],
];
