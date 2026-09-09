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

$totalStudents = 0;
$avgProgress = 0;
$totalSubmissions = 0;
$avgScore = 0;
if (!empty($courseIds)) {
    $ph = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE course_id IN ($ph)");
    $stmt->execute($courseIds);
    $totalStudents = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT AVG(progress_percent) FROM enrollments WHERE course_id IN ($ph)");
    $stmt->execute($courseIds);
    $avgProgress = round((float)$stmt->fetchColumn());
    $stmt = $db->prepare("SELECT COUNT(*), AVG(score) FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id WHERE a.course_id IN ($ph)");
    $stmt->execute($courseIds);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $totalSubmissions = (int)$row[0];
    $avgScore = $row[1] !== null ? round((float)$row[1], 1) : 0;
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='analytics'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Analytics</h1><p>Insights into your teaching performance</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalStudents; ?></span><span class="kpi-label">Students</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $avgProgress; ?>%</span><span class="kpi-label">Avg Progress</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-file-alt"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $totalSubmissions; ?></span><span class="kpi-label">Submissions</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-star"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $avgScore; ?></span><span class="kpi-label">Avg Score</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Course Progress</h2></div>
            <?php if(empty($courseList)): ?><div class="empty-state">No courses to analyze</div>
            <?php else: ?>
            <table class="admin-table"><thead><tr><th>Course</th><th>Students</th><th>Avg Progress</th></tr></thead><tbody>
                <?php foreach($courseList as $c): ?>
                <?php
                $stmt = $db->prepare('SELECT AVG(progress_percent) FROM enrollments WHERE course_id = :cid');
                $stmt->execute([':cid' => $c['id']]);
                $prog = round((float)$stmt->fetchColumn());
                $stmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :cid');
                $stmt->execute([':cid' => $c['id']]);
                $cnt = (int)$stmt->fetchColumn();
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['title']); ?></td>
                    <td><?php echo $cnt; ?></td>
                    <td><div class="progress-bar" style="width:100%"><div class="progress-fill" style="width:<?php echo $prog; ?>%"></div></div> <?php echo $prog; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>