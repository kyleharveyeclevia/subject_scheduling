<?php
// Simple debug script for dropdown filtering
echo "<h1>Dropdown Filtering Debug</h1>";
echo "<p>This script helps debug the dropdown filtering issues.</p>";

// Test 1: Check current values
echo "<h2>Current Values:</h2>";
echo "<p>Current Academic Year: " . ($_GET['academic_year'] ?? 'Not set') . "</p>";
echo "<p>Current Year Level: " . ($_GET['year_level'] ?? 'Not set') . "</p>";
echo "<p>Current Semester: " . ($_GET['semester'] ?? 'Not set') . "</p>";

// Test 2: Check database
echo "<h2>Database Test:</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Check subjects count
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $result = $db->single();
    echo "<p>✅ Subjects table has " . $result['count'] . " records</p>";
    
    // Check sample data for 2024-2025, 1st Year, first
    $db->query("SELECT COUNT(*) as count FROM subjects WHERE academic_year = '2024-2025' AND year_level = '1st Year' AND semester = 'first'");
    $db->execute();
    $result = $db->single();
    echo "<p>✅ Found " . $result['count'] . " subjects for 2024-2025, 1st Year, first semester</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Test API endpoint
echo "<h2>API Test:</h2>";
$testData = [
    'action' => 'filter_courses',
    'academic_year' => '2024-2025',
    'year_level' => '1st Year',
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
        echo "<p>✅ API working - Found " . count($data['activeSubjects']) . " active and " . count($data['inactiveSubjects']) . " inactive courses</p>";
    } else {
        echo "<p>❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p>❌ API not accessible</p>";
}

echo "<h2>Manual Testing Instructions:</h2>";
echo "<ol>";
echo "<li>Navigate to the <a href='dashboards/admin/Manage Course Assignment.php' target='_blank'>Manage Course Assignment Dashboard</a></li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to the Console tab</li>";
echo "<li>Look for these messages:</li>";
echo "<ul>";
echo "<li>'Setting up dropdown event listeners...'</li>";
echo "<li>'Dropdown elements found: {academicYearSelect: true, yearLevelSelect: true, semesterSelect: true}'</li>";
echo "<li>'Initial filtering with: {currentAcademicYear: ..., currentYearLevel: ..., currentSemester: ...}'</li>";
echo "</ul>";
echo "<li>Try changing a dropdown and look for:</li>";
echo "<ul>";
echo "<li>'Academic year changed to: [value]' or 'Year level changed to: [value]' or 'Semester changed to: [value]'</li>";
echo "<li>'=== FILTER DATA FUNCTION CALLED ==='</li>";
echo "<li>'=== UPDATE TABLES FUNCTION CALLED ==='</li>";
echo "</ul>";
echo "</ol>";

echo "<h2>Expected Behavior:</h2>";
echo "<ul>";
echo "<li>✅ Dropdowns should have default values selected (not empty)</li>";
echo "<li>✅ Page should automatically filter data on load</li>";
echo "<li>✅ Changing dropdowns should trigger filtering</li>";
echo "<li>✅ Tables should update with filtered results</li>";
echo "<li>✅ Console should show detailed logging</li>";
echo "</ul>";

echo "<h2>Common Issues:</h2>";
echo "<ul>";
echo "<li><strong>Dropdowns show 'Select Year Level' or 'Select Semester':</strong> The disabled placeholder is still there</li>";
echo "<li><strong>No console messages:</strong> JavaScript is not loading or there's a syntax error</li>";
echo "<li><strong>API calls failing:</strong> Check if fetch-courses.php is accessible</li>";
echo "<li><strong>No data returned:</strong> Check if database has data for the selected criteria</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
echo "<p><a href='fix_database_migration.php'>Run Database Migration</a></p>";
?>
