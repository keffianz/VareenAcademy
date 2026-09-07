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

$courseData = [];
foreach ($courseList as $c) {
    $cid = (int)$c['id'];
    $stmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :cid');
    $stmt->execute([':cid' => $cid]);
    $students = (int)$stmt->fetchColumn();
    $lessons = $courseModel->getCourseLessons($cid);
    $lessonCount = is_array($lessons) ? count($lessons) : 0;
    $stmt = $db->prepare('SELECT AVG(progress_percent) FROM enrollments WHERE course_id = :cid');
    $stmt->execute([':cid' => $cid]);
    $progress = round((float)$stmt->fetchColumn());
    $courseData[] = array_merge($c, ['students' => $students, 'lessons' => $lessonCount, 'progress' => $progress]);
}
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='courses'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>My Courses</h1><p>Manage your assigned courses</p></div>
        </div>
        <?php if(empty($courseData)): ?><div class="empty-state"><i class="fas fa-book"></i><p>No courses assigned yet</p></div>
        <?php else: ?><div class="courses-grid">
            <?php foreach($courseData as $c): ?>
            <div class="course-card">
                <div class="course-card-header"><h3><?php echo htmlspecialchars($c['title']); ?></h3><span class="role-badge role-teacher"><?php echo $c['status']; ?></span></div>
                <div class="course-stats">
                    <span><i class="fas fa-users"></i> <?php echo $c['students']; ?> students</span>
                    <span><i class="fas fa-list-ul"></i> <?php echo $c['lessons']; ?> lessons</span>
                </div>
                <div class="course-progress"><div class="progress-bar"><div class="progress-fill" style="width:<?php echo $c['progress']; ?>%"></div></div><span><?php echo $c['progress']; ?>% complete</span></div>
                <div class="course-actions">
                    <a href="/index.php?page=teacher-module-editor&course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary">Manage</a>
                    <a href="/index.php?page=teacher-students&course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-secondary">Students</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>