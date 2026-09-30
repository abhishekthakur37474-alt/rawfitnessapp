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

        $todayAttendance = 0;
        try {
            $todayAttendance = (int) $pdo->query(
                'SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()'
            )->fetchColumn();
        } catch (Throwable $e) {
            $todayAttendance = 0;
        }

        return [
            'total' => $total,
            'active' => $active,
            'expiring' => $expiring,
            'expired' => $expired,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
            'todayAttendance' => $todayAttendance,
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
    {        $map = [
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
            'manual' => 'info',
            'face_machine' => 'success',
            'sent' => 'success',
        ];
        $class = $map[strtolower($status)] ?? 'secondary';
        $label = ucwords(str_replace('_', ' ', $status));
        return '<span class="badge text-bg-' . $class . '">' . htmlspecialchars($label) . '</span>';
    }

    public static function storeImage(array $file, string $subdir, array $config): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if (($file['size'] ?? 0) > (int) ($config['uploads']['max_bytes'] ?? 0)) {
            return null;
        }
        $mime = null;
        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: null;
        }
        if ($mime === null) {
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                default => '',
            };
        }
        if (!in_array($mime, $config['uploads']['allowed_mime'] ?? [], true)) {
            return null;
        }
        $extOut = $mime === 'image/png' ? 'png' : 'jpg';
        $name = bin2hex(random_bytes(16)) . '.' . $extOut;
        $dir = rtrim((string) $config['uploads']['dir'], '/') . '/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return null;
        }
        return rtrim((string) $config['uploads']['public_path'], '/') . '/' . $subdir . '/' . $name;
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
