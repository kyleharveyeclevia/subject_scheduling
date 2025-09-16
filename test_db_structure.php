<?php
require_once 'config/database.php';

echo "<h2>Database Structure Test</h2>";

try {
    $database = new Database();
    
    // Check subjects table structure
    echo "<h3>Subjects Table Structure:</h3>";
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    echo "<table border='1'>";
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
    
    // Check if section_id exists
    $hasSectionId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'section_id') {
            $hasSectionId = true;
            break;
        }
    }
    
    echo "<p><strong>Has section_id column:</strong> " . ($hasSectionId ? 'YES' : 'NO') . "</p>";
    
    // Check subjects data
    echo "<h3>Subjects Data (1st Year, first semester):</h3>";
    $database->query("SELECT * FROM subjects WHERE year_level = '1st Year' AND semester = 'first' LIMIT 10");
    $database->execute();
    $subjects = $database->resultset();
    
    if (empty($subjects)) {
        echo "<p>No subjects found for 1st Year, first semester</p>";
    } else {
        echo "<p>Found " . count($subjects) . " subjects:</p>";
        echo "<table border='1'>";
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
