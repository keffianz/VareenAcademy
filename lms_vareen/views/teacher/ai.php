<?php
requireRoles(['teacher', 'admin']);
?>
<div class="dashboard-wrapper">
    <?php $teacher_active='ai'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>AI Teaching Assistant</h1><p>Your AI-powered productivity tool</p></div>
        </div>
        <div class="dashboard-section" style="margin-bottom:20px">
            <div class="section-header"><h2>Quick Prompts</h2></div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Generate a quiz from my lesson">Generate Quiz</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Create a rubric for my assignment">Create Rubric</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Draft an announcement for my students">Draft Announcement</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Translate instructions to Hausa">Translate to Hausa</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Generate practice exercises">Practice Exercises</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Explain a difficult topic simply">Explain Topic</button>
                <button class="btn btn-ghost btn-sm ai-prompt" data-prompt="Suggest feedback for student mistakes">Feedback Tips</button>
            </div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>Chat with AI Assistant</h2></div>
            <div id="aiChatBox" style="max-height:400px;overflow-y:auto;padding:12px;background:#fafafa;border-radius:8px;margin-bottom:12px">
                <div style="color:#888;font-size:13px;text-align:center;padding:20px">Ask anything about teaching, grading, or course management</div>
            </div>
            <form id="aiChatForm" style="display:flex;gap:8px">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="text" id="aiPrompt" name="prompt" placeholder="Ask AI for help..." class="search-input" style="flex:1" required>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){
    var s=document.getElementById('teacherSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');
    if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});
    if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});

    var chatBox=document.getElementById('aiChatBox');
    var form=document.getElementById('aiChatForm');
    var promptInput=document.getElementById('aiPrompt');
    var csrfToken=document.querySelector('#aiChatForm [name="csrf_token"]').value;

    function appendMessage(role, text) {
        var div=document.createElement('div');
        div.style.marginBottom='10px';
        div.style.padding='10px 14px';
        div.style.borderRadius='8px';
        div.style.fontSize='13px';
        div.style.maxWidth='85%';
        if(role==='user'){
            div.style.background='#667eea';
            div.style.color='#fff';
            div.style.marginLeft='auto';
            div.style.textAlign='right';
        } else {
            div.style.background='#fff';
            div.style.border='1px solid #eee';
            div.style.color='#333';
            div.style.whiteSpace='pre-wrap';
        }
        div.textContent=text;
        var placeholder=chatBox.querySelector('[style*="Ask anything"]');
        if(placeholder)placeholder.remove();
        chatBox.appendChild(div);
        chatBox.scrollTop=chatBox.scrollHeight;
    }

    // Quick prompt buttons
    document.querySelectorAll('.ai-prompt').forEach(function(btn){
        btn.addEventListener('click',function(){
            promptInput.value=this.getAttribute('data-prompt');
            form.dispatchEvent(new Event('submit'));
        });
    });

    form.addEventListener('submit',function(e){
        e.preventDefault();
        var prompt=promptInput.value.trim();
        if(!prompt)return;
        appendMessage('user',prompt);
        promptInput.value='';
        appendMessage('ai','Thinking...');
        var lastMsg=chatBox.lastElementChild;
        var formData=new FormData();
        formData.append('prompt',prompt);
        formData.append('context','teacher');
        formData.append('csrf_token',csrfToken);
        fetch('/lms_vareen/src/api/teacher.php?action=ai_chat',{method:'POST',body:formData})
            .then(function(r){return r.json()})
            .then(function(data){
                lastMsg.textContent=data.success?(data.response||'No response from AI.'):'Error: '+(data.message||'Failed');
            })
            .catch(function(){lastMsg.textContent='Network error. Please try again.';});
    });
})();
</script>