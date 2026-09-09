<?php
/**
 * Student Assignments View
 */

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

<div class="dashboard-wrapper">
    <?php $student_active = 'assignments'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1>My Assignments</h1>
                <p>View and submit your assignments before the deadline.</p>
            </div>
            <button class="btn btn-logout" id="studentLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2>Assignments</h2></div>

            <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <p>No assignments yet — when your enrolled courses add assignments, they will appear here.</p>
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
                                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>" />

                                    <label>Submission text (optional)</label>
                                    <textarea name="submission_text" placeholder="Write your answer... (optional)"></textarea>

                                    <label>Upload file (optional)</label>
                                    <input type="file" name="submission_file" />

                                    <button class="btn btn-primary" type="submit"><?php echo !empty($a['submission_status']) ? 'Resubmit' : 'Submit'; ?></button>

                                    <?php if (!empty($a['submission_file'])): ?>
                                        <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars($a['submission_file']); ?>" target="_blank">View Last File</a>
                                    <?php endif; ?>
                                </form>
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
    document.querySelectorAll('.submit-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            const res = await fetch('<?php echo appBasePath(); ?>/src/api/assignments.php?action=student_submit', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            const msg = document.createElement('div');
            msg.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
            msg.textContent = data.message || (data.success ? 'Submitted' : 'Submit failed');
            form.prepend(msg);
            if (data.success) {
                setTimeout(() => location.reload(), 1200);
            }
        });
    });
</script>

<style>
.assignments-list{display:flex;flex-direction:column;gap:16px}
.assignment-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px}
.assignment-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap}
.assignment-meta{display:flex;flex-direction:column;gap:8px}
.meta-row{display:flex;gap:10px;align-items:center}
.badge{display:inline-block;background:#f0f4ff;color:#667eea;border:1px solid #d9e2ff;padding:4px 10px;border-radius:999px;font-size:12px}
.assignment-instructions h4{margin:14px 0 8px}
.assignment-submit{margin-top:14px;padding-top:14px;border-top:1px solid #f0f0f0}
textarea,input[type='file']{width:100%}
textarea{min-height:90px}
label{display:block;margin-top:10px;font-size:13px;color:#333}
</style>
