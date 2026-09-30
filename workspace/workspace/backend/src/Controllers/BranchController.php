<?php

namespace App\Controllers;

use App\Repositories\BranchRepository;
use App\Support\Json;
use PDO;

final class BranchController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
    ) {
    }

    public function index(): never
    {
        $repo = new BranchRepository($this->pdo);
        $base = (string) ($this->config['base_url'] ?? '');
        $rows = $repo->all(true);
        Json::ok('ok', [
            'branches' => array_map(static fn (array $r): array => $repo->public($r, $base), $rows),
        ]);
    }

    public function show(int $id): never
    {
        $repo = new BranchRepository($this->pdo);
        $row = $repo->find($id);
        if (!$row || (int) $row['is_active'] !== 1) {
            Json::fail('Branch not found', 404);
        }
        Json::ok('ok', [
            'branch' => $repo->detail($row, (string) ($this->config['base_url'] ?? '')),
        ]);
    }
}
