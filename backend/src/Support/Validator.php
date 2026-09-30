<?php

namespace App\Support;

final class Validator
{
    public static function mobile(mixed $value): string
    {
        $v = preg_replace('/\D+/', '', (string) $value);
        if (str_starts_with($v, '91') && strlen($v) === 12) {
            $v = substr($v, 2);
        }
        if (!preg_match('/^[6-9]\d{9}$/', $v)) {
            Json::fail('Enter a valid 10-digit mobile number', 422);
        }
        return $v;
    }

    public static function otp(mixed $value): string
    {
        $v = trim((string) $value);
        if (!preg_match('/^\d{6}$/', $v)) {
            Json::fail('Enter a valid 6-digit OTP', 422);
        }
        return $v;
    }

    public static function name(mixed $value): string
    {
        $v = trim((string) $value);
        if (strlen($v) < 2 || strlen($v) > 120) {
            Json::fail('Enter your full name', 422);
        }
        return $v;
    }

    public static function gender(mixed $value): string
    {
        $v = strtolower(trim((string) $value));
        if (!in_array($v, ['male', 'female', 'other'], true)) {
            Json::fail('Select a valid gender', 422);
        }
        return $v;
    }

    public static function heightCm(mixed $value): float
    {
        $n = is_numeric($value) ? (float) $value : 0;
        if ($n < 80 || $n > 250) {
            Json::fail('Height must be between 80 and 250 cm', 422);
        }
        return round($n, 1);
    }

    public static function govType(mixed $value): string
    {
        $v = strtolower(trim((string) $value));
        if (!in_array($v, ['aadhaar', 'pan', 'passport', 'driving_license'], true)) {
            Json::fail('Select a valid ID type', 422);
        }
        return $v;
    }

    public static function govNumber(string $type, mixed $value): string
    {
        $v = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');
        $ok = match ($type) {
            'aadhaar' => (bool) preg_match('/^\d{12}$/', $v),
            'pan' => (bool) preg_match('/^[A-Z]{5}\d{4}[A-Z]$/', $v),
            'passport' => (bool) preg_match('/^[A-Z][0-9]{7}$/', $v),
            'driving_license' => strlen($v) >= 8 && strlen($v) <= 20,
            default => false,
        };
        if (!$ok) {
            Json::fail('Enter a valid ID number', 422);
        }
        return $v;
    }
}
