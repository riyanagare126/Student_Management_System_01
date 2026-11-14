-- =======================================================
-- Student Management System Database Schema & Sample Data
-- Target Database: MySQL / MariaDB (phpMyAdmin compatible)
-- =======================================================

CREATE DATABASE IF NOT EXISTS `student_management_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `student_management_db`;

-- -------------------------------------------------------
-- 1. Table: users
-- Roles: admin, teacher, student
-- -------------------------------------------------------
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 2. Table: classes (or courses)
-- -------------------------------------------------------
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_name` VARCHAR(100) NOT NULL,
  `section` VARCHAR(20) DEFAULT 'A',
  `room_no` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 3. Table: students
-- -------------------------------------------------------
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL UNIQUE,
  `roll_no` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `class_id` INT NOT NULL,
  `dob` DATE DEFAULT NULL,
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT 'default-avatar.svg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 4. Table: teachers
-- -------------------------------------------------------
CREATE TABLE `teachers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 5. Table: attendance
-- Status: Present, Absent, Late, Excused
-- -------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_date` (`student_id`, `date`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 6. Table: marks
-- -------------------------------------------------------
CREATE TABLE `marks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `marks_obtained` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `exam_type` VARCHAR(50) NOT NULL DEFAULT 'Final Exam',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 7. Table: fees
-- -------------------------------------------------------
CREATE TABLE `fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `title` VARCHAR(100) NOT NULL DEFAULT 'Tuition Fee',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Paid', 'Unpaid', 'Pending') NOT NULL DEFAULT 'Pending',
  `payment_date` DATE DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `receipt_no` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =======================================================
-- SAMPLE DATA INSERTION
-- Initial Default Passwords:
-- admin    / admin123
-- teacher1 / teacher123
-- student1 / student123
-- student2 / student123
-- student3 / student123
-- (Upon first login, these are automatically converted into secure bcrypt hashes)
-- =======================================================

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'admin123', 'admin'),
(2, 'teacher1', 'teacher123', 'teacher'),
(3, 'student1', 'student123', 'student'),
(4, 'student2', 'student123', 'student'),
(5, 'student3', 'student123', 'student');

INSERT INTO `classes` (`id`, `class_name`, `section`, `room_no`) VALUES
(1, 'Grade 10 - Science', 'A', 'Lab-101'),
(2, 'Grade 10 - Commerce', 'B', 'Room-102'),
(3, 'Grade 11 - Computer Science', 'A', 'CS-Lab-1'),
(4, 'Grade 12 - Advanced Mathematics', 'A', 'Room-204');

INSERT INTO `teachers` (`id`, `user_id`, `name`, `email`, `phone`, `subject`) VALUES
(1, 2, 'Prof. Sarah Jenkins', 'sarah.jenkins@school.edu', '+1 (555) 234-5678', 'Computer Science & Mathematics');

INSERT INTO `students` (`id`, `user_id`, `roll_no`, `name`, `class_id`, `dob`, `gender`, `email`, `phone`, `address`, `photo`) VALUES
(1, 3, 'STD-2024-001', 'Alexander Hayes', 1, '2008-04-15', 'Male', 'alexander.h@student.edu', '+1 (555) 345-6789', '742 Evergreen Terrace, Springfield', 'default-avatar.svg'),
(2, 4, 'STD-2024-002', 'Sophia Ramirez', 1, '2008-09-22', 'Female', 'sophia.r@student.edu', '+1 (555) 456-7890', '124 Conch Street, Pacific Grove', 'default-avatar.svg'),
(3, 5, 'STD-2024-003', 'Ethan Miller', 3, '2007-11-05', 'Male', 'ethan.m@student.edu', '+1 (555) 567-8901', '42 Wallaby Way, Sydney Heights', 'default-avatar.svg'),
(4, NULL, 'STD-2024-004', 'Emma Watson', 2, '2008-02-18', 'Female', 'emma.w@student.edu', '+1 (555) 678-9012', '10 Downing Crescent, Kingsland', 'default-avatar.svg'),
(5, NULL, 'STD-2024-005', 'Liam Johnson', 3, '2007-06-30', 'Male', 'liam.j@student.edu', '+1 (555) 789-0123', '221B Baker Street, Metro City', 'default-avatar.svg');

-- Sample Attendance
INSERT INTO `attendance` (`student_id`, `date`, `status`, `remarks`) VALUES
(1, CURDATE(), 'Present', 'On time'),
(2, CURDATE(), 'Present', 'Active participant'),
(3, CURDATE(), 'Late', 'Arrived 15 mins late'),
(4, CURDATE(), 'Absent', 'Sick leave request pending'),
(5, CURDATE(), 'Present', 'Regular attendance'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 'Normal'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 'Normal'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 'Normal'),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Excused', 'Doctor appointment'),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 'Normal');

-- Sample Marks
INSERT INTO `marks` (`student_id`, `subject`, `marks_obtained`, `total_marks`, `exam_type`) VALUES
(1, 'Mathematics', 92.50, 100.00, 'Midterm Examination'),
(1, 'Physics', 88.00, 100.00, 'Midterm Examination'),
(1, 'Chemistry', 84.50, 100.00, 'Midterm Examination'),
(1, 'English Literature', 90.00, 100.00, 'Midterm Examination'),
(1, 'Computer Science', 96.00, 100.00, 'Midterm Examination'),
(2, 'Mathematics', 78.00, 100.00, 'Midterm Examination'),
(2, 'Physics', 82.00, 100.00, 'Midterm Examination'),
(2, 'English Literature', 89.50, 100.00, 'Midterm Examination'),
(3, 'Computer Science', 95.00, 100.00, 'Midterm Examination'),
(3, 'Calculus', 89.00, 100.00, 'Midterm Examination'),
(3, 'Data Structures', 92.00, 100.00, 'Midterm Examination');

-- Sample Fees
INSERT INTO `fees` (`id`, `student_id`, `title`, `amount`, `status`, `payment_date`, `due_date`, `receipt_no`) VALUES
(1, 1, 'Fall Semester Tuition 2024', 1200.00, 'Paid', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'REC-2024-1001'),
(2, 1, 'Science Lab & Equipment Fee', 150.00, 'Paid', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'REC-2024-1002'),
(3, 2, 'Fall Semester Tuition 2024', 1200.00, 'Unpaid', NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL),
(4, 3, 'CS Laboratory & Software License', 250.00, 'Paid', DATE_SUB(CURDATE(), INTERVAL 5 DAY), CURDATE(), 'REC-2024-1003'),
(5, 4, 'Annual Library & Activity Fee', 100.00, 'Pending', NULL, DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL),
(6, 5, 'Fall Semester Tuition 2024', 1200.00, 'Unpaid', NULL, DATE_ADD(CURDATE(), INTERVAL 14 DAY), NULL);
