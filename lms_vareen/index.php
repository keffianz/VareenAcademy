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

// Allow login, signup and password reset pages without authentication (no layout for auth pages)
if ($page === 'login' || $page === 'signup' || $page === 'password-reset') {
    require_once __DIR__ . '/views/auth/' . $page . '.php';
    exit;
}
// Public pages: no login required, self-contained views (own header/styles)
if ($page === 'verify' || $page === 'instructors' || $page === 'become-instructor'
    || $page === 'legal-privacy' || $page === 'legal-terms' || $page === 'legal-refund') {
    require_once __DIR__ . '/views/' . $page . '.php';
    exit;
}


// Known routed pages — anything else is a 404, even for unauthenticated visitors,
// so "requires login" never masks "does not exist".
$knownPages = [
    // Student pages
    'student-dashboard', 'assignments', 'courses', 'lessons', 'quizzes', 'quiz-attempt',
    'quiz-result', 'live-classes', 'course-detail', 'notifications', 'profile', 'certificates',
    'checkout', 'my-payments', 'payment-callback', 'certificate-print',
    'student-community', 'student-showcase', 'student-ai', 'post-detail',
    // Teacher pages
    'teacher-dashboard', 'teacher-lesson-editor', 'teacher-module-editor', 'teacher-quiz-editor',
    'teacher-quiz-attempts', 'teacher-resource-editor', 'teacher-live-classes', 'teacher-assignments-editor',
    'teacher-attendance', 'teacher-courses', 'teacher-students', 'teacher-community',
    'teacher-discussions', 'teacher-showcase', 'teacher-ai', 'teacher-analytics',
    'teacher-calendar', 'teacher-profile', 'teacher-settings', 'teacher-grades', 'teacher-progress',
    // Admin pages
    'admin-dashboard', 'admin-users', 'admin-teachers', 'admin-courses', 'admin-lessons',
    'admin-quizzes', 'admin-live', 'admin-reports', 'admin-settings', 'admin-applications',
    'admin-certificates', 'admin-verify', 'admin-payments', 'admin-coupons',
    'admin-community', 'admin-discussions', 'admin-moderation', 'admin-analytics',
    'admin-activity', 'admin-ai', 'admin-notifications',
];
if ($page !== null && !in_array($page, $knownPages, true)) {
    http_response_code(404);
    $view_content = '<div style="padding:40px;text-align:center;">'
        . '<h1>404 - Page Not Found</h1>'
        . '<p>The page you requested does not exist.</p>'
        . '<a href="index.php">Back to Home</a>'
        . '</div>';
    $GLOBALS['view_content'] = $view_content;
    $GLOBALS['page_title'] = 'Page Not Found';
    include __DIR__ . '/views/layout.php';
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
        render_page('views/student/courses.php', 'Browse Courses');
        break;

    case 'lessons':
        requireRole('student');
        render_page('views/student/lessons.php', 'My Lessons');
        break;

    case 'quizzes':
        requireRole('student');
        render_page('views/student/quizzes.php', 'Quizzes');
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
        render_page('views/student/live-classes.php', 'Live Classes');
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

    case 'certificates':
        requireRole('student');
        render_page('views/student/certificates.php', 'My Certificates');
        break;

    case 'checkout':
        requireRole('student');
        render_page('views/checkout.php', 'Checkout');
        break;

    case 'my-payments':
        requireRole('student');
        render_page('views/student/my-payments.php', 'My Payments');
        break;

    case 'payment-callback':
        requireRole('student');
        render_page('views/payment-callback.php', 'Payment Confirmation');
        break;

    case 'certificate-print':
        requireRole('student');
        // Standalone full HTML document (own <!DOCTYPE>) — do not wrap in layout.
        require_once __DIR__ . '/views/certificate-print.php';
        exit;

    case 'student-community':
        requireRole('student');
        render_page('views/student/student-community.php', 'Community Hub');
        break;

    case 'student-showcase':
        requireRole('student');
        render_page('views/student/student-showcase.php', 'Student Showcase');
        break;

    case 'student-ai':
        requireRole('student');
        render_page('views/student/student-ai.php', 'AI Assistant');
        break;

    case 'post-detail':
        requireRole('student');
        render_page('views/student/post-detail.php', 'Discussion');
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

    case 'teacher-attendance':
        requireRole('teacher');
        render_page('views/teacher/attendance.php', 'Attendance');
        break;
    case 'teacher-courses':
        requireRole('teacher');
        render_page('views/teacher/courses.php', 'My Courses');
        break;
    case 'teacher-students':
        requireRole('teacher');
        render_page('views/teacher/students.php', 'My Students');
        break;
    case 'teacher-community':
        requireRole('teacher');
        render_page('views/teacher/community.php', 'Community Hub');
        break;
    case 'teacher-discussions':
        requireRole('teacher');
        render_page('views/teacher/discussions.php', 'Discussions');
        break;
    case 'teacher-showcase':
        requireRole('teacher');
        render_page('views/teacher/showcase.php', 'Student Showcase');
        break;
    case 'teacher-ai':
        requireRole('teacher');
        render_page('views/teacher/ai.php', 'AI Teaching Assistant');
        break;
    case 'teacher-analytics':
        requireRole('teacher');
        render_page('views/teacher/analytics.php', 'Analytics');
        break;
    case 'teacher-calendar':
        requireRole('teacher');
        render_page('views/teacher/calendar.php', 'Calendar');
        break;
    case 'teacher-profile':
        requireRole('teacher');
        render_page('views/teacher/profile.php', 'Profile');
        break;
    case 'teacher-settings':
        requireRole('teacher');
        render_page('views/teacher/settings.php', 'Settings');
        break;
    case 'teacher-grades':
        requireRole('teacher');
        render_page('views/teacher/grades.php', 'Grades');
        break;
    case 'teacher-progress':
        requireRole('teacher');
        render_page('views/teacher/progress.php', 'Student Progress');
        break;

    // Admin Pages
    case 'admin-dashboard':
        requireRole('admin');
        render_page('views/admin/dashboard.php', 'Admin Dashboard');
        break;

    case 'admin-users':
        requireRole('admin');
        render_page('views/admin/users.php', 'Manage Users');
        break;

    case 'admin-courses':
        requireRole('admin');
        render_page('views/admin/courses.php', 'Manage Courses');
        break;

    case 'admin-reports':
        requireRole('admin');
        render_page('views/admin/reports.php', 'Reports');
        break;

    case 'admin-settings':
        requireRole('admin');
        render_page('views/admin/settings.php', 'Settings');
        break;

    case 'admin-applications':
        requireRole('admin');
        render_page('views/admin/applications.php', 'Instructor Applications');
        break;

    case 'admin-certificates':
        requireRole('admin');
        render_page('views/admin/certificates.php', 'Certificate Management');
        break;

    case 'admin-payments':
        requireRole('admin');
        render_page('views/admin/payments.php', 'Payments');
        break;

    case 'admin-teachers':
        requireRole('admin');
        render_page('views/admin/teachers.php', 'Teachers');
        break;

    case 'admin-lessons':
        requireRole('admin');
        render_page('views/admin/lessons.php', 'Lessons');
        break;

    case 'admin-quizzes':
        requireRole('admin');
        render_page('views/admin/quizzes.php', 'Quizzes');
        break;

    case 'admin-live':
        requireRole('admin');
        render_page('views/admin/live.php', 'Live Classes');
        break;

    case 'admin-coupons':
        requireRole('admin');
        render_page('views/admin/coupons.php', 'Coupons');
        break;

    case 'admin-community':
        requireRole('admin');
        render_page('views/admin/community.php', 'Community Hub');
        break;

    case 'admin-discussions':
        requireRole('admin');
        render_page('views/admin/discussions.php', 'Discussions');
        break;

    case 'admin-moderation':
        requireRole('admin');
        render_page('views/admin/moderation.php', 'AI Moderation');
        break;

    case 'admin-analytics':
        requireRole('admin');
        render_page('views/admin/analytics.php', 'Analytics');
        break;

    case 'admin-activity':
        requireRole('admin');
        render_page('views/admin/activity.php', 'Activity Log');
        break;

    case 'admin-ai':
        requireRole('admin');
        render_page('views/admin/ai.php', 'AI Control Center');
        break;

    case 'admin-notifications':
        requireRole('admin');
        render_page('views/admin/notifications.php', 'Notifications');
        break;

    case 'admin-verify':
        requireRole('admin');
        render_page('views/admin/verify.php', 'Certificate Verification');
        break;

    // Default: redirect to appropriate dashboard
    case null:
        $role = getCurrentUserRole();
        if ($role === 'student') {
            redirectTo('index.php?page=student-dashboard');
        } elseif ($role === 'teacher') {
            redirectTo('index.php?page=teacher-dashboard');
        } elseif ($role === 'admin') {
            redirectTo('index.php?page=admin-dashboard');
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


