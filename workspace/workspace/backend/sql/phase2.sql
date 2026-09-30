SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- Phase 2: Home + Membership + Packages + Payment UI (Coming Soon)
-- Tables are also auto-created by App\Support\Schema::migrate on first request.

CREATE TABLE IF NOT EXISTS branches (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS packages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo seed (safe to edit from the admin panel afterwards)
INSERT INTO branches (id, name, city, is_active)
SELECT 1, 'Raw Fitness Main', 'Mumbai', 1
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE id = 1);

INSERT INTO packages (name, duration_days, price, discount, description, branch_id, is_active)
SELECT * FROM (
  SELECT 'Monthly' AS name, 30 AS duration_days, 1500.00 AS price, 0.00 AS discount, 'Full gym access for 30 days.' AS description, NULL AS branch_id, 1 AS is_active
  UNION ALL SELECT 'Quarterly', 90, 4000.00, 500.00, 'Three months of training with a discount.', NULL, 1
  UNION ALL SELECT 'Half Yearly', 180, 7500.00, 1500.00, 'Six months membership, best value.', NULL, 1
  UNION ALL SELECT 'Yearly', 365, 12000.00, 3000.00, 'Annual membership with maximum savings.', NULL, 1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM packages);
