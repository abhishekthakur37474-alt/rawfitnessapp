<?php

namespace App\Repositories;

use App\Support\Media;
use PDO;

final class NotificationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications
             (user_id, title, body, type, data_json, is_read, image, audience, onesignal_id, onesignal_status, onesignal_response)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['title'],
            $data['body'] ?? null,
            $data['type'] ?? 'general',
            $data['data_json'] ?? null,
            (int) ($data['is_read'] ?? 0),
            $data['image'] ?? null,
            $data['audience'] ?? 'all',
            $data['onesignal_id'] ?? null,
            $data['onesignal_status'] ?? null,
            $data['onesignal_response'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePushResult(int $id, ?string $onesignalId, string $status, string $response): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET onesignal_id = ?, onesignal_status = ?, onesignal_response = ? WHERE id = ?'
        );
        $stmt->execute([$onesignalId, $status, $response, $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function forUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT n.*,
                    CASE
                        WHEN n.user_id = ? THEN n.is_read
                        WHEN r.user_id IS NOT NULL THEN 1
                        ELSE 0
                    END AS is_read_for_user
                FROM notifications n
                LEFT JOIN notification_reads r
                    ON r.notification_id = n.id AND r.user_id = ?
                WHERE n.user_id IS NULL OR n.user_id = ?
                ORDER BY n.created_at DESC, n.id DESC
                LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications n
                LEFT JOIN notification_reads r
                    ON r.notification_id = n.id AND r.user_id = ?
                WHERE (n.user_id IS NULL OR n.user_id = ?)
                  AND (
                    (n.user_id = ? AND n.is_read = 0)
                    OR (n.user_id IS NULL AND r.user_id IS NULL)
                  )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $id, int $userId): bool
    {
        $row = $this->find($id);
        if (!$row) {
            return false;
        }
        $owner = $row['user_id'] !== null ? (int) $row['user_id'] : null;
        if ($owner !== null && $owner !== $userId) {
            return false;
        }
        if ($owner === $userId) {
            $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);
        }
        $ins = $this->pdo->prepare(
            'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)'
        );
        $ins->execute([$id, $userId]);
        return true;
    }

    public function markAllRead(int $userId): void
    {
        $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        )->execute([$userId]);
        $rows = $this->pdo->prepare(
            'SELECT id FROM notifications WHERE user_id IS NULL OR user_id = ?'
        );
        $rows->execute([$userId]);
        $ins = $this->pdo->prepare(
            'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)'
        );
        foreach ($rows as $row) {
            $ins->execute([(int) $row['id'], $userId]);
        }
    }

    public function adminList(?string $search = null, int $limit = 40, int $offset = 0): array
    {
        $sql = 'SELECT n.*, u.member_id, u.name AS member_name
                FROM notifications n
                LEFT JOIN users u ON u.id = n.user_id';
        $vals = [];
        if ($search !== null && $search !== '') {
            $sql .= ' WHERE n.title LIKE ? OR n.body LIKE ? OR u.member_id LIKE ? OR u.name LIKE ?';
            $like = '%' . $search . '%';
            $vals = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY n.created_at DESC, n.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return $stmt->fetchAll();
    }

    public function adminCount(?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications n LEFT JOIN users u ON u.id = n.user_id';
        $vals = [];
        if ($search !== null && $search !== '') {
            $sql .= ' WHERE n.title LIKE ? OR n.body LIKE ? OR u.member_id LIKE ? OR u.name LIKE ?';
            $like = '%' . $search . '%';
            $vals = [$like, $like, $like, $like];
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($vals);
        return (int) $stmt->fetchColumn();
    }

    public function public(array $row, string $baseUrl, ?int $userId = null): array
    {
        $read = $userId !== null
            ? ((int) ($row['is_read_for_user'] ?? $row['is_read'] ?? 0) === 1)
            : ((int) ($row['is_read'] ?? 0) === 1);
        $data = [];
        if (!empty($row['data_json'])) {
            $decoded = json_decode((string) $row['data_json'], true);
            $data = is_array($decoded) ? $decoded : [];
        }
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'body' => $row['body'],
            'type' => $row['type'] ?? 'general',
            'data' => $data,
            'is_read' => $read,
            'image_url' => Media::url($row['image'] ?? null, $baseUrl),
            'created_at' => $row['created_at'],
        ];
    }

    public function reminderSent(int $membershipId, int $daysBefore, string $date): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM membership_reminders
             WHERE membership_id = ? AND days_before = ? AND sent_on = ? LIMIT 1'
        );
        $stmt->execute([$membershipId, $daysBefore, $date]);
        return (bool) $stmt->fetch();
    }

    public function logReminder(int $membershipId, int $userId, int $daysBefore, string $date): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO membership_reminders (membership_id, user_id, days_before, sent_on)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$membershipId, $userId, $daysBefore, $date]);
    }
}
