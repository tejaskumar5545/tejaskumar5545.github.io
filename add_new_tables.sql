-- Run this on your existing college_notes database to add the new tables
-- (USE phpMyAdmin or: mysql -uroot college_notes < add_new_tables.sql)

CREATE TABLE IF NOT EXISTS `pyq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `semester` varchar(20) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `year` varchar(20) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `semester` varchar(20) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `deadline` date DEFAULT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General',
  `is_important` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'General',
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `syllabus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `semester` varchar(20) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `practicals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `semester` varchar(20) NOT NULL,
  `branch` varchar(50) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `coding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT 'Easy',
  `code` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `placement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `package` varchar(100) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data (idempotent - safe to run multiple times)
INSERT INTO `notices` (`title`, `content`, `category`, `is_important`)
SELECT 'Welcome to the Study Portal!', 'All notes, PYQ papers, assignments, online tests and notices are now available in one place. Register as a student to get started.', 'General', 1
WHERE NOT EXISTS (SELECT 1 FROM notices WHERE title = 'Welcome to the Study Portal!');

INSERT INTO `notices` (`title`, `content`, `category`, `is_important`)
SELECT 'Online Test Guidelines', 'Attempt online tests only after logging in as a student. Results are shown instantly after submission.', 'Exam', 0
WHERE NOT EXISTS (SELECT 1 FROM notices WHERE title = 'Online Test Guidelines');

INSERT INTO `syllabus` (`title`, `subject`, `semester`, `branch`, `pdf_file`)
SELECT 'Computer Engineering Syllabus', 'All Subjects', 'Semester 1', 'Computer Engineering', 'syllabus_computer_1.pdf'
WHERE NOT EXISTS (SELECT 1 FROM syllabus WHERE title = 'Computer Engineering Syllabus');

INSERT INTO `practicals` (`title`, `subject`, `semester`, `branch`, `pdf_file`)
SELECT 'Physics Practical List', 'Applied Physics', 'Semester 1', 'Computer Engineering', 'practical_physics_1.pdf'
WHERE NOT EXISTS (SELECT 1 FROM practicals WHERE title = 'Physics Practical List');

INSERT INTO `coding` (`title`, `description`, `language`, `difficulty`, `code`)
SELECT 'Hello World in C', 'Basic C program to print Hello World', 'C', 'Easy', '#include <stdio.h>\nint main() {\n    printf("Hello World");\n    return 0;\n}'
WHERE NOT EXISTS (SELECT 1 FROM coding WHERE title = 'Hello World in C');

INSERT INTO `projects` (`title`, `description`, `semester`, `branch`, `pdf_file`)
SELECT 'Student Study Portal', 'A complete web portal for students', 'Semester 5', 'Computer Engineering', 'project_study_portal.pdf'
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE title = 'Student Study Portal');

INSERT INTO `placement` (`title`, `company`, `description`, `role`, `package`, `link`)
SELECT 'TCS Campus Drive', 'TCS', 'Campus placement drive for final year students. Apply before the deadline.', 'Software Engineer', '7 LPA', 'https://www.tcs.com'
WHERE NOT EXISTS (SELECT 1 FROM placement WHERE title = 'TCS Campus Drive');
