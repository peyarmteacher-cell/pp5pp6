<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];
$academic_year = $_GET['academic_year'] ?? '';
// P6 มักจะใช้ข้อมูลรายปี แต่ถ้ามีเทอมส่งมา กรองตามเทอมที่ตั้งค่าไว้ได้
$semester = $_GET['semester'] ?? '';

try {
    // ดึงห้องเรียนที่ครูคนนี้ได้รับมอบหมายกิจกรรมพัฒนาผู้เรียน (LD) 
    // หรือเป็นครูประจำชั้น (เผื่อไว้)
    $sql = "
        SELECT DISTINCT c.* 
        FROM classrooms c
        LEFT JOIN learner_development_assignments lda ON c.id = lda.classroom_id
        WHERE c.school_id = ? 
        AND (
            lda.teacher_id = ? 
            OR c.teacher_id_1 = ? 
            OR c.teacher_id_2 = ?
        )
    ";
    
    $params = [$school_id, $teacher_id, $teacher_id, $teacher_id];
    
    if (!empty($academic_year)) {
        // หากในตาราง assignments มีการเก็บปีการศึกษา (แนะนำให้กรองถ้ามีคอลัมน์นี้)
        // แต่ถ้าอิงคลาสนั้นๆ ในปีปัจจุบันเป็นหลัก ก็ใช้ Query ด้านบนได้เลย
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($classrooms);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
