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

$level_name = $classroom['level'];
$room_name = $classroom['room'];
$class_teacher_1 = $classroom['t1_name'] ? $classroom['t1_name'] . ' ' . $classroom['t1_last'] : '';
$class_teacher_2 = $classroom['t2_name'] ? $classroom['t2_name'] . ' ' . $classroom['t2_last'] : '';

// 2. ดึงสถิตินักเรียน
$male_count = 0;
$female_count = 0;
$stmt_stats = $pdo->prepare("SELECT gender, COUNT(*) as count FROM students WHERE classroom_id = ? AND status = 'studying' GROUP BY gender");
$stmt_stats->execute([$classroom_id]);
$stats_rows = $stmt_stats->fetchAll();
foreach ($stats_rows as $row) {
    if ($row['gender'] === 'ชาย') $male_count = $row['count'];
    if ($row['gender'] === 'หญิง') $female_count = $row['count'];
}
$total_count = $male_count + $female_count;

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

$semester_query = $semester === 'annual' ? "IN (1, 2)" : "= ?";
$semester_params = $semester === 'annual' ? [] : [$semester];

foreach ($subjects as $sub) {
    $grade_dist = array_fill_keys(['4', '3.5', '3', '2.5', '2', '1.5', '1', '0', 'ร', 'มส'], 0);
    $query = "
        SELECT grade, COUNT(*) as count 
        FROM grades 
        WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester $semester_query
        GROUP BY grade
    ";
    $stmt_grades = $pdo->prepare($query);
    $params = array_merge([$sub['subject_id'], $classroom_id, $year], $semester_params);
    $stmt_grades->execute($params);
    while ($row = $stmt_grades->fetch()) {
        if (isset($grade_dist[$row['grade']])) $grade_dist[$row['grade']] = $row['count'];
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

?>

<style>
    /* --- ปรับขอบกระดาษ (บน ขวา ล่าง ซ้าย) --- */
    .page {
        padding-top: 15mm !important;    /* ปรับระยะขอบบน */
        padding-bottom: 15mm !important; /* ปรับระยะขอบล่าง */
        padding-left: 20mm !important;   /* ปรับระยะขอบซ้าย */
        padding-right: 15mm !important;  /* ปรับระยะขอบขวา */
        box-shadow: none !important;
        margin: 0 auto !important;
        border: none !important; /* ไม่มีเส้นขอบหน้ากระดาษ */
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
        margin-bottom: 6px;
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
        margin: 10px 0;
        width: 100%;
    }
    .stats-row {
        display: grid;
        grid-template-columns: 220px 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 4px;
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
        margin: 10px 0 8px 0;
        font-size: 16px;
    }

    /* --- ตารางสรุปผลสัมฤทธิ์ --- */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .summary-table th, .summary-table td {
        border: 1px solid #000;
        padding: 4px 2px;
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
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    .eval-table {
        width: 100%;
        border-collapse: collapse;
    }
    .eval-table th, .eval-table td {
        border: 1px solid #000;
        padding: 4px;
        font-size: 14px;
        text-align: center;
    }

    /* --- ส่วนการอนุมัติ --- */
    .approval-section {
        margin-top: auto;
        padding-top: 10px;
    }
    .approval-title {
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
        font-size: 16px;
    }
    .sig-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        width: 100%;
        margin-bottom: 15px;
    }
    .sig-row {
        display: flex;
        align-items: baseline;
        margin-bottom: 8px;
        width: 450px;
    }
    .sig-line {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #000;
        margin-right: 10px;
    }
    .sig-label {
        width: 180px;
        text-align: left;
        font-size: 15px;
    }

    .approval-check-row {
        display: flex;
        justify-content: center;
        gap: 60px;
        margin: 15px 0;
        font-size: 16px;
    }
    .check-box {
        width: 18px;
        height: 18px;
        border: 1px solid #000;
        display: inline-block;
        vertical-align: middle;
        margin-right: 8px;
    }

    .director-sig {
        text-align: center;
        margin-top: 10px;
    }
    .date-row {
        margin-top: 15px;
        text-align: center;
        font-size: 16px;
    }
    .font-bold { font-weight: bold; }
</style>

<div class="page">
    <div class="cover-container">
        <div class="header-container">
            <?php if ($logo_url): ?>
            <div class="logo-box">
                <img src="<?= $logo_url ?>" class="logo-img" referrerPolicy="no-referrer">
            </div>
            <?php endif; ?>
            
            <div class="header-info">
                <div class="main-title">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.๕)</div>
                <div class="school-name">โรงเรียน<?= $school_name ?></div>
                <div class="affiliation"><?= $affiliation ?></div>
            </div>
        </div>

        <div class="flex-row">
            <div class="flex-fixed">ชั้น</div>
            <div class="flex-fill"><?= $level_name ?>/<?= $room_name ?></div>
            <div class="flex-fixed">ภาคเรียนที่</div>
            <div class="flex-fill"><?= $semester === 'annual' ? '1-2' : $semester ?></div>
            <div class="flex-fixed">ปีการศึกษา</div>
            <div class="flex-fill"><?= $year ?></div>
        </div>

        <div class="flex-row" style="margin-bottom: 15px;">
            <div class="flex-fill"><?= $class_teacher_1 ?><?= $class_teacher_2 ? ' และ ' . $class_teacher_2 : '' ?></div>
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
            <div>
                <table class="eval-table">
                    <tr>
                        <th colspan="5">คุณลักษณะอันพึงประสงค์</th>
                    </tr>
                    <tr>
                        <td width="30%" class="font-bold">จำนวนนักเรียน</td>
                        <td width="17.5%">ไม่ผ่าน<br>-</td>
                        <td width="17.5%">ผ่าน<br>-</td>
                        <td width="17.5%">ดี<br>-</td>
                        <td width="17.5%">ดีเยี่ยม<br>-</td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="eval-table">
                    <tr>
                        <th colspan="5">การอ่าน คิดวิเคราะห์ และเขียน</th>
                    </tr>
                    <tr>
                        <td width="30%" class="font-bold">จำนวนนักเรียน</td>
                        <td width="17.5%">ไม่ผ่าน<br>-</td>
                        <td width="17.5%">ผ่าน<br>-</td>
                        <td width="17.5%">ดี<br>-</td>
                        <td width="17.5%">ดีเยี่ยม<br>-</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="width: 54.5%;">
            <table class="eval-table">
                <tr>
                    <th colspan="5">สมรรถนะสำคัญของผู้เรียน</th>
                </tr>
                <tr>
                    <td width="30%" class="font-bold">จำนวนนักเรียน</td>
                    <td width="17.5%">ปรับปรุง<br>-</td>
                    <td width="17.5%">พอใช้<br>-</td>
                    <td width="17.5%">ดี<br>-</td>
                    <td width="17.5%">ดีเยี่ยม<br>-</td>
                </tr>
            </table>
        </div>

        <div class="approval-section">
            <div class="approval-title">การอนุมัติผลการเรียน</div>
            
            <div class="sig-container">
                <div class="sig-row">
                    <div class="sig-line"></div>
                    <div class="sig-label">ครูประจำชั้น/ครูที่ปรึกษา</div>
                </div>
                <div style="text-align: right; width: 450px; padding-right: 190px; margin-top: -5px; margin-bottom: 5px;">
                    ( <?= $class_teacher_1 ?: '..........................................................' ?> )
                </div>

                <?php if ($deputy_director_name): ?>
                <div class="sig-row">
                    <div class="sig-line"></div>
                    <div class="sig-label"><?= $deputy_director_position ?></div>
                </div>
                <div style="text-align: right; width: 450px; padding-right: 190px; margin-top: -5px; margin-bottom: 5px;">
                    ( <?= $deputy_director_name ?> )
                </div>
                <?php else: ?>
                <div class="sig-row">
                    <div class="sig-line"></div>
                    <div class="sig-label"><?= $academic_head_position ?></div>
                </div>
                <div style="text-align: right; width: 450px; padding-right: 190px; margin-top: -5px; margin-bottom: 5px;">
                    ( <?= $academic_head_name ?: '..........................................................' ?> )
                </div>
                <?php endif; ?>
            </div>

            <div class="approval-check-row">
                <div><span class="check-box"></span> อนุมัติ</div>
                <div><span class="check-box"></span> ไม่อนุมัติ</div>
            </div>

            <div class="director-sig">
                <p>( <?= $director_name ?: '..........................................................' ?> )</p>
                <p class="font-bold">ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
            </div>

            <div class="date-row">
                วันที่ <span class="dotted-line" style="min-width: 30px;"></span> เดือน <span class="dotted-line" style="min-width: 100px;"></span> พ.ศ. <span class="dotted-line" style="min-width: 50px;"></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
