<?php

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByMobile(string $mobile): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE mobile = ? LIMIT 1');
        $stmt->execute([$mobile]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $mobile): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (member_id, mobile, is_onboarded) VALUES (?, ?, 0)'
            );
            $tmp = 'TMP' . bin2hex(random_bytes(4));
            $stmt->execute([$tmp, $mobile]);
            $id = (int) $this->pdo->lastInsertId();
            $memberId = sprintf('GYM%06d', $id);
            $upd = $this->pdo->prepare('UPDATE users SET member_id = ? WHERE id = ?');
            $upd->execute([$memberId, $id]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $existing = $this->findByMobile($mobile);
            if ($existing) {
                return $existing;
            }
            throw $e;
        }
        return $this->findById($id) ?? [];
    }

    public function updateProfile(int $id, array $fields): void
    {
        $allowed = ['name', 'gender', 'height_cm', 'is_onboarded'];
        $sets = [];
        $vals = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            $sets[] = "{$k} = ?";
            $vals[] = $v;
        }
        if ($sets === []) {
            return;
        }
        $vals[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
    }

    public function publicUser(array $row, ?array $gov = null): array
    {
        return [
            'id' => (int) $row['id'],
            'member_id' => $row['member_id'],
            'mobile' => $row['mobile'],
            'name' => $row['name'],
            'gender' => $row['gender'],
            'height_cm' => $row['height_cm'] !== null ? (float) $row['height_cm'] : null,
            'is_onboarded' => (int) $row['is_onboarded'] === 1,
            'gov_id_status' => $gov['status'] ?? null,
            'gov_id' => $gov,
        ];
    }
}
