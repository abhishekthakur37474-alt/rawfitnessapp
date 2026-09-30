<?php

namespace App\Repositories;

use PDO;

final class SettingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $out;
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            return $default;
        }
        return (string) ($row['setting_value'] ?? $default);
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    public function overlay(array $config): array
    {
        $all = $this->all();
        if (($all['app_name'] ?? '') !== '') {
            $config['app_name'] = $all['app_name'];
        }
        if (($all['onesignal_app_id'] ?? '') !== '') {
            $config['onesignal']['app_id'] = $all['onesignal_app_id'];
        }
        if (($all['onesignal_rest_api_key'] ?? '') !== '') {
            $config['onesignal']['rest_api_key'] = $all['onesignal_rest_api_key'];
        }
        if (($all['otp_provider'] ?? '') !== '') {
            $config['otp']['provider'] = $all['otp_provider'];
        }
        if (($all['otp_apitxt_base_url'] ?? '') !== '') {
            $config['otp']['apitxt']['base_url'] = $all['otp_apitxt_base_url'];
        }
        if (($all['otp_apitxt_api_key'] ?? '') !== '') {
            $config['otp']['apitxt']['api_key'] = $all['otp_apitxt_api_key'];
        }
        if (($all['otp_apitxt_sender'] ?? '') !== '') {
            $config['otp']['apitxt']['sender'] = $all['otp_apitxt_sender'];
        }
        $config['contact'] = [
            'phone' => $all['contact_phone'] ?? '',
            'whatsapp' => $all['contact_whatsapp'] ?? '',
            'email' => $all['contact_email'] ?? '',
        ];
        $config['legal'] = [
            'terms_url' => $all['terms_url'] ?? '',
            'privacy_url' => $all['privacy_url'] ?? '',
        ];
        return $config;
    }
}
