<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

// Get POST data
$role = $_POST['role'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$email = '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Debug logging
error_log("Registration request received:");
error_log("Role: " . $role);
error_log("Full name: " . $full_name);
error_log("Email: " . $email);
error_log("Password length: " . strlen($password));
error_log("Confirm password length: " . strlen($confirm_password));
error_log("All POST data: " . print_r($_POST, true));

// Validate basic fields
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Full name is required';
}

if (empty($password)) {
    $errors[] = 'Password is required';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Validate full name (letters, spaces, and common name characters)
if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $full_name)) {
    $errors[] = 'Full name must contain only letters, spaces, and common name characters (apostrophes, hyphens, periods)';
}

// Validate password strength
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
}

// Role-specific validation
$data = [
    'role' => $role,
    'full_name' => $full_name,
    'password' => $password
];

switch ($role) {
    case 'teacher':
        $teacher_id = trim($_POST['teacher_id'] ?? '');
        $department = $_POST['department'] ?? '';
        
        if (empty($teacher_id)) {
            $errors[] = 'Instructor ID is required';
        }
        
        if (!preg_match('/^[A-Z]{2}\d+$|^\d+$/', $teacher_id)) {
            $errors[] = 'Instructor ID must contain only numbers, or exactly 2 capital letters followed by numbers';
        }
        
        if (empty($department)) {
            $errors[] = 'Department is required';
        }
        
        $data['teacher_id'] = $teacher_id;
        $data['department'] = $department;
        break;
        
    case 'student':
        $student_id = trim($_POST['student_id'] ?? '');
        $year_level = $_POST['year_level'] ?? '';
        $section = $_POST['section'] ?? '';
        $year_section = $_POST['year_section'] ?? '';
        
        // If year_level and section are empty but year_section is provided, extract them
        if ((empty($year_level) || empty($section)) && !empty($year_section)) {
            $parts = explode('_', $year_section);
            if (count($parts) === 2) {
                $year_level = $parts[0]; // "First Year", "Second Year", etc.
                $section = $parts[1];     // "A", "B", etc.
            }
        }
        
        if (empty($student_id)) {
            $errors[] = 'Student ID is required';
        }
        
        if (!preg_match('/^\d{2}-\d-\d-\d{4}$/', $student_id)) {
            $errors[] = 'Student ID must be in format: xx-x-x-xxxx';
        }
        
        if (empty($year_level)) {
            $errors[] = 'Year level is required';
        }
        
        if (empty($section)) {
            $errors[] = 'Section is required';
        }
        
        $data['student_id'] = $student_id;
        $data['year_level'] = $year_level;
        $data['section'] = $section;
        break;
        
    default:
        $errors[] = 'Invalid role selected';
}

// If there are validation errors, return JSON error response
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit();
}

try {
    error_log("Calling User::register with data: " . print_r($data, true));
    
    $user = new User();
    $result = $user->register($data);
    
    error_log("Registration result: " . print_r($result, true));
    
    header('Content-Type: application/json');
    // Force a consistent success message guiding next steps
    if ($result['success']) {
        $result['message'] = 'Registration successful. Your account is pending admin approval. You will be able to login once approved.';
    }
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Exception during registration: " . $e->getMessage());
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>
