<?php

namespace App\Repositories;

use PDO;

final class AnnouncementRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?int $branchId = null, ?string $search = null): array
    {
        $sql = 'SELECT a.*, b.name AS branch_name FROM announcements a
                LEFT JOIN branches b ON b.id = a.branch_id';
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'a.is_active = 1';
        }
        if ($branchId !== null) {
            $where[] = '(a.branch_id = ? OR a.branch_id IS NULL)';
            $vals[] = $branchId;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(a.title LIKE ? OR a.body LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.published_at DESC, a.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM announcements WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO announcements (branch_id, title, body, is_active, published_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['body'],
            $data['is_active'],
            $data['published_at'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE announcements SET branch_id = ?, title = ?, body = ?, is_active = ?, published_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['body'],
            $data['is_active'],
            $data['published_at'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE announcements SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public function public(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? null,
            'title' => $row['title'],
            'body' => $row['body'],
            'published_at' => $row['published_at'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }
}
