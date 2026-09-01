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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>VEREEN Academy LMS</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/styles.css'; ?>">
    <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/responsive.css'; ?>">
    
    <!-- AI Assistant Widget CSS (only for students) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
        <link rel="stylesheet" href="<?php echo appBasePath() . '/public/css/ai-assistant.css'; ?>">
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
    
    <!-- Additional JS can be added here per page -->
    <?php if (!empty($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
