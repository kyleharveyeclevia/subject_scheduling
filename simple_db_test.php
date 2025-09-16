<?php
require_once 'config/database.php';

echo "Database Structure Test\n";
echo "======================\n\n";

try {
    $database = new Database();
    
    // Check subjects table structure
    echo "Subjects Table Structure:\n";
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    foreach ($columns as $column) {
        echo sprintf("%-15s %-20s %-5s %-5s %-10s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?? 'NULL', 
            $column['Extra']
        );
    }
    
    // Check if section_id exists
    $hasSectionId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'section_id') {
            $hasSectionId = true;
            break;
        }
    }
    
    echo "\nHas section_id column: " . ($hasSectionId ? 'YES' : 'NO') . "\n";
    
    // Check subjects data
    echo "\nSubjects Data (1st Year, first semester):\n";
    $database->query("SELECT * FROM subjects WHERE year_level = '1st Year' AND semester = 'first' LIMIT 10");
    $database->execute();
    $subjects = $database->resultset();
    
    if (empty($subjects)) {
        echo "No subjects found for 1st Year, first semester\n";
    } else {
        echo "Found " . count($subjects) . " subjects:\n";
        foreach ($subjects as $subject) {
            echo "  - " . $subject['subject_code'] . ": " . $subject['subject_name'] . "\n";
        }
    }
    
    // Check all subjects for 1st Year
    echo "\nAll subjects for 1st Year:\n";
    $database->query("SELECT * FROM subjects WHERE year_level = '1st Year' LIMIT 10");
    $database->execute();
    $allFirstYear = $database->resultset();
    
    if (empty($allFirstYear)) {
        echo "No subjects found for 1st Year\n";
    } else {
        echo "Found " . count($allFirstYear) . " subjects:\n";
        foreach ($allFirstYear as $subject) {
            echo "  - " . $subject['subject_code'] . ": " . $subject['subject_name'] . " (Semester: " . ($subject['semester'] ?? 'NULL') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
