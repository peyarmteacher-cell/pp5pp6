<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

try {
    $stmt = $pdo->prepare('
        SELECT t.*, s.name as subject_name, s.code as subject_code, c.level, c.room
        FROM timetables t
        JOIN subjects s ON t.subject_id = s.id
        JOIN classrooms c ON t.classroom_id = c.id
        WHERE t.teacher_id = ? AND t.academic_year = ? AND t.semester = ?
        ORDER BY t.day_of_week ASC, t.period_number ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $timetable = $stmt->fetchAll();

    echo json_encode($timetable);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
