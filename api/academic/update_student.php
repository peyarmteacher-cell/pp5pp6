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
$id = $data['id'] ?? '';
$name = $data['name'] ?? '';
$level = $data['level'] ?? '';
$room = $data['room'] ?? '1';
$school_id = $_SESSION['school_id'];

if (empty($id) || empty($name) || empty($level)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
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

    $stmt = $pdo->prepare('UPDATE students SET name = ?, level = ?, room = ?, classroom_id = ? WHERE id = ? AND school_id = ?');
    $stmt->execute([$name, $level, $room, $classroom_id, $id, $school_id]);
    echo json_encode(['message' => 'อัปเดตข้อมูลนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $e->getMessage()]);
}
?>
