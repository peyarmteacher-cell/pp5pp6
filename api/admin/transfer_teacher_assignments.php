<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ']);
    exit;
}

$role = $_SESSION['role'] ?? '';
$is_academic = $_SESSION['is_academic'] ?? false;
$position = $_SESSION['position'] ?? '';
$is_director = (strpos($position, 'ผู้อำนวยการ') !== false);
$school_id = $_SESSION['school_id'] ?? null;

if ($role !== 'admin' && $role !== 'super_admin' && !$is_academic && !$is_director) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์ในการโอนย้ายงานสอน']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$from_teacher_id = $data['from_teacher_id'] ?? null;
$to_teacher_id = $data['to_teacher_id'] ?? null;
$academic_year = $data['academic_year'] ?? null;
$transfer_homeroom = $data['transfer_homeroom'] ?? true;
$transfer_ld = $data['transfer_ld'] ?? true;

if (empty($from_teacher_id) || empty($to_teacher_id)) {
    echo json_encode(['error' => 'กรุณาระบุครูผู้สอนเดิมและครูผู้สอนท่านใหม่ที่จะรับโอนงาน']);
    exit;
}

if ((int)$from_teacher_id === (int)$to_teacher_id) {
    echo json_encode(['error' => 'ครูผู้ส่งมอบและครูผู้รับโอนงานต้องไม่ใช่บุคคลเดียวกัน']);
    exit;
}

try {
    // 1. ตรวจสอบว่าทั้งสองท่านอยู่ในโรงเรียนเดียวกัน
    $stmt_from = $pdo->prepare("SELECT id, name, last_name, school_id FROM users WHERE id = ?");
    $stmt_from->execute([$from_teacher_id]);
    $from_teacher = $stmt_from->fetch(PDO::FETCH_ASSOC);

    $stmt_to = $pdo->prepare("SELECT id, name, last_name, school_id FROM users WHERE id = ?");
    $stmt_to->execute([$to_teacher_id]);
    $to_teacher = $stmt_to->fetch(PDO::FETCH_ASSOC);

    if (!$from_teacher || !$to_teacher) {
        echo json_encode(['error' => 'ไม่พบข้อมูลคุณครูในระบบ']);
        exit;
    }

    if ($role !== 'super_admin') {
        if ($from_teacher['school_id'] != $school_id || $to_teacher['school_id'] != $school_id) {
            http_response_code(403);
            echo json_encode(['error' => 'คุณครูไม่ได้อยู่ในโรงเรียนของคุณ']);
            exit;
        }
    }

    $from_name = $from_teacher['name'] . (!empty($from_teacher['last_name']) ? ' ' . $from_teacher['last_name'] : '');
    $to_name = $to_teacher['name'] . (!empty($to_teacher['last_name']) ? ' ' . $to_teacher['last_name'] : '');

    $pdo->beginTransaction();

    // 2. ดึงงานสอนทั้งหมดของครูเดิม
    $query_ta = "SELECT * FROM teacher_assignments WHERE teacher_id = ?";
    $params_ta = [$from_teacher_id];
    if (!empty($academic_year)) {
        $query_ta .= " AND academic_year = ?";
        $params_ta[] = $academic_year;
    }
    $stmt_ta = $pdo->prepare($query_ta);
    $stmt_ta->execute($params_ta);
    $assignments = $stmt_ta->fetchAll(PDO::FETCH_ASSOC);

    $transferred_courses = 0;
    foreach ($assignments as $a) {
        $sub_id = $a['subject_id'];
        $class_id = $a['classroom_id'];
        $ay = $a['academic_year'];
        $sem = $a['semester'];

        // ตรวจสอบว่าครูท่านใหม่มีงานสอนนี้อยู่แล้วหรือไม่
        if ($class_id !== null) {
            $check = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?");
            $check->execute([$to_teacher_id, $sub_id, $class_id, $ay, $sem]);
        } else {
            $check = $pdo->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND subject_id = ? AND classroom_id IS NULL AND academic_year = ? AND semester = ?");
            $check->execute([$to_teacher_id, $sub_id, $ay, $sem]);
        }
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pdo->prepare("DELETE FROM teacher_assignments WHERE id = ?")->execute([$a['id']]);
        } else {
            $pdo->prepare("UPDATE teacher_assignments SET teacher_id = ? WHERE id = ?")->execute([$to_teacher_id, $a['id']]);
        }
        $transferred_courses++;
    }

    // 3. โอนย้ายคะแนนในตาราง grades
    $query_grades = "UPDATE grades SET teacher_id = ? WHERE teacher_id = ?";
    $params_grades = [$to_teacher_id, $from_teacher_id];
    if (!empty($academic_year)) {
        $query_grades .= " AND academic_year = ?";
        $params_grades[] = $academic_year;
    }
    $stmt_g = $pdo->prepare($query_grades);
    $stmt_g->execute($params_grades);
    $transferred_grades = $stmt_g->rowCount();

    // 4. โอนย้ายคะแนนคุณลักษณะและอ่านคิดวิเคราะห์
    try {
        $query_char = "UPDATE characteristics_scores SET teacher_id = ? WHERE teacher_id = ?";
        $params_char = [$to_teacher_id, $from_teacher_id];
        if (!empty($academic_year)) {
            $query_char .= " AND academic_year = ?";
            $params_char[] = $academic_year;
        }
        $pdo->prepare($query_char)->execute($params_char);
    } catch (Exception $e) {}

    try {
        $query_anal = "UPDATE analytical_scores SET teacher_id = ? WHERE teacher_id = ?";
        $params_anal = [$to_teacher_id, $from_teacher_id];
        if (!empty($academic_year)) {
            $query_anal .= " AND academic_year = ?";
            $params_anal[] = $academic_year;
        }
        $pdo->prepare($query_anal)->execute($params_anal);
    } catch (Exception $e) {}

    // 5. โอนย้าย attendance
    try {
        $query_att = "UPDATE attendance SET teacher_id = ? WHERE teacher_id = ?";
        $params_att = [$to_teacher_id, $from_teacher_id];
        if (!empty($academic_year)) {
            $query_att .= " AND academic_year = ?";
            $params_att[] = $academic_year;
        }
        $pdo->prepare($query_att)->execute($params_att);
    } catch (Exception $e) {}

    // 6. โอนย้ายตารางสอน (timetables)
    try {
        $query_time = "UPDATE IGNORE timetables SET teacher_id = ? WHERE teacher_id = ?";
        $params_time = [$to_teacher_id, $from_teacher_id];
        if (!empty($academic_year)) {
            $query_time .= " AND academic_year = ?";
            $params_time[] = $academic_year;
        }
        $pdo->prepare($query_time)->execute($params_time);
    } catch (Exception $e) {}

    // 7. โอนย้ายกิจกรรมพัฒนาผู้เรียน (ถ้าเลือก)
    if ($transfer_ld) {
        try {
            $query_lda = "SELECT * FROM learner_development_assignments WHERE teacher_id = ?";
            $params_lda = [$from_teacher_id];
            if (!empty($academic_year)) {
                $query_lda .= " AND academic_year = ?";
                $params_lda[] = $academic_year;
            }
            $stmt_lda = $pdo->prepare($query_lda);
            $stmt_lda->execute($params_lda);
            $ld_assignments = $stmt_lda->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ld_assignments as $lda) {
                $chk_ld = $pdo->prepare("SELECT id FROM learner_development_assignments WHERE teacher_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?");
                $chk_ld->execute([$to_teacher_id, $lda['classroom_id'], $lda['academic_year'], $lda['semester']]);
                if ($chk_ld->fetch()) {
                    $pdo->prepare("DELETE FROM learner_development_assignments WHERE id = ?")->execute([$lda['id']]);
                } else {
                    $pdo->prepare("UPDATE learner_development_assignments SET teacher_id = ? WHERE id = ?")->execute([$to_teacher_id, $lda['id']]);
                }
            }

            // อัปเดตผลการประเมินกิจกรรมพัฒนาผู้เรียน
            $query_ldr = "UPDATE learner_development_results SET teacher_id = ? WHERE teacher_id = ?";
            $params_ldr = [$to_teacher_id, $from_teacher_id];
            if (!empty($academic_year)) {
                $query_ldr .= " AND academic_year = ?";
                $params_ldr[] = $academic_year;
            }
            $pdo->prepare($query_ldr)->execute($params_ldr);
        } catch (Exception $e) {}
    }

    // 8. โอนย้ายครูประจำชั้น (classrooms) ถ้าเลือก
    if ($transfer_homeroom) {
        try {
            $pdo->prepare("UPDATE classrooms SET teacher_id_1 = ? WHERE teacher_id_1 = ?")->execute([$to_teacher_id, $from_teacher_id]);
            $pdo->prepare("UPDATE classrooms SET teacher_id_2 = ? WHERE teacher_id_2 = ?")->execute([$to_teacher_id, $from_teacher_id]);
        } catch (Exception $e) {}
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "โอนย้ายภาระงานสอนและคะแนนจาก คุณครู{$from_name} ไปยัง คุณครู{$to_name} สำเร็จเรียบร้อยแล้ว",
        'transferred_courses' => $transferred_courses,
        'transferred_grades' => $transferred_grades
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการโอนย้ายงานสอน: ' . $e->getMessage()]);
}
?>
