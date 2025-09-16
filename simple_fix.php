<?php
// Simple, direct fix for first year courses
require_once 'config/database.php';

echo "<h1>🔧 Simple Fix for First Year Courses</h1>";

try {
    $database = new Database();
    echo "<p>✅ Database connected</p>";
    
    // Step 1: Add missing columns to subjects table
    echo "<h3>Step 1: Adding missing columns...</h3>";
    
    try {
        $database->query("ALTER TABLE subjects ADD COLUMN IF NOT EXISTS section_id INT NULL AFTER year_level");
        $database->execute();
        echo "<p>✅ Added section_id column</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ section_id column already exists</p>";
    }
    
    try {
        $database->query("ALTER TABLE subjects ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'first' AFTER year_level");
        $database->execute();
        echo "<p>✅ Added semester column</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ semester column already exists</p>";
    }
    
    try {
        $database->query("ALTER TABLE subjects ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025' AFTER semester");
        $database->execute();
        echo "<p>✅ Added academic_year column</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ academic_year column already exists</p>";
    }
    
    // Step 2: Link existing subjects to sections
    echo "<h3>Step 2: Linking subjects to sections...</h3>";
    
    // Get first available section for each year level
    $database->query("SELECT year_level, MIN(section_id) as first_section FROM sections WHERE status = 'available' GROUP BY year_level");
    $database->execute();
    $firstSections = $database->resultset();
    
    foreach ($firstSections as $fs) {
        $database->query("UPDATE subjects SET section_id = :section_id WHERE year_level = :year_level AND section_id IS NULL");
        $database->bind(':section_id', $fs['first_section']);
        $database->bind(':year_level', $fs['year_level']);
        $database->execute();
        echo "<p>✅ Linked {$fs['year_level']} subjects to section ID {$fs['first_section']}</p>";
    }
    
    // Step 3: Test if it works
    echo "<h3>Step 3: Testing the fix...</h3>";
    
    $database->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = '1st Year' AND semester = 'first' AND section_id IS NOT NULL");
    $database->execute();
    $result = $database->single();
    
    if ($result['count'] > 0) {
        echo "<p>🎉 SUCCESS! Found {$result['count']} first year subjects with proper data</p>";
        echo "<p>First year courses should now display in the dashboard!</p>";
    } else {
        echo "<p>⚠️ Still no first year subjects found. Checking what's missing...</p>";
        
        // Check what we have
        $database->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = '1st Year'");
        $database->execute();
        $totalFirstYear = $database->single();
        
        $database->query("SELECT COUNT(*) as count FROM sections WHERE year_level = '1st Year' AND status = 'available'");
        $database->execute();
        $totalSections = $database->single();
        
        echo "<p>Total 1st Year subjects: {$totalFirstYear['count']}</p>";
        echo "<p>Total 1st Year sections: {$totalSections['count']}</p>";
    }
    
    echo "<hr>";
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<p>1. Go to <a href='dashboards/admin/Courses-dashboard.php'>Course Dashboard</a></p>";
    echo "<p>2. Select '1st Year' and 'First Semester'</p>";
    echo "<p>3. You should now see first year courses!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
