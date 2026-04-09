<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

if (!isset($_FILES['logo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่พบไฟล์ที่อัปโหลด']);
    exit;
}

$file = $_FILES['logo'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 500 * 1024; // 500KB

if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP)']);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'ขนาดไฟล์ต้องไม่เกิน 500KB']);
    exit;
}

$upload_dir = '../../uploads/logos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'logo_' . $_SESSION['school_id'] . '_' . time() . '.' . $extension;
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Return the relative path for the frontend
    $relative_path = 'uploads/logos/' . $filename;
    echo json_encode([
        'status' => 'success',
        'url' => $relative_path
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการบันทึกไฟล์']);
}
?>
