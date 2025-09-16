<?php
session_start();
require_once 'config/database.php';

echo "<h2>Testing Generate Schedule API</h2>";

try {
    $database = new Database();
    
    // Get 1st year sections
    $database->query('SELECT section_id, section_name FROM sections WHERE year_level = "1st Year" ORDER BY section_name');
    $database->execute();
    $sections = $database->resultset();
    
    echo "<h3>Testing Course Loading for Different Sections</h3>";
    
    foreach ($sections as $section) {
        echo "<h4>Section: " . $section['section_name'] . " (ID: " . $section['section_id'] . ")</h4>";
        
        // Simulate the API call that generate-schedule.php makes
        $year_level = "1st Year";
        $semester = "first";
        $section_id = $section['section_id'];
        
        // Use the same logic as get_courses.php
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
        
        echo "<p><strong>Courses found:</strong> " . count($courses) . "</p>";
        
        if (count($courses) > 0) {
            echo "<ul>";
            foreach ($courses as $course) {
                echo "<li>" . $course['code'] . " - " . $course['title'] . " (" . $course['units'] . " units)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>No courses found for this section!</p>";
        }
        
        echo "<hr>";
    }
    
    // Test what happens when no section_id is provided (should show all courses)
    echo "<h4>Test: No section filter (should show all 1st year courses)</h4>";
    
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
              ORDER BY s.subject_code ASC";
    
    $database->query($query);
    $database->bind(':year_level', $year_level);
    $database->execute();
    $allCourses = $database->resultset();
    
    echo "<p><strong>All 1st year courses:</strong> " . count($allCourses) . "</p>";
    
    if (count($allCourses) > 0) {
        echo "<ul>";
        foreach ($allCourses as $course) {
            $sectionInfo = $course['section_name'] ? " (Section: " . $course['section_name'] . ")" : " (No section assigned)";
            echo "<li>" . $course['code'] . " - " . $course['title'] . $sectionInfo . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>Summary</h3>";
    echo "<p>If the fix worked correctly, you should see:</p>";
    echo "<ul>";
    echo "<li>Different courses for Section A vs Section B</li>";
    echo "<li>Each section should have its own unique set of courses</li>";
    echo "<li>The total number of courses across all sections should equal the total 1st year courses</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
