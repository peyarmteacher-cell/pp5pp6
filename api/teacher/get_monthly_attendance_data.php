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
$classroom_id = $_GET['classroom_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;
$month_str = $_GET['month'] ?? date('Y-m'); // Format: YYYY-MM e.g. 2026-07

if (!$classroom_id || !$subject_id) {
    echo json_encode(['error' => 'กรุณาระบุห้องเรียนและรายวิชา']);
    exit;
}

try {
    // 1. ตรวจสอบข้อมูลห้องเรียนและวิชา
    $stmt_cls = $pdo->prepare('SELECT id, level, room FROM classrooms WHERE id = ?');
    $stmt_cls->execute([$classroom_id]);
    $classroom = $stmt_cls->fetch(PDO::FETCH_ASSOC);

    $is_ld_activity = (strpos($subject_id, 'LD:') === 0);
    $activity_type = $is_ld_activity ? substr($subject_id, 3) : null;
    $activity_labels = [
        'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
        'scouts' => ['name' => 'กิจกรรมลูกเสือ/เนตรนารี', 'code' => 'ลูกเสือ'],
        'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
        'social' => ['name' => 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', 'code' => 'กิจกรรมเพื่อสังคมฯ'],
        'reducing_time' => ['name' => 'กิจกรรมลดเวลาเรียน เพิ่มเวลารู้', 'code' => 'ลดเวลาเรียนฯ'],
        'prayer' => ['name' => 'กิจกรรมสวดมนต์', 'code' => 'สวดมนต์']
    ];

    $subject = null;
    if ($is_ld_activity) {
        $act = $activity_labels[$activity_type] ?? null;
        $subject = [
            'id' => $subject_id,
            'code' => $act ? $act['code'] : 'กิจกรรม',
            'name' => $act ? $act['name'] : 'กิจกรรมพัฒนาผู้เรียน'
        ];
    } else {
        $stmt_sub = $pdo->prepare('SELECT id, code, name FROM subjects WHERE id = ?');
        $stmt_sub->execute([$subject_id]);
        $subject = $stmt_sub->fetch(PDO::FETCH_ASSOC);
    }

    // 2. หาวันที่สอนในเดือนนั้นตามตารางสอน (timetables)
    if ($is_ld_activity) {
        $stmt_tt = $pdo->prepare('
            SELECT day_of_week, period_number 
            FROM timetables 
            WHERE classroom_id = ? AND activity_type = ? 
              AND (academic_year = ? OR academic_year IS NULL OR academic_year = "")
              AND (semester = ? OR semester = 0 OR ? = "annual")
            ORDER BY day_of_week ASC, period_number ASC
        ');
        $stmt_tt->execute([$classroom_id, $activity_type, $academic_year, $semester, $semester]);
    } else {
        $stmt_tt = $pdo->prepare('
            SELECT day_of_week, period_number 
            FROM timetables 
            WHERE classroom_id = ? AND subject_id = ? 
              AND (academic_year = ? OR academic_year IS NULL OR academic_year = "")
              AND (semester = ? OR semester = 0 OR ? = "annual")
            ORDER BY day_of_week ASC, period_number ASC
        ');
        $stmt_tt->execute([$classroom_id, $subject_id, $academic_year, $semester, $semester]);
    }
    $tt_slots = $stmt_tt->fetchAll(PDO::FETCH_ASSOC);

    // คำนวณวันทั้งหมดในเดือนนั้น (รองรับทั้ง ค.ศ. และ พ.ศ.)
    $parts = explode('-', $month_str);
    $year = (int)$parts[0];
    $month = (int)($parts[1] ?? 1);
    if ($year > 2400) {
        $year -= 543;
    }
    $normalized_month_str = sprintf('%04d-%02d', $year, $month);
    $buddhist_month_str = sprintf('%04d-%02d', $year + 543, $month);
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $day_names = [
        1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ', 4 => 'พฤหัสบดี',
        5 => 'ศุกร์', 6 => 'เสาร์', 7 => 'อาทิตย์'
    ];

    $teaching_sessions = [];

    if (!empty($tt_slots)) {
        // อ้างอิงตามตารางสอน
        for ($d = 1; $d <= $days_in_month; $d++) {
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow = (int)date('N', strtotime($date_str)); // 1=Mon .. 7=Sun

            foreach ($tt_slots as $slot) {
                if ($slot['day_of_week'] == $dow) {
                    $teaching_sessions[] = [
                        'date' => $date_str,
                        'day' => $d,
                        'dow' => $dow,
                        'day_name' => $day_names[$dow] ?? '',
                        'period_number' => (int)$slot['period_number']
                    ];
                }
            }
        }
    } else {
        // หากยังไม่ได้จัดตารางสอน ใช้วันจันทร์-ศุกร์ (วันเปิดเรียนปกติ)
        for ($d = 1; $d <= $days_in_month; $d++) {
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow = (int)date('N', strtotime($date_str));

            if ($dow <= 5) { // จันทร์ - ศุกร์
                $teaching_sessions[] = [
                    'date' => $date_str,
                    'day' => $d,
                    'dow' => $dow,
                    'day_name' => $day_names[$dow] ?? '',
                    'period_number' => 1
                ];
            }
        }
    }

    $total_sessions = count($teaching_sessions);

    // 3. ดึงรายชื่อนักเรียนในห้องเรียนนี้
    $cls_level = $classroom['level'] ?? '';
    $cls_room = $classroom['room'] ?? '';
    $clean_room = str_replace('ห้อง', '', $cls_room);

    $stmt_std = $pdo->prepare('
        SELECT s.id, s.student_code, 
               IFNULL(sp.prefix, s.prefix) AS prefix, 
               IFNULL(sp.name, s.name) AS name, 
               IFNULL(sp.last_name, s.last_name) AS last_name 
        FROM students s
        LEFT JOIN student_profiles sp ON s.student_profile_id = sp.id
        WHERE (s.classroom_id = ? OR (s.level = ? AND (s.room = ? OR s.room = ? OR REPLACE(s.room, "ห้อง", "") = ?)))
          AND (s.academic_year = ? OR s.academic_year IS NULL OR s.academic_year = "")
          AND (s.status = "studying" OR s.status IS NULL OR s.status = "" OR s.status = "กำลังศึกษา") 
        ORDER BY CAST(s.student_code AS UNSIGNED) ASC, s.student_code ASC, s.id ASC
    ');
    $stmt_std->execute([$classroom_id, $cls_level, $cls_room, $clean_room, $clean_room, $academic_year]);
    $students = $stmt_std->fetchAll(PDO::FETCH_ASSOC);

    // 4. ดึงข้อมูลการมาเรียนที่มีการบันทึกไว้แล้วในเดือนนี้
    if ($is_ld_activity) {
        $stmt_att = $pdo->prepare('
            SELECT student_id, check_date, period_number, status 
            FROM attendance 
            WHERE classroom_id = ? AND activity_type = ? 
              AND (check_date LIKE ? OR check_date LIKE ?)
        ');
        $stmt_att->execute([$classroom_id, $activity_type, $normalized_month_str . '-%', $buddhist_month_str . '-%']);
    } else {
        $stmt_att = $pdo->prepare('
            SELECT student_id, check_date, period_number, status 
            FROM attendance 
            WHERE classroom_id = ? AND subject_id = ? 
              AND (check_date LIKE ? OR check_date LIKE ?)
        ');
        $stmt_att->execute([$classroom_id, $subject_id, $normalized_month_str . '-%', $buddhist_month_str . '-%']);
    }
    $existing_attendance = $stmt_att->fetchAll(PDO::FETCH_ASSOC);

    $student_summary = [];
    foreach ($students as $s) {
        $student_summary[$s['id']] = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'leave' => 0,
            'sick' => 0,
            'total_recorded' => 0
        ];
    }

    foreach ($existing_attendance as $row) {
        $sid = $row['student_id'];
        $st = $row['status'];
        if (isset($student_summary[$sid])) {
            $student_summary[$sid][$st] = ($student_summary[$sid][$st] ?? 0) + 1;
            $student_summary[$sid]['total_recorded']++;
        }
    }

    echo json_encode([
        'classroom' => $classroom,
        'subject' => $subject,
        'month' => $normalized_month_str,
        'total_sessions' => $total_sessions,
        'teaching_sessions' => $teaching_sessions,
        'has_timetable' => !empty($tt_slots),
        'students' => $students,
        'existing_attendance' => $existing_attendance,
        'student_summary' => $student_summary
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
