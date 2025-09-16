<form id="studentRegistrationForm" method="POST">
    <input type="hidden" name="role" value="student">
    
    <div class="form-group">
        <label for="student_full_name" class="form-label">
            <i class="fas fa-user"></i> Full Name *
        </label>
        <input 
            type="text" 
            id="student_full_name" 
            name="full_name" 
            class="form-input" 
            placeholder="Enter your full name"
            pattern="^[A-Za-z\s\'\.\-]+$"
            title="Full name must contain only letters, spaces, and common name characters (apostrophes, hyphens, periods)"
            required
        >
    </div>

    <div class="form-group">
        <label for="student_id" class="form-label">
            <i class="fas fa-id-badge"></i> Student ID Number *
        </label>
        <input 
            type="text" 
            id="student_id" 
            name="student_id" 
            class="form-input" 
            placeholder="Enter your 8-digit student ID number"
            pattern="^[0-9]{8}$"
            title="Student ID must be exactly 8 digits"
            maxlength="8"
            minlength="8"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)"
            required
        >
    </div>

    <div class="form-group">
        <label for="student_year_section" class="form-label">
            <i class="fas fa-graduation-cap"></i> Year & Section *
        </label>
        <select id="student_year_section" name="year_section" class="form-input" required onchange="window.updateYearAndSection(this.value)">
            <option value="">Select your year and section</option>
            <optgroup label="First Year">
                <option value="First Year_A">1A - First Year Section A</option>
                <option value="First Year_B">1B - First Year Section B</option>
            </optgroup>
            <optgroup label="Second Year">
                <option value="Second Year_A">2A - Second Year Section A</option>
                <option value="Second Year_B">2B - Second Year Section B</option>
            </optgroup>
            <optgroup label="Third Year">
                <option value="Third Year_A">3A - Third Year Section A</option>
                <option value="Third Year_B">3B - Third Year Section B</option>
            </optgroup>
            <optgroup label="Fourth Year">
                <option value="Fourth Year_A">4A - Fourth Year Section A</option>
                <option value="Fourth Year_B">4B - Fourth Year Section B</option>
            </optgroup>
        </select>
        <!-- Hidden fields for year_level and section -->
        <input type="hidden" id="year_level" name="year_level" value="">
        <input type="hidden" id="section" name="section" value="">
    </div>
    
    

    <div class="form-group">
        <label for="student_password" class="form-label">
            <i class="fas fa-lock"></i> Password *
        </label>
        <div class="password-input-wrapper">
            <input type="password" id="student_password" name="password" class="form-input" 
                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" 
                   title="Password must contain at least 8 characters with uppercase, lowercase, numbers, and special characters (@$!%*?&)" 
                   required>
            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <div class="password-strength-meter" id="student_password_strength" style="display: none;">
            <div class="password-strength-bar">
                <div class="password-strength-fill"></div>
            </div>
            <div class="password-strength-text">
                <i class="fas fa-shield-alt"></i>
                <span>Password Strength: <span class="strength-level">Weak</span></span>
            </div>
        </div>
        <div class="password-requirements" id="student_password_requirements" style="display: none;">
            <h4><i class="fas fa-list-check"></i> Password Requirements:</h4>
            <div class="requirement-item" data-requirement="length">
                <div class="requirement-icon unmet"><i class="fas fa-times"></i></div>
                <span>At least 8 characters</span>
            </div>
            <div class="requirement-item" data-requirement="lowercase">
                <div class="requirement-icon unmet"><i class="fas fa-times"></i></div>
                <span>One lowercase letter (a-z)</span>
            </div>
            <div class="requirement-item" data-requirement="uppercase">
                <div class="requirement-icon unmet"><i class="fas fa-times"></i></div>
                <span>One uppercase letter (A-Z)</span>
            </div>
            <div class="requirement-item" data-requirement="number">
                <div class="requirement-icon unmet"><i class="fas fa-times"></i></div>
                <span>One number (0-9)</span>
            </div>
            <div class="requirement-item" data-requirement="special">
                <div class="requirement-icon unmet"><i class="fas fa-times"></i></div>
                <span>One special character (@$!%*?&)</span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="student_confirm_password" class="form-label">
            <i class="fas fa-lock"></i> Confirm Password *
        </label>
        <div class="password-input-wrapper">
            <input type="password" id="student_confirm_password" name="confirm_password" class="form-input" 
                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" 
                   title="Password must match the password above" 
                   required>
            <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <div class="validation-message" id="student_confirm_password_message" style="display: none;"></div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-clock"></i>
        <strong>Student Registration:</strong> Your account will require admin approval before you can log in.
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary btn-block" onclick="handleStudentRegistration(event)">
            <i class="fas fa-user-plus"></i> Create Student Account
        </button>
    </div>
</form>

<!-- Registration Success Modal -->
<div id="registrationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-check-circle" style="color: #28a745;"></i> Registration Successful!</h2>
        </div>
        <div class="modal-body">
            <div class="success-message">
                <p><strong>Your student account has been created successfully!</strong></p>
                <p>However, your account is currently <span class="status-pending">pending approval</span> by an administrator.</p>
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                    <ul>
                        <li>An administrator will review your registration</li>
                        <li>You will receive approval or rejection notification</li>
                        <li>Once approved, you can login to your account</li>
                        <li>You will be redirected to your Student Dashboard</li>
                    </ul>
                </div>
                <p class="note"><i class="fas fa-clock"></i> This process usually takes 24-48 hours.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="goToLogin()">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeRegistrationModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fff;
    margin: auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 8px 8px 0 0;
    text-align: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.modal-body {
    padding: 25px;
}

.success-message {
    text-align: center;
}

.success-message p {
    margin-bottom: 15px;
    font-size: 1.1rem;
    line-height: 1.6;
}

.status-pending {
    background: #ffc107;
    color: #212529;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.info-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: left;
}

.info-box h4 {
    color: #495057;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
}

.info-box li {
    margin-bottom: 8px;
    color: #6c757d;
    line-height: 1.5;
}

.note {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 12px;
    margin-top: 20px;
    color: #0056b3;
    font-style: italic;
}

.modal-footer {
    padding: 20px;
    text-align: center;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.modal-footer .btn {
    margin: 0 5px;
    min-width: 120px;
}

.btn-primary {
    background: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    background: #545b62;
    border-color: #545b62;
}
</style>

<script>
async function checkDuplicateStudentId(studentId) {
    try {
        const res = await fetch('api/check-duplicate-id.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: String(studentId || '').trim(), role: 'student' })
        });
        const data = await res.json();
        return data?.isDuplicate === true ? (data.message || 'This ID number is already registered.') : '';
    } catch (e) {
        console.error('Duplicate check failed', e);
        return '';
    }
}

// Real-time duplicate check on blur
document.addEventListener('DOMContentLoaded', function() {
    const studentIdInput = document.getElementById('student_id');
    studentIdInput?.addEventListener('blur', async function() {
        const message = await checkDuplicateStudentId(this.value);
        if (message) {
            alert(message);
            this.focus();
        }
    });
});

function handleStudentRegistration(event) {
    event.preventDefault();
    
    const form = event.target.closest('form');
    const formData = new FormData(form);
    
    // Basic validation
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show loading state
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    submitBtn.disabled = true;

    // Duplicate check before submit
    const studentId = formData.get('student_id');
    checkDuplicateStudentId(studentId).then((message) => {
        if (message) {
            alert(message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            return;
        }
        // Submit form
        fetch('auth/register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            try {
                const data = JSON.parse(result);
                console.log('Registration response:', data);
                if (data.success) {
                    console.log('Registration successful, showing modal...');
                    
                    // Try to show modal
                    const modal = document.getElementById('registrationModal');
                    if (modal) {
                        modal.style.display = 'flex';
                        modal.style.visibility = 'visible';
                        modal.style.opacity = '1';
                        document.body.style.overflow = 'hidden';
                        console.log('Modal shown successfully');
                    } else if (typeof window.showRegistrationModal === 'function') {
                        console.log('Using window function');
                        window.showRegistrationModal();
                    } else {
                        console.log('Using fallback alert');
                        alert('Registration successful! Your account is pending admin approval. You will be redirected to the login page.');
                        window.location.href = 'index.php?success=' + encodeURIComponent(data.message);
                    }
                } else {
                    // Show error
                    console.log('Registration failed:', data.message);
                    alert('Registration failed: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (e) {
                console.log('JSON parse error:', e);
                console.log('Raw response:', result);
                // If response is not JSON, check if it's a redirect
                if (result.includes('success') || result.includes('Registration successful')) {
                    console.log('Success detected in response, attempting to show modal...');
                    if (typeof window.showRegistrationModal === 'function') {
                        console.log('showRegistrationModal function found, calling it...');
                        window.showRegistrationModal();
                    } else {
                        console.log('showRegistrationModal function not found, using fallback redirect');
                        window.location.href = 'index.php?success=Registration successful';
                    }
                } else {
                    console.log('No success detected, showing error');
                    alert('Registration failed. Please try again.');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Registration failed. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
}

// Global function to show registration modal
window.showRegistrationModal = function() {
    console.log('showRegistrationModal called');
    const modal = document.getElementById('registrationModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Registration modal displayed');
    } else {
        console.error('Registration modal not found');
    }
};

// Function to close registration modal
function closeRegistrationModal() {
    const modal = document.getElementById('registrationModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Function to go to login page
function goToLogin() {
    window.location.href = '../index.php';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('registrationModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};
</script>
