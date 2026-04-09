<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$province = $data['province'] ?? '';
$logo_url = $data['logo_url'] ?? '';

if (empty($name) || empty($province)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE schools SET name = ?, province = ?, logo_url = ? WHERE id = ?');
    $stmt->execute([$name, $province, $logo_url, $_SESSION['school_id']]);

    // Update session school name
    $_SESSION['school_name'] = $name;

    echo json_encode([
        'status' => 'success',
        'message' => 'อัปเดตข้อมูลโรงเรียนเรียบร้อยแล้ว'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
