<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/admin/assets/(.*)$#', $uri, $m)) {
    $file = __DIR__ . '/admin/public/assets/' . $m[1];
    if (is_file($file)) {
        $mime = mime_content_type($file) ?: 'text/css';
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit('Not found');
}

if (preg_match('#^/uploads/(.*)$#', $uri, $m) || preg_match('#^/api/uploads/(.*)$#', $uri, $m)) {
    $file = __DIR__ . '/backend/public/uploads/' . $m[1];
    if (is_file($file)) {
        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit('Not found');
}

if (str_starts_with($uri, '/admin')) {
    require __DIR__ . '/admin/public/index.php';
    exit;
}

if (str_starts_with($uri, '/api') || $uri === '/health') {
    require __DIR__ . '/backend/public/index.php';
    exit;
}

header('Location: /admin/');
exit;
