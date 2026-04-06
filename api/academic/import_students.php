<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Admin หรือ งานวิชาการ
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !$_SESSION['is_academic'])) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$students = $data['students'] ?? [];
$school_id = $_SESSION['school_id'];

if (empty($students)) {
    echo json_encode(['error' => 'ไม่พบข้อมูลนักเรียนที่จะนำเข้า']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($students as $s) {
        $student_code = trim($s['student_code'] ?? '');
        $prefix = trim($s['prefix'] ?? '');
        $national_id = trim($s['national_id'] ?? '');
        $name = trim($s['name'] ?? '');
        $level = trim($s['level'] ?? '');
        $room = trim($s['room'] ?? '');
        $academic_year = trim($s['academic_year'] ?? '2567');

        if (empty($student_code) || empty($name) || empty($level)) continue;

        // 1. ตรวจสอบ/สร้างห้องเรียน
        $stmt = $pdo->prepare('SELECT id FROM classrooms WHERE school_id = ? AND level = ? AND room = ?');
        $stmt->execute([$school_id, $level, $room]);
        $classroom = $stmt->fetch();

        $classroom_id = null;
        if (!$classroom) {
            $stmt = $pdo->prepare('INSERT INTO classrooms (school_id, level, room) VALUES (?, ?, ?)');
            $stmt->execute([$school_id, $level, $room]);
            $classroom_id = $pdo->lastInsertId();
        } else {
            $classroom_id = $classroom['id'];
        }

        // 2. ตรวจสอบนักเรียนเดิม (จากเลขประจำตัว หรือ เลขบัตรประชาชน)
        $stmt = $pdo->prepare('SELECT id FROM students WHERE school_id = ? AND (student_code = ? OR national_id = ?)');
        $stmt->execute([$school_id, $student_code, $national_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // อัปเดต
            $stmt = $pdo->prepare('UPDATE students SET prefix = ?, name = ?, level = ?, room = ?, classroom_id = ?, national_id = ?, academic_year = ? WHERE id = ?');
            $stmt->execute([$prefix, $name, $level, $room, $classroom_id, $national_id, $academic_year, $existing['id']]);
        } else {
            // เพิ่มใหม่
            $stmt = $pdo->prepare('INSERT INTO students (student_code, prefix, national_id, name, level, room, classroom_id, academic_year, school_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$student_code, $prefix, $national_id, $name, $level, $room, $classroom_id, $academic_year, $school_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['message' => 'นำเข้าข้อมูลนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถนำเข้าข้อมูลได้: ' . $e->getMessage()]);
}
?>
