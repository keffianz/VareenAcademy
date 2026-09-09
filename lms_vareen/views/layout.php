<?php
/**
 * Main Layout Template
 * Wraps all views with proper HTML structure
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>VAREEN Academy LMS</title>
    
    <!-- CSS Files (all local, versioned for cache busting) -->
    <link rel="preload" href="<?php echo appBasePath() . '/public/css/styles.css?v=1.0.1'; ?>" as="style">
    <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/styles.css?v=1.0.1'; ?>">
    <?php if (isset($_SESSION['role'])): ?>
    <!-- Dashboard shell (sidebar, topbar, KPI cards) — logged-in roles only.
         Loaded AFTER styles.css so dashboard rules win the cascade for shared
         class names (.btn, .alert), and BEFORE responsive.css so media-query
         overrides always take precedence. -->
    <link rel="preload" href="<?php echo appBasePath() . '/public/css/dashboard.css?v=1.0.1'; ?>" as="style">
    <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/dashboard.css?v=1.0.1'; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/responsive.css?v=1.0.1'; ?>">

    <!-- Font Awesome (self-hosted — sidebar/KPI/button icons across all dashboards) -->
    <link rel="preload" href="<?php echo appBasePath() . '/public/css/font-awesome-local.css'; ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/font-awesome-local.css'; ?>"></noscript>
    <!-- Fallback: if local font files are missing, load from CDN -->
    <script>
    (function(){
        var test=document.createElement('span');
        test.className='fa';
        test.style.cssText='position:absolute;visibility:hidden;font-family:\'Font Awesome 6 Free\'';
        test.innerHTML='&#xf005;'; /* star */
        document.body.appendChild(test);
        var w=test.offsetWidth;
        document.body.removeChild(test);
        if(!w||w<10){
            var link=document.createElement('link');
            link.rel='stylesheet';
            link.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
            link.crossOrigin='anonymous';
            link.referrerPolicy='no-referrer';
            document.head.appendChild(link);
        }
    })();
    </script>
    
    <!-- AI Assistant Widget CSS (only for students) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
        <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/ai-assistant.css?v=1.0.1'; ?>">
    <?php endif; ?>
    
    <!-- Additional CSS can be added here per page -->
    <?php if (!empty($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Page Content -->
    <div class="page-wrapper">
        <?php
        // The actual view content is output here
        // This is set by the router before including the layout
        if (isset($view_content)) {
            echo $view_content;
        }
        ?>
    </div>
    
    <!-- JavaScript Files -->
    <script src="<?php echo appBasePath() . '/public/js/main.js'; ?>"></script>
    
    <!-- AI Assistant Widget JS (only for students) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
        <script src="<?php echo appBasePath() . '/public/js/ai-assistant.js'; ?>"></script>
    <?php endif; ?>
    
    <!-- Admin shared JS (sidebar toggle + scrim, logout, KPIs) — all dashboard roles -->
    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','teacher','student'], true)): ?>
        <script src="<?php echo appBasePath() . '/public/js/admin.js'; ?>"></script>
    <?php endif; ?>
    
    <!-- Command Palette (admin & teacher roles only) -->
    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','teacher'], true)): ?>
        <script src="<?php echo appBasePath() . '/public/js/command-palette.js'; ?>"></script>
    <?php endif; ?>
    
    <!-- Additional JS can be added here per page -->
    <?php if (!empty($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
