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

        $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            address VARCHAR(255) DEFAULT NULL,
            city VARCHAR(80) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            whatsapp VARCHAR(20) DEFAULT NULL,
            email VARCHAR(190) DEFAULT NULL,
            lat DECIMAL(10,7) DEFAULT NULL,
            lng DECIMAL(10,7) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(140) NOT NULL,
            duration_days INT UNSIGNED NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0,
            description TEXT,
            branch_id INT UNSIGNED DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_packages_active (is_active),
            KEY idx_packages_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS memberships (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            package_id INT UNSIGNED DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            due_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_memberships_user (user_id),
            KEY idx_memberships_end (end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS membership_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            package_id INT UNSIGNED DEFAULT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            note VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_mreq_user (user_id),
            KEY idx_mreq_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_id INT UNSIGNED DEFAULT NULL,
            user_id INT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            mode ENUM('cash','upi','card','online') NOT NULL DEFAULT 'cash',
            txn_ref VARCHAR(120) DEFAULT NULL,
            status ENUM('pending','success','failed') NOT NULL DEFAULT 'success',
            receipt_no VARCHAR(24) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_payments_user (user_id),
            KEY idx_payments_membership (membership_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS workout_plans (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(160) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            category VARCHAR(80) DEFAULT NULL,
            level ENUM('beginner','intermediate','advanced','all') NOT NULL DEFAULT 'all',
            description TEXT,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_workout_plans_active (is_active),
            KEY idx_workout_plans_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS workout_days (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            plan_id INT UNSIGNED NOT NULL,
            day_number INT UNSIGNED NOT NULL DEFAULT 1,
            title VARCHAR(160) DEFAULT NULL,
            notes VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_workout_days_plan (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS workout_exercises (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            day_id INT UNSIGNED NOT NULL,
            name VARCHAR(160) NOT NULL,
            sets VARCHAR(32) DEFAULT NULL,
            reps VARCHAR(32) DEFAULT NULL,
            rest VARCHAR(32) DEFAULT NULL,
            notes VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_workout_ex_day (day_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS diet_plans (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(160) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            category VARCHAR(80) DEFAULT NULL,
            calories INT UNSIGNED DEFAULT NULL,
            description TEXT,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_diet_plans_active (is_active),
            KEY idx_diet_plans_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS diet_meals (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            plan_id INT UNSIGNED NOT NULL,
            meal_type VARCHAR(80) NOT NULL,
            items TEXT,
            calories INT UNSIGNED DEFAULT NULL,
            meal_time VARCHAR(32) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_diet_meals_plan (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureColumn(
            $pdo,
            'branches',
            'description',
            'description TEXT NULL AFTER email'
        );

        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_timings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED NOT NULL,
            day_of_week TINYINT UNSIGNED NOT NULL,
            open_time TIME DEFAULT NULL,
            close_time TIME DEFAULT NULL,
            is_closed TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_branch_day (branch_id, day_of_week),
            KEY idx_timings_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_photos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED NOT NULL,
            image VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_photos_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS facilities (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            icon VARCHAR(80) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_facility_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_facilities (
            branch_id INT UNSIGNED NOT NULL,
            facility_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (branch_id, facility_id),
            KEY idx_bf_facility (facility_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS parking_info (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED NOT NULL,
            has_parking TINYINT(1) NOT NULL DEFAULT 0,
            two_wheeler TINYINT(1) NOT NULL DEFAULT 0,
            four_wheeler TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY uq_parking_branch (branch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS trainers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(120) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            role VARCHAR(120) DEFAULT NULL,
            bio TEXT,
            phone VARCHAR(20) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_trainers_branch (branch_id),
            KEY idx_trainers_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS trainer_certifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            trainer_id INT UNSIGNED NOT NULL,
            title VARCHAR(160) NOT NULL,
            issuer VARCHAR(160) DEFAULT NULL,
            year VARCHAR(8) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_certs_trainer (trainer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(160) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            description TEXT,
            location VARCHAR(160) DEFAULT NULL,
            starts_at DATETIME DEFAULT NULL,
            ends_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_events_branch (branch_id),
            KEY idx_events_starts (starts_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(160) NOT NULL,
            body TEXT,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ann_branch (branch_id),
            KEY idx_ann_published (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(160) DEFAULT NULL,
            image VARCHAR(255) NOT NULL,
            link_type VARCHAR(32) DEFAULT NULL,
            link_value VARCHAR(255) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_banners_branch (branch_id),
            KEY idx_banners_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED DEFAULT NULL,
            check_in DATETIME NOT NULL,
            check_out DATETIME DEFAULT NULL,
            source ENUM('manual','face_machine') NOT NULL DEFAULT 'manual',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_att_user (user_id),
            KEY idx_att_branch (branch_id),
            KEY idx_att_checkin (check_in)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(190) NOT NULL,
            body TEXT,
            type VARCHAR(32) NOT NULL DEFAULT 'general',
            data_json TEXT,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            image VARCHAR(255) DEFAULT NULL,
            audience VARCHAR(32) NOT NULL DEFAULT 'all',
            onesignal_id VARCHAR(64) DEFAULT NULL,
            onesignal_status VARCHAR(32) DEFAULT NULL,
            onesignal_response TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notif_user (user_id),
            KEY idx_notif_read (is_read),
            KEY idx_notif_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notification_reads (
            notification_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id, user_id),
            KEY idx_nread_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS membership_reminders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            days_before INT NOT NULL,
            sent_on DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reminder_day (membership_id, days_before, sent_on),
            KEY idx_reminder_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::seedFacilities($pdo);

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
        self::upsertSetting($pdo, 'contact_phone', '');
        self::upsertSetting($pdo, 'contact_whatsapp', '');
        self::upsertSetting($pdo, 'contact_email', '');
        self::upsertSetting($pdo, 'otp_provider', $config['otp']['provider'] ?? 'apitxt');
        self::upsertSetting($pdo, 'otp_apitxt_base_url', $config['otp']['apitxt']['base_url'] ?? '');
        self::upsertSetting($pdo, 'otp_apitxt_api_key', $config['otp']['apitxt']['api_key'] ?? '');
        self::upsertSetting($pdo, 'otp_apitxt_sender', $config['otp']['apitxt']['sender'] ?? 'RAWFIT');
        self::upsertSetting($pdo, 'terms_url', '');
        self::upsertSetting($pdo, 'privacy_url', '');
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }

    private static function seedFacilities(PDO $pdo): void
    {
        $defaults = [
            ['Locker room', 'lock'],
            ['Cardio zone', 'directions_run'],
            ['Free weights', 'fitness_center'],
            ['Personal training', 'person'],
            ['Steam / sauna', 'hot_tub'],
            ['Group classes', 'groups'],
        ];
        $exists = $pdo->prepare('SELECT id FROM facilities WHERE name = ? LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO facilities (name, icon, is_active) VALUES (?, ?, 1)');
        foreach ($defaults as [$name, $icon]) {
            $exists->execute([$name]);
            if (!$exists->fetch()) {
                $insert->execute([$name, $icon]);
            }
        }
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
