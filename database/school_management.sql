-- School Time Management System - Database Schema
-- Import in phpMyAdmin or run: mysql -u root -p < school_management.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

CREATE DATABASE IF NOT EXISTS `school_management`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `school_management`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','teacher','coordinator') NOT NULL DEFAULT 'teacher',
  `teacher_id` INT(11)      DEFAULT NULL,
  `last_login` DATETIME     DEFAULT NULL,
  `status`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: teachers
-- --------------------------------------------------------
CREATE TABLE `teachers` (
  `id`                     INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`                INT(11)      DEFAULT NULL,
  `name`                   VARCHAR(100) NOT NULL,
  `email`                  VARCHAR(100) DEFAULT NULL,
  `phone`                  VARCHAR(20)  DEFAULT NULL,
  `employee_id`            VARCHAR(50)  DEFAULT NULL,
  `subject_specialization` VARCHAR(100) DEFAULT NULL,
  `qualification`          VARCHAR(100) DEFAULT NULL,
  `joining_date`           DATE         DEFAULT NULL,
  `status`                 ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: classes
-- --------------------------------------------------------
CREATE TABLE `classes` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name` (`class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: sections
-- --------------------------------------------------------
CREATE TABLE `sections` (
  `id`           INT(11)     NOT NULL AUTO_INCREMENT,
  `class_id`     INT(11)     NOT NULL,
  `section_name` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: subjects
-- --------------------------------------------------------
CREATE TABLE `subjects` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `subject_name` VARCHAR(100) NOT NULL,
  `subject_code` VARCHAR(20)  DEFAULT NULL,
  `class_id`     INT(11)      DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: rooms
-- --------------------------------------------------------
CREATE TABLE `rooms` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `room_number` VARCHAR(20) NOT NULL,
  `capacity`    INT(11)     DEFAULT 40,
  `floor`       VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: timetable
-- --------------------------------------------------------
CREATE TABLE `timetable` (
  `id`            INT(11)  NOT NULL AUTO_INCREMENT,
  `class_id`      INT(11)  NOT NULL,
  `section_id`    INT(11)  DEFAULT NULL,
  `subject_id`    INT(11)  NOT NULL,
  `teacher_id`    INT(11)  NOT NULL,
  `room_id`       INT(11)  DEFAULT NULL,
  `day`           ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_number` INT(11)  NOT NULL,
  `start_time`    TIME     NOT NULL,
  `end_time`      TIME     NOT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_period`   (`class_id`,`section_id`,`day`,`period_number`),
  UNIQUE KEY `unique_teacher_period` (`teacher_id`,`day`,`period_number`),
  KEY `section_id`  (`section_id`),
  KEY `subject_id`  (`subject_id`),
  KEY `room_id`     (`room_id`),
  CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`)   REFERENCES `classes`  (`id`),
  CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `timetable_ibfk_5` FOREIGN KEY (`room_id`)    REFERENCES `rooms`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: teacher_availability
-- --------------------------------------------------------
CREATE TABLE `teacher_availability` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id`    INT(11) NOT NULL,
  `date`          DATE    NOT NULL,
  `period_number` INT(11) DEFAULT NULL,
  `status`        ENUM('available','unavailable','on_leave') NOT NULL DEFAULT 'available',
  `reason`        TEXT    DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_date_period` (`teacher_id`,`date`,`period_number`),
  CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: substitutions
-- --------------------------------------------------------
CREATE TABLE `substitutions` (
  `id`                      INT(11) NOT NULL AUTO_INCREMENT,
  `original_teacher_id`     INT(11) NOT NULL,
  `substitute_teacher_id`   INT(11) NOT NULL,
  `timetable_id`            INT(11) NOT NULL,
  `date`                    DATE    NOT NULL,
  `status`                  ENUM('pending','assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_by`             INT(11) DEFAULT NULL,
  `notes`                   TEXT    DEFAULT NULL,
  `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `original_teacher_id`   (`original_teacher_id`),
  KEY `substitute_teacher_id` (`substitute_teacher_id`),
  KEY `timetable_id`          (`timetable_id`),
  CONSTRAINT `substitutions_ibfk_1` FOREIGN KEY (`original_teacher_id`)   REFERENCES `teachers`  (`id`),
  CONSTRAINT `substitutions_ibfk_2` FOREIGN KEY (`substitute_teacher_id`) REFERENCES `teachers`  (`id`),
  CONSTRAINT `substitutions_ibfk_3` FOREIGN KEY (`timetable_id`)          REFERENCES `timetable` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: attendance
-- --------------------------------------------------------
CREATE TABLE `attendance` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(11) NOT NULL,
  `date`       DATE    NOT NULL,
  `status`     ENUM('present','absent','late','on_leave') NOT NULL DEFAULT 'present',
  `check_in`   TIME    DEFAULT NULL,
  `notes`      TEXT    DEFAULT NULL,
  `marked_by`  INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_date` (`teacher_id`,`date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: leaves
-- --------------------------------------------------------
CREATE TABLE `leaves` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `teacher_id`    INT(11) NOT NULL,
  `leave_type`    ENUM('sick','casual','earned','other') NOT NULL DEFAULT 'casual',
  `from_date`     DATE    NOT NULL,
  `to_date`       DATE    NOT NULL,
  `reason`        TEXT    NOT NULL,
  `status`        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `applied_on`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by`   INT(11)   DEFAULT NULL,
  `approved_on`   DATETIME  DEFAULT NULL,
  `admin_remarks` TEXT      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `type`       ENUM('info','warning','success','danger') NOT NULL DEFAULT 'info',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `action`     VARCHAR(200) NOT NULL,
  `details`    TEXT         DEFAULT NULL,
  `ip_address` VARCHAR(50)  DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user  (login: admin / admin123)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Teachers
INSERT INTO `teachers` (`name`, `email`, `phone`, `employee_id`, `subject_specialization`, `qualification`, `joining_date`) VALUES
('Rajesh Kumar',  'rajesh@school.edu',  '9876543210', 'EMP001', 'Mathematics',   'M.Sc Mathematics', '2020-06-01'),
('Priya Sharma',  'priya@school.edu',   '9876543211', 'EMP002', 'English',        'M.A English',      '2019-07-01'),
('Amit Singh',    'amit@school.edu',    '9876543212', 'EMP003', 'Science',        'M.Sc Physics',     '2021-01-10'),
('Sunita Devi',   'sunita@school.edu',  '9876543213', 'EMP004', 'Hindi',          'M.A Hindi',        '2018-08-01'),
('Vikram Patel',  'vikram@school.edu',  '9876543214', 'EMP005', 'Social Science', 'M.A History',      '2022-03-15'),
('Neha Gupta',    'neha@school.edu',    '9876543215', 'EMP006', 'Computer Science','B.Tech CS',       '2023-01-05');

-- Teacher login users  (password: teacher123)
INSERT INTO `users` (`username`, `password`, `role`, `teacher_id`) VALUES
('rajesh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1),
('priya',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 2),
('amit',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 3),
('sunita', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 4),
('vikram', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 5);

UPDATE `teachers` SET `user_id` = 2 WHERE `id` = 1;
UPDATE `teachers` SET `user_id` = 3 WHERE `id` = 2;
UPDATE `teachers` SET `user_id` = 4 WHERE `id` = 3;
UPDATE `teachers` SET `user_id` = 5 WHERE `id` = 4;
UPDATE `teachers` SET `user_id` = 6 WHERE `id` = 5;

-- Classes
INSERT INTO `classes` (`class_name`) VALUES
('Class 6'),('Class 7'),('Class 8'),('Class 9'),('Class 10');

-- Sections
INSERT INTO `sections` (`class_id`, `section_name`) VALUES
(1,'A'),(1,'B'),(2,'A'),(2,'B'),(3,'A'),(3,'B'),(4,'A'),(4,'B'),(5,'A'),(5,'B');

-- Subjects
INSERT INTO `subjects` (`subject_name`, `subject_code`) VALUES
('Mathematics',    'MATH'),
('English',        'ENG'),
('Science',        'SCI'),
('Hindi',          'HIN'),
('Social Science', 'SST'),
('Computer Science','CS'),
('Physical Education','PE');

-- Rooms
INSERT INTO `rooms` (`room_number`, `capacity`, `floor`) VALUES
('101',40,'Ground'),('102',40,'Ground'),
('201',40,'First'), ('202',40,'First'),
('Lab-1',30,'Ground'),('Hall',200,'Ground');

-- Sample timetable entries
INSERT INTO `timetable` (`class_id`,`section_id`,`subject_id`,`teacher_id`,`room_id`,`day`,`period_number`,`start_time`,`end_time`) VALUES
(1,1,1,1,1,'Monday',   1,'08:00:00','08:45:00'),
(1,1,2,2,1,'Monday',   2,'08:45:00','09:30:00'),
(1,1,3,3,1,'Monday',   3,'09:30:00','10:15:00'),
(1,1,4,4,1,'Monday',   4,'10:30:00','11:15:00'),
(1,1,5,5,1,'Monday',   5,'11:15:00','12:00:00'),
(1,1,1,1,1,'Tuesday',  1,'08:00:00','08:45:00'),
(1,1,3,3,1,'Tuesday',  2,'08:45:00','09:30:00'),
(1,1,2,2,1,'Tuesday',  3,'09:30:00','10:15:00'),
(2,3,1,1,2,'Monday',   1,'08:00:00','08:45:00'),
(2,3,2,2,2,'Monday',   2,'08:45:00','09:30:00'),
(2,3,3,3,2,'Wednesday',1,'08:00:00','08:45:00'),
(3,5,1,1,3,'Thursday', 1,'08:00:00','08:45:00'),
(3,5,4,4,3,'Thursday', 2,'08:45:00','09:30:00'),
(4,7,5,5,4,'Friday',   1,'08:00:00','08:45:00'),
(5,9,1,1,5,'Saturday', 1,'08:00:00','08:45:00');

COMMIT;
