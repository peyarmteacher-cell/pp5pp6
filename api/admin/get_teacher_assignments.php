<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Admin โรงเรียนเท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$teacher_id = $_GET['teacher_id'] ?? '';
$academic_year = $_GET['academic_year'] ?? date('Y');
$semester = $_GET['semester'] ?? 1;

if (empty($teacher_id)) {
    echo json_encode(['error' => 'ไม่พบรหัสคุณครู']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT ta.id as assignment_id, s.code, s.name, s.level, s.hours, s.credits
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        WHERE ta.teacher_id = ? AND ta.academic_year = ? AND ta.semester = ?
        ORDER BY s.level ASC, s.code ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $assignments = $stmt->fetchAll();
    echo json_encode($assignments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลงานสอนได้: ' . $e->getMessage()]);
}
?>
