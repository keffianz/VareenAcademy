<?php
requireRoles(['teacher', 'admin']);
require_once 'src/classes/Database.php';

$userId = getCurrentUserId();
$db = (new Database())->connect();

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$msgClass = 'alert alert-success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        requireCsrf();
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        if ($firstName && $lastName) {
            $stmt = $db->prepare('UPDATE users SET first_name = :fn, last_name = :ln, specialization = :spec, bio = :bio WHERE id = :id');
            $stmt->execute([':fn' => $firstName, ':ln' => $lastName, ':spec' => $specialization, ':bio' => $bio, ':id' => $userId]);
            $message = 'Profile updated successfully.';
            $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    if ($_POST['action'] === 'change_password') {
        requireCsrf();
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match.';
            $msgClass = 'alert alert-error';
        } elseif (strlen($newPassword) < 8) {
            $message = 'New password must be at least 8 characters.';
            $msgClass = 'alert alert-error';
        } else {
            $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($oldPassword, $row['password'])) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE users SET password = :pw WHERE id = :id');
                $stmt->execute([':pw' => $newHash, ':id' => $userId]);
                $message = 'Password changed successfully.';
                $msgClass = 'alert alert-success';
            } else {
                $message = 'Current password is incorrect.';
                $msgClass = 'alert alert-error';
            }
        }
    }
}
?>

<div class="dashboard-wrapper">
    <?php $teacher_active='profile'; include __DIR__.'/_sidebar.php'; ?>
    <div class="dashboard-content">
        <div class="dashboard-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="topbar-title"><h1>Profile</h1><p>Manage your instructor profile</p></div>
            <button class="btn btn-logout" id="teacherLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if($message): ?><div class="<?php echo $msgClass; ?>" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="dashboard-section">
            <div class="section-header"><h2>Personal Information</h2></div>
            <p class="dash-sub" style="margin:0 0 16px;">Update your name, specialization and bio.</p>
            <form method="POST" class="form-grid" id="profForm">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required class="form-input"></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required class="form-input"></div>
                <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input" disabled></div>
                <div class="form-group"><label>Specialization</label><input type="text" name="specialization" value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>" class="form-input" placeholder="e.g. Web Development"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Bio</label><textarea name="bio" class="form-input" rows="4" placeholder="Tell students about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea></div>
                <div class="form-group"><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="dashboard-section">
            <div class="section-header"><h2>Change Password</h2></div>
            <p class="dash-sub" style="margin:0 0 16px;">Enter your current password to set a new one. Minimum 8 characters.</p>
            <div id="pwMsg" class="alert" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;display:none;"></div>
            <form method="POST" class="form-grid" id="pwForm">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                <div class="form-group"><label>Current Password</label><input type="password" name="old_password" id="pwOld" required class="form-input" autocomplete="current-password"></div>
                <div class="form-group"><label>New Password</label><input type="password" name="new_password" id="pwNew" required class="form-input" minlength="8" autocomplete="new-password"></div>
                <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" id="pwConfirm" required class="form-input" minlength="8" autocomplete="new-password"></div>
                <div class="form-group"><button type="submit" class="btn btn-primary" id="btnPwSave">Change Password</button></div>
            </form>
        </div>

        <!-- Profile Photo -->
        <div class="dashboard-section">
            <div class="section-header"><h2>Profile Photo</h2></div>
            <p class="dash-sub" style="margin:0 0 16px;">Upload a JPG, PNG or WEBP image (max 5 MB). Existing photo will be replaced.</p>
            <div id="avMsg" class="alert" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;display:none;"></div>
            <div class="card" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="flex-shrink:0;">
                    <?php
                    $avatar = $user['profile_image'] ?? '';
                    $avatarUrl = $avatar !== '' && file_exists(__DIR__ . '/../..' . $avatar)
                        ? appBasePath() . $avatar
                        : appBasePath() . '/public/images/default-avatar.png';
                    ?>
                    <img src="<?php echo $avatarUrl; ?>" alt="Profile photo" id="teacherAvatarPreview"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #ddd;background:#f5f5f5;">
                </div>
                <div style="flex:1;min-width:200px;">
                    <input type="file" id="teacherAvatarInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    <button class="btn btn-secondary" type="button" id="teacherAvatarBtn">Choose Photo</button>
                    <span class="muted" style="margin-left:8px;">&middot;</span>
                    <button class="btn btn-primary" type="button" id="teacherAvatarUploadBtn" disabled style="opacity:0.6;">Upload Photo</button>
                    <span id="teacherAvFileSize" class="muted" style="margin-left:8px;"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo appBasePath(); ?>/public/js/auth.js"></script>
<script>
(function () {
    var $ = function (s) { return document.querySelector(s); };
    var pwMsgEl = $('#pwMsg');
    var avMsgEl = $('#avMsg');

    function showMsg(el, text, isOk) {
        el.textContent = text;
        el.style.display = 'block';
        el.className = 'alert ' + (isOk ? 'alert-success' : 'alert-error');
    }

    // ---- AJAX password change (falls back to POST if Auth not loaded) ----
    var pwOld = $('#pwOld'), pwNew = $('#pwNew'), pwConfirm = $('#pwConfirm');
    var pwForm = document.querySelector('form[action="change_password"]');
    if (pwForm && Auth && Auth.changePassword) {
        pwForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var oldPw = pwOld.value;
            var newPw = pwNew.value;
            if (newPw !== pwConfirm.value) {
                showMsg(pwMsgEl, 'New passwords do not match.', false);
                return;
            }
            if (newPw.length < 8) {
                showMsg(pwMsgEl, 'New password must be at least 8 characters.', false);
                return;
            }
            Auth.changePassword(oldPw, newPw).then(function (data) {
                if (data.success) {
                    showMsg(pwMsgEl, data.message || 'Password changed successfully.', true);
                    pwOld.value = ''; pwNew.value = ''; pwConfirm.value = '';
                } else {
                    showMsg(pwMsgEl, data.message || 'Password change failed.', false);
                }
            }).catch(function () {
                showMsg(pwMsgEl, 'Network error — password not changed.', false);
            });
        });
    }

    // ---- Avatar upload ----
    var avatarInput = $('#teacherAvatarInput');
    var avatarBtn = $('#teacherAvatarBtn');
    var uploadBtn = $('#teacherAvatarUploadBtn');
    var avFileSize = $('#teacherAvFileSize');
    var selectedFile = null;

    avatarBtn.addEventListener('click', function () { avatarInput.click(); });

    avatarInput.addEventListener('change', function () {
        selectedFile = avatarInput.files[0] || null;
        if (selectedFile) {
            if (selectedFile.size > 5 * 1024 * 1024) {
                showMsg(avMsgEl, 'Image must be under 5 MB.', false);
                selectedFile = null; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
                avFileSize.textContent = '';
                return;
            }
            var okTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (okTypes.indexOf(selectedFile.type) === -1) {
                showMsg(avMsgEl, 'Please select a JPG, PNG or WEBP image.', false);
                selectedFile = null; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
                avFileSize.textContent = '';
                return;
            }
            avFileSize.textContent = (selectedFile.size / 1024).toFixed(1) + ' KB';
            uploadBtn.disabled = false; uploadBtn.style.opacity = '1';
            avMsgEl.style.display = 'none';
        } else {
            avFileSize.textContent = ''; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
        }
    });

    uploadBtn.addEventListener('click', function () {
        if (!selectedFile) return;
        var prevSrc = $('#teacherAvatarPreview').src;
        uploadBtn.disabled = true; uploadBtn.textContent = 'Uploading...';
        Auth.uploadAvatar(selectedFile).then(function (data) {
            if (data.success && data.profile_image) {
                $('#teacherAvatarPreview').src = authBasePath() + data.profile_image;
                showMsg(avMsgEl, data.message || 'Photo updated.', true);
            } else {
                $('#teacherAvatarPreview').src = prevSrc;
                showMsg(avMsgEl, data.message || 'Photo upload failed.', false);
            }
        }).catch(function () {
            $('#teacherAvatarPreview').src = prevSrc;
            showMsg(avMsgEl, 'Network error — photo not uploaded.', false);
        }).finally(function () {
            uploadBtn.disabled = false; uploadBtn.textContent = 'Upload Photo';
            avatarInput.value = ''; selectedFile = null; avFileSize.textContent = '';
        });
    });

    // ---- Sidebar toggle ----
    var s = document.getElementById('teacherSidebar');
    var t = document.getElementById('sidebarToggle');
    var c = document.getElementById('sidebarClose');
    if (t && s) t.addEventListener('click', function () { s.classList.add('active'); });
    if (c && s) c.addEventListener('click', function () { s.classList.remove('active'); });
})();
</script>
<style>
  .muted{color:#888;font-size:13px}
  .section-header{margin-top:24px;padding-top:20px;border-top:1px solid #eee}
  @media (max-width:640px){form-grid{grid-template-columns:1fr}}
</style>
<script>
(function () {
    'use strict';
    /*
     * Password change is AJAX-driven for instant feedback. The form still
     * degrades to a native POST if auth.js failed to load - that path is
     * handled server-side at the top of this view.
     */
    var form = document.getElementById('pwForm');
    if (!form) return;
    var oldPw     = document.getElementById('pwOld');
    var newPw     = document.getElementById('pwNew');
    var confirmPw = document.getElementById('pwConfirm');
    var pwMsg     = document.getElementById('pwMsg');
    var btn       = document.getElementById('btnPwSave');

    function show(text, ok) {
        pwMsg.textContent = text;
        pwMsg.style.display = 'block';
        pwMsg.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
    }

    form.addEventListener('submit', function (e) {
        // No auth.js -> let the browser POST normally.
        if (typeof Auth === 'undefined' || typeof Auth.changePassword !== 'function') return;

        e.preventDefault();
        if (newPw.value !== confirmPw.value) { show('New passwords do not match.', false); return; }
        if (newPw.value.length < 8)          { show('New password must be at least 8 characters.', false); return; }

        btn.disabled = true;
        Auth.changePassword(oldPw.value, newPw.value).then(function (data) {
            var ok = !!(data && data.success);
            show((data && data.message) || (ok ? 'Password changed successfully.' : 'Password change failed.'), ok);
            if (ok) { oldPw.value = ''; newPw.value = ''; confirmPw.value = ''; }
        }).catch(function () {
            show('Network error - password not changed.', false);
        }).then(function () {
            btn.disabled = false;
        });
    });
})();
</script>
