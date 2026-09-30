<?php

namespace App\Repositories;

use PDO;

final class FacilityRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM facilities';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM facilities WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO facilities (name, icon, is_active) VALUES (?, ?, ?)');
        $stmt->execute([$data['name'], $data['icon'], $data['is_active']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE facilities SET name = ?, icon = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['icon'], $data['is_active'], $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM branch_facilities WHERE facility_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM facilities WHERE id = ?')->execute([$id]);
    }
}
