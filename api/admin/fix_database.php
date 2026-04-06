<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Super Admin เท่านั้นที่สามารถรันสคริปต์นี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$results = [];

try {
    // 1. ตรวจสอบและเพิ่มคอลัมน์ is_academic ในตาราง users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_academic'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_academic BOOLEAN DEFAULT FALSE AFTER role");
        $results[] = "เพิ่มคอลัมน์ is_academic ในตาราง users สำเร็จ";
    } else {
        $results[] = "ตาราง users มีคอลัมน์ is_academic อยู่แล้ว";
    }

    // 2. ตรวจสอบและเพิ่มคอลัมน์ national_id ในตาราง students
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'national_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN national_id VARCHAR(13) NOT NULL AFTER student_code");
        $results[] = "เพิ่มคอลัมน์ national_id ในตาราง students สำเร็จ";
    } else {
        $results[] = "ตาราง students มีคอลัมน์ national_id อยู่แล้ว";
    }

    // 3. ตรวจสอบและเพิ่มคอลัมน์ hours ในตาราง subjects
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'hours'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE subjects ADD COLUMN hours INT DEFAULT 40 AFTER level");
        $results[] = "เพิ่มคอลัมน์ hours ในตาราง subjects สำเร็จ";
    } else {
        $results[] = "ตาราง subjects มีคอลัมน์ hours อยู่แล้ว";
    }

    // 4. ตรวจสอบและเพิ่มคอลัมน์ credits ในตาราง subjects
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'credits'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE subjects ADD COLUMN credits FLOAT DEFAULT 1.0 AFTER hours");
        $results[] = "เพิ่มคอลัมน์ credits ในตาราง subjects สำเร็จ";
    } else {
        $results[] = "ตาราง subjects มีคอลัมน์ credits อยู่แล้ว";
    }

    // 5. เพิ่มตาราง classrooms
    $pdo->exec("CREATE TABLE IF NOT EXISTS classrooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        level VARCHAR(50) NOT NULL,
        room VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
        UNIQUE KEY unique_classroom (school_id, level, room)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง classrooms สำเร็จ";

    // 6. เพิ่มคอลัมน์ room และ classroom_id ในตาราง students
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'room'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN room VARCHAR(10) AFTER level");
        $results[] = "เพิ่มคอลัมน์ room ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN classroom_id INT AFTER room");
        $pdo->exec("ALTER TABLE students ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL");
        $results[] = "เพิ่มคอลัมน์ classroom_id และ Foreign Key ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'academic_year'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN academic_year VARCHAR(4) DEFAULT '2567' AFTER school_id");
        $results[] = "เพิ่มคอลัมน์ academic_year ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'prefix'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN prefix VARCHAR(20) AFTER student_code");
        $results[] = "เพิ่มคอลัมน์ prefix ในตาราง students สำเร็จ";
    }

    // ซิงค์ classroom_id ให้กับนักเรียนที่ยังไม่มี (อิงตาม level และ room)
    $pdo->exec("UPDATE students s 
                JOIN classrooms c ON s.school_id = c.school_id AND s.level = c.level AND s.room = c.room
                SET s.classroom_id = c.id
                WHERE s.classroom_id IS NULL");
    $results[] = "ซิงค์ข้อมูลห้องเรียนให้นักเรียนสำเร็จ";

    // 7. ตรวจสอบและเพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments
    $stmt = $pdo->query("SHOW COLUMNS FROM teacher_assignments LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE teacher_assignments ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE teacher_assignments ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
        $results[] = "เพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments สำเร็จ";
    }

    // 8. เพิ่มตารางบันทึกคะแนน
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        score_midterm FLOAT DEFAULT 0,
        score_final FLOAT DEFAULT 0,
        score_total FLOAT DEFAULT 0,
        grade VARCHAR(5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_grade (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง grades สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS characteristics_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        item1 INT DEFAULT 0,
        item2 INT DEFAULT 0,
        item3 INT DEFAULT 0,
        item4 INT DEFAULT 0,
        item5 INT DEFAULT 0,
        item6 INT DEFAULT 0,
        item7 INT DEFAULT 0,
        item8 INT DEFAULT 0,
        average_score FLOAT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_char (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง characteristics_scores สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS analytical_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        classroom_id INT,
        teacher_id INT,
        academic_year VARCHAR(4),
        semester INT,
        score INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_analytical (student_id, subject_id, classroom_id, academic_year, semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง analytical_scores สำเร็จ";

    // 9. ปรับปรุงตาราง learning_units และเพิ่ม unit_scores
    $pdo->exec("CREATE TABLE IF NOT EXISTS learning_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_id INT,
        classroom_id INT,
        academic_year VARCHAR(4),
        semester INT,
        unit_name VARCHAR(255) NOT NULL,
        max_score FLOAT DEFAULT 10,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ตรวจสอบและเพิ่มคอลัมน์ classroom_id ใน learning_units หากไม่มี
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE learning_units ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'academic_year'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN academic_year VARCHAR(4) AFTER classroom_id");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM learning_units LIKE 'semester'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE learning_units ADD COLUMN semester INT AFTER academic_year");
    }
    $results[] = "ตรวจสอบ/สร้างตาราง learning_units สำเร็จ";

    // ปรับปรุงตาราง grades ให้รองรับคะแนนหน่วยและร้อยละ
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_units'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_units FLOAT DEFAULT 0 AFTER semester");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_midterm'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_midterm FLOAT DEFAULT 0 AFTER score_units");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_final'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_final FLOAT DEFAULT 0 AFTER score_midterm");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_total'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_total FLOAT DEFAULT 0 AFTER score_final");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_percent'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_percent FLOAT DEFAULT 0 AFTER score_total");
    }
    $results[] = "ตรวจสอบ/ปรับปรุงตาราง grades สำเร็จ";

    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        learning_unit_id INT,
        score FLOAT DEFAULT 0,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (learning_unit_id) REFERENCES learning_units(id) ON DELETE CASCADE,
        UNIQUE KEY unique_unit_score (student_id, learning_unit_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง unit_scores สำเร็จ";

    // 10. ปรับปรุงตาราง grades ให้รองรับคะแนนรายปี
    $stmt = $pdo->query("SHOW COLUMNS FROM grades LIKE 'score_semester1'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_semester1 FLOAT DEFAULT 0 AFTER semester");
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_semester2 FLOAT DEFAULT 0 AFTER score_semester1");
        $pdo->exec("ALTER TABLE grades ADD COLUMN score_annual_avg FLOAT DEFAULT 0 AFTER score_semester2");
        $results[] = "เพิ่มคอลัมน์คะแนนรายปีในตาราง grades สำเร็จ";
    }

    // 11. เพิ่มตาราง academic_years และคอลัมน์สถานะนักเรียน
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT,
        year VARCHAR(4) NOT NULL,
        is_current BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
        UNIQUE KEY unique_year (school_id, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = "ตรวจสอบ/สร้างตาราง academic_years สำเร็จ";

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('studying', 'graduated', 'transferred', 'quit') DEFAULT 'studying' AFTER academic_year");
        $results[] = "เพิ่มคอลัมน์ status ในตาราง students สำเร็จ";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'generation'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE students ADD COLUMN generation VARCHAR(50) AFTER status");
        $results[] = "เพิ่มคอลัมน์ generation ในตาราง students สำเร็จ";
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'ตรวจสอบและปรับปรุงฐานข้อมูลเรียบร้อยแล้ว',
        'details' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการปรับปรุงฐานข้อมูล: ' . $e->getMessage(),
        'details' => $results
    ]);
}
?>
