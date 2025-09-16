<?php
// Simple script to clean up the Courses-dashboard.php file
// Remove course_type and prerequisites data attributes from edit buttons

$file_path = 'dashboards/admin/Courses-dashboard.php';
$content = file_get_contents($file_path);

// Remove course_type and prerequisites data attributes from edit buttons
$content = preg_replace('/data-course-type="[^"]*"/', '', $content);
$content = preg_replace('/data-prerequisites="[^"]*"/', '', $content);

// Write the cleaned content back to the file
file_put_contents($file_path, $content);

echo "Cleanup completed! Removed course_type and prerequisites data attributes.\n";
?>
