<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use PDO;

final class ExpiryReminderService
{
    public function __construct(
        private PDO $pdo,
        private OneSignalService $push,
    ) {
    }

    public function run(?string $today = null): array
    {
        $today = $today ?: date('Y-m-d');
        $expired = $this->expirePast();
        $sent = 0;
        $skipped = 0;
        $daysList = [7, 3, 1, 0];
        $repo = new NotificationRepository($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT m.id, m.user_id, m.end_date, u.member_id, u.name,
                    DATEDIFF(m.end_date, CURDATE()) AS days_left
             FROM memberships m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.status = 'active' AND m.end_date >= CURDATE()
               AND DATEDIFF(m.end_date, CURDATE()) IN (7, 3, 1, 0)"
        );
        $rows = $stmt ? $stmt->fetchAll() : [];

        foreach ($rows as $row) {
            $daysLeft = (int) $row['days_left'];
            if (!in_array($daysLeft, $daysList, true)) {
                continue;
            }
            $membershipId = (int) $row['id'];
            $userId = (int) $row['user_id'];
            if ($repo->reminderSent($membershipId, $daysLeft, $today)) {
                $skipped++;
                continue;
            }

            $name = trim((string) ($row['name'] ?? '')) ?: 'Athlete';
            if ($daysLeft === 0) {
                $title = 'Membership expires today';
                $body = $name . ', your Raw Fitness membership expires today. Renew to keep training.';
            } else {
                $title = 'Membership expiring in ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's');
                $body = $name . ', your membership ends on ' . date('d M Y', strtotime((string) $row['end_date'])) . '. Renew now.';
            }
            $externalId = (string) ($row['member_id'] ?? $userId);
            $data = [
                'type' => 'membership',
                'membership_id' => $membershipId,
                'days_left' => $daysLeft,
            ];
            $this->push->dispatch(
                audience: 'member',
                title: $title,
                body: $body,
                data: $data,
                userId: $userId,
                externalIds: [$externalId],
                type: 'membership',
            );
            $repo->logReminder($membershipId, $userId, $daysLeft, $today);
            $sent++;
        }

        return [
            'expired' => $expired,
            'sent' => $sent,
            'skipped' => $skipped,
            'date' => $today,
        ];
    }

    private function expirePast(): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE memberships SET status = "expired"
             WHERE status = "active" AND end_date < CURDATE()'
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
