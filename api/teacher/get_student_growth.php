<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['error' => 'ไม่พบรหัสนักเรียน']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT academic_year, semester, record_number, weight, height, recorded_date
        FROM student_health_records
        WHERE student_id = ?
        ORDER BY academic_year ASC, semester ASC, record_number ASC
    ');
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll();

    echo json_encode($records);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
