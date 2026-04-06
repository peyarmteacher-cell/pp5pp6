<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Admin หรือ งานวิชาการ
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !$_SESSION['is_academic'])) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_code = $data['student_code'] ?? '';
$prefix = $data['prefix'] ?? '';
$national_id = $data['national_id'] ?? '';
$name = $data['name'] ?? '';
$level = $data['level'] ?? '';
$room = $data['room'] ?? '1';
$academic_year = $data['academic_year'] ?? '2567';
$school_id = $_SESSION['school_id'];

if (empty($student_code) || empty($national_id) || empty($name) || empty($level)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลนักเรียนให้ครบถ้วน']);
    exit;
}

try {
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

    $stmt = $pdo->prepare('INSERT INTO students (student_code, prefix, national_id, name, level, room, classroom_id, academic_year, school_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$student_code, $prefix, $national_id, $name, $level, $room, $classroom_id, $academic_year, $school_id]);
    echo json_encode(['message' => 'เพิ่มข้อมูลนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถเพิ่มข้อมูลได้: ' . $e->getMessage()]);
}
?>
