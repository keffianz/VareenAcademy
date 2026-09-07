<?php
requireRole('student');
require_once 'src/classes/Database.php';
require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';

$db = (new Database())->connect();
$course = new Course();
$enrollment = new Enrollment();
$user_id = getCurrentUserId();

// Get enrolled course IDs
$enrolled = $enrollment->getStudentEnrollments($user_id);
$enrolledIds = array_column($enrolled, 'course_id');

// Get all published courses
$courses = $course->getAllCourses();

// Filter out enrolled courses
$available = array_filter($courses, function($c) use ($enrolledIds) {
    return !in_array($c['id'], $enrolledIds);
});
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'courses'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Browse Courses</h1><p>Discover new courses to enroll in</p></div>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Available Courses</h2></div>
            <?php if (empty($available)): ?>
                <div class="empty-state"><p>No new courses available. You're enrolled in everything!</p></div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($available as $c): ?>
                        <div class="course-card">
                            <div class="course-thumb">
                                <?php if (!empty($c['thumbnail'])): ?>
                                    <img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="<?php echo htmlspecialchars($c['title']); ?>">
                                <?php else: ?>
                                    <div class="course-thumb-placeholder"><i class="fas fa-book"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="course-info">
                                <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                                <p class="course-desc"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 100)) . '...'; ?></p>
                                <div class="course-meta">
                                    <span><i class="fas fa-users"></i> <?php echo $c['enrollment_count'] ?? 0; ?> students</span>
                                    <span><i class="fas fa-star"></i> <?php echo number_format($c['rating'] ?? 0, 1); ?></span>
                                </div>
                                <a href="/index.php?page=course-detail&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">View Course</a>
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