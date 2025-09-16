<?php
// Script to add default sections to the database
require_once 'config/database.php';

echo "<h2>Adding Default Sections to Database</h2>";

try {
    $database = new Database();
    
    // Check if sections already exist
    $database->query("SELECT COUNT(*) as count FROM sections");
    $database->execute();
    $result = $database->single();
    
    if ($result['count'] > 0) {
        echo "<p>⚠️ Sections already exist in database. Current count: " . $result['count'] . "</p>";
        
        // Show existing sections
        $database->query("SELECT * FROM sections ORDER BY year_level, section_name");
        $database->execute();
        $existing_sections = $database->resultset();
        
        echo "<h3>Existing Sections:</h3>";
        foreach ($existing_sections as $section) {
            $status_color = $section['status'] === 'available' ? 'green' : 'red';
            echo "<p style='color: {$status_color};'>- {$section['section_name']} ({$section['year_level']}) - {$section['status']}</p>";
        }
    } else {
        echo "<p>📝 No sections found. Adding default sections...</p>";
    }
    
    // Define default sections
    $default_sections = [
        ['section_name' => 'A', 'year_level' => '1st Year', 'status' => 'available'],
        ['section_name' => 'B', 'year_level' => '1st Year', 'status' => 'available'],
        ['section_name' => 'C', 'year_level' => '1st Year', 'status' => 'available'],
        ['section_name' => 'A', 'year_level' => '2nd Year', 'status' => 'available'],
        ['section_name' => 'B', 'year_level' => '2nd Year', 'status' => 'available'],
        ['section_name' => 'A', 'year_level' => '3rd Year', 'status' => 'available'],
        ['section_name' => 'B', 'year_level' => '3rd Year', 'status' => 'available'],
        ['section_name' => 'A', 'year_level' => '4th Year', 'status' => 'available'],
        ['section_name' => 'B', 'year_level' => '4th Year', 'status' => 'available'],
        ['section_name' => 'Einstein', 'year_level' => '1st Year', 'status' => 'unavailable'],
        ['section_name' => 'Newton', 'year_level' => '2nd Year', 'status' => 'unavailable']
    ];
    
    $added_count = 0;
    $skipped_count = 0;
    
    foreach ($default_sections as $section) {
        try {
            // Check if section already exists
            $database->query("SELECT COUNT(*) as count FROM sections WHERE section_name = ? AND year_level = ?");
            $database->bind(1, $section['section_name']);
            $database->bind(2, $section['year_level']);
            $database->execute();
            $exists = $database->single();
            
            if ($exists['count'] == 0) {
                // Insert new section
                $database->query("INSERT INTO sections (section_name, year_level, status) VALUES (?, ?, ?)");
                $database->bind(1, $section['section_name']);
                $database->bind(2, $section['year_level']);
                $database->bind(3, $section['status']);
                $database->execute();
                
                $status_icon = $section['status'] === 'available' ? '✅' : '⚠️';
                echo "<p>{$status_icon} Added: {$section['section_name']} ({$section['year_level']}) - {$section['status']}</p>";
                $added_count++;
            } else {
                echo "<p>⏭️ Skipped: {$section['section_name']} ({$section['year_level']}) - already exists</p>";
                $skipped_count++;
            }
        } catch (Exception $e) {
            echo "<p>❌ Error adding {$section['section_name']} ({$section['year_level']}): " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>✅ Added: {$added_count} sections</p>";
    echo "<p>⏭️ Skipped: {$skipped_count} sections</p>";
    
    // Show final sections count
    $database->query("SELECT COUNT(*) as count FROM sections");
    $database->execute();
    $final_result = $database->single();
    echo "<p><strong>Total sections in database: {$final_result['count']}</strong></p>";
    
    // Show available sections by year level
    echo "<h3>Available Sections by Year Level:</h3>";
    $year_levels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    
    foreach ($year_levels as $year_level) {
        $database->query("SELECT section_name, status FROM sections WHERE year_level = ? ORDER BY section_name");
        $database->bind(1, $year_level);
        $database->execute();
        $sections = $database->resultset();
        
        echo "<p><strong>{$year_level}:</strong></p>";
        if (count($sections) > 0) {
            foreach ($sections as $section) {
                $status_color = $section['status'] === 'available' ? 'green' : 'red';
                echo "<p style='color: {$status_color}; margin-left: 20px;'>- {$section['section_name']} ({$section['status']})</p>";
            }
        } else {
            echo "<p style='color: orange; margin-left: 20px;'>- No sections defined</p>";
        }
    }
    
    echo "<h3>🎉 Next Steps:</h3>";
    echo "<p>1. <strong>Refresh your sections dashboard</strong> - you should now see these sections</p>";
    echo "<p>2. <strong>Test the course dropdowns</strong> - they should now populate with sections</p>";
    echo "<p>3. <strong>Run the database migration</strong> if you haven't already: <code>add_section_to_subjects.sql</code></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Make sure your database connection is working and the sections table exists.</p>";
}
?>
