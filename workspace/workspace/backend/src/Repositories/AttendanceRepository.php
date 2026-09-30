<?php

namespace App\Repositories;

use PDO;

final class AttendanceRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forUser(int $userId, ?int $branchId = null, ?string $month = null): array
    {
        $sql = 'SELECT a.*, b.name AS branch_name FROM attendance a
                LEFT JOIN branches b ON b.id = a.branch_id
                WHERE a.user_id = ?';
        $vals = [$userId];
        if ($branchId !== null) {
            $sql .= ' AND a.branch_id = ?';
            $vals[] = $branchId;
        }
        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $sql .= ' AND DATE_FORMAT(a.check_in, "%Y-%m") = ?';
            $vals[] = $month;
        }
        $sql .= ' ORDER BY a.check_in DESC, a.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function adminList(?int $userId = null, ?int $branchId = null, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT a.*, u.member_id, u.name AS member_name, u.mobile, b.name AS branch_name
                FROM attendance a
                INNER JOIN users u ON u.id = a.user_id
                LEFT JOIN branches b ON b.id = a.branch_id';
        [$where, $vals] = $this->adminFilters($userId, $branchId, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.check_in DESC, a.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function adminCount(?int $userId = null, ?int $branchId = null, ?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM attendance a
                INNER JOIN users u ON u.id = a.user_id';
        [$where, $vals] = $this->adminFilters($userId, $branchId, $search);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance (user_id, branch_id, check_in, check_out, source)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['branch_id'] ?? null,
            $data['check_in'],
            $data['check_out'] ?? null,
            $data['source'] ?? 'manual',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attendance WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setCheckout(int $id, string $checkOut): void
    {
        $stmt = $this->pdo->prepare('UPDATE attendance SET check_out = ? WHERE id = ?');
        $stmt->execute([$checkOut, $id]);
    }

    public function openForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM attendance WHERE user_id = ? AND check_out IS NULL ORDER BY check_in DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function public(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'branch_id' => $row['branch_id'] !== null ? (int) $row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? null,
            'check_in' => $row['check_in'],
            'check_out' => $row['check_out'],
            'source' => $row['source'],
        ];
    }

    private function adminFilters(?int $userId, ?int $branchId, ?string $search): array
    {
        $where = [];
        $vals = [];
        if ($userId !== null) {
            $where[] = 'a.user_id = ?';
            $vals[] = $userId;
        }
        if ($branchId !== null) {
            $where[] = 'a.branch_id = ?';
            $vals[] = $branchId;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(u.name LIKE ? OR u.member_id LIKE ? OR u.mobile LIKE ?)';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
            $vals[] = '%' . $search . '%';
        }
        return [$where, $vals];
    }
}
