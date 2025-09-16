<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../../classes/User.php';
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
            case 'add_academic_year':
                $academic_year = trim($_POST['academic_year'] ?? '');
                $status = trim($_POST['status'] ?? '');
                
                if (!$academic_year || !$status) {
                    echo json_encode(['success' => false, 'message' => 'Academic year and status are required']);
                    exit;
                }
                
                // Validate academic year format and 1-year gap
                if (!preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
                    echo json_encode(['success' => false, 'message' => 'Academic year must be in format: xxxx-xxxx (e.g., 2024-2025)']);
                    exit;
                }
                
                $years = explode('-', $academic_year);
                $first_year = intval($years[0]);
                $second_year = intval($years[1]);
                
                if ($second_year <= $first_year) {
                    echo json_encode(['success' => false, 'message' => 'Second year must be greater than first year (e.g., 2025-2026)']);
                    exit;
                }
                
                if ($second_year !== $first_year + 1) {
                    echo json_encode(['success' => false, 'message' => 'Academic year must have exactly 1 year gap (e.g., 2025-2026)']);
                    exit;
                }
                
                // Check for duplicate academic year
                $db->query("SELECT COUNT(*) as count FROM academic_years WHERE academic_year = ?");
                $db->bind(1, $academic_year);
                $db->execute();
                $result = $db->single();
                
                if ($result['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Academic year "' . $academic_year . '" already exists in the table']);
                    exit;
                }
                
                // Check if trying to set as active when there's already an active academic year
                if ($status === 'active') {
                    $db->query("SELECT academic_year FROM academic_years WHERE status = 'active'");
                    $db->execute();
                    $existing_active = $db->single();
                    
                    if ($existing_active) {
                        $active_year = $existing_active['academic_year'];
                        echo json_encode(['success' => false, 'message' => "Cannot set this academic year as 'Present Academic Year'. There is already an active academic year: {$active_year}. Please change the status of the current active academic year to 'Other Academic Year' first before setting this one as active."]);
                        exit;
                    }
                }
                
                // Insert new academic year
                $db->query("INSERT INTO academic_years (academic_year, status) VALUES (?, ?)");
                $db->bind(1, $academic_year);
                $db->bind(2, $status);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Academic year added successfully']);
                exit;
                
            case 'edit_academic_year':
                $id = intval($_POST['edit_academic_year_id'] ?? 0);
                $academic_year = trim($_POST['edit_academic_year'] ?? '');
                $status = trim($_POST['edit_status'] ?? '');
                $is_locked = intval($_POST['edit_is_locked'] ?? 0);
                
                if (!$id || !$academic_year || !$status) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }
                
                // Validate academic year format and 1-year gap
                if (!preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
                    echo json_encode(['success' => false, 'message' => 'Academic year must be in format: xxxx-xxxx (e.g., 2024-2025)']);
                    exit;
                }
                
                $years = explode('-', $academic_year);
                $first_year = intval($years[0]);
                $second_year = intval($years[1]);
                
                if ($second_year <= $first_year) {
                    echo json_encode(['success' => false, 'message' => 'Second year must be greater than first year (e.g., 2025-2026)']);
                    exit;
                }
                
                if ($second_year !== $first_year + 1) {
                    echo json_encode(['success' => false, 'message' => 'Academic year must have exactly 1 year gap (e.g., 2025-2026)']);
                    exit;
                }
                
                // Check for duplicate academic year (excluding current)
                $db->query("SELECT COUNT(*) as count FROM academic_years WHERE academic_year = ? AND id != ?");
                $db->bind(1, $academic_year);
                $db->bind(2, $id);
                $db->execute();
                $result = $db->single();
                
                if ($result['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Academic year "' . $academic_year . '" already exists in the table']);
                    exit;
                }
                
                // Update academic year
                $db->query("UPDATE academic_years SET academic_year = ?, status = ?, is_locked = ? WHERE id = ?");
                $db->bind(1, $academic_year);
                $db->bind(2, $status);
                $db->bind(3, $is_locked);
                $db->bind(4, $id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Academic year updated successfully']);
                exit;
                
            case 'delete_academic_year':
                $id = intval($_POST['id'] ?? 0);
                
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'Academic year ID is required']);
                    exit;
                }
                
                // Delete academic year
                $db->query("DELETE FROM academic_years WHERE id = ?");
                $db->bind(1, $id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Academic year deleted successfully']);
                exit;
                
            case 'toggle_status':
                $id = intval($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                
                if (!$id || !$status) {
                    echo json_encode(['success' => false, 'message' => 'Academic year ID and status are required']);
                    exit;
                }
                
                // Check if academic year is locked
                $db->query("SELECT is_locked FROM academic_years WHERE id = ?");
                $db->bind(1, $id);
                $db->execute();
                $year_data = $db->resultset();
                
                if (!empty($year_data) && $year_data[0]['is_locked']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot toggle status: Academic year is locked']);
                    exit;
                }
                
                // If setting to active, check if there's already an active academic year
                if ($status === 'active') {
                    $db->query("SELECT id, academic_year FROM academic_years WHERE status = 'active' AND id != ?");
                    $db->bind(1, $id);
                    $db->execute();
                    $existing_active = $db->resultset();
                    
                    if (!empty($existing_active)) {
                        $active_year = $existing_active[0]['academic_year'];
                        echo json_encode(['success' => false, 'message' => "Cannot activate this academic year. There is already an active academic year: {$active_year}. Please change the status of the current active academic year to 'Other Academic Year' first before activating this one."]);
                        exit;
                    }
                    
                    // Get the academic year being activated
                    $db->query("SELECT academic_year FROM academic_years WHERE id = ?");
                    $db->bind(1, $id);
                    $db->execute();
                    $year_data = $db->single();
                    
                    if ($year_data) {
                        $academic_year = $year_data['academic_year'];
                        
                        // No need to check for future academic years since we removed that functionality
                    }
                    
                    // If no active year exists, set all other academic years to inactive
                    $db->query("UPDATE academic_years SET status = 'inactive' WHERE id != ?");
                    $db->bind(1, $id);
                    $db->execute();
                }
                
                // Update the selected academic year status
                $db->query("UPDATE academic_years SET status = ? WHERE id = ?");
                $db->bind(1, $status);
                $db->bind(2, $id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                exit;
                
            case 'toggle_lock':
                $id = intval($_POST['id'] ?? 0);
                $lock_status = intval($_POST['lock_status'] ?? 0);
                
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'Academic year ID is required']);
                    exit;
                }
                
                // Update academic year lock status
                $db->query("UPDATE academic_years SET is_locked = ? WHERE id = ?");
                $db->bind(1, $lock_status);
                $db->bind(2, $id);
                $db->execute();
                
                echo json_encode(['success' => true, 'message' => 'Lock status updated successfully']);
                exit;
                
            case 'check_duplicate':
                $academic_year = trim($_POST['academic_year'] ?? '');
                $exclude_id = intval($_POST['exclude_id'] ?? 0);
                
                if (!$academic_year) {
                    echo json_encode(['is_duplicate' => false]);
                    exit;
                }
                
                // Check for duplicate academic year
                if ($exclude_id > 0) {
                    $db->query("SELECT COUNT(*) as count FROM academic_years WHERE academic_year = ? AND id != ?");
                    $db->bind(1, $academic_year);
                    $db->bind(2, $exclude_id);
                } else {
                    $db->query("SELECT COUNT(*) as count FROM academic_years WHERE academic_year = ?");
                    $db->bind(1, $academic_year);
                }
                $db->execute();
                $result = $db->single();
                
                echo json_encode(['is_duplicate' => $result['count'] > 0]);
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

// Fetch existing academic years for display
$academic_years = [];
try {
    $db->query("SELECT id, academic_year, status, COALESCE(is_locked, 0) as is_locked FROM academic_years ORDER BY academic_year ASC");
    $db->execute();
    $academic_years = $db->resultset();
} catch (Exception $e) {
    // Handle error silently for now
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Year Dashboard - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Dashboard Navigation Cards */
        .dashboard-navigation-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card-nav {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
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
            border-radius: 8px;
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



        /* Local SVG Icon Styles */
        .local-icon {
            width: 1em;
            height: 1em;
            display: inline-block;
            vertical-align: middle;
            fill: currentColor;
        }

        .local-icon-sm {
            width: 0.875em;
            height: 0.875em;
        }

        .local-icon-lg {
            width: 1.33em;
            height: 1.33em;
        }

        /* Table styling for academic years */
        .table-section-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin: 16px 0 10px; 
        }
        .table-section-header h3 { 
            margin: 0; 
            font-size: 1rem; 
            font-weight: 600; 
        }
        
        /* Active/Inactive table color themes */
        #activeAcademicYears { 
            border: 1px solid #16a34a22; 
            border-left: 4px solid #16a34a; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-bottom: 24px; 
            box-shadow: 0 1px 6px rgba(22,163,74,0.12); 
        }
        #activeAcademicYears thead { 
            background: #16a34a; 
            color: #fff; 
        }
        #activeAcademicYears tbody tr:nth-child(even) { 
            background: #16a34a0a; 
        }
        #activeAcademicYears tbody tr:hover { 
            background: #16a34a14; 
        }
        #activeAcademicYears td, #activeAcademicYears th { 
            border-color: #16a34a22; 
        }

        #inactiveAcademicYears { 
            border: 1px solid #3b82f622; 
            border-left: 4px solid #3b82f6; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-top: 24px; 
            box-shadow: 0 1px 6px rgba(59,130,246,0.12); 
        }
        #inactiveAcademicYears thead { 
            background: #3b82f6; 
            color: #fff; 
        }
        #inactiveAcademicYears tbody tr:nth-child(even) { 
            background: #3b82f60a; 
        }
        #inactiveAcademicYears tbody tr:hover { 
            background: #3b82f614; 
        }
        #inactiveAcademicYears td, #inactiveAcademicYears th { 
            border-color: #3b82f622; 
        }

        #futureAcademicYears { 
            border: 1px solid #f59e0b22; 
            border-left: 4px solid #f59e0b; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-top: 24px; 
            box-shadow: 0 1px 6px rgba(245,158,11,0.12); 
        }
        #futureAcademicYears thead { 
            background: #f59e0b; 
            color: #fff; 
        }
        #futureAcademicYears tbody tr:nth-child(even) { 
            background: #f59e0b0a; 
        }
        #futureAcademicYears tbody tr:hover { 
            background: #f59e0b14; 
        }
        #futureAcademicYears td, #futureAcademicYears th { 
            border-color: #f59e0b22; 
        }

        /* Clean Status indicator colors */
        .status-text {
            white-space: nowrap;
            display: inline-block;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .status-available { 
            color: #16a34a; 
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        .status-unavailable { 
            color: #3b82f6; 
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .status-future { 
            color: #f59e0b; 
            background: #fffbeb;
            border: 1px solid #fed7aa;
        }
        
        .status-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 0.875rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 150px;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .status-select:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* Toggle switch matching lock button colors */
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #3b82f6; transition: .2s; border-radius: 999px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; top: 3px; background: white; transition: .2s; border-radius: 999px; box-shadow: 0 1px 2px rgba(0,0,0,.15); }
        input:checked + .slider { background: #16a34a; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* Simplified Lock Button Styling */
        .lock-toggle {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            min-width: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .lock-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Red button for LOCKED status */
        .lock-toggle.locked {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: 2px solid #b91c1c;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .lock-toggle.locked:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            border-color: #991b1b;
        }

        /* Green button for UNLOCKED status */
        .lock-toggle.unlocked {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border: 2px solid #15803d;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .lock-toggle.unlocked:hover {
            background: linear-gradient(135deg, #15803d, #166534);
            border-color: #166534;
        }

        .lock-toggle i {
            font-size: 1.1em;
        }

        .lock-toggle.locked i {
            animation: lockPulse 2s infinite;
        }

        .lock-toggle.unlocked i {
            animation: unlockPulse 2s infinite;
        }

        @keyframes lockPulse {
            0%, 100% { 
                transform: scale(1);
                color: #fca5a5;
            }
            50% { 
                transform: scale(1.1);
                color: #f87171;
            }
        }

        @keyframes unlockPulse {
            0%, 100% { 
                transform: scale(1);
                color: #86efac;
            }
            50% { 
                transform: scale(1.1);
                color: #4ade80;
            }
        }

        /* Dashboard card styling */
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
        .section-title { 
            margin: 0; 
            font-size: 1.1rem; 
            font-weight: 600; 
        }
        .section-title i { 
            color: var(--primary-color); 
            margin-right: .5rem; 
        }

        /* Table styling */
        .table-container { 
            width: 100%; 
            overflow-x: auto; 
        }
        .table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            table-layout: fixed; 
        }
        .table thead th { 
            position: sticky; 
            top: 0; 
            font-weight: 600; 
            text-align: center; 
            padding: .75rem .9rem; 
            border-bottom: 1px solid rgba(229,231,235,.9); 
        }
        .table tbody td { 
            padding: .7rem .9rem; 
            border-bottom: 1px solid rgba(229,231,235,.6); 
        }
        .table tbody tr:hover { 
            background: #fafbfd; 
        }
        .table tbody tr:nth-child(even) { 
            background: #fcfdff; 
        }
        .table .muted { 
            text-align: center; 
            color: var(--text-secondary); 
            padding: 1rem; 
        }

        /* Button styling */
        .btn { 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: all 0.2s; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        .btn-primary { 
            background: #6366f1; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #5855eb; 
        }
        .btn-success { 
            background: #10b981; 
            color: white; 
        }
        .btn-success:hover { 
            background: #059669; 
        }
        .btn-secondary { 
            background: #6b7280; 
            color: white; 
        }
        .btn-secondary:hover { 
            background: #4b5563; 
        }
        .btn-danger { 
            background: #ef4444; 
            color: white; 
        }
        .btn-danger:hover { 
            background: #dc2626; 
        }
        .btn-warning { 
            background: #f59e0b; 
            color: white; 
        }
        .btn-warning:hover { 
            background: #d97706; 
        }
        .btn-sm { 
            padding: .35rem .55rem; 
            font-size: .85rem; 
            border-radius: 8px; 
        }
        
        /* Disabled button styling */
        .btn:disabled,
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .btn:disabled:hover,
        .btn.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Form styling */
        .form-group { 
            margin-bottom: 1rem; 
            position: relative; 
        }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 0.35rem; 
            color: #374151; 
        }
        .form-input { 
            width: 100%; 
            padding: 0.7rem 0.8rem; 
            border-radius: 10px; 
            border: 1px solid rgba(229,231,235,.9); 
            background: #fff; 
            transition: border-color 0.2s; 
        }
        .form-input:focus { 
            outline: none; 
            border-color: #6366f1; 
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1); 
        }
        .form-input.error { 
            border-color: #dc2626; 
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
        }
        
        .table td {
            border-bottom: 1px solid #f3f4f6;
        }
        
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        /* Academic Year column alignment - center aligned */
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
        
        /* Lock Status column alignment - center aligned */
        .table td:nth-child(4) {
            text-align: center;
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
                    <i class="fas fa-calendar-alt"></i> Academic Year Dashboard
                </h1>
                <button id="headerSidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
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
                <a href="academic-year-dashboard.php" class="dashboard-card-nav active" data-dashboard="academic-year">
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


            </div>

            <div class="main-content">
                 <div class="dashboard-card">
                     <div class="management-controls">
                         <h2 class="section-title" style="font-size: 1.5rem; font-weight: 700;">
                             <i class="fas fa-calendar-alt"></i>
                             Academic Year Information
                         </h2>
                         <button type="button" class="btn btn-primary" id="addAcademicYearBtn">
                             <i class="fas fa-plus"></i> Add New Academic Year
                         </button>
                     </div>

                    <!-- Present Academic Years Table -->
                    <div class="table-section-header">
                        <h3><i class="fas fa-check-circle" style="color: #16a34a;"></i> Present Academic Year</h3>
                    </div>
                    <div class="table-container">
                        <table class="table" id="activeAcademicYears">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Academic Year</th>
                                    <th style="width: 20%;">Academic Year Status</th>
                                    <th style="width: 40%;">Actions</th>
                                    <th style="width: 20%;">Lock Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $activeYears = array_filter($academic_years, function($year) {
                                    return $year['status'] === 'active';
                                });
                                
                                if (empty($activeYears)): ?>
                                    <tr>
                                        <td colspan="4" class="muted">No present academic years found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activeYears as $year): ?>
                                        <tr data-year-id="<?php echo $year['id']; ?>">
                                            <td><?php echo htmlspecialchars($year['academic_year']); ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <label class="switch">
                                                        <input type="checkbox" checked class="status-toggle" data-year-id="<?php echo $year['id']; ?>" <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-text status-available">Present Academic Year</span>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary edit-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                                        data-status="<?php echo $year['status']; ?>"
                                                        data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; justify-content: center;">
                                                    <button type="button" class="btn btn-sm lock-toggle <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'locked' : 'unlocked'; ?>"
                                                            data-year-id="<?php echo $year['id']; ?>"
                                                            data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                        <i class="fas <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                        <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'Locked' : 'Unlocked'; ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>


                    <!-- Other Academic Years Table -->
                    <div class="table-section-header">
                        <h3><i class="fas fa-times-circle" style="color: #3b82f6;"></i> Other Academic Years</h3>
                    </div>
                    <div class="table-container">
                        <table class="table" id="inactiveAcademicYears">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Academic Year</th>
                                    <th style="width: 20%;">Academic Year Status</th>
                                    <th style="width: 40%;">Actions</th>
                                    <th style="width: 20%;">Lock Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $inactiveYears = array_filter($academic_years, function($year) {
                                    return $year['status'] === 'inactive';
                                });
                                
                                if (empty($inactiveYears)): ?>
                                    <tr>
                                        <td colspan="4" class="muted">No other academic years found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inactiveYears as $year): ?>
                                        <tr data-year-id="<?php echo $year['id']; ?>">
                                            <td><?php echo htmlspecialchars($year['academic_year']); ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <label class="switch">
                                                        <input type="checkbox" class="status-toggle" data-year-id="<?php echo $year['id']; ?>" <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-text status-unavailable">Other Academic Year</span>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary edit-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                                        data-status="<?php echo $year['status']; ?>"
                                                        data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                                        <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; justify-content: center;">
                                                    <button type="button" class="btn btn-sm lock-toggle <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'locked' : 'unlocked'; ?>"
                                                            data-year-id="<?php echo $year['id']; ?>"
                                                            data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                        <i class="fas <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                        <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'Locked' : 'Unlocked'; ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Future Academic Years Table -->
                    <div class="table-section-header">
                        <h3><i class="fas fa-calendar-plus" style="color: #f59e0b;"></i> Future Academic Years</h3>
                    </div>
                    <div class="table-container">
                        <table class="table" id="futureAcademicYears">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Academic Year</th>
                                    <th style="width: 20%;">Academic Year Status</th>
                                    <th style="width: 40%;">Actions</th>
                                    <th style="width: 20%;">Lock Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $futureYears = array_filter($academic_years, function($year) {
                                    return $year['status'] === 'future';
                                });
                                
                                if (empty($futureYears)): ?>
                                    <tr>
                                        <td colspan="4" class="muted">No future academic years found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($futureYears as $year): ?>
                                        <tr data-year-id="<?php echo $year['id']; ?>">
                                            <td><?php echo htmlspecialchars($year['academic_year']); ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <label class="switch">
                                                        <input type="checkbox" class="status-toggle" data-year-id="<?php echo $year['id']; ?>" <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-text status-future">Future Academic Year</span>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary edit-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                                        data-status="<?php echo $year['status']; ?>"
                                                        data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-academic-year <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>"
                                                        data-year-id="<?php echo $year['id']; ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($year['academic_year']); ?>"
                                                        <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; justify-content: center;">
                                                    <button type="button" class="btn btn-sm lock-toggle <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'locked' : 'unlocked'; ?>"
                                                            data-year-id="<?php echo $year['id']; ?>"
                                                            data-is-locked="<?php echo isset($year['is_locked']) ? ($year['is_locked'] ? '1' : '0') : '0'; ?>">
                                                        <i class="fas <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                        <?php echo (isset($year['is_locked']) && $year['is_locked']) ? 'Locked' : 'Unlocked'; ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Academic Year Modal -->
            <div id="addAcademicYearModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0; color: #111827;">Add New Academic Year</h3>
                        <button id="closeAddAcademicYearModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
                    </div>
                    <form id="addAcademicYearForm">
                        <div class="form-group">
                            <label for="academic_year">Academic Year *</label>
                            <input type="text" id="academic_year" name="academic_year" class="form-input" placeholder="e.g., 2024-2025" required>
                            <div class="field-error" id="academicYearError"></div>
                        </div>
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" class="form-input" required>
                                <option value="" disabled selected>Select status</option>
                                <option value="active">Present Academic Year</option>
                                <option value="inactive">Other Academic Year</option>
                                <option value="future">Future Academic Year</option>
                            </select>
                            <div class="field-error" id="statusError"></div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <button type="button" class="btn btn-secondary" id="cancelAddAcademicYear">Cancel</button>
                            <button type="submit" class="btn btn-success">Add Academic Year</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Academic Year Modal -->
            <div id="editAcademicYearModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0; color: #111827;">Edit Academic Year</h3>
                        <button id="closeEditAcademicYearModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
                    </div>
                    <form id="editAcademicYearForm">
                        <input type="hidden" id="edit_academic_year_id" name="edit_academic_year_id">
                        <div class="form-group">
                            <label for="edit_academic_year">Academic Year *</label>
                            <input type="text" id="edit_academic_year" name="edit_academic_year" class="form-input" required>
                            <div class="field-error" id="editAcademicYearError"></div>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status *</label>
                            <select id="edit_status" name="edit_status" class="form-input" required>
                                <option value="" disabled>Select status</option>
                                <option value="active">Present Academic Year</option>
                                <option value="inactive">Other Academic Year</option>
                                <option value="future">Future Academic Year</option>
                            </select>
                            <div class="field-error" id="editStatusError"></div>
                        </div>
                        <div class="form-group">
                            <label for="edit_is_locked">Lock Status</label>
                            <select id="edit_is_locked" name="edit_is_locked" class="form-input">
                                <option value="0">Unlocked</option>
                                <option value="1">Locked</option>
                            </select>
                            <div class="field-error" id="editLockError"></div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <button type="button" class="btn btn-secondary" id="cancelEditAcademicYear">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Confirm Modal -->
            <div id="confirmModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:3100; background:rgba(17,24,39,.45); padding:1rem;">
                <div class="notice-box" style="background:#fff; width:min(480px,92vw); border-radius:14px; border:1px solid rgba(229,231,235,.9); box-shadow:0 20px 60px rgba(0,0,0,.25); padding:1rem 1.25rem; text-align:center;">
                                <div class="notice-header" style="display:flex; align-items:center; justify-content:center; gap:.5rem; margin-bottom:.5rem;">
                <i id="confirmIcon" class="fas fa-exclamation-triangle" style="color:#dc2626" aria-hidden="true"></i>
                <h4 id="confirmHeader">Confirm Action</h4>
            </div>
                                <div class="notice-content" style="margin:.5rem 0;">
                <span id="confirmText" style="white-space: pre-line; line-height: 1.5;"></span>
            </div>
                    <div class="notice-actions" style="margin-top:.75rem; display:flex; justify-content:center; gap:.5rem;">
                        <button id="confirmYes" class="btn btn-primary">Yes, Change Status</button>
                        <button id="confirmNo" class="btn btn-secondary">Cancel</button>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Initialize sidebar functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebarFunctionality();
            initializeAcademicYearFunctionality();
        });

        // Sidebar functionality
        function initializeSidebarFunctionality() {
            const sidebar = document.querySelector('.admin-sidebar');
            const adminMain = document.querySelector('.admin-main');

            // Mobile sidebar toggle
            const headerSidebarToggle = document.getElementById('headerSidebarToggle');
            if (headerSidebarToggle) {
                headerSidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }
        }

        // Success modal function
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

        // Error modal function
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
            const confirmHeader = document.getElementById('confirmHeader');
            const confirmIcon = document.getElementById('confirmIcon');
            
            confirmText.textContent = message;
            
            // Update button text, header, and icon based on action type
            if (actionType === 'delete') {
                confirmYes.textContent = 'Yes, Delete';
                confirmHeader.textContent = 'Confirm Deletion';
                confirmIcon.className = 'fas fa-trash';
                confirmIcon.style.color = '#dc2626';
                confirmYes.className = 'btn btn-danger';
            } else if (actionType === 'change') {
                confirmYes.textContent = 'Yes, Change Status';
                confirmHeader.textContent = 'Confirm Status Change';
                confirmIcon.className = 'fas fa-exchange-alt';
                confirmIcon.style.color = '#f59e0b';
                confirmYes.className = 'btn btn-warning';
            } else if (actionType === 'lock') {
                confirmYes.textContent = 'Yes, Lock';
                confirmHeader.textContent = 'Confirm Lock';
                confirmIcon.className = 'fas fa-lock';
                confirmIcon.style.color = '#dc2626';
                confirmYes.className = 'btn btn-danger';
            } else if (actionType === 'unlock') {
                confirmYes.textContent = 'Yes, Unlock';
                confirmHeader.textContent = 'Confirm Unlock';
                confirmIcon.className = 'fas fa-unlock';
                confirmIcon.style.color = '#16a34a';
                confirmYes.className = 'btn btn-success';
            } else {
                confirmYes.textContent = 'Yes, Confirm';
                confirmHeader.textContent = 'Confirm Action';
                confirmIcon.className = 'fas fa-exclamation-triangle';
                confirmIcon.style.color = '#dc2626';
                confirmYes.className = 'btn btn-primary';
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
            const errorElement = document.getElementById(elementId);
            
            // Map error element IDs to their corresponding input element IDs
            const inputElementMap = {
                'academicYearError': 'academic_year',
                'statusError': 'status',
                'editAcademicYearError': 'edit_academic_year',
                'editStatusError': 'edit_status'
            };
            
            const inputElementId = inputElementMap[elementId];
            const inputElement = inputElementId ? document.getElementById(inputElementId) : null;
            
            if (errorElement && inputElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
            }
        }

        // Check for duplicate academic year
        async function checkDuplicateAcademicYear(academicYear, excludeId = null) {
            try {
                const formData = new FormData();
                formData.append('action', 'check_duplicate');
                formData.append('academic_year', academicYear);
                if (excludeId) {
                    formData.append('exclude_id', excludeId);
                }
                
                const response = await fetch('academic-year-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                return result.is_duplicate;
            } catch (error) {
                console.error('Error checking duplicate:', error);
                return false;
            }
        }

        // Check if there's already an active academic year
        function checkForActiveAcademicYear(excludeId = null) {
            const activeTable = document.getElementById('activeAcademicYears');
            const activeRows = activeTable.querySelectorAll('tbody tr[data-year-id]');
            
            for (let row of activeRows) {
                const yearId = row.getAttribute('data-year-id');
                if (excludeId && yearId === excludeId.toString()) {
                    continue; // Skip the current row being toggled
                }
                // Check if this row has active status (toggle checked or select value active)
                const toggle = row.querySelector('.status-toggle');
                const select = row.querySelector('.status-select');
                
                if (toggle && toggle.checked) {
                    return {
                        hasActive: true,
                        activeYear: row.querySelector('td:first-child').textContent.trim()
                    };
                } else if (select && select.value === 'active') {
                    return {
                        hasActive: true,
                        activeYear: row.querySelector('td:first-child').textContent.trim()
                    };
                }
            }
            
            return { hasActive: false, activeYear: null };
        }

        // Academic Year validation functions
        async function validateAcademicYearInput() {
            const input = this;
            const value = input.value;
            const errorElement = document.getElementById('academicYearError');
            
            // Check if input contains letters or invalid special characters
            if (/[a-zA-Z]/.test(value)) {
                showFieldError('academicYearError', 'Letters are not allowed. Only numbers are permitted.');
                return;
            }
            
            // Check for invalid special characters (allow only numbers and dashes)
            if (/[^0-9\-]/.test(value)) {
                showFieldError('academicYearError', 'Special characters are not allowed. Only numbers are permitted.');
                return;
            }
            
            // Check for exact format: xxxx-xxxx (4 digits, hyphen, 4 digits)
            if (value.length > 0) {
                const formatRegex = /^\d{4}-\d{4}$/;
                if (!formatRegex.test(value)) {
                    showFieldError('academicYearError', 'Academic Year must be in format: xxxx-xxxx (e.g., 2024-2025)');
                    return;
                }
                
                // Check for 1-year gap only
                const years = value.split('-');
                const firstYear = parseInt(years[0]);
                const secondYear = parseInt(years[1]);
                
                if (secondYear <= firstYear) {
                    showFieldError('academicYearError', 'Second year must be greater than first year (e.g., 2025-2026)');
                    return;
                }
                
                if (secondYear !== firstYear + 1) {
                    showFieldError('academicYearError', 'Academic Year must have exactly 1 year gap (e.g., 2025-2026)');
                    return;
                }
                
                // Check for duplicate academic year
                const isDuplicate = await checkDuplicateAcademicYear(value);
                if (isDuplicate) {
                    showFieldError('academicYearError', 'Academic year "' + value + '" already exists in the table');
                    return;
                }
            }
            
            // Clear error if input is valid
            if (errorElement) {
                errorElement.style.display = 'none';
                input.classList.remove('error');
            }
        }

        function preventInvalidCharacters(e) {
            const key = e.key;
            
            // Allow navigation keys and deletion keys
            if (key === 'Backspace' || key === 'Delete' || key === 'ArrowLeft' || 
                key === 'ArrowRight' || key === 'ArrowUp' || key === 'ArrowDown' || 
                key === 'Tab' || key === 'Enter') {
                return true;
            }
            
            // Allow only numbers (no need for dash since it's auto-inserted)
            if (/[0-9]/.test(key)) {
                const input = e.target;
                const value = input.value;
                
                // Check total length limit (9 characters: xxxx-xxxx)
                if (value.length >= 9 && key !== 'Backspace' && key !== 'Delete') {
                    e.preventDefault();
                    if (input.id === 'academic_year') {
                        showFieldError('academicYearError', 'Maximum length reached. Format: xxxx-xxxx');
                    } else if (input.id === 'edit_academic_year') {
                        showFieldError('editAcademicYearError', 'Maximum length reached. Format: xxxx-xxxx');
                    }
                    return false;
                }
                
                // Auto-insert hyphen after 4 digits when typing
                setTimeout(() => {
                    const currentValue = input.value;
                    if (currentValue.length === 4 && !currentValue.includes('-')) {
                        input.value = currentValue + '-';
                        input.setSelectionRange(5, 5);
                    }
                }, 10);
                
                return true;
            }
            
            // Prevent invalid characters and show error below the field
            e.preventDefault();
            
            // Determine which input field this is and show appropriate error
            const input = e.target;
            if (input.id === 'academic_year') {
                showFieldError('academicYearError', 'Only numbers are allowed. Letters and special characters are not permitted.');
            } else if (input.id === 'edit_academic_year') {
                showFieldError('editAcademicYearError', 'Only numbers are allowed. Letters and special characters are not permitted.');
            }
            
            return false;
        }

        async function preventInvalidPaste(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            
            // Check if pasted text contains invalid characters
            if (/[a-zA-Z]/.test(pastedText)) {
                // Show error below the field instead of alert
                const target = e.target;
                if (target.id === 'academic_year') {
                    showFieldError('academicYearError', 'Letters are not allowed. Only numbers are permitted.');
                } else if (target.id === 'edit_academic_year') {
                    showFieldError('editAcademicYearError', 'Letters are not allowed. Only numbers are permitted.');
                }
                return;
            }
            
            if (/[^0-9\-]/.test(pastedText)) {
                // Show error below the field instead of alert
                const target = e.target;
                if (target.id === 'academic_year') {
                    showFieldError('academicYearError', 'Special characters are not allowed. Only numbers are permitted.');
                } else if (target.id === 'edit_academic_year') {
                    showFieldError('editAcademicYearError', 'Special characters are not allowed. Only numbers are permitted.');
                }
                return;
            }
            
            // Check if pasted text follows the xxxx-xxxx format
            const target = e.target;
            const currentValue = target.value;
            const newValue = currentValue.substring(0, target.selectionStart) + pastedText + currentValue.substring(target.selectionEnd);
            
            // Validate the new value would follow the format
            if (newValue.length > 0) {
                const formatRegex = /^\d{4}-\d{4}$/;
                if (!formatRegex.test(newValue)) {
                    if (target.id === 'academic_year') {
                        showFieldError('academicYearError', 'Pasted text must follow format: xxxx-xxxx (e.g., 2024-2025)');
                    } else if (target.id === 'edit_academic_year') {
                        showFieldError('editAcademicYearError', 'Pasted text must follow format: xxxx-xxxx (e.g., 2024-2025)');
                    }
                    return;
                }
                
                // Check for 1-year gap only
                const years = newValue.split('-');
                const firstYear = parseInt(years[0]);
                const secondYear = parseInt(years[1]);
                
                if (secondYear <= firstYear) {
                    if (target.id === 'academic_year') {
                        showFieldError('academicYearError', 'Second year must be greater than first year (e.g., 2025-2026)');
                    } else if (target.id === 'edit_academic_year') {
                        showFieldError('editAcademicYearError', 'Second year must be greater than first year (e.g., 2025-2026)');
                    }
                    return;
                }
                
                if (secondYear !== firstYear + 1) {
                    if (target.id === 'academic_year') {
                        showFieldError('academicYearError', 'Academic Year must have exactly 1 year gap (e.g., 2025-2026)');
                    } else if (target.id === 'edit_academic_year') {
                        showFieldError('editAcademicYearError', 'Academic Year must have exactly 1 year gap (e.g., 2025-2026)');
                    }
                    return;
                }
                
                // Check for duplicate academic year
                const editId = target.id === 'edit_academic_year' ? document.getElementById('edit_academic_year_id').value : null;
                const isDuplicate = await checkDuplicateAcademicYear(newValue, editId);
                if (isDuplicate) {
                    if (target.id === 'academic_year') {
                        showFieldError('academicYearError', 'Academic year "' + newValue + '" already exists in the table');
                    } else if (target.id === 'edit_academic_year') {
                        showFieldError('editAcademicYearError', 'Academic year "' + newValue + '" already exists in the table');
                    }
                    return;
                }
            }
            
            // If valid, insert the text
            const start = target.selectionStart;
            const end = target.selectionEnd;
            target.value = newValue;
            
            // Set cursor position
            target.selectionStart = target.selectionEnd = start + pastedText.length;
            
            // Clear any existing errors since the input is now valid
            if (target.id === 'academic_year') {
                clearFieldError('academicYearError');
            } else if (target.id === 'edit_academic_year') {
                clearFieldError('editAcademicYearError');
            }
        }

        async function validateEditAcademicYearInput() {
            const input = this;
            const value = input.value;
            const errorElement = document.getElementById('editAcademicYearError');
            const editId = document.getElementById('edit_academic_year_id').value;
            
            // Check if input contains letters or invalid special characters
            if (/[a-zA-Z]/.test(value)) {
                showFieldError('editAcademicYearError', 'Letters are not allowed. Only numbers are permitted.');
                return;
            }
            
            // Check for invalid special characters (allow only numbers and dashes)
            if (/[^0-9\-]/.test(value)) {
                showFieldError('editAcademicYearError', 'Special characters are not allowed. Only numbers are permitted.');
                return;
            }
            
            // Check for exact format: xxxx-xxxx (4 digits, hyphen, 4 digits)
            if (value.length > 0) {
                const formatRegex = /^\d{4}-\d{4}$/;
                if (!formatRegex.test(value)) {
                    showFieldError('editAcademicYearError', 'Academic Year must be in format: xxxx-xxxx (e.g., 2024-2025)');
                    return;
                }
                
                // Check for 1-year gap only
                const years = value.split('-');
                const firstYear = parseInt(years[0]);
                const secondYear = parseInt(years[1]);
                
                if (secondYear <= firstYear) {
                    showFieldError('editAcademicYearError', 'Second year must be greater than first year (e.g., 2025-2026)');
                    return;
                }
                
                if (secondYear !== firstYear + 1) {
                    showFieldError('editAcademicYearError', 'Academic Year must have exactly 1 year gap (e.g., 2025-2026)');
                    return;
                }
                
                // Check for duplicate academic year (excluding current)
                const isDuplicate = await checkDuplicateAcademicYear(value, editId);
                if (isDuplicate) {
                    showFieldError('editAcademicYearError', 'Academic year "' + value + '" already exists in the table');
                    return;
                }
            }
            
            // Clear error if input is valid
            if (errorElement) {
                errorElement.style.display = 'none';
                input.classList.remove('error');
            }
        }

        function clearFieldError(elementId) {
            const errorElement = document.getElementById(elementId);
            
            // Map error element IDs to their corresponding input element IDs
            const inputElementMap = {
                'academicYearError': 'academic_year',
                'statusError': 'status',
                'editAcademicYearError': 'edit_academic_year',
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

        // Toggle lock status function
        async function toggleLockStatus(yearId, newLockStatus, button) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_lock');
                formData.append('id', yearId);
                formData.append('lock_status', newLockStatus);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update button appearance
                    if (newLockStatus === 1) {
                        button.className = 'btn btn-sm lock-toggle locked';
                        button.innerHTML = '<i class="fas fa-lock"></i> Locked';
                        button.setAttribute('data-is-locked', '1');
                        
                        // Disable status toggle/select, edit, and delete buttons for this row
                        const row = button.closest('tr');
                        const statusToggle = row.querySelector('.status-toggle');
                        const statusSelect = row.querySelector('.status-select');
                        const editBtn = row.querySelector('.edit-academic-year');
                        const deleteBtn = row.querySelector('.delete-academic-year');
                        
                        if (statusToggle) {
                            statusToggle.disabled = true;
                        }
                        if (statusSelect) {
                            statusSelect.disabled = true;
                        }
                        if (editBtn) {
                            editBtn.disabled = true;
                            editBtn.classList.add('disabled');
                        }
                        if (deleteBtn) {
                            deleteBtn.disabled = true;
                            deleteBtn.classList.add('disabled');
                        }
                    } else {
                        button.className = 'btn btn-sm lock-toggle unlocked';
                        button.innerHTML = '<i class="fas fa-unlock"></i> Unlocked';
                        button.setAttribute('data-is-locked', '0');
                        
                        // Enable status toggle/select, edit, and delete buttons for this row
                        const row = button.closest('tr');
                        const statusToggle = row.querySelector('.status-toggle');
                        const statusSelect = row.querySelector('.status-select');
                        const editBtn = row.querySelector('.edit-academic-year');
                        const deleteBtn = row.querySelector('.delete-academic-year');
                        
                        if (statusToggle) {
                            statusToggle.disabled = false;
                        }
                        if (statusSelect) {
                            statusSelect.disabled = false;
                        }
                        if (editBtn) {
                            editBtn.disabled = false;
                            editBtn.classList.remove('disabled');
                        }
                        if (deleteBtn) {
                            deleteBtn.disabled = false;
                            deleteBtn.classList.remove('disabled');
                        }
                    }
                    
                    showSuccessModal('Success', 'Lock status updated successfully');
                } else {
                    showErrorModal(result.message || 'Failed to update lock status');
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorModal('An error occurred while updating lock status');
            }
        }

        // Flag to prevent double execution
        let isHandlingToggle = false;

        // Function to add a new academic year row to the appropriate table
        function addNewAcademicYearRow(academicYear, status) {
            // Determine target table based on status
            let targetTable;
            if (status === 'active') {
                targetTable = document.getElementById('activeAcademicYears');
            } else if (status === 'future') {
                targetTable = document.getElementById('futureAcademicYears');
            } else {
                targetTable = document.getElementById('inactiveAcademicYears');
            }
            
            if (!targetTable) return;
            
            const targetTbody = targetTable.querySelector('tbody');
            
            // Remove placeholder if exists
            const placeholder = targetTbody.querySelector('.muted');
            if (placeholder) {
                placeholder.closest('tr').remove();
            }
            
            // Create new row
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-year-id', 'new-' + Date.now()); // Temporary ID
            
            // Determine status display and selected option
            let statusDisplay, selectedOption;
            if (status === 'active') {
                statusDisplay = 'Present Academic Year';
                selectedOption = '<option value="active" selected>Present Academic Year</option><option value="inactive">Other Academic Year</option><option value="future">Future Academic Year</option>';
            } else if (status === 'future') {
                statusDisplay = 'Future Academic Year';
                selectedOption = '<option value="active">Present Academic Year</option><option value="inactive">Other Academic Year</option><option value="future" selected>Future Academic Year</option>';
            } else {
                statusDisplay = 'Other Academic Year';
                selectedOption = '<option value="active">Present Academic Year</option><option value="inactive" selected>Other Academic Year</option><option value="future">Future Academic Year</option>';
            }
            
            newRow.innerHTML = `
                <td>${academicYear}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <select class="status-select" data-year-id="new-${Date.now()}" disabled>
                            ${selectedOption}
                        </select>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-secondary edit-academic-year" disabled>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-academic-year" disabled>
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
                <td>
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <button type="button" class="btn btn-sm lock-toggle unlocked" disabled>
                            <i class="fas fa-unlock"></i> Unlocked
                        </button>
                    </div>
                </td>
            `;
            
            // Add row to table
            targetTbody.appendChild(newRow);
            
            // Add animation
            newRow.style.opacity = '0';
            newRow.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                newRow.style.transition = 'all 0.3s ease';
                newRow.style.opacity = '1';
                newRow.style.transform = 'translateY(0)';
            }, 100);
        }

        // Function to check for existing active academic year and disable option
        function checkAndDisableActiveOption() {
            const statusSelect = document.getElementById('status');
            const activeOption = statusSelect.querySelector('option[value="active"]');
            const warningMessage = document.getElementById('activeYearWarning');
            const academicYearInput = document.getElementById('academic_year');
            
            // Check if there's already an active academic year in the table
            const activeTable = document.getElementById('activeAcademicYears');
            const activeRows = activeTable.querySelectorAll('tbody tr[data-year-id]');
            const hasActiveYear = activeRows.length > 0;
            
            // No need to check for future academic years since we removed that functionality
            
            // No need to check for earlier future years since we removed that functionality
            
            if (hasActiveYear) {
                // Disable the "Present Academic Year" option
                activeOption.disabled = true;
                activeOption.style.color = '#9ca3af';
                activeOption.style.backgroundColor = '#f3f4f6';
                
                // Add warning message if it doesn't exist
                if (!warningMessage) {
                    const warningDiv = document.createElement('div');
                    warningDiv.id = 'activeYearWarning';
                    warningDiv.className = 'alert alert-warning';
                    warningDiv.style.cssText = 'margin-top: 1rem; padding: 0.75rem; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; color: #92400e;';
                    
                    let warningText = '';
                    if (hasActiveYear) {
                        warningText = '<i class="fas fa-exclamation-triangle"></i> <strong>Notice:</strong> There is already a present academic year. You cannot set a new academic year as active until you change the current active academic year to "Other Academic Year".';
                    }
                    
                    warningDiv.innerHTML = warningText;
                    
                    // Insert warning after the status field
                    const statusGroup = statusSelect.closest('.form-group');
                    statusGroup.appendChild(warningDiv);
                } else {
                    // Update existing warning message
                    let warningText = '';
                    if (hasActiveYear) {
                        warningText = '<i class="fas fa-exclamation-triangle"></i> <strong>Notice:</strong> There is already a present academic year. You cannot set a new academic year as active until you change the current active academic year to "Other Academic Year".';
                    }
                    warningMessage.innerHTML = warningText;
                }
            } else {
                // Enable the "Present Academic Year" option
                activeOption.disabled = false;
                activeOption.style.color = '';
                activeOption.style.backgroundColor = '';
                
                // Remove warning message if it exists
                if (warningMessage) {
                    warningMessage.remove();
                }
            }
        }

        // Function to reset the add academic year form
        function resetAddAcademicYearForm() {
            const form = document.getElementById('addAcademicYearForm');
            if (form) {
                form.reset();
            }
            
            // Clear field errors
            clearFieldError('academicYearError');
            clearFieldError('statusError');
            
            // Remove any warning messages
            const warningMessage = document.getElementById('activeYearWarning');
            if (warningMessage) {
                warningMessage.remove();
            }
            
            // Reset status select styling
            const statusSelect = document.getElementById('status');
            const activeOption = statusSelect.querySelector('option[value="active"]');
            if (activeOption) {
                activeOption.disabled = false;
                activeOption.style.color = '';
                activeOption.style.backgroundColor = '';
            }
        }

        // Academic Year Functionality
        function initializeAcademicYearFunctionality() {
            // Add Academic Year functionality
            document.getElementById('addAcademicYearBtn').addEventListener('click', () => {
                document.getElementById('addAcademicYearModal').style.display = 'flex';
                
                // Check for existing active academic year and disable option if needed
                checkAndDisableActiveOption();
                
                // Add validation event listeners when modal opens
                setTimeout(() => {
                    const academicYearInput = document.getElementById('academic_year');
                    if (academicYearInput) {
                        academicYearInput.addEventListener('input', validateAcademicYearInput);
                        academicYearInput.addEventListener('keypress', preventInvalidCharacters);
                        academicYearInput.addEventListener('paste', preventInvalidPaste);
                        academicYearInput.addEventListener('input', checkAndDisableActiveOption);
                    }
                }, 100);
            });

            // Add event delegation for lock toggle buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('.lock-toggle')) {
                    const button = e.target.closest('.lock-toggle');
                    const yearId = button.getAttribute('data-year-id');
                    const currentLockStatus = button.getAttribute('data-is-locked');
                    const newLockStatus = currentLockStatus === '1' ? 0 : 1;
                    const academicYear = button.closest('tr').querySelector('td:first-child').textContent;
                    
                    // Show confirmation modal for lock/unlock action
                    const action = newLockStatus === 1 ? 'lock' : 'unlock';
                    const actionText = newLockStatus === 1 ? 'Lock' : 'Unlock';
                    const currentStatus = currentLockStatus === '1' ? 'locked' : 'unlocked';
                    const message = `Are you sure you want to ${action} the academic year "${academicYear}"? 

Current status: ${currentStatus}
${actionText === 'Lock' ? 'This will prevent editing and deletion of this academic year.' : 'This will allow editing and deletion of this academic year.'}`;
                    
                    showConfirmModal(message, async (confirmed) => {
                        if (confirmed) {
                            toggleLockStatus(yearId, newLockStatus, button);
                        }
                    }, action);
                }
            });

            document.getElementById('closeAddAcademicYearModal').addEventListener('click', () => {
                document.getElementById('addAcademicYearModal').style.display = 'none';
                resetAddAcademicYearForm();
            });

            document.getElementById('cancelAddAcademicYear').addEventListener('click', () => {
                document.getElementById('addAcademicYearModal').style.display = 'none';
                resetAddAcademicYearForm();
            });

            // Edit Academic Year functionality
            document.getElementById('closeEditAcademicYearModal').addEventListener('click', () => {
                document.getElementById('editAcademicYearModal').style.display = 'none';
            });

            document.getElementById('cancelEditAcademicYear').addEventListener('click', () => {
                document.getElementById('editAcademicYearModal').style.display = 'none';
            });

            // Form submissions
            document.getElementById('addAcademicYearForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // Check if trying to set as active when there's already an active academic year
                const statusSelect = document.getElementById('status');
                const selectedStatus = statusSelect.value;
                const activeOption = statusSelect.querySelector('option[value="active"]');
                
                if (selectedStatus === 'active' && activeOption.disabled) {
                    showErrorModal('Error', 'Cannot set this academic year as "Present Academic Year". There is already an active academic year. Please change the status of the current active academic year to "Other Academic Year" first before setting this one as active.');
                    return;
                }
                
                const formData = new FormData(e.target);
                formData.append('action', 'add_academic_year');
                
                try {
                    const response = await fetch('academic-year-dashboard.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccessModal('Success', 'Academic year added successfully!');
                        document.getElementById('addAcademicYearModal').style.display = 'none';
                        
                        // Get form data
                        const academicYear = document.getElementById('academic_year').value;
                        const status = document.getElementById('status').value;
                        
                        // Add the new row to the appropriate table
                        addNewAcademicYearRow(academicYear, status);
                        
                        e.target.reset();
                        resetAddAcademicYearForm();
                    } else {
                        showErrorModal('Error', data.message || 'Failed to add academic year');
                    }
                } catch (err) {
                    showErrorModal('Error', err.message || 'Error adding academic year');
                }
            });

            document.getElementById('editAcademicYearForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                formData.append('action', 'edit_academic_year');
                
                try {
                    const response = await fetch('academic-year-dashboard.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccessModal('Success', 'Academic year updated successfully!');
                        document.getElementById('editAcademicYearModal').style.display = 'none';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showErrorModal('Error', data.message || 'Failed to update academic year');
                    }
                } catch (err) {
                    showErrorModal('Error', err.message || 'Error updating academic year');
                }
            });

            // Status toggle functionality
            document.addEventListener('change', async (e) => {
                if (e.target.classList.contains('status-toggle')) {
                    // Skip if we're already handling this toggle in the click event
                    if (isHandlingToggle) {
                        return;
                    }
                    
                    console.log('Status toggle changed:', e.target.checked);
                    const toggle = e.target;
                    const yearId = toggle.dataset.yearId;
                    const newStatus = toggle.checked ? 'active' : 'inactive';
                    const statusText = toggle.closest('td').querySelector('.status-text');
                    
                    // Check if trying to activate a past academic year when there's already an active one
                    if (newStatus === 'active') {
                        const activeCheck = checkForActiveAcademicYear(yearId);
                        if (activeCheck.hasActive) {
                            // Revert the toggle
                            toggle.checked = false;
                            showErrorModal('Cannot Activate Academic Year', `Cannot activate this academic year. There is already an active academic year: ${activeCheck.activeYear}. Please change the status of the current active academic year to 'Other Academic Year' first before activating this one.`);
                            return;
                        }
                    }
                    
                    // Update status text immediately
                    if (statusText) {
                        if (newStatus === 'active') {
                            statusText.textContent = 'Present Academic Year';
                            statusText.className = 'status-text status-available';
                        } else if (newStatus === 'future') {
                            statusText.textContent = 'Future Academic Year';
                            statusText.className = 'status-text status-future';
                        } else {
                            statusText.textContent = 'Other Academic Year';
                            statusText.className = 'status-text status-unavailable';
                        }
                    }
                    
                    // Show confirmation for all valid status changes
                    let statusDisplay = newStatus === 'active' ? 'Present Academic Year' : newStatus === 'future' ? 'Future Academic Year' : 'Other Academic Year';
                    showConfirmModal(`Are you sure you want to change this academic year status to ${statusDisplay}?`, async (confirmed) => {
                        if (confirmed) {
                            try {
                                const formData = new FormData();
                                formData.append('action', 'toggle_status');
                                formData.append('id', yearId);
                                formData.append('status', newStatus);

                                const response = await fetch('academic-year-dashboard.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                const data = await response.json();
                                
                                if (data.success) {
                                    // If setting to active, move all other active years to inactive table
                                    if (newStatus === 'active') {
                                        const activeTable = document.getElementById('activeAcademicYears');
                                        const inactiveTable = document.getElementById('inactiveAcademicYears');
                                        const activeRows = activeTable.querySelectorAll('tbody tr:not(.muted)');
                                        
                                        activeRows.forEach(row => {
                                            if (row !== toggle.closest('tr')) {
                                                const inactiveTbody = inactiveTable.querySelector('tbody');
                                                inactiveTbody.appendChild(row);
                                                
                                                // Update the moved row's toggle and status text
                                                const movedToggle = row.querySelector('.status-toggle');
                                                const movedStatusText = row.querySelector('.status-text');
                                                if (movedToggle) movedToggle.checked = false;
                                                if (movedStatusText) {
                                                    movedStatusText.textContent = 'Other Academic Year';
                                                    movedStatusText.className = 'status-text status-unavailable';
                                                }
                                            }
                                        });
                                    }
                                    
                                    // Move current row to appropriate table
                                    const row = toggle.closest('tr');
                                    const currentTable = row.closest('table');
                                    let targetTable;
                                    
                                    if (newStatus === 'active') {
                                        targetTable = document.getElementById('activeAcademicYears');
                                    } else if (newStatus === 'future') {
                                        targetTable = document.getElementById('futureAcademicYears');
                                    } else {
                                        targetTable = document.getElementById('inactiveAcademicYears');
                                    }
                                    
                                    if (currentTable && targetTable) {
                                        const targetTbody = targetTable.querySelector('tbody');
                                        const currentTbody = currentTable.querySelector('tbody');
                                        
                                        // Remove placeholder if exists
                                        const placeholder = targetTbody.querySelector('.muted');
                                        if (placeholder) {
                                            placeholder.closest('tr').remove();
                                        }
                                        
                                        // Remove row from current table
                                        row.remove();
                                        
                                        // Add row to target table
                                        targetTbody.appendChild(row);
                                        
                                        // Add placeholder to source table if empty
                                        if (currentTbody.querySelectorAll('tr').length === 0) {
                                            const placeholderRow = document.createElement('tr');
                                            let placeholderText = '';
                                            if (currentTable.id === 'activeAcademicYears') {
                                                placeholderText = 'No present academic years found';
                                            } else {
                                                placeholderText = 'No other academic years found';
                                            }
                                            placeholderRow.innerHTML = `<td colspan="4" class="muted">${placeholderText}</td>`;
                                            currentTbody.appendChild(placeholderRow);
                                        }
                                    }
                                    
                                    showSuccessModal('Success', 'Academic year status updated successfully!');
                                } else {
                                    // Revert toggle if failed - need to determine original value
                                    const row = toggle.closest('tr');
                                    const currentTable = row.closest('table');
                                    if (currentTable.id === 'activeAcademicYears') {
                                        toggle.checked = true;
                                    } else {
                                        toggle.checked = false;
                                    }
                                    showErrorModal('Error', data.message || 'Failed to update status');
                                }
                            } catch (err) {
                                // Revert toggle if failed - need to determine original value
                                const row = toggle.closest('tr');
                                const currentTable = row.closest('table');
                                if (currentTable.id === 'activeAcademicYears') {
                                    toggle.checked = true;
                                } else {
                                    toggle.checked = false;
                                }
                                showErrorModal('Error', err.message || 'Error updating status');
                            }
                        } else {
                            // Revert toggle if cancelled - need to determine original value
                            const row = toggle.closest('tr');
                            const currentTable = row.closest('table');
                            if (currentTable.id === 'activeAcademicYears') {
                                toggle.checked = true;
                            } else {
                                toggle.checked = false;
                            }
                        }
                    }, 'change');
                }
            });

            // Backup click event for toggle switches
            document.addEventListener('click', async (e) => {
                if (e.target.closest('.switch')) {
                    const switchElement = e.target.closest('.switch');
                    const toggle = switchElement.querySelector('.status-toggle');
                    if (toggle && !toggle.disabled) {
                        isHandlingToggle = true;
                        const yearId = toggle.dataset.yearId;
                        const newStatus = !toggle.checked ? 'active' : 'inactive';
                        
                        // Check if trying to activate when there's already an active academic year
                        if (newStatus === 'active') {
                            const activeCheck = checkForActiveAcademicYear(yearId);
                            if (activeCheck.hasActive) {
                                e.preventDefault(); // Prevent default checkbox behavior only for errors
                                e.stopPropagation(); // Stop event bubbling only for errors
                                showErrorModal('Cannot Activate Academic Year', `Cannot activate this academic year. There is already an active academic year: ${activeCheck.activeYear}. Please change the status of the current active academic year to 'Other Academic Year' first before activating this one.`);
                                isHandlingToggle = false;
                                return; // Don't trigger the change event
                            }
                        }
                        
                        // Only proceed if no error occurred
                        toggle.checked = !toggle.checked;
                        toggle.dispatchEvent(new Event('change'));
                        isHandlingToggle = false;
                    }
                }
            });

            // Delete functionality
            async function handleDeleteAcademicYear(deleteBtn) {
                const yearId = deleteBtn.dataset.yearId;
                const academicYear = deleteBtn.dataset.academicYear;
                
                showConfirmModal(`Are you sure you want to delete academic year "${academicYear}"? This action cannot be undone.`, async (confirmed) => {
                    if (confirmed) {
                        try {
                            const formData = new FormData();
                            formData.append('action', 'delete_academic_year');
                            formData.append('id', yearId);
                            
                            const response = await fetch('academic-year-dashboard.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                showSuccessModal('Success', 'Academic year deleted successfully!');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                showErrorModal('Error', data.message || 'Failed to delete academic year');
                            }
                        } catch (err) {
                            showErrorModal('Error', err.message || 'Error deleting academic year');
                        }
                    }
                }, 'delete');
            }

            // Edit functionality
            function handleEditAcademicYear(editBtn) {
                const yearId = editBtn.dataset.yearId;
                const academicYear = editBtn.dataset.academicYear;
                const status = editBtn.dataset.status;
                const isLocked = editBtn.dataset.isLocked || '0';
                
                document.getElementById('edit_academic_year_id').value = yearId;
                document.getElementById('edit_academic_year').value = academicYear;
                document.getElementById('edit_status').value = status;
                document.getElementById('edit_is_locked').value = isLocked;
                
                document.getElementById('editAcademicYearModal').style.display = 'flex';
                
                // Add validation event listeners when edit modal opens
                setTimeout(() => {
                    const editAcademicYearInput = document.getElementById('edit_academic_year');
                    if (editAcademicYearInput) {
                        editAcademicYearInput.addEventListener('input', validateEditAcademicYearInput);
                        editAcademicYearInput.addEventListener('keypress', preventInvalidCharacters);
                        editAcademicYearInput.addEventListener('paste', preventInvalidPaste);
                    }
                }, 100);
            }

            // Event delegation for edit and delete buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-academic-year')) {
                    const editBtn = e.target.closest('.edit-academic-year');
                    // Check if button is disabled (locked academic year)
                    if (editBtn.disabled || editBtn.classList.contains('disabled')) {
                        showErrorModal('Error', 'Cannot edit academic year: It is currently locked');
                        return;
                    }
                    handleEditAcademicYear(editBtn);
                } else if (e.target.closest('.delete-academic-year')) {
                    const deleteBtn = e.target.closest('.delete-academic-year');
                    // Check if button is disabled (locked academic year)
                    if (deleteBtn.disabled || deleteBtn.classList.contains('disabled')) {
                        showErrorModal('Error', 'Cannot delete academic year: It is currently locked');
                        return;
                    }
                    handleDeleteAcademicYear(deleteBtn);
                }
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
