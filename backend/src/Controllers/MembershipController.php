<?php

namespace App\Controllers;

use App\Repositories\MembershipRepository;
use App\Repositories\PackageRepository;
use App\Support\Json;
use App\Support\Validator;
use PDO;

final class MembershipController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private int $userId,
    ) {
    }

    public function current(): never
    {
        $repo = new MembershipRepository($this->pdo);
        $current = $repo->currentPayload($this->userId);
        $hasActive = $current !== null
            && in_array($current['status'], ['active', 'expiring'], true);
        $pending = $repo->latestPendingRequest($this->userId);
        Json::ok('ok', [
            'membership' => $current,
            'has_active' => $hasActive,
            'pending_request' => $pending ? [
                'id' => (int) $pending['id'],
                'package_id' => $pending['package_id'] !== null ? (int) $pending['package_id'] : null,
                'amount' => (float) $pending['amount'],
                'status' => $pending['status'],
                'created_at' => $pending['created_at'],
            ] : null,
        ]);
    }

    public function history(): never
    {
        $repo = new MembershipRepository($this->pdo);
        Json::ok('ok', ['memberships' => $repo->historyForUser($this->userId)]);
    }

    public function renew(): never
    {
        $body = Json::body();
        $packageId = Validator::intId($body['package_id'] ?? 0, 'package');
        $package = (new PackageRepository($this->pdo))->find($packageId);
        if (!$package || (int) $package['is_active'] !== 1) {
            Json::fail('Selected package is unavailable', 422);
        }

        $repo = new MembershipRepository($this->pdo);
        if ($repo->latestPendingRequest($this->userId)) {
            Json::fail('You already have a membership request pending approval', 409);
        }

        $amount = max(0.0, (float) $package['price'] - (float) $package['discount']);
        $note = trim((string) ($body['note'] ?? ''));
        $requestId = $repo->createRequest(
            $this->userId,
            $packageId,
            $amount,
            $note !== '' ? $note : null
        );

        Json::ok('Renewal request submitted. Our team will confirm shortly.', [
            'request_id' => $requestId,
            'amount' => $amount,
        ], 201);
    }
}
