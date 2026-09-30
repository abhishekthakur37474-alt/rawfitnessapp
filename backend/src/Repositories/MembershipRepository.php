<?php

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final class MembershipRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function latestForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, p.name AS package_name, p.duration_days AS package_duration
             FROM memberships m
             LEFT JOIN packages p ON p.id = m.package_id
             WHERE m.user_id = ?
             ORDER BY m.end_date DESC, m.id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function historyForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, p.name AS package_name, p.duration_days AS package_duration
             FROM memberships m
             LEFT JOIN packages p ON p.id = m.package_id
             WHERE m.user_id = ?
             ORDER BY m.start_date DESC, m.id DESC'
        );
        $stmt->execute([$userId]);
        return array_map(fn (array $r) => $this->public($r), $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, p.name AS package_name, p.duration_days AS package_duration
             FROM memberships m
             LEFT JOIN packages p ON p.id = m.package_id
             WHERE m.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(
        int $userId,
        ?int $packageId,
        string $startDate,
        string $endDate,
        float $amount,
        float $paidAmount,
        float $dueAmount,
        string $status = 'active'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO memberships
             (user_id, package_id, start_date, end_date, amount, paid_amount, due_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $packageId,
            $startDate,
            $endDate,
            $amount,
            $paidAmount,
            $dueAmount,
            $status,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cancelOthers(int $userId, int $exceptId = 0): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE memberships SET status = "cancelled"
             WHERE user_id = ? AND id <> ? AND status = "active"'
        );
        $stmt->execute([$userId, $exceptId]);
    }

    public function recalcTotals(int $membershipId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE membership_id = ? AND status = "success"'
        );
        $stmt->execute([$membershipId]);
        $paid = (float) $stmt->fetchColumn();

        $row = $this->find($membershipId);
        if (!$row) {
            return;
        }
        $amount = (float) $row['amount'];
        $due = max(0.0, $amount - $paid);
        $upd = $this->pdo->prepare(
            'UPDATE memberships SET paid_amount = ?, due_amount = ? WHERE id = ?'
        );
        $upd->execute([$paid, $due, $membershipId]);
    }

    public function expirePast(): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE memberships SET status = "expired"
             WHERE status = "active" AND end_date < CURDATE()'
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function statusOf(array $row): string
    {
        if (($row['status'] ?? '') === 'cancelled') {
            return 'cancelled';
        }
        $end = new DateTimeImmutable((string) $row['end_date']);
        $today = new DateTimeImmutable('today');
        if ($end < $today) {
            return 'expired';
        }
        $daysLeft = (int) $today->diff($end)->days;
        return $daysLeft <= 7 ? 'expiring' : 'active';
    }

    public function public(array $row): array
    {
        $end = (new DateTimeImmutable((string) $row['end_date']))->setTime(0, 0);
        $today = (new DateTimeImmutable('today'))->setTime(0, 0);
        $diff = $today->diff($end);
        $daysLeft = $end >= $today ? (int) $diff->days : -((int) $diff->days);

        return [
            'id' => (int) $row['id'],
            'package_id' => $row['package_id'] !== null ? (int) $row['package_id'] : null,
            'package_name' => $row['package_name'] ?? null,
            'package_duration_days' => isset($row['package_duration']) ? (int) $row['package_duration'] : null,
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'amount' => (float) $row['amount'],
            'paid_amount' => (float) $row['paid_amount'],
            'due_amount' => (float) $row['due_amount'],
            'status' => $this->statusOf($row),
            'days_left' => $daysLeft,
        ];
    }

    public function currentPayload(int $userId): ?array
    {
        $row = $this->latestForUser($userId);
        return $row ? $this->public($row) : null;
    }

    public function createRequest(int $userId, ?int $packageId, float $amount, ?string $note = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO membership_requests (user_id, package_id, amount, status, note)
             VALUES (?, ?, ?, "pending", ?)'
        );
        $stmt->execute([$userId, $packageId, $amount, $note]);
        return (int) $this->pdo->lastInsertId();
    }

    public function latestPendingRequest(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM membership_requests
             WHERE user_id = ? AND status = "pending"
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
