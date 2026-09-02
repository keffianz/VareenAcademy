<?php
// Signup page
?>
<script>window.CSRF_TOKEN = '<?php echo csrfToken(); ?>';</script>
<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>VEREEN Academy</h1>
            <p>Create Your Account</p>
        </div>

        <form id="signupForm" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        placeholder="John"
                        required
                    >
                    <small class="error-msg" id="firstNameError"></small>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        placeholder="Doe"
                        required
                    >
                    <small class="error-msg" id="lastNameError"></small>
                </div>
            </div>

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

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="••••••••"
                    required
                >
                <small class="error-msg" id="confirmPasswordError"></small>
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the Terms & Conditions</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            
            <div class="error-message" id="signupError" style="display: none;"></div>
            <div class="success-message" id="signupSuccess" style="display: none;"></div>
        </form>

        <div class="auth-divider">Or</div>

        <div class="auth-links">
            <p class="signin-link">
<a href="index.php?page=login">Sign In</a>
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
        max-width: 500px;
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

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
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
    .form-group input[type="password"],
    .form-group select {
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
    .form-group input[type="password"]:focus,
    .form-group select:focus {
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
        font-size: 13px;
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

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
document.getElementById('signupForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Clear previous errors
    document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
    document.getElementById('signupError').style.display = 'none';
    document.getElementById('signupSuccess').style.display = 'none';

    const first_name = document.getElementById('first_name').value;
    const last_name = document.getElementById('last_name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirm_password = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;

    // Validation
    let isValid = true;

    if (first_name.trim() === '') {
        document.getElementById('firstNameError').textContent = 'First name is required';
        isValid = false;
    }

    if (last_name.trim() === '') {
        document.getElementById('lastNameError').textContent = 'Last name is required';
        isValid = false;
    }

    if (email.trim() === '') {
        document.getElementById('emailError').textContent = 'Email is required';
        isValid = false;
    }

    if (password.length < 8) {
        document.getElementById('passwordError').textContent = 'Password must be at least 8 characters';
        isValid = false;
    }

    if (password !== confirm_password) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
        isValid = false;
    }

    if (!terms) {
        document.getElementById('signupError').textContent = 'You must agree to the terms';
        document.getElementById('signupError').style.display = 'block';
        isValid = false;
    }

    if (!isValid) return;

    try {
        const response = await fetch('/lms_vareen/src/api/auth.php?action=signup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify({ first_name, last_name, email, password, role: 'student' })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('signupSuccess').textContent = 'Account created! Redirecting to login...';
            document.getElementById('signupSuccess').style.display = 'block';
            
            setTimeout(() => {
window.location.href = 'index.php?page=login&msg=signup_success';
            }, 2000);
        } else {
            document.getElementById('signupError').textContent = data.message;
            document.getElementById('signupError').style.display = 'block';
        }
    } catch (error) {
        document.getElementById('signupError').textContent = 'An error occurred. Please try again.';
        document.getElementById('signupError').style.display = 'block';
    }
});
</script>
