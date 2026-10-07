<?php
$type = $_GET['type'] ?? 'subject';
$classroom_id = $_GET['classroom_id'] ?? '';

if ($type === 'class' && $classroom_id) {
    include 'p5_classroom_cover.php';
    exit;
}

require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$assignment_id = $_GET['assignment_id'] ?? '';

$subject_name = '';
$subject_code = '';
$subject_hours = '';
$learning_area = '';
$level_name = '';
$room_name = '';
$teacher_name = '';
$teacher_position = '';
$class_teacher_name = '';
$class_teacher_position = '';

if ($type === 'subject' && $assignment_id) {
    try {
        $stmt = $pdo->prepare('
            SELECT ta.*, s.name as subject_name, s.code as subject_code, s.hours, s.learning_area,
                   c.level, c.room, u.name as teacher_name, u.last_name as teacher_last, u.position as teacher_pos,
                   c.id as cid
            FROM teacher_assignments ta
            JOIN subjects s ON ta.subject_id = s.id
            JOIN classrooms c ON ta.classroom_id = c.id
            JOIN users u ON ta.teacher_id = u.id
            WHERE ta.id = ?
        ');
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        $select_learning_area = (strpos($error_msg, 'learning_area') !== false) ? '"" as learning_area' : 's.learning_area';
        $select_last_name = (strpos($error_msg, 'last_name') !== false) ? '"" as teacher_last' : 'u.last_name as teacher_last';

        if (strpos($error_msg, 'learning_area') !== false || strpos($error_msg, 'last_name') !== false) {
            $stmt = $pdo->prepare("
                SELECT ta.*, s.name as subject_name, s.code as subject_code, s.hours, $select_learning_area,
                       c.level, c.room, u.name as teacher_name, $select_last_name, u.position as teacher_pos,
                       c.id as cid
                FROM teacher_assignments ta
                JOIN subjects s ON ta.subject_id = s.id
                JOIN classrooms c ON ta.classroom_id = c.id
                JOIN users u ON ta.teacher_id = u.id
                WHERE ta.id = ?
            ");
            $stmt->execute([$assignment_id]);
            $assignment = $stmt->fetch();
        } else {
            die('<div style="padding: 20px; color: red; border: 1px solid red; margin: 20px;">
                    <h3>เกิดข้อผิดพลาดในการดึงข้อมูล</h3>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>กรุณาติดต่อผู้ดูแลระบบเพื่อตรวจสอบฐานข้อมูล</p>
                 </div>');
        }
    }
    
    if ($assignment) {
        $subject_name = $assignment['subject_name'];
        $subject_code = $assignment['subject_code'];
        $subject_hours = $assignment['hours'];
        $learning_area = $assignment['learning_area'];
        $level_name = formatLevelName($assignment['level']);
        $room_name = $assignment['room'];
        $teacher_name = $assignment['teacher_name'] . ' ' . $assignment['teacher_last'];
        $teacher_position = formatTeacherPosition($assignment['teacher_pos']);
        $classroom_id = $assignment['cid'];
        $subject_id = $assignment['subject_id'];

        // ดึงชื่อครูประจำชั้น
        $stmt_ct = $pdo->prepare('
            SELECT u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos,
                   u2.name as t2_name, u2.last_name as t2_last, u2.position as t2_pos
            FROM classrooms c
            LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
            LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
            WHERE c.id = ?
        ');
        $stmt_ct->execute([$classroom_id]);
        $ct = $stmt_ct->fetch();
        $class_teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
        if ($ct['t2_name']) {
            $class_teacher_name .= ($class_teacher_name ? ' / ' : '') . $ct['t2_name'] . ' ' . $ct['t2_last'];
        }
        $class_teacher_position = formatTeacherPosition($ct['t1_pos'] ?? '');

        // Fallback to Learner Development assignment if no classroom teacher is set in the classrooms table
        if (!$class_teacher_name) {
            $stmt_ld = $pdo->prepare('
                SELECT u.name, u.last_name, u.position
                FROM learner_development_assignments lda
                JOIN users u ON lda.teacher_id = u.id
                WHERE lda.classroom_id = ? AND lda.academic_year = ?
                ORDER BY lda.id ASC
            ');
            $stmt_ld->execute([$classroom_id, $year]);
            $ld_teachers = $stmt_ld->fetchAll();
            if (isset($ld_teachers[0])) {
                $class_teacher_name = $ld_teachers[0]['name'] . ' ' . $ld_teachers[0]['last_name'];
                $class_teacher_position = formatTeacherPosition($ld_teachers[0]['position'] ?? '');
            }
            if (isset($ld_teachers[1])) {
                $class_teacher_name .= ($class_teacher_name ? ' / ' : '') . $ld_teachers[1]['name'] . ' ' . $ld_teachers[1]['last_name'];
            }
        }
    }
} else if ($classroom_id) {
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    if ($classroom) {
        $level_name = formatLevelName($classroom['level']);
        $room_name = $classroom['room'];
        
        // ดึงชื่อครูประจำชั้น
        $stmt_t = $pdo->prepare('
            SELECT u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos,
                   u2.name as t2_name, u2.last_name as t2_last, u2.position as t2_pos
            FROM classrooms c
            LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
            LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
            WHERE c.id = ?
        ');
        $stmt_t->execute([$classroom_id]);
        $ct = $stmt_t->fetch();
        $class_teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
        if ($ct['t2_name']) {
            $class_teacher_name .= ($class_teacher_name ? ' / ' : '') . $ct['t2_name'] . ' ' . $ct['t2_last'];
        }
        $class_teacher_position = formatTeacherPosition($ct['t1_pos'] ?? '');

        // Fallback to Learner Development assignment if no classroom teacher is set in the classrooms table
        if (!$class_teacher_name) {
            $stmt_ld = $pdo->prepare('
                SELECT u.name, u.last_name, u.position
                FROM learner_development_assignments lda
                JOIN users u ON lda.teacher_id = u.id
                WHERE lda.classroom_id = ? AND lda.academic_year = ?
                ORDER BY lda.id ASC
            ');
            $stmt_ld->execute([$classroom_id, $year]);
            $ld_teachers = $stmt_ld->fetchAll();
            if (isset($ld_teachers[0])) {
                $class_teacher_name = $ld_teachers[0]['name'] . ' ' . $ld_teachers[0]['last_name'];
                $class_teacher_position = formatTeacherPosition($ld_teachers[0]['position'] ?? '');
            }
            if (isset($ld_teachers[1])) {
                $class_teacher_name .= ($class_teacher_name ? ' / ' : '') . $ld_teachers[1]['name'] . ' ' . $ld_teachers[1]['last_name'];
            }
        }

        $teacher_name = $class_teacher_name;
        $teacher_position = $class_teacher_position;
    }
}

// 1. สถิตินักเรียน
$male_count = 0;
$female_count = 0;
$total_count = 0;
$student_ids = [];
$students_for_cover = [];

if (isset($students) && is_array($students) && !empty($students)) {
    $students_for_cover = $students;
} else if ($classroom_id || (!empty($assignment) && (!empty($assignment['level']) || !empty($assignment['classroom_level']) || !empty($assignment['subject_level'])))) {
    $raw_c_lvl = $assignment['classroom_level'] ?? ($assignment['level'] ?? ($assignment['subject_level'] ?? ($classroom['level'] ?? '')));
    $raw_c_rm = $assignment['classroom_room'] ?? ($assignment['room'] ?? ($classroom['room'] ?? ''));
    $lvl_vars = function_exists('getLevelVariants') ? getLevelVariants($raw_c_lvl) : [$raw_c_lvl];
    $rm_vars = function_exists('getRoomVariants') ? getRoomVariants($raw_c_rm) : [$raw_c_rm];

    $where_parts = [];
    $params = [];

    if (!empty($lvl_vars)) {
        $lvl_in = implode(',', array_fill(0, count($lvl_vars), '?'));
        $where_parts[] = "s.level IN ($lvl_in)";
        $params = array_merge($params, $lvl_vars);
    }

    if ($classroom_id > 0 && !empty($rm_vars)) {
        $rm_in = implode(',', array_fill(0, count($rm_vars), '?'));
        $where_parts[] = "(s.classroom_id = ? OR s.room IN ($rm_in))";
        $params[] = $classroom_id;
        $params = array_merge($params, $rm_vars);
    } else if ($classroom_id > 0) {
        $where_parts[] = "s.classroom_id = ?";
        $params[] = $classroom_id;
    } else if (!empty($rm_vars)) {
        $rm_in = implode(',', array_fill(0, count($rm_vars), '?'));
        $where_parts[] = "s.room IN ($rm_in)";
        $params = array_merge($params, $rm_vars);
    }

    if (!empty($school_id)) {
        $where_parts[] = "(s.school_id = ? OR s.school_id = 0 OR s.school_id IS NULL)";
        $params[] = $school_id;
    }

    if (!empty($year)) {
        $where_parts[] = "(s.academic_year = ? OR s.academic_year IS NULL OR s.academic_year = '')";
        $params[] = $year;
    }

    $where_parts[] = "(s.status = 'studying' OR s.status IS NULL OR s.status = '' OR s.status = 'กำลังศึกษา')";

    $query_std = "
        SELECT s.id, s.gender, s.prefix, sp.gender as sp_gender, sp.prefix as sp_prefix
        FROM students s
        LEFT JOIN student_profiles sp ON s.student_profile_id = sp.id
        WHERE " . implode(' AND ', $where_parts) . "
        ORDER BY s.student_code ASC
    ";
    try {
        $stmt_stats = $pdo->prepare($query_std);
        $stmt_stats->execute($params);
        $students_for_cover = $stmt_stats->fetchAll();

        if (empty($students_for_cover) && !empty($lvl_vars)) {
            // Fallback without academic_year, strictly enforcing grade level
            $where_fb = ["s.level IN (" . implode(',', array_fill(0, count($lvl_vars), '?')) . ")"];
            $params_fb = $lvl_vars;
            if ($classroom_id > 0 && !empty($rm_vars)) {
                $where_fb[] = "(s.classroom_id = ? OR s.room IN (" . implode(',', array_fill(0, count($rm_vars), '?')) . "))";
                $params_fb[] = $classroom_id;
                $params_fb = array_merge($params_fb, $rm_vars);
            } else if ($classroom_id > 0) {
                $where_fb[] = "s.classroom_id = ?";
                $params_fb[] = $classroom_id;
            } else if (!empty($rm_vars)) {
                $where_fb[] = "s.room IN (" . implode(',', array_fill(0, count($rm_vars), '?')) . ")";
                $params_fb = array_merge($params_fb, $rm_vars);
            }
            if (!empty($school_id)) {
                $where_fb[] = "(s.school_id = ? OR s.school_id = 0 OR s.school_id IS NULL)";
                $params_fb[] = $school_id;
            }
            $where_fb[] = "(s.status = 'studying' OR s.status IS NULL OR s.status = '' OR s.status = 'กำลังศึกษา')";
            $stmt_fb = $pdo->prepare("SELECT s.id, s.gender, s.prefix, sp.gender as sp_gender, sp.prefix as sp_prefix FROM students s LEFT JOIN student_profiles sp ON s.student_profile_id = sp.id WHERE " . implode(' AND ', $where_fb) . " ORDER BY s.student_code ASC");
            $stmt_fb->execute($params_fb);
            $students_for_cover = $stmt_fb->fetchAll();
        }
    } catch (Exception $e) {}
}

foreach ($students_for_cover as $row) {
    $student_ids[] = $row['id'];
    $g = trim($row['sp_gender'] ?? ($row['gender'] ?? ''));
    $p = trim($row['sp_prefix'] ?? ($row['prefix'] ?? ''));
    $is_female = ($g === 'หญิง' || $g === 'female' || $g === 'f' || $g === '2' || 
                  strpos($p, 'หญิง') !== false || strpos($p, 'ด.ญ.') !== false || strpos($p, 'ด.ญ') !== false || strpos($p, 'นาง') !== false || strpos($p, 'น.ส.') !== false);
    if ($is_female) {
        $female_count++;
    } else {
        $male_count++;
    }
}
$total_count = count($students_for_cover);

// 2. สรุปผลสัมฤทธิ์ (ถ้าเป็นรายวิชา)
$grade_dist = array_fill_keys(['4', '3.5', '3', '2.5', '2', '1.5', '1', '0', 'ร', 'มส'], 0);
if ($type === 'subject' && isset($subject_id)) {
    try {
        if ($semester === 'annual') {
            $primary_grading_mode = $school['primary_grading_mode'] ?? 'average';
            $stmt_g = $pdo->prepare("
                SELECT student_id, semester, score_total, score_percent, grade 
                FROM grades 
                WHERE subject_id = ? AND academic_year = ?
            ");
            $stmt_g->execute([$subject_id, $year]);
            $rows = $stmt_g->fetchAll();
            $student_records = [];
            foreach ($rows as $r) {
                if (empty($student_ids) || in_array($r['student_id'], $student_ids)) {
                    $student_records[$r['student_id']][$r['semester']] = $r;
                }
            }
            foreach ($student_records as $sid => $sems) {
                $g1 = $sems[1] ?? null;
                $g2 = $sems[2] ?? null;
                $s1_t = $g1 ? (float)($g1['score_total'] !== null ? $g1['score_total'] : $g1['score_percent']) : 0;
                $s2_t = $g2 ? (float)($g2['score_total'] !== null ? $g2['score_total'] : $g2['score_percent']) : 0;

                if ($primary_grading_mode === 'sum') {
                    $final_pct = $s1_t + $s2_t;
                } else {
                    $final_pct = ($s1_t + $s2_t) / 2;
                }

                $calc_grade = '0';
                if ($final_pct >= 80) $calc_grade = '4';
                else if ($final_pct >= 75) $calc_grade = '3.5';
                else if ($final_pct >= 70) $calc_grade = '3';
                else if ($final_pct >= 65) $calc_grade = '2.5';
                else if ($final_pct >= 60) $calc_grade = '2';
                else if ($final_pct >= 55) $calc_grade = '1.5';
                else if ($final_pct >= 50) $calc_grade = '1';

                if (($g1 && $g1['grade'] === 'ร') || ($g2 && $g2['grade'] === 'ร')) $calc_grade = 'ร';
                if (($g1 && $g1['grade'] === 'มส') || ($g2 && $g2['grade'] === 'มส')) $calc_grade = 'มส';

                if (isset($grade_dist[$calc_grade])) {
                    $grade_dist[$calc_grade]++;
                }
            }
        } else {
            if (!empty($student_ids)) {
                $in_placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                $stmt_grades = $pdo->prepare("
                    SELECT grade, COUNT(*) as count 
                    FROM grades 
                    WHERE subject_id = ? AND academic_year = ? AND semester = ? AND student_id IN ($in_placeholders) 
                    GROUP BY grade
                ");
                $stmt_grades->execute(array_merge([$subject_id, $year, $semester], $student_ids));
            } else {
                $stmt_grades = $pdo->prepare("
                    SELECT grade, COUNT(*) as count 
                    FROM grades 
                    WHERE subject_id = ? AND academic_year = ? AND semester = ? 
                    AND (classroom_id = ? OR classroom_id = 0 OR classroom_id IS NULL)
                    GROUP BY grade
                ");
                $stmt_grades->execute([$subject_id, $year, $semester, $classroom_id]);
            }
            while ($row = $stmt_grades->fetch()) {
                if (isset($grade_dist[$row['grade']])) $grade_dist[$row['grade']] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {}
}

// 3. สรุปคุณลักษณะฯ และ อ่านคิดวิเคราะห์
$char_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$anal_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$comp_dist = array_fill_keys(['0', '1', '2', '3'], 0);

if ($type === 'subject' && isset($subject_id)) {
    try {
        $sem_cond = ($semester === 'annual') ? "IN (1, 2)" : "= ?";
        $sem_p = ($semester === 'annual') ? [] : [$semester];

        if (!empty($student_ids)) {
            $in_p = implode(',', array_fill(0, count($student_ids), '?'));
            
            // คุณลักษณะ
            $stmt_c = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM characteristics_scores WHERE subject_id = ? AND academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
            $stmt_c->execute(array_merge([$subject_id, $year], $sem_p, $student_ids));
            while ($row = $stmt_c->fetch()) {
                $s = (string)round($row['score']);
                if (isset($char_dist[$s])) $char_dist[$s] = (int)$row['count'];
            }

            // อ่านคิดวิเคราะห์
            $stmt_a = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM analytical_scores WHERE subject_id = ? AND academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
            $stmt_a->execute(array_merge([$subject_id, $year], $sem_p, $student_ids));
            while ($row = $stmt_a->fetch()) {
                $s = (string)round($row['score']);
                if (isset($anal_dist[$s])) $anal_dist[$s] = (int)$row['count'];
            }
        } else {
            $stmt_c = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM characteristics_scores WHERE subject_id = ? AND academic_year = ? AND semester $sem_cond AND (classroom_id = ? OR classroom_id = 0) GROUP BY score");
            $stmt_c->execute(array_merge([$subject_id, $year], $sem_p, [$classroom_id]));
            while ($row = $stmt_c->fetch()) {
                $s = (string)round($row['score']);
                if (isset($char_dist[$s])) $char_dist[$s] = (int)$row['count'];
            }

            $stmt_a = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM analytical_scores WHERE subject_id = ? AND academic_year = ? AND semester $sem_cond AND (classroom_id = ? OR classroom_id = 0) GROUP BY score");
            $stmt_a->execute(array_merge([$subject_id, $year], $sem_p, [$classroom_id]));
            while ($row = $stmt_a->fetch()) {
                $s = (string)round($row['score']);
                if (isset($anal_dist[$s])) $anal_dist[$s] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {}
}

// สมรรถนะสำคัญ
try {
    $sem_cond = ($semester === 'annual') ? "IN (1, 2)" : "= ?";
    $sem_p = ($semester === 'annual') ? [] : [$semester];
    if (!empty($student_ids)) {
        $in_p = implode(',', array_fill(0, count($student_ids), '?'));
        $stmt_comp = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM competency_scores WHERE academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
        $stmt_comp->execute(array_merge([$year], $sem_p, $student_ids));
    } else {
        $stmt_comp = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM competency_scores WHERE classroom_id = ? AND academic_year = ? AND semester $sem_cond GROUP BY score");
        $stmt_comp->execute(array_merge([$classroom_id, $year], $sem_p));
    }
    while ($row = $stmt_comp->fetch()) {
        $s = (string)round($row['score']);
        if (isset($comp_dist[$s])) $comp_dist[$s] = (int)$row['count'];
    }
} catch (Exception $e) {}

$approval_date_raw = $_GET['approval_date'] ?? '';
$approval_date = formatThaiDate($approval_date_raw);

?>

<style>
    /* --- ปรับขอบกระดาษ (บน ขวา ล่าง ซ้าย) --- */
    .page {
        padding-top: 10mm !important;    /* ปรับระยะขอบบน */
        padding-bottom: 10mm !important; /* ปรับระยะขอบล่าง */
        padding-left: 15mm !important;   /* ปรับระยะขอบซ้าย */
        padding-right: 15mm !important;  /* ปรับระยะขอบขวา */
    }

    /* --- พื้นที่หลักของหน้าปก --- */
    .cover-container {
        padding: 0;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    /* --- โลโก้โรงเรียน --- */
    .logo-container {
        text-align: center;
        margin-bottom: 5px; /* ปรับระยะห่างใต้โลโก้ */
    }
    .logo-img {
        width: 120px; /* ปรับขนาดความกว้างโลโก้ */
        height: 120px; /* ปรับขนาดความสูงโลโก้ */
        object-fit: contain;
    }
    /* --- หัวข้อรายงาน --- */
    .main-title {
        font-size: 20px; /* ปรับขนาดตัวอักษรหัวข้อ ปพ.5 */
        font-weight: bold;
        text-align: center;
        margin-bottom: 2px;
    }
    .school-name {
        font-size: 18px; /* ปรับขนาดชื่อโรงเรียน */
        font-weight: bold;
        text-align: center;
        margin-bottom: 2px;
    }
    .affiliation {
        font-size: 16px; /* ปรับขนาดชื่อสังกัด */
        text-align: center;
        margin-bottom: 10px;
    }
    
    /* --- แถวข้อมูลทั่วไป (ชั้น, ภาคเรียน, วิชา ฯลฯ) --- */
    .flex-row {
        display: flex;
        align-items: baseline;
        font-size: 16px; /* ปรับขนาดตัวอักษรแถวข้อมูล */
        margin-bottom: 5px; /* ปรับระยะห่างระหว่างแถว */
        width: 100%;
        white-space: nowrap;
    }
    .flex-fill {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #666; /* ปรับลักษณะเส้นจุดไข่ปลา (0.5pt คือความหนา) */
        margin: 0 5px;
        text-align: center;
        min-height: 1.2em;
    }
    .flex-fixed {
        flex-shrink: 0;
    }

    /* --- ส่วนชื่อครูประจำวิชา/ประจำชั้น --- */
    .teacher-section {
        margin: 8px 0; /* ปรับระยะห่างบน-ล่าง ของส่วนชื่อครู */
        width: 100%;
    }
    .teacher-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 6px;
        font-size: 16px;
    }
    .teacher-label {
        width: 180px;
        text-align: left;
        padding-left: 10px;
        flex-shrink: 0;
    }
    .teacher-dotted {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #666; /* เส้นจุดไข่ปลาชื่อครู */
        text-align: center;
    }

    /* --- หัวข้อส่วนต่างๆ (สรุปผลสัมฤทธิ์ ฯลฯ) --- */
    .section-title {
        font-weight: bold;
        text-align: center;
        margin: 8px 0 4px 0; /* ปรับระยะห่างหัวข้อส่วน */
        text-decoration: underline;
        font-size: 16px;
    }
    /* --- ตารางสถิตินักเรียน --- */
    .stats-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        border: 1px solid #000;
    }
    .stats-table td {
        border: 1px solid #000;
        padding: 4px 8px; /* ปรับความกว้างช่องในตารางสถิติ */
        font-size: 16px;
        white-space: nowrap;
    }
    /* --- ตารางสรุปผลการเรียน --- */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .summary-table th, .summary-table td {
        border: 1px solid #000;
        padding: 3px;
        font-size: 14px; /* ปรับขนาดตัวเลขในตารางเกรด */
        white-space: nowrap;
    }
    /* --- ส่วนการอนุมัติ (ท้ายหน้า) --- */
    .approval-section {
        margin-top: auto;
        padding-top: 5px;
    }
    .signature-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 5px 0;
    }
    .signature-item-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin-bottom: 12px; /* ปรับระยะห่างระหว่างชุดลงชื่อ */
    }
    .signature-row {
        display: grid;
        grid-template-columns: 1fr 250px 1fr;
        align-items: baseline;
        width: 100%;
    }
    .sig-label {
        text-align: right;
        padding-right: 10px;
        font-size: 14px;
    }
    .sig-dotted {
        width: 250px;
        border-bottom: 0.5pt dotted #666; /* เส้นลงชื่อ */
    }
    .sig-pos {
        text-align: left;
        padding-left: 10px;
        font-size: 16px;
    }
    .sig-name {
        width: 100%;
        text-align: center;
        font-weight: bold;
        margin-top: 2px;
        font-size: 16px; /* ปรับขนาดชื่อในวงเล็บ */
    }
    /* --- ช่องติ๊ก อนุมัติ/ไม่อนุมัติ --- */
    .approval-box {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 8px 0;
        justify-content: center;
        font-size: 14px;
    }
    .check-box {
        width: 14px;
        height: 14px;
        border: 1px solid #000;
        display: inline-block;
    }
</style>

<div class="page">
    <div class="cover-container">
        <div class="logo-container">
            <img src="<?= !empty($logo_url) ? $logo_url : $garuda_url ?>" class="logo-img" referrerPolicy="no-referrer">
        </div>

        <div class="main-title">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.๕)</div>
        <div class="school-name">โรงเรียน<?= $school_name ?></div>
        <div class="affiliation"><?= $affiliation ?></div>

        <div class="flex-row">
            <div class="flex-fixed">ชั้น</div>
            <div class="flex-fill"><?= $level_name ?>/<?= $room_name ?></div>
            <?php if ($semester !== 'annual'): ?>
                <div class="flex-fixed">ภาคเรียนที่</div>
                <div class="flex-fill"><?= $semester ?></div>
            <?php endif; ?>
            <div class="flex-fixed">ปีการศึกษา</div>
            <div class="flex-fill"><?= $year ?></div>
        </div>

        <div class="flex-row">
            <div class="flex-fixed">รายวิชา</div>
            <div class="flex-fill"><?= $subject_name ?></div>
            <div class="flex-fixed">รหัส</div>
            <div class="flex-fill"><?= $subject_code ?></div>
            <div class="flex-fixed">เวลาเรียน</div>
            <div class="flex-fill"><?= $subject_hours ?></div>
            <div class="flex-fixed">ชั่วโมง/ปี</div>
        </div>

        <div class="flex-row">
            <div class="flex-fixed">กลุ่มสาระการเรียนรู้</div>
            <div class="flex-fill"><?= $learning_area ?></div>
        </div>

        <div class="teacher-section">
            <div class="teacher-row">
                <div class="teacher-dotted"><?= $teacher_name ?></div>
                <div class="teacher-label">ครูประจำวิชา</div>
            </div>
            <div class="teacher-row">
                <div class="teacher-dotted"><?= $class_teacher_name ?></div>
                <div class="teacher-label">ครูที่ปรึกษา/ครูประจำชั้น</div>
            </div>
        </div>

        <table class="stats-table">
            <tr>
                <td class="font-bold" style="font-size: 16px;">จำนวนนักเรียนทั้งหมด</td>
                <td style="text-align: center;">ชาย <span style="display: inline-block; width: 50px; border-bottom: 1px dotted #000;"><?= $male_count ?></span> คน</td>
                <td style="text-align: center;">หญิง <span style="display: inline-block; width: 50px; border-bottom: 1px dotted #000;"><?= $female_count ?></span> คน</td>
                <td style="text-align: center;">รวม <span style="display: inline-block; width: 50px; border-bottom: 1px dotted #000;"><?= $total_count ?></span> คน</td>
            </tr>
        </table>

        <div class="section-title">สรุปผลสัมฤทธิ์ทางการเรียนรู้</div>
        <table class="summary-table">
            <tr style="background-color: #f8fafc;">
                <th width="15%">ระดับ</th>
                <th>มส</th>
                <th>ร</th>
                <th>0</th>
                <th>1</th>
                <th>1.5</th>
                <th>2</th>
                <th>2.5</th>
                <th>3</th>
                <th>3.5</th>
                <th>4</th>
            </tr>
            <tr>
                <td class="font-bold" style="text-align: center;">จำนวนนักเรียน</td>
                <td style="text-align: center;"><?= $grade_dist['มส'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['ร'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['0'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['1'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['1.5'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['2'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['2.5'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['3'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['3.5'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $grade_dist['4'] ?: '-' ?></td>
            </tr>
        </table>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <table class="summary-table">
                    <tr style="background-color: #f8fafc;">
                        <th rowspan="2">สรุปการประเมิน</th>
                        <th colspan="4">คุณลักษณะอันพึงประสงค์</th>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <th width="20%">ไม่ผ่าน</th>
                        <th width="20%">ผ่าน</th>
                        <th width="20%">ดี</th>
                        <th width="20%">ดีเยี่ยม</th>
                    </tr>
                    <tr>
                        <td class="font-bold" style="text-align: center;">จำนวนนักเรียน</td>
                        <td style="text-align: center;"><?= $char_dist['0'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $char_dist['1'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $char_dist['2'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $char_dist['3'] ?: '-' ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="summary-table">
                    <tr style="background-color: #f8fafc;">
                        <th rowspan="2">สรุปการประเมิน</th>
                        <th colspan="4">การอ่าน คิดวิเคราะห์ และเขียน</th>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <th width="20%">ไม่ผ่าน</th>
                        <th width="20%">ผ่าน</th>
                        <th width="20%">ดี</th>
                        <th width="20%">ดีเยี่ยม</th>
                    </tr>
                    <tr>
                        <td class="font-bold" style="text-align: center;">จำนวนนักเรียน</td>
                        <td style="text-align: center;"><?= $anal_dist['0'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $anal_dist['1'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $anal_dist['2'] ?: '-' ?></td>
                        <td style="text-align: center;"><?= $anal_dist['3'] ?: '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="summary-table">
            <tr style="background-color: #f8fafc;">
                <th rowspan="2" width="25%">สรุปการประเมิน</th>
                <th colspan="4">สมรรถนะสำคัญของผู้เรียน</th>
            </tr>
            <tr style="background-color: #f8fafc;">
                <th>ปรับปรุง</th>
                <th>พอใช้</th>
                <th>ดี</th>
                <th>ดีเยี่ยม</th>
            </tr>
            <tr>
                <td class="font-bold" style="text-align: center;">จำนวนนักเรียน</td>
                <td style="text-align: center;"><?= $comp_dist['0'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $comp_dist['1'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $comp_dist['2'] ?: '-' ?></td>
                <td style="text-align: center;"><?= $comp_dist['3'] ?: '-' ?></td>
            </tr>
        </table>

        <div class="approval-section">
            <div class="section-title" style="text-decoration: none; margin-bottom: 20px;">การอนุมัติผลการเรียน</div>
            
            <div class="signature-group">
                <div class="signature-item-container">
                    <div class="signature-row">
                        <div class="sig-label">ลงชื่อ</div>
                        <div class="sig-dotted"></div>
                        <div class="sig-pos">ครูประจำวิชา</div>
                    </div>
                    <div class="sig-name">( <?= $teacher_name ?> )</div>
                </div>

                <?php if ($deputy_director_name): ?>
                    <div class="signature-item-container">
                        <div class="signature-row">
                            <div class="sig-label">ลงชื่อ</div>
                            <div class="sig-dotted"></div>
                            <div class="sig-pos"><?= $deputy_director_position ?></div>
                        </div>
                        <div class="sig-name">( <?= $deputy_director_name ?> )</div>
                    </div>
                <?php else: ?>
                    <div class="signature-item-container">
                        <div class="signature-row">
                            <div class="sig-label">ลงชื่อ</div>
                            <div class="sig-dotted"></div>
                            <div class="sig-pos"><?= $academic_head_position ?></div>
                        </div>
                        <div class="sig-name">( <?= $academic_head_name ?: '..........................................................' ?> )</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="approval-box">
                <div class="check-box"></div> อนุมัติ
                <div style="width: 40px;"></div>
                <div class="check-box"></div> ไม่อนุมัติ
            </div>

            <div style="text-align: center; margin-top: 40px;">
				<p style="margin-bottom: 5px;visibility:hidden;">..........................................................</p>                
                <p class="font-bold" style="font-size: 16px;">( <?= $director_name ?: '..........................................................' ?> )</p>
                <p style="font-size: 16px;">ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
                <p style="margin-top: 16px;">วันที่ <span style="display: inline-block; width: 40px; border-bottom: 1px dotted #000;"><?= $approval_date['day'] ?: '&nbsp;' ?></span> เดือน <span style="display: inline-block; width: 120px; border-bottom: 1px dotted #000;"><?= $approval_date['month'] ?: '&nbsp;' ?></span> พ.ศ. <span style="display: inline-block; width: 60px; border-bottom: 1px dotted #000;"><?= $approval_date['year'] ?: '&nbsp;' ?></span></p>
            </div>
        </div>
    </div>
</div>

<?php if (!isset($no_footer)): ?>
</body>
</html>
<?php endif; ?>
