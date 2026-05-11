<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !$_SESSION['is_academic'])) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$from_year = $data['from_year'] ?? '';
$to_year = $data['to_year'] ?? '';
$school_id = $_SESSION['school_id'];

if (empty($from_year) || empty($to_year)) {
    echo json_encode(['error' => 'ข้อมูลปีการศึกษาไม่ครบถ้วน']);
    exit;
}

try {
    // 1. ตรวจสอบว่าปีการศึกษาปลายทางมีนร.หรือยัง (นับเฉพาะห้องเรียนและระดับชั้น)
    $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? AND academic_year = ?");
    $check->execute([$school_id, $to_year]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['error' => 'มีข้อมูลนักเรียนในปีการศึกษาปลายทางแล้ว กรุณาลบข้อมูลเดิมก่อนหากต้องการดึงใหม่']);
        exit;
    }

    $levels = ['ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6', 'ม.1', 'ม.2', 'ม.3'];
    
    // 2. ดึงข้อมูลนักเรียนจากปีการศึกษาเดิมที่สถานะกำลังเรียนอยู่
    $stmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? AND academic_year = ? AND status = 'studying'");
    $stmt->execute([$school_id, $from_year]);
    $old_students = $stmt->fetchAll();
    
    $promoted_count = 0;
    
    foreach ($old_students as $student) {
        $current_level = $student['level'];
        $level_index = array_search($current_level, $levels);
        
        if ($level_index !== false) {
            // ข้ามนักเรียนชั้นสูงสุด (ม.3) เพราะต้องไปทำในเมนูจบการศึกษา
            if ($current_level === 'ม.3') {
                continue;
            }
            
            // กรณี ป.6 ถ้าไม่มี ม.1 ให้ข้าม (ไปจบการศึกษา)
            if ($current_level === 'ป.6' && !in_array('ม.1', $levels)) {
                 continue;
            }
            
            $next_level = $levels[$level_index + 1];
            
            // คัดลอกนักเรียนไปปีการศึกษาใหม่พร้อมเลื่อนชั้น
            $ins = $pdo->prepare("INSERT INTO students (student_code, national_id, name, level, room, academic_year, school_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'studying')");
            $ins->execute([
                $student['student_code'],
                $student['national_id'],
                $student['name'],
                $next_level,
                $student['room'],
                $to_year,
                $school_id
            ]);
            $promoted_count++;
        }
    }
    
    echo json_encode(['message' => "ดึงข้อมูลและเลื่อนชั้นนักเรียนสำเร็จ จำนวน $promoted_count ราย"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
