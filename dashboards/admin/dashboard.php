<?php
// Ensure no stray output breaks redirects or session checks
ob_start();
session_start();
require_once '../../classes/User.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$user = new User();
$pending_users = $user->getPendingUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Subject Scheduling System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            color: #1e293b;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Modern Navigation */
        .netflix-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: transparent;
            padding: 1rem 2rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            transition: all 0.3s ease;
        }

        .netflix-logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: rgba(30, 41, 59, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-links a:hover {
            color: #1e293b;
            transform: translateY(-2px);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: auto;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .profile-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(15, 15, 35, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 0.5rem 0;
            min-width: 180px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
            margin-top: 0.5rem;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0.25rem;
        }

        .profile-dropdown a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        /* Modern Hero Section */
        .hero-section {
            height: auto;
            min-height: 15vh;
            background: linear-gradient(135deg, 
                rgba(248, 250, 252, 0.95) 0%, 
                rgba(241, 245, 249, 0.9) 50%, 
                rgba(226, 232, 240, 0.95) 100%),
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 107, 107, 0.1) 0%, transparent 50%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 1.5rem 0 0 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            padding: 0 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            line-height: 1.1;
            letter-spacing: -2px;
            animation: fadeInUp 1s ease-out;
            color: #667eea;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-subtitle {
            font-size: 1.6rem;
            margin-bottom: 2.5rem;
            color: #000000;
            text-shadow: 0 1px 5px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out 0.2s both;
            font-weight: 400;
        }

        .play-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .play-button:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        /* Modern Episodes Section */
        .episodes-section {
            padding: 0 1rem 3rem 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            margin-top: 0;
            position: relative;
        }

        .episodes-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .episodes-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: #1e293b;
            text-align: center;
            position: relative;
        }

        .episodes-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(45deg, #e50914, #ff6b6b);
            border-radius: 2px;
        }

        .episodes-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
            width: 100%;
        }

        .episode-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .episode-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(229, 9, 20, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
        }

        .episode-thumbnail {
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .episode-thumbnail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .episode-card:hover .episode-thumbnail::before {
            transform: translateX(100%);
        }


        .episode-info {
            padding: 2rem;
        }

        .episode-number {
            font-size: 0.9rem;
            color: rgba(30, 41, 59, 0.6);
            margin-bottom: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .episode-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #1e293b;
            line-height: 1.3;
        }

        .episode-description {
            font-size: 1rem;
            color: rgba(30, 41, 59, 0.7);
            line-height: 1.5;
        }

        /* Admin specific styling */
        .admin-hero-title {
            color: #667eea;
        }

        .episode-card.manage-users .episode-thumbnail {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .episode-card.manage-scheduling .episode-thumbnail {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .episode-card.generate-schedule .episode-thumbnail {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .episode-card.export-schedule .episode-thumbnail {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .episodes-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .netflix-nav {
                padding: 1rem;
            }

            .nav-links {
                display: none;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .episodes-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
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
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #2f2f2f;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #404040;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: white;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #b3b3b3;
            padding: 0.25rem;
        }

        .modal-close:hover {
            color: white;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: white;
            font-weight: 500;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #404040;
            border-radius: 4px;
            background: #1a1a1a;
            color: white;
            font-size: 1rem;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #e50914;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .btn-primary {
            background: #e50914;
            color: white;
        }

        .btn-primary:hover {
            background: #b8070f;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #404040;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
            transform: translateY(-1px);
        }

        .flex {
            display: flex;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* Admin Sidebar - Matching manage-scheduling.php */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15), 0 0 40px rgba(102, 126, 234, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            border-radius: 0 20px 20px 0;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            color: white;
            border-radius: 0 20px 0 0;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .admin-sidebar.collapsed .admin-profile {
            justify-content: center;
        }


        .admin-sidebar.collapsed .admin-info {
            display: none;
        }

        .admin-avatar {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            backdrop-filter: blur(15px);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .admin-avatar:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2), 0 0 20px rgba(102, 126, 234, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .admin-sidebar.collapsed .admin-info {
            display: none;
        }

        .admin-info h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.5px;
        }

        .admin-info span {
            font-size: 0.9rem;
            opacity: 0.85;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .sidebar-nav {
            padding: 1.5rem 0;
            flex: 1;
            position: relative;
        }

        .sidebar-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1.5rem;
            right: 1.5rem;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
        }

        .nav-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0 15px 15px 0;
            margin-right: 1rem;
            position: relative;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .admin-sidebar.collapsed .nav-link {
            justify-content: center;
            margin-right: 0;
            border-radius: 0;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.1) 100%);
            color: white;
            transform: translateX(6px) translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 3px solid rgba(255, 255, 255, 0.3);
        }

        .nav-link.active {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.15) 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: translateX(4px);
            border-left: 4px solid #ffffff;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, #ffffff 0%, rgba(255, 255, 255, 0.8) 100%);
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        .nav-link i {
            width: 22px;
            text-align: center;
            font-size: 1.7rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }

        .nav-link:hover i {
            transform: scale(1.1);
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.15));
        }

        .nav-link span {
            font-size: 0.95rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar-footer {
            padding: 1.5rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin-top: auto;
            position: relative;
        }

        .sidebar-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1.5rem;
            right: 1.5rem;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
        }

        .logout-link {
            color: rgba(255, 255, 255, 0.75) !important;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .logout-link:hover {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.15) 0%, rgba(255, 107, 107, 0.1) 100%) !important;
            color: #ff6b6b !important;
            transform: translateX(4px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
        }

        /* Main content adjustment */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            transition: all 0.3s ease;
        }

        .admin-main.sidebar-collapsed {
            margin-left: 80px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 1.1rem;
        }

        .sidebar-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }

    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar" aria-expanded="true">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar" onclick="toggleSidebar()">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Manage User Accounts</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-scheduling.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Manage Scheduling Information</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="generate-schedule.php" class="nav-link">
                            <i class="fas fa-magic"></i>
                            <span>Generate Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="export-schedule.php" class="nav-link">
                            <i class="fas fa-download"></i>
                            <span>Export/Download Schedule</span>
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-footer">
                    <a href="../../auth/logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main" id="adminMain">
            <div class="main-content">

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title admin-hero-title">Smart Subject Scheduling</h1>
            <p class="hero-subtitle">Manage your subject scheduling system with powerful administrative tools</p>
        </div>
    </section>

    <!-- Episodes Section -->
    <section class="episodes-section" id="episodes">
        <div class="episodes-container">
            <div class="episodes-grid">
                    <!-- Manage User Accounts Card -->
                <a href="manage-users.php" class="episode-card manage-users">
                    <div class="episode-thumbnail">
                            <i class="fas fa-users"></i>
                        </div>
                    <div class="episode-info">
                        <div class="episode-title">Manage User Accounts</div>
                        <div class="episode-description">Review and manage user registrations, approve or reject new accounts, and maintain user information across the system.</div>
                        </div>
                    </a>

                    <!-- Manage Scheduling Information Card -->
                <a href="manage-scheduling.php" class="episode-card manage-scheduling">
                    <div class="episode-thumbnail">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    <div class="episode-info">
                        <div class="episode-title">Manage Scheduling Information</div>
                        <div class="episode-description">Configure and maintain scheduling parameters, set up time slots, rooms, and other scheduling requirements.</div>
                        </div>
                    </a>

                    <!-- Generate Schedule Card -->
                <a href="generate-schedule.php" class="episode-card generate-schedule">
                    <div class="episode-thumbnail">
                            <i class="fas fa-magic"></i>
                        </div>
                    <div class="episode-info">
                        <div class="episode-title">Generate Schedule</div>
                        <div class="episode-description">Automatically create optimized schedules based on user preferences, room availability, and constraints.</div>
                        </div>
                    </a>

                    <!-- Export/Download Schedule Card -->
                <a href="export-schedule.php" class="episode-card export-schedule">
                    <div class="episode-thumbnail">
                            <i class="fas fa-download"></i>
                        </div>
                    <div class="episode-info">
                        <div class="episode-title">Export/Download Schedule</div>
                        <div class="episode-description">Export generated schedules in various formats, download reports, and share scheduling information.</div>
                        </div>
                    </a>
                </div>
            </div>
    </section>
            </div> <!-- End main-content -->
        </main>
    </div> <!-- End admin-layout -->

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit User</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div id="editFormFields">
                        <!-- Dynamic form fields will be loaded here -->
                    </div>
                    <div class="flex gap-2" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-collapse sidebar when page loads
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.admin-main');
            
            if (sidebar && mainContent) {
                // Auto-collapse sidebar after a short delay when page loads
                setTimeout(() => {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    console.log('Sidebar auto-collapsed on page load');
                }, 500);
            }
            
            // Add hover effects to episode cards
            const episodeCards = document.querySelectorAll('.episode-card');
            
            episodeCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });

        // Play button functionality
        function scrollToEpisodes() {
            document.getElementById('episodes').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');
        }

        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const mainContent = document.getElementById('adminMain');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        }


        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const profileIcon = document.querySelector('.profile-icon');
            const dropdown = document.getElementById('profileDropdown');
            
            if (!profileIcon.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Admin Dashboard JavaScript
        async function updateUserStatus(userId, status) {
            const action = status === 'approved' ? 'approve' : 'reject';
            
            const ok = await modalConfirm(`Are you sure you want to ${action} this user?`);
            if (!ok) { return; }

            try {
                const response = await fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&status=${status}`
                });

                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    // Reload page after 1.5 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating user status:', error);
                showAlert('An error occurred while updating user status', 'error');
            }
        }

        async function editUser(userId) {
            try {
                const response = await fetch(`get-user.php?user_id=${userId}`);
                const result = await response.json();
                
                if (result.success) {
                    populateEditForm(result.user);
                    document.getElementById('editUserModal').style.display = 'flex';
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Error loading user data:', error);
                showAlert('An error occurred while loading user data', 'error');
            }
        }

        function populateEditForm(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            
            let formFields = `
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" value="${user.full_name}" required>
                </div>
                
            `;

            if (user.role === 'teacher') {
                formFields += `
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select" required>
                            <option value="College of Communication and Information Technology" ${user.department === 'College of Communication and Information Technology' ? 'selected' : ''}>College of Communication and Information Technology</option>
                            <option value="College of Teacher Education" ${user.department === 'College of Teacher Education' ? 'selected' : ''}>College of Teacher Education</option>
                        </select>
                    </div>
                `;
            } else if (user.role === 'student') {
                formFields += `
                    <div class="form-group">
                        <label class="form-label">Year Level</label>
                        <select name="year_level" class="form-select" required>
                            <option value="First Year" ${user.year_level === 'First Year' ? 'selected' : ''}>First Year</option>
                            <option value="Second Year" ${user.year_level === 'Second Year' ? 'selected' : ''}>Second Year</option>
                            <option value="Third Year" ${user.year_level === 'Third Year' ? 'selected' : ''}>Third Year</option>
                            <option value="Fourth Year" ${user.year_level === 'Fourth Year' ? 'selected' : ''}>Fourth Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section</label>
                        <select name="section" class="form-select" required>
                            <option value="A" ${user.section === 'A' ? 'selected' : ''}>Section A</option>
                            <option value="B" ${user.section === 'B' ? 'selected' : ''}>Section B</option>
                            <option value="C" ${user.section === 'C' ? 'selected' : ''}>Section C</option>
                        </select>
                    </div>
                `;
            }

            document.getElementById('editFormFields').innerHTML = formFields;
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Handle edit form submission
        document.getElementById('editUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('update-user.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeEditModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating user:', error);
                showAlert('An error occurred while updating user', 'error');
            }
        });

        // Backward-compatible aliases leveraging shared modal helpers
        function showAlert(message, type){ 
            try { 
                showNotice(message, type); 
            } catch(e) { 
                alert(String(message)); 
            } 
        }
        
        function modalConfirm(message){ 
            try { 
                return showConfirm(message, { confirmText: 'Confirm', cancelText: 'Cancel' }); 
            } catch(e) { 
                return Promise.resolve(confirm(String(message))); 
            } 
        }
    </script>
</body>
</html>
