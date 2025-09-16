-- Duplicate existing courses for each section within the same year level
-- This script creates section-specific copies of courses

USE subject_scheduling;

-- First, let's see what we're working with
SELECT 'Current sections' as info, COUNT(*) as count FROM sections WHERE status = 'available'
UNION ALL
SELECT 'Current courses', COUNT(*) FROM subjects;

-- Show sections by year level
SELECT 
    year_level,
    GROUP_CONCAT(section_name ORDER BY section_name) as sections,
    COUNT(*) as section_count
FROM sections 
WHERE status = 'available' 
GROUP BY year_level 
ORDER BY year_level;

-- Show existing courses by year level
SELECT 
    year_level,
    COUNT(*) as course_count,
    GROUP_CONCAT(subject_code ORDER BY subject_code) as courses
FROM subjects 
GROUP BY year_level 
ORDER BY year_level;

-- Create duplicate courses for each section
-- We'll use a temporary table approach to avoid complex INSERT statements

-- Create temporary table to store the courses we want to duplicate
CREATE TEMPORARY TABLE temp_courses_to_duplicate AS
SELECT 
    s.subject_code,
    s.subject_name,
    s.units,
    s.year_level,
    s.semester,
    s.status,
    s.academic_year,
    sec.section_id,
    sec.section_name
FROM subjects s
CROSS JOIN sections sec
WHERE sec.status = 'available' 
  AND s.year_level = sec.year_level
  AND NOT EXISTS (
      -- Check if this course already exists for this section
      SELECT 1 FROM subjects existing 
      WHERE existing.subject_code = s.subject_code 
        AND existing.year_level = s.year_level 
        AND existing.section_id = sec.section_id
  );

-- Show what will be duplicated
SELECT 
    'Courses to duplicate' as info,
    COUNT(*) as count
FROM temp_courses_to_duplicate;

-- Show sample of what will be created
SELECT 
    subject_code,
    subject_name,
    year_level,
    section_name,
    status
FROM temp_courses_to_duplicate 
ORDER BY year_level, section_name, subject_code 
LIMIT 10;

-- Insert the duplicate courses
INSERT INTO subjects (subject_code, subject_name, units, year_level, semester, section_id, status, academic_year)
SELECT 
    subject_code,
    subject_name,
    units,
    year_level,
    semester,
    section_id,
    status,
    academic_year
FROM temp_courses_to_duplicate;

-- Show results
SELECT 
    'After duplication' as info,
    COUNT(*) as total_courses
FROM subjects;

-- Show courses by section
SELECT 
    s.year_level,
    s.section_name,
    COUNT(sub.subject_id) as course_count
FROM sections s
LEFT JOIN subjects sub ON s.section_id = sub.section_id
WHERE s.status = 'available'
GROUP BY s.year_level, s.section_name
ORDER BY s.year_level, s.section_name;

-- Show sample courses for each section
SELECT 
    s.year_level,
    s.section_name,
    sub.subject_code,
    sub.subject_name,
    sub.status
FROM sections s
JOIN subjects sub ON s.section_id = sub.section_id
WHERE s.status = 'available'
ORDER BY s.year_level, s.section_name, sub.subject_code
LIMIT 20;

-- Clean up
DROP TEMPORARY TABLE IF EXISTS temp_courses_to_duplicate;

-- Final summary
SELECT 
    'DUPLICATION COMPLETE!' as status,
    'Each section now has the same courses as other sections in the same year level' as description;
