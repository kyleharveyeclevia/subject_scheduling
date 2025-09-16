-- Update all existing courses to have academic year '2025-2026'
-- This script will update the academic_year column for all courses that don't have an academic year set

-- First, let's see what courses currently exist and their academic year status
SELECT 
    subject_id,
    subject_code,
    subject_name,
    year_level,
    semester,
    academic_year,
    status
FROM subjects 
ORDER BY subject_code;

-- Update all courses to have academic year '2025-2026'
-- This will update courses that have NULL or empty academic_year
UPDATE subjects 
SET academic_year = '2025-2026' 
WHERE academic_year IS NULL 
   OR academic_year = '' 
   OR academic_year = '2024-2025';

-- Verify the update was successful
SELECT 
    COUNT(*) as total_courses,
    academic_year,
    COUNT(*) as count_per_year
FROM subjects 
GROUP BY academic_year
ORDER BY academic_year;

-- Show updated courses
SELECT 
    subject_id,
    subject_code,
    subject_name,
    year_level,
    semester,
    academic_year,
    status
FROM subjects 
WHERE academic_year = '2025-2026'
ORDER BY year_level, semester, subject_code;
