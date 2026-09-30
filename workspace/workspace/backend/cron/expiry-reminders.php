<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/src/bootstrap.php';

use App\Repositories\SettingRepository;
use App\Services\ExpiryReminderService;
use App\Services\OneSignalService;
use App\Support\Database;
use App\Support\Schema;

$pdo = Database::pdo($config['db']);
Schema::migrate($pdo, $config);
$config = (new SettingRepository($pdo))->overlay($config);

$push = new OneSignalService($config, $pdo);
$service = new ExpiryReminderService($pdo, $push);
$result = $service->run();

$line = date('c') . ' expired=' . $result['expired'] . ' sent=' . $result['sent'] . ' skipped=' . $result['skipped'] . PHP_EOL;
echo $line;
error_log('[expiry-reminders] ' . trim($line));
