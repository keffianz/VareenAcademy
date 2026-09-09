<?php
requireRole('student');
require_once 'src/classes/Database.php';

$db = (new Database())->connect();
$user_id = getCurrentUserId();

// Get quizzes for enrolled courses
$stmt = $db->prepare(
    'SELECT q.*, c.title as course_title,
            (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
            (SELECT score FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = :sid ORDER BY qa.completed_at DESC LIMIT 1) as last_score,
            (SELECT completed_at FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = :sid ORDER BY qa.completed_at DESC LIMIT 1) as last_attempt
     FROM quizzes q
     JOIN courses c ON c.id = q.course_id
     JOIN enrollments e ON e.course_id = c.id
     WHERE e.student_id = :sid AND q.is_active = 1
     ORDER BY q.created_at DESC'
);
$stmt->execute([':sid' => $user_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'quizzes'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Quizzes</h1><p>Test your knowledge and track your scores</p></div>
            <button class="btn btn-logout" id="studentLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Available Quizzes</h2></div>
            <?php if (empty($quizzes)): ?>
                <div class="empty-state"><p>No quizzes available yet. Enroll in courses to access quizzes.</p></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Quiz</th><th>Course</th><th>Questions</th><th>Last Score</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($quizzes as $q): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($q['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($q['course_title']); ?></td>
                                <td><?php echo $q['question_count']; ?></td>
                                <td>
                                    <?php if ($q['last_score'] !== null): ?>
                                        <span class="role-badge <?php echo $q['last_score'] >= ($q['passing_score'] ?? 70) ? 'role-student' : 'role-admin'; ?>"><?php echo $q['last_score']; ?>%</span>
                                    <?php else: ?>
                                        <span class="muted">Not attempted</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="/index.php?page=quiz-attempt&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-primary">Start Quiz</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>