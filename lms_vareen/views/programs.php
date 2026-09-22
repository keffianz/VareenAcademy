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
<!-- On-Campus Programs -->
<?php if (!empty($onCampus)): ?>
<div class="program-group">
<h2 class="program-group-title"><i class="fas fa-building"></i> On-Campus Programs <span class="program-count"><?php echo count($onCampus); ?> courses</span></h2>
<div class="programs-grid">
<?php foreach ($onCampus as $c): ?>
<?php
$price = (float)($c['price'] ?? 0);
$cur = strtoupper(trim((string)($c['currency'] ?? 'NGN')));
$sym = ($cur === 'NGN' ? 'N' : $cur . ' ');
$fmt = $price > 0 ? $sym . number_format($price) : 'Free';
$ml = [
    'on_campus' => '<span class="mode-badge on-campus">On-Campus</span>',
    'online'   => '<span class="mode-badge online">Online</span>',
    'hybrid'   => '<span class="mode-badge hybrid">Hybrid</span>',
];
?>
<div class="program-card">
<div class="program-card-thumb">
<?php if (!empty($c['thumbnail'])): ?><img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="<?php echo htmlspecialchars($c['title']); ?>"><?php else: ?><div class="program-card-thumb-placeholder"><i class="fas fa-book-open"></i></div><?php endif; ?>
<?php if (!empty($c['delivery_mode']) && isset($ml[$c['delivery_mode']])): ?><span class="program-card-mode-badge"><?php echo $ml[$c['delivery_mode']]; ?></span><?php endif; ?>
</div>
<div class="program-card-body">
<h3><a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></a></h3>
<p class="program-card-desc"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 120)) . (strlen($c['description'] ?? '') > 120 ? '...' : ''); ?></p>
<div class="program-card-meta">
<span class="program-card-instructor"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($c['instructor'] ?? 'Unassigned'); ?></span>
<?php if (!empty($c['duration_weeks'])): ?><span class="program-card-duration"><i class="fas fa-calendar-alt"></i> <?php echo (int)$c['duration_weeks']; ?> weeks</span><?php endif; ?>
<?php if (!empty($c['level'])): ?><span class="program-card-level"><?php echo htmlspecialchars(ucfirst($c['level'])); ?></span><?php endif; ?>
</div>
<div class="program-card-footer">
<span class="program-card-price"><?php echo $fmt; ?></span>
<a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-small">View Program</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<!-- Online Programs -->
<?php if (!empty($online)): ?>
<div class="program-group">
<h2 class="program-group-title"><i class="fas fa-laptop"></i> Online Programs <span class="program-count"><?php echo count($online); ?> courses</span></h2>
<div class="programs-grid">
<?php foreach ($online as $c): ?>
<?php
$price = (float)($c['price'] ?? 0);
$cur = strtoupper(trim((string)($c['currency'] ?? 'NGN')));
$sym = ($cur === 'NGN' ? 'N' : $cur . ' ');
$fmt = $price > 0 ? $sym . number_format($price) : 'Free';
$ml = [
    'on_campus' => '<span class="mode-badge on-campus">On-Campus</span>',
    'online'   => '<span class="mode-badge online">Online</span>',
    'hybrid'   => '<span class="mode-badge hybrid">Hybrid</span>',
];
?>
<div class="program-card">
<div class="program-card-thumb">
<?php if (!empty($c['thumbnail'])): ?><img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="<?php echo htmlspecialchars($c['title']); ?>"><?php else: ?><div class="program-card-thumb-placeholder"><i class="fas fa-book-open"></i></div><?php endif; ?>
<?php if (!empty($c['delivery_mode']) && isset($ml[$c['delivery_mode']])): ?><span class="program-card-mode-badge"><?php echo $ml[$c['delivery_mode']]; ?></span><?php endif; ?>
</div>
<div class="program-card-body">
<h3><a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></a></h3>
<p class="program-card-desc"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 120)) . (strlen($c['description'] ?? '') > 120 ? '...' : ''); ?></p>
<div class="program-card-meta">
<span class="program-card-instructor"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($c['instructor'] ?? 'Unassigned'); ?></span>
<?php if (!empty($c['duration_weeks'])): ?><span class="program-card-duration"><i class="fas fa-calendar-alt"></i> <?php echo (int)$c['duration_weeks']; ?> weeks</span><?php endif; ?>
<?php if (!empty($c['level'])): ?><span class="program-card-level"><?php echo htmlspecialchars(ucfirst($c['level'])); ?></span><?php endif; ?>
</div>
<div class="program-card-footer">
<span class="program-card-price"><?php echo $fmt; ?></span>
<a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-small">View Program</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>


<!-- Hybrid Programs -->
<?php if (!empty($hybrid)): ?>
<div class="program-group">
<h2 class="program-group-title"><i class="fas fa-handshake"></i> Hybrid Programs <span class="program-count"><?php echo count($hybrid); ?> courses</span></h2>
<div class="programs-grid">
<?php foreach ($hybrid as $c): ?>
<?php
$price = (float)($c['price'] ?? 0);
$cur = strtoupper(trim((string)($c['currency'] ?? 'NGN')));
$sym = ($cur === 'NGN' ? 'N' : $cur . ' ');
$fmt = $price > 0 ? $sym . number_format($price) : 'Free';
$ml = [
    'on_campus' => '<span class="mode-badge on-campus">On-Campus</span>',
    'online'   => '<span class="mode-badge online">Online</span>',
    'hybrid'   => '<span class="mode-badge hybrid">Hybrid</span>',
];
?>
<div class="program-card">
<div class="program-card-thumb">
<?php if (!empty($c['thumbnail'])): ?><img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="<?php echo htmlspecialchars($c['title']); ?>"><?php else: ?><div class="program-card-thumb-placeholder"><i class="fas fa-book-open"></i></div><?php endif; ?>
<?php if (!empty($c['delivery_mode']) && isset($ml[$c['delivery_mode']])): ?><span class="program-card-mode-badge"><?php echo $ml[$c['delivery_mode']]; ?></span><?php endif; ?>
</div>
<div class="program-card-body">
<h3><a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></a></h3>
<p class="program-card-desc"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 120)) . (strlen($c['description'] ?? '') > 120 ? '...' : ''); ?></p>
<div class="program-card-meta">
<span class="program-card-instructor"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($c['instructor'] ?? 'Unassigned'); ?></span>
<?php if (!empty($c['duration_weeks'])): ?><span class="program-card-duration"><i class="fas fa-calendar-alt"></i> <?php echo (int)$c['duration_weeks']; ?> weeks</span><?php endif; ?>
<?php if (!empty($c['level'])): ?><span class="program-card-level"><?php echo htmlspecialchars(ucfirst($c['level'])); ?></span><?php endif; ?>
</div>
<div class="program-card-footer">
<span class="program-card-price"><?php echo $fmt; ?></span>
<a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-small">View Program</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php if (empty($allCourses)): ?>
<div class="empty-state">
  <i class="fas fa-book-open"></i>
  <h2>No Programs Available</h2>
  <p>We're currently setting up our courses. Check back soon!</p>
  <a href="<?php echo appBasePath(); ?>/index.php" class="btn btn-primary">Back to Home</a>
</div>
<?php endif; ?>
</div>
</div>

<style>
    .programs-page-wrapper{padding:60px 0 80px}
    .programs-header{text-align:center;margin-bottom:50px}
    .programs-header h1{font-size:40px;color:#333;margin-bottom:12px}
    .programs-header p{color:#6c757d;font-size:18px;margin-bottom:30px;max-width:600px;margin-left:auto;margin-right:auto}
    .programs-tabs{display:flex;justify-content:center;gap:8px;margin-top:10px}
    .tab-btn{padding:10px 24px;border:2px solid #667eea;background:transparent;color:#667eea;border-radius:25px;font-weight:600;cursor:pointer;transition:all 0.3s;font-size:14px}
    .tab-btn:hover,.tab-btn.active{background:#667eea;color:#fff}
    .program-group{margin-bottom:50px}
    .program-group-title{font-size:24px;color:#333;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #e9ecef;display:flex;align-items:center;gap:10px}
    .program-group-title i{color:#667eea}
    .program-count{font-size:14px;color:#868e96;font-weight:400;margin-left:auto}
    .programs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
    .program-card{background:#fff;border:1px solid #e9ecef;border-radius:12px;overflow:hidden;transition:box-shadow 0.3s,transform 0.3s;display:flex;flex-direction:column}
    .program-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.12);transform:translateY(-4px)}
    .program-card-thumb{position:relative;height:170px;background:#f8f9fa;overflow:hidden}
    .program-card-thumb img{width:100%;height:100%;object-fit:cover}
    .program-card-thumb-placeholder{display:flex;align-items:center;justify-content:center;height:100%;color:#ced4da;font-size:40px}
    .program-card-mode-badge{position:absolute;top:10px;right:10px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;text-transform:uppercase;letter-spacing:0.5px}
    .mode-badge.on-campus{background:#667eea}
    .mode-badge.online{background:#28a745}
    .mode-badge.hybrid{background:#fd7e14}
    .program-card-body{padding:16px 20px 20px;flex:1;display:flex;flex-direction:column}
    .program-card-body h3{margin:0 0 8px;font-size:16px;line-height:1.3}
    .program-card-body h3 a{color:#333;text-decoration:none}
    .program-card-body h3 a:hover{color:#667eea}
    .program-card-desc{color:#6c757d;font-size:13px;line-height:1.5;margin:0 0 12px;flex:1}
    .program-card-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:#868e96;margin-bottom:12px}
    .program-card-meta span{display:flex;align-items:center;gap:4px}
    .program-card-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f3f5;margin-top:auto}
    .program-card-price{font-size:18px;font-weight:700;color:#667eea}
    .empty-state{text-align:center;padding:80px 20px}
    .empty-state i{font-size:60px;color:#ccc;margin-bottom:20px}
    .empty-state h2{color:#333;margin-bottom:10px}
    .empty-state p{color:#868e96;margin-bottom:24px}
    @media(max-width:768px){.programs-page-wrapper{padding:40px 0 60px}.programs-header h1{font-size:28px}.programs-grid{grid-template-columns:1fr}.programs-tabs{flex-wrap:wrap}}
</style>
