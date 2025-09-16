<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

// Department breadcrumbs
$departments = [
        'College of Communication and Information Technology' => 'CCIT',
        'College of Teacher Education' => 'CTE'
    ];
$current_department = isset($_GET['department']) && in_array($_GET['department'], array_keys($departments)) ? $_GET['department'] : 'College of Communication and Information Technology';

// Ensure required classes are loaded before handling any POST actions
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../config/database.php';

// Toggle status (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    try {
        $user_id = trim($_POST['user_id'] ?? '');
        $new_status = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'approved';
        if (!$user_id) throw new Exception('Missing user id');
        $db = new Database();
        $db->query('UPDATE users SET status = :status WHERE user_id = :user_id');
        $db->bind(':status', $new_status);
        $db->bind(':user_id', $user_id);
        $db->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $new_status]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Delete teacher (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_teacher') {
    try {
        $user_id = trim($_POST['user_id'] ?? '');
        if (!$user_id) throw new Exception('Missing user id');
        $db = new Database();
        // delete role-specific first (FK safety)
        $db->query('DELETE FROM teachers WHERE user_id = :user_id');
        $db->bind(':user_id', $user_id);
        $db->execute();
        $db->query('DELETE FROM users WHERE user_id = :user_id');
        $db->bind(':user_id', $user_id);
        $db->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Edit teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_teacher') {
    $edit_user_id = trim($_POST['user_id'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $teacher_id = trim($_POST['teacher_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status     = ($_POST['status'] ?? 'approved') === 'inactive' ? 'inactive' : 'approved';

	$old_edit = compact('full_name','teacher_id','department','status');
    $errors = [];
    if (!$edit_user_id) { $errors[] = 'Missing user id.'; }
            if (!$full_name || !preg_match('/^[A-Za-zÀ-ÿ\s.\'-]+$/u', $full_name)) { $errors[] = 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only.'; }
            if (!$teacher_id || !preg_match('/^[A-Z]{2}[0-9]+$|^[0-9]+$/', $teacher_id)) { $errors[] = 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers.'; }
    $allowed_departments = [
        'College of Communication and Information Technology',
        'College of Teacher Education'
    ];
    if (!$department || !in_array($department, $allowed_departments, true)) { $errors[] = 'Please select a valid department.'; }

    if (empty($errors)) {
        try {
            $db = new Database();
            
            // Check for duplicates before updating (excluding current user)
            // Check if full name already exists for other teachers
            $db->query('SELECT COUNT(*) as count FROM users WHERE LOWER(full_name) = LOWER(:full_name) AND role = "teacher" AND user_id != :user_id');
            $db->bind(':full_name', $full_name);
            $db->bind(':user_id', $edit_user_id);
            $db->execute();
            $nameResult = $db->single();
            
            if ($nameResult['count'] > 0) {
                $errors[] = 'Instructor full name already exists for another teacher.';
                throw new Exception('Instructor full name already exists for another teacher.');
            }
            
            // Check if instructor ID already exists for other teachers
            $db->query('SELECT COUNT(*) as count FROM teachers WHERE LOWER(teacher_id) = LOWER(:teacher_id) AND user_id != :user_id');
            $db->bind(':teacher_id', $teacher_id);
            $db->bind(':user_id', $edit_user_id);
            $db->execute();
            $idResult = $db->single();
            
            if ($idResult['count'] > 0) {
                $errors[] = 'Instructor ID already exists for another teacher.';
                throw new Exception('Instructor ID already exists for another teacher.');
            }
            
            // update users
			$db->query('UPDATE users SET full_name = :full_name, status = :status WHERE user_id = :user_id');
            $db->bind(':full_name', $full_name);
            $db->bind(':status', $status);
            $db->bind(':user_id', $edit_user_id);
            $db->execute();
            // update teachers
            $db->query('UPDATE teachers SET teacher_id = :teacher_id, department = :department WHERE user_id = :user_id');
            $db->bind(':teacher_id', $teacher_id);
            $db->bind(':department', $department);
            $db->bind(':user_id', $edit_user_id);
            $db->execute();
            $message = 'Teacher updated successfully.';
            $message_type = 'success';

            // If AJAX request, return JSON and exit
            if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);
                exit;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
            if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }
    } else {
        $message = implode(' ', $errors);
        $message_type = 'error';
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
}

$message = null;
$message_type = 'info';
$old = ['full_name' => '', 'teacher_id' => '', 'department' => ''];
$errors_map = [];

// Handle Add New Teacher submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_teacher') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $teacher_id = trim($_POST['teacher_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $status     = ($_POST['status'] ?? 'approved') === 'inactive' ? 'inactive' : 'approved';

    // preserve
    $old = [
        'full_name' => $full_name,
        'teacher_id' => $teacher_id,
        'department' => $department,
    ];

    $errors = [];
    // Instructor full name: letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only
    if (!$full_name || !preg_match('/^[A-Za-zÀ-ÿ\s.\'-]+$/u', $full_name)) {
        $errors[] = 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only';
        $errors_map['full_name'] = 'Letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only';
    }
    // Instructor ID: letters and numbers only
    if (!$teacher_id || !preg_match('/^[A-Z]{2}[0-9]+$|^[0-9]+$/', $teacher_id)) {
        $errors[] = 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers.';
        $errors_map['teacher_id'] = 'Only numbers, or exactly 2 capital letters followed by numbers';
    }
    // Department: must be one of two options
    $allowed_departments = [
        'College of Communication and Information Technology',
        'College of Teacher Education'
    ];
    if (!$department || !in_array($department, $allowed_departments, true)) {
        $errors[] = 'Please select a valid department.';
        $errors_map['department'] = 'Select one from the list';
    }
    if (empty($errors)) {
        try {
            // Check for duplicates before inserting
            $db = new Database();
            
            // Check if full name already exists
            $db->query('SELECT COUNT(*) as count FROM users WHERE LOWER(full_name) = LOWER(:full_name) AND role = "teacher"');
            $db->bind(':full_name', $full_name);
            $db->execute();
            $nameResult = $db->single();
            
            if ($nameResult['count'] > 0) {
                $errors[] = 'Instructor full name already exists in the system.';
                $errors_map['full_name'] = 'Instructor full name already exists';
                throw new Exception('Instructor full name already exists in the system.');
            }
            
            // Check if instructor ID already exists
            $db->query('SELECT COUNT(*) as count FROM teachers WHERE LOWER(teacher_id) = LOWER(:teacher_id)');
            $db->bind(':teacher_id', $teacher_id);
            $db->execute();
            $idResult = $db->single();
            
            if ($idResult['count'] > 0) {
                $errors[] = 'Instructor ID already exists in the system.';
                $errors_map['teacher_id'] = 'Instructor ID already exists';
                throw new Exception('Instructor ID already exists in the system.');
            }
            
            // Direct DB insert to allow status and no password entry

            // Generate user_id similar to User::generateUserId for role 'teacher'
            $prefix = strtoupper(substr('teacher', 0, 3));
            $timestamp = date('ymd');
            $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $user_id = $prefix . $timestamp . $random;

            // Set default password for teachers (they can change it later)
            $defaultPassword = 'Teacher123!';
            $password_hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            // Insert into users
            $db->query('INSERT INTO users (user_id, full_name, email, password_hash, role, status, email_verified) 
					VALUES (:user_id, :full_name, NULL, :password_hash, :role, :status, :email_verified)');
            $db->bind(':user_id', $user_id);
            $db->bind(':full_name', $full_name);
            $db->bind(':password_hash', $password_hash);
            $db->bind(':role', 'teacher');
            $db->bind(':status', $status);
            $db->bind(':email_verified', 1);
            $db->execute();

            // Insert into teachers
            $db->query('INSERT INTO teachers (user_id, teacher_id, department) 
                        VALUES (:user_id, :teacher_id, :department)');
            $db->bind(':user_id', $user_id);
            $db->bind(':teacher_id', $teacher_id);
            $db->bind(':department', $department);
            $db->execute();

            // Prepare response info without extra SELECT for speed
            $userRow = [
                'created_at' => date('Y-m-d H:i:s'),
                'status' => $status,
            ];

            $message = 'Teacher added successfully. Default password: Teacher123! (Teacher can change it after login)';
            $message_type = 'success';

            // If AJAX request, return JSON and exit
            if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'teacher' => [
                        'user_id' => $user_id,
                        'teacher_id' => $teacher_id,
                        'full_name' => $full_name,
                        'department' => $department,
                        'created_at' => $userRow['created_at'] ?? date('Y-m-d H:i:s'),
                        'status' => $userRow['status'] ?? $status,
                    ],
                ]);
                exit;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
            if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }
    } else {
        $message = implode(' ', $errors);
        $message_type = 'error';
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
}

// Fetch teachers list filtered by department
try {
    $db = new Database();
	$db->query('SELECT u.user_id, u.full_name, u.created_at, u.status, t.teacher_id, t.department
                FROM users u
                INNER JOIN teachers t ON u.user_id = t.user_id
                WHERE u.role = "teacher" AND t.department = :department
                ORDER BY u.full_name ASC');
    $db->bind(':department', $current_department);
    $teachers = $db->resultset();
} catch (Exception $e) {
    $teachers = [];
    $message = $message ?? ('Error loading teachers: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Instructors Dashboard - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .table-section-header { display:flex; align-items:center; justify-content:space-between; margin: 16px 0 10px; }
        .table-section-header h3 { margin:0; font-size: 1rem; font-weight:600; }
        /* Active/Not Active table color themes */
        #activeTeachers { border: 1px solid #16a34a22; border-left: 4px solid #16a34a; border-radius: 8px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 1px 6px rgba(22,163,74,0.12); }
        #activeTeachers thead { background: #16a34a; color: #fff; }
        #activeTeachers tbody tr:nth-child(even) { background: #16a34a0a; }
        #activeTeachers tbody tr:hover { background: #16a34a14; }
        #activeTeachers td, #activeTeachers th { border-color: #16a34a22; }

        #inactiveTeachers { border: 1px solid #dc262622; border-left: 4px solid #dc2626; border-radius: 8px; overflow: hidden; margin-top: 24px; box-shadow: 0 1px 6px rgba(220,38,38,0.12); }
        #inactiveTeachers thead { background: #dc2626; color: #fff; }
        #inactiveTeachers tbody tr:nth-child(even) { background: #dc26260a; }
        #inactiveTeachers tbody tr:hover { background: #dc262614; }
        #inactiveTeachers td, #inactiveTeachers th { border-color: #dc262622; }

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
		/* Section layout */
		/* Make this page's content full-width */
		.admin-main .main-content { padding-left: 0; padding-right: 0; }
		/* Optional: stretch the card edge-to-edge */
		.dashboard-card { margin-left: 0; margin-right: 0; }

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
		.table-container { width: 100%; overflow-x: auto; }
		.table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .table thead th { position: sticky; top: 0; background: #16a34a; color: #fff; font-weight: 600; text-align: left; padding: .75rem .9rem; border-bottom: 1px solid rgba(229,231,235,.9); }
		.table tbody td { padding: .7rem .9rem; border-bottom: 1px solid rgba(229,231,235,.6); }
		.table tbody tr:hover { background: #fafbfd; }
		.table tbody tr:nth-child(even) { background: #fcfdff; }
		.table .muted { text-align: center; color: var(--text-secondary); padding: 1rem; }
		
		/* Available/Unavailable table color themes */
		#activeTeachers { 
			border: 1px solid #16a34a22; 
			border-left: 4px solid #16a34a; 
			border-radius: 8px; 
			overflow: hidden; 
			margin-bottom: 24px; 
			box-shadow: 0 1px 6px rgba(22,163,74,0.12); 
		}
		#activeTeachers thead { 
			background: #16a34a; 
			color: #fff; 
		}
		#activeTeachers tbody tr:nth-child(even) { 
			background: #f0fdf4; 
		}
		#activeTeachers tbody tr:nth-child(odd) { 
			background: #f7fef9; 
		}
		
		#inactiveTeachers { 
			border: 1px solid #dc262622; 
			border-left: 4px solid #dc2626; 
			border-radius: 8px; 
			overflow: hidden; 
			margin-bottom: 24px; 
			box-shadow: 0 1px 6px rgba(220,38,38,0.12); 
		}
		#inactiveTeachers thead { 
			background: #dc2626; 
			color: #fff; 
		}
		#inactiveTeachers tbody tr:nth-child(even) { 
			background: #fef2f2; 
		}
		#inactiveTeachers tbody tr:nth-child(odd) { 
			background: #fef7f7; 
		}
		.btn.btn-sm { padding: .35rem .55rem; font-size: .85rem; border-radius: 8px; }
		.btn.btn-secondary { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
		.btn.btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
		.btn.btn-secondary:hover { background: #e0e7ff; }
		.btn.btn-danger:hover { background: #fecaca; }

		/* Table section headers (matching subjects dashboard) */
		.table-section-header { display:flex; align-items:center; justify-content:space-between; margin: 16px 0 10px; }
		.table-section-header h3 { margin:0; font-size: 1rem; font-weight:600; }

		/* Form Styling */
		.form { padding: 1rem 1.25rem 1.25rem; }
		.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
		@media (max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
		.form-group label { display: block; font-weight: 600; margin-bottom: .35rem; }
		.form-input { 
			width: 100%; 
			padding: .7rem .8rem; 
			border-radius: 10px; 
			border: 1px solid rgba(229,231,235,.9); 
			background: #fff; 
			transition: border-color 0.2s, box-shadow 0.2s;
		}
		.form-input:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
		}
		.form-input.error {
			border-color: #dc2626;
		}
		.password-field { position: relative; }
		.password-toggle { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); background: transparent; border: 0; color: var(--text-secondary); cursor: pointer; padding: .25rem; }
		.field-error { 
			color: #dc2626; 
			font-size: 0.875rem; 
			margin-top: 0.25rem; 
			display: none; 
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

		/* Table Blue Color Coding (matching subjects dashboard) */
		.blue-theme { border-left: 6px solid #2563eb !important; }
		.table-section-header .year-label.blue-theme { color: #2563eb; }
		.table-container.blue-theme { box-shadow: 0 1px 8px #2563eb22; }
		
		/* Red header for inactive/not available tables */
		#inactiveTeachers thead th {
			background: #dc2626 !important;
			color: #fff !important;
		}
		
		/* Center Instructor ID, Full Name, and Department columns in both tables */
		#activeTeachers th:nth-child(1), #activeTeachers td:nth-child(1),
		#inactiveTeachers th:nth-child(1), #inactiveTeachers td:nth-child(1) {
			text-align: center; /* Instructor ID */
		}
		
		#activeTeachers th:nth-child(2), #activeTeachers td:nth-child(2),
		#inactiveTeachers th:nth-child(2), #inactiveTeachers td:nth-child(2) {
			text-align: center; /* Instructor Full Name */
		}
		
		#activeTeachers th:nth-child(3), #activeTeachers td:nth-child(3),
		#inactiveTeachers th:nth-child(3), #inactiveTeachers td:nth-child(3) {
			text-align: left; /* Department - justified/left-aligned */
		}
		
		/* Center Department header, but justify Department data */
		#activeTeachers th:nth-child(3), #inactiveTeachers th:nth-child(3) {
			text-align: center; /* Department header centered */
		}
		
		#activeTeachers td:nth-child(3), #inactiveTeachers td:nth-child(3) {
			text-align: left; /* Department data justified/left-aligned */
		}
		
		#activeTeachers th:nth-child(4), #activeTeachers td:nth-child(4),
		#inactiveTeachers th:nth-child(4), #inactiveTeachers td:nth-child(4) {
			text-align: center; /* Status header and data centered */
		}
		
		#activeTeachers th:nth-child(5), #activeTeachers td:nth-child(5),
		#inactiveTeachers th:nth-child(5), #inactiveTeachers td:nth-child(5) {
			text-align: center; /* Actions header and data centered */
		}
		
		/* Ensure Actions header is perfectly centered over Edit/Delete buttons */
		#activeTeachers th:nth-child(5),
		#inactiveTeachers th:nth-child(5) {
			text-align: center !important; /* Force center alignment for Actions header */
		}
		
		/* Center the action buttons container */
		#activeTeachers .action-buttons,
		#inactiveTeachers .action-buttons {
			display: flex;
			justify-content: center;
			align-items: center;
			gap: 8px; /* Space between Edit and Delete buttons */
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
                    <li class="nav-item"><a href="manage-scheduling.php" class="nav-link active"><i class="fas fa-calendar-alt"></i><span>Manage Scheduling Information</span></a></li>
                    <li class="nav-item"><a href="generate-schedule.php" class="nav-link"><i class="fas fa-magic"></i><span>Generate Schedule</span></a></li>
                </ul>
                <div class="sidebar-footer">
                    <a href="../../auth/logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </nav>
        </aside>

        <main class="admin-main" id="adminMain">
            <div class="main-header">
                <h1 class="page-title"><i class="fas fa-chalkboard-teacher"></i> Instructors Dashboard</h1>
                <button id="headerSidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
            
            <!-- Main Dashboard Navigation Cards -->
            <div class="dashboard-navigation-cards">
                <a href="Instructor-dashboard.php" class="dashboard-card-nav active" data-dashboard="instructors">
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
                <a href="sections-dashboard.php" class="dashboard-card-nav" data-dashboard="sections">
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

                <div class="dashboard-card" style="width:100%">
                    <div class="management-controls">
                        <div class="controls-left">
                            <h2 class="section-title" style="font-size: 1.5rem; font-weight: 700;"><i class="fas fa-chalkboard-teacher"></i> Instructor Information</h2>
                        </div>
                        <div class="controls-right">
                            <button class="btn btn-primary" id="openAddTeacherModal">
								<i class="fas fa-plus-circle"></i> Add New Teacher
                            </button>
                        </div>
                    </div>
                    <!-- Department Breadcrumbs -->
					<nav class="breadcrumb-navigation" style="margin-bottom:1.2rem;">
                        <?php foreach ($departments as $department => $name): ?>
							<a href="?department=<?php echo urlencode($department); ?>" 
							   class="breadcrumb-tab <?php echo $current_department === $department ? 'active' : ''; ?>">
								<?php echo htmlspecialchars($name); ?><?php echo ($current_department !== $department) ? '' : ''; ?>
							</a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="table-container">
                        <div class="table-section-header"><h3><i class="fas fa-check-circle" style="color:#16a34a"></i> Active Instructors</h3></div>
                        <table class="table" id="activeTeachers">
                            <thead>
                                <tr>
                                    <th>Instructor ID</th>
                                    <th>Instructor Full Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $teachersActive = [];
                                $teachersInactive = [];
                                foreach (($teachers ?? []) as $t) {
                                    if (($t['status'] ?? '') === 'approved') $teachersActive[] = $t; else $teachersInactive[] = $t;
                                }
                                ?>
                                <?php if (!empty($teachersActive)): ?>
                                    <?php foreach ($teachersActive as $t): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['teacher_id']); ?></td>
                                            <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($t['department']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="status-toggle" data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>" checked />
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-available">Active</span>
                                            </td>
                                            <td>
												<div class="action-buttons">
                                                <button class="btn btn-sm btn-secondary edit-btn"
                                                    data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>"
                                                    data-full-name="<?php echo htmlspecialchars($t['full_name']); ?>"
                                                    data-teacher-id="<?php echo htmlspecialchars($t['teacher_id']); ?>"
                                                    data-department="<?php echo htmlspecialchars($t['department']); ?>"
                                                    data-status="approved">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
												</div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
									<tr><td colspan="5" class="muted">No active instructors found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="table-section-header"><h3><i class="fas fa-ban" style="color:#dc2626"></i> Not Active Instructors</h3></div>
                        <table class="table" id="inactiveTeachers">
                            <thead>
                                <tr>
                                    <th>Instructor ID</th>
                                    <th>Instructor Full Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($teachersInactive)): ?>
                                    <?php foreach ($teachersInactive as $t): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['teacher_id']); ?></td>
                                            <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($t['department']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="status-toggle" data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>" />
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-unavailable">Not Active</span>
                                            </td>
                                            <td>
												<div class="action-buttons">
                                                <button class="btn btn-sm btn-secondary edit-btn"
                                                    data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>"
                                                    data-full-name="<?php echo htmlspecialchars($t['full_name']); ?>"
                                                    data-teacher-id="<?php echo htmlspecialchars($t['teacher_id']); ?>"
                                                    data-department="<?php echo htmlspecialchars($t['department']); ?>"
                                                    data-status="inactive">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" data-user-id="<?php echo htmlspecialchars($t['user_id']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
												</div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
									<tr><td colspan="5" class="muted">No not active instructors found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Teacher Modal -->
                <div id="addTeacherModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
                    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 2rem auto; position: relative; top: 50%; transform: translateY(-50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-user-plus" style="color: #6366f1; margin-right: 0.5rem;"></i>Add New Instructor</h3>
                            <button id="closeAddTeacherModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="POST" action="Instructor-dashboard.php" class="form" novalidate>
                            <input type="hidden" name="action" value="add_teacher" />
                            <div class="form-grid">
<div class="form-group">
                            <label for="full_name">Instructor Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" required pattern="^[A-Za-zÀ-ÿ .'-]+$" title="Letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only" value="<?php echo htmlspecialchars($old['full_name'] ?? ''); ?>" />
    <span class="field-error" id="fullNameError"></span>
    <?php if (!empty($errors_map['full_name'])): ?>
        <small class="field-error"><?php echo htmlspecialchars($errors_map['full_name']); ?></small>
    <?php endif; ?>
</div>
<div class="form-group">
                            <label for="teacher_id">Instructor ID</label>
    <input type="text" id="teacher_id" name="teacher_id" class="form-input" required pattern="^[A-Za-z0-9]+$" title="Letters and numbers only, no special characters" value="<?php echo htmlspecialchars($old['teacher_id'] ?? ''); ?>" />
    <span class="field-error" id="teacherIdError"></span>
    <?php if (!empty($errors_map['teacher_id'])): ?>
        <small class="field-error"><?php echo htmlspecialchars($errors_map['teacher_id']); ?></small>
    <?php endif; ?>
</div>
                                <div class="form-group">
                                    <label for="department">Department</label>
                                                                        <select id="department" name="department" class="form-input" required>
                                        <option value="" disabled <?php echo empty($old['department']) ? 'selected' : ''; ?>>Select department</option>
                                        <option value="College of Communication and Information Technology" <?php echo (isset($old['department']) && $old['department']==='College of Communication and Information Technology') ? 'selected' : ''; ?>>College of Communication and Information Technology</option>
                                        <option value="College of Teacher Education" <?php echo (isset($old['department']) && $old['department']==='College of Teacher Education') ? 'selected' : ''; ?>>College of Teacher Education</option>
                                    </select>
                                    <span class="field-error" id="departmentError"></span>
                                    <?php if (!empty($errors_map['department'])): ?>
                                        <small class="field-error"><?php echo htmlspecialchars($errors_map['department']); ?></small>
                                    <?php endif; ?>
</div>
                                <div class="form-group">
                                    <label for="status">Instructor Status</label>
                                    <select id="status" name="status" class="form-input" required>
                                        <option value="" disabled selected>Select status</option>
                                        <option value="approved">Active</option>
                                        <option value="inactive">Not Active</option>
                                    </select>
                                    <span class="field-error" id="statusError"></span>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                <button type="button" class="btn btn-secondary" id="cancelAddTeacher">Cancel</button>
                                <button type="submit" class="btn btn-success">Save Instructor</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Teacher Modal -->
                <div id="editTeacherModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
                    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 2rem auto; position: relative; top: 50%; transform: translateY(-50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-user-edit" style="color: #6366f1; margin-right: 0.5rem;"></i>Edit Instructor</h3>
                            <button id="closeEditTeacherModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="POST" action="Instructor-dashboard.php" class="form" novalidate>
                            <input type="hidden" name="action" value="edit_teacher" />
                            <input type="hidden" id="edit_user_id" name="user_id" />
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="edit_full_name">Instructor Full Name</label>
                                    <input type="text" id="edit_full_name" name="full_name" class="form-input" required pattern="^[A-Za-zÀ-ÿ .'-]+$" title="Letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only" />
                                    <span class="field-error" id="editFullNameError"></span>
                                </div>
                                <div class="form-group">
                                    <label for="edit_teacher_id">Instructor ID</label>
                                    <input type="text" id="edit_teacher_id" name="teacher_id" class="form-input" required pattern="^[A-Za-z0-9]+$" title="Letters and numbers only, no special characters" />
                                    <span class="field-error" id="editTeacherIdError"></span>
                                </div>
                                <div class="form-group">
                                    <label for="edit_department">Department</label>
                                    <select id="edit_department" name="department" class="form-input" required>
                                        <option value="College of Communication and Information Technology">College of Communication and Information Technology</option>
                                        <option value="College of Teacher Education">College of Teacher Education</option>
                                    </select>
                                    <span class="field-error" id="editDepartmentError"></span>
                                </div>
                                <div class="form-group">
                                    <label for="edit_status">Instructor Status</label>
                                    <select id="edit_status" name="status" class="form-input" required>
                                        <option value="" disabled>Select status</option>
                                        <option value="approved">Active</option>
                                        <option value="inactive">Not Active</option>
                                    </select>
                                    <span class="field-error" id="editStatusError"></span>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                <button type="button" class="btn btn-secondary" id="cancelEditTeacher">Cancel</button>
                                <button type="submit" class="btn btn-success">Save Changes</button>
                            </div>
                        </form>
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
                <button id="confirmYes" class="btn btn-danger">Delete</button>
                <button id="confirmNo" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>


        // Success modal function (copied from subjects dashboard)
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

        // Error modal function (copied from subjects dashboard)
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
        
        // Helper functions for field validation
        function showFieldError(errorElement, message) {
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                errorElement.previousElementSibling.classList.add('error');
            }
        }
        
        function clearFieldError(errorElement) {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                errorElement.previousElementSibling.classList.remove('error');
            }
        }
        


        function showConfirm(message, opts = {}) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const text = document.getElementById('confirmText');
                const yes = document.getElementById('confirmYes');
                const no = document.getElementById('confirmNo');
                if (!modal || !text || !yes || !no) {
                    resolve(!!window.confirm(message));
                    return;
                }
                text.textContent = message;
                if (opts.confirmText) yes.textContent = opts.confirmText;
                if (opts.cancelText) no.textContent = opts.cancelText;
                modal.style.display = 'flex';
                
                const cleanup = () => {
                    modal.style.display = 'none';
                    yes.removeEventListener('click', onYes);
                    no.removeEventListener('click', onNo);
                };
                const onYes = () => { cleanup(); resolve(true); };
                const onNo = () => { cleanup(); resolve(false); };
                yes.addEventListener('click', onYes, { once: true });
                no.addEventListener('click', onNo, { once: true });
            });
        }

        // Unique loading functions
        function showUniqueLoading() {
            const loading = document.getElementById('uniqueLoading');
            if (loading) {
                loading.style.display = 'flex';
            }
        }
        
        function hideUniqueLoading() {
            const loading = document.getElementById('uniqueLoading');
            if (loading) {
                loading.style.display = 'none';
            }
        }
        
        // Function to sort table rows alphabetically by instructor name
        function sortTableAlphabetically(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
            const emptyRow = tbody.querySelector('tr.empty-row');
            
            // Sort rows by instructor name (2nd column) using localeCompare for proper alphabetical sorting
            rows.sort((a, b) => {
                const nameA = a.querySelector('td:nth-child(2)').textContent.trim();
                const nameB = b.querySelector('td:nth-child(2)').textContent.trim();
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

        // Modal controls
        document.addEventListener('click', (e) => {
            // Add Teacher Modal
            if (e.target.closest('#openAddTeacherModal')) {
                e.preventDefault();
                const modal = document.getElementById('addTeacherModal');
                const form = modal.querySelector('form');
                if (form) form.reset();
                modal.style.display = 'flex';
                return;
            }
            
            // Close Add Teacher Modal
            if (e.target.closest('#closeAddTeacherModal, #cancelAddTeacher')) {
                e.preventDefault();
                document.getElementById('addTeacherModal').style.display = 'none';
                return;
            }

            // Edit Teacher
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                e.preventDefault();
                const d = editBtn.dataset;
                document.getElementById('edit_user_id').value = d.userId;
                document.getElementById('edit_full_name').value = d.fullName;
                document.getElementById('edit_teacher_id').value = d.teacherId;
                document.getElementById('edit_department').value = d.department;
                document.getElementById('edit_status').value = d.status;
                document.getElementById('editTeacherModal').style.display = 'flex';
                return;
            }
            
            // Close Edit Teacher Modal
            if (e.target.closest('#closeEditTeacherModal, #cancelEditTeacher')) {
                e.preventDefault();
                document.getElementById('editTeacherModal').style.display = 'none';
                return;
            }

            // Delete Teacher
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                e.preventDefault();
                handleDeleteTeacher(deleteBtn);
                return;
            }
        });
        
        // Check for duplicate instructor full name
        function checkDuplicateFullName(name, inputElement, excludeUserId = null) {
            // Get all instructor full names from both active and inactive tables
            const activeTable = document.getElementById('activeTeachers');
            const inactiveTable = document.getElementById('inactiveTeachers');
            
            let existingNames = [];
            
            // Collect names from active instructors
            if (activeTable) {
                const activeRows = activeTable.querySelectorAll('tbody tr:not(.empty-row)');
                activeRows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    const userId = row.querySelector('.edit-btn')?.getAttribute('data-user-id');
                    
                    if (nameCell && userId !== excludeUserId) {
                        existingNames.push(nameCell.textContent.trim().toLowerCase());
                    }
                });
            }
            
            // Collect names from inactive instructors
            if (inactiveTable) {
                const inactiveRows = inactiveTable.querySelectorAll('tbody tr:not(.empty-row)');
                inactiveRows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    const userId = row.querySelector('.edit-btn')?.getAttribute('data-user-id');
                    
                    if (nameCell && userId !== excludeUserId) {
                        existingNames.push(nameCell.textContent.trim().toLowerCase());
                    }
                });
            }
            
            // Check if the new name already exists (case-insensitive)
            const normalizedName = name.toLowerCase();
            const isDuplicate = existingNames.includes(normalizedName);
            
            if (isDuplicate) {
                const errorId = inputElement.id === 'full_name' ? 'fullNameError' : 'editFullNameError';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    showFieldError(errorElement, 'Instructor full name already exists in the table');
                }
                return false; // Return false to indicate validation failed
            }
            
            return true; // Return true to indicate validation passed
        }
        
        // Check for duplicate instructor ID
        function checkDuplicateInstructorId(id, inputElement, excludeUserId = null) {
            // Get all instructor IDs from both active and inactive tables
            const activeTable = document.getElementById('activeTeachers');
            const inactiveTable = document.getElementById('inactiveTeachers');
            
            let existingIds = [];
            
            // Collect IDs from active instructors
            if (activeTable) {
                const activeRows = activeTable.querySelectorAll('tbody tr:not(.empty-row)');
                activeRows.forEach(row => {
                    const idCell = row.querySelector('td:nth-child(1)');
                    const userId = row.querySelector('.edit-btn')?.getAttribute('data-user-id');
                    
                    if (idCell && userId !== excludeUserId) {
                        existingIds.push(idCell.textContent.trim().toLowerCase());
                    }
                });
            }
            
            // Collect IDs from inactive instructors
            if (inactiveTable) {
                const inactiveRows = inactiveTable.querySelectorAll('tbody tr:not(.empty-row)');
                inactiveRows.forEach(row => {
                    const idCell = row.querySelector('td:nth-child(1)');
                    const userId = row.querySelector('.edit-btn')?.getAttribute('data-user-id');
                    
                    if (idCell && userId !== excludeUserId) {
                        existingIds.push(idCell.textContent.trim().toLowerCase());
                    }
                });
            }
            
            // Check if the new ID already exists (case-insensitive)
            const normalizedId = id.toLowerCase();
            const isDuplicate = existingIds.includes(normalizedId);
            
            if (isDuplicate) {
                const errorId = inputElement.id === 'teacher_id' ? 'teacherIdError' : 'editTeacherIdError';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    showFieldError(errorElement, 'Instructor ID already exists in the table');
                }
                return false; // Return false to indicate validation failed
            }
            
            return true; // Return true to indicate validation passed
        }
        
        // Real-time field validation
        document.addEventListener('input', function(e) {
            // Add Teacher form validation
            if (e.target.id === 'full_name') {
                const name = e.target.value.trim();
                const errorElement = document.getElementById('fullNameError');
                
                if (!name) {
                    showFieldError(errorElement, 'Instructor full name is required');
                } else if (!/^[A-Za-zÀ-ÿ .'-]+$/.test(name)) {
                    showFieldError(errorElement, 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only');
                } else if (!checkDuplicateFullName(name, e.target)) {
                    // Duplicate check failed, error already shown
                    return;
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            if (e.target.id === 'teacher_id') {
                const tid = e.target.value.trim();
                const errorElement = document.getElementById('teacherIdError');
                
                if (!tid) {
                    showFieldError(errorElement, 'Instructor ID is required');
                } else if (!/^[A-Z]{2}[0-9]+$|^[0-9]+$/.test(tid)) {
                    showFieldError(errorElement, 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers');
                } else if (!checkDuplicateInstructorId(tid, e.target)) {
                    // Duplicate check failed, error already shown
                    return;
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            // Edit Teacher form validation
            if (e.target.id === 'edit_full_name') {
                const name = e.target.value.trim();
                const errorElement = document.getElementById('editFullNameError');
                
                if (!name) {
                    showFieldError(errorElement, 'Instructor full name is required');
                } else if (!/^[A-Za-zÀ-ÿ .'-]+$/.test(name)) {
                    showFieldError(errorElement, 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only');
                } else if (!checkDuplicateFullName(name, e.target)) {
                    // Duplicate check failed, error already shown
                    return;
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            if (e.target.id === 'edit_teacher_id') {
                const tid = e.target.value.trim();
                const errorElement = document.getElementById('editTeacherIdError');
                
                if (!tid) {
                    showFieldError(errorElement, 'Instructor ID is required');
                } else if (!/^[A-Z]{2}[0-9]+$|^[0-9]+$/.test(tid)) {
                    showFieldError(errorElement, 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers');
                } else if (!checkDuplicateInstructorId(tid, e.target)) {
                    // Duplicate check failed, error already shown
                    return;
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            // Department validation
            if (e.target.id === 'department') {
                const dept = e.target.value;
                const errorElement = document.getElementById('departmentError');
                
                if (!dept || dept === '') {
                    showFieldError(errorElement, 'Please select a department');
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            // Instructor Status validation
            if (e.target.id === 'status') {
                const status = e.target.value;
                const errorElement = document.getElementById('statusError');
                
                if (!status || status === '') {
                    showFieldError(errorElement, 'Please select a status');
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            // Edit Department validation
            if (e.target.id === 'edit_department') {
                const dept = e.target.value;
                const errorElement = document.getElementById('editDepartmentError');
                
                if (!dept || dept === '') {
                    showFieldError(errorElement, 'Please select a department');
                } else {
                    clearFieldError(errorElement);
                }
            }
            
            // Edit Instructor Status validation
            if (e.target.id === 'edit_status') {
                const status = e.target.value;
                const errorElement = document.getElementById('editStatusError');
                
                if (!status || status === '') {
                    showFieldError(errorElement, 'Please select a status');
                } else {
                    clearFieldError(errorElement);
                }
            }
        });

		// Toggle status handler
document.addEventListener('change', async function(e) {
	if (e.target.classList.contains('status-toggle')) {
		e.preventDefault();
		const userId = e.target.getAttribute('data-user-id');
		const isChecked = e.target.checked;
		const newStatus = isChecked ? 'approved' : 'inactive';
		const statusText = isChecked ? 'Active' : 'Not Active';
		
		// Revert toggle state temporarily until confirmation
		e.target.checked = !isChecked;
		
		// Show confirmation dialog
		const confirmed = await showConfirm(`Are you sure you want to change this teacher's status to ${statusText}?`, {
			confirmText: 'Yes, Change Status',
			cancelText: 'Cancel'
		});
		
		if (!confirmed) return;
		
		// Set back to user's intended state
		e.target.checked = isChecked;
		
		showUniqueLoading();
		
		try {
			const formData = new FormData();
			formData.append('ajax', '1');
			formData.append('action', 'toggle_status');
			formData.append('user_id', userId);
			formData.append('status', newStatus);
			
			const response = await fetch('Instructor-dashboard.php', {
				method: 'POST',
				body: new URLSearchParams(formData)
			});
			
			if (!response.ok) throw new Error('Request failed');
			
			const data = await response.json();
			if (!data.success) throw new Error(data.message || 'Failed to update status');
			
			// Update UI
			const row = e.target.closest('tr');
			const statusSpan = row.querySelector('.status-available, .status-unavailable');
			if (statusSpan) {
				statusSpan.textContent = statusText;
				statusSpan.className = isChecked ? 'status-available' : 'status-unavailable';
			}
			
			// Move row to appropriate table and maintain alphabetical sorting
			const currentTable = row.closest('table');
			const targetTableId = isChecked ? 'activeTeachers' : 'inactiveTeachers';
			const targetTable = document.getElementById(targetTableId);
			
			if (currentTable.id !== targetTableId && targetTable) {
				const targetTbody = targetTable.querySelector('tbody');
				if (targetTbody) {
					// Remove empty message if it exists
					const emptyRow = targetTbody.querySelector('tr td.muted');
					if (emptyRow && emptyRow.parentElement) {
						emptyRow.parentElement.remove();
					}
					
					// Get the instructor name for sorting
					const instructorName = row.querySelector('td:nth-child(2)').textContent.trim();
					
					// Find the correct position to insert the row to maintain alphabetical order
					const existingRows = targetTbody.querySelectorAll('tr:not(.empty-row)');
					let insertPosition = null;
					
					// Use localeCompare for proper alphabetical sorting that handles accented characters
					for (let i = 0; i < existingRows.length; i++) {
						const existingName = existingRows[i].querySelector('td:nth-child(2)').textContent.trim();
						if (instructorName.localeCompare(existingName, undefined, {sensitivity: 'base'}) < 0) {
							insertPosition = existingRows[i];
							break;
						}
					}
					
					// Insert the row at the correct position
					if (insertPosition) {
						targetTbody.insertBefore(row, insertPosition);
					} else {
						// If no position found, append to the end
					targetTbody.appendChild(row);
					}
					
					// Add empty message if current table is now empty
					const currentTbody = currentTable.querySelector('tbody');
					if (currentTbody && !currentTbody.querySelector('tr:not(.empty-row)')) {
						const emptyMessage = document.createElement('tr');
						emptyMessage.className = 'empty-row';
						                        emptyMessage.innerHTML = `<td colspan="5" class="muted">No ${isChecked ? 'not active' : 'active'} instructors found.</td>`;
						currentTbody.appendChild(emptyMessage);
					}
				}
			}
			
			hideUniqueLoading();
			                        showSuccessModal('Status Updated Successfully', `Instructor status has been updated to ${statusText}`);
		} catch (err) {
			hideUniqueLoading();
			                        showErrorModal('Update Failed', err.message || 'Error updating status');
			// Revert toggle state
			e.target.checked = !isChecked;
		}
	}
});

        // Delete teacher handler
        async function handleDeleteTeacher(deleteBtn) {
            const userId = deleteBtn.getAttribute('data-user-id');
            const teacherName = deleteBtn.closest('tr').querySelector('td:nth-child(2)')?.textContent || 'this teacher';
            const confirmed = await showConfirm(`Delete instructor "${teacherName}"? This action cannot be undone.`, {
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });
            
            if (!confirmed) return;
            
            try {
                const response = await fetch('Instructor-dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_teacher',
                        user_id: userId,
                        ajax: '1'
                    })
                });
                
                if (response.ok) {
                    showSuccessModal('Instructor Deleted', 'Instructor has been successfully deleted from the system.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error('Failed to delete teacher');
                }
            } catch (error) {
                                    showErrorModal('Delete Failed', 'Error deleting instructor');
            }
        }

        // Form submissions
        document.addEventListener('submit', async (e) => {
            // Add Teacher form
            if (e.target.closest('#addTeacherModal form')) {
                e.preventDefault();
                const form = e.target;
                
                const name = form.querySelector('#full_name')?.value?.trim() || '';
                const tid = form.querySelector('#teacher_id')?.value?.trim() || '';
                const dept = form.querySelector('#department')?.value || '';
                
                // Clear previous errors
                clearFieldError(document.getElementById('fullNameError'));
                clearFieldError(document.getElementById('teacherIdError'));
                clearFieldError(document.getElementById('departmentError'));
                clearFieldError(document.getElementById('statusError'));
                
                let hasErrors = false;
                
                if (!name) {
                    showFieldError(document.getElementById('fullNameError'), 'Instructor full name is required');
                    hasErrors = true;
                } else if (!/^[A-Za-zÀ-ÿ .'-]+$/.test(name)) { 
                    showFieldError(document.getElementById('fullNameError'), 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only'); 
                    hasErrors = true;
                } else if (!checkDuplicateFullName(name, form.querySelector('#full_name'))) {
                    hasErrors = true;
                }
                
                if (!tid) {
                    showFieldError(document.getElementById('teacherIdError'), 'Instructor ID is required');
                    hasErrors = true;
                } else if (!/^[A-Z]{2}[0-9]+$|^[0-9]+$/.test(tid)) { 
                    showFieldError(document.getElementById('teacherIdError'), 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers'); 
                    hasErrors = true;
                } else if (!checkDuplicateInstructorId(tid, form.querySelector('#teacher_id'))) {
                    hasErrors = true;
                }
                
                if (!dept) { 
                    showFieldError(document.getElementById('departmentError'), 'Please select a department');
                    hasErrors = true;
                }
                
                const status = form.querySelector('#status')?.value || '';
                if (!status || status === '') { 
                    showFieldError(document.getElementById('statusError'), 'Please select a status');
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) { 
                    submitBtn.disabled = true; 
                }

                showUniqueLoading();

                try {
                    const formData = new FormData(form);
                    formData.set('action', 'add_teacher');
                    formData.set('ajax', '1');
                    
                    const response = await fetch('Instructor-dashboard.php', { 
                        method: 'POST', 
                        body: new URLSearchParams(Array.from(formData.entries())) 
                    });
                    
                    if (!response.ok) throw new Error('Request failed');
                    
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Failed to add');

                    hideUniqueLoading();
                    showSuccessModal('Instructor Added', 'Instructor has been successfully added to the system.');
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    hideUniqueLoading();
                    showErrorModal('Add Failed', err.message || 'Error adding instructor');
                } finally {
                    if (submitBtn) { 
                        submitBtn.disabled = false; 
                    }
                }
                return;
            }

            // Edit Teacher form
            if (e.target.closest('#editTeacherModal form')) {
                e.preventDefault();
                const form = e.target;
                
                const name = form.querySelector('#edit_full_name')?.value?.trim() || '';
                const tid = form.querySelector('#edit_teacher_id')?.value?.trim() || '';
                const dept = form.querySelector('#edit_department')?.value || '';
                
                // Clear previous errors
                clearFieldError(document.getElementById('editFullNameError'));
                clearFieldError(document.getElementById('editTeacherIdError'));
                clearFieldError(document.getElementById('editDepartmentError'));
                clearFieldError(document.getElementById('editStatusError'));
                
                let hasErrors = false;
                
                if (!name) {
                    showFieldError(document.getElementById('editFullNameError'), 'Instructor full name is required');
                    hasErrors = true;
                } else if (!/^[A-Za-zÀ-ÿ .'-]+$/.test(name)) { 
                    showFieldError(document.getElementById('editFullNameError'), 'Instructor full name must contain letters (including ñ and accented characters), spaces, dots, apostrophes, and hyphens only'); 
                    hasErrors = true;
                } else if (!checkDuplicateFullName(name, form.querySelector('#edit_full_name'), document.getElementById('edit_user_id').value)) {
                    hasErrors = true;
                }
                
                if (!tid) {
                    showFieldError(document.getElementById('editTeacherIdError'), 'Instructor ID is required');
                    hasErrors = true;
                } else if (!/^[A-Z]{2}[0-9]+$|^[0-9]+$/.test(tid)) { 
                    showFieldError(document.getElementById('editTeacherIdError'), 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers'); 
                    hasErrors = true;
                } else if (!checkDuplicateInstructorId(tid, form.querySelector('#edit_teacher_id'), document.getElementById('edit_user_id').value)) {
                    hasErrors = true;
                }
                
                if (!dept) { 
                    showFieldError(document.getElementById('editDepartmentError'), 'Please select a department');
                    hasErrors = true;
                }
                
                const status = form.querySelector('#edit_status')?.value || '';
                if (!status || status === '') { 
                    showFieldError(document.getElementById('editStatusError'), 'Please select a status');
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) { 
                    submitBtn.disabled = true; 
                }

                showUniqueLoading();

                try {
                    const formData = new FormData(form);
                    formData.set('ajax', '1');
                    
                    const response = await fetch('Instructor-dashboard.php', { 
                        method: 'POST', 
                        body: new URLSearchParams(Array.from(formData.entries())) 
                    });
                    
                    if (!response.ok) throw new Error('Request failed');
                    
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Failed to update');

                    hideUniqueLoading();
                    showSuccessModal('Instructor Updated', 'Instructor information has been successfully updated.');
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    hideUniqueLoading();
                    showErrorModal('Update Failed', err.message || 'Error updating instructor');
                } finally {
                    if (submitBtn) { 
                        submitBtn.disabled = false; 
                    }
                }
                return;
            }
        });
    </script>
    <?php if (!empty($message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            try { 
                if ('<?php echo $message_type; ?>' === 'success') {
                    showSuccessModal('Success', '<?php echo $message; ?>');
                } else {
                    showErrorModal('Error', '<?php echo $message; ?>');
                }
            } catch(e) { /* no-op */ }
        });
    </script>
    <?php endif; ?>
    
    <script>
        // Ensure tables are sorted alphabetically when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Sort both tables alphabetically by instructor name
            sortTableAlphabetically('activeTeachers');
            sortTableAlphabetically('inactiveTeachers');
        });
    </script>
    
    <!-- Unique Loading Screen -->
    <div class="unique-loading" id="uniqueLoading">
        <div class="unique-spinner"></div>
        <div class="unique-loading-text">Processing...</div>
        <div class="unique-loading-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    
    <style>
        /* Full-width main area for this page */
        .admin-main { width: 100%; }
        .admin-main .main-content { padding-left: 0; padding-right: 0; max-width: none; }
        .dashboard-card { margin-left: 0; margin-right: 0; width: 100%; }
        /* Section layout */
        /* Make this page's content full-width */
        .admin-main .main-content { padding-left: 0; padding-right: 0; }
        /* Optional: stretch the card edge-to-edge */
        .dashboard-card { margin-left: 0; margin-right: 0; }

        .dashboard-card {
            background: #fff;
            border: 1px solid rgba(229,231,235,.6);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 1rem 1rem 1.25rem;
        }

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

		/* Breadcrumb Navigation */
		.breadcrumb-navigation {
			display: flex;
			flex-wrap: wrap;
			gap: 0.5rem;
			margin-top: 0.5rem;
		}




        /* Unique Loading Screen */
        .unique-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            min-width: 120px;
        }
        
        .unique-spinner {
            width: 40px;
            height: 40px;
            position: relative;
        }
        
        .unique-spinner::before,
        .unique-spinner::after {
            content: '';
            position: absolute;
            border-radius: 50%;
        }
        
        .unique-spinner::before {
            width: 40px;
            height: 40px;
            background: conic-gradient(from 0deg, #6366f1, #8b5cf6, #ec4899, #f59e0b, #6366f1);
            animation: uniqueRotate 1.5s linear infinite;
        }
        
        .unique-spinner::after {
            width: 32px;
            height: 32px;
            background: #ffffff;
            top: 4px;
            left: 4px;
        }
        
        @keyframes uniqueRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .unique-loading-text {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            text-align: center;
        }
        
        .unique-loading-dots {
            display: flex;
            gap: 4px;
        }
        
        .unique-loading-dots span {
            width: 6px;
            height: 6px;
            background: #6366f1;
            border-radius: 50%;
            animation: uniqueBounce 1.4s ease-in-out infinite both;
        }
        
        .unique-loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .unique-loading-dots span:nth-child(2) { animation-delay: -0.16s; }
        .unique-loading-dots span:nth-child(3) { animation-delay: 0s; }
        
        @keyframes uniqueBounce {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1.2); opacity: 1; }
        }
    </style>
    
    <script>
        // Initialize dashboard navigation breadcrumb functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboardNavigation();
            initializeBreadcrumbAutoScroll();
        });
        
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

        // Initialize breadcrumb auto-scroll functionality
        function initializeBreadcrumbAutoScroll() {
            const breadcrumbTabs = document.querySelectorAll('.breadcrumb-tab');
            
            breadcrumbTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    // Always add scroll parameter when clicking breadcrumb tabs
                    const href = this.getAttribute('href');
                    const newUrl = new URL(href, window.location.href);
                    newUrl.searchParams.set('scroll', 'tables');
                    window.location.href = newUrl.toString();
                });
            });
            
            // Check if we should scroll to tables after page load
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('scroll') === 'tables') {
                // Remove the scroll parameter from URL without refreshing
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.delete('scroll');
                window.history.replaceState({}, '', newUrl.toString());
                
                // Scroll to the tables section with smooth animation
                setTimeout(() => {
                    const tablesSection = document.querySelector('.table-container');
                    if (tablesSection) {
                        tablesSection.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                        
                        // Add a subtle highlight effect to draw attention
                        tablesSection.style.transition = 'box-shadow 0.3s ease';
                        tablesSection.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.3)';
                        
                        // Remove the highlight after a few seconds
                        setTimeout(() => {
                            tablesSection.style.boxShadow = '';
                        }, 2000);
                    }
                }, 100); // Small delay to ensure page is fully loaded
            }
            
            // Also auto-scroll to tables if department parameter is present (for page refresh scenarios)
            if (urlParams.get('department')) {
                setTimeout(() => {
                    const tablesSection = document.querySelector('.table-container');
                    if (tablesSection) {
                        tablesSection.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                        
                        // Add a subtle highlight effect to draw attention
                        tablesSection.style.transition = 'box-shadow 0.3s ease';
                        tablesSection.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.3)';
                        
                        // Remove the highlight after a few seconds
                        setTimeout(() => {
                            tablesSection.style.boxShadow = '';
                        }, 2000);
                    }
                }, 200); // Slightly longer delay for page refresh scenarios
            }
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
