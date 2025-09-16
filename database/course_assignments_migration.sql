-- Course Assignments Table Migration Script
-- Create a separate table to store course assignments

USE subject_scheduling;

-- Create course_assignments table to store instructor and section assignments
CREATE TABLE IF NOT EXISTS course_assignments (
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
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_course_assignments_subject_id ON course_assignments(subject_id);
CREATE INDEX IF NOT EXISTS idx_course_assignments_teacher_id ON course_assignments(teacher_id);
CREATE INDEX IF NOT EXISTS idx_course_assignments_section ON course_assignments(section_name);
CREATE INDEX IF NOT EXISTS idx_course_assignments_academic_year ON course_assignments(academic_year);
CREATE INDEX IF NOT EXISTS idx_course_assignments_year_semester ON course_assignments(year_level, semester);

-- Verify the changes
SELECT 'Course assignments table migration completed successfully' as status;
