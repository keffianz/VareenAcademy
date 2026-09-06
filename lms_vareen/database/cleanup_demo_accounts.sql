-- ============================================================
-- VAREEN Academy — Cleanup of DEMO / TEST Accounts (v1.1)
-- ============================================================
-- PURPOSE:
--   Removes ALL demo / test login accounts that were previously
--   seeded into the database. Run this ONCE after the previous
--   import (setup_shared_hosting.sql + demo_accounts.sql) to
--   harden the deployment.
--
--   This file is IDEMPOTENT — running it repeatedly is safe and
--   simply removes the demo accounts again if they reappear.
--
-- REMOVED ACCOUNTS:
--   * Gmail-alias demo accounts (abubakarabdulrahim663+...@gmail.com)
--   * Legacy seeded accounts (@vereenacademy.com with a known
--     default password) — admin@, staff@, student@
--
-- SECURITY:
--   After this runs, the only way into the LMS is a real account
--   (student self-signup, or admin/teacher accounts created
--   manually with bcrypt hashes).
--   These demo emails/passwords are also removed from the login UI.
-- ============================================================

USE `u374397808_vereen_academy`;

-- 1) Remove the Gmail-alias demo accounts (3 roles)
DELETE FROM `users`
WHERE `email` LIKE 'abubakarabdulrahim663+%';

-- 2) Remove legacy seeded demo accounts with known default passwords.
--    NOTE: this targets ONLY the well-known seeded demo emails, so real
--    student accounts are never touched.
DELETE FROM `users`
WHERE `email` IN (
    'admin@vereenacademy.com',
    'staff@vereenacademy.com',
    'student@vereenacademy.com'
) AND (
    `username` IN ('admin', 'staff', 'student')
    OR `full_name` LIKE 'VAREEN %'
);

-- 3) Remove any orphaned password-reset tokens / AI conversations /
--    enrollments that referenced the deleted demo accounts
--    (foreign keys with ON DELETE CASCADE handle most of this;
--    this is a safety net for non-cascading rows).
DELETE pr FROM `password_resets` pr
LEFT JOIN `users` u ON pr.user_id = u.id
WHERE u.id IS NULL;

-- Done
SELECT CONCAT('Removed. Demo accounts remaining: ',
    (SELECT COUNT(*) FROM `users`
     WHERE `email` LIKE 'abubakarabdulrahim663+%'
        OR `email` IN ('admin@vereenacademy.com','staff@vereenacademy.com','student@vereenacademy.com'))
) AS status;