<?php

namespace App\Controllers;

use App\Repositories\AnnouncementRepository;
use App\Repositories\BannerRepository;
use App\Repositories\EventRepository;
use App\Support\Json;
use PDO;

final class GymFeedController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
    ) {
    }

    public function events(): never
    {
        $repo = new EventRepository($this->pdo);
        $branchId = $this->branchId();
        $base = (string) ($this->config['base_url'] ?? '');
        $rows = $repo->all(true, $branchId);
        Json::ok('ok', [
            'events' => array_map(static fn (array $r): array => $repo->public($r, $base), $rows),
        ]);
    }

    public function announcements(): never
    {
        $repo = new AnnouncementRepository($this->pdo);
        $rows = $repo->all(true, $this->branchId());
        Json::ok('ok', [
            'announcements' => array_map(static fn (array $r): array => $repo->public($r), $rows),
        ]);
    }

    public function banners(): never
    {
        $repo = new BannerRepository($this->pdo);
        $base = (string) ($this->config['base_url'] ?? '');
        $rows = $repo->all(true, $this->branchId());
        Json::ok('ok', [
            'banners' => array_map(static fn (array $r): array => $repo->public($r, $base), $rows),
        ]);
    }

    private function branchId(): ?int
    {
        return isset($_GET['branch_id']) && $_GET['branch_id'] !== ''
            ? (int) $_GET['branch_id']
            : null;
    }
}
