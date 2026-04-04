<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT u.*, s.name as school_name 
        FROM users u 
        LEFT JOIN schools s ON u.school_id = s.id 
        WHERE u.username = ?
    ');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'ไม่พบชื่อผู้ใช้นี้ในระบบ (' . $username . ')']);
        exit;
    }

    // ตรวจสอบรหัสผ่าน (รองรับทั้งแบบ Plain Text และ Hashed)
    $isPasswordCorrect = false;
    if ($password === $user['password']) {
        $isPasswordCorrect = true;
    } else if (password_verify($password, $user['password'])) {
        $isPasswordCorrect = true;
    }

    if (!$isPasswordCorrect) {
        http_response_code(401);
        echo json_encode(['error' => 'รหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    if (!$user['is_approved']) {
        http_response_code(403);
        echo json_encode(['error' => 'บัญชีของคุณยังไม่ได้รับการอนุมัติ กรุณารอการตรวจสอบ']);
        exit;
    }

    // เก็บข้อมูลใน Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['school_id'] = $user['school_id'];
    $_SESSION['school_name'] = $user['school_name'];

    echo json_encode($user);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
