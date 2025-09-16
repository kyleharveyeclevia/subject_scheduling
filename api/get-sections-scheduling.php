<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Get parameters from request
    $year_level = $_GET['year_level'] ?? '';
    $academic_year = $_GET['academic_year'] ?? '';
    
    if (!empty($year_level) && !empty($academic_year)) {
        // Fetch all sections for the specified year level and academic year
        $database->query("SELECT s.section_id, s.section_name, s.year_level 
                         FROM sections s
                         WHERE s.status = 'available' 
                         AND s.year_level = :year_level 
                         ORDER BY s.section_name ASC");
        $database->bind(':year_level', $year_level);
    } elseif (!empty($year_level)) {
        // Fetch all sections for the specified year level
        $database->query("SELECT s.section_id, s.section_name, s.year_level 
                         FROM sections s
                         WHERE s.status = 'available' 
                         AND s.year_level = :year_level 
                         ORDER BY s.section_name ASC");
        $database->bind(':year_level', $year_level);
    } elseif (!empty($academic_year)) {
        // Fetch all sections (academic year filtering not needed for sections)
        $database->query("SELECT s.section_id, s.section_name, s.year_level 
                         FROM sections s
                         WHERE s.status = 'available' 
                         ORDER BY s.year_level, s.section_name ASC");
    } else {
        // Fetch all available sections
        $database->query("SELECT s.section_id, s.section_name, s.year_level 
                         FROM sections s
                         WHERE s.status = 'available' 
                         ORDER BY s.year_level, s.section_name ASC");
    }
    
    $database->execute();
    $sections = $database->resultset();
    
    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch sections: ' . $e->getMessage()
    ]);
}
?>
