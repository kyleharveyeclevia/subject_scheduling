-- Add Future Status to Academic Years Table Migration
-- This migration adds support for 'future' status in academic_years table

USE subject_scheduling;

-- Modify the status ENUM to include 'future' status
ALTER TABLE academic_years MODIFY COLUMN status ENUM('active', 'inactive', 'future') NOT NULL DEFAULT 'active';

-- Insert some sample future academic years
INSERT INTO academic_years (academic_year, status, is_locked) VALUES
('2027-2028', 'future', FALSE),
('2028-2029', 'future', FALSE),
('2029-2030', 'future', FALSE);
