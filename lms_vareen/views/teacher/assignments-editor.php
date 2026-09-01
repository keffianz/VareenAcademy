<?php
/**
 * Teacher Assignments Editor
 */

requireRoles(['teacher', 'admin']);

require_once 'src/classes/Course.php';
require_once 'src/classes/Enrollment.php';
require_once 'src/classes/Module.php';

$db = (new Database())->connect();

$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Get teacher courses
$stmt = $db->prepare("SELECT * FROM courses WHERE is_active = 1");
$stmt->execute();
$allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$teacherCourses = array_values(array_filter($allCourses, function($c) use ($role, $userId) {
    return $role === 'admin' || (int)($c['teacher_id'] ?? 0) === (int)$userId;
}));

$selectedCourseId = (int)($_GET['course_id'] ?? ($teacherCourses[0]['id'] ?? 0));

$assignments = [];
if ($selectedCourseId) {
    $stmt = $db->prepare("SELECT * FROM assignments WHERE course_id = :course_id AND is_active = 1 ORDER BY due_date ASC, created_at DESC");
    $stmt->execute([':course_id' => $selectedCourseId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container">
    <div class="page-header">
        <h1>Assignments</h1>
        <p>Create assignments and grade submissions.</p>
    </div>

    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 18px;">
        <div class="card">
            <h2>Create Assignment</h2>
            <form id="assignmentCreateForm">
                <label>Course</label>
                <select name="course_id" required>
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === $selectedCourseId) ? 'selected' : ''; ?> >
                            <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Title</label>
                <input type="text" name="title" required />

                <label>Description</label>
                <textarea name="description"></textarea>

                <label>Instructions</label>
                <textarea name="instructions" required></textarea>

                <label>Due date (optional)</label>
                <input type="datetime-local" name="due_date" />

                <label>Max score</label>
                <input type="number" name="max_score" value="100" />

                <button class="btn btn-primary" type="submit">Create</button>
            </form>
        </div>

        <div class="card">
            <h2>Your Assignments</h2>
            <?php if (empty($assignments)): ?>
                <p>No assignments for this course yet.</p>
            <?php else: ?>
                <div class="assignments-list">
                    <?php foreach ($assignments as $a): ?>
                        <div class="assignment-item" data-id="<?php echo (int)$a['id']; ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                                <div class="muted">Due: <?php echo $a['due_date'] ? htmlspecialchars($a['due_date']) : '—'; ?></div>
                            </div>
                            <div class="actions">
                                <button class="btn btn-outline" onclick="viewSubmissions(<?php echo (int)$a['id']; ?>)">View Submissions</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="submissionsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:999; padding:30px;">
    <div style="background:#fff; max-width:920px; margin:0 auto; border-radius:12px; padding:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2 style="margin:0;">Submissions</h2>
            <button class="btn btn-outline" onclick="closeModal()">Close</button>
        </div>
        <div id="submissionsContainer"></div>
    </div>
</div>

<script>
    const formToPayload = (form) => {
        const fd = new FormData(form);
        const payload = {};
        for (const [k,v] of fd.entries()) payload[k] = v;
        // datetime-local -> mysql datetime string
        if (payload.due_date) {
            payload.due_date = payload.due_date.replace('T', ' ') + ':00';
        }
        return payload;
    };

    document.getElementById('assignmentCreateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = formToPayload(form);

        const res = await fetch('/src/api/assignments.php?action=teacher_create', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams(payload)
        });

        const data = await res.json();
        if (data.success) location.reload();
        else showToast(data.message || 'Create failed', 'error');
    });

    async function viewSubmissions(assignmentId) {
        const res = await fetch('/src/api/assignments.php?action=teacher_list_submissions', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ assignment_id: assignmentId })
        });
        const data = await res.json();
        if (!data.success) {
            showToast(data.message || 'Failed', 'error');
            return;
        }

        const container = document.getElementById('submissionsContainer');
        container.innerHTML = '';

        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p>No submissions yet.</p>';
        } else {
            data.data.forEach(s => {
                const fileLink = s.file_path ? `<a href="${s.file_path}" target="_blank" class="btn btn-small">View File</a>` : '';

                const html = `
                    <div class="submission-item" style="border:1px solid #eee; border-radius:10px; padding:14px; margin-bottom:12px;">
                        <div><strong>${s.first_name} ${s.last_name}</strong></div>
                        <div class="muted">Submitted at: ${s.submitted_at || ''}</div>
                        <div style="margin-top:10px;">${s.submission_text ? `<div><strong>Text:</strong><pre style="white-space:pre-wrap;">${escapeHtml(s.submission_text)}</pre></div>` : ''}</div>
                        <div style="margin-top:10px; display:flex; gap:10px; align-items:center;">${fileLink}</div>

                        <div style="margin-top:12px; padding-top:12px; border-top:1px solid #f0f0f0;">
                            ${s.status === 'graded' ? `<div><strong>Graded:</strong> Score ${s.score ?? '-'}</div><div class="muted">${s.feedback ? escapeHtml(s.feedback) : ''}</div>` : `
                                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    <input type="number" id="score_${s.id}" placeholder="Score" style="width:120px;" />
                                    <input type="text" id="feedback_${s.id}" placeholder="Feedback" style="flex:1; min-width:240px;" />
                                    <button class="btn btn-primary" onclick="grade(${s.id})">Grade</button>
                                </div>
                            `}
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', html);
            });
        }

        document.getElementById('submissionsModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('submissionsModal').style.display = 'none';
    }

    async function grade(submissionId) {
        const score = document.getElementById('score_' + submissionId).value;
        const feedback = document.getElementById('feedback_' + submissionId).value;

        const res = await fetch('/src/api/assignments.php?action=teacher_grade', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({ submission_id: submissionId, score, feedback })
        });
        const data = await res.json();
        if (data.success) {
            // refresh modal
            // simplest: reload page
            location.reload();
        } else {
            showToast(data.message || 'Grade failed', 'error');
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&','&amp;')
            .replaceAll('<','<')
            .replaceAll('>','>')
            .replaceAll('"','"')
            .replaceAll("'",'&#039;');
    }
</script>

<style>
    .container{padding:30px 15px;max-width:1100px;margin:0 auto;}
    .page-header h1{margin:0 0 8px 0;font-size:28px;}
    .page-header p{margin:0 0 20px 0;color:#666;}
    .card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:18px;}
    label{display:block;margin-top:12px;font-size:14px;color:#333;}
    input[type='text'],input[type='number'],input[type='datetime-local'],textarea,select{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;margin-top:6px;}
    textarea{min-height:90px;}
    .btn{display:inline-block;border-radius:10px;padding:10px 14px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
    .btn-primary{background:#667eea;color:#fff;}
    .btn-outline{background:transparent;border-color:#667eea;color:#667eea;}
    .muted{color:#777;font-size:13px;margin-top:6px;}
    .assignments-list{display:flex;flex-direction:column;gap:12px;}
    .assignment-item{display:flex;justify-content:space-between;align-items:center;gap:12px;border:1px solid #eee;border-radius:10px;padding:12px 14px;}
</style>

