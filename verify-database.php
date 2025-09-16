<?php
// Simple database verification script
echo "<h1>Database Verification</h1>";

try {
    // Test database connection
    $host = 'localhost';
    $dbname = 'subject_scheduling';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['users', 'teachers', 'students', 'admins'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<details>";
            echo "<summary>Table '$table' structure</summary>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</details>";
            
            // Show sample data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>📊 Records in '$table': {$count['count']}</p>";
            
            if ($count['count'] > 0) {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<details>";
                echo "<summary>Sample data from '$table'</summary>";
                echo "<pre>" . print_r($sample, true) . "</pre>";
                echo "</details>";
            }
            
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    }
    
    // Check users table specifically
    echo "<h3>Users Table Analysis:</h3>";
    
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($status_counts)) {
        echo "<p>User status distribution:</p>";
        foreach ($status_counts as $status) {
            $color = $status['status'] === 'pending' ? 'orange' : ($status['status'] === 'approved' ? 'green' : 'red');
            echo "<p style='color: $color;'>• {$status['status']}: {$status['count']}</p>";
        }
    } else {
        echo "<p>No users found in the system</p>";
    }
    
    // Check recent registrations
    $stmt = $pdo->query("SELECT u.user_id, u.full_name, u.role, u.status, u.created_at,
                                CASE 
                                    WHEN u.role = 'teacher' THEN t.teacher_id
                                    WHEN u.role = 'student' THEN s.student_id
                                    ELSE NULL
                                END as role_id
                         FROM users u
                         LEFT JOIN teachers t ON u.user_id = t.user_id
                         LEFT JOIN students s ON u.user_id = s.user_id
                         WHERE u.role != 'admin'
                         ORDER BY u.created_at DESC
                         LIMIT 5");
    
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recent_users)) {
        echo "<h3>Recent Registrations:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>User ID</th><th>Name</th><th>Role</th><th>Role ID</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($recent_users as $user) {
            $status_color = $user['status'] === 'pending' ? 'orange' : ($user['status'] === 'approved' ? 'green' : 'red');
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['role_id']}</td>";
            echo "<td style='color: $status_color; font-weight: bold;'>{$user['status']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Verification Complete</h3>";
echo "<p>Check the results above to identify any database issues.</p>";
?>
