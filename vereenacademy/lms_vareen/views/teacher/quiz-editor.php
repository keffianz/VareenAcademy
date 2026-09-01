<?php
// Teacher Quiz Editor - Phase 7 MVP
requireRoles(['teacher', 'admin']);

require_once 'src/classes/Course.php';
require_once 'src/classes/Database.php';

$userId = getCurrentUserId();
$role = getCurrentUserRole();

$db = (new Database())->connect();

$course_id = (int)($_GET['course_id'] ?? 0);

// Basic teacher course list (like teacher/dashboard)
$courseList = [];
if ($course_id) {
    $courseStmt = $db->prepare('SELECT * FROM courses WHERE id = :id AND is_active = 1');
    $courseStmt->execute([':id' => $course_id]);
    $c = $courseStmt->fetch(PDO::FETCH_ASSOC);
    if ($c) {
        if ($role === 'admin' || (int)$c['teacher_id'] === (int)$userId) {
            $courseList[] = $c;
        }
    }
}

if (empty($courseList)) {
    $stmt = $db->prepare('SELECT * FROM courses WHERE is_active = 1');
    $stmt->execute();
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $c) {
        if ($role === 'admin' || (int)$c['teacher_id'] === (int)$userId) $courseList[] = $c;
    }
}

// If no course selected yet, redirect to first course
if (empty($course_id) && !empty($courseList)) {
    $course_id = (int)$courseList[0]['id'];
}

$quizzes = [];
if ($course_id) {
    $stmt = $db->prepare('SELECT * FROM quizzes WHERE course_id = :cid AND is_active = 1 ORDER BY created_at DESC');
    $stmt->execute([':cid' => $course_id]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper to fetch quiz questions
$quiz_questions = [];
$quiz_options = [];
if (!empty($_GET['quiz_id'])) {
    $quiz_id = (int)$_GET['quiz_id'];

    $qstmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = :qid ORDER BY position, id');
    $qstmt->execute([':qid' => $quiz_id]);
    $quiz_questions = $qstmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($quiz_questions as $q) {
        $ostmt = $db->prepare('SELECT * FROM quiz_options WHERE question_id = :qid ORDER BY position, id');
        $ostmt->execute([':qid' => (int)$q['id']]);
        $quiz_options[(int)$q['id']] = $ostmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$selectedQuizId = (int)($_GET['quiz_id'] ?? 0);
?>

<div class="quiz-editor-page">
    <div class="container">
        <div class="page-header">
            <h1>Quiz Builder</h1>
            <p>Create quizzes, add questions and options.</p>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>Select Course</h2>
                <div class="field">
                    <select id="courseSelect" class="input">
                        <?php foreach ($courseList as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === (int)$course_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h2 style="margin-top:18px;">Quizzes</h2>
                <div class="quiz-list" id="quizList">
                    <?php if (empty($quizzes)): ?>
                        <div class="empty">No quizzes yet.</div>
                    <?php else: ?>
                        <?php foreach ($quizzes as $q): ?>
                            <div class="quiz-row <?php echo ((int)$q['id'] === (int)$selectedQuizId) ? 'active' : ''; ?>" data-quiz-id="<?php echo (int)$q['id']; ?>">
                                <div>
                                    <div class="quiz-title"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <div class="muted">Pass: <?php echo (int)$q['pass_score']; ?> • Timed: <?php echo !empty($q['is_timed']) ? 'Yes' : 'No'; ?></div>
                                </div>
                                <a class="btn btn-small" href="/index.php?page=teacher-quiz-editor&course_id=<?php echo (int)$course_id; ?>&quiz_id=<?php echo (int)$q['id']; ?>">Edit</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 style="margin-top:18px;">Create Quiz</h2>
                <form id="createQuizForm" class="form">
                    <input type="hidden" name="course_id" value="<?php echo (int)$course_id; ?>" />
                    <label>Title</label>
                    <input name="title" class="input" required />

                    <label>Description</label>
                    <textarea name="description" class="input"></textarea>

                    <label>Instructions</label>
                    <textarea name="instructions" class="input"></textarea>

                    <label>Time limit (minutes, optional)</label>
                    <input type="number" name="time_limit_minutes" class="input" min="0" value="0" />

                    <label>Pass score</label>
                    <input type="number" name="pass_score" class="input" min="0" value="60" />

                    <button class="btn btn-primary" type="submit">Create</button>
                </form>
            </div>

            <div class="card">
                <h2>Questions</h2>
                <?php if (!$selectedQuizId): ?>
                    <div class="empty">Select a quiz to add questions.</div>
                <?php else: ?>
                    <div class="quiz-meta muted">Quiz ID: <?php echo (int)$selectedQuizId; ?></div>

                    <div class="question-add">
                        <h3>Add Question</h3>
                        <form id="addQuestionForm" class="form">
                            <input type="hidden" name="quiz_id" value="<?php echo (int)$selectedQuizId; ?>" />
                            <label>Question text</label>
                            <textarea name="question_text" class="input" required></textarea>

                            <label>Type</label>
                            <select name="question_type" class="input">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>

                            <label>Points</label>
                            <input type="number" name="points" class="input" min="1" value="1" />

                            <label>Position</label>
                            <input type="number" name="position" class="input" min="0" value="0" />

                            <button class="btn btn-primary" type="submit">Add Question</button>
                        </form>
                    </div>

                    <hr />

                    <div class="questions-list">
                        <?php foreach ($quiz_questions as $q): ?>
                            <div class="question-block">
                                <div class="question-title">
                                    <strong><?php echo htmlspecialchars($q['question_text']); ?></strong>
                                    <div class="muted">Type: <?php echo htmlspecialchars($q['question_type']); ?> • Points: <?php echo (int)$q['points']; ?></div>
                                </div>

                                <div class="options-block">
                                    <h4>Options</h4>
                                    <?php if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false'): ?>
                                        <div class="options">
                                            <?php foreach (($quiz_options[(int)$q['id']] ?? []) as $opt): ?>
                                                <div class="opt-row">
                                                    <span class="opt-text"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                                    <span class="muted">Correct: <?php echo ((int)$opt['is_correct'] === 1) ? 'Yes' : 'No'; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <form class="add-option-form" data-question-id="<?php echo (int)$q['id']; ?>">
                                            <label>Option text</label>
                                            <input class="input" name="option_text" required />

                                            <label>
                                                Correct?
                                                <input type="checkbox" name="is_correct" value="1" />
                                            </label>

                                            <label>Position</label>
                                            <input class="input" type="number" name="position" min="0" value="0" />

                                            <button class="btn" type="submit">Add Option</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="muted">Short answer: options are not used for MVP auto-grading.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr />
                        <?php endforeach; ?>
                    </div>

                    <div class="empty muted" style="margin-top:10px;">
                        MVP note: updates/deletes for questions/options can be added later; current editor focuses on creation.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .quiz-editor-page{padding:40px 0;background:#f8f9fa;min-height:calc(100vh - 100px);}
    .container{max-width:1100px;margin:0 auto;padding:0 15px;}
    .page-header h1{font-size:32px;margin:0 0 10px;color:#333;}
    .page-header p{margin:0 0 20px;color:#666;}
    .grid-2{display:grid;grid-template-columns:420px 1fr;gap:18px;align-items:start;}
    .card{background:#fff;border:1px solid #eee;border-radius:12px;padding:18px;}
    .form{display:flex;flex-direction:column;gap:10px;}
    .field{margin-bottom:10px;}
    label{font-size:13px;color:#333;}
    .input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;}
    textarea.input{min-height:90px;}
    .btn{display:inline-block;border-radius:10px;padding:10px 14px;text-decoration:none;border:1px solid transparent;cursor:pointer;}
    .btn-primary{background:#667eea;color:#fff;}
    .btn-small{padding:8px 12px;font-size:12px;}
    .muted{color:#666;}
    .empty{padding:12px;border:1px dashed #ddd;border-radius:10px;color:#666;background:#fafafa;}
    .quiz-row{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px;border:1px solid #eee;border-radius:10px;margin-bottom:10px;}
    .quiz-row.active{border-color:#667eea;background:#f0f4ff;}
    .quiz-title{font-weight:700;}
    .question-block{padding:10px 0;}
    .options{display:flex;flex-direction:column;gap:8px;}
    .opt-row{display:flex;justify-content:space-between;gap:10px;background:#f8f9fa;border:1px solid #eee;border-radius:10px;padding:10px;}
    .opt-text{font-weight:600;}
    hr{border:0;border-top:1px solid #eee;margin:18px 0;}
    @media (max-width: 900px){.grid-2{grid-template-columns:1fr;}}
</style>

<script>
    const courseSelect = document.getElementById('courseSelect');
    if(courseSelect){
        courseSelect.addEventListener('change', ()=>{
            const cid = courseSelect.value;
            window.location.href = '/index.php?page=teacher-quiz-editor&course_id=' + cid;
        });
    }

    function showToast(msg, type='success'){
        // Fallback if global showToast doesn't exist
        if(typeof window.showToast === 'function') return window.showToast(msg, type);
        alert(msg);
    }

    document.getElementById('createQuizForm')?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action','teacher_create_quiz');

        const res = await fetch('/src/api/quizzes.php?action=teacher_create_quiz', {method:'POST', body: fd});
        const data = await res.json();
        if(data.success){
            showToast(data.message || 'Quiz created', 'success');
            setTimeout(()=>{
                window.location.href = '/index.php?page=teacher-quiz-editor&course_id=' + fd.get('course_id') + '&quiz_id=' + data.quiz_id;
            }, 700);
        }else{
            showToast(data.message || 'Failed', 'error');
        }
    });

    document.getElementById('addQuestionForm')?.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const fd = new FormData(e.target);
        const quiz_id = fd.get('quiz_id');
        const res = await fetch('/src/api/quizzes.php?action=teacher_add_question', {method:'POST', body: fd});
        const data = await res.json();
        if(data.success){
            showToast(data.message || 'Question added', 'success');
            setTimeout(()=>{
                window.location.href = '/index.php?page=teacher-quiz-editor&course_id=<?php echo (int)$course_id; ?>&quiz_id=' + quiz_id;
            }, 600);
        }else{
            showToast(data.message || 'Failed', 'error');
        }
    });

    document.querySelectorAll('.add-option-form').forEach(form=>{
        form.addEventListener('submit', async (e)=>{
            e.preventDefault();
            const qid = form.getAttribute('data-question-id');
            const fd = new FormData(form);
            fd.append('question_id', qid);
            const is_correct = fd.get('is_correct') ? 1 : 0;
            fd.set('is_correct', is_correct);

            const res = await fetch('/src/api/quizzes.php?action=teacher_add_option', {method:'POST', body: fd});
            const data = await res.json();
            if(data.success){
                showToast(data.message || 'Option added', 'success');
                setTimeout(()=>{
                    window.location.href = '/index.php?page=teacher-quiz-editor&course_id=<?php echo (int)$course_id; ?>&quiz_id=<?php echo (int)$selectedQuizId; ?>';
                }, 600);
            }else{
                showToast(data.message || 'Failed', 'error');
            }
        });
    });
</script>

