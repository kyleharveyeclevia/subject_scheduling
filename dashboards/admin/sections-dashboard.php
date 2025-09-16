<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

// Ensure required classes are loaded
require_once __DIR__ . '/../../config/database.php';

// Get admin info
$db = new Database();
$adminId = $_SESSION['user_id'];
$db->query("SELECT full_name FROM users WHERE user_id = ?");
$db->bind(1, $adminId);
    $db->execute();
$admin = $db->single();
$_SESSION['full_name'] = $admin['full_name'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_section':
                $section_name = trim($_POST['section_name'] ?? '');
                $year_level = '1st Year'; // Always set to 1st Year
                $status = trim($_POST['status'] ?? '');
                
                if (!$section_name || !$status) {
                    echo json_encode(['success' => false, 'message' => 'Section name and status are required']);
                    exit;
                }
    
                // Validate section name length - must be exactly 2 characters
                if (strlen($section_name) !== 2) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly one capital letter and one number (2 characters total).']);
                    exit;
                }
                
                // Validate section name format (capital letters and numbers only)
                if (!preg_match('/^[A-Z0-9]+$/', $section_name)) {
                    echo json_encode(['success' => false, 'message' => 'Section name can only contain capital letters and numbers. Special characters and lowercase letters are not allowed.']);
                    exit;
                }
                
                // Validate exactly one capital letter and one number
                $capitalLetters = preg_match_all('/[A-Z]/', $section_name);
                $numbers = preg_match_all('/[0-9]/', $section_name);
                
                if ($capitalLetters !== 1) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly ONE capital letter.']);
                    exit;
                }
                
                if ($numbers !== 1) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly ONE number.']);
                    exit;
                }
                
                // Check for duplicate section name (since all sections are now 1st Year)
                $db->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ?");
                $db->bind(1, $section_name);
                $db->execute();
                $result = $db->single();
                
                if ($result['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Section name already exists']);
                    exit;
                }
                
                // Insert new section
                $db->query("INSERT INTO sections (section_name, year_level, status) VALUES (?, ?, ?)");
                $db->bind(1, $section_name);
                $db->bind(2, $year_level);
                $db->bind(3, $status);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Section added successfully']);
                exit;
                
            case 'edit_section':
                $section_id = intval($_POST['edit_section_id'] ?? 0);
                $section_name = trim($_POST['edit_section_name'] ?? '');
                $year_level = '1st Year'; // Always set to 1st Year
                $status = trim($_POST['edit_status'] ?? '');
                
                if (!$section_id || !$section_name || !$status) {
                    echo json_encode(['success' => false, 'message' => 'Section ID, section name, and status are required']);
                    exit;
                }
                
                // Validate section name length - must be exactly 2 characters
                if (strlen($section_name) !== 2) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly one capital letter and one number (2 characters total).']);
                    exit;
                }
                
                // Validate section name format (capital letters and numbers only)
                if (!preg_match('/^[A-Z0-9]+$/', $section_name)) {
                    echo json_encode(['success' => false, 'message' => 'Section name can only contain capital letters and numbers. Special characters and lowercase letters are not allowed.']);
                    exit;
                }
                
                // Validate exactly one capital letter and one number
                $capitalLetters = preg_match_all('/[A-Z]/', $section_name);
                $numbers = preg_match_all('/[0-9]/', $section_name);
                
                if ($capitalLetters !== 1) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly ONE capital letter.']);
                    exit;
                }
                
                if ($numbers !== 1) {
                    echo json_encode(['success' => false, 'message' => 'Section name must contain exactly ONE number.']);
                    exit;
                }
                
                // Check for duplicate section name (excluding current section, since all sections are now 1st Year)
                $db->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ? AND section_id != ?");
                $db->bind(1, $section_name);
                $db->bind(2, $section_id);
                $db->execute();
                $result = $db->single();
                
                if ($result['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Section name already exists']);
                    exit;
                }
                
                // Update section
                $db->query("UPDATE sections SET section_name = ?, year_level = ?, status = ? WHERE section_id = ?");
                $db->bind(1, $section_name);
                $db->bind(2, $year_level);
                $db->bind(3, $status);
                $db->bind(4, $section_id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
                exit;
                
            case 'delete_section':
                $section_id = intval($_POST['section_id'] ?? 0);
                
                if (!$section_id) {
                    echo json_encode(['success' => false, 'message' => 'Section ID is required']);
                    exit;
                }
                
                // Delete section
                $db->query("DELETE FROM sections WHERE section_id = ?");
                $db->bind(1, $section_id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
                exit;
                
            case 'toggle_status':
                $section_id = intval($_POST['section_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                
                if (!$section_id || !$status) {
                    echo json_encode(['success' => false, 'message' => 'Section ID and status are required']);
                    exit;
                }
                
                // Update section status
                $db->query("UPDATE sections SET status = ? WHERE section_id = ?");
                $db->bind(1, $status);
                $db->bind(2, $section_id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                exit;
                
            case 'check_duplicate_section':
                $section_name = trim($_POST['section_name'] ?? '');
                $exclude_id = intval($_POST['exclude_id'] ?? 0);
                
                if (!$section_name) {
                    echo json_encode(['success' => false, 'message' => 'Section name is required']);
    exit;
}

                // Check for duplicate section name
                if ($exclude_id > 0) {
                    // Exclude current section when editing
                    $db->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ? AND section_id != ?");
                    $db->bind(1, $section_name);
                    $db->bind(2, $exclude_id);
                } else {
                    // Check for new section
                    $db->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ?");
                        $db->bind(1, $section_name);
                }
                
                $db->execute();
                $result = $db->single();
                $isDuplicate = $result['count'] > 0;
                
                echo json_encode(['success' => true, 'isDuplicate' => $isDuplicate]);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
                    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
                    }
                }
                
// Fetch existing sections for display
$sections = [];
try {
    // Sort sections by section name only (all sections are now 1st Year)
    $db->query("SELECT * FROM sections ORDER BY section_name ASC");
    $db->execute();
    $sections = $db->resultset();
} catch (Exception $e) {
    // Handle error silently for now
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management - NLS Admin Dashboard</title>
    <meta name="description" content="Manage sections and their availability status in the NLS system">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css" />
    
    <style>
        .table-section-header { display:flex; align-items:center; justify-content:space-between; margin: 16px 0 10px; }
        .table-section-header h3 { margin:0; font-size: 1rem; font-weight:600; }
        
        /* Available/Not Available table color themes */
        #activeSections { border: 1px solid #16a34a22; border-left: 4px solid #16a34a; border-radius: 8px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 1px 6px rgba(22,163,74,0.12); }
        #activeSections thead { background: #16a34a; color: #fff; }
        #activeSections tbody tr:nth-child(even) { background: #16a34a0a; }
        #activeSections tbody tr:hover { background: #16a34a14; }
        #activeSections td, #activeSections th { border-color: #16a34a22; }

        #inactiveSections { border: 1px solid #dc262622; border-left: 4px solid #dc2626; border-radius: 8px; overflow: hidden; margin-top: 24px; box-shadow: 0 1px 6px rgba(220,38,38,0.12); }
        #inactiveSections thead { background: #dc2626; color: #fff; }
        #inactiveSections tbody tr:nth-child(even) { background: #dc26260a; }
        #inactiveSections tbody tr:hover { background: #dc262614; }
        #inactiveSections td, #inactiveSections th { border-color: #dc262622; }

        /* Status indicator colors */
        .status-available { color: #16a34a; background: #16a34a22; padding: 4px 8px; border-radius: 4px; }
        .status-unavailable { color: #dc2626; background: #dc262622; padding: 4px 8px; border-radius: 4px; }

        /* Toggle switch */
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #e5e7eb; transition: .2s; border-radius: 999px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; top: 3px; background: white; transition: .2s; border-radius: 999px; box-shadow: 0 1px 2px rgba(0,0,0,.15); }
        input:checked + .slider { background: #22c55e; }
        input:checked + .slider:before { transform: translateX(20px); }
        
        /* Full-width main area for this page */
        .admin-main { width: 100%; }
        .admin-main .main-content { padding-left: 0; padding-right: 0; max-width: none; }
        .dashboard-card { margin-left: 0; margin-right: 0; width: 100%; }

        .dashboard-card {
            background: #fff;
            border: 1px solid rgba(229,231,235,.6);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 1rem 1rem 1.25rem;
        }

        .management-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0.25rem 0.75rem;
            border-bottom: 1px solid rgba(229,231,235,.6);
            margin-bottom: 0.75rem;
            gap: 1rem;
        }
        .section-title { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .section-title i { color: var(--primary-color); margin-right: .5rem; }
        
        /* Dashboard Navigation Cards */
        .dashboard-navigation-cards {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card-nav {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #1e293b;
            font-weight: 700;
            aspect-ratio: 2/1;
            justify-content: flex-start;
            min-height: 80px;
        }

        .dashboard-card-nav:hover {
            background: rgba(238, 242, 255, 0.2);
            color: #3730a3;
            border-color: rgba(199, 210, 254, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card-nav.active {
            background: rgba(99, 102, 241, 0.3);
            color: #fff;
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .dashboard-card-nav .dashboard-card-icon {
            width: 45px;
            height: 45px;
            background: #6366f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
        }

        .dashboard-card-nav.active .dashboard-card-icon {
            background: #6366f1;
        }

        .dashboard-card-nav .dashboard-card-content {
            text-align: left;
            flex: 1;
        }

        .dashboard-card-nav .dashboard-card-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .dashboard-card-nav .dashboard-card-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 600;
            color: #475569;
        }

        /* Responsive adjustments for dashboard navigation cards */
        @media (max-width: 1200px) {
            .dashboard-navigation-cards {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.25rem;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-navigation-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .dashboard-card-nav {
                padding: 1.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-navigation-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        /* Breadcrumb styling */
        .breadcrumb-navigation {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }
        .breadcrumb-tab {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.95rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .breadcrumb-tab:hover {
            background: #eef2ff;
            color: #3730a3;
            border-color: #c7d2fe;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .breadcrumb-tab.active {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        /* Special styling for Show All tab */
        .breadcrumb-tab[data-year="all"] {
            background: #f59e0b;
            color: #fff;
            border-color: #f59e0b;
        }
        
        .breadcrumb-tab[data-year="all"]:hover {
            background: #d97706;
            border-color: #d97706;
        }
        
        .breadcrumb-tab[data-year="all"].active {
            background: #f59e0b;
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .table-container { width: 100%; overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .table thead th { position: sticky; top: 0; background: #16a34a; color: #fff; font-weight: 600; text-align: center; padding: .75rem .9rem; border-bottom: 1px solid rgba(229,231,235,.9); }
        .table tbody td { padding: .7rem .9rem; border-bottom: 1px solid rgba(229,231,235,.6); }
        .table tbody tr:hover { background: #fafbfd; }
        .table tbody tr:nth-child(even) { background: #fcfdff; }
        .table .muted { text-align: center; color: var(--text-secondary); padding: 1rem; }
        .btn.btn-sm { padding: .35rem .55rem; font-size: .85rem; border-radius: 8px; }
        .btn.btn-secondary { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .btn.btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn.btn-secondary:hover { background: #e0e7ff; }
        .btn.btn-danger:hover { background: #fecaca; }

        /* Form styling */
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        
        @media (max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 1rem; position: relative; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.35rem; color: #374151; }
        .form-input { width: 100%; padding: 0.7rem 0.8rem; border-radius: 10px; border: 1px solid rgba(229,231,235,.9); background: #fff; transition: all 0.2s ease; }
        .form-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-input.error { 
            border-color: #dc2626 !important; 
            border-width: 2px !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2) !important;
            background-color: #fef2f2 !important;
        }
        
        /* Ensure select elements also get proper error styling */
        select.form-input.error {
            background-color: #fef2f2 !important;
            border-color: #dc2626 !important;
            border-width: 2px !important;
        }
        .field-error { 
            display: none; 
            color: #dc2626; 
            margin-top: 0.35rem; 
            font-size: 0.85rem; 
            font-weight: 500; 
            line-height: 1.4;
            padding: 0.25rem 0.5rem;
            min-height: 1.2rem;
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 4px;
            position: relative;
        }
        
        .field-error:before {
            content: "⚠ ";
            margin-right: 0.25rem;
        }


        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 10px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #5855eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        /* Table spacing improvements */
        .table th, .table td {
            padding: 1rem 1.5rem;
            text-align: left;
            vertical-align: middle;
        }
        
        .table th {
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            background-color: #f9fafb;
        }
        
        .table td {
            border-bottom: 1px solid #f3f4f6;
        }
        
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        /* Section Name column alignment - center aligned */
        .table td:nth-child(1) {
            text-align: center;
        }
        
        /* Status column alignment - center aligned */
        .table td:nth-child(2) {
            text-align: center;
        }
        
        /* Actions column alignment - center aligned */
        .table td:nth-child(3) {
            text-align: center;
        }
        
        /* Button spacing in actions column */
        .table td:nth-child(3) .btn {
            margin: 0 0.25rem;
        }

        /* Modal animations */
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
        
        /* Red header for inactive/not available tables */
        #inactiveSections thead th {
            background: #dc2626 !important;
            color: #fff !important;
        }

    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar" id="adminSidebar" aria-expanded="true">
            <div class="sidebar-header">
                <div class="admin-profile">
                    <div class="admin-avatar" onclick="toggleSidebar()"><i class="fas fa-user-shield"></i></div>
                    <div class="admin-info">
                        <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard Overview</span></a></li>
                    <li class="nav-item"><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i><span>Manage User Accounts</span></a></li>
                    <li class="nav-item"><a href="manage-scheduling.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Manage Scheduling Information</span></a></li>
                    <li class="nav-item"><a href="generate-schedule.php" class="nav-link"><i class="fas fa-magic"></i><span>Generate Schedule</span></a></li>
                </ul>
                <div class="sidebar-footer">
                    <a href="../../auth/logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </nav>
        </aside>

        <main class="admin-main" id="adminMain">
            <div class="main-header">
                <h1 class="page-title"><i class="fas fa-users"></i> Sections Dashboard</h1>
                <button id="headerSidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
            
            <!-- Main Dashboard Navigation Cards -->
            <div class="dashboard-navigation-cards">
                <a href="Instructor-dashboard.php" class="dashboard-card-nav" data-dashboard="instructors">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Instructors</h3>
                        <p>Manage Instructors</p>
                    </div>
                </a>
                <a href="Courses-dashboard.php" class="dashboard-card-nav" data-dashboard="courses">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Courses</h3>
                        <p>Manage Courses</p>
                    </div>
                </a>
                <a href="academic-year-dashboard.php" class="dashboard-card-nav" data-dashboard="academic-year">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Academic Year</h3>
                        <p>Manage Academic Years</p>
                    </div>
                </a>
                <a href="rooms-dashboard.php" class="dashboard-card-nav" data-dashboard="rooms">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Rooms</h3>
                        <p>Manage Rooms</p>
                    </div>
                </a>
                <a href="sections-dashboard.php" class="dashboard-card-nav active" data-dashboard="sections">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Sections</h3>
                        <p>Manage Sections</p>
                    </div>
                </a>
                <a href="generate-schedule.php" class="dashboard-card-nav" data-dashboard="generate-schedule">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Generate</h3>
                        <p>Auto Schedule</p>
                    </div>
                </a>


            </div>
            <div class="main-content">
                <div class="dashboard-card">
                    <div class="management-controls">
                        <h2 class="section-title" style="font-size: 1.5rem; font-weight: 700;">
                            <i class="fas fa-users"></i>
                            Section Information
                        </h2>
                        <button type="button" class="btn btn-primary" id="addSectionBtn">
                            <i class="fas fa-plus"></i> Add New Section
                        </button>
                    </div>



                    <!-- Available Sections Table -->
                    <div class="table-section-header">
                        <h3><i class="fas fa-check-circle" style="color: #16a34a;"></i> Available Sections</h3>
                    </div>
                    <div class="table-container">
                        <table class="table" id="activeSections">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Section Name</th>
                                    <th style="width: 25%;">Status</th>
                                    <th style="width: 50%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $activeSections = array_filter($sections, function($section) {
                                    return $section['status'] === 'available';
                                });
                                
                                if (empty($activeSections)): ?>
                                    <tr>
                                        <td colspan="3" class="muted">No available sections found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activeSections as $section): ?>
                                        <tr data-section-id="<?php echo $section['section_id']; ?>">
                                            <td data-year-level="<?php echo htmlspecialchars($section['year_level']); ?>"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" checked class="status-toggle" data-section-id="<?php echo $section['section_id']; ?>">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-text status-available">Available</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary edit-section"
                                                        data-section-id="<?php echo $section['section_id']; ?>"
                                                        data-section-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                        data-status="<?php echo $section['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-section"
                                                        data-section-id="<?php echo $section['section_id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Not Available Sections Table -->
                    <div class="table-section-header">
                        <h3><i class="fas fa-times-circle" style="color: #dc2626;"></i> Not Available Sections</h3>
                    </div>
                    <div class="table-container">
                        <table class="table" id="inactiveSections">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Section Name</th>
                                    <th style="width: 25%;">Status</th>
                                    <th style="width: 50%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $inactiveSections = array_filter($sections, function($section) {
                                    return $section['status'] === 'unavailable';
                                });
                                
                                if (empty($inactiveSections)): ?>
                                    <tr>
                                        <td colspan="3" class="muted">No unavailable sections found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inactiveSections as $section): ?>
                                        <tr data-section-id="<?php echo $section['section_id']; ?>">
                                            <td data-year-level="<?php echo htmlspecialchars($section['year_level']); ?>"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="status-toggle" data-section-id="<?php echo $section['section_id']; ?>">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-text status-unavailable">Not Available</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary edit-section"
                                                        data-section-id="<?php echo $section['section_id']; ?>"
                                                        data-section-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                                                        data-year-level="<?php echo htmlspecialchars($section['year_level']); ?>"
                                                        data-status="<?php echo $section['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-section"
                                                        data-section-id="<?php echo $section['section_id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirm Modal -->
    <div id="confirmModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:3100; background:rgba(17,24,39,.45); padding:1rem;">
        <div class="notice-box" style="background:#fff; width:min(480px,92vw); border-radius:14px; border:1px solid rgba(229,231,235,.9); box-shadow:0 20px 60px rgba(0,0,0,.25); padding:1rem 1.25rem; text-align:center;">
            <div class="notice-header" style="display:flex; align-items:center; justify-content:center; gap:.5rem; margin-bottom:.5rem;">
                <i class="fas fa-exclamation-triangle" style="color:#dc2626" aria-hidden="true"></i>
                <h4>Confirm Action</h4>
            </div>
            <div class="notice-content" style="margin:.5rem 0;">
                <span id="confirmText"></span>
            </div>
            <div class="notice-actions" style="margin-top:.75rem; display:flex; justify-content:center; gap:.5rem;">
                <button id="confirmYes" class="btn btn-primary">Yes, Change Status</button>
                <button id="confirmNo" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    
                
    <!-- Add Section Modal -->
    <div id="addSectionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #111827;">Add New Section</h3>
                <button id="closeAddSectionModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            <form id="addSectionForm">
                <div class="form-grid">
                <div class="form-group">
                        <label for="section_name">Section Name *</label>
                        <input type="text" id="section_name" name="section_name" class="form-input" required>
                        <div class="field-error" id="sectionNameError"></div>
                </div>
                <div class="form-group" style="display: none;">
                        <label for="year_level">Year Level *</label>
                        <select id="year_level" name="year_level" class="form-input" required>
                            <option value="1st Year" selected>1st Year</option>
                        </select>
                        <div class="field-error" id="yearLevelError"></div>
                </div>
                </div>
                <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-input" required>
                            <option value="" disabled selected>Select section status</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                        <div class="field-error" id="statusError"></div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelAddSection">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Section</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div id="editSectionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #111827;">Edit Section</h3>
                <button id="closeEditSectionModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            <form id="editSectionForm">
                <input type="hidden" id="edit_section_id" name="edit_section_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_section_name">Section Name *</label>
                        <input type="text" id="edit_section_name" name="edit_section_name" class="form-input" required>
                        <div class="field-error" id="editSectionNameError"></div>
                    </div>
                    <div class="form-group" style="display: none;">
                        <label for="edit_year_level">Year Level *</label>
                        <select id="edit_year_level" name="edit_year_level" class="form-input" required>
                            <option value="1st Year" selected>1st Year</option>
                        </select>
                        <div class="field-error" id="editYearLevelError"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="edit_status" class="form-input" required>
                        <option value="" disabled>Select section status</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                    <div class="field-error" id="editStatusError"></div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelEditSection">Cancel</button>
                    <button type="submit" class="btn btn-success">Save changes</button>
    </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Success modal function (same as room dashboard)
        function showSuccessModal(title, message) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                animation: modalSlideIn 0.3s ease-out;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    max-width: 450px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                ">
                    <div style="
                        width: 70px;
                        height: 70px;
                        background: #10b981;
                        border-radius: 50%;
                        margin: 0 auto 1.5rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="fas fa-check-circle" style="color: white; font-size: 32px;"></i>
                    </div>
                    <h3 style="
                        margin: 0 0 1rem 0;
                        color: #111827;
                        font-size: 1.5rem;
                        font-weight: 600;
                    ">${title}</h3>
                    <p style="
                        margin: 0 0 2rem 0;
                        color: #6b7280;
                        line-height: 1.6;
                        font-size: 1.1rem;
                    ">${message}</p>
                    <button id="successOkBtn" style="
                        background: #10b981;
                        color: white;
                        border: none;
                        padding: 1rem 2rem;
                        border-radius: 12px;
                        font-weight: 600;
                        font-size: 1.1rem;
                        cursor: pointer;
                        transition: all 0.2s;
                        min-width: 120px;
                    " onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'" 
                       onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                        OK
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listener to OK button
            document.getElementById('successOkBtn').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 4000);
        }

        // Error modal function (same as room dashboard)
        function showErrorModal(title, message) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                animation: modalSlideIn 0.3s ease-out;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    max-width: 450px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                ">
                    <div style="
                        width: 70px;
                        height: 70px;
                        background: #ef4444;
                        border-radius: 50%;
                        margin: 0 auto 1.5rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="fas fa-exclamation-triangle" style="color: white; font-size: 32px;"></i>
                    </div>
                    <h3 style="
                        margin: 0 0 1rem 0;
                        color: #111827;
                        font-size: 1.5rem;
                        font-weight: 600;
                    ">${title}</h3>
                    <p style="
                        margin: 0 0 2rem 0;
                        color: #6b7280;
                        line-height: 1.6;
                        font-size: 1.1rem;
                    ">${message}</p>
                    <button id="errorOkBtn" style="
                        background: #ef4444;
                        color: white;
                        border: none;
                        padding: 1rem 2rem;
                        border-radius: 12px;
                        font-weight: 600;
                        font-size: 1.1rem;
                        cursor: pointer;
                        transition: all 0.2s;
                        min-width: 120px;
                    " onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-2px)'" 
                       onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
                        OK
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listener to OK button
            document.getElementById('errorOkBtn').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
            // Auto-remove after 5 seconds (longer for errors)
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 5000);
        }
        
        // Modal functions
        function showConfirmModal(message, onConfirm, actionType = 'change') {
            const modal = document.getElementById('confirmModal');
            const confirmText = document.getElementById('confirmText');
            const confirmYes = document.getElementById('confirmYes');
            const confirmNo = document.getElementById('confirmNo');
            
            confirmText.textContent = message;
            
            // Update button text based on action type
            if (actionType === 'delete') {
                confirmYes.textContent = 'Yes, Delete';
            } else if (actionType === 'change') {
                confirmYes.textContent = 'Yes, Change Status';
            } else {
                confirmYes.textContent = 'Yes, Confirm';
            }
            
                modal.style.display = 'flex';
            
            const handleConfirm = () => {
                    modal.style.display = 'none';
                confirmYes.removeEventListener('click', handleConfirm);
                confirmNo.removeEventListener('click', handleCancel);
                onConfirm(true);
            };
            
            const handleCancel = () => {
                    modal.style.display = 'none';
                confirmYes.removeEventListener('click', handleConfirm);
                confirmNo.removeEventListener('click', handleCancel);
                onConfirm(false);
            };
            
            confirmYes.addEventListener('click', handleConfirm);
            confirmNo.addEventListener('click', handleCancel);
        }

        // Field error functions
        function showFieldError(elementId, message) {
            console.log('=== showFieldError called ===');
            console.log('Element ID:', elementId);
            console.log('Message:', message);
            
            const errorElement = document.getElementById(elementId);
            
            // Map error element IDs to their corresponding input element IDs
            const inputElementMap = {
                'sectionNameError': 'section_name',
                'statusError': 'status',
                'editSectionNameError': 'edit_section_name',
                'editStatusError': 'edit_status'
            };
            
            const inputElementId = inputElementMap[elementId];
            const inputElement = inputElementId ? document.getElementById(inputElementId) : null;
            
            console.log('Error element found:', !!errorElement);
            console.log('Input element ID mapped to:', inputElementId);
            console.log('Input element found:', !!inputElement);
            
            if (errorElement) {
                console.log('Error element details:', {
                    id: errorElement.id,
                    display: errorElement.style.display,
                    computedDisplay: window.getComputedStyle(errorElement).display,
                    className: errorElement.className,
                    textContent: errorElement.textContent
                });
            }
            
            if (errorElement && inputElement) {
                console.log('Setting error message...');
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
                
                console.log('After setting error:');
                console.log('Error element display style:', errorElement.style.display);
                console.log('Error element text:', errorElement.textContent);
                console.log('Input element classes:', inputElement.className);
                
                // Force a reflow to ensure the change is applied
                errorElement.offsetHeight;
                
                console.log('After reflow - computed display:', window.getComputedStyle(errorElement).display);
            } else {
                console.log('Missing elements for field error:', elementId);
                if (!errorElement) console.log('Error element not found');
                if (!inputElement) console.log('Input element not found');
            }
        }

        function clearFieldError(elementId) {
            const errorElement = document.getElementById(elementId);
            
            // Map error element IDs to their corresponding input element IDs
            const inputElementMap = {
                'sectionNameError': 'section_name',
                'statusError': 'status',
                'editSectionNameError': 'edit_section_name',
                'editStatusError': 'edit_status'
            };
            
            const inputElementId = inputElementMap[elementId];
            const inputElement = inputElementId ? document.getElementById(inputElementId) : null;
            
            if (errorElement && inputElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                inputElement.classList.remove('error');
            }
        }
        
        // Function to sort table rows alphabetically by section name
        function sortTableAlphabetically(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr:not(.muted)'));
            const emptyRow = tbody.querySelector('tr.muted');
            
            // Sort rows by section name (1st column)
            rows.sort((a, b) => {
                const nameA = a.querySelector('td:nth-child(1)').textContent.trim();
                const nameB = b.querySelector('td:nth-child(1)').textContent.trim();
                return nameA.localeCompare(nameB, undefined, {sensitivity: 'base'});
            });
            
            // Remove all rows (except empty row)
            rows.forEach(row => row.remove());
            
            // Re-insert rows in sorted order
            rows.forEach(row => tbody.appendChild(row));
            
            // Re-add empty row if it existed
            if (emptyRow) {
                tbody.appendChild(emptyRow);
            }
        }

        // Add Section functionality
        document.getElementById('addSectionBtn').addEventListener('click', () => {
            document.getElementById('addSectionModal').style.display = 'flex';
        });

        document.getElementById('closeAddSectionModal').addEventListener('click', () => {
            document.getElementById('addSectionModal').style.display = 'none';
        });

        document.getElementById('cancelAddSection').addEventListener('click', () => {
            document.getElementById('addSectionModal').style.display = 'none';
        });

        // Edit Section functionality
        document.getElementById('closeEditSectionModal').addEventListener('click', () => {
            document.getElementById('editSectionModal').style.display = 'none';
        });

        document.getElementById('cancelEditSection').addEventListener('click', () => {
            document.getElementById('editSectionModal').style.display = 'none';
        });

        // Form submissions
        document.getElementById('addSectionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'add_section');
            
            try {
                const response = await fetch('sections-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccessModal('Success', 'Section added successfully!');
                    document.getElementById('addSectionModal').style.display = 'none';
                    e.target.reset();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
            } else {
                    showErrorModal('Error', data.message || 'Failed to add section');
                }
            } catch (err) {
                showErrorModal('Cannot save Section information', err.message || 'Error adding section');
            }
        });

        document.getElementById('editSectionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'edit_section');
            
            try {
                const response = await fetch('sections-dashboard.php', {
                method: 'POST',
                body: formData
                });
                
                const data = await response.json();
                    
                    if (data.success) {
                    showSuccessModal('Success', 'Section updated successfully!');
                    document.getElementById('editSectionModal').style.display = 'none';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                    showErrorModal('Error', data.message || 'Failed to update section');
                }
            } catch (err) {
                showErrorModal('Error', err.message || 'Error updating section');
            }
        });

        // Status toggle functionality
        document.addEventListener('change', async (e) => {
            if (e.target.classList.contains('status-toggle')) {
                const toggle = e.target;
                const sectionId = toggle.dataset.sectionId;
                const newStatus = toggle.checked ? 'available' : 'unavailable';
                const statusText = toggle.closest('td').querySelector('.status-text');
                
                // Update status text immediately
                if (statusText) {
                    statusText.textContent = newStatus === 'available' ? 'Available' : 'Not Available';
                    statusText.className = `status-text ${newStatus === 'available' ? 'status-available' : 'status-unavailable'}`;
                }
                
                // Show confirmation
                showConfirmModal(`Are you sure you want to change this section status to ${newStatus === 'available' ? 'Available' : 'Not Available'}?`, async (confirmed) => {
                    if (confirmed) {
                        try {
                            const formData = new FormData();
                            formData.append('action', 'toggle_status');
                            formData.append('section_id', sectionId);
                            formData.append('status', newStatus);

                            const response = await fetch('sections-dashboard.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                // Move row to appropriate table
                                const row = toggle.closest('tr');
                                const currentTable = row.closest('table');
                                const targetTable = newStatus === 'available' ? 
                                    document.getElementById('activeSections') : 
                                    document.getElementById('inactiveSections');
                                
                                if (currentTable && targetTable) {
                                    const targetTbody = targetTable.querySelector('tbody');
                                    const currentTbody = currentTable.querySelector('tbody');
                                    
                                    // Remove placeholder if exists
                                    const placeholder = targetTbody.querySelector('.muted');
                                    if (placeholder) {
                                        placeholder.closest('tr').remove();
                                    }
                                    
                                    // Get the section name for sorting
                                    const sectionName = row.querySelector('td:nth-child(1)').textContent.trim();
                                    
                                    // Find the correct position to insert the row to maintain alphabetical order
                                    const existingRows = targetTbody.querySelectorAll('tr:not(.muted)');
                                    let insertPosition = null;
                                    
                                    // Use localeCompare for proper alphabetical sorting
                                    for (let i = 0; i < existingRows.length; i++) {
                                        const existingName = existingRows[i].querySelector('td:nth-child(1)').textContent.trim();
                                        if (sectionName.localeCompare(existingName, undefined, {sensitivity: 'base'}) < 0) {
                                            insertPosition = existingRows[i];
                                            break;
                                        }
                                    }
                                    
                                    // Remove row from current table
                                    row.remove();
                                    
                                    // Insert the row at the correct position
                                    if (insertPosition) {
                                        targetTbody.insertBefore(row, insertPosition);
                                    } else {
                                        // If no position found, append to the end
                                        targetTbody.appendChild(row);
                                    }
                                    
                                    // Add placeholder to source table if empty
                                    if (currentTbody.querySelectorAll('tr').length === 0) {
                                        const placeholderRow = document.createElement('tr');
                                        placeholderRow.innerHTML = `<td colspan="3" class="muted">No ${currentTable.id === 'activeSections' ? 'available' : 'unavailable'} sections found</td>`;
                                        currentTbody.appendChild(placeholderRow);
                                    }
                                    
                                    // Rebuild year level separators after moving rows
                                    // rebuildYearLevelSeparators(); // No longer needed
                                }
                                
                                showSuccessModal('Success', 'Section status updated successfully!');
                            } else {
                                // Revert toggle if failed
                                toggle.checked = !toggle.checked;
                                if (statusText) {
                                    statusText.textContent = toggle.checked ? 'Available' : 'Not Available';
                                    statusText.className = `status-text ${toggle.checked ? 'status-available' : 'status-unavailable'}`;
                                }
                                showErrorModal('Error', data.message || 'Failed to update status');
                            }
                        } catch (err) {
                            // Revert toggle if failed
                            toggle.checked = !toggle.checked;
                            if (statusText) {
                                statusText.textContent = toggle.checked ? 'Available' : 'Not Available';
                                statusText.className = `status-text ${toggle.checked ? 'status-available' : 'status-unavailable'}`;
                            }
                            showErrorModal('Error', err.message || 'Error updating status');
                        }
                    } else {
                        // Revert toggle if cancelled
                        toggle.checked = !toggle.checked;
                        if (statusText) {
                            statusText.textContent = toggle.checked ? 'Available' : 'Not Available';
                            statusText.className = `status-text ${toggle.checked ? 'status-available' : 'status-unavailable'}`;
                        }
                    }
                }, 'change');
            }
        });

        // Delete functionality
        async function handleDeleteSection(deleteBtn) {
            const sectionId = deleteBtn.dataset.sectionId;
            const sectionName = deleteBtn.dataset.sectionName;
            
                        showConfirmModal(`Are you sure you want to delete section "${sectionName}"? This action cannot be undone.`, async (confirmed) => {
                if (confirmed) {
                    try {
            const formData = new FormData();
            formData.append('action', 'delete_section');
            formData.append('section_id', sectionId);
            
                        const response = await fetch('sections-dashboard.php', {
                method: 'POST',
                body: formData
                        });
                        
                        const data = await response.json();
                    
                    if (data.success) {
                            showSuccessModal('Success', 'Section deleted successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                            showErrorModal('Error', data.message || 'Failed to delete section');
                        }
                    } catch (err) {
                        showErrorModal('Error', err.message || 'Error deleting section');
                    }
                }
            }, 'delete');
        }

        // Edit functionality
        function handleEditSection(editBtn) {
            const sectionId = editBtn.dataset.sectionId;
            const sectionName = editBtn.dataset.sectionName;
            const status = editBtn.dataset.status;
            
            document.getElementById('edit_section_id').value = sectionId;
            document.getElementById('edit_section_name').value = sectionName;
            // Year level is always "1st Year" now
            document.getElementById('edit_status').value = status;
            
            document.getElementById('editSectionModal').style.display = 'flex';
            
            // Attach validation event listeners when edit modal opens
            const editSectionNameInput = document.getElementById('edit_section_name');
            const editYearLevelSelect = document.getElementById('edit_year_level');
            const editStatusSelect = document.getElementById('edit_status');
            
            // Remove existing listeners to avoid duplicates
            editSectionNameInput.removeEventListener('input', validateEditSectionName);
            editSectionNameInput.removeEventListener('keypress', preventSpecialChars);
            editSectionNameInput.removeEventListener('paste', preventSpecialCharsPaste);
            // editYearLevelSelect.removeEventListener('change', validateEditYearLevel);
            editStatusSelect.removeEventListener('change', validateEditStatus);
            
            // Add validation listeners
            editSectionNameInput.addEventListener('input', validateEditSectionName);
            editSectionNameInput.addEventListener('input', convertToUppercase);
            editSectionNameInput.addEventListener('keypress', preventSpecialChars);
            editSectionNameInput.addEventListener('paste', preventSpecialCharsPaste);
            // editYearLevelSelect.addEventListener('change', validateEditYearLevel);
            editStatusSelect.addEventListener('change', validateEditStatus);
        }



        // Event delegation for edit and delete buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-section')) {
                const editBtn = e.target.closest('.edit-section');
                handleEditSection(editBtn);
            } else if (e.target.closest('.delete-section')) {
                const deleteBtn = e.target.closest('.delete-section');
                handleDeleteSection(deleteBtn);
            }
        });

        // Add Section functionality
        document.getElementById('addSectionBtn').addEventListener('click', () => {
            console.log('=== Add Section Button Clicked ===');
            
            // Show modal first
            const modal = document.getElementById('addSectionModal');
            modal.style.display = 'flex';
            console.log('Modal display set to flex');
            
            // Wait a bit for modal to be fully rendered
                setTimeout(() => {
                console.log('=== Setting up modal validation ===');
                
                const sectionNameInput = document.getElementById('section_name');
                const yearLevelSelect = document.getElementById('year_level');
                const statusSelect = document.getElementById('status');
                const sectionNameError = document.getElementById('sectionNameError');
                const yearLevelError = document.getElementById('yearLevelError');
                const statusError = document.getElementById('statusError');
                
                console.log('Modal elements found:', {
                    modal: !!modal,
                    sectionNameInput: !!sectionNameInput,
                    yearLevelSelect: !!yearLevelSelect,
                    statusSelect: !!statusSelect,
                    sectionNameError: !!sectionNameError,
                    yearLevelError: !!yearLevelError,
                    statusError: !!statusError
                });
                
                if (!sectionNameInput || !yearLevelSelect || !statusSelect || !sectionNameError || !yearLevelError || !statusError) {
                    console.error('Missing modal elements!');
                    return;
                }
                
                // Remove existing listeners to avoid duplicates
                sectionNameInput.removeEventListener('input', validateSectionName);
                sectionNameInput.removeEventListener('keypress', preventSpecialChars);
                sectionNameInput.removeEventListener('paste', preventSpecialCharsPaste);
                statusSelect.removeEventListener('change', validateStatus);
                
                // Add validation listeners
                sectionNameInput.addEventListener('input', validateSectionName);
                sectionNameInput.addEventListener('input', preventSpecialChars);
                sectionNameInput.addEventListener('input', convertToUppercase);
                sectionNameInput.addEventListener('paste', preventSpecialCharsPaste);
                statusSelect.addEventListener('change', validateStatus);
                
                console.log('Event listeners attached successfully');
                
                // Clear any existing error messages
                clearFieldError('sectionNameError');
                clearFieldError('statusError');
                
                console.log('=== Modal validation setup complete ===');
            }, 200); // Increased timeout to ensure modal is fully rendered
        });

        // Edit Section functionality
        document.getElementById('closeEditSectionModal').addEventListener('click', () => {
            document.getElementById('editSectionModal').style.display = 'none';
        });

        document.getElementById('cancelEditSection').addEventListener('click', () => {
            document.getElementById('editSectionModal').style.display = 'none';
        });

        // Validation functions
        async function validateSectionName() {
            const value = this.value;
            console.log('=== validateSectionName called ===');
            console.log('Input value:', value);
            console.log('This element:', this);
            
            // Only validate if there's a value
            if (!value.trim()) {
                console.log('Empty value, showing required error');
                showFieldError('sectionNameError', 'Section name is required');
                return;
            }
            
            // Check length limit first - must be exactly 2 characters
            if (value.length !== 2) {
                console.log('Invalid length detected:', value.length);
                showFieldError('sectionNameError', 'Section name must contain exactly one capital letter and one number (2 characters total).');
                return;
            }
            
            // Check for special characters (but don't auto-clean)
            if (!/^[A-Z0-9]+$/.test(value)) {
                console.log('Special characters detected, showing error...');
                showFieldError('sectionNameError', 'Special characters are not allowed. Only capital letters and numbers are permitted.');
                return;
            }
            
            // Check the length limit: exactly one capital letter and one number only
            const capitalLetters = value.match(/[A-Z]/g) || [];
            const lowercaseLetters = value.match(/[a-z]/g) || [];
            const numbers = value.match(/[0-9]/g) || [];
            
            if (capitalLetters.length > 1) {
                console.log('Too many capital letters detected');
                showFieldError('sectionNameError', 'Section name can only contain ONE capital letter. You have ' + capitalLetters.length + ' capital letters.');
                return;
            }
            
            if (lowercaseLetters.length > 0) {
                console.log('Lowercase letters detected');
                showFieldError('sectionNameError', 'Section name must contain only capital letters. Lowercase letters are not allowed.');
                return;
            }
            
            if (numbers.length > 1) {
                console.log('Too many numbers detected');
                showFieldError('sectionNameError', 'Section name can only contain ONE number. You have ' + numbers.length + ' numbers.');
                return;
            }

            if (capitalLetters.length === 0) {
                console.log('No capital letters detected');
                showFieldError('sectionNameError', 'Section name must contain exactly ONE capital letter.');
                return;
            }
            
            if (numbers.length === 0) {
                console.log('No numbers detected');
                showFieldError('sectionNameError', 'Section name must contain exactly ONE number.');
                return;
            }
            
            // If we get here, the input is valid (exactly one capital letter and one number)
            clearFieldError('sectionNameError');
            
            // Check for duplicates only if input is valid
            console.log('Checking for duplicates...');
            const trimmedValue = value.trim();
            if (trimmedValue) {
                const isDuplicate = await checkDuplicateSectionName(trimmedValue);
                console.log('Duplicate check result:', isDuplicate);
                if (isDuplicate) {
                    showFieldError('sectionNameError', 'Section name already exists. Please choose a different name.');
                }
            }
        }

        function validateStatus() {
            if (!this.value) {
                showFieldError('statusError', 'Please select a status');
            } else {
                clearFieldError('statusError');
            }
        }

        // function validateYearLevel() {
        //     if (!this.value) {
        //         showFieldError('yearLevelError', 'Please select a year level');
        //     } else {
        //         clearFieldError('yearLevelError');
        //     }
        // }

        // function validateEditYearLevel() {
        //     if (!this.value) {
        //         clearFieldError('editYearLevelError');
        //     } else {
        //         clearFieldError('editYearLevelError');
        //     }
        // }



        async function validateEditSectionName() {
            const value = this.value;
            
            // Only validate if there's a value
            if (!value.trim()) {
                showFieldError('editSectionNameError', 'Section name is required');
                return;
            }
            
            // Check length limit first - must be exactly 2 characters
            if (value.length !== 2) {
                showFieldError('editSectionNameError', 'Section name must contain exactly one capital letter and one number (2 characters total).');
                return;
            }
            
            // Check for special characters (but don't auto-clean)
            if (!/^[A-Z0-9]+$/.test(value)) {
                showFieldError('editSectionNameError', 'Special characters are not allowed. Only capital letters and numbers are permitted.');
                return;
            }
            
            // Check the length limit: exactly one capital letter and one number only
            const capitalLetters = value.match(/[A-Z]/g) || [];
            const lowercaseLetters = value.match(/[a-z]/g) || [];
            const numbers = value.match(/[0-9]/g) || [];
            
            if (capitalLetters.length > 1) {
                showFieldError('editSectionNameError', 'Section name can only contain ONE capital letter. You have ' + capitalLetters.length + ' capital letters.');
                return;
            }
            
            if (lowercaseLetters.length > 0) {
                showFieldError('editSectionNameError', 'Section name must contain only capital letters. Lowercase letters are not allowed.');
                return;
            }
            
            if (numbers.length > 1) {
                showFieldError('editSectionNameError', 'Section name can only contain ONE number. You have ' + numbers.length + ' numbers.');
                return;
            }
            
            if (capitalLetters.length === 0) {
                showFieldError('editSectionNameError', 'Section name must contain exactly ONE capital letter.');
                return;
            }
            
            if (numbers.length === 0) {
                showFieldError('editSectionNameError', 'Section name must contain exactly ONE number.');
                return;
            }
            
            // If we get here, the input is valid (exactly one capital letter and one number)
            clearFieldError('editSectionNameError');
            
            // Check for duplicates only if input is valid
            const trimmedValue = value.trim();
            if (trimmedValue) {
                const currentSectionId = document.getElementById('edit_section_id').value;
                const isDuplicate = await checkDuplicateSectionName(trimmedValue, currentSectionId);
                if (isDuplicate) {
                    showFieldError('editSectionNameError', 'Section name already exists. Please choose a different name.');
                }
            }
        }

                function validateEditStatus() {
            if (!this.value) {
                showFieldError('editStatusError', 'Please select a status');
            } else {
                clearFieldError('editStatusError');
            }
        }



        // Convert lowercase letters to uppercase
        function convertToUppercase(e) {
            const value = e.target.value;
            const uppercaseValue = value.toUpperCase();
            if (value !== uppercaseValue) {
                e.target.value = uppercaseValue;
            }
        }

        // Prevent special characters and enforce exactly one letter + one number limit
        function preventSpecialChars(e) {
            // Use the key property instead of deprecated keyCode/charCode
            const key = e.key;
            
            // Allow navigation keys
            if (key === 'Backspace' || 
                key === 'Delete' || 
                key === 'ArrowLeft' || 
                key === 'ArrowRight' || 
                key === 'ArrowUp' || 
                key === 'ArrowDown' ||
                key === 'Tab' ||
                key === 'Enter') {
                return true;
            }
            
            // Check current value length - if already 2 characters, prevent any new input
            const currentValue = e.target.value;
            if (currentValue.length >= 2) {
                e.preventDefault();
                showFieldError('sectionNameError', 'Section name can only contain exactly one capital letter and one number (2 characters total).');
                return false;
            }
            
            // Check if it's a capital letter or number
            if (/^[A-Z0-9]$/.test(key)) {
                const capitalLetters = currentValue.match(/[A-Z]/g) || [];
                const numbers = currentValue.match(/[0-9]/g) || [];
                
                // If adding a capital letter, check if we already have one
                if (/^[A-Z]$/.test(key) && capitalLetters.length >= 1) {
                    e.preventDefault();
                    showFieldError('sectionNameError', 'Section name can only contain ONE capital letter. You already have a capital letter.');
                    return false;
                }
                
                // If adding a number, check if we already have one
                if (/^[0-9]$/.test(key) && numbers.length >= 1) {
                    e.preventDefault();
                    showFieldError('sectionNameError', 'Section name can only contain ONE number. You already have a number.');
                    return false;
                }
                
                // Allow the character
                return true;
            }
            
            // Check if it's a lowercase letter and prevent it
            if (/^[a-z]$/.test(key)) {
                e.preventDefault();
                showFieldError('sectionNameError', 'Only capital letters are allowed. Please use uppercase letters.');
                return false;
            }
            
            // Invalid key, prevent it
            e.preventDefault();
            showFieldError('sectionNameError', 'Special characters are not allowed. Only letters and numbers are permitted.');
            return false;
        }

        // Prevent special characters from being pasted
        function preventSpecialCharsPaste(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanText = pastedText.replace(/[^A-Z0-9]/g, '');
            
            // Limit to exactly 2 characters
            const limitedText = cleanText.substring(0, 2);
            
            if (cleanText !== pastedText) {
                showFieldError('sectionNameError', 'Only capital letters and numbers are allowed. Other characters were removed from pasted text.');
            }
            
            if (cleanText.length > 2) {
                showFieldError('sectionNameError', 'Section name can only contain exactly one capital letter and one number (2 characters total). Extra characters were removed.');
            }
            
            // Insert only the limited text
            const target = e.target;
            const start = target.selectionStart;
            const end = target.selectionEnd;
            const currentValue = target.value;
            target.value = currentValue.substring(0, start) + limitedText + currentValue.substring(end);
            
            // Set cursor position
            target.selectionStart = target.selectionEnd = start + limitedText.length;
            
            // Clear error after a moment
                setTimeout(() => {
                clearFieldError('sectionNameError');
            }, 2000);
        }

        // Check for duplicate section names
        async function checkDuplicateSectionName(sectionName, excludeId = null) {
            try {
                const formData = new FormData();
                formData.append('action', 'check_duplicate_section');
                formData.append('section_name', sectionName);
                if (excludeId) {
                    formData.append('exclude_id', excludeId);
                }
                
                const response = await fetch('sections-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                return data.isDuplicate;
            } catch (error) {
                console.error('Error checking duplicate:', error);
                return false;
            }
        }
        
        // Ensure tables are sorted alphabetically when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Sort both tables alphabetically by section name
            sortTableAlphabetically('activeSections');
            sortTableAlphabetically('inactiveSections');
            
            // Rebuild year level separators after sorting
            // rebuildYearLevelSeparators(); // No longer needed
            
                    // Year level breadcrumb functionality removed - all sections are now 1st Year
            
            // Initialize dashboard navigation breadcrumb functionality
            initializeDashboardNavigation();
        });
        
        // Year level functionality removed - all sections are now 1st Year
        function initializeYearLevelBreadcrumb() {
            const yearLevelTabs = document.querySelectorAll('.breadcrumb-tab[data-year]');
            
            // Year level tab click handlers
            yearLevelTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all year level tabs
                    yearLevelTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Get the selected year level
                    const selectedYear = this.dataset.year;
                    
                    // Filter sections by the selected year level
                    filterSectionsByYearLevel(selectedYear);
                    
                    // Auto-scroll to tables section
                    scrollToTables();
                });
            });
        }
        
        // Function to scroll to tables section
        function scrollToTables() {
            const tablesSection = document.querySelector('.table-container');
            if (tablesSection) {
                tablesSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }
        
        // Filter sections by year level
        function filterSectionsByYearLevel(yearLevel) {
            const activeTable = document.getElementById('activeSections');
            const inactiveTable = document.getElementById('inactiveSections');
            
            if (!activeTable || !inactiveTable) {
                console.error('Tables not found');
                return;
            }
            
            // Show/hide rows based on year level
            const allRows = [...activeTable.querySelectorAll('tbody tr'), ...inactiveTable.querySelectorAll('tbody tr')];
            
            let visibleCount = 0;
            let hiddenCount = 0;
            
            allRows.forEach(row => {
                // Show/hide section rows based on year level
                if (yearLevel === 'all') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    const yearLevelCell = row.querySelector('td[data-year-level]');
                    if (yearLevelCell) {
                        const rowYearLevel = yearLevelCell.getAttribute('data-year-level');
                        if (rowYearLevel === yearLevel) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                            hiddenCount++;
                        }
                    }
                }
            });
            
            // Show message if no sections found for specific year level
            if (yearLevel !== 'all' && visibleCount === 0) {
                showNoSectionsMessage(yearLevel);
            } else {
                hideNoSectionsMessage();
            }
            
            // Update the year courses breadcrumb to show current selection
            const yearCoursesBreadcrumb = document.querySelector('.year-courses-breadcrumb h2');
            if (yearCoursesBreadcrumb) {
                if (yearLevel === 'all') {
                    yearCoursesBreadcrumb.innerHTML = `<i class="fas fa-graduation-cap" style="color: #6366f1; margin-right: 0.5rem;"></i>All Year Sections`;
                } else {
                    const yearDisplay = yearLevel === '1st Year' ? 'First Year' : 
                                      yearLevel === '2nd Year' ? 'Second Year' : 
                                      yearLevel === '3rd Year' ? 'Third Year' : 
                                      yearLevel === '4th Year' ? 'Fourth Year' : yearLevel;
                    yearCoursesBreadcrumb.innerHTML = `<i class="fas fa-graduation-cap" style="color: #6366f1; margin-right: 0.5rem;"></i>${yearDisplay} Sections`;
                }
            }
            
            console.log(`Filtered sections by year level: ${yearLevel}`);
            console.log(`Visible rows: ${visibleCount}, Hidden rows: ${hiddenCount}`);
        }
        
        // Function to show message when no sections found for a year level
        function showNoSectionsMessage(yearLevel) {
            // Remove existing no-sections message
            hideNoSectionsMessage();
            
            const yearDisplay = yearLevel === '1st Year' ? 'First Year' : 
                              yearLevel === '2nd Year' ? 'Second Year' : 
                              yearLevel === '3rd Year' ? 'Third Year' : 
                              yearLevel === '4th Year' ? 'Fourth Year' : yearLevel;
            
            const message = `No ${yearDisplay} sections found.`;
            
            // Add message to both tables
            const activeTable = document.getElementById('activeSections');
            const inactiveTable = document.getElementById('inactiveSections');
            
            if (activeTable) {
                const tbody = activeTable.querySelector('tbody');
                if (tbody) {
                    const noSectionsRow = document.createElement('tr');
                    noSectionsRow.className = 'no-sections-message';
                    noSectionsRow.innerHTML = `<td colspan="3" class="muted">${message}</td>`;
                    tbody.appendChild(noSectionsRow);
                }
            }
            
            if (inactiveTable) {
                const tbody = inactiveTable.querySelector('tbody');
                if (tbody) {
                    const noSectionsRow = document.createElement('tr');
                    noSectionsRow.className = 'no-sections-message';
                    noSectionsRow.innerHTML = `<td colspan="3" class="muted">${message}</td>`;
                    tbody.appendChild(noSectionsRow);
                }
            }
        }
        
        // Function to hide no-sections message
        function hideNoSectionsMessage() {
            const messages = document.querySelectorAll('.no-sections-message');
            messages.forEach(msg => msg.remove());
        }
        
        // Initialize dashboard navigation cards
        function initializeDashboardNavigation() {
            const dashboardCards = document.querySelectorAll('.dashboard-card-nav[data-dashboard]');
            
            // Add click handlers for dashboard navigation
            dashboardCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Remove active class from all dashboard cards
                    dashboardCards.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Navigate to the selected dashboard
                    const dashboard = this.dataset.dashboard;
                    const href = this.getAttribute('href');
                    
                    if (href && href !== '#') {
                        // Add a small delay to show the active state before navigation
                        setTimeout(() => {
                            window.location.href = href;
                        }, 150);
                    }
                });
            });
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

