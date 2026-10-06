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

// เฉพาะ Admin, Super Admin, งานวิชาการ หรือ ผู้อำนวยการ เท่านั้น
if ($role !== 'admin' && $role !== 'super_admin' && !$is_academic && !$is_director) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์ในการเปลี่ยนครูผู้สอน']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$assignment_id = $data['assignment_id'] ?? null;
$new_teacher_id = $data['new_teacher_id'] ?? null;

if (empty($assignment_id) || empty($new_teacher_id)) {
    echo json_encode(['error' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุรหัสงานสอนและครูผู้สอนท่านใหม่)']);
    exit;
}

try {
    // 1. ดึงข้อมูลงานสอนเดิม พร้อมตรวจสอบสิทธิ์ตามโรงเรียน
    $stmt = $pdo->prepare("
        SELECT ta.*, sub.name as subject_name, sub.code as subject_code, u.name as old_teacher_name, u.school_id as current_school_id
        FROM teacher_assignments ta
        JOIN subjects sub ON ta.subject_id = sub.id
        JOIN users u ON ta.teacher_id = u.id
        WHERE ta.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        echo json_encode(['error' => 'ไม่พบข้อมูลงานสอนที่ระบุ']);
        exit;
    }

    // ตรวจสอบโรงเรียน (ยกเว้น super_admin)
    if ($role !== 'super_admin' && $assignment['current_school_id'] != $school_id) {
        http_response_code(403);
        echo json_encode(['error' => 'งานสอนนี้ไม่ได้อยู่ในโรงเรียนของคุณ']);
        exit;
    }

    $target_school_id = ($role === 'super_admin') ? $assignment['current_school_id'] : $school_id;

    // 2. ตรวจสอบครูผู้สอนท่านใหม่
    $stmt_new = $pdo->prepare("SELECT id, name, last_name, is_approved FROM users WHERE id = ? AND school_id = ?");
    $stmt_new->execute([$new_teacher_id, $target_school_id]);
    $new_teacher = $stmt_new->fetch(PDO::FETCH_ASSOC);

    if (!$new_teacher) {
        echo json_encode(['error' => 'ไม่พบข้อมูลครูผู้สอนท่านใหม่ในโรงเรียนนี้']);
        exit;
    }

    if ((int)$assignment['teacher_id'] === (int)$new_teacher_id) {
        echo json_encode(['error' => 'คุณครูท่านนี้เป็นผู้รับผิดชอบรายวิชานี้อยู่แล้ว']);
        exit;
    }

    $new_teacher_fullname = $new_teacher['name'] . (!empty($new_teacher['last_name']) ? ' ' . $new_teacher['last_name'] : '');

    $pdo->beginTransaction();

    $subject_id = $assignment['subject_id'];
    $classroom_id = $assignment['classroom_id'];
    $academic_year = $assignment['academic_year'];
    $semester = $assignment['semester'];
    $old_teacher_id = $assignment['teacher_id'];

    // 3. ตรวจสอบว่าครูท่านใหม่ได้รับมอบหมายวิชานี้อยู่แล้วหรือไม่
    if ($classroom_id !== null) {
        $stmt_check = $pdo->prepare("
            SELECT id FROM teacher_assignments 
            WHERE teacher_id = ? AND subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
        ");
        $stmt_check->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester]);
    } else {
        $stmt_check = $pdo->prepare("
            SELECT id FROM teacher_assignments 
            WHERE teacher_id = ? AND subject_id = ? AND classroom_id IS NULL AND academic_year = ? AND semester = ?
        ");
        $stmt_check->execute([$new_teacher_id, $subject_id, $academic_year, $semester]);
    }
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // หากครูท่านใหม่มีงานสอนนี้อยู่แล้ว ให้ลบงานสอนเดิมของครูเก่าออกเพื่อไม่ให้ซ้ำซ้อน
        $stmt_del = $pdo->prepare("DELETE FROM teacher_assignments WHERE id = ?");
        $stmt_del->execute([$assignment_id]);
    } else {
        // อัปเดตงานสอนเดิมให้เป็นของครูท่านใหม่
        $stmt_upd = $pdo->prepare("UPDATE teacher_assignments SET teacher_id = ? WHERE id = ?");
        $stmt_upd->execute([$new_teacher_id, $assignment_id]);
    }

    // 4. อัปเดตตาราง grades ที่บันทึกไว้ในรายวิชานี้ให้เป็นของครูท่านใหม่
    if ($classroom_id !== null) {
        $stmt_grades = $pdo->prepare("
            UPDATE grades 
            SET teacher_id = ? 
            WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
        ");
        $stmt_grades->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester]);
    } else {
        $stmt_grades = $pdo->prepare("
            UPDATE grades 
            SET teacher_id = ? 
            WHERE subject_id = ? AND (classroom_id IS NULL OR classroom_id = 0) AND academic_year = ? AND semester = ?
        ");
        $stmt_grades->execute([$new_teacher_id, $subject_id, $academic_year, $semester]);
    }

    // 5. อัปเดต characteristics_scores
    if ($classroom_id !== null) {
        $stmt_char = $pdo->prepare("
            UPDATE characteristics_scores 
            SET teacher_id = ? 
            WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
        ");
        $stmt_char->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester]);
    } else {
        $stmt_char = $pdo->prepare("
            UPDATE characteristics_scores 
            SET teacher_id = ? 
            WHERE subject_id = ? AND (classroom_id IS NULL OR classroom_id = 0) AND academic_year = ? AND semester = ?
        ");
        $stmt_char->execute([$new_teacher_id, $subject_id, $academic_year, $semester]);
    }

    // 6. อัปเดต analytical_scores
    if ($classroom_id !== null) {
        $stmt_anal = $pdo->prepare("
            UPDATE analytical_scores 
            SET teacher_id = ? 
            WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
        ");
        $stmt_anal->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester]);
    } else {
        $stmt_anal = $pdo->prepare("
            UPDATE analytical_scores 
            SET teacher_id = ? 
            WHERE subject_id = ? AND (classroom_id IS NULL OR classroom_id = 0) AND academic_year = ? AND semester = ?
        ");
        $stmt_anal->execute([$new_teacher_id, $subject_id, $academic_year, $semester]);
    }

    // 7. อัปเดต attendance
    if ($classroom_id !== null) {
        $stmt_att = $pdo->prepare("
            UPDATE attendance 
            SET teacher_id = ? 
            WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
        ");
        $stmt_att->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester]);
    } else {
        $stmt_att = $pdo->prepare("
            UPDATE attendance 
            SET teacher_id = ? 
            WHERE subject_id = ? AND (classroom_id IS NULL OR classroom_id = 0) AND academic_year = ? AND semester = ?
        ");
        $stmt_att->execute([$new_teacher_id, $subject_id, $academic_year, $semester]);
    }

    // 8. อัปเดตตารางสอน (timetables) ของวิชานี้ (ถ้ามี)
    try {
        if ($classroom_id !== null) {
            $stmt_time = $pdo->prepare("
                UPDATE timetables 
                SET teacher_id = ? 
                WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ? AND teacher_id = ?
            ");
            $stmt_time->execute([$new_teacher_id, $subject_id, $classroom_id, $academic_year, $semester, $old_teacher_id]);
        }
    } catch (Exception $e_time) {
        // ข้ามหากมีข้อขัดแย้งของตารางสอน
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "เปลี่ยนครูผู้สอนเป็น คุณครู{$new_teacher_fullname} เรียบร้อยแล้ว (คะแนนและข้อมูลที่เคยบันทึกไว้ทั้งหมดถูกโอนไปยังครูท่านใหม่เรียบร้อย)",
        'new_teacher_name' => $new_teacher_fullname
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการเปลี่ยนครูผู้สอน: ' . $e->getMessage()]);
}
?>
