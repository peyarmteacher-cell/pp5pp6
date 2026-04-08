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
$check_date = $data['check_date'] ?? date('Y-m-d');
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? 1;
$records = $data['records'] ?? []; // [{student_id, subject_id, period_number, status}]

if (!$classroom_id || empty($academic_year) || empty($records)) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO attendance 
        (student_id, subject_id, activity_type, classroom_id, academic_year, semester, check_date, period_number, status, teacher_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status),
        teacher_id = VALUES(teacher_id)
    ');

    foreach ($records as $r) {
        $activity_type = null;
        $real_subject_id = $r['subject_id'];

        if (is_string($r['subject_id']) && strpos($r['subject_id'], 'LD:') === 0) {
            $activity_type = str_replace('LD:', '', $r['subject_id']);
            $real_subject_id = null;
        }

        $stmt->execute([
            $r['student_id'],
            $real_subject_id,
            $activity_type,
            $classroom_id,
            $academic_year,
            $semester,
            $check_date,
            $r['period_number'],
            $r['status'],
            $teacher_id
        ]);
    }

    $pdo->commit();
    echo json_encode(['message' => 'บันทึกการมาเรียนเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
