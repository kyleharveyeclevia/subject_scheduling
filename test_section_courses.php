<?php
session_start();
require_once 'config/database.php';

try {
    $database = new Database();
    
    // Check if section_id column exists
    $database->query('DESCRIBE subjects');
    $database->execute();
    $columns = $database->resultset();
    
    $hasSectionId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'section_id') {
            $hasSectionId = true;
            break;
        }
    }
    
    echo "<h2>Database Structure Analysis</h2>";
    
    if ($hasSectionId) {
        echo "<p style='color: green;'>✓ section_id column exists in subjects table</p>";
        
        // Check how courses are assigned to sections
        $database->query('SELECT s.subject_id, s.subject_code, s.subject_name, s.year_level, s.section_id, sec.section_name 
                         FROM subjects s 
                         LEFT JOIN sections sec ON s.section_id = sec.section_id 
                         WHERE s.year_level = "1st Year" 
                         ORDER BY s.section_id, s.subject_code');
        $database->execute();
        $subjects = $database->resultset();
        
        echo "<h3>1st Year Courses by Section:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Subject ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Section ID</th><th>Section Name</th></tr>";
        
        $sectionA = [];
        $sectionB = [];
        
        foreach ($subjects as $subject) {
            echo "<tr>";
            echo "<td>" . $subject['subject_id'] . "</td>";
            echo "<td>" . $subject['subject_code'] . "</td>";
            echo "<td>" . $subject['subject_name'] . "</td>";
            echo "<td>" . $subject['year_level'] . "</td>";
            echo "<td>" . ($subject['section_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($subject['section_name'] ?? 'NULL') . "</td>";
            echo "</tr>";
            
            if ($subject['section_name'] === 'A') {
                $sectionA[] = $subject;
            } elseif ($subject['section_name'] === 'B') {
                $sectionB[] = $subject;
            }
        }
        echo "</table>";
        
        echo "<h3>Analysis:</h3>";
        echo "<p><strong>Section A courses:</strong> " . count($sectionA) . " courses</p>";
        echo "<p><strong>Section B courses:</strong> " . count($sectionB) . " courses</p>";
        
        if (count($sectionA) === 0 && count($sectionB) === 0) {
            echo "<p style='color: red;'>⚠️ No courses are assigned to specific sections (1A or 1B)</p>";
            echo "<p>This explains why both sections show the same courses - they're not properly differentiated!</p>";
        } elseif (count($sectionA) === count($sectionB) && count($sectionA) > 0) {
            echo "<p style='color: orange;'>⚠️ Both sections have the same number of courses - checking if they're different...</p>";
            
            $codesA = array_column($sectionA, 'subject_code');
            $codesB = array_column($sectionB, 'subject_code');
            
            if ($codesA === $codesB) {
                echo "<p style='color: red;'>❌ Both sections have identical courses! This is the problem.</p>";
            } else {
                echo "<p style='color: green;'>✓ Sections have different courses - the issue might be elsewhere.</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ section_id column does NOT exist in subjects table</p>";
        echo "<p>This means courses are not assigned to specific sections, which explains why 1A and 1B show the same courses!</p>";
        
        // Check what columns do exist
        echo "<h3>Current subjects table columns:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
        }
        echo "</ul>";
    }
    
    // Check sections table
    $database->query('SELECT section_id, section_name, year_level FROM sections WHERE year_level = "1st Year" ORDER BY section_name');
    $database->execute();
    $sections = $database->resultset();
    
    echo "<h3>Available 1st Year Sections:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Section ID</th><th>Section Name</th><th>Year Level</th></tr>";
    
    foreach ($sections as $section) {
        echo "<tr>";
        echo "<td>" . $section['section_id'] . "</td>";
        echo "<td>" . $section['section_name'] . "</td>";
        echo "<td>" . $section['year_level'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
