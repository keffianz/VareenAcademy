-- ============================================================
-- VAREEN Academy — Demo/Seed Data
-- ============================================================
-- Populates tables that are otherwise left empty after importing
-- sql/setup_shared_hosting.sql (or sql/setup.sql).
--
-- All content is based on the real academy website:
--   VAREEN Computer Academy & Cyber Café — Keffi, Nasarawa State, Nigeria
--   Phones : 08130397723 / 08133765467
--   Email  : VEREENacademy@gmail.com
--   Address: Near Total Filling Station, Keffi, Nasarawa State, Nigeria
--
-- IMPORTANT:
--   * Target schema = sql/setup_shared_hosting.sql (the maintained,
--     production shared-hosting schema). Import that file first, then
--     import THIS file once on a fresh database.
--   * LMS demo accounts use bcrypt hashes of the demo password `password123`
--     so they can be signed into immediately. Change these in production.
--   * Seed login accounts (email / password):
--       staff@vereenacademy.com    -> password123   (Teacher / Staff tab, role=teacher)
--       student@vereenacademy.com  -> password123   (Student tab)
--       maryam/emeka/halima/zainab/ibrahim students -> password123 (Student tab)
--   * Foreign keys are resolved with sub-queries so this script is
--     position-independent (no hard-coded auto-increment IDs).
-- ============================================================

-- ============================================================
-- 1. USERS — ensure the LMS has a teacher + demo students
-- ============================================================
INSERT INTO `users`
  (`username`, `email`, `password_hash`, `password`, `full_name`,
   `first_name`, `last_name`, `role`, `is_active`, `email_verified`,
   `phone`, `city`, `country`, `address`)
VALUES
  ('staff', 'staff@vereenacademy.com', '$2y$10$ckK3sE4/sCTxIJSVKo4Q3O6VcDaE.i97WspmfL1gdmaYPOTgegUEW', '$2y$10$ckK3sE4/sCTxIJSVKo4Q3O6VcDaE.i97WspmfL1gdmaYPOTgegUEW',
   'VAREEN Staff', 'VAREEN', 'Staff', 'teacher', 1, 1,
   '08130397723', 'Keffi', 'Nigeria', 'Near Total Filling Station, Keffi, Nasarawa State'),
  ('student', 'student@vereenacademy.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'VAREEN Student', 'VAREEN', 'Student', 'student', 1, 1,
   '08130397723', 'Keffi', 'Nigeria', 'Keffi, Nasarawa State'),
  ('maryam', 'maryam.abubakar@example.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'Maryam Abubakar', 'Maryam', 'Abubakar', 'student', 1, 1,
   '08123456789', 'Keffi', 'Nigeria', 'Keffi, Nasarawa State'),
  ('emeka', 'emeka.nwosu@example.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'Emeka Nwosu', 'Emeka', 'Nwosu', 'student', 1, 1,
   '08129876543', 'Keffi', 'Nigeria', 'Keffi, Nasarawa State'),
  ('halima', 'halima.suleiman@example.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'Halima Suleiman', 'Halima', 'Suleiman', 'student', 1, 1,
   '08134567890', 'Keffi', 'Nigeria', 'Keffi, Nasarawa State'),
  ('zainab', 'zainab.ahmed@example.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'Zainab Ahmed', 'Zainab', 'Ahmed', 'student', 1, 1,
   '08145678901', 'Keana', 'Nigeria', 'Keffi, Nasarawa State'),
  ('ibrahim', 'ibrahim.musa@example.com', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em', '$2y$10$3jI7baG026l0Qg12L8KrPu1RwVbwAwfpChT5Ijy7rnmTRtpdRx3em',
   'Ibrahim Musa', 'Ibrahim', 'Musa', 'student', 1, 1,
   '08156789012', 'Keffi', 'Nigeria', 'Keffi, Nasarawa State')
ON DUPLICATE KEY UPDATE
  `full_name` = VALUES(`full_name`),
  `role` = VALUES(`role`),
  `is_active` = 1,
  `email_verified` = 1;

-- ============================================================
-- 2. SETTINGS — real academy contact details
-- ============================================================
UPDATE `settings` SET `setting_value` = 'VAREEN Computer Academy & Cyber Café'    WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'Leading computer training institute and cyber café in Keffi, Nigeria' WHERE `setting_key` = 'site_description';
UPDATE `settings` SET `setting_value` = 'VEREENacademy@gmail.com'                WHERE `setting_key` = 'contact_email';
UPDATE `settings` SET `setting_value` = '08130397723 / 08133765467'              WHERE `setting_key` = 'contact_phone';
UPDATE `settings` SET `setting_value` = 'Near Total Filling Station, Keffi, Nasarawa State, Nigeria' WHERE `setting_key` = 'address';

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
  ('whatsapp_number', '08130397723', 'string', 'WhatsApp contact number'),
  ('secondary_phone', '08133765467', 'string', 'Secondary contact phone'),
  ('opening_hours', 'Mon - Sat: 8:00 AM - 8:00 PM, Cyber Café: 24/7', 'string', 'Academy opening hours'),
  ('exam_registrations_open', '1', 'boolean', 'Allow exam (WAEC/NECO/JAMB/NABTEB) registrations')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`);

-- ============================================================
-- 3. PROGRAMS — align sample rows with the real site and add the rest
-- ============================================================
UPDATE `programs` SET `name` = 'Basic Computer Skills', `duration` = '4 weeks',
  `description` = 'Perfect for beginners. Learn fundamental computer operations, internet usage, essential software, and file management.'
  WHERE `name` = 'Computer Basics';
UPDATE `programs` SET `name` = 'Programming & Web Development', `duration` = '6 months',
  `description` = 'Full-stack development covering HTML, CSS, JavaScript, PHP, and MySQL, from beginner to job-ready.'
  WHERE `name` = 'Web Development';
UPDATE `programs` SET `name` = 'Data Analysis & Excel Advanced', `duration` = '8 weeks',
  `description` = 'Master advanced Excel functions, pivot tables, data visualization, and business intelligence.'
  WHERE `name` = 'Data Analysis';
UPDATE `programs` SET `name` = 'Cybersecurity Basics',
  `description` = 'Introduction to cybersecurity principles, network security, and ethical hacking basics.'
  WHERE `name` = 'Cybersecurity Fundamentals';

INSERT INTO `programs` (`name`, `description`, `duration`, `fee`, `category`) VALUES
  ('Internet & Email', 'Practical internet browsing, email management, online safety, and everyday web applications.', '2 weeks', 15000.00, 'Basic Computing'),
  ('Digital Marketing', 'Social media marketing, content creation, SEO basics, and online business promotion.', '3 months', 55000.00, 'Digital Marketing'),
  ('Networking & Hardware', 'Computer hardware maintenance, network setup and configuration, and troubleshooting skills.', '10 weeks', 60000.00, 'Networking'),
  ('Mobile App Development', 'Build mobile applications using modern tools and publish them to app stores.', '4 months', 70000.00, 'Programming'),
  ('Database Management', 'Design, administer, and query databases using MySQL and SQL best practices.', '6 weeks', 50000.00, 'Programming'),
  ('Graphic Design with Adobe Suite', 'Professional design with Adobe Photoshop, Illustrator, and modern design tools.', '8 weeks', 60000.00, 'Design'),
  ('Video Editing and Production', 'Edit professional videos, add effects, and produce content for social media and business.', '8 weeks', 55000.00, 'Design'),
  ('Social Media Management', 'Manage business social media accounts, grow audiences, and create engaging content calendars.', '6 weeks', 45000.00, 'Digital Marketing'),
  ('E-commerce and Online Business', 'Start and grow an online business, set up online stores, and handle digital payments.', '10 weeks', 65000.00, 'Digital Marketing'),
  ('IT Support and Troubleshooting', 'Practical computer repair, software installation, and end-user technical support skills.', '6 weeks', 50000.00, 'IT'),
  ('Advanced Programming', 'In-depth Python, JavaScript, and modern web frameworks for serious developers.', '4 months', 90000.00, 'Programming');

-- ============================================================
-- 4. GALLERY — add items using real image files shipped in /images
-- ============================================================
INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `is_featured`) VALUES
  ('Computer Operation Class', 'Hands-on practical class teaching basic computer operations', 'images/Computer-operation-class.png', 'Classes', 1),
  ('Kids Learning Programme', 'Young learners gaining their first digital skills', 'images/kids-learning.png', 'Classes', 1),
  ('Microsoft Office Training', 'Practical training on Microsoft Office applications', 'images/Microsoft-Office-Suite.png', 'Classes', 0),
  ('Graphics Design Lab', 'Creative design projects at the graphics workstation', 'images/Graphics-design.png', 'Classes', 0),
  ('Printing & Photocopy Bay', 'Professional printing, photocopy, and binding services', 'images/Photocopy-and-printing-machin.png', 'Cyber Cafe', 0),
  ('Document Scanning', 'Fast and reliable document scanning services', 'images/Scanning-documents.png', 'Cyber Cafe', 0);

-- ============================================================
-- 5. NEWS — based on the latest updates published on the site
-- ============================================================
INSERT INTO `news`
  (`title`, `content`, `excerpt`, `author_id`, `is_published`, `published_at`, `image_url`)
VALUES
  ('New Advanced Programming Course Launched',
   'We are excited to announce our new advanced programming course covering Python, JavaScript, and modern web development. The course is designed for students who have completed our introductory programming programme and are ready to take the next step toward a professional development career.',
   'Advanced programming course now covering Python, JavaScript, and web development.',
   (SELECT `id` FROM `users` WHERE `username` = 'staff'), 1, '2024-12-15 10:00:00', NULL),
  ('Cyber Café 24/7 Service Now Available',
   'Extended hours for our cyber café services to better serve our community''s digital needs around the clock. Printing, photocopying, scanning, and internet services are now available 24 hours a day, seven days a week.',
   'Printing, photocopy, scanning, and internet services now available 24/7.',
   (SELECT `id` FROM `users` WHERE `username` = 'staff'), 1, '2024-12-10 09:00:00', NULL),
  ('Student Success Stories',
   'Celebrating our graduates who have successfully launched their careers in tech and secured promising jobs. From data entry clerks to software developers, our alumni are making a real impact across Nasarawa State and beyond.',
   'Celebrating graduates who have launched successful careers in tech.',
   (SELECT `id` FROM `users` WHERE `username` = 'staff'), 1, '2024-12-05 09:00:00', NULL),
  ('Applications Open for New Intake',
   'VAREEN Academy is now accepting applications for its next training intake. Programmes include basic computer skills, Microsoft Office, graphics design, programming and web development, data analysis, networking, and more. Apply online today or visit us near Total Filling Station in Keffi.',
   'New training intake now open - apply online or visit us in Keffi.',
   (SELECT `id` FROM `users` WHERE `username` = 'staff'), 1, '2025-01-08 08:00:00', NULL),
  ('Exam Registration Support: WAEC, NECO, JAMB & NABTEB',
   'We offer expert assistance with government examination registration, including WAEC, NECO, JAMB, and NABTEB. Our experienced team guides students through every step of the process to ensure smooth and successful registration.',
   'Get reliable WAEC, NECO, JAMB, and NABTEB registration support at VAREEN Academy.',
   (SELECT `id` FROM `users` WHERE `username` = 'staff'), 1, '2025-02-02 08:00:00', NULL);

-- ============================================================
-- 6. WEBSITE FORMS — contact, applications, recruitment, exams
-- ============================================================
-- 6a. Contact messages
INSERT INTO `contact_messages` (`name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
  ('Amina Hassan', 'amina.hassan@example.com', '08123456780', 'Tuition fees',
   'Hello, please I would like to know the full fee breakdown for the Graphics Design programme and whether instalment payment is allowed.', 'read', '2025-01-14 11:20:00'),
  ('John Okafor', 'john.okafor@example.com', '08134567891', 'Exam registration',
   'I need help registering for my NECO exams. What documents are required and how long does the process take?', 'unread', '2025-01-20 14:05:00'),
  ('Grace Okafor', 'grace.okafor@example.com', '08145678902', 'CAC registration',
   'Good day, I run a small business and need assistance with CAC registration. Can you also handle tax clearance?', 'replied', '2025-01-08 09:40:00'),
  ('Samuel Adeyemi', 'samuel.adeyemi@example.com', '08156789013', 'Tax clearance',
   'I need a tax clearance certificate urgently for a contract. What is your turnaround time?', 'read', '2025-02-01 16:30:00'),
  ('Fatima Bello', 'fatima.bello@example.com', '08167890124', 'WAEC registration',
   'Please I want to register my daughter for WAEC. What is the deadline and total cost?', 'unread', '2025-02-05 10:15:00'),
  ('Victor Eze', 'victor.eze@example.com', '08178901235', 'Printing services',
   'Do you offer bulk photocopying and binding for students? I need a quote for a large order.', 'unread', '2025-02-08 12:00:00');

-- 6b. Training course applications
INSERT INTO `applications` (`first_name`, `last_name`, `email`, `phone`, `program`, `start_date`, `status`, `notes`, `created_at`) VALUES
  ('Sarah', 'Johnson', 'sarah.johnson@example.com', '08123456781', 'Programming & Web Development', '2025-02-03', 'approved',
   'Placed in the evening cohort for working professionals.', '2025-01-12 10:00:00'),
  ('Michael', 'Adebayo', 'michael.adebayo@example.com', '08134567892', 'Microsoft Office Suite', '2025-02-10', 'approved',
   'Weekend schedule requested.', '2025-01-16 13:30:00'),
  ('Halima', 'Suleiman', 'halima.suleiman@example.com', '08134567890', 'Graphics Design', '2025-01-27', 'completed',
   'Completed course and opened a small design business.', '2025-01-05 09:15:00'),
  ('Zainab', 'Ahmed', 'zainab.ahmed@example.com', '08145678901', 'Online Basic Digital Skills', '2025-02-17', 'pending',
   'Online self-paced cohort.', '2025-02-06 11:45:00'),
  ('Emeka', 'Nwosu', 'emeka.nwosu@example.com', '08129876543', 'Basic Computer Skills', '2025-02-03', 'pending',
   NULL, '2025-02-09 08:50:00');

-- 6c. Recruitment applications (staff recruitment)
INSERT INTO `recruitment_applications`
  (`first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `address`, `city`, `state`, `agency`,
   `position_applied`, `qualification_level`, `years_of_experience`, `specialization`, `previous_employment`,
   `medical_fitness`, `criminal_record`, `criminal_details`, `guarantor_name`, `guarantor_phone`,
   `guarantor_address`, `application_fee`, `status`, `interview_date`, `notes`, `created_at`)
VALUES
  ('Chinedu', 'Okafor', 'chinedu.okafor@example.com', '08123456782', '1990-03-14',
   '12 Gidan Waya, Keffi', 'Keffi', 'Nasarawa', NULL,
   'Computer Instructor', 'B.Sc. Computer Science', 5, 'Programming, Networking',
   'ICT teacher at a community secondary school, Nasarawa State.',
   'fit', 0, NULL, 'Emeka Okafor', '08134567893', 'Keffi, Nasarawa State', 5000.00,
   'interviewed', '2025-02-06 10:00:00', 'Strong candidates for the programming facilitation role.', '2025-01-22 09:00:00'),
  ('Ngozi', 'Eze', 'ngozi.eze@example.com', '08145678903', '1993-07-21',
   '3 Hassan Taiwo Road, Keffi', 'Keffi', 'Nasarawa', NULL,
   'Front Desk Officer / Cyber Café Attendant', 'OND Certificate', 2, 'Customer Service, Office Suite',
   'Receptionist at a business centre in Keffi.',
   'fit', 0, NULL, 'Ifeoma Eze', '08156789014', 'Keffi, Nasarawa State', 5000.00,
   'shortlisted', '2025-02-08 11:00:00', NULL, '2025-01-24 12:30:00'),
  ('Yusuf', 'Musa', 'yusuf.musa@example.com', '08167890125', '1987-11-02',
   'Keffi New Layout, Keffi', 'Keffi', 'Nasarawa', NULL,
   'Computer Instructor', 'HND Electrical/Electronics Engineering', 4, 'Hardware, Networking, Repair',
   'IT support technician at a private firm in Abuja.',
   'fit', 0, NULL, 'Amina Musa', '08178901236', 'Keffi, Nasarawa State', 5000.00,
   'pending', NULL, NULL, '2025-02-03 15:00:00');

-- 6d. Exam registrations (WAEC / NECO / JAMB / NABTEB)
INSERT INTO `exam_registrations`
  (`exam_type`, `full_name`, `email`, `phone`, `address`, `state`, `lga`, `additional_info`, `status`, `created_at`)
VALUES
  ('WAEC', 'Fatima Bello', 'fatima.bello@example.com', '08167890124', 'Keffi, Nasarawa State', 'Nasarawa', 'Keffi',
   'Registering my daughter for Senior WAEC.', 'contacted', '2025-02-05 10:15:00'),
  ('NECO', 'John Okafor', 'john.okafor@example.com', '08134567891', 'Keffi, Nasarawa State', 'Nasarawa', 'Keffi',
   'First time NECO candidate.', 'pending', '2025-01-20 14:05:00'),
  ('JAMB', 'Nneka Okoro', 'nneka.okoro@example.com', '08123456783', 'Keffi, Nasarawa State', 'Nasarawa', 'Keffi',
   'Needs JAMB registration for university admission this year.', 'completed', '2025-01-18 09:00:00'),
  ('NABTEB', 'Kunle Adebayo', 'kunle.adebayo@example.com', '08134567894', 'Keffi, Nasarawa State', 'Nasarawa', 'Keffi',
   'NABTEB candidate 2025.', 'pending', '2025-02-04 13:20:00'),
  ('WAEC', 'Adamu Salami', 'adamu.salami@example.com', '08145678905', 'Keffi, Nasarawa State', 'Nasarawa', 'Keffi',
   'Sitting May/June WASSCE.', 'contacted', '2025-02-07 10:40:00');

-- 6e. "Become an Instructor" applications
INSERT INTO `instructor_applications`
  (`first_name`, `last_name`, `email`, `phone`, `specialization`, `experience_years`,
   `bio`, `cv_url`, `portfolio_url`, `sample_lesson_url`, `additional_info`, `status`, `created_at`)
VALUES
  ('Chinedu', 'Okafor', 'chinedu.okafor@example.com', '08123456782', 'Web Development', 5,
   'Experienced web developer and educator passionate about teaching JavaScript and PHP.', NULL, NULL, NULL,
   'Available for weekend cohorts.', 'approved', '2025-01-22 09:00:00'),
  ('Blessing', 'Danladi', 'blessing.danladi@example.com', '08145678906', 'Graphics Design', 3,
   'Graphic designer with a focus on Adobe Photoshop, Illustrator, and brand design.', NULL, NULL, NULL,
   NULL, 'pending', '2025-02-05 11:15:00'),
  ('Peter', 'Elias', 'peter.elias@example.com', '08156789017', 'Cybersecurity', 4,
   'Network security specialist interested in teaching cybersecurity fundamentals.', NULL, NULL, NULL,
   'Prepared to run lab sessions on Saturdays.', 'pending', '2025-02-09 10:30:00');

-- ============================================================
-- 7. LMS — COURSES, MODULES & LESSONS (teacher = 'staff')
-- ============================================================
INSERT INTO `courses`
  (`teacher_id`, `title`, `description`, `category`, `thumbnail`, `price`, `is_active`)
VALUES
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Basic Computer Skills',
   'Introduction to computer fundamentals, Windows, internet usage, and essential applications for beginners.',
   'Basic Computing', 'images/Computer-operation-class.png', 15000.00, 1),
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Microsoft Office Suite',
   'Practical mastery of Microsoft Word, Excel, PowerPoint, and Access for the modern workplace.',
   'Office Applications', 'images/Microsoft-Office-Suite.png', 35000.00, 1),
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Programming & Web Development',
   'Full-stack web development: HTML, CSS, JavaScript, PHP, and MySQL - from zero to job-ready.',
   'Programming', NULL, 75000.00, 1),
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Graphics Design',
   'Professional graphic design using Adobe Photoshop, Illustrator, and design fundamentals.',
   'Design', 'images/Graphics-design.png', 50000.00, 1),
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Data Analysis & Excel Advanced',
   'Advanced Excel formulas, pivot tables, dashboards, and business intelligence techniques.',
   'Data Science', NULL, 45000.00, 1),
  ((SELECT `id` FROM `users` WHERE `username` = 'staff'), 'Cybersecurity Basics',
   'Introduction to cybersecurity principles, safe online practices, network security, and ethical hacking basics.',
   'Security', NULL, 60000.00, 1);

-- Modules for the courses above
INSERT INTO `modules` (`course_id`, `title`, `description`, `position`, `is_active`)
VALUES
  ((SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 'Getting Started with Computers', 'Overview of computer hardware, operating systems, and the desktop.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 'Internet, Email & Safety', 'Browsing, email, and staying safe online.', 2, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), 'Word & Document Processing', 'Creating, formatting, and printing professional documents.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), 'Excel & Spreadsheets', 'Working with formulas, functions, and charts.', 2, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), 'HTML & CSS Fundamentals', 'Building and styling static web pages.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), 'JavaScript & Interactivity', 'Adding dynamic behaviour to websites.', 2, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), 'PHP & MySQL', 'Server-side programming and databases.', 3, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), 'Design Principles', 'Colour, typography, and layout basics.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), 'Photoshop & Illustrator Projects', 'Hands-on design projects with Adobe tools.', 2, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Data Analysis & Excel Advanced'), 'Advanced Formulas & Pivot Tables', 'Business-critical Excel techniques.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics'), 'Security Fundamentals', 'Threats, passwords, and safe practices.', 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics'), 'Network & System Security', 'Securing devices and networks.', 2, 1);

-- Lessons (content delivered on the LMS)
INSERT INTO `lessons`
  (`module_id`, `course_id`, `title`, `description`, `video_url`, `video_duration`, `content`, `position`, `is_locked`, `is_active`)
VALUES
  ((SELECT `id` FROM `modules` WHERE `title` = 'Getting Started with Computers' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'),
   'Computer Hardware Explained', 'Identify the main parts of a computer and their functions.',
   NULL, 25, 'CPU, RAM, storage, input and output devices; how they all work together.', 1, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Getting Started with Computers' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'),
   'Using Windows & Managing Files', 'Navigating the desktop, folders, and file management.',
   NULL, 30, 'Work with files and folders: copy, move, rename, and organise documents.', 2, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Internet, Email & Safety' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'),
   'Browsing the Web & Email', 'Effective web searching and managing an email account.',
   NULL, 28, 'Attach files, organise inbox, and search the web efficiently.', 1, 1, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Word & Document Processing' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'),
   'Microsoft Word Fundamentals', 'Create, format, and print professional documents in Word.',
   NULL, 35, 'Styles, tables, mail merge, headers/footers, and page setup.', 1, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Excel & Spreadsheets' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'),
   'Excel Basics: Formulas & Charts', 'Enter data, write formulas, and build simple charts.',
   NULL, 40, 'SUM, AVERAGE, COUNT, cell references, and chart creation.', 1, 1, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'HTML & CSS Fundamentals' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'),
   'Your First HTML Page', 'Structure a web page with HTML elements.',
   NULL, 32, 'Headings, paragraphs, links, images, lists, and semantic HTML.', 1, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'HTML & CSS Fundamentals' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'),
   'Styling with CSS', 'Control colours, layout, fonts, and responsiveness with CSS.',
   NULL, 38, 'Selectors, the box model, flexbox, and responsive design.', 2, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'JavaScript & Interactivity' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'),
   'JavaScript Basics', 'Add interactivity to your web pages with JavaScript.',
   NULL, 45, 'Variables, functions, events, and the DOM.', 1, 1, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Design Principles' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'),
   'Colour, Typography & Layout', 'Foundations of visual design for compelling artwork.',
   NULL, 30, 'Colour theory, font pairing, spacing, and composition.', 1, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Photoshop & Illustrator Projects' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'),
   'Designing a Flyer in Photoshop', 'Hands-on project creating a professional flyer.',
   NULL, 42, 'Layers, text effects, image editing, and export for print.', 1, 1, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Advanced Formulas & Pivot Tables' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Data Analysis & Excel Advanced')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Data Analysis & Excel Advanced'),
   'Power Functions & Pivot Tables', 'IF, VLOOKUP, INDEX/MATCH, and pivot table analysis.',
   NULL, 48, 'Business intelligence with advanced Excel techniques.', 1, 0, 1),
  ((SELECT `id` FROM `modules` WHERE `title` = 'Security Fundamentals' AND `course_id` = (SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics')),
   (SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics'),
   'Stay Safe Online', 'Recognise threats and protect your accounts and data.',
   NULL, 27, 'Phishing, strong passwords, two-factor authentication, and scams.', 1, 0, 1);

-- ============================================================
-- 8. LMS — ENROLLMENTS, PROGRESS, LIVE CLASSES
-- ============================================================
INSERT INTO `enrollments` (`student_id`, `course_id`, `progress`, `status`, `enrolled_at`, `completed_at`)
VALUES
  ((SELECT `id` FROM `users` WHERE `username` = 'student'), (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 100.00, 'completed', '2024-11-04 09:00:00', '2025-01-15 17:00:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'student'), (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), 45.00, 'active', '2025-01-20 08:30:00', NULL),
  ((SELECT `id` FROM `users` WHERE `username` = 'maryam'), (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), 100.00, 'completed', '2024-10-01 10:00:00', '2025-03-14 16:00:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'maryam'), (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), 80.00, 'active', '2025-01-27 09:15:00', NULL),
  ((SELECT `id` FROM `users` WHERE `username` = 'emeka'), (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 60.00, 'active', '2025-02-03 08:45:00', NULL),
  ((SELECT `id` FROM `users` WHERE `username` = 'halima'), (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), 100.00, 'completed', '2024-11-11 10:30:00', '2025-01-27 15:30:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'zainab'), (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), 25.00, 'active', '2025-02-17 11:00:00', NULL),
  ((SELECT `id` FROM `users` WHERE `username` = 'ibrahim'), (SELECT `id` FROM `courses` WHERE `title` = 'Data Analysis & Excel Advanced'), 10.00, 'active', '2025-02-10 13:00:00', NULL);

-- Lesson progress (a few completed lessons for the built-in demo student)
INSERT INTO `lesson_progress` (`student_id`, `lesson_id`, `course_id`, `watched_duration`, `is_completed`, `completed_at`)
VALUES
  ((SELECT `id` FROM `users` WHERE `username` = 'student'),
   (SELECT `id` FROM `lessons` WHERE `title` = 'Computer Hardware Explained'),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 25, 1, '2024-11-10 12:00:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'student'),
   (SELECT `id` FROM `lessons` WHERE `title` = 'Using Windows & Managing Files'),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 30, 1, '2024-11-17 14:00:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'student'),
   (SELECT `id` FROM `lessons` WHERE `title` = 'Browsing the Web & Email'),
   (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), 28, 1, '2024-11-24 13:30:00');

-- Live classes
INSERT INTO `live_classes`
  (`course_id`, `teacher_id`, `title`, `description`, `scheduled_at`, `meeting_url`, `meeting_platform`, `duration_minutes`, `recording_url`, `status`)
VALUES
  ((SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Computer Basics Q&A Session', 'Open question-and-answer session on computer fundamentals.', '2025-01-20 15:00:00',
   NULL, 'Google Meet', 60, NULL, 'completed'),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Excel Q&A Live Tutorial', 'Live tutorial on Excel formulas and practical use cases.', '2025-02-20 17:00:00',
   NULL, 'Zoom', 75, NULL, 'scheduled'),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Live Code Along: JavaScript', 'Interactive coding session building a small JavaScript project.', '2025-02-18 18:00:00',
   NULL, 'Google Meet', 90, NULL, 'scheduled'),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Security Awareness Webinar', 'Webinar on protecting personal data and avoiding online scams.', '2025-01-30 16:00:00',
   NULL, 'Zoom', 60, NULL, 'completed');

-- Live class attendance
INSERT INTO `live_class_attendance` (`live_class_id`, `student_id`, `joined_at`, `left_at`, `duration_minutes`)
VALUES
  ((SELECT `id` FROM `live_classes` WHERE `title` = 'Computer Basics Q&A Session'), (SELECT `id` FROM `users` WHERE `username` = 'student'), '2025-01-20 15:02:00', '2025-01-20 16:00:00', 58),
  ((SELECT `id` FROM `live_classes` WHERE `title` = 'Security Awareness Webinar'), (SELECT `id` FROM `users` WHERE `username` = 'maryam'), '2025-01-30 16:05:00', '2025-01-30 17:00:00', 55);

-- ============================================================
-- 9. LMS — ASSIGNMENTS & SUBMISSIONS
-- ============================================================
INSERT INTO `assignments`
  (`course_id`, `teacher_id`, `title`, `description`, `instructions`, `due_date`, `max_score`, `is_active`)
VALUES
  ((SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Create a Personal Document', 'Produce a one-page formatted CV using Microsoft Word skills learned in class.',
   'Use headings, bullet lists, and a table. Save and submit as a .docx file.', '2025-02-14 23:59:00', 100, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Build a Budget Spreadsheet', 'Create a monthly budget spreadsheet with formulas and a chart.',
   'Include income, expenses, totals, and a pie chart. Submit the workbook.', '2025-03-01 23:59:00', 100, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Build a Personal Profile Page', 'Create a responsive personal profile page using HTML and CSS.',
   'Include a header, profile image, about section, and contact links. Deploy or zip the code.', '2025-03-10 23:59:00', 100, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Design a Business Flyer', 'Design a one-page flyer for a local business of your choice.',
   'Use Photoshop or Illustrator. Submit a print-ready PDF plus source file.', '2025-02-25 23:59:00', 100, 1);

-- Assignment submissions (graded examples)
INSERT INTO `submissions`
  (`assignment_id`, `student_id`, `file_path`, `submission_text`, `status`, `submitted_at`, `score`, `feedback`, `graded_at`, `graded_by`)
VALUES
  ((SELECT `id` FROM `assignments` WHERE `title` = 'Create a Personal Document'), (SELECT `id` FROM `users` WHERE `username` = 'student'),
   NULL, 'Submitted my formatted CV as a Word document. Please check the layout and headings.',
   'graded', '2025-02-12 10:15:00', 92, 'Well formatted and professional. Minor adjustment to the table borders for consistency.', '2025-02-13 09:00:00',
   (SELECT `id` FROM `users` WHERE `username` = 'staff')),
  ((SELECT `id` FROM `assignments` WHERE `title` = 'Design a Business Flyer'), (SELECT `id` FROM `users` WHERE `username` = 'halima'),
   NULL, 'Submitted a print-ready PDF of my own design studio flyer.',
   'graded', '2025-02-22 12:30:00', 95, 'Excellent use of colour and typography. Great composition.', '2025-02-23 10:00:00',
   (SELECT `id` FROM `users` WHERE `username` = 'staff')),
  ((SELECT `id` FROM `assignments` WHERE `title` = 'Build a Budget Spreadsheet'), (SELECT `id` FROM `users` WHERE `username` = 'zainab'),
   NULL, 'Submitted my Excel workbook with formulas and a chart tab.',
   'submitted', '2025-02-28 16:45:00', NULL, NULL, NULL, NULL);

-- ============================================================
-- 10. LMS — QUIZZES, QUESTIONS & OPTIONS
-- ============================================================
INSERT INTO `quizzes`
  (`course_id`, `teacher_id`, `title`, `description`, `instructions`, `time_limit_minutes`, `pass_score`, `is_timed`, `is_active`)
VALUES
  ((SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Computer Basics Quiz', 'Covers computer hardware, Windows, and file management.', 'Answer all questions. You need at least 60% to pass.', 15, 60, 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Word & Excel Quiz', 'Tests Microsoft Word and Excel fundamentals.', 'Work quickly and carefully. One attempt allowed.', 20, 60, 1, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'HTML & CSS Quiz', 'Essentials of HTML structure and CSS styling.', 'Multiple choice only.', 15, 60, 0, 1),
  ((SELECT `id` FROM `courses` WHERE `title` = 'Cybersecurity Basics'), (SELECT `id` FROM `users` WHERE `username` = 'staff'),
   'Security Awareness Quiz', 'Safe online habits and recognising threats.', 'Best of luck - one attempt.', 10, 60, 0, 1);

-- Questions for the Computer Basics Quiz
INSERT INTO `quiz_questions` (`quiz_id`, `question_text`, `question_type`, `points`, `position`)
VALUES
  ((SELECT `id` FROM `quizzes` WHERE `title` = 'Computer Basics Quiz'), 'Which component is known as the "brain" of the computer?', 'multiple_choice', 1, 1),
  ((SELECT `id` FROM `quizzes` WHERE `title` = 'Computer Basics Quiz'), 'Which device is an OUTPUT device?', 'multiple_choice', 1, 2),
  ((SELECT `id` FROM `quizzes` WHERE `title` = 'Computer Basics Quiz'), 'The Windows file manager is called Explorer.', 'true_false', 1, 3);

INSERT INTO `quiz_options` (`question_id`, `option_text`, `is_correct`, `position`)
VALUES
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which component is known as the "brain" of the computer?'), 'CPU', 1, 1),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which component is known as the "brain" of the computer?'), 'Monitor', 0, 2),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which component is known as the "brain" of the computer?'), 'Keyboard', 0, 3),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which device is an OUTPUT device?'), 'Scanner', 0, 1),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which device is an OUTPUT device?'), 'Printer', 1, 2),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which device is an OUTPUT device?'), 'Mouse', 0, 3),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'The Windows file manager is called Explorer.'), 'True', 1, 1),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'The Windows file manager is called Explorer.'), 'False', 0, 2);

-- Questions for the HTML & CSS Quiz
INSERT INTO `quiz_questions` (`quiz_id`, `question_text`, `question_type`, `points`, `position`)
VALUES
  ((SELECT `id` FROM `quizzes` WHERE `title` = 'HTML & CSS Quiz'), 'Which tag creates a hyperlink in HTML?', 'multiple_choice', 1, 1),
  ((SELECT `id` FROM `quizzes` WHERE `title` = 'HTML & CSS Quiz'), 'Which CSS property changes the text colour?', 'multiple_choice', 1, 2);

INSERT INTO `quiz_options` (`question_id`, `option_text`, `is_correct`, `position`)
VALUES
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which tag creates a hyperlink in HTML?'), '<a>', 1, 1),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which tag creates a hyperlink in HTML?'), '<link>', 0, 2),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which tag creates a hyperlink in HTML?'), '<href>', 0, 3),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which CSS property changes the text colour?'), 'font-color', 0, 1),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which CSS property changes the text colour?'), 'text-color', 0, 2),
  ((SELECT `id` FROM `quiz_questions` WHERE `question_text` = 'Which CSS property changes the text colour?'), 'color', 1, 3);

-- ============================================================
-- 11. LMS — CERTIFICATES, NOTIFICATIONS, RESOURCES, AI LOG
-- ============================================================
INSERT INTO `certificates` (`certificate_code`, `student_id`, `course_id`, `issued_at`, `revoked`)
VALUES
  ('VR-2025-0001', (SELECT `id` FROM `users` WHERE `username` = 'maryam'), (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'), '2025-03-16 10:00:00', 0),
  ('VR-2025-0002', (SELECT `id` FROM `users` WHERE `username` = 'halima'), (SELECT `id` FROM `courses` WHERE `title` = 'Graphics Design'), '2025-01-29 11:00:00', 0),
  ('VR-2025-0003', (SELECT `id` FROM `users` WHERE `username` = 'student'), (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'), '2025-01-16 09:30:00', 0);

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `related_item_id`, `related_type`, `is_read`, `created_at`)
VALUES
  ((SELECT `id` FROM `users` WHERE `username` = 'student'), 'course', 'Course Completed', 'Congratulations! You have completed Basic Computer Skills and earned a certificate.', NULL, 'course', 1, '2025-01-16 09:35:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'student'), 'assignment', 'Assignment Graded', 'Your assignment "Create a Personal Document" was graded: 92/100.', NULL, 'assignment', 0, '2025-02-13 09:05:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'zainab'), 'live_class', 'Upcoming Live Class', 'Excel Q&A Live Tutorial is scheduled for 2025-02-20 at 5:00 PM.', NULL, 'live_class', 0, '2025-02-19 08:00:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'halima'), 'course', 'Certificate Issued', 'Your Graphics Design certificate VR-2025-0002 is now available.', NULL, 'certificate', 0, '2025-01-29 11:05:00');

INSERT INTO `resources` (`lesson_id`, `course_id`, `title`, `file_path`, `file_type`, `file_size`)
VALUES
  ((SELECT `id` FROM `lessons` WHERE `title` = 'Using Windows & Managing Files'), (SELECT `id` FROM `courses` WHERE `title` = 'Basic Computer Skills'),
   'Windows File Management Cheat Sheet', 'uploads/basic-computer/file-management-cheatsheet.pdf', 'PDF', 240),
  ((SELECT `id` FROM `lessons` WHERE `title` = 'Your First HTML Page'), (SELECT `id` FROM `courses` WHERE `title` = 'Programming & Web Development'),
   'HTML Starter Template', 'uploads/programming/html-starter.zip', 'ZIP', 15),
  ((SELECT `id` FROM `lessons` WHERE `title` = 'Excel Basics: Formulas & Charts'), (SELECT `id` FROM `courses` WHERE `title` = 'Microsoft Office Suite'),
   'Excel Practice Workbook', 'uploads/microsoft-office/excel-practice.xlsx', 'XLSX', 182);

INSERT INTO `ai_conversations` (`student_id`, `lesson_id`, `question`, `answer`, `success`, `created_at`)
VALUES
  ((SELECT `id` FROM `users` WHERE `username` = 'student'), (SELECT `id` FROM `lessons` WHERE `title` = 'Using Windows & Managing Files'),
   'How do I copy and paste a file using keyboard shortcuts?',
   'Select the file and press Ctrl+C to copy, then navigate to the destination folder and press Ctrl+V to paste. For moving, use Ctrl+X instead of Ctrl+C.', 1, '2025-01-05 14:10:00'),
  ((SELECT `id` FROM `users` WHERE `username` = 'maryam'), (SELECT `id` FROM `lessons` WHERE `title` = 'Styling with CSS'),
   'What is the box model in CSS?',
   'The CSS box model describes how every element is rendered as a box with content, padding, borders, and margins. It determines spacing and layout of elements.', 0, '2025-03-02 16:20:00');

-- ============================================================
-- DONE — seed data applied successfully.
-- ============================================================
SELECT 'VAREEN Academy seed data applied successfully.' AS seed_status;