<?php

namespace App\Services;

final class PaymentGateway
{
    public function initiate(array $payload): array
    {
        return [
            'status' => 'coming_soon',
            'message' => 'Online payments are coming soon.',
        ];
    }
}
