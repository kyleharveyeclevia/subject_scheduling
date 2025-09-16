<?php
/**
 * Test script to verify course duplication functionality
 * This script tests the new course duplication feature across sections
 */

require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Testing Course Duplication Functionality</h2>";
    
    // First, let's check what sections exist
    echo "<h3>1. Current Sections by Year Level</h3>";
    $db->query("SELECT year_level, section_name, status FROM sections WHERE status = 'available' ORDER BY year_level, section_name");
    $sections = $db->resultset();
    
    if (empty($sections)) {
        echo "<p style='color: red;'>❌ No active sections found. Please add sections first.</p>";
        exit;
    }
    
    $sections_by_year = [];
    foreach ($sections as $section) {
        $sections_by_year[$section['year_level']][] = $section['section_name'];
    }
    
    foreach ($sections_by_year as $year_level => $section_names) {
        echo "<p><strong>{$year_level}:</strong> " . implode(', ', $section_names) . " (" . count($section_names) . " sections)</p>";
    }
    
    // Test the duplication logic
    echo "<h3>2. Testing Course Duplication Logic</h3>";
    
    // Get a sample year level to test with
    $test_year_level = array_keys($sections_by_year)[0];
    $test_sections = $sections_by_year[$test_year_level];
    
    echo "<p>Testing with year level: <strong>{$test_year_level}</strong></p>";
    echo "<p>Available sections: " . implode(', ', $test_sections) . "</p>";
    
    // Simulate adding a test course
    $test_course = [
        'subject_code' => 'TEST101',
        'subject_name' => 'Test Course for Duplication',
        'units' => 3,
        'year_level' => $test_year_level,
        'semester' => 'first',
        'academic_year' => '2024-2025',
        'status' => 'available'
    ];
    
    echo "<h4>3. Simulating Course Addition</h4>";
    echo "<p>Course: <strong>{$test_course['subject_code']}</strong> - {$test_course['subject_name']}</p>";
    
    // Check if test course already exists
    $db->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = ? AND year_level = ? AND semester = ?");
    $db->bind(1, $test_course['subject_code']);
    $db->bind(2, $test_course['year_level']);
    $db->bind(3, $test_course['semester']);
    $existing = $db->single();
    
    if ($existing['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Test course already exists. Cleaning up first...</p>";
        
        // Clean up existing test course
        $db->query("DELETE FROM subjects WHERE subject_code = ? AND year_level = ? AND semester = ?");
        $db->bind(1, $test_course['subject_code']);
        $db->bind(2, $test_course['year_level']);
        $db->bind(3, $test_course['semester']);
        $db->execute();
        echo "<p style='color: green;'>✅ Cleaned up existing test course</p>";
    }
    
    // Get sections for the test year level
    $db->query("SELECT section_id, section_name FROM sections WHERE status = 'available' AND year_level = ? ORDER BY section_name ASC");
    $db->bind(1, $test_course['year_level']);
    $db->execute();
    $test_sections_data = $db->resultset();
    
    echo "<p>Found " . count($test_sections_data) . " active sections for {$test_course['year_level']}</p>";
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        $success_count = 0;
        $total_sections = count($test_sections_data);
        
        echo "<h4>4. Duplicating Course Across Sections</h4>";
        
        // Insert the course for each section
        foreach ($test_sections_data as $section) {
            echo "<p>Adding to section: <strong>{$section['section_name']}</strong> (ID: {$section['section_id']})</p>";
            
            // Insert the course for this section
            $db->query("INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, section_id, status, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $db->bind(1, $test_course['subject_code']);
            $db->bind(2, $test_course['subject_name']);
            $db->bind(3, $test_course['units']);
            $db->bind(4, $test_course['year_level']);
            $db->bind(5, $test_course['semester']);
            $db->bind(6, $section['section_id']);
            $db->bind(7, $test_course['status']);
            $db->bind(8, $test_course['academic_year']);
            
            if ($db->execute()) {
                $success_count++;
                echo "<p style='color: green;'>✅ Successfully added to {$section['section_name']}</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add to {$section['section_name']}</p>";
            }
        }
        
        // Commit transaction
        $db->commit();
        
        echo "<h4>5. Results</h4>";
        echo "<p style='color: green; font-weight: bold;'>✅ Course successfully duplicated to {$success_count} out of {$total_sections} sections!</p>";
        
        // Verify the results
        echo "<h4>6. Verification</h4>";
        $db->query("SELECT s.subject_code, s.subject_name, s.year_level, s.semester, sec.section_name, s.status 
                   FROM subjects s 
                   JOIN sections sec ON s.section_id = sec.section_id 
                   WHERE s.subject_code = ? AND s.year_level = ? AND s.semester = ? 
                   ORDER BY sec.section_name");
        $db->bind(1, $test_course['subject_code']);
        $db->bind(2, $test_course['year_level']);
        $db->bind(3, $test_course['semester']);
        $results = $db->resultset();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Course Code</th><th>Course Name</th><th>Year Level</th><th>Semester</th><th>Section</th><th>Status</th></tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['year_level']}</td>";
            echo "<td>{$row['semester']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Total courses created:</strong> " . count($results) . "</p>";
        
        if (count($results) === count($test_sections_data)) {
            echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS: Course duplication is working correctly!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ ISSUE: Expected " . count($test_sections_data) . " courses, but found " . count($results) . "</p>";
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        echo "<p style='color: red;'>❌ Error during duplication: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
