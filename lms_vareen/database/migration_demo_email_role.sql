-- ============================================================
-- VAREEN Academy LMS
-- Migration: allow one email per role (per-role accounts)
--
-- Why: the three DEMO / TEST accounts intentionally share one email
-- address (abubakarabdulrahim663@gmail.com) with one account per role.
-- Real users are unaffected: the application still enforces a unique
-- email at signup (User::emailExists now checks email + role).
--
-- Run once against the application database, e.g.:
--   mysql -u root vareen_lms < migration_demo_email_role.sql
-- ============================================================

-- Step 1: drop the old single-column unique constraint.
-- The index name varies between installs (usually `email`), so delete
-- any unique index on `email` that is not the new composite one.
SET @idx := (
    SELECT DISTINCT INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'email'
      AND INDEX_NAME  <> 'uq_users_email_role'
      AND INDEX_NAME  <> 'PRIMARY'
    LIMIT 1
);
SET @ddl := IF(@idx IS NOT NULL,
    CONCAT('ALTER TABLE `users` DROP INDEX `', @idx, '`'),
    'SELECT "users.email unique index already replaced" AS info'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: add the composite unique constraint (email, role).
SET @ddl := IF(@idx IS NOT NULL,
    'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_email_role` (`email`, `role`)',
    'SELECT "uq_users_email_role already exists" AS info'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: keep single-email lookups fast.
SET @ddl := IF(@idx IS NOT NULL,
    'ALTER TABLE `users` ADD INDEX `idx_users_email` (`email`)',
    'SELECT "idx_users_email already exists" AS info'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
