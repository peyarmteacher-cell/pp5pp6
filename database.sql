-- ตารางโรงเรียน
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(8) UNIQUE NOT NULL, -- รหัส 8 หลัก
    name VARCHAR(255) NOT NULL,
    province VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางผู้ใช้งาน (ครู/Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(13) UNIQUE NOT NULL, -- เลขบัตรประชาชน
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher') DEFAULT 'teacher',
    school_id INT,
    position VARCHAR(100), -- ตำแหน่ง (ครู คศ.1, ผอ. ฯลฯ)
    affiliation VARCHAR(255), -- สังกัด (เช่น สพป.บุรีรัมย์ เขต 3)
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางนักเรียน
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL, -- ระดับชั้น เช่น ป.1
    school_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางรายวิชา
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    level VARCHAR(20) NOT NULL,
    school_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
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
