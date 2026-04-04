<?php
require_once 'config.php';

header('Content-Type: application/json');

// รับข้อมูล JSON จากการ POST
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
    exit;
}

try {
    // ค้นหาผู้ใช้
    $stmt = $pdo->prepare('
        SELECT u.*, s.name as school_name 
        FROM users u 
        LEFT JOIN schools s ON u.school_id = s.id 
        WHERE u.username = ? AND u.password = ?
    ');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    if (!$user['is_approved']) {
        http_response_code(403);
        echo json_encode(['error' => 'บัญชีของคุณยังไม่ได้รับการอนุมัติ กรุณารอการตรวจสอบ']);
        exit;
    }

    // ส่งข้อมูลผู้ใช้กลับไป
    echo json_encode($user);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในระบบฐานข้อมูล: ' . $e->getMessage()]);
}
?>
