<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';
require_once 'src/classes/Course.php';
require_once 'src/classes/Community.php';

$userId = getCurrentUserId();
$role = getCurrentUserRole();
$db = (new Database())->connect();
$courseModel = new Course();
$community = new Community();

$allCourses = $courseModel->getAllCourses(1, 100);
$courseList = [];
foreach ($allCourses as $c) {
    if ($role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId) $courseList[] = $c;
}

$totalStudents = 0; $courseIds = array_column($courseList, 'id'); $totalCourses = count($courseList);
foreach ($courseList as $c) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :cid');
    $stmt->execute([':cid' => (int)$c['id']]);
    $totalStudents += (int)$stmt->fetchColumn();
}

$pendingSubmissions = 0;
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id WHERE a.course_id IN ($ph) AND s.score IS NULL");
    $stmt->execute($courseIds);
    $pendingSubmissions = (int)$stmt->fetchColumn();
}

$liveToday = 0; $upcomingClasses = [];
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT l.*, c.title AS course_title FROM live_classes l JOIN courses c ON c.id = l.course_id WHERE l.course_id IN ($ph) AND DATE(l.scheduled_at) = CURDATE() ORDER BY l.scheduled_at ASC");
    $stmt->execute($courseIds);
    $liveToday = $stmt->rowCount();
    $stmt = $db->prepare("SELECT l.*, c.title AS course_title FROM live_classes l JOIN courses c ON c.id = l.course_id WHERE l.course_id IN ($ph) AND l.scheduled_at > NOW() AND l.scheduled_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY l.scheduled_at ASC LIMIT 5");
    $stmt->execute($courseIds);
    $upcomingClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$communityQuestions = $community->totalCount();
$avgProgress = 0;
if ($totalCourses > 0) {
    $stmt = $db->prepare("SELECT AVG(progress_percent) FROM enrollments WHERE course_id IN (" . implode(',', array_fill(0, count($courseIds), '?')) . ")");
    $stmt->execute($courseIds);
    $avgProgress = round((float)$stmt->fetchColumn());
}

$recentActivity = [];
if (!empty($courseIds)) {
    $stmt = $db->prepare("SELECT lp.*, CONCAT(u.first_name,' ',u.last_name) AS student_name, l.title AS lesson_title FROM lesson_progress lp JOIN users u ON u.id=lp.user_id JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id WHERE m.course_id IN (" . implode(',', array_fill(0, count($courseIds), '?')) . ") ORDER BY lp.updated_at DESC LIMIT 8");
    $stmt->execute($courseIds);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='dashboard'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Teacher Dashboard</h1><p>Welcome back! Here's your teaching overview.</p></div>
            <div class="topbar-actions"><a href="/index.php?page=teacher-ai" class="btn btn-ai"><i class="fas fa-robot"></i> AI</a></div>
        </div>
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalStudents; ?></span><span class="kpi-label">Students</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-book"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalCourses; ?></span><span class="kpi-label">Courses</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-tasks"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $pendingSubmissions; ?></span><span class="kpi-label">Pending Grading</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-video"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $liveToday; ?></span><span class="kpi-label">Live Today</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comments"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $communityQuestions; ?></span><span class="kpi-label">Posts</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $avgProgress; ?>%</span><span class="kpi-label">Avg Progress</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Quick Actions</h2></div>
            <div class="quick-actions">
                <a href="/index.php?page=teacher-lesson-editor" class="quick-action-card"><i class="fas fa-plus-circle"></i><span>Add Lesson</span></a>
                <a href="/index.php?page=teacher-assignments-editor" class="quick-action-card"><i class="fas fa-tasks"></i><span>Create Assignment</span></a>
                <a href="/index.php?page=teacher-quiz-editor" class="quick-action-card"><i class="fas fa-question-circle"></i><span>Create Quiz</span></a>
                <a href="/index.php?page=teacher-live-classes" class="quick-action-card"><i class="fas fa-video"></i><span>Schedule Class</span></a>
                <a href="/index.php?page=teacher-community" class="quick-action-card"><i class="fas fa-comments"></i><span>Community</span></a>
                <a href="/index.php?page=teacher-ai" class="quick-action-card"><i class="fas fa-robot"></i><span>AI Assistant</span></a>
            </div>
        </div>
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <div class="section-header"><h2>Recent Student Activity</h2></div>
                <?php if(empty($recentActivity)): ?><div class="empty-state"><i class="fas fa-history"></i><p>No recent activity</p></div>
                <?php else: ?><div class="activity-list">
                    <?php foreach($recentActivity as $act): ?>
                    <div class="activity-item">
                        <div class="activity-avatar"><?php echo strtoupper(substr($act['student_name']??'S',0,1)); ?></div>
                        <div class="activity-info"><strong><?php echo htmlspecialchars($act['student_name']); ?></strong><span><?php echo htmlspecialchars($act['lesson_title']); ?></span></div>
                        <span class="activity-time"><?php echo date('M j',strtotime($act['updated_at'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div><?php endif; ?>
            </div>
            <?php if(!empty($upcomingClasses)): ?>
            <div class="dashboard-section">
                <div class="section-header"><h2>Upcoming Classes</h2></div>
                <table class="admin-table"><thead><tr><th>Class</th><th>Course</th><th>Date</th></tr></thead><tbody>
                    <?php foreach($upcomingClasses as $cls): ?>
                    <tr><td><?php echo htmlspecialchars($cls['title']); ?></td><td><?php echo htmlspecialchars($cls['course_title']); ?></td><td><?php echo date('M j, g:i A',strtotime($cls['scheduled_at'])); ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>