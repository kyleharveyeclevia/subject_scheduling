<?php
// Test script to verify first year courses fix
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test First Year Courses Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .test-section { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test First Year Courses Fix</h1>
        <p>This script tests whether the first year courses fix is working properly.</p>";

try {
    $database = new Database();
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Test 1: Check sections table
    echo "<div class='test-section'>
        <h3>Test 1: Sections Table</h3>";
    
    $database->query("SELECT COUNT(*) as count FROM sections WHERE year_level = '1st Year' AND status = 'available'");
    $database->execute();
    $firstYearSections = $database->single();
    
    if ($firstYearSections['count'] > 0) {
        echo "<div class='success'>✅ Found " . $firstYearSections['count'] . " available sections for 1st Year</div>";
        
        // Show the sections
        $database->query("SELECT * FROM sections WHERE year_level = '1st Year' AND status = 'available' ORDER BY section_name");
        $database->execute();
        $sections = $database->resultset();
        
        echo "<h4>1st Year Sections:</h4>
        <table>
            <tr><th>Section ID</th><th>Section Name</th><th>Status</th></tr>";
        
        foreach ($sections as $section) {
            echo "<tr>
                <td>{$section['section_id']}</td>
                <td>{$section['section_name']}</td>
                <td>{$section['status']}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No available sections found for 1st Year</div>";
    }
    echo "</div>";
    
    // Test 2: Check subjects table structure
    echo "<div class='test-section'>
        <h3>Test 2: Subjects Table Structure</h3>";
    
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    $requiredColumns = ['section_id', 'semester', 'academic_year'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<div class='success'>✅ All required columns exist in subjects table</div>";
    } else {
        echo "<div class='error'>❌ Missing columns: " . implode(', ', $missingColumns) . "</div>";
    }
    
    echo "<h4>Subjects Table Columns:</h4>
    <table>
        <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>
            <td>{$column['Field']}</td>
            <td>{$column['Type']}</td>
            <td>{$column['Null']}</td>
            <td>{$column['Key']}</td>
        </tr>";
    }
    echo "</table></div>";
    
    // Test 3: Check subjects data for first year
    echo "<div class='test-section'>
        <h3>Test 3: First Year Subjects Data</h3>";
    
    // Test the exact query the dashboard uses
    $database->query("SELECT s.*, sec.section_name 
                     FROM subjects s 
                     LEFT JOIN sections sec ON s.section_id = sec.section_id 
                     WHERE s.year_level = '1st Year' AND s.semester = 'first' AND s.status = 'available'");
    $database->execute();
    $firstYearSubjects = $database->resultset();
    
    if (count($firstYearSubjects) > 0) {
        echo "<div class='success'>✅ Found " . count($firstYearSubjects) . " active subjects for 1st Year, 1st Semester</div>";
        
        echo "<h4>First Year Subjects (1st Semester):</h4>
        <table>
            <tr><th>Subject Code</th><th>Subject Name</th><th>Units</th><th>Section</th><th>Status</th></tr>";
        
        foreach ($firstYearSubjects as $subject) {
            $sectionName = $subject['section_name'] ?? 'N/A';
            echo "<tr>
                <td>{$subject['subject_code']}</td>
                <td>{$subject['subject_name']}</td>
                <td>{$subject['units']}</td>
                <td>{$sectionName}</td>
                <td>{$subject['status']}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No subjects found for 1st Year, 1st Semester</div>";
        
        // Check what subjects exist for first year
        $database->query("SELECT * FROM subjects WHERE year_level = '1st Year'");
        $database->execute();
        $allFirstYearSubjects = $database->resultset();
        
        if (count($allFirstYearSubjects) > 0) {
            echo "<div class='warning'>⚠️ Found " . count($allFirstYearSubjects) . " subjects for 1st Year, but they may not have proper semester/section data</div>";
            
            echo "<h4>All 1st Year Subjects:</h4>
            <table>
                <tr><th>Subject Code</th><th>Subject Name</th><th>Semester</th><th>Section ID</th><th>Status</th></tr>";
            
            foreach ($allFirstYearSubjects as $subject) {
                $semester = $subject['semester'] ?? 'NULL';
                $sectionId = $subject['section_id'] ?? 'NULL';
                echo "<tr>
                    <td>{$subject['subject_code']}</td>
                    <td>{$subject['subject_name']}</td>
                    <td>{$semester}</td>
                    <td>{$sectionId}</td>
                    <td>{$subject['status']}</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ No subjects found for 1st Year at all</div>";
        }
    }
    echo "</div>";
    
    // Test 4: Test the dashboard query logic
    echo "<div class='test-section'>
        <h3>Test 4: Dashboard Query Logic</h3>";
    
    // Simulate the dashboard's query process
    $database->query("SELECT section_id, section_name FROM sections WHERE year_level = '1st Year' AND status = 'available' ORDER BY section_name");
    $database->execute();
    $availableSections = $database->resultset();
    
    echo "<div class='info'>ℹ️ Dashboard query process for 1st Year:</div>";
    echo "<div class='info'>1. Found " . count($availableSections) . " available sections</div>";
    
    if (count($availableSections) > 0) {
        $totalSubjects = 0;
        foreach ($availableSections as $section) {
            $database->query("SELECT COUNT(*) as count FROM subjects 
                            WHERE status = 'available' AND year_level = '1st Year' AND semester = 'first' AND section_id = :section_id");
            $database->bind(':section_id', $section['section_id']);
            $database->execute();
            $subjectCount = $database->single();
            
            echo "<div class='info'>2. Section {$section['section_name']}: {$subjectCount['count']} subjects</div>";
            $totalSubjects += $subjectCount['count'];
        }
        
        echo "<div class='success'>✅ Total subjects found: {$totalSubjects}</div>";
        
        if ($totalSubjects > 0) {
            echo "<div class='success'>🎉 The fix is working! First year courses should now display in the dashboard.</div>";
        } else {
            echo "<div class='warning'>⚠️ Sections exist but no subjects are assigned to them</div>";
        }
    } else {
        echo "<div class='error'>❌ No available sections found for 1st Year</div>";
    }
    echo "</div>";
    
    // Test 5: Summary
    echo "<div class='test-section'>
        <h3>Test 5: Summary</h3>";
    
    $allTestsPassed = true;
    $issues = [];
    
    if ($firstYearSections['count'] == 0) {
        $allTestsPassed = false;
        $issues[] = "No sections for 1st Year";
    }
    
    if (!empty($missingColumns)) {
        $allTestsPassed = false;
        $issues[] = "Missing columns: " . implode(', ', $missingColumns);
    }
    
    if (count($firstYearSubjects) == 0) {
        $allTestsPassed = false;
        $issues[] = "No subjects found for 1st Year, 1st Semester";
    }
    
    if ($allTestsPassed) {
        echo "<div class='success'>
            <h4>🎉 All Tests Passed!</h4>
            <p>The first year courses fix is working correctly. You should now see first year data in the course dashboard.</p>
        </div>";
    } else {
        echo "<div class='error'>
            <h4>❌ Tests Failed</h4>
            <p>The following issues were found:</p>
            <ul>";
        
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        
        echo "</ul>
            <p>Please run the <code>fix_first_year_courses.php</code> script to resolve these issues.</p>
        </div>";
    }
    
    echo "</div>";
    
    // Navigation
    echo "<div style='margin-top: 20px; text-align: center;'>
        <a href='fix_first_year_courses.php' class='btn'>🔧 Run Fix Script</a>
        <a href='dashboards/admin/Courses-dashboard.php' class='btn btn-success'>🚀 Go to Course Dashboard</a>
        <a href='dashboards/admin/dashboard.php' class='btn'>🏠 Admin Dashboard</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Error Occurred</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
        <p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>
    </div>";
    
    echo "<div style='margin-top: 20px; text-align: center;'>
        <a href='fix_first_year_courses.php' class='btn'>🔧 Run Fix Script</a>
    </div>";
}

echo "</div></body></html>";
?>
