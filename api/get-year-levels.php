<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Fetch distinct year levels from subjects table
    $database->query("SELECT DISTINCT year_level FROM subjects WHERE status = 'available' ORDER BY 
        CASE 
            WHEN year_level = '1st Year' THEN 1
            WHEN year_level = '2nd Year' THEN 2
            WHEN year_level = '3rd Year' THEN 3
            WHEN year_level = '4th Year' THEN 4
            ELSE 5
        END");
    $database->execute();
    $yearLevels = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'year_levels' => $yearLevels
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch year levels: ' . $e->getMessage()
    ]);
}
?>
