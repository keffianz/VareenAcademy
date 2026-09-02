<?php
/**
 * Database Configuration
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'u374397808_vereenacademy');
define('DB_PASS', 'Abubakar11@');
define('DB_NAME', 'u374397808_vereen_academy');

// Application constants
define('APP_NAME', 'VEREEN Academy');
define('APP_URL', 'https://vereenacademy.com/lms_vareen/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Session configuration
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes
define('REMEMBER_ME_DAYS', 30);

// Security
// IMPORTANT: In production, these secrets should be stored in environment variables or a .env file
// Never commit real secrets to version control
define('JWT_SECRET', 'sk_live_' . bin2hex(random_bytes(24))); // Generated secure random key - regenerate per environment
define('PASSWORD_MIN_LENGTH', 8);

// Pagination
define('ITEMS_PER_PAGE', 10);
define('DASHBOARD_ITEMS_LIMIT', 5);

// Email configuration (optional)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', '');
define('MAIL_PASS', '');
define('MAIL_FROM', 'noreply@vereenacademy.com');

// File upload allowed types
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov', 'avi']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Error reporting
if (!defined('ENVIRONMENT')) { define('ENVIRONMENT', 'production'); }
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('UTC');
