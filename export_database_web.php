<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Export - Subject Scheduling</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-line;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Database Export Tool</h1>
        <p style="text-align: center; color: #666;">
            Export your current Subject Scheduling database to a .sql file
        </p>
        
        <button class="btn" onclick="exportDatabase()">🚀 Export Database</button>
        
        <div id="result"></div>
    </div>

    <script>
        function exportDatabase() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="info">⏳ Exporting database... Please wait...</div>';
            
            // Create a form to submit the export request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'export';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>

<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'subject_scheduling';

if (isset($_POST['export'])) {
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='success'>✅ Connected to database successfully!</div>";
        
        // Start building SQL file content
        $sql_content = "-- Database Export for Subject Scheduling System\n";
        $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql_content .= "CREATE DATABASE IF NOT EXISTS `$database`;\n";
        $sql_content .= "USE `$database`;\n\n";
        
        // Get all tables
        $tables_query = "SHOW TABLES";
        $tables_stmt = $pdo->query($tables_query);
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<div class='info'>📋 Found " . count($tables) . " tables to export</div>";
        
        $total_rows = 0;
        
        // Export each table
        foreach ($tables as $table) {
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
                
                $total_rows += count($data_rows);
            }
        }
        
        // Write to file
        $filename = "subject_scheduling_backup_" . date('Y-m-d_H-i-s') . ".sql";
        file_put_contents($filename, $sql_content);
        
        // Also create a copy with a simple name for easy transfer
        copy($filename, "current_database.sql");
        
        $file_size = formatBytes(filesize($filename));
        
        echo "<div class='success'>";
        echo "🎉 Database exported successfully!<br>";
        echo "📁 Main file: <strong>$filename</strong><br>";
        echo "📋 Transfer file: <strong>current_database.sql</strong><br>";
        echo "📊 Total tables: " . count($tables) . "<br>";
        echo "📊 Total rows: $total_rows<br>";
        echo "📊 File size: $file_size<br><br>";
        echo "✅ You can now transfer 'current_database.sql' to your other computer!";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "❌ Error: " . $e->getMessage() . "<br><br>";
        echo "Please check:<br>";
        echo "• WAMP server is running<br>";
        echo "• MySQL service is active<br>";
        echo "• Database '$database' exists<br>";
        echo "• Username '$username' has access";
        echo "</div>";
    }
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>
</body>
</html>
