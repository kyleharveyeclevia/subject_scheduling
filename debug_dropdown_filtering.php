<?php
// Debug script for dropdown filtering issues
echo "<h1>Dropdown Filtering Debug</h1>";
echo "<p>This script helps debug the dropdown filtering issues.</p>";

// Test 1: Check current values
echo "<h2>Current Values:</h2>";
echo "<p>Current Academic Year: " . ($_GET['academic_year'] ?? 'Not set') . "</p>";
echo "<p>Current Year Level: " . ($_GET['year_level'] ?? 'Not set') . "</p>";
echo "<p>Current Semester: " . ($_GET['semester'] ?? 'Not set') . "</p>";

// Test 2: Check database connection
echo "<h2>Database Test:</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if subjects table has data
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $result = $db->single();
    echo "<p>✅ Subjects table has " . $result['count'] . " records</p>";
    
    // Check if academic_years table has data
    $db->query("SELECT COUNT(*) as count FROM academic_years");
    $db->execute();
    $result = $db->single();
    echo "<p>✅ Academic years table has " . $result['count'] . " records</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 3: Test the fetch-courses.php endpoint
echo "<h2>API Endpoint Test:</h2>";
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
    if ($data && isset($data['success'])) {
        if ($data['success']) {
            echo "<p>✅ API endpoint working - Found " . count($data['activeSubjects']) . " active and " . count($data['inactiveSubjects']) . " inactive courses</p>";
        } else {
            echo "<p>❌ API endpoint returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p>❌ API endpoint returned invalid JSON: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
    }
} else {
    echo "<p>❌ API endpoint not accessible</p>";
}

// Test 4: Check file permissions
echo "<h2>File Permissions Test:</h2>";
$files = [
    'dashboards/admin/fetch-courses.php',
    'dashboards/admin/Manage Course Assignment.php',
    'config/database.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
        if (is_readable($file)) {
            echo "<p>✅ $file is readable</p>";
        } else {
            echo "<p>❌ $file is not readable</p>";
        }
    } else {
        echo "<p>❌ $file does not exist</p>";
    }
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
echo "<li>If you don't see these messages, there's a JavaScript error</li>";
echo "<li>Try changing a dropdown and look for:</li>";
echo "<ul>";
echo "<li>'Academic year changed to: [value]'</li>";
echo "<li>'=== FILTER DATA FUNCTION CALLED ==='</li>";
echo "<li>'Making fetch request to fetch-courses.php...'</li>";
echo "</ul>";
echo "</ol>";

echo "<h2>Common Issues and Solutions:</h2>";
echo "<ul>";
echo "<li><strong>No console messages:</strong> JavaScript is not loading or there's a syntax error</li>";
echo "<li><strong>Dropdown elements not found:</strong> Check if the HTML IDs match the JavaScript selectors</li>";
echo "<li><strong>API calls failing:</strong> Check if fetch-courses.php is accessible and has proper permissions</li>";
echo "<li><strong>No data returned:</strong> Check if the database has data for the selected criteria</li>";
echo "<li><strong>Event listeners not working:</strong> Check if DOMContentLoaded is firing</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
echo "<p><a href='fix_database_migration.php'>Run Database Migration</a></p>";
?>
