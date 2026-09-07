<?php
/**
 * Teacher Dashboard Topbar + Sidebar wrapper.
 * Sets up the premium dashboard layout for teacher pages.
 * Usage: include this file after setting $teacher_active and any KPI/section content.
 */
?>
<div class="dashboard-wrapper">
    <?php include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title">
                <h1><?php echo htmlspecialchars($page_title ?? 'Teacher Dashboard'); ?></h1>
                <p><?php echo htmlspecialchars($page_subtitle ?? ''); ?></p>
            </div>
            <div class="topbar-actions">
                <a href="/index.php?page=teacher-ai" class="btn btn-ai" title="AI Assistant"><i class="fas fa-robot"></i></a>
                <a href="/index.php?page=teacher-profile" class="btn btn-icon"><i class="fas fa-user-circle"></i></a>
            </div>
        </div>
        <?php if (!empty($success_message)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if (!empty($error_message)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>