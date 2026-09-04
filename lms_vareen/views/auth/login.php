<?php
// Login page — role-aware (Admin / Teacher / Student).
// The selected role is only a hint for account selection; the backend verifies
// email + password + intended role, and the session role always decides
// authorization and the post-login redirect.
?>
<script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>VAREEN Academy</h1>
            <p>Welcome Back</p>
        </div>

        <form id="loginForm" class="auth-form">
            <div class="form-group">
                <span class="role-label" id="roleLabel">I am signing in as</span>
                <div class="role-tabs" role="tablist" aria-label="Select your account type">
                    <button type="button" class="role-tab" data-role="student" role="tab" aria-selected="true">Student</button>
                    <button type="button" class="role-tab" data-role="teacher" role="tab" aria-selected="false">Teacher / Staff</button>
                    <button type="button" class="role-tab" data-role="admin" role="tab" aria-selected="false">Admin</button>
                </div>
                <input type="hidden" name="intended_role" id="intendedRole" value="student" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="your@email.com"
                    autocomplete="username"
                    required
                >
                <small class="error-msg" id="emailError"></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                >
                <small class="error-msg" id="passwordError"></small>
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>

            <div class="error-message" id="loginError" style="display: none;"></div>
            <div class="success-message" id="loginSuccess" style="display: none;"></div>
        </form>

        <div class="auth-divider">Or</div>

        <div class="auth-links">
            <p class="forgot-password">
<a href="index.php?page=password-reset">Forgot Password?</a>
            </p>
            <p class="signup-link">
<a href="index.php?page=signup">Sign Up</a>
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
        margin-bottom: 30px;
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

    .auth-form {
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label,
    .role-label {
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

    .form-group.checkbox {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }

    .form-group.checkbox input[type="checkbox"] {
        margin: 0;
        margin-right: 8px;
        width: auto;
        cursor: pointer;
    }

    .form-group.checkbox label {
        margin: 0;
        font-weight: 400;
        cursor: pointer;
    }

    /* Role selection tabs */
    .role-tabs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .role-tab {
        padding: 10px 6px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f8f9ff;
        color: #555;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        line-height: 1.3;
    }

    .role-tab:hover {
        border-color: #667eea;
        color: #667eea;
    }

    .role-tab.active {
        background: #667eea;
        border-color: #667eea;
        color: #fff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.35);
    }

    .form-group.checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group.checkbox input[type="checkbox"] {
        width: auto;
    }

    .form-group.checkbox label {
        margin-bottom: 0;
        font-weight: 400;
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

    .btn-primary:disabled {
        background: #9aa7f2;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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

    .forgot-password {
        margin-bottom: 15px;
    }

    .signup-link {
        margin: 0;
        color: #666;
    }

    /* Mobile Responsive */
    @media (max-width: 480px) {
        .auth-box {
            padding: 30px 20px;
        }

        .auth-header h1 {
            font-size: 24px;
        }

        .role-tabs {
            grid-template-columns: 1fr;
        }

        .role-tab {
            padding: 10px 12px;
        }
    }

    @media (max-width: 768px) {
        .auth-box {
            padding: 30px 20px;
        }
    }
</style>

<script>
(function () {
    var selectedRole = 'student';

    var tabs = document.querySelectorAll('.role-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            selectedRole = tab.getAttribute('data-role');
            document.getElementById('intendedRole').value = selectedRole;
        });
    });

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        var loginError = document.getElementById('loginError');
        var loginSuccess = document.getElementById('loginSuccess');
        var submitBtn = e.target.querySelector('button[type="submit"]');

        loginError.style.display = 'none';
        loginSuccess.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing In…';

        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN
                },
                body: JSON.stringify({ email: email, password: password, intended_role: selectedRole })
            });

            const data = await response.json();

            if (data.success) {
                loginSuccess.textContent = 'Login successful! Redirecting...';
                loginSuccess.style.display = 'block';

                // Redirect by the SERVER-VERIFIED role, not by the selected tab.
                setTimeout(() => {
                    if (data.user.role === 'admin') {
                        window.location.href = 'index.php?page=admin-dashboard';
                    } else if (data.user.role === 'teacher') {
                        window.location.href = 'index.php?page=teacher-dashboard';
                    } else {
                        window.location.href = 'index.php?page=student-dashboard';
                    }
                }, 1000);
            } else {
                loginError.textContent = data.message || 'Login failed. Please try again.';
                loginError.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
            }
        } catch (error) {
            loginError.textContent = 'An error occurred. Please try again.';
            loginError.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In';
        }
    });
})();
</script>
