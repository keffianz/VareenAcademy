<?php
// Login page
?>
<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>VEREEN Academy</h1>
            <p>Welcome Back</p>
        </div>

        <form id="loginForm" class="auth-form">
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

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
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

    .forgot-password {
        margin-bottom: 15px;
    }

    .signup-link {
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
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    console.log('Login payload:', { email, password });


    try {
        const response = await fetch('/lms_vareen/src/api/auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('loginSuccess').textContent = 'Login successful! Redirecting...';
            document.getElementById('loginSuccess').style.display = 'block';

            // Redirect based on role
            setTimeout(() => {
                if (data.user.role === 'teacher' || data.user.role === 'admin') {
                    window.location.href = 'index.php?page=teacher-dashboard';
                } else {
                    window.location.href = 'index.php?page=student-dashboard';
                }
            }, 1000);
        } else {
            document.getElementById('loginError').textContent = data.message;
            document.getElementById('loginError').style.display = 'block';
        }
    } catch (error) {
        document.getElementById('loginError').textContent = 'An error occurred. Please try again.';
        document.getElementById('loginError').style.display = 'block';
    }
});
</script>
