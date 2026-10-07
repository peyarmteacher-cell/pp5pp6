<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'teacher';
$is_admin = ($user_role === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'] == 1));

$check_date = $_GET['check_date'] ?? date('Y-m-d');
$academic_year = $_GET['academic_year'] ?? '';
$semester = $_GET['semester'] ?? 1;

if (empty($academic_year)) {
    try {
        $stmt_y = $pdo->prepare("SELECT year FROM academic_years WHERE (school_id = ? OR ? = 0) AND is_current = 1 LIMIT 1");
        $stmt_y->execute([$school_id, $school_id]);
        $y_row = $stmt_y->fetch();
        $academic_year = $y_row ? $y_row['year'] : '2567';
    } catch (Exception $e) {
        $academic_year = '2567';
    }
}

// คำนวณวันในสัปดาห์
$timestamp = strtotime($check_date);
$day_of_week = (int)date('N', $timestamp); // 1 = จันทร์, ..., 7 = อาทิตย์
$day_num = (int)date('j', $timestamp);
$month_num = (int)date('n', $timestamp);
$year_num = (int)date('Y', $timestamp);
$buddhist_year = $year_num > 2400 ? $year_num : ($year_num + 543);

$thai_day_names = [
    1 => 'วันจันทร์', 2 => 'วันอังคาร', 3 => 'วันพุธ', 4 => 'วันพฤหัสบดี',
    5 => 'วันศุกร์', 6 => 'วันเสาร์', 7 => 'วันอาทิตย์'
];
$thai_month_names = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$day_name = $thai_day_names[$day_of_week] ?? '';
$month_name = $thai_month_names[$month_num] ?? '';
$formatted_thai_date = "{$day_name}ที่ {$day_num} {$month_name} พ.ศ. {$buddhist_year}";

$activity_labels = [
    'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
    'scouts' => ['name' => 'กิจกรรมลูกเสือ/เนตรนารี', 'code' => 'ลูกเสือ'],
    'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
    'social' => ['name' => 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', 'code' => 'กิจกรรมเพื่อสังคมฯ'],
    'reducing_time' => ['name' => 'กิจกรรมลดเวลาเรียน เพิ่มเวลารู้', 'code' => 'ลดเวลาเรียนฯ'],
    'prayer' => ['name' => 'กิจกรรมสวดมนต์', 'code' => 'สวดมนต์']
];

try {
    // ดึงตารางสอนทั้งหมดของครูในวันนี้
    $sql = "
        SELECT t.id, t.period_number, t.day_of_week, t.subject_id, t.activity_type,
               t.classroom_id, c.level as classroom_level, c.room as classroom_room,
               s.name as subject_name, s.code as subject_code
        FROM timetables t
        JOIN classrooms c ON t.classroom_id = c.id
        LEFT JOIN subjects s ON t.subject_id = s.id
        WHERE (t.teacher_id = ? OR ? = 1)
          AND (t.academic_year = ? OR t.academic_year IS NULL OR t.academic_year = '')
          AND (t.semester = ? OR t.semester = 0 OR t.semester IS NULL OR ? = 'annual')
          AND t.day_of_week = ?
        ORDER BY t.period_number ASC, c.level ASC, c.room ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id, $is_admin ? 1 : 0, $academic_year, $semester, $semester, $day_of_week]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedule = [];
    foreach ($slots as $s) {
        $sub_id = $s['subject_id'];
        $sub_name = $s['subject_name'];
        $sub_code = $s['subject_code'];

        if (!empty($s['activity_type'])) {
            $act = $activity_labels[$s['activity_type']] ?? null;
            if ($act) {
                $sub_name = $act['name'];
                $sub_code = $act['code'];
                $sub_id = 'LD:' . $s['activity_type'];
            }
        }

        $c_name = $s['classroom_level'] . ($s['classroom_room'] ? '/' . str_replace('ห้อง', '', $s['classroom_room']) : '');

        $schedule[] = [
            'id' => $s['id'],
            'period_number' => (int)$s['period_number'],
            'classroom_id' => (int)$s['classroom_id'],
            'classroom_name' => $c_name,
            'level' => $s['classroom_level'],
            'room' => $s['classroom_room'],
            'subject_id' => $sub_id,
            'subject_code' => $sub_code ?: 'ไม่ระบุรหัส',
            'subject_name' => $sub_name ?: 'ไม่ระบุชื่อวิชา',
            'activity_type' => $s['activity_type']
        ];
    }

    echo json_encode([
        'check_date' => $check_date,
        'day_of_week' => $day_of_week,
        'day_name' => $day_name,
        'formatted_thai_date' => $formatted_thai_date,
        'schedule' => $schedule,
        'total_classes' => count($schedule),
        'is_weekend' => ($day_of_week >= 6)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
