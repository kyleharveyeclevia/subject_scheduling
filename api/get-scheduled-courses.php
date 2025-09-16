<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Initialize database connection
$database = new Database();

// Get parameters
$section_id = isset($_GET['section_id']) ? $_GET['section_id'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Validate parameters
if (empty($section_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Section ID is required']);
    exit();
}

try {
    // Get section name from section_id
    $database->query("SELECT section_name FROM sections WHERE section_id = :section_id");
    $database->bind(':section_id', $section_id);
    $database->execute();
    $section = $database->single();
    
    if (!$section) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Section not found']);
        exit();
    }
    
    $section_name = $section['section_name'];
    
    // For now, we'll return an empty array since the current system uses localStorage
    // In a future enhancement, this could be modified to read from a database table
    // that stores the actual schedule data
    
    // Get scheduled courses from localStorage data (this would need to be passed from frontend)
    // For now, we'll return an empty array and let the frontend handle the filtering
    $scheduled_courses = [];
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode([
        'success' => true,
        'scheduled_courses' => $scheduled_courses,
        'section_name' => $section_name,
        'count' => count($scheduled_courses)
    ]);
    
} catch (Exception $e) {
    error_log("Database error in get-scheduled-courses.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'scheduled_courses' => []
    ]);
}
?>
