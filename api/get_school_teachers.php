<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    exit;
}

$school_id = $_GET['school_id'] ?? '';

if (empty($school_id)) {
    echo json_encode(['error' => 'ไม่พบรหัสโรงเรียน']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, name, position, role, is_approved FROM users WHERE school_id = ? ORDER BY name ASC');
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
    echo json_encode($teachers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลคุณครูได้']);
}
?>
