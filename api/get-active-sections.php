<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Get year level from request (optional)
    $year_level = $_GET['year_level'] ?? '';
    
    if (!empty($year_level)) {
        // Fetch active sections for the specified year level
        $database->query("SELECT section_id, section_name, year_level FROM sections WHERE status = 'available' AND year_level = :year_level ORDER BY section_name ASC");
        $database->bind(':year_level', $year_level);
    } else {
        // Fetch all active sections
        $database->query("SELECT section_id, section_name, year_level FROM sections WHERE status = 'available' ORDER BY section_name ASC");
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
