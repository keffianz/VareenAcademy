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

      <div id="evaluationPanel" style="display:none; margin:18px 0; padding:16px; background:#fff; border:1px solid #e5e7eb; border-radius:12px;">
        <h2 style="font-size:18px; margin:0 0 8px;">Evaluation &amp; Study Advice</h2>
        <div id="evalAdvice" class="muted"></div>
        <div id="evalCounts" class="muted" style="margin-top:6px; font-size:13px;"></div>
        <div id="evalWeakList" style="margin-top:10px;"></div>
        <div id="evalMistakes" style="margin-top:10px;"></div>
        <div id="evalLessons" style="margin-top:10px;"></div>
      </div>

      <div id="questionsWrap"></div>

      <div style="margin-top:18px;">
        <a class="btn" style="display:inline-block; border-radius:10px; padding:10px 14px; border:1px solid #ddd; text-decoration:none;" href="<?php echo appBasePath(); ?>/index.php?page=quizzes">Back to Quizzes</a>
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
// App-relative API base (the app lives under /lms_vareen/)
const API_BASE = '<?php echo appBasePath(); ?>/src/api/';

  function getCsrfToken(){
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function showToast(msg,type){
    if(typeof window.showToast === 'function') return window.showToast(msg,type);
    alert(msg);
  }

  async function apiPost(url, dataObj){
    const fd = new FormData();
    Object.entries(dataObj).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(url,{method:'POST',body:fd,headers:{'X-CSRF-Token':getCsrfToken()}});
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

    const last = await apiPost(API_BASE + 'quizzes.php?action=student_get_last_attempt_for_quiz',{quiz_id:quizId});
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

    const details = await apiPost(API_BASE + 'quizzes.php?action=student_get_attempt_details',{attempt_id: attempt.id});
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
        const selectedText = userSel ? (q.options.find(o=>o.id===userSel)?.option_text || '-') : '—';
        extra += `<div class="muted" style="margin-top:8px; font-size:13px;">Selected: ${escapeHtml(selectedText)}</div>`;
        extra += `<div style="margin-top:10px; font-weight:700;">Correct Option:</div>`;

        q.options.forEach(o=>{
          const isCorrect = o.is_correct === 1;
          const isUser = (userSel && o.id === userSel);
          const cls = isCorrect ? 'correct' : (isUser ? 'wrong' : '');
          extra += `<div class="option-row ${cls}"><div style="font-weight:700;">${isCorrect ? '✓' : ''}</div><div style="flex:1;">${escapeHtml(o.option_text)}</div><div class="muted">${isCorrect ? 'Correct' : (isUser ? 'Your answer' : '')}</div></div>`;
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
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
    return String(str ?? '').replace(/[&<>"']/g, c => map[c]);
  }

  function renderEvaluation(ev){
    if(!ev) return;
    let e = ev;
    if(typeof e === 'string'){
      try { e = JSON.parse(e); } catch(err){ return; }
    }
    const panel = document.getElementById('evaluationPanel');
    if(!panel) return;
    panel.style.display = 'block';

    document.getElementById('evalAdvice').textContent = e.study_advice || '';

    const auto = e.auto_graded || {};
    let counts = 'Auto-graded: ' + (auto.correct || 0) + ' correct • ' + (auto.incorrect || 0) + ' incorrect';
    if (auto.needs_review) counts += ' • ' + auto.needs_review + ' short answer(s) pending teacher review';
    document.getElementById('evalCounts').textContent = counts;

    const weakList = document.getElementById('evalWeakList');
    weakList.innerHTML = '';
    const weak = e.weak_areas || [];
    if(weak.length){
      const h = document.createElement('div');
      h.style.fontWeight = '700';
      h.textContent = 'Weak areas (topics to revise):';
      weakList.appendChild(h);
      weak.forEach(w=>{
        const d = document.createElement('div');
        d.className = 'muted';
        d.textContent = '• ' + (w.question || '');
        weakList.appendChild(d);
      });
    } else {
      const d = document.createElement('div');
      d.className = 'muted';
      d.textContent = 'No weak areas detected in the auto-graded questions.';
      weakList.appendChild(d);
    }

    const mistakes = document.getElementById('evalMistakes');
    mistakes.innerHTML = '';
    const ms = e.common_mistakes || [];
    if(ms.length){
      const h = document.createElement('div');
      h.style.fontWeight = '700';
      h.style.marginTop = '8px';
      h.textContent = 'Common mistakes:';
      mistakes.appendChild(h);
      ms.forEach(m=>{
        const d = document.createElement('div');
        d.className = 'muted';
        d.textContent = '• ' + m;
        mistakes.appendChild(d);
      });
    }

    const lessons = document.getElementById('evalLessons');
    lessons.innerHTML = '';
    const rec = e.recommended_lessons || [];
    if(rec.length){
      const h = document.createElement('div');
      h.style.fontWeight = '700';
      h.style.marginTop = '8px';
      h.textContent = 'Recommended lessons:';
      lessons.appendChild(h);
      rec.forEach(l=>{
        const a = document.createElement('a');
        a.href = '<?php echo appBasePath(); ?>/index.php?page=lessons&id=' + encodeURIComponent(l.id);
        a.textContent = l.title;
        a.style.display = 'block';
        lessons.appendChild(a);
      });
    }
  }

  loadResult().catch(e=>{showToast(e.message||'Failed','error');});
</script>


