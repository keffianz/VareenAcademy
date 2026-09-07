<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';
require_once 'src/classes/Course.php';

$userId = getCurrentUserId();
$role = getCurrentUserRole();
$db = (new Database())->connect();
$courseModel = new Course();

$allCourses = $courseModel->getAllCourses(1, 100);
$courseList = [];
foreach ($allCourses as $c) {
    if ($role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId) $courseList[] = $c;
}
$courseIds = array_column($courseList, 'id');

$studentProgress = [];
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS student_name, c.title AS course_title, e.progress_percent, e.last_accessed FROM enrollments e JOIN users u ON u.id = e.user_id JOIN courses c ON c.id = e.course_id WHERE e.course_id IN ($ph) ORDER BY e.progress_percent DESC LIMIT 50");
    $stmt->execute($courseIds);
    $studentProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='progress'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Student Progress</h1><p>Track how your students are doing</p></div>
        </div>
        <?php if(empty($studentProgress)): ?><div class="empty-state"><i class="fas fa-chart-line"></i><p>No student data yet</p></div>
        <?php else: ?>
        <table class="admin-table"><thead><tr><th>Student</th><th>Course</th><th>Progress</th><th>Last Active</th></tr></thead><tbody>
            <?php foreach($studentProgress as $sp): ?>
            <tr>
                <td><?php echo htmlspecialchars($sp['student_name']); ?></td>
                <td><?php echo htmlspecialchars($sp['course_title']); ?></td>
                <td><div class="progress-bar" style="width:100%"><div class="progress-fill" style="width:<?php echo $sp['progress_percent']; ?>%"></div></div> <?php echo $sp['progress_percent']; ?>%</td>
                <td><?php echo $sp['last_accessed']?date('M j, g:i A', strtotime($sp['last_accessed'])):'—'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>