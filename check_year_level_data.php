<?php
// Script to check year level data availability
echo "<h1>Year Level Data Check</h1>";
echo "<p>This script checks what data is available for different year levels.</p>";

try {
    require_once 'config/database.php';
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Check data for each year level
    $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $semesters = ['first', 'second', 'summer'];
    $academicYears = ['2024-2025', '2025-2026'];
    
    echo "<h2>Data Availability by Year Level:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Active Subjects</th><th>Inactive Subjects</th><th>Total</th></tr>";
    
    foreach ($yearLevels as $yearLevel) {
        foreach ($semesters as $semester) {
            foreach ($academicYears as $academicYear) {
                // Skip summer semester for non-3rd year
                if ($semester === 'summer' && $yearLevel !== '3rd Year') {
                    continue;
                }
                
                // Count active subjects
                $db->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = :year_level AND semester = :semester AND academic_year = :academic_year AND status = 'available'");
                $db->bind(':year_level', $yearLevel);
                $db->bind(':semester', $semester);
                $db->bind(':academic_year', $academicYear);
                $db->execute();
                $activeCount = $db->single()['count'];
                
                // Count inactive subjects
                $db->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = :year_level AND semester = :semester AND academic_year = :academic_year AND status = 'unavailable'");
                $db->bind(':year_level', $yearLevel);
                $db->bind(':semester', $semester);
                $db->bind(':academic_year', $academicYear);
                $db->execute();
                $inactiveCount = $db->single()['count'];
                
                $total = $activeCount + $inactiveCount;
                
                if ($total > 0) {
                    $bgColor = $total > 0 ? '#d1fae5' : '#fee2e2';
                    echo "<tr style='background-color: $bgColor;'>";
                    echo "<td>$yearLevel</td>";
                    echo "<td>" . ucfirst($semester) . "</td>";
                    echo "<td>$academicYear</td>";
                    echo "<td>$activeCount</td>";
                    echo "<td>$inactiveCount</td>";
                    echo "<td><strong>$total</strong></td>";
                    echo "</tr>";
                }
            }
        }
    }
    echo "</table>";
    
    // Show sample data
    echo "<h2>Sample Data (First 10 records):</h2>";
    $db->query("SELECT subject_code, subject_name, year_level, semester, academic_year, status FROM subjects ORDER BY year_level, semester, subject_code LIMIT 10");
    $db->execute();
    $sampleData = $db->resultset();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
    foreach ($sampleData as $row) {
        $bgColor = $row['status'] === 'available' ? '#d1fae5' : '#fee2e2';
        echo "<tr style='background-color: $bgColor;'>";
        echo "<td>" . htmlspecialchars($row['subject_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['year_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['academic_year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>How to View Different Year Levels:</h2>";
echo "<ol>";
echo "<li>Go to the <a href='dashboards/admin/Manage Course Assignment.php' target='_blank'>Manage Course Assignment Dashboard</a></li>";
echo "<li>Change the <strong>Year Level</strong> dropdown to see different year levels</li>";
echo "<li>Change the <strong>Semester</strong> dropdown to see different semesters</li>";
echo "<li>Change the <strong>Academic Year</strong> dropdown to see different academic years</li>";
echo "<li>If the dropdowns don't work automatically, click the <strong>🔄 Refresh Data</strong> button</li>";
echo "<li>Check the browser console (F12) for any error messages</li>";
echo "</ol>";

echo "<h2>Troubleshooting:</h2>";
echo "<ul>";
echo "<li><strong>No data shows:</strong> The database might not have data for that combination</li>";
echo "<li><strong>Dropdowns don't change data:</strong> Click the Refresh Data button</li>";
echo "<li><strong>JavaScript errors:</strong> Check browser console (F12)</li>";
echo "<li><strong>Database issues:</strong> Run <a href='fix_database_migration.php'>database migration</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
echo "<p><a href='fix_database_migration.php'>Run Database Migration</a></p>";
?>
