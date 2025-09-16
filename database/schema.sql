-- Subject Scheduling Login System Database Schema
-- Created: 2025-08-10

CREATE DATABASE IF NOT EXISTS subject_scheduling;
USE subject_scheduling;

-- Users table for all user types
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Teacher specific information
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    department ENUM('College of Communication and Information Technology', 'College of Teacher Education') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Student specific information
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    year_level ENUM('First Year', 'Second Year', 'Third Year', 'Fourth Year') NOT NULL,
    section ENUM('A', 'B', 'C') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Admin specific information
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    admin_id VARCHAR(20) UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Login attempts tracking for security
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20),
    ip_address VARCHAR(45),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_time (ip_address, attempt_time)
);

-- Email verification tokens
CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sections table for student sections and year levels
CREATE TABLE sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_year (section_name, year_level)
);

-- Subjects table for course information
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    subject_code VARCHAR(50) UNIQUE NOT NULL,
    units INT NOT NULL DEFAULT 3,
    year_level VARCHAR(20) NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table for classroom information
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY room_name (room_name)
);

-- Teacher Loads table for managing teacher-subject assignments with scheduling
CREATE TABLE teacher_loads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    section_id INT NOT NULL,
    room_id INT NOT NULL,
    units INT NOT NULL,
    schedule_days ENUM('MWF', 'TTH', 'F', 'S') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject_section (teacher_id, subject_id, section_id),
    UNIQUE KEY unique_room_time_schedule (room_id, schedule_days, start_time, end_time)
);

-- Insert default sections
INSERT INTO sections (section_name, year_level, status) VALUES
('A', '1st Year', 'available'),
('B', '1st Year', 'available'),
('C', '1st Year', 'available'),
('A', '2nd Year', 'available'),
('B', '2nd Year', 'available'),
('A', '3rd Year', 'available'),
('B', '3rd Year', 'available'),
('A', '4th Year', 'available'),
('B', '4th Year', 'available'),
('Einstein', '1st Year', 'unavailable'),
('Newton', '2nd Year', 'unavailable');

-- Insert default subjects
INSERT INTO subjects (subject_name, subject_code, units, year_level, status) VALUES
('Mathematics I', 'MATH101', 3, '1st Year', 'available'),
('English I', 'ENG101', 3, '1st Year', 'available'),
('Physics I', 'PHYS101', 3, '1st Year', 'available'),
('Chemistry I', 'CHEM101', 3, '1st Year', 'available'),
('Mathematics II', 'MATH201', 3, '2nd Year', 'available'),
('English II', 'ENG201', 3, '2nd Year', 'available'),
('Physics II', 'PHYS201', 3, '2nd Year', 'available'),
('Chemistry II', 'CHEM201', 3, '2nd Year', 'available'),
('Advanced Mathematics', 'MATH301', 3, '3rd Year', 'available'),
('Research Methods', 'RES301', 3, '3rd Year', 'available'),
('Thesis Writing', 'THESIS401', 6, '4th Year', 'available'),
('Capstone Project', 'CAP401', 6, '4th Year', 'available');

-- Insert default rooms
INSERT INTO rooms (room_name, status) VALUES
('Room 101', 'available'),
('Room 102', 'available'),
('Lab A', 'available'),
('Conference Room', 'unavailable'),
('Computer Lab', 'available');

-- Insert default admin account (password: @Admin1899)
-- Note: Password hash will be generated dynamically in setup.php for security
INSERT INTO users (user_id, full_name, email, password_hash, role, status, email_verified) 
VALUES ('22120091', 'System Administrator', 'admin@gmail.com', 'PLACEHOLDER_HASH', 'admin', 'approved', TRUE);

INSERT INTO admins (user_id, admin_id) 
VALUES ('22120091', '22120091');
