<?php

namespace App\Repositories;

use PDO;

final class OneSignalRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function upsert(int $userId, string $subscriptionId, string $platform): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO onesignal_subscriptions (user_id, subscription_id, platform)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), updated_at = NOW()'
        );
        $stmt->execute([$userId, $subscriptionId, $platform]);
    }
}
