<?php

namespace App\Controllers;

use App\Repositories\PackageRepository;
use App\Support\Json;
use PDO;

final class PackageController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): never
    {
        $branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== ''
            ? (int) $_GET['branch_id']
            : null;
        $packages = (new PackageRepository($this->pdo))->all(true, $branchId);
        Json::ok('ok', ['packages' => $packages]);
    }
}
