-- VAREEN Academy — Community & Admin Enhancement Migration
-- Safe to run multiple times (IF NOT EXISTS / INSERT IGNORE)
-- Run this in phpMyAdmin on u374397808_vereen_academy if the community
-- tables are missing — the dashboards degrade gracefully without them,
-- but Community features require these tables.

CREATE TABLE IF NOT EXISTS communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-users',
    category VARCHAR(50) DEFAULT 'general',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_communities_active (is_active),
    INDEX idx_communities_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    UNIQUE KEY uk_community_channel (community_id, slug),
    INDEX idx_channels_community (community_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    channel_id INT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    is_announcement TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES community_channels(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_posts_community (community_id),
    INDEX idx_posts_channel (channel_id),
    INDEX idx_posts_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comments_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_post_user_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NULL,
    comment_id INT NULL,
    reported_by INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending','reviewed','actioned','dismissed') DEFAULT 'pending',
    reviewed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_action (action),
    INDEX idx_activity_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_type ENUM('global','course','teacher','student') DEFAULT 'global',
    target_id INT NULL,
    is_scheduled TINYINT(1) DEFAULT 0,
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_announcements_target (target_type),
    INDEX idx_announcements_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default communities
INSERT IGNORE INTO communities (id, name, slug, description, icon, category, sort_order) VALUES
(1, 'General', 'general', 'General discussion for all', 'fa-comments', 'general', 1),
(2, 'Programming', 'programming', 'Programming languages and coding', 'fa-code', 'academic', 2),
(3, 'Graphic Design', 'graphic-design', 'Design tips and feedback', 'fa-palette', 'academic', 3),
(4, 'AI & Technology', 'ai-technology', 'AI and emerging tech', 'fa-robot', 'academic', 4),
(5, 'NYSC', 'nysc', 'NYSC discussions and support', 'fa-graduation-cap', 'general', 5),
(6, 'Student Showcase', 'student-showcase', 'Show your projects', 'fa-star', 'general', 6),
(7, 'Career Hub', 'career-hub', 'Job tips and career advice', 'fa-briefcase', 'general', 7);

-- Default channels
INSERT IGNORE INTO community_channels (community_id, name, slug, description, sort_order) VALUES
(1, 'Introductions', 'introductions', 'Introduce yourself', 1),
(1, 'Announcements', 'announcements', 'Official announcements', 2),
(1, 'Questions', 'questions', 'Ask questions', 3),
(2, 'HTML', 'html', 'HTML topics', 1),
(2, 'CSS', 'css', 'Styling and layouts', 2),
(2, 'JavaScript', 'javascript', 'JS development', 3),
(2, 'PHP', 'php', 'PHP development', 4),
(2, 'Python', 'python', 'Python programming', 5);
