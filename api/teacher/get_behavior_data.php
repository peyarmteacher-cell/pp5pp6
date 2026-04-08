<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$classroom_id = $_GET['classroom_id'] ?? '';
$check_date = $_GET['check_date'] ?? date('Y-m-d');

if (empty($classroom_id)) {
    echo json_encode(['error' => 'กรุณาระบุห้องเรียน']);
    exit;
}

try {
    // ดึงรายชื่อนักเรียนในห้อง
    $stmt = $pdo->prepare("SELECT id, prefix, name, lastname FROM students WHERE classroom_id = ? ORDER BY id ASC");
    $stmt->execute([$classroom_id]);
    $students = $stmt->fetchAll();

    // ดึงข้อมูลบันทึกพฤติกรรม
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM student_behavior_records r
        JOIN students s ON r.student_id = s.id
        WHERE s.classroom_id = ? AND r.check_date = ?
    ");
    $stmt->execute([$classroom_id, $check_date]);
    $records = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'students' => $students,
        'records' => $records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
