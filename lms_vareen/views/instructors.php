<?php
// Public "Meet Our Instructors" directory (no login required).
$apiBase = appBasePath() . '/src/api/public.php';
?>
<div class="instructors-page">
    <header class="public-header">
        <a class="brand" href="index.php?page=login">VEREEN Academy</a>
        <nav class="public-nav">
            <a href="index.php?page=verify">Verify Certificate</a>
            <a href="index.php?page=become-instructor">Teach with us</a>
            <a href="index.php?page=login" class="btn btn-small">Sign in</a>
        </nav>
    </header>

    <main class="instructors-main">
        <h1>Meet Our Instructors</h1>
        <p class="page-intro">Learn from experienced practitioners. Every VEREEN Academy instructor is reviewed by our team before their first course goes live.</p>
        <div id="instructorGrid" class="instructor-grid" aria-live="polite">
            <p class="loading">Loading instructors…</p>
        </div>
    </main>
</div>

<script>
(function () {
    'use strict';
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function initials(name) {
        return name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0].toUpperCase(); }).join('');
    }
    fetch('<?php echo $apiBase; ?>?action=instructors')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var grid = document.getElementById('instructorGrid');
            var list = (data && data.instructors) || [];
            if (!list.length) {
                grid.innerHTML = '<p class="loading">Our instructor profiles are being prepared. Check back soon.</p>';
                return;
            }
            grid.innerHTML = list.map(function (t) {
                var name = (t.first_name || '') + ' ' + (t.last_name || '');
                var courses = (t.courses || []).map(function (c) {
                    return '<span class="course-chip">' + esc(c.title) + '</span>';
                }).join('');
                var photo = t.profile_image
                    ? '<img src="' + esc(t.profile_image) + '" alt="Photo of ' + esc(name) + '">'
                    : '<span class="avatar-fallback">' + esc(initials(name || '?')) + '</span>';
                return '<article class="instructor-card">'
                    + '<div class="avatar">' + photo + '</div>'
                    + '<h2>' + esc(name) + '</h2>'
                    + '<p class="position">' + esc(t.specialization || 'Instructor') + '</p>'
                    + (t.bio ? '<p class="bio">' + esc(t.bio) + '</p>' : '')
                    + '<p class="count">' + (t.course_count || 0) + ' course' + ((t.course_count || 0) === 1 ? '' : 's') + '</p>'
                    + (courses ? '<div class="chips">' + courses + '</div>' : '')
                    + '</article>';
            }).join('');
        })
        .catch(function () {
            document.getElementById('instructorGrid').innerHTML =
                '<p class="loading">Could not load instructors. Please try again later.</p>';
        });
})();
</script>

<style>
    .instructors-page { min-height: 100vh; background: #f4f6fb; font-family: 'Segoe UI', system-ui, sans-serif; }
    .public-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); flex-wrap: wrap; gap: 10px; }
    .brand { font-weight: 700; color: #667eea; text-decoration: none; font-size: 20px; }
    .public-nav { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .public-nav a { color: #444; text-decoration: none; font-size: 14px; }
    .public-nav a:hover { color: #667eea; }
    .btn-small { padding: 7px 14px; border-radius: 6px; background: #667eea; color: #fff !important; }
    .instructors-main { max-width: 1080px; margin: 0 auto; padding: 40px 20px 60px; }
    .instructors-main h1 { color: #333; margin: 0 0 8px; font-size: 28px; }
    .page-intro { color: #666; margin: 0 0 28px; max-width: 640px; }
    .loading { color: #888; }
    .instructor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .instructor-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.06); display: flex; flex-direction: column; }
    .avatar { width: 72px; height: 72px; border-radius: 50%; overflow: hidden; background: #eef1ff; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-fallback { color: #667eea; font-weight: 700; font-size: 24px; }
    .instructor-card h2 { margin: 0 0 4px; font-size: 18px; color: #333; }
    .position { color: #667eea; font-weight: 600; font-size: 13px; margin: 0 0 10px; }
    .bio { color: #666; font-size: 14px; line-height: 1.5; margin: 0 0 10px; }
    .count { color: #999; font-size: 12px; font-weight: 600; text-transform: uppercase; margin: 0 0 10px; }
    .chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .course-chip { background: #f2f4fd; color: #4a58b0; border-radius: 12px; padding: 3px 10px; font-size: 12px; }
    @media (max-width: 480px) {
        .instructors-main { padding: 24px 14px 40px; }
        .instructor-grid { grid-template-columns: 1fr; }
    }
</style>
