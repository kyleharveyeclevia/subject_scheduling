<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    
    // Get parameters from request
    $year_level = $_GET['year_level'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $section_id = $_GET['section_id'] ?? '';
    $academic_year = $_GET['academic_year'] ?? '';
    
    // Validate required parameters
    if (empty($year_level)) {
        echo json_encode([
            'success' => false,
            'message' => 'Year level is required'
        ]);
        exit();
    }
    
    // Build query to get subjects from the subject scheduling database
    $query = "SELECT s.subject_id as id, 
                     s.subject_code as code, 
                     s.subject_name as title, 
                     s.units, 
                     s.year_level, 
                     s.semester, 
                     s.academic_year,
                     s.status,
                     sec.section_name,
                     sec.section_id
              FROM subjects s
              LEFT JOIN sections sec ON s.section_id = sec.section_id
              WHERE s.status = 'available' 
              AND s.year_level = :year_level";
    
    // Add semester filter if provided
    if (!empty($semester)) {
        // Handle different semester formats
        $semesterMapping = [
            'first' => ['first', '1st Semester'],
            'second' => ['second', '2nd Semester'],
            'summer' => ['summer', 'Mid Year']
        ];
        
        if (isset($semesterMapping[$semester])) {
            $placeholders = [];
            foreach ($semesterMapping[$semester] as $index => $value) {
                $placeholders[] = ":semester_$index";
            }
            $query .= " AND s.semester IN (" . implode(',', $placeholders) . ")";
        } else {
            $query .= " AND s.semester = :semester";
        }
    }
    
    // Add academic year filter if provided
    if (!empty($academic_year)) {
        $query .= " AND s.academic_year = :academic_year";
    }
    
    // Add section filter if provided - this is REQUIRED for proper section separation
    if (!empty($section_id)) {
        $query .= " AND s.section_id = :section_id";
    } else {
        // If no section_id provided, return empty result to prevent cross-section contamination
        $query .= " AND 1=0"; // This will return no results
    }
    
    $query .= " ORDER BY s.subject_code ASC";
    
    $database->query($query);
    $database->bind(':year_level', $year_level);
    
    if (!empty($semester)) {
        // Handle different semester formats
        $semesterMapping = [
            'first' => ['first', '1st Semester'],
            'second' => ['2nd Semester', 'second'],
            'summer' => ['summer', 'Mid Year']
        ];
        
        if (isset($semesterMapping[$semester])) {
            foreach ($semesterMapping[$semester] as $index => $value) {
                $database->bind(":semester_$index", $value);
            }
        } else {
            $database->bind(':semester', $semester);
        }
    }
    
    if (!empty($academic_year)) {
        $database->bind(':academic_year', $academic_year);
    }
    
    if (!empty($section_id)) {
        $database->bind(':section_id', $section_id);
    }
    
    $database->execute();
    $subjects = $database->resultset();
    
    // Format the response
    $formattedCourses = [];
    foreach ($subjects as $subject) {
        $formattedCourses[] = [
            'id' => $subject['id'],
            'code' => $subject['code'],
            'title' => $subject['title'],
            'units' => $subject['units'],
            'year_level' => $subject['year_level'],
            'semester' => $subject['semester'] ?? '',
            'academic_year' => $subject['academic_year'] ?? '',
            'status' => 'active',
            'section_name' => $subject['section_name'] ?? '',
            'section_id' => $subject['section_id'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'courses' => $formattedCourses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch courses: ' . $e->getMessage()
    ]);
}
?>
