<?php
session_start();
echo "<h2>Database Connection Test</h2>";

try {
    require_once '../../config/database.php';
    $db = new Database();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test basic query
    $db->query("SELECT COUNT(*) as total FROM subjects");
    $result = $db->single();
    echo "<p>Total subjects: <strong>" . $result['total'] . "</strong></p>";
    
    // Test year levels
    $db->query("SELECT DISTINCT year_level FROM subjects ORDER BY year_level");
    $yearLevels = $db->resultset();
    echo "<p>Available year levels:</p><ul>";
    foreach ($yearLevels as $level) {
        echo "<li>" . $level['year_level'] . "</li>";
    }
    echo "</ul>";
    
    // Test semesters
    $db->query("SELECT DISTINCT semester FROM subjects ORDER BY semester");
    $semesters = $db->resultset();
    echo "<p>Available semesters:</p><ul>";
    foreach ($semesters as $sem) {
        echo "<li>" . $sem['semester'] . "</li>";
    }
    echo "</ul>";
    
    // Test specific query
    $db->query("SELECT subject_id, subject_code, subject_name, year_level, semester, status FROM subjects WHERE year_level = '1st Year' AND semester = 'first' LIMIT 5");
    $courses = $db->resultset();
    echo "<p>Courses for 1st Year, First Semester: <strong>" . count($courses) . "</strong></p>";
    
    if (count($courses) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year</th><th>Semester</th><th>Status</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . $course['subject_id'] . "</td>";
            echo "<td>" . $course['subject_code'] . "</td>";
            echo "<td>" . $course['subject_name'] . "</td>";
            echo "<td>" . $course['year_level'] . "</td>";
            echo "<td>" . $course['semester'] . "</td>";
            echo "<td>" . $course['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
