<?php
// Test script to verify the API endpoint is working
echo "<h2>Testing API Endpoint</h2>";

// Test the API endpoint directly
$test_url = "http://localhost/nls2/api/get-active-sections.php?year_level=1st%20Year";
echo "<p><strong>Testing URL:</strong> <a href='{$test_url}' target='_blank'>{$test_url}</a></p>";

// Test with different year levels
$year_levels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

foreach ($year_levels as $year_level) {
    echo "<h3>Testing Year Level: {$year_level}</h3>";
    
    $url = "http://localhost/nls2/api/get-active-sections.php?year_level=" . urlencode($year_level);
    
    try {
        $response = file_get_contents($url);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                if ($data['success']) {
                    echo "<p style='color: green;'>✅ Success! Found " . count($data['sections']) . " sections</p>";
                    if (count($data['sections']) > 0) {
                        echo "<ul>";
                        foreach ($data['sections'] as $section) {
                            echo "<li>{$section['section_name']} (ID: {$section['section_id']})</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ No sections found for this year level</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ API Error: {$data['message']}</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Invalid JSON response</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to fetch from API</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Test the database directly
echo "<h2>Direct Database Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    
    // Check sections table
    $database->query("SELECT COUNT(*) as count FROM sections");
    $database->execute();
    $result = $database->single();
    echo "<p><strong>Total sections in database:</strong> {$result['count']}</p>";
    
    // Check sections by year level
    foreach ($year_levels as $year_level) {
        $database->query("SELECT COUNT(*) as count FROM sections WHERE year_level = ? AND status = 'available'");
        $database->bind(1, $year_level);
        $database->execute();
        $count = $database->single();
        
        echo "<p><strong>{$year_level} (available):</strong> {$count['count']} sections</p>";
        
        if ($count['count'] > 0) {
            $database->query("SELECT section_id, section_name, status FROM sections WHERE year_level = ? AND status = 'available' ORDER BY section_name");
            $database->bind(1, $year_level);
            $database->execute();
            $sections = $database->resultset();
            
            echo "<ul>";
            foreach ($sections as $section) {
                echo "<li>{$section['section_name']} (ID: {$section['section_id']}) - {$section['status']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
