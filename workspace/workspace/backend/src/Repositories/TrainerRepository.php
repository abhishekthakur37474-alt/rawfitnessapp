<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class TrainerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(bool $activeOnly = true, ?int $branchId = null, ?string $search = null): array
    {
        $sql = 'SELECT t.*, b.name AS branch_name FROM trainers t
                LEFT JOIN branches b ON b.id = t.branch_id';
        [$where, $vals] = $this->filters($activeOnly, $branchId, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.is_active DESC, t.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, b.name AS branch_name FROM trainers t
             LEFT JOIN branches b ON b.id = t.branch_id
             WHERE t.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO trainers (branch_id, name, image, role, bio, phone, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['name'],
            $data['image'],
            $data['role'],
            $data['bio'],
            $data['phone'],
            $data['is_active'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE trainers SET branch_id = ?, name = ?, image = ?, role = ?, bio = ?, phone = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['branch_id'],
            $data['name'],
            $data['image'],
            $data['role'],
            $data['bio'],
            $data['phone'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM trainer_certifications WHERE trainer_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM trainers WHERE id = ?')->execute([$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE trainers SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    }

    public function certifications(int $trainerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM trainer_certifications WHERE trainer_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$trainerId]);
        return $stmt->fetchAll();
    }

    public function replaceCertifications(int $trainerId, array $certs): void
    {
        $this->pdo->prepare('DELETE FROM trainer_certifications WHERE trainer_id = ?')->execute([$trainerId]);
        $ins = $this->pdo->prepare(
            'INSERT INTO trainer_certifications (trainer_id, title, issuer, year) VALUES (?, ?, ?, ?)'
        );
        foreach ($certs as $c) {
            $title = trim((string) ($c['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $ins->execute([
                $trainerId,
                $title,
                ($c['issuer'] ?? '') !== '' ? $c['issuer'] : null,
                ($c['year'] ?? '') !== '' ? $c['year'] : null,
            ]);
        }
    }

    public function public(array $row, string $baseUrl): array
    {
        return [
            'id' => (int) $row['id'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? null,
            'name' => $row['name'],
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'role' => $row['role'],
            'bio' => $row['bio'],
            'phone' => $row['phone'],
            'is_active' => (int) $row['is_active'] === 1,
        ];
    }

    public function detail(array $row, string $baseUrl): array
    {
        $certs = $this->certifications((int) $row['id']);
        return array_merge($this->public($row, $baseUrl), [
            'certifications' => array_map(static fn (array $c): array => [
                'id' => (int) $c['id'],
                'title' => $c['title'],
                'issuer' => $c['issuer'],
                'year' => $c['year'],
            ], $certs),
        ]);
    }

    private function filters(bool $activeOnly, ?int $branchId, ?string $search): array
    {
        $where = [];
        $vals = [];
        if ($activeOnly) {
            $where[] = 't.is_active = 1';
        }
        if ($branchId !== null) {
            $where[] = '(t.branch_id = ? OR t.branch_id IS NULL)';
            $vals[] = $branchId;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(t.name LIKE ? OR t.role LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        return [$where, $vals];
    }
}
