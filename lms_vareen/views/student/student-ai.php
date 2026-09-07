<?php
requireRole('student');
?>
<div class="dashboard-wrapper">
    <?php $student_active = 'student-ai'; include __DIR__ . '/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Ask My Lesson</h1><p>Your AI study tutor — ask questions about your enrolled lessons</p></div>
        </div>

        <div class="dashboard-section">
            <div class="section-header"><h2><i class="fas fa-robot"></i> AI Study Tutor</h2></div>
            <div id="aiStatus" class="alert" style="margin-bottom:14px"></div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="aiLessonSelect">Choose a lesson</label>
                    <select id="aiLessonSelect" class="form-input">
                        <option value="0">Loading your lessons…</option>
                    </select>
                </div>
            </div>

            <div id="aiChatBox" style="max-height:360px;overflow-y:auto;padding:12px;background:#fafafa;border-radius:8px;margin-bottom:12px">
                <div style="color:#888;font-size:13px;text-align:center;padding:20px">
                    Ask anything about the lesson — explain it simply, translate to Hausa, give practice questions, or summarize it.
                </div>
            </div>

            <form id="aiChatForm" style="display:flex;gap:8px">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="text" id="aiQuestion" name="question" placeholder="Ask a question about this lesson…" class="search-input" style="flex:1" required minlength="5" disabled>
                <button type="submit" class="btn btn-primary" id="aiSendBtn" disabled><i class="fas fa-paper-plane"></i> Ask</button>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){
    var s=document.getElementById('studentSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');
    if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});
    if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});
    var apiBase='/lms_vareen/src/api/ai_assistant.php';
    var chatBox=document.getElementById('aiChatBox');
    var lessonSelect=document.getElementById('aiLessonSelect');
    var questionInput=document.getElementById('aiQuestion');
    var sendBtn=document.getElementById('aiSendBtn');
    var form=document.getElementById('aiChatForm');
    var statusBox=document.getElementById('aiStatus');

    function setStatus(text,ok){statusBox.className='alert '+(ok?'alert-success':'alert-warning');statusBox.textContent=text;}

    function appendMessage(role,text){
        var div=document.createElement('div');
        div.style.marginBottom='10px';div.style.padding='10px 14px';div.style.borderRadius='8px';
        div.style.fontSize='13px';div.style.maxWidth='85%';div.style.whiteSpace='pre-wrap';
        if(role==='user'){div.style.background='#667eea';div.style.color='#fff';div.style.marginLeft='auto';div.style.textAlign='right';}
        else{div.style.background='#fff';div.style.border='1px solid #eee';div.style.color='#333';}
        div.textContent=text;
        var ph=chatBox.querySelector('[style*="Ask anything"]');if(ph)ph.remove();
        chatBox.appendChild(div);chatBox.scrollTop=chatBox.scrollHeight;
    }

    fetch(apiBase+'?action=my_lessons')
        .then(function(r){return r.json();})
        .then(function(data){
            if(!data.success){setStatus(data.message||'Failed to load lessons',false);return;}
            var lessons=data.lessons||[];
            if(!lessons.length){setStatus('You have no lessons yet. Enroll in a course to start learning with AI.',false);
                lessonSelect.innerHTML='<option value="0">No lessons available</option>';return;}
            lessonSelect.innerHTML=lessons.map(function(l){return '<option value="'+l.id+'">'+l.title+' — '+(l.course_title||'')+'</option>';}).join('');
            lessonSelect.disabled=false;questionInput.disabled=false;sendBtn.disabled=false;
            setStatus((data.remaining||0)+' daily AI questions remaining out of '+(data.daily_limit||10),data.remaining>0);
        })
        .catch(function(){setStatus('Unable to reach the AI assistant.',false);lessonSelect.innerHTML='<option value="0">Failed to load lessons</option>';});

    form.addEventListener('submit',function(e){
        e.preventDefault();
        var lessonId=lessonSelect.value,question=questionInput.value.trim();
        if(!lessonId||!question)return;
        appendMessage('user',question);questionInput.value='';appendMessage('ai','Thinking…');
        var lastMsg=chatBox.lastElementChild;
        var fd=new FormData();fd.append('lesson_id',lessonId);fd.append('question',question);
        fetch(apiBase+'?action=ask',{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                lastMsg.textContent=data.success?(data.message||data.answer||'Done'):('⚠ '+(data.message||'Something went wrong.'));
            })
            .catch(function(){lastMsg.textContent='Network error. Please try again.';});
    });
})();
</script>