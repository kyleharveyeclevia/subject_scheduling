<?php
// Debug script to check form loading
echo "<h1>Form Debug Test</h1>";

echo "<h2>Testing Student Form:</h2>";
$studentFormPath = __DIR__ . '/forms/student-form.php';
if (file_exists($studentFormPath)) {
    echo "<p>✅ Student form file exists</p>";
    echo "<p>File size: " . filesize($studentFormPath) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($studentFormPath)) . "</p>";
    
    // Read first 500 characters
    $content = file_get_contents($studentFormPath);
    echo "<h3>First 500 characters:</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
} else {
    echo "<p>❌ Student form file does not exist</p>";
}

echo "<h2>Testing Teacher Form:</h2>";
$teacherFormPath = __DIR__ . '/forms/teacher-form.php';
if (file_exists($teacherFormPath)) {
    echo "<p>✅ Teacher form file exists</p>";
    echo "<p>File size: " . filesize($teacherFormPath) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($teacherFormPath)) . "</p>";
    
    // Read first 500 characters
    $content = file_get_contents($teacherFormPath);
    echo "<h3>First 500 characters:</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
} else {
    echo "<p>❌ Teacher form file does not exist</p>";
}

echo "<h2>Direct Form Test:</h2>";
echo "<p>Try accessing these URLs directly:</p>";
echo "<ul>";
echo "<li><a href='forms/student-form.php' target='_blank'>Student Form Direct</a></li>";
echo "<li><a href='forms/teacher-form.php' target='_blank'>Teacher Form Direct</a></li>";
echo "</ul>";
?>
