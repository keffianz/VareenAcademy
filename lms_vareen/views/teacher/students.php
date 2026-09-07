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
$students = [];
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.created_at, (SELECT COUNT(*) FROM enrollments e WHERE e.user_id = u.id AND e.course_id IN ($ph)) AS course_count FROM users u JOIN enrollments e ON e.user_id = u.id WHERE e.course_id IN ($ph) AND u.role = 'student' ORDER BY u.first_name");
    $stmt->execute(array_merge($courseIds, $courseIds));
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='mystudents'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>My Students</h1><p><?php echo count($students); ?> students enrolled in your courses</p></div>
        </div>
        <?php if(empty($students)): ?><div class="empty-state"><i class="fas fa-users"></i><p>No students enrolled yet</p></div>
        <?php else: ?>
        <table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Courses</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
            <?php foreach($students as $s): ?>
            <tr>
                <td><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></td>
                <td><?php echo htmlspecialchars($s['email']); ?></td>
                <td><?php echo $s['course_count']; ?></td>
                <td><?php echo date('M j, Y', strtotime($s['created_at'])); ?></td>
                <td><a href="/index.php?page=teacher-progress&student_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary">View Progress</a></td>
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