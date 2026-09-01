<?php
// Assignments page
requireRole('student');

require_once 'src/classes/Course.php';

$course = new Course();
$user_id = getCurrentUserId();

// Get enrolled courses and their assignments
?>

<div class="assignments-page">
    <div class="container">
        <h1><i class="fas fa-tasks"></i> My Assignments</h1>

        <p style="color: #666; margin-bottom: 30px;">
            View and submit your assignments here. Complete them before the deadline to stay on track!
        </p>

        <div class="alert alert-info">
            <strong>Coming Soon:</strong> Assignment submission system is being prepared. Check back soon!
        </div>

        <div class="empty-state">
            <i class="fas fa-tasks"></i>
            <h2>Assignments will appear here</h2>
            <p>Once you enroll in courses with assignments, they will be listed here.</p>
            <a href="/index.php?page=courses" class="btn btn-primary">Browse Courses</a>
        </div>
    </div>
</div>

<style>
    .assignments-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: calc(100vh - 100px);
    }

    .assignments-page h1 {
        margin-bottom: 20px;
        font-size: 32px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .assignments-page h1 i {
        color: var(--primary-color);
    }
</style>
