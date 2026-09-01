<?php
/**
 * Lesson Player Page - Display individual lesson with video
 */

require_once 'src/classes/Lesson.php';
require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';
require_once 'src/classes/LessonProgress.php';
require_once 'src/classes/Resource.php';

// Authentication required
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$lesson_id = $_GET['id'] ?? 0;
if (!$lesson_id) {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Get lesson details
$lesson = new Lesson();
$lesson_data = $lesson->getLessonById($lesson_id, $_SESSION['user_id']);

if (!$lesson_data) {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Get course info
$course = new Course();
$course_data = $course->getCourseById($lesson_data['course_id']);

// Check enrollment
$enrollment = new Enrollment();
if (!$enrollment->isEnrolled($_SESSION['user_id'], $lesson_data['course_id']) && $_SESSION['user_role'] === 'student') {
    header('Location: ' . BASE_URL . '?page=courses');
    exit;
}

// Get resources
$resource = new Resource();
$resources = $resource->getResourcesByLesson($lesson_id);

// Get next/previous lessons
$next_lesson = $lesson->getNextLesson($lesson_id, $lesson_data['course_id']);
$prev_lesson = $lesson->getPreviousLesson($lesson_id, $lesson_data['course_id']);

// Get progress
$progress = new LessonProgress();
$lesson_progress = $progress->getProgress($_SESSION['user_id'], $lesson_id);
$is_completed = $lesson_progress && $lesson_progress['is_completed'] == 1;
?>

<div class="lesson-player-container">
    <!-- Lesson Header -->
    <div class="lesson-header">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>?page=course-detail&id=<?php echo $lesson_data['course_id']; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Course
            </a>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="container">
        <div class="lesson-layout">
            <!-- Video Player & Info -->
            <main class="lesson-main">
                <!-- Video Player -->
                <div class="video-player-wrapper">
                    <div class="video-player">
                        <?php if ($lesson_data['video_url']): ?>
                            <!-- Check if YouTube URL -->
                            <?php if (strpos($lesson_data['video_url'], 'youtube') !== false): ?>
                                <!-- YouTube Embed -->
                                <?php 
                                $video_id = '';
                                if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $lesson_data['video_url'], $matches)) {
                                    $video_id = $matches[2];
                                }
                                ?>
                                <iframe width="100%" height="600" 
                                    src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                    title="<?php echo htmlspecialchars($lesson_data['title']); ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            <?php else: ?>
                                <!-- HTML5 Video -->
                                <video id="lessonVideo" width="100%" height="600" controls>
                                    <source src="<?php echo htmlspecialchars($lesson_data['video_url']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-video-placeholder">
                                <i class="fas fa-video"></i>
                                <p>No video available for this lesson</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Player Controls -->
                    <div class="player-controls">
                        <div class="controls-left">
                            <?php if ($is_completed): ?>
                                <span class="status-badge completed">
                                    <i class="fas fa-check-circle"></i> Completed
                                </span>
                            <?php else: ?>
                                <button class="btn btn-primary" onclick="markLessonComplete(<?php echo $lesson_id; ?>)">
                                    <i class="fas fa-check"></i> Mark as Complete
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="controls-right">
                            <?php if ($lesson_data['video_duration']): ?>
                                <span class="duration-badge">
                                    <i class="fas fa-clock"></i> <?php echo Lesson::formatDuration($lesson_data['video_duration']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Lesson Info -->
                <div class="lesson-info-card">
                    <div class="lesson-title-section">
                        <h1><?php echo htmlspecialchars($lesson_data['title']); ?></h1>
                        <div class="lesson-meta">
                            <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($lesson_data['module_title']); ?></span>
                        </div>
                    </div>

                    <?php if ($lesson_data['description']): ?>
                        <div class="lesson-description">
                            <h3>About this lesson</h3>
                            <p><?php echo nl2br(htmlspecialchars($lesson_data['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resources Section -->
                <?php if (!empty($resources)): ?>
                    <div class="resources-card">
                        <h3><i class="fas fa-download"></i> Lesson Resources</h3>
                        <div class="resources-list">
                            <?php foreach ($resources as $res): ?>
                                <a href="<?php echo htmlspecialchars($res['file_path']); ?>" download class="resource-item">
                                    <div class="resource-icon">
                                        <i class="fas <?php echo Resource::getFileIcon($res['file_type']); ?>"></i>
                                    </div>
                                    <div class="resource-info">
                                        <div class="resource-name"><?php echo htmlspecialchars($res['title']); ?></div>
                                        <div class="resource-type"><?php echo strtoupper($res['file_type']); ?> • <?php echo Resource::formatFileSize(filesize($res['file_path']) ?: 0); ?></div>
                                    </div>
                                    <div class="resource-action">
                                        <i class="fas fa-download"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Navigation -->
                <div class="lesson-navigation">
                    <?php if ($prev_lesson): ?>
                        <a href="<?php echo BASE_URL; ?>?page=lesson&id=<?php echo $prev_lesson['id']; ?>" class="nav-button nav-prev">
                            <i class="fas fa-chevron-left"></i>
                            <div>
                                <div class="nav-label">Previous Lesson</div>
                                <div class="nav-title"><?php echo htmlspecialchars($prev_lesson['title']); ?></div>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="nav-button nav-button-disabled"></div>
                    <?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>?page=course-detail&id=<?php echo $lesson_data['course_id']; ?>" class="nav-center-button">
                        <i class="fas fa-list"></i> Back to Course
                    </a>

                    <?php if ($next_lesson): ?>
                        <a href="<?php echo BASE_URL; ?>?page=lesson&id=<?php echo $next_lesson['id']; ?>" class="nav-button nav-next">
                            <div>
                                <div class="nav-label">Next Lesson</div>
                                <div class="nav-title"><?php echo htmlspecialchars($next_lesson['title']); ?></div>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <div class="nav-button nav-button-disabled"></div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="lesson-sidebar">
                <!-- Course Info -->
                <div class="sidebar-card">
                    <h3>Course</h3>
                    <a href="<?php echo BASE_URL; ?>?page=course-detail&id=<?php echo $lesson_data['course_id']; ?>" class="course-link">
                        <div class="course-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="course-details">
                            <div class="course-title"><?php echo htmlspecialchars($course_data['title']); ?></div>
                            <div class="course-instructor"><?php echo htmlspecialchars($course_data['instructor'] ?? 'Instructor'); ?></div>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Course Progress -->
                <div class="sidebar-card">
                    <h3>Your Progress</h3>
                    <div class="progress-details">
                        <?php 
                        $course_progress = $progress->getCourseProgress($_SESSION['user_id'], $lesson_data['course_id']);
                        $percentage = $course_progress['progress_percentage'] ?? 0;
                        ?>
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>Overall Course</span>
                                <span class="progress-percent"><?php echo round($percentage, 1); ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>

                        <div class="progress-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo $course_progress['completed_lessons'] ?? 0; ?></span>
                                <span class="stat-label">Completed</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo ($course_progress['total_lessons'] ?? 0) - ($course_progress['completed_lessons'] ?? 0); ?></span>
                                <span class="stat-label">Remaining</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lesson Status -->
                <div class="sidebar-card status-card">
                    <?php if ($is_completed): ?>
                        <div class="status-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Lesson Completed!</h3>
                        <p>Great job! You've completed this lesson.</p>
                    <?php else: ?>
                        <div class="status-icon pending">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <h3>Continue Learning</h3>
                        <p>Watch the video and mark this lesson as complete.</p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.lesson-player-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding-top: 0;
}

.lesson-header {
    background: white;
    padding: 15px 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 30px;
    position: sticky;
    top: 70px;
    z-index: 10;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.3s;
}

.back-link:hover {
    opacity: 0.8;
}

.lesson-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    align-items: start;
}

.lesson-main {
    min-height: 500px;
}

.video-player-wrapper {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
}

.video-player {
    background: #000;
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
}

.video-player iframe,
.video-player video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.no-video-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
    color: #999;
}

.no-video-placeholder i {
    font-size: 3rem;
    margin-bottom: 15px;
}

.player-controls {
    padding: 15px 20px;
    background: #f9fafb;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.controls-left,
.controls-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-badge.completed {
    background: #d4edda;
    color: #155724;
}

.duration-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 0.9rem;
}

.lesson-info-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.lesson-title-section h1 {
    margin: 0 0 12px;
    font-size: 2rem;
    color: #1a202c;
}

.lesson-meta {
    display: flex;
    gap: 20px;
    color: #666;
    font-size: 0.9rem;
}

.lesson-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.lesson-description {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.lesson-description h3 {
    margin: 0 0 12px;
    color: #1a202c;
    font-size: 1rem;
}

.lesson-description p {
    color: #666;
    line-height: 1.7;
    margin: 0;
}

.resources-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.resources-card h3 {
    margin: 0 0 20px;
    color: #1a202c;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.resources-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.resource-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    border: 1px solid #e0e0e0;
}

.resource-item:hover {
    background: #f0f4ff;
    border-color: #667eea;
    transform: translateX(3px);
}

.resource-icon {
    font-size: 1.5rem;
    color: #667eea;
    min-width: 30px;
    text-align: center;
}

.resource-info {
    flex: 1;
    min-width: 0;
}

.resource-name {
    font-weight: 600;
    color: #1a202c;
    word-break: break-word;
}

.resource-type {
    font-size: 0.8rem;
    color: #999;
}

.resource-action {
    font-size: 1rem;
    color: #667eea;
    min-width: 20px;
    text-align: right;
}

.lesson-navigation {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 15px;
    margin-top: 30px;
}

.nav-button {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    color: #1a202c;
    transition: all 0.3s;
    min-height: 70px;
}

.nav-button:hover {
    background: #f9fafb;
    border-color: #667eea;
    transform: translateY(-2px);
}

.nav-button i {
    color: #667eea;
    font-size: 1.3rem;
}

.nav-prev {
    justify-content: flex-start;
}

.nav-next {
    justify-content: flex-end;
    flex-direction: row-reverse;
}

.nav-label {
    font-size: 0.75rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.nav-title {
    font-weight: 600;
    color: #1a202c;
}

.nav-center-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 20px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    color: #1a202c;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.3s;
}

.nav-center-button:hover {
    background: #f9fafb;
    border-color: #667eea;
}

.nav-button-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.lesson-sidebar {
    position: sticky;
    top: 100px;
}

.sidebar-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.sidebar-card h3 {
    margin: 0 0 15px;
    color: #1a202c;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.course-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
}

.course-link:hover {
    color: #667eea;
}

.course-icon {
    font-size: 2rem;
    color: #667eea;
    min-width: 50px;
    text-align: center;
}

.course-details {
    flex: 1;
    min-width: 0;
}

.course-title {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
    word-break: break-word;
}

.course-instructor {
    font-size: 0.85rem;
    color: #999;
}

.course-link i:last-child {
    color: #667eea;
    margin-left: auto;
}

.progress-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.progress-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #1a202c;
}

.progress-percent {
    font-weight: 700;
    color: #667eea;
}

.progress-bar {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #667eea;
    transition: width 0.3s;
}

.progress-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.4rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 4px;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #999;
    text-transform: uppercase;
}

.status-card {
    text-align: center;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
    border: 1px solid #d4deff;
}

.status-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.status-icon.completed {
    color: #10b981;
}

.status-icon.pending {
    color: #667eea;
}

.status-card h3 {
    margin: 10px 0;
    color: #1a202c;
}

.status-card p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .lesson-layout {
        grid-template-columns: 1fr;
        gap: 25px;
    }

    .lesson-sidebar {
        position: relative;
        top: 0;
    }

    .lesson-navigation {
        grid-template-columns: 1fr;
    }

    .nav-center-button {
        display: none;
    }
}

@media (max-width: 768px) {
    .lesson-header {
        top: 0;
    }

    .lesson-title-section h1 {
        font-size: 1.5rem;
    }

    .lesson-meta {
        flex-direction: column;
        gap: 10px;
    }

    .video-player {
        padding-bottom: 56.25%;
    }

    .player-controls {
        flex-direction: column;
        align-items: stretch;
    }

    .controls-left,
    .controls-right {
        justify-content: center;
    }

    .lesson-info-card,
    .resources-card {
        padding: 15px;
    }

    .lesson-navigation {
        gap: 10px;
    }

    .nav-button {
        padding: 12px;
        font-size: 0.9rem;
        gap: 10px;
    }

    .nav-label {
        display: none;
    }

    .nav-title {
        font-size: 0.85rem;
    }

    .container {
        padding: 0 15px;
    }
}
</style>

<script>
// Update video progress tracking
const video = document.getElementById('lessonVideo');
if (video) {
    video.addEventListener('timeupdate', function() {
        // Send progress update every 30 seconds
        if (Math.floor(video.currentTime) % 30 === 0) {
            recordProgress(<?php echo $lesson_id; ?>, Math.floor(video.currentTime));
        }
    });
}

function recordProgress(lessonId, watchedDuration) {
    const formData = new FormData();
    formData.append('action', 'record_progress');
    formData.append('lesson_id', lessonId);
    formData.append('watched_duration', watchedDuration);

    fetch('<?php echo BASE_URL; ?>src/api/lessons.php', {
        method: 'POST',
        body: formData
    }).catch(err => console.log('Progress recorded'));
}

function markLessonComplete(lessonId) {
    const formData = new FormData();
    formData.append('action', 'mark_complete');
    formData.append('lesson_id', lessonId);

    fetch('<?php echo BASE_URL; ?>src/api/lessons.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Lesson marked as completed!', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}
</script>
