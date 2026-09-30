<?php

namespace App\Controllers;

use App\Repositories\NotificationRepository;
use App\Support\Json;
use PDO;

final class NotificationController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private int $userId,
    ) {
    }

    public function index(): never
    {
        $repo = new NotificationRepository($this->pdo);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $rows = $repo->forUser($this->userId, $perPage, ($page - 1) * $perPage);
        $base = (string) ($this->config['base_url'] ?? '');
        Json::ok('ok', [
            'notifications' => array_map(
                fn (array $r): array => $repo->public($r, $base, $this->userId),
                $rows
            ),
            'unread_count' => $repo->unreadCount($this->userId),
        ]);
    }

    public function unreadCount(): never
    {
        $repo = new NotificationRepository($this->pdo);
        Json::ok('ok', ['unread_count' => $repo->unreadCount($this->userId)]);
    }

    public function markRead(int $id): never
    {
        $repo = new NotificationRepository($this->pdo);
        if (!$repo->markRead($id, $this->userId)) {
            Json::fail('Notification not found', 404);
        }
        Json::ok('Marked as read', ['unread_count' => $repo->unreadCount($this->userId)]);
    }

    public function markAllRead(): never
    {
        $repo = new NotificationRepository($this->pdo);
        $repo->markAllRead($this->userId);
        Json::ok('All marked as read', ['unread_count' => 0]);
    }
}
