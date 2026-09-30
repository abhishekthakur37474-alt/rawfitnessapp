<?php

namespace App\Services;

final class OtpService
{
    public function __construct(private array $config)
    {
    }

    public function generate(): string
    {
        $len = (int) ($this->config['length'] ?? 6);
        $max = (10 ** $len) - 1;
        return str_pad((string) random_int(0, $max), $len, '0', STR_PAD_LEFT);
    }

    public function hash(string $otp): string
    {
        return password_hash($otp, PASSWORD_DEFAULT);
    }

    public function verify(string $otp, string $hash): bool
    {
        return password_verify($otp, $hash);
    }

    public function send(string $mobile, string $otp): bool
    {
        $provider = $this->config['provider'] ?? 'apitxt';
        return match ($provider) {
            'apitxt' => $this->sendApitxt($mobile, $otp),
            default => throw new \RuntimeException("Unknown OTP provider: {$provider}"),
        };
    }

    private function sendApitxt(string $mobile, string $otp): bool
    {
        $cfg = $this->config['apitxt'] ?? [];
        $base = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        if ($base === '') {
            error_log('[OtpService] apitxt not configured; OTP not sent');
            return false;
        }

        $payload = json_encode([
            'to' => $mobile,
            'sender' => $cfg['sender'] ?? 'RAWFIT',
            'message' => "Your Raw Fitness OTP is {$otp}. Valid for 5 minutes.",
        ]);

        $ch = curl_init($base);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($cfg['api_key'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $code >= 400) {
            error_log('[OtpService] apitxt error: ' . ($err ?: $res));
            return false;
        }
        return true;
    }
}
