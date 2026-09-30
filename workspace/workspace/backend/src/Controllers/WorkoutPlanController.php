<?php

namespace App\Controllers;

use App\Repositories\WorkoutPlanRepository;
use App\Support\Json;
use PDO;

final class WorkoutPlanController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
    ) {
    }

    public function index(): never
    {
        $repo = new WorkoutPlanRepository($this->pdo);
        $category = trim((string) ($_GET['category'] ?? ''));
        $base = (string) ($this->config['base_url'] ?? '');
        $plans = $repo->all(true, $category !== '' ? $category : null);
        Json::ok('ok', [
            'plans' => array_map(static fn (array $r): array => $repo->public($r, $base), $plans),
            'categories' => $repo->categories(true),
        ]);
    }

    public function show(int $id): never
    {
        $repo = new WorkoutPlanRepository($this->pdo);
        $row = $repo->find($id);
        if (!$row || (int) $row['is_active'] !== 1) {
            Json::fail('Workout plan not found', 404);
        }
        Json::ok('ok', [
            'plan' => $repo->detail($row, (string) ($this->config['base_url'] ?? '')),
        ]);
    }
}
