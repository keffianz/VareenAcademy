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

$grades = [];
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT s.id, s.score, s.submitted_at, a.title AS assignment_title, a.max_score, c.title AS course_title, CONCAT(u.first_name, ' ', u.last_name) AS student_name FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id JOIN courses c ON c.id = a.course_id JOIN users u ON u.id = s.student_id WHERE a.course_id IN ($ph) AND s.score IS NOT NULL ORDER BY s.submitted_at DESC LIMIT 50");
    $stmt->execute($courseIds);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='grades'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Grades</h1><p>Recently graded submissions</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if(empty($grades)): ?><div class="empty-state"><i class="fas fa-award"></i><p>No grades yet</p></div>
        <?php else: ?>
        <table class="admin-table"><thead><tr><th>Student</th><th>Assignment</th><th>Course</th><th>Score</th><th>Submitted</th></tr></thead><tbody>
            <?php foreach($grades as $g): ?>
            <tr>
                <td><?php echo htmlspecialchars($g['student_name']); ?></td>
                <td><?php echo htmlspecialchars($g['assignment_title']); ?></td>
                <td><?php echo htmlspecialchars($g['course_title']); ?></td>
                <td><strong><?php echo $g['score']; ?></strong>/<?php echo $g['max_score']; ?></td>
                <td><?php echo date('M j, g:i A', strtotime($g['submitted_at'])); ?></td>
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