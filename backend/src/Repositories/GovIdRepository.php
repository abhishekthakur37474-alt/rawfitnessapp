<?php

namespace App\Repositories;

use PDO;

final class GovIdRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function latestForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_gov_ids WHERE user_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $userId, string $type, string $number, string $imagePath): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_gov_ids (user_id, type, id_number, image_path, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $number, $imagePath, 'pending']);
        return $this->latestForUser($userId) ?? [];
    }

    public function public(array $row, string $baseUrl): array
    {
        return [
            'type' => $row['type'],
            'id_number' => $row['id_number'],
            'status' => $row['status'],
            'reason' => $row['reason'],
            'image_url' => $baseUrl . $row['image_path'],
        ];
    }

    public function setStatus(int $id, string $status, ?string $reason = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE user_gov_ids SET status = ?, reason = ? WHERE id = ?');
        $stmt->execute([$status, $reason, $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_gov_ids WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
