<?php
// Debug script to check why the Manage Academic Year Course dashboard is not displaying data
require_once 'config/database.php';

echo "<h2>🔍 Debug: Manage Academic Year Course Dashboard Data</h2>";

try {
    $db = new Database();
    
    // Check database connection
    echo "<h3>1. Database Connection</h3>";
    $db->query("SELECT 1");
    $db->execute();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if subjects table exists and its structure
    echo "<h3>2. Subjects Table Structure</h3>";
    $db->query("DESCRIBE subjects");
    $result = $db->resultset();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($result as $row) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if academic_years table exists
    echo "<h3>3. Academic Years Table</h3>";
    $db->query("SHOW TABLES LIKE 'academic_years'");
    $tables = $db->resultset();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ academic_years table exists</p>";
        $db->query("SELECT * FROM academic_years");
        $years = $db->resultset();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Academic Year</th><th>Status</th><th>Is Locked</th></tr>";
        foreach ($years as $year) {
            echo "<tr>";
            echo "<td>" . $year['id'] . "</td>";
            echo "<td>" . $year['academic_year'] . "</td>";
            echo "<td>" . $year['status'] . "</td>";
            echo "<td>" . ($year['is_locked'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ academic_years table does NOT exist</p>";
    }
    
    // Check total subjects count
    echo "<h3>4. Subjects Count</h3>";
    $db->query("SELECT COUNT(*) as total FROM subjects");
    $count = $db->single();
    echo "<p><strong>Total subjects in database:</strong> " . $count['total'] . "</p>";
    
    if ($count['total'] > 0) {
        // Show all subjects
        echo "<h3>5. All Subjects in Database</h3>";
        $db->query("SELECT * FROM subjects ORDER BY year_level, subject_code");
        $subjects = $db->resultset();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Units</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
        foreach ($subjects as $subject) {
            echo "<tr>";
            echo "<td>" . $subject['subject_id'] . "</td>";
            echo "<td>" . $subject['subject_code'] . "</td>";
            echo "<td>" . $subject['subject_name'] . "</td>";
            echo "<td>" . $subject['units'] . "</td>";
            echo "<td>" . $subject['year_level'] . "</td>";
            echo "<td>" . (isset($subject['semester']) ? $subject['semester'] : 'NULL') . "</td>";
            echo "<td>" . (isset($subject['academic_year']) ? $subject['academic_year'] : 'NULL') . "</td>";
            echo "<td>" . $subject['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test the exact query used in the dashboard
        echo "<h3>6. Dashboard Query Test</h3>";
        $current_year_level = '1st Year';
        $current_semester = 'first';
        $current_academic_year = '2024-2025';
        
        echo "<p><strong>Testing with:</strong></p>";
        echo "<ul>";
        echo "<li>Year Level: " . $current_year_level . "</li>";
        echo "<li>Semester: " . $current_semester . "</li>";
        echo "<li>Academic Year: " . $current_academic_year . "</li>";
        echo "</ul>";
        
        // Test active subjects query
        echo "<h4>Active Subjects Query:</h4>";
        try {
            $db->query("SELECT subject_id as id, subject_code, subject_name, units, year_level, semester, academic_year,
                                 CASE WHEN status = 'available' THEN 'active' ELSE 'inactive' END as status 
                          FROM subjects 
                          WHERE status = 'available' AND year_level = :year_level AND semester = :semester AND academic_year = :academic_year
                          ORDER BY subject_code");
            $db->bind(':year_level', $current_year_level);
            $db->bind(':semester', $current_semester);
            $db->bind(':academic_year', $current_academic_year);
            $activeSubjects = $db->resultset();
            
            echo "<p style='color: green;'>✅ Active subjects query successful</p>";
            echo "<p><strong>Active subjects found:</strong> " . count($activeSubjects) . "</p>";
            
            if (count($activeSubjects) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Units</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
                foreach ($activeSubjects as $subject) {
                    echo "<tr>";
                    echo "<td>" . $subject['id'] . "</td>";
                    echo "<td>" . $subject['subject_code'] . "</td>";
                    echo "<td>" . $subject['subject_name'] . "</td>";
                    echo "<td>" . $subject['units'] . "</td>";
                    echo "<td>" . $subject['year_level'] . "</td>";
                    echo "<td>" . $subject['semester'] . "</td>";
                    echo "<td>" . $subject['academic_year'] . "</td>";
                    echo "<td>" . $subject['status'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ No active subjects found with the current filters</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Active subjects query failed: " . $e->getMessage() . "</p>";
        }
        
        // Test inactive subjects query
        echo "<h4>Inactive Subjects Query:</h4>";
        try {
            $db->query("SELECT subject_id as id, subject_code, subject_name, units, year_level, semester, academic_year,
                                 CASE WHEN status = 'unavailable' THEN 'inactive' ELSE 'active' END as status 
                          FROM subjects 
                          WHERE status = 'unavailable' AND year_level = :year_level AND semester = :semester AND academic_year = :academic_year
                          ORDER BY subject_code");
            $db->bind(':year_level', $current_year_level);
            $db->bind(':semester', $current_semester);
            $db->bind(':academic_year', $current_academic_year);
            $inactiveSubjects = $db->resultset();
            
            echo "<p style='color: green;'>✅ Inactive subjects query successful</p>";
            echo "<p><strong>Inactive subjects found:</strong> " . count($inactiveSubjects) . "</p>";
            
            if (count($inactiveSubjects) > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Units</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
                foreach ($inactiveSubjects as $subject) {
                    echo "<tr>";
                    echo "<td>" . $subject['id'] . "</td>";
                    echo "<td>" . $subject['subject_code'] . "</td>";
                    echo "<td>" . $subject['subject_name'] . "</td>";
                    echo "<td>" . $subject['units'] . "</td>";
                    echo "<td>" . $subject['year_level'] . "</td>";
                    echo "<td>" . $subject['semester'] . "</td>";
                    echo "<td>" . $subject['academic_year'] . "</td>";
                    echo "<td>" . $subject['status'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ No inactive subjects found with the current filters</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Inactive subjects query failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No subjects found in database</p>";
    }
    
    echo "<h3>7. Recommendations</h3>";
    echo "<ul>";
    echo "<li>If the academic_years table doesn't exist, run the migration: <a href='run_migration_web.php?run=migration'>Run Migration</a></li>";
    echo "<li>If semester/academic_year columns are missing, run the migration</li>";
    echo "<li>If no subjects are found, check if the database was properly initialized</li>";
    echo "<li>If subjects exist but queries return 0 results, check the filter values</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and configuration.</p>";
}
?>
