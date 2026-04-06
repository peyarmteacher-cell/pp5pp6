<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

try {
    $academic_year = $_GET['academic_year'] ?? '2567';
    $stmt = $pdo->prepare('SELECT * FROM students WHERE school_id = ? AND academic_year = ? ORDER BY level ASC, room ASC, name ASC');
    $stmt->execute([$_SESSION['school_id'], $academic_year]);
    $students = $stmt->fetchAll();
    echo json_encode($students);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถดึงข้อมูลนักเรียนได้']);
}
?>
