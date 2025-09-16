<?php
session_start();
require_once __DIR__ . '/../../classes/User.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get user ID from query parameter
$user_id = $_GET['user_id'] ?? '';

// Validate input
if (empty($user_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

header('Content-Type: application/json');

try {
    $user = new User();
    $user_data = $user->getUserById($user_id);
    
    if ($user_data) {
        echo json_encode([
            'success' => true, 
            'data' => $user_data
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'User not found'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get user error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load user data: ' . $e->getMessage()
    ]);
}
?>
