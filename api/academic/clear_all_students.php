<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];

try {
    // ลบข้อมูลนักเรียนทั้งหมดของโรงเรียนนี้
    // ตารางอื่นๆ เช่น attendance, grades, characteristics_scores, behavior_records ฯลฯ
    // มีการตั้งค่า Foreign Key เป็น ON DELETE CASCADE ไว้แล้ว
    // ดังนั้นการลบจากตาราง students จะลบข้อมูลที่เกี่ยวข้องโดยอัตโนมัติ

    $pdo->beginTransaction();

    // ลบตัวนักเรียน (ซึ่งจะ Cascade ไปยัง Attendance, Grades, Behavior, Health, ฯลฯ)
    $stmt = $pdo->prepare("DELETE FROM students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    
    $pdo->commit();

    echo json_encode(['message' => 'ล้างข้อมูลนักเรียนและข้อมูลการเรียนทั้งหมดเรียบร้อยแล้ว (ข้อมูลครูและรายวิชายังอยู่ครบถ้วน)']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
