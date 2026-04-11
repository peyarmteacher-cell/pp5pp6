<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$password = $data['password'] ?? '';

if (empty($name)) {
    echo json_encode(['error' => 'กรุณากรอกชื่อ']);
    exit;
}

try {
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ? WHERE id = ?');
        $stmt->execute([$name, $hashedPassword, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $_SESSION['user_id']]);
    }
    
    // อัปเดต Session
    $_SESSION['name'] = $name;
    
    echo json_encode(['message' => 'อัปเดตข้อมูลสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $e->getMessage()]);
}
?>
