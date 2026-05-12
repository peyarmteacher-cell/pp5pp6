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
        SELECT c.id, c.level, c.room
        FROM learner_development_assignments lda
        JOIN classrooms c ON lda.classroom_id = c.id
        WHERE lda.teacher_id = ? AND lda.academic_year = ? AND lda.semester = ?
        ORDER BY c.level ASC, c.room ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($classrooms);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
