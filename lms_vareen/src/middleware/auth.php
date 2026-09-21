<?php
/**
 * Authentication Middleware
 */

function appBasePath(): string {
    $phpSelf = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('#^/[^/]+#', $phpSelf, $m)) {
        return $m[0]; // e.g. /lms_vareen
    }
    return '';
}

// BASE_URL is used across views for redirects and hrefs. It was never defined
// as a constant, which throws a fatal Error on PHP 8 (undefined constant).
// Define it here so it is available as soon as the middleware is loaded.
if (!defined('BASE_URL')) {
    define('BASE_URL', appBasePath());
}

function redirectTo(string $pathAndQuery): void {
    $base = rtrim(appBasePath(), '/');
    $pathAndQuery = '/' . ltrim($pathAndQuery, '/');
    header('Location: ' . $base . $pathAndQuery);
    exit;
}


function requireLogin() {
    if (isset($_SESSION['user_id'])) {
        return;
    }

    // Prevent redirect loop when user is already on the login page
    $page = $_GET['page'] ?? null;
    if ($page === 'login') {
        return;
    }

    redirectTo('/index.php?page=login');
}



function requireRole($role) {
    requireLogin();

    $currentRole = $_SESSION['role'] ?? null;
    if ($currentRole !== $role) {
        http_response_code(403);
        die('Access denied');
    }
}


function requireRoles($roles = []) {
    requireLogin();
    
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        die('Access denied');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}




function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        $timeout = 30 * 60; // 30 minutes
        if (time() - $_SESSION['login_time'] > $timeout) {
            session_destroy();
            redirectTo('index.php?page=login&msg=session_expired');

        }

        $_SESSION['login_time'] = time();
    }
}


function checkAuth() {
    vaBootSession();
    
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in again.']);
        exit;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
    ];
}

/**
 * Unified, safe session bootstrap used by EVERY entry point (router,
 * API endpoints, auth pages). Ensures identical session cookie
 * attributes everywhere so the browser never silently drops the
 * session cookie between the page load and the API POST — the root
 * cause of "Invalid or missing security token" (403) on login.
 */
function vaBootSession(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        );
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

/**
 * Get or create the CSRF token for the current session.
 */
function csrfToken(): string {
    vaBootSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Reject state-changing requests that do not carry a valid CSRF token.
 * The token may be sent either in the X-CSRF-Token header (JS/fetch flows,
 * which public/js/main.js attaches automatically) or as a csrf_token POST
 * field (native form POST flows). Both are session-bound and equally
 * CSRF-resistant, so accepting either is standard practice.
 */
function requireCsrf(): void {
    vaBootSession();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or missing security token. Please refresh the page and try again.']);
        exit;
    }
}

/**
 * Global relative-time formatter ("just now", "5m ago", "3h ago", "2d ago", date).
 * Single shared helper (VX-012) — previously duplicated per-view or JS-only,
 * which caused a fatal error on the notifications page.
 */
if (!function_exists('timeAgo')) {
    function timeAgo($datetime): string {
        $ts = strtotime((string)$datetime);
        if ($ts === false) { return ''; }
        $diff = time() - $ts;
        if ($diff < 60)     return 'just now';
        if ($diff < 3600)   return floor($diff / 60) . 'm ago';
        if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $ts);
    }
}
