<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Database Test</h2>";
    
    // Test 1: Check if subjects table exists and has data
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $result = $db->single();
    echo "<p>Total subjects: " . $result['count'] . "</p>";
    
    // Test 2: Check available subjects
    $db->query("SELECT COUNT(*) as count FROM subjects WHERE status = 'available'");
    $db->execute();
    $result = $db->single();
    echo "<p>Available subjects: " . $result['count'] . "</p>";
    
    // Test 3: Show some sample subjects
    $db->query("SELECT subject_id, subject_code, subject_name, year_level, semester, academic_year, status FROM subjects LIMIT 5");
    $db->execute();
    $subjects = $db->resultset();
    
    echo "<h3>Sample Subjects:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
    
    foreach ($subjects as $subject) {
        echo "<tr>";
        echo "<td>" . $subject['subject_id'] . "</td>";
        echo "<td>" . $subject['subject_code'] . "</td>";
        echo "<td>" . $subject['subject_name'] . "</td>";
        echo "<td>" . $subject['year_level'] . "</td>";
        echo "<td>" . $subject['semester'] . "</td>";
        echo "<td>" . $subject['academic_year'] . "</td>";
        echo "<td>" . $subject['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 4: Test the exact query from fetch-courses.php
    echo "<h3>Testing fetch-courses query:</h3>";
    
    $testParams = [
        'academic_year' => '2024-2025',
        'year_level' => '1st Year',
        'semester' => 'first'
    ];
    
    $db->query("SELECT COUNT(*) as count FROM subjects 
                WHERE academic_year = :academic_year 
                AND year_level = :year_level 
                AND semester = :semester
                AND status = 'available'");
    $db->bind(':academic_year', $testParams['academic_year']);
    $db->bind(':year_level', $testParams['year_level']);
    $db->bind(':semester', $testParams['semester']);
    $db->execute();
    $result = $db->single();
    
    echo "<p>Query result for " . $testParams['year_level'] . ", " . $testParams['semester'] . ", " . $testParams['academic_year'] . ": " . $result['count'] . " courses</p>";
    
    if ($result['count'] > 0) {
        $db->query("SELECT subject_id, subject_code, subject_name FROM subjects 
                    WHERE academic_year = :academic_year 
                    AND year_level = :year_level 
                    AND semester = :semester
                    AND status = 'available'
                    ORDER BY subject_code");
        $db->bind(':academic_year', $testParams['academic_year']);
        $db->bind(':year_level', $testParams['year_level']);
        $db->bind(':semester', $testParams['semester']);
        $db->execute();
        $courses = $db->resultset();
        
        echo "<ul>";
        foreach ($courses as $course) {
            echo "<li>" . $course['subject_code'] . " - " . $course['subject_name'] . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
