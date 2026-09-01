<?php
/**
 * VEREEN Academy LMS - Main Router
 * Routes requests to appropriate views based on ?page= parameter
 * Wraps all views in a shared HTML page layout
 */

// Start session BEFORE including middleware
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth middleware FIRST so requireRole(), getCurrentUserId(), etc. exist
require_once __DIR__ . '/src/middleware/auth.php';

// Helper function to render a view wrapped in layout
function render_page($view_path, $page_title = 'Dashboard') {
    // Set the page title for the layout
    $GLOBALS['page_title'] = $page_title;
    $GLOBALS['additional_css'] = [];
    $GLOBALS['additional_js'] = [];
    
    // Start output buffering to capture the view output
    ob_start();
    
    // Include the view file
    if (file_exists(__DIR__ . '/' . $view_path)) {
        include __DIR__ . '/' . $view_path;
    } else {
        echo '<div style="padding: 40px; text-align: center;">';
        echo '<h1>404 - View Not Found</h1>';
        echo '<p>The requested view does not exist.</p>';
        echo '</div>';
    }
    
    // Capture the view output
    $view_content = ob_get_clean();
    
    // Set the view content for the layout
    $GLOBALS['view_content'] = $view_content;
    
    // Include the main layout
    include __DIR__ . '/views/layout.php';
}

// Get requested page
$page = $_GET['page'] ?? null;

// Allow login and registration pages without authentication (no layout for auth pages)
if ($page === 'login' || $page === 'register') {
    require_once __DIR__ . '/views/auth/' . $page . '.php';
    exit;
}

// For all other pages, require login
requireLogin();

// Route to the appropriate page
switch ($page) {
    // Student Pages
    case 'student-dashboard':
        requireRole('student');
        render_page('views/student/dashboard.php', 'Dashboard');
        break;

    case 'assignments':
        requireRole('student');
        render_page('views/student/assignments.php', 'Assignments');
        break;

    case 'courses':
        requireRole('student');
        render_page('views/courses.php', 'Courses');
        break;

    case 'lessons':
        requireRole('student');
        render_page('views/lesson.php', 'Lessons');
        break;

    case 'quizzes':
        requireRole('student');
        render_page('views/quizzes.php', 'Quizzes');
        break;

    case 'quiz-attempt':
        requireRole('student');
        render_page('views/quiz-attempt.php', 'Quiz Attempt');
        break;

    case 'quiz-result':
        requireRole('student');
        render_page('views/quiz-result.php', 'Quiz Results');
        break;

    case 'live-classes':
        requireRole('student');
        render_page('views/live-classes.php', 'Live Classes');
        break;

    case 'course-detail':
        requireRole('student');
        render_page('views/course-detail.php', 'Course Details');
        break;

    case 'notifications':
        requireRole('student');
        render_page('views/notifications.php', 'Notifications');
        break;

    case 'profile':
        requireRole('student');
        render_page('views/profile.php', 'Profile');
        break;

    // Teacher Pages
    case 'teacher-dashboard':
        requireRole('teacher');
        render_page('views/teacher/dashboard.php', 'Dashboard');
        break;

    case 'teacher-lesson-editor':
        requireRole('teacher');
        render_page('views/teacher/lesson-editor.php', 'Edit Lesson');
        break;

    case 'teacher-module-editor':
        requireRole('teacher');
        render_page('views/teacher/module-editor.php', 'Edit Module');
        break;

    case 'teacher-quiz-editor':
        requireRole('teacher');
        render_page('views/teacher/quiz-editor.php', 'Edit Quiz');
        break;

    case 'teacher-quiz-attempts':
        requireRole('teacher');
        render_page('views/teacher/quiz-attempts.php', 'Quiz Attempts');
        break;

    case 'teacher-resource-editor':
        requireRole('teacher');
        render_page('views/teacher/resource-editor.php', 'Edit Resource');
        break;

    case 'teacher-live-classes':
        requireRole('teacher');
        render_page('views/teacher/live-classes.php', 'Live Classes');
        break;

    case 'teacher-assignments-editor':
        requireRole('teacher');
        render_page('views/teacher/assignments-editor.php', 'Edit Assignments');
        break;

    // Default: redirect to appropriate dashboard
    case null:
        $role = getCurrentUserRole();
        if ($role === 'student') {
            redirectTo('index.php?page=student-dashboard');
        } elseif ($role === 'teacher') {
            redirectTo('index.php?page=teacher-dashboard');
        } else {
            redirectTo('index.php?page=login');
        }
        break;

    // Unknown page: 404
    default:
        http_response_code(404);
        ob_start();
        echo '<div style="padding: 40px; text-align: center;">';
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>The page you requested does not exist.</p>';
        echo '<a href="index.php">Back to Dashboard</a>';
        echo '</div>';
        $view_content = ob_get_clean();
        include __DIR__ . '/views/layout.php';
        exit;
}


