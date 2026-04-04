<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็นคุณครู
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? '';
$subject_id = $data['subject_id'] ?? '';
$week_number = $data['week_number'] ?? '';
$status = $data['status'] ?? 'present';
$date = $data['date'] ?? date('Y-m-d');

if (empty($student_id) || empty($subject_id) || empty($week_number)) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    // ตรวจสอบว่ามีข้อมูลเดิมไหม ถ้ามีให้ UPDATE ถ้าไม่มีให้ INSERT
    $stmt = $pdo->prepare('SELECT id FROM attendance WHERE student_id = ? AND subject_id = ? AND week_number = ?');
    $stmt->execute([$student_id, $subject_id, $week_number]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE attendance SET status = ?, date = ? WHERE id = ?');
        $stmt->execute([$status, $date, $existing['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO attendance (student_id, subject_id, week_number, status, date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $subject_id, $week_number, $status, $date]);
    }
    echo json_encode(['message' => 'บันทึกเวลาเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()]);
}
?>
