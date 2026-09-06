-- ============================================================
-- Fix seeded demo student/staff accounts whose passwords were
-- placeholder bcrypt values that never matched any plaintext.
-- (Admin login worked; Student and Teacher/Staff tabs always
-- returned "Invalid email or password".)
--
-- Run once, e.g.:
--   mysql -u root vareen_lms < lms_vareen/database/migration_fix_seed_passwords.sql
-- or re-import the corrected sql/setup_shared_hosting.sql.
--
-- The specific intended login column is `users`.`password`.
-- (Old) placeholder hashes are matched so real accounts are never touched.
-- New hashes below are bcrypt of the demo password: password123
--   student -> log in on the Student tab
--   staff   -> role=teacher, log in on the Teacher / Staff tab
-- ============================================================

UPDATE `users`
SET `password` = '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em'
WHERE `role` = 'student'
  AND `password` = '$2y$10$3VYfifncJsqfw.BgXwkpfufL6kfPeNkDYkEkgtshfiXKW7ogVmhpu';

UPDATE `users`
SET `password` = '$2y$10$ckK3sE4/sCTxIJSVKo4Q3O6VcDaE.i97WspmfL1gdmaYPOTgegUEW'
WHERE `role` = 'teacher'
  AND `password` = '$2y$10$OzSxBaULfT9mCjxCk6jQqeFG1OYctbhuxjjtJ85Cn62np2PPci.vi';

-- If your host uses the shared-hosting schema that also stores
-- `password_hash`, mirror the new hashes there too.
UPDATE `users`
SET `password_hash` = `password`
WHERE `role` IN ('student', 'teacher')
  AND `password` IN (
    '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
    '$2y$10$ckK3sE4/sCTxIJSVKo4Q3O6VcDaE.i97WspmfL1gdmaYPOTgegUEW'
  )
  AND (`password_hash` IS NULL OR `password_hash` <> `password`);