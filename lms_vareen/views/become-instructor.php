<?php
// Public "Become an Instructor" application page (no login required).
// On submit, stores an instructor_applications row for admin review.
$notice = '';
$noticeType = '';
$form = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'specialization' => '', 'experience_years' => '0', 'bio' => '', 'portfolio_url' => '', 'sample_lesson_url' => '', 'additional_info' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['first_name']        = trim($_POST['first_name'] ?? '');
    $form['last_name']         = trim($_POST['last_name'] ?? '');
    $form['email']             = strtolower(trim($_POST['email'] ?? ''));
    $form['phone']             = trim($_POST['phone'] ?? '');
    $form['specialization']    = trim($_POST['specialization'] ?? '');
    $form['experience_years']  = (int)($_POST['experience_years'] ?? 0);
    $form['bio']               = trim($_POST['bio'] ?? '');
    $form['portfolio_url']     = trim($_POST['portfolio_url'] ?? '');
    $form['sample_lesson_url'] = trim($_POST['sample_lesson_url'] ?? '');
    $form['additional_info']   = trim($_POST['additional_info'] ?? '');

    $errors = [];
    if ($form['first_name'] === '') $errors[] = 'First name is required.';
    if ($form['last_name'] === '')  $errors[] = 'Last name is required.';
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($form['specialization'] === '') $errors[] = 'Please tell us your area of specialization.';

    if (empty($errors)) {
        try {
            require_once __DIR__ . '/../src/classes/Database.php';
            $pdo = (new Database())->connect();
            $stmt = $pdo->prepare(
                'INSERT INTO instructor_applications
                    (first_name, last_name, email, phone, specialization, experience_years, bio, portfolio_url, sample_lesson_url, additional_info)
                 VALUES (:fn, :ln, :em, :ph, :spec, :exp, :bio, :port, :sample, :info)'
            );
            $stmt->execute([
                ':fn' => $form['first_name'], ':ln' => $form['last_name'], ':em' => $form['email'],
                ':ph' => $form['phone'], ':spec' => $form['specialization'], ':exp' => $form['experience_years'],
                ':bio' => $form['bio'], ':port' => $form['portfolio_url'], ':sample' => $form['sample_lesson_url'],
                ':info' => $form['additional_info'],
            ]);
            $notice = 'Thank you! Your application has been submitted and will be reviewed by our team.';
            $noticeType = 'success';
            $form = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'specialization' => '', 'experience_years' => '0', 'bio' => '', 'portfolio_url' => '', 'sample_lesson_url' => '', 'additional_info' => ''];
        } catch (PDOException $e) {
            $notice = 'Sorry, we could not submit your application right now. Please try again later.';
            $noticeType = 'error';
        }
    } else {
        $notice = implode(' ', $errors);
        $noticeType = 'error';
    }
}
?>
<div class="become-page">
    <header class="public-header">
        <a class="brand" href="index.php?page=login">VAREEN Academy</a>
        <nav class="public-nav">
            <a href="index.php?page=instructors">Instructors</a>
            <a href="index.php?page=verify">Verify Certificate</a>
            <a href="index.php?page=login" class="btn btn-small">Sign in</a>
        </nav>
    </header>
    <main class="become-main">
        <h1>Become an Instructor</h1>
        <p class="page-intro">Share your expertise with students across Nigeria. Fill in the form below and our team will review your application within a few business days.</p>
        <?php if ($notice !== ''): ?>
            <div class="form-notice <?php echo $noticeType; ?>"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form id="instructorForm" class="become-form" method="POST" action="">
            <div class="form-row">
                <label for="first_name">First name *</label>
                <input type="text" id="first_name" name="first_name" required maxlength="100" value="<?php echo htmlspecialchars($form['first_name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="last_name">Last name *</label>
                <input type="text" id="last_name" name="last_name" required maxlength="100" value="<?php echo htmlspecialchars($form['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required maxlength="255" value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" maxlength="30" value="<?php echo htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="specialization">Area of specialization *</label>
                <input type="text" id="specialization" name="specialization" required maxlength="255" placeholder="e.g. Web Development, Data Science" value="<?php echo htmlspecialchars($form['specialization'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="experience_years">Years of experience</label>
                <input type="number" id="experience_years" name="experience_years" min="0" max="60" value="<?php echo htmlspecialchars($form['experience_years'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="bio">Short bio</label>
                <textarea id="bio" name="bio" rows="4" maxlength="2000" placeholder="Tell us about your background and teaching experience."><?php echo htmlspecialchars($form['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="form-row">
                <label for="portfolio_url">Portfolio / LinkedIn URL</label>
                <input type="url" id="portfolio_url" name="portfolio_url" maxlength="500" value="<?php echo htmlspecialchars($form['portfolio_url'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="sample_lesson_url">Sample lesson URL (optional)</label>
                <input type="url" id="sample_lesson_url" name="sample_lesson_url" maxlength="500" value="<?php echo htmlspecialchars($form['sample_lesson_url'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="additional_info">Anything else we should know?</label>
                <textarea id="additional_info" name="additional_info" rows="3" maxlength="2000"><?php echo htmlspecialchars($form['additional_info'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
<style>
    .become-page{min-height:100vh;background:#f4f6fb;font-family:'Segoe UI',system-ui,sans-serif}
    .public-header{display:flex;justify-content:space-between;align-items:center;padding:14px 24px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08);flex-wrap:wrap;gap:10px}
    .brand{font-weight:700;color:#667eea;text-decoration:none;font-size:20px}
    .public-nav{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
    .public-nav a{color:#444;text-decoration:none;font-size:14px}
    .public-nav a:hover{color:#667eea}
    .btn-small{padding:7px 14px;border-radius:6px;background:#667eea;color:#fff!important}
    .become-main{max-width:640px;margin:0 auto;padding:40px 20px 60px}
    .become-main h1{color:#333;margin:0 0 8px;font-size:28px}
    .page-intro{color:#666;margin:0 0 28px}
    .form-notice{padding:14px 16px;border-radius:8px;margin-bottom:20px;font-size:14px}
    .form-notice.success{background:#e6f6ec;color:#2e9e5b;border-left:4px solid #2e9e5b}
    .form-notice.error{background:#fdeaea;color:#c9302c;border-left:4px solid #d9534f}
    .become-form{background:#fff;border-radius:12px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
    .form-row{margin-bottom:16px}
    .form-row label{display:block;font-size:13px;font-weight:600;color:#333;margin-bottom:6px}
    .form-row input,.form-row textarea{width:100%;padding:11px 13px;border:1px solid #d5daea;border-radius:8px;font-size:14px;font-family:inherit;box-sizing:border-box}
    .form-row input:focus,.form-row textarea:focus{outline:none;border-color:#667eea}
    .btn{cursor:pointer;border:none;border-radius:8px;font-size:15px}
    .btn-primary{background:#667eea;color:#fff}
    .btn-primary:hover{background:#5568d3}
    .btn-block{width:100%;padding:13px;font-weight:600}
    @media(max-width:480px){.become-main{padding:24px 14px 40px}.become-form{padding:20px}}
</style>

            <button type="submit" class="btn btn-primary btn-block">Submit Application</button>
        </form>
    </main>
</div>
