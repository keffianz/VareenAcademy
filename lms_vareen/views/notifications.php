<?php
// Require login
requireLogin();

require_once 'src/classes/Notification.php';

$notification = new Notification();
$user_id = getCurrentUserId();
$page = $_GET['pg'] ?? 1;
$limit = 10;

// Get all notifications with pagination
$notifications = $notification->getAll($user_id, $page, $limit);
?>

<div class="notifications-container">
    <div class="container">
        <div class="notifications-header">
            <h1>Notifications</h1>
            <?php if (!empty($notifications)): ?>
                <button class="btn btn-small btn-outline-primary" id="markAllRead">
                    Mark All as Read
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell"></i>
                <h2>No Notifications</h2>
                <p>You're all caught up! Check back later for updates.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-card <?php echo ($notif['is_read']) ? 'read' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>">
                        <div class="notification-icon">
                            <?php
                            $icon = 'fa-info-circle';
                            switch ($notif['type']) {
                                case 'assignment':
                                    $icon = 'fa-tasks';
                                    break;
                                case 'class':
                                    $icon = 'fa-video';
                                    break;
                                case 'quiz':
                                    $icon = 'fa-list-check';
                                    break;
                                case 'announcement':
                                    $icon = 'fa-bullhorn';
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>

                        <div class="notification-body">
                            <h3><?php echo htmlspecialchars($notif['title']); ?></h3>
                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small class="notification-time">
                                <?php echo timeAgo($notif['created_at']); ?>
                            </small>
                        </div>

                        <?php if (!$notif['is_read']): ?>
                            <div class="notification-actions">
                                <button class="btn-mark-read" onclick="markNotificationRead(<?php echo $notif['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/index.php?page=notifications&pg=<?php echo $page - 1; ?>" class="btn btn-small">
                        ← Previous
                    </a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $page; ?></span>

                <?php if (count($notifications) >= $limit): ?>
                    <a href="/index.php?page=notifications&pg=<?php echo $page + 1; ?>" class="btn btn-small">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .notifications-container {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: calc(100vh - 100px);
    }

    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: var(--box-shadow);
    }

    .notifications-header h1 {
        margin: 0;
        font-size: 28px;
        color: #333;
    }

    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .notification-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        gap: 15px;
        align-items: flex-start;
        box-shadow: var(--box-shadow);
        border-left: 4px solid #ddd;
        transition: all 0.3s;
    }

    .notification-card.unread {
        background: #f0f4ff;
        border-left-color: var(--primary-color);
    }

    .notification-card:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .notification-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
    }

    .notification-body {
        flex: 1;
    }

    .notification-card h3 {
        margin: 0 0 8px;
        font-size: 16px;
        color: #333;
    }

    .notification-card p {
        margin: 0 0 8px;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }

    .notification-time {
        color: #999;
        font-size: 12px;
    }

    .notification-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn-mark-read {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--primary-color);
        font-size: 16px;
        transition: all 0.3s;
        padding: 5px 10px;
    }

    .btn-mark-read:hover {
        color: var(--secondary-color);
        transform: scale(1.1);
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: var(--box-shadow);
    }

    .empty-state i {
        font-size: 64px;
        color: #ddd;
        margin-bottom: 20px;
        display: block;
    }

    .empty-state h2 {
        margin: 0 0 10px;
        color: #333;
    }

    .empty-state p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 30px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: var(--box-shadow);
    }

    .page-info {
        color: #666;
        font-size: 14px;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .notifications-container {
            padding: 20px 0;
        }

        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .notifications-header h1 {
            font-size: 24px;
        }

        .notification-card {
            gap: 12px;
            padding: 15px;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .notification-body h3 {
            font-size: 14px;
        }

        .notification-body p {
            font-size: 13px;
        }

        .pagination {
            flex-wrap: wrap;
            gap: 10px;
        }
    }

    @media (max-width: 480px) {
        .notifications-header {
            padding: 15px;
        }

        .notification-card {
            padding: 12px;
        }

        .notification-body h3 {
            font-size: 13px;
        }
    }
</style>

<script>
function timeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diff = now - date;
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return minutes + 'm ago';
    if (hours < 24) return hours + 'h ago';
    if (days < 7) return days + 'd ago';
    
    return date.toLocaleDateString();
}

function markNotificationRead(notificationId) {
    fetch('<?php echo appBasePath(); ?>/src/api/dashboard.php?action=mark_notification_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`[data-id="${notificationId}"]`);
            if (card) {
                card.classList.remove('unread');
                card.classList.add('read');
                card.querySelector('.notification-actions').remove();
            }
        }
    });
}

document.getElementById('markAllRead')?.addEventListener('click', () => {
    fetch('<?php echo appBasePath(); ?>/src/api/dashboard.php?action=mark_all_notifications_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-card').forEach(card => {
                card.classList.remove('unread');
                card.classList.add('read');
                const actions = card.querySelector('.notification-actions');
                if (actions) actions.remove();
            });
            location.reload();
        }
    });
});
</script>
