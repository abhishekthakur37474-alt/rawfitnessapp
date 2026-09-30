<?php

namespace App\Support;

final class Media
{
    public static function url(?string $path, string $baseUrl): ?string
    {
        $path = $path !== null ? trim($path) : '';
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
