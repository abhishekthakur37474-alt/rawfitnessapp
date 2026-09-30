<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['timezone']);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/src/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

return $config;
