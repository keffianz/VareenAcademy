-- =====================================================================
-- VAREEN Academy - Batch 2 Migration (reference DDL)
-- =====================================================================
-- NOTE: This file documents the Batch 2 schema changes. The schema is also
-- consolidated in sql/setup_shared_hosting.sql (fresh installs); apply this
-- file's statements manually ONLY when upgrading an older existing database.
-- =====================================================================

-- 1. Allow the same email to exist once PER ROLE (demo accounts).
--    Normal registrations remain globally unique via application-level
--    checks in User::register() / User::emailExists().
ALTER TABLE users DROP INDEX email;
ALTER TABLE users ADD UNIQUE KEY uniq_user_email_role (email, role);

-- 2. Teacher/instructor specialization (public instructor directory)
ALTER TABLE users ADD COLUMN specialization VARCHAR(255) NULL AFTER bio;

-- 3. AI conversation log (required by AIAssistant::logConversation)
CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT,
    success BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_student_date (student_id, created_at),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- 4. "Become an Instructor" applications (admin review queue)
CREATE TABLE IF NOT EXISTS instructor_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    specialization VARCHAR(255) NOT NULL,
    experience_years INT DEFAULT 0,
    bio TEXT,
    cv_url VARCHAR(500),
    portfolio_url VARCHAR(500),
    sample_lesson_url VARCHAR(500),
    additional_info TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_app_status (status),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Certificates (auto-issued on 100% course completion)
CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_code VARCHAR(32) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked BOOLEAN DEFAULT 0,
    revoked_at DATETIME NULL,
    UNIQUE KEY uniq_certificate_student_course (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- 6. Simple key/value site settings (admin editable)
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);