<?php
// Simple script to read section IDs and data from the database
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Check Database Sections</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Database Sections Check</h1>
        <p>This script reads and displays the current section data from your database.</p>";

try {
    $database = new Database();
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Check if sections table exists
    echo "<h2>📋 Checking Sections Table</h2>";
    
    $database->query("SHOW TABLES LIKE 'sections'");
    $database->execute();
    $sectionsTable = $database->resultset();
    
    if (empty($sectionsTable)) {
        echo "<div class='error'>❌ Sections table does not exist</div>";
        echo "<p>The sections table is missing. This is why first year courses aren't showing.</p>";
    } else {
        echo "<div class='success'>✅ Sections table exists</div>";
        
        // Read all sections
        $database->query("SELECT * FROM sections ORDER BY year_level, section_name");
        $database->execute();
        $sections = $database->resultset();
        
        if (empty($sections)) {
            echo "<div class='warning'>⚠️ Sections table exists but has no data</div>";
        } else {
            echo "<div class='success'>✅ Found " . count($sections) . " sections</div>";
            
            echo "<h3>📊 All Sections in Database:</h3>
            <table>
                <tr><th>Section ID</th><th>Section Name</th><th>Year Level</th><th>Status</th><th>Created At</th></tr>";
            
            foreach ($sections as $section) {
                echo "<tr>
                    <td><strong>{$section['section_id']}</strong></td>
                    <td>{$section['section_name']}</td>
                    <td>{$section['year_level']}</td>
                    <td>{$section['status']}</td>
                    <td>{$section['created_at']}</td>
                </tr>";
            }
            echo "</table>";
            
            // Check sections by year level
            echo "<h3>📚 Sections by Year Level:</h3>";
            $database->query("SELECT year_level, COUNT(*) as count FROM sections GROUP BY year_level ORDER BY year_level");
            $database->execute();
            $yearLevelCounts = $database->resultset();
            
            foreach ($yearLevelCounts as $ylc) {
                echo "<p><strong>{$ylc['year_level']}:</strong> {$ylc['count']} sections</p>";
            }
        }
    }
    
    // Check subjects table structure
    echo "<h2>📚 Checking Subjects Table</h2>";
    
    $database->query("SHOW TABLES LIKE 'subjects'");
    $database->execute();
    $subjectsTable = $database->resultset();
    
    if (empty($subjectsTable)) {
        echo "<div class='error'>❌ Subjects table does not exist</div>";
    } else {
        echo "<div class='success'>✅ Subjects table exists</div>";
        
        // Check table structure
        $database->query("DESCRIBE subjects");
        $database->execute();
        $columns = $database->resultset();
        
        echo "<h3>📋 Subjects Table Structure:</h3>
        <table>
            <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            $default = $column['Default'] ?? 'NULL';
            echo "<tr>
                <td>{$column['Field']}</td>
                <td>{$column['Type']}</td>
                <td>{$column['Null']}</td>
                <td>{$column['Key']}</td>
                <td>{$default}</td>
                <td>{$column['Extra']}</td>
            </tr>";
        }
        echo "</table>";
        
        // Check if section_id column exists
        $hasSectionId = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'section_id') {
                $hasSectionId = true;
                break;
            }
        }
        
        if ($hasSectionId) {
            echo "<div class='success'>✅ section_id column exists in subjects table</div>";
        } else {
            echo "<div class='error'>❌ section_id column is missing from subjects table</div>";
        }
        
        // Check subjects data
        $database->query("SELECT COUNT(*) as count FROM subjects");
        $database->execute();
        $subjectsCount = $database->single();
        
        echo "<h3>📊 Subjects Data:</h3>";
        echo "<p><strong>Total subjects:</strong> {$subjectsCount['count']}</p>";
        
        if ($subjectsCount['count'] > 0) {
            // Show sample subjects
            $database->query("SELECT * FROM subjects LIMIT 10");
            $database->execute();
            $sampleSubjects = $database->resultset();
            
            echo "<h4>Sample Subjects (first 10):</h4>
            <table>
                <tr><th>Subject ID</th><th>Subject Code</th><th>Subject Name</th><th>Year Level</th><th>Section ID</th><th>Status</th></tr>";
            
            foreach ($sampleSubjects as $subject) {
                $sectionId = $subject['section_id'] ?? 'NULL';
                echo "<tr>
                    <td>{$subject['subject_id']}</td>
                    <td>{$subject['subject_code']}</td>
                    <td>{$subject['subject_name']}</td>
                    <td>{$subject['year_level']}</td>
                    <td>{$sectionId}</td>
                    <td>{$subject['status']}</td>
                </tr>";
            }
            echo "</table>";
            
            // Check subjects by year level
            $database->query("SELECT year_level, COUNT(*) as count FROM subjects GROUP BY year_level ORDER BY year_level");
            $database->execute();
            $subjectsByYear = $database->resultset();
            
            echo "<h4>Subjects by Year Level:</h4>";
            foreach ($subjectsByYear as $sby) {
                echo "<p><strong>{$sby['year_level']}:</strong> {$sby['count']} subjects</p>";
            }
        }
    }
    
    // Test the relationship
    echo "<h2>🔗 Testing Section-Subject Relationship</h2>";
    
    if ($hasSectionId && !empty($sections)) {
        // Test JOIN query
        $database->query("SELECT s.subject_code, s.subject_name, s.year_level, sec.section_name, sec.section_id
                         FROM subjects s 
                         LEFT JOIN sections sec ON s.section_id = sec.section_id 
                         WHERE s.year_level = '1st Year'
                         LIMIT 10");
        $database->execute();
        $joinedData = $database->resultset();
        
        if (empty($joinedData)) {
            echo "<div class='warning'>⚠️ No subjects found for 1st Year</div>";
        } else {
            echo "<div class='success'>✅ Found " . count($joinedData) . " subjects for 1st Year with section data</div>";
            
            echo "<h4>1st Year Subjects with Section Info:</h4>
            <table>
                <tr><th>Subject Code</th><th>Subject Name</th><th>Year Level</th><th>Section Name</th><th>Section ID</th></tr>";
            
            foreach ($joinedData as $data) {
                $sectionName = $data['section_name'] ?? 'NULL';
                $sectionId = $data['section_id'] ?? 'NULL';
                echo "<tr>
                    <td>{$data['subject_code']}</td>
                    <td>{$data['subject_name']}</td>
                    <td>{$data['year_level']}</td>
                    <td>{$sectionName}</td>
                    <td>{$sectionId}</td>
                </tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div class='error'>❌ Cannot test relationship - missing section_id column or sections data</div>";
    }
    
    // Summary
    echo "<h2>📋 Summary</h2>";
    
    $issues = [];
    if (empty($sectionsTable)) {
        $issues[] = "Missing sections table";
    } elseif (empty($sections)) {
        $issues[] = "Sections table is empty";
    }
    
    if (!$hasSectionId) {
        $issues[] = "Missing section_id column in subjects table";
    }
    
    if (empty($issues)) {
        echo "<div class='success'>
            <h3>✅ Database Structure Looks Good!</h3>
            <p>Your database has the necessary structure for first year courses to display.</p>
        </div>";
    } else {
        echo "<div class='error'>
            <h3>❌ Issues Found:</h3>
            <ul>";
        
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        
        echo "</ul>
            <p>These issues need to be resolved for first year courses to show properly.</p>
        </div>";
    }
    
    echo "<div style='margin-top: 20px; text-align: center;'>
        <a href='fix_first_year_courses.php' class='btn'>🔧 Run Fix Script</a>
        <a href='dashboards/admin/Courses-dashboard.php' class='btn'>🚀 Go to Course Dashboard</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Error Occurred</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
        <p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>
    </div>";
}

echo "</div></body></html>";
?>
