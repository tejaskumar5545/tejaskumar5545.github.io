-- ClassroomX Seed Data
-- Run AFTER schema.sql to insert default admin and sample data.
--
-- Usage:
--   mysql -u root -p your_database_name < database/seed.sql
--
-- NOTE: Default admin password is "admin123" — CHANGE IT after first login!
-- To generate a new hash, run in PHP:
--   echo password_hash('your_new_password', PASSWORD_DEFAULT);

USE `college_notes`;

-- Default admin (username: admin, password: admin123)
INSERT INTO `admin` (`username`, `password`)
SELECT 'admin', '$2y$10$YourHashedPasswordHere'
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username = 'admin');

-- Sample notices
INSERT INTO `notices` (`title`, `content`, `category`, `is_important`)
SELECT 'Welcome to the Study Portal!', 'All notes, PYQ papers, assignments, online tests and notices are now available in one place. Register as a student to get started.', 'General', 1
WHERE NOT EXISTS (SELECT 1 FROM notices WHERE title = 'Welcome to the Study Portal!');

INSERT INTO `notices` (`title`, `content`, `category`, `is_important`)
SELECT 'Online Test Guidelines', 'Attempt online tests only after logging in as a student. Results are shown instantly after submission.', 'Exam', 0
WHERE NOT EXISTS (SELECT 1 FROM notices WHERE title = 'Online Test Guidelines');

-- Sample syllabus entry
INSERT INTO `syllabus` (`title`, `subject`, `semester`, `branch`, `pdf_file`)
SELECT 'Computer Engineering Syllabus', 'All Subjects', 'Semester 1', 'Computer Engineering', 'syllabus_computer_1.pdf'
WHERE NOT EXISTS (SELECT 1 FROM syllabus WHERE title = 'Computer Engineering Syllabus');

-- Sample practical entry
INSERT INTO `practicals` (`title`, `subject`, `semester`, `branch`, `pdf_file`)
SELECT 'Physics Practical List', 'Applied Physics', 'Semester 1', 'Computer Engineering', 'practical_physics_1.pdf'
WHERE NOT EXISTS (SELECT 1 FROM practicals WHERE title = 'Physics Practical List');

-- Sample coding problem
INSERT INTO `coding` (`title`, `description`, `language`, `difficulty`, `code`)
SELECT 'Hello World in C', 'Basic C program to print Hello World', 'C', 'Easy', '#include <stdio.h>\nint main() {\n    printf("Hello World");\n    return 0;\n}'
WHERE NOT EXISTS (SELECT 1 FROM coding WHERE title = 'Hello World in C');

-- Sample project
INSERT INTO `projects` (`title`, `description`, `semester`, `branch`, `pdf_file`)
SELECT 'Student Study Portal', 'A complete web portal for students', 'Semester 5', 'Computer Engineering', 'project_study_portal.pdf'
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE title = 'Student Study Portal');

-- Sample placement
INSERT INTO `placement` (`title`, `company`, `description`, `role`, `package`, `link`)
SELECT 'TCS Campus Drive', 'TCS', 'Campus placement drive for final year students. Apply before the deadline.', 'Software Engineer', '7 LPA', 'https://www.tcs.com'
WHERE NOT EXISTS (SELECT 1 FROM placement WHERE title = 'TCS Campus Drive');
