<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$school_id = $_GET['school_id'] ?? '';
$current_role = $_SESSION['role'];
$current_school_id = $_SESSION['school_id'];

// ตรวจสอบสิทธิ์: Super Admin ดูได้ทุกที่, Admin ดูได้เฉพาะโรงเรียนตัวเอง
if ($current_role !== 'super_admin' && (string)$school_id !== (string)$current_school_id) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลโรงเรียนนี้']);
    exit;
}

if (empty($school_id)) {
    echo json_encode(['error' => 'ไม่พบรหัสโรงเรียน']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, name, position, role, is_approved, is_academic FROM users WHERE school_id = ? ORDER BY name ASC');
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
    echo json_encode($teachers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลคุณครูได้']);
}
?>
