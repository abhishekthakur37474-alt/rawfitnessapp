<?php

namespace App\Controllers;

use App\Repositories\AttendanceRepository;
use App\Support\Json;
use PDO;

final class AttendanceController
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private int $userId,
    ) {
    }

    public function history(): never
    {
        $repo = new AttendanceRepository($this->pdo);
        $branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== ''
            ? (int) $_GET['branch_id']
            : null;
        $month = trim((string) ($_GET['month'] ?? ''));
        $rows = $repo->forUser($this->userId, $branchId, $month !== '' ? $month : null);
        Json::ok('ok', [
            'attendance' => array_map(static fn (array $r): array => $repo->public($r), $rows),
            'face_checkin_available' => false,
            'note' => 'Face check-in coming soon',
        ]);
    }

    public function checkin(): never
    {
        $expected = (string) ($this->config['attendance']['device_key'] ?? '');
        $given = (string) (
            $_SERVER['HTTP_X_DEVICE_KEY']
            ?? ($_POST['device_key'] ?? '')
        );
        $body = Json::body();
        if ($given === '' && isset($body['device_key'])) {
            $given = (string) $body['device_key'];
        }
        if ($expected === '' || !hash_equals($expected, $given)) {
            Json::fail('Invalid device key', 403);
        }

        $userId = (int) ($body['user_id'] ?? $this->userId);
        if ($userId < 1) {
            Json::fail('user_id is required');
        }
        $branchId = isset($body['branch_id']) && $body['branch_id'] !== ''
            ? (int) $body['branch_id']
            : null;
        $checkIn = trim((string) ($body['check_in'] ?? '')) ?: date('Y-m-d H:i:s');
        try {
            $checkIn = (new \DateTimeImmutable($checkIn))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            Json::fail('Invalid check_in timestamp');
        }

        $repo = new AttendanceRepository($this->pdo);
        $open = $repo->openForUser($userId);
        if ($open) {
            $repo->setCheckout((int) $open['id'], $checkIn);
            Json::ok('Checked out', [
                'attendance' => $repo->public($repo->find((int) $open['id']) ?? $open),
                'action' => 'checkout',
                'face_checkin_available' => false,
            ]);
        }

        $id = $repo->create([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'check_in' => $checkIn,
            'check_out' => null,
            'source' => 'face_machine',
        ]);
        $row = $repo->find($id);
        Json::ok('Checked in', [
            'attendance' => $repo->public($row ?? ['id' => $id, 'user_id' => $userId, 'branch_id' => $branchId, 'check_in' => $checkIn, 'check_out' => null, 'source' => 'face_machine']),
            'action' => 'checkin',
            'face_checkin_available' => false,
            'note' => 'Face check-in coming soon',
        ]);
    }
}
