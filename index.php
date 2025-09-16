<?php
// Start output buffering early to prevent premature output that can break header redirects
ob_start();
session_start();
require_once 'classes/User.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent form resubmission on refresh by using POST-Redirect-GET pattern
    if (isset($_POST['login_submitted'])) {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($login_id) || empty($password)) {
        // Store validation error in session and redirect
        $_SESSION['login_error'] = 'Please fill in all fields';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Hidden admin login check (no database required) - MUST BE FIRST
        if ($login_id === '22120091' && $password === '@Admin1899') {
            // Strengthen session handling
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            session_regenerate_id(true);
            
            // Set admin session variables
            $_SESSION['user_id'] = '22120091';
            $_SESSION['full_name'] = 'System Administrator';
            
            $_SESSION['role'] = 'admin';
            $_SESSION['status'] = 'approved';
            $_SESSION['admin_id'] = '22120091';
            
            // Try header redirect first
            if (!headers_sent()) {
                header('Location: dashboards/admin/dashboard.php');
                exit();
            }
            // Fallback redirect if headers already sent (e.g., by server config)
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=dashboards/admin/dashboard.php" />'
                . '<script>window.location.replace("dashboards/admin/dashboard.php");</script>'
                . '</head><body>'
                . '<p>Redirecting to <a href="dashboards/admin/dashboard.php">Admin Dashboard</a>...</p>'
                . '</body></html>';
            exit();
        }
        
        // For non-admin users, use User class for database login
        $user = new User();
        $result = $user->login($login_id, $password, $_SERVER['REMOTE_ADDR']);
        
        if ($result['success']) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables for regular users
            $_SESSION['user_id'] = $result['user']['user_id'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            
            $_SESSION['role'] = $result['user']['role'];
            $_SESSION['status'] = $result['user']['status'];
            
            // Set role-specific session data
            if ($result['user']['role'] === 'teacher') {
                $_SESSION['teacher_id'] = $result['user']['teacher_id'] ?? '';
                $_SESSION['department'] = $result['user']['department'] ?? '';
            } elseif ($result['user']['role'] === 'student') {
                $_SESSION['student_id'] = $result['user']['student_id'] ?? '';
                $_SESSION['year_level'] = $result['user']['year_level'] ?? '';
                $_SESSION['section'] = $result['user']['section'] ?? '';
            } elseif ($result['user']['role'] === 'admin') {
                $_SESSION['admin_id'] = $result['user']['admin_id'] ?? '';
            }
            
            // Redirect to appropriate dashboard
            $dashboards = [
                'admin' => 'dashboards/admin/dashboard.php',
                'teacher' => 'dashboards/instructor/dashboard.php',
                'student' => 'dashboards/student/dashboard.php'
            ];
            $target = $dashboards[$result['user']['role']] ?? 'index.php';
            
            // Debug session before redirect
            error_log("Login successful for user: {$_SESSION['user_id']}, role: {$_SESSION['role']}, status: {$_SESSION['status']}");
            
            if (!headers_sent()) {
                header('Location: ' . $target);
                exit();
            }
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $target . '" />'
                . '<script>window.location.replace("' . $target . '");</script>'
                . '</head><body>'
                . '<p>Redirecting to <a href="' . $target . '">dashboard</a>...</p>'
                . '</body></html>';
            exit();
        } else {
            // Store error in session and redirect to prevent form resubmission
            $_SESSION['login_error'] = $result['message'];
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
    }
}

// Retrieve and clear login error from session
$error = null;
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error after retrieving it
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    $dashboards = [
        'admin' => 'dashboards/admin/dashboard.php',
        'teacher' => 'dashboards/instructor/dashboard.php',
        'student' => 'dashboards/student/dashboard.php'
    ];
    $target = $dashboards[$role] ?? 'index.php';
    if (!headers_sent()) {
        header('Location: ' . $target);
        exit();
    }
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $target . '" />'
        . '<script>window.location.replace("' . $target . '");</script>'
        . '</head><body>'
        . '<p>Redirecting to <a href="' . $target . '">dashboard</a>...</p>'
        . '</body></html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Scheduling System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 
                0 15px 30px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            height: auto;
            min-height: auto;
            display: block;
            position: relative;
        }

        .login-section {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            background: white;
            overflow: visible;
        }

        .form-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
            text-align: center;
            letter-spacing: -0.02em;
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 1rem;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            min-height: 48px;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-1px);
        }

        .form-input:disabled {
            background: rgba(243, 244, 246, 0.8);
            color: #9ca3af;
            cursor: not-allowed;
            border-color: rgba(209, 213, 219, 0.5);
        }

        .form-input:disabled::placeholder {
            color: #9ca3af;
        }

        .form-input::placeholder {
            color: #666;
            font-weight: 500;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .form-input:focus + .input-icon {
            color: #764ba2;
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #667eea;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
            padding: 4px;
            border-radius: 4px;
            background: none;
            border: none;
            outline: none;
            box-shadow: none;
        }

        .password-toggle:hover {
            color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle:focus {
            outline: none;
            box-shadow: none;
            border: none;
        }

        .password-toggle:active {
            outline: none;
            box-shadow: none;
            border: none;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .login-link p {
            margin: 0;
            font-size: 1rem;
            color: #555;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 10px 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            background: rgba(102, 126, 234, 0.05);
        }

        .login-link a:hover {
            color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
            border-color: rgba(102, 126, 234, 0.5);
            transform: translateY(-1px);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .modal-header i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-message p {
            font-size: 1.2rem;
            color: #dc3545;
            font-weight: 600;
            margin: 0;
            line-height: 1.5;
        }

        .modal-footer {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                max-width: 400px;
                margin: 10px;
            }

            .login-section {
                padding: 30px 25px;
            }

            .form-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
        <div class="container">
        <div class="login-section">
            <h2 class="form-title">Login</h2>
            <p class="form-subtitle">Enter your ID number and password to access your account</p>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" method="POST">
                        <input type="hidden" name="login_submitted" value="1">
                        <div class="form-group">
                    <label for="login_id" class="form-label">ID Number</label>
                    <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="login_id" 
                                name="login_id" 
                                class="form-input" 
                                placeholder="Enter your ID number"
                                required
                            >
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                        </div>

                        <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-input" 
                                    placeholder="Enter your password"
                                    required
                                >
                        <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                <button type="submit" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>

            <div class="login-link">
                <p>Don't have an account?</p>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Login Failed</h2>
            </div>
            <div class="error-message">
                <p id="errorMessage">Incorrect Username or password. Please try again.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-primary" onclick="closeErrorModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function initPasswordToggle() {
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordInput = document.getElementById('password');
            
            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    // Don't toggle if button is disabled
                    if (this.disabled) {
                        return;
                    }
                    
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = passwordToggle.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        }

        // Modal functionality
        function showErrorModal(message) {
            const modal = document.getElementById('errorModal');
            const errorMessage = document.getElementById('errorMessage');
            
            if (errorMessage) {
                errorMessage.textContent = message;
            }
            
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('errorModal');
            if (event.target === modal) {
                closeErrorModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeErrorModal();
            }
        });

        // Initialize form validation
        function initFormValidation() {
            const loginIdInput = document.getElementById('login_id');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');
            
            if (loginIdInput && passwordInput) {
                // Initially disable password field
                passwordInput.disabled = true;
                passwordInput.placeholder = 'Please enter your ID number first';
                
                // Initially disable password toggle
                if (passwordToggle) {
                    passwordToggle.disabled = true;
                    passwordToggle.style.opacity = '0.5';
                    passwordToggle.style.cursor = 'not-allowed';
                }
                
                // Add event listener to ID input
                loginIdInput.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        // Enable password field when ID is entered
                        passwordInput.disabled = false;
                        passwordInput.placeholder = 'Enter your password';
                        passwordInput.style.opacity = '1';
                        passwordInput.style.cursor = 'text';
                        
                        // Enable password toggle
                        if (passwordToggle) {
                            passwordToggle.disabled = false;
                            passwordToggle.style.opacity = '1';
                            passwordToggle.style.cursor = 'pointer';
                        }
                    } else {
                        // Disable password field when ID is empty
                        passwordInput.disabled = true;
                        passwordInput.placeholder = 'Please enter your ID number first';
                        passwordInput.value = '';
                        passwordInput.style.opacity = '0.6';
                        passwordInput.style.cursor = 'not-allowed';
                        
                        // Disable password toggle
                        if (passwordToggle) {
                            passwordToggle.disabled = true;
                            passwordToggle.style.opacity = '0.5';
                            passwordToggle.style.cursor = 'not-allowed';
                        }
                    }
                });
                
                // Add visual styling for disabled state
                passwordInput.style.transition = 'opacity 0.3s ease';
                if (passwordToggle) {
                    passwordToggle.style.transition = 'opacity 0.3s ease';
                }
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initPasswordToggle();
            initFormValidation();
            
            // Clear form fields on page load to prevent data persistence
            document.getElementById('login_id').value = '';
            document.getElementById('password').value = '';
            
            // Check for PHP error and show modal
            <?php if (isset($error)): ?>
                showErrorModal('<?php echo addslashes($error); ?>');
            <?php elseif (isset($_GET['error'])): ?>
                showErrorModal('<?php echo addslashes($_GET['error']); ?>');
            <?php endif; ?>
        });
    </script>
    <?php ob_end_flush(); ?>
</body>
</html>
