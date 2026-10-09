-- MySQL Database Export for Training Enrollment System
-- Database: training_db
-- IPT Laboratory Activity: A Multi-Table PHP PDO Application

CREATE DATABASE IF NOT EXISTS `training_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `training_db`;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `courses`
-- --------------------------------------------------------
CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `classes`
-- --------------------------------------------------------
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `slots` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`class_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `fk_classes_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `enrollments`
-- --------------------------------------------------------
CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`enrollment_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Initial Data for `courses`
-- --------------------------------------------------------
INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `description`) VALUES
(1, 'CS101', 'Web Development Fundamentals', 'Core introduction to HTML5, CSS3, responsive web layouts, and interactive UI design.'),
(2, 'CS102', 'PHP & MySQL Backend Engineering', 'Server-side application architecture, PDO wrappers, prepared statements, and transactional integrity.'),
(3, 'CS103', 'Database Architecture & Security', 'Relational database normalization, indexing, query optimization, and enterprise transactions.');

-- --------------------------------------------------------
-- Initial Data for `classes`
-- --------------------------------------------------------
INSERT INTO `classes` (`class_id`, `course_id`, `class_code`, `schedule`, `instructor`, `slots`) VALUES
(1, 1, 'WD-A1', 'Mon/Wed 09:00 AM - 11:00 AM', 'Prof. Alan Turing', 15),
(2, 1, 'WD-A2', 'Tue/Thu 01:00 PM - 03:00 PM', 'Prof. Ada Lovelace', 0),
(3, 2, 'PHP-B1', 'Mon/Wed 01:00 PM - 03:00 PM', 'Dr. Linus Torvalds', 12),
(4, 3, 'DB-C1', 'Fri 08:00 AM - 12:00 PM', 'Prof. Grace Hopper', 10);
