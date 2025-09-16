<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

// Ensure required classes are loaded
require_once __DIR__ . '/../../config/database.php';

// Get sections from database
$sections = [];
try {
    $db = new Database();
    $db->query("SELECT section_name, year_level FROM sections WHERE status = 'available' ORDER BY year_level ASC, section_name ASC");
    $db->execute();
    $sections = $db->resultset();
} catch (Exception $e) {
    // Handle error silently for now
}

// Get instructors from database
$instructors = [];
try {
    $db->query("SELECT u.user_id, u.full_name, u.status, t.teacher_id, t.department
                FROM users u
                INNER JOIN teachers t ON u.user_id = t.user_id
                WHERE u.role = 'teacher' AND u.status = 'approved'
                ORDER BY u.full_name ASC");
    $db->execute();
    $instructors = $db->resultset();
} catch (Exception $e) {
    // Handle error silently for now
}

// Fetch academic years from database
$academic_years = [];
try {
    $db->query("SELECT academic_year, status FROM academic_years ORDER BY academic_year DESC");
    $db->execute();
    $academic_years = $db->resultset();
} catch (Exception $e) {
    error_log("Failed to fetch academic years: " . $e->getMessage());
    // Fallback to default academic years
    $academic_years = [
        ['academic_year' => '2024-2025', 'status' => 'active'],
        ['academic_year' => '2025-2026', 'status' => 'inactive'],
        ['academic_year' => '2026-2027', 'status' => 'inactive']
    ];
}

// Get current academic year from URL parameter, default to first active academic year
$current_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
if (empty($current_academic_year)) {
    // Find the first active academic year
    foreach ($academic_years as $ay) {
        if ($ay['status'] === 'active') {
            $current_academic_year = $ay['academic_year'];
            break;
        }
    }
    // If no active academic year found, use the first one
    if (empty($current_academic_year) && !empty($academic_years)) {
        $current_academic_year = $academic_years[0]['academic_year'];
    }
}

// Year levels for dropdown
$year_levels = [
    '1st Year' => 'First Year',
    '2nd Year' => 'Second Year', 
    '3rd Year' => 'Third Year',
    '4th Year' => 'Fourth Year'
];

// Get current year level and semester from URL parameters
$current_year_level = isset($_GET['year_level']) && in_array($_GET['year_level'], array_keys($year_levels)) ? $_GET['year_level'] : '1st Year';
$current_semester = isset($_GET['semester']) && in_array($_GET['semester'], ['first', 'second', 'summer']) ? $_GET['semester'] : 'first';

// Fetch subjects from database
$activeSubjects = [];
try {
    $db->query("SELECT subject_id as id, subject_code, subject_name, units, year_level, semester, academic_year,
                       CASE WHEN status = 'available' THEN 'active' ELSE 'inactive' END as status
                FROM subjects 
                WHERE status = 'available' AND year_level = :year_level AND semester = :semester AND academic_year = :academic_year
                ORDER BY subject_code");
    $db->bind(':year_level', $current_year_level);
    $db->bind(':semester', $current_semester);
    $db->bind(':academic_year', $current_academic_year);
    $activeSubjects = $db->resultset();
} catch (Exception $e) {
    // Fallback to empty arrays if database error
    $activeSubjects = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Course - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Assignment Form Styles */
        .assignment-form-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04),
                0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .assignment-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4, #10b981);
            border-radius: 24px 24px 0 0;
            animation: gradientShift 3s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .form-header h2 {
            color: #1f2937;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-header h2 i {
            font-size: 2.5rem;
            color: #6366f1;
            text-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        .form-header p {
            color: #6b7280;
            font-size: 1.2rem;
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
            min-width: 220px;
            transition: all 0.3s ease;
        }

        .form-group:hover {
            transform: translateY(-2px);
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: #1f2937;
            font-size: 1.2rem;
            position: relative;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            letter-spacing: 0.025em;
        }

        .form-group label.required::after {
            content: ' *';
            color: #ef4444;
            font-weight: 800;
            text-shadow: 0 1px 2px rgba(239, 68, 68, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
            min-width: 220px;
            transition: all 0.3s ease;
            position: relative;
        }

        .form-group:hover {
            transform: translateY(-3px);
        }

        .form-group::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .form-group:hover::before {
            opacity: 0.3;
        }

        .form-input {
            width: 100%;
            min-width: 180px;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #1f2937;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1), 0 8px 25px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
        }

        .form-input:hover {
            border-color: #6366f1;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .form-input:disabled {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #94a3b8;
            border-color: #cbd5e1;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            position: relative;
        }

        .form-input:disabled:hover {
            transform: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-input:disabled::after {
            content: '🔒';
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.5;
        }

        /* Add a subtle indicator for required fields that are not yet available */
        .form-group.required-not-met label::after {
            content: ' ⏳';
            color: #f59e0b;
            font-weight: 600;
        }

        /* Enhanced dropdown styling */
        .form-input option {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            padding: 1rem 1.25rem !important;
            background: #ffffff !important;
            color: #1f2937 !important;
            border: none !important;
            margin: 0.25rem 0 !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }

        .form-input option:hover {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: #ffffff !important;
            transform: translateX(4px) !important;
        }

        .form-input option:checked,
        .form-input option:selected {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
        }

        /* Enhanced optgroup styling */
        .form-input optgroup {
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
            padding: 1rem 1.25rem !important;
            margin: 0.5rem 0 !important;
            border: none !important;
            border-radius: 8px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
        }

        /* Custom dropdown arrow */
        .form-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 1rem center !important;
            background-size: 1.5rem !important;
            padding-right: 3rem !important;
        }

        .form-actions {
            margin-top: 3rem;
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            padding-top: 2rem;
            border-top: 2px solid rgba(229, 231, 235, 0.5);
            position: relative;
        }

        .form-actions::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
            border-radius: 1px;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 160px;
            justify-content: center;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.5);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            border-color: #6366f1;
            color: #6366f1;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.2);
        }

        /* Success/Error Message Styles */
        .message-container {
            margin: 1.5rem 0;
            padding: 1.5rem;
            border-radius: 16px;
            border: 2px solid;
            display: none;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            animation: messageSlideIn 0.5s ease-out;
        }

        .message-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #10b981;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
        }

        .message-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-color: #ef4444;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.2);
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced responsive adjustments */
        @media (max-width: 768px) {
            .assignment-form-container {
                padding: 2rem;
                border-radius: 20px;
            }

            .form-header h2 {
                font-size: 2rem;
            }

            .form-header h2 i {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                padding: 1.25rem 2rem;
                font-size: 1.2rem;
            }

            .form-input {
                font-size: 1.2rem;
                padding: 1.25rem 1.5rem;
            }

            .form-group label {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .assignment-form-container {
                padding: 1.5rem;
                border-radius: 16px;
            }

            .form-header h2 {
                font-size: 1.75rem;
            }

            .form-header h2 i {
                font-size: 1.75rem;
            }

            .form-input {
                font-size: 1.1rem;
                padding: 1rem 1.25rem;
            }

            .form-group label {
                font-size: 1.1rem;
            }
        }

        /* Additional modern enhancements */
        .main-content {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .dashboard-card {
            background: transparent;
            border: none;
            box-shadow: none;
        }

        /* Admin Section Cards */
        .admin-sections {
            margin-bottom: 2rem;
        }

        .admin-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            min-height: 120px;
        }

        .admin-section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
            text-decoration: none;
            color: inherit;
        }

        .admin-section-card .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, #667eea, #5a6fd8);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .admin-section-card .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .admin-section-card .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .admin-section-card .card-content p {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.4;
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-cards-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .admin-section-card {
                padding: 1.25rem;
                min-height: 100px;
            }
            
            .admin-section-card .card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }
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
                        <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
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
            <div class="main-header">
                <h1 class="page-title">
                    <i class="fas fa-chalkboard-teacher"></i> Assign Course
                </h1>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Admin Section Navigation Cards -->
            <div class="admin-sections">
                <div class="admin-cards-grid">
                    <!-- Dashboard Overview Card -->
                    <a href="dashboard.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="card-content">
                            <h3>Dashboard Overview</h3>
                            <p>View system overview, statistics, and quick access to all administrative functions and reports.</p>
                        </div>
                    </a>

                    <!-- Manage User Accounts Card -->
                    <a href="manage-users.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3>Manage User Accounts</h3>
                            <p>Review and manage user registrations, approve or reject new accounts, and maintain user information.</p>
                        </div>
                    </a>

                    <!-- Manage Scheduling Information Card -->
                    <a href="manage-scheduling.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="card-content">
                            <h3>Manage Scheduling Information</h3>
                            <p>Configure and maintain scheduling parameters, set up time slots, rooms, and other scheduling requirements.</p>
                        </div>
                    </a>


                    <!-- Generate Schedule Card -->
                    <a href="generate-schedule.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="card-content">
                            <h3>Generate Schedule</h3>
                            <p>Automatically create optimized schedules based on user preferences, room availability, and scheduling constraints.</p>
                        </div>
                    </a>

                    <!-- Export/Download Schedule Card -->
                    <a href="export-schedule.php" class="admin-section-card clickable-card">
                        <div class="card-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="card-content">
                            <h3>Export/Download Schedule</h3>
                            <p>Export generated schedules in various formats, download reports, and share scheduling information with stakeholders.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="main-content">
                <div class="dashboard-card" style="width:100%">
                    <!-- Assignment Form Container -->
                    <div class="assignment-form-container">
                        <div class="form-header">
                            <h2>
                                <i class="fas fa-chalkboard-teacher"></i>
                                Assign Course to Instructor
                            </h2>
                            <p>Select the course, instructor, and section to create a new assignment</p>
                        </div>

                        <!-- Success/Error Messages -->
                        <div id="messageContainer" class="message-container"></div>

                        <form id="assignCourseForm">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="academicYearSelect" class="required">📅 Academic Year</label>
                                    <select id="academicYearSelect" name="academic_year" class="form-input" required>
                                        <option value="" disabled selected>Choose an academic year...</option>
                                        <?php 
                                        // Separate active and inactive academic years
                                        $active_years = [];
                                        $inactive_years = [];
                                        
                                        foreach ($academic_years as $ay) {
                                            if ($ay['status'] === 'active') {
                                                $active_years[] = $ay;
                                            } else {
                                                $inactive_years[] = $ay;
                                            }
                                        }
                                        
                                        // Show active academic years first (Present)
                                        if (!empty($active_years)): ?>
                                            <optgroup label="Present Academic Years">
                                                <?php foreach ($active_years as $ay): ?>
                                                    <option value="<?php echo htmlspecialchars($ay['academic_year']); ?>">
                                                        <?php echo htmlspecialchars($ay['academic_year']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        
                                        <!-- Show inactive academic years (Past) -->
                                        <?php if (!empty($inactive_years)): ?>
                                            <optgroup label="Past Academic Years">
                                                <?php foreach ($inactive_years as $ay): ?>
                                                    <option value="<?php echo htmlspecialchars($ay['academic_year']); ?>">
                                                        <?php echo htmlspecialchars($ay['academic_year']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="yearLevelSelect" class="required">🎓 Year Level</label>
                                    <select id="yearLevelSelect" name="year_level" class="form-input" required>
                                        <option value="" disabled selected>Choose a year level...</option>
                                        <?php foreach ($year_levels as $year_level => $name): ?>
                                            <option value="<?php echo htmlspecialchars($year_level); ?>">
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="semesterSelect" class="required">📚 Semester</label>
                                    <select id="semesterSelect" name="semester" class="form-input" required>
                                        <option value="" disabled selected>Choose a semester...</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sectionSelect" class="required">👥 Section</label>
                                    <select id="sectionSelect" name="section" class="form-input" required>
                                        <option value="" disabled selected>Choose a section...</option>
                                        <?php if (!empty($sections)): ?>
                                            <?php 
                                            // Group sections by year level
                                            $sections_by_year = [];
                                            foreach ($sections as $section) {
                                                $year_level = $section['year_level'];
                                                if (!isset($sections_by_year[$year_level])) {
                                                    $sections_by_year[$year_level] = [];
                                                }
                                                $sections_by_year[$year_level][] = $section;
                                            }
                                            
                                            // Display sections grouped by year level
                                            foreach ($sections_by_year as $year_level => $year_sections): ?>
                                                <optgroup label="<?php echo htmlspecialchars($year_level); ?>">
                                                    <?php foreach ($year_sections as $section): ?>
                                                        <option value="<?php echo htmlspecialchars($section['section_name']); ?>">
                                                            <?php echo htmlspecialchars($section['section_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="courseSelect" class="required">📚 Course</label>
                                    <select id="courseSelect" name="course" class="form-input" required disabled>
                                        <option value="" disabled selected>Please select Year Level and Semester first...</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="instructorSelect" class="required">👨‍🏫 Instructor</label>
                                    <select id="instructorSelect" name="instructor" class="form-input" required>
                                        <option value="" disabled selected>Choose an instructor...</option>
                                        <?php if (!empty($instructors)): ?>
                                            <?php 
                                            // Group instructors by department
                                            $instructors_by_department = [];
                                            foreach ($instructors as $instructor) {
                                                $department = $instructor['department'];
                                                if (!isset($instructors_by_department[$department])) {
                                                    $instructors_by_department[$department] = [];
                                                }
                                                $instructors_by_department[$department][] = $instructor;
                                            }
                                            
                                            // Display instructors grouped by department
                                            foreach ($instructors_by_department as $department => $dept_instructors): ?>
                                                <optgroup label="<?php echo htmlspecialchars($department); ?>">
                                                    <?php foreach ($dept_instructors as $instructor): ?>
                                                        <option value="<?php echo htmlspecialchars($instructor['user_id']); ?>"
                                                                data-teacher-id="<?php echo htmlspecialchars($instructor['teacher_id']); ?>"
                                                                data-department="<?php echo htmlspecialchars($instructor['department']); ?>">
                                                            <?php echo htmlspecialchars($instructor['full_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i>
                                    Assign Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize page functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Assign Course Dashboard loaded');
            
            // Initialize sidebar toggle
            initializeSidebarToggle();
            
            // Initialize form functionality
            initializeFormFunctionality();
            
            // Set initial semester options based on current year level
            updateSemesterOptions(document.getElementById('yearLevelSelect').value);
            
            // Set initial section options based on current year level
            updateSectionOptions(document.getElementById('yearLevelSelect').value);
        });
        
        // Initialize sidebar toggle functionality
        function initializeSidebarToggle() {
            const headerToggle = document.getElementById('headerSidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            const mainContent = document.querySelector('.admin-main');
            
            if (headerToggle && sidebar && mainContent) {
                // Check saved state
                const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                
                if (isSidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                    headerToggle.classList.add('sidebar-collapsed');
                }
                
                headerToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('sidebar-collapsed');
                    headerToggle.classList.toggle('sidebar-collapsed');
                    
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                });
            }
        }
        
        // Initialize form functionality
        function initializeFormFunctionality() {
            console.log('🔧 Initializing form functionality...');
            
            // Initialize course dropdown as disabled
            const courseSelect = document.getElementById('courseSelect');
            const courseGroup = courseSelect ? courseSelect.closest('.form-group') : null;
            
            console.log('Course select element:', courseSelect);
            console.log('Course group element:', courseGroup);
            
            if (courseSelect) {
                courseSelect.disabled = true;
                courseSelect.innerHTML = '<option value="" disabled selected>Please select Year Level and Semester first...</option>';
                
                // Add visual indicator class
                if (courseGroup) {
                    courseGroup.classList.add('required-not-met');
                }
                console.log('✅ Course dropdown initialized as disabled');
            } else {
                console.error('❌ Course select element not found!');
            }
            
            // Year level change handler
            const yearLevelSelect = document.getElementById('yearLevelSelect');
            console.log('Year level select element:', yearLevelSelect);
            
            if (yearLevelSelect) {
                yearLevelSelect.addEventListener('change', function() {
                    console.log('🎓 Year level changed to:', this.value);
                    updateSemesterOptions(this.value);
                    updateSectionOptions(this.value);
                    // Reset course dropdown when year level changes
                    if (courseSelect) {
                        courseSelect.disabled = true;
                        courseSelect.innerHTML = '<option value="" disabled selected>Please select Semester first...</option>';
                        
                        // Add visual indicator class
                        if (courseGroup) {
                            courseGroup.classList.add('required-not-met');
                        }
                        console.log('🔄 Course dropdown reset due to year level change');
                    }
                });
            } else {
                console.error('❌ Year level select element not found!');
            }
            
            // Semester change handler
            const semesterSelect = document.getElementById('semesterSelect');
            console.log('Semester select element:', semesterSelect);
            
            if (semesterSelect) {
                semesterSelect.addEventListener('change', function() {
                    console.log('📚 Semester changed to:', this.value);
                    
                    // Check if we have all required criteria
                    const academicYear = academicYearSelect ? academicYearSelect.value : '';
                    const yearLevel = yearLevelSelect ? yearLevelSelect.value : '';
                    const semester = this.value;
                    
                    console.log('Current criteria:', { academicYear, yearLevel, semester });
                    
                    if (academicYear && yearLevel && semester) {
                        console.log('✅ All criteria met, loading courses...');
                        filterCourses();
                    } else {
                        console.log('⚠️ Missing criteria, cannot load courses yet');
                    }
                });
            } else {
                console.error('❌ Semester select element not found!');
            }
            
            // Academic year change handler
            const academicYearSelect = document.getElementById('academicYearSelect');
            console.log('Academic year select element:', academicYearSelect);
            
            if (academicYearSelect) {
                academicYearSelect.addEventListener('change', function() {
                    console.log('📅 Academic year changed to:', this.value);
                    // Only filter courses if both year level and semester are selected
                    const yearLevel = yearLevelSelect ? yearLevelSelect.value : '';
                    const semester = semesterSelect ? semesterSelect.value : '';
                    console.log('Current year level:', yearLevel, 'Current semester:', semester);
                    if (yearLevel && semester) {
                        console.log('✅ Both year level and semester selected, calling filterCourses()');
                        filterCourses();
                    } else {
                        console.log('⚠️ Year level or semester not selected yet');
                    }
                });
            } else {
                console.error('❌ Academic year select element not found!');
            }
            
            // Form submission handler
            const form = document.getElementById('assignCourseForm');
            if (form) {
                form.addEventListener('submit', handleFormSubmit);
            }
        }
        
        // Update semester options based on year level
        function updateSemesterOptions(yearLevel) {
            const semesterSelect = document.getElementById('semesterSelect');
            if (!semesterSelect) return;
            
            // Clear current options
            semesterSelect.innerHTML = '<option value="" disabled selected>Choose a semester...</option>';
            
            // Add semester options
            const semesters = [
                { value: 'first', label: 'First Semester' },
                { value: 'second', label: 'Second Semester' }
            ];
            
            // Add summer option only for 3rd Year
            if (yearLevel === '3rd Year') {
                semesters.push({ value: 'summer', label: 'Mid Year' });
            }
            
            // Add options to dropdown
            semesters.forEach(semester => {
                const option = document.createElement('option');
                option.value = semester.value;
                option.textContent = semester.label;
                semesterSelect.appendChild(option);
            });
            
            // Set default selection to first semester
            semesterSelect.value = 'first';
        }
        
        // Update section options based on year level
        function updateSectionOptions(yearLevel) {
            const sectionSelect = document.getElementById('sectionSelect');
            if (!sectionSelect) return;
            
            // Clear current options
            sectionSelect.innerHTML = '<option value="" disabled selected>Choose a section...</option>';
            
            // Get sections data from PHP
            const sectionsData = <?php echo json_encode($sections); ?>;
            
            // Filter sections by year level
            const filteredSections = sectionsData.filter(section => section.year_level === yearLevel);
            
            // Add filtered sections to dropdown
            filteredSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_name;
                option.textContent = section.section_name;
                sectionSelect.appendChild(option);
            });
            
            // If no sections found for the year level, add a message
            if (filteredSections.length === 0) {
                const noSectionOption = document.createElement('option');
                noSectionOption.value = '';
                noSectionOption.disabled = true;
                noSectionOption.textContent = 'No sections available for this year level';
                sectionSelect.appendChild(noSectionOption);
            }
        }
        
        // Filter courses based on selected criteria
        async function filterCourses() {
            console.log('🚀 filterCourses() called');
            
            const academicYearSelect = document.getElementById('academicYearSelect');
            const yearLevelSelect = document.getElementById('yearLevelSelect');
            const semesterSelect = document.getElementById('semesterSelect');
            const courseSelect = document.getElementById('courseSelect');
            
            console.log('Form elements found:', {
                academicYearSelect: !!academicYearSelect,
                yearLevelSelect: !!yearLevelSelect,
                semesterSelect: !!semesterSelect,
                courseSelect: !!courseSelect
            });
            
            if (!academicYearSelect || !yearLevelSelect || !semesterSelect || !courseSelect) {
                console.error('❌ One or more form elements not found!');
                return;
            }
            
            const selectedAcademicYear = academicYearSelect.value;
            const selectedYearLevel = yearLevelSelect.value;
            const selectedSemester = semesterSelect.value;
            
            // Check if year level and semester are selected
            if (!selectedYearLevel || !selectedSemester) {
                courseSelect.innerHTML = '<option value="" disabled selected>Please select Year Level and Semester first...</option>';
                courseSelect.disabled = true;
                
                // Add visual indicator class
                const courseGroup = courseSelect.closest('.form-group');
                if (courseGroup) {
                    courseGroup.classList.add('required-not-met');
                }
                return;
            }
            
            // Enable course dropdown
            courseSelect.disabled = false;
            
            // Remove visual indicator class
            const courseGroup = courseSelect.closest('.form-group');
            if (courseGroup) {
                courseGroup.classList.remove('required-not-met');
            }
            
            // Show loading state
            courseSelect.innerHTML = '<option value="" disabled selected>Loading courses...</option>';
            
            try {
                // Debug logging
                console.log('Fetching courses with:', {
                    academic_year: selectedAcademicYear,
                    year_level: selectedYearLevel,
                    semester: selectedSemester
                });
                
                // Fetch courses from the courses dashboard endpoint
                const response = await fetch('fetch-courses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=fetch_courses&academic_year=${encodeURIComponent(selectedAcademicYear)}&year_level=${encodeURIComponent(selectedYearLevel)}&semester=${encodeURIComponent(selectedSemester)}`
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    // Clear current options
                    courseSelect.innerHTML = '<option value="" disabled selected>Choose a course to assign...</option>';
                    
                    if (data.success && data.courses && data.courses.length > 0) {
                        // Add filtered courses to dropdown
                        data.courses.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.subject_id || course.id;
                            option.textContent = course.subject_code + ' - ' + course.subject_name;
                            option.setAttribute('data-code', course.subject_code);
                            option.setAttribute('data-name', course.subject_name);
                            option.setAttribute('data-units', course.units);
                            option.setAttribute('data-year-level', course.year_level);
                            option.setAttribute('data-semester', course.semester);
                            option.setAttribute('data-academic-year', course.academic_year);
                            courseSelect.appendChild(option);
                        });
                    } else {
                        // No courses found
                        const noCourseOption = document.createElement('option');
                        noCourseOption.value = '';
                        noCourseOption.disabled = true;
                        noCourseOption.textContent = 'No courses available for the selected criteria';
                        courseSelect.appendChild(noCourseOption);
                    }
                } else {
                    console.error('Response not ok. Status:', response.status);
                    const errorText = await response.text();
                    console.error('Error response text:', errorText);
                    throw new Error('Failed to fetch courses: ' + response.status);
                }
            } catch (error) {
                console.error('Error fetching courses:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                
                // Show error message
                courseSelect.innerHTML = '<option value="" disabled selected>Error loading courses. Please try again.</option>';
            }
        }
        
        // Handle form submission
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);
            
            // Validate form
            const courseId = formData.get('course');
            const instructor = formData.get('instructor');
            const section = formData.get('section');
            const academicYear = formData.get('academic_year');
            const yearLevel = formData.get('year_level');
            const semester = formData.get('semester');
            
            if (!courseId || !instructor || !section || !academicYear || !yearLevel || !semester) {
                showMessage('Please fill in all required fields.', 'error');
                return;
            }
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            try {
                // Send the assignment data to the server
                const result = await handleAssignment(formData);
                
                showMessage(`Course assigned successfully!\nInstructor: ${result.instructor_name}\nSection: ${result.section}`, 'success');
                form.reset();
                
                // Reset semester and section options
                updateSemesterOptions(yearLevel);
                updateSectionOptions(yearLevel);
                
                // Redirect to dashboard after a short delay
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
                
            } catch (error) {
                showMessage('Failed to assign course: ' + error.message, 'error');
            } finally {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-check"></i> Assign Course';
            }
        }
        
        // Handle assignment process
        async function handleAssignment(formData) {
            const courseId = formData.get('course');
            const instructorId = formData.get('instructor');
            const section = formData.get('section');
            const academicYear = formData.get('academic_year');
            const yearLevel = formData.get('year_level');
            const semester = formData.get('semester');
            
            try {
                console.log('Submitting course assignment:', { courseId, instructorId, section, academicYear, yearLevel, semester });
                
                // Send data to server
                const response = await fetch('assign-course.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=assign_course&course_id=${encodeURIComponent(courseId)}&instructor_id=${encodeURIComponent(instructorId)}&section=${encodeURIComponent(section)}&academic_year=${encodeURIComponent(academicYear)}&year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    return data;
                } else {
                    throw new Error(data.message || 'Failed to assign course');
                }
                
            } catch (error) {
                console.error('Error assigning course:', error);
                throw error;
            }
        }
        
        // Show message
        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            if (!messageContainer) return;
            
            messageContainer.textContent = message;
            messageContainer.className = `message-container message-${type}`;
            messageContainer.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageContainer.style.display = 'none';
            }, 5000);
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const mainContent = document.getElementById('adminMain');
            if (sidebar && mainContent) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
            }
        }
    </script>
</body>
</html>
