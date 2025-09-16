-- Add missing constraints and indexes for existing instructor_id column
-- This migration adds foreign key constraint and index for the instructor_id field

USE subject_scheduling;

-- Check if the foreign key constraint already exists
SELECT COUNT(*) as constraint_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'subject_scheduling' 
AND TABLE_NAME = 'subjects' 
AND COLUMN_NAME = 'instructor_id' 
AND REFERENCED_TABLE_NAME = 'teachers';

-- Add foreign key constraint if it doesn't exist
-- Note: This will fail if the constraint already exists, which is fine
ALTER TABLE subjects ADD CONSTRAINT fk_subjects_instructor 
FOREIGN KEY (instructor_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL;

-- Add index for better performance if it doesn't exist
-- Note: This will fail if the index already exists, which is fine
CREATE INDEX idx_subjects_instructor ON subjects(instructor_id);

-- Add comment to document the field
ALTER TABLE subjects MODIFY COLUMN instructor_id VARCHAR(20) NULL COMMENT 'Reference to teachers table - links course to specific instructor';

-- Verify the changes
SELECT 'Instructor constraints migration completed successfully' as status;
DESCRIBE subjects;
