<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Get academic year from request (optional)
    $academic_year = $_GET['academic_year'] ?? '';
    
    if (!empty($academic_year)) {
        // Fetch distinct year levels from sections table for specific academic year
        // Include sections that have subjects for the academic year, or all sections if no subjects exist
        $database->query("SELECT DISTINCT s.year_level 
                         FROM sections s 
                         LEFT JOIN subjects sub ON s.section_id = sub.section_id AND sub.academic_year = :academic_year AND sub.status = 'available'
                         WHERE s.status = 'available' 
                         AND (sub.year_level IS NOT NULL OR NOT EXISTS (SELECT 1 FROM subjects WHERE academic_year = :academic_year AND status = 'available'))
                         ORDER BY 
                            CASE 
                                WHEN s.year_level = '1st Year' THEN 1
                                WHEN s.year_level = '2nd Year' THEN 2
                                WHEN s.year_level = '3rd Year' THEN 3
                                WHEN s.year_level = '4th Year' THEN 4
                                ELSE 5
                            END");
        $database->bind(':academic_year', $academic_year);
    } else {
        // Fetch distinct year levels from sections table (shows all available year levels)
        $database->query("SELECT DISTINCT year_level FROM sections 
                         WHERE status = 'available' 
                         ORDER BY 
                            CASE 
                                WHEN year_level = '1st Year' THEN 1
                                WHEN year_level = '2nd Year' THEN 2
                                WHEN year_level = '3rd Year' THEN 3
                                WHEN year_level = '4th Year' THEN 4
                                ELSE 5
                            END");
    }
    
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
