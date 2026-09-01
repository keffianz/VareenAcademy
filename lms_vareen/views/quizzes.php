<?php
// Quizzes page - Student (Phase 7 MVP)
requireRole('student');
?>

<div class="quizzes-page">
    <div class="container">
        <h1><i class="fas fa-list-check"></i> My Quizzes</h1>

        <p style="color: #666; margin-bottom: 30px;">
            Test your knowledge with quizzes from your enrolled courses. Auto-grading supported for Multiple Choice & True/False.
        </p>

        <div id="quizzesLoading" class="alert alert-info">
            Loading quizzes...
        </div>

        <div id="quizzesUI" style="display:none;">
            <div id="quizzesList"></div>
        </div>

    </div>
</div>

<style>
    .quizzes-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: calc(100vh - 100px);
    }

    .quizzes-page h1 {
        margin-bottom: 20px;
        font-size: 32px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quizzes-page h1 i {
        color: var(--primary-color);
    }

    .empty-state{padding:30px 15px;background:#fff;border:1px dashed #ddd;border-radius:12px;text-align:center;color:#666;}
    .quiz-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;margin-bottom:14px;}
    .quiz-actions{margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;}
    .btn{display:inline-block;border-radius:10px;padding:10px 14px;border:1px solid transparent;cursor:pointer;text-decoration:none;}
    .btn-primary{background:#667eea;color:#fff;}
    .btn-outline-primary{background:transparent;border-color:#667eea;color:#667eea;}
    .muted{color:#666;}
</style>

<script>
    function showToast(msg,type){
        if(typeof window.showToast === 'function') return window.showToast(msg,type);
        alert(msg);
    }

    async function loadQuizzes(){
        const res = await fetch('/src/api/quizzes.php?action=student_list_quizzes', {method:'POST'});
        const data = await res.json();
        document.getElementById('quizzesLoading').style.display='none';
        document.getElementById('quizzesUI').style.display='block';

        const list = document.getElementById('quizzesList');
        list.innerHTML = '';

        if(!data.success){
            showToast(data.message || 'Failed to load quizzes', 'error');
            return;
        }

        const quizzes = data.data || [];
        if(quizzes.length === 0){
            list.innerHTML = `
                <div class="empty-state">
                    <div style="font-size:42px;margin-bottom:10px;"><i class="fas fa-list-check"></i></div>
                    <h2 style="margin:0 0 8px;">No quizzes yet</h2>
                    <p class="muted">When teachers add quizzes to your courses, they will appear here.</p>
                </div>
            `;
            return;
        }

        quizzes.forEach(q=>{
            const card = document.createElement('div');
            card.className = 'quiz-card';
            card.innerHTML = `
                <h3 style="margin:0 0 6px;">${q.title}</h3>
                <div class="muted">Course ID: ${q.course_id} • Pass Score: ${q.pass_score}${q.time_limit_minutes ? ' • Time: '+q.time_limit_minutes+' min' : ''}</div>
                <div class="quiz-actions">
                    <a class="btn btn-primary" href="/index.php?page=quiz-attempt&quiz_id=${q.id}">Start Quiz</a>
                </div>
            `;
            list.appendChild(card);
        });
    }

    loadQuizzes();
</script>

