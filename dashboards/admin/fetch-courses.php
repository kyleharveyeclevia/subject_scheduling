<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Ensure required classes are loaded
require_once __DIR__ . '/../../config/database.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get the action
$action = $_POST['action'] ?? '';

if ($action === 'fetch_courses') {
    // Get parameters
    $academic_year = $_POST['academic_year'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $semester = $_POST['semester'] ?? '';
    
    // Debug logging
    error_log("fetch-courses.php called with: academic_year=$academic_year, year_level=$year_level, semester=$semester");
    
    // Validate required parameters
    if (empty($academic_year) || empty($year_level) || empty($semester)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Academic year, year level, and semester are required',
            'debug' => [
                'academic_year' => $academic_year,
                'year_level' => $year_level,
                'semester' => $semester
            ]
        ]);
        exit();
    }
    
    try {
        $db = new Database();
        
        // Fetch courses based on the criteria
        $db->query("SELECT 
                        subject_id,
                        subject_code,
                        subject_name,
                        units,
                        year_level,
                        semester,
                        academic_year,
                        status
                    FROM subjects 
                    WHERE academic_year = :academic_year 
                    AND year_level = :year_level 
                    AND semester = :semester
                    AND status = 'available'
                    ORDER BY subject_code ASC");
        
        $db->bind(':academic_year', $academic_year);
        $db->bind(':year_level', $year_level);
        $db->bind(':semester', $semester);
        $db->execute();
        
        $courses = $db->resultset();
        
        // Debug logging
        error_log("fetch-courses.php found " . count($courses) . " courses");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'courses' => $courses,
            'count' => count($courses),
            'debug' => [
                'academic_year' => $academic_year,
                'year_level' => $year_level,
                'semester' => $semester,
                'query_params' => [
                    'academic_year' => $academic_year,
                    'year_level' => $year_level,
                    'semester' => $semester
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching courses: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while fetching courses'
        ]);
    }
} elseif ($action === 'filter_courses') {
    // Handle filter_courses action for real-time data filtering
    $academic_year = $_POST['academic_year'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $semester = $_POST['semester'] ?? '';
    
    // Debug logging
    error_log("filter_courses called with: academic_year=$academic_year, year_level=$year_level, semester=$semester");
    
    try {
        $db = new Database();
        
        // Build the WHERE clause based on provided parameters
        $where_conditions = [];
        $params = [];
        
        if (!empty($academic_year)) {
            $where_conditions[] = "academic_year = :academic_year";
            $params[':academic_year'] = $academic_year;
        }
        
        if (!empty($year_level)) {
            $where_conditions[] = "year_level = :year_level";
            $params[':year_level'] = $year_level;
        }
        
        if (!empty($semester)) {
            $where_conditions[] = "semester = :semester";
            $params[':semester'] = $semester;
        }
        
        // If no filters provided, get all subjects
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Fetch active subjects with assignment data
        $active_query = "SELECT 
                            s.subject_id as id,
                            s.subject_code,
                            s.subject_name,
                            s.units,
                            s.year_level,
                            s.semester,
                            s.academic_year,
                            'active' as status,
                            u.full_name as instructor,
                            ca.section_name as section
                        FROM subjects s
                        LEFT JOIN course_assignments ca ON s.subject_id = ca.subject_id 
                            AND ca.academic_year = :academic_year 
                            AND ca.year_level = :year_level 
                            AND ca.semester = :semester
                        LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
                        LEFT JOIN users u ON t.user_id = u.user_id
                        " . ($where_clause ? $where_clause . " AND s.status = 'available'" : "WHERE s.status = 'available'") . "
                        ORDER BY s.subject_code ASC";
        
        $db->query($active_query);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->execute();
        $activeSubjects = $db->resultset();
        
        // Fetch inactive subjects with assignment data
        $inactive_query = "SELECT 
                            s.subject_id as id,
                            s.subject_code,
                            s.subject_name,
                            s.units,
                            s.year_level,
                            s.semester,
                            s.academic_year,
                            'inactive' as status,
                            u.full_name as instructor,
                            ca.section_name as section
                        FROM subjects s
                        LEFT JOIN course_assignments ca ON s.subject_id = ca.subject_id 
                            AND ca.academic_year = :academic_year 
                            AND ca.year_level = :year_level 
                            AND ca.semester = :semester
                        LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
                        LEFT JOIN users u ON t.user_id = u.user_id
                        " . ($where_clause ? $where_clause . " AND s.status = 'unavailable'" : "WHERE s.status = 'unavailable'") . "
                        ORDER BY s.subject_code ASC";
        
        $db->query($inactive_query);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->execute();
        $inactiveSubjects = $db->resultset();
        
        // Debug logging
        error_log("filter_courses found " . count($activeSubjects) . " active and " . count($inactiveSubjects) . " inactive subjects");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'activeSubjects' => $activeSubjects,
            'inactiveSubjects' => $inactiveSubjects,
            'counts' => [
                'active' => count($activeSubjects),
                'inactive' => count($inactiveSubjects)
            ],
            'debug' => [
                'academic_year' => $academic_year,
                'year_level' => $year_level,
                'semester' => $semester,
                'where_clause' => $where_clause,
                'params' => $params
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error filtering courses: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while filtering courses: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
