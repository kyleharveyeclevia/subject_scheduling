<?php
// Database Export Script for Subject Scheduling System
// This script will export your current database to a .sql file

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'subject_scheduling';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    echo "Exporting database: $database\n\n";
    
    // Start building SQL file content
    $sql_content = "-- Database Export for Subject Scheduling System\n";
    $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    $sql_content .= "CREATE DATABASE IF NOT EXISTS `$database`;\n";
    $sql_content .= "USE `$database`;\n\n";
    
    // Get all tables
    $tables_query = "SHOW TABLES";
    $tables_stmt = $pdo->query($tables_query);
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Export each table
    foreach ($tables as $table) {
        echo "Exporting table: $table\n";
        
        // Get table structure
        $create_table_query = "SHOW CREATE TABLE `$table`";
        $create_table_stmt = $pdo->query($create_table_query);
        $create_table_result = $create_table_stmt->fetch(PDO::FETCH_ASSOC);
        
        $sql_content .= "-- Table structure for table `$table`\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $create_table_result['Create Table'] . ";\n\n";
        
        // Get table data
        $data_query = "SELECT * FROM `$table`";
        $data_stmt = $pdo->query($data_query);
        $data_rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data_rows)) {
            $sql_content .= "-- Data for table `$table`\n";
            
            // Get column names
            $columns = array_keys($data_rows[0]);
            $column_list = '`' . implode('`, `', $columns) . '`';
            
            // Insert statements
            $sql_content .= "INSERT INTO `$table` ($column_list) VALUES\n";
            
            $values = [];
            foreach ($data_rows as $row) {
                $escaped_values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escaped_values[] = 'NULL';
                    } else {
                        $escaped_values[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = "(" . implode(', ', $escaped_values) . ")";
            }
            
            $sql_content .= implode(",\n", $values) . ";\n\n";
            
            echo "  - Exported " . count($data_rows) . " rows\n";
        } else {
            echo "  - No data found\n";
        }
    }
    
    // Write to file
    $filename = "subject_scheduling_backup_" . date('Y-m-d_H-i-s') . ".sql";
    file_put_contents($filename, $sql_content);
    
    echo "\n✅ Database exported successfully!\n";
    echo "📁 File saved as: $filename\n";
    echo "📊 Total size: " . formatBytes(filesize($filename)) . "\n";
    
    // Also create a copy with a simple name for easy transfer
    copy($filename, "current_database.sql");
    echo "📋 Also saved as: current_database.sql (for easy transfer)\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Make sure:\n";
    echo "1. WAMP server is running\n";
    echo "2. MySQL service is active\n";
    echo "3. Database '$database' exists\n";
    echo "4. Username '$username' has access\n";
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>
