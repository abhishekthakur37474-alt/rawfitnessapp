<?php

namespace App\Services;

final class OneSignalService
{
    public function __construct(private array $config)
    {
    }

    public function sendToExternalIds(array $externalIds, string $title, string $body, array $data = []): array
    {
        return $this->post([
            'app_id' => $this->config['app_id'] ?? '',
            'target_channel' => 'push',
            'include_aliases' => ['external_id' => array_values($externalIds)],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ]);
    }

    public function sendToAll(string $title, string $body, array $data = []): array
    {
        return $this->post([
            'app_id' => $this->config['app_id'] ?? '',
            'included_segments' => ['Subscribed Users'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data' => $data,
        ]);
    }

    private function post(array $payload): array
    {
        $url = $this->config['api_url'] ?? 'https://api.onesignal.com/notifications';
        $key = $this->config['rest_api_key'] ?? '';
        if (($this->config['app_id'] ?? '') === '' || $key === '') {
            return ['ok' => false, 'error' => 'OneSignal is not configured'];
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

        return [
            'ok' => $res !== false && $code < 400,
            'status' => $code,
            'body' => $res === false ? $err : $res,
        ];
    }
}
