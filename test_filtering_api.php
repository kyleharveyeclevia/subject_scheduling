<?php
// Test script to verify the filtering API
echo "<h1>Filtering API Test</h1>";
echo "<p>This script tests the filtering API to ensure it's working correctly.</p>";

// Test 1: Check database connection and data
echo "<h2>Database Test:</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "<p>✅ Database connection successful</p>";
    
    // Check subjects table
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $db->execute();
    $result = $db->single();
    echo "<p>✅ Subjects table has " . $result['count'] . " records</p>";
    
    // Check sample data
    $db->query("SELECT subject_code, subject_name, year_level, semester, academic_year, status FROM subjects LIMIT 5");
    $db->execute();
    $sampleData = $db->resultset();
    
    echo "<h3>Sample Data:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Code</th><th>Name</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Status</th></tr>";
    foreach ($sampleData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['subject_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subject_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['year_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['academic_year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 2: Test the filter_courses API directly
echo "<h2>API Test:</h2>";

$testCases = [
    [
        'name' => 'Test 1: 2024-2025, 1st Year, first semester',
        'data' => [
            'action' => 'filter_courses',
            'academic_year' => '2024-2025',
            'year_level' => '1st Year',
            'semester' => 'first'
        ]
    ],
    [
        'name' => 'Test 2: 2024-2025, 2nd Year, second semester',
        'data' => [
            'action' => 'filter_courses',
            'academic_year' => '2024-2025',
            'year_level' => '2nd Year',
            'semester' => 'second'
        ]
    ],
    [
        'name' => 'Test 3: 2025-2026, 1st Year, first semester',
        'data' => [
            'action' => 'filter_courses',
            'academic_year' => '2025-2026',
            'year_level' => '1st Year',
            'semester' => 'first'
        ]
    ]
];

foreach ($testCases as $test) {
    echo "<h3>" . $test['name'] . "</h3>";
    
    $postData = http_build_query($test['data']);
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
                echo "<p>✅ API working - Found " . count($data['activeSubjects']) . " active and " . count($data['inactiveSubjects']) . " inactive courses</p>";
                
                if (!empty($data['activeSubjects'])) {
                    echo "<p><strong>Active Subjects:</strong></p>";
                    echo "<ul>";
                    foreach (array_slice($data['activeSubjects'], 0, 3) as $subject) {
                        echo "<li>" . htmlspecialchars($subject['subject_code']) . " - " . htmlspecialchars($subject['subject_name']) . "</li>";
                    }
                    if (count($data['activeSubjects']) > 3) {
                        echo "<li>... and " . (count($data['activeSubjects']) - 3) . " more</li>";
                    }
                    echo "</ul>";
                }
                
                if (!empty($data['inactiveSubjects'])) {
                    echo "<p><strong>Inactive Subjects:</strong></p>";
                    echo "<ul>";
                    foreach (array_slice($data['inactiveSubjects'], 0, 3) as $subject) {
                        echo "<li>" . htmlspecialchars($subject['subject_code']) . " - " . htmlspecialchars($subject['subject_name']) . "</li>";
                    }
                    if (count($data['inactiveSubjects']) > 3) {
                        echo "<li>... and " . (count($data['inactiveSubjects']) - 3) . " more</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<p>❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p>❌ API returned invalid JSON: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
        }
    } else {
        echo "<p>❌ API not accessible</p>";
    }
}

echo "<h2>Manual Testing Instructions:</h2>";
echo "<ol>";
echo "<li>Navigate to the <a href='dashboards/admin/Manage Course Assignment.php' target='_blank'>Manage Course Assignment Dashboard</a></li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to the Console tab</li>";
echo "<li>Click the '🔧 Test Filtering' button</li>";
echo "<li>Look for these console messages:</li>";
echo "<ul>";
echo "<li>'=== MANUAL FILTERING TEST ==='</li>";
echo "<li>'Current dropdown values: ...'</li>";
echo "<li>'=== FILTER DATA FUNCTION CALLED ==='</li>";
echo "<li>'Making fetch request to fetch-courses.php...'</li>";
echo "<li>'=== UPDATE TABLES FUNCTION CALLED ==='</li>";
echo "<li>'=== TABLE UPDATE COMPLETE ==='</li>";
echo "</ul>";
echo "<li>If you see these messages, the filtering should work</li>";
echo "<li>If you don't see these messages, there's a JavaScript error</li>";
echo "</ol>";

echo "<h2>Expected Results:</h2>";
echo "<ul>";
echo "<li>✅ API should return data for valid filter combinations</li>";
echo "<li>✅ Tables should update when filtering</li>";
echo "<li>✅ Console should show detailed logging</li>";
echo "<li>✅ No JavaScript errors in console</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='dashboards/admin/Manage Course Assignment.php'>Go to Course Assignment Dashboard</a></p>";
echo "<p><a href='fix_database_migration.php'>Run Database Migration</a></p>";
?>
