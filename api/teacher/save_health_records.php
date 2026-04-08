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
$classroom_id = $data['classroom_id'] ?? null;
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? 1;
$record_number = $data['record_number'] ?? 1;
$records = $data['records'] ?? [];
$recorded_date = $data['recorded_date'] ?? date('Y-m-d');

if (!$classroom_id || empty($academic_year) || empty($records)) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO student_health_records 
        (student_id, classroom_id, academic_year, semester, record_number, weight, height, recorded_date, teacher_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        weight = VALUES(weight),
        height = VALUES(height),
        recorded_date = VALUES(recorded_date),
        teacher_id = VALUES(teacher_id)
    ');

    foreach ($records as $r) {
        $stmt->execute([
            $r['student_id'],
            $classroom_id,
            $academic_year,
            $semester,
            $record_number,
            $r['weight'],
            $r['height'],
            $recorded_date,
            $teacher_id
        ]);
    }

    $pdo->commit();
    echo json_encode(['message' => 'บันทึกข้อมูลน้ำหนัก-ส่วนสูงเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
