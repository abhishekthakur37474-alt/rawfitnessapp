<?php

namespace App\Repositories;

use PDO;

final class PackageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?int $branchId = null): array
    {
        $sql = 'SELECT * FROM packages';
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }
        if ($branchId !== null) {
            $where[] = '(branch_id = ? OR branch_id IS NULL)';
            $vals[] = $branchId;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY duration_days ASC, price ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return array_map(fn (array $r) => $this->public($r), $stmt->fetchAll());
    }

    public function search(?string $term, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT p.*, b.name AS branch_name FROM packages p
                LEFT JOIN branches b ON b.id = p.branch_id';
        $vals = [];
        if ($term !== null && $term !== '') {
            $sql .= ' WHERE p.name LIKE ?';
            $vals[] = '%' . $term . '%';
        }
        $sql .= ' ORDER BY p.is_active DESC, p.duration_days ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function countAll(?string $term = null): int
    {
        $sql = 'SELECT COUNT(*) FROM packages';
        $vals = [];
        if ($term !== null && $term !== '') {
            $sql .= ' WHERE name LIKE ?';
            $vals[] = '%' . $term . '%';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM packages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO packages (name, duration_days, price, discount, description, branch_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['duration_days'],
            $data['price'],
            $data['discount'],
            $data['description'],
            $data['branch_id'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE packages SET name = ?, duration_days = ?, price = ?, discount = ?,
             description = ?, branch_id = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['duration_days'],
            $data['price'],
            $data['discount'],
            $data['description'],
            $data['branch_id'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM packages WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE packages SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public function public(array $row): array
    {
        $price = (float) $row['price'];
        $discount = (float) $row['discount'];
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'duration_days' => (int) $row['duration_days'],
            'price' => $price,
            'discount' => $discount,
            'final_price' => max(0.0, $price - $discount),
            'description' => $row['description'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }
}
