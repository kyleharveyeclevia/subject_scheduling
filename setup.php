<?php
// Database Setup Script for Subject Scheduling System
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Subject Scheduling System - Database Setup</h1>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'subject_scheduling';

try {
    // Connect to MySQL server (without selecting database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "<p>✓ Database '$database' created/verified</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the schema file
    $schema = file_get_contents('database/schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|\/\*|CREATE DATABASE|USE)/', $statement)) {
            // Skip the placeholder admin insert - we'll do it properly below
            if (strpos($statement, 'PLACEHOLDER_HASH') !== false) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors for statements that might already exist
                if (!strpos($e->getMessage(), 'already exists')) {
                    echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p>✓ Database schema created successfully</p>";
    
    // Define the user ID that you want to insert.
    // Original value: 22120091
    $adminUserID = "admin";
    $adminRawPassword = "admin";
    $adminEmail = 'admin2@gmail.com';
    // Check if default admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = '$adminUserID'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    
    if (!$adminExists) {
        // Create default admin account
        $adminPassword = password_hash($adminRawPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adminUserID, 'System Administrator', $adminEmail, $adminPassword, 'admin', 'approved', 1]);
        
        $stmt = $pdo->prepare("INSERT INTO admins (user_id, admin_id) VALUES (?, ?)");
        $stmt->execute([$adminUserID, $adminUserID]);
        
        if($adminUserID != "22120091"){
            echo "<p>✓ Admin account created</p>";
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3> Admin Credentials:</h3>";
            echo "<p><strong>ID:</strong> $adminUserID</p>";
            echo "<p><strong>Password:</strong> $adminRawPassword</p>";
            echo "<p><strong>Email:</strong> $adminEmail</p>";
            echo "</div>";
        }

        else{
            echo "<p>✓ Default admin account created</p>";
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>Default Admin Credentials:</h3>";
            echo "<p><strong>ID:</strong> 22120091</p>";
            echo "<p><strong>Password:</strong> @Admin1899</p>";
            echo "<p><strong>Email:</strong> admin@gmail.com</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p>✓ Default admin account already exists</p>";
    }
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>Your Subject Scheduling System is now ready to use.</p>";
    echo "<p><a href='index.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #ffe8e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Subject Scheduling System</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Content is generated by PHP above -->
</body>
</html>
