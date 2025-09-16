<?php
// Test script to verify course data loading
echo "<h1>Course Data Test</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'subject_scheduling';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful</p>";
    
    // Test 1: Check if subjects table has required columns
    $stmt = $pdo->query("DESCRIBE subjects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    $required_columns = ['subject_id', 'subject_code', 'subject_name', 'units', 'year_level', 'semester', 'academic_year', 'status'];
    $missing_columns = array_diff($required_columns, $column_names);
    
    if (empty($missing_columns)) {
        echo "<p>✅ All required columns exist in subjects table</p>";
    } else {
        echo "<p>❌ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        echo "<p><a href='fix_database_migration.php'>Run database migration</a></p>";
    }
    
    // Test 2: Check if there's data in the subjects table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['count'] > 0) {
        echo "<p>✅ Subjects table has {$count['count']} records</p>";
    } else {
        echo "<p>❌ Subjects table is empty</p>";
        echo "<p><a href='fix_database_migration.php'>Run database migration to add sample data</a></p>";
    }
    
    // Test 3: Test specific queries that the dashboard uses
    $test_criteria = [
        ['year_level' => '1st Year', 'semester' => 'first', 'academic_year' => '2024-2025'],
        ['year_level' => '2nd Year', 'semester' => 'first', 'academic_year' => '2024-2025'],
        ['year_level' => '3rd Year', 'semester' => 'summer', 'academic_year' => '2024-2025'],
    ];
    
    echo "<h3>Testing Dashboard Queries:</h3>";
    
    foreach ($test_criteria as $criteria) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subjects WHERE year_level = ? AND semester = ? AND academic_year = ? AND status = 'available'");
        $stmt->execute([$criteria['year_level'], $criteria['semester'], $criteria['academic_year']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $status = $result['count'] > 0 ? '✅' : '❌';
        echo "<p>$status {$criteria['year_level']} - {$criteria['semester']} - {$criteria['academic_year']}: {$result['count']} active courses</p>";
    }
    
    // Test 4: Show sample data
    $stmt = $pdo->query("SELECT subject_code, subject_name, year_level, semester, academic_year, status FROM subjects ORDER BY year_level, semester, subject_code LIMIT 15");
    $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sample_data)) {
        echo "<h3>Sample Course Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
        
        foreach ($sample_data as $row) {
            $status_color = $row['status'] === 'available' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['year_level']}</td>";
            echo "<td>{$row['semester']}</td>";
            echo "<td>{$row['academic_year']}</td>";
            echo "<td style='color: $status_color;'>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 5: Test the filter_courses action
    echo "<h3>Testing filter_courses API:</h3>";
    
    $test_data = [
        'action' => 'filter_courses',
        'academic_year' => '2024-2025',
        'year_level' => '1st Year',
        'semester' => 'first'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/nls2/dashboards/admin/fetch-courses.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p>✅ filter_courses API working - Found {$data['counts']['active']} active and {$data['counts']['inactive']} inactive courses</p>";
        } else {
            echo "<p>❌ filter_courses API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p>❌ filter_courses API failed with HTTP code: $http_code</p>";
    }
    
    echo "<h2>Test Summary:</h2>";
    echo "<p>If all tests pass, the course assignment dashboard should work properly with real-time data.</p>";
    echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
