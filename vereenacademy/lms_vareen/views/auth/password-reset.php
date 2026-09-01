<?php
// Password reset page
?>
<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>VEREEN Academy</h1>
            <p>Reset Your Password</p>
        </div>

        <form id="resetForm" class="auth-form">
            <div id="emailStep">
                <p class="reset-description">Enter your email address and we'll send you a link to reset your password.</p>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your@email.com"
                        required
                    >
                    <small class="error-msg" id="emailError"></small>
                </div>

                <button type="button" class="btn btn-primary btn-block" onclick="sendResetLink()">Send Reset Link</button>
                
                <div class="error-message" id="resetError" style="display: none;"></div>
                <div class="success-message" id="resetSuccess" style="display: none;"></div>
            </div>

            <div id="passwordStep" style="display: none;">
                <p class="reset-description">Enter the code you received and your new password.</p>

                <div class="form-group">
                    <label for="token">Reset Code</label>
                    <input 
                        type="text" 
                        id="token" 
                        name="token" 
                        placeholder="Enter the code from email"
                        required
                    >
                    <small class="error-msg" id="tokenError"></small>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        placeholder="••••••••"
                        required
                    >
                    <small class="error-msg" id="newPasswordError"></small>
                </div>

                <div class="form-group">
                    <label for="confirm_new_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_new_password" 
                        name="confirm_new_password" 
                        placeholder="••••••••"
                        required
                    >
                    <small class="error-msg" id="confirmPasswordError"></small>
                </div>

                <button type="button" class="btn btn-primary btn-block" onclick="resetPassword()">Reset Password</button>
                
                <div class="error-message" id="resetError2" style="display: none;"></div>
                <div class="success-message" id="resetSuccess2" style="display: none;"></div>
            </div>
        </form>

        <div class="auth-divider">Or</div>

        <div class="auth-links">
            <p class="signin-link">
<a href="index.php?page=login">Back to Sign In</a>
            </p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }

    .auth-box {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .auth-header h1 {
        margin: 0;
        color: #667eea;
        font-size: 28px;
        font-weight: 600;
    }

    .auth-header p {
        margin: 10px 0 0;
        color: #666;
        font-size: 14px;
    }

    .reset-description {
        color: #666;
        font-size: 13px;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .auth-form {
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
        font-weight: 500;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-block {
        width: 100%;
        display: block;
    }

    .error-message,
    .success-message {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 14px;
        text-align: center;
    }

    .error-message {
        background: #fee;
        color: #c33;
        border: 1px solid #fcc;
    }

    .success-message {
        background: #efe;
        color: #3c3;
        border: 1px solid #cfc;
    }

    .error-msg {
        color: #c33;
        font-size: 12px;
        display: block;
        margin-top: 5px;
    }

    .auth-divider {
        text-align: center;
        margin: 25px 0;
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
    }

    .auth-links {
        text-align: center;
    }

    .auth-links a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .auth-links a:hover {
        text-decoration: underline;
    }

    .signin-link {
        margin: 0;
        color: #666;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .auth-box {
            padding: 30px 20px;
        }

        .auth-header h1 {
            font-size: 24px;
        }
    }
</style>

<script>
async function sendResetLink() {
    const email = document.getElementById('email').value;
    document.getElementById('emailError').textContent = '';
    document.getElementById('resetError').style.display = 'none';
    document.getElementById('resetSuccess').style.display = 'none';

    if (email.trim() === '') {
        document.getElementById('emailError').textContent = 'Email is required';
        return;
    }

    try {
        const response = await fetch('/src/api/auth.php?action=request_reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('resetSuccess').textContent = 'Reset link sent! Check your email. (Token: ' + data.token + ')';
            document.getElementById('resetSuccess').style.display = 'block';
            
            // For demo purposes, show the token
            setTimeout(() => {
                document.getElementById('emailStep').style.display = 'none';
                document.getElementById('passwordStep').style.display = 'block';
            }, 2000);
        } else {
            document.getElementById('resetError').textContent = data.message;
            document.getElementById('resetError').style.display = 'block';
        }
    } catch (error) {
        document.getElementById('resetError').textContent = 'An error occurred. Please try again.';
        document.getElementById('resetError').style.display = 'block';
    }
}

async function resetPassword() {
    const token = document.getElementById('token').value;
    const new_password = document.getElementById('new_password').value;
    const confirm_new_password = document.getElementById('confirm_new_password').value;

    document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
    document.getElementById('resetError2').style.display = 'none';
    document.getElementById('resetSuccess2').style.display = 'none';

    let isValid = true;

    if (token.trim() === '') {
        document.getElementById('tokenError').textContent = 'Reset code is required';
        isValid = false;
    }

    if (new_password.length < 8) {
        document.getElementById('newPasswordError').textContent = 'Password must be at least 8 characters';
        isValid = false;
    }

    if (new_password !== confirm_new_password) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
        isValid = false;
    }

    if (!isValid) return;

    try {
        const response = await fetch('/src/api/auth.php?action=reset_password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token, new_password })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('resetSuccess2').textContent = 'Password reset successfully! Redirecting to login...';
            document.getElementById('resetSuccess2').style.display = 'block';
            
            setTimeout(() => {
                window.location.href = '/index.php?page=login';
            }, 2000);
        } else {
            document.getElementById('resetError2').textContent = data.message;
            document.getElementById('resetError2').style.display = 'block';
        }
    } catch (error) {
        document.getElementById('resetError2').textContent = 'An error occurred. Please try again.';
        document.getElementById('resetError2').style.display = 'block';
    }
}
</script>
