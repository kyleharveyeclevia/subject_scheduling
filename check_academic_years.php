<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "=== Academic Years Table Structure ===\n";
    $db->query("DESCRIBE academic_years");
    $db->execute();
    $structure = $db->resultset();
    foreach($structure as $row) {
        echo json_encode($row) . "\n";
    }
    
    echo "\n=== Academic Years Data ===\n";
    $db->query("SELECT * FROM academic_years ORDER BY academic_year");
    $db->execute();
    $academic_years = $db->resultset();
    
    if (empty($academic_years)) {
        echo "No academic years found in database.\n";
    } else {
        foreach($academic_years as $year) {
            echo json_encode($year) . "\n";
        }
    }
    
    echo "\n=== Checking for 'future' status ===\n";
    $db->query("SELECT * FROM academic_years WHERE status = 'future'");
    $db->execute();
    $future_years = $db->resultset();
    
    if (empty($future_years)) {
        echo "No academic years with 'future' status found.\n";
    } else {
        echo "Found " . count($future_years) . " future academic years:\n";
        foreach($future_years as $year) {
            echo json_encode($year) . "\n";
        }
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
