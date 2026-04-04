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
$national_id = $data['national_id'] ?? '';
$name = $data['name'] ?? '';
$level = $data['level'] ?? '';

if (empty($student_code) || empty($national_id) || empty($name) || empty($level)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลนักเรียนให้ครบถ้วน']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO students (student_code, national_id, name, level, school_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$student_code, $national_id, $name, $level, $_SESSION['school_id']]);
    echo json_encode(['message' => 'เพิ่มข้อมูลนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถเพิ่มข้อมูลได้: ' . $e->getMessage()]);
}
?>
