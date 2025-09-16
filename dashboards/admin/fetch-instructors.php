<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Ensure required classes are loaded
require_once __DIR__ . '/../../config/database.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if action is specified
if (!isset($_POST['action']) || $_POST['action'] !== 'fetch_instructors') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Debug: Log the request
error_log("fetch-instructors.php: Request received - action: " . ($_POST['action'] ?? 'none'));

try {
    $db = new Database();
    
    // Debug: Log that we're starting the process
    error_log("fetch-instructors.php: Starting instructor fetch process");
    
    // Use the EXACT same query as the instructors dashboard
    error_log("fetch-instructors.php: Using instructors dashboard query");
    $db->query("SELECT u.user_id, u.full_name, t.teacher_id, t.department, u.status
                FROM users u
                INNER JOIN teachers t ON u.user_id = t.user_id
                WHERE u.role = 'teacher' AND u.status = 'approved'
                ORDER BY t.department ASC, u.full_name ASC");
    $db->execute();
    $result = $db->resultset();
    error_log("fetch-instructors.php: Query result count: " . count($result));
    
    $instructors = [];
    if (!empty($result)) {
        error_log("fetch-instructors.php: Successfully found " . count($result) . " instructors");
        
        $currentDepartment = '';
        foreach ($result as $row) {
            // Add department separator if department changes
            if ($currentDepartment !== $row['department']) {
                $currentDepartment = $row['department'];
                $instructors[] = [
                    'id' => 'separator_' . $currentDepartment,
                    'full_name' => '--- ' . $currentDepartment . ' ---',
                    'specialization' => 'separator',
                    'is_separator' => true
                ];
            }
            
            // Add the instructor
            $instructors[] = [
                'id' => $row['user_id'],
                'full_name' => $row['full_name'],
                'specialization' => $row['department'],
                'is_separator' => false
            ];
        }
    } else {
        error_log("fetch-instructors.php: No instructors found with the dashboard query");
    }
    
    error_log("fetch-instructors.php: Final instructor count: " . count($instructors));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'instructors' => $instructors,
        'count' => count($instructors),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    error_log("Error fetching instructors: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
