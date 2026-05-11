<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !($_SESSION['is_academic'] ?? false))) {
    http_response_code(403);
    echo json_encode(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้']);
    exit;
}

$school_id = $_SESSION['school_id'];

try {
    if (!$pdo) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    $pdo->beginTransaction();

    // ลบตัวนักเรียน (ซึ่งจะ Cascade ไปยัง Attendance, Grades, Behavior, Health, ฯลฯ)
    $stmt = $pdo->prepare("DELETE FROM students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    
    $pdo->commit();

    echo json_encode(['message' => 'ล้างข้อมูลนักเรียนและข้อมูลการเรียนทั้งหมดเรียบร้อยแล้ว (ข้อมูลครูและรายวิชายังอยู่ครบถ้วน)']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
