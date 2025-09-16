<?php
// Web-accessible migration script to fix the Manage Academic Year Course dashboard
require_once 'config/database.php';

// Simple security check
if (!isset($_GET['run']) || $_GET['run'] !== 'migration') {
    echo "To run the migration, add ?run=migration to the URL";
    exit;
}

try {
    $db = new Database();
    
    echo "<h2>🔧 Database Migration for Manage Academic Year Course Dashboard</h2>";
    
    // Step 1: Create academic_years table if it doesn't exist
    echo "<p><strong>1. Creating academic_years table...</strong></p>";
    $db->query("CREATE TABLE IF NOT EXISTS academic_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        academic_year VARCHAR(20) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        is_locked BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $db->execute();
    echo "<p style='color: green;'>✅ academic_years table created/verified</p>";
    
    // Step 2: Insert default academic years if they don't exist
    echo "<p><strong>2. Inserting default academic years...</strong></p>";
    $db->query("INSERT IGNORE INTO academic_years (academic_year, status, is_locked) VALUES
        ('2024-2025', 'active', FALSE),
        ('2025-2026', 'inactive', FALSE),
        ('2026-2027', 'inactive', FALSE)");
    $db->execute();
    echo "<p style='color: green;'>✅ Default academic years inserted</p>";
    
    // Step 3: Add semester column to subjects table if it doesn't exist
    echo "<p><strong>3. Adding semester column to subjects table...</strong></p>";
    try {
        $db->query("ALTER TABLE subjects ADD COLUMN semester VARCHAR(20) DEFAULT 'first' AFTER year_level");
        $db->execute();
        echo "<p style='color: green;'>✅ semester column added</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ️ semester column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Step 4: Add academic_year column to subjects table if it doesn't exist
    echo "<p><strong>4. Adding academic_year column to subjects table...</strong></p>";
    try {
        $db->query("ALTER TABLE subjects ADD COLUMN academic_year VARCHAR(20) DEFAULT '2024-2025' AFTER semester");
        $db->execute();
        echo "<p style='color: green;'>✅ academic_year column added</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ️ academic_year column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Step 5: Update existing subjects to have proper semester and academic_year values
    echo "<p><strong>5. Updating existing subjects with default values...</strong></p>";
    $db->query("UPDATE subjects SET semester = 'first' WHERE semester IS NULL OR semester = ''");
    $db->execute();
    $db->query("UPDATE subjects SET academic_year = '2024-2025' WHERE academic_year IS NULL OR academic_year = ''");
    $db->execute();
    echo "<p style='color: green;'>✅ Existing subjects updated with default values</p>";
    
    // Step 6: Add indexes for better performance
    echo "<p><strong>6. Adding performance indexes...</strong></p>";
    try {
        $db->query("CREATE INDEX idx_subjects_semester ON subjects(semester)");
        $db->execute();
        echo "<p style='color: green;'>✅ semester index added</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ️ semester index already exists</p>";
        }
    }
    
    try {
        $db->query("CREATE INDEX idx_subjects_year_semester ON subjects(year_level, semester)");
        $db->execute();
        echo "<p style='color: green;'>✅ year_semester index added</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ️ year_semester index already exists</p>";
        }
    }
    
    try {
        $db->query("CREATE INDEX idx_subjects_academic_year ON subjects(academic_year)");
        $db->execute();
        echo "<p style='color: green;'>✅ academic_year index added</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ️ academic_year index already exists</p>";
        }
    }
    
    // Step 7: Verify the changes
    echo "<p><strong>7. Verifying database structure...</strong></p>";
    $db->query("DESCRIBE subjects");
    $result = $db->resultset();
    echo "<p>Current subjects table structure:</p><ul>";
    foreach ($result as $row) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Step 8: Check if there are any subjects in the database
    $db->query("SELECT COUNT(*) as total FROM subjects");
    $count = $db->single();
    echo "<p><strong>Total subjects in database:</strong> " . $count['total'] . "</p>";
    
    if ($count['total'] > 0) {
        $db->query("SELECT subject_code, subject_name, year_level, semester, academic_year, status FROM subjects LIMIT 5");
        $subjects = $db->resultset();
        echo "<p><strong>Sample subjects:</strong></p><ul>";
        foreach ($subjects as $subject) {
            echo "<li>" . $subject['subject_code'] . " (" . $subject['subject_name'] . ") - " . 
                 $subject['year_level'] . " " . $subject['semester'] . " " . $subject['academic_year'] . 
                 " [" . $subject['status'] . "]</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3 style='color: green;'>🎉 Migration completed successfully!</h3>";
    echo "<p>The Manage Academic Year Course dashboard should now display data correctly.</p>";
    echo "<p><a href='dashboards/admin/academic-year-dashboard.php'>Go to Academic Year Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
