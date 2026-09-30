<?php

namespace App\Middleware;

use App\Support\Json;

final class JwtAuth
{
    public function __construct(private array $jwtConfig)
    {
    }

    public function user(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $k => $v) {
                if (strtolower((string) $k) === 'authorization') {
                    $header = (string) $v;
                    break;
                }
            }
        }
        $token = '';
        if (preg_match('/Bearer\s+(\S+)/', $header, $m)) {
            $token = $m[1];
        } elseif (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_AUTH_TOKEN'];
        }
        if ($token === '') {
            Json::fail('Unauthorized', 401);
        }

        $payload = $this->decode($token);
        if ($payload === null) {
            Json::fail('Invalid token', 401);
        }

        return $payload;
    }

    public function encode(array $claims): string
    {
        $header = $this->b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $now = time();
        $body = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + (int) $this->jwtConfig['ttl_seconds'],
        ]);
        $payload = $this->b64(json_encode($body));
        $sig = $this->sign("{$header}.{$payload}");
        return "{$header}.{$payload}.{$sig}";
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $sig] = $parts;
        if (!hash_equals($this->sign("{$header}.{$payload}"), $sig)) {
            return null;
        }
        $data = json_decode($this->ub64($payload), true);
        if (!is_array($data) || (($data['exp'] ?? 0) < time())) {
            return null;
        }
        return $data;
    }

    private function sign(string $data): string
    {
        return $this->b64(hash_hmac('sha256', $data, $this->jwtConfig['secret'], true));
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function ub64(string $b64): string
    {
        $remainder = strlen($b64) % 4;
        if ($remainder) {
            $b64 .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($b64, '-_', '+/'));
    }
}
