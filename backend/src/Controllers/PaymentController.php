<?php

namespace App\Controllers;

use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Services\PaymentGateway;
use App\Support\Json;
use PDO;

final class PaymentController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private int $userId,
    ) {
    }

    public function history(): never
    {
        $payments = (new PaymentRepository($this->pdo))->forUser($this->userId);
        Json::ok('ok', ['payments' => $payments]);
    }

    public function initiate(): never
    {
        $body = Json::body();
        $method = strtolower(trim((string) ($body['method'] ?? '')));
        if (!in_array($method, ['upi', 'card', 'netbanking', 'wallet'], true)) {
            Json::fail('Select a valid payment method', 422);
        }
        $gateway = new PaymentGateway();
        $result = $gateway->initiate([
            'user_id' => $this->userId,
            'method' => $method,
            'amount' => $body['amount'] ?? 0,
        ]);
        Json::ok(
            (string) ($result['message'] ?? 'Online payments are coming soon.'),
            ['status' => (string) ($result['status'] ?? 'coming_soon')]
        );
    }

    public function receipt(int $id): never
    {
        $repo = new PaymentRepository($this->pdo);
        $row = $repo->find($id);
        if (!$row || (int) $row['user_id'] !== $this->userId) {
            Json::fail('Receipt not found', 404);
        }
        $user = (new UserRepository($this->pdo))->findById($this->userId);
        $payload = $repo->public($row);
        $payload['member'] = [
            'member_id' => $user['member_id'] ?? null,
            'name' => $user['name'] ?? null,
            'mobile' => $user['mobile'] ?? null,
        ];
        $payload['gym_name'] = $this->config['app_name'] ?? 'Raw Fitness';
        Json::ok('ok', $payload);
    }
}
