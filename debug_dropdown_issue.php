<?php
// Debug script for dropdown filtering issue
echo "<h1>Dropdown Filtering Debug</h1>";
echo "<p>This script helps debug why the dropdown filtering is not working.</p>";

// Test 1: Check if the changes were applied
echo "<h2>File Check:</h2>";
$filePath = 'dashboards/admin/Manage Course Assignment.php';
if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    
    // Check if disabled placeholders were removed
    if (strpos($content, '<option value="" disabled>Select Year Level</option>') !== false) {
        echo "<p>❌ Disabled placeholder still exists in Year Level dropdown</p>";
    } else {
        echo "<p>✅ Disabled placeholder removed from Year Level dropdown</p>";
    }
    
    if (strpos($content, '<option value="" disabled>Select Semester</option>') !== false) {
        echo "<p>❌ Disabled placeholder still exists in Semester dropdown</p>";
    } else {
        echo "<p>✅ Disabled placeholder removed from Semester dropdown</p>";
    }
    
    // Check if manual filter button exists
    if (strpos($content, 'manualFilter()') !== false) {
        echo "<p>✅ Manual filter button exists</p>";
    } else {
        echo "<p>❌ Manual filter button not found</p>";
    }
    
    // Check if event listeners exist
    if (strpos($content, 'addEventListener') !== false) {
        echo "<p>✅ Event listeners exist</p>";
    } else {
        echo "<p>❌ Event listeners not found</p>";
    }
    
} else {
    echo "<p>❌ File not found</p>";
}

// Test 2: Check database data
echo "<h2>Database Data Check:</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Check total subjects
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $totalSubjects = $db->single()['count'];
    echo "<p>✅ Total subjects in database: $totalSubjects</p>";
    
    // Check data by year level
    $db->query("SELECT year_level, COUNT(*) as count FROM subjects GROUP BY year_level ORDER BY year_level");
    $db->execute();
    $yearLevelData = $db->resultset();
    
    echo "<h3>Data by Year Level:</h3>";
    foreach ($yearLevelData as $row) {
        echo "<p>• {$row['year_level']}: {$row['count']} subjects</p>";
    }
    
    // Check if there's data for other year levels
    $db->query("SELECT COUNT(*) as count FROM subjects WHERE year_level != '1st Year'");
    $db->execute();
    $otherYearLevels = $db->single()['count'];
    echo "<p>✅ Subjects for other year levels (not 1st Year): $otherYearLevels</p>";
    
    if ($otherYearLevels == 0) {
        echo "<p>⚠️ <strong>No data found for other year levels!</strong> This is why you can only see 1st Year data.</p>";
        echo "<p>You need to run the database migration to add sample data for all year levels.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Test API endpoint
echo "<h2>API Test:</h2>";
$testData = [
    'action' => 'filter_courses',
    'academic_year' => '2024-2025',
    'year_level' => '2nd Year',
    'semester' => 'first'
];

$postData = http_build_query($testData);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$url = 'http://localhost/nls2/dashboards/admin/fetch-courses.php';
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        $activeCount = count($data['activeSubjects']);
        $inactiveCount = count($data['inactiveSubjects']);
        echo "<p>✅ API working for 2nd Year - Found $activeCount active and $inactiveCount inactive courses</p>";
        
        if ($activeCount == 0 && $inactiveCount == 0) {
            echo "<p>⚠️ <strong>No data returned for 2nd Year!</strong> This confirms there's no data for other year levels.</p>";
        }
    } else {
        echo "<p>❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p>❌ API not accessible</p>";
}

echo "<h2>Solutions:</h2>";
echo "<ol>";
echo "<li><strong>If no data for other year levels:</strong> Run <a href='fix_database_migration.php'>database migration</a> to add sample data</li>";
echo "<li><strong>If dropdowns still have disabled placeholders:</strong> The file changes weren't applied properly</li>";
echo "<li><strong>If API is not working:</strong> Check if fetch-courses.php is accessible</li>";
echo "<li><strong>If JavaScript errors:</strong> Check browser console (F12) for errors</li>";
echo "</ol>";

echo "<h2>Manual Testing Steps:</h2>";
echo "<ol>";
echo "<li>Open <a href='dashboards/admin/Manage Course Assignment.php' target='_blank'>Manage Course Assignment Dashboard</a></li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to Console tab</li>";
echo "<li>Look for any error messages</li>";
echo "<li>Try clicking the '🔄 Refresh Data' button</li>";
echo "<li>Check if the dropdowns show actual values (not 'Select Year Level')</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
echo "<p><a href='fix_database_migration.php'>Run Database Migration</a></p>";
?>
