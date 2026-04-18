<?php
/**
 * API to save parent feedback for a student
 */
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['parent_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $_SESSION['student_id'];
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? '';
$feedback_text = $data['feedback_text'] ?? '';
$tags = $data['tags'] ?? ''; // ลิสต์ข้อความสั้นๆ เช่น "ช่วยเหลือดี, อ่านหนังสือมากขึ้น"

if (empty($academic_year) || empty($semester)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลปีการศึกษาไม่ถูกต้อง']);
    exit;
}

try {
    // ตรวจสอบว่ามีข้อมูลเดิมอยู่หรือไม่
    $stmt = $pdo->prepare("SELECT id FROM parent_feedback WHERE student_id = ? AND academic_year = ? AND semester = ?");
    $stmt->execute([$student_id, $academic_year, $semester]);
    $existing = $stmt->fetch();

    if ($existing) {
        // อัปเดต
        $stmt = $pdo->prepare("UPDATE parent_feedback SET feedback_text = ?, tags = ? WHERE id = ?");
        $stmt->execute([$feedback_text, $tags, $existing['id']]);
    } else {
        // เพิ่มใหม่
        $stmt = $pdo->prepare("INSERT INTO parent_feedback (student_id, academic_year, semester, feedback_text, tags) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $academic_year, $semester, $feedback_text, $tags]);
    }

    echo json_encode(['success' => true, 'message' => 'บันทึกความคิดเห็นเรียบร้อยแล้ว']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
