<?php
session_start();
require_once 'config/database.php';

try {
    $database = new Database();
    
    echo "<h2>Fixing Section Course Assignments</h2>";
    
    // First, ensure section_id column exists
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
    
    if (!$hasSectionId) {
        echo "<p style='color: orange;'>Adding section_id column...</p>";
        $database->query('ALTER TABLE subjects ADD COLUMN section_id INT NULL AFTER year_level');
        $database->execute();
        
        // Add foreign key constraint
        try {
            $database->query('ALTER TABLE subjects ADD CONSTRAINT fk_subjects_section FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE SET NULL');
            $database->execute();
        } catch (Exception $e) {
            // Constraint might already exist
        }
        
        // Add index
        try {
            $database->query('CREATE INDEX idx_subjects_section ON subjects(section_id)');
            $database->execute();
        } catch (Exception $e) {
            // Index might already exist
        }
        
        echo "<p style='color: green;'>✓ Added section_id column</p>";
    }
    
    // Get 1st year sections
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
    
    echo "<h3>1st Year Subjects to Assign:</h3>";
    foreach ($subjects as $subject) {
        echo "<p>- " . $subject['subject_code'] . " - " . $subject['subject_name'] . "</p>";
    }
    
    // Clear existing assignments for 1st year
    $database->query('UPDATE subjects SET section_id = NULL WHERE year_level = "1st Year"');
    $database->execute();
    echo "<p style='color: blue;'>Cleared existing section assignments for 1st year subjects</p>";
    
    // Create a more realistic distribution of courses
    // This simulates how different sections might have different course loads
    $sectionAssignments = [];
    
    // Define which courses go to which sections (this should be configurable)
    $courseAssignments = [
        'A' => ['MATH101', 'ENG101', 'PHYS101'],  // Section A gets these courses
        'B' => ['CHEM101', 'HIST101', 'BIO101']   // Section B gets these courses
    ];
    
    // If we don't have predefined assignments, distribute evenly
    if (empty($courseAssignments['A']) && empty($courseAssignments['B'])) {
        $subjectsPerSection = ceil(count($subjects) / count($sections));
        $sectionIndex = 0;
        
        foreach ($subjects as $index => $subject) {
            $targetSection = $sections[$sectionIndex % count($sections)];
            $sectionAssignments[$subject['subject_id']] = $targetSection['section_id'];
            
            if (($index + 1) % $subjectsPerSection === 0) {
                $sectionIndex++;
            }
        }
    } else {
        // Use predefined assignments
        foreach ($courseAssignments as $sectionName => $courseCodes) {
            $section = array_filter($sections, function($s) use ($sectionName) {
                return $s['section_name'] === $sectionName;
            });
            $section = reset($section);
            
            if ($section) {
                foreach ($courseCodes as $courseCode) {
                    $subject = array_filter($subjects, function($s) use ($courseCode) {
                        return $s['subject_code'] === $courseCode;
                    });
                    $subject = reset($subject);
                    
                    if ($subject) {
                        $sectionAssignments[$subject['subject_id']] = $section['section_id'];
                    }
                }
            }
        }
        
        // Assign any remaining subjects to the first section
        foreach ($subjects as $subject) {
            if (!isset($sectionAssignments[$subject['subject_id']])) {
                $sectionAssignments[$subject['subject_id']] = $sections[0]['section_id'];
            }
        }
    }
    
    // Apply the assignments
    echo "<h3>Applying Section Assignments:</h3>";
    foreach ($sectionAssignments as $subjectId => $sectionId) {
        $database->query('UPDATE subjects SET section_id = :section_id WHERE subject_id = :subject_id');
        $database->bind(':section_id', $sectionId);
        $database->bind(':subject_id', $subjectId);
        $database->execute();
        
        // Get subject and section names for display
        $subject = array_filter($subjects, function($s) use ($subjectId) {
            return $s['subject_id'] == $subjectId;
        });
        $subject = reset($subject);
        
        $section = array_filter($sections, function($s) use ($sectionId) {
            return $s['section_id'] == $sectionId;
        });
        $section = reset($section);
        
        echo "<p>Assigned " . $subject['subject_code'] . " to section " . $section['section_name'] . "</p>";
    }
    
    echo "<p style='color: green;'>✓ Section assignments completed!</p>";
    
    // Verify the results
    echo "<h3>Verification - Courses by Section:</h3>";
    $database->query('SELECT s.subject_id, s.subject_code, s.subject_name, s.section_id, sec.section_name 
                     FROM subjects s 
                     LEFT JOIN sections sec ON s.section_id = sec.section_id 
                     WHERE s.year_level = "1st Year" 
                     ORDER BY s.section_id, s.subject_code');
    $database->execute();
    $results = $database->resultset();
    
    $groupedResults = [];
    foreach ($results as $result) {
        $sectionName = $result['section_name'] ?? 'Unassigned';
        if (!isset($groupedResults[$sectionName])) {
            $groupedResults[$sectionName] = [];
        }
        $groupedResults[$sectionName][] = $result;
    }
    
    foreach ($groupedResults as $sectionName => $courses) {
        echo "<h4>Section " . $sectionName . " (" . count($courses) . " courses):</h4>";
        echo "<ul>";
        foreach ($courses as $course) {
            echo "<li>" . $course['subject_code'] . " - " . $course['subject_name'] . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ Fix completed! Now sections 1A and 1B should show different courses in the generate schedule dashboard.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
