<?php
/**
 * Course Detail Page - Display course modules and lessons
 */

require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Module.php';
require_once 'src/classes/Lesson.php';
require_once 'src/classes/LessonProgress.php';

// Authentication required
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$course_id = $_GET['id'] ?? 0;
if (!$course_id) {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Get course details
$course = new Course();
$course_data = $course->getCourseById($course_id);

if (!$course_data) {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Check if student is enrolled
$enrollment = new Enrollment();
if (!$enrollment->isEnrolled($_SESSION['user_id'], $course_id) && $_SESSION['user_role'] === 'student') {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Get course modules and lessons
$module = new Module();
$modules = $module->getModulesByCourse($course_id);

// Get course progress
$lesson_progress = new LessonProgress();
$progress = $lesson_progress->getCourseProgress($_SESSION['user_id'], $course_id);

// Get last watched lesson for continue learning
$last_lesson = $lesson_progress->getLastWatchedLesson($_SESSION['user_id'], $course_id);
?>

<div class="course-detail-container">
    <!-- Header -->
    <div class="course-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>?page=courses" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
            <h1><?php echo htmlspecialchars($course_data['title']); ?></h1>
            <p class="course-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($course_data['instructor'] ?? 'Instructor'); ?></span>
                <span><i class="fas fa-video"></i> <?php echo count($modules); ?> Modules</span>
            </p>

            <!-- Progress Bar -->
            <?php if ($progress['total_lessons'] > 0): ?>
                <div class="course-progress-section">
                    <div class="progress-label">
                        <span>Course Progress</span>
                        <span class="progress-percentage"><?php echo round($progress['progress_percentage'] ?? 0, 1); ?>%</span>
                    </div>
                    <div class="progress-bar-large">
                        <div class="progress-fill" style="width: <?php echo round($progress['progress_percentage'] ?? 0, 1); ?>%"></div>
                    </div>
                    <p class="progress-text"><?php echo ($progress['completed_lessons'] ?? 0); ?> of <?php echo ($progress['total_lessons'] ?? 0); ?> lessons completed</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="course-layout">
            <!-- Sidebar -->
            <aside class="course-sidebar">
                <div class="sidebar-card">
                    <h3>About This Course</h3>
                    <p><?php echo nl2br(htmlspecialchars($course_data['description'] ?? '')); ?></p>
                </div>

                <?php if ($last_lesson): ?>
                    <div class="sidebar-card cta-card">
                        <h4><i class="fas fa-play-circle"></i> Continue Learning</h4>
                        <p><?php echo htmlspecialchars($last_lesson['title']); ?></p>
                        <a href="<?php echo BASE_URL; ?>?page=lesson&id=<?php echo $last_lesson['id']; ?>" class="btn btn-primary btn-small">
                            Resume <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="sidebar-card">
                    <h4>Course Stats</h4>
                    <div class="stats-grid">
                        <div class="stat">
                            <span class="stat-value"><?php echo count($modules); ?></span>
                            <span class="stat-label">Modules</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo ($progress['total_lessons'] ?? 0); ?></span>
                            <span class="stat-label">Lessons</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="course-main">
                <!-- Modules List -->
                <div class="modules-section">
                    <h2 class="section-title">Course Content</h2>

                    <?php if (empty($modules)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <h3>No modules yet</h3>
                            <p>This course doesn't have any modules yet. Check back soon!</p>
                        </div>
                    <?php else: ?>
                        <div class="modules-accordion">
                            <?php foreach ($modules as $index => $mod): ?>
                                <?php 
                                // Get lessons for this module
                                $lesson = new Lesson();
                                $lessons = $lesson->getLessonsByModule($mod['id']);
                                
                                // Get completed lessons count
                                $completed = new LessonProgress();
                                $completed_ids = $completed->getCompletedLessons($_SESSION['user_id'], $course_id);
                                $completed_count = count(array_filter($lessons, function($l) use ($completed_ids) {
                                    return in_array($l['id'], $completed_ids);
                                }));
                                ?>
                                <div class="module-accordion-item" data-module-id="<?php echo $mod['id']; ?>">
                                    <div class="module-header" onclick="toggleModule(event)">
                                        <div class="module-title-section">
                                            <i class="fas fa-chevron-down toggle-icon"></i>
                                            <div class="module-info">
                                                <h3><?php echo htmlspecialchars($mod['title']); ?></h3>
                                                <span class="lesson-count"><?php echo count($lessons); ?> lessons</span>
                                            </div>
                                        </div>
                                        <div class="module-progress">
                                            <span class="progress-text"><?php echo $completed_count; ?>/<?php echo count($lessons); ?></span>
                                            <div class="progress-indicator" style="width: 40px; height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden;">
                                                <div style="width: <?php echo count($lessons) > 0 ? ($completed_count / count($lessons) * 100) : 0; ?>%; height: 100%; background: #667eea; transition: width 0.3s;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="module-content">
                                        <ul class="lessons-list">
                                            <?php foreach ($lessons as $l): 
                                                $is_completed = in_array($l['id'], $completed_ids);
                                                ?>
                                                <li class="lesson-item <?php echo $is_completed ? 'completed' : ''; ?>">
                                                    <a href="<?php echo BASE_URL; ?>?page=lesson&id=<?php echo $l['id']; ?>" class="lesson-link">
                                                        <div class="lesson-icon">
                                                            <?php if ($is_completed): ?>
                                                                <i class="fas fa-check-circle"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-play-circle"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="lesson-details">
                                                            <h4><?php echo htmlspecialchars($l['title']); ?></h4>
                                                            <?php if ($l['video_duration']): ?>
                                                                <span class="lesson-duration">
                                                                    <i class="fas fa-clock"></i> 
                                                                    <?php echo Lesson::formatDuration($l['video_duration']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($is_completed): ?>
                                                            <div class="lesson-status">
                                                                <span class="badge badge-success">Completed</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<style>
.course-detail-container {
    min-height: 100vh;
}

.course-header {
    padding: 40px 20px;
    color: white;
    margin-bottom: 40px;
}

.course-header .back-link {
    display: inline-block;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 15px;
    transition: opacity 0.3s;
}

.course-header .back-link:hover {
    opacity: 0.8;
}

.course-header h1 {
    font-size: 2.5rem;
    margin: 15px 0;
    font-weight: 700;
}

.course-meta {
    display: flex;
    gap: 25px;
    font-size: 0.95rem;
    opacity: 0.95;
    margin-bottom: 30px;
}

.course-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.course-progress-section {
    margin-top: 30px;
    background: rgba(0, 0, 0, 0.1);
    padding: 20px;
    border-radius: 8px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-weight: 500;
}

.progress-percentage {
    font-size: 1.2rem;
    font-weight: 700;
}

.progress-bar-large {
    height: 8px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-bar-large .progress-fill {
    height: 100%;
    background: white;
    transition: width 0.3s;
}

.progress-text {
    font-size: 0.9rem;
    opacity: 0.9;
}

.course-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 40px;
    align-items: start;
}

.course-sidebar {
    position: sticky;
    top: 80px;
}

.sidebar-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.sidebar-card h3 {
    font-size: 1.1rem;
    margin-bottom: 12px;
    color: #1a202c;
}

.sidebar-card h4 {
    font-size: 0.95rem;
    margin-bottom: 15px;
    color: #1a202c;
}

.sidebar-card p {
    color: #666;
    line-height: 1.6;
    font-size: 0.95rem;
}

.cta-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.cta-card h4,
.cta-card p {
    color: white;
}

.cta-card p {
    font-size: 0.9rem;
    margin: 10px 0 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-label {
    display: block;
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.course-main {
    min-height: 400px;
}

.modules-section .section-title {
    font-size: 1.5rem;
    margin-bottom: 25px;
    color: #1a202c;
}

.modules-accordion {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.module-accordion-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.module-header {
    padding: 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s;
    border-bottom: 1px solid #f0f0f0;
}

.module-header:hover {
    background: #f9fafb;
}

.module-title-section {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.toggle-icon {
    transition: transform 0.3s;
    color: #667eea;
    font-size: 0.9rem;
}

.module-accordion-item.open .toggle-icon {
    transform: rotate(180deg);
}

.module-info h3 {
    margin: 0 0 5px;
    color: #1a202c;
    font-size: 1.1rem;
}

.lesson-count {
    font-size: 0.8rem;
    color: #999;
}

.module-progress {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 120px;
}

.progress-text {
    font-size: 0.85rem;
    color: #666;
    min-width: 40px;
    text-align: right;
}

.module-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.module-accordion-item.open .module-content {
    max-height: 2000px;
}

.lessons-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.lesson-item {
    border-top: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.lesson-item:hover {
    background: #f9fafb;
}

.lesson-item.completed {
    opacity: 0.85;
}

.lesson-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px 20px;
    color: inherit;
    text-decoration: none;
    transition: all 0.3s;
}

.lesson-link:hover {
    background: #f0f4ff;
}

.lesson-icon {
    font-size: 1.5rem;
    color: #667eea;
    min-width: 30px;
}

.lesson-item.completed .lesson-icon {
    color: #10b981;
}

.lesson-details {
    flex: 1;
}

.lesson-details h4 {
    margin: 0 0 6px;
    color: #1a202c;
    font-size: 0.95rem;
    font-weight: 600;
}

.lesson-duration {
    font-size: 0.8rem;
    color: #999;
    display: flex;
    align-items: center;
    gap: 5px;
}

.lesson-status {
    display: flex;
    align-items: center;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.empty-state {
    text-align: center;
    padding: 60px 40px;
    color: #999;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h3 {
    color: #1a202c;
    margin: 15px 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .course-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .course-sidebar {
        position: relative;
        top: 0;
    }

    .course-header h1 {
        font-size: 1.8rem;
    }

    .course-meta {
        flex-wrap: wrap;
        gap: 15px;
    }
}

@media (max-width: 768px) {
    .course-header {
        padding: 25px 15px;
        margin-bottom: 25px;
    }

    .course-header h1 {
        font-size: 1.5rem;
    }

    .module-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .module-progress {
        align-self: flex-end;
        margin-top: 10px;
    }

    .container {
        padding: 0 15px;
    }
}
</style>

<script>
function toggleModule(event) {
    const item = event.currentTarget.closest('.module-accordion-item');
    item.classList.toggle('open');
    
    // Save state
    const moduleId = item.dataset.moduleId;
    const state = item.classList.contains('open');
    localStorage.setItem('module_' + moduleId + '_open', state);
}

// Restore module states on load
document.querySelectorAll('.module-accordion-item').forEach(item => {
    const moduleId = item.dataset.moduleId;
    const wasOpen = localStorage.getItem('module_' + moduleId + '_open') === 'true';
    if (wasOpen) {
        item.classList.add('open');
    }
});
</script>
