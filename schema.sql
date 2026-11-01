-- Fellowship Academy Portal — Database Schema (v2)
-- Run this once in phpMyAdmin (or `mysql -u root -p < schema.sql`) to create everything.
-- If you already have the OLD version of this database installed, use migrate_v2.sql instead
-- so you don't lose existing student/attendance/fee data.

CREATE DATABASE IF NOT EXISTS academy_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academy_portal;

-- Students table. `roll` auto-increments starting from 1001.
-- Handles BOTH regular academic classes (Nursery..Class 12) AND skill classes
-- (AI & Computer Skills, Web & App Development, etc.) via `program_type`.
CREATE TABLE IF NOT EXISTS students (
  id VARCHAR(50) NOT NULL PRIMARY KEY,
  roll INT NOT NULL AUTO_INCREMENT UNIQUE,
  name VARCHAR(255) NOT NULL,
  father VARCHAR(255) DEFAULT '',
  phone VARCHAR(50) DEFAULT '',
  program_type ENUM('academic','skill') NOT NULL DEFAULT 'academic',
  class VARCHAR(100) NOT NULL,              -- academic class name OR skill course name
  stream VARCHAR(50) DEFAULT '',            -- only used for Class 11 / Class 12
  batch VARCHAR(100) DEFAULT '',            -- only used for skill courses, e.g. "Batch 4 - Evening"
  start_date DATE DEFAULT NULL,             -- skill course start date
  end_date DATE DEFAULT NULL,               -- skill course expected end date
  course_status ENUM('ongoing','completed') DEFAULT 'ongoing',
  fee_type ENUM('monthly','one_time') NOT NULL DEFAULT 'monthly',
  monthly_fee DECIMAL(10,2) DEFAULT 0,      -- monthly fee OR one-time course fee, depending on fee_type
  admission_date DATE NOT NULL
) ENGINE=InnoDB;

ALTER TABLE students AUTO_INCREMENT = 1001;

-- Attendance table. One row per student per day.
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  att_date DATE NOT NULL,
  status ENUM('present','absent') NOT NULL,
  UNIQUE KEY unique_student_date (student_id, att_date),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fees table.
CREATE TABLE IF NOT EXISTS fees (
  id VARCHAR(50) NOT NULL PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  month VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) DEFAULT 0,
  status ENUM('paid','due') DEFAULT 'due',
  fee_date DATE NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin login accounts. A default admin (username: admin / password: admin123)
-- is created automatically the first time the portal runs — see api/db.php.
-- IMPORTANT: change this password immediately after your first login.
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Enquiries submitted from the public "Contact / Admissions" form on the homepage.
CREATE TABLE IF NOT EXISTS enquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT '',
  interest VARCHAR(150) DEFAULT '',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Absence fines (Rs 30 per absent day from 1 Nov 2026). The fine amount itself is
-- calculated from `attendance`; this table only remembers paid/unpaid per student per month.
-- (api/fines.php also creates it automatically if missing.)
CREATE TABLE IF NOT EXISTS fines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL,
  month CHAR(7) NOT NULL,              -- e.g. 2026-11
  paid TINYINT(1) NOT NULL DEFAULT 0,
  paid_date DATE DEFAULT NULL,
  UNIQUE KEY unique_student_month (student_id, month),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;
