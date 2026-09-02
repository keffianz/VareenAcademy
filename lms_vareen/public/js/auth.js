/**
 * VEREEN Academy - Authentication JavaScript
 */

const Auth = {
    /**
     * Perform login
     */
    login: async (email, password) => {
        try {
const response = await fetch('/lms_vareen/src/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: 'Login failed' };
        }
    },

    /**
     * Perform signup
     */
    signup: async (first_name, last_name, email, password, role = 'student') => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=signup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ first_name, last_name, email, password, role })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Signup error:', error);
            return { success: false, message: 'Signup failed' };
        }
    },

    /**
     * Logout
     */
    logout: async () => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=logout', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                window.location.href = '/index.php?page=login';
            }
            return data;
        } catch (error) {
            console.error('Logout error:', error);
            return { success: false, message: 'Logout failed' };
        }
    },

    /**
     * Check if email exists
     */
    checkEmail: async (email) => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=check_email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Email check error:', error);
            return { success: false };
        }
    },

    /**
     * Request password reset
     */
    requestPasswordReset: async (email) => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=request_reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Password reset request error:', error);
            return { success: false, message: 'Request failed' };
        }
    },

    /**
     * Reset password with token
     */
    resetPassword: async (token, new_password) => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=reset_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token, new_password })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Password reset error:', error);
            return { success: false, message: 'Reset failed' };
        }
    },

    /**
     * Change password
     */
    changePassword: async (old_password, new_password) => {
        try {
            const response = await fetch('/lms_vareen/src/api/auth.php?action=change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ old_password, new_password })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Change password error:', error);
            return { success: false, message: 'Password change failed' };
        }
    },

    /**
     * Validate form
     */
    validateSignupForm: (firstName, lastName, email, password, confirmPassword, terms) => {
        const errors = [];

        if (!firstName.trim()) {
            errors.push('First name is required');
        }

        if (!lastName.trim()) {
            errors.push('Last name is required');
        }

        if (!email.trim()) {
            errors.push('Email is required');
        } else if (!VereenaUtils.isValidEmail(email)) {
            errors.push('Invalid email format');
        }

        if (!password) {
            errors.push('Password is required');
        } else if (password.length < 8) {
            errors.push('Password must be at least 8 characters');
        }

        if (password !== confirmPassword) {
            errors.push('Passwords do not match');
        }

        if (!terms) {
            errors.push('You must agree to the terms and conditions');
        }

        return {
            valid: errors.length === 0,
            errors
        };
    },

    /**
     * Validate login form
     */
    validateLoginForm: (email, password) => {
        const errors = [];

        if (!email.trim()) {
            errors.push('Email is required');
        } else if (!VereenaUtils.isValidEmail(email)) {
            errors.push('Invalid email format');
        }

        if (!password) {
            errors.push('Password is required');
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }
};

// Export Auth
window.Auth = Auth;

// Handle form errors display
function displayFormErrors(errors, errorElementId) {
    const errorElement = document.getElementById(errorElementId);
    if (!errorElement) return;

    if (errors.length === 0) {
        errorElement.style.display = 'none';
        return;
    }

    errorElement.innerHTML = errors.join('<br>');
    errorElement.style.display = 'block';
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.remove('error');
        const errorMsg = field.nextElementSibling;
        if (errorMsg && errorMsg.classList.contains('error-msg')) {
            errorMsg.textContent = '';
        }
    }
}

function setFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('error');
        const errorMsg = field.nextElementSibling;
        if (errorMsg && errorMsg.classList.contains('error-msg')) {
            errorMsg.textContent = message;
        }
    }
}

// Make functions globally available
window.displayFormErrors = displayFormErrors;
window.clearFieldError = clearFieldError;
window.setFieldError = setFieldError;
