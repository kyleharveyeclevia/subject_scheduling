<?php
session_start();
require_once 'config/database.php';

try {
    $database = new Database();
    
    echo "<h2>Running Section Migration</h2>";
    
    // Check if section_id column already exists
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
    
    if ($hasSectionId) {
        echo "<p style='color: green;'>✓ section_id column already exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ section_id column does not exist. Running migration...</p>";
        
        // Add section_id column
        $database->query('ALTER TABLE subjects ADD COLUMN section_id INT NULL AFTER year_level');
        $database->execute();
        echo "<p style='color: green;'>✓ Added section_id column</p>";
        
        // Add foreign key constraint
        try {
            $database->query('ALTER TABLE subjects ADD CONSTRAINT fk_subjects_section FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE SET NULL');
            $database->execute();
            echo "<p style='color: green;'>✓ Added foreign key constraint</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Foreign key constraint already exists or failed: " . $e->getMessage() . "</p>";
        }
        
        // Add index
        try {
            $database->query('CREATE INDEX idx_subjects_section ON subjects(section_id)');
            $database->execute();
            echo "<p style='color: green;'>✓ Added index</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Index already exists or failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Now assign courses to sections
    echo "<h3>Assigning courses to sections...</h3>";
    
    // Get all 1st year sections
    $database->query('SELECT section_id, section_name FROM sections WHERE year_level = "1st Year" ORDER BY section_name');
    $database->execute();
    $sections = $database->resultset();
    
    echo "<p>Found " . count($sections) . " 1st year sections:</p>";
    foreach ($sections as $section) {
        echo "<p>- " . $section['section_name'] . " (ID: " . $section['section_id'] . ")</p>";
    }
    
    // Get all 1st year subjects
    $database->query('SELECT subject_id, subject_code, subject_name FROM subjects WHERE year_level = "1st Year" ORDER BY subject_code');
    $database->execute();
    $subjects = $database->resultset();
    
    echo "<p>Found " . count($subjects) . " 1st year subjects:</p>";
    foreach ($subjects as $subject) {
        echo "<p>- " . $subject['subject_code'] . " - " . $subject['subject_name'] . "</p>";
    }
    
    // Assign subjects to sections (distribute evenly)
    $subjectsPerSection = ceil(count($subjects) / count($sections));
    $sectionIndex = 0;
    
    foreach ($subjects as $index => $subject) {
        $targetSection = $sections[$sectionIndex % count($sections)];
        
        $database->query('UPDATE subjects SET section_id = :section_id WHERE subject_id = :subject_id');
        $database->bind(':section_id', $targetSection['section_id']);
        $database->bind(':subject_id', $subject['subject_id']);
        $database->execute();
        
        echo "<p>Assigned " . $subject['subject_code'] . " to section " . $targetSection['section_name'] . "</p>";
        
        if (($index + 1) % $subjectsPerSection === 0) {
            $sectionIndex++;
        }
    }
    
    echo "<p style='color: green;'>✓ Migration completed successfully!</p>";
    
    // Verify the results
    echo "<h3>Verification:</h3>";
    $database->query('SELECT s.subject_id, s.subject_code, s.subject_name, s.section_id, sec.section_name 
                     FROM subjects s 
                     LEFT JOIN sections sec ON s.section_id = sec.section_id 
                     WHERE s.year_level = "1st Year" 
                     ORDER BY s.section_id, s.subject_code');
    $database->execute();
    $results = $database->resultset();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Code</th><th>Name</th><th>Section</th></tr>";
    
    $currentSection = '';
    foreach ($results as $result) {
        if ($currentSection !== $result['section_name']) {
            $currentSection = $result['section_name'];
            echo "<tr style='background-color: #f0f0f0;'><td colspan='3'><strong>Section " . $result['section_name'] . "</strong></td></tr>";
        }
        echo "<tr>";
        echo "<td>" . $result['subject_code'] . "</td>";
        echo "<td>" . $result['subject_name'] . "</td>";
        echo "<td>" . $result['section_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
