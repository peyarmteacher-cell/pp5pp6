<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$teacher_id = $_SESSION['user_id'];
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? 1;
$day_of_week = $data['day_of_week'] ?? null;
$period_number = $data['period_number'] ?? null;
$subject_id = $data['subject_id'] ?? null;
$classroom_id = $data['classroom_id'] ?? null;

if (!$day_of_week || !$period_number || !$academic_year) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    if ($subject_id === null) {
        // ลบข้อมูล
        $stmt = $pdo->prepare('DELETE FROM timetables WHERE classroom_id = ? AND academic_year = ? AND semester = ? AND day_of_week = ? AND period_number = ?');
        $stmt->execute([$classroom_id, $academic_year, $semester, $day_of_week, $period_number]);
    } else {
        // บันทึกหรืออัปเดต
        $stmt = $pdo->prepare('
            INSERT INTO timetables (teacher_id, subject_id, classroom_id, academic_year, semester, day_of_week, period_number)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            teacher_id = VALUES(teacher_id),
            subject_id = VALUES(subject_id)
        ');
        $stmt->execute([$teacher_id, $subject_id, $classroom_id, $academic_year, $semester, $day_of_week, $period_number]);
    }

    echo json_encode(['message' => 'บันทึกตารางสอนเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
