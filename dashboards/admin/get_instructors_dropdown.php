<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../../config/database.php';

// Initialize database connection
$database = new Database();

try {
    // Query to get all active instructors with their department information
    $database->query("SELECT u.user_id, u.full_name, t.teacher_id, t.department, u.status
                      FROM users u
                      INNER JOIN teachers t ON u.user_id = t.user_id
                      WHERE u.role = 'teacher' AND u.status = 'approved'
                      ORDER BY t.department ASC, u.full_name ASC");
    
    $instructors = $database->resultset();
    
    // Format the data
    $formattedInstructors = [];
    foreach ($instructors as $instructor) {
        $formattedInstructors[] = [
            'user_id' => $instructor['user_id'],
            'teacher_id' => $instructor['teacher_id'],
            'full_name' => $instructor['full_name'],
            'department' => $instructor['department']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'instructors' => $formattedInstructors
    ]);
    
} catch (Exception $e) {
    error_log("Database error in get_instructors_dropdown.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'instructors' => []
    ]);
}
?>
