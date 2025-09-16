-- Subjects Table Migration Script
-- Add missing columns to subjects table for academic year course management

USE subject_scheduling;

-- Add missing columns to subjects table
ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS section VARCHAR(10) DEFAULT 'A',
ADD COLUMN IF NOT EXISTS semester VARCHAR(20) DEFAULT 'first',
ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025';

-- Update existing subjects with sample data
-- 1st Year subjects
UPDATE subjects SET 
    section = 'A',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '1st Year' AND subject_code IN ('MATH101', 'ENG101');

UPDATE subjects SET 
    section = 'B',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '1st Year' AND subject_code IN ('PHYS101', 'CHEM101');

UPDATE subjects SET 
    section = 'C',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '1st Year' AND subject_code NOT IN ('MATH101', 'ENG101', 'PHYS101', 'CHEM101');

-- 2nd Year subjects
UPDATE subjects SET 
    section = 'A',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '2nd Year' AND subject_code IN ('MATH201', 'ENG201');

UPDATE subjects SET 
    section = 'B',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '2nd Year' AND subject_code IN ('PHYS201', 'CHEM201');

-- 3rd Year subjects
UPDATE subjects SET 
    section = 'A',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '3rd Year' AND subject_code = 'MATH301';

UPDATE subjects SET 
    section = 'B',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '3rd Year' AND subject_code = 'RES301';

-- 4th Year subjects
UPDATE subjects SET 
    section = 'A',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '4th Year' AND subject_code = 'THESIS401';

UPDATE subjects SET 
    section = 'B',
    semester = 'first',
    academic_year = '2024-2025'
WHERE year_level = '4th Year' AND subject_code = 'CAP401';

-- Add some second semester courses
INSERT INTO subjects (subject_name, subject_code, units, year_level, section, semester, academic_year, status) VALUES
('Mathematics I - Second Sem', 'MATH101S', 3, '1st Year', 'A', 'second', '2024-2025', 'available'),
('English I - Second Sem', 'ENG101S', 3, '1st Year', 'B', 'second', '2024-2025', 'available'),
('Mathematics II - Second Sem', 'MATH201S', 3, '2nd Year', 'A', 'second', '2024-2025', 'available'),
('English II - Second Sem', 'ENG201S', 3, '2nd Year', 'B', 'second', '2024-2025', 'available'),
('Advanced Mathematics - Second Sem', 'MATH301S', 3, '3rd Year', 'A', 'second', '2024-2025', 'available'),
('Research Methods - Second Sem', 'RES301S', 3, '3rd Year', 'B', 'second', '2024-2025', 'available');

-- Add some summer courses for 3rd Year
INSERT INTO subjects (subject_name, subject_code, units, year_level, section, semester, academic_year, status) VALUES
('Summer Mathematics', 'MATH301SU', 3, '3rd Year', 'A', 'summer', '2024-2025', 'available'),
('Summer Research', 'RES301SU', 3, '3rd Year', 'B', 'summer', '2024-2025', 'available');

-- Verify the changes
SELECT 'Subjects table migration completed successfully' as status;
SELECT COUNT(*) as total_subjects FROM subjects;
SELECT year_level, section, semester, COUNT(*) as count FROM subjects GROUP BY year_level, section, semester ORDER BY year_level, section, semester;
