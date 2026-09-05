-- ============================================================
-- VAREEN Academy — Demo Accounts (v1.0 Release Candidate)
-- ============================================================
-- PURPOSE:
--   Seeds the three demo accounts shown in the login page
--   "Demo Accounts" panel. Each account uses a Gmail alias of the
--   same mailbox but a UNIQUE email string per role, so email
--   uniqueness is preserved and one email never maps to two roles.
--
-- SECURITY NOTES:
--   - Passwords are stored as bcrypt hashes (password_hash), never plaintext.
--   - These are DEMO credentials intended for evaluation / marking.
--   - Rotate or remove these accounts before real production use.
--
-- IDEMPOTENT:
--   - INSERT ... SELECT ... WHERE NOT EXISTS: re-running this file
--     never creates duplicates and never overwrites existing users.
--   - Run this AFTER the base schema (setup_shared_hosting.sql).
-- ============================================================

USE `u374397808_vereen_academy`;

-- Admin demo account
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `password`, `full_name`,
     `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
SELECT
    'demo_admin',
    'abubakarabdulrahim663+admin@gmail.com',
    '$2y$10$xYR8kMpTeVJbu8tbZtOReOq.yuwVs1mDKx8BXHlCkGf6A4GNTAms2',
    '$2y$10$xYR8kMpTeVJbu8tbZtOReOq.yuwVs1mDKx8BXHlCkGf6A4GNTAms2',
    'Demo Administrator',
    'Abubakar',
    'Abdulrahim',
    'admin', 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `users`
    WHERE `email` = 'abubakarabdulrahim663+admin@gmail.com' AND `role` = 'admin'
);

-- Teacher demo account
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `password`, `full_name`,
     `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
SELECT
    'demo_teacher',
    'abubakarabdulrahim663+teacher@gmail.com',
    '$2y$10$/P/rSKChf5y6XvKM4xuhoeadJnILKV8qID3x4vFTkQ.8NfLyeDbFu',
    '$2y$10$/P/rSKChf5y6XvKM4xuhoeadJnILKV8qID3x4vFTkQ.8NfLyeDbFu',
    'Demo Instructor',
    'Abubakar',
    'Abdulrahim',
    'teacher', 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `users`
    WHERE `email` = 'abubakarabdulrahim663+teacher@gmail.com' AND `role` = 'teacher'
);

-- Student demo account
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `password`, `full_name`,
     `first_name`, `last_name`, `role`, `is_active`, `email_verified`)
SELECT
    'demo_student',
    'abubakarabdulrahim663+student@gmail.com',
    '$2y$10$FkmMnPDqfA7gvFyrOQcJoOy0bzvzlOcvytvoK8gjrcwpVJupasfmS',
    '$2y$10$FkmMnPDqfA7gvFyrOQcJoOy0bzvzlOcvytvoK8gjrcwpVJupasfmS',
    'Demo Learner',
    'Abubakar',
    'Abdulrahim',
    'student', 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `users`
    WHERE `email` = 'abubakarabdulrahim663+student@gmail.com' AND `role` = 'student'
);

-- Safety net: make sure demo accounts are usable even if they pre-existed
-- in a deactivated or unverified state (does NOT touch passwords of
-- accounts that already exist).
UPDATE `users` SET `is_active` = 1, `email_verified` = 1
WHERE `email` IN (
    'abubakarabdulrahim663+admin@gmail.com',
    'abubakarabdulrahim663+teacher@gmail.com',
    'abubakarabdulrahim663+student@gmail.com'
) AND `role` IN ('admin', 'teacher', 'student');
