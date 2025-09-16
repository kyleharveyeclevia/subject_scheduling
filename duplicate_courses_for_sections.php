<?php
// Script to duplicate existing courses for each section within the same year level
require_once 'config/database.php';

echo "<h2>🔄 Duplicating Courses for Sections</h2>";

try {
    $database = new Database();
    
    // First, check if section_id column exists
    echo "<h3>🔍 Checking Database Structure</h3>";
    try {
        $database->query("DESCRIBE subjects");
        $database->execute();
        $columns = $database->resultset();
        
        $has_section_id = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'section_id') {
                $has_section_id = true;
                echo "<p style='color: green;'>✅ section_id column exists</p>";
                break;
            }
        }
        
        if (!$has_section_id) {
            echo "<p style='color: red;'>❌ section_id column missing! Please run the migration first.</p>";
            echo "<p>Run: <code>add_section_to_subjects.sql</code></p>";
            exit;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking subjects table: " . $e->getMessage() . "</p>";
        exit;
    }
    
    // Get all available sections
    echo "<h3>📋 Available Sections</h3>";
    $database->query("SELECT section_id, section_name, year_level, status FROM sections WHERE status = 'available' ORDER BY year_level, section_name");
    $database->execute();
    $sections = $database->resultset();
    
    if (count($sections) == 0) {
        echo "<p style='color: red;'>❌ No available sections found! Please add sections first.</p>";
        echo "<p>Run: <a href='add_default_sections.php'>add_default_sections.php</a></p>";
        exit;
    }
    
    echo "<p>Found <strong>" . count($sections) . "</strong> available sections:</p>";
    foreach ($sections as $section) {
        echo "<p style='margin-left: 20px;'>- {$section['section_name']} ({$section['year_level']}) - ID: {$section['section_id']}</p>";
    }
    
    // Get existing courses (subjects)
    echo "<h3>📚 Existing Courses</h3>";
    $database->query("SELECT subject_id, subject_code, subject_name, units, year_level, semester, status, academic_year FROM subjects ORDER BY year_level, subject_code");
    $database->execute();
    $existing_courses = $database->resultset();
    
    if (count($existing_courses) == 0) {
        echo "<p style='color: orange;'>⚠️ No existing courses found. Nothing to duplicate.</p>";
        exit;
    }
    
    echo "<p>Found <strong>" . count($existing_courses) . "</strong> existing courses:</p>";
    foreach ($existing_courses as $course) {
        echo "<p style='margin-left: 20px;'>- {$course['subject_code']} - {$course['subject_name']} ({$course['year_level']})</p>";
    }
    
    // Group sections by year level
    $sections_by_year = [];
    foreach ($sections as $section) {
        $sections_by_year[$section['year_level']][] = $section;
    }
    
    echo "<h3>🔄 Duplication Process</h3>";
    
    $total_duplicated = 0;
    $total_skipped = 0;
    
    foreach ($existing_courses as $course) {
        $year_level = $course['year_level'];
        
        if (isset($sections_by_year[$year_level])) {
            $year_sections = $sections_by_year[$year_level];
            
            echo "<h4>📖 Course: {$course['subject_code']} - {$course['subject_name']} ({$year_level})</h4>";
            
            foreach ($year_sections as $section) {
                // Check if this course already exists for this section
                $database->query("SELECT COUNT(*) as count FROM subjects WHERE subject_code = ? AND year_level = ? AND section_id = ?");
                $database->bind(1, $course['subject_code']);
                $database->bind(2, $year_level);
                $database->bind(3, $section['section_id']);
                $database->execute();
                $exists = $database->single();
                
                if ($exists['count'] == 0) {
                    // Create duplicate course for this section
                    $database->query("INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, section_id, status, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $database->bind(1, $course['subject_code']);
                    $database->bind(2, $course['subject_name']);
                    $database->bind(3, $course['units']);
                    $database->bind(4, $course['year_level']);
                    $database->bind(5, $course['semester']);
                    $database->bind(6, $section['section_id']);
                    $database->bind(7, $course['status']);
                    $database->bind(8, $course['academic_year']);
                    
                    if ($database->execute()) {
                        echo "<p style='color: green; margin-left: 20px;'>✅ Created for Section {$section['section_name']}</p>";
                        $total_duplicated++;
                    } else {
                        echo "<p style='color: red; margin-left: 20px;'>❌ Failed to create for Section {$section['section_name']}</p>";
                    }
                } else {
                    echo "<p style='color: orange; margin-left: 20px;'>⏭️ Already exists for Section {$section['section_name']}</p>";
                    $total_skipped++;
                }
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No sections found for year level: {$year_level}</p>";
        }
        echo "<hr>";
    }
    
    // Show final results
    echo "<h3>📊 Final Results</h3>";
    echo "<p><strong>Total courses duplicated:</strong> {$total_duplicated}</p>";
    echo "<p><strong>Total courses skipped (already existed):</strong> {$total_skipped}</p>";
    
    // Show courses by section
    echo "<h3>🎯 Courses by Section</h3>";
    foreach ($sections_by_year as $year_level => $year_sections) {
        echo "<h4>{$year_level}</h4>";
        
        foreach ($year_sections as $section) {
            $database->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = ? AND section_id = ?");
            $database->bind(1, $year_level);
            $database->bind(2, $section['section_id']);
            $database->execute();
            $course_count = $database->single();
            
            echo "<p style='margin-left: 20px;'><strong>Section {$section['section_name']}:</strong> {$course_count['count']} courses</p>";
            
            // Show sample courses
            $database->query("SELECT subject_code, subject_name, status FROM subjects WHERE year_level = ? AND section_id = ? ORDER BY subject_code LIMIT 3");
            $database->bind(1, $year_level);
            $database->bind(2, $section['section_id']);
            $database->execute();
            $sample_courses = $database->resultset();
            
            foreach ($sample_courses as $course) {
                $status_color = $course['status'] === 'available' ? 'green' : 'red';
                echo "<p style='margin-left: 40px; color: {$status_color};'>- {$course['subject_code']} - {$course['subject_name']} ({$course['status']})</p>";
            }
        }
    }
    
    echo "<h3>🎉 Next Steps</h3>";
    echo "<p>1. <strong>Refresh your course dashboard</strong> - you should now see courses organized by sections</p>";
    echo "<p>2. <strong>Check the sections dashboard</strong> - each section should now have courses</p>";
    echo "<p>3. <strong>Test the section dropdowns</strong> - they should now show relevant courses</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Make sure your database connection is working and all required tables exist.</p>";
}
?>
