<?php
// Student quiz attempt UI - Phase 7 MVP
requireLogin();
requireRole('student');

require_once 'src/classes/Database.php';
$db = (new Database())->connect();
$user_id = getCurrentUserId();

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$attempt_id = (int)($_GET['attempt_id'] ?? 0);

if (!$quiz_id) {
    header('Location: ' . BASE_URL . '?page=quizzes');
    exit;
}

?>
  <div class="quiz-attempt-page">
  <div class="container">
    <h1><i class="fas fa-question-circle"></i> Quiz Attempt</h1>
    <p class="muted">Answer all questions then submit. Auto-grading for multiple choice and true/false.</p>
    <div class="quiz-progress" id="quizProgress" aria-live="polite" style="margin:10px 0; color:#667eea; font-weight:700;">Loading…</div>


    <div id="quizLoad">Loading quiz...</div>

    <div id="quizUI" style="display:none;">
      <form id="quizForm">
        <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz_id; ?>" />
        <input type="hidden" name="attempt_id" id="attempt_id" value="<?php echo (int)$attempt_id; ?>" />
        <div id="questions"></div>
        <button class="btn btn-primary" type="submit">Submit Quiz</button>
      </form>
    </div>

  </div>
</div>

<style>
  .quiz-attempt-page{padding:40px 0;background:#f8f9fa;min-height:calc(100vh - 100px);}
  .container{max-width:900px;margin:0 auto;padding:0 15px;}
  h1{font-size:28px;margin:0 0 10px;}
  .muted{color:#666;}
  .question-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;margin:14px 0;}
  .option{display:flex;gap:10px;align-items:center;margin:10px 0;}
  .option input{transform:scale(1.2);}
  .btn{display:inline-block;border-radius:10px;padding:10px 14px;border:1px solid transparent;cursor:pointer;}
  .btn-primary{background:#667eea;color:#fff;}
</style>

<script>
  const quizId = <?php echo (int)$quiz_id; ?>;

  function showToast(msg,type){
    if(typeof window.showToast === 'function') return window.showToast(msg,type);
    alert(msg);
  }

  async function apiPost(url, dataObj){
    const fd = new FormData();
    Object.entries(dataObj).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(url,{method:'POST',body:fd});
    return res.json();
  }

  async function startOrUseAttempt(){
    // If attempt_id passed, just use it (fresh UI will still submit)
    const attemptEl = document.getElementById('attempt_id');
    let attemptId = parseInt(attemptEl.value || '0',10);

    if(!attemptId){
      const r = await apiPost('src/api/quizzes.php?action=student_start_attempt',{quiz_id:quizId});

      if(!r.success) throw new Error(r.message||'Failed to start attempt');
      attemptEl.value = r.data ? r.data.attempt_id : r.attempt_id;
      attemptId = parseInt(attemptEl.value,10);
    }

    return attemptId;
  }

  async function loadQuiz(){
    const r = await apiPost('src/api/quizzes.php?action=student_get_quiz_with_questions',{quiz_id:quizId});

    if(!r.success) throw new Error(r.message||'Failed to load quiz');

    const quiz = r.data.quiz;
    const questions = r.data.questions;

    document.getElementById('quizLoad').style.display='none';
    const ui = document.getElementById('quizUI');
    ui.style.display='block';

    const questionsEl = document.getElementById('questions');
    questionsEl.innerHTML = '';

    questions.forEach((q, idx)=>{
      const card = document.createElement('div');
      card.className = 'question-card';

      const title = document.createElement('div');
      title.innerHTML = '<strong>'+(idx+1)+'.</strong> '+q.question_text+'<div class="muted" style="margin-top:4px;">Type: '+q.question_type+' • Points: '+q.points+'</div>';
      card.appendChild(title);

      if(q.question_type === 'short_answer'){
        const ta = document.createElement('textarea');
        ta.name = 'answer_text_'+q.id;
        ta.style.width = '100%';
        ta.style.minHeight = '90px';
        ta.placeholder = 'Type your answer...';
        card.appendChild(ta);
      } else {
        const opts = q.options || [];
        opts.forEach(opt=>{
          const row = document.createElement('label');
          row.className='option';
          row.innerHTML = `<input type="radio" name="q_${q.id}" value="${opt.id}" /> <span>${opt.option_text}</span>`;
          card.appendChild(row);
        });
      }

      questionsEl.appendChild(card);
    });

    // Quiz questions loaded; update progress
    const quizProgress = document.getElementById('quizProgress');
    if (quizProgress) {
      const total = questions.length;
      const answered = countAnswered(questions);
      quizProgress.textContent = `Answered: ${answered} / ${total}`;
    }

    window.__quizQuestions = questions;
  }

  function countAnswered(questions){
    let answered = 0;
    questions.forEach(q=>{
      if(q.question_type === 'short_answer'){
        const ta = document.querySelector('textarea[name="answer_text_'+q.id+'"]');
        if(ta && ta.value && ta.value.trim().length > 0) answered++;
      } else {
        const selected = document.querySelector('input[name="q_'+q.id+'"]:checked');
        if(selected) answered++;
      }
    });
    return answered;
  }


  async function init(){
    try{
      await startOrUseAttempt();
      await loadQuiz();
    }catch(e){
      showToast(e.message||'Error','error');
    }
  }

  document.getElementById('quizForm').addEventListener('submit', async (e)=>{
    e.preventDefault();

    const attemptId = parseInt(document.getElementById('attempt_id').value,10);
    const questions = window.__quizQuestions || [];

    const answers = questions.map(q=>{
      if(q.question_type === 'short_answer'){
        const ta = document.querySelector('textarea[name="answer_text_'+q.id+'"]');
        return {question_id:q.id, selected_option_id:null, answer_text: (ta?ta.value:'')};
      }

      const selected = document.querySelector('input[name="q_'+q.id+'"]:checked');
      return {question_id:q.id, selected_option_id: selected?parseInt(selected.value,10):null, answer_text:null};
    });

    const res = await fetch('src/api/quizzes.php?action=student_submit_attempt',{


      method:'POST',
      body:(()=>{
        const fd = new FormData();
        fd.append('attempt_id', attemptId);
        fd.append('answers', JSON.stringify(answers));
        return fd;
      })()
    });

    const data = await res.json();
    if(data.success){
      showToast('Quiz submitted. Score: '+data.data.percentage+'%','success');
      // last attempt only UI; return to quizzes page
      setTimeout(()=>{ window.location.href = 'index.php?page=quizzes'; }, 800);

    }else{
      showToast(data.message||'Submit failed','error');
    }
  });

  init();
</script>

