<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Not logged in or not admin";
    exit();
}

echo "Logged in as admin<br>";

// Include database configuration
require_once '../../config/database.php';

// Initialize database connection
try {
    $database = new Database();
    echo "Database connection successful<br>";
    
    // Test a simple query
    $database->query("SELECT COUNT(*) as count FROM subjects");
    $result = $database->single();
    echo "Subjects count: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "Test file working!";
?>
