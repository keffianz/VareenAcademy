-- VAREEN Academy Database Setup
-- Fresh MySQL/MariaDB setup script
-- Database: u374397808_vereenacademy
-- Username: u374397808_vereenacademy
-- Password: Abubakar11@

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `u374397808_vereen_academy`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `u374397808_vereen_academy`;

-- Create user with specified credentials
CREATE USER IF NOT EXISTS 'u374397808_vereenacademy'@'localhost' IDENTIFIED BY 'Abubakar11@';

-- Grant all privileges on the database to the user
GRANT ALL PRIVILEGES ON `u374397808_vereen_academy`.* TO 'u374397808_vereenacademy'@'localhost';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- ===========================================
-- TABLES SETUP
-- ===========================================

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Applications Table
CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(191) NOT NULL,
  `last_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `program` VARCHAR(255) NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_program` (`program`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recruitment Applications Table
CREATE TABLE IF NOT EXISTS `recruitment_applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(191) NOT NULL,
  `last_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(191) DEFAULT NULL,
  `state` VARCHAR(191) DEFAULT NULL,
  `agency` VARCHAR(100) DEFAULT NULL,
  `position_applied` VARCHAR(255) DEFAULT NULL,
  `qualification_level` VARCHAR(100) DEFAULT NULL,
  `years_of_experience` INT DEFAULT NULL,
  `specialization` VARCHAR(255) DEFAULT NULL,
  `previous_employment` TEXT DEFAULT NULL,
  `medical_fitness` VARCHAR(50) DEFAULT NULL,
  `criminal_record` TINYINT(1) DEFAULT 0,
  `criminal_details` TEXT DEFAULT NULL,
  `guarantor_name` VARCHAR(191) DEFAULT NULL,
  `guarantor_phone` VARCHAR(50) DEFAULT NULL,
  `guarantor_address` TEXT DEFAULT NULL,
  `application_fee` DECIMAL(10,2) DEFAULT NULL,
  `status` ENUM('pending', 'under_review', 'shortlisted', 'interviewed', 'selected', 'rejected') DEFAULT 'pending',
  `interview_date` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_agency` (`agency`),
  INDEX `idx_position` (`position_applied`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programs Table
CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `duration` VARCHAR(100) DEFAULT NULL,
  `fee` DECIMAL(10,2) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `max_students` INT DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table (for admin/staff)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(191) DEFAULT NULL,
  `role` ENUM('admin', 'staff', 'instructor') DEFAULT 'staff',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News/Announcements Table
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `excerpt` VARCHAR(500) DEFAULT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `is_published` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_author_id` (`author_id`),
  INDEX `idx_is_published` (`is_published`),
  INDEX `idx_published_at` (`published_at`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_is_featured` (`is_featured`),
  INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- SAMPLE DATA INSERTION
-- ===========================================

-- Insert sample programs
INSERT INTO `programs` (`name`, `description`, `duration`, `fee`, `category`) VALUES
('Computer Basics', 'Learn fundamental computer skills including Windows, internet browsing, and basic applications.', '3 months', 25000.00, 'Basic Computing'),
('Microsoft Office Suite', 'Master Microsoft Office applications including Word, Excel, PowerPoint, and Access.', '2 months', 35000.00, 'Office Applications'),
('Graphics Design', 'Learn professional graphic design using Adobe Photoshop, Illustrator, and CorelDRAW.', '4 months', 50000.00, 'Design'),
('Web Development', 'Full-stack web development course covering HTML, CSS, JavaScript, PHP, and MySQL.', '6 months', 75000.00, 'Programming'),
('Data Analysis', 'Learn data analysis and visualization using Excel, SQL, and basic Python.', '3 months', 45000.00, 'Data Science'),
('Cybersecurity Fundamentals', 'Introduction to cybersecurity principles, network security, and ethical hacking basics.', '4 months', 60000.00, 'Security');

-- Insert sample gallery items
INSERT INTO `gallery` (`title`, `description`, `image_url`, `category`, `is_featured`) VALUES
('Computer Lab', 'Modern computer lab equipped with latest technology', 'images/computer-lab-logo.png', 'Facilities', 1),
('Workshop on Coding', 'Interactive coding workshop for students', 'images/Workshop-on-Coding.jpg', 'Events', 1),
('Annual Hackathon', 'Annual programming competition event', 'images/Annual-Hackathon.jpg', 'Events', 1),
('Guest Speaker Session', 'Industry experts sharing knowledge with students', 'images/Guest-Speaker-Session.jpg', 'Events', 0),
('Community Gathering', 'Community outreach program', 'images/Community-Gathering.jpg', 'Community', 0);

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'VAREEN Academy', 'string', 'Website name'),
('site_description', 'Leading computer training institute in Nigeria', 'string', 'Website description'),
('contact_email', 'info@vereenacademy.com', 'string', 'Primary contact email'),
('contact_phone', '+234 xxx xxx xxxx', 'string', 'Primary contact phone'),
('address', 'Nigeria', 'string', 'Academy address'),
('currency', 'NGN', 'string', 'Default currency'),
('maintenance_mode', '0', 'boolean', 'Site maintenance mode'),
('registration_open', '1', 'boolean', 'Allow new registrations');

-- ===========================================
-- STORED PROCEDURES
-- ===========================================

DELIMITER //

-- Procedure to get dashboard statistics
CREATE PROCEDURE `get_dashboard_stats`()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM contact_messages WHERE status = 'unread') as unread_messages,
        (SELECT COUNT(*) FROM applications WHERE status = 'pending') as pending_applications,
        (SELECT COUNT(*) FROM recruitment_applications WHERE status = 'pending') as pending_recruitments,
        (SELECT COUNT(*) FROM programs WHERE is_active = 1) as active_programs;
END //

-- Procedure to update message status
CREATE PROCEDURE `update_message_status`(IN msg_id INT, IN new_status ENUM('unread', 'read', 'replied'))
BEGIN
    UPDATE contact_messages
    SET status = new_status, updated_at = CURRENT_TIMESTAMP
    WHERE id = msg_id;
END //

-- Procedure to get monthly statistics
CREATE PROCEDURE `get_monthly_stats`(IN target_month INT, IN target_year INT)
BEGIN
    SELECT
        COUNT(CASE WHEN status = 'unread' THEN 1 END) as unread_messages,
        COUNT(CASE WHEN status = 'read' THEN 1 END) as read_messages,
        COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_messages
    FROM contact_messages
    WHERE MONTH(created_at) = target_month AND YEAR(created_at) = target_year;
END //

DELIMITER ;

-- ===========================================
-- VIEWS
-- ===========================================

-- View for active programs
CREATE OR REPLACE VIEW `active_programs_view` AS
SELECT * FROM programs WHERE is_active = 1 ORDER BY created_at DESC;

-- View for recent applications
CREATE OR REPLACE VIEW `recent_applications_view` AS
SELECT
    a.*,
    CONCAT(a.first_name, ' ', a.last_name) as full_name,
    p.name as program_name
FROM applications a
LEFT JOIN programs p ON a.program = p.name
ORDER BY a.created_at DESC
LIMIT 50;

-- View for unread messages
CREATE OR REPLACE VIEW `unread_messages_view` AS
SELECT * FROM contact_messages
WHERE status = 'unread'
ORDER BY created_at DESC;

-- ===========================================
-- TRIGGERS
-- ===========================================

DELIMITER //

-- Trigger to update updated_at timestamp
CREATE TRIGGER `contact_messages_updated_at`
    BEFORE UPDATE ON `contact_messages`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //

CREATE TRIGGER `applications_updated_at`
    BEFORE UPDATE ON `applications`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //

CREATE TRIGGER `recruitment_applications_updated_at`
    BEFORE UPDATE ON `recruitment_applications`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- ===========================================
-- FINAL SETUP
-- ===========================================

-- Create admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123' - you should change this
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@vereenacademy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Set proper permissions for the user
GRANT SELECT, INSERT, UPDATE, DELETE ON `vereen_academy`.* TO 'vereenacademy'@'localhost';

-- Final flush
FLUSH PRIVILEGES;

-- Display setup completion message
SELECT 'VAREEN Academy database setup completed successfully!' as status;


-- Exam Registrations Table
CREATE TABLE IF NOT EXISTS `exam_registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_type` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `lga` VARCHAR(100) DEFAULT NULL,
  `additional_info` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'contacted', 'completed') DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_exam_type` (`exam_type`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
