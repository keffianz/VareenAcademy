<?php
/**
 * Premium Student Sidebar — grouped navigation.
 * Set $student_active before including this file.
 */
$student_groups = [
    'main' => [
        'label' => 'MAIN',
        'items' => [
            'student-dashboard' => ['fa-home', 'Dashboard', '/index.php?page=student-dashboard'],
        ],
    ],
    'learning' => [
        'label' => 'LEARNING',
        'items' => [
            'courses' => ['fa-book', 'Browse Courses', '/index.php?page=courses'],
            'lessons' => ['fa-graduation-cap', 'My Lessons', '/index.php?page=lessons'],
            'quizzes' => ['fa-list-check', 'Quizzes', '/index.php?page=quizzes'],
            'assignments' => ['fa-tasks', 'Assignments', '/index.php?page=assignments'],
        ],
    ],
    'live' => [
        'label' => 'LIVE',
        'items' => [
            'live-classes' => ['fa-video', 'Live Classes', '/index.php?page=live-classes'],
        ],
    ],
    'community' => [
        'label' => 'COMMUNITY',
        'items' => [
            'student-community' => ['fa-comments', 'Community Hub', '/index.php?page=student-community'],
            'student-showcase' => ['fa-star', 'Student Showcase', '/index.php?page=student-showcase'],
        ],
    ],
    'ai' => [
        'label' => 'AI',
        'items' => [
            'student-ai' => ['fa-robot', 'AI Assistant', '/index.php?page=student-ai'],
        ],
    ],
    'account' => [
        'label' => 'ACCOUNT',
        'items' => [
            'certificates' => ['fa-certificate', 'Certificates', '/index.php?page=certificates'],
            'my-payments' => ['fa-credit-card', 'My Payments', '/index.php?page=my-payments'],
            'profile' => ['fa-user', 'Profile', '/index.php?page=profile'],
        ],
    ],
];
$student_active = $student_active ?? 'student-dashboard';
?>
<aside class="dashboard-sidebar" id="studentSidebar" role="navigation" aria-label="Student navigation">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>VAREEN</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close menu"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-menu">
        <?php foreach ($student_groups as $group): ?>
            <div class="sidebar-group">
                <p class="sidebar-group-label"><?php echo $group['label']; ?></p>
                <ul>
                    <?php foreach ($group['items'] as $key => $item): ?>
                        <li>
                            <a href="<?php echo $item[2]; ?>"<?php echo $key === $student_active ? ' class="active" aria-current="page"' : ''; ?>>
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
        <button class="sidebar-logout" id="studentLogoutBtn" type="button">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>