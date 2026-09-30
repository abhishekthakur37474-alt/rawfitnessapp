<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Repositories\SettingRepository;
use PDO;

final class OneSignalService
{
    public function __construct(
        private array $config,
        private ?PDO $pdo = null,
    ) {
        if ($this->pdo) {
            $this->config = (new SettingRepository($this->pdo))->overlay($this->config);
        }
    }

    public function sendToExternalIds(array $externalIds, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $ids = array_values(array_filter(array_map('strval', $externalIds)));
        if ($ids === []) {
            return ['ok' => false, 'error' => 'No recipients', 'status' => 0, 'body' => '', 'id' => null];
        }
        $payload = [
            'app_id' => $this->appId(),
            'target_channel' => 'push',
            'include_aliases' => ['external_id' => $ids],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ];
        $this->attachImage($payload, $imageUrl);
        return $this->post($payload);
    }

    public function sendToAll(string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $payload = [
            'app_id' => $this->appId(),
            'included_segments' => ['Subscribed Users'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ];
        $this->attachImage($payload, $imageUrl);
        return $this->post($payload);
    }

    public function sendToBranch(int $branchId, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $payload = [
            'app_id' => $this->appId(),
            'filters' => [
                ['field' => 'tag', 'key' => 'branch_id', 'relation' => '=', 'value' => (string) $branchId],
            ],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ];
        $this->attachImage($payload, $imageUrl);
        return $this->post($payload);
    }

    public function dispatch(
        string $audience,
        string $title,
        string $body,
        array $data = [],
        ?int $userId = null,
        ?int $branchId = null,
        ?string $image = null,
        ?string $imageUrl = null,
        array $externalIds = [],
        string $type = 'general',
    ): array {
        $result = match ($audience) {
            'member' => $this->sendToExternalIds($externalIds, $title, $body, $data, $imageUrl),
            'branch' => $this->sendToBranch((int) $branchId, $title, $body, $data, $imageUrl),
            default => $this->sendToAll($title, $body, $data, $imageUrl),
        };

        $logId = null;
        if ($this->pdo) {
            $repo = new NotificationRepository($this->pdo);
            $logId = $repo->create([
                'user_id' => $audience === 'member' ? $userId : null,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data_json' => $data === [] ? null : json_encode($data),
                'is_read' => 0,
                'image' => $image,
                'audience' => $audience,
                'onesignal_id' => $result['id'] ?? null,
                'onesignal_status' => !empty($result['ok']) ? 'sent' : 'failed',
                'onesignal_response' => (string) ($result['body'] ?? ($result['error'] ?? '')),
            ]);
        }

        error_log('[OneSignal] audience=' . $audience . ' ok=' . (!empty($result['ok']) ? '1' : '0') . ' body=' . substr((string) ($result['body'] ?? $result['error'] ?? ''), 0, 500));

        return $result + ['log_id' => $logId];
    }

    private function attachImage(array &$payload, ?string $imageUrl): void
    {
        if ($imageUrl === null || $imageUrl === '') {
            return;
        }
        $payload['big_picture'] = $imageUrl;
        $payload['ios_attachments'] = ['id' => $imageUrl];
    }

    private function appId(): string
    {
        return (string) ($this->config['onesignal']['app_id'] ?? '');
    }

    private function post(array $payload): array
    {
        $url = $this->config['onesignal']['api_url'] ?? 'https://api.onesignal.com/notifications';
        $key = (string) ($this->config['onesignal']['rest_api_key'] ?? '');
        if ($this->appId() === '' || $key === '') {
            return ['ok' => false, 'error' => 'OneSignal is not configured', 'status' => 0, 'body' => '', 'id' => null];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Key ' . $key,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($res) ? json_decode($res, true) : null;
        $id = is_array($decoded) ? ($decoded['id'] ?? null) : null;

        return [
            'ok' => $res !== false && $code < 400,
            'status' => $code,
            'body' => $res === false ? $err : (string) $res,
            'id' => $id,
            'error' => $res === false ? $err : null,
        ];
    }
}
