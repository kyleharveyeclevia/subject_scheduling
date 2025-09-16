-- Academic Years Table Migration
-- Add this table to the subject_scheduling database

USE subject_scheduling;

-- Create academic_years table
CREATE TABLE IF NOT EXISTS academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    is_locked BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default academic years
INSERT INTO academic_years (academic_year, status, is_locked) VALUES
('2024-2025', 'active', FALSE),
('2025-2026', 'inactive', FALSE),
('2026-2027', 'inactive', FALSE);

-- Add academic_year column to subjects table if it doesn't exist
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) DEFAULT '2024-2025';

-- Add foreign key constraint to link subjects to academic_years
ALTER TABLE subjects ADD CONSTRAINT fk_subjects_academic_year 
FOREIGN KEY (academic_year) REFERENCES academic_years(academic_year) ON DELETE RESTRICT;
