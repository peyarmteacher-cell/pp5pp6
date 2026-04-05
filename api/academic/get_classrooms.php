<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$school_id = $_SESSION['school_id'];

try {
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE school_id = ? ORDER BY level ASC, room ASC');
    $stmt->execute([$school_id]);
    $classrooms = $stmt->fetchAll();
    echo json_encode($classrooms);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลห้องเรียนได้: ' . $e->getMessage()]);
}
?>
