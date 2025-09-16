<?php
/**
 * Update all existing courses to have academic year '2025-2026'
 * This script will update the academic_year column for all courses
 */

// Include database configuration
require_once 'config/database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Updating Courses Academic Year to 2025-2026</h2>\n";
    echo "<hr>\n";
    
    // First, let's see what courses currently exist
    echo "<h3>Current Courses Status:</h3>\n";
    $query = "SELECT 
                subject_id,
                subject_code,
                subject_name,
                year_level,
                semester,
                academic_year,
                status
              FROM subjects 
              ORDER BY subject_code";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>\n";
    
    foreach ($courses as $course) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($course['subject_id']) . "</td>";
        echo "<td>" . htmlspecialchars($course['subject_code']) . "</td>";
        echo "<td>" . htmlspecialchars($course['subject_name']) . "</td>";
        echo "<td>" . htmlspecialchars($course['year_level']) . "</td>";
        echo "<td>" . htmlspecialchars($course['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($course['academic_year'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($course['status']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // Count courses by academic year before update
    echo "<h3>Courses Count by Academic Year (Before Update):</h3>\n";
    $countQuery = "SELECT 
                     academic_year,
                     COUNT(*) as count
                   FROM subjects 
                   GROUP BY academic_year
                   ORDER BY academic_year";
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Academic Year</th><th>Count</th></tr>\n";
    foreach ($counts as $count) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($count['academic_year'] ?? 'NULL') . "</td>";
        echo "<td>" . $count['count'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // Update all courses to have academic year '2025-2026'
    echo "<h3>Updating Courses...</h3>\n";
    
    $updateQuery = "UPDATE subjects 
                    SET academic_year = '2025-2026' 
                    WHERE academic_year IS NULL 
                       OR academic_year = '' 
                       OR academic_year = '2024-2025'";
    
    $stmt = $db->prepare($updateQuery);
    $result = $stmt->execute();
    $affectedRows = $stmt->rowCount();
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully updated $affectedRows courses to academic year '2025-2026'</p>\n";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Error updating courses</p>\n";
    }
    
    echo "<hr>\n";
    
    // Count courses by academic year after update
    echo "<h3>Courses Count by Academic Year (After Update):</h3>\n";
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Academic Year</th><th>Count</th></tr>\n";
    foreach ($counts as $count) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($count['academic_year'] ?? 'NULL') . "</td>";
        echo "<td>" . $count['count'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<hr>\n";
    
    // Show updated courses
    echo "<h3>Updated Courses (Academic Year 2025-2026):</h3>\n";
    
    $updatedQuery = "SELECT 
                       subject_id,
                       subject_code,
                       subject_name,
                       year_level,
                       semester,
                       academic_year,
                       status
                     FROM subjects 
                     WHERE academic_year = '2025-2026'
                     ORDER BY year_level, semester, subject_code";
    
    $stmt = $db->prepare($updatedQuery);
    $stmt->execute();
    $updatedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>\n";
    
    foreach ($updatedCourses as $course) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($course['subject_id']) . "</td>";
        echo "<td>" . htmlspecialchars($course['subject_code']) . "</td>";
        echo "<td>" . htmlspecialchars($course['subject_name']) . "</td>";
        echo "<td>" . htmlspecialchars($course['year_level']) . "</td>";
        echo "<td>" . htmlspecialchars($course['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($course['academic_year']) . "</td>";
        echo "<td>" . htmlspecialchars($course['status']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<hr>\n";
    echo "<p style='color: blue; font-weight: bold;'>🎉 Update completed successfully! All courses now have academic year '2025-2026'</p>\n";
    echo "<p>You can now access the Manage Academic Year Course dashboard and select '2025-2026' to see all your courses.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
table {
    border-collapse: collapse;
    margin: 10px 0;
    background-color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th {
    background-color: #4CAF50;
    color: white;
    padding: 10px;
}
td {
    padding: 8px;
    border: 1px solid #ddd;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
</style>
