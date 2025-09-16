<?php
require_once 'config/database.php';

echo "<h2>🔧 Running Future Status Migration</h2>";

try {
    $db = new Database();
    
    echo "<p><strong>1. Modifying status ENUM to include 'future' status...</strong></p>";
    $db->query("ALTER TABLE academic_years MODIFY COLUMN status ENUM('active', 'inactive', 'future') NOT NULL DEFAULT 'active'");
    $db->execute();
    echo "<p style='color: green;'>✅ Status ENUM updated successfully</p>";
    
    echo "<p><strong>2. Inserting sample future academic years...</strong></p>";
    $db->query("INSERT INTO academic_years (academic_year, status, is_locked) VALUES
        ('2027-2028', 'future', FALSE),
        ('2028-2029', 'future', FALSE),
        ('2029-2030', 'future', FALSE)");
    $db->execute();
    echo "<p style='color: green;'>✅ Sample future academic years inserted</p>";
    
    echo "<p><strong>3. Verifying the changes...</strong></p>";
    $db->query("SELECT * FROM academic_years WHERE status = 'future' ORDER BY academic_year");
    $db->execute();
    $future_years = $db->resultset();
    
    if (empty($future_years)) {
        echo "<p style='color: red;'>❌ No future academic years found after migration</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($future_years) . " future academic years:</p>";
        echo "<ul>";
        foreach($future_years as $year) {
            echo "<li>" . htmlspecialchars($year['academic_year']) . " (ID: " . $year['id'] . ")</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>4. All academic years in database:</strong></p>";
    $db->query("SELECT * FROM academic_years ORDER BY academic_year");
    $db->execute();
    $all_years = $db->resultset();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Academic Year</th><th>Status</th><th>Is Locked</th><th>Created At</th></tr>";
    foreach($all_years as $year) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($year['id']) . "</td>";
        echo "<td>" . htmlspecialchars($year['academic_year']) . "</td>";
        echo "<td style='color: " . ($year['status'] === 'future' ? 'orange' : ($year['status'] === 'active' ? 'green' : 'gray')) . ";'>" . htmlspecialchars($year['status']) . "</td>";
        echo "<td>" . ($year['is_locked'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($year['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Migration completed successfully! You should now see future academic years in your dashboard.</p>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
