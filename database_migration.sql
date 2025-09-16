-- Database Migration Script for Courses Dashboard
-- Add semester column to the subjects table

-- Add semester column (default to 'first' for existing records)
ALTER TABLE subjects ADD COLUMN semester VARCHAR(20) DEFAULT 'first' AFTER year_level;

-- Update existing records to have proper semester values
-- This ensures existing courses are properly categorized
UPDATE subjects SET semester = 'first' WHERE semester IS NULL OR semester = '';

-- Add index for better performance
CREATE INDEX idx_subjects_semester ON subjects(semester);
CREATE INDEX idx_subjects_year_semester ON subjects(year_level, semester);

-- Verify the changes
SELECT 'Migration completed successfully' as status;
