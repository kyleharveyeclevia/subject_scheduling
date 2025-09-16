// Subject Scheduling System - Enhanced JavaScript

// Function to update breadcrumb
function updateBreadcrumb(section) {
    const currentSection = document.getElementById('currentSection');
    if (currentSection) {
        currentSection.textContent = section;
    }
}

// Initialize tab switching with breadcrumb update
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all tab content
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Show selected tab content
            const selectedTab = document.getElementById(this.dataset.tab);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Update breadcrumb
            updateBreadcrumb(this.querySelector('span').textContent.trim());
        });
    });
});

class FormValidator {
    constructor() {
        this.patterns = {
            name: /^[A-Za-z\s'.-]+$/,
            number: /^\d+$/,
            email: /^[a-zA-Z0-9._%+-]+@gmail\.com$/,
            password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
        };
        
        this.messages = {
            name: 'Name must contain only letters, spaces, and common name characters (apostrophes, hyphens, periods)',
            number: 'Only numbers are allowed',
            email: 'Email must be a valid @gmail.com address',
            password: 'Password must contain at least 8 characters with uppercase, lowercase, number and special character',
            passwordMatch: 'Passwords do not match',
            required: 'This field is required'
        };
        
        this.init();
    }
    
    init() {
        // Loading overlay removed - using unique loading spinner instead
        
        // Initialize form enhancements
        this.initializeFormEnhancements();
        
        // Initialize animations
        this.initializeAnimations();
        
        // Initialize accessibility features
        this.initializeAccessibility();
    }
    
    createLoadingOverlay() {
        // Removed - using unique loading spinner instead
    }
    
    showLoading() {
        // Removed - using unique loading spinner instead
    }
    
    hideLoading() {
        // Removed - using unique loading spinner instead
    }

    validateField(field, type, value = null) {
        const fieldValue = value || field.value.trim();
        
        // Clear previous validation
        this.clearValidation(field);

        // Check if required field is empty
        if (field.hasAttribute('required') && !fieldValue) {
            this.showError(field, this.messages.required);
            return false;
        }

        // Skip validation if field is empty and not required
        if (!fieldValue && !field.hasAttribute('required')) {
            return true;
        }

        // Special handling for name field: only show error if invalid characters are present
        if (type === 'name') {
            if (!fieldValue) {
                return true; // Empty field - no error
            }
            
            // Check if the input contains invalid characters (numbers or disallowed special chars)
            const hasInvalidChars = /[0-9@#$%^&*()+=\[\]{};':"\\|,.<>\/?~`]/.test(fieldValue);
            
            if (hasInvalidChars) {
                this.showError(field, this.messages[type]);
                return false;
            } else {
                // Valid characters - show success
                this.showSuccess(field);
                return true;
            }
        }

        // Validate based on type for other fields
        if (type && this.patterns[type] && !this.patterns[type].test(fieldValue)) {
            this.showError(field, this.messages[type]);
            return false;
        }

        // Show success for valid fields
        if (fieldValue && (!type || this.patterns[type]?.test(fieldValue))) {
            this.showSuccess(field);
        }

        return true;
    }

    validatePasswordMatch(password, confirmPassword) {
        this.clearValidation(confirmPassword);

        if (password.value !== confirmPassword.value) {
            // Don't use showError for confirm password - handled by RegistrationValidator
            return false;
        }

        if (confirmPassword.value) {
            // Don't use showSuccess for confirm password - handled by RegistrationValidator
        }

        return true;
    }

    showError(field, message) {
        field.classList.add('error');
        field.classList.remove('success');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        
        field.parentNode.appendChild(errorDiv);
        
        // Add shake animation
        field.style.animation = 'shake 0.3s ease-in-out';
        setTimeout(() => {
            field.style.animation = '';
        }, 300);
    }
    
    showSuccess(field, message = '') {
        // Disabled - no success messages shown per user request
        field.classList.add('success');
        field.classList.remove('error');
        
        // Don't create success message elements
        // const successDiv = document.createElement('div');
        // successDiv.className = 'success-message';
        // successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        // field.parentNode.appendChild(successDiv);
    }
    
    clearValidation(field) {
        const errorElement = field.parentNode.querySelector('.error-message');
        const successElement = field.parentNode.querySelector('.success-message');
        
        if (errorElement) errorElement.remove();
        if (successElement) successElement.remove();
        
        field.classList.remove('error', 'success');
    }

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            const type = input.dataset.validate;
            if (!this.validateField(input, type)) {
                isValid = false;
            }
        });
        
        // Check password confirmation if exists
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        
        if (password && confirmPassword) {
            if (!this.validatePasswordMatch(password, confirmPassword)) {
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    initializeFormEnhancements() {
        // Add floating labels effect
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            // Add focus and blur effects
            input.addEventListener('focus', () => {
                input.parentNode.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentNode.classList.remove('focused');
                }
            });
            
            // Real-time validation
            input.addEventListener('input', () => {
                const type = input.dataset.validate;
                if (type) {
                    // Debounce validation
                    clearTimeout(input.validationTimeout);
                    input.validationTimeout = setTimeout(() => {
                        this.validateField(input, type);
                    }, 500);
                }
            });
        });
        
        // Enhanced password toggle
        this.initializePasswordToggles();
        
        // Form submission enhancements
        this.initializeFormSubmission();
    }
    
    initializePasswordToggles() {
        const passwordGroups = document.querySelectorAll('.password-group');
        passwordGroups.forEach(group => {
            const input = group.querySelector('input[type="password"]');
            let toggle = group.querySelector('.password-toggle');
            
            if (!toggle && input) {
                toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'password-toggle';
                toggle.innerHTML = '<i class="fas fa-eye"></i>';
                toggle.setAttribute('aria-label', 'Toggle password visibility');
                group.appendChild(toggle);
            }
            
            if (toggle && input) {
                toggle.addEventListener('click', () => {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    toggle.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
                    toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            }
        });
    }
    
    initializeFormSubmission() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    // Show loading state
                    submitBtn.disabled = true;
                    
                    // Reset after 10 seconds if no response
                    setTimeout(() => {
                        submitBtn.disabled = false;
                    }, 10000);
                }
            });
        });
    }
    
    initializeAnimations() {
        // Add fade-in animation to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });
        
        // Add slide-in animation to form groups
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.animationDelay = `${index * 0.1}s`;
            group.classList.add('slide-in');
        });
    }
    
    initializeAccessibility() {
        // Add skip link
        if (!document.querySelector('.skip-link')) {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = 'Skip to main content';
            document.body.insertBefore(skipLink, document.body.firstChild);
        }
        
        // Add main content ID if not exists
        const mainContent = document.querySelector('main, .main-content, .admin-main');
        if (mainContent && !mainContent.id) {
            mainContent.id = 'main-content';
        }
        
        // Enhance keyboard navigation
        this.initializeKeyboardNavigation();
    }
    
    initializeKeyboardNavigation() {
        // Trap focus in modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Close any open modals or dropdowns
                const modals = document.querySelectorAll('.modal.active, .dropdown.open');
                modals.forEach(modal => {
                    modal.classList.remove('active', 'open');
                });
            }
        });
        
        // Add focus indicators for keyboard users
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-user');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-user');
        });
    }
}

// Password Toggle Functionality - Working Implementation
function initPasswordToggles() {
    // Remove any existing listeners first
    document.removeEventListener('click', handlePasswordToggleClick);
    
    // Add global click listener for password toggles
    document.addEventListener('click', handlePasswordToggleClick);
    
    console.log('Password toggle system initialized');
}

function handlePasswordToggleClick(event) {
    // Check if clicked element is a password toggle button or its child
    const toggleButton = event.target.closest('.password-toggle');
    if (!toggleButton) return;
    
    event.preventDefault();
    event.stopPropagation();
    
    console.log('Password toggle button clicked!');
    
    // Find the password input in the same wrapper
    const wrapper = toggleButton.parentElement;
    const passwordInput = wrapper.querySelector('input[type="password"], input[type="text"]');
    const icon = toggleButton.querySelector('i');
    
    if (!passwordInput) {
        console.error('Could not find password input');
        return;
    }
    
    if (!icon) {
        console.error('Could not find toggle icon');
        return;
    }
    
    console.log('Before toggle - Input type:', passwordInput.type);
    console.log('Before toggle - Input value:', passwordInput.value);
    
    // Toggle the password visibility
    if (passwordInput.type === 'password') {
        // Show password
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        toggleButton.setAttribute('aria-label', 'Hide password');
        console.log('PASSWORD IS NOW VISIBLE AS TEXT');
    } else {
        // Hide password
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        toggleButton.setAttribute('aria-label', 'Show password');
        console.log('PASSWORD IS NOW HIDDEN');
    }
    
    console.log('After toggle - Input type:', passwordInput.type);
    
    // Keep focus on input
    passwordInput.focus();
}

// Legacy PasswordToggle class for compatibility
class PasswordToggle {
    constructor() {
        initPasswordToggles();
    }
}

// Enhanced Registration Form Validation System
class RegistrationValidator {
    constructor() {
        this.validationRules = {
            full_name: {
                pattern: /^[A-Za-z\s'.-]+$/,
                minLength: 2,
                message: 'Name must contain only letters, spaces, and common name characters (apostrophes, hyphens, periods)'
            },
            teacher_id: {
                pattern: /^[A-Z]{2}[0-9]+$|^[0-9]+$/,
                minLength: 4,
                message: 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers (minimum 4 characters total)'
            },
            student_id: {
                pattern: /^[0-9]{8}$/,
                minLength: 8,
                maxLength: 8,
                message: 'Student ID must be exactly 8 digits'
            }
        };
        
        this.passwordRequirements = {
            length: { test: (pwd) => pwd.length >= 8, message: 'At least 8 characters' },
            lowercase: { test: (pwd) => /[a-z]/.test(pwd), message: 'One lowercase letter (a-z)' },
            uppercase: { test: (pwd) => /[A-Z]/.test(pwd), message: 'One uppercase letter (A-Z)' },
            number: { test: (pwd) => /\d/.test(pwd), message: 'One number (0-9)' },
            special: { test: (pwd) => /[@$!%*?&]/.test(pwd), message: 'One special character (@$!%*?&)' }
        };
    }

    initializeForm(formContainer) {
        const form = formContainer.querySelector('form');
        if (!form) return;

        // Clear any existing validation messages
        this.clearAllValidationMessages(form);

        this.setupFormValidation(form);
        this.setupPasswordStrengthMeter(form);
        this.setupRealTimeValidation(form);
        this.preventInvalidSubmission(form);
    }

    setupFormValidation(form) {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            // Mark field as user-interacted when user starts typing
            input.addEventListener('input', () => {
                input.setAttribute('data-user-interacted', 'true');
                this.validateField(input);
            });
            input.addEventListener('blur', () => {
                input.setAttribute('data-user-interacted', 'true');
                this.validateField(input);
            });
        });
    }

    setupPasswordStrengthMeter(form) {
        const passwordInputs = form.querySelectorAll('input[name="password"]');
        passwordInputs.forEach(input => {
            const role = input.id.includes('teacher') ? 'teacher' : 'student';
            const strengthMeter = document.getElementById(`${role}_password_strength`);
            const requirements = document.getElementById(`${role}_password_requirements`);
            
            if (strengthMeter && requirements) {
                input.addEventListener('input', () => {
                    this.updatePasswordStrength(input, strengthMeter, requirements);
                });
                
                input.addEventListener('focus', () => {
                    // Only show if there's content in the password field
                    if (input.value && input.value.trim() !== '') {
                        strengthMeter.style.display = 'block';
                        requirements.style.display = 'block';
                    }
                });
                
                input.addEventListener('blur', () => {
                    // Hide if password field is empty when user leaves
                    if (!input.value || input.value.trim() === '') {
                        strengthMeter.style.display = 'none';
                        requirements.style.display = 'none';
                    }
                });
            }
        });
    }

    setupRealTimeValidation(form) {
        const confirmPasswordInputs = form.querySelectorAll('input[name="confirm_password"]');
        confirmPasswordInputs.forEach(input => {
            const role = input.id.includes('teacher') ? 'teacher' : 'student';
            const messageElement = document.getElementById(`${role}_confirm_password_message`);
            
            input.addEventListener('input', () => {
                this.validatePasswordMatch(input, messageElement);
            });
        });

        // Setup duplicate ID checking for student and instructor ID fields
        const idInputs = form.querySelectorAll('input[name="student_id"], input[name="teacher_id"]');
        idInputs.forEach(input => {
            let idCheckTimeout;
            
            // Check on blur (when user leaves the field)
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            // Check on input with debouncing (wait for user to stop typing)
            input.addEventListener('input', () => {
                clearTimeout(idCheckTimeout);
                idCheckTimeout = setTimeout(() => {
                    const value = input.value.trim();
                    if (value.length === 8 && /^\d{8}$/.test(value)) {
                        this.validateField(input);
                    }
                }, 500); // Wait 500ms after user stops typing
            });
        });
    }

    preventInvalidSubmission(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) return;

        const validateForm = () => {
            const isValid = this.isFormValid(form);
            submitButton.disabled = !isValid;
            submitButton.classList.toggle('disabled', !isValid);
        };

        form.addEventListener('input', validateForm);
        form.addEventListener('change', validateForm);
        setTimeout(validateForm, 100);

        form.addEventListener('submit', (e) => {
            if (!this.isFormValid(form)) {
                e.preventDefault();
                this.showFormErrors(form);
                return false;
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        const rule = this.validationRules[fieldName];
        
        if (!rule) return true;

        // For full_name field: only show error if user has entered invalid characters
        if (fieldName === 'full_name') {
            if (!value) {
                // Empty field - no error message
                this.updateFieldValidation(field, true, '');
                return true;
            }
            
            // Check if the input contains invalid characters (numbers or disallowed special chars)
            const hasInvalidChars = /[0-9@#$%^&*()+=\[\]{};':"\\|,.<>\/?~`]/.test(value);
            
            if (hasInvalidChars) {
                // Show error only if invalid characters are present
                this.updateFieldValidation(field, false, rule.message);
                return false;
            } else {
                // Valid characters - no error message
                this.updateFieldValidation(field, true, '');
                return true;
            }
        }

        // For other fields, use the original logic
        const meetsPattern = rule.pattern.test(value);
        const meetsMinLength = value.length >= (rule.minLength || 0);
        const meetsMaxLength = !rule.maxLength || value.length <= rule.maxLength;
        
        const isValid = meetsPattern && meetsMinLength && meetsMaxLength;
        
        // Special handling for instructor ID to show green when 2 letters are entered
        if (fieldName === 'teacher_id') {
            const hasTwoLetters = /^[A-Z]{2}/.test(value);
            field.classList.toggle('valid-two-letters', hasTwoLetters);
        }
        
        // Check for duplicate ID if this is a student_id or teacher_id field
        if (isValid && (fieldName === 'student_id' || fieldName === 'teacher_id')) {
            this.checkDuplicateId(field, value, fieldName);
            return true; // Return true for now, async validation will update later
        }
        
        this.updateFieldValidation(field, isValid, isValid ? '' : rule.message);
        
        return isValid;
    }

    async checkDuplicateId(field, id, fieldName) {
        // Only check if ID has the correct format and length
        if (!id || id.length !== 8 || !/^\d{8}$/.test(id)) {
            return;
        }

        const role = fieldName === 'student_id' ? 'student' : 'teacher';
        
        try {
            console.log('Checking duplicate ID:', id, 'for role:', role);
            
            const response = await fetch('api/check-duplicate-id.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    role: role
                })
            });

            console.log('Response status:', response.status);

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            const result = await response.json();
            console.log('API result:', result);
            
            if (result.isDuplicate) {
                this.updateFieldValidation(field, false, result.message);
                // Mark field as having duplicate ID for form validation
                field.setAttribute('data-duplicate-id', 'true');
                console.log('Duplicate ID found, field marked as invalid');
            } else {
                this.updateFieldValidation(field, true, '');
                // Remove duplicate ID marker
                field.removeAttribute('data-duplicate-id');
                console.log('ID is available, field marked as valid');
            }
            
        } catch (error) {
            console.error('Error checking duplicate ID:', error);
            // On error, don't block registration but log the issue
            field.removeAttribute('data-duplicate-id');
        }
    }

    updatePasswordStrength(passwordInput, strengthMeter, requirementsContainer) {
        const password = passwordInput.value;
        
        // Hide meter and requirements if password is empty
        if (!password || password.trim() === '') {
            strengthMeter.style.display = 'none';
            requirementsContainer.style.display = 'none';
            return false;
        }
        
        // Show meter and requirements if password has content
        strengthMeter.style.display = 'block';
        requirementsContainer.style.display = 'block';
        
        const results = {};
        let metCount = 0;

        Object.keys(this.passwordRequirements).forEach(key => {
            const requirement = this.passwordRequirements[key];
            results[key] = requirement.test(password);
            if (results[key]) metCount++;
        });

        const strengthLevel = this.getStrengthLevel(metCount);
        const strengthFill = strengthMeter.querySelector('.password-strength-fill');
        const strengthText = strengthMeter.querySelector('.strength-level');
        
        strengthFill.className = `password-strength-fill ${strengthLevel}`;
        strengthText.textContent = this.formatStrengthLabel(strengthLevel);
        strengthText.parentElement.parentElement.className = `password-strength-text ${strengthLevel}`;

        Object.keys(results).forEach(key => {
            const requirementItem = requirementsContainer.querySelector(`[data-requirement="${key}"]`);
            if (requirementItem) {
                const icon = requirementItem.querySelector('.requirement-icon');
                const iconElement = icon.querySelector('i');
                
                if (results[key]) {
                    requirementItem.classList.add('met');
                    requirementItem.classList.remove('unmet');
                    icon.classList.add('met');
                    icon.classList.remove('unmet');
                    iconElement.className = 'fas fa-check';
                } else {
                    requirementItem.classList.add('unmet');
                    requirementItem.classList.remove('met');
                    icon.classList.add('unmet');
                    icon.classList.remove('met');
                    iconElement.className = 'fas fa-times';
                }
            }
        });

        return metCount === 5;
    }

    validatePasswordMatch(confirmInput, messageElement) {
        const form = confirmInput.closest('form');
        const passwordInput = form.querySelector('input[name="password"]');
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;
        
        const isMatch = password === confirmPassword && confirmPassword.length > 0;
        const isEmpty = confirmPassword.length === 0;
        
        // Clear any existing timeout to prevent auto-dismiss
        if (messageElement.dismissTimeout) {
            clearTimeout(messageElement.dismissTimeout);
            messageElement.dismissTimeout = null;
        }
        
        if (isEmpty) {
            messageElement.style.display = 'none';
        } else {
            messageElement.style.display = 'block';
            messageElement.className = `validation-message ${isMatch ? 'success' : 'error'}`;
            messageElement.innerHTML = isMatch 
                ? '<i class="fas fa-check-circle"></i> Passwords match!'
                : '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
            
            // Reset any fade-out styles
            messageElement.style.opacity = '1';
            messageElement.style.transform = 'translateY(0)';
        }
        
        return isMatch || isEmpty;
    }

    getStrengthLevel(metCount) {
        if (metCount <= 2) return 'poor';
        if (metCount <= 3) return 'strong';
        return 'very-strong';
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    formatStrengthLabel(str) {
        // Convert tokens like 'very-strong' -> 'Very Strong'
        return str
            .split('-')
            .map(part => this.capitalizeFirst(part))
            .join(' ');
    }

    isFormValid(form) {
        const requiredFields = form.querySelectorAll('input[required], select[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            const value = field.value.trim();
            
            if (!value) {
                isValid = false;
                return;
            }

            const rule = this.validationRules[field.name];
            if (rule) {
                const meetsPattern = rule.pattern.test(value);
                const meetsMinLength = value.length >= (rule.minLength || 0);
                const meetsMaxLength = !rule.maxLength || value.length <= rule.maxLength;
                
                if (!meetsPattern || !meetsMinLength || !meetsMaxLength) {
                    isValid = false;
                    return;
                }
            }

            // Check for duplicate ID marker
            if (field.hasAttribute('data-duplicate-id')) {
                isValid = false;
                return;
            }

            if (field.name === 'confirm_password') {
                const passwordInput = form.querySelector('input[name="password"]');
                if (value !== passwordInput.value) {
                    isValid = false;
                    return;
                }
            }
        });

        return isValid;
    }

    updateFieldValidation(field, isValid, message) {
        field.classList.toggle('valid', isValid);
        field.classList.toggle('invalid', !isValid);

        const fieldGroup = field.closest('.form-group');
        let messageElement = fieldGroup.querySelector('.validation-message');
        
        if (message && !isValid) {
            // Clear all other validation messages first to prevent stacking
            this.clearOtherValidationMessages(field);
            
            // Clear any existing timeout first
            if (messageElement && messageElement.dismissTimeout) {
                clearTimeout(messageElement.dismissTimeout);
            }
            
            if (!messageElement) {
                messageElement = document.createElement('div');
                messageElement.className = 'validation-message error';
                field.parentNode.insertAdjacentElement('afterend', messageElement);
            }
            
            // Only update if message is different to prevent flashing
            const newContent = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            if (messageElement.innerHTML !== newContent) {
                messageElement.innerHTML = newContent;
            }
            
            // Reset styles and show message
            messageElement.style.display = 'block';
            messageElement.style.opacity = '1';
            messageElement.style.transform = 'translateY(0)';
            
            // Auto-dismiss after 4 seconds
            messageElement.dismissTimeout = setTimeout(() => {
                if (messageElement && messageElement.parentNode) {
                    messageElement.style.opacity = '0';
                    messageElement.style.transform = 'translateY(-5px)';
                    setTimeout(() => {
                        if (messageElement && messageElement.parentNode) {
                            messageElement.style.display = 'none';
                            messageElement.style.opacity = '';
                            messageElement.style.transform = '';
                        }
                    }, 300);
                }
            }, 4000);
        } else if (messageElement && field.name !== 'confirm_password') {
            // Clear timeout if hiding message manually
            if (messageElement.dismissTimeout) {
                clearTimeout(messageElement.dismissTimeout);
            }
            messageElement.style.opacity = '0';
            messageElement.style.transform = 'translateY(-5px)';
            setTimeout(() => {
                if (messageElement && messageElement.parentNode) {
                    messageElement.style.display = 'none';
                    messageElement.style.opacity = '';
                    messageElement.style.transform = '';
                }
            }, 300);
        }
        
        // Special handling for confirm_password - don't create duplicate messages
        if (field.name === 'confirm_password') {
            // Remove any dynamically created validation messages for confirm password
            const dynamicMessages = fieldGroup.querySelectorAll('.validation-message:not([id])');
            dynamicMessages.forEach(msg => msg.remove());
        }
    }

    clearOtherValidationMessages(currentField) {
        const form = currentField.closest('form');
        if (!form) return;
        
        const allValidationMessages = form.querySelectorAll('.validation-message.error');
        allValidationMessages.forEach(msg => {
            const msgField = msg.closest('.form-group').querySelector('input, select');
            // Only clear messages from other fields, not the current one or confirm password
            if (msgField && msgField !== currentField && msgField.name !== 'confirm_password') {
                if (msg.dismissTimeout) {
                    clearTimeout(msg.dismissTimeout);
                }
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-5px)';
                setTimeout(() => {
                    if (msg && msg.parentNode) {
                        msg.style.display = 'none';
                        msg.style.opacity = '';
                        msg.style.transform = '';
                    }
                }, 300);
            }
        });
    }

    clearAllValidationMessages(form) {
        const allMessages = form.querySelectorAll('.validation-message');
        allMessages.forEach(message => {
            message.remove();
        });
        
        // Also clear any validation classes from inputs
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.classList.remove('valid', 'invalid', 'valid-two-letters');
            input.removeAttribute('data-user-interacted');
        });
    }

    showFormErrors(form) {
        const errors = this.getFormErrors(form);
        let errorSummary = form.querySelector('.form-errors-summary');
        
        if (!errorSummary) {
            errorSummary = document.createElement('div');
            errorSummary.className = 'form-errors-summary';
            form.insertAdjacentElement('afterbegin', errorSummary);
        }

        errorSummary.innerHTML = `
            <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
            <ul>
                ${errors.map(error => `<li>${error}</li>`).join('')}
            </ul>
        `;
        
        errorSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    getFormErrors(form) {
        const errors = [];
        const inputs = form.querySelectorAll('input[required], select[required]');

        inputs.forEach(input => {
            const fieldName = input.name;
            const value = input.value.trim();
            const label = input.closest('.form-group').querySelector('.form-label').textContent.replace('*', '').trim();

            if (!value) {
                errors.push(`${label} is required`);
                return;
            }

            if (this.validationRules[fieldName]) {
                const rule = this.validationRules[fieldName];
                if (!rule.pattern.test(value) || value.length < (rule.minLength || 0)) {
                    errors.push(`${label}: ${rule.message}`);
                }
            }

            if (fieldName === 'password') {
                const unmetRequirements = Object.entries(this.passwordRequirements)
                    .filter(([key, req]) => !req.test(value))
                    .map(([key, req]) => req.message);
                
                if (unmetRequirements.length > 0) {
                    errors.push(`Password must meet all requirements: ${unmetRequirements.join(', ')}`);
                }
            }

            if (fieldName === 'confirm_password') {
                const passwordInput = form.querySelector('input[name="password"]');
                if (value !== passwordInput.value) {
                    errors.push('Confirm Password must match the password');
                }
            }
        });

        return errors;
    }
}

class FormHandler {
    constructor() {
        this.validator = new FormValidator();
        this.passwordToggle = new PasswordToggle();
        this.initEventListeners();
    }

    initEventListeners() {
        // Form submission
        document.addEventListener('submit', (e) => {
            if (e.target.matches('form')) {
                this.handleFormSubmit(e);
            }
        });

        // Real-time validation
        document.addEventListener('blur', (e) => {
            if (e.target.matches('input[data-validate], select[data-validate]')) {
                const validateType = e.target.getAttribute('data-validate');
                this.validator.validateField(e.target, validateType);
            }
        }, true);

        // Role selection
        document.addEventListener('click', (e) => {
            if (e.target.matches('.role-card')) {
                this.handleRoleSelection(e.target);
            }
        });

        // Dynamic form loading
        const roleCards = document.querySelectorAll('.role-card');
        roleCards.forEach(card => {
            card.addEventListener('click', () => {
                const role = card.getAttribute('data-role');
                this.loadRoleForm(role);
            });
        });
    }

    handleFormSubmit(e) {
        const form = e.target;
        // Only intercept forms that explicitly opt-in to AJAX via data-action
        const dataAction = form.getAttribute('data-action');
        if (!dataAction) {
            // Allow normal HTML form submission/redirect handled by PHP
            return;
        }

        e.preventDefault();

        if (!this.validator.validateForm(form)) {
            this.showAlert('Please correct the errors above', 'error');
            return;
        }

        this.submitForm(form);
    }

    async submitForm(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            // Use data-action for AJAX endpoint
            const action = form.getAttribute('data-action');
            
            console.log('Submitting form to:', action);
            console.log('Form data:', Object.fromEntries(formData));
            console.log('Form ID:', form.id);
            
            const response = await fetch(action, {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers));
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
                console.log('Parsed result:', result);
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
                this.showAlert('Invalid server response. Please try again.', 'error');
                return;
            }
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                
                // Redirect if specified
                if (result.redirect) {
                    console.log('Redirecting to:', result.redirect);
                    
                    // Force cache clearing before redirect
                    this.clearBrowserCache();
                    
                    setTimeout(() => {
                        console.log('Executing redirect to:', result.redirect);
                        // Use replace instead of href to prevent back button issues
                        window.location.replace(result.redirect);
                    }, 1000);
                } else if (form.id === 'loginForm' && result.user && result.user.role) {
                    // Fallback for login form if no redirect specified but we have user role
                    console.log('No redirect URL provided, using fallback for role:', result.user.role);
                    const dashboardUrl = this.getDashboardUrl(result.user.role);
                    console.log('Fallback dashboard URL:', dashboardUrl);
                    
                    // Force cache clearing before redirect
                    this.clearBrowserCache();
                    
                    setTimeout(() => {
                        console.log('Executing fallback redirect to dashboard:', dashboardUrl);
                        // Use replace instead of href to prevent back button issues
                        window.location.replace(dashboardUrl);
                    }, 1000);
                }
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showAlert('An error occurred. Please try again.', 'error');
        } finally {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    getDashboardUrl(role) {
        // Get the base URL for proper path resolution
        const baseUrl = window.location.protocol + '//' + window.location.host;
        const projectPath = '/nls2'; // Adjust this if your project is in a different directory
        
        const dashboards = {
            'admin': baseUrl + projectPath + '/dashboards/admin/dashboard.php',
            'teacher': baseUrl + projectPath + '/dashboards/teacher/dashboard.php',
            'student': baseUrl + projectPath + '/dashboards/student/dashboard.php'
        };
        console.log('Dashboard URL for role', role, ':', dashboards[role]);
        return dashboards[role] || baseUrl + projectPath + '/index.php';
    }
    
    clearBrowserCache() {
        // Add cache-busting parameter to prevent caching issues
        console.log('Clearing browser cache before redirect');
        
        // Force reload of cached resources
        if (window.performance && window.performance.navigation) {
            if (window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
                console.log('Detected back/forward navigation, forcing fresh page load');
                window.location.reload(true);
            }
        }
        
        // Clear any service worker caches if applicable
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    console.log('Clearing cache:', name);
                    caches.delete(name);
                });
            });
        }
    }

    handleRoleSelection(card) {
        // Remove active class from all cards
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
        
        // Add active class to selected card
        card.classList.add('active');
        
        // Set hidden role input if exists
        const roleInput = document.querySelector('input[name="role"]');
        if (roleInput) {
            roleInput.value = card.getAttribute('data-role');
        }
    }

    loadRoleForm(role) {
        const formContainer = document.getElementById('dynamicForm');
        if (!formContainer) return;

        // Show loading
        formContainer.innerHTML = '<div class="text-center">Loading form...</div>';

        // Load role-specific form
        fetch(`forms/${role}-form.php`)
            .then(response => response.text())
            .then(html => {
                formContainer.innerHTML = html;
                // Reinitialize password toggles for new form
                initPasswordToggles();
                // Initialize enhanced validation system
                if (!this.registrationValidator) {
                    this.registrationValidator = new RegistrationValidator();
                }
                this.registrationValidator.initializeForm(formContainer);
            })
            .catch(error => {
                console.error('Error loading form:', error);
                formContainer.innerHTML = '<div class="alert alert-error">Error loading form. Please try again.</div>';
            });
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => this.dismissAlert(alert));

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        
        const icon = this.getAlertIcon(type);
        const closeButton = '<button type="button" class="alert-close" aria-label="Close alert"><i class="fas fa-times"></i></button>';
        alert.innerHTML = `${icon} ${message} ${closeButton}`;

        // Add close button event listener
        const closeBtn = alert.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => this.dismissAlert(alert));

        // Insert at top of form or body
        const form = document.querySelector('form');
        const target = form || document.body;
        
        if (form) {
            form.insertBefore(alert, form.firstChild);
        } else {
            target.insertBefore(alert, target.firstChild);
        }

        // Trigger fade-in animation
        setTimeout(() => alert.classList.add('show'), 10);

        // Auto remove after 4 seconds (shorter for better UX)
        setTimeout(() => {
            if (alert.parentNode) {
                this.dismissAlert(alert);
            }
        }, 4000);
    }

    dismissAlert(alert) {
        if (!alert || !alert.parentNode) return;
        
        alert.classList.add('fade-out');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 300); // Wait for fade-out animation
    }

    getAlertIcon(type) {
        const icons = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>',
            'info': '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons['info'];
    }
}

// Utility functions
function showLoading(element, text = 'Loading...') {
    element.innerHTML = text;
}

function hideLoading(element, originalContent) {
    element.innerHTML = originalContent;
}

function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Collapsible Sidebar Class
class SidebarManager {
    constructor() {
        this.sidebar = document.querySelector('.admin-sidebar');
        this.mainContent = document.querySelector('.admin-main');
        this.toggleButton = document.querySelector('.sidebar-toggle');
        this.isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true' || window.innerWidth <= 1024;
        
        // Expose instance globally for other helpers to use
        try { window.SidebarManagerInstance = this; } catch (e) {}

        this.init();
    }
    
    init() {
        if (!this.sidebar || !this.mainContent) {
            console.log('Sidebar or main content not found');
            return;
        }
        
        console.log('SidebarManager initializing...');
        
        // Create toggle button if it doesn't exist
        if (!this.toggleButton) {
            this.createToggleButton();
        }
        
        // Set initial state
        this.updateSidebarState();
        
        // Add event listeners
        this.toggleButton.addEventListener('click', () => this.toggleSidebar());
        
        // Handle window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Handle outside clicks
        document.addEventListener('click', (e) => this.handleOutsideClick(e));
        
        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth <= 1024 && !this.isCollapsed) {
                this.toggleSidebar();
            }
        });

        // Add click handlers for navigation links to auto-collapse sidebar
        this.addNavigationLinkHandlers();
        
        // Re-initialize navigation handlers when DOM changes (for dynamic content)
        this.observeNavigationChanges();

        // Mark sidebar manager ready to avoid duplicate toggle bindings
        try {
            document.body.dataset.sidebarManagerReady = '1';
        } catch (e) {}
        
        console.log('SidebarManager initialized successfully');
    }
    
    createToggleButton() {
        this.toggleButton = document.createElement('button');
        this.toggleButton.className = 'sidebar-toggle';
        this.toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        this.toggleButton.setAttribute('aria-label', 'Toggle sidebar');
        this.toggleButton.setAttribute('title', 'Toggle sidebar');
        document.body.appendChild(this.toggleButton);
    }
    
    toggleSidebar() {
        this.isCollapsed = !this.isCollapsed;
        this.updateSidebarState();
        
        // Store preference in localStorage
        localStorage.setItem('sidebarCollapsed', this.isCollapsed.toString());
        
        // Announce to screen readers
        const announcement = this.isCollapsed ? 'Sidebar collapsed' : 'Sidebar expanded';
        this.announceToScreenReader(announcement);
    }
    
    updateSidebarState() {
        if (window.innerWidth <= 1024) {
            // Mobile behavior
            this.sidebar.classList.toggle('mobile-open', !this.isCollapsed);
            this.sidebar.classList.remove('collapsed');
            this.mainContent.classList.remove('sidebar-collapsed');
            this.toggleButton.classList.toggle('sidebar-open', !this.isCollapsed);
        } else {
            // Desktop behavior
            this.sidebar.classList.toggle('collapsed', this.isCollapsed);
            this.sidebar.classList.remove('mobile-open');
            this.mainContent.classList.toggle('sidebar-collapsed', this.isCollapsed);
            this.toggleButton.classList.toggle('sidebar-open', !this.isCollapsed);
        }
        
        // Update toggle button icon
        const icon = this.toggleButton.querySelector('i');
        if (icon) {
            icon.className = this.isCollapsed ? 'fas fa-bars' : 'fas fa-times';
        }
        
        // Update aria-expanded attribute
        this.sidebar.setAttribute('aria-expanded', (!this.isCollapsed).toString());
    }
    
    handleResize() {
        const wasMobile = this.sidebar.classList.contains('mobile-open');
        const isMobile = window.innerWidth <= 1024;
        
        if (wasMobile && !isMobile) {
            // Switching from mobile to desktop
            this.isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        } else if (!wasMobile && isMobile) {
            // Switching from desktop to mobile
            this.isCollapsed = true;
        }
        
        this.updateSidebarState();
    }
    
    handleOutsideClick(e) {
        if (window.innerWidth <= 1024 && !this.isCollapsed) {
            if (!this.sidebar.contains(e.target) && !this.toggleButton.contains(e.target)) {
                this.isCollapsed = true;
                this.updateSidebarState();
            }
        }
    }
    
    addNavigationLinkHandlers() {
        console.log('Adding navigation link handlers...');
        
        // Get all navigation links in the sidebar - include both nav-link class and any anchor tags
        const navLinks = this.sidebar.querySelectorAll('.nav-link, a[href], .nav-item a');
        console.log('Found', navLinks.length, 'navigation links');
        
        navLinks.forEach((link, index) => {
            console.log(`Setting up handler for link ${index + 1}:`, link.textContent.trim());
            
            // Skip logout links and external links
            const href = link.getAttribute('href');
            if (!href || href.includes('logout') || href.startsWith('http') || href.startsWith('mailto')) {
                return;
            }
            
            // Remove any existing listeners to avoid duplicates
            const newLink = link.cloneNode(true);
            link.parentNode.replaceChild(newLink, link);
            
            newLink.addEventListener('click', (e) => {
                console.log('Navigation link clicked:', newLink.textContent.trim());
                
                // Always auto-collapse sidebar when navigating to different admin sections
                // This provides better UX by giving more screen space for the new content
                this.collapseSidebar();
            });
        });
        
        // Also handle dashboard switcher links
        const switcherLinks = document.querySelectorAll('.dashboard-switcher .switch-link');
        switcherLinks.forEach((link) => {
            const href = link.getAttribute('href');
            if (!href || href.includes('logout')) return;
            
            link.addEventListener('click', () => {
                console.log('Dashboard switcher link clicked:', link.textContent.trim());
                
                // Always collapse sidebar when switching dashboards
                this.collapseSidebar();
            });
        });
        
        console.log('Navigation link handlers added successfully');
    }
    
    observeNavigationChanges() {
        // Use MutationObserver to watch for dynamically added navigation links
        const observer = new MutationObserver((mutations) => {
            let shouldReinitialize = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    // Check if any added nodes contain navigation links
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const hasNavLinks = node.matches && (
                                node.matches('.nav-link, .nav-item, .dashboard-switcher') ||
                                node.querySelector('.nav-link, .nav-item, .dashboard-switcher')
                            );
                            if (hasNavLinks) {
                                shouldReinitialize = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReinitialize) {
                console.log('Navigation structure changed, reinitializing handlers...');
                setTimeout(() => this.addNavigationLinkHandlers(), 100);
            }
        });
        
        // Observe changes in the sidebar and main content areas
        if (this.sidebar) {
            observer.observe(this.sidebar, { childList: true, subtree: true });
        }
        if (this.mainContent) {
            observer.observe(this.mainContent, { childList: true, subtree: true });
        }
    }
    
    // Helper method to check if sidebar is actually visible
    isSidebarVisible() {
        if (window.innerWidth <= 1024) {
            // On mobile, check if sidebar has mobile-open class
            return this.sidebar.classList.contains('mobile-open');
        } else {
            // On desktop, sidebar is visible if NOT collapsed
            return !this.isCollapsed;
        }
    }
    
    // Helper method to collapse sidebar
    collapseSidebar() {
        this.isCollapsed = true;
        this.updateSidebarState();
        
        // Store preference in localStorage
        localStorage.setItem('sidebarCollapsed', this.isCollapsed.toString());
        
        // Announce to screen readers
        this.announceToScreenReader('Sidebar collapsed');
    }
    
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FormHandler();
    new SidebarManager();
    // Global sidebar toggle button for all admin pages
    ensureGlobalSidebarToggle();
    
    // Initialize tooltips
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
});
// Ensure a global toggle button exists and works across admin pages
function ensureGlobalSidebarToggle() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    if (!sidebar || !main) return;

    // Collect all possible toggle buttons (header and/or floating)
    let toggles = Array.from(document.querySelectorAll('#headerSidebarToggle, .sidebar-toggle'));

    // If none exist, create a floating one
    if (toggles.length === 0) {
        const created = document.createElement('button');
        created.className = 'sidebar-toggle';
        created.innerHTML = '<i class="fas fa-bars"></i>';
        created.setAttribute('aria-label', 'Toggle sidebar');
        document.body.appendChild(created);
        toggles = [created];
    }

    // Helper to sync a button's visual state
    const syncButtonState = (btn, collapsed) => {
        btn.classList.toggle('sidebar-open', !collapsed);
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = collapsed ? 'fas fa-bars' : 'fas fa-times';
        }
    };

    const manager = window.SidebarManagerInstance;
    const initialCollapsed = (manager && typeof manager.isCollapsed === 'boolean')
        ? manager.isCollapsed
        : (localStorage.getItem('sidebarCollapsed') === 'true' || window.innerWidth <= 1024);

    // Initialize visual state of all toggles
    toggles.forEach(btn => syncButtonState(btn, initialCollapsed));

    // Bind click handlers via cloning to remove any previously attached listeners
    toggles.forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (window.SidebarManagerInstance && typeof window.SidebarManagerInstance.toggleSidebar === 'function') {
                // Delegate to the single source of truth
                window.SidebarManagerInstance.toggleSidebar();

                // After toggle, sync all toggle buttons to current state
                const collapsedNow = window.SidebarManagerInstance.isCollapsed;
                document.querySelectorAll('#headerSidebarToggle, .sidebar-toggle').forEach(b => syncButtonState(b, collapsedNow));
            } else {
                // Fallback: minimal class toggle to keep UI usable until manager initializes
                const isCollapsed = sidebar.classList.toggle('collapsed');
                main.classList.toggle('sidebar-collapsed', isCollapsed);
                syncButtonState(newBtn, isCollapsed);
                localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
            }
        });
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Export for use in other scripts
window.FormValidator = FormValidator;
window.FormHandler = FormHandler;
