<?php
session_start();
require_once __DIR__ . '/../../classes/User.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$user_id = $_POST['user_id'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$email = '';

// Validate basic input
if (empty($user_id) || empty($full_name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID and full name are required']);
    exit();
}

// Validate full name (letters, spaces, dots, apostrophes, and hyphens only)
if (!preg_match('/^[a-zA-Z\s.\'-]+$/', $full_name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Full name must contain only letters, spaces, dots, apostrophes, and hyphens']);
    exit();
}

// Email validation removed

// Prepare update data
$update_data = [
    'full_name' => $full_name
];

// Add role-specific data
$department = $_POST['department'] ?? '';
$year_level = $_POST['year_level'] ?? '';
$section = $_POST['section'] ?? '';

if (!empty($department)) {
    $update_data['department'] = $department;
}

if (!empty($year_level)) {
    $update_data['year_level'] = $year_level;
}

if (!empty($section)) {
    $update_data['section'] = $section;
}

header('Content-Type: application/json');

try {
    $user = new User();
    $result = $user->updateUser($user_id, $update_data);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Update user error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update user: ' . $e->getMessage()
    ]);
}
?>
