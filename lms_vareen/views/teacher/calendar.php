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

$upcomingEvents = [];
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT l.title, l.scheduled_at, 'live' AS type, c.title AS course_title FROM live_classes l JOIN courses c ON c.id = l.course_id WHERE l.course_id IN ($ph) AND l.scheduled_at > NOW() ORDER BY l.scheduled_at ASC LIMIT 20");
    $stmt->execute($courseIds);
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT title, due_date AS scheduled_at, 'assignment' AS type, (SELECT title FROM courses WHERE id = a.course_id) AS course_title FROM assignments a WHERE course_id IN ($ph) AND due_date > NOW() ORDER BY due_date ASC LIMIT 20");
    $stmt->execute($courseIds);
    $upcomingEvents = array_merge($upcomingEvents, $stmt->fetchAll(PDO::FETCH_ASSOC));
    usort($upcomingEvents, fn($a, $b) => strtotime($a['scheduled_at']) - strtotime($b['scheduled_at']));
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='calendar'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Calendar</h1><p>Your teaching schedule at a glance</p></div>
        </div>
        <?php if(empty($upcomingEvents)): ?><div class="empty-state"><i class="fas fa-calendar"></i><p>No upcoming events</p></div>
        <?php else: ?>
        <table class="admin-table"><thead><tr><th>Date</th><th>Event</th><th>Course</th><th>Type</th></tr></thead><tbody>
            <?php foreach($upcomingEvents as $e): ?>
            <tr>
                <td><?php echo date('M j, g:i A', strtotime($e['scheduled_at'])); ?></td>
                <td><?php echo htmlspecialchars($e['title']); ?></td>
                <td><?php echo htmlspecialchars($e['course_title'] ?? ''); ?></td>
                <td><span class="role-badge <?php echo $e['type']==='live'?'role-student':'role-teacher'; ?>"><?php echo $e['type']==='live'?'Live Class':'Assignment'; ?></span></td>
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