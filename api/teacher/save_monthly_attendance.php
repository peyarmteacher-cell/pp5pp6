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
$data = json_decode(file_get_contents('php://input'), true);

$classroom_id = $data['classroom_id'] ?? null;
$subject_id = $data['subject_id'] ?? null;
$academic_year = $data['academic_year'] ?? '';
$semester = $data['semester'] ?? 1;
$month_str = $data['month'] ?? ''; // e.g. 2026-07
$sessions = $data['sessions'] ?? []; // [{ date: '2026-07-02', period_number: 1 }]
$students = $data['students'] ?? []; // [{ id: 1, student_code: '...' }]
$overrides = $data['overrides'] ?? []; // { student_id: { absent_count: 0, leave_count: 0, sick_count: 0 } }

if (!$classroom_id || !$subject_id || empty($sessions) || empty($students)) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$is_ld_activity = (strpos($subject_id, 'LD:') === 0);
$clean_activity_type = $is_ld_activity ? substr($subject_id, 3) : null;
$clean_subject_id = $is_ld_activity ? null : (int)$subject_id;

// ทำความสะอาดวันที่ในแต่ละ session ให้เป็น ค.ศ. YYYY-MM-DD
foreach ($sessions as &$sess) {
    $d_parts = explode('-', $sess['date']);
    $y = (int)$d_parts[0];
    $m = (int)($d_parts[1] ?? 1);
    $d = (int)($d_parts[2] ?? 1);
    if ($y > 2400) {
        $y -= 543;
    }
    $sess['date'] = sprintf('%04d-%02d-%02d', $y, $m, $d);
}
unset($sess);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO attendance 
        (student_id, subject_id, activity_type, classroom_id, academic_year, semester, check_date, period_number, status, teacher_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status),
        teacher_id = VALUES(teacher_id)
    ');

    $inserted_count = 0;

    foreach ($students as $std) {
        $sid = $std['id'];
        $std_overrides = $overrides[$sid] ?? null;
        
        $absent_count = (int)($std_overrides['absent_count'] ?? 0);
        $leave_count = (int)($std_overrides['leave_count'] ?? 0);
        $sick_count = (int)($std_overrides['sick_count'] ?? 0);

        // จัดสรรวันขาด/ลา/ป่วย จากวันท้ายๆ ของเดือน
        $non_present_map = [];
        $total_sessions = count($sessions);
        $curr_idx = $total_sessions - 1;

        for ($i = 0; $i < $absent_count && $curr_idx >= 0; $i++, $curr_idx--) {
            $non_present_map[$curr_idx] = 'absent';
        }
        for ($i = 0; $i < $leave_count && $curr_idx >= 0; $i++, $curr_idx--) {
            $non_present_map[$curr_idx] = 'leave';
        }
        for ($i = 0; $i < $sick_count && $curr_idx >= 0; $i++, $curr_idx--) {
            $non_present_map[$curr_idx] = 'sick';
        }

        foreach ($sessions as $s_idx => $session) {
            $status = $non_present_map[$s_idx] ?? 'present';

            $stmt->execute([
                $sid,
                $clean_subject_id,
                $clean_activity_type,
                $classroom_id,
                $academic_year,
                $semester,
                $session['date'],
                $session['period_number'] ?? 1,
                $status,
                $teacher_id
            ]);
            $inserted_count++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกเวลาเรียนทั้งเดือนเรียบร้อยแล้ว (' . count($students) . ' คน x ' . count($sessions) . ' คาบ)',
        'total_records' => $inserted_count,
        'student_count' => count($students),
        'session_count' => count($sessions)
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()]);
}
