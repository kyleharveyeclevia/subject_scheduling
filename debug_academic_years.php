<?php
require_once 'config/database.php';

echo "<h2>Academic Years Debug Information</h2>";

try {
    $db = new Database();
    
    echo "<h3>1. Table Structure</h3>";
    $db->query("DESCRIBE academic_years");
    $db->execute();
    $structure = $db->resultset();
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach($structure as $row) {
        echo "<tr>";
        foreach($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. All Academic Years</h3>";
    $db->query("SELECT * FROM academic_years ORDER BY academic_year");
    $db->execute();
    $academic_years = $db->resultset();
    
    if (empty($academic_years)) {
        echo "<p>No academic years found in database.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Academic Year</th><th>Status</th><th>Is Locked</th><th>Created At</th><th>Updated At</th></tr>";
        foreach($academic_years as $year) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($year['id']) . "</td>";
            echo "<td>" . htmlspecialchars($year['academic_year']) . "</td>";
            echo "<td>" . htmlspecialchars($year['status']) . "</td>";
            echo "<td>" . htmlspecialchars($year['is_locked']) . "</td>";
            echo "<td>" . htmlspecialchars($year['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($year['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3. Future Academic Years (status = 'future')</h3>";
    $db->query("SELECT * FROM academic_years WHERE status = 'future'");
    $db->execute();
    $future_years = $db->resultset();
    
    if (empty($future_years)) {
        echo "<p style='color: red;'>No academic years with 'future' status found.</p>";
    } else {
        echo "<p style='color: green;'>Found " . count($future_years) . " future academic years:</p>";
        echo "<table border='1'><tr><th>ID</th><th>Academic Year</th><th>Status</th><th>Is Locked</th></tr>";
        foreach($future_years as $year) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($year['id']) . "</td>";
            echo "<td>" . htmlspecialchars($year['academic_year']) . "</td>";
            echo "<td>" . htmlspecialchars($year['status']) . "</td>";
            echo "<td>" . htmlspecialchars($year['is_locked']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4. Dashboard Query Test</h3>";
    // This is the query used in the dashboard
    $db->query("SELECT id, academic_year, status, COALESCE(is_locked, 0) as is_locked FROM academic_years ORDER BY academic_year ASC");
    $db->execute();
    $dashboard_years = $db->resultset();
    
    echo "<p>Dashboard query results:</p>";
    echo "<table border='1'><tr><th>ID</th><th>Academic Year</th><th>Status</th><th>Is Locked</th></tr>";
    foreach($dashboard_years as $year) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($year['id']) . "</td>";
        echo "<td>" . htmlspecialchars($year['academic_year']) . "</td>";
        echo "<td>" . htmlspecialchars($year['status']) . "</td>";
        echo "<td>" . htmlspecialchars($year['is_locked']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
