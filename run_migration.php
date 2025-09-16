<?php
// Simple migration script to create course_assignments table
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Create course_assignments table
    $db->query("CREATE TABLE IF NOT EXISTS course_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_id INT NOT NULL,
        teacher_id VARCHAR(20) NOT NULL,
        section_name VARCHAR(100) NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        year_level VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        assigned_by VARCHAR(20) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY unique_subject_assignment (subject_id, academic_year, year_level, semester)
    )");
    $db->execute();
    
    // Add indexes for better performance
    $db->query("CREATE INDEX IF NOT EXISTS idx_course_assignments_subject_id ON course_assignments(subject_id)");
    $db->execute();
    
    $db->query("CREATE INDEX IF NOT EXISTS idx_course_assignments_teacher_id ON course_assignments(teacher_id)");
    $db->execute();
    
    $db->query("CREATE INDEX IF NOT EXISTS idx_course_assignments_section ON course_assignments(section_name)");
    $db->execute();
    
    $db->query("CREATE INDEX IF NOT EXISTS idx_course_assignments_academic_year ON course_assignments(academic_year)");
    $db->execute();
    
    $db->query("CREATE INDEX IF NOT EXISTS idx_course_assignments_year_semester ON course_assignments(year_level, semester)");
    $db->execute();
    
    echo "✅ Course assignments table migration completed successfully!\n";
    echo "The course_assignments table has been created with all necessary indexes.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
