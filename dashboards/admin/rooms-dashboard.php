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

// Fetch rooms data
try {
    $db->query('SELECT id, room_name, status FROM rooms ORDER BY room_name ASC');
    $rooms = $db->resultset();
} catch (Exception $e) {
    $rooms = [];
    $message = 'Error loading rooms: ' . $e->getMessage();
}

// Separate rooms by status
$roomsActive = [];
$roomsInactive = [];
foreach (($rooms ?? []) as $room) {
    if (($room['status'] ?? '') === 'available') {
        $roomsActive[] = $room;
    } else {
        $roomsInactive[] = $room;
    }
}

// Handle room status toggle (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    try {
        $room_id = trim($_POST['room_id'] ?? '');
        $new_status = ($_POST['status'] ?? '') === 'available' ? 'available' : 'unavailable';
        if (!$room_id) throw new Exception('Missing room id');
        
        $db = new Database();
        $db->query('UPDATE rooms SET status = :status WHERE id = :room_id');
        $db->bind(':status', $new_status);
        $db->bind(':room_id', $room_id);
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

// Handle room deletion (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_room') {
    try {
        $room_id = trim($_POST['room_id'] ?? '');
        if (!$room_id) throw new Exception('Missing room id');
        
        $db = new Database();
        $db->query('DELETE FROM rooms WHERE id = :room_id');
        $db->bind(':room_id', $room_id);
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

// Handle Add New Room submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $room_name = trim($_POST['room_name'] ?? '');
    $status = ($_POST['status'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available';

    $errors = [];
    // Room name: letters, numbers, spaces, and hyphens only
    if (!$room_name) {
        $errors[] = 'Room name is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\s\-]+$/', $room_name)) {
        $errors[] = 'Room name must contain letters, numbers, spaces, and hyphens only.';
    }
    
    if (empty($errors)) {
        try {
            // Check for duplicates before inserting
            $db = new Database();
            
            // Check if room name already exists
            $db->query('SELECT COUNT(*) as count FROM rooms WHERE LOWER(room_name) = LOWER(:room_name)');
            $db->bind(':room_name', $room_name);
            $db->execute();
            $nameResult = $db->single();
            
            if ($nameResult['count'] > 0) {
                $errors[] = 'Room name already exists.';
                throw new Exception('Room name already exists.');
            }
            
            // Insert new room
            $db->query('INSERT INTO rooms (room_name, status) VALUES (:room_name, :status)');
            $db->bind(':room_name', $room_name);
            $db->bind(':status', $status);
            $db->execute();
            
            $message = 'Room added successfully.';
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
                echo json_encode([
                    'success' => false,
                    'message' => $message
                ]);
        exit;
    }
        }
                } else {
        $message = 'Validation errors: ' . implode(', ', $errors);
                        $message_type = 'error';
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
    exit;
        }
    }
}

// Handle Edit Room submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_room') {
    $room_id = trim($_POST['room_id'] ?? '');
    $room_name = trim($_POST['room_name'] ?? '');
    $status = ($_POST['status'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available';

    $errors = [];
    if (!$room_id) { $errors[] = 'Missing room id.'; }
    if (!$room_name) {
        $errors[] = 'Room name is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\s\-]+$/', $room_name)) { 
        $errors[] = 'Room name must contain letters, numbers, spaces, and hyphens only.'; 
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            
            // Check for duplicates before updating (excluding current room)
            $db->query('SELECT COUNT(*) as count FROM rooms WHERE LOWER(room_name) = LOWER(:room_name) AND id != :room_id');
            $db->bind(':room_name', $room_name);
            $db->bind(':room_id', $room_id);
            $db->execute();
            $nameResult = $db->single();
            
            if ($nameResult['count'] > 0) {
                $errors[] = 'Room name already exists for another room.';
                throw new Exception('Room name already exists for another room.');
            }
            
            // Update room
            $db->query('UPDATE rooms SET room_name = :room_name, status = :status WHERE id = :room_id');
            $db->bind(':room_name', $room_name);
            $db->bind(':status', $status);
            $db->bind(':room_id', $room_id);
                    $db->execute();
            
            $message = 'Room updated successfully.';
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
                echo json_encode([
                    'success' => false,
                    'message' => $message
                ]);
                exit;
            }
        }
    } else {
        $message = 'Validation errors: ' . implode(', ', $errors);
    $message_type = 'error';
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }
    }
}

// Handle duplicate room name checking (AJAX-friendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_duplicate_room') {
    try {
        $room_name = trim($_POST['room_name'] ?? '');
        $exclude_room_id = trim($_POST['exclude_room_id'] ?? '');
                
                if (!$room_name) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => 'Room name is required']);
            exit;
        }
        
        $db = new Database();
        
        if ($exclude_room_id) {
            // Check for duplicates excluding current room (for edit)
            $db->query('SELECT COUNT(*) as count FROM rooms WHERE LOWER(room_name) = LOWER(:room_name) AND id != :exclude_room_id');
            $db->bind(':room_name', $room_name);
            $db->bind(':exclude_room_id', $exclude_room_id);
                } else {
            // Check for duplicates (for add)
            $db->query('SELECT COUNT(*) as count FROM rooms WHERE LOWER(room_name) = LOWER(:room_name)');
            $db->bind(':room_name', $room_name);
        }
        
                    $db->execute();
        $result = $db->single();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'exists' => $result['count'] > 0
        ]);
        exit;
                } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - NLS Admin Dashboard</title>
    <meta name="description" content="Manage rooms and their availability status in the NLS system">
    
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
        #activeRooms { border: 1px solid #16a34a22; border-left: 4px solid #16a34a; border-radius: 8px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 1px 6px rgba(22,163,74,0.12); }
        #activeRooms thead { background: #16a34a; color: #fff; }
        #activeRooms tbody tr:nth-child(even) { background: #16a34a0a; }
        #activeRooms tbody tr:hover { background: #16a34a14; }
        #activeRooms td, #activeRooms th { border-color: #16a34a22; }

        #inactiveRooms { border: 1px solid #dc262622; border-left: 4px solid #dc2626; border-radius: 8px; overflow: hidden; margin-top: 24px; box-shadow: 0 1px 6px rgba(220,38,38,0.12); }
         #inactiveRooms thead { background: #dc2626; color: #fff; }
        #inactiveRooms thead { background: #dc2626; color: #fff; }
        #inactiveRooms tbody tr:nth-child(even) { background: #dc26260a; }
        #inactiveRooms tbody tr:hover { background: #dc262614; }
        #inactiveRooms td, #inactiveRooms th { border-color: #dc262622; }

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
        
        .table-container { width: 100%; overflow-x: auto; }
        .table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
         .table thead th { position: sticky; top: 0; background: #16a34a; color: #fff; font-weight: 600; text-align: center; padding: .75rem .9rem; border-bottom: 1px solid rgba(229,231,235,.9); }
        .table tbody td { padding: .7rem .9rem; border-bottom: 1px solid rgba(229,231,235,.6); }
        .table tbody tr:hover { background: #fafbfd; }
        .table tbody tr:nth-child(even) { background: #fcfdff; }
        .table .muted { text-align: center; color: var(--text-secondary); padding: 1rem; }
        
        /* Column alignment for better data positioning */
        .table td:nth-child(1) { text-align: center; } /* Room Name column - center aligned */
        .table td:nth-child(2) { text-align: center; } /* Status column - center aligned */
        .table td:nth-child(3) { text-align: center; } /* Actions column - center aligned */
        
        /* Action buttons container alignment */
        .action-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Button spacing in actions column */
        .table td:nth-child(3) .btn {
            margin: 0 0.25rem;
        }
        
        .btn.btn-sm { padding: .35rem .55rem; font-size: .85rem; border-radius: 8px; }
        .btn.btn-secondary { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .btn.btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn.btn-secondary:hover { background: #e0e7ff; }
        .btn.btn-danger:hover { background: #fecaca; }

         /* Modal animations */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                 transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

         /* Form styling */
         .form-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
         .form-group { display: flex; flex-direction: column; }
         .form-group label { margin-bottom: 0.5rem; font-weight: 500; color: #374151; }
         .form-input { padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
         .form-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
         .form-input.error { border-color: #dc2626; }
         .field-error { 
             color: #dc2626; 
             font-size: 0.875rem; 
             margin-top: 0.25rem; 
            display: none;
         }
         .btn-success { background: #10b981; color: white; border: 1px solid #059669; }
         .btn-success:hover { background: #059669; }
         
         /* Red header for inactive/not available tables */
         #inactiveRooms thead th {
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
                <h1 class="page-title"><i class="fas fa-door-open"></i> Rooms Dashboard</h1>
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
                <a href="rooms-dashboard.php" class="dashboard-card-nav active" data-dashboard="rooms">
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
                            <h2 class="section-title" style="font-size: 1.5rem; font-weight: 700;"><i class="fas fa-door-open"></i> Room Information</h2>
                        </div>
                        <div class="controls-right">
                            <button class="btn btn-primary" id="openAddRoomModal">
                                <i class="fas fa-plus-circle"></i> Add New Room
                            </button>
                        </div>
                    </div>



                    <div class="table-container">
                        <div class="table-section-header"><h3><i class="fas fa-check-circle" style="color:#16a34a"></i> Available Rooms</h3></div>
                        <table class="table" id="activeRooms">
                            <thead>
                                <tr>
                                    <th>Room Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($roomsActive)): ?>
                                    <?php foreach ($roomsActive as $room): ?>
                                        <tr data-room-id="<?php echo $room['id']; ?>">
                                            <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="status-toggle" data-room-id="<?php echo $room['id']; ?>" checked />
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-available">Available</span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                <button class="btn btn-sm btn-secondary edit-room"
                                                        data-room-id="<?php echo $room['id']; ?>"
                                                    data-room-name="<?php echo htmlspecialchars($room['room_name']); ?>"
                                                        data-room-status="<?php echo $room['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                    <button class="btn btn-sm btn-danger delete-room" data-room-id="<?php echo $room['id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="muted">No available rooms found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="table-section-header"><h3><i class="fas fa-ban" style="color:#dc2626"></i> Not Available Rooms</h3></div>
                        <table class="table" id="inactiveRooms">
                            <thead>
                                <tr>
                                    <th>Room Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($roomsInactive)): ?>
                                    <?php foreach ($roomsInactive as $room): ?>
                                        <tr data-room-id="<?php echo $room['id']; ?>">
                                            <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="status-toggle" data-room-id="<?php echo $room['id']; ?>" />
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-unavailable">Not Available</span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                <button class="btn btn-sm btn-secondary edit-room"
                                                        data-room-id="<?php echo $room['id']; ?>"
                                                    data-room-name="<?php echo htmlspecialchars($room['room_name']); ?>"
                                                        data-room-status="<?php echo $room['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                    <button class="btn btn-sm btn-danger delete-room" data-room-id="<?php echo $room['id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="muted">No unavailable rooms found.</td></tr>
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
    
    <!-- Add Room Modal -->
    <div id="addRoomModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 2rem auto; position: relative; top: 50%; transform: translateY(-50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-door-open" style="color: #6366f1; margin-right: 0.5rem;"></i>Add New Room</h3>
                <button id="closeAddRoomModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="rooms-dashboard.php" class="form" novalidate>
                <input type="hidden" name="action" value="add_room" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="room_name">Room Name</label>
                        <input type="text" id="room_name" name="room_name" class="form-input" required pattern="^[A-Za-z0-9\s\-]+$" title="Letters, numbers, spaces, and hyphens only" />
<span class="field-error" id="roomNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="status">Room Status</label>
                        <select id="status" name="status" class="form-input" required>
                            <option value="" disabled selected>Select room status</option>
                            <option value="available">Available</option>
                            <option value="unavailable">Not Available</option>
                        </select>
                        <span class="field-error" id="statusError"></span>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelAddRoom">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 2rem auto; position: relative; top: 50%; transform: translateY(-50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-edit" style="color: #6366f1; margin-right: 0.5rem;"></i>Edit Room</h3>
                <button id="closeEditRoomModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="rooms-dashboard.php" class="form" novalidate>
                <input type="hidden" name="action" value="edit_room" />
                <input type="hidden" id="edit_room_id" name="room_id" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_room_name">Room Name</label>
                        <input type="text" id="edit_room_name" name="room_name" class="form-input" required pattern="^[A-Za-z0-9\s\-]+$" title="Letters, numbers, spaces, and hyphens only" />
<span class="field-error" id="editRoomNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Room Status</label>
                        <select id="edit_status" name="status" class="form-input" required>
                            <option value="" disabled>Select room status</option>
                            <option value="available">Available</option>
                            <option value="unavailable">Not Available</option>
                        </select>
                        <span class="field-error" id="editStatusError"></span>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelEditRoom">Cancel</button>
                    <button type="submit" class="btn btn-success">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Success modal function (same as instructor dashboard)
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

        // Error modal function (same as instructor dashboard)
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

        // Confirmation modal function (same as instructor dashboard)
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

        // Function to sort table rows alphabetically by room name
        function sortTableAlphabetically(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr:not(.muted)'));
            const emptyRow = tbody.querySelector('tr.muted');
            
            // Sort rows by room name (1st column)
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

        // Delete room handler (same as instructor dashboard)
        async function handleDeleteRoom(deleteBtn) {
            const roomId = deleteBtn.getAttribute('data-room-id');
            const roomName = deleteBtn.closest('tr').querySelector('td:nth-child(1)')?.textContent || 'this room';
            const confirmed = await showConfirm(`Delete room "${roomName}"? This action cannot be undone.`, {
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });
            
            if (!confirmed) return;
            
            try {
                const response = await fetch('rooms-dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_room',
                        room_id: roomId,
                        ajax: '1'
                    })
                });
                
                if (response.ok) {
                    showSuccessModal('Room Deleted', 'Room has been successfully deleted from the system.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error('Failed to delete room');
                }
            } catch (error) {
                showErrorModal('Delete Failed', 'Error deleting room');
            }
        }

        // Room status toggle handler
        document.addEventListener('change', async function(e) {
            if (e.target.classList.contains('status-toggle')) {
                e.preventDefault();
                const roomId = e.target.getAttribute('data-room-id');
                const isChecked = e.target.checked;
                const newStatus = isChecked ? 'available' : 'unavailable';
                const statusText = isChecked ? 'Available' : 'Not Available';
                
                // Revert toggle state temporarily until confirmation
                e.target.checked = !isChecked;
                
                // Show confirmation dialog using the same modal as instructor dashboard
                const confirmed = await showConfirm(`Are you sure you want to change this room's status to ${statusText}?`, {
                    confirmText: 'Yes, Change Status',
                    cancelText: 'Cancel'
                });
                
                if (!confirmed) return;
                
                // Set back to user's intended state
                e.target.checked = isChecked;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('room_id', roomId);
                    formData.append('status', newStatus);
                    
                    const response = await fetch('rooms-dashboard.php', {
                    method: 'POST',
                        body: formData
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
                    const targetTableId = isChecked ? 'activeRooms' : 'inactiveRooms';
                    const targetTable = document.getElementById(targetTableId);
                    
                    if (currentTable.id !== targetTableId && targetTable) {
                        const targetTbody = targetTable.querySelector('tbody');
                        if (targetTbody) {
                            // Remove empty message if it exists
                            const emptyRow = targetTbody.querySelector('tr td.muted');
                            if (emptyRow && emptyRow.parentElement) {
                                emptyRow.parentElement.remove();
                            }
                            
                            // Get the room name for sorting
                            const roomName = row.querySelector('td:nth-child(1)').textContent.trim();
                            
                            // Find the correct position to insert the row to maintain alphabetical order
                            const existingRows = targetTbody.querySelectorAll('tr:not(.muted)');
                            let insertPosition = null;
                            
                            // Use localeCompare for proper alphabetical sorting
                            for (let i = 0; i < existingRows.length; i++) {
                                const existingName = existingRows[i].querySelector('td:nth-child(1)').textContent.trim();
                                if (roomName.localeCompare(existingName, undefined, {sensitivity: 'base'}) < 0) {
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
                            if (currentTbody && !currentTbody.querySelector('tr:not(.muted)')) {
                                const emptyMessage = document.createElement('tr');
                                emptyMessage.innerHTML = `<td colspan="3" class="muted">No ${isChecked ? 'unavailable' : 'available'} rooms found.</td>`;
                                currentTbody.appendChild(emptyMessage);
                            }
                        }
                    }
                    
                    // Show success message using the same modal as instructor dashboard
                    showSuccessModal('Status Updated Successfully', `Room status has been updated to ${statusText}`);
                } catch (err) {
                    // Show error message
                    showErrorModal('Status Update Failed', 'Error updating room status: ' + err.message);
                    // Revert toggle state
                    e.target.checked = !isChecked;
                }
            }
        });

        // Delete room button event listeners
        document.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-room');
            if (deleteBtn) {
                e.preventDefault();
                handleDeleteRoom(deleteBtn);
                return;
            }
        });

        // Modal controls
        document.addEventListener('click', (e) => {
            // Add Room Modal
            if (e.target.closest('#openAddRoomModal')) {
                e.preventDefault();
                const modal = document.getElementById('addRoomModal');
                const form = modal.querySelector('form');
                if (form) form.reset();
                modal.style.display = 'flex';
                return;
            }
            
            // Close Add Room Modal
            if (e.target.closest('#closeAddRoomModal, #cancelAddRoom')) {
                e.preventDefault();
                document.getElementById('addRoomModal').style.display = 'none';
                return;
            }

            // Edit Room
            const editBtn = e.target.closest('.edit-room');
            if (editBtn) {
                e.preventDefault();
                const d = editBtn.dataset;
                document.getElementById('edit_room_id').value = d.roomId;
                document.getElementById('edit_room_name').value = d.roomName;
                document.getElementById('edit_status').value = d.roomStatus;
                document.getElementById('editRoomModal').style.display = 'flex';
                return;
            }

            // Close Edit Room Modal
            if (e.target.closest('#closeEditRoomModal, #cancelEditRoom')) {
                e.preventDefault();
                document.getElementById('editRoomModal').style.display = 'none';
                return;
            }
        });

        // Form submissions
        document.addEventListener('submit', async (e) => {
            // Add Room form
            if (e.target.closest('#addRoomModal form')) {
                e.preventDefault();
                const form = e.target;
                
                const roomName = form.querySelector('#room_name')?.value?.trim() || '';
                const status = form.querySelector('#status')?.value || '';
                
                // Clear previous errors
                clearFieldError(document.getElementById('roomNameError'));
                clearFieldError(document.getElementById('statusError'));
                
                let hasErrors = false;
                
                if (!roomName) {
                    showFieldError(document.getElementById('roomNameError'), 'Room name is required');
                    hasErrors = true;
                } else if (!/^[A-Za-z0-9\s\-]+$/.test(roomName)) { 
                    showFieldError(document.getElementById('roomNameError'), 'Room name must contain letters, numbers, spaces, and hyphens only'); 
                    hasErrors = true;
                }
                
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

                try {
                    const formData = new FormData(form);
                formData.set('ajax', '1');
                
                    const response = await fetch('rooms-dashboard.php', { 
                    method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) throw new Error('Request failed');
                    
                    const data = await response.json();
                    if (!data.success) {
                        // Check for duplicate room name error and show field-level alert
                        if (data.message && data.message.includes('already exists')) {
                            showFieldError(document.getElementById('roomNameError'), 'Room name already exists');
                            return;
                        }
                        throw new Error(data.message || 'Failed to add room');
                    }
                    
                        showSuccessModal('Room Added', 'Room has been successfully added to the system.');
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    showErrorModal('Cannot save Room information', err.message || 'Error adding room');
                } finally {
                    if (submitBtn) { 
                        submitBtn.disabled = false; 
                    }
                }
            }

            // Edit Room form
            if (e.target.closest('#editRoomModal form')) {
                e.preventDefault();
                const form = e.target;
                
                const roomName = form.querySelector('#edit_room_name')?.value?.trim() || '';
                const status = form.querySelector('#edit_status')?.value || '';
                
                // Clear previous errors
                clearFieldError(document.getElementById('editRoomNameError'));
                clearFieldError(document.getElementById('editStatusError'));
                
                let hasErrors = false;
                
                if (!roomName) {
                    showFieldError(document.getElementById('editRoomNameError'), 'Room name is required');
                    hasErrors = true;
                } else if (!/^[A-Za-z0-9\s\-]+$/.test(roomName)) { 
                    showFieldError(document.getElementById('editRoomNameError'), 'Room name must contain letters, numbers, spaces, and hyphens only'); 
                    hasErrors = true;
                }
                
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

                try {
                    const formData = new FormData(form);
                    formData.set('ajax', '1');
                    
                    const response = await fetch('rooms-dashboard.php', { 
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) throw new Error('Request failed');
                    
                    const data = await response.json();
                    if (!data.success) {
                        // Check for duplicate room name error and show field-level alert
                        if (data.message && data.message.includes('already exists')) {
                            showFieldError(document.getElementById('editRoomNameError'), 'Room name already exists for another room');
                            return;
                        }
                        throw new Error(data.message || 'Failed to update room');
                    }
                    
                    showSuccessModal('Room Updated', 'Room information has been successfully updated.');
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    showErrorModal('Update Failed', err.message || 'Error updating room');
                } finally {
                    if (submitBtn) { 
                        submitBtn.disabled = false; 
                    }
                }
            }
        });

        // Field error functions (same as instructor dashboard)
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

                // Real-time validation for form fields (same as instructor dashboard)
        document.addEventListener('input', (e) => {
            const target = e.target;
            
            // Add Room form validation
            if (target.closest('#addRoomModal')) {
                if (target.id === 'room_name') {
                    const errorElement = document.getElementById('roomNameError');
                    const value = target.value.trim();
                    
                    if (!value) {
                        showFieldError(errorElement, 'Room name is required');
                    } else if (!/^[A-Za-z0-9\s\-]+$/.test(value)) {
                        showFieldError(errorElement, 'Room name must contain letters, numbers, spaces, and hyphens only');
                    } else {
                        // Clear error for valid format - duplicate check will happen on form submission
                        clearFieldError(errorElement);
                    }
                }
                
                if (target.id === 'status') {
                    const errorElement = document.getElementById('statusError');
                    const value = target.value;
                    
                    if (!value || value === '') {
                        showFieldError(errorElement, 'Please select a status');
                        } else {
                        clearFieldError(errorElement);
                    }
                }
            }
            
            // Edit Room form validation
            if (target.closest('#editRoomModal')) {
                if (target.id === 'edit_room_name') {
                    const errorElement = document.getElementById('editRoomNameError');
                    const value = target.value.trim();
                    
                    if (!value) {
                        showFieldError(errorElement, 'Room name is required');
                    } else if (!/^[A-Za-z0-9\s\-]+$/.test(value)) {
                        showFieldError(errorElement, 'Room name must contain letters, numbers, spaces, and hyphens only');
                    } else {
                        // Clear error for valid format - duplicate check will happen on form submission
                        clearFieldError(errorElement);
                    }
                }
                
                if (target.id === 'edit_status') {
                    const errorElement = document.getElementById('editStatusError');
                    const value = target.value;
                    
                    if (!value || value === '') {
                        showFieldError(errorElement, 'Please select a status');
                    } else {
                        clearFieldError(errorElement);
                    }
                }
            }
        });

        // Allow typing special characters but show validation error (same as instructor dashboard)
        // Special characters will be caught by the input validation and show error message

        // Allow pasting special characters but show validation error (same as instructor dashboard)
        // Special characters will be caught by the input validation and show error message

        // Function to check for duplicate room names (same as instructor dashboard duplicate checking)
        async function checkDuplicateRoomName(roomName, errorElement, excludeRoomId = null) {
            if (!roomName || roomName.length < 2) return Promise.resolve(); // Don't check for very short names
            
            try {
                const formData = new FormData();
                formData.append('action', 'check_duplicate_room');
                formData.append('room_name', roomName);
                if (excludeRoomId) {
                    formData.append('exclude_room_id', excludeRoomId);
                }
                
                const response = await fetch('rooms-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.exists) {
                        const message = excludeRoomId ? 
                            'Room name already exists for another room' : 
                            'Room name already exists';
                        showFieldError(errorElement, message);
                    }
                }
                return Promise.resolve();
            } catch (error) {
                // Silently fail for real-time checks
                console.log('Duplicate check failed:', error);
                return Promise.resolve();
            }
        }
        
        // Ensure tables are sorted alphabetically when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Sort both tables alphabetically by room name
            sortTableAlphabetically('activeRooms');
            sortTableAlphabetically('inactiveRooms');
            

            
            // Initialize dashboard navigation breadcrumb functionality
            initializeDashboardNavigation();
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

