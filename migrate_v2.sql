-- Run this INSTEAD of schema.sql if you already have an existing academy_portal
-- database from before (with your students/attendance/fees data already in it).
-- This safely adds the new Skill Classes + Login + Enquiries features
-- without deleting anything you already have.
--
-- How to run: open phpMyAdmin -> select the academy_portal database -> SQL tab
-- -> paste this whole file -> Go.

USE academy_portal;

ALTER TABLE students
  ADD COLUMN IF NOT EXISTS program_type ENUM('academic','skill') NOT NULL DEFAULT 'academic' AFTER phone,
  ADD COLUMN IF NOT EXISTS stream VARCHAR(50) DEFAULT '' AFTER class,
  ADD COLUMN IF NOT EXISTS batch VARCHAR(100) DEFAULT '' AFTER stream,
  ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT NULL AFTER batch,
  ADD COLUMN IF NOT EXISTS end_date DATE DEFAULT NULL AFTER start_date,
  ADD COLUMN IF NOT EXISTS course_status ENUM('ongoing','completed') DEFAULT 'ongoing' AFTER end_date,
  ADD COLUMN IF NOT EXISTS fee_type ENUM('monthly','one_time') NOT NULL DEFAULT 'monthly' AFTER course_status;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT '',
  interest VARCHAR(150) DEFAULT '',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
