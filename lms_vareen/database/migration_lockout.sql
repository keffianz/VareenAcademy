-- ============================================================
-- VAREEN Academy LMS — Per-Account Login Lockout Migration
-- Adds DB-backed brute-force protection that survives cookie
-- clearing (previously the lockout counters lived only in $_SESSION,
-- so a user could bypass the lock by clearing cookies).
--
-- NOTE: run this ONCE. If your MySQL version rejects the stored
-- procedure fallback, run the two plain ALTER statements manually.
-- ============================================================

ALTER TABLE users
    ADD COLUMN failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN locked_until DATETIME NULL;

CREATE INDEX idx_users_locked_until ON users (locked_until);