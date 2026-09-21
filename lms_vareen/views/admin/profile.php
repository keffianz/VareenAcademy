<?php
/**
 * VAREEN Admin - My Profile
 * Edit personal info, change password, upload profile photo.
 * Requires admin role. CSRF enforced.
 */
requireRole('admin');
// Views cannot rely on autoloading — mirror every sibling admin view.
require_once 'src/classes/Database.php';
$admin_active = 'profile';
$page_title = 'My Profile';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dashboard-wrapper">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dashboard-content">
  <div class="dashboard-topbar">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <div><h1>My Profile</h1><p class="dash-sub">Manage your admin account details.</p></div>
    <button class="btn btn-logout" id="adminLogoutBtnTop"><i class="fas fa-sign-out-alt"></i> Logout</button>
  </div>
<?php
try {
    $db = (new Database())->connect();
    $stmt = $db->prepare('SELECT id, first_name, last_name, email, phone, city, country, specialization, bio, profile_image, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[admin-profile] load failed: ' . $e->getMessage());
    $admin = [];
}
?>
  <div class="dashboard-section">
    <div class="section-header"><h2 style="margin:0;font-size:18px;">Personal Information</h2></div>
    <p class="dash-sub" style="margin:0 0 16px;">Update your name, contact details and bio. Email cannot be changed here.</p>
    <div id="profMsg" class="admin-msg" hidden></div>
    <form id="profForm" class="card admin-form">
      <div class="admin-grid">
        <label>First name<input name="first_name" id="firstName" value="<?php echo htmlspecialchars($admin['first_name'] ?? ''); ?>" required></label>
        <label>Last name<input name="last_name" id="lastName" value="<?php echo htmlspecialchars($admin['last_name'] ?? ''); ?>" required></label>
        <label>Email <span class="muted">(readonly)</span>
          <input name="email" id="adminEmail" type="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" disabled>
        </label>
        <label>Phone<input name="phone" id="adminPhone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>"></label>
        <label>City<input name="city" id="adminCity" value="<?php echo htmlspecialchars($admin['city'] ?? ''); ?>"></label>
        <label>Country<input name="country" id="adminCountry" value="<?php echo htmlspecialchars($admin['country'] ?? ''); ?>"></label>
        <label>Specialization<input name="specialization" id="adminSpec" value="<?php echo htmlspecialchars($admin['specialization'] ?? ''); ?>" placeholder="e.g. IT Management"></label>
      </div>
      <div class="admin-grid" style="grid-template-columns:1fr;">
        <label>Bio <span class="muted">(max 2000 characters)</span>
          <textarea name="bio" id="adminBio" rows="4" style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;"><?php echo htmlspecialchars($admin['bio'] ?? ''); ?></textarea>
        </label>
      </div>
      <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
        <button class="btn btn-primary" type="submit" id="btnProfSave">Save Profile</button>
        <span id="profLoading" class="muted" style="display:none;">Saving...</span>
      </div>
    </form>
    <div class="section-header" style="margin-top:28px;"><h2 style="margin:0;font-size:18px;">Change Password</h2></div>
    <p class="dash-sub" style="margin:0 0 16px;">Enter your current password to set a new one.</p>
    <div id="pwMsg" class="admin-msg" hidden></div>
    <form id="pwForm" class="card admin-form">
      <div class="admin-grid">
        <label>Current password<input name="old_password" id="pwOld" type="password" autocomplete="current-password" required></label>
        <label>New password <span class="muted">(min 8 characters)</span><input name="new_password" id="pwNew" type="password" autocomplete="new-password" required minlength="8"></label>
        <label>Confirm new password<input name="confirm_password" id="pwConfirm" type="password" autocomplete="new-password" required minlength="8"></label>
      </div>
      <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
        <button class="btn btn-primary" type="submit" id="btnPwSave">Update Password</button>
        <span id="pwLoading" class="muted" style="display:none;">Processing...</span>
      </div>
    </form>
    <div class="section-header" style="margin-top:28px;"><h2 style="margin:0;font-size:18px;">Profile Photo</h2></div>
    <p class="dash-sub" style="margin:0 0 16px;">Upload a JPG, PNG or WEBP image. Existing photo will be replaced.</p>
    <div id="avMsg" class="admin-msg" hidden></div>
    <div class="card" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
      <div style="flex-shrink:0;">
        <?php
        $avatar = $admin['profile_image'] ?? '';
        $avatarUrl = $avatar !== '' && file_exists(__DIR__ . '/../..' . $avatar)
            ? appBasePath() . $avatar
            : appBasePath() . '/public/images/default-avatar.png';
        ?>
        <img src="<?php echo $avatarUrl; ?>" alt="Profile photo" id="adminAvatarPreview"
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #ddd;background:#f5f5f5;">
      </div>
      <div style="flex:1;min-width:200px;">
        <input type="file" id="adminAvatarInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <button class="btn btn-secondary" type="button" id="adminAvatarBtn">Choose Photo</button>
        <span class="muted"> &middot; </span>
        <button class="btn btn-primary" type="button" id="adminAvatarUploadBtn" disabled style="opacity:0.6;">Upload Photo</button>
        <span id="avFileSize" class="muted" style="margin-left:8px;"></span>
      </div>
    </div>
  </div>
</main>
</div>
<style>
  .admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
  .admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
  .admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
  .admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin:0 0 6px}
  .admin-grid label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:#333}
  .admin-grid input{padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-weight:400;background:#fff}
  .admin-grid input:disabled{background:#f5f5f5;color:#666}
  .muted{color:#888;font-size:13px}
  .section-header{margin-top:24px;padding-top:20px;border-top:1px solid #eee}
  @media (max-width:640px){.admin-grid{grid-template-columns:1fr}}
</style>
<script>
  window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';
  const ADMIN_API = '<?php echo appBasePath(); ?>/src/api/admin.php';
  const AUTH_API  = '<?php echo appBasePath(); ?>/src/api/auth.php';
  const adminUserId = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
  const $ = (s) => document.querySelector(s);
  const msg = (el, text, ok) => {
    el.textContent = text; el.hidden = false;
    el.className = 'admin-msg ' + (ok ? 'ok' : 'err');
  };
  const clearMsg = (el) => { el.textContent = ''; el.hidden = true; };

  // --- Profile info ---
  document.getElementById('profForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    clearMsg(document.getElementById('profMsg'));
    const btn = document.getElementById('btnProfSave');
    const ld  = document.getElementById('profLoading');
    btn.disabled = true; ld.style.display = 'inline';
    const fd = new FormData();
    fd.append('user_id', adminUserId);
    fd.append('first_name',    document.getElementById('firstName').value.trim());
    fd.append('last_name',     document.getElementById('lastName').value.trim());
    fd.append('phone',         document.getElementById('adminPhone').value.trim());
    fd.append('city',          document.getElementById('adminCity').value.trim());
    fd.append('country',       document.getElementById('adminCountry').value.trim());
    fd.append('specialization',document.getElementById('adminSpec').value.trim());
    fd.append('bio',           document.getElementById('adminBio').value.trim());
    try {
      const r = await fetch(ADMIN_API + '?action=admin_profile_update', {
        method: 'POST',
        headers: { 'X-CSRF-Token': window.CSRF_TOKEN },
        body: fd
      });
      const d = await r.json();
      msg(document.getElementById('profMsg'), d.message || 'Profile updated', !!d.success);
    } catch (err) {
      msg(document.getElementById('profMsg'), 'Network error', false);
    } finally {
      btn.disabled = false; ld.style.display = 'none';
    }
  });

  // --- Change password ---
  document.getElementById('pwForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    clearMsg(document.getElementById('pwMsg'));
    const oldPw     = document.getElementById('pwOld').value;
    const newPw     = document.getElementById('pwNew').value;
    const confirmPw = document.getElementById('pwConfirm').value;
    if (newPw !== confirmPw) { msg(document.getElementById('pwMsg'), 'Passwords do not match', false); return; }
    if (newPw.length < 8)    { msg(document.getElementById('pwMsg'), 'Password must be at least 8 characters', false); return; }
    const btn = document.getElementById('btnPwSave');
    const ld  = document.getElementById('pwLoading');
    btn.disabled = true; ld.style.display = 'inline';
    try {
      const r = await fetch(AUTH_API + '?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
        body: JSON.stringify({ old_password: oldPw, new_password: newPw })
      });
      const d = await r.json();
      msg(document.getElementById('pwMsg'), d.message || 'Password updated', !!d.success);
      if (d.success) {
        document.getElementById('pwOld').value = '';
        document.getElementById('pwNew').value = '';
        document.getElementById('pwConfirm').value = '';
      }
    } catch (err) {
      msg(document.getElementById('pwMsg'), 'Network error', false);
    } finally {
      btn.disabled = false; ld.style.display = 'none';
    }
  });

  // --- Avatar upload ---
  const avatarInput = document.getElementById('adminAvatarInput');
  const avatarBtn   = document.getElementById('adminAvatarBtn');
  const uploadBtn   = document.getElementById('adminAvatarUploadBtn');
  const avMsg       = document.getElementById('avMsg');
  const avFileSize  = document.getElementById('avFileSize');
  let selectedFile  = null;

  avatarBtn.addEventListener('click', () => avatarInput.click());

  avatarInput.addEventListener('change', () => {
    selectedFile = avatarInput.files[0] || null;
    if (selectedFile) {
      if (selectedFile.size > 5 * 1024 * 1024) {
        msg(avMsg, 'Image must be under 5 MB', false);
        selectedFile = null; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
        avFileSize.textContent = ''; return;
      }
      const okTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!okTypes.includes(selectedFile.type)) {
        msg(avMsg, 'Please select a JPG, PNG or WEBP image', false);
        selectedFile = null; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
        avFileSize.textContent = ''; return;
      }
      avFileSize.textContent = (selectedFile.size / 1024).toFixed(1) + ' KB';
      uploadBtn.disabled = false; uploadBtn.style.opacity = '1';
      avMsg.hidden = true;
    } else {
      avFileSize.textContent = ''; uploadBtn.disabled = true; uploadBtn.style.opacity = '0.6';
    }
  });

  uploadBtn.addEventListener('click', async () => {
    if (!selectedFile) return;
    clearMsg(avMsg);
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';
    try {
      const r = await fetch(AUTH_API + '?action=upload_avatar', {
        method: 'POST',
        headers: { 'X-CSRF-Token': window.CSRF_TOKEN },
        body: new FormData().append('avatar', selectedFile)
      });
      const d = await r.json();
      msg(avMsg, d.message || 'Photo updated', !!d.success);
      if (d.success && d.profile_image) {
        document.getElementById('adminAvatarPreview').src =
          '<?php echo addslashes(appBasePath()); ?>' + d.profile_image;
      }
    } catch (err) {
      msg(avMsg, 'Network error', false);
    } finally {
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Upload Photo';
      avatarInput.value = '';
      selectedFile = null;
      avFileSize.textContent = '';
    }
  });
</script>