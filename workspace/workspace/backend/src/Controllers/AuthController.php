<?php

namespace App\Controllers;

use App\Middleware\JwtAuth;
use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;
use App\Services\OtpService;
use App\Support\Http;
use App\Support\Json;
use App\Support\Validator;
use PDO;

final class AuthController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private JwtAuth $jwt,
        private OtpService $otp,
    ) {
    }

    public function sendOtp(): never
    {
        $body = Json::body();
        $mobile = Validator::mobile($body['mobile'] ?? '');
        $otps = new OtpRepository($this->pdo);
        $limit = (int) ($this->config['otp']['rate_limit_per_hour'] ?? 5);
        if ($otps->countLastHour($mobile) >= $limit) {
            Json::fail('Too many OTP requests. Try again later.', 429);
        }

        $code = $this->otp->generate();
        $otps->create(
            $mobile,
            $this->otp->hash($code),
            (int) ($this->config['otp']['expiry_minutes'] ?? 5),
            Http::ip()
        );
        $sent = $this->otp->send($mobile, $code);
        $data = [
            'expires_in' => ((int) ($this->config['otp']['expiry_minutes'] ?? 5)) * 60,
        ];
        if (!$sent && !empty($this->config['otp']['dev_return_otp'])) {
            $data['dev_otp'] = $code;
        }
        Json::ok($sent ? 'OTP sent' : 'OTP generated', $data);
    }

    public function verifyOtp(): never
    {
        $body = Json::body();
        $mobile = Validator::mobile($body['mobile'] ?? '');
        $code = Validator::otp($body['otp'] ?? '');
        $otps = new OtpRepository($this->pdo);
        $row = $otps->latestActive($mobile);
        if (!$row || !$this->otp->verify($code, $row['otp_hash'])) {
            Json::fail('Invalid or expired OTP', 422);
        }
        $otps->consume((int) $row['id']);

        $users = new UserRepository($this->pdo);
        $user = $users->findByMobile($mobile);
        if (!$user) {
            $user = $users->create($mobile);
        }
        $token = $this->jwt->encode([
            'sub' => (int) $user['id'],
            'member_id' => $user['member_id'],
        ]);
        Json::ok('Logged in', [
            'token' => $token,
            'is_onboarded' => (int) $user['is_onboarded'] === 1,
            'user' => $users->publicUser($user),
        ]);
    }
}
