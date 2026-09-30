<?php

namespace App\Support;

final class Cors
{
    public static function apply(array $origins): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allow = in_array('*', $origins, true) || in_array($origin, $origins, true)
            ? (in_array('*', $origins, true) ? '*' : $origin)
            : $origins[0];

        header("Access-Control-Allow-Origin: {$allow}");
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Auth-Token, X-HTTP-Method-Override');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 86400');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
