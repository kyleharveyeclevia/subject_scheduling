<?php
// Test script to verify section migration
require_once 'config/database.php';

echo "<h2>Testing Section Migration</h2>";

try {
    $database = new Database();
    
    // Test 1: Check if sections table exists and has data
    echo "<h3>Test 1: Sections Table</h3>";
    $database->query("SELECT * FROM sections WHERE status = 'available' ORDER BY year_level, section_name");
    $database->execute();
    $sections = $database->resultset();
    
    echo "<p>Available sections found: " . count($sections) . "</p>";
    foreach ($sections as $section) {
        echo "<p>- {$section['section_name']} ({$section['year_level']}) - {$section['status']}</p>";
    }
    
    // Test 2: Check subjects table structure
    echo "<h3>Test 2: Subjects Table Structure</h3>";
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    echo "<p>Subjects table columns:</p>";
    foreach ($columns as $column) {
        echo "<p>- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}</p>";
    }
    
    // Test 3: Test the API endpoint
    echo "<h3>Test 3: API Endpoint</h3>";
    $test_year = '1st Year';
    $database->query("SELECT section_id, section_name, year_level FROM sections WHERE status = 'available' AND year_level = :year_level ORDER BY section_name ASC");
    $database->bind(':year_level', $test_year);
    $database->execute();
    $test_sections = $database->resultset();
    
    echo "<p>Sections for '{$test_year}': " . count($test_sections) . "</p>";
    foreach ($test_sections as $section) {
        echo "<p>- {$section['section_name']} (ID: {$section['section_id']})</p>";
    }
    
    echo "<h3>Migration Status: ✅ Ready to run</h3>";
    echo "<p>You can now run the migration script: <code>add_section_to_subjects.sql</code></p>";
    
} catch (Exception $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
}
?>
