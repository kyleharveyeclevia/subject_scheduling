<?php
// Test current database state
require_once 'config/database.php';

echo "<h1>🔍 Current Database State Test</h1>";

try {
    $database = new Database();
    echo "<p>✅ Database connected</p>";
    
    // Check subjects table structure
    echo "<h3>📋 Subjects Table Structure:</h3>";
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if required columns exist
    $requiredColumns = ['section_id', 'semester', 'academic_year'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✅ All required columns exist!</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
    
    // Check sections table
    echo "<h3>📋 Sections Table Data:</h3>";
    $database->query("SELECT * FROM sections WHERE year_level = '1st Year' AND status = 'available'");
    $database->execute();
    $sections = $database->resultset();
    
    if (empty($sections)) {
        echo "<p style='color: red;'>❌ No 1st Year sections found!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($sections) . " 1st Year sections:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Section ID</th><th>Section Name</th><th>Year Level</th><th>Status</th></tr>";
        foreach ($sections as $section) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($section['section_id']) . "</td>";
            echo "<td>" . htmlspecialchars($section['section_name']) . "</td>";
            echo "<td>" . htmlspecialchars($section['year_level']) . "</td>";
            echo "<td>" . htmlspecialchars($section['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check subjects data
    echo "<h3>📋 Subjects Data (1st Year):</h3>";
    $database->query("SELECT * FROM subjects WHERE year_level = '1st Year' LIMIT 10");
    $database->execute();
    $subjects = $database->resultset();
    
    if (empty($subjects)) {
        echo "<p style='color: red;'>❌ No 1st Year subjects found!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($subjects) . " 1st Year subjects:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        if (!empty($subjects)) {
            echo "<tr>";
            foreach (array_keys($subjects[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            foreach ($subjects as $subject) {
                echo "<tr>";
                foreach ($subject as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    
    // Test the exact query used in courses dashboard
    echo "<h3>🧪 Testing Courses Dashboard Query:</h3>";
    if (!empty($sections)) {
        $section = $sections[0]; // Use first section
        echo "<p>Testing query for section: " . htmlspecialchars($section['section_name']) . " (ID: " . htmlspecialchars($section['section_id']) . ")</p>";
        
        try {
            $database->query("SELECT s.subject_id as id, s.subject_code, s.subject_name, s.units, s.year_level, s.semester, s.academic_year,
                                     CASE WHEN s.status = 'available' THEN 'active' ELSE 'inactive' END as status,
                                     sec.section_name, s.instructor_id, u.full_name as instructor_name, t.department as instructor_department
                              FROM subjects s
                              LEFT JOIN sections sec ON s.section_id = sec.section_id
                              LEFT JOIN teachers t ON s.instructor_id = t.teacher_id COLLATE utf8mb4_unicode_ci
                              LEFT JOIN users u ON t.user_id = u.user_id COLLATE utf8mb4_unicode_ci
                              WHERE s.status = 'available' AND s.year_level = '1st Year' AND s.semester = 'first' AND s.section_id = :section_id
                              ORDER BY s.subject_code");
            $database->bind(':section_id', $section['section_id']);
            $database->execute();
            $result = $database->resultset();
            
            if (empty($result)) {
                echo "<p style='color: orange;'>⚠️ Query returned no results. This explains why courses aren't showing!</p>";
            } else {
                echo "<p style='color: green;'>✅ Query returned " . count($result) . " results!</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🚀 Next Steps:</h3>";
    if (!empty($missingColumns)) {
        echo "<p>1. <a href='simple_fix.php'>Run the database fix script</a> to add missing columns</p>";
    } else {
        echo "<p>1. Database structure looks good!</p>";
    }
    echo "<p>2. <a href='dashboards/admin/Courses-dashboard.php'>Test the Courses Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
h3 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
</style>
