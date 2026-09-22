<?php
/**
 * Programs Page (VX-055)
 * Public listing of all courses grouped by delivery mode.
 */
require_once 'src/classes/Course.php';
$course = new Course();
$allCourses = $course->getAllCourses(1, 0);
$onCampus = [];
$online = [];
$hybrid = [];
foreach ($allCourses as $c) {
    $mode = $c['delivery_mode'] ?? 'online';
    if ($mode === 'on_campus') $onCampus[] = $c;
    elseif ($mode === 'hybrid') $hybrid[] = $c;
    else $online[] = $c;
}
?>
<div class="programs-page-wrapper">
<div class="container">
<div class="programs-header">
<h1>Our Programs</h1>
<p>Explore our range of professional courses — on-campus, online, or hybrid.</p>
<div class="programs-tabs">
<button class="tab-btn <?php echo empty($_GET['mode']) ? 'active' : ''; ?>" data-mode="">All</button>
<button class="tab-btn <?php echo ($_GET['mode'] ?? '') === 'on_campus' ? 'active' : ''; ?>" data-mode="on_campus">On-Campus</button>
<button class="tab-btn <?php echo ($_GET['mode'] ?? '') === 'online' ? 'active' : ''; ?>" data-mode="online">Online</button>
<button class="tab-btn <?php echo ($_GET['mode'] ?? '') === 'hybrid' ? 'active' : ''; ?>" data-mode="hybrid">Hybrid</button>
</div>
</div>
