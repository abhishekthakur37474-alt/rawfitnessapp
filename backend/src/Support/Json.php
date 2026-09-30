<?php

namespace App\Support;

final class Json
{
    public static function send(bool $status, string $message, mixed $data = null, int $http = 200): never
    {
        http_response_code($http);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(string $message = 'ok', mixed $data = null, int $http = 200): never
    {
        self::send(true, $message, $data, $http);
    }

    public static function fail(string $message, int $http = 400, mixed $data = null): never
    {
        self::send(false, $message, $data, $http);
    }

    public static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
