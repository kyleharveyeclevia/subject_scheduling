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
$status = trim($_POST['status'] ?? '');

// Validate input
if (empty($user_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Validate status
$valid_statuses = ['approved', 'rejected', 'suspended', 'pending'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
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
    
    // Check if user is trying to change their own status
    if ($user_id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot change your own account status']);
        exit();
    }
    
    // Perform the status update
    $result = $user->updateUserStatus($user_id, $status);
    
    // Add appropriate success message based on status
    if ($result['success']) {
        $messages = [
            'approved' => 'User has been approved successfully',
            'rejected' => 'User has been rejected',
            'suspended' => 'User has been suspended',
            'pending' => 'User status has been reset to pending'
        ];
        
        $result['message'] = $messages[$status] ?? 'User status updated successfully';
        
        // Log the action for audit purposes
        error_log("Admin {$_SESSION['user_id']} changed user {$user_id} status to {$status}");
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Update status error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update user status: ' . $e->getMessage()
    ]);
}
?>
