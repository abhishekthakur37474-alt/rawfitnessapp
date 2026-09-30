<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class EventRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?int $branchId = null, ?string $search = null): array
    {
        $sql = 'SELECT e.*, b.name AS branch_name FROM events e
                LEFT JOIN branches b ON b.id = e.branch_id';
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 'e.is_active = 1';
        }
        if ($branchId !== null) {
            $where[] = '(e.branch_id = ? OR e.branch_id IS NULL)';
            $vals[] = $branchId;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(e.title LIKE ? OR e.location LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.starts_at IS NULL, e.starts_at DESC, e.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, b.name AS branch_name FROM events e
             LEFT JOIN branches b ON b.id = e.branch_id WHERE e.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO events (branch_id, title, image, description, location, starts_at, ends_at, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['image'],
            $data['description'],
            $data['location'],
            $data['starts_at'],
            $data['ends_at'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE events SET branch_id = ?, title = ?, image = ?, description = ?, location = ?,
             starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['title'],
            $data['image'],
            $data['description'],
            $data['location'],
            $data['starts_at'],
            $data['ends_at'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE events SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public function public(array $row, string $baseUrl): array
    {
        return [
            'id' => (int) $row['id'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? null,
            'title' => $row['title'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'description' => $row['description'],
            'location' => $row['location'],
            'starts_at' => $row['starts_at'],
            'ends_at' => $row['ends_at'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }
}
