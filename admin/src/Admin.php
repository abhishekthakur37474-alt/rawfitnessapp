<?php

final class Admin
{
    public static function stats(PDO $pdo): array
    {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $active = (int) $pdo->query(
            'SELECT COUNT(*) FROM memberships WHERE status = "active" AND end_date >= CURDATE()'
        )->fetchColumn();
        $expiring = (int) $pdo->query(
            'SELECT COUNT(*) FROM memberships
             WHERE status = "active" AND end_date >= CURDATE()
             AND end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
        )->fetchColumn();
        $expired = (int) $pdo->query(
            'SELECT COUNT(*) FROM users u
             WHERE NOT EXISTS (
                SELECT 1 FROM memberships m
                WHERE m.user_id = u.id AND m.status = "active" AND m.end_date >= CURDATE()
             )'
        )->fetchColumn();

        $today = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = "success" AND DATE(created_at) = ?'
        );
        $today->execute([date('Y-m-d')]);
        $todayRevenue = (float) $today->fetchColumn();

        $month = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = "success" AND DATE_FORMAT(created_at, "%Y-%m") = ?'
        );
        $month->execute([date('Y-m')]);
        $monthRevenue = (float) $month->fetchColumn();

        return [
            'total' => $total,
            'active' => $active,
            'expiring' => $expiring,
            'expired' => $expired,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
        ];
    }

    public static function money($value): string
    {
        return '₹' . number_format((float) $value, 2);
    }

    public static function memberStatus(?string $status, ?string $endDate): string
    {
        if ($status === null || $endDate === null) {
            return 'none';
        }
        if ($status === 'cancelled') {
            return 'cancelled';
        }
        try {
            $end = new DateTimeImmutable($endDate);
        } catch (Throwable $e) {
            return 'none';
        }
        $today = new DateTimeImmutable('today');
        if ($end < $today) {
            return 'expired';
        }
        return $today->diff($end)->days <= 7 ? 'expiring' : 'active';
    }

    public static function badge(string $status): string
    {
        $map = [
            'active' => 'success',
            'expiring' => 'warning',
            'expired' => 'danger',
            'none' => 'secondary',
            'inactive' => 'secondary',
            'cancelled' => 'secondary',
            'pending' => 'warning',
            'approved' => 'success',
            'verified' => 'success',
            'rejected' => 'danger',
            'success' => 'success',
            'failed' => 'danger',
            'coming_soon' => 'info',
        ];
        $class = $map[strtolower($status)] ?? 'secondary';
        $label = ucwords(str_replace('_', ' ', $status));
        return '<span class="badge text-bg-' . $class . '">' . htmlspecialchars($label) . '</span>';
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function takeFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
