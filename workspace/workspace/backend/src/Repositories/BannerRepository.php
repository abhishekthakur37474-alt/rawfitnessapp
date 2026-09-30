<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class BannerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?int $branchId = null): array
    {
        $sql = 'SELECT bn.*, b.name AS branch_name FROM banners bn
                LEFT JOIN branches b ON b.id = bn.branch_id';
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'bn.is_active = 1';
        }
        if ($branchId !== null) {
            $where[] = '(bn.branch_id = ? OR bn.branch_id IS NULL)';
            $vals[] = $branchId;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY bn.sort_order ASC, bn.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM banners WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO banners (branch_id, title, image, link_type, link_value, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['image'],
            $data['link_type'],
            $data['link_value'],
            $data['sort_order'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE banners SET branch_id = ?, title = ?, image = ?, link_type = ?, link_value = ?,
             sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['image'],
            $data['link_type'],
            $data['link_value'],
            $data['sort_order'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE banners SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public function public(array $row, string $baseUrl): array
    {
        return [
            'id' => (int) $row['id'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'title' => $row['title'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'link_type' => $row['link_type'],
            'link_value' => $row['link_value'],
            'sort_order' => (int) $row['sort_order'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }
}
