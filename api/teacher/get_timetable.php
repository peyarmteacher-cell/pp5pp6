<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

try {
    $stmt = $pdo->prepare('
        SELECT t.*, 
               s.name as subject_name, s.code as subject_code, 
               c.level, c.room
        FROM timetables t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN classrooms c ON t.classroom_id = c.id
        WHERE t.teacher_id = ? AND t.academic_year = ? AND t.semester = ?
        ORDER BY t.day_of_week ASC, t.period_number ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $timetable = $stmt->fetchAll();

    foreach ($timetable as &$t) {
        if (!empty($t['activity_type'])) {
            $activities = [
                'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
                'scouts' => ['name' => 'กิจกรรมลูกเสือ-เนตรนารี', 'code' => 'ลูกเสือ'],
                'scout' => ['name' => 'กิจกรรมลูกเสือ-เนตรนารี', 'code' => 'ลูกเสือ'],
                'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
                'social' => ['name' => 'กิจกรรมเพื่อสังคมฯ', 'code' => 'สังคมฯ'],
                'lunch' => ['name' => 'พักรับประทานอาหาร', 'code' => 'พักกลางวัน'],
                'homeroom' => ['name' => 'Home Room', 'code' => 'โฮมรูม']
            ];
            $act = $activities[strtolower($t['activity_type'])] ?? null;
            if ($act) {
                $t['subject_name'] = $act['name'];
                $t['subject_code'] = $act['code'];
            }
        }
    }

    echo json_encode($timetable);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
