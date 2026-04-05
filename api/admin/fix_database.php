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

    // 7. ตรวจสอบและเพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments
    $stmt = $pdo->query("SHOW COLUMNS FROM teacher_assignments LIKE 'classroom_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE teacher_assignments ADD COLUMN classroom_id INT AFTER subject_id");
        $pdo->exec("ALTER TABLE teacher_assignments ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE");
        $results[] = "เพิ่มคอลัมน์ classroom_id ในตาราง teacher_assignments สำเร็จ";
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
