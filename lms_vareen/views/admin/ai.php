<?php
requireRole('admin');
require_once '../src/config/ai_config.php';
$aiConfigured = ai_key_configured();
$maskedKey = ai_masked_key();
$keySource = 'none';
if (getenv('ANTHROPIC_API_KEY')) {
    $keySource = 'environment';
} elseif (is_file(__DIR__ . '/../src/config/ai_local_key.php')) {
    $keySource = 'admin_ui';
}
$conversations = []; // Would be fetched from AI conversation log table
?>
<div class="dashboard-wrapper">
    <?php $admin_active='ai'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>AI Control Center</h1><p>Manage AI assistant settings</p></div>
            <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-robot"></i></div><div class="kpi-info"><span class="kpi-count"><?php echo $aiConfigured?'ON':'OFF'; ?></span><span class="kpi-label">AI Status</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-comments"></i></div><div class="kpi-info"><span class="kpi-count">0</span><span class="kpi-label">Conversations</span></div></div>
            <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-info"><span class="kpi-count">0</span><span class="kpi-label">Active Users</span></div></div>
        </div>
        <div class="dashboard-section">
            <div class="section-header"><h2>AI Settings</h2></div>
            <div class="form-grid">
                <div class="form-group"><label>AI Status</label><p><?php echo $aiConfigured?'<span class="status-ok">✓ Configured</span>':'<span class="status-warn">⚠ Not configured</span>'; ?></p></div>
                <div class="form-group"><label>Provider</label><p>Anthropic (Claude) — <code><?php echo htmlspecialchars(AI_MODEL); ?></code></p></div>
                <div class="form-group"><label>Features</label><p>Answer questions, suggest lessons, translate to Hausa, summarize discussions, flag inappropriate content</p></div>
            </div>
        </div>

        <div class="dashboard-section" id="apiKeySection">
            <div class="section-header"><h2>Anthropic API Key</h2></div>
            <div class="card" style="background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:20px;margin-bottom:20px;max-width:640px">
                <div id="aiKeyStatus" style="margin-bottom:14px;font-size:14px">
                    <?php if ($aiConfigured): ?>
                        <p>Current key: <code id="aiMaskedKey"><?php echo htmlspecialchars($maskedKey ?? ''); ?></code></p>
                        <p class="muted" style="color:#777;font-size:13px;margin-top:4px">Source: <?php echo htmlspecialchars($keySource); ?></p>
                    <?php else: ?>
                        <p class="status-warn">⚠ No API key configured. The AI Assistant widget will not work until a key is set.</p>
                    <?php endif; ?>
                </div>
                <form id="aiKeyForm">
                    <label for="api_key" style="display:block;margin-bottom:6px;font-size:14px;color:#333">New / replacement API key</label>
                    <input type="password" id="api_key" name="api_key" placeholder="sk-ant-..." autocomplete="off"
                           style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px" />
                    <p class="muted" style="color:#777;font-size:12px;margin-top:6px">
                        The key is stored server-side (env var takes precedence) and is never displayed in full again.
                        An environment variable <code>ANTHROPIC_API_KEY</code> overrides anything saved here.
                    </p>
                    <button type="submit" class="btn btn-primary" style="margin-top:12px;background:#667eea;color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer">Save API Key</button>
                </form>
                <div id="aiKeyMsg" style="display:none;margin-top:12px;padding:10px 12px;border-radius:8px;font-size:14px"></div>
            </div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();

(function(){
    var form = document.getElementById('aiKeyForm');
    var msg = document.getElementById('aiKeyMsg');
    if (!form) return;
    form.addEventListener('submit', async function(e){
        e.preventDefault();
        var key = document.getElementById('api_key').value.trim();
        msg.style.display = 'block';
        msg.style.background = '#f0f4ff';
        msg.style.color = '#333';
        msg.textContent = 'Saving…';
        if (!key) { msg.textContent = 'Please paste an API key first.'; return; }
        try {
            var fd = new FormData();
            fd.append('action', 'ai_save_key');
            fd.append('api_key', key);
            var res = await fetch('<?php echo appBasePath(); ?>/src/api/admin.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]')||{}).content || '' },
                body: fd
            });
            var data = await res.json();
            msg.textContent = data.message || (data.success ? 'Saved' : 'Failed');
            msg.style.background = data.success ? '#e6f9ee' : '#fdecec';
            if (data.success) {
                document.getElementById('api_key').value = '';
                var st = document.getElementById('aiKeyStatus');
                st.innerHTML = '<p>Current key: <code>' + (data.masked_key || '') + '</code></p>' +
                               '<p class="muted" style="color:#777;font-size:13px;margin-top:4px">Source: admin_ui</p>';
            }
        } catch (err) {
            msg.textContent = 'Network error — please try again.';
            msg.style.background = '#fdecec';
        }
    });
})();
</script>