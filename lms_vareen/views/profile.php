<?php
// Student/User Profile page
requireLogin();

require_once 'src/classes/User.php';

$user = new User();
$user_id = getCurrentUserId();
$user_data = $user->getUserById($user_id);

if (!$user_data) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// Resolve the avatar URL (VX-009): stored paths are relative to lms_vareen/
$avatar_path = trim((string)($user_data['profile_image'] ?? ''));
$avatar_url = '';
if ($avatar_path !== '') {
    $avatar_url = preg_match('#^https?://#i', $avatar_path)
        ? $avatar_path
        : BASE_URL . ltrim($avatar_path, '/');
}
?>

<div class="profile-page">
    <div class="container">
        <div class="profile-header">
            <div class="profile-banner"></div>
            
            <div class="profile-content">
                <div class="profile-avatar" id="profileAvatarWrap">
                    <?php if ($avatar_url !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?> profile photo" id="profileAvatarImg">
                    <?php else: ?>
                        <img src="" alt="" id="profileAvatarImg" hidden>
                        <i class="fas fa-user" id="profileAvatarPlaceholder"></i>
                    <?php endif; ?>
                    <button type="button" class="avatar-upload-btn" id="avatarUploadBtn" title="Upload profile photo">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                </div>

                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h1>
                    <p class="profile-role">
                        <span class="badge badge-primary">
                            <?php echo ucfirst($_SESSION['role']); ?>
                        </span>
                    </p>
                    <p class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user_data['email']); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Profile Information -->
            <section class="profile-section">
                <h2>Profile Information</h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>First Name</label>
                        <p><?php echo htmlspecialchars($user_data['first_name']); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Last Name</label>
                        <p><?php echo htmlspecialchars($user_data['last_name']); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Member Since</label>
                        <p><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Phone</label>
                        <p><?php echo htmlspecialchars($user_data['phone'] ?? 'Not set'); ?></p>
                    </div>

                    <div class="info-item">
                        <label>City</label>
                        <p><?php echo htmlspecialchars($user_data['city'] ?? 'Not set'); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Country</label>
                        <p><?php echo htmlspecialchars($user_data['country'] ?? 'Not set'); ?></p>
                    </div>

                    <div class="info-item">
                        <label>Specialization</label>
                        <p><?php echo htmlspecialchars(($user_data['specialization'] ?? '') !== '' ? $user_data['specialization'] : 'Not set'); ?></p>
                    </div>
                </div>

                <?php if (!empty($user_data['bio'])): ?>
                    <div class="info-item info-item-full">
                        <label>About</label>
                        <p><?php echo nl2br(htmlspecialchars($user_data['bio'])); ?></p>
                    </div>
                <?php endif; ?>

                <button class="btn btn-primary" onclick="editProfile()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </section>

            <!-- Security -->
            <section class="profile-section">
                <h2>Security</h2>
                
                <div class="security-item">
                    <div>
                        <h3>Password</h3>
                        <p>Last changed: <?php echo date('M d, Y', strtotime($user_data['updated_at'])); ?></p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="changePassword()">
                        Change Password
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Change Password</h2>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>

        <form id="changePasswordForm" class="modal-body">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="oldPassword" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="newPassword" required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPassword" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Change Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Profile Modal (VX-009) -->
<div id="editProfileModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="modal-close" onclick="closeEditProfileModal()">&times;</button>
        </div>

        <form id="editProfileForm" class="modal-body">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                <small>Email cannot be changed. Contact support if needed.</small>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="+234 800 000 0000">
            </div>

            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" value="<?php echo htmlspecialchars($user_data['specialization'] ?? ''); ?>" placeholder="e.g. Web Development">
            </div>

            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Country</label>
                <input type="text" name="country" value="<?php echo htmlspecialchars($user_data['country'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>About</label>
                <textarea name="bio" rows="3" placeholder="Tell us about yourself"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditProfileModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .profile-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: calc(100vh - 100px);
    }

    .profile-header {
        background: white;
        border-radius: 8px;
        box-shadow: var(--box-shadow);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .profile-banner {
        height: 150px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .profile-content {
        padding: 30px;
        display: flex;
        gap: 25px;
        align-items: flex-start;
        margin-top: -75px;
        position: relative;
        z-index: 1;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
        border: 4px solid white;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-upload-btn {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 2px solid white;
        background: #4a5568;
        color: white;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .avatar-upload-btn:hover,
    .avatar-upload-btn:focus-visible {
        background: #2d3748;
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }

    .info-item-full {
        grid-column: 1 / -1;
        margin-bottom: 20px;
    }

    .profile-info h1 {
        margin: 0 0 10px;
        font-size: 28px;
        color: #333;
    }

    .profile-role {
        margin: 0 0 8px;
    }

    .profile-email {
        margin: 0;
        color: #666;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }

    .profile-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: var(--box-shadow);
    }

    .profile-section h2 {
        margin: 0 0 25px;
        font-size: 18px;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 15px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-item label {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-item p {
        margin: 0;
        font-size: 14px;
        color: #333;
    }

    .security-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid #eee;
    }

    .security-item:last-child {
        border-bottom: none;
    }

    .security-item h3 {
        margin: 0 0 5px;
        font-size: 14px;
        color: #333;
    }

    .security-item p {
        margin: 0;
        font-size: 12px;
        color: #999;
    }

    /* Modal */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .modal-body {
        padding: 25px;
    }

    .modal-footer {
        padding: 15px 25px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .profile-content {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .security-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>

<script src="<?php echo appBasePath(); ?>/public/js/auth.js"></script>

<script>
function changePassword() {
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function closePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
    document.getElementById('changePasswordForm').reset();
}

document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const oldPassword = document.getElementById('oldPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        VereenaUtils.showToast('Passwords do not match', 'error');
        return;
    }

    const response = await Auth.changePassword(oldPassword, newPassword);
    
    if (response.success) {
        VereenaUtils.showToast('Password changed successfully', 'success');
        closePasswordModal();
    } else {
        VereenaUtils.showToast(response.message, 'error');
    }
});

function editProfile() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

// Save profile edits (VX-009)
document.getElementById('editProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const btn = document.getElementById('saveProfileBtn');
    const payload = {};
    ['first_name', 'last_name', 'phone', 'specialization', 'city', 'country', 'bio'].forEach((key) => {
        const el = form.querySelector('[name="' + key + '"]');
        if (el) payload[key] = el.value.trim();
    });

    if (!payload.first_name || !payload.last_name) {
        VereenaUtils.showToast('First and last name are required', 'error');
        return;
    }

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    const response = await Auth.updateProfile(payload);

    btn.disabled = false;
    btn.innerHTML = original;

    if (response.success) {
        VereenaUtils.showToast('Profile updated successfully', 'success');
        closeEditProfileModal();
        setTimeout(() => window.location.reload(), 800);
    } else {
        VereenaUtils.showToast(response.message || 'Update failed', 'error');
    }
});

// Profile photo upload (VX-009)
document.getElementById('avatarUploadBtn').addEventListener('click', () => {
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        VereenaUtils.showToast('Please choose an image file', 'error');
        e.target.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        VereenaUtils.showToast('Image too large (max 5MB)', 'error');
        e.target.value = '';
        return;
    }

    const btn = document.getElementById('avatarUploadBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const response = await Auth.uploadAvatar(file);

    btn.disabled = false;
    btn.innerHTML = original;
    e.target.value = '';

    if (response.success && response.profile_image) {
        const img = document.getElementById('profileAvatarImg');
        const placeholder = document.getElementById('profileAvatarPlaceholder');
        // BASE_URL is rendered by PHP; strip the leading app path from the stored relative path
        img.src = '<?php echo BASE_URL; ?>' + String(response.profile_image).replace(/^\/+/, '');
        img.hidden = false;
        if (placeholder) placeholder.style.display = 'none';
        VereenaUtils.showToast('Profile photo updated', 'success');
    } else {
        VereenaUtils.showToast(response.message || 'Photo upload failed', 'error');
    }
});

// Close modal when clicking outside
document.getElementById('changePasswordModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'changePasswordModal') {
        closePasswordModal();
    }
});

document.getElementById('editProfileModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'editProfileModal') {
        closeEditProfileModal();
    }
});

// Escape closes any open modal
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (document.getElementById('changePasswordModal').style.display === 'flex') closePasswordModal();
    if (document.getElementById('editProfileModal').style.display === 'flex') closeEditProfileModal();
});
</script>
