<?php
session_start();
require_once __DIR__ . '/../../classes/User.php';

// Set JSON header first
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$user_id = trim($_POST['user_id'] ?? '');

// Validate input
if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Validate user_id format (should be alphanumeric)
if (!preg_match('/^[A-Za-z0-9]+$/', $user_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID format']);
    exit();
}

try {
    $user = new User();
    
    // First, verify the user exists
    $existing_user = $user->getUserById($user_id);
    if (!$existing_user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Check if user is trying to delete themselves
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit();
    }
    
    // Check if user is an admin (prevent deleting other admins)
    if ($existing_user['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete admin accounts']);
        exit();
    }
    
    // Perform the deletion
    $result = $user->deleteUser($user_id);
    
    // Log the action for audit purposes
    if ($result['success']) {
        error_log("Admin {$_SESSION['user_id']} deleted user {$user_id} ({$existing_user['full_name']})");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}
?>
