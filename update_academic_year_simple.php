<?php
/**
 * Simple script to update all courses to academic year '2025-2026'
 * Run this by accessing: http://localhost/nls2/update_academic_year_simple.php
 */

// Database connection
$host = 'localhost';
$dbname = 'nls2';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Updating Courses Academic Year to 2025-2026</h2>";
    echo "<hr>";
    
    // Check current status
    echo "<h3>Current Status:</h3>";
    $stmt = $pdo->query("SELECT academic_year, COUNT(*) as count FROM subjects GROUP BY academic_year ORDER BY academic_year");
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Academic Year</th><th>Count</th></tr>";
    foreach ($current as $row) {
        echo "<tr><td>" . ($row['academic_year'] ?? 'NULL') . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // Update all courses
    echo "<h3>Updating...</h3>";
    $updateStmt = $pdo->prepare("UPDATE subjects SET academic_year = '2025-2026' WHERE academic_year IS NULL OR academic_year = '' OR academic_year = '2024-2025'");
    $result = $updateStmt->execute();
    $affected = $updateStmt->rowCount();
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully updated $affected courses to academic year '2025-2026'</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Error updating courses</p>";
    }
    
    echo "<hr>";
    
    // Check final status
    echo "<h3>Final Status:</h3>";
    $stmt = $pdo->query("SELECT academic_year, COUNT(*) as count FROM subjects GROUP BY academic_year ORDER BY academic_year");
    $final = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Academic Year</th><th>Count</th></tr>";
    foreach ($final as $row) {
        echo "<tr><td>" . ($row['academic_year'] ?? 'NULL') . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: blue; font-weight: bold;'>🎉 Update completed! You can now access the Manage Academic Year Course dashboard.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
h2, h3 { color: #333; }
table { border-collapse: collapse; margin: 10px 0; background-color: white; }
th { background-color: #4CAF50; color: white; padding: 10px; }
td { padding: 8px; border: 1px solid #ddd; }
tr:nth-child(even) { background-color: #f2f2f2; }
</style>
