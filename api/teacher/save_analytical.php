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
$subject_id = $data['subject_id'] ?? '';
$classroom_id = $data['classroom_id'] ?? '';
$academic_year = $data['academic_year'] ?? '2567';
$semester = $data['semester'] ?? 1;
$scores = $data['scores'] ?? [];

if (empty($subject_id) || empty($classroom_id) || empty($scores)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($scores as $s) {
        $student_id = $s['student_id'];
        $score = $s['score'] ?? 0;

        $stmt = $pdo->prepare('
            INSERT INTO analytical_scores (student_id, subject_id, classroom_id, teacher_id, academic_year, semester, score)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            score = VALUES(score),
            teacher_id = VALUES(teacher_id)
        ');
        $stmt->execute([$student_id, $subject_id, $classroom_id, $teacher_id, $academic_year, $semester, $score]);
    }

    $pdo->commit();
    echo json_encode(['message' => 'บันทึกอ่านคิดวิเคราะห์สำเร็จ']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
