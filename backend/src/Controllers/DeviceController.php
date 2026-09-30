<?php

namespace App\Controllers;

use App\Repositories\OneSignalRepository;
use App\Support\Json;
use PDO;

final class DeviceController
{
    public function __construct(private PDO $pdo, private int $userId)
    {
    }

    public function saveOneSignal(): never
    {
        $body = Json::body();
        $sub = trim((string) ($body['subscription_id'] ?? ''));
        $platform = trim((string) ($body['platform'] ?? 'flutter'));
        if ($sub === '') {
            Json::fail('subscription_id is required', 422);
        }
        (new OneSignalRepository($this->pdo))->upsert($this->userId, $sub, $platform !== '' ? $platform : 'flutter');
        Json::ok('Subscription saved');
    }
}
