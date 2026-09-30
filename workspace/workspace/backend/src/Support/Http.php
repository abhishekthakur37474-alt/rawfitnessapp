<?php

namespace App\Support;

final class Http
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = preg_replace('#^/api#', '', $uri) ?: '/';
        return rtrim($uri, '/') ?: '/';
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public static function match(string $pattern, string $path, ?array &$params = null): bool
    {
        $params = [];
        $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (!preg_match($regex, $path, $m)) {
            return false;
        }
        foreach ($m as $k => $v) {
            if (!is_int($k)) {
                $params[$k] = $v;
            }
        }
        return true;
    }
}
