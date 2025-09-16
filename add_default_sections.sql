-- Add default sections to the database
-- This will populate the sections table with basic sections for each year level

USE subject_scheduling;

-- Clear existing sections (optional - comment out if you want to keep existing ones)
-- DELETE FROM sections;

-- Add default sections for each year level
INSERT INTO sections (section_name, year_level, status) VALUES
-- 1st Year Sections
('A', '1st Year', 'available'),
('B', '1st Year', 'available'),
('C', '1st Year', 'available'),

-- 2nd Year Sections
('A', '2nd Year', 'available'),
('B', '2nd Year', 'available'),
('C', '2nd Year', 'available'),

-- 3rd Year Sections
('A', '3rd Year', 'available'),
('B', '3rd Year', 'available'),

-- 4th Year Sections
('A', '4th Year', 'available'),
('B', '4th Year', 'available')

-- Add more sections as needed
-- ('D', '1st Year', 'available'),
-- ('D', '2nd Year', 'available'),
-- ('C', '3rd Year', 'available'),
-- ('C', '4th Year', 'available')
ON DUPLICATE KEY UPDATE 
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;

-- Show the added sections
SELECT 
    section_id,
    section_name,
    year_level,
    status,
    created_at
FROM sections 
ORDER BY year_level, section_name;

-- Count sections by year level
SELECT 
    year_level,
    COUNT(*) as section_count,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_count
FROM sections 
GROUP BY year_level 
ORDER BY year_level;
