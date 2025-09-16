<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: id and role']);
    exit;
}

$id = trim($input['id']);
$role = trim($input['role']);

// Validate inputs
if (empty($id) || empty($role)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID and role cannot be empty']);
    exit;
}

if (!in_array($role, ['student', 'teacher'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role. Must be student or teacher']);
    exit;
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    $options = array(
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Check for duplicate ID across ALL user tables (students, teachers, admins)
    $duplicateFound = false;
    $duplicateRole = '';
    
    // Check students table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $duplicateFound = true;
        $duplicateRole = 'Student';
    }
    
    // Check teachers table
    if (!$duplicateFound) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $duplicateFound = true;
            $duplicateRole = 'Teacher';
        }
    }
    
    // Check admins table
    if (!$duplicateFound) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $duplicateFound = true;
            $duplicateRole = 'Admin';
        }
    }
    
    if ($duplicateFound) {
        echo json_encode([
            'isDuplicate' => true,
            'message' => "This ID number is already registered as a {$duplicateRole}. Please use a different ID number."
        ]);
    } else {
        $roleCapitalized = ucfirst($role);
        echo json_encode([
            'isDuplicate' => false,
            'message' => "{$roleCapitalized} ID is available."
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in check-duplicate-id.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'isDuplicate' => false // Default to false to allow registration if DB is down
    ]);
}
?>
