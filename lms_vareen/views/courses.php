<?php
// Courses listing page
require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';

$course = new Course();
$page = $_GET['pg'] ?? 1;
$limit = 12;
$keyword = $_GET['q'] ?? '';

if ($keyword) {
    $courses = $course->searchCourses($keyword, $page, $limit);
} else {
    $courses = $course->getAllCourses($page, $limit);
}

$total = $course->getTotalCourses();
$total_pages = ceil($total / $limit);

// Check enrollment status for each course
$enrollment = new Enrollment();
$user_id = $_SESSION['user_id'] ?? 0;
foreach ($courses as &$c) {
    $c['is_enrolled'] = $user_id && $enrollment->isEnrolled($user_id, $c['id']);
}
?>

<div class="courses-page">
    <div class="container">
        <h1>Browse Courses</h1>
        
        <form action="/index.php?page=courses" method="GET" class="search-form" style="margin-bottom: 30px;">
            <input type="hidden" name="page" value="courses">
            <input type="text" name="q" placeholder="Search courses..." value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h2>No Courses Found</h2>
                <p>Try a different search or browse all courses.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($courses as $c): ?>
                    <div class="card">
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($c['description'], 0, 100)) . '...'; ?></p>
                            <p class="text-muted">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                            </p>
                            <?php if ($c['is_enrolled']): ?>
                                <a href="/index.php?page=course-detail&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-small btn-block">
                                    <i class="fas fa-play-circle"></i> Continue Learning
                                </a>
                            <?php elseif (isLoggedIn() && getCurrentUserRole() === 'student'): ?>
                                <button class="btn btn-primary btn-small btn-block" onclick="enrollCourse(<?php echo $c['id']; ?>)">
                                    <i class="fas fa-check-circle"></i> Enroll Now
                                </button>
                            <?php else: ?>
                                <a href="/index.php?page=login" class="btn btn-primary btn-small btn-block">
                                    Sign in to Enroll
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination" style="margin-top: 40px;">
                <?php if ($page > 1): ?>
                    <a href="/index.php?page=courses&pg=<?php echo $page - 1; ?>&q=<?php echo urlencode($keyword); ?>" class="btn">Previous</a>
                <?php endif; ?>
                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="/index.php?page=courses&pg=<?php echo $page + 1; ?>&q=<?php echo urlencode($keyword); ?>" class="btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .courses-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: calc(100vh - 100px);
    }

    .courses-page h1 {
        margin-bottom: 30px;
        font-size: 32px;
        color: #333;
    }

    .search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
    }

    .search-form input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
    }

    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
        }
    }
</style>

<script>
function enrollCourse(courseId) {
    fetch('<?php echo appBasePath(); ?>/src/api/dashboard.php?action=enroll', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ course_id: courseId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            VereenaUtils.showToast('Enrolled successfully!', 'success');
            setTimeout(() => {
                window.location.href = '/index.php?page=student-dashboard';
            }, 1000);
        } else {
            VereenaUtils.showToast(data.message, 'error');
        }
    });
}
</script>
