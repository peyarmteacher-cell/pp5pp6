<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$check_date = $_GET['check_date'] ?? date('Y-m-d');
$classroom_id = $_GET['classroom_id'] ?? null;
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

if (!$classroom_id) {
    echo json_encode(['error' => 'กรุณาระบุห้องเรียน']);
    exit;
}

// หาว่าวันนี้เป็นวันอะไร (1=Mon, ..., 7=Sun)
$day_of_week = date('N', strtotime($check_date));

try {
    // 1. ดึงตารางสอนของวันนี้ในห้องนี้
    $stmt = $pdo->prepare('
        SELECT t.*, s.name as subject_name, s.code as subject_code
        FROM timetables t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.classroom_id = ? AND t.academic_year = ? AND t.semester = ? AND t.day_of_week = ?
        ORDER BY t.period_number ASC
    ');
    $stmt->execute([$classroom_id, $academic_year, $semester, $day_of_week]);
    $subjects = $stmt->fetchAll();

    // 2. ดึงรายชื่อนักเรียน
    $stmt = $pdo->prepare('SELECT id, student_code, prefix, name, last_name FROM students WHERE classroom_id = ? AND status = "studying" ORDER BY student_code ASC');
    $stmt->execute([$classroom_id]);
    $students = $stmt->fetchAll();

    // 3. ดึงข้อมูลการมาเรียนที่บันทึกไว้แล้ว
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE classroom_id = ? AND check_date = ?');
    $stmt->execute([$classroom_id, $check_date]);
    $attendance_data = $stmt->fetchAll();

    echo json_encode([
        'subjects' => $subjects,
        'students' => $students,
        'attendance' => $attendance_data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
