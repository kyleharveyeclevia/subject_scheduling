<?php
// Simple script to just read existing sections from database
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Read Database Sections</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .section-id { font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📋 Your Database Sections</h1>
        <p>Reading existing sections from your database...</p>";

try {
    $database = new Database();
    echo "<div class='success'>✅ Database connected</div>";
    
    // Read all sections
    $database->query("SELECT * FROM sections ORDER BY year_level, section_name");
    $database->execute();
    $sections = $database->resultset();
    
    if (empty($sections)) {
        echo "<div class='info'>ℹ️ No sections found in database</div>";
    } else {
        echo "<div class='success'>✅ Found " . count($sections) . " sections</div>";
        
        echo "<h2>📊 All Sections:</h2>
        <table>
            <tr>
                <th>Section ID</th>
                <th>Section Name</th>
                <th>Year Level</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>";
        
        foreach ($sections as $section) {
            echo "<tr>
                <td class='section-id'>{$section['section_id']}</td>
                <td>{$section['section_name']}</td>
                <td>{$section['year_level']}</td>
                <td>{$section['status']}</td>
                <td>{$section['created_at']}</td>
            </tr>";
        }
        echo "</table>";
        
        // Group by year level
        echo "<h2>📚 Sections by Year Level:</h2>";
        $database->query("SELECT year_level, COUNT(*) as count FROM sections GROUP BY year_level ORDER BY year_level");
        $database->execute();
        $yearLevelCounts = $database->resultset();
        
        foreach ($yearLevelCounts as $ylc) {
            echo "<p><strong>{$ylc['year_level']}:</strong> {$ylc['count']} sections</p>";
        }
        
        // Check specifically for 1st Year
        echo "<h2>🎯 1st Year Sections:</h2>";
        $database->query("SELECT * FROM sections WHERE year_level = '1st Year' ORDER BY section_name");
        $database->execute();
        $firstYearSections = $database->resultset();
        
        if (empty($firstYearSections)) {
            echo "<div class='info'>ℹ️ No sections found for 1st Year</div>";
        } else {
            echo "<div class='success'>✅ Found " . count($firstYearSections) . " sections for 1st Year</div>";
            
            echo "<table>
                <tr>
                    <th>Section ID</th>
                    <th>Section Name</th>
                    <th>Status</th>
                </tr>";
            
            foreach ($firstYearSections as $section) {
                echo "<tr>
                    <td class='section-id'>{$section['section_id']}</td>
                    <td>{$section['section_name']}</td>
                    <td>{$section['status']}</td>
                </tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Error:</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

echo "</div></body></html>";
?>
