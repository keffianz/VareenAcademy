-- ============================================================
-- VAREEN Academy LMS — Demo Content (courses, modules, lessons,
-- assignments, live classes, enrollments)
-- Idempotent: safe to run multiple times (no duplicates).
-- Run in phpMyAdmin on u374397808_vereen_academy.
-- ============================================================

-- ---------- COURSES (taught by the staff/teacher account) ----------
INSERT INTO courses (teacher_id, title, description, category, price, is_active)
SELECT u.id, 'Web Development Fundamentals',
       'Learn HTML, CSS and JavaScript from scratch and build your first responsive website. Perfect for beginners who want to start a career in tech.',
       'Programming', 25000.00, 1
FROM users u
WHERE u.email = 'staff@vereenacademy.com'
  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.title = 'Web Development Fundamentals');

INSERT INTO courses (teacher_id, title, description, category, price, is_active)
SELECT u.id, 'Computer Basics & Microsoft Office',
       'Master the computer from power button to productivity: Windows, Word, Excel and PowerPoint. Ideal for students, job seekers and small business owners.',
       'Digital Literacy', 10000.00, 1
FROM users u
WHERE u.email = 'staff@vereenacademy.com'
  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.title = 'Computer Basics & Microsoft Office');

INSERT INTO courses (teacher_id, title, description, category, price, is_active)
SELECT u.id, 'Graphic Design with Canva & Photoshop',
       'Create professional flyers, logos and social media designs using Canva and Adobe Photoshop. Build a portfolio that wins clients.',
       'Design', 20000.00, 1
FROM users u
WHERE u.email = 'staff@vereenacademy.com'
  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.title = 'Graphic Design with Canva & Photoshop');

INSERT INTO courses (teacher_id, title, description, category, price, is_active)
SELECT u.id, 'Data Entry & Remote Work Skills',
       'Develop fast, accurate typing and data-entry skills, and learn how to find and keep remote work with international clients.',
       'Digital Skills', 15000.00, 1
FROM users u
WHERE u.email = 'staff@vereenacademy.com'
  AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.title = 'Data Entry & Remote Work Skills');

-- ---------- MODULES ----------
INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Getting Started with the Web', 'Understand how the internet works and set up your tools.', 1, 1
FROM courses c
WHERE c.title = 'Web Development Fundamentals'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Getting Started with the Web');

INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Building Your First Webpage', 'Hands-on HTML and CSS practice.', 2, 1
FROM courses c
WHERE c.title = 'Web Development Fundamentals'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Building Your First Webpage');

INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Computer Essentials', 'Hardware, software and file management basics.', 1, 1
FROM courses c
WHERE c.title = 'Computer Basics & Microsoft Office'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Computer Essentials');

INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Microsoft Word in Practice', 'Documents, formatting and printing.', 2, 1
FROM courses c
WHERE c.title = 'Computer Basics & Microsoft Office'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Microsoft Word in Practice');

INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Design Foundations', 'Colour, layout and typography principles.', 1, 1
FROM courses c
WHERE c.title = 'Graphic Design with Canva & Photoshop'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Design Foundations');

INSERT INTO modules (course_id, title, description, position, is_active)
SELECT c.id, 'Typing & Accuracy Drills', 'Build speed and precision for data entry.', 1, 1
FROM courses c
WHERE c.title = 'Data Entry & Remote Work Skills'
  AND NOT EXISTS (SELECT 1 FROM modules m WHERE m.course_id = c.id AND m.title = 'Typing & Accuracy Drills');

-- ---------- LESSONS ----------
INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'How the Internet Works', 'Clients, servers, browsers and websites explained simply.',
       'https://www.youtube.com/watch?v=7_LPdttKXPc', 720,
       '<p>Welcome to the course! In this lesson we break down what happens when you type a web address and press Enter.</p><p>Take notes — there is a short quiz after this module.</p>', 1, 0, 1
FROM modules m
WHERE m.title = 'Getting Started with the Web'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Web Development Fundamentals')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'How the Internet Works');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Setting Up VS Code', 'Install and configure the editor used by professionals.',
       'https://www.youtube.com/watch?v=fJelY6Oky4A', 900,
       '<p>We install Visual Studio Code and the essential extensions for web development.</p>', 2, 0, 1
FROM modules m
WHERE m.title = 'Getting Started with the Web'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Web Development Fundamentals')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Setting Up VS Code');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'HTML Structure & Tags', 'Headings, paragraphs, links and images.',
       'https://www.youtube.com/watch?v=qz0aGYrrlhU', 1500,
       '<p>Your first HTML document. Follow along and save your file as index.html.</p>', 1, 0, 1
FROM modules m
WHERE m.title = 'Building Your First Webpage'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Web Development Fundamentals')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'HTML Structure & Tags');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Styling with CSS', 'Colours, fonts and the box model.',
       'https://www.youtube.com/watch?v=yfoY53QXEnI', 1800,
       '<p>CSS makes the web beautiful. We style the page we built in the previous lesson.</p>', 2, 1, 1
FROM modules m
WHERE m.title = 'Building Your First Webpage'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Web Development Fundamentals')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Styling with CSS');


INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Parts of a Computer', 'CPU, RAM, storage and peripherals in plain language.',
       'https://www.youtube.com/watch?v=AkFi90lWSmE', 600,
       '<p>Understanding what each component does helps you use and maintain computers confidently.</p>', 1, 0, 1
FROM modules m
WHERE m.title = 'Computer Essentials'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Computer Basics & Microsoft Office')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Parts of a Computer');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Files & Folders Management', 'Create, rename, move and back up your work.',
       'https://www.youtube.com/watch?v=SmLwnfNvM6o', 540,
       '<p>Good file habits save hours. Learn the folder structure professionals use.</p>', 2, 0, 1
FROM modules m
WHERE m.title = 'Computer Essentials'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Computer Basics & Microsoft Office')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Files & Folders Management');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Design Principles That Just Work', 'Contrast, alignment, repetition and proximity.',
       'https://www.youtube.com/watch?v=W0avwgoT1mQ', 1080,
       '<p>The four principles that separate amateur designs from professional ones.</p>', 1, 0, 1
FROM modules m
WHERE m.title = 'Design Foundations'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Graphic Design with Canva & Photoshop')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Design Principles That Just Work');

INSERT INTO lessons (module_id, course_id, title, description, video_url, video_duration, content, position, is_locked, is_active)
SELECT m.id, m.course_id, 'Touch Typing Basics', 'Home row technique and posture.',
       'https://www.youtube.com/watch?v=kQ1E_MybJPA', 480,
       '<p>Speed comes from accuracy first. Practice 15 minutes daily with the drills.</p>', 1, 0, 1
FROM modules m
WHERE m.title = 'Typing & Accuracy Drills'
  AND m.course_id = (SELECT id FROM courses WHERE title = 'Data Entry & Remote Work Skills')
  AND NOT EXISTS (SELECT 1 FROM lessons l WHERE l.module_id = m.id AND l.title = 'Touch Typing Basics');


-- ---------- ASSIGNMENTS ----------
INSERT INTO assignments (course_id, teacher_id, title, description, instructions, due_date, max_score, is_active)
SELECT c.id, c.teacher_id, 'Build Your Profile Page',
       'Create a simple personal profile page using the HTML and CSS you have learned.',
       'Include: your name as a heading, a paragraph about you, a list of skills, and one image. Submit the HTML file.',
       DATE_ADD(NOW(), INTERVAL 14 DAY), 100, 1
FROM courses c
WHERE c.title = 'Web Development Fundamentals'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.course_id = c.id AND a.title = 'Build Your Profile Page');

INSERT INTO assignments (course_id, teacher_id, title, description, instructions, due_date, max_score, is_active)
SELECT c.id, c.teacher_id, 'Format a Business Letter',
       'Produce a correctly formatted one-page business letter in Microsoft Word.',
       'Use proper margins, a letterhead, date, recipient block, body and signature. Submit as .docx or PDF.',
       DATE_ADD(NOW(), INTERVAL 10 DAY), 50, 1
FROM courses c
WHERE c.title = 'Computer Basics & Microsoft Office'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.course_id = c.id AND a.title = 'Format a Business Letter');

-- ---------- LIVE CLASSES ----------
INSERT INTO live_classes (course_id, teacher_id, title, description, scheduled_at, meeting_url, meeting_platform, duration_minutes, status)
SELECT c.id, c.teacher_id, 'Live Q&A: Your First Website',
       'Bring your questions about HTML and CSS. We will debug student code together.',
       DATE_ADD(NOW(), INTERVAL 7 DAY), 'https://meet.google.com/vareen-demo', 'google_meet', 60, 'scheduled'
FROM courses c
WHERE c.title = 'Web Development Fundamentals'
  AND NOT EXISTS (SELECT 1 FROM live_classes lc WHERE lc.course_id = c.id AND lc.title = 'Live Q&A: Your First Website');

INSERT INTO live_classes (course_id, teacher_id, title, description, scheduled_at, meeting_url, meeting_platform, duration_minutes, status)
SELECT c.id, c.teacher_id, 'Live Demo: Word Tips & Tricks',
       'A guided walkthrough of the features students ask about most.',
       DATE_ADD(NOW(), INTERVAL 5 DAY), 'https://meet.google.com/vareen-demo', 'google_meet', 45, 'scheduled'
FROM courses c
WHERE c.title = 'Computer Basics & Microsoft Office'
  AND NOT EXISTS (SELECT 1 FROM live_classes lc WHERE lc.course_id = c.id AND lc.title = 'Live Demo: Word Tips & Tricks');

-- ---------- ENROLLMENTS (the student account) ----------
INSERT INTO enrollments (student_id, course_id, progress, status)
SELECT u.id, c.id, 0.00, 'active'
FROM users u, courses c
WHERE u.email = 'student@vereenacademy.com'
  AND c.title = 'Web Development Fundamentals'
  AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = u.id AND e.course_id = c.id);

INSERT INTO enrollments (student_id, course_id, progress, status)
SELECT u.id, c.id, 35.00, 'active'
FROM users u, courses c
WHERE u.email = 'student@vereenacademy.com'
  AND c.title = 'Computer Basics & Microsoft Office'
  AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = u.id AND e.course_id = c.id);

