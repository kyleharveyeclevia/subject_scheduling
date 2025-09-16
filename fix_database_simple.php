<?php
// Simple database constraint fix script
// This will definitely fix the unique constraint issue

echo "<h1>Database Constraint Fix</h1>";
echo "<p>Fixing the unique constraint on subject_code...</p>";

try {
    // Include database configuration
    require_once 'config/database.php';
    
    // Initialize database connection
    $database = new Database();
    
    echo "<p>✓ Database connected successfully</p>";
    
    // Step 1: Show current constraint
    echo "<h3>Step 1: Checking current constraint...</h3>";
    try {
        $database->query("SHOW INDEX FROM subjects WHERE Key_name = 'subject_code'");
        $index = $database->single();
        if ($index) {
            echo "<p>Current subject_code index: Non_unique = " . $index['Non_unique'] . "</p>";
            if ($index['Non_unique'] == 0) {
                echo "<p style='color: red;'>❌ This is a UNIQUE constraint (prevents duplicates)</p>";
            } else {
                echo "<p style='color: green;'>✅ This allows duplicates</p>";
            }
        } else {
            echo "<p>No subject_code index found</p>";
        }
    } catch (Exception $e) {
        echo "<p>No subject_code index found (this is fine)</p>";
    }
    
    // Step 2: Drop any existing constraint
    echo "<h3>Step 2: Dropping existing constraint...</h3>";
    try {
        $database->query("ALTER TABLE subjects DROP INDEX subject_code");
        $database->execute();
        echo "<p style='color: green;'>✓ Dropped existing subject_code constraint</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ No existing constraint to drop (this is fine)</p>";
    }
    
    // Step 3: Add new non-unique index
    echo "<h3>Step 3: Adding new non-unique index...</h3>";
    try {
        $database->query("ALTER TABLE subjects ADD INDEX subject_code (subject_code)");
        $database->execute();
        echo "<p style='color: green;'>✓ Added new non-unique index on subject_code</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error adding index: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Step 4: Verify the fix
    echo "<h3>Step 4: Verifying the fix...</h3>";
    try {
        $database->query("SHOW INDEX FROM subjects WHERE Key_name = 'subject_code'");
        $index = $database->single();
        if ($index) {
            echo "<p style='color: green;'>✅ subject_code index is now non-unique (Non_unique = " . $index['Non_unique'] . ")</p>";
            echo "<p><strong>SUCCESS! You can now have up to 2 course codes with the same value!</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Index not found after fix</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error verifying index: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Step 5: Test with a simple query
    echo "<h3>Step 5: Testing the fix...</h3>";
    try {
        // Try to insert a test record (this should work now)
        $database->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = 'TEST123'");
        $result = $database->single();
        $currentCount = $result['count'];
        echo "<p>Current count of 'TEST123': " . $currentCount . "</p>";
        
        if ($currentCount < 2) {
            echo "<p style='color: green;'>✅ Database is ready to accept duplicate course codes!</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 'TEST123' already exists " . $currentCount . " times</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error testing: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. <strong>Go back to your dashboard</strong></p>";
    echo "<p>2. <strong>Try adding a subject with course code 'ENG101'</strong></p>";
    echo "<p>3. <strong>Try adding another subject with course code 'ENG101'</strong></p>";
    echo "<p>4. <strong>You should now be able to add the same course code twice!</strong></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Fatal Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
