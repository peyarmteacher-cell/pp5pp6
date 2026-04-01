-- คำสั่งสร้างฐานข้อมูลสำหรับระบบบริหารจัดการสถานศึกษา
-- MySQL Database Schema

CREATE DATABASE IF NOT EXISTS school_db;
USE school_db;

-- 1. ตารางโรงเรียน (Schools)
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    province VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ตารางผู้ใช้งาน (Users/Teachers)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL,
    school_id INT,
    national_id VARCHAR(13),
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
);

-- 3. ตารางนักเรียน (Students)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL,
    school_id INT,
    status ENUM('active', 'graduated', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

-- 4. ตารางคะแนน (Grades/Records)
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    k_score INT DEFAULT 0, -- คะแนนเก็บ
    p_score INT DEFAULT 0, -- คะแนนปฏิบัติ
    a_score INT DEFAULT 0, -- คะแนนคุณลักษณะ
    midterm_score INT DEFAULT 0,
    final_score INT DEFAULT 0,
    academic_year INT NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ข้อมูลตัวอย่าง (Seed Data)
INSERT INTO schools (code, name, province) VALUES ('10203040', 'โรงเรียนบ้านเลยเลย', 'เลย');
INSERT INTO schools (code, name, province) VALUES ('50607080', 'โรงเรียนบ้านหนองบัว', 'เลย');

INSERT INTO users (username, password, name, role, school_id) 
VALUES ('admin', '123456', 'Super Administrator', 'super_admin', NULL);

INSERT INTO students (student_code, name, level, school_id) 
VALUES ('64001', 'เด็กชายจำลอง 1 นามจำลอง 1', 'ป.1', 1);
INSERT INTO students (student_code, name, level, school_id) 
VALUES ('64002', 'เด็กหญิงสมใจ 2 นามสมใจ 2', 'ป.1', 1);
