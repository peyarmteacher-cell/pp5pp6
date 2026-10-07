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
        LEFT JOIN subjects s ON t.subject_id = s.id
        WHERE t.classroom_id = ? AND t.academic_year = ? AND t.semester = ? AND t.day_of_week = ?
        ORDER BY t.period_number ASC
    ');
    $stmt->execute([$classroom_id, $academic_year, $semester, $day_of_week]);
    $subjects = $stmt->fetchAll();

    foreach ($subjects as &$s) {
        if ($s['activity_type']) {
            $activities = [
                'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
                'scouts' => ['name' => 'กิจกรรมลูกเสือ/เนตรนารี', 'code' => 'ลูกเสือ'],
                'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
                'social' => ['name' => 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', 'code' => 'กิจกรรมเพื่อสังคมฯ'],
                'reducing_time' => ['name' => 'กิจกรรมลดเวลาเรียน เพิ่มเวลารู้', 'code' => 'ลดเวลาเรียนฯ'],
                'prayer' => ['name' => 'กิจกรรมสวดมนต์', 'code' => 'สวดมนต์']
            ];
            $act = $activities[$s['activity_type']] ?? null;
            if ($act) {
                $s['subject_name'] = $act['name'];
                $s['subject_code'] = $act['code'];
                $s['subject_id'] = 'LD:' . $s['activity_type'];
            }
        }
    }

    // หากไม่มีตารางสอนในวันนี้ ให้ดึงวิชาที่ได้รับมอบหมายสอนในห้องนี้มาให้เลือก เพื่อไม่ให้ครูถูกบล็อกการเช็คชื่อ
    if (empty($subjects)) {
        $stmt_fallback = $pdo->prepare('
            SELECT DISTINCT s.id as subject_id, s.name as subject_name, s.code as subject_code, 1 as period_number
            FROM teacher_assignments ta
            JOIN subjects s ON ta.subject_id = s.id
            WHERE ta.teacher_id = ? AND ta.classroom_id = ? AND ta.academic_year = ? AND (ta.semester = ? OR ta.semester = 0)
            ORDER BY s.code ASC
        ');
        $stmt_fallback->execute([$teacher_id, $classroom_id, $academic_year, $semester]);
        $subjects = $stmt_fallback->fetchAll();

        if (empty($subjects)) {
            $stmt_all_subs = $pdo->prepare('
                SELECT DISTINCT s.id as subject_id, s.name as subject_name, s.code as subject_code, 1 as period_number
                FROM classrooms c
                JOIN subjects s ON (c.level = s.level OR c.level = REPLACE(s.level, "ประถมศึกษาปีที่ ", "ป."))
                WHERE c.id = ?
                ORDER BY s.code ASC
            ');
            $stmt_all_subs->execute([$classroom_id]);
            $subjects = $stmt_all_subs->fetchAll();
        }
    }

    // 2. ดึงรายชื่อนักเรียนในห้องเรียนนี้
    $stmt_cls = $pdo->prepare('SELECT level, room FROM classrooms WHERE id = ?');
    $stmt_cls->execute([$classroom_id]);
    $cls_info = $stmt_cls->fetch();
    $cls_level = $cls_info['level'] ?? '';
    $cls_room = $cls_info['room'] ?? '';
    $clean_room = str_replace('ห้อง', '', $cls_room);

    $stmt = $pdo->prepare('
        SELECT s.id, s.student_code, 
               IFNULL(sp.prefix, s.prefix) AS prefix, 
               IFNULL(sp.name, s.name) AS name, 
               IFNULL(sp.last_name, s.last_name) AS last_name 
        FROM students s
        LEFT JOIN student_profiles sp ON s.student_profile_id = sp.id
        WHERE (s.classroom_id = ? OR (s.level = ? AND (s.room = ? OR s.room = ? OR REPLACE(s.room, "ห้อง", "") = ?)))
          AND (s.academic_year = ? OR s.academic_year IS NULL OR s.academic_year = "")
          AND (s.status = "studying" OR s.status IS NULL OR s.status = "" OR s.status = "กำลังศึกษา") 
        ORDER BY s.student_code ASC
    ');
    $stmt->execute([$classroom_id, $cls_level, $cls_room, $clean_room, $clean_room, $academic_year]);
    $students = $stmt->fetchAll();

    // 3. ดึงข้อมูลการมาเรียนที่บันทึกไว้แล้ว
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE classroom_id = ? AND check_date = ?');
    $stmt->execute([$classroom_id, $check_date]);
    $attendance_data = $stmt->fetchAll();

    foreach ($attendance_data as &$ad) {
        if ($ad['activity_type']) {
            $ad['subject_id'] = 'LD:' . $ad['activity_type'];
        }
        // ตรวจสอบว่า subject_id ไม่เป็น null เพื่อป้องกัน JS error
        if ($ad['subject_id'] === null) {
            $ad['subject_id'] = 'none';
        }
    }

    echo json_encode([
        'subjects' => $subjects,
        'students' => $students,
        'attendance' => $attendance_data,
        'debug' => [
            'classroom_id' => $classroom_id,
            'day_of_week' => $day_of_week,
            'student_count' => count($students),
            'subject_count' => count($subjects)
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
