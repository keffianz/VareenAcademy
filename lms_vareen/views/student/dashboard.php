<?php
// Require student role
requireRole('student');

require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Course.php';
require_once 'src/classes/Notification.php';

$enrollment = new Enrollment();
$course = new Course();
$notification = new Notification();
$user_id = getCurrentUserId();

// Get student dashboard data
$dashboard = $enrollment->getStudentDashboard($user_id);
$user = new \stdClass();
$user->first_name = $_SESSION['first_name'];
$user->last_name = $_SESSION['last_name'];

// Calculate overall progress
$overall_progress = 0;
if (!empty($dashboard['courses'])) {
    $total_progress = 0;
    foreach ($dashboard['courses'] as $c) {
        $total_progress += $c['progress'];
    }
    $overall_progress = count($dashboard['courses']) > 0 ? round($total_progress / count($dashboard['courses'])) : 0;
}

$stats = $dashboard['stats'] ?? ['total_courses' => 0, 'pending_assignments' => 0, 'unread_notifications' => 0];
$notifications = $dashboard['notifications'] ?? [];
$courses = $dashboard['courses'] ?? [];
$upcoming_classes = $dashboard['upcoming_classes'] ?? [];
$recent_recordings = $dashboard['recent_recordings'] ?? [];
$pending_assignments = $dashboard['pending_assignments'] ?? [];
// Relative-time helper used by the notifications list (line ~278).
// Previously this only existed in JS and never ran — PHP fatalled.
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
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
?>

<div class="dashboard-wrapper">
    <?php $student_active = 'student-dashboard'; include __DIR__ . '/_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-content">
        <!-- Top Bar -->
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">
                <h1>Welcome, <?php echo htmlspecialchars($user->first_name); ?>! 👋</h1>
                <p>Here's what's happening with your courses</p>
            </div>
            <button class="btn btn-logout" id="studentLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <!-- Statistics Cards -->
        <section class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Enrolled Courses</p>
                    <h3 data-target="<?php echo $stats['total_courses'] ?? 0; ?>"><?php echo $stats['total_courses'] ?? 0; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Pending Tasks</p>
                    <h3 data-target="<?php echo $stats['pending_assignments'] ?? 0; ?>"><?php echo $stats['pending_assignments'] ?? 0; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">New Notifications</p>
                    <h3 data-target="<?php echo $stats['unread_notifications'] ?? 0; ?>"><?php echo $stats['unread_notifications'] ?? 0; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Overall Progress</p>
                    <h3><?php echo $overall_progress; ?>%</h3>
                </div>
            </div>
        </section>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="dashboard-left">
                <!-- Enrolled Courses -->
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-book"></i> Enrolled Courses</h2>
                        <a href="/index.php?page=courses" class="btn btn-small btn-outline-primary">
                            Browse More
                        </a>
                    </div>

                    <?php if (empty($courses)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <p>No courses yet. Start learning today!</p>
                            <a href="/index.php?page=courses" class="btn btn-primary btn-small">
                                Browse Courses
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $c): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                                        <span class="badge badge-primary">
                                            <?php echo round($c['progress']); ?>%
                                        </span>
                                    </div>
                                    <p class="course-instructor">
                                        <i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    </p>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo $c['progress']; ?>%"></div>
                                    </div>
                                    <div class="course-meta">
                                        <span>
                                            <i class="fas fa-video"></i>
                                            <?php echo $c['completed_lessons'] . '/' . $c['total_lessons']; ?> lessons
                                        </span>
                                    </div>
                                    <a href="/index.php?page=course-detail&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-small btn-block">
                                        Continue Learning
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Pending Assignments -->
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-tasks"></i> Pending Assignments</h2>
                    </div>

                    <?php if (empty($pending_assignments)): ?>
                        <div class="empty-state small">
                            <i class="fas fa-check-circle"></i>
                            <p>No pending assignments! 🎉</p>
                        </div>
                    <?php else: ?>
                        <div class="assignments-list">
                            <?php foreach ($pending_assignments as $a): ?>
                                <div class="assignment-item">
                                    <div class="assignment-info">
                                        <h4><?php echo htmlspecialchars($a['title']); ?></h4>
                                        <p class="course-name">
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($a['course_title']); ?>
                                        </p>
                                    </div>
                                    <div class="assignment-due">
                                        <?php if ($a['due_date']): ?>
                                            <p class="due-date <?php echo (strtotime($a['due_date']) < time()) ? 'overdue' : ''; ?>">
                                                Due: <?php echo date('M d, Y', strtotime($a['due_date'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <a href="/index.php?page=assignments&id=<?php echo $a['id']; ?>" class="btn btn-small">
                                            <?php echo ($a['submitted'] > 0) ? 'Resubmit' : 'Submit'; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Right Column -->
            <div class="dashboard-right">
                <!-- Upcoming Classes -->
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-video"></i> Upcoming Classes</h2>
                    </div>

                    <?php if (empty($upcoming_classes)): ?>
                        <div class="empty-state small">
                            <i class="fas fa-calendar-alt"></i>
                            <p>No classes scheduled</p>
                        </div>
                    <?php else: ?>
                        <div class="classes-list">
                            <?php foreach ($upcoming_classes as $lc): ?>
                                <div class="class-item">
                                    <div class="class-time">
                                        <p class="time-label"><?php echo date('M d', strtotime($lc['scheduled_at'])); ?></p>
                                        <p class="time-value"><?php echo date('H:i A', strtotime($lc['scheduled_at'])); ?></p>
                                    </div>
                                    <div class="class-details">
                                        <h4><?php echo htmlspecialchars($lc['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($lc['course_title']); ?></p>
                                    </div>
                                    <a href="/index.php?page=live-classes&id=<?php echo $lc['id']; ?>" class="btn btn-small btn-primary">
                                        Join Class
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Recent Recordings -->
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-play-circle"></i> Recent Recordings</h2>
                    </div>

                    <?php if (empty($recent_recordings)): ?>
                        <div class="empty-state small">
                            <i class="fas fa-video"></i>
                            <p>No recordings available</p>
                        </div>
                    <?php else: ?>
                        <div class="recordings-list">
                            <?php foreach ($recent_recordings as $rec): ?>
                                <div class="recording-item">
                                    <div class="recording-thumbnail">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                    <div class="recording-info">
                                        <h4><?php echo htmlspecialchars($rec['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($rec['course_title']); ?></p>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($rec['recording_url']); ?>" target="_blank" class="btn btn-small">
                                        Watch
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Notifications -->
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bell"></i> Notifications</h2>
                        <a href="/index.php?page=notifications" class="btn btn-small btn-outline-primary">
                            View All
                        </a>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="empty-state small">
                            <i class="fas fa-bell"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($notif['message'], 0, 50) . '...'); ?></p>
                                        <small><?php echo timeAgo($notif['created_at']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    /* Shell (wrapper, sidebar, topbar) now comes from the shared
       public/css/dashboard.css — dark premium sidebar, 260px fixed,
       matching Admin & Teacher. Only content styles remain below. */

    /* Statistics Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        gap: 15px;
        align-items: center;
        box-shadow: var(--box-shadow);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        flex-shrink: 0;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        margin: 0;
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-info h3 {
        margin: 5px 0 0;
        font-size: 24px;
        color: #333;
    }

    /* Dashboard Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    /* Dashboard Section */
    .dashboard-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: var(--box-shadow);
        margin-bottom: 30px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .section-header h2 {
        margin: 0;
        font-size: 18px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-header i {
        color: var(--primary-color);
    }

    /* Courses Grid */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }

    .course-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #eee;
        transition: all 0.3s;
    }

    .course-card:hover {
        background: white;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transform: translateY(-3px);
    }

    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
    }

    .course-card h3 {
        margin: 0;
        font-size: 14px;
        color: #333;
        flex: 1;
    }

    .course-instructor {
        margin: 8px 0;
        font-size: 12px;
        color: #666;
    }

    .course-meta {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        font-size: 12px;
        color: #666;
    }

    /* Assignments List */
    .assignments-list,
    .classes-list,
    .recordings-list,
    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .assignment-item,
    .class-item,
    .recording-item,
    .notification-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
        border-left: 3px solid var(--primary-color);
        transition: all 0.3s;
    }

    .assignment-item:hover,
    .class-item:hover,
    .recording-item:hover,
    .notification-item:hover {
        background: white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    }

    .assignment-info h4,
    .class-details h4,
    .recording-info h4,
    .notification-content h4 {
        margin: 0 0 5px;
        font-size: 13px;
        color: #333;
    }

    .assignment-info p,
    .class-details p,
    .recording-info p,
    .notification-content p {
        margin: 0;
        font-size: 12px;
        color: #666;
    }

    .course-name,
    .notification-content small {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .assignment-due {
        text-align: right;
    }

    .due-date {
        margin: 0 0 8px;
        font-size: 12px;
        color: #999;
    }

    .due-date.overdue {
        color: #dc3545;
        font-weight: 600;
    }

    .class-time {
        width: 60px;
        text-align: center;
    }

    .time-label {
        margin: 0;
        font-size: 11px;
        color: #999;
        text-transform: uppercase;
    }

    .time-value {
        margin: 3px 0 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--primary-color);
    }

    .class-details {
        flex: 1;
        margin: 0 15px;
    }

    .recording-thumbnail {
        width: 50px;
        height: 50px;
        background: var(--primary-color);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        margin-right: 10px;
    }

    .recording-info {
        flex: 1;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        flex-shrink: 0;
    }

    .notification-content {
        flex: 1;
        margin: 0 12px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state.small {
        padding: 40px 20px;
    }

    .empty-state i {
        font-size: 48px;
        color: #ddd;
        display: block;
        margin-bottom: 15px;
    }

    .empty-state p {
        margin: 10px 0 15px;
        font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        /* Sidebar drawer handled by shared dashboard.css */

        .topbar-title h1 {
            font-size: 20px;
        }

        .topbar-title p {
            display: none;
        }

        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            padding: 15px;
            flex-direction: column;
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
        }

        .dashboard-section {
            padding: 20px;
            margin-bottom: 20px;
        }

        .courses-grid {
            grid-template-columns: 1fr;
        }

        .section-header {
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .assignment-item,
        .class-item,
        .recording-item,
        .notification-item {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .dashboard-content {
            padding: 15px;
        }

        .stats-cards {
            grid-template-columns: 1fr;
        }

        .stat-card {
            flex-direction: row;
            text-align: left;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }

        .stat-label {
            font-size: 11px;
        }

        .stat-info h3 {
            font-size: 20px;
        }

        .topbar-title h1 {
            font-size: 18px;
        }

        .section-header h2 {
            font-size: 16px;
        }

        .assignment-item,
        .class-item,
        .recording-item,
        .notification-item {
            padding: 12px;
            font-size: 12px;
        }

        .class-time {
            width: 50px;
        }

        .time-value {
            font-size: 12px;
        }
    }
</style>

