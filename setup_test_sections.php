<?php
/**
 * Setup test sections for course duplication testing
 * This script creates sample sections for each year level if they don't exist
 */

require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Setting up Test Sections</h2>";
    
    // Check if sections table exists and has data
    $db->query("SELECT COUNT(*) as count FROM sections WHERE status = 'available'");
    $result = $db->single();
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✅ Sections already exist ({$result['count']} active sections)</p>";
        
        // Show existing sections
        $db->query("SELECT year_level, section_name FROM sections WHERE status = 'available' ORDER BY year_level, section_name");
        $sections = $db->resultset();
        
        $sections_by_year = [];
        foreach ($sections as $section) {
            $sections_by_year[$section['year_level']][] = $section['section_name'];
        }
        
        echo "<h3>Existing Sections:</h3>";
        foreach ($sections_by_year as $year_level => $section_names) {
            echo "<p><strong>{$year_level}:</strong> " . implode(', ', $section_names) . "</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No active sections found. Creating sample sections...</p>";
        
        // Create sample sections for each year level
        $sample_sections = [
            '1st Year' => ['1A', '1B', '1C'],
            '2nd Year' => ['2A', '2B', '2C'],
            '3rd Year' => ['3A', '3B'],
            '4th Year' => ['4A', '4B']
        ];
        
        $db->beginTransaction();
        
        try {
            $total_created = 0;
            
            foreach ($sample_sections as $year_level => $section_names) {
                echo "<p>Creating sections for <strong>{$year_level}</strong>...</p>";
                
                foreach ($section_names as $section_name) {
                    // Check if section already exists
                    $db->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ? AND year_level = ?");
                    $db->bind(1, $section_name);
                    $db->bind(2, $year_level);
                    $existing = $db->single();
                    
                    if ($existing['count'] == 0) {
                        // Insert new section
                        $db->query("INSERT INTO sections (section_name, year_level, status) VALUES (?, ?, 'available')");
                        $db->bind(1, $section_name);
                        $db->bind(2, $year_level);
                        
                        if ($db->execute()) {
                            echo "<p style='color: green;'>✅ Created section: {$section_name} ({$year_level})</p>";
                            $total_created++;
                        } else {
                            echo "<p style='color: red;'>❌ Failed to create section: {$section_name} ({$year_level})</p>";
                        }
                    } else {
                        echo "<p style='color: blue;'>ℹ️ Section already exists: {$section_name} ({$year_level})</p>";
                    }
                }
            }
            
            $db->commit();
            echo "<p style='color: green; font-weight: bold;'>✅ Successfully created {$total_created} new sections!</p>";
            
        } catch (Exception $e) {
            $db->rollback();
            echo "<p style='color: red;'>❌ Error creating sections: " . $e->getMessage() . "</p>";
        }
    }
    
    // Final verification
    echo "<h3>Final Section Count by Year Level:</h3>";
    $db->query("SELECT year_level, COUNT(*) as count FROM sections WHERE status = 'available' GROUP BY year_level ORDER BY year_level");
    $final_counts = $db->resultset();
    
    foreach ($final_counts as $count) {
        echo "<p><strong>{$count['year_level']}:</strong> {$count['count']} sections</p>";
    }
    
    echo "<p><a href='test_course_duplication.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Run Course Duplication Test</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
