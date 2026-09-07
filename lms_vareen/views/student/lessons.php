<?php
requireRole('student');
require_once 'src/classes/Database.php';
require_once 'src/classes/Course.php';

$db = (new Database())->connect();
$user_id = getCurrentUserId();

// Get enrolled courses
$stmt = $db->prepare(
    'SELECT c.*, e.progress, e.enrolled_at,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
            (SELECT COUNT(*) FROM lessons l JOIN lesson_progress lp ON lp.lesson_id = l.id WHERE l.course_id = c.id AND lp.student_id = :sid AND lp.completed = 1) AS completed_lessons
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     WHERE e.student_id = :sid
     ORDER BY e.enrolled_at DESC'
);
$stmt->execute([':sid' => $user_id]);
$enrolled = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'lessons'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>My Lessons</h1><p>Continue learning where you left off</p></div>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Enrolled Courses</h2></div>
            <?php if (empty($enrolled)): ?>
                <div class="empty-state"><p>You haven't enrolled in any courses yet. <a href="/index.php?page=courses">Browse courses</a></p></div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($enrolled as $c): ?>
                        <div class="course-card">
                            <div class="course-thumb">
                                <?php if (!empty($c['thumbnail'])): ?>
                                    <img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="">
                                <?php else: ?>
                                    <div class="course-thumb-placeholder"><i class="fas fa-graduation-cap"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="course-info">
                                <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                                <div class="course-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:<?php echo $c['progress']; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo $c['progress']; ?>%</span>
                                </div>
                                <p class="course-meta"><?php echo $c['completed_lessons']; ?>/<?php echo $c['lesson_count']; ?> lessons completed</p>
                                <a href="/index.php?page=course-learn&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">Continue Learning</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>