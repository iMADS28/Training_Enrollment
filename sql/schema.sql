-- sql/schema.sql
-- Training Enrollment System Database Schema and Sample Data
-- Database: training_db

CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

-- 1. Students Table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Courses Table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Classes Table
CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule VARCHAR(100),
    instructor VARCHAR(100),
    slots INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Enrollments Table
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data (Safe Insertion)
INSERT INTO courses (course_code, course_name, description) VALUES
('CS101', 'Web Development Fundamentals', 'Core introduction to HTML5, CSS3, responsive web layouts, and interactive UI design.'),
('CS102', 'PHP & MySQL Backend Engineering', 'Server-side application architecture, PDO wrappers, prepared statements, and transactional integrity.'),
('CS103', 'Database Architecture & Security', 'Relational database normalization, indexing, query optimization, and enterprise transactions.')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Classes Sample Data (Note: WD-A2 is created with 0 slots to demonstrate rollback)
INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
(1, 'WD-A1', 'Mon/Wed 09:00 AM - 11:00 AM', 'Prof. Alan Turing', 15),
(1, 'WD-A2', 'Tue/Thu 01:00 PM - 03:00 PM', 'Prof. Ada Lovelace', 0),
(2, 'PHP-B1', 'Mon/Wed 01:00 PM - 03:00 PM', 'Dr. Linus Torvalds', 12),
(3, 'DB-C1', 'Fri 08:00 AM - 12:00 PM', 'Prof. Grace Hopper', 10);
