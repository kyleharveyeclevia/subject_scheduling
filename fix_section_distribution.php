<?php
session_start();
require_once 'config/database.php';

try {
    $database = new Database();
    
    echo "<h2>Fixing Course Distribution Between Sections</h2>";
    
    // Get all 1st year sections
    $database->query('SELECT section_id, section_name FROM sections WHERE year_level = "1st Year" ORDER BY section_name');
    $database->execute();
    $sections = $database->resultset();
    
    echo "<h3>Available 1st Year Sections:</h3>";
    foreach ($sections as $section) {
        echo "<p>- " . $section['section_name'] . " (ID: " . $section['section_id'] . ")</p>";
    }
    
    // Get all 1st year subjects
    $database->query('SELECT subject_id, subject_code, subject_name FROM subjects WHERE year_level = "1st Year" ORDER BY subject_code');
    $database->execute();
    $subjects = $database->resultset();
    
    echo "<h3>1st Year Subjects to Distribute:</h3>";
    foreach ($subjects as $subject) {
        echo "<p>- " . $subject['subject_code'] . " - " . $subject['subject_name'] . "</p>";
    }
    
    // Clear all existing section assignments for 1st year
    $database->query('UPDATE subjects SET section_id = NULL WHERE year_level = "1st Year"');
    $database->execute();
    echo "<p style='color: blue;'>✓ Cleared all existing section assignments</p>";
    
    // Distribute subjects evenly between sections
    $subjectsPerSection = ceil(count($subjects) / count($sections));
    echo "<p><strong>Subjects per section:</strong> " . $subjectsPerSection . "</p>";
    
    $sectionIndex = 0;
    $assignments = [];
    
    foreach ($subjects as $index => $subject) {
        $targetSection = $sections[$sectionIndex % count($sections)];
        $assignments[] = [
            'subject_id' => $subject['subject_id'],
            'subject_code' => $subject['subject_code'],
            'section_id' => $targetSection['section_id'],
            'section_name' => $targetSection['section_name']
        ];
        
        // Move to next section after assigning the calculated number of subjects
        if (($index + 1) % $subjectsPerSection === 0) {
            $sectionIndex++;
        }
    }
    
    // Apply assignments
    echo "<h3>Applying Assignments:</h3>";
    foreach ($assignments as $assignment) {
        $database->query('UPDATE subjects SET section_id = :section_id WHERE subject_id = :subject_id');
        $database->bind(':section_id', $assignment['section_id']);
        $database->bind(':subject_id', $assignment['subject_id']);
        $database->execute();
        
        echo "<p>✓ " . $assignment['subject_code'] . " → Section " . $assignment['section_name'] . "</p>";
    }
    
    // Verify the distribution
    echo "<h3>Verification - Final Distribution:</h3>";
    
    foreach ($sections as $section) {
        $database->query('SELECT subject_id, subject_code, subject_name FROM subjects WHERE year_level = "1st Year" AND section_id = :section_id ORDER BY subject_code');
        $database->bind(':section_id', $section['section_id']);
        $database->execute();
        $sectionSubjects = $database->resultset();
        
        echo "<h4>Section " . $section['section_name'] . " (" . count($sectionSubjects) . " courses):</h4>";
        if (count($sectionSubjects) > 0) {
            echo "<ul>";
            foreach ($sectionSubjects as $subject) {
                echo "<li>" . $subject['subject_code'] . " - " . $subject['subject_name'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ No courses assigned to this section!</p>";
        }
    }
    
    // Test the API for each section
    echo "<h3>API Test - Generate Schedule Dashboard:</h3>";
    
    foreach ($sections as $section) {
        echo "<h4>Testing Section " . $section['section_name'] . " in Generate Schedule:</h4>";
        
        // Simulate the exact API call
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
        $apiCourses = $database->resultset();
        
        echo "<p><strong>API Response:</strong> " . count($apiCourses) . " courses</p>";
        
        if (count($apiCourses) > 0) {
            echo "<ul>";
            foreach ($apiCourses as $course) {
                echo "<li>" . $course['code'] . " - " . $course['title'] . "</li>";
            }
            echo "</ul>";
            echo "<p style='color: green;'>✅ Section " . $section['section_name'] . " will show courses in Generate Schedule Dashboard</p>";
        } else {
            echo "<p style='color: red;'>❌ Section " . $section['section_name'] . " will show NO courses in Generate Schedule Dashboard</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h3>Summary</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ Course distribution fix completed!</p>";
    echo "<p>Now both sections 1A and 1B should show different courses in the Generate Schedule Dashboard.</p>";
    echo "<p>Each section will have its own unique set of courses for scheduling.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>

