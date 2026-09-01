<?php
/**
 * Teacher Dashboard - Phase 4 starter UI
 */

requireRoles(['teacher', 'admin']);

require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Module.php';
require_once 'src/classes/Lesson.php';
require_once 'src/classes/LessonProgress.php';

$userId = getCurrentUserId();
$role = getCurrentUserRole();

$courseModel = new Course();
$moduleModel = new Module();
$lessonModel = new Lesson();
$progressModel = new LessonProgress();
$enrollmentModel = new Enrollment();

$courseList = [];
// For MVP: teachers see their own courses; admins would see all courses.
// Course.php does not have list-by-teacher method, so we use getAllCourses and filter lightly.
// (This can be optimized later.)
$allCourses = $courseModel->getAllCourses(1, 50);
foreach ($allCourses as $c) {
    if ($role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId) {
        $courseList[] = $c;
    }
}

// Basic stats per course
$courseStats = [];
foreach ($courseList as $c) {
    $cid = (int)$c['id'];
    $modules = $moduleModel->getModulesByCourse($cid);
    $moduleCount = count($modules);

    // Total lessons count
    $lessons = $lessonModel->getCourseLessons($cid); // note: Course.php has getCourseLessons, not Lesson
    // fallback: derive from lessons table via Course model
    if (!is_array($lessons)) {
        $lessons = (new Course())->getCourseLessons($cid);
    }
    $lessonCount = is_array($lessons) ? count($lessons) : 0;

    $courseStats[] = [
        'course' => $c,
        'module_count' => $moduleCount,
        'lesson_count' => $lessonCount
    ];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Teacher Dashboard</h1>
        <p>Manage your courses, modules, lessons, and resources.</p>
    </div>

    <div class="card">
        <h2>Your Courses</h2>
        <?php if (empty($courseStats)): ?>
            <p>No courses found.</p>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($courseStats as $s): ?>
                    <div class="course-card">
                        <h3><?php echo htmlspecialchars($s['course']['title'] ?? ''); ?></h3>
                        <div class="muted">Modules: <?php echo (int)$s['module_count']; ?> • Lessons: <?php echo (int)$s['lesson_count']; ?></div>

                        <div class="actions">
                            <a class="btn btn-primary" href="/index.php?page=teacher-module-editor&course_id=<?php echo (int)$s['course']['id']; ?>">
                                Manage Modules
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .container{padding:30px 15px;max-width:1100px;margin:0 auto;}
    .page-header h1{margin:0 0 10px 0;font-size:30px;}
    .page-header p{margin:0 0 20px 0;color:#666;}
    .card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:20px;}
    .courses-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;}
    .course-card{border:1px solid #eee;border-radius:12px;padding:16px;}
    .muted{color:#777;font-size:13px;margin-top:6px;}
    .actions{margin-top:14px;}
    .btn{display:inline-block;border-radius:10px;padding:10px 14px;text-decoration:none;border:1px solid transparent;cursor:pointer;}
    .btn-primary{background:#667eea;color:#fff;}
</style>

