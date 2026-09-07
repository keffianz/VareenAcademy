<?php
/**
 * Premium Teacher Sidebar — grouped navigation.
 * Set $teacher_active before including this file.
 */
$teacher_groups = [
    'main' => [
        'label' => 'MAIN',
        'items' => [
            'dashboard' => ['fa-tachometer-alt', 'Dashboard', '/index.php?page=teacher-dashboard'],
        ],
    ],
    'teaching' => [
        'label' => 'TEACHING',
        'items' => [
            'courses'     => ['fa-book',       'My Courses',    '/index.php?page=teacher-courses'],
            'lessons'     => ['fa-list-ul',    'Lessons',       '/index.php?page=teacher-lessons'],
            'resources'   => ['fa-folder-open','Resources',     '/index.php?page=teacher-resources'],
            'assignments' => ['fa-tasks',      'Assignments',   '/index.php?page=teacher-assignments'],
            'quizzes'     => ['fa-question-circle', 'Quizzes',  '/index.php?page=teacher-quizzes'],
        ],
    ],
    'live' => [
        'label' => 'LIVE',
        'items' => [
            'live'        => ['fa-video',      'Live Classes',  '/index.php?page=teacher-live-classes'],
            'attendance'  => ['fa-user-check',  'Attendance',    '/index.php?page=teacher-attendance'],
        ],
    ],
    'community' => [
        'label' => 'COMMUNITY',
        'items' => [
            'community'   => ['fa-comments',   'Community Hub', '/index.php?page=teacher-community'],
            'discussions' => ['fa-comment-dots','Discussions',   '/index.php?page=teacher-discussions'],
            'showcase'    => ['fa-star',       'Student Showcase', '/index.php?page=teacher-showcase'],
        ],
    ],
    'students' => [
        'label' => 'STUDENTS',
        'items' => [
            'mystudents'  => ['fa-users',      'My Students',   '/index.php?page=teacher-students'],
            'progress'    => ['fa-chart-line', 'Progress',      '/index.php?page=teacher-progress'],
            'grades'      => ['fa-award',      'Grades',        '/index.php?page=teacher-grades'],
        ],
    ],
    'ai' => [
        'label' => 'AI',
        'items' => [
            'ai'          => ['fa-robot',      'AI Teaching Assistant', '/index.php?page=teacher-ai'],
        ],
    ],
    'insights' => [
        'label' => 'INSIGHTS',
        'items' => [
            'analytics'   => ['fa-chart-pie',  'Analytics',     '/index.php?page=teacher-analytics'],
            'calendar'    => ['fa-calendar',   'Calendar',      '/index.php?page=teacher-calendar'],
        ],
    ],
    'account' => [
        'label' => 'ACCOUNT',
        'items' => [
            'profile'     => ['fa-user-circle','Profile',       '/index.php?page=teacher-profile'],
            'settings'    => ['fa-cog',        'Settings',      '/index.php?page=teacher-settings'],
        ],
    ],
];
$teacher_active = $teacher_active ?? 'dashboard';
?>
<aside class="dashboard-sidebar" id="teacherSidebar" role="navigation" aria-label="Teacher navigation">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Teacher Portal</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close menu"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-menu">
        <?php foreach ($teacher_groups as $group): ?>
            <div class="sidebar-group">
                <p class="sidebar-group-label"><?php echo $group['label']; ?></p>
                <ul>
                    <?php foreach ($group['items'] as $key => $item): ?>
                        <li>
                            <a href="<?php echo $item[2]; ?>"<?php echo $key === $teacher_active ? ' class="active" aria-current="page"' : ''; ?>>
                                <i class="fas <?php echo $item[0]; ?>"></i>
                                <span><?php echo $item[1]; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>