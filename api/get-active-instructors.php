<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Query to get all active instructors with their department information
    $database->query("SELECT u.user_id, u.full_name, t.teacher_id, t.department, u.status
                      FROM users u
                      INNER JOIN teachers t ON u.user_id = t.user_id COLLATE utf8mb4_unicode_ci
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
    
    echo json_encode([
        'success' => true,
        'instructors' => $formattedInstructors
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch instructors: ' . $e->getMessage()
    ]);
}
?>
