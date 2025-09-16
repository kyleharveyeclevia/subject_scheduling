<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['course_id']) || !isset($input['instructor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$courseId = intval($input['course_id']);
$instructorId = intval($input['instructor_id']);

try {
    $db = new Database();
    
    // First, check if the course exists
    $db->query("SELECT subject_id as id, subject_code as code, subject_name as title FROM subjects WHERE subject_id = :course_id");
    $db->bind(':course_id', $courseId);
    $course = $db->single();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit;
    }
    
    // Check if the instructor exists
    $db->query("SELECT user_id, full_name FROM teachers WHERE user_id = :instructor_id AND status = 'active'");
    $db->bind(':instructor_id', $instructorId);
    $instructor = $db->single();
    
    if (!$instructor) {
        echo json_encode(['success' => false, 'message' => 'Instructor not found or inactive']);
        exit;
    }
    
    // Check if there's already an assignment for this course
    $db->query("SELECT id FROM teacher_assignments WHERE subject_id = :course_id");
    $db->bind(':course_id', $courseId);
    $existingAssignment = $db->single();
    
    if ($existingAssignment) {
        // Update existing assignment
        $db->query("UPDATE teacher_assignments SET teacher_id = :teacher_id, assigned_date = NOW() WHERE subject_id = :subject_id");
        $db->bind(':teacher_id', $instructorId);
        $db->bind(':subject_id', $courseId);
        $db->execute();
        
        $message = "Instructor assignment updated successfully";
    } else {
        // Create new assignment
        $db->query("INSERT INTO teacher_assignments (subject_id, teacher_id, assigned_date) VALUES (:subject_id, :teacher_id, NOW())");
        $db->bind(':subject_id', $courseId);
        $db->bind(':teacher_id', $instructorId);
        $db->execute();
        
        $message = "Instructor assigned successfully";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'course' => $course,
        'instructor' => $instructor
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in assign_instructor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in assign_instructor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
