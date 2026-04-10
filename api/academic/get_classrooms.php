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
    $stmt = $pdo->prepare('
        SELECT c.*, 
               u1.name as teacher_name_1,
               u2.name as teacher_name_2
        FROM classrooms c
        LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
        LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
        WHERE c.school_id = ? 
        ORDER BY c.level ASC, c.room ASC
    ');
    $stmt->execute([$school_id]);
    $classrooms = $stmt->fetchAll();
    echo json_encode($classrooms);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลห้องเรียนได้: ' . $e->getMessage()]);
}
?>
