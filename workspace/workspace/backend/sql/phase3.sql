SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- Phase 3: Workout Plans + Diet Plans
-- Tables are also auto-created by App\Support\Schema::migrate on first request.

CREATE TABLE IF NOT EXISTS workout_plans (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workout_days (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_id INT UNSIGNED NOT NULL,
  day_number INT UNSIGNED NOT NULL DEFAULT 1,
  title VARCHAR(160) DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_workout_days_plan (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workout_exercises (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS diet_plans (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS diet_meals (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo seed (safe to edit from the admin panel afterwards)
INSERT INTO workout_plans (title, category, level, description, is_active)
SELECT 'Full Body Beginner', 'Strength', 'beginner', 'A balanced three day routine to build a base.', 1
WHERE NOT EXISTS (SELECT 1 FROM workout_plans);

INSERT INTO workout_plans (title, category, level, description, is_active)
SELECT 'HIIT Fat Burn', 'HIIT', 'advanced', 'High intensity intervals to burn fat fast.', 1
WHERE (SELECT COUNT(*) FROM workout_plans) < 2;

INSERT INTO workout_days (plan_id, day_number, title, notes)
SELECT p.id, 1, 'Day 1 · Push', 'Warm up for 10 minutes'
FROM workout_plans p
WHERE p.title = 'Full Body Beginner'
  AND NOT EXISTS (SELECT 1 FROM workout_days d WHERE d.plan_id = p.id);

INSERT INTO workout_days (plan_id, day_number, title, notes)
SELECT p.id, 2, 'Day 2 · Pull', 'Focus on controlled reps'
FROM workout_plans p
WHERE p.title = 'Full Body Beginner'
  AND (SELECT COUNT(*) FROM workout_days d WHERE d.plan_id = p.id) < 2;

INSERT INTO workout_exercises (day_id, name, sets, reps, rest, notes, sort_order)
SELECT d.id, 'Bench Press', '4', '10', '90s', 'Keep elbows tucked', 0
FROM workout_days d
JOIN workout_plans p ON p.id = d.plan_id
WHERE p.title = 'Full Body Beginner' AND d.day_number = 1
  AND NOT EXISTS (SELECT 1 FROM workout_exercises e WHERE e.day_id = d.id);

INSERT INTO workout_exercises (day_id, name, sets, reps, rest, notes, sort_order)
SELECT d.id, 'Overhead Press', '3', '12', '60s', 'Brace your core', 1
FROM workout_days d
JOIN workout_plans p ON p.id = d.plan_id
WHERE p.title = 'Full Body Beginner' AND d.day_number = 1
  AND (SELECT COUNT(*) FROM workout_exercises e WHERE e.day_id = d.id) < 2;

INSERT INTO workout_exercises (day_id, name, sets, reps, rest, notes, sort_order)
SELECT d.id, 'Deadlift', '4', '8', '120s', 'Keep back neutral', 0
FROM workout_days d
JOIN workout_plans p ON p.id = d.plan_id
WHERE p.title = 'Full Body Beginner' AND d.day_number = 2
  AND NOT EXISTS (SELECT 1 FROM workout_exercises e WHERE e.day_id = d.id);

INSERT INTO diet_plans (title, category, calories, description, is_active)
SELECT 'Lean Bulk', 'Weight Gain', 2600, 'High protein plan to support muscle gain.', 1
WHERE NOT EXISTS (SELECT 1 FROM diet_plans);

INSERT INTO diet_plans (title, category, calories, description, is_active)
SELECT 'Fat Loss', 'Weight Loss', 1800, 'Calorie controlled plan for steady fat loss.', 1
WHERE (SELECT COUNT(*) FROM diet_plans) < 2;

INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
SELECT p.id, 'Breakfast', 'Oats, whey protein, banana, almonds', 550, '08:00', 0
FROM diet_plans p
WHERE p.title = 'Lean Bulk'
  AND NOT EXISTS (SELECT 1 FROM diet_meals m WHERE m.plan_id = p.id);

INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
SELECT p.id, 'Lunch', 'Rice, grilled chicken, salad', 750, '13:00', 1
FROM diet_plans p
WHERE p.title = 'Lean Bulk'
  AND (SELECT COUNT(*) FROM diet_meals m WHERE m.plan_id = p.id) < 2;

INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
SELECT p.id, 'Snack', 'Greek yogurt, mixed nuts', 300, '17:30', 2
FROM diet_plans p
WHERE p.title = 'Lean Bulk'
  AND (SELECT COUNT(*) FROM diet_meals m WHERE m.plan_id = p.id) < 3;

INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
SELECT p.id, 'Breakfast', 'Egg white omelette, toast', 350, '08:00', 0
FROM diet_plans p
WHERE p.title = 'Fat Loss'
  AND NOT EXISTS (SELECT 1 FROM diet_meals m WHERE m.plan_id = p.id);

INSERT INTO diet_meals (plan_id, meal_type, items, calories, meal_time, sort_order)
SELECT p.id, 'Lunch', 'Quinoa bowl, veggies, paneer', 550, '13:00', 1
FROM diet_plans p
WHERE p.title = 'Fat Loss'
  AND (SELECT COUNT(*) FROM diet_meals m WHERE m.plan_id = p.id) < 2;
