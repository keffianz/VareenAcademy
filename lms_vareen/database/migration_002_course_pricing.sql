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
-- OFFICIAL LAUNCH CATALOGUE (VX-055) — the 21 programs advertised on the site
--   On-Campus : 12 programs  (delivery_mode = 'on_campus')
--   Online    :  9 programs  (delivery_mode = 'online')
--
-- Idempotent: a course is only created when no row with that exact title
-- already exists, so re-running never duplicates rows and never touches the
-- 4 legacy/demo courses that are already in the database.
--
-- teacher_id is NOT NULL and has a FK to users(id), so every seeded course is
-- assigned to the first `teacher` account. If the database has no teacher yet,
-- the first `admin` account is used as a temporary owner — reassign it in
-- Admin -> Courses (or with the SQL at the bottom of this file) afterwards.
-- If the database has neither a teacher nor an admin, the whole seed is
-- skipped without error and courses can still be created from the Admin panel.
-- ----------------------------------------------------------------------------
SET @teacher := (SELECT id FROM users WHERE role = 'teacher' ORDER BY id LIMIT 1);
SET @teacher := IFNULL(@teacher, (SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1));

INSERT INTO courses
    (teacher_id, title, description, category, thumbnail, price, currency,
     duration_weeks, delivery_mode, level, next_cohort, is_active)
SELECT @teacher, v.title, v.description, v.category, NULL,
       v.price, 'NGN', v.duration_weeks, v.delivery_mode, v.level, NULL, 1
FROM (
    -- ------------------------- On-Campus (12) -------------------------
    SELECT 'Basic Computer Skills' AS title,
           'Perfect for beginners. Learn fundamental computer operations, internet usage and the essential software every modern workplace expects. Covers computer fundamentals, file management, internet and email, plus Microsoft Office basics.' AS description,
           'Computer Skills' AS category, 25000.00 AS price,
           4 AS duration_weeks, 'on_campus' AS delivery_mode, 'beginner' AS level
    UNION ALL SELECT 'Microsoft Office Suite',
           'Master the Microsoft Office applications used in modern workplaces: Word processing, Excel spreadsheets, PowerPoint presentations and Access database management.',
           'Office Productivity', 45000.00, 6, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Graphics Design',
           'Professional graphic design using industry-standard tools and techniques. Covers Adobe Photoshop, Adobe Illustrator, logo design, and print plus digital media production.',
           'Design', 70000.00, 8, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Programming & Web Development',
           'A complete introduction to programming and web development with modern technologies. Covers HTML, CSS, JavaScript, Python, database design and full project builds.',
           'Programming', 120000.00, 12, 'on_campus', 'advanced'
    UNION ALL SELECT 'Data Analysis',
           'Master advanced spreadsheet functions and data analysis techniques for business intelligence, including data visualisation, pivot tables and reporting.',
           'Data & Analytics', 60000.00, 8, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Networking',
           'Computer hardware maintenance, network setup, configuration and troubleshooting. Covers hardware components, network protocols, IP addressing and system maintenance.',
           'Networking & Hardware', 80000.00, 10, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Mobile App Development',
           'Design and build mobile applications for Android and iOS. Covers Android development, iOS development, cross-platform frameworks, working with APIs and app store deployment.',
           'Programming', 150000.00, 14, 'on_campus', 'advanced'
    UNION ALL SELECT 'Cybersecurity',
           'Essential security concepts, defensive best practice and protection techniques for individuals and organisations. Covers network security, data protection, threat awareness and security best practices.',
           'Cybersecurity', 70000.00, 8, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Database Management',
           'Design, build and administer relational databases. Covers SQL fundamentals, database design and normalisation, MySQL and PostgreSQL, backup and data administration.',
           'Data & Analytics', 80000.00, 10, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Adobe Graphics Design',
           'Professional graphic design using the full Adobe Creative Suite. Covers Adobe Photoshop, Illustrator and InDesign, print preparation and complete design projects.',
           'Design', 85000.00, 10, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Video Editing',
           'Professional video editing and production for content creators and businesses. Covers Adobe Premiere Pro, footage production, colour grading and content delivery.',
           'Media Production', 75000.00, 12, 'on_campus', 'intermediate'
    UNION ALL SELECT 'Social Media Management',
           'Plan, manage and grow a business social media presence. Covers platform management, content strategy, analytics and reporting, and community engagement.',
           'Digital Marketing', 35000.00, 8, 'on_campus', 'beginner'
    -- --------------------------- Online (9) ---------------------------
    UNION ALL SELECT 'Basic Digital Skills',
           'Start from zero and become confident with everyday digital tools. Covers computer basics, internet and email, online safety and digital productivity.',
           'Computer Skills', 20000.00, 6, 'online', 'beginner'
    UNION ALL SELECT 'Advanced Office Skills',
           'Take Microsoft Office to an advanced level. Covers advanced Word documents, Excel functions and data tools, PowerPoint storytelling and Access databases.',
           'Office Productivity', 50000.00, 8, 'online', 'intermediate'
    UNION ALL SELECT 'Web Development',
           'Build modern, responsive websites from scratch. Covers HTML, CSS, JavaScript, front-end frameworks, back-end fundamentals and deployment.',
           'Programming', 130000.00, 12, 'online', 'advanced'
    UNION ALL SELECT 'Digital Marketing',
           'Grow brands online with modern digital marketing. Covers search engine optimisation, social media marketing, content marketing, email campaigns and analytics.',
           'Digital Marketing', 60000.00, 8, 'online', 'intermediate'
    UNION ALL SELECT 'Cloud Computing & AWS',
           'Deploy and manage systems in the cloud with AWS. Covers cloud fundamentals, compute, storage, networking, identity and access management plus cost control.',
           'Cloud & DevOps', 100000.00, 10, 'online', 'advanced'
    UNION ALL SELECT 'UI/UX Design',
           'Design digital products people love to use. Covers user research, wireframing, prototyping, visual design systems and usability testing in Figma.',
           'Design', 80000.00, 8, 'online', 'intermediate'
    UNION ALL SELECT 'Data Science & Machine Learning',
           'Analyse data and build predictive models. Covers Python for data science, statistics, data visualisation, machine learning algorithms and model evaluation.',
           'Data & Analytics', 150000.00, 16, 'online', 'advanced'
    UNION ALL SELECT 'Blockchain & Cryptocurrency',
           'Understand blockchain technology and digital assets. Covers how blockchains work, wallets, smart contracts, decentralised applications and security.',
           'Blockchain', 70000.00, 8, 'online', 'intermediate'
    UNION ALL SELECT 'DevOps & Automation',
           'Automate the delivery of reliable software. Covers Linux, Git, CI/CD pipelines, containers with Docker, orchestration and infrastructure as code.',
           'Cloud & DevOps', 120000.00, 10, 'online', 'advanced'
) AS v
WHERE @teacher IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.title = v.title);

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
