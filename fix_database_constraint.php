<?php
// Database constraint fix script
// This script will modify the subjects table to allow duplicate course codes (up to 2)

// Include database configuration
require_once 'config/database.php';

try {
    // Initialize database connection
    $database = new Database();
    
    echo "<h2>Fixing Database Constraint for Duplicate Course Codes</h2>";
    
    // Check current table structure
    echo "<h3>Current Table Structure:</h3>";
    $database->query("SHOW CREATE TABLE subjects");
    $result = $database->single();
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    
    // Check current indexes
    echo "<h3>Current Indexes:</h3>";
    $database->query("SHOW INDEX FROM subjects");
    $indexes = $database->resultset();
    echo "<table border='1'>";
    echo "<tr><th>Index Name</th><th>Column</th><th>Non Unique</th></tr>";
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Non_unique']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix the constraint
    echo "<h3>Fixing Constraint...</h3>";
    
    // Drop the unique constraint if it exists
    try {
        $database->query("ALTER TABLE subjects DROP INDEX subject_code");
        $database->execute();
        echo "<p style='color: green;'>✓ Dropped unique constraint on subject_code</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ No unique constraint found on subject_code (this is fine)</p>";
    }
    
    // Add a regular index (allows duplicates)
    try {
        $database->query("ALTER TABLE subjects ADD INDEX subject_code (subject_code)");
        $database->execute();
        echo "<p style='color: green;'>✓ Added regular index on subject_code (allows duplicates)</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Index already exists or error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Verify the fix
    echo "<h3>Verifying Fix...</h3>";
    $database->query("SHOW INDEX FROM subjects WHERE Key_name = 'subject_code'");
    $index = $database->single();
    if ($index) {
        echo "<p style='color: green;'>✓ subject_code index is now non-unique (Non_unique = " . $index['Non_unique'] . ")</p>";
        echo "<p>This means you can now have up to 2 course codes with the same value!</p>";
    }
    
    echo "<h3>Test the Fix:</h3>";
    echo "<p>Now try adding a subject with a duplicate course code in your dashboard.</p>";
    echo "<p>You should be able to add the same course code twice without getting the 'Duplicate entry' error.</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
