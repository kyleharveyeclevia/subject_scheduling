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

if ($action === 'assign_course') {
    // Get form data
    $courseId = $_POST['course_id'] ?? '';
    $instructorId = $_POST['instructor_id'] ?? '';
    $section = $_POST['section'] ?? '';
    $academicYear = $_POST['academic_year'] ?? '';
    $yearLevel = $_POST['year_level'] ?? '';
    $semester = $_POST['semester'] ?? '';
    
    // Debug logging
    error_log("assign-course.php called with: course_id=$courseId, instructor_id=$instructorId, section=$section, academic_year=$academicYear, year_level=$yearLevel, semester=$semester");
    
    // Validate required parameters
    if (empty($courseId) || empty($instructorId) || empty($section)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Course ID, Instructor ID, and Section are required'
        ]);
        exit();
    }
    
    try {
        $db = new Database();
        
        // First, get instructor details
        $db->query("SELECT u.full_name, t.teacher_id 
                    FROM users u 
                    INNER JOIN teachers t ON u.user_id = t.user_id 
                    WHERE u.user_id = :user_id AND u.role = 'teacher'");
        $db->bind(':user_id', $instructorId);
        $db->execute();
        $instructor = $db->single();
        
        if (!$instructor) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Instructor not found'
            ]);
            exit();
        }
        
        // Check if assignment already exists
        $db->query("SELECT id FROM course_assignments 
                    WHERE subject_id = :subject_id 
                    AND academic_year = :academic_year 
                    AND year_level = :year_level 
                    AND semester = :semester");
        $db->bind(':subject_id', $courseId);
        $db->bind(':academic_year', $academicYear);
        $db->bind(':year_level', $yearLevel);
        $db->bind(':semester', $semester);
        $db->execute();
        $existingAssignment = $db->single();
        
        if ($existingAssignment) {
            // Update existing assignment
            $db->query("UPDATE course_assignments SET 
                        teacher_id = :teacher_id,
                        section_name = :section_name,
                        assigned_by = :assigned_by,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :assignment_id");
            $db->bind(':teacher_id', $instructor['teacher_id']);
            $db->bind(':section_name', $section);
            $db->bind(':assigned_by', $_SESSION['user_id']);
            $db->bind(':assignment_id', $existingAssignment['id']);
        } else {
            // Create new assignment
            $db->query("INSERT INTO course_assignments 
                        (subject_id, teacher_id, section_name, academic_year, year_level, semester, assigned_by) 
                        VALUES (:subject_id, :teacher_id, :section_name, :academic_year, :year_level, :semester, :assigned_by)");
            $db->bind(':subject_id', $courseId);
            $db->bind(':teacher_id', $instructor['teacher_id']);
            $db->bind(':section_name', $section);
            $db->bind(':academic_year', $academicYear);
            $db->bind(':year_level', $yearLevel);
            $db->bind(':semester', $semester);
            $db->bind(':assigned_by', $_SESSION['user_id']);
        }
        
        $result = $db->execute();
        
        if ($result) {
            // Get updated course information with assignment details
            $db->query("SELECT 
                        s.subject_code,
                        s.subject_name,
                        u.full_name as instructor_name,
                        ca.section_name
                        FROM subjects s
                        LEFT JOIN course_assignments ca ON s.subject_id = ca.subject_id 
                        AND ca.academic_year = :academic_year 
                        AND ca.year_level = :year_level 
                        AND ca.semester = :semester
                        LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
                        LEFT JOIN users u ON t.user_id = u.user_id
                        WHERE s.subject_id = :course_id");
            
            $db->bind(':course_id', $courseId);
            $db->bind(':academic_year', $academicYear);
            $db->bind(':year_level', $yearLevel);
            $db->bind(':semester', $semester);
            $db->execute();
            $updatedCourse = $db->single();
            
            // Debug logging
            error_log("Course assigned successfully: " . json_encode($updatedCourse));
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Course assigned successfully!',
                'course' => $updatedCourse,
                'instructor_name' => $instructor['full_name'],
                'section' => $section
            ]);
            
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to assign course to database'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error assigning course: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while assigning course'
        ]);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?>
