-- ============================================================================
-- VAREEN Academy — LAUNCH COURSE SEED (VX-055)
-- File: lms_vareen/database/seed_courses_launch.sql
--
-- WHAT THIS DOES:
--   Inserts the 21 official launch courses (12 on-campus + 9 online)
--   with prices stored in SQL (nothing hardcoded in PHP).
--
-- UPLOAD ORDER (Hostinger phpMyAdmin, run once each, in this order):
--   1) lms_vareen/database/vareen_full_schema.sql   (creates tables)
--   2) lms_vareen/database/migration_002_course_pricing.sql  (adds
--      currency / duration_weeks / delivery_mode / level / next_cohort)
--   3) THIS FILE (inserts the 21 courses)
--
-- SAFE TO RE-RUN: every INSERT is guarded by NOT EXISTS on title, so
--   re-uploading never creates duplicates and never touches your existing
--   courses (legacy/demo rows are left intact).
-- FK-SAFE: no ALTER, no teacher_id MODIFY — courses_ibfk_1 is untouched.
--   teacher_id resolves to the first active teacher (admin fallback);
--   reassign instructors later in Admin -> Courses. If no teacher/admin
--   exists yet, the seed skips cleanly instead of violating NOT NULL.
-- ============================================================================

SET @teacher := (
    SELECT id FROM users
    WHERE role IN ('teacher', 'admin') AND is_active = 1
    ORDER BY FIELD(role, 'teacher', 'admin'), id
    LIMIT 1
);

-- ------------------------- ON-CAMPUS (12) -------------------------

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Basic Computer Skills', 'Perfect for beginners. Computer fundamentals, internet and email, Microsoft Office basics and file management.', 'Computer Skills', NULL, 25000.00, 'NGN', 4, 'on_campus', 'beginner', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Basic Computer Skills');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Microsoft Office Suite', 'Master Word, Excel, PowerPoint and Access for the modern workplace.', 'Office Productivity', NULL, 45000.00, 'NGN', 6, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Microsoft Office Suite');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Graphics Design', 'Professional design with Photoshop and Illustrator: logos, print and digital media.', 'Design', NULL, 70000.00, 'NGN', 8, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Graphics Design');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Programming & Web Development', 'HTML, CSS, JavaScript, Python and databases with real portfolio projects.', 'Programming', NULL, 120000.00, 'NGN', 12, 'on_campus', 'advanced', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Programming & Web Development');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Data Analysis', 'Advanced Excel, pivot tables, charts and business intelligence basics.', 'Data & Analytics', NULL, 60000.00, 'NGN', 8, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Data Analysis');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Networking', 'Hardware components, network setup, troubleshooting and system maintenance.', 'Networking & Hardware', NULL, 80000.00, 'NGN', 10, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Networking');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Mobile App Development', 'Android, iOS and cross-platform apps through to store deployment.', 'Programming', NULL, 150000.00, 'NGN', 14, 'on_campus', 'advanced', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Mobile App Development');
INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Cybersecurity', 'Network security, data protection, ethical hacking and best practices.', 'Cybersecurity', NULL, 70000.00, 'NGN', 8, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Cybersecurity');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Database Management', 'SQL fundamentals, database design, MySQL/PostgreSQL and administration.', 'Data & Analytics', NULL, 80000.00, 'NGN', 10, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Database Management');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Adobe Graphics Design', 'Professional design with the Adobe Creative Suite: Photoshop, Illustrator, InDesign.', 'Design', NULL, 85000.00, 'NGN', 10, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Adobe Graphics Design');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Video Editing', 'Premiere Pro, production, color grading and content creation.', 'Media Production', NULL, 75000.00, 'NGN', 12, 'on_campus', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Video Editing');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Social Media Management', 'Platform management, content strategy, analytics and community engagement.', 'Digital Marketing', NULL, 35000.00, 'NGN', 8, 'on_campus', 'beginner', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Social Media Management');


-- ------------------------- ONLINE (9) -------------------------

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Basic Digital Skills', 'Online-first digital literacy: devices, internet safety, email and everyday apps.', 'Computer Skills', NULL, 20000.00, 'NGN', 4, 'online', 'beginner', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Basic Digital Skills');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Advanced Office Skills', 'Advanced Word, Excel automation, professional decks and Access workflows.', 'Office Productivity', NULL, 50000.00, 'NGN', 8, 'online', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Advanced Office Skills');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Web Development', 'Full-stack web development online: frontend, APIs, databases and deployment.', 'Programming', NULL, 130000.00, 'NGN', 14, 'online', 'advanced', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Web Development');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Digital Marketing', 'SEO, social ads, email funnels, analytics and growth strategy.', 'Digital Marketing', NULL, 60000.00, 'NGN', 8, 'online', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Digital Marketing');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Cloud Computing & AWS', 'Cloud fundamentals, EC2/S3/IAM, deployment and cost management on AWS.', 'Cloud & DevOps', NULL, 100000.00, 'NGN', 10, 'online', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Cloud Computing & AWS');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'UI/UX Design', 'User research, wireframing, Figma prototyping, design systems and usability testing.', 'Design', NULL, 80000.00, 'NGN', 10, 'online', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'UI/UX Design');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Data Science & Machine Learning', 'Python data stack, statistics, visualisation, ML algorithms and model evaluation.', 'Data & Analytics', NULL, 150000.00, 'NGN', 16, 'online', 'advanced', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Data Science & Machine Learning');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'Blockchain & Cryptocurrency', 'How blockchains work: wallets, smart contracts, dApps and security.', 'Blockchain', NULL, 70000.00, 'NGN', 8, 'online', 'intermediate', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'Blockchain & Cryptocurrency');

INSERT INTO courses (teacher_id, title, description, category, thumbnail, price, currency, duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, 'DevOps & Automation', 'Linux, Git, CI/CD pipelines, Docker, orchestration and infrastructure as code.', 'Cloud & DevOps', NULL, 120000.00, 'NGN', 10, 'online', 'advanced', NULL, 1
WHERE @teacher IS NOT NULL AND NOT EXISTS (SELECT 1 FROM courses WHERE title = 'DevOps & Automation');

-- ------------------------- VERIFY (run after import) -------------------------
-- SELECT COUNT(*) AS total, SUM(price > 0) AS priced
-- FROM courses WHERE is_active = 1;
-- Expect: 21 new rows (plus any legacy/demo rows already present), all priced.
