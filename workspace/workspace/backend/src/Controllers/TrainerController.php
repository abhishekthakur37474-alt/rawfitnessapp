<?php

namespace App\Controllers;

use App\Repositories\TrainerRepository;
use App\Support\Json;
use PDO;

final class TrainerController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
    ) {
    }

    public function index(): never
    {
        $repo = new TrainerRepository($this->pdo);
        $branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== ''
            ? (int) $_GET['branch_id']
            : null;
        $base = (string) ($this->config['base_url'] ?? '');
        $rows = $repo->all(true, $branchId);
        Json::ok('ok', [
            'trainers' => array_map(static fn (array $r): array => $repo->public($r, $base), $rows),
        ]);
    }

    public function show(int $id): never
    {
        $repo = new TrainerRepository($this->pdo);
        $row = $repo->find($id);
        if (!$row || (int) $row['is_active'] !== 1) {
            Json::fail('Trainer not found', 404);
        }
        Json::ok('ok', [
            'trainer' => $repo->detail($row, (string) ($this->config['base_url'] ?? '')),
        ]);
    }
}
