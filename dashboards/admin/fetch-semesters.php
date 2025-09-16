Are you absolutely sure you want to proceed?<?php
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
if (!isset($_POST['action']) || $_POST['action'] !== 'fetch_semesters') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Get year level parameter
$year_level = $_POST['year_level'] ?? '';

// Debug: Log the request
error_log("fetch-semesters.php: Request received - year_level: " . $year_level);

try {
    $db = new Database();
    
    // Debug: Log that we're starting the process
    error_log("fetch-semesters.php: Starting semester fetch process");
    
    // Build query to get all available semesters for the specified year level
    $query = "SELECT DISTINCT s.semester 
              FROM subjects s 
              WHERE s.status = 'active' 
                AND s.year_level = :year_level 
                AND s.semester IS NOT NULL 
                AND s.semester != ''";
    
    error_log("fetch-semesters.php: Executing query: " . $query);
    
    $db->query($query);
    $db->bind(':year_level', $year_level);
    $db->execute();
    $result = $db->resultset();
    
    error_log("fetch-semesters.php: Query result count: " . count($result));
    
    $semesters = [];
    if (!empty($result)) {
        error_log("fetch-semesters.php: Successfully found " . count($result) . " semesters");
        foreach ($result as $row) {
            $semesters[] = $row['semester'];
        }
        
        // Sort semesters in logical order
        usort($semesters, function($a, $b) {
            $order = ['1st Semester' => 1, '2nd Semester' => 2, 'Summer' => 3];
            return ($order[$a] ?? 999) - ($order[$b] ?? 999);
        });
        
    } else {
        error_log("fetch-semesters.php: No semesters found for year level: " . $year_level);
        // Return default semesters if none found in database
        $semesters = ['1st Semester', '2nd Semester', 'Summer'];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'semesters' => $semesters,
        'count' => count($semesters),
        'year_level' => $year_level,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    error_log("Error fetching semesters: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
