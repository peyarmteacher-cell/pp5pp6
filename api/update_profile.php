<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$affiliation = $data['affiliation'] ?? '';

if (empty($name)) {
    echo json_encode(['error' => 'กรุณากรอกชื่อ']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET name = ?, affiliation = ? WHERE id = ?');
    $stmt->execute([$name, $affiliation, $_SESSION['user_id']]);
    
    // อัปเดต Session
    $_SESSION['name'] = $name;
    $_SESSION['affiliation'] = $affiliation;
    
    echo json_encode(['message' => 'อัปเดตข้อมูลสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถอัปเดตข้อมูลได้']);
}
?>
