-- ============================================================================
-- MIGRATION 002 — Course Pricing System (VX-045)
-- Adds pricing metadata to `courses`: currency, duration, delivery mode,
-- level and next cohort date, then seeds official launch prices.
-- Run against the VAREEN LMS database. Idempotent: safe to re-run.
-- ============================================================================

-- Helper pattern: each ALTER is guarded by an INFORMATION_SCHEMA existence check.
SET @db := DATABASE();

SET @ddl := (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'currency') = 0,
    'ALTER TABLE courses ADD COLUMN currency VARCHAR(8) NOT NULL DEFAULT ''NGN'' AFTER price',
    'SELECT 1'));
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl := (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'duration_weeks') = 0,
    'ALTER TABLE courses ADD COLUMN duration_weeks INT DEFAULT NULL AFTER currency',
    'SELECT 1'));
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl := (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'delivery_mode') = 0,
    'ALTER TABLE courses ADD COLUMN delivery_mode ENUM(''on_campus'',''online'',''hybrid'') NOT NULL DEFAULT ''online'' AFTER duration_weeks',
    'SELECT 1'));
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl := (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'level') = 0,
    'ALTER TABLE courses ADD COLUMN level ENUM(''beginner'',''intermediate'',''advanced'') NOT NULL DEFAULT ''beginner'' AFTER delivery_mode',
    'SELECT 1'));
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl := (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'next_cohort') = 0,
    'ALTER TABLE courses ADD COLUMN next_cohort DATE DEFAULT NULL AFTER level',
    'SELECT 1'));
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

-- (No teacher_id MODIFY — foreign key courses_ibfk_1 blocks it, and admin API
-- already supports courses without a teacher until they are assigned.
-- If you really need nullable teacher_id on a fresh DB, run this BEFORE
-- creating the FK: ALTER TABLE courses MODIFY teacher_id INT NULL DEFAULT NULL;)

-- ----------------------------------------------------------------------------
-- OFFICIAL LAUNCH PRICES (VAREEN Academy — 2026)
-- Applied to existing courses matching the official titles. Courses that do
-- not exist yet are created by admins (with teacher assignment) and priced
-- here afterwards. Re-running keeps prices in sync.
-- ----------------------------------------------------------------------------
UPDATE courses SET price = 25000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Basic Computer Skills';
UPDATE courses SET price = 45000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Microsoft Office Suite';
UPDATE courses SET price = 70000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Graphics Design';
UPDATE courses SET price = 120000.00, currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Programming & Web Development';
UPDATE courses SET price = 60000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Data Analysis';
UPDATE courses SET price = 80000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Networking';
UPDATE courses SET price = 150000.00, currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Mobile App Development';
UPDATE courses SET price = 70000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Cybersecurity';
UPDATE courses SET price = 80000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Database Management';
UPDATE courses SET price = 85000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Adobe Graphics Design';
UPDATE courses SET price = 75000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Video Editing';
UPDATE courses SET price = 35000.00,  currency = 'NGN', delivery_mode = 'on_campus' WHERE title = 'Social Media Management';

UPDATE courses SET price = 20000.00,  currency = 'NGN', delivery_mode = 'online' WHERE title = 'Basic Digital Skills';
UPDATE courses SET price = 50000.00,  currency = 'NGN', delivery_mode = 'online' WHERE title = 'Advanced Office Skills';
UPDATE courses SET price = 130000.00, currency = 'NGN', delivery_mode = 'online' WHERE title = 'Web Development';
UPDATE courses SET price = 60000.00,  currency = 'NGN', delivery_mode = 'online' WHERE title = 'Digital Marketing';
UPDATE courses SET price = 100000.00, currency = 'NGN', delivery_mode = 'online' WHERE title = 'Cloud Computing & AWS';
UPDATE courses SET price = 80000.00,  currency = 'NGN', delivery_mode = 'online' WHERE title = 'UI/UX Design';
UPDATE courses SET price = 150000.00, currency = 'NGN', delivery_mode = 'online' WHERE title = 'Data Science & Machine Learning';
UPDATE courses SET price = 70000.00,  currency = 'NGN', delivery_mode = 'online' WHERE title = 'Blockchain & Cryptocurrency';
UPDATE courses SET price = 120000.00, currency = 'NGN', delivery_mode = 'online' WHERE title = 'DevOps & Automation';
