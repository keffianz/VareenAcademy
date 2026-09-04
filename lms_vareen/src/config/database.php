<?php
/**
 * Database Configuration
 *
 * SECURITY: Credentials are loaded from (in order of priority):
 *   1. Environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME)
 *   2. Local untracked config: src/config/local_db.php  (copy local_db.example.php)
 *   3. Safe defaults that deliberately do not contain credentials
 *
 * NEVER commit real production credentials to version control.
 * The credentials previously committed here must be considered exposed:
 * rotate that database user's password on the production server.
 */

$__localDb = [];
$__localFile = __DIR__ . '/local_db.php';
if (is_file($__localFile)) {
    $__localDb = require $__localFile;
    if (!is_array($__localDb)) {
        $__localDb = [];
    }
}

$__dbCfg = static function (string $key, string $envVar, string $default) use ($__localDb): string {
    $fromEnv = getenv($envVar);
    if ($fromEnv !== false && $fromEnv !== '') {
        return $fromEnv;
    }
    if (isset($__localDb[$key]) && $__localDb[$key] !== '') {
        return (string)$__localDb[$key];
    }
    return $default;
};

define('DB_HOST', $__dbCfg('host', 'DB_HOST', 'localhost'));
define('DB_USER', $__dbCfg('user', 'DB_USER', ''));
define('DB_PASS', $__dbCfg('pass', 'DB_PASS', ''));
define('DB_NAME', $__dbCfg('name', 'DB_NAME', 'u374397808_vereen_academy'));

// Application constants
define('APP_NAME', 'VAREEN Academy');
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
