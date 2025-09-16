-- Add section_id column to subjects table
-- This migration links courses with specific sections

USE subject_scheduling;

-- Add section_id column to subjects table
ALTER TABLE subjects ADD COLUMN section_id INT NULL AFTER year_level;

-- Add foreign key constraint
ALTER TABLE subjects ADD CONSTRAINT fk_subjects_section 
FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX idx_subjects_section ON subjects(section_id);

-- Update existing subjects to have a default section (optional)
-- This assigns existing subjects to the first available section of their year level
UPDATE subjects s 
JOIN (
    SELECT subject_id, year_level, 
           (SELECT section_id FROM sections WHERE year_level = s2.year_level AND status = 'available' LIMIT 1) as default_section
    FROM subjects s2
) AS temp ON s.subject_id = temp.subject_id
SET s.section_id = temp.default_section
WHERE s.section_id IS NULL;

-- Make section_id required for new records (after setting defaults)
ALTER TABLE subjects MODIFY COLUMN section_id INT NOT NULL;

-- Add comment to document the change
ALTER TABLE subjects MODIFY COLUMN section_id INT NOT NULL COMMENT 'Reference to sections table - links course to specific section';
