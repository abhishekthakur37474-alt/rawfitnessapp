<?php

namespace App\Support;

use PDO;

final class Schema
{
    public static function migrate(PDO $pdo, array $config): void
    {
        $pdo->exec("SET NAMES utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id VARCHAR(16) NOT NULL,
            mobile VARCHAR(15) NOT NULL,
            name VARCHAR(120) DEFAULT NULL,
            gender ENUM('male','female','other') DEFAULT NULL,
            height_cm DECIMAL(5,1) DEFAULT NULL,
            is_onboarded TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_users_member_id (member_id),
            UNIQUE KEY uq_users_mobile (mobile)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS otp_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            mobile VARCHAR(15) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            consumed_at DATETIME DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_otp_mobile_created (mobile, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_gov_ids (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('aadhaar','pan','passport','driving_license') NOT NULL,
            id_number VARCHAR(64) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
            reason VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gov_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_admin_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS device_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(255) NOT NULL,
            platform VARCHAR(32) DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_device_token (token),
            KEY idx_device_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS onesignal_subscriptions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            subscription_id VARCHAR(191) NOT NULL,
            platform VARCHAR(32) DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_onesignal_sub (subscription_id),
            KEY idx_onesignal_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(64) NOT NULL,
            setting_value TEXT,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_settings_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $email = $config['admin']['seed_email'] ?? 'admin@rawfitness.local';
        $exists = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if (!$exists->fetch()) {
            $ins = $pdo->prepare('INSERT INTO admin_users (name, email, password_hash) VALUES (?, ?, ?)');
            $ins->execute([
                $config['admin']['seed_name'] ?? 'Gym Admin',
                $email,
                password_hash($config['admin']['seed_password'] ?? 'Admin@123', PASSWORD_DEFAULT),
            ]);
        }

        self::upsertSetting($pdo, 'onesignal_app_id', $config['onesignal']['app_id'] ?? '');
        self::upsertSetting($pdo, 'onesignal_rest_api_key', $config['onesignal']['rest_api_key'] ?? '');
        self::upsertSetting($pdo, 'app_name', $config['app_name'] ?? 'Raw Fitness');
    }

    private static function upsertSetting(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare('SELECT id, setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            $ins = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
            $ins->execute([$key, $value]);
            return;
        }
        if (($row['setting_value'] ?? '') === '' && $value !== '') {
            $upd = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
            $upd->execute([$value, $key]);
        }
    }
}
