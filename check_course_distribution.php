<?php
session_start();
require_once 'config/database.php';

try {
    $database = new Database();
    
    echo "<h2>Current Course Distribution Analysis</h2>";
    
    // Check all 1st year subjects and their section assignments
    $database->query('SELECT s.subject_id, s.subject_code, s.subject_name, s.section_id, sec.section_name 
                     FROM subjects s 
                     LEFT JOIN sections sec ON s.section_id = sec.section_id 
                     WHERE s.year_level = "1st Year" 
                     ORDER BY s.section_id, s.subject_code');
    $database->execute();
    $subjects = $database->resultset();
    
    echo "<h3>All 1st Year Subjects:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Subject ID</th><th>Code</th><th>Name</th><th>Section ID</th><th>Section Name</th></tr>";
    
    $sectionCounts = [];
    foreach ($subjects as $subject) {
        echo "<tr>";
        echo "<td>" . $subject['subject_id'] . "</td>";
        echo "<td>" . $subject['subject_code'] . "</td>";
        echo "<td>" . $subject['subject_name'] . "</td>";
        echo "<td>" . ($subject['section_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($subject['section_name'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        $sectionName = $subject['section_name'] ?? 'Unassigned';
        if (!isset($sectionCounts[$sectionName])) {
            $sectionCounts[$sectionName] = 0;
        }
        $sectionCounts[$sectionName]++;
    }
    echo "</table>";
    
    echo "<h3>Course Count by Section:</h3>";
    foreach ($sectionCounts as $sectionName => $count) {
        echo "<p><strong>" . $sectionName . ":</strong> " . $count . " courses</p>";
    }
    
    // Check available sections
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
    
    // Test the API calls for each section
    echo "<h3>API Test Results:</h3>";
    
    foreach ($sections as $section) {
        echo "<h4>Testing Section: " . $section['section_name'] . " (ID: " . $section['section_id'] . ")</h4>";
        
        // Simulate the exact API call from generate-schedule.php
        $year_level = "1st Year";
        $semester = "first";
        $section_id = $section['section_id'];
        
        $query = "SELECT s.subject_id as id, 
                         s.subject_code as code, 
                         s.subject_name as title, 
                         s.units, 
                         s.year_level, 
                         s.semester, 
                         s.academic_year,
                         CASE WHEN s.status = 'available' THEN 'active' ELSE 'inactive' END as status,
                         sec.section_name
                  FROM subjects s
                  LEFT JOIN sections sec ON s.section_id = sec.section_id
                  WHERE s.status = 'available' 
                  AND s.year_level = :year_level
                  AND s.section_id = :section_id
                  ORDER BY s.subject_code ASC";
        
        $database->query($query);
        $database->bind(':year_level', $year_level);
        $database->bind(':section_id', $section_id);
        $database->execute();
        $courses = $database->resultset();
        
        echo "<p><strong>Courses returned by API:</strong> " . count($courses) . "</p>";
        
        if (count($courses) > 0) {
            echo "<ul>";
            foreach ($courses as $course) {
                echo "<li>" . $course['code'] . " - " . $course['title'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ No courses found for this section!</p>";
        }
        
        echo "<hr>";
    }
    
    // Check if there are any subjects without section assignments
    $database->query('SELECT COUNT(*) as count FROM subjects WHERE year_level = "1st Year" AND section_id IS NULL');
    $database->execute();
    $unassigned = $database->single();
    
    echo "<h3>Unassigned Subjects:</h3>";
    echo "<p><strong>Count:</strong> " . $unassigned['count'] . "</p>";
    
    if ($unassigned['count'] > 0) {
        $database->query('SELECT subject_id, subject_code, subject_name FROM subjects WHERE year_level = "1st Year" AND section_id IS NULL');
        $database->execute();
        $unassignedSubjects = $database->resultset();
        
        echo "<ul>";
        foreach ($unassignedSubjects as $subject) {
            echo "<li>" . $subject['subject_code'] . " - " . $subject['subject_name'] . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

