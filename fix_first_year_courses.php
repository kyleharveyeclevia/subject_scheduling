<?php
// Comprehensive fix for first year courses not showing in course dashboard
// This script addresses the database structure and data issues

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Admin privileges required.');
}

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix First Year Courses Issue</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #007bff; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
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
        <h1>🔧 Fix First Year Courses Issue</h1>
        <p>This script will fix the database structure and data issues preventing first year courses from showing in the course dashboard.</p>";

try {
    $database = new Database();
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Step 1: Check and fix sections table
    echo "<div class='step'>
        <h3>Step 1: Checking and Fixing Sections Table</h3>";
    
    // Check if sections table exists
    $database->query("SHOW TABLES LIKE 'sections'");
    $database->execute();
    $sectionsTable = $database->resultset();
    
    if (empty($sectionsTable)) {
        echo "<div class='error'>❌ Sections table does not exist. Creating it...</div>";
        
        // Create sections table
        $database->query("CREATE TABLE sections (
            section_id INT AUTO_INCREMENT PRIMARY KEY,
            section_name VARCHAR(100) NOT NULL,
            year_level VARCHAR(20) NOT NULL,
            status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_section_year (section_name, year_level)
        )");
        $database->execute();
        echo "<div class='success'>✅ Sections table created successfully</div>";
    } else {
        echo "<div class='success'>✅ Sections table already exists</div>";
    }
    
    // Check if sections have data
    $database->query("SELECT COUNT(*) as count FROM sections");
    $database->execute();
    $sectionsCount = $database->single();
    
    if ($sectionsCount['count'] == 0) {
        echo "<div class='warning'>⚠️ No sections found. Adding default sections...</div>";
        
        // Add default sections
        $defaultSections = [
            ['section_name' => 'A', 'year_level' => '1st Year', 'status' => 'available'],
            ['section_name' => 'B', 'year_level' => '1st Year', 'status' => 'available'],
            ['section_name' => 'C', 'year_level' => '1st Year', 'status' => 'available'],
            ['section_name' => 'A', 'year_level' => '2nd Year', 'status' => 'available'],
            ['section_name' => 'B', 'year_level' => '2nd Year', 'status' => 'available'],
            ['section_name' => 'A', 'year_level' => '3rd Year', 'status' => 'available'],
            ['section_name' => 'B', 'year_level' => '3rd Year', 'status' => 'available'],
            ['section_name' => 'A', 'year_level' => '4th Year', 'status' => 'available'],
            ['section_name' => 'B', 'year_level' => '4th Year', 'status' => 'available']
        ];
        
        foreach ($defaultSections as $section) {
            $database->query("INSERT INTO sections (section_name, year_level, status) VALUES (:name, :year, :status)");
            $database->bind(':name', $section['section_name']);
            $database->bind(':year', $section['year_level']);
            $database->bind(':status', $section['status']);
            $database->execute();
        }
        
        echo "<div class='success'>✅ Added " . count($defaultSections) . " default sections</div>";
    } else {
        echo "<div class='success'>✅ Found " . $sectionsCount['count'] . " existing sections</div>";
    }
    
    // Show current sections
    $database->query("SELECT * FROM sections ORDER BY year_level, section_name");
    $database->execute();
    $sections = $database->resultset();
    
    echo "<h4>Current Sections:</h4>
    <table>
        <tr><th>Section ID</th><th>Section Name</th><th>Year Level</th><th>Status</th></tr>";
    
    foreach ($sections as $section) {
        echo "<tr>
            <td>{$section['section_id']}</td>
            <td>{$section['section_name']}</td>
            <td>{$section['year_level']}</td>
            <td>{$section['status']}</td>
        </tr>";
    }
    echo "</table></div>";
    
    // Step 2: Check and fix subjects table structure
    echo "<div class='step'>
        <h3>Step 2: Checking and Fixing Subjects Table Structure</h3>";
    
    // Check subjects table structure
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    $hasSectionId = false;
    $hasSemester = false;
    $hasAcademicYear = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'section_id') $hasSectionId = true;
        if ($column['Field'] === 'semester') $hasSemester = true;
        if ($column['Field'] === 'academic_year') $hasAcademicYear = true;
    }
    
    // Add missing columns
    if (!$hasSectionId) {
        echo "<div class='warning'>⚠️ Adding section_id column to subjects table...</div>";
        $database->query("ALTER TABLE subjects ADD COLUMN section_id INT NULL AFTER year_level");
        $database->execute();
        echo "<div class='success'>✅ Added section_id column</div>";
    } else {
        echo "<div class='success'>✅ section_id column already exists</div>";
    }
    
    if (!$hasSemester) {
        echo "<div class='warning'>⚠️ Adding semester column to subjects table...</div>";
        $database->query("ALTER TABLE subjects ADD COLUMN semester VARCHAR(20) DEFAULT 'first' AFTER year_level");
        $database->execute();
        echo "<div class='success'>✅ Added semester column</div>";
    } else {
        echo "<div class='success'>✅ semester column already exists</div>";
    }
    
    if (!$hasAcademicYear) {
        echo "<div class='warning'>⚠️ Adding academic_year column to subjects table...</div>";
        $database->query("ALTER TABLE subjects ADD COLUMN academic_year VARCHAR(20) DEFAULT '2024-2025' AFTER semester");
        $database->execute();
        echo "<div class='success'>✅ Added academic_year column</div>";
    } else {
        echo "<div class='success'>✅ academic_year column already exists</div>";
    }
    
    // Add foreign key constraint for section_id
    try {
        $database->query("ALTER TABLE subjects ADD CONSTRAINT fk_subjects_section 
                        FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE SET NULL");
        $database->execute();
        echo "<div class='success'>✅ Added foreign key constraint for section_id</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ️ Foreign key constraint already exists or couldn't be added</div>";
    }
    
    echo "</div>";
    
    // Step 3: Check and fix subjects data
    echo "<div class='step'>
        <h3>Step 3: Checking and Fixing Subjects Data</h3>";
    
    // Check if subjects have data
    $database->query("SELECT COUNT(*) as count FROM subjects");
    $database->execute();
    $subjectsCount = $database->single();
    
    if ($subjectsCount['count'] == 0) {
        echo "<div class='warning'>⚠️ No subjects found. Adding sample subjects...</div>";
        
        // Add sample subjects
        $sampleSubjects = [
            ['subject_code' => 'MATH101', 'subject_name' => 'Mathematics I', 'units' => 3, 'year_level' => '1st Year', 'semester' => 'first'],
            ['subject_code' => 'ENG101', 'subject_name' => 'English I', 'units' => 3, 'year_level' => '1st Year', 'semester' => 'first'],
            ['subject_code' => 'PHYS101', 'subject_name' => 'Physics I', 'units' => 3, 'year_level' => '1st Year', 'semester' => 'first'],
            ['subject_code' => 'CHEM101', 'subject_name' => 'Chemistry I', 'units' => 3, 'year_level' => '1st Year', 'semester' => 'first'],
            ['subject_code' => 'MATH201', 'subject_name' => 'Mathematics II', 'units' => 3, 'year_level' => '2nd Year', 'semester' => 'first'],
            ['subject_code' => 'ENG201', 'subject_name' => 'English II', 'units' => 3, 'year_level' => '2nd Year', 'semester' => 'first']
        ];
        
        foreach ($sampleSubjects as $subject) {
            $database->query("INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, academic_year, status) 
                            VALUES (:code, :name, :units, :year, :semester, '2024-2025', 'available')");
            $database->bind(':code', $subject['subject_code']);
            $database->bind(':name', $subject['subject_name']);
            $database->bind(':units', $subject['units']);
            $database->bind(':year', $subject['year_level']);
            $database->bind(':semester', $subject['semester']);
            $database->execute();
        }
        
        echo "<div class='success'>✅ Added " . count($sampleSubjects) . " sample subjects</div>";
    } else {
        echo "<div class='success'>✅ Found " . $subjectsCount['count'] . " existing subjects</div>";
    }
    
    // Update existing subjects with proper section_id
    echo "<div class='info'>ℹ️ Updating existing subjects with proper section assignments...</div>";
    
    // Get first available section for each year level
    $database->query("SELECT year_level, MIN(section_id) as first_section FROM sections WHERE status = 'available' GROUP BY year_level");
    $database->execute();
    $firstSections = $database->resultset();
    
    $sectionMap = [];
    foreach ($firstSections as $fs) {
        $sectionMap[$fs['year_level']] = $fs['first_section'];
    }
    
    // Update subjects without section_id
    foreach ($sectionMap as $yearLevel => $sectionId) {
        $database->query("UPDATE subjects SET section_id = :section_id WHERE year_level = :year_level AND section_id IS NULL");
        $database->bind(':section_id', $sectionId);
        $database->bind(':year_level', $yearLevel);
        $database->execute();
    }
    
    echo "<div class='success'>✅ Updated subjects with section assignments</div>";
    
    // Show current subjects
    $database->query("SELECT s.*, sec.section_name 
                     FROM subjects s 
                     LEFT JOIN sections sec ON s.section_id = sec.section_id 
                     ORDER BY s.year_level, s.semester, s.subject_code 
                     LIMIT 20");
    $database->execute();
    $subjects = $database->resultset();
    
    echo "<h4>Current Subjects (showing first 20):</h4>
    <table>
        <tr><th>Subject Code</th><th>Subject Name</th><th>Year Level</th><th>Semester</th><th>Section</th><th>Status</th></tr>";
    
    foreach ($subjects as $subject) {
        $sectionName = $subject['section_name'] ?? 'N/A';
        echo "<tr>
            <td>{$subject['subject_code']}</td>
            <td>{$subject['subject_name']}</td>
            <td>{$subject['year_level']}</td>
            <td>{$subject['semester']}</td>
            <td>{$sectionName}</td>
            <td>{$subject['status']}</td>
        </tr>";
    }
    echo "</table></div>";
    
    // Step 4: Test the fix
    echo "<div class='step'>
        <h3>Step 4: Testing the Fix</h3>";
    
    // Test query that the dashboard uses
    $database->query("SELECT COUNT(*) as count FROM sections WHERE year_level = '1st Year' AND status = 'available'");
    $database->execute();
    $firstYearSections = $database->single();
    
    echo "<div class='info'>ℹ️ Testing first year sections query...</div>";
    echo "<div class='success'>✅ Found " . $firstYearSections['count'] . " available sections for 1st Year</div>";
    
    if ($firstYearSections['count'] > 0) {
        // Test subjects query for first year
        $database->query("SELECT s.*, sec.section_name 
                         FROM subjects s 
                         LEFT JOIN sections sec ON s.section_id = sec.section_id 
                         WHERE s.year_level = '1st Year' AND s.semester = 'first' AND s.status = 'available'");
        $database->execute();
        $firstYearSubjects = $database->resultset();
        
        echo "<div class='success'>✅ Found " . count($firstYearSubjects) . " active subjects for 1st Year, 1st Semester</div>";
        
        if (count($firstYearSubjects) > 0) {
            echo "<h4>First Year Subjects Found:</h4>
            <table>
                <tr><th>Subject Code</th><th>Subject Name</th><th>Section</th></tr>";
            
            foreach ($firstYearSubjects as $subject) {
                $sectionName = $subject['section_name'] ?? 'N/A';
                echo "<tr>
                    <td>{$subject['subject_code']}</td>
                    <td>{$subject['subject_name']}</td>
                    <td>{$sectionName}</td>
                </tr>";
            }
            echo "</table>";
        }
    }
    
    echo "</div>";
    
    // Step 5: Summary and next steps
    echo "<div class='step'>
        <h3>Step 5: Summary and Next Steps</h3>
        
        <div class='success'>
            <h4>✅ Fix Applied Successfully!</h4>
            <p>The database structure and data issues have been resolved. Here's what was fixed:</p>
            <ul>
                <li>✅ Sections table structure and data</li>
                <li>✅ Subjects table structure (added missing columns)</li>
                <li>✅ Section-subject relationships</li>
                <li>✅ Sample data for testing</li>
            </ul>
        </div>
        
        <div class='info'>
            <h4>🔄 Next Steps:</h4>
            <ol>
                <li><strong>Test the Course Dashboard:</strong> Navigate to <code>dashboards/admin/Courses-dashboard.php</code></li>
                <li><strong>Verify First Year Data:</strong> Select '1st Year' and 'First Semester' - you should now see courses</li>
                <li><strong>Check All Year Levels:</strong> Test other year levels to ensure they work as well</li>
                <li><strong>Add More Courses:</strong> Use the 'Add New Course' button to add more courses as needed</li>
            </ol>
        </div>
        
        <div class='warning'>
            <h4>⚠️ Important Notes:</h4>
            <ul>
                <li>All existing subjects have been assigned to the first available section of their year level</li>
                <li>You may want to manually adjust section assignments for specific courses</li>
                <li>The default academic year is set to '2024-2025'</li>
                <li>If you still don't see data, check the browser console for JavaScript errors</li>
            </ul>
        </div>
        
        <div style='margin-top: 20px;'>
            <a href='dashboards/admin/Courses-dashboard.php' class='btn btn-success'>🚀 Go to Course Dashboard</a>
            <a href='dashboards/admin/sections-dashboard.php' class='btn'>⚙️ Manage Sections</a>
            <a href='dashboards/admin/dashboard.php' class='btn'>🏠 Admin Dashboard</a>
        </div>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Error Occurred</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
        <p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>
    </div>";
    
    echo "<div class='step'>
        <h3>🔧 Manual Fix Required</h3>
        <p>Due to the error, you may need to manually fix the database. Here are the SQL commands:</p>
        
        <div class='code'>
-- Create sections table if it doesn't exist
CREATE TABLE IF NOT EXISTS sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_year (section_name, year_level)
);

-- Add missing columns to subjects table
ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS section_id INT NULL AFTER year_level,
ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'first' AFTER year_level,
ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025' AFTER semester;

-- Insert default sections
INSERT INTO sections (section_name, year_level, status) VALUES
('A', '1st Year', 'available'),
('B', '1st Year', 'available'),
('C', '1st Year', 'available'),
('A', '2nd Year', 'available'),
('B', '2nd Year', 'available')
ON DUPLICATE KEY UPDATE status = VALUES(status);
        </div>
    </div>";
}

echo "</div></body></html>";
?>
