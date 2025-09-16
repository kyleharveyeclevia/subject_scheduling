-- Add instructor_id column to subjects table
-- This migration links courses with specific instructors

USE subject_scheduling;

-- Add instructor_id column to subjects table
ALTER TABLE subjects ADD COLUMN instructor_id VARCHAR(20) NULL AFTER section_id;

-- Add foreign key constraint to teachers table
ALTER TABLE subjects ADD CONSTRAINT fk_subjects_instructor 
FOREIGN KEY (instructor_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX idx_subjects_instructor ON subjects(instructor_id);

-- Add comment to document the change
ALTER TABLE subjects MODIFY COLUMN instructor_id VARCHAR(20) NULL COMMENT 'Reference to teachers table - links course to specific instructor';

-- Verify the changes
SELECT 'Instructor field migration completed successfully' as status;
DESCRIBE subjects;
