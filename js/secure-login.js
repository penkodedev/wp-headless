// Modern JavaScript for secure login functionality
// ES6+ features with proper error handling and accessibility

class SecureLogin {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateRemainingTime();
        this.setupFormValidation();
    }

    bindEvents() {
        // Password visibility toggle
        const toggleButton = document.querySelector('.toggle-password');
        if (toggleButton) {
            toggleButton.addEventListener('click', this.togglePasswordVisibility.bind(this));
        }

        // Form submission with validation
        const loginForm = document.getElementById('loginform');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }

        // Error message dismissal - disabled to prevent accidental hiding
        // const errorDiv = document.querySelector('.login-error');
        // if (errorDiv) {
        //     errorDiv.addEventListener('click', this.dismissError.bind(this));
        // }

        // Keyboard navigation improvements
        this.setupKeyboardNavigation();
    }

    togglePasswordVisibility(event) {
        event.preventDefault();

        const passwordInput = document.getElementById('user_pass');
        const toggleText = event.currentTarget.querySelector('.toggle-text');
        const isVisible = passwordInput.type === 'text';

        passwordInput.type = isVisible ? 'password' : 'text';
        toggleText.textContent = isVisible ?
            event.currentTarget.dataset.showText :
            event.currentTarget.dataset.hideText;

        // Update ARIA label
        event.currentTarget.setAttribute('aria-label',
            isVisible ? event.currentTarget.dataset.showText : event.currentTarget.dataset.hideText
        );

        // Focus management for accessibility
        passwordInput.focus();
    }

    handleFormSubmit(event) {
        const form = event.target;
        const submitButton = form.querySelector('#wp-submit');

        if (!this.validateForm(form)) {
            event.preventDefault();
            return false;
        }

        // Show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = this.getLocalizedText('logging_in', 'Logging in...');
        }
    }

    validateForm(form) {
        const username = form.querySelector('#user_login');
        const password = form.querySelector('#user_pass');
        let isValid = true;
        let firstInvalidField = null;

        // Clear previous errors
        this.clearFieldErrors();

        // Validate username
        if (!username.value.trim()) {
            this.showFieldError(username, this.getLocalizedText('username_required', 'Username is required'));
            if (!firstInvalidField) firstInvalidField = username;
            isValid = false;
        }

        // Validate password
        if (!password.value) {
            this.showFieldError(password, this.getLocalizedText('password_required', 'Password is required'));
            if (!firstInvalidField) firstInvalidField = password;
            isValid = false;
        }

        // Focus first invalid field
        if (firstInvalidField) {
            firstInvalidField.focus();
        }

        return isValid;
    }

    showFieldError(field, message) {
        const fieldGroup = field.closest('.form-group');
        if (fieldGroup) {
            fieldGroup.classList.add('has-error');

            let errorElement = fieldGroup.querySelector('.field-error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'field-error';
                errorElement.setAttribute('role', 'alert');
                fieldGroup.appendChild(errorElement);
            }
            errorElement.textContent = message;
        }

        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', field.getAttribute('aria-describedby') + ' field-error-' + field.id);
    }

    clearFieldErrors() {
        const errorFields = document.querySelectorAll('.form-group.has-error');
        errorFields.forEach(field => {
            field.classList.remove('has-error');
            const errorElement = field.querySelector('.field-error');
            if (errorElement) {
                errorElement.remove();
            }
        });

        // Reset aria-invalid
        const invalidFields = document.querySelectorAll('[aria-invalid="true"]');
        invalidFields.forEach(field => {
            field.removeAttribute('aria-invalid');
        });
    }

    dismissError(event) {
        const errorDiv = event.currentTarget;
        errorDiv.style.display = 'none';

        // Announce to screen readers
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'screen-reader-text';
        announcement.textContent = this.getLocalizedText('error_dismissed', 'Error message dismissed');
        document.body.appendChild(announcement);

        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    updateRemainingTime() {
        const remainingTimeElement = document.getElementById('remaining-time');
        if (!remainingTimeElement) return;

        const updateTimer = () => {
            let remainingTime = parseInt(remainingTimeElement.textContent, 10);

            if (remainingTime > 0) {
                remainingTime--;
                remainingTimeElement.textContent = remainingTime;

                // Update accessibility announcement
                const announcement = document.querySelector('.time-announcement');
                if (announcement) {
                    announcement.textContent = `${remainingTime} seconds remaining`;
                }

                setTimeout(updateTimer, 1000); // Update every second
            } else {
                // Lockout expired
                const errorDiv = document.getElementById('error-message');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }

                // Re-enable form
                const submitButton = document.getElementById('wp-submit');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = this.getLocalizedText('log_in', 'Log In');
                }
            }
        };

        updateTimer();
    }

    setupKeyboardNavigation() {
        // Enhanced keyboard navigation for form
        const form = document.getElementById('loginform');
        if (!form) return;

        form.addEventListener('keydown', (event) => {
            // Ctrl/Cmd + Enter to submit
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });
    }

    setupFormValidation() {
        // Real-time validation feedback
        const inputs = document.querySelectorAll('#loginform input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                if (input.value.trim() === '') {
                    this.showFieldError(input, `${input.previousElementSibling.textContent} is required`);
                } else {
                    this.clearFieldErrors();
                }
            });

            input.addEventListener('input', () => {
                if (input.hasAttribute('aria-invalid')) {
                    input.removeAttribute('aria-invalid');
                    const fieldGroup = input.closest('.form-group');
                    if (fieldGroup) {
                        fieldGroup.classList.remove('has-error');
                        const errorElement = fieldGroup.querySelector('.field-error');
                        if (errorElement) {
                            errorElement.remove();
                        }
                    }
                }
            });
        });
    }

    getLocalizedText(key, fallback) {
        // Simple localization helper - can be extended with WordPress localization
        const translations = {
            'logging_in': 'Logging in...',
            'username_required': 'Username is required',
            'password_required': 'Password is required',
            'error_dismissed': 'Error message dismissed',
            'log_in': 'Log In'
        };

        return translations[key] || fallback;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new SecureLogin());
} else {
    new SecureLogin();
}

// Export for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SecureLogin;
}
