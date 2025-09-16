 <?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../../config/database.php';

// Initialize database connection
$database = new Database();

// Get parameters
$year_level = isset($_GET['year_level']) ? $_GET['year_level'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$section_id = isset($_GET['section_id']) ? $_GET['section_id'] : '';

// Debug logging
error_log("get_courses.php called with: year_level=$year_level, semester=$semester, section_id=$section_id");

// Additional debugging
error_log("get_courses.php: Starting database query execution");

// Validate parameters
if (empty($year_level)) {
    error_log("get_courses.php: Missing year_level parameter");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Year level is required']);
    exit();
}

try {
    error_log("get_courses.php: Executing database query");
    
    // Use exactly the same logic as the courses dashboard
    // Check if subjects table has the required columns
    $database->query("DESCRIBE subjects");
    $database->execute();
    $columns = $database->resultset();
    
    $hasSemester = false;
    $hasSection = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'semester') $hasSemester = true;
        if ($column['Field'] === 'section') $hasSection = true;
    }
    
    // Get section name from section_id if provided
    $section_name = '';
    if (!empty($section_id)) {
        $database->query("SELECT section_name FROM sections WHERE section_id = :section_id");
        $database->bind(':section_id', $section_id);
        $database->execute();
        $section = $database->single();
        if ($section) {
            $section_name = $section['section_name'];
        }
    }
    
    // Build query to get section-specific active courses only
    // This prevents duplicate courses from appearing in the dropdown
    // Only shows courses that are:
    // 1. Marked as 'available' (active) in the courses dashboard
    // 2. Assigned to the specific year level
    // 3. Assigned to the specific section (if section_id provided)
    $query = "SELECT s.subject_id as id, 
                     s.subject_code as code, 
                     s.subject_name as title, 
                     s.units, 
                     s.year_level, 
                     s.semester, 
                     s.academic_year,
                     CASE WHEN s.status = 'available' THEN 'active' ELSE 'inactive' END as status,
                     sec.section_name
              FROM subjects s
              LEFT JOIN sections sec ON s.section_id = sec.section_id
              WHERE s.status = 'available' 
              AND s.year_level = :year_level";
    
    // Add semester filter if column exists and semester is provided
    if ($hasSemester && !empty($semester)) {
        $query .= " AND s.semester = :semester";
    }
    
    // Academic year filtering removed - courses will be loaded without academic year restrictions
    
    // Academic year filter is now properly handled
    
    // Add section filter - only show courses assigned to the specific section
    // This prevents duplicate courses from appearing in the dropdown
    if (!empty($section_id)) {
        $query .= " AND s.section_id = :section_id";
    }
    // If section column exists but no section selected, show all courses for the year level
    // This allows the system to work even if courses aren't assigned to specific sections
    
    $query .= " ORDER BY s.subject_code ASC";
    
    $database->query($query);
    $database->bind(':year_level', $year_level);
    
    if ($hasSemester && !empty($semester)) {
        $database->bind(':semester', $semester);
    }
    
    // Academic year binding removed - no academic year filtering applied
    
    // Bind section_id parameter if provided
    if (!empty($section_id)) {
        $database->bind(':section_id', $section_id);
    }
    
    $database->execute();
    $courses = $database->resultset();
    
    error_log("get_courses.php: Query returned " . count($courses) . " courses for year_level=$year_level, semester=$semester, section_id=$section_id");
    error_log("get_courses.php: Final query: " . $query);
    error_log("get_courses.php: Parameters: year_level=$year_level, semester=$semester, section_id=$section_id");
    
    // Additional debug logging
    if (empty($courses)) {
        error_log("get_courses.php: No courses found. Checking if subjects exist for year_level=$year_level");
        
        // Check if any subjects exist for this year level
        $database->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = :year_level");
        $database->bind(':year_level', $year_level);
        $database->execute();
        $totalSubjects = $database->single();
        
        error_log("get_courses.php: Total subjects for year_level=$year_level: " . $totalSubjects['count']);
        
        if ($totalSubjects['count'] > 0) {
            // Check how many are available
            $database->query("SELECT COUNT(*) as count FROM subjects WHERE year_level = :year_level AND status = 'available'");
            $database->bind(':year_level', $year_level);
            $database->execute();
            $availableSubjects = $database->single();
            
            error_log("get_courses.php: Available subjects for year_level=$year_level: " . $availableSubjects['count']);
        }
    }
    
    // Format the data to match the expected structure (same as Courses Dashboard)
    // Only include courses that are marked as 'available' (active) in the courses dashboard
    $formattedCourses = [];
    foreach ($courses as $course) {
        // Only include courses that are marked as 'available' (active)
        // This ensures we only show courses that are active in the courses dashboard
        if ($course['status'] === 'active' || $course['status'] === 'available') {
            $formattedCourses[] = [
                'id' => $course['id'],
                'code' => $course['code'],
                'title' => $course['title'],
                'units' => $course['units'],
                'year_level' => $course['year_level'],
                'semester' => $course['semester'] ?? '',
                'academic_year' => $course['academic_year'] ?? '',
                'status' => 'active', // Always mark as active since we filtered for available courses
                'section_name' => $course['section_name'] ?? $section_name
            ];
        }
    }
    
    error_log("get_courses.php: Sending response with " . count($formattedCourses) . " active courses for section $section_id (filtered from " . count($courses) . " total courses)");
    
    // Log the active courses for debugging
    if (count($formattedCourses) > 0) {
        error_log("Active courses found for section $section_id:");
        foreach ($formattedCourses as $course) {
            error_log("- " . $course['code'] . " - " . $course['title'] . " (Status: " . $course['status'] . ", Section: " . ($course['section_name'] ?? 'N/A') . ")");
        }
    } else {
        error_log("No active courses found for year_level: $year_level, semester: $semester, section_id: $section_id");
    }
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode([
        'success' => true,
        'courses' => $formattedCourses,
        'count' => count($formattedCourses),
        'timestamp' => time(),
        'debug' => [
            'year_level' => $year_level,
            'semester' => $semester,
            'section_id' => $section_id,
            'section_name' => $section_name,
            'has_academic_year' => false,
            'has_semester' => $hasSemester,
            'has_section' => $hasSection,
            'query_used' => 'section_specific_active_courses',
            'academic_year_used' => 'none',
            'status_filter' => 'active_courses_only',
            'section_filter' => 'specific_section_only'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Database error in get_courses.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'courses' => [],
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
