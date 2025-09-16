<?php
echo "<h1>Super Simple Database Test</h1>";

// Test 1: Basic PHP
echo "<p>✅ PHP is working</p>";

// Test 2: Session
session_start();
echo "<p>✅ Session started</p>";

// Test 3: Check if config file exists
$configPath = '../../config/database.php';
if (file_exists($configPath)) {
    echo "<p>✅ Config file exists at: $configPath</p>";
} else {
    echo "<p>❌ Config file NOT found at: $configPath</p>";
    echo "<p>Current directory: " . __DIR__ . "</p>";
    echo "<p>Files in current directory:</p><ul>";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Test 4: Try to include config
try {
    require_once $configPath;
    echo "<p>✅ Config file included successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error including config: " . $e->getMessage() . "</p>";
}

// Test 5: Try to create Database object
try {
    $db = new Database();
    echo "<p>✅ Database object created</p>";
} catch (Exception $e) {
    echo "<p>❌ Error creating Database object: " . $e->getMessage() . "</p>";
}

// Test 6: Try basic connection
try {
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Test 7: Try simple query
    $db->query("SELECT 1 as test");
    $result = $db->single();
    echo "<p>✅ Basic query works: " . $result['test'] . "</p>";
    
    // Test 8: Check if subjects table exists
    $db->query("SHOW TABLES LIKE 'subjects'");
    $tables = $db->resultset();
    if (count($tables) > 0) {
        echo "<p>✅ Subjects table exists</p>";
        
        // Test 9: Count subjects
        $db->query("SELECT COUNT(*) as total FROM subjects");
        $count = $db->single();
        echo "<p>✅ Total subjects: " . $count['total'] . "</p>";
        
        if ($count['total'] > 0) {
            // Test 10: Get sample subjects
            $db->query("SELECT * FROM subjects LIMIT 3");
            $subjects = $db->resultset();
            echo "<p>✅ Sample subjects:</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year</th><th>Semester</th><th>Status</th></tr>";
            foreach ($subjects as $subject) {
                echo "<tr>";
                echo "<td>" . $subject['subject_id'] . "</td>";
                echo "<td>" . $subject['subject_code'] . "</td>";
                echo "<td>" . $subject['subject_name'] . "</td>";
                echo "<td>" . $subject['year_level'] . "</td>";
                echo "<td>" . $subject['semester'] . "</td>";
                echo "<td>" . $subject['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ Subjects table exists but is empty</p>";
        }
        
    } else {
        echo "<p>❌ Subjects table does NOT exist</p>";
        
        // Show what tables do exist
        $db->query("SHOW TABLES");
        $allTables = $db->resultset();
        echo "<p>Available tables:</p><ul>";
        foreach ($allTables as $table) {
            $tableName = array_values($table)[0];
            echo "<li>$tableName</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo "<p><strong>Summary:</strong> This test shows exactly what's working and what's not.</p>";
?>
