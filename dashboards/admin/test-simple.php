<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    
    // Test 1: Basic connection
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'test1' => 'Connection OK'
    ]);
    
    // Test 2: Count subjects
    $database->query("SELECT COUNT(*) as total FROM subjects");
    $totalSubjects = $database->single()['total'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Subjects count retrieved',
        'test2' => 'Count OK',
        'total_subjects' => $totalSubjects
    ]);
    
    // Test 3: Get all subjects (first 5)
    $database->query("SELECT subject_id, subject_code, subject_name, year_level, semester, status FROM subjects LIMIT 5");
    $subjects = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sample subjects retrieved',
        'test3' => 'Sample OK',
        'sample_subjects' => $subjects
    ]);
    
    // Test 4: Check year levels
    $database->query("SELECT DISTINCT year_level FROM subjects ORDER BY year_level");
    $yearLevels = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'message' => 'Year levels retrieved',
        'test4' => 'Year levels OK',
        'year_levels' => array_column($yearLevels, 'year_level')
    ]);
    
    // Test 5: Check semesters
    $database->query("SELECT DISTINCT semester FROM subjects ORDER BY semester");
    $semesters = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'message' => 'Semesters retrieved',
        'test5' => 'Semesters OK',
        'semesters' => array_column($semesters, 'semester')
    ]);
    
    // Test 6: Check statuses
    $database->query("SELECT DISTINCT status FROM subjects ORDER BY status");
    $statuses = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'message' => 'Statuses retrieved',
        'test6' => 'Statuses OK',
        'statuses' => array_column($statuses, 'status')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>
