<?php

// Start session BEFORE including middleware that uses $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow the login page to render without auth restrictions
$page = $_GET['page'] ?? null;
if ($page === 'login') {
    require_once __DIR__ . '/views/auth/login.php';
    exit;
}

// Include auth middleware FIRST so requireRole(), getCurrentUserId(), etc. exist
require_once __DIR__ . '/src/middleware/auth.php';

// Assignments page - Student
requireRole('student');






require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Database.php';

$db = (new Database())->connect();
$user_id = getCurrentUserId();

$stmt = $db->prepare("SELECT a.*, c.title as course_title,
    (SELECT status FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id ORDER BY submitted_at DESC LIMIT 1) as submission_status,
    (SELECT file_path FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = :student_id ORDER BY submitted_at DESC LIMIT 1) as submission_file
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON e.course_id = c.id
    WHERE e.student_id = :student_id AND a.is_active = 1
    ORDER BY a.due_date ASC, a.created_at DESC");
$stmt->execute([':student_id' => (int)$user_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="assignments-page">
    <div class="container">
        <h1><i class="fas fa-tasks"></i> My Assignments</h1>
        <p class="muted">View and submit your assignments before the deadline.</p>

        <?php if (empty($assignments)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h2>No assignments yet</h2>
                <p>When your enrolled courses add assignments, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="assignments-list">
                <?php foreach ($assignments as $a): ?>
                    <div class="assignment-card">
                        <div class="assignment-head">
                            <div>
                                <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                                <div class="muted"><i class="fas fa-book"></i> <?php echo htmlspecialchars($a['course_title'] ?? ''); ?></div>
                            </div>
                            <div class="assignment-meta">
                                <div class="meta-row">
                                    <span class="badge">Due</span>
                                    <span><?php echo $a['due_date'] ? htmlspecialchars(date('M d, Y H:i', strtotime($a['due_date']))) : '—'; ?></span>
                                </div>
                                <div class="meta-row">
                                    <span class="badge">Status</span>
                                    <span><?php echo htmlspecialchars($a['submission_status'] ?? 'pending'); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($a['instructions'])): ?>
                            <div class="assignment-instructions">
                                <h4>Instructions</h4>
                                <p><?php echo nl2br(htmlspecialchars($a['instructions'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="assignment-submit">
                            <?php $alreadySubmitted = !empty($a['submission_status']) && $a['submission_status'] === 'submitted' || $a['submission_status'] === 'graded'; ?>
                            <form class="submit-form" enctype="multipart/form-data" method="POST" data-assignment-id="<?php echo (int)$a['id']; ?>">
                                <input type="hidden" name="assignment_id" value="<?php echo (int)$a['id']; ?>" />

                                <label>Submission text (optional)</label>
                                <textarea name="submission_text" placeholder="Write your answer... (optional)"></textarea>

                                <label>Upload file (optional)</label>
                                <input type="file" name="submission_file" />

                                <button class="btn btn-primary" type="submit">
                                    <?php echo !empty($a['submission_status']) ? 'Resubmit' : 'Submit'; ?>
                                </button>

                                <?php if (!empty($a['submission_file'])): ?>
                                    <a class="btn btn-outline" href="<?php echo htmlspecialchars($a['submission_file']); ?>" target="_blank">View Last File</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll('.submit-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const fd = new FormData(form);

            // require either text or file (server validates too)
            const res = await fetch('/src/api/assignments.php?action=student_submit', {
                method: 'POST',
                body: fd
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || 'Submitted', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Submit failed', 'error');
            }
        });
    });
</script>

<style>
.assignments-page{padding:40px 0;background:#f8f9fa;min-height:calc(100vh - 100px);}
.assignments-page .container{max-width:1050px;margin:0 auto;padding:0 15px;}
.assignments-page h1{font-size:32px;margin-bottom:10px;}
.muted{color:#666;}
.assignments-list{display:flex;flex-direction:column;gap:16px;}
.assignment-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;}
.assignment-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;}
.assignment-meta{display:flex;flex-direction:column;gap:8px;}
.meta-row{display:flex;gap:10px;align-items:center;}
.badge{display:inline-block;background:#f0f4ff;color:#667eea;border:1px solid #d9e2ff;padding:4px 10px;border-radius:999px;font-size:12px;}
.assignment-instructions h4{margin:14px 0 8px;}
.assignment-submit{margin-top:14px;padding-top:14px;border-top:1px solid #f0f0f0;}
textarea,input[type='file']{width:100%;}
textarea{min-height:90px;}
label{display:block;margin-top:10px;font-size:13px;color:#333;}
.btn{display:inline-block;border-radius:10px;padding:10px 14px;text-decoration:none;border:1px solid transparent;cursor:pointer;margin-top:10px;}
.btn-primary{background:#667eea;color:#fff;}
.btn-outline{background:transparent;border-color:#667eea;color:#667eea;}
.submit-form{display:flex;flex-direction:column;gap:2px;}
</style>

