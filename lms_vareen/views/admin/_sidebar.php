<?php
/**
 * Shared admin sidebar partial.
 * Set $admin_active ('dashboard'|'users'|'courses'|'applications'|'certificates'|'reports'|'settings')
 * before including this file.
 */
$admin_items = [
    'dashboard'    => ['fa-tachometer-alt',    'Dashboard',                '/index.php?page=admin-dashboard'],
    'users'        => ['fa-users-cog',         'Manage Users',             '/index.php?page=admin-users'],
    'courses'      => ['fa-book',              'Manage Courses',           '/index.php?page=admin-courses'],
    'applications' => ['fa-chalkboard-teacher', 'Instructor Applications', '/index.php?page=admin-applications'],
    'payments'     => ['fa-credit-card',        'Payments',                  '/index.php?page=admin-payments'],
    'certificates' => ['fa-certificate',       'Certificates',             '/index.php?page=admin-certificates'],
    'reports'      => ['fa-chart-bar',         'Reports',                  '/index.php?page=admin-reports'],
    'settings'     => ['fa-cog',               'Settings',                 '/index.php?page=admin-settings'],
];
$admin_active = $admin_active ?? 'dashboard';
?>
<aside class="dashboard-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h3>Admin Panel</h3>
        <button class="sidebar-close" id="sidebarClose" type="button"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-menu">
        <ul>
            <?php foreach ($admin_items as $key => $item): ?>
                <li><a href="<?php echo $item[2]; ?>"<?php echo $key === $admin_active ? ' class="active"' : ''; ?>>
                    <i class="fas <?php echo $item[0]; ?>"></i> <?php echo $item[1]; ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
