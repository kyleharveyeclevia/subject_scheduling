<?php
session_start();

// Start output buffering to prevent any output before JSON response
ob_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Include database configuration
require_once '../../config/database.php';

// Initialize database connection
$database = new Database();

// Test database connection
try {
    $database->query("SELECT 1");
    $database->execute();
    error_log("Database connection successful");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

// Fetch academic years from database
$academic_years = [];
try {
    $database->query("SELECT academic_year, status FROM academic_years ORDER BY academic_year DESC");
    $database->execute();
    $academic_years = $database->resultset();
} catch (Exception $e) {
    error_log("Failed to fetch academic years: " . $e->getMessage());
    // Fallback to default academic years
    $academic_years = [
        ['academic_year' => '2024-2025', 'status' => 'active'],
        ['academic_year' => '2025-2026', 'status' => 'inactive'],
        ['academic_year' => '2026-2027', 'status' => 'inactive']
    ];
}

// Year levels for breadcrumb navigation
$year_levels = [
    '1st Year' => 'First Year',
    '2nd Year' => 'Second Year', 
    '3rd Year' => 'Third Year',
    '4th Year' => 'Fourth Year'
];

// Helper function to format semester names
function formatSemesterName($semester) {
    switch($semester) {
        case 'first':
            return 'First Semester';
        case 'second':
            return 'Second Semester';
        case 'summer':
            return 'Mid Year';
        default:
            return ucfirst($semester);
    }
}

// Use default academic year (no filtering by academic year)
$current_academic_year = '2024-2025'; // Default academic year

$current_year_level = isset($_GET['year_level']) && in_array($_GET['year_level'], array_keys($year_levels)) ? $_GET['year_level'] : '1st Year';

// Get current semester from URL parameter, default to 'first'
$current_semester = isset($_GET['semester']) && in_array($_GET['semester'], ['first', 'second', 'summer']) ? $_GET['semester'] : 'first';

// Validate semester based on year level - redirect if summer is selected for 1st, 2nd, or 4th year
if (($current_year_level === '1st Year' || $current_year_level === '2nd Year' || $current_year_level === '4th Year') && $current_semester === 'summer') {
    header('Location: ?year_level=' . urlencode($current_year_level) . '&semester=first');
    exit();
}



// Fetch subjects from database
try {
    // Get all available sections for the current year level
    $database->query("SELECT section_id, section_name FROM sections WHERE year_level = :year_level AND status = 'available' ORDER BY section_name");
    $database->bind(':year_level', $current_year_level);
    $database->execute();
    $availableSections = $database->resultset();
    
    // Initialize arrays to store subjects by section
    $activeSubjectsBySection = [];
    $inactiveSubjectsBySection = [];
    
    // Fetch active subjects for current year level and semester, grouped by section
    foreach ($availableSections as $section) {
        $database->query("SELECT s.subject_id as id, s.subject_code, s.subject_name, s.units, s.year_level, s.semester, s.academic_year,
                                 CASE WHEN s.status = 'available' THEN 'active' ELSE 'inactive' END as status,
                                 sec.section_name
                          FROM subjects s
                          LEFT JOIN sections sec ON s.section_id = sec.section_id
                          WHERE s.status = 'available' AND s.year_level = :year_level AND s.semester = :semester AND s.section_id = :section_id
                          ORDER BY s.subject_code");
        $database->bind(':year_level', $current_year_level);
        $database->bind(':semester', $current_semester);
        $database->bind(':section_id', $section['section_id']);
        $activeSubjectsBySection[$section['section_name']] = $database->resultset();
        
        // Fetch inactive subjects for current year level and semester, grouped by section
        $database->query("SELECT s.subject_id as id, s.subject_code, s.subject_name, s.units, s.year_level, s.semester, s.academic_year,
                                 CASE WHEN s.status = 'unavailable' THEN 'inactive' ELSE 'active' END as status,
                                 sec.section_name
                          FROM subjects s
                          LEFT JOIN sections sec ON s.section_id = sec.section_id
                          WHERE s.status = 'unavailable' AND s.year_level = :year_level AND s.semester = :semester AND s.section_id = :section_id
                          ORDER BY s.subject_code");
        $database->bind(':year_level', $current_year_level);
        $database->bind(':semester', $current_semester);
        $database->bind(':section_id', $section['section_id']);
        $inactiveSubjectsBySection[$section['section_name']] = $database->resultset();
    }
    
    // Debug logging
    error_log("Current year level: " . $current_year_level);
    error_log("Current semester: " . $current_semester);
    error_log("Current academic year: " . $current_academic_year);
    error_log("Available sections: " . count($availableSections));
    
    // Check total subjects count for debugging
    try {
        $database->query("SELECT COUNT(*) as total FROM subjects");
        $database->execute();
        $totalCount = $database->single();
        error_log("Total subjects in database: " . $totalCount['total']);
    } catch (Exception $e) {
        error_log("Error counting total subjects: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Fallback to empty arrays if database error
    $availableSections = [];
    $activeSubjectsBySection = [];
    $inactiveSubjectsBySection = [];
    error_log("Database error: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Debug: Log the action being processed
    error_log("Processing action: " . $action);
    
    // Only process AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        try {
            if ($action === 'add_subject') {
                // Add new course
                $subject_code = isset($_POST['subject_code']) ? trim($_POST['subject_code']) : '';
                $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';
                $units = isset($_POST['units']) ? (int)$_POST['units'] : 0;
                $year_level = isset($_POST['year_level']) ? $_POST['year_level'] : '';
                $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
                $section_id = isset($_POST['section']) ? (int)$_POST['section'] : 0;
                $academic_year = $current_academic_year; // Use default academic year
                $status = isset($_POST['status']) ? ($_POST['status'] === 'active' ? 'available' : 'unavailable') : 'available';
                
                // Validate required fields
                if (empty($subject_code) || empty($subject_name) || empty($units) || empty($year_level) || empty($semester) || empty($section_id) || empty($academic_year)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit();
                }
                
                // Validate semester based on year level - prevent Mid Year for 1st, 2nd, and 4th year
                if (($year_level === '1st Year' || $year_level === '2nd Year' || $year_level === '4th Year') && $semester === 'summer') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Mid Year semester is not available for 1st Year, 2nd Year, and 4th Year']);
                    exit();
                }
                
                // Get all active sections for this year level
                $database->query("SELECT section_id, section_name FROM sections WHERE status = 'available' AND year_level = ? ORDER BY section_name ASC");
                $database->bind(1, $year_level);
                $database->execute();
                $sections = $database->resultset();
                
                if (empty($sections)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'No active sections found for this year level']);
                    exit();
                }
                
                // Check if subject code already exists for this year level and semester across all sections
                $database->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = ? AND year_level = ? AND semester = ?");
                $database->bind(1, $subject_code);
                $database->bind(2, $year_level);
                $database->bind(3, $semester);
                $result = $database->single();
                
                // Allow maximum of 4 instances per section, so total max = sections count * 4
                $max_allowed = count($sections) * 4;
                if ($result['count'] >= $max_allowed) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Course code already exists {$result['count']} times. Maximum allowed is {$max_allowed} (4 per section)."]);
                    exit();
                }
                
                // Start transaction for multiple insertions
                $database->beginTransaction();
                
                try {
                    $success_count = 0;
                    $total_sections = count($sections);
                    
                    // Insert the course for each section
                    foreach ($sections as $section) {
                        // Check if this specific course already exists for this section
                        $database->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = ? AND year_level = ? AND semester = ? AND section_id = ?");
                        $database->bind(1, $subject_code);
                        $database->bind(2, $year_level);
                        $database->bind(3, $semester);
                        $database->bind(4, $section['section_id']);
                        $existing = $database->single();
                        
                        // Skip if this course already exists for this section (max 4 per section)
                        if ($existing['count'] >= 4) {
                            continue;
                        }
                        
                        // Insert the course for this section
                        $database->query("INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, section_id, status, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $database->bind(1, $subject_code);
                        $database->bind(2, $subject_name);
                        $database->bind(3, $units);
                        $database->bind(4, $year_level);
                        $database->bind(5, $semester);
                        $database->bind(6, $section['section_id']);
                        $database->bind(7, $status);
                        $database->bind(8, $academic_year);
                        
                        if ($database->execute()) {
                            $success_count++;
                        }
                    }
                    
                    // Commit transaction
                    $database->commit();
                    
                    if ($success_count > 0) {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true, 
                            'message' => "Course added successfully to {$success_count} section(s) out of {$total_sections} total sections"
                        ]);
                        exit();
                    } else {
                        ob_clean();
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Course already exists in all sections for this year level and semester']);
                        exit();
                    }
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $database->rollback();
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to add course: ' . $e->getMessage()]);
                    exit();
                }
                
            } elseif ($action === 'edit_subject') {
                // Edit existing subject
                $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
                $subject_code = isset($_POST['subject_code']) ? trim($_POST['subject_code']) : '';
                $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';
                $units = isset($_POST['units']) ? (int)$_POST['units'] : 0;
                $year_level = isset($_POST['year_level']) ? $_POST['year_level'] : '';
                $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
                $section_id = isset($_POST['section']) ? (int)$_POST['section'] : 0;
                $academic_year = $current_academic_year; // Use default academic year
                $status = isset($_POST['status']) ? ($_POST['status'] === 'active' ? 'available' : 'unavailable') : 'available';
                
                // Validate required fields
                if (empty($subject_id) || empty($subject_code) || empty($subject_name) || empty($units) || empty($year_level) || empty($semester) || empty($section_id) || empty($academic_year)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit();
                }
                
                // Validate semester based on year level - prevent Mid Year for 1st, 2nd, and 4th year
                if (($year_level === '1st Year' || $year_level === '2nd Year' || $year_level === '4th Year') && $semester === 'summer') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Mid Year semester is not available for 1st Year, 2nd Year, and 4th Year']);
                    exit();
                }
                
                // Check if subject code already exists for other subjects (allow up to 4 occurrences)
                $database->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = ? AND subject_id != ?");
                $database->bind(1, $subject_code);
                $database->bind(2, $subject_id);
                $result = $database->single();
                
                if ($result['count'] >= 4) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Course code already exists 4 times in the table. You cannot have more than 4 same course codes.']);
                    exit();
                }
                
                // Update subject
                $database->query("UPDATE subjects SET subject_code = ?, subject_name = ?, units = ?, year_level = ?, semester = ?, section_id = ?, academic_year = ?, status = ? WHERE subject_id = ?");
                $database->bind(1, $subject_code);
                $database->bind(2, $subject_name);
                $database->bind(3, $units);
                $database->bind(4, $year_level);
                $database->bind(5, $semester);
                $database->bind(6, $section_id);
                $database->bind(7, $academic_year);
                $database->bind(8, $status);
                $database->bind(9, $subject_id);
                
                if ($database->execute()) {
                    // Clear any output buffer before sending JSON
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
                    exit();
                } else {
                    // Clear any output buffer before sending JSON
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to update subject']);
                    exit();
                }
            } elseif ($action === 'toggle_status') {
                // Toggle course status
                $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
                $status = isset($_POST['status']) ? $_POST['status'] : '';
                
                if (empty($subject_id) || !in_array($status, ['available', 'unavailable'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                    exit();
                }
                
                // Update course status
                $database->query("UPDATE subjects SET status = ? WHERE subject_id = ?");
                $database->bind(1, $status);
                $database->bind(2, $subject_id);
                
                if ($database->execute()) {
                    // Return JSON response for AJAX request
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                    exit();
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                    exit();
                }
            } elseif ($action === 'delete_subject') {
                // Delete subject
                $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
                
                if (empty($subject_id)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                    exit();
                }
                
                // Delete subject from database
                $database->query("DELETE FROM subjects WHERE subject_id = ?");
                $database->bind(1, $subject_id);
                
                if ($database->execute()) {
                    // Return JSON response for AJAX request
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
                    exit();
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to delete subject']);
                    exit();
                }
            }
        } catch (Exception $e) {
            // Log error for debugging
            error_log('Subject dashboard error: ' . $e->getMessage());
            
            // Clear any output buffer before sending JSON
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit();
        }
    } else {
        // Non-AJAX POST request - redirect to prevent JSON display
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            header('Location: Courses-dashboard.php');
            exit();
        }
    }
}

// Check for success messages from redirects
$success_message = $_GET['success'] ?? '';
if ($success_message === 'added') {
                $success_message = 'Course added successfully';
} elseif ($success_message === 'updated') {
    $success_message = 'Course updated successfully';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Courses Dashboard - Subject Scheduling System</title>
    <link rel="stylesheet" href="../../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Section Group Styling */
        .section-group {
            margin-bottom: 3rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(248, 250, 252, 0.1) 100%);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .section-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(99, 102, 241, 0.2);
        }

        .section-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header h2 i {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .no-sections-message {
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(248, 250, 252, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            color: #6b7280;
        }

        .no-sections-message p {
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .no-sections-message i {
            color: #6366f1;
            font-size: 1.25rem;
        }

        .no-sections-message .btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .no-sections-message .btn:hover {
            background: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        /* Table styling to ensure proper column display */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        
        .table-container {
            margin-top: 1.5rem;
        }
        
        /* Action buttons styling */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
        }
        
        /* Status column styling */
        .status-column {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Year Level Cards Styling (matching enhanced breadcrumb design) */
        .year-level-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 0.125rem 0 0.25rem 0;
        }
        
        .year-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 999px;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .year-card:hover {
            background: #eef2ff;
            color: #3730a3;
            border-color: #c7d2fe;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .year-card.active {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .year-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .year-card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .year-card-content h3 {
            margin: 0;
            color: inherit;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .year-card-content p {
            margin: 0;
            color: inherit;
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Responsive adjustments for year cards */
        @media (max-width: 768px) {
            .year-level-cards {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .year-card {
                padding: 0.75rem 1rem;
            }
            
            .year-card-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .year-card-content h3 {
                font-size: 0.9rem;
            }
            
            .year-card-content p {
                font-size: 0.8rem;
            }
        }
        
        /* Dashboard Navigation Cards Styling */
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
            background: rgba(238, 242, 255, 0.2) !important;
            color: #3730a3 !important;
            border-color: rgba(199, 210, 254, 0.4) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Force hover effect with higher specificity */
        .dashboard-navigation-cards .dashboard-card-nav:hover {
            background: rgba(238, 242, 255, 0.2) !important;
            color: #3730a3 !important;
            border-color: rgba(199, 210, 254, 0.4) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Test hover effect - more visible to ensure it's working */
        .dashboard-card-nav:hover {
            background: rgba(99, 102, 241, 0.1) !important;
            border-color: #6366f1 !important;
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
            
            .dashboard-card-nav .dashboard-card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }
            
            .dashboard-card-nav .dashboard-card-content h3 {
                font-size: 1rem;
            }
            
            .dashboard-card-nav .dashboard-card-content p {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-navigation-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        /* Table styling */
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
        
        /* Available/Unavailable table color themes */
        #activeSubjects { 
            border: 1px solid #16a34a22; 
            border-left: 4px solid #16a34a; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-bottom: 24px; 
            box-shadow: 0 1px 6px rgba(22,163,74,0.12); 
        }
        #activeSubjects thead { 
            background: #16a34a; 
            color: #fff; 
        }
        
        /* Apply green header to active tables and general tables */
        .table thead th {
            background: #16a34a !important;
            color: #fff !important;
        }

        /* Override with red header for inactive tables */
        .table[id*="inactiveSubjects"] thead th {
            background: #dc2626 !important;
            color: #fff !important;
        }
        #activeSubjects tbody tr:nth-child(even) { 
            background: #16a34a0a; 
        }
        #activeSubjects tbody tr:hover { 
            background: #16a34a14; 
        }
        #activeSubjects td, #activeSubjects th { 
            border-color: #16a34a22; 
        }
        


        /* Styling for inactive subjects tables (both old and new section-based) */
        #inactiveSubjects, 
        .table[id*="inactiveSubjects"] { 
            border: 1px solid #dc262622; 
            border-left: 4px solid #dc2626; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-top: 24px; 
            box-shadow: 0 1px 6px rgba(220,38,38,0.12); 
        }
        
        #inactiveSubjects thead th, 
        .table[id*="inactiveSubjects"] thead th { 
            background: #dc2626 !important; 
            color: #fff !important; 
        }
        
        #inactiveSubjects tbody tr:nth-child(even), 
        .table[id*="inactiveSubjects"] tbody tr:nth-child(even) { 
            background: #dc26260a; 
        }
        
        #inactiveSubjects tbody tr:hover, 
        .table[id*="inactiveSubjects"] tbody tr:hover { 
            background: #dc262614; 
        }
        
        #inactiveSubjects td, #inactiveSubjects th, 
        .table[id*="inactiveSubjects"] td, .table[id*="inactiveSubjects"] th { 
            border-color: #dc262622; 
        }
        
        /* Center Course Title header and data in Courses Dashboard */
        #activeSubjects th:nth-child(2),
        #activeSubjects td:nth-child(2) {
            text-align: center; /* Center Course Title header and data exactly in middle */
        }
        


        /* Status indicator colors */
        .status-active { 
            color: #16a34a; 
            background: #16a34a22; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.875rem;
            font-weight: 500;
            margin-left: 8px;
        }
        .status-inactive { 
            color: #dc2626; 
            background: #dc262622; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.875rem;
            font-weight: 500;
            margin-left: 8px;
        }

        /* Toggle switch */
        .switch { 
            position: relative; 
            display: inline-block; 
            width: 50px; 
            height: 26px; 
            vertical-align: middle;
        }
        .switch input { 
            opacity: 0; 
            width: 0; 
            height: 0; 
        }
        .slider { 
            position: absolute; 
            cursor: pointer; 
            inset: 0; 
            background: #e5e7eb; 
            transition: .2s; 
            border-radius: 999px; 
            border: 1px solid #d1d5db;
        }
        .slider:before { 
            position: absolute; 
            content: ""; 
            height: 20px; 
            width: 20px; 
            left: 2px; 
            top: 2px; 
            background: white; 
            transition: .2s; 
            border-radius: 999px; 
            box-shadow: 0 2px 4px rgba(0,0,0,.2); 
        }
        input:checked + .slider { 
            background: #22c55e; 
            border-color: #16a34a;
        }
        input:checked + .slider:before { 
            transform: translateX(24px); 
        }
        
        /* Hover effects for toggle switch */
        .switch:hover .slider {
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        }
        
        .switch:hover input:checked + .slider {
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.3);
        }
        
        /* Status column styling */
        .status-column {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Center the Semester column header */
        .table th:nth-child(5) {
            text-align: center;
        }
        
        /* Center the Semester column content */
        .table td:nth-child(5) {
            text-align: center;
        }
        
        /* Center the Status column header */
        .table th:nth-child(6) {
            text-align: center;
        }
        
        /* Center the Status column content */
        .table td:nth-child(6) {
            text-align: center;
        }
        
        /* Center the Actions column header */
        .table th:nth-child(7) {
            text-align: center;
        }
        
        /* Center the Actions column content */
        .table td:nth-child(7) {
            text-align: center;
        }

        /* Table styles */
        .table-container { 
            width: 100%; 
            overflow-x: auto; 
        }
        .table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            table-layout: auto; 
        }
        .table thead th { 
            position: sticky; 
            top: 0; 
            background: #16a34a; 
            color: #fff; 
            font-weight: 600; 
            text-align: left; 
            padding: 1rem 1.2rem; 
            border-bottom: 1px solid rgba(229,231,235,.9); 
        }
        .table tbody td { 
            padding: 0.9rem 1.2rem; 
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
        .btn.btn-sm { 
            padding: .35rem .55rem; 
            font-size: .85rem; 
            border-radius: 8px; 
        }
        .btn.btn-secondary { 
            background: #eef2ff; 
            color: #3730a3; 
            border: 1px solid #c7d2fe; 
        }
        .btn.btn-danger { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
        .btn.btn-secondary:hover { 
            background: #e0e7ff; 
        }
        .btn.btn-danger:hover { 
            background: #fecaca; 
        }

        /* Management controls */
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

        /* Column width adjustments */
        #activeSubjects th:nth-child(1), #activeSubjects td:nth-child(1),
        #inactiveSubjects th:nth-child(1), #inactiveSubjects td:nth-child(1) {
            width: 15%; /* Course Code */
        }
        
        #activeSubjects th:nth-child(2), #activeSubjects td:nth-child(2),
        #inactiveSubjects th:nth-child(2), #inactiveSubjects td:nth-child(2) {
            width: 25%; /* Course Title */
        }
        
        #activeSubjects th:nth-child(3), #activeSubjects td:nth-child(3),
        #inactiveSubjects th:nth-child(3), #inactiveSubjects td:nth-child(3) {
            width: 10%; /* Units */
            text-align: center;
        }

        #activeSubjects th:nth-child(4), #activeSubjects td:nth-child(4),
        #inactiveSubjects th:nth-child(4), #inactiveSubjects td:nth-child(4) {
            width: 15%; /* Year Level */
            text-align: center;
        }

        #activeSubjects th:nth-child(5), #activeSubjects td:nth-child(5),
        #inactiveSubjects th:nth-child(5), #inactiveSubjects td:nth-child(5) {
            width: 20%; /* Status - toggle switch and label */
        }

        #activeSubjects th:nth-child(6), #activeSubjects td:nth-child(6),
        #inactiveSubjects th:nth-child(6), #inactiveSubjects td:nth-child(6) {
            width: 15%; /* Actions - Edit and Delete buttons */
        }

        /* Table cell padding adjustments for better spacing */
        .table thead th {
            padding: 1rem 1.2rem;
        }
        
        .table tbody td {
            padding: 0.9rem 1.2rem;
        }

        /* Status column specific styling */
        .table td:nth-child(4) {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        /* Actions column specific styling */
        .table td:nth-child(5) {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        /* Course Title column left alignment with text wrapping */
        .table td:nth-child(2) {
            text-align: left;
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
            max-width: 300px;
            min-width: 200px;
            line-height: 1.4;
            vertical-align: middle;
        }
        
        /* Prevent text wrapping for other columns */
        .table td:nth-child(1), /* Course Code */
        .table td:nth-child(3), /* Units */
        .table td:nth-child(4), /* Year Level */
        .table td:nth-child(5), /* Semester */
        .table td:nth-child(6), /* Section */
        .table td:nth-child(7), /* Status */
        .table td:nth-child(8) { /* Actions */
            white-space: nowrap;
        }
        
        /* Units column center alignment */
        .table td:nth-child(3) {
            text-align: center;
            font-weight: 500;
        }

        /* Action buttons side by side layout */
        .action-buttons {
            display: flex !important;
            flex-direction: row !important;
            gap: 0.5rem !important;
            justify-content: center !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
        }

        /* Button sizing for side by side layout */
        .btn.btn-sm {
            padding: 0.4rem 0.6rem !important;
            font-size: 0.8rem !important;
            white-space: nowrap !important;
            min-width: auto !important;
            display: inline-block !important;
            float: none !important;
        }

        /* Ensure buttons don't stack */
        .action-buttons .btn {
            display: inline-block !important;
            float: none !important;
            margin: 0 !important;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
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

        .field-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        /* Animation for modal */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-60%) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) scale(1);
            }
        }
        .success-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3200;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .error-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3200;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .success-content, .error-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .success-icon {
            width: 60px;
            height: 60px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-icon {
            width: 60px;
            height: 60px;
            background: #ef4444;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-icon i {
            color: white;
            font-size: 24px;
        }
        
        .error-icon i {
            color: white;
            font-size: 24px;
        }
        
        .success-content h3, .error-content h3 {
            margin: 0 0 0.5rem 0;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .success-content p, .error-content p {
            margin: 0 0 1.5rem 0;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .btn {
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background: #10b981;
            color: white;
        }
        
        .btn-primary:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }





        /* Enhanced form input styling */
        .form-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .form-input:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }



        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced dropdown container with subtle background pattern */
        .academic-year-dropdown {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 250, 252, 0.8) 100%);
            border-radius: 12px;
            padding: 0.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .academic-year-dropdown::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(99, 102, 241, 0.03) 50%, transparent 70%);
            border-radius: 12px;
            pointer-events: none;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        /* Improved dropdown arrow animation */
        #academicYearSelect {
            position: relative;
            z-index: 1;
        }

        #academicYearSelect:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
        }

        /* Enhanced option styling with better contrast */
        #academicYearSelect option {
            position: relative;
            background: #ffffff;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        #academicYearSelect option:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-color: #cbd5e1;
            transform: translateX(4px);
        }

        /* Status indicator for academic years */
        .academic-year-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 0.5rem;
        }

        .academic-year-status.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .academic-year-status.inactive {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }

        /* Responsive improvements */
        @media (max-width: 640px) {
            .academic-year-header {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .academic-year-header h2 {
                font-size: 1.125rem;
                margin-bottom: 0.875rem;
            }

            #academicYearSelect {
                max-width: 100%;
                font-size: 0.9rem;
                padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            }

            .academic-year-dropdown::before {
                font-size: 1rem;
                left: 0.75rem;
            }

            .academic-year-dropdown {
                padding: 0.25rem;
            }
        }

        /* Print styles */
        @media print {
            .academic-year-header {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                box-shadow: none !important;
            }

            #academicYearSelect {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                color: #000000 !important;
                box-shadow: none !important;
            }
        }

        /* Enhanced Year Level Dropdown Styling */
        .year-level-header {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1), 0 2px 4px -1px rgba(245, 158, 11, 0.06);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.1s both;
        }

        .year-level-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #f97316, #ea580c);
            border-radius: 16px 16px 0 0;
        }

        .year-level-header h2 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0 1.25rem 0;
            color: #92400e;
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .year-level-header h2 i {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.5rem;
        }

        .year-level-dropdown {
            position: relative;
        }

        .year-level-dropdown::before {
            content: '🎓';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            z-index: 2;
            pointer-events: none;
        }

        #yearLevelSelect {
            width: 100%;
            max-width: 320px;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #fef3c7 100%);
            color: #92400e;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(245, 158, 11, 0.1), 0 1px 2px 0 rgba(245, 158, 11, 0.06);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23f59e0b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            background-color: #ffffff;
        }

        #yearLevelSelect:hover {
            border-color: #f59e0b;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.15), 0 2px 4px -1px rgba(245, 158, 11, 0.1);
            transform: translateY(-1px);
        }

        #yearLevelSelect:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1), 0 4px 6px -1px rgba(245, 158, 11, 0.15), 0 2px 4px -1px rgba(245, 158, 11, 0.1);
            transform: translateY(-1px);
        }

        #yearLevelSelect:active {
            transform: translateY(0);
        }

        #yearLevelSelect option {
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #92400e;
            border-left: 3px solid transparent;
        }

        #yearLevelSelect option:hover {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left-color: #f59e0b;
            transform: translateX(4px);
        }

        #yearLevelSelect option:checked {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left-color: #ea580c;
            font-weight: 700;
            color: #78350f;
        }

        /* Enhanced dropdown container with subtle background pattern */
        .year-level-dropdown {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(254, 243, 199, 0.8) 100%);
            border-radius: 12px;
            padding: 0.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(251, 191, 36, 0.5);
        }

        .year-level-dropdown::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(245, 158, 11, 0.03) 50%, transparent 70%);
            border-radius: 12px;
            pointer-events: none;
            animation: shimmer 3s ease-in-out infinite;
        }

        /* Year level specific animations */
        .year-level-header {
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.1s both;
        }

        /* Responsive design for year level dropdown */
        @media (max-width: 768px) {
            .year-level-header {
                padding: 1.25rem;
                margin-bottom: 1.5rem;
            }

            .year-level-header h2 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            #yearLevelSelect {
                max-width: 100%;
                font-size: 0.95rem;
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }

            .year-level-dropdown::before {
                font-size: 1.125rem;
                left: 0.875rem;
            }
        }

        @media (max-width: 640px) {
            .year-level-header {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .year-level-header h2 {
                font-size: 1.125rem;
                margin-bottom: 0.875rem;
            }

            #yearLevelSelect {
                max-width: 100%;
                font-size: 0.9rem;
                padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            }

            .year-level-dropdown::before {
                font-size: 1rem;
                left: 0.75rem;
            }

            .year-level-dropdown {
                padding: 0.25rem;
            }
        }

        /* Enhanced focus states for accessibility */
        #yearLevelSelect:focus-visible {
            outline: 2px solid #ea580c;
            outline-offset: 2px;
        }

        /* Dark mode support for year level */
        @media (prefers-color-scheme: dark) {
            .year-level-header {
                background: linear-gradient(135deg, #451a03 0%, #78350f 100%);
                border-color: #f59e0b;
            }

            .year-level-header h2 {
                color: #fde68a;
            }

            #yearLevelSelect {
                background: linear-gradient(135deg, #451a03 0%, #78350f 100%);
                color: #fde68a;
                border-color: #f59e0b;
            }

            #yearLevelSelect:hover {
                border-color: #fbbf24;
            }

            #yearLevelSelect:focus {
                border-color: #f97316;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1), 0 4px 6px -1px rgba(245, 158, 11, 0.15), 0 2px 4px -1px rgba(245, 158, 11, 0.1);
            }

            #yearLevelSelect option {
                background: #451a03;
                color: #fde68a;
            }

            #yearLevelSelect option:hover {
                background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
            }

            #yearLevelSelect option:checked {
                background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
                color: #fef3c7;
            }
        }

        /* Print styles for year level */
        @media print {
            .year-level-header {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                box-shadow: none !important;
            }

            #yearLevelSelect {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                color: #000000 !important;
                box-shadow: none !important;
            }
        }

        /* Additional Year Level Dropdown Enhancements */
        .year-level-header {
            position: relative;
            overflow: hidden;
        }

        .year-level-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Enhanced option styling with icons */
        #yearLevelSelect option {
            position: relative;
            padding-left: 2.5rem;
        }

        #yearLevelSelect option::before {
            content: '🎓';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.875rem;
        }

        /* Year level specific option styling */
        #yearLevelSelect option[value="1st Year"]::before { content: '🥇'; }
        #yearLevelSelect option[value="2nd Year"]::before { content: '🥈'; }
        #yearLevelSelect option[value="3rd Year"]::before { content: '🥉'; }
        #yearLevelSelect option[value="4th Year"]::before { content: '🏆'; }

        /* Enhanced hover effects for the entire header */
        .year-level-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(245, 158, 11, 0.2), 0 4px 10px -2px rgba(245, 158, 11, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Focus ring enhancement */
        #yearLevelSelect:focus {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1), 0 4px 6px -1px rgba(245, 158, 11, 0.15), 0 2px 4px -1px rgba(245, 158, 11, 0.1); }
            50% { box-shadow: 0 0 0 6px rgba(234, 88, 12, 0.15), 0 4px 6px -1px rgba(245, 158, 11, 0.15), 0 2px 4px -1px rgba(245, 158, 11, 0.1); }
        }

        /* Loading state for year level dropdown */
        .year-level-dropdown.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #fbbf24;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }

        /* Success state animation */
        .year-level-dropdown.success {
            animation: successPulse 0.6s ease-out;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* Enhanced mobile experience */
        @media (max-width: 480px) {
            .year-level-header {
                padding: 1rem;
                margin-bottom: 1.25rem;
            }

            .year-level-header h2 {
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }

            #yearLevelSelect {
                font-size: 0.875rem;
                padding: 0.75rem 0.75rem 0.75rem 2.25rem;
            }

            .year-level-dropdown::before {
                font-size: 0.875rem;
                left: 0.625rem;
            }

            .year-level-dropdown {
                padding: 0.25rem;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .year-level-header {
                border-width: 3px;
                border-color: #92400e;
            }

            #yearLevelSelect {
                border-width: 3px;
                border-color: #92400e;
            }

            #yearLevelSelect:focus {
                border-width: 4px;
                border-color: #ea580c;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .year-level-header,
            .year-level-header::after,
            #yearLevelSelect,
            .year-level-dropdown::after {
                animation: none;
                transition: none;
            }

            .year-level-header:hover {
                transform: none;
            }

            #yearLevelSelect:hover {
                transform: none;
            }
        }

        /* Section Select Dropdown Styling */
        .section-select-dropdown {
            background: transparent !important;
            border: none !important;
            padding: 0.5rem !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: none !important;
        }

        #sectionSelect {
            width: 100%;
            max-width: 400px;
            padding: 1.25rem 1.5rem !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            color: #374151 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
        }

        #sectionSelect:hover {
            border-color: #d1d5db !important;
            background: #fafafa !important;
            box-shadow: 0 8px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) !important;
        }

        #sectionSelect:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 8px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) !important;
        }

        #sectionSelect:active {
            transform: translateY(0) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        }

        #sectionSelect option {
            padding: 0.5rem 1rem;
            background: #ffffff;
            color: #374151;
            font-weight: 500;
        }

        #sectionSelect option:hover {
            background: #f3f4f6;
        }

        #sectionSelect option:checked {
            background: #eff6ff;
            color: #1e40af;
            font-weight: 600;
        }

        .section-filter-indicator {
            display: inline-block;
            background: #10b981;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-left: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }


        /* Enhanced Semester Dropdown Styling */
        .semester-courses-header {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid #3b82f6;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1), 0 2px 4px -1px rgba(59, 130, 246, 0.06);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s both;
        }

        .semester-courses-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
            border-radius: 16px 16px 0 0;
        }

        .semester-courses-header h2 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0 1.25rem 0;
            color: #1e40af;
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .semester-courses-header h2 i {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.5rem;
        }

        .semester-dropdown {
            position: relative;
        }

        .semester-dropdown::before {
            content: '📅';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            z-index: 2;
            pointer-events: none;
        }

        #semesterSelect {
            width: 100%;
            max-width: 320px;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #93c5fd;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #dbeafe 100%);
            color: #1e40af;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px 0 rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(59, 130, 246, 0.06);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%233b82f6' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            background-color: #ffffff;
        }

        #semesterSelect:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.15), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        #semesterSelect:focus {
            outline: none;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1), 0 4px 6px -1px rgba(59, 130, 246, 0.15), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        #semesterSelect:active {
            transform: translateY(0);
        }

        #semesterSelect option {
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #1e40af;
            border-left: 3px solid transparent;
        }

        #semesterSelect option:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left-color: #3b82f6;
            transform: translateX(4px);
        }

        #semesterSelect option:checked {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left-color: #1d4ed8;
            font-weight: 700;
            color: #1e3a8a;
        }

        /* Enhanced dropdown container with subtle background pattern */
        .semester-dropdown {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(219, 234, 254, 0.8) 100%);
            border-radius: 12px;
            padding: 0.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(147, 197, 253, 0.5);
        }

        .semester-dropdown::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(59, 130, 246, 0.03) 50%, transparent 70%);
            border-radius: 12px;
            pointer-events: none;
            animation: shimmer 3s ease-in-out infinite;
        }

        /* Semester specific animations */
        .semester-courses-header {
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s both;
        }

        /* Responsive design for semester dropdown */
        @media (max-width: 768px) {
            .semester-courses-header {
                padding: 1.25rem;
                margin-bottom: 1.5rem;
            }

            .semester-courses-header h2 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }

            #semesterSelect {
                max-width: 100%;
                font-size: 0.95rem;
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }

            .semester-dropdown::before {
                font-size: 1.125rem;
                left: 0.875rem;
            }
        }

        @media (max-width: 640px) {
            .semester-courses-header {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .semester-courses-header h2 {
                font-size: 1.125rem;
                margin-bottom: 0.875rem;
            }

            #semesterSelect {
                max-width: 100%;
                font-size: 0.9rem;
                padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            }

            .semester-dropdown::before {
                font-size: 1rem;
                left: 0.75rem;
            }

            .semester-dropdown {
                padding: 0.25rem;
            }
        }

        /* Enhanced focus states for accessibility */
        #semesterSelect:focus-visible {
            outline: 2px solid #1d4ed8;
            outline-offset: 2px;
        }

        /* Dark mode support for semester */
        @media (prefers-color-scheme: dark) {
            .semester-courses-header {
                background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                border-color: #3b82f6;
            }

            .semester-courses-header h2 {
                color: #bfdbfe;
            }

            #semesterSelect {
                background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                color: #bfdbfe;
                border-color: #3b82f6;
            }

            #semesterSelect:hover {
                border-color: #60a5fa;
            }

            #semesterSelect:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1), 0 4px 6px -1px rgba(59, 130, 246, 0.15), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
            }

            #semesterSelect option {
                background: #1e3a8a;
                color: #bfdbfe;
            }

            #semesterSelect option:hover {
                background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            }

            #semesterSelect option:checked {
                background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
                color: #dbeafe;
            }
        }

        /* Print styles for semester */
        @media print {
            .semester-courses-header {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                box-shadow: none !important;
            }

            #semesterSelect {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                color: #000000 !important;
                box-shadow: none !important;
            }
        }

        /* Additional Semester Dropdown Enhancements */
        .semester-courses-header {
            position: relative;
            overflow: hidden;
        }

        .semester-courses-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            pointer-events: none;
        }

        /* Enhanced option styling with icons */
        #semesterSelect option {
            position: relative;
            padding-left: 2.5rem;
        }

        #semesterSelect option::before {
            content: '📅';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.875rem;
        }

        /* Semester specific option styling */
        #semesterSelect option[value="first"]::before { content: '🌱'; }
        #semesterSelect option[value="second"]::before { content: '🍂'; }
        #semesterSelect option[value="summer"]::before { content: '☀️'; }

        /* Enhanced hover effects for the entire header */
        .semester-courses-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(59, 130, 246, 0.2), 0 4px 10px -2px rgba(59, 130, 246, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Focus ring enhancement */
        #semesterSelect:focus {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Loading state for semester dropdown */
        .semester-dropdown.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #93c5fd;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Success state animation */
        .semester-dropdown.success {
            animation: successPulse 0.6s ease-out;
        }

        /* Enhanced mobile experience */
        @media (max-width: 480px) {
            .semester-courses-header {
                padding: 1rem;
                margin-bottom: 1.25rem;
            }

            .semester-courses-header h2 {
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }

            #semesterSelect {
                font-size: 0.875rem;
                padding: 0.75rem 0.75rem 0.75rem 2.25rem;
            }

            .semester-dropdown::before {
                font-size: 0.875rem;
                left: 0.625rem;
            }

            .semester-dropdown {
                padding: 0.25rem;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .semester-courses-header {
                border-width: 3px;
                border-color: #1e40af;
            }

            #semesterSelect {
                border-width: 3px;
                border-color: #1e40af;
            }

            #semesterSelect:focus {
                border-width: 4px;
                border-color: #1d4ed8;
            }
        }

        /* Reduced motion support for semester */
        @media (prefers-reduced-motion: reduce) {
            .semester-courses-header,
            .semester-courses-header::after,
            #semesterSelect,
            .semester-dropdown::after {
                animation: none;
                transition: none;
            }

            .semester-courses-header:hover {
                transform: none;
            }

            #semesterSelect:hover {
                transform: none;
            }
        }

        /* Semester validation styling */
        .semester-dropdown.error {
            animation: errorShake 0.5s ease-in-out;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Semester option descriptions */
        #semesterSelect option[value="first"]::after {
            content: ' - Beginning of Academic Year';
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        #semesterSelect option[value="second"]::after {
            content: ' - Middle of Academic Year';
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        #semesterSelect option[value="summer"]::after {
            content: ' - Available for 3rd Year Only';
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        /* Enhanced semester header with seasonal themes */
        .semester-courses-header[data-semester="first"] {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #3b82f6;
        }

        .semester-courses-header[data-semester="second"] {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
        }

        .semester-courses-header[data-semester="summer"] {
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
            border-color: #f59e0b;
        }

        /* Semester transition effects */
        .semester-dropdown {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .semester-dropdown:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 25px -5px rgba(59, 130, 246, 0.15), 0 4px 10px -2px rgba(59, 130, 246, 0.1);
        }

        /* Semester option hover effects */
        #semesterSelect option:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left-color: #3b82f6;
            transform: translateX(4px);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        /* Semester selection indicator */
        .semester-dropdown::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .semester-dropdown:focus-within::after {
            width: 80%;
        }

        /* Responsive design for horizontal layout */
        @media (max-width: 1024px) {
            .dropdowns-row {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .dropdowns-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .selection-header {
                padding: 1.25rem;
                margin-bottom: 1.5rem;
            }

            .selection-header h2 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 640px) {
            .selection-header {
                padding: 1rem;
                margin-bottom: 1.25rem;
            }

            .selection-header h2 {
                font-size: 1.125rem;
                margin-bottom: 0.875rem;
            }
        }

        /* Enhanced horizontal layout styling */
        .dropdowns-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .dropdown-item {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-width: 0; /* Prevents overflow */
        }

        .dropdown-item label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }

        /* Ensure all dropdowns have consistent sizing */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            width: 100%;
            min-width: 0;
        }

        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            width: 100%;
            max-width: none;
            min-width: 0;
        }

        /* Enhanced spacing and alignment */
        .selection-header {
            padding: 1.5rem 2rem;
        }

        .dropdowns-row {
            gap: 2rem;
        }

        /* Hover effects for the entire row */
        .dropdowns-row:hover .dropdown-item {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        /* Individual dropdown item hover effects */
        .dropdown-item:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        /* Responsive breakpoints for better mobile experience */
        @media (max-width: 1200px) {
            .dropdowns-row {
                gap: 1.5rem;
            }
        }

        @media (max-width: 1024px) {
            .dropdowns-row {
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
            }
            
            .dropdown-item:last-child {
                grid-column: 1 / -1;
                max-width: 50%;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .dropdowns-row {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .dropdown-item:last-child {
                grid-column: 1;
                max-width: none;
                margin: 0;
            }
            
            .selection-header {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .selection-header {
                padding: 1.25rem;
            }
            
            .dropdowns-row {
                gap: 1.25rem;
            }
        }

        /* Enhanced visual hierarchy */
        .dropdown-item label {
            color: #1f2937;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            padding-left: 0.25rem;
        }

        /* Consistent dropdown heights */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            height: auto;
            display: flex;
            align-items: stretch;
        }

        /* Better spacing between elements */
        .dropdowns-row {
            margin-top: 0;
        }

        /* Enhanced Font Sizes and Visibility for All Dropdowns */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            font-size: 1.1rem !important;
            font-weight: 600;
            padding: 1.25rem 1rem 1.25rem 1.5rem;
            line-height: 1.4;
            color: #1e293b;
        }

        /* Larger labels for better visibility */
        .dropdown-item label {
            font-size: 1rem !important;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.75rem;
            padding-left: 0.25rem;
            letter-spacing: 0.025em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Enhanced option styling with larger fonts */
        #academicYearSelect option,
        #yearLevelSelect option,
        #semesterSelect option {
            font-size: 1rem !important;
            padding: 1rem 1.25rem;
            margin: 0.375rem 0;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Remove icons from dropdown content */
        .academic-year-dropdown::before,
        .year-level-dropdown::before,
        .semester-dropdown::before {
            display: none;
        }

        /* Enhanced dropdown container sizing */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            padding: 0.75rem;
        }

        /* Better contrast for selected options */
        #academicYearSelect option:checked,
        #yearLevelSelect option:checked,
        #semesterSelect option:checked {
            font-weight: 700;
            font-size: 1.05rem !important;
        }

        /* Enhanced focus states with larger text */
        #academicYearSelect:focus,
        #yearLevelSelect:focus,
        #semesterSelect:focus {
            font-size: 1.1rem !important;
        }

        /* Responsive font sizing */
        @media (max-width: 768px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 1rem !important;
                padding: 1.125rem 1rem 1.125rem 1.5rem;
            }

            .dropdown-item label {
                font-size: 0.95rem !important;
            }
        }

        @media (max-width: 480px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 0.95rem !important;
                padding: 1rem 1rem 1rem 1.25rem;
            }

            .dropdown-item label {
                font-size: 0.9rem !important;
            }
        }

        /* High contrast mode for better readability */
        @media (prefers-contrast: high) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-weight: 700;
                border-width: 3px;
            }

            .dropdown-item label {
                font-weight: 800;
                color: #000000;
            }
        }

        /* Enhanced option descriptions with larger fonts */
        #semesterSelect option[value="first"]::after,
        #semesterSelect option[value="second"]::after,
        #semesterSelect option[value="summer"]::after {
            font-size: 0.9rem !important;
            font-weight: 500;
        }

        /* Better spacing for option groups */
        #academicYearSelect optgroup {
            font-size: 1rem !important;
            font-weight: 800;
            padding: 0.75rem 0;
        }

        /* Enhanced dropdown arrow sizing */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            background-size: 1.5rem !important;
            background-position: right 1.25rem center;
        }

        /* Beautiful and Clean Dropdown Styling */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            /* Clean, modern appearance */
            background: #ffffff !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            color: #374151 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            padding: 1rem 1.25rem !important;
            line-height: 1.5 !important;
            transition: all 0.2s ease-in-out !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            cursor: pointer !important;
        }

        /* Hover effects - subtle and elegant */
        #academicYearSelect:hover,
        #yearLevelSelect:hover,
        #semesterSelect:hover {
            border-color: #d1d5db !important;
            background: #fafafa !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            transform: translateY(-1px) !important;
        }

        /* Focus states - clean and accessible */
        #academicYearSelect:focus,
        #yearLevelSelect:focus,
        #semesterSelect:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            transform: translateY(-1px) !important;
        }

        /* Active states */
        #academicYearSelect:active,
        #yearLevelSelect:active,
        #semesterSelect:active {
            transform: translateY(0) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        }

        /* Beautiful option styling */
        #academicYearSelect option,
        #yearLevelSelect option,
        #semesterSelect option {
            background: #ffffff !important;
            color: #374151 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.95rem !important;
            font-weight: 400 !important;
            padding: 0.75rem 1rem !important;
            margin: 0.25rem 0 !important;
            border-radius: 6px !important;
            transition: all 0.15s ease !important;
        }

        /* Option hover effects */
        #academicYearSelect option:hover,
        #yearLevelSelect option:hover,
        #semesterSelect option:hover {
            background: #f3f4f6 !important;
            color: #1f2937 !important;
            font-weight: 500 !important;
        }

        /* Selected option styling */
        #academicYearSelect option:checked,
        #yearLevelSelect option:checked,
        #semesterSelect option:checked {
            background: #3b82f6 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Beautiful optgroup styling */
        #academicYearSelect optgroup {
            background: #f8fafc !important;
            color: #6b7280 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 0.75rem 0 !important;
            margin: 0.5rem 0 !important;
            border-bottom: 1px solid #e5e7eb !important;
        }

        /* Enhanced dropdown containers */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            background: transparent !important;
            border: none !important;
            padding: 0.5rem !important;
            backdrop-filter: none !important;
        }

        /* Remove shimmer effects for cleaner look */
        .academic-year-dropdown::after,
        .year-level-dropdown::after,
        .semester-dropdown::after {
            display: none !important;
        }

        /* Beautiful labels */
        .dropdown-item label {
            color: #374151 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            text-transform: none !important;
            letter-spacing: 0.025em !important;
            margin-bottom: 0.75rem !important;
            padding-left: 0.25rem !important;
        }

        /* Custom dropdown arrow - clean and modern */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 1rem center !important;
            background-size: 1.25rem !important;
            padding-right: 3rem !important;
        }

        /* Disabled state styling */
        #academicYearSelect option:disabled,
        #yearLevelSelect option:disabled,
        #semesterSelect option:disabled {
            background: #f9fafb !important;
            color: #9ca3af !important;
            font-style: italic !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 0.95rem !important;
                padding: 0.875rem 1rem 0.875rem 1.25rem !important;
                padding-right: 2.75rem !important;
            }
        }

        @media (max-width: 480px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 0.9rem !important;
                padding: 0.75rem 1rem 0.75rem 1.25rem !important;
                padding-right: 2.5rem !important;
            }
        }

        /* Modern Dropdown Styling with Glassmorphism & Contemporary Design */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            /* Modern glassmorphism effect */
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 16px !important;
            color: #1f2937 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            padding: 1.125rem 1.5rem !important;
            line-height: 1.5 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
            cursor: pointer !important;
            position: relative !important;
            overflow: hidden !important;
        }

        /* Modern hover effects with enhanced animations */
        #academicYearSelect:hover,
        #yearLevelSelect:hover,
        #semesterSelect:hover {
            background: rgba(255, 255, 255, 0.98) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
            box-shadow: 
                0 10px 25px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-2px) scale(1.02) !important;
        }

        /* Modern focus states with enhanced visual feedback */
        #academicYearSelect:focus,
        #yearLevelSelect:focus,
        #semesterSelect:focus {
            outline: none !important;
            background: rgba(255, 255, 255, 1) !important;
            border-color: #3b82f6 !important;
            box-shadow: 
                0 0 0 4px rgba(59, 130, 246, 0.15),
                0 10px 25px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
            transform: translateY(-2px) scale(1.02) !important;
        }

        /* Modern active states with smooth transitions */
        #academicYearSelect:active,
        #yearLevelSelect:active,
        #semesterSelect:active {
            transform: translateY(0) scale(1) !important;
            transition: all 0.1s ease-out !important;
        }

        /* Modern option styling with enhanced visual hierarchy */
        #academicYearSelect option,
        #yearLevelSelect option,
        #semesterSelect option {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #1f2937 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.95rem !important;
            font-weight: 400 !important;
            padding: 0.875rem 1.25rem !important;
            margin: 0.375rem 0 !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
            border: 1px solid transparent !important;
        }

        /* Modern option hover effects */
        #academicYearSelect option:hover,
        #yearLevelSelect option:hover,
        #semesterSelect option:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #1e40af !important;
            font-weight: 500 !important;
            border-color: rgba(59, 130, 246, 0.2) !important;
            transform: translateX(4px) !important;
        }

        /* Modern selected option styling */
        #academicYearSelect option:checked,
        #yearLevelSelect option:checked,
        #semesterSelect option:checked {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3) !important;
        }

        /* Modern optgroup styling */
        #academicYearSelect optgroup {
            background: rgba(248, 250, 252, 0.8) !important;
            color: #6b7280 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            padding: 1rem 0 !important;
            margin: 0.75rem 0 !important;
            border-bottom: 2px solid rgba(229, 231, 235, 0.5) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        /* Modern dropdown containers with enhanced styling */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 20px !important;
            padding: 0.75rem !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
        }

        /* Modern labels with enhanced typography */
        .dropdown-item label {
            color: #1f2937 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            text-transform: none !important;
            letter-spacing: 0.025em !important;
            margin-bottom: 0.25rem !important;
            padding-left: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        /* Enhanced responsive design for labels */
        @media (max-width: 768px) {
            .dropdown-item label {
                font-size: 1.15rem !important;
            }
        }

        @media (max-width: 480px) {
            .dropdown-item label {
                font-size: 1.1rem !important;
            }
        }

        /* Modern dropdown arrow with enhanced styling */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 1.25rem center !important;
            background-size: 1.5rem !important;
            padding-right: 3.5rem !important;
        }

        /* Modern disabled state styling */
        #academicYearSelect option:disabled,
        #yearLevelSelect option:disabled,
        #semesterSelect option:disabled {
            background: rgba(249, 250, 251, 0.8) !important;
            color: #9ca3af !important;
            font-style: italic !important;
            opacity: 0.7 !important;
        }

        /* Enhanced responsive design */
        @media (max-width: 768px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 0.95rem !important;
                padding: 1rem 1.25rem !important;
                padding-right: 3rem !important;
                border-radius: 14px !important;
            }
            
            .dropdown-item label {
                font-size: 0.9rem !important;
            }
        }

        @media (max-width: 480px) {
            #academicYearSelect,
            #yearLevelSelect,
            #semesterSelect {
                font-size: 0.9rem !important;
                padding: 0.875rem 1.125rem !important;
                padding-right: 2.75rem !important;
                border-radius: 12px !important;
            }
            
            .dropdown-item label {
                font-size: 0.85rem !important;
            }
        }

        /* Modern selection header styling */
        .selection-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 24px !important;
        }

        /* Filter subheader styling */
        .filter-subheader {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin: 0 0 1.5rem 0 !important;
            color: #374151 !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            text-align: center !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .filter-subheader:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-subheader i {
            color: #3b82f6 !important;
            margin-right: 0.5rem;
        }
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
        }

        /* Enhanced dropdown row with modern spacing */
        .dropdowns-row {
            gap: 2.5rem !important;
            margin-top: 0.5rem !important;
        }

        /* Fixed Dropdown Visibility - Clear and Readable */
        #academicYearSelect,
        #yearLevelSelect,
        #semesterSelect {
            /* Solid background for better visibility */
            background: #ffffff !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 16px !important;
            color: #1f2937 !important;
            font-weight: 500 !important;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }

        /* Clear option visibility */
        #academicYearSelect option,
        #yearLevelSelect option,
        #semesterSelect option {
            background: #ffffff !important;
            color: #1f2937 !important;
            font-weight: 400 !important;
            padding: 0.875rem 1.25rem !important;
            margin: 0.25rem 0 !important;
            border-radius: 8px !important;
            border: 1px solid transparent !important;
        }

        /* Clear option hover effects */
        #academicYearSelect option:hover,
        #yearLevelSelect option:hover,
        #semesterSelect option:hover {
            background: #f3f4f6 !important;
            color: #1e40af !important;
            font-weight: 500 !important;
            border-color: #d1d5db !important;
        }

        /* Clear selected option styling */
        #academicYearSelect option:checked,
        #yearLevelSelect option:checked,
        #semesterSelect option:checked {
            background: #3b82f6 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border-color: #3b82f6 !important;
        }

        /* Clear optgroup styling */
        #academicYearSelect optgroup {
            background: #f8fafc !important;
            color: #6b7280 !important;
            font-weight: 700 !important;
            padding: 0.75rem 0 !important;
            margin: 0.5rem 0 !important;
            border-bottom: 2px solid #e5e7eb !important;
        }

        /* Clear dropdown containers */
        .academic-year-dropdown,
        .year-level-dropdown,
        .semester-dropdown {
            background: transparent !important;
            border: none !important;
            padding: 0.5rem !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: none !important;
        }

        /* Enhanced hover effects for main dropdowns */
        #academicYearSelect:hover,
        #yearLevelSelect:hover,
        #semesterSelect:hover {
            background: #fafafa !important;
            border-color: #d1d5db !important;
            box-shadow: 
                0 8px 25px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) !important;
        }

        /* Enhanced focus states */
        #academicYearSelect:focus,
        #yearLevelSelect:focus,
        #semesterSelect:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 8px 25px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) !important;
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
                    <li class="nav-item"><a href="export-schedule.php" class="nav-link"><i class="fas fa-download"></i><span>Export/Download Schedule</span></a></li>
                </ul>
                <div class="sidebar-footer">
                    <a href="../../auth/logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </nav>
        </aside>

        <main class="admin-main" id="adminMain">
            <div class="main-header">
                <h1 class="page-title"><i class="fas fa-book"></i> Courses Dashboard</h1>
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
                <a href="Courses-dashboard.php" class="dashboard-card-nav active" data-dashboard="courses">
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
                    <div class="management-controls" style="position: relative; display: flex; justify-content: center; align-items: center; padding: 1rem 0;">
                        <div style="text-align: center;">
                            <h2 class="section-title" style="font-size: 2rem; font-weight: 700; margin: 0; text-align: center;">Course Information</h2>
                            <p style="margin: 0.5rem 0 0 0; color: #374151; font-size: 1.1rem; font-weight: 500;">
                                <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                                Use the filters below to view courses by year level, semester, and section
                            </p>
                        </div>
                        <button class="btn btn-primary" id="openAddSubjectModal" style="position: absolute; right: 0; top: 0; background: linear-gradient(135deg, #667eea, #5a6fd8); border: none; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" onmouseover="this.style.background='linear-gradient(135deg, #5a6fd8, #4c5fd0)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.background='linear-gradient(135deg, #667eea, #5a6fd8)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'">
                            <i class="fas fa-plus"></i> Add New Course
                        </button>
                    </div>

                                        <!-- Combined Selection Header -->
                    <div class="selection-header">
                        <div class="dropdowns-row">


                            <!-- Year Level Dropdown -->
                            <div class="dropdown-item">
                                <label for="yearLevelSelect">🎓 Year Level</label>
                                <div class="year-level-dropdown">
                                    <select id="yearLevelSelect">
                                        <option value="" disabled>Select Year Level</option>
                                        <option value="1st Year" <?php echo $current_year_level === '1st Year' ? 'selected' : ''; ?>>
                                            1st Year
                                        </option>
                                        <option value="2nd Year" <?php echo $current_year_level === '2nd Year' ? 'selected' : ''; ?>>
                                            2nd Year
                                        </option>
                                        <option value="3rd Year" <?php echo $current_year_level === '3rd Year' ? 'selected' : ''; ?>>
                                            3rd Year
                                        </option>
                                        <option value="4th Year" <?php echo $current_year_level === '4th Year' ? 'selected' : ''; ?>>
                                            4th Year
                                        </option>
                                    </select>
                    </div>
                    </div>

                            <!-- Semester Dropdown -->
                            <div class="dropdown-item">
                                <label for="semesterSelect">📅 Semester</label>
                                <div class="semester-dropdown">
                                    <select id="semesterSelect">
                                        <option value="" disabled>Select Semester</option>
                                        <option value="first" <?php echo ($current_semester === 'first' || $current_semester === '') ? 'selected' : ''; ?>>
                                            First Semester
                                        </option>
                                        <option value="second" <?php echo $current_semester === 'second' ? 'selected' : ''; ?>>
                                            Second Semester
                                        </option>
                        <?php if ($current_year_level === '3rd Year'): ?>
                                        <option value="summer" <?php echo $current_semester === 'summer' ? 'selected' : ''; ?>>
                                            Mid Year
                                        </option>
                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Section Dropdown -->
                            <div class="dropdown-item">
                                <label for="sectionSelect">
                                    👥 Section
                                    <?php if (isset($_GET['section']) && $_GET['section']): ?>
                                        <span class="section-filter-indicator">Filtered</span>
                                    <?php endif; ?>
                                </label>
                                <div class="section-select-dropdown">
                                    <select id="sectionSelect">
                                        <?php if (!empty($availableSections)): ?>
                                            <?php 
                                            $hasValidSection = false;
                                            $firstValidSection = '';
                                            $hasSelectedSection = isset($_GET['section']) && !empty($_GET['section']);
                                            
                                            // Filter out sections A, B, C
                                            $filteredSections = array_filter($availableSections, function($section) {
                                                return !in_array($section['section_name'], ['A', 'B', 'C']);
                                            });
                                            
                                            foreach ($filteredSections as $section): 
                                                $hasValidSection = true;
                                                if (empty($firstValidSection)) $firstValidSection = $section['section_name'];
                                                
                                                // Determine if this section should be selected
                                                $isSelected = false;
                                                if ($hasSelectedSection) {
                                                    $isSelected = $_GET['section'] === $section['section_name'];
                                                } else {
                                                    // If no section is specified in URL, select the first valid one
                                                    $isSelected = empty($firstValidSection) || $firstValidSection === $section['section_name'];
                                                }
                                            ?>
                                                <option value="<?php echo htmlspecialchars($section['section_name']); ?>" 
                                                        <?php echo $isSelected ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if (!$hasValidSection): ?>
                                                <option value="" disabled>No valid sections available</option>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No sections available</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>







                    <div class="table-container">
                        <?php 
                        // Get selected section from URL parameter
                        $selectedSection = $_GET['section'] ?? '';
                        
                        // Filter sections based on selection
                        $sectionsToShow = $availableSections;
                        if ($selectedSection) {
                            $sectionsToShow = array_filter($availableSections, function($section) use ($selectedSection) {
                                return $section['section_name'] === $selectedSection;
                            });
                        }
                        
                        if (!empty($sectionsToShow)): ?>
                            <?php foreach ($sectionsToShow as $section): ?>
                                <div class="section-group" data-section="<?php echo htmlspecialchars($section['section_name']); ?>">
                                    <div class="section-header">
                                        <h2><i class="fas fa-users"></i> <?php echo htmlspecialchars($section['section_name']); ?></h2>
                                    </div>
                                    
                                    <!-- Active Courses for this Section -->
                                    <div class="table-section-header">
                                        <h3><i class="fas fa-check-circle"></i> Active Courses</h3>
                                    </div>
                                    <div class="table-container">
                                        <div style="overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb;">
                                            <table class="table" id="activeSubjects_<?php echo htmlspecialchars($section['section_name']); ?>" style="min-width: 1200px;">
                                                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Units</th>
                                    <th>Semester</th>
                                    <th>Year Level</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeSubjectsBySection[$section['section_name']])): ?>
                                    <?php foreach ($activeSubjectsBySection[$section['section_name']] as $subject): ?>
                                        <tr data-subject-id="<?php echo $subject['id']; ?>">
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo $subject['units']; ?></td>
                                            <td><?php echo formatSemesterName($subject['semester'] ?? 'first'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['year_level']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['section_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="status-column">
                                                    <label class="switch">
                                                        <input type="checkbox" class="status-toggle" 
                                                               data-subject-id="<?php echo $subject['id']; ?>"
                                                               checked>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-active">Active</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-secondary edit-subject"
                                                            data-subject-id="<?php echo $subject['id']; ?>"
                                                            data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                            data-subject-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                            data-units="<?php echo $subject['units']; ?>"
                                                            data-year-level="<?php echo htmlspecialchars($subject['year_level']); ?>"
                                                            data-semester="<?php echo htmlspecialchars($subject['semester'] ?? 'first'); ?>"
                                                            data-section="<?php echo htmlspecialchars($subject['section_name'] ?? ''); ?>"
                                                            data-academic-year="<?php echo htmlspecialchars($subject['academic_year'] ?? $current_academic_year); ?>"
                                                            data-course-type="<?php echo htmlspecialchars($subject['course_type'] ?? 'lecture'); ?>"
                                                            data-prerequisites="<?php echo htmlspecialchars($subject['prerequisites'] ?? ''); ?>"
                                                            data-status="<?php echo htmlspecialchars($subject['status']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-subject" data-subject-id="<?php echo $subject['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="muted">No active courses found for <?php echo htmlspecialchars($section['section_name']); ?>.</td></tr>
                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        </div>
                                    </div>

                                    <!-- Inactive Courses for this Section -->
                                    <div class="table-section-header">
                                        <h3><i class="fas fa-ban"></i> Inactive Courses</h3>
                                    </div>
                                    <div class="table-container">
                                        <div style="overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb;">
                                            <table class="table" id="inactiveSubjects_<?php echo htmlspecialchars($section['section_name']); ?>" style="min-width: 1200px;">
                                <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Units</th>
                                    <th>Semester</th>
                                    <th>Year Level</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inactiveSubjectsBySection[$section['section_name']])): ?>
                                    <?php foreach ($inactiveSubjectsBySection[$section['section_name']] as $subject): ?>
                                        <tr data-subject-id="<?php echo $subject['id']; ?>">
                                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo $subject['units']; ?></td>
                                            <td><?php echo formatSemesterName($subject['semester'] ?? 'first'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['year_level']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['section_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="status-column">
                                                    <label class="switch">
                                                        <input type="checkbox" class="status-toggle" 
                                                               data-subject-id="<?php echo $subject['id']; ?>"
                                                               checked>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-inactive">Inactive</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-secondary edit-subject"
                                                            data-subject-id="<?php echo $subject['id']; ?>"
                                                            data-subject-code="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                                            data-subject-name="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                                                            data-units="<?php echo $subject['units']; ?>"
                                                            data-year-level="<?php echo htmlspecialchars($subject['year_level']); ?>"
                                                            data-semester="<?php echo htmlspecialchars($subject['semester'] ?? 'first'); ?>"
                                                            data-section="<?php echo htmlspecialchars($subject['section_name'] ?? ''); ?>"
                                                            data-academic-year="<?php echo htmlspecialchars($subject['academic_year'] ?? $current_academic_year); ?>"
                                                            data-course-type="<?php echo htmlspecialchars($subject['course_type'] ?? 'lecture'); ?>"
                                                            data-prerequisites="<?php echo htmlspecialchars($subject['prerequisites'] ?? ''); ?>"
                                                            data-status="<?php echo htmlspecialchars($subject['status']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-subject" data-subject-id="<?php echo $subject['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                                                    <?php else: ?>
                                        <tr><td colspan="9" class="muted">No active courses found for <?php echo htmlspecialchars($section['section_name']); ?>.</td></tr>
                                    <?php endif; ?>
                                            </tbody>
                                        </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-sections-message">
                                <?php if ($selectedSection): ?>
                                    <p><i class="fas fa-info-circle"></i> "<?php echo htmlspecialchars($selectedSection); ?>" is not available for <?php echo htmlspecialchars($current_year_level); ?>.</p>
                                    <p><a href="?year_level=<?php echo urlencode($current_year_level); ?>&semester=<?php echo urlencode($current_semester); ?>" class="btn btn-primary">View All Sections</a></p>
                                <?php else: ?>
                                    <p><i class="fas fa-info-circle"></i> No sections available for <?php echo htmlspecialchars($current_year_level); ?>.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:3100; background:rgba(17,24,39,.45); padding:1rem;">
        <div class="notice-box" style="background:#fff; width:min(480px,92vw); border-radius:14px; border:1px solid rgba(229,231,235,.9); box-shadow:0 20px 60px rgba(0,0,0,.25); padding:1rem 1.25rem; text-align:center;">
            <div class="notice-header" style="display:flex; align-items:center; justify-content:center; gap:.5rem; margin-bottom:.5rem;">
                <i class="fas fa-exclamation-triangle" style="color:#dc2626" aria-hidden="true"></i>
                <h4>Confirm Action</h4>
            </div>
            <div class="notice-content" style="margin:.5rem 0;">
                <span id="confirmMessage"></span>
            </div>
            <div class="notice-actions" style="margin-top:.75rem; display:flex; justify-content:center; gap:.5rem;">
                <button id="confirmYes" class="btn btn-danger">Yes</button>
                <button id="confirmNo" class="btn btn-secondary">No</button>
            </div>
        </div>
    </div>
    
    <!-- Add Subject Modal -->
    <div id="addSubjectModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 0 auto; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-book" style="color: #6366f1; margin-right: 0.5rem;"></i>Add New Course</h3>
                <button id="closeAddSubjectModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="Courses-dashboard.php" class="form" id="addSubjectForm" novalidate>
                <input type="hidden" name="action" value="add_subject" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subject_code">Course Code</label>
                        <input type="text" id="subject_code" name="subject_code" class="form-input" required pattern="^[A-Z0-9\s+]+$" title="Only uppercase letters, numbers, spaces, and plus signs are allowed" />
                        <span class="field-error" id="subjectCodeError"></span>
                    </div>
                    <div class="form-group">
                        <label for="subject_name">Course Title</label>
                        <input type="text" id="subject_name" name="subject_name" class="form-input" required title="Letters, numbers, spaces, and most special characters are allowed" />
                        <span class="field-error" id="subjectNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="units">Units</label>
                        <select id="units" name="units" class="form-input" required>
                            <option value="" disabled selected>Select units</option>
                            <option value="0">0 Unit</option>
                            <option value="1">1 Unit</option>
                            <option value="1.5">1.5 Units</option>
                            <option value="2">2 Units</option>
                            <option value="3">3 Units</option>
                        </select>
                        <span class="field-error" id="unitsError"></span>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" class="form-input" required>
                            <option value="" disabled selected>Select year level</option>
                            <?php foreach ($year_levels as $year_level => $name): ?>
                                <option value="<?php echo htmlspecialchars($year_level); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" id="yearLevelError"></span>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" class="form-input" required>
                            <option value="" disabled selected>Select semester</option>
                            <option value="first">First Semester</option>
                            <option value="second">Second Semester</option>
                            <option value="summer" id="summerOption">Mid Year</option>
                        </select>
                        <span class="field-error" id="semesterError"></span>
                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" class="form-input" required>
                            <option value="" disabled selected>Select Section</option>
                        </select>
                        <span class="field-error" id="sectionError"></span>
                    </div>


                                    <div class="form-group">
                        <label for="status">Course Status</label>
                        <select id="status" name="status" class="form-input" required>
                            <option value="" disabled selected>Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <span class="field-error" id="statusError"></span>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelAddSubject">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editSubjectModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; animation: modalSlideIn 0.3s ease-out;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; margin: 0 auto; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; color: #111827; font-size: 1.25rem; font-weight: 600;"><i class="fas fa-edit" style="color: #6366f1; margin-right: 0.5rem;"></i>Edit Course</h3>
                <button id="closeEditSubjectModal" style="background: transparent; border: none; color: #6b7280; font-size: 1.25rem; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="Courses-dashboard.php" class="form" id="editSubjectForm" novalidate>
                <input type="hidden" name="action" value="edit_subject" />
                <input type="hidden" id="edit_subject_id" name="subject_id" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_subject_code">Course Code</label>
                        <input type="text" id="edit_subject_code" name="subject_code" class="form-input" required pattern="^[A-Z0-9\s+]+$" title="Only uppercase letters, numbers, spaces, and plus signs are allowed" />
                        <span class="field-error" id="editSubjectCodeError"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_subject_name">Course Title</label>
                        <input type="text" id="edit_subject_name" name="subject_name" class="form-input" required title="Letters, numbers, spaces, and most special characters are allowed" />
                        <span class="field-error" id="editSubjectNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_units">Units</label>
                        <select id="edit_units" name="units" class="form-input" required>
                            <option value="" disabled>Select units</option>
                            <option value="0">0 Unit</option>
                            <option value="1">1 Unit</option>
                            <option value="1.5">1.5 Units</option>
                            <option value="2">2 Units</option>
                            <option value="3">3 Units</option>
                        </select>
                        <span class="field-error" id="editUnitsError"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_year_level">Year Level</label>
                        <select id="edit_year_level" name="year_level" class="form-input" required>
                            <?php foreach ($year_levels as $year_level => $name): ?>
                                <option value="<?php echo htmlspecialchars($year_level); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" id="editYearLevelError"></span>
                    </div>
                    <div class="form-group">
                        <label for="edit_semester">Semester</label>
                        <select id="edit_semester" name="semester" class="form-input" required>
                            <option value="" disabled>Select semester</option>
                            <option value="first">First Semester</option>
                            <option value="second">Second Semester</option>
                            <option value="summer" id="editSummerOption">Mid Year</option>
                        </select>
                        <span class="field-error" id="editSemesterError"></span>
                    </div>

                    <div class="form-group">
                        <label for="edit_section">Section</label>
                        <select id="edit_section" name="section" class="form-input" required>
                            <option value="" disabled selected>Select Section</option>
                        </select>
                        <span class="field-error" id="editSectionError"></span>
                    </div>


                                    <div class="form-group">
                        <label for="edit_status">Course Status</label>
                        <select id="edit_status" name="status" class="form-input" required>
                            <option value="" disabled>Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <span class="field-error" id="editStatusError"></span>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" id="cancelEditSubject">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
        </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeSubjectDashboard();
            initializeYearLevelDropdown();
            initializeSemesterDropdown();
            initializeSectionDropdown();
            initializeSectionDropdowns();
            initializeSectionNavigation();
        });


        // Year level dropdown functionality
        function initializeYearLevelDropdown() {
            const yearLevelSelect = document.getElementById('yearLevelSelect');
            if (yearLevelSelect) {
                yearLevelSelect.addEventListener('change', function() {
                    const selectedYearLevel = this.value;
                    const currentSemester = '<?php echo $current_semester; ?>';
                    const currentSection = '<?php echo $_GET['section'] ?? ''; ?>';
                    
                    // Validate semester based on year level - redirect if summer is selected for 1st, 2nd, or 4th year
                    let targetSemester = currentSemester;
                    if ((selectedYearLevel === '1st Year' || selectedYearLevel === '2nd Year' || selectedYearLevel === '4th Year') && currentSemester === 'summer') {
                        targetSemester = 'first';
                    }
                    
                    // Build URL with current parameters
                    let newUrl = `?year_level=${encodeURIComponent(selectedYearLevel)}&semester=${encodeURIComponent(targetSemester)}`;
                    if (currentSection) {
                        newUrl += `&section=${encodeURIComponent(currentSection)}`;
                    }
                    
                    // Redirect to the new year level and adjusting semester if needed
                    window.location.href = newUrl;
                });
            }
        }

        // Semester dropdown functionality
        function initializeSemesterDropdown() {
            const semesterSelect = document.getElementById('semesterSelect');
            if (semesterSelect) {
                semesterSelect.addEventListener('change', function() {
                    const selectedSemester = this.value;
                    const currentYearLevel = '<?php echo $current_year_level; ?>';
                    const currentSection = '<?php echo $_GET['section'] ?? ''; ?>';
                    
                    // Validate semester based on year level - prevent Mid Year for 1st, 2nd, and 4th year
                    if ((currentYearLevel === '1st Year' || currentYearLevel === '2nd Year' || currentYearLevel === '4th Year') && selectedSemester === 'summer') {
                        // Show error message and reset to first semester
                        alert('Mid Year semester is not available for 1st Year, 2nd Year, and 4th Year. Please select First or Second Semester.');
                        this.value = 'first';
                        return;
                    }
                    
                    // Build URL with current parameters
                    let newUrl = `?year_level=${encodeURIComponent(currentYearLevel)}&semester=${encodeURIComponent(selectedSemester)}`;
                    if (currentSection) {
                        newUrl += `&section=${encodeURIComponent(currentSection)}`;
                    }
                    
                    // Redirect to the new semester while preserving year level and section
                    window.location.href = newUrl;
                });
            }
        }

        // Section dropdown functionality
        function initializeSectionDropdown() {
            const sectionSelect = document.getElementById('sectionSelect');
            if (sectionSelect) {
                // If no section is selected and we have a valid first section, select it
                if (sectionSelect.value === '' && sectionSelect.options.length > 0) {
                    // Find first valid option (skip disabled options)
                    for (let i = 0; i < sectionSelect.options.length; i++) {
                        if (!sectionSelect.options[i].disabled) {
                            sectionSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
                
                sectionSelect.addEventListener('change', function() {
                    const selectedSection = this.value;
                    const currentYearLevel = '<?php echo $current_year_level; ?>';
                    const currentSemester = '<?php echo $current_semester; ?>';
                    
                    // Build URL with current parameters
                    let newUrl = `?year_level=${encodeURIComponent(currentYearLevel)}&semester=${encodeURIComponent(currentSemester)}`;
                    if (selectedSection) {
                        newUrl += `&section=${encodeURIComponent(selectedSection)}`;
                    }
                    
                    // Redirect to the new section while preserving year level and semester
                    window.location.href = newUrl;
                });
            }
        }

        // Section dropdown functionality
        function initializeSectionDropdowns() {
            console.log('Initializing section dropdowns...');
            
            // Initialize section dropdowns when year level changes
            const addYearLevel = document.getElementById('year_level');
            const editYearLevel = document.getElementById('edit_year_level');
            
            if (addYearLevel) {
                console.log('Found add year level dropdown');
                addYearLevel.addEventListener('change', function() {
                    console.log(`Add year level changed to: ${this.value}`);
                    if (this.value) {
                        loadSectionsForYearLevel(this.value, 'section');
                    } else {
                        // Clear section dropdown if no year level selected
                        const sectionDropdown = document.getElementById('section');
                        if (sectionDropdown) {
                            sectionDropdown.innerHTML = '<option value="" disabled selected>Select Section</option>';
                        }
                    }
                });
            } else {
                console.log('Add year level dropdown not found');
            }
            
            if (editYearLevel) {
                console.log('Found edit year level dropdown');
                editYearLevel.addEventListener('change', function() {
                    console.log(`Edit year level changed to: ${this.value}`);
                    if (this.value) {
                        loadSectionsForYearLevel(this.value, 'edit_section');
                    } else {
                        // Clear section dropdown if no year level selected
                        const sectionDropdown = document.getElementById('edit_section');
                        if (sectionDropdown) {
                            sectionDropdown.innerHTML = '<option value="" disabled selected>Select Section</option>';
                        }
                    }
                });
            } else {
                console.log('Edit year level dropdown not found');
            }
        }

        // Test function to manually load sections
        function testLoadSections(dropdownId) {
            const yearLevel = document.getElementById('year_level')?.value || document.getElementById('edit_year_level')?.value;
            if (yearLevel) {
                console.log(`Testing section loading for ${yearLevel} in ${dropdownId}`);
                loadSectionsForYearLevel(yearLevel, dropdownId);
            } else {
                alert('Please select a year level first');
            }
        }

        // Load sections for a specific year level
        async function loadSectionsForYearLevel(yearLevel, dropdownId) {
            console.log(`Loading sections for year level: ${yearLevel}, dropdown: ${dropdownId}`);
            
            try {
                const apiUrl = `../../api/get-active-sections.php?year_level=${encodeURIComponent(yearLevel)}`;
                console.log(`API URL: ${apiUrl}`);
                
                const response = await fetch(apiUrl);
                console.log(`Response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log(`API Response:`, data);
                
                if (data.success) {
                    const dropdown = document.getElementById(dropdownId);
                    if (dropdown) {
                        console.log(`Found dropdown: ${dropdownId}`);
                        
                        // Clear existing options
                        dropdown.innerHTML = '<option value="" disabled selected>Select Section</option>';
                        
                        // Add new options
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section_id;
                            option.textContent = section.section_name;
                            dropdown.appendChild(option);
                            console.log(`Added section option: ${section.section_name} (ID: ${section.section_id})`);
                        });
                        
                        console.log(`Total sections loaded: ${data.sections.length}`);
                    } else {
                        console.error(`Dropdown not found: ${dropdownId}`);
                    }
                } else {
                    console.error('Failed to load sections:', data.message);
                }
            } catch (error) {
                console.error('Error loading sections:', error);
            }
        }



        // Subject dashboard functionality
        function initializeSubjectDashboard() {
            // Direct button click handler for Add Subject Modal
            const addButton = document.getElementById('openAddSubjectModal');
            if (addButton) {
                addButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modal = document.getElementById('addSubjectModal');
                    if (!modal) {
                        return;
                    }
                    const form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                        // Reset semester field to default value
                        const semesterField = document.getElementById('semester');
                        if (semesterField) {
                            semesterField.value = '';
                        }
                    }
                    modal.style.display = 'flex';
                    modal.style.zIndex = '9999';
                    modal.style.position = 'fixed';
                    modal.style.top = '0';
                    modal.style.left = '0';
                    modal.style.width = '100%';
                    modal.style.height = '100%';
                    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                    
                    // Setup real-time validation and Mid Year option visibility
                    try {
                        setTimeout(setupRealTimeValidation, 100);
                        setTimeout(() => {
                            const yearLevel = document.getElementById('year_level');
                            if (yearLevel && yearLevel.value) {
                                toggleSummerOption(yearLevel.value, 'summerOption');
                                // Load sections for the selected year level
                                loadSectionsForYearLevel(yearLevel.value, 'section');
                            }
                        }, 150);
                    } catch (error) {
                        // Silent error handling
                    }
                });
            }
            
            // Modal controls for other elements
            document.addEventListener('click', (e) => {
                
                // Close Add Subject Modal
                if (e.target.closest('#closeAddSubjectModal, #cancelAddSubject')) {
                    e.preventDefault();
                    document.getElementById('addSubjectModal').style.display = 'none';
                    return;
                }

                // Edit Subject
                const editBtn = e.target.closest('.edit-subject');
                if (editBtn) {
                    e.preventDefault();
                    const d = editBtn.dataset;
                    document.getElementById('edit_subject_id').value = d.subjectId;
                    document.getElementById('edit_subject_code').value = d.subjectCode;
                    document.getElementById('edit_subject_name').value = d.subjectName;
                    document.getElementById('edit_units').value = d.units;
                    document.getElementById('edit_year_level').value = d.yearLevel;
                    // Set values for new fields from data attributes
                    document.getElementById('edit_semester').value = d.semester || 'first';
                    document.getElementById('edit_status').value = d.status;
                    document.getElementById('editSubjectModal').style.display = 'flex';
                    // Setup real-time validation when modal opens
                    setTimeout(setupRealTimeValidation, 100);
                    // Initialize Mid Year option visibility based on current year level
                    setTimeout(() => {
                        toggleSummerOption(d.yearLevel, 'editSummerOption');
                        // Load sections for the selected year level and set the current section
                        loadSectionsForYearLevel(d.yearLevel, 'edit_section').then(() => {
                            if (d.section) {
                                const editSection = document.getElementById('edit_section');
                                if (editSection) {
                                    // Find and select the section that matches the current section name
                                    Array.from(editSection.options).forEach(option => {
                                        if (option.textContent === d.section) {
                                            option.selected = true;
                                        }
                                    });
                                }
                            }
                        });
                    }, 150);
                    return;
                }
                
                // Close Edit Subject Modal
                if (e.target.closest('#closeEditSubjectModal, #cancelEditSubject')) {
                    e.preventDefault();
                    document.getElementById('editSubjectModal').style.display = 'none';
                    return;
                }

                // Delete Subject
                const deleteBtn = e.target.closest('.delete-subject');
                if (deleteBtn) {
                    e.preventDefault();
                    handleDeleteSubject(deleteBtn);
                    return;
                }
            });

            // Status toggle handler for database updates
        document.addEventListener('change', async function(e) {
            if (e.target.classList.contains('status-toggle')) {
                e.preventDefault();
                const subjectId = e.target.getAttribute('data-subject-id');
                const isChecked = e.target.checked;
                const newStatus = isChecked ? 'available' : 'unavailable';
                    const statusText = isChecked ? 'Active' : 'Inactive';
                
                // Revert toggle state temporarily until confirmation
                e.target.checked = !isChecked;
                
                try {
                        const confirmed = await showConfirmModal(`Are you sure you want to change this course's status to ${statusText}?`);
                    if (!confirmed) return;
                    
                    // Set back to user's intended state
                    e.target.checked = isChecked;
                        
                        // Show loading state
                        const row = e.target.closest('tr');
                        row.style.opacity = '0.6';
                    
                    try {
                            // Submit status change to PHP backend
                        const formData = new FormData();
                        formData.append('action', 'toggle_status');
                        formData.append('subject_id', subjectId);
                        formData.append('status', newStatus);
                        
                        const response = await fetch('Courses-dashboard.php', {
                            method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                        });
                        
                        if (!response.ok) throw new Error('Request failed');
                        
                        // Update UI
                            const statusSpan = row.querySelector('.status-active, .status-inactive');
                        if (statusSpan) {
                            statusSpan.textContent = statusText;
                                statusSpan.className = isChecked ? 'status-active' : 'status-inactive';
                        }
                        
                        // Move row to appropriate table within the same section
                        const currentTable = row.closest('table');
                        const currentSectionGroup = currentTable.closest('.section-group');
                        const sectionName = currentSectionGroup.dataset.section;
                        
                        const targetTableId = isChecked ? `activeSubjects_${sectionName}` : `inactiveSubjects_${sectionName}`;
                        const targetTable = document.getElementById(targetTableId);
                        
                        if (currentTable.id !== targetTableId && targetTable) {
                            const targetTbody = targetTable.querySelector('tbody');
                            if (targetTbody) {
                                // Remove empty message if it exists
                                const emptyRow = targetTbody.querySelector('tr td.muted');
                                if (emptyRow && emptyRow.parentElement) {
                                    emptyRow.parentElement.remove();
                                }
                                
                                // Get the subject code for sorting
                                const subjectCode = row.querySelector('td:nth-child(1)').textContent.trim();
                                
                                // Find the correct position to insert the row to maintain alphabetical order
                                const existingRows = targetTbody.querySelectorAll('tr:not(.muted)');
                                let insertPosition = null;
                                
                                // Use localeCompare for proper alphabetical sorting
                                for (let i = 0; i < existingRows.length; i++) {
                                    const existingCode = existingRows[i].querySelector('td:nth-child(1)').textContent.trim();
                                    if (subjectCode.localeCompare(existingCode, undefined, {sensitivity: 'base'}) < 0) {
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
                                    emptyMessage.className = 'muted';
                                    const tableId = currentTable.id;
                                    emptyMessage.innerHTML = `<td colspan="8" class="muted">No active courses found for Section ${sectionName}.</td>`;
                                    currentTbody.appendChild(emptyMessage);
                                }
                            }
                        }
                        
                            // Reset row opacity
                            row.style.opacity = '1';
                            
                            // Show success message
                            showSuccessMessage(`Course status has been updated to ${statusText}`);
                            
                            // Trigger custom event for real-time sync with other dashboards
                            const statusChangeEvent = new CustomEvent('courseStatusChanged', {
                                detail: {
                                    subjectId: subjectId,
                                    newStatus: newStatus,
                                    subjectCode: row.querySelector('td:nth-child(1)').textContent.trim(),
                                    subjectName: row.querySelector('td:nth-child(2)').textContent.trim()
                                }
                            });
                            document.dispatchEvent(statusChangeEvent);
                            
                    } catch (err) {
                            // Reset row opacity
                            row.style.opacity = '1';
                            showErrorMessage('Failed to update course status');
                        e.target.checked = !isChecked;
                    }
                } catch (error) {
                    e.target.checked = !isChecked;
                }
            }
        });
        }

        // Handle delete subject
        async function handleDeleteSubject(deleteBtn) {
            const subjectId = deleteBtn.getAttribute('data-subject-id');
            const subjectName = deleteBtn.closest('tr').querySelector('td:nth-child(2)').textContent; // Get subject name from table row
            
            try {
                const confirmed = await showConfirmModal(`Are you sure you want to delete the subject "${subjectName}"?`);
                if (!confirmed) return;
                
                // Show loading state
                const row = deleteBtn.closest('tr');
                row.style.opacity = '0.6';
                
                try {
                    // Submit delete request to PHP backend
                    const formData = new FormData();
                    formData.append('action', 'delete_subject');
                    formData.append('subject_id', subjectId);
                    
                    const response = await fetch('Courses-dashboard.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    if (!response.ok) throw new Error('Request failed');
                    
                    // Remove the row from the table
                    row.remove();
                    
                    // Show delete success message with red theme and trash icon
                    showDeleteSuccessMessage(`Subject "${subjectName}" has been deleted successfully`);
                    
                    // Dispatch course change event for real-time sync with other dashboards
                    const courseChangeEvent = new CustomEvent('courseStatusChanged', {
                        detail: {
                            action: 'delete_subject',
                            subjectId: subjectId,
                            subjectName: subjectName,
                            message: `Subject "${subjectName}" has been deleted successfully`,
                            timestamp: new Date().toISOString()
                        }
                    });
                    document.dispatchEvent(courseChangeEvent);
                    
                    // Check if table is now empty and add empty message if needed
                    const table = row.closest('table');
                    const tbody = table.querySelector('tbody');
                    if (tbody && !tbody.querySelector('tr:not(.muted)')) {
                        const tableId = table.id;
                        const sectionGroup = table.closest('.section-group');
                        const sectionName = sectionGroup ? sectionGroup.dataset.section : '';
                        const statusText = tableId.includes('activeSubjects') ? 'active' : 'inactive';
                        const emptyMessage = document.createElement('tr');
                        emptyMessage.className = 'muted';
                        emptyMessage.innerHTML = `<td colspan="8" class="muted">No ${statusText} courses found for Section ${sectionName}.</td>`;
                        tbody.appendChild(emptyMessage);
                    }
                    
                } catch (err) {
                    // Reset row opacity
                    row.style.opacity = '1';
                    // Removed error message display
                }
            } catch (error) {
                // User cancelled deletion
            }
        }

        // Real-time validation setup
        function setupRealTimeValidation() {
            // Add New Course - Course Code validation
            const addSubjectCode = document.getElementById('subject_code');
            if (addSubjectCode) {
                addSubjectCode.addEventListener('input', function() {
                    // Convert to uppercase
                    this.value = this.value.toUpperCase();
                    validateCourseCodeRealTime(this, 'subjectCodeError');
                });
            }
            
            // Add New Course - Course Title validation
            const addSubjectName = document.getElementById('subject_name');
            if (addSubjectName) {
                addSubjectName.addEventListener('input', function() {
                    validateCourseTitleRealTime(this);
                });
            }
            
            // Add New Course - Status validation
            const addStatus = document.getElementById('status');
            if (addStatus) {
                addStatus.addEventListener('change', function() {
                    validateStatusRealTime(this, 'statusError');
                });
            }
            
            
            // Edit Course - Course Code validation
            const editSubjectCode = document.getElementById('edit_subject_code');
            if (editSubjectCode) {
                editSubjectCode.addEventListener('input', function() {
                    // Convert to uppercase
                    this.value = this.value.toUpperCase();
                    validateCourseCodeRealTime(this, 'editSubjectCodeError');
                });
            }
            
            // Edit Course - Course Title validation
            const editSubjectName = document.getElementById('edit_subject_name');
            if (editSubjectName) {
                editSubjectName.addEventListener('input', function() {
                    validateEditCourseTitleRealTime(this);
                });
            }
            
            // Edit Course - Status validation
            const editStatus = document.getElementById('edit_status');
            if (editStatus) {
                editStatus.addEventListener('change', function() {
                    validateStatusRealTime(this, 'editStatusError');
                });
            }
            
            
            // Add New Course - Year Level change handler for Mid Year semester visibility
            const addYearLevel = document.getElementById('year_level');
            if (addYearLevel) {
                addYearLevel.addEventListener('change', function() {
                    toggleSummerOption(this.value, 'summerOption');
                });
            }
            
            // Edit Course - Year Level change handler for Mid Year semester visibility
            const editYearLevel = document.getElementById('edit_year_level');
            if (editYearLevel) {
                editYearLevel.addEventListener('change', function() {
                    toggleSummerOption(this.value, 'editSummerOption');
                });
            }
        }

        // Real-time validation functions
        function validateCourseCodeRealTime(inputElement, errorId) {
            const value = inputElement.value.trim();
            const errorElement = document.getElementById(errorId);
            
            if (!errorElement) return;
            
            // Check if input contains only letters, numbers, spaces, and plus signs
            if (!/^[A-Z0-9\s+]*$/.test(value)) {
                errorElement.textContent = 'Course Code can only contain uppercase letters, numbers, spaces, and plus signs';
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
                return false;
            } else if (value === '') {
                errorElement.textContent = 'Course Code is required';
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
                return false;
            } else {
                // Check for duplicate course code (limit to 4 occurrences)
                const isEditForm = errorId.includes('edit');
                const currentSubjectId = isEditForm ? document.getElementById('edit_subject_id')?.value : null;
                
                if (!checkDuplicateCourseCode(value, inputElement, currentSubjectId)) {
                    return false; // Validation failed
                }
                
                // If we reach here, validation passed
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                inputElement.classList.remove('error');
                return true;
            }
        }

        // Check for duplicate course code (limit to 4 occurrences)
        function checkDuplicateCourseCode(code, inputElement, excludeSubjectId = null) {
            // Get all course codes from all section tables
            const sectionGroups = document.querySelectorAll('.section-group');
            let existingCodes = [];
            
            sectionGroups.forEach(sectionGroup => {
                const sectionName = sectionGroup.dataset.section;
                const activeTable = document.getElementById(`activeSubjects_${sectionName}`);
                const inactiveTable = document.getElementById(`inactiveSubjects_${sectionName}`);
                
                // Collect codes from active subjects
                if (activeTable) {
                    const activeRows = activeTable.querySelectorAll('tbody tr:not(.muted)');
                    activeRows.forEach(row => {
                        const codeCell = row.querySelector('td:nth-child(1)');
                        const subjectId = row.querySelector('.edit-subject')?.getAttribute('data-subject-id');
                        
                        if (codeCell && subjectId !== excludeSubjectId) {
                            existingCodes.push(codeCell.textContent.trim().toLowerCase());
                        }
                    });
                }
                
                // Collect codes from inactive subjects
                if (inactiveTable) {
                    const inactiveRows = inactiveTable.querySelectorAll('tbody tr:not(.muted)');
                    inactiveRows.forEach(row => {
                        const codeCell = row.querySelector('td:nth-child(1)');
                        const subjectId = row.querySelector('.edit-subject')?.getAttribute('data-subject-id');
                        
                        if (codeCell && subjectId !== excludeSubjectId) {
                            existingCodes.push(codeCell.textContent.trim().toLowerCase());
                        }
                    });
                }
            });
            
            // Check if the new code already exists more than 4 times (case-insensitive)
            const normalizedCode = code.toLowerCase();
            const codeCount = existingCodes.filter(existingCode => existingCode === normalizedCode).length;
            
            // Allow up to 4 occurrences, so if count is 4 or more, it's a duplicate
            if (codeCount >= 4) {
                const errorId = inputElement.id === 'subject_code' ? 'subjectCodeError' : 'editSubjectCodeError';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    errorElement.textContent = 'Course Code already exists 4 times in the table. You cannot have more than 4 same course codes.';
                    errorElement.style.display = 'block';
                    inputElement.classList.add('error');
                }
                return false; // Return false to indicate validation failed
            }
            
            return true; // Return true to indicate validation passed
        }

        // Add New Course - Course Title validation
        function validateCourseTitleRealTime(inputElement) {
            const value = inputElement.value;
            const errorElement = document.getElementById('subjectNameError');
            
            if (value === '') {
                showFieldError(errorElement, 'Course Title is required');
                return false;
            }
            
            // Allow letters, numbers, spaces, and most special characters
            const allowedPattern = /^[A-Za-z0-9\s()\-&.,!?;:'""@#$%^+=<>[\]{}|\\/]+$/;
            
            if (!allowedPattern.test(value)) {
                showFieldError(errorElement, 'Only letters, numbers, spaces and characters are allowed');
                return false;
            }
            
            // Check for duplicate course title
            if (!checkDuplicateCourseTitle(value, inputElement)) {
                return false;
            }
            
            // If we reach here, validation passed
            clearFieldError(errorElement);
            return true;
        }
        
        // Edit Course - Course Title validation
        function validateEditCourseTitleRealTime(inputElement) {
            const value = inputElement.value;
            const errorElement = document.getElementById('editSubjectNameError');
            
            if (value === '') {
                showFieldError(errorElement, 'Course Title is required');
                return false;
            }
            
            // Allow letters, numbers, spaces, and most special characters
            const allowedPattern = /^[A-Za-z0-9\s()\-&.,!?;:'""@#$%^+=<>[\]{}|\\/]+$/;
            
            if (!allowedPattern.test(value)) {
                showFieldError(errorElement, 'Only letters, numbers, spaces and characters are allowed');
                return false;
            }
            
            // Check for duplicate course title (exclude current subject)
            const currentSubjectId = document.getElementById('edit_subject_id')?.value;
            if (!checkDuplicateCourseTitle(value, inputElement, currentSubjectId)) {
                return false;
            }
            
            // If we reach here, validation passed
            clearFieldError(errorElement);
            return true;
        }

        // Check for duplicate course title
        function checkDuplicateCourseTitle(title, inputElement, excludeSubjectId = null) {
            // Get all course titles from all section tables
            const sectionGroups = document.querySelectorAll('.section-group');
            let existingTitles = [];
            
            sectionGroups.forEach(sectionGroup => {
                const sectionName = sectionGroup.dataset.section;
                const activeTable = document.getElementById(`activeSubjects_${sectionName}`);
                const inactiveTable = document.getElementById(`inactiveSubjects_${sectionName}`);
                
                // Collect titles from active subjects
                if (activeTable) {
                    const activeRows = activeTable.querySelectorAll('tbody tr:not(.muted)');
                    activeRows.forEach(row => {
                        const titleCell = row.querySelector('td:nth-child(2)');
                        const subjectId = row.querySelector('.edit-subject')?.getAttribute('data-subject-id');
                        
                        if (titleCell && subjectId !== excludeSubjectId) {
                            existingTitles.push(titleCell.textContent.trim().toLowerCase());
                        }
                    });
                }
                
                // Collect titles from inactive subjects
                if (inactiveTable) {
                    const inactiveRows = inactiveTable.querySelectorAll('tbody tr:not(.muted)');
                    inactiveRows.forEach(row => {
                        const titleCell = row.querySelector('td:nth-child(2)');
                        const subjectId = row.querySelector('.edit-subject')?.getAttribute('data-subject-id');
                        
                        if (titleCell && subjectId !== excludeSubjectId) {
                            existingTitles.push(titleCell.textContent.trim().toLowerCase());
                        }
                    });
                }
            });
            
            // Check if the new title already exists (case-insensitive)
            const normalizedTitle = title.toLowerCase();
            const isDuplicate = existingTitles.includes(normalizedTitle);
            
            if (isDuplicate) {
                showFieldError(document.getElementById(inputElement.id === 'subject_name' ? 'subjectNameError' : 'editSubjectNameError'), 'Course Title already exists in the table');
                return false; // Return false to indicate validation failed
            }
            
            return true; // Return true to indicate validation passed
        }

        function validateStatusRealTime(selectElement, errorId) {
            const value = selectElement.value;
            const errorElement = document.getElementById(errorId);
            
            if (!errorElement) return;
            
            // Check if status is selected
            if (!value || value === '') {
                errorElement.textContent = 'Please select course status';
                errorElement.style.display = 'block';
                selectElement.classList.add('error');
            } else {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                selectElement.classList.remove('error');
            }
        }


        // Function to toggle Mid Year semester option visibility based on year level
        function toggleSummerOption(yearLevel, optionId) {
            const summerOption = document.getElementById(optionId);
            if (summerOption) {
                if (yearLevel === '1st Year' || yearLevel === '2nd Year' || yearLevel === '4th Year') {
                    summerOption.style.display = 'none';
                    // If Mid Year was selected, reset to first semester
                    const semesterSelect = summerOption.closest('select');
                    if (semesterSelect && semesterSelect.value === 'summer') {
                        semesterSelect.value = 'first';
                    }
                } else {
                    summerOption.style.display = 'block';
                }
            }
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
        
        // Show custom confirmation modal
        function showConfirmModal(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const confirmMessage = document.getElementById('confirmMessage');
                const confirmYes = document.getElementById('confirmYes');
                const confirmNo = document.getElementById('confirmNo');
                
                // Set the message
                confirmMessage.textContent = message;
                
                // Show the modal
                modal.style.display = 'flex';
                
                // Handle Yes button click
                const handleYes = () => {
                    modal.style.display = 'none';
                    confirmYes.removeEventListener('click', handleYes);
                    confirmNo.removeEventListener('click', handleNo);
                    resolve(true);
                };
                
                // Handle No button click
                const handleNo = () => {
                    modal.style.display = 'none';
                    confirmYes.removeEventListener('click', handleYes);
                    confirmNo.removeEventListener('click', handleNo);
                    resolve(false);
                };
                
                // Add event listeners
                confirmYes.addEventListener('click', handleYes);
                confirmNo.addEventListener('click', handleNo);
                
                // Handle clicking outside modal to close
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        handleNo();
                    }
                });
                
                // Handle escape key
                document.addEventListener('keydown', function handleEscape(e) {
                    if (e.key === 'Escape') {
                        document.removeEventListener('keydown', handleEscape);
                        handleNo();
                    }
                });
            });
        }

        // Show success message
        function showSuccessMessage(message) {
            console.log('showSuccessMessage called with:', message);
            const modal = document.createElement('div');
            modal.className = 'success-modal';
            const uniqueId = 'formSuccessOkBtn_' + Date.now();
            modal.innerHTML = `
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Success!</h3>
                    <p>${message}</p>
                    <button id="${uniqueId}" class="btn btn-primary">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
            console.log('Success modal added to DOM:', modal);
            
            // Add event listener to OK button
            const okButton = document.getElementById(uniqueId);
            if (okButton) {
                okButton.addEventListener('click', function() {
                    if (modal.parentElement) {
                        modal.remove();
                    }
                });
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 5000);
        }
        
        function showErrorMessage(message) {
            const modal = document.createElement('div');
            modal.className = 'error-modal';
            const uniqueId = 'formErrorOkBtn_' + Date.now();
            modal.innerHTML = `
                <div class="error-content">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Error</h3>
                    <p>${message}</p>
                    <button id="${uniqueId}" class="btn btn-danger">OK</button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listener to OK button
            const okButton = document.getElementById(uniqueId);
            if (okButton) {
                okButton.addEventListener('click', function() {
                    if (modal.parentElement) {
                        modal.remove();
                    }
                });
            }
            
            // Auto-remove after 8 seconds
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 8000);
        }

        // Show success modal (for status toggle)
        function showSuccessModal(message) {
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
                    ">Status Updated Successfully</h3>
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
                        Continue
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listener to OK button
            const okButton = modal.querySelector('#successOkBtn');
            if (okButton) {
                okButton.addEventListener('click', function() {
                    modal.remove();
                });
            }
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 4000);
        }

        // Show delete success message with red theme and trash icon
        function showDeleteSuccessMessage(message) {
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
                        <i class="fas fa-trash" style="color: white; font-size: 32px;"></i>
                    </div>
                    <h3 style="
                        margin: 0 0 1rem 0;
                        color: #111827;
                        font-size: 1.5rem;
                        font-weight: 600;
                    ">Subject Deleted</h3>
                    <p style="
                        margin: 0 0 2rem 0;
                        color: #6b7280;
                        line-height: 1.6;
                        font-size: 1.1rem;
                    ">${message}</p>
                    <button id="deleteSuccessOkBtn" style="
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
                    " onmouseover="this.style.background='#c73636'; this.style.transform='translateY(-2px)'" 
                       onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)'">
                        OK
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listener to OK button
            const okButton = modal.querySelector('#deleteSuccessOkBtn');
            if (okButton) {
                okButton.addEventListener('click', function() {
                    modal.remove();
                });
            }
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (modal.parentElement) {
                    modal.remove();
                }
            }, 4000);
        }

        // Function to sort table rows alphabetically by subject code
        function sortTableAlphabetically(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr:not(.muted)'));
            const emptyRow = tbody.querySelector('tr.muted');
            
            // Sort rows by subject code (1st column)
            rows.sort((a, b) => {
                const codeA = a.querySelector('td:nth-child(1)').textContent.trim();
                const codeB = b.querySelector('td:nth-child(1)').textContent.trim();
                return codeA.localeCompare(codeB, undefined, {sensitivity: 'base'});
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

        // Function to sort all section tables alphabetically
        function sortAllSectionTables() {
            const sectionGroups = document.querySelectorAll('.section-group');
            sectionGroups.forEach(sectionGroup => {
                const sectionName = sectionGroup.dataset.section;
                const activeTable = document.getElementById(`activeSubjects_${sectionName}`);
                const inactiveTable = document.getElementById(`inactiveSubjects_${sectionName}`);
                
                if (activeTable) {
                    sortTableAlphabetically(`activeSubjects_${sectionName}`);
                }
                if (inactiveTable) {
                    sortTableAlphabetically(`inactiveSubjects_${sectionName}`);
                }
            });
        }

        // Function to get current section from URL or dropdown
        function getCurrentSection() {
            // First check URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            
            if (sectionParam) {
                return sectionParam;
            }
            
            // If no URL parameter, get selected section from dropdown
            const sectionSelect = document.getElementById('sectionSelect');
            if (sectionSelect && sectionSelect.value) {
                return sectionSelect.value;
            }
            
            return '';
        }

        // Function to filter section groups based on selection
        function filterSectionGroups() {
            const selectedSection = getCurrentSection();
            const sectionGroups = document.querySelectorAll('.section-group');
            
            // If we have a selected section, hide all others
            if (selectedSection) {
                sectionGroups.forEach(sectionGroup => {
                    const sectionName = sectionGroup.dataset.section;
                    if (sectionName !== selectedSection) {
                        sectionGroup.style.display = 'none';
                    } else {
                        sectionGroup.style.display = 'block';
                    }
                });
            } else {
                // If no section is selected, show all sections
                sectionGroups.forEach(sectionGroup => {
                    sectionGroup.style.display = 'block';
                });
            }
        }

        // Function to update section filter UI
        function updateSectionFilterUI() {
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            const sectionSelect = document.getElementById('sectionSelect');
            
            if (sectionSelect) {
                // If section is specified in URL, select it
                if (sectionParam) {
                    // Try to find the option with this value
                    for (let i = 0; i < sectionSelect.options.length; i++) {
                        if (sectionSelect.options[i].value === sectionParam) {
                            sectionSelect.selectedIndex = i;
                            break;
                        }
                    }
                } else {
                    // If no section in URL, select the first valid option
                    // This is handled by the PHP code and the initializeSectionDropdown function
                }
            }
        }
        // Validate form before submission
        function validateFormBeforeSubmit(form) {
            let isValid = true;
            
            
            // Validate section field
            const sectionField = form.querySelector('[name="section"]');
            if (sectionField && sectionField.value === '') {
                const errorId = sectionField.id === 'section' ? 'sectionError' : 'editSectionError';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    errorElement.textContent = 'Please select a section';
                    errorElement.style.display = 'block';
                    sectionField.classList.add('error');
                }
                isValid = false;
            }
            
            // Validate other required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (field.value === '') {
                    const fieldName = field.name || field.id;
                    console.error(`Required field ${fieldName} is empty`);
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        // Handle form submissions
        document.addEventListener('submit', async function(e) {
            if (e.target.id === 'addSubjectForm' || e.target.id === 'editSubjectForm') {
                e.preventDefault();
                
                // Validate required fields before submission
                if (!validateFormBeforeSubmit(e.target)) {
                    return;
                }
                
                // Get submit button and disable it to prevent double submission
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                    submitBtn.style.opacity = '0.7';
                }
                
                const formData = new FormData(e.target);
                const action = e.target.id === 'addSubjectForm' ? 'add_subject' : 'edit_subject';
                formData.append('action', action);
                
                try {
                    const response = await fetch('Courses-dashboard.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Get response text first for debugging
                    const responseText = await response.text();
                    console.log('Response text:', responseText);
                    
                    // Try to parse as JSON
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        console.error('Response text:', responseText);
                        throw new Error('Invalid response format from server');
                    }
                    
                    if (result.success) {
                        // Close modal first
                        const modal = document.getElementById('addSubjectModal');
                        if (modal) {
                            modal.style.display = 'none';
                        }
                        
                        // Show success message
                        console.log('Showing success message:', result.message);
                        showSuccessMessage(result.message);
                        
                        // Dispatch course change event for real-time sync with other dashboards
                        const courseChangeEvent = new CustomEvent('courseStatusChanged', {
                            detail: {
                                action: action,
                                message: result.message,
                                timestamp: new Date().toISOString()
                            }
                        });
                        document.dispatchEvent(courseChangeEvent);
                        
                        // Refresh page after success message
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        // Show error message as modal
                        showErrorMessage(result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (error.message === 'Invalid response format from server') {
                        showErrorMessage('Server returned invalid response format. Please check the console for details.');
                    } else {
                        showErrorMessage(`Error: ${error.message}`);
                    }
                } finally {
                    // Re-enable submit button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        submitBtn.style.opacity = '1';
                    }
                }
            }
        });
        
        // Ensure tables are sorted alphabetically when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Sort all section tables alphabetically by subject code
            sortAllSectionTables();
            
            // Filter section groups based on current selection
            filterSectionGroups();
            
            // Update section filter UI
            updateSectionFilterUI();
            
            // Initialize Mid Year option visibility based on current year level
            const currentYearLevel = '<?php echo $current_year_level; ?>';
            if (currentYearLevel) {
                toggleSummerOption(currentYearLevel, 'summerOption');
                toggleSummerOption(currentYearLevel, 'editSummerOption');
            }
            
            // Initialize dashboard navigation breadcrumb functionality
            initializeDashboardNavigation();
            
            // Initialize year level and semester breadcrumb functionality
            initializeYearLevelBreadcrumb();
            initializeSemesterBreadcrumb();
            
            // Auto-scroll to tables section if user has selected year level and semester
            autoScrollToTables();
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

        // Initialize year level breadcrumb functionality
        function initializeYearLevelBreadcrumb() {
            const yearLevelTabs = document.querySelectorAll('.breadcrumb-tab[href*="year_level"]');
            
            yearLevelTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get the year level from the href
                    const href = this.getAttribute('href');
                    const urlParams = new URLSearchParams(href.split('?')[1]);
                    const yearLevel = urlParams.get('year_level');
                    
                    // Always redirect to first semester when switching year levels
                    const newUrl = `?year_level=${encodeURIComponent(yearLevel)}&semester=first`;
                    
                    // Navigate to the new URL
                    window.location.href = newUrl;
                });
            });
        }

        // Initialize semester breadcrumb functionality
        function initializeSemesterBreadcrumb() {
            const semesterTabs = document.querySelectorAll('.breadcrumb-tab[href*="semester"]');
            
            // Ensure URL always has semester parameter, default to 'first' if missing
            ensureSemesterParameter();
            
            semesterTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all semester tabs
                    semesterTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Get the current year level and selected semester
                    const href = this.getAttribute('href');
                    const urlParams = new URLSearchParams(href.split('?')[1]);
                    const yearLevel = urlParams.get('year_level');
                    const semester = urlParams.get('semester');
                    
                    // Navigate to the selected semester
                    const newUrl = `?year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}`;
                    window.location.href = newUrl;
                });
            });
        }

        // Ensure semester parameter is always present in URL
        function ensureSemesterParameter() {
            const urlParams = new URLSearchParams(window.location.search);
            const yearLevel = urlParams.get('year_level');
            const semester = urlParams.get('semester');
            
            // If year level is specified but no semester, add semester=first
            if (yearLevel && !semester) {
                const newUrl = `?year_level=${encodeURIComponent(yearLevel)}&semester=first`;
                window.history.replaceState({}, '', newUrl);
                
                // Update the active state of semester tabs
                updateSemesterActiveState('first');
            }
        }

        // Update semester breadcrumb active states
        function updateSemesterActiveState(activeSemester) {
            const semesterTabs = document.querySelectorAll('.breadcrumb-tab[href*="semester"]');
            
            semesterTabs.forEach(tab => {
                tab.classList.remove('active');
                const href = tab.getAttribute('href');
                const urlParams = new URLSearchParams(href.split('?')[1]);
                const tabSemester = urlParams.get('semester');
                
                if (tabSemester === activeSemester) {
                    tab.classList.add('active');
                }
            });
        }

        // Auto-scroll to tables section when page loads
        function autoScrollToTables() {
            // Check if user has selected both year level and semester
            const urlParams = new URLSearchParams(window.location.search);
            const yearLevel = urlParams.get('year_level');
            const semester = urlParams.get('semester');
            
            // If both are selected, scroll to tables section
            if (yearLevel && semester) {
                // Wait a bit for the page to fully load
                setTimeout(() => {
                    // Find the first table container
                    const tableContainer = document.querySelector('.table-container');
                    if (tableContainer) {
                        // Smooth scroll to the table container
                        tableContainer.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start',
                            inline: 'nearest'
                        });
                        
                        // Add a subtle highlight effect
                        tableContainer.style.transition = 'box-shadow 0.3s ease';
                        tableContainer.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.3)';
                        
                        // Remove the highlight after a few seconds
                        setTimeout(() => {
                            tableContainer.style.boxShadow = '';
                        }, 2000);
                    }
                }, 500);
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
