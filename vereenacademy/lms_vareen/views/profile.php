<?php
// Student/User Profile page
requireLogin();

require_once 'src/classes/User.php';

$user = new User();
$user_id = getCurrentUserId();
$user_data = $user->getUserById($user_id);

if (!$user_data) {
    header('Location: /index.php?page=login');
    exit;
}
?>

<div class="profile-page">
    <div class="container">
        <div class="profile-header">
            <div class="profile-banner"></div>
            
            <div class="profile-content">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
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
                </div>

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
    VereenaUtils.showToast('Profile editing coming soon', 'info');
}

// Close modal when clicking outside
document.getElementById('changePasswordModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'changePasswordModal') {
        closePasswordModal();
    }
});
</script>
