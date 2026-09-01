<?php
requireLogin();
requireRole('student');
?>
<div class="quiz-result-page">
  <div class="container">
    <h1><i class="fas fa-clipboard-check"></i> Quiz Result</h1>
    <p class="muted">Shows your latest attempt for this quiz.</p>

    <div id="resultLoading" class="alert alert-info" style="display:block;">Loading result...</div>
    <div id="resultUI" style="display:none;">
      <div class="result-summary" style="margin:18px 0; padding:16px; background:#fff; border:1px solid #eee; border-radius:12px;">
        <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
          <div>
            <div class="muted" style="font-size:13px;">Score</div>
            <div id="summaryScore" style="font-size:28px; font-weight:800; color:#1f2937;">-</div>
          </div>
          <div>
            <div class="muted" style="font-size:13px;">Percentage</div>
            <div id="summaryPercent" style="font-size:22px; font-weight:800; color:#1f2937;">-</div>
          </div>
          <div>
            <div class="muted" style="font-size:13px;">Status</div>
            <div id="summaryStatus" style="font-size:16px; font-weight:700; color:#1f2937;">-</div>
          </div>
        </div>
      </div>

      <div id="questionsWrap"></div>

      <div style="margin-top:18px;">
        <a class="btn" style="display:inline-block; border-radius:10px; padding:10px 14px; border:1px solid #ddd; text-decoration:none;" href="/index.php?page=quizzes">Back to Quizzes</a>
      </div>
    </div>
  </div>
</div>

<style>
  .quiz-result-page{padding:40px 0;background:#f8f9fa;min-height:calc(100vh - 100px);}
  .container{max-width:900px;margin:0 auto;padding:0 15px;}
  h1{font-size:28px;margin:0 0 10px;}
  .muted{color:#666;}
  .alert{padding:12px 14px;border-radius:10px;border:1px solid transparent;}
  .alert-info{background:#eef6ff;border-color:#d6e6ff;color:#1d4ed8;}
  .question-card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;margin:14px 0;}
  .badge{display:inline-block;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:700;border:1px solid transparent;}
  .badge-correct{background:#ecfdf5;border-color:#bbf7d0;color:#166534;}
  .badge-wrong{background:#fef2f2;border-color:#fecaca;color:#991b1b;}
  .option-row{display:flex;gap:10px;align-items:center;justify-content:space-between;padding:10px;border:1px solid #eee;border-radius:10px;margin-top:10px;}
  .option-row.correct{background:#ecfdf5;border-color:#bbf7d0;}
  .option-row.wrong{background:#fef2f2;border-color:#fecaca;}
</style>

<script>
const quizId = <?php echo (int)($_GET['quiz_id'] ?? 0); ?>;

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

  async function loadResult(){
    if(!quizId){
      showToast('Quiz ID missing','error');
      return;
    }

    const loading = document.getElementById('resultLoading');
    const ui = document.getElementById('resultUI');
    loading.style.display='block';
    ui.style.display='none';

    const last = await apiPost('/src/api/quizzes.php?action=student_get_last_attempt_for_quiz',{quiz_id:quizId});
    if(!last.success) throw new Error(last.message || 'Failed to load attempt');

    const attempt = last.data;
    if(!attempt){
      loading.style.display='block';
      ui.style.display='none';
      loading.textContent = 'No attempts found for this quiz yet.';
      return;
    }

    // If attempt not graded yet, show message
    if(attempt.status !== 'graded'){
      loading.style.display='block';
      ui.style.display='none';
      loading.textContent = 'Your last attempt is not graded yet.';
      return;
    }

    const details = await apiPost('/src/api/quizzes.php?action=student_get_attempt_details',{attempt_id: attempt.id});
    if(!details.success) throw new Error(details.message || 'Failed to load attempt details');

    const data = details.data;
    const attemptDetails = data.attempt;
    const questions = data.questions || [];

document.getElementById('summaryScore').textContent = `${attemptDetails.score} / ${attemptDetails.max_score}`;
    document.getElementById('summaryPercent').textContent = `${attemptDetails.percentage}%`;
    document.getElementById('summaryStatus').textContent = (attemptDetails.pass_score !== null && attemptDetails.pass_score !== undefined) && Number(attemptDetails.percentage) >= Number(attemptDetails.pass_score) ? 'Passed' : 'Completed';

    const wrap = document.getElementById('questionsWrap');
    wrap.innerHTML = '';

    questions.forEach((q, idx)=>{
      const card = document.createElement('div');
      card.className = 'question-card';

      const correctnessBadge = q.is_correct === 1
        ? '<span class="badge badge-correct">Correct</span>'
        : '<span class="badge badge-wrong">Incorrect</span>';

      let extra = '';
      if(q.question_type === 'multiple_choice' || q.question_type === 'true_false'){
        const userSel = q.selected_option_id;
        extra += `<div class="muted" style="margin-top:8px; font-size:13px;">Selected: ${userSel ? (q.options.find(o=>o.id===userSel)?.option_text || '-') : '—'}</div>`;
        extra += `<div style="margin-top:10px; font-weight:700;">Correct Option:</div>`;

        q.options.forEach(o=>{
          const isCorrect = o.is_correct === 1;
          const isUser = (userSel && o.id === userSel);
          const cls = isCorrect ? 'correct' : (isUser ? 'wrong' : '');
          extra += `<div class="option-row ${cls}"><div style="font-weight:700;">${isCorrect ? '✓' : ''}</div><div style="flex:1;">${o.option_text}</div><div class="muted">${isCorrect ? 'Correct' : (isUser ? 'Your answer' : '')}</div></div>`;
        });
      } else {
        extra += `<div class="muted" style="margin-top:8px; font-size:13px;">Short answer (MVP grading pending)</div>`;
        if((q.answer_text||'').trim()){
          extra += `<div style="margin-top:8px; white-space:pre-wrap;">Your answer: <span style="font-weight:700;">${escapeHtml(q.answer_text)}</span></div>`;
        }
      }

      card.innerHTML = `
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
          <div>
            <div style="font-weight:800; margin-bottom:6px;">${idx+1}. ${escapeHtml(q.question_text)}</div>
            <div class="muted">Type: ${q.question_type} • Points: ${q.points} • Earned: ${q.points_earned}</div>
          </div>
          ${correctnessBadge}
        </div>
        ${extra}
      `;

      wrap.appendChild(card);
    });

    loading.style.display='none';
    ui.style.display='block';
  }

  function escapeHtml(str){
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','<')
      .replaceAll('>','>')
      .replaceAll('"','"')
      .replaceAll("'",'&#039;');
  }

  loadResult().catch(e=>{showToast(e.message||'Failed','error');});
</script>


