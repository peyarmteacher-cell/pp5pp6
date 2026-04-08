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
        SELECT ta.id as assignment_id, s.id as subject_id, s.code as subject_code, s.name as subject_name, s.level, c.id as classroom_id, c.room
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        LEFT JOIN classrooms c ON ta.classroom_id = c.id
        WHERE ta.teacher_id = ? AND ta.academic_year = ? AND ta.semester = ?
        ORDER BY s.level ASC, c.room ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $assignments = $stmt->fetchAll();

    echo json_encode($assignments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
