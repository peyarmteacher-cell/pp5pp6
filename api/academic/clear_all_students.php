<?php
require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];

try {
    // ลบข้อมูลนักเรียนทั้งหมดของโรงเรียนนี้
    // หมายเหตุ: ข้อมูลการเข้าเรียน (attendance), คะแนน (grades), พฤติกรรม (behavior) 
    // มักจะผูกกับ student_id หรือ student_code/academic_year
    // หากมี table อื่นที่เชื่อมโยงด้วย Foreign Key เราอาจต้องลบตามลำดับ
    
    $pdo->beginTransaction();

    // ลบข้อมูลที่เกี่ยวข้องกับนักเรียนก่อน (ถ้ามี)
    $pdo->prepare("DELETE FROM attendance WHERE school_id = ?")->execute([$school_id]);
    $pdo->prepare("DELETE FROM grades WHERE school_id = ?")->execute([$school_id]);
    $pdo->prepare("DELETE FROM student_behavior WHERE school_id = ?")->execute([$school_id]);
    $pdo->prepare("DELETE FROM student_health WHERE school_id = ?")->execute([$school_id]);
    $pdo->prepare("DELETE FROM learner_development WHERE school_id = ?")->execute([$school_id]);
    $pdo->prepare("DELETE FROM competency_scores WHERE school_id = ?")->execute([$school_id]);
    
    // ลบตัวนักเรียน
    $stmt = $pdo->prepare("DELETE FROM students WHERE school_id = ?");
    $stmt->execute([$school_id]);
    
    $pdo->commit();

    echo json_encode(['message' => 'ล้างข้อมูลนักเรียนและข้อมูลการเรียนทั้งหมดเรียบร้อยแล้ว (ข้อมูลครูและรายวิชายังอยู่ครบถ้วน)']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
