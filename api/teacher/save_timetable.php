<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$is_admin_or_academic = ($_SESSION['role'] === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'] == 1));
$teacher_id = $_SESSION['user_id'];
if ($is_admin_or_academic && isset($data['teacher_id']) && !empty($data['teacher_id'])) {
    $teacher_id = $data['teacher_id'];
}
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? 1;
$day_of_week = $data['day_of_week'] ?? null;
$period_number = $data['period_number'] ?? null;
$subject_id = $data['subject_id'] ?? null;
$classroom_id = $data['classroom_id'] ?? null;

if (!$day_of_week || !$period_number || !$academic_year) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    if ($subject_id === null) {
        // ลบข้อมูล
        $stmt = $pdo->prepare('DELETE FROM timetables WHERE teacher_id = ? AND academic_year = ? AND semester = ? AND day_of_week = ? AND period_number = ?');
        $stmt->execute([$teacher_id, $academic_year, $semester, $day_of_week, $period_number]);
    } else {
        $activity_type = null;
        $real_subject_id = $subject_id;

        // ตรวจสอบว่าเป็นกิจกรรมพัฒนาผู้เรียนหรือไม่
        if (is_string($subject_id) && strpos($subject_id, 'LD:') === 0) {
            $activity_type = str_replace('LD:', '', $subject_id);
            $real_subject_id = null;
        }

        // บันทึกหรืออัปเดต
        $stmt = $pdo->prepare('
            INSERT INTO timetables (teacher_id, subject_id, activity_type, classroom_id, academic_year, semester, day_of_week, period_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            teacher_id = VALUES(teacher_id),
            subject_id = VALUES(subject_id),
            activity_type = VALUES(activity_type)
        ');
        $stmt->execute([$teacher_id, $real_subject_id, $activity_type, $classroom_id, $academic_year, $semester, $day_of_week, $period_number]);
    }

    echo json_encode(['message' => 'บันทึกตารางสอนเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
