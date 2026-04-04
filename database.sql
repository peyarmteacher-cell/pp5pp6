-- คำสั่งสร้างตารางข้อมูลสำหรับระบบบริหารจัดการสถานศึกษา
-- MySQL Database Schema

-- ลบตารางเก่าออกก่อนเพื่อป้องกันความสับสน (ระวัง: ข้อมูลเก่าจะหายหมด)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS schools;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. ตารางโรงเรียน (Schools)
CREATE TABLE schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    province VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางผู้ใช้งาน (Users/Teachers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL,
    school_id INT,
    national_id VARCHAR(13) UNIQUE,
    position VARCHAR(100),
    is_approved BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตารางนักเรียน (Students)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL,
    school_id INT,
    status ENUM('active', 'graduated', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตารางคะแนน (Grades/Records)
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    k_score INT DEFAULT 0,
    p_score INT DEFAULT 0,
    a_score INT DEFAULT 0,
    midterm_score INT DEFAULT 0,
    final_score INT DEFAULT 0,
    academic_year INT NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ข้อมูลตัวอย่าง (Seed Data)
-- ใส่ข้อมูลโรงเรียนก่อน
INSERT INTO schools (id, code, name, province) VALUES (1, '10203040', 'โรงเรียนบ้านเลยเลย', 'เลย');
INSERT INTO schools (id, code, name, province) VALUES (2, '50607080', 'โรงเรียนบ้านหนองบัว', 'เลย');

-- ใส่ข้อมูลผู้ใช้
INSERT INTO users (username, password, name, role, school_id, is_approved, is_first_login) 
VALUES ('admin', '123456', 'Super Administrator', 'super_admin', NULL, TRUE, FALSE);

-- ใส่ข้อมูลนักเรียน (อ้างอิง school_id = 1)
INSERT INTO students (student_code, name, level, school_id) 
VALUES ('64001', 'เด็กชายจำลอง 1 นามจำลอง 1', 'ป.1', 1);
INSERT INTO students (student_code, name, level, school_id) 
VALUES ('64002', 'เด็กหญิงสมใจ 2 นามสมใจ 2', 'ป.1', 1);
