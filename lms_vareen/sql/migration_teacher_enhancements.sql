-- VAREEN Academy — Teacher Enhancement Migration
-- NOTE: uses MySQL-compatible column-add guards. MariaDB's
-- "ADD COLUMN IF NOT EXISTS" is NOT supported on Hostinger MySQL,
-- so this migration instead creates a tiny helper procedure to check
-- information_schema before altering. Safe to run multiple times.

DELIMITER $$

DROP PROCEDURE IF EXISTS vareen_add_column $$
CREATE PROCEDURE vareen_add_column()
BEGIN
    -- Add bio column if missing (teacher profiles)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bio'
    ) THEN
        ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER specialization;
    END IF;

    -- Add avatar column if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'
    ) THEN
        ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER bio;
    END IF;
END $$

DELIMITER ;

CALL vareen_add_column();
DROP PROCEDURE IF EXISTS vareen_add_column;

-- Teacher announcements (course-specific)
CREATE TABLE IF NOT EXISTS teacher_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_teacher_announcements_teacher (teacher_id),
    INDEX idx_teacher_announcements_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AI conversation log (supports both student lesson-based and teacher general chat)
-- Extended schema: student uses lesson_id+question+answer, teacher uses prompt+response+context
CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id INT NULL,
    lesson_id INT NULL,
    role ENUM('teacher','student','admin') NOT NULL,
    question TEXT NULL,
    answer TEXT NULL,
    prompt TEXT NULL,
    response TEXT NULL,
    context VARCHAR(50) DEFAULT 'general',
    success TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ai_conversations_user (user_id),
    INDEX idx_ai_conversations_student (student_id),
    INDEX idx_ai_conversations_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
