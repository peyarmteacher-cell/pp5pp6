-- ตารางโรงเรียน
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) UNIQUE NOT NULL, -- รหัส 8 หลัก
    name VARCHAR(255) NOT NULL,
    province VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางผู้ใช้งาน (เพิ่มฟิลด์งานวิชาการ)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(13) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher') DEFAULT 'teacher',
    is_academic BOOLEAN DEFAULT FALSE, -- งานวิชาการ
    school_id INT,
    position VARCHAR(100),
    affiliation VARCHAR(255),
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางนักเรียน (เพิ่มเลขบัตรประชาชน)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL,
    national_id VARCHAR(13) NOT NULL, -- เลขบัตรประชาชนนักเรียน
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL,
    school_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางรายวิชา (เพิ่มชั่วโมง/หน่วยกิต)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL,
    hours INT DEFAULT 40, -- จำนวนชั่วโมง
    credits FLOAT DEFAULT 1.0, -- หน่วยกิต
    learning_area VARCHAR(255), -- กลุ่มสาระการเรียนรู้
    school_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางมอบหมายงานสอน
CREATE TABLE IF NOT EXISTS teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    subject_id INT,
    academic_year VARCHAR(4),
    semester INT,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางหน่วยการเรียนรู้
CREATE TABLE IF NOT EXISTS learning_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    unit_name VARCHAR(255) NOT NULL,
    max_score FLOAT DEFAULT 10,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางบันทึกเวลาเรียน (20 สัปดาห์)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject_id INT,
    week_number INT, -- 1-20
    status ENUM('present', 'absent', 'late', 'sick', 'leave') DEFAULT 'present',
    date DATE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางคะแนนคุณลักษณะ/อ่านเขียน/กิจกรรม
CREATE TABLE IF NOT EXISTS evaluation_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject_id INT,
    category ENUM('characteristics', 'analytical', 'activities', 'clubs') NOT NULL,
    score INT DEFAULT 3, -- 0-3
    academic_year VARCHAR(4),
    semester INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางคะแนน (ปพ.5)
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject_id INT,
    k_score FLOAT DEFAULT 0, -- คะแนน K
    p_score FLOAT DEFAULT 0, -- คะแนน P
    a_score FLOAT DEFAULT 0, -- คะแนน A
    midterm_score FLOAT DEFAULT 0,
    final_score FLOAT DEFAULT 0,
    total_score FLOAT DEFAULT 0,
    grade_point FLOAT DEFAULT 0,
    academic_year VARCHAR(4),
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- เพิ่มข้อมูล Super Admin เริ่มต้น (รหัสผ่าน: 123456)
-- ลบข้อมูลเดิมออกก่อน (ถ้ามี) เพื่อป้องกัน Error เรื่องชื่อซ้ำ
DELETE FROM users WHERE username = '0000000000000';

-- เพิ่มข้อมูล Super Admin ใหม่
INSERT INTO users (username, password, name, role, affiliation, is_approved) 
VALUES ('0000000000000', '123456', 'Super Admin System', 'super_admin', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์เขต 3', TRUE);
