-- Manual SQL commands to add sections to all courses
-- Format: 1A, 1B for 1st Year, 2A, 2B for 2nd Year, etc.

USE subject_scheduling;

-- First, add the section column if it doesn't exist
ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS section VARCHAR(10) DEFAULT '1A';

-- Update 1st Year subjects with sections 1A, 1B, 1C
UPDATE subjects SET section = '1A' WHERE year_level = '1st Year' AND subject_code IN ('MATH101', 'ENG101');
UPDATE subjects SET section = '1B' WHERE year_level = '1st Year' AND subject_code IN ('PHYS101', 'CHEM101');
UPDATE subjects SET section = '1C' WHERE year_level = '1st Year' AND subject_code NOT IN ('MATH101', 'ENG101', 'PHYS101', 'CHEM101');

-- Update 2nd Year subjects with sections 2A, 2B
UPDATE subjects SET section = '2A' WHERE year_level = '2nd Year' AND subject_code IN ('MATH201', 'ENG201');
UPDATE subjects SET section = '2B' WHERE year_level = '2nd Year' AND subject_code IN ('PHYS201', 'CHEM201');

-- Update 3rd Year subjects with sections 3A, 3B
UPDATE subjects SET section = '3A' WHERE year_level = '3rd Year' AND subject_code = 'MATH301';
UPDATE subjects SET section = '3B' WHERE year_level = '3rd Year' AND subject_code = 'RES301';

-- Update 4th Year subjects with sections 4A, 4B
UPDATE subjects SET section = '4A' WHERE year_level = '4th Year' AND subject_code = 'THESIS401';
UPDATE subjects SET section = '4B' WHERE year_level = '4th Year' AND subject_code = 'CAP401';

-- Verify the results
SELECT year_level, section, COUNT(*) as course_count 
FROM subjects 
GROUP BY year_level, section 
ORDER BY year_level, section;
