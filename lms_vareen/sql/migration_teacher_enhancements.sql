-- VAREEN Academy — Teacher Enhancement Migration
-- Adds bio column to users table for teacher profiles

ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER specialization;
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL AFTER bio;

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
