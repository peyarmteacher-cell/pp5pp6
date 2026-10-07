<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$classroom_id = $_GET['classroom_id'] ?? '';

if (!$classroom_id || !$year) {
    die('กรุณาระบุห้องเรียนและปีการศึกษา');
}

// 1. ดึงข้อมูลห้องเรียนและครูประจำชั้น
$stmt = $pdo->prepare('
    SELECT c.*, u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos,
           u2.name as t2_name, u2.last_name as t2_last, u2.position as t2_pos
    FROM classrooms c
    LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
    LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
    WHERE c.id = ?
');
$stmt->execute([$classroom_id]);
$classroom = $stmt->fetch();

if (!$classroom) {
    die('ไม่พบข้อมูลห้องเรียน');
}

$level_name = formatLevelName($classroom['level']);
$room_name = $classroom['room'];
$class_teacher_1 = $classroom['t1_name'] ? $classroom['t1_name'] . ' ' . $classroom['t1_last'] : '';
$class_teacher_2 = $classroom['t2_name'] ? $classroom['t2_name'] . ' ' . $classroom['t2_last'] : '';

// Fallback to Learner Development assignment if no classroom teacher is set in the classrooms table
if (!$class_teacher_1) {
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
        $class_teacher_1 = $ld_teachers[0]['name'] . ' ' . $ld_teachers[0]['last_name'];
    }
    if (isset($ld_teachers[1])) {
        $class_teacher_2 = $ld_teachers[1]['name'] . ' ' . $ld_teachers[1]['last_name'];
    }
}

// 2. ดึงสถิตินักเรียน
$male_count = 0;
$female_count = 0;
$total_count = 0;
$student_ids = [];
$students_list = [];

if (isset($students) && is_array($students) && !empty($students)) {
    $students_list = $students;
} else if ($classroom_id) {
    $raw_c_lvl = $classroom['level'] ?? '';
    $raw_c_rm = $classroom['room'] ?? '';
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
        $students_list = $stmt_stats->fetchAll();

        if (empty($students_list) && !empty($lvl_vars)) {
            // Fallback without academic_year, strictly enforcing grade level and room
            $where_fb = ["s.level IN (" . implode(',', array_fill(0, count($lvl_vars), '?')) . ")"];
            $params_fb = $lvl_vars;
            if ($classroom_id > 0 && !empty($rm_vars)) {
                $where_fb[] = "(s.classroom_id = ? OR s.room IN (" . implode(',', array_fill(0, count($rm_vars), '?')) . "))";
                $params_fb[] = $classroom_id;
                $params_fb = array_merge($params_fb, $rm_vars);
            } else if ($classroom_id > 0) {
                $where_fb[] = "s.classroom_id = ?";
                $params_fb[] = $classroom_id;
            }
            if (!empty($school_id)) {
                $where_fb[] = "(s.school_id = ? OR s.school_id = 0 OR s.school_id IS NULL)";
                $params_fb[] = $school_id;
            }
            $where_fb[] = "(s.status = 'studying' OR s.status IS NULL OR s.status = '' OR s.status = 'กำลังศึกษา')";
            $stmt_fb = $pdo->prepare("SELECT s.id, s.gender, s.prefix, sp.gender as sp_gender, sp.prefix as sp_prefix FROM students s LEFT JOIN student_profiles sp ON s.student_profile_id = sp.id WHERE " . implode(' AND ', $where_fb) . " ORDER BY s.student_code ASC");
            $stmt_fb->execute($params_fb);
            $students_list = $stmt_fb->fetchAll();
        }
    } catch (Exception $e) {}
}

foreach ($students_list as $row) {
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
$total_count = count($students_list);

// 3. ดึงรายวิชาทั้งหมดตามระดับชั้น
$subjects_data = [];
$stmt_subs = $pdo->prepare("
    SELECT id as subject_id, code, name
    FROM subjects
    WHERE level = ? AND school_id = ?
    ORDER BY code ASC
");
$stmt_subs->execute([$level_name, $school_id]);
$subjects = $stmt_subs->fetchAll();

$primary_grading_mode = $school['primary_grading_mode'] ?? 'average';

foreach ($subjects as $sub) {
    $grade_dist = array_fill_keys(['4', '3.5', '3', '2.5', '2', '1.5', '1', '0', 'ร', 'มส'], 0);
    
    if ($semester === 'annual') {
        $stmt_g = $pdo->prepare("SELECT student_id, semester, score_total, score_percent, grade FROM grades WHERE subject_id = ? AND classroom_id = ? AND academic_year = ?");
        $stmt_g->execute([$sub['subject_id'], $classroom_id, $year]);
        $rows = $stmt_g->fetchAll();
        $student_records = [];
        foreach ($rows as $r) {
            $student_records[$r['student_id']][$r['semester']] = $r;
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

            if (isset($grade_dist[$calc_grade])) {
                $grade_dist[$calc_grade]++;
            }
        }
    } else {
        $query = "
            SELECT grade, COUNT(*) as count 
            FROM grades 
            WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?
            GROUP BY grade
        ";
        $stmt_grades = $pdo->prepare($query);
        $stmt_grades->execute([$sub['subject_id'], $classroom_id, $year, $semester]);
        while ($row = $stmt_grades->fetch()) {
            if (isset($grade_dist[$row['grade']])) $grade_dist[$row['grade']] = $row['count'];
        }
    }

    $subjects_data[] = [
        'code' => $sub['code'],
        'name' => $sub['name'],
        'grades' => $grade_dist
    ];
}

// 4. ดึงผลกิจกรรมพัฒนาผู้เรียน
$ld_stats = [
    'guidance' => ['P' => 0, 'F' => 0],
    'scout' => ['P' => 0, 'F' => 0],
    'club' => ['P' => 0, 'F' => 0],
    'social' => ['P' => 0, 'F' => 0]
];

$ld_query = "
    SELECT guidance_result, scout_result, club_result, social_result, COUNT(*) as count
    FROM learner_development_results
    WHERE classroom_id = ? AND academic_year = ? AND semester $semester_query
    GROUP BY guidance_result, scout_result, club_result, social_result
";
$stmt_ld = $pdo->prepare($ld_query);
$ld_params = array_merge([$classroom_id, $year], $semester_params);
$stmt_ld->execute($ld_params);
while ($row = $stmt_ld->fetch()) {
    if ($row['guidance_result'] === 'P') $ld_stats['guidance']['P'] += $row['count'];
    if ($row['guidance_result'] === 'F') $ld_stats['guidance']['F'] += $row['count'];
    
    if ($row['scout_result'] === 'P') $ld_stats['scout']['P'] += $row['count'];
    if ($row['scout_result'] === 'F') $ld_stats['scout']['F'] += $row['count'];
    
    if ($row['club_result'] === 'P') $ld_stats['club']['P'] += $row['count'];
    if ($row['club_result'] === 'F') $ld_stats['club']['F'] += $row['count'];
    
    if ($row['social_result'] === 'P') $ld_stats['social']['P'] += $row['count'];
    if ($row['social_result'] === 'F') $ld_stats['social']['F'] += $row['count'];
}

// 5. สรุปคุณลักษณะฯ, อ่านคิดวิเคราะห์ และสมรรถนะสำคัญ ของห้องเรียน
$char_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$anal_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$comp_dist = array_fill_keys(['0', '1', '2', '3'], 0);

try {
    $sem_cond = ($semester === 'annual') ? "IN (1, 2)" : "= ?";
    $sem_p = ($semester === 'annual') ? [] : [$semester];

    if (!empty($student_ids)) {
        $in_p = implode(',', array_fill(0, count($student_ids), '?'));

        // คุณลักษณะอันพึงประสงค์
        $stmt_c = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM characteristics_scores WHERE academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
        $stmt_c->execute(array_merge([$year], $sem_p, $student_ids));
        while ($row = $stmt_c->fetch()) {
            $s = (string)round($row['score']);
            if (isset($char_dist[$s])) $char_dist[$s] = (int)$row['count'];
        }

        // การอ่าน คิดวิเคราะห์ และเขียน
        $stmt_a = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM analytical_scores WHERE academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
        $stmt_a->execute(array_merge([$year], $sem_p, $student_ids));
        while ($row = $stmt_a->fetch()) {
            $s = (string)round($row['score']);
            if (isset($anal_dist[$s])) $anal_dist[$s] = (int)$row['count'];
        }

        // สมรรถนะสำคัญของผู้เรียน
        $stmt_comp = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM competency_scores WHERE academic_year = ? AND semester $sem_cond AND student_id IN ($in_p) GROUP BY score");
        $stmt_comp->execute(array_merge([$year], $sem_p, $student_ids));
        while ($row = $stmt_comp->fetch()) {
            $s = (string)round($row['score']);
            if (isset($comp_dist[$s])) $comp_dist[$s] = (int)$row['count'];
        }
    } else {
        $stmt_c = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM characteristics_scores WHERE classroom_id = ? AND academic_year = ? AND semester $sem_cond GROUP BY score");
        $stmt_c->execute(array_merge([$classroom_id, $year], $sem_p));
        while ($row = $stmt_c->fetch()) {
            $s = (string)round($row['score']);
            if (isset($char_dist[$s])) $char_dist[$s] = (int)$row['count'];
        }

        $stmt_a = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM analytical_scores WHERE classroom_id = ? AND academic_year = ? AND semester $sem_cond GROUP BY score");
        $stmt_a->execute(array_merge([$classroom_id, $year], $sem_p));
        while ($row = $stmt_a->fetch()) {
            $s = (string)round($row['score']);
            if (isset($anal_dist[$s])) $anal_dist[$s] = (int)$row['count'];
        }

        $stmt_comp = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(DISTINCT student_id) as count FROM competency_scores WHERE classroom_id = ? AND academic_year = ? AND semester $sem_cond GROUP BY score");
        $stmt_comp->execute(array_merge([$classroom_id, $year], $sem_p));
        while ($row = $stmt_comp->fetch()) {
            $s = (string)round($row['score']);
            if (isset($comp_dist[$s])) $comp_dist[$s] = (int)$row['count'];
        }
    }
} catch (Exception $e) {}

$approval_date_raw = $_GET['approval_date'] ?? '';
$approval_date = formatThaiDate($approval_date_raw);

?>

<style>
    /* --- ปรับขอบกระดาษ (บน ขวา ล่าง ซ้าย) --- */
    .page {
        padding-top: 10mm !important;    /* ปรับระยะขอบบนให้เท่ากับปกรายวิชา */
        padding-bottom: 10mm !important; /* ปรับระยะขอบล่างให้เท่ากับปกรายวิชา */
        padding-left: 15mm !important;   /* ปรับระยะขอบซ้ายให้เท่ากับปกรายวิชา */
        padding-right: 15mm !important;  /* ปรับระยะขอบขวาให้เท่ากับปกรายวิชา */
        box-shadow: none !important;
        margin: 0 auto !important;
        border: none !important;
    }

    /* --- พื้นที่หลักของหน้าปก --- */
    .cover-container {
        padding: 0;
        display: flex;
        flex-direction: column;
        position: relative;
        height: 100%;
    }
    
    /* --- ส่วนหัวแบบมีโลโก้ด้านข้าง --- */
    .header-container {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        gap: 10px; /* ลดระยะห่างระหว่างโลโก้กับข้อความ */
    }
    .logo-box {
        flex-shrink: 0;
    }
    .logo-img {
        width: 90px; /* ปรับขนาดโลโก้ให้เล็กลงเล็กน้อย */
        height: 90px;
        object-fit: contain;
    }
    .header-info {
        flex-grow: 1;
        text-align: center;
    }
    .main-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 2px; /* ลดระยะห่าง */
    }
    .school-name {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .affiliation {
        font-size: 18px;
        margin-bottom: 10px;
    }

    .flex-row {
        display: flex;
        align-items: baseline;
        font-size: 16px;
        margin-bottom: 4px; /* ปรับให้แคบลงเล็กน้อย */
        width: 100%;
    }
    .flex-fill {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #000;
        margin: 0 5px;
        text-align: center;
        min-height: 1.2em;
    }
    .flex-fixed {
        flex-shrink: 0;
    }

    /* --- ตารางสถิตินักเรียนแบบละเอียด --- */
    .stats-section {
        margin: 5px 0; /* ลดระยะห่าง */
        width: 100%;
    }
    .stats-row {
        display: grid;
        grid-template-columns: 150px 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 4px; /* ลดระยะห่าง */
        font-size: 16px;
    }
    .stats-label { text-align: left; }
    .stats-value {
        text-align: center;
        white-space: nowrap;
    }
    .dotted-line {
        display: inline-block;
        border-bottom: 0.5pt dotted #000;
        min-width: 40px;
        text-align: center;
        margin: 0 3px;
    }

    .section-title {
        font-weight: bold;
        text-align: center;
        margin: 5px 0 3px 0; /* ลดระยะห่าง */
        font-size: 16px;
    }

    /* --- ตารางสรุปผลสัมฤทธิ์ --- */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px; /* ลดระยะห่าง */
    }
    .summary-table th, .summary-table td {
        border: 1px solid #000;
        padding: 3px 2px; /* ลด padding */
        font-size: 14px;
        text-align: center;
    }
    .summary-table th {
        background-color: #fff;
        font-weight: bold;
    }
    .text-left { text-align: left !important; padding-left: 8px !important; }

    /* --- ตารางประเมิน 3 ตารางด้านล่าง --- */
    .evaluation-grid {
        margin-bottom: 5px; /* ลดระยะห่างให้แคบลง */
    }
    .eval-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px; /* ลดระยะห่างให้แคบลง */
    }
    .eval-table th, .eval-table td {
        border: 1px solid #000;
        padding: 3px; /* ลด padding */
        font-size: 14px;
        text-align: center;
    }

    /* --- ส่วนการอนุมัติ (ปรับให้เหมือนปกรายวิชา) --- */
    .approval-section {
        margin-top: auto;
        padding-top: 5px;
        width: 100%; /* เพิ่มความกว้างเต็ม */
    }
    .approval-title {
        font-weight: bold;
        text-align: center;
        margin-bottom: 10px;
        font-size: 16px;
        width: 100%;
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
        margin-bottom: 10px;
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
        border-bottom: 0.5pt dotted #000;
    }
    .sig-pos {
        text-align: left;
        padding-left: 10px;
        font-size: 16px;
        white-space: nowrap;
    }
    .sig-name {
        width: 100%;
        text-align: center;
        font-weight: bold;
        margin-top: 2px;
        font-size: 16px;
    }

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
        vertical-align: middle;
        margin-right: 8px;
    }

    .director-sig {
        text-align: center;
        margin-top: 45px; /* เพิ่มพื้นที่ว่างสำหรับเซ็นชื่อให้มากขึ้น */
    }
    .director-sig p {
        margin: 2px 0; /* ปรับให้ชื่อและตำแหน่งอยู่ชิดกัน */
    }
    .date-row {
        margin-top: 5px; /* ปรับให้ตำแหน่งผู้อำนวยการอยู่เกือบติดกับวันที่ */
        text-align: center;
        font-size: 16px;
    }
    .font-bold { font-weight: bold; }
</style>

<div class="page">
    <div class="cover-container">
        <div class="header-container">
            <div class="logo-box">
                <img src="<?= !empty($logo_url) ? $logo_url : $garuda_url ?>" class="logo-img" referrerPolicy="no-referrer">
            </div>
            
            <div class="header-info">
                <div class="main-title">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.๕)</div>
                <div class="school-name">โรงเรียน<?= $school_name ?></div>
                <div class="affiliation"><?= $affiliation ?></div>
            </div>
        </div>

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

        <div class="flex-row" style="margin-bottom: 15px;">
            <div class="flex-fill"><?= $class_teacher_1 ?><?= $class_teacher_2 ? ' / ' . $class_teacher_2 : '' ?></div>
            <div class="flex-fixed">ครูผู้สอน/ครูประจำชั้น</div>
        </div>

        <div class="stats-section">
            <div class="stats-row" style="grid-template-columns: 150px 1fr 1fr 1fr;">
                <div class="stats-label font-bold">นักเรียนทั้งหมด</div>
                <div class="stats-value">ชาย <span class="dotted-line"><?= $male_count ?></span> คน</div>
                <div class="stats-value">หญิง <span class="dotted-line"><?= $female_count ?></span> คน</div>
                <div class="stats-value">รวม <span class="dotted-line"><?= $total_count ?></span> คน</div>
            </div>
        </div>

        <div class="section-title">สรุปผลสัมฤทธิ์ทางการเรียนรู้</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th width="12%">รหัส</th>
                    <th>รายวิชา</th>
                    <th width="6%">มส</th>
                    <th width="6%">ร</th>
                    <th width="6%">0</th>
                    <th width="6%">1</th>
                    <th width="6%">1.5</th>
                    <th width="6%">2</th>
                    <th width="6%">2.5</th>
                    <th width="6%">3</th>
                    <th width="6%">3.5</th>
                    <th width="6%">4</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rowCount = 0;
                foreach ($subjects_data as $sub): 
                    $rowCount++;
                ?>
                <tr>
                    <td><?= $sub['code'] ?></td>
                    <td class="text-left"><?= $sub['name'] ?></td>
                    <td><?= $sub['grades']['มส'] ?: '-' ?></td>
                    <td><?= $sub['grades']['ร'] ?: '-' ?></td>
                    <td><?= $sub['grades']['0'] ?: '-' ?></td>
                    <td><?= $sub['grades']['1'] ?: '-' ?></td>
                    <td><?= $sub['grades']['1.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['2'] ?: '-' ?></td>
                    <td><?= $sub['grades']['2.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['3'] ?: '-' ?></td>
                    <td><?= $sub['grades']['3.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['4'] ?: '-' ?></td>
                </tr>
                <?php endforeach; 
                // เพิ่มแถวว่างให้ครบ 12 แถวเพื่อให้ดูสวยงามเหมือนในภาพ
                for($i = $rowCount; $i < 12; $i++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="evaluation-grid">
            <table class="eval-table">
                <tr>
                    <th rowspan="2" width="20%">สรุปการประเมิน</th>
                    <th colspan="4">คุณลักษณะอันพึงประสงค์</th>
                    <th colspan="4">การอ่าน คิดวิเคราะห์ และเขียน</th>
                </tr>
                <tr>
                    <th width="10%">ไม่ผ่าน</th>
                    <th width="10%">ผ่าน</th>
                    <th width="10%">ดี</th>
                    <th width="10%">ดีเยี่ยม</th>
                    <th width="10%">ไม่ผ่าน</th>
                    <th width="10%">ผ่าน</th>
                    <th width="10%">ดี</th>
                    <th width="10%">ดีเยี่ยม</th>
                </tr>
                <tr>
                    <td class="font-bold">จำนวนนักเรียน</td>
                    <td><?= $char_dist['0'] ?: '-' ?></td>
                    <td><?= $char_dist['1'] ?: '-' ?></td>
                    <td><?= $char_dist['2'] ?: '-' ?></td>
                    <td><?= $char_dist['3'] ?: '-' ?></td>
                    <td><?= $anal_dist['0'] ?: '-' ?></td>
                    <td><?= $anal_dist['1'] ?: '-' ?></td>
                    <td><?= $anal_dist['2'] ?: '-' ?></td>
                    <td><?= $anal_dist['3'] ?: '-' ?></td>
                </tr>
            </table>
        </div>

        <div style="width: 50%; margin-top: 5px;">
            <table class="eval-table">
                <tr>
                    <th colspan="5">สมรรถนะสำคัญของผู้เรียน</th>
                </tr>
                <tr>
                    <td width="30%" class="font-bold">จำนวนนักเรียน</td>
                    <td width="17.5%">ปรับปรุง<br><?= $comp_dist['0'] ?: '-' ?></td>
                    <td width="17.5%">พอใช้<br><?= $comp_dist['1'] ?: '-' ?></td>
                    <td width="17.5%">ดี<br><?= $comp_dist['2'] ?: '-' ?></td>
                    <td width="17.5%">ดีเยี่ยม<br><?= $comp_dist['3'] ?: '-' ?></td>
                </tr>
            </table>
        </div>

        <div class="approval-section">
            <div class="approval-title">การอนุมัติผลการเรียน</div>
            
            <div class="signature-group">
                <?php if ($class_teacher_2): ?>
                <div style="display: flex; justify-content: space-around; width: 100%; margin-bottom: 10px; gap: 10px;">
                    <div class="signature-item-container" style="flex: 1; margin-bottom: 0;">
                        <div class="signature-row" style="grid-template-columns: 1fr 180px 1fr;">
                            <div class="sig-label" style="font-size: 13px;">ลงชื่อ</div>
                            <div class="sig-dotted" style="width: 180px;"></div>
                            <div class="sig-pos" style="text-align: left; padding-left: 5px; font-size: 13px;">ครูประจำชั้น</div>
                        </div>
                        <div class="sig-name" style="text-align: center; margin-top: 3px; font-size: 13px;">( <?= $class_teacher_1 ?> )</div>
                    </div>
                    <div class="signature-item-container" style="flex: 1; margin-bottom: 0;">
                        <div class="signature-row" style="grid-template-columns: 1fr 180px 1fr;">
                            <div class="sig-label" style="font-size: 13px;">ลงชื่อ</div>
                            <div class="sig-dotted" style="width: 180px;"></div>
                            <div class="sig-pos" style="text-align: left; padding-left: 5px; font-size: 13px;">ครูประจำชั้น</div>
                        </div>
                        <div class="sig-name" style="text-align: center; margin-top: 3px; font-size: 13px;">( <?= $class_teacher_2 ?> )</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="signature-item-container">
                    <div class="signature-row">
                        <div class="sig-label">ลงชื่อ</div>
                        <div class="sig-dotted"></div>
                        <div class="sig-pos">ครูประจำชั้น/ครูที่ปรึกษา</div>
                    </div>
                    <div class="sig-name">( <?= $class_teacher_1 ?: '..........................................................' ?> )</div>
                </div>
                <?php endif; ?>

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

            <div class="director-sig">
                <p class="font-bold">( <?= $director_name ?: '..........................................................' ?> )</p>
                <p class="font-bold">ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
            </div>

            <div class="date-row">
                วันที่ <span class="dotted-line" style="min-width: 40px;"><?= $approval_date['day'] ?: '&nbsp;' ?></span> เดือน <span class="dotted-line" style="min-width: 120px;"><?= $approval_date['month'] ?: '&nbsp;' ?></span> พ.ศ. <span class="dotted-line" style="min-width: 60px;"><?= $approval_date['year'] ?: '&nbsp;' ?></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
