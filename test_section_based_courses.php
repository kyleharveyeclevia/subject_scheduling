<?php
// Test file to verify section-based courses structure
require_once 'config/database.php';

echo "<h1>🧪 Testing Section-Based Courses Structure</h1>";

try {
    $database = new Database();
    
    // Test 1: Check if sections table exists and has data
    echo "<h2>📋 Test 1: Sections Table</h2>";
    $database->query("SELECT * FROM sections WHERE status = 'available' ORDER BY year_level, section_name");
    $database->execute();
    $sections = $database->resultset();
    
    if (!empty($sections)) {
        echo "<p>✅ Found " . count($sections) . " available sections:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Section ID</th><th>Section Name</th><th>Year Level</th><th>Status</th></tr>";
        foreach ($sections as $section) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($section['section_id']) . "</td>";
            echo "<td>" . htmlspecialchars($section['section_name']) . "</td>";
            echo "<td>" . htmlspecialchars($section['year_level']) . "</td>";
            echo "<td>" . htmlspecialchars($section['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No sections found</p>";
    }
    
    // Test 2: Check if subjects table has section_id column
    echo "<h2>📚 Test 2: Subjects Table Structure</h2>";
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    $hasSectionId = false;
    echo "<p>Subjects table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column['Field']) . " - " . htmlspecialchars($column['Type']);
        if ($column['Field'] === 'section_id') {
            $hasSectionId = true;
            echo " ✅";
        }
        echo "</li>";
    }
    echo "</ul>";
    
    if ($hasSectionId) {
        echo "<p>✅ section_id column exists</p>";
    } else {
        echo "<p>❌ section_id column missing</p>";
    }
    
    // Test 3: Check subjects grouped by sections
    echo "<h2>🎯 Test 3: Subjects Grouped by Sections</h2>";
    
    $year_levels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    
    foreach ($year_levels as $year_level) {
        echo "<h3>📖 " . htmlspecialchars($year_level) . "</h3>";
        
        // Get sections for this year level
        $database->query("SELECT section_id, section_name FROM sections WHERE year_level = :year_level AND status = 'available' ORDER BY section_name");
        $database->bind(':year_level', $year_level);
        $database->execute();
        $yearSections = $database->resultset();
        
        if (!empty($yearSections)) {
            foreach ($yearSections as $section) {
                echo "<h4>Section " . htmlspecialchars($section['section_name']) . "</h4>";
                
                // Get active subjects for this section
                $database->query("SELECT s.subject_id, s.subject_code, s.subject_name, s.units, s.semester, s.status, sec.section_name
                                 FROM subjects s
                                 LEFT JOIN sections sec ON s.section_id = sec.section_id
                                 WHERE s.year_level = :year_level AND s.section_id = :section_id AND s.status = 'available'
                                 ORDER BY s.subject_code");
                $database->bind(':year_level', $year_level);
                $database->bind(':section_id', $section['section_id']);
                $database->execute();
                $activeSubjects = $database->resultset();
                
                // Get inactive subjects for this section
                $database->query("SELECT s.subject_id, s.subject_code, s.subject_name, s.units, s.semester, s.status, sec.section_name
                                 FROM subjects s
                                 LEFT JOIN sections sec ON s.section_id = sec.section_id
                                 WHERE s.year_level = :year_level AND s.section_id = :section_id AND s.status = 'unavailable'
                                 ORDER BY s.subject_code");
                $database->bind(':year_level', $year_level);
                $database->bind(':section_id', $section['section_id']);
                $database->execute();
                $inactiveSubjects = $database->resultset();
                
                echo "<p><strong>Active Courses:</strong> " . count($activeSubjects) . "</p>";
                if (!empty($activeSubjects)) {
                    echo "<ul>";
                    foreach ($activeSubjects as $subject) {
                        echo "<li>" . htmlspecialchars($subject['subject_code']) . " - " . htmlspecialchars($subject['subject_name']) . " (" . $subject['units'] . " units, " . htmlspecialchars($subject['semester']) . ")</li>";
                    }
                    echo "</ul>";
                }
                
                echo "<p><strong>Inactive Courses:</strong> " . count($inactiveSubjects) . "</p>";
                if (!empty($inactiveSubjects)) {
                    echo "<ul>";
                    foreach ($inactiveSubjects as $subject) {
                        echo "<li>" . htmlspecialchars($subject['subject_code']) . " - " . htmlspecialchars($subject['subject_name']) . " (" . $subject['units'] . " units, " . htmlspecialchars($subject['semester']) . ")</li>";
                    }
                    echo "</ul>";
                }
            }
        } else {
            echo "<p>No sections available for " . htmlspecialchars($year_level) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8fafc; }
h1 { color: #1f2937; border-bottom: 3px solid #6366f1; padding-bottom: 10px; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
h3 { color: #4b5563; }
h4 { color: #6b7280; }
table { margin: 10px 0; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #6366f1; color: white; font-weight: 600; }
ul { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
li { margin: 5px 0; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
p { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 10px 0; }
</style>
