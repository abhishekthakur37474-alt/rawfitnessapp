<?php

namespace App\Repositories;

use PDO;

final class OtpRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countLastHour(string $mobile): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM otp_requests WHERE mobile = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)'
        );
        $stmt->execute([$mobile]);
        return (int) $stmt->fetchColumn();
    }

    public function create(string $mobile, string $hash, int $expiryMinutes, string $ip): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO otp_requests (mobile, otp_hash, expires_at, ip_address) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)'
        );
        $stmt->execute([$mobile, $hash, $expiryMinutes, $ip]);
        return (int) $this->pdo->lastInsertId();
    }

    public function latestActive(string $mobile): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM otp_requests
             WHERE mobile = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$mobile]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function consume(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE otp_requests SET consumed_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
