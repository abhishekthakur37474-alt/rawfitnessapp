<?php

namespace App\Repositories;

use PDO;

final class PaymentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        int $userId,
        ?int $membershipId,
        float $amount,
        string $mode,
        ?string $txnRef,
        string $status = 'success'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (membership_id, user_id, amount, mode, txn_ref, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$membershipId, $userId, $amount, $mode, $txnRef, $status]);
        $id = (int) $this->pdo->lastInsertId();
        $upd = $this->pdo->prepare('UPDATE payments SET receipt_no = ? WHERE id = ?');
        $upd->execute([sprintf('RCP%06d', $id), $id]);
        return $id;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pay.*, p.name AS package_name, m.start_date, m.end_date
             FROM payments pay
             LEFT JOIN memberships m ON m.id = pay.membership_id
             LEFT JOIN packages p ON p.id = m.package_id
             WHERE pay.user_id = ?
             ORDER BY pay.created_at DESC, pay.id DESC'
        );
        $stmt->execute([$userId]);
        return array_map(fn (array $r) => $this->public($r), $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pay.*, p.name AS package_name, m.start_date, m.end_date
             FROM payments pay
             LEFT JOIN memberships m ON m.id = pay.membership_id
             LEFT JOIN packages p ON p.id = m.package_id
             WHERE pay.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function sumSince(string $fromDate): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = "success" AND created_at >= ?'
        );
        $stmt->execute([$fromDate . ' 00:00:00']);
        return (float) $stmt->fetchColumn();
    }

    public function public(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'membership_id' => $row['membership_id'] !== null ? (int) $row['membership_id'] : null,
            'amount' => (float) $row['amount'],
            'mode' => $row['mode'],
            'txn_ref' => $row['txn_ref'],
            'status' => $row['status'],
            'receipt_no' => $row['receipt_no'],
            'package_name' => $row['package_name'] ?? null,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $row['end_date'] ?? null,
            'created_at' => $row['created_at'],
        ];
    }
}
