<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Course Analysis - Subject Scheduling Database</h2>";
    
    // Check all courses in the subject scheduling database
    echo "<h3>1. All Courses in Subject Scheduling Database:</h3>";
    $db->query("SELECT s.subject_id, s.subject_code, s.subject_name, s.year_level, s.semester, s.section_id, sec.section_name, s.status
                FROM subjects s
                LEFT JOIN sections sec ON s.section_id = sec.section_id
                ORDER BY s.year_level, s.semester, s.subject_code");
    $db->execute();
    $all_courses = $db->resultset();
    
    echo "<p>Total courses in database: " . count($all_courses) . "</p>";
    
    if (!empty($all_courses)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Section</th><th>Status</th></tr>";
        
        foreach ($all_courses as $row) {
            echo "<tr>";
            echo "<td>{$row['subject_id']}</td>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['year_level']}</td>";
            echo "<td>{$row['semester']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for courses that might not belong in subject scheduling
    echo "<h3>2. Analyzing Course Codes for Subject Scheduling Relevance:</h3>";
    
    $suspicious_courses = [];
    $valid_courses = [];
    
    foreach ($all_courses as $course) {
        $code = $course['subject_code'];
        $name = $course['subject_name'];
        
        // Check if course code looks like it belongs in subject scheduling
        $is_valid = false;
        
        // Valid patterns for subject scheduling courses
        if (preg_match('/^(CC|CS|ENG|MATH|PHYS|CHEM|FILN|NSTP|RES|THESIS)\d+/i', $code)) {
            $is_valid = true;
        }
        
        // Check for specific valid course codes
        $valid_codes = ['CC101', 'CS1', 'CS101', 'EN +', 'ENG101', 'FILN 1', 'MATH101', 'NSTP101', 'PHYS101', 'RES301', 'THESIS401'];
        if (in_array($code, $valid_codes)) {
            $is_valid = true;
        }
        
        if ($is_valid) {
            $valid_courses[] = $course;
        } else {
            $suspicious_courses[] = $course;
        }
    }
    
    echo "<h4>Valid Subject Scheduling Courses (" . count($valid_courses) . "):</h4>";
    if (!empty($valid_courses)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Year Level</th><th>Section</th></tr>";
        
        foreach ($valid_courses as $row) {
            echo "<tr>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['year_level']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Suspicious/Invalid Courses (" . count($suspicious_courses) . "):</h4>";
    if (!empty($suspicious_courses)) {
        echo "<p style='color: orange;'>These courses might not belong in the subject scheduling system:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Code</th><th>Name</th><th>Year Level</th><th>Section</th><th>Action</th></tr>";
        
        foreach ($suspicious_courses as $row) {
            echo "<tr>";
            echo "<td>{$row['subject_id']}</td>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['year_level']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "<td><button onclick='removeCourse({$row['subject_id']})' style='color: red;'>Remove</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No suspicious courses found - all courses appear to be valid for subject scheduling</p>";
    }
    
    // Check for duplicate course codes across different contexts
    echo "<h3>3. Duplicate Course Code Analysis:</h3>";
    $db->query("SELECT subject_code, COUNT(*) as count, GROUP_CONCAT(DISTINCT year_level) as year_levels, GROUP_CONCAT(DISTINCT section_id) as sections
                FROM subjects 
                GROUP BY subject_code 
                HAVING COUNT(*) > 1 
                ORDER BY subject_code");
    $db->execute();
    $duplicates = $db->resultset();
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✅ No duplicate course codes found</p>";
    } else {
        echo "<p style='color: orange;'>Found " . count($duplicates) . " course codes with duplicates:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Course Code</th><th>Count</th><th>Year Levels</th><th>Sections</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>{$dup['subject_code']}</td>";
            echo "<td>{$dup['count']}</td>";
            echo "<td>{$dup['year_levels']}</td>";
            echo "<td>{$dup['sections']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}
?>

<script>
function removeCourse(subjectId) {
    if (confirm('Are you sure you want to remove this course? This action cannot be undone.')) {
        // This would need to be implemented with proper AJAX call
        alert('Remove functionality would be implemented here for course ID: ' + subjectId);
    }
}
</script>
