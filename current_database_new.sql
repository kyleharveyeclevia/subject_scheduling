-- Database Export for Subject Scheduling System
-- Generated on: 2025-09-15 00:12:44

CREATE DATABASE IF NOT EXISTS `subject_scheduling`;
USE `subject_scheduling`;

-- Table structure for table `academic_years`
DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_year` (`academic_year`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `academic_years`
INSERT INTO `academic_years` (`id`, `academic_year`, `status`, `created_at`, `updated_at`, `is_locked`) VALUES
('9', '2025-2026', 'inactive', '2025-09-04 10:12:06', '2025-09-05 03:13:51', '0'),
('11', '2027-2028', 'active', '2025-09-05 03:10:05', '2025-09-05 04:50:47', '0');

-- Table structure for table `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `admins`
INSERT INTO `admins` (`id`, `user_id`, `admin_id`) VALUES
('1', '22120091', '22120091');

-- Table structure for table `course_assignments`
DROP TABLE IF EXISTS `course_assignments`;
CREATE TABLE `course_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `room_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subject_assignment` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `course_schedules`
DROP TABLE IF EXISTS `course_schedules`;
CREATE TABLE `course_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `year_level` varchar(20) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `course_title` varchar(100) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `schedule_day` varchar(20) NOT NULL,
  `schedule_time` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `course_schedules`
INSERT INTO `course_schedules` (`id`, `year_level`, `section_name`, `semester`, `course_title`, `course_code`, `schedule_day`, `schedule_time`, `created_at`, `updated_at`) VALUES
('79', '', '', '1st Semester', 'MATH101', 'MATH101', 'Thursday', '10:00:00', '2025-09-11 22:26:14', '2025-09-11 22:26:14'),
('78', '', '', '1st Semester', 'MATH101', 'MATH101', 'Tuesday', '10:00:00', '2025-09-11 22:26:14', '2025-09-11 22:26:14'),
('76', '', '', '1st Semester', 'CS101', 'CS101', 'Wednesday', '08:00:00', '2025-09-11 22:26:14', '2025-09-11 22:26:14'),
('77', '', '', '1st Semester', 'CS101', 'CS101', 'Friday', '08:00:00', '2025-09-11 22:26:14', '2025-09-11 22:26:14'),
('75', '', '', '1st Semester', 'CS101', 'CS101', 'Monday', '08:00:00', '2025-09-11 22:26:14', '2025-09-11 22:26:14'),
('74', '1st Year', '1A', 'first', 'CC101', 'CC101', 'Wednesday', '08:00', '2025-09-11 22:23:06', '2025-09-11 22:23:06'),
('73', '1st Year', '1A', 'first', 'CC101', 'CC101', 'Monday', '08:00', '2025-09-11 22:23:06', '2025-09-11 22:23:06');

-- Table structure for table `email_verifications`
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `login_attempts`
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `login_attempts`
INSERT INTO `login_attempts` (`id`, `user_id`, `ip_address`, `attempt_time`, `success`) VALUES
('1', '22120091', '::1', '2025-08-11 03:45:59', '0'),
('2', '22120091', '::1', '2025-08-11 03:46:00', '0'),
('3', '22120091', '::1', '2025-08-11 03:46:01', '0'),
('4', '22120091', '::1', '2025-08-11 03:46:01', '0'),
('5', '22120091', '::1', '2025-08-11 03:46:02', '0'),
('6', '22120091', '::1', '2025-08-11 03:47:06', '0'),
('7', '22120091', '::1', '2025-08-11 03:47:08', '0'),
('8', '22120091', '::1', '2025-08-11 03:47:09', '0'),
('9', '22120091', '::1', '2025-08-11 03:47:09', '0'),
('10', '22120091', '::1', '2025-08-11 03:47:09', '0'),
('11', 'DFGVDSVGDV', '::1', '2025-08-11 18:59:33', '0'),
('12', '16189999', '::1', '2025-08-13 09:22:50', '0'),
('13', '06189999', '::1', '2025-08-13 09:35:21', '0'),
('14', '06189999', '::1', '2025-08-13 09:35:28', '0'),
('15', '06189999', '::1', '2025-08-13 09:46:56', '0'),
('16', '06189999', '::1', '2025-08-13 09:47:02', '0'),
('17', '06189999', '::1', '2025-08-13 09:50:36', '0'),
('18', '06189999', '::1', '2025-08-13 09:50:43', '0'),
('19', '06189999', '::1', '2025-08-13 09:50:55', '0'),
('20', '06189999', '::1', '2025-08-13 09:51:43', '0'),
('21', '06189999', '::1', '2025-08-13 09:52:06', '0'),
('22', '06189999', '::1', '2025-08-13 09:53:24', '0'),
('23', '06189999', '::1', '2025-08-13 09:53:33', '0'),
('24', '06189999', '::1', '2025-08-13 09:54:30', '0'),
('25', '06189999', '::1', '2025-08-13 09:54:34', '0'),
('26', '06189999', '::1', '2025-08-13 09:56:48', '0'),
('27', '06189999', '::1', '2025-08-13 09:57:08', '0'),
('28', '06189999', '::1', '2025-08-13 09:57:27', '0'),
('29', '06189999', '::1', '2025-08-13 09:57:43', '0'),
('30', '06189999', '::1', '2025-08-13 09:57:49', '0'),
('31', '06189999', '::1', '2025-08-13 09:57:54', '0'),
('32', '06189999', '::1', '2025-08-13 09:59:17', '0'),
('33', '22120091', '::1', '2025-08-13 16:57:32', '0'),
('34', '06189999', '::1', '2025-08-14 10:23:34', '0'),
('35', '06189999', '::1', '2025-08-14 10:23:48', '0'),
('36', '22120091', '::1', '2025-08-15 12:02:46', '0'),
('37', '22120091', '::1', '2025-08-15 12:03:04', '0'),
('38', '22120091', '::1', '2025-08-15 12:03:20', '0'),
('39', '22120091', '::1', '2025-08-15 12:03:33', '0'),
('40', 'admin', '::1', '2025-08-15 14:07:01', '0'),
('41', '22120091', '::1', '2025-08-16 13:15:27', '0'),
('42', '12345678', '::1', '2025-08-17 16:27:40', '0'),
('43', '12345678', '::1', '2025-08-17 18:34:47', '0'),
('44', '22120091', '::1', '2025-08-19 21:00:36', '0'),
('45', '22120091', '::1', '2025-08-19 21:00:41', '0'),
('46', '22120091', '::1', '2025-08-20 14:02:00', '0'),
('47', '22120091', '::1', '2025-08-20 14:41:27', '0'),
('48', '22120091', '::1', '2025-08-24 06:34:46', '0'),
('49', '22120091', '::1', '2025-08-26 03:37:44', '0'),
('50', '22120091', '::1', '2025-09-05 09:00:03', '0'),
('51', '22120091', '::1', '2025-09-08 08:41:54', '0'),
('52', 'hfghfghj', '::1', '2025-09-08 08:44:15', '0'),
('53', 'hfghfghj', '::1', '2025-09-08 08:44:24', '0'),
('54', 'hfghfghj', '::1', '2025-09-08 08:44:29', '0'),
('55', 'hfghfghj', '::1', '2025-09-08 08:44:44', '0'),
('56', 'hfghfghj', '::1', '2025-09-08 08:44:52', '0'),
('57', 'dfsdargfer', '::1', '2025-09-08 08:45:02', '0'),
('58', 'dfsdargfer', '::1', '2025-09-08 08:46:20', '0'),
('59', 'dfsdargfer', '::1', '2025-09-08 08:46:27', '0'),
('60', 'dfsdargfer', '::1', '2025-09-08 08:46:32', '0'),
('61', '22120091', '::1', '2025-09-08 10:15:45', '0'),
('62', '22-1-2-0098', '::1', '2025-09-08 10:16:44', '0'),
('63', '22-1-2-0098', '::1', '2025-09-08 10:16:56', '0'),
('64', '22120091', '127.0.0.1', '2025-09-08 10:19:07', '0'),
('65', '22120091', '127.0.0.1', '2025-09-08 10:19:07', '0'),
('66', '22120091', '127.0.0.1', '2025-09-08 10:19:07', '0'),
('67', '22120091', '127.0.0.1', '2025-09-08 10:19:07', '0'),
('68', '22120091', '127.0.0.1', '2025-09-08 10:19:07', '0'),
('69', '22-2-2-0098', '::1', '2025-09-08 10:19:54', '0'),
('70', '22120091', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('71', '22120091', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('72', '22120091', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('73', '22120091', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('74', '22120091', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('75', '22-1-2-0098', '127.0.0.1', '2025-09-08 10:20:10', '0'),
('76', '22120091', '127.0.0.1', '2025-09-08 10:21:17', '0'),
('77', '22120091', '127.0.0.1', '2025-09-08 10:21:17', '0'),
('78', '22120091', '127.0.0.1', '2025-09-08 10:21:17', '0'),
('79', '22120091', '127.0.0.1', '2025-09-08 10:21:17', '0'),
('80', '22120091', '127.0.0.1', '2025-09-08 10:21:17', '0'),
('81', '22-1-2-0098', '::1', '2025-09-08 10:21:52', '0'),
('82', '22-1-2-0098', '::1', '2025-09-08 10:21:58', '0'),
('83', '22-1-2-0098', '::1', '2025-09-08 10:22:09', '0'),
('84', '22-1-2-0098', '::1', '2025-09-08 10:23:04', '0'),
('85', '22-1-2-0098', '::1', '2025-09-08 10:23:47', '0'),
('86', '22-1-2-0098', '::1', '2025-09-08 10:23:57', '0'),
('87', 'TEA250812457', '127.0.0.1', '2025-09-08 10:27:01', '1'),
('88', 'SC223', '127.0.0.1', '2025-09-08 10:27:01', '1'),
('89', 'TEA250812457', '127.0.0.1', '2025-09-08 10:27:24', '1'),
('90', 'SC223', '127.0.0.1', '2025-09-08 10:27:24', '1'),
('91', 'TEA250812457', '127.0.0.1', '2025-09-08 10:27:24', '0'),
('92', '22-2-2-0098', '::1', '2025-09-08 10:28:11', '0'),
('93', '22-2-2-0098', '::1', '2025-09-08 10:28:25', '0'),
('94', 'SC221', '::1', '2025-09-08 10:33:31', '1'),
('95', '22-1-2-0098', '::1', '2025-09-08 10:34:28', '1'),
('96', '22-1-2-0098', '::1', '2025-09-08 10:35:35', '0'),
('97', '22-1-2-0098', '::1', '2025-09-08 10:36:37', '0'),
('98', '22-1-2-0098', '::1', '2025-09-08 10:36:44', '0'),
('99', '22-1-2-0098', '::1', '2025-09-08 10:36:52', '0'),
('100', '22-1-2-0091', '::1', '2025-09-08 10:42:33', '0'),
('101', '22-1-2-0098', '::1', '2025-09-08 10:42:57', '1'),
('102', 'knjklhewr fv', '::1', '2025-09-08 10:43:28', '0'),
('103', 'SC1234', '::1', '2025-09-08 10:59:36', '1'),
('104', '22-1-2-0091', '::1', '2025-09-08 18:53:23', '0'),
('105', '22120091', '::1', '2025-09-12 05:56:54', '0'),
('106', '22120091', '::1', '2025-09-12 05:57:04', '0');

-- Table structure for table `rooms`
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('available','unavailable') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_name` (`room_name`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `rooms`
INSERT INTO `rooms` (`id`, `room_name`, `status`, `created_at`, `updated_at`) VALUES
('1', 'TBA', 'available', '2025-09-11 21:38:09', '2025-09-11 21:38:09'),
('6', 'Room 205', 'unavailable', '2025-08-15 12:16:18', '2025-08-23 15:48:41'),
('30', 'Room 203', 'unavailable', '2025-08-22 06:02:15', '2025-08-23 15:48:30'),
('32', 'Room 101', 'available', '2025-08-22 08:03:02', '2025-08-22 08:03:02'),
('33', 'Room 102', 'available', '2025-08-22 08:03:33', '2025-08-23 16:18:15'),
('34', 'Room 103', 'available', '2025-08-22 08:03:46', '2025-08-22 08:03:46'),
('35', 'Hybrid Room', 'available', '2025-08-22 08:04:09', '2025-08-26 06:11:16'),
('36', 'Computer Laboratory', 'available', '2025-08-22 08:04:28', '2025-08-26 06:11:06'),
('37', 'Court', 'available', '2025-08-22 08:04:46', '2025-08-26 06:11:12'),
('38', 'To be Announced', 'available', '2025-08-22 08:05:05', '2025-08-23 15:54:46'),
('39', 'Room 201', 'unavailable', '2025-08-22 08:09:30', '2025-08-23 15:49:08'),
('40', 'Extra Room 1', 'available', '2025-08-26 06:11:41', '2025-09-04 07:48:50'),
('41', 'Extra Room 2', 'available', '2025-09-04 07:48:31', '2025-09-04 07:48:31');

-- Table structure for table `scheduled_courses`
DROP TABLE IF EXISTS `scheduled_courses`;
CREATE TABLE `scheduled_courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `section_id` int NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_title` varchar(255) NOT NULL,
  `instructor` varchar(100) DEFAULT 'TBA',
  `room` varchar(100) DEFAULT 'TBA',
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `units` int NOT NULL,
  `semester` varchar(20) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `timetable_type` enum('student','instructor','room') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_schedule` (`section_id`,`course_code`,`day_of_week`,`start_time`,`timetable_type`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_section_semester` (`section_id`,`semester`,`academic_year`),
  KEY `idx_course_code` (`course_code`),
  KEY `idx_day_time` (`day_of_week`,`start_time`),
  KEY `idx_timetable_type` (`timetable_type`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `scheduled_courses`
INSERT INTO `scheduled_courses` (`id`, `subject_id`, `section_id`, `course_code`, `course_title`, `instructor`, `room`, `day_of_week`, `start_time`, `end_time`, `units`, `semester`, `academic_year`, `timetable_type`, `status`, `created_at`, `updated_at`) VALUES
('2', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'tuesday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'student', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34'),
('3', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'tuesday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'instructor', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34'),
('4', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'tuesday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'room', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34'),
('5', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'thursday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'student', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34'),
('6', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'thursday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'instructor', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34'),
('7', '227', '31', 'CC101', 'Intoduction to Computing (1B)', 'TBA', 'TBA', 'thursday', '08:00:00', '11:00:00', '3', 'first', '2024-2025', 'room', 'active', '2025-09-12 14:14:34', '2025-09-12 14:14:34');

-- Table structure for table `sections`
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `section_id` int NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `year_level` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('available','unavailable') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `section_year` (`section_name`,`year_level`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `sections`
INSERT INTO `sections` (`section_id`, `section_name`, `year_level`, `status`, `created_at`, `updated_at`) VALUES
('19', '3A', '3rd Year', 'available', '2025-08-22 07:10:35', '2025-08-24 06:14:12'),
('20', '2B', '2nd Year', 'available', '2025-08-22 07:12:45', '2025-08-26 06:05:05'),
('21', '2A', '2nd Year', 'available', '2025-08-22 07:59:32', '2025-08-24 06:13:56'),
('22', '3B', '3rd Year', 'available', '2025-08-22 07:59:51', '2025-08-24 06:14:23'),
('24', '4A', '4th Year', 'available', '2025-08-22 08:08:46', '2025-08-24 06:14:32'),
('25', '4B', '4th Year', 'available', '2025-08-22 08:08:57', '2025-08-24 06:14:38'),
('27', '2C', '2nd Year', 'unavailable', '2025-08-23 15:51:16', '2025-08-26 06:05:11'),
('30', '1A', '1st Year', 'available', '2025-09-03 10:23:27', '2025-09-03 10:23:27'),
('31', '1B', '1st Year', 'available', '2025-09-03 10:23:57', '2025-09-03 10:23:57'),
('32', '1C', '1st Year', 'unavailable', '2025-09-03 10:24:40', '2025-09-08 11:25:21');

-- Table structure for table `students`
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `year_level` enum('First Year','Second Year','Third Year','Fourth Year') NOT NULL,
  `section` enum('A','B','C') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `students`
INSERT INTO `students` (`id`, `user_id`, `student_id`, `year_level`, `section`) VALUES
('11', 'STU250908906', '22-1-2-0098', 'Fourth Year', 'B'),
('2', 'STU250908925', '467567', 'First Year', 'A'),
('3', 'STU250908780', '22-1-2-0091', 'First Year', 'A'),
('4', 'STU250908674', '43-5-3-4564', 'First Year', 'A'),
('5', 'STU250908816', '53-6-4-7556', 'First Year', 'A'),
('6', 'STU250908317', '65-8-6-7867', 'First Year', 'A'),
('7', 'STU250908024', '22-1-2-0092', 'First Year', 'A'),
('8', 'STU250908251', '43-6-5-4756', 'First Year', 'A'),
('9', 'STU250908092', '23-4-5-3255', 'First Year', 'A');

-- Table structure for table `subjects`
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subject_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `units` int NOT NULL DEFAULT '3',
  `year_level` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `section_id` int DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'first',
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '2024-2025',
  `status` enum('available','unavailable') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `instructor_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`subject_id`),
  KEY `subject_code` (`subject_code`),
  KEY `idx_subjects_semester` (`semester`),
  KEY `idx_subjects_year_semester` (`year_level`,`semester`),
  KEY `idx_year_level` (`year_level`),
  KEY `idx_semester` (`semester`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_status` (`status`),
  KEY `idx_subjects_section_id` (`section_id`),
  CONSTRAINT `fk_subjects_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `subjects`
INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `units`, `year_level`, `section_id`, `semester`, `academic_year`, `status`, `created_at`, `updated_at`, `instructor_id`) VALUES
('210', 'CS Elective I', 'CSE 1', '3', '2nd Year', '21', 'first', '2024-2025', 'available', '2025-09-06 14:00:56', '2025-09-06 14:02:11', NULL),
('220', 'National Service Training Program', 'NSTP101', '3', '1st Year', '30', '1st Semester', '2024-2025', 'available', '2025-09-11 22:36:43', '2025-09-12 15:16:21', NULL),
('223', 'Technical Writing', 'ENG102', '3', '1st Year', '30', '2nd Semester', '2024-2025', 'available', '2025-09-11 22:36:43', '2025-09-12 15:16:21', NULL),
('226', 'Intoduction to Computing', 'CC101', '3', '1st Year', '30', 'first', '2024-2025', 'available', '2025-09-12 06:22:42', '2025-09-12 15:16:21', NULL),
('227', 'Intoduction to Computing', 'CC101', '3', '1st Year', '31', 'first', '2024-2025', 'available', '2025-09-12 06:22:42', '2025-09-12 15:22:42', NULL),
('228', 'Enhanced Communication Skills', 'EN +', '3', '1st Year', '30', 'first', '2024-2025', 'available', '2025-09-12 06:23:17', '2025-09-12 15:16:21', NULL),
('229', 'Enhanced Communication Skills', 'EN +', '3', '1st Year', '31', 'first', '2024-2025', 'available', '2025-09-12 06:23:17', '2025-09-12 15:23:05', NULL),
('230', 'Kontekstwalisadong Komunikasyon', 'FILN 1', '3', '1st Year', '30', 'first', '2024-2025', 'available', '2025-09-12 06:24:38', '2025-09-12 15:47:20', NULL),
('231', 'Kontekstwalisadong Komunikasyon', 'FILN 1', '2', '1st Year', '31', 'first', '2024-2025', 'available', '2025-09-12 06:24:38', '2025-09-12 15:22:55', NULL),
('234', 'Understanding the Self', 'GEC 1', '3', '1st Year', '30', 'first', '2024-2025', 'available', '2025-09-15 04:42:49', '2025-09-15 04:42:49', NULL),
('235', 'Understanding the Self', 'GEC 1', '3', '1st Year', '31', 'first', '2024-2025', 'available', '2025-09-15 04:42:49', '2025-09-15 04:42:49', NULL);

-- Table structure for table `teacher_assignments`
DROP TABLE IF EXISTS `teacher_assignments`;
CREATE TABLE `teacher_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `section` varchar(100) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `assigned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`subject_id`,`academic_year`,`year_level`,`semester`,`section`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_year_level` (`year_level`),
  KEY `idx_semester` (`semester`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `teacher_assignments`
INSERT INTO `teacher_assignments` (`id`, `subject_id`, `teacher_id`, `section`, `academic_year`, `year_level`, `semester`, `assigned_date`, `updated_at`) VALUES
('1', '1', 'T001', 'A', '2024-2025', '1st Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36'),
('2', '2', 'T002', 'B', '2024-2025', '1st Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36'),
('3', '3', 'T003', 'C', '2024-2025', '1st Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36'),
('4', '4', 'T001', 'A', '2024-2025', '1st Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36'),
('5', '5', 'T002', 'A', '2024-2025', '2nd Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36'),
('6', '6', 'T003', 'B', '2024-2025', '2nd Year', 'first', '2025-09-01 09:59:36', '2025-09-01 09:59:36');

-- Table structure for table `teacher_loads`
DROP TABLE IF EXISTS `teacher_loads`;
CREATE TABLE `teacher_loads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` varchar(20) NOT NULL,
  `subject_id` int NOT NULL,
  `section_id` int NOT NULL,
  `room_id` int NOT NULL,
  `units` int NOT NULL,
  `schedule_days` enum('MWF','TTH','F','S') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_subject_section` (`teacher_id`,`subject_id`,`section_id`),
  UNIQUE KEY `unique_room_time_schedule` (`room_id`,`schedule_days`,`start_time`,`end_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `teachers`
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `department` enum('College of Communication and Information Technology','College of Teacher Education') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `teacher_id` (`teacher_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `teachers`
INSERT INTO `teachers` (`id`, `user_id`, `teacher_id`, `department`) VALUES
('2', 'TEA250812457', 'SC223', 'College of Teacher Education'),
('3', 'TEA250812616', 'SC222', 'College of Teacher Education'),
('10', 'TEA250822173', 'SC123', 'College of Communication and Information Technology'),
('14', 'TEA250822016', 'SC224', 'College of Teacher Education'),
('7', 'TEA250818331', 'SC112', 'College of Communication and Information Technology'),
('9', 'TEA250822585', 'SC101', 'College of Communication and Information Technology'),
('11', 'TEA250822252', 'SC111', 'College of Communication and Information Technology'),
('12', 'TEA250822306', 'SC102', 'College of Communication and Information Technology'),
('13', 'TEA250822326', 'SC221', 'College of Teacher Education'),
('15', 'TEA250822051', 'SC107', 'College of Communication and Information Technology'),
('21', 'TEA250908142', 'SC1234', 'College of Communication and Information Technology'),
('19', 'TEA250903546', 'SC114', 'College of Teacher Education'),
('22', 'TBA001', 'TBA001', 'College of Communication and Information Technology');

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `users`
INSERT INTO `users` (`id`, `user_id`, `full_name`, `email`, `password_hash`, `role`, `status`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
('1', '22120091', 'System Administrator', 'admin@gmail.com', 'PLACEHOLDER_HASH', 'admin', 'approved', '1', NULL, NULL, NULL, '2025-08-11 03:45:54', '2025-08-11 03:45:54'),
('3', 'TEA250812457', 'Nomer Tandoc', '2222@gmail.com', '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', '', '1', NULL, NULL, NULL, '2025-08-12 12:07:01', '2025-09-10 09:52:13'),
('4', 'TEA250812616', 'Mary Antonette Nievera', 'elmerebilaneq960@gmail.com', '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-12 12:10:06', '2025-09-08 10:20:54'),
('33', 'TEA250908142', 'Jannie Escobar', NULL, '$2y$10$Oo38JF7U/w.5rqGc/EOZCO7ajzmPU1rBwq1fwPnULiETzOgBM/xO.', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-09-08 10:58:28', '2025-09-08 10:59:07'),
('12', 'TEA250822173', 'Analyn Edañol', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:27:29', '2025-09-08 11:18:48'),
('11', 'TEA250822585', 'Jannie Escobar', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:15:18', '2025-09-08 10:20:54'),
('9', 'TEA250818331', 'Joseph Jay Barrera', 'jaypark@gmail.com', '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-18 10:41:59', '2025-09-08 10:20:54'),
('13', 'TEA250822252', 'John April Marpa', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:28:21', '2025-09-08 10:20:54'),
('14', 'TEA250822306', 'Jing Jing Gonggora', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:30:17', '2025-09-08 10:20:54'),
('15', 'TEA250822326', 'Ana Mae Misa', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:31:25', '2025-09-08 10:20:54'),
('16', 'TEA250822016', 'Hazel Joy Montehermoso', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'pending', '1', NULL, NULL, NULL, '2025-08-22 08:36:47', '2025-09-08 10:20:54'),
('17', 'TEA250822051', 'Ele Mae Linga -Morana', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-08-22 08:38:11', '2025-09-11 21:38:47'),
('32', 'STU250908906', 'Elmer', NULL, '$2y$10$R2ciKCBaJelBMmJQIIWs4eZiDArt7ACrvlLx80vyddEthjI8KASrG', 'student', 'approved', '1', NULL, NULL, NULL, '2025-09-08 10:15:28', '2025-09-08 11:16:34'),
('21', 'TEA250903546', 'Anthony Mon', NULL, '$2y$10$nZ2mX.bIT.Nk7KVJY8s8XOXQPJBetXJG7YyFJsttcnQlE6Si7n1KK', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-09-03 09:17:50', '2025-09-08 10:20:54'),
('23', 'STU250908925', 'dfghdfhsf', NULL, '$2y$10$9OlU3PUvkeXt8eZizH6px.EfhOIA2lU1su2dwIP2wUcxp2l5uFe.y', 'student', 'pending', '1', NULL, NULL, NULL, '2025-09-08 08:00:23', '2025-09-08 08:00:23'),
('24', 'STU250908780', 'fsdgdfagdg', NULL, '$2y$10$hf6Kw9cJzj/IM8.1wtdHXesKURfXX3yOJUAoYYfoeF1fIAQcgvZOq', 'student', 'pending', '1', NULL, NULL, NULL, '2025-09-08 08:10:47', '2025-09-08 08:10:47'),
('25', 'STU250908674', 'fdhbfghfg', NULL, '$2y$10$nt1p5sCdQ3AdLM0UgC81c.lgva2.4gi2xJC.qKjuZZtG/aiLHA6lO', 'student', 'pending', '1', NULL, NULL, NULL, '2025-09-08 08:13:45', '2025-09-08 08:13:45'),
('26', 'STU250908816', 'gvdfsghdfhb', NULL, '$2y$10$PqED4/IykNpniFqtkqRLEOz5xZWAQDNpeGuhEfKgXBgd0Q6fvKB7u', 'student', 'pending', '1', NULL, NULL, NULL, '2025-09-08 08:23:59', '2025-09-08 08:23:59'),
('27', 'STU250908317', 'dsgdfgdfg', NULL, '$2y$10$jy7wpt7OStG0yJpTuN4Ch.izZ46ejaAqFM9LByyrVYkJmyCOInCAq', 'student', 'approved', '1', NULL, NULL, NULL, '2025-09-08 08:30:32', '2025-09-08 11:14:14'),
('28', 'STU250908024', 'fbgdfhgdfh', NULL, '$2y$10$Ac7kuNOMaVEgWSJEzCffiem3sLmw0MPGRyeWm5cKxs2Wo7uWYNW1q', 'student', 'approved', '1', NULL, NULL, NULL, '2025-09-08 08:32:56', '2025-09-08 09:28:57'),
('29', 'STU250908251', 'grdshgdfhr', NULL, '$2y$10$PS2FPORb6KC.i2nusyWlAeWhKEidz5g1YFO6BNd/tWaCD2r7PG9YS', 'student', 'approved', '1', NULL, NULL, NULL, '2025-09-08 08:33:28', '2025-09-08 09:27:02'),
('30', 'STU250908092', 'dsgdfgd', NULL, '$2y$10$1OtTrHryODpplHd7rRFXe.z696EZhbc6ISbqFeS8b7o.5v3/FaSni', 'student', 'rejected', '1', NULL, NULL, NULL, '2025-09-08 08:36:54', '2025-09-08 11:15:49'),
('34', 'TBA001', 'To Be Announced', 'tba@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'approved', '1', NULL, NULL, NULL, '2025-09-11 21:38:09', '2025-09-11 21:38:09');

