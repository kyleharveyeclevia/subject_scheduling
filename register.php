<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    $dashboards = [
        'admin' => 'dashboards/admin/dashboard.php',
        'teacher' => 'dashboards/teacher/dashboard.php',
        'student' => 'dashboards/student/dashboard.php'
    ];
    header('Location: ' . ($dashboards[$role] ?? 'index.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Scheduling System - Register</title>
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
            max-width: 800px;
            height: auto;
            min-height: auto;
            display: block;
            position: relative;
        }




        .right-section {
            padding: 15px 30px;
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

        .form-group {
            margin-bottom: 4px;
            position: relative;
        }

        .form-group.no-margin {
            margin-bottom: 0px;
        }

        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 4px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .container {
                max-width: 95%;
                height: auto;
                min-height: 100vh;
            }
            
            .right-section {
                padding: 25px 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-row .form-group {
                margin-bottom: 18px;
            }
        }

        .form-label {
            display: block;
            font-weight: 700;
            color: #333;
            margin-bottom: 3px;
            font-size: 0.95rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            font-size: 1.3rem;
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
            box-shadow: 
                0 0 0 4px rgba(102, 126, 234, 0.2),
                0 8px 25px rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #666;
            font-weight: 500;
        }

        select.form-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding: 12px 36px 12px 16px;
        }

        select.form-input option {
            padding: 10px 14px;
            font-size: 1.3rem;
            background: white;
            color: #333;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.3rem;
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
            font-size: 1.3rem;
            transition: all 0.3s ease;
            z-index: 2;
            padding: 4px;
            border-radius: 4px;
        }

        .password-toggle:hover {
            color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-50%) scale(1.1);
        }

        .password-meter {
            margin-top: 8px;
        }

        .password-strength {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
        }

        .strength-bar {
            height: 6px;
            flex: 1;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 3px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .strength-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0;
            transition: width 0.4s ease;
            border-radius: 3px;
        }

        .strength-bar.weak::before {
            width: 100%;
            background: linear-gradient(90deg, #ff6b6b, #ee5a52);
        }

        .strength-bar.fair::before {
            width: 100%;
            background: linear-gradient(90deg, #ffd93d, #ffb347);
        }

        .strength-bar.good::before {
            width: 100%;
            background: linear-gradient(90deg, #4ecdc4, #44a08d);
        }

        .strength-bar.strong::before {
            width: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
        }

        .password-requirements {
            font-size: 1.2rem;
            color: #6c757d;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            padding: 0 16px;
            margin-top: 8px;
        }

        .password-requirements.show {
            max-height: 200px;
            padding: 16px;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .requirement i {
            margin-right: 12px;
            font-size: 1rem;
            width: 16px;
            transition: all 0.3s ease;
        }

        .requirement.met {
            color: #28a745;
            font-weight: 600;
        }

        .requirement.met i {
            transform: scale(1.1);
        }

        .requirement.unmet {
            color: #dc3545;
        }

        .password-error {
            color: #dc3545;
            font-size: 1.1rem;
            margin-top: 8px;
            display: none;
        }

        .password-error.show {
            display: block;
        }

        .password-error.password-first {
            color: #dc3545;
            font-weight: 600;
        }

        .login-link {
            text-align: center;
            margin-top: 5px;
            padding-top: 8px;
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
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 5px;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .role-selection {
            display: flex;
            gap: 10px;
            margin-bottom: 40px;
            justify-content: center;
            background: rgba(102, 126, 234, 0.05);
            padding: 6px;
            border-radius: 10px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .role-btn {
            flex: 1;
            padding: 14px 28px;
            border: 2px solid transparent;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            font-weight: 700;
            position: relative;
            overflow: hidden;
            color: #555;
            font-size: 1.3rem;
        }

        .role-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .role-btn:hover::before {
            left: 100%;
        }

        .role-btn:hover {
            border-color: rgba(102, 126, 234, 0.3);
            color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .role-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .role-btn i {
            color: #667eea;
            transition: all 0.3s ease;
        }

        .role-btn.active i {
            color: white;
        }

        .role-btn:hover i {
            color: #764ba2;
        }

        .form-section {
            display: none;
            flex-direction: column;
            height: auto;
        }

        .form-section.active {
            display: flex;
        }

        .form-content {
            flex: none;
        }

        .form-footer {
            margin-top: 20px;
            padding-top: 20px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.3rem;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transform: scale(0.9) translateY(20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal.show .modal-content {
            transform: scale(1) translateY(0);
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .modal-body {
            text-align: center;
        }

        .success-message {
            margin-bottom: 20px;
        }

        .success-message p {
            margin-bottom: 15px;
            color: #555;
            line-height: 1.6;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .info-box h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }

        .info-box li {
            margin-bottom: 8px;
        }
        
        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Admin Approval Modal Specific Styling */
        #adminApprovalModal .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 249, 250, 0.95) 100%);
            border: 2px solid rgba(102, 126, 234, 0.2);
            box-shadow: 
                0 30px 60px rgba(102, 126, 234, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        #adminApprovalModal .modal-header h2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #adminApprovalModal .modal-header i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        #adminApprovalModal .success-message p {
            font-size: 1.2rem;
            color: #333;
            font-weight: 500;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        #adminApprovalModal .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
            overflow: hidden;
        }

        #adminApprovalModal .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        #adminApprovalModal .info-box h4 {
            color: #667eea;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        #adminApprovalModal .info-box h4 i {
            margin-right: 8px;
            color: #764ba2;
        }

        #adminApprovalModal .info-box ul {
            color: #555;
            font-size: 1rem;
        }

        #adminApprovalModal .info-box li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
        }

        #adminApprovalModal .info-box li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
            font-size: 1.1rem;
        }

        #adminApprovalModal .modal-footer {
            margin-top: 35px;
            gap: 20px;
        }

        #adminApprovalModal .btn-secondary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #adminApprovalModal .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        #adminApprovalModal .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #adminApprovalModal .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Error Modal Specific Styling */
        #errorModal .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 249, 250, 0.95) 100%);
            border: 2px solid rgba(220, 53, 69, 0.2);
            box-shadow: 
                0 30px 60px rgba(220, 53, 69, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        #errorModal .modal-header h2 {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #errorModal .modal-header i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        #errorModal .error-message p {
            font-size: 1.2rem;
            color: #dc3545;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 20px;
            text-align: center;
        }

        #errorModal .modal-footer {
            margin-top: 35px;
            gap: 20px;
        }

        #errorModal .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #errorModal .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        #errorModal .login-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #errorModal .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 400px;
            }

            .left-section {
                padding: 40px 30px;
            }

            .right-section {
                padding: 40px 30px;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .role-selection {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Forms Section -->
        <div class="right-section">
            <!-- Main Registration Header -->
            <h2 class="form-title">Student Registration</h2>
            
            <!-- Role Selection -->
            <div class="role-selection">
                <div class="role-btn active" onclick="selectRole('student')">
                    <i class="fas fa-user-graduate"></i> Student
                </div>
                <div class="role-btn" onclick="selectRole('teacher')">
                    <i class="fas fa-chalkboard-teacher"></i> Instructor
                </div>
            </div>

            <!-- Student Registration Form -->
            <div id="studentForm" class="form-section active">
                <form id="studentRegistrationForm">
                    <input type="hidden" name="role" value="student">
                    
                    <div class="form-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" pattern="[a-zA-ZÀ-ÿ\s']+" title="Please enter only letters, spaces, apostrophes, and accented characters" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Student ID Number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-id-badge input-icon"></i>
                                    <input type="text" name="student_id" class="form-input" placeholder="Enter Student ID number" pattern="[0-9]{2}-[0-9]-[0-9]-[0-9]{4}" title="Please enter student ID in format: xx-x-x-xxxx" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Year & Section</label>
                            <div class="input-wrapper">
                                <select name="year_section" class="form-input" required onchange="updateYearAndSection(this.value)">
                                    <option value="">Select your year and section</option>
                                    <option value="First Year_A">1A - First Year Section A</option>
                                    <option value="First Year_B">1B - First Year Section B</option>
                                    <option value="Second Year_A">2A - Second Year Section A</option>
                                    <option value="Second Year_B">2B - Second Year Section B</option>
                                    <option value="Third Year_A">3A - Third Year Section A</option>
                                    <option value="Third Year_B">3B - Third Year Section B</option>
                                    <option value="Fourth Year_A">4A - Fourth Year Section A</option>
                                    <option value="Fourth Year_B">4B - Fourth Year Section B</option>
                                </select>
                    </div>
                            <input type="hidden" id="year_level" name="year_level" value="">
                            <input type="hidden" id="section" name="section" value="">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="password" class="form-input" placeholder="Enter your password" required oninput="checkPasswordStrength(this)">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                    </div>
                            <div class="password-meter">
                                <div class="password-strength">
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                </div>
                                <div class="password-requirements">
                                    <div class="requirement unmet" id="req-length">
                                        <i class="fas fa-times"></i>
                                        <span>At least 8 characters</span>
                                    </div>
                                    <div class="requirement unmet" id="req-uppercase">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 uppercase letter</span>
                                    </div>
                                    <div class="requirement unmet" id="req-lowercase">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 lowercase letter</span>
                                    </div>
                                    <div class="requirement unmet" id="req-number">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 number</span>
                                    </div>
                                    <div class="requirement unmet" id="req-special">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 special character</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group no-margin">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your password" required oninput="checkPasswordMatch(this)">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div id="password-error-student" class="password-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                Passwords do not match
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="login-btn" onclick="handleStudentRegistration(event)">
                            <i class="fas fa-user-plus"></i> Create Student Account
                    </button>
                        
                        <div class="login-link">
                            <p>Already have an Account? <a href="index.php">Login</a></p>
                </div>
                    </div>
                </form>
            </div>
            
            <!-- Instructor Registration Form -->
            <div id="teacherForm" class="form-section">
                <form id="teacherRegistrationForm">
                    <input type="hidden" name="role" value="teacher">
                    
                <div class="form-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" pattern="[a-zA-ZÀ-ÿ\s']+" title="Please enter only letters, spaces, apostrophes, and accented characters" required>
                        </div>
                    </div>
                            <div class="form-group">
                                <label class="form-label">Instructor ID Number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-id-badge input-icon"></i>
                                    <input type="text" name="teacher_id" class="form-input" placeholder="Instructor ID number" pattern="[A-Z]{2}[0-9]+" title="Please enter 2 capital letters followed by numbers only" required oninput="validateInstructorId(this)">
                    </div>
                </div>
            </div>
            
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <div class="input-wrapper">
                                <select name="department" class="form-input" required>
                                    <option value="">Select Department</option>
                                    <option value="College of Communication and Information Technology">College of Communication and Information Technology</option>
                                    <option value="College of Teacher Education">College of Teacher Education</option>
                                </select>
                    </div>
                    </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="password" class="form-input" placeholder="Enter your password" required oninput="checkPasswordStrength(this)">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                </div>
                            <div class="password-meter">
                                <div class="password-strength">
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
            </div>
                                <div class="password-requirements">
                                    <div class="requirement unmet" id="req-length-teacher">
                                        <i class="fas fa-times"></i>
                                        <span>At least 8 characters</span>
                                    </div>
                                    <div class="requirement unmet" id="req-uppercase-teacher">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 uppercase letter</span>
                                    </div>
                                    <div class="requirement unmet" id="req-lowercase-teacher">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 lowercase letter</span>
                                    </div>
                                    <div class="requirement unmet" id="req-number-teacher">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 number</span>
                                    </div>
                                    <div class="requirement unmet" id="req-special-teacher">
                                        <i class="fas fa-times"></i>
                                        <span>At least 1 special character</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group no-margin">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your password" required oninput="checkPasswordMatch(this)">
                                <span class="password-toggle" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div id="password-error-teacher" class="password-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                Passwords do not match
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="login-btn" onclick="handleTeacherRegistration(event)">
                            <i class="fas fa-user-plus"></i> Create Instructor Account
                        </button>
                        
                        <div class="login-link">
                            <p>Already have an Account? <a href="index.php">Login</a></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Registration Success Modal -->
    <div id="registrationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-check-circle" style="color: #28a745; margin-right: 10px;"></i> Registration Successful!</h2>
            </div>
            <div class="modal-body">
                <div class="success-message">
                    <p><strong>Your account has been created successfully!</strong></p>
                    <p>However, your account is currently <span class="status-pending">pending approval</span> by an administrator.</p>
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                        <ul>
                            <li>An administrator will review your registration</li>
                            <li>You will receive approval or rejection notification</li>
                            <li>Once approved, you can login to your account</li>
                            <li>You will be redirected to your dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Close
                </button>
                <button class="login-btn" onclick="goToLogin()">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </button>
            </div>
        </div>
    </div>

    <!-- Admin Approval Modal -->
    <div id="adminApprovalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-clock" style="color: #ffc107; margin-right: 10px;"></i> Account Pending Approval</h2>
            </div>
            <div class="modal-body">
                <div class="success-message">
                    <p><strong id="approvalMessage">Your account will require admin approval before you can log in.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeAdminApprovalModal(); clearAllForms();">
                    <i class="fas fa-user-plus"></i> Register another account
                </button>
                <button class="login-btn" onclick="goToLogin()">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle" style="color: #dc3545; margin-right: 10px;"></i> Registration Failed</h2>
            </div>
            <div class="modal-body">
                <div class="error-message">
                    <p><strong id="errorMessage">Registration failed. Please try again.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="login-btn" onclick="closeErrorModal()">
                    <i class="fas fa-edit"></i> Edit Student ID Number
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentRole = 'student';

        // Initialize the page with correct header
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure the header matches the default active role (student)
            const mainHeader = document.querySelector('.right-section .form-title');
            mainHeader.textContent = 'Student Registration';
        });

        function selectRole(role) {
            currentRole = role;
            
            // Update role buttons
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update main header
            const mainHeader = document.querySelector('.right-section .form-title');
            if (role === 'student') {
                mainHeader.textContent = 'Student Registration';
            } else {
                mainHeader.textContent = 'Instructor Registration';
            }
            
            // Show/hide forms
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            document.getElementById(role + 'Form').classList.add('active');
        }


        function updateYearAndSection(value) {
            if (value) {
                const parts = value.split('_');
                if (parts.length === 2) {
                    document.getElementById('year_level').value = parts[0];
                    document.getElementById('section').value = parts[1];
                }
            }
        }

        function handleStudentRegistration(event) {
            event.preventDefault();
            submitForm('student');
        }

        function handleTeacherRegistration(event) {
            event.preventDefault();
            submitForm('teacher');
        }

        function submitForm(role) {
            const form = document.getElementById(role + 'RegistrationForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            submitBtn.disabled = true;

            // Submit form
            fetch('auth/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                try {
                    const data = JSON.parse(result);
                    if (data.success) {
                        showAdminApprovalModal();
                    } else {
                        showErrorModal(data.message);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                } catch (e) {
                    if (result.includes('success') || result.includes('Registration successful')) {
                        showAdminApprovalModal();
                    } else {
                        showErrorModal('Registration failed. Please try again.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Registration failed. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function showModal() {
            const modal = document.getElementById('registrationModal');
            const successMessage = modal.querySelector('.success-message p:first-child');
            
            // Update the success message based on current role
            if (currentRole === 'student') {
                successMessage.innerHTML = '<strong>Your student account has been created successfully!</strong>';
            } else {
                successMessage.innerHTML = '<strong>Your instructor account has been created successfully!</strong>';
            }
            
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('registrationModal');
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
        }

        function showAdminApprovalModal() {
            const modal = document.getElementById('adminApprovalModal');
            const approvalMessage = document.getElementById('approvalMessage');
            
            // Update the approval message based on current role
            if (currentRole === 'student') {
                approvalMessage.innerHTML = '<strong>Thank you for registering!</strong><br>This account needs to be approved by the admin.';
            } else {
                approvalMessage.innerHTML = '<strong>Thank you for registering!</strong><br>This account needs to be approved by the admin.';
            }
            
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeAdminApprovalModal() {
            const modal = document.getElementById('adminApprovalModal');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function showErrorModal(message) {
            const modal = document.getElementById('errorModal');
            const errorMessage = document.getElementById('errorMessage');
            
            // Update the error message
            errorMessage.textContent = message;
            
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function goToLogin() {
            window.location.href = 'index.php';
        }

        function togglePassword(toggleElement) {
            const input = toggleElement.parentElement.querySelector('input');
            const icon = toggleElement.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function validateFullName(input) {
            // Remove any invalid characters (keep only letters, spaces, apostrophes, and accented characters)
            input.value = input.value.replace(/[^a-zA-ZÀ-ÿ\s']/g, '');
        }

        function validateStudentId(input) {
            let value = input.value;
            
            // Remove any non-numeric characters
            value = value.replace(/[^0-9]/g, '');
            
            // Format as xx-x-x-xxxx
            if (value.length >= 2) {
                let formatted = value.substring(0, 2);
                if (value.length >= 3) {
                    formatted += '-' + value.substring(2, 3);
                    if (value.length >= 4) {
                        formatted += '-' + value.substring(3, 4);
                        if (value.length >= 5) {
                            formatted += '-' + value.substring(4, 8);
                        }
                    }
                }
                input.value = formatted;
            } else {
                input.value = value;
            }
        }

        function validateInstructorId(input) {
            let value = input.value;
            // Convert to uppercase and remove any invalid characters
            value = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Ensure only 2 letters followed by numbers
            if (value.length >= 2) {
                const letters = value.substring(0, 2);
                const numbers = value.substring(2);
                
                // Keep only letters in the first 2 positions
                const validLetters = letters.replace(/[^A-Z]/g, '');
                // Keep only numbers in the remaining positions
                const validNumbers = numbers.replace(/[^0-9]/g, '');
                
                input.value = validLetters + validNumbers;
            } else {
                // For first 2 characters, only allow letters
                input.value = value.replace(/[^A-Z]/g, '');
            }
        }

        function handleInstructorIdInput(event) {
            const input = event.target;
            const char = event.key;
            
            // Allow backspace, delete, tab, escape, enter, arrow keys
            if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key) ||
                // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (event.ctrlKey && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase()))) {
                return;
            }
            
            // For first 2 characters, only allow capital letters
            if (input.value.length < 2) {
                if (!/[A-Z]/.test(char)) {
                    event.preventDefault();
                }
            } else {
                // For remaining characters, only allow numbers
                if (!/[0-9]/.test(char)) {
                    event.preventDefault();
                }
            }
        }

        function checkPasswordStrength(input) {
            const password = input.value;
            const isStudentForm = input.closest('#studentForm');
            const suffix = isStudentForm ? '' : '-teacher';
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            
            // Check if all requirements are met
            const allRequirementsMet = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
            
            // Show/hide password requirements based on input and requirements status
            const requirementsDiv = input.parentElement.nextElementSibling.querySelector('.password-requirements');
            if (password.length > 0 && !allRequirementsMet) {
                requirementsDiv.classList.add('show');
            } else {
                requirementsDiv.classList.remove('show');
            }
            
            // Update requirement indicators
            updateRequirement('req-length' + suffix, hasLength);
            updateRequirement('req-uppercase' + suffix, hasUppercase);
            updateRequirement('req-lowercase' + suffix, hasLowercase);
            updateRequirement('req-number' + suffix, hasNumber);
            updateRequirement('req-special' + suffix, hasSpecial);
            
            // Calculate strength
            const strength = [hasLength, hasUppercase, hasLowercase, hasNumber, hasSpecial].filter(Boolean).length;
            updateStrengthBars(input, strength);
            
            // Also check password match when password field is edited
            const confirmPasswordInput = input.closest('form').querySelector('input[name="confirm_password"]');
            if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                checkPasswordMatch(confirmPasswordInput);
            }
        }

        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (element) {
                const icon = element.querySelector('i');
                if (met) {
                    element.classList.remove('unmet');
                    element.classList.add('met');
                    icon.className = 'fas fa-check';
                } else {
                    element.classList.remove('met');
                    element.classList.add('unmet');
                    icon.className = 'fas fa-times';
                }
            }
        }

        function updateStrengthBars(input, strength) {
            const strengthBars = input.parentElement.nextElementSibling.querySelectorAll('.strength-bar');
            strengthBars.forEach((bar, index) => {
                bar.className = 'strength-bar';
                if (index < strength) {
                    if (strength === 1) bar.classList.add('weak');
                    else if (strength === 2) bar.classList.add('weak');
                    else if (strength === 3) bar.classList.add('fair');
                    else if (strength === 4) bar.classList.add('good');
                    else if (strength === 5) bar.classList.add('strong');
                }
            });
        }

        function checkPasswordMatch(input) {
            const confirmPassword = input.value;
            const passwordInput = input.closest('form').querySelector('input[name="password"]');
            const password = passwordInput.value;
            const isStudentForm = input.closest('#studentForm');
            const errorId = isStudentForm ? 'password-error-student' : 'password-error-teacher';
            
            const errorDiv = document.getElementById(errorId);
            
            if (confirmPassword.length > 0) {
                // Check if password field is empty first
                if (password.length === 0) {
                    errorDiv.textContent = 'Please enter your password first';
                    errorDiv.classList.add('show', 'password-first');
                } else if (password === confirmPassword) {
                    errorDiv.textContent = 'Passwords match';
                    errorDiv.classList.remove('show', 'password-first');
                } else {
                    errorDiv.textContent = 'Passwords do not match';
                    errorDiv.classList.add('show');
                    errorDiv.classList.remove('password-first');
                }
            } else {
                errorDiv.classList.remove('show', 'password-first');
            }
        }
            
            function initFormValidation() {
            // Add input event listeners to full name fields
            const fullNameInputs = document.querySelectorAll('input[name="full_name"]');
            fullNameInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateFullName(this);
                });
                
                input.addEventListener('keypress', function(e) {
                    // Prevent invalid characters from being typed
                    const char = String.fromCharCode(e.which);
                    if (!/[a-zA-ZÀ-ÿ\s']/.test(char)) {
                        e.preventDefault();
                    }
                });
            });

            // Add input event listeners to student ID fields
            const studentIdInputs = document.querySelectorAll('input[name="student_id"]');
            studentIdInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateStudentId(this);
                });
                
                input.addEventListener('keypress', function(e) {
                    // Prevent invalid characters from being typed (only numbers and dashes)
                    const char = String.fromCharCode(e.which);
                    if (!/[0-9\-]/.test(char)) {
                        e.preventDefault();
                    }
                });
            });

            // Add input event listeners to instructor ID fields
            const instructorIdInputs = document.querySelectorAll('input[name="teacher_id"]');
            instructorIdInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateInstructorId(this);
                });
                
                input.addEventListener('keypress', function(e) {
                    // Prevent invalid characters from being typed (only letters and numbers)
                    const char = String.fromCharCode(e.which);
                    if (!/[A-Za-z0-9]/.test(char)) {
                            e.preventDefault();
                        }
                    });
                });
            }

        // Function to clear all forms
        function clearAllForms() {
            // Clear student form
            const studentForm = document.getElementById('studentRegistrationForm');
            if (studentForm) {
                studentForm.reset();
            }
            
            // Clear teacher form
            const teacherForm = document.getElementById('teacherRegistrationForm');
            if (teacherForm) {
                teacherForm.reset();
            }
            
            // Clear password strength indicators
            document.querySelectorAll('.password-requirements').forEach(req => {
                req.classList.remove('show');
            });
            
            // Clear password strength bars
            document.querySelectorAll('.strength-bar').forEach(bar => {
                bar.className = 'strength-bar';
            });
            
            // Clear password error messages
            document.querySelectorAll('.password-error').forEach(error => {
                error.classList.remove('show', 'password-first');
            });
            
            // Clear requirement indicators
            document.querySelectorAll('.requirement').forEach(req => {
                req.classList.remove('met');
                req.classList.add('unmet');
                const icon = req.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-times';
                }
            });
            
            // Reset role selection to student
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            const studentBtn = document.querySelector('.role-btn[onclick="selectRole(\'student\')"]');
            if (studentBtn) {
                studentBtn.classList.add('active');
            }
            
            // Reset header to student registration
            const mainHeader = document.querySelector('.right-section .form-title');
            if (mainHeader) {
                mainHeader.textContent = 'Student Registration';
            }
            
            // Show student form, hide teacher form
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            const studentFormSection = document.getElementById('studentForm');
            if (studentFormSection) {
                studentFormSection.classList.add('active');
            }
            
            // Reset current role
            currentRole = 'student';
            
            // Reset submit buttons to normal state
            const submitButtons = document.querySelectorAll('.login-btn[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = false;
                // Reset button text based on current role
                if (currentRole === 'student') {
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Student Account';
                } else {
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Instructor Account';
                }
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Registration page loaded successfully');
            clearAllForms(); // Clear all forms when page loads
            initFormValidation();
        });
    </script>
</body>
</html>