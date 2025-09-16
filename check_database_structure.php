<?php
require_once 'config/database.php';

echo "<h2>🔍 Database Structure Check</h2>";

try {
    $database = new Database();
    
    echo "<h3>📊 Current Database:</h3>";
    $database->query("SELECT DATABASE() as current_db");
    $database->execute();
    $result = $database->single();
    echo "<p><strong>Current Database:</strong> " . ($result['current_db'] ?? 'None selected') . "</p>";
    
    echo "<h3>📋 All Tables:</h3>";
    $database->query("SHOW TABLES");
    $database->execute();
    $tables = $database->resultset();
    
    if (empty($tables)) {
        echo "<p>❌ No tables found in the database.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li><strong>$tableName</strong></li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>🔍 Checking for 'subjects' table:</h3>";
    if (!empty($tables)) {
        $database->query("SHOW TABLES LIKE 'subjects'");
        $database->execute();
        $subjectsTable = $database->resultset();
        
        if (empty($subjectsTable)) {
            echo "<p>❌ 'subjects' table does not exist.</p>";
            
            // Check for similar table names
            echo "<h4>🔍 Looking for similar table names:</h4>";
            $database->query("SHOW TABLES LIKE '%subject%'");
            $database->execute();
            $similarTables = $database->resultset();
            
            if (!empty($similarTables)) {
                echo "<p>Found similar tables:</p><ul>";
                foreach ($similarTables as $table) {
                    $tableName = array_values($table)[0];
                    echo "<li>$tableName</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No tables with 'subject' in the name found.</p>";
            }
            
            // Check for 'courses' table
            echo "<h4>🔍 Looking for 'courses' table:</h4>";
            $database->query("SHOW TABLES LIKE 'courses'");
            $database->execute();
            $coursesTable = $database->resultset();
            
            if (!empty($coursesTable)) {
                echo "<p>✅ Found 'courses' table!</p>";
            } else {
                echo "<p>❌ 'courses' table also not found.</p>";
            }
        } else {
            echo "<p>✅ 'subjects' table exists!</p>";
            
            // Check table structure
            echo "<h4>📋 Table Structure:</h4>";
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
            
            // Check if section_id column exists
            $hasSectionId = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'section_id') {
                    $hasSectionId = true;
                    break;
                }
            }
            
            if ($hasSectionId) {
                echo "<p>✅ 'section_id' column already exists in subjects table!</p>";
            } else {
                echo "<p>❌ 'section_id' column does not exist in subjects table.</p>";
            }
        }
    }
    
    echo "<h3>🔍 Checking for 'sections' table:</h3>";
    $database->query("SHOW TABLES LIKE 'sections'");
    $database->execute();
    $sectionsTable = $database->resultset();
    
    if (empty($sectionsTable)) {
        echo "<p>❌ 'sections' table does not exist.</p>";
    } else {
        echo "<p>✅ 'sections' table exists!</p>";
        
        // Check sections table structure
        echo "<h4>📋 Sections Table Structure:</h4>";
        $database->query("DESCRIBE sections");
        $database->execute();
        $sectionsColumns = $database->resultset();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($sectionsColumns as $column) {
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
        
        // Check sections data
        echo "<h4>📊 Sections Data:</h4>";
        $database->query("SELECT * FROM sections LIMIT 10");
        $database->execute();
        $sectionsData = $database->resultset();
        
        if (empty($sectionsData)) {
            echo "<p>❌ No data in sections table.</p>";
        } else {
            echo "<p>✅ Found " . count($sectionsData) . " sections:</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            if (!empty($sectionsData)) {
                echo "<tr>";
                foreach (array_keys($sectionsData[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                foreach ($sectionsData as $section) {
                    echo "<tr>";
                    foreach ($section as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h3 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
h4 { color: #666; }
</style>
