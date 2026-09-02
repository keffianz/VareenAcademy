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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
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
 * Get or create the CSRF token for the current session.
 */
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Reject state-changing requests that do not carry a valid CSRF token.
 * The token must be sent in the X-CSRF-Token header.
 */
function requireCsrf(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or missing security token. Please refresh the page and try again.']);
        exit;
    }
}
