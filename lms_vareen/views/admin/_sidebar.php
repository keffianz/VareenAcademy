<?php
/**
 * Premium Admin Sidebar — grouped navigation.
 * Set $admin_active before including this file.
 */
$admin_groups = [
    'main' => [
        'label' => 'MAIN',
        'items' => [
            'dashboard' => ['fa-tachometer-alt', 'Dashboard', '/index.php?page=admin-dashboard'],
        ],
    ],
    'users' => [
        'label' => 'USERS',
        'items' => [
            'users'        => ['fa-users-cog',          'Students',               '/index.php?page=admin-users'],
            'teachers'     => ['fa-chalkboard-teacher', 'Teachers',               '/index.php?page=admin-teachers'],
            'applications' => ['fa-user-plus',          'Instructor Applications', '/index.php?page=admin-applications'],
        ],
    ],
    'academic' => [
        'label' => 'ACADEMIC',
        'items' => [
            'courses'  => ['fa-book',     'Courses',      '/index.php?page=admin-courses'],
            'lessons'  => ['fa-list-ul',  'Lessons',      '/index.php?page=admin-lessons'],
            'quizzes'  => ['fa-question-circle', 'Quizzes', '/index.php?page=admin-quizzes'],
            'live'     => ['fa-video',    'Live Classes', '/index.php?page=admin-live'],
        ],
    ],
    'community' => [
        'label' => 'COMMUNITY',
        'items' => [
            'community'    => ['fa-comments', 'Community Hub',  '/index.php?page=admin-community'],
            'discussions'  => ['fa-comment-dots', 'Discussions', '/index.php?page=admin-discussions'],
            'reports'      => ['fa-flag',    'Reports',         '/index.php?page=admin-reports'],
            'moderation'   => ['fa-shield-alt', 'AI Moderation', '/index.php?page=admin-moderation'],
        ],
    ],
    'finance' => [
        'label' => 'FINANCE',
        'items' => [
            'payments' => ['fa-credit-card', 'Payments', '/index.php?page=admin-payments'],
            'coupons'  => ['fa-ticket-alt',  'Coupons',  '/index.php?page=admin-coupons'],
        ],
    ],
    'certificates' => [
        'label' => 'CERTIFICATES',
        'items' => [
            'certificates' => ['fa-certificate', 'Certificates', '/index.php?page=admin-certificates'],
            'verify'       => ['fa-check-circle', 'Verification', '/index.php?page=admin-verify'],
        ],
    ],
    'insights' => [
        'label' => 'INSIGHTS',
        'items' => [
            'analytics'    => ['fa-chart-pie', 'Analytics',    '/index.php?page=admin-analytics'],
            'activity'     => ['fa-history',   'Activity Log', '/index.php?page=admin-activity'],
        ],
    ],
    'system' => [
        'label' => 'SYSTEM',
        'items' => [
            'ai'         => ['fa-robot',     'AI Control Center', '/index.php?page=admin-ai'],
            'notifications' => ['fa-bell',   'Notifications',     '/index.php?page=admin-notifications'],
            'settings'   => ['fa-cog',       'Settings',          '/index.php?page=admin-settings'],
        ],
    ],
];
$admin_active = $admin_active ?? 'dashboard';
?>
<aside class="dashboard-sidebar" id="adminSidebar" role="navigation" aria-label="Admin navigation">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>VAREEN Admin</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close menu"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-menu">
        <?php foreach ($admin_groups as $group): ?>
            <div class="sidebar-group">
                <p class="sidebar-group-label"><?php echo $group['label']; ?></p>
                <ul>
                    <?php foreach ($group['items'] as $key => $item): ?>
                        <li>
                            <a href="<?php echo $item[2]; ?>"<?php echo $key === $admin_active ? ' class="active" aria-current="page"' : ''; ?>>
                                <i class="fas <?php echo $item[0]; ?>"></i>
                                <span><?php echo $item[1]; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <button class="sidebar-logout" id="adminLogoutBtn" type="button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>
