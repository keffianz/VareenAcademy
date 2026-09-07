<?php
requireRole('admin');
$aiConfigured = file_exists(__DIR__.'/../src/config/ai_config.php');
$conversations = []; // Would be fetched from AI conversation log table
?>
<div class="dashboard-wrapper">
    <?php $admin_active='ai'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>AI Control Center</h1><p>Manage AI assistant settings</p></div>
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
                <div class="form-group"><label>Enable AI</label><p>Configure AI in <code>src/config/ai_config.php</code></p></div>
                <div class="form-group"><label>Features</label><p>Answer questions, suggest lessons, translate to Hausa, summarize discussions, flag inappropriate content</p></div>
            </div>
        </div>
    </div>
</div>
<script src="/lms_vareen/public/js/auth.js"></script>
<script>
(function(){var s=document.getElementById('adminSidebar'),t=document.getElementById('sidebarToggle'),c=document.getElementById('sidebarClose');if(t&&s)t.addEventListener('click',function(){s.classList.add('active')});if(c&&s)c.addEventListener('click',function(){s.classList.remove('active')});})();
</script>