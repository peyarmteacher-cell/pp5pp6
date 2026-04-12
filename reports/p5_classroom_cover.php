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

// 3. ดึงรายวิชาทั้งหมดและผลสัมฤทธิ์
$subjects_data = [];
$stmt_subs = $pdo->prepare("
    SELECT s.id as subject_id, s.code, s.name
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    WHERE ta.classroom_id = ? AND ta.academic_year = ? AND ta.semester = ?
    ORDER BY s.code ASC
");
$stmt_subs->execute([$classroom_id, $year, $semester]);
$subjects = $stmt_subs->fetchAll();

foreach ($subjects as $sub) {
    $grade_dist = array_fill_keys(['4', '3.5', '3', '2.5', '2', '1.5', '1', '0', 'ร', 'มส'], 0);
    $stmt_grades = $pdo->prepare("
        SELECT grade, COUNT(*) as count 
        FROM grades 
        WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ? 
        GROUP BY grade
    ");
    $stmt_grades->execute([$sub['subject_id'], $classroom_id, $year, $semester]);
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

$stmt_ld = $pdo->prepare("
    SELECT guidance_result, scout_result, club_result, social_result, COUNT(*) as count
    FROM learner_development_results
    WHERE classroom_id = ? AND academic_year = ? AND semester = ?
    GROUP BY guidance_result, scout_result, club_result, social_result
");
$stmt_ld->execute([$classroom_id, $year, $semester]);
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
    
    /* --- ส่วนหัวแบบมีโลโก้ด้านข้าง --- */
    .header-container {
        display: flex;
        align-items: flex-start;
        margin-bottom: 10px;
        gap: 20px;
    }
    .logo-box {
        flex-shrink: 0;
    }
    .logo-img {
        width: 100px;
        height: 100px;
        object-fit: contain;
    }
    .header-info {
        flex-grow: 1;
    }
    .main-title {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 2px;
    }
    .school-name {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 2px;
    }
    .affiliation {
        font-size: 14px;
        margin-bottom: 8px;
    }

    .flex-row {
        display: flex;
        align-items: baseline;
        font-size: 15px;
        margin-bottom: 4px;
        width: 100%;
        white-space: nowrap;
    }
    .flex-fill {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #666;
        margin: 0 5px;
        text-align: center;
        min-height: 1.2em;
    }
    .flex-fixed {
        flex-shrink: 0;
    }

    .teacher-section {
        margin: 5px 0;
        width: 100%;
    }
    .teacher-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
        font-size: 15px;
    }
    .teacher-dotted {
        flex-grow: 1;
        border-bottom: 0.5pt dotted #666;
        text-align: center;
    }
    .teacher-label {
        width: 180px;
        text-align: left;
        padding-left: 10px;
    }

    .section-title {
        font-weight: bold;
        text-align: center;
        margin: 10px 0 5px 0;
        text-decoration: underline;
        font-size: 15px;
    }

    .stats-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        border: 1px solid #000;
    }
    .stats-table td {
        border: 1px solid #000;
        padding: 4px 8px;
        font-size: 15px;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .summary-table th, .summary-table td {
        border: 1px solid #000;
        padding: 3px;
        font-size: 12px;
        text-align: center;
    }
    .summary-table th {
        background-color: #f8fafc;
    }
    .text-left { text-align: left !important; padding-left: 5px !important; }

    .approval-section {
        margin-top: auto;
        padding-top: 10px;
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
    .sig-label { text-align: right; padding-right: 10px; font-size: 14px; }
    .sig-dotted { border-bottom: 0.5pt dotted #666; }
    .sig-pos { text-align: left; padding-left: 10px; font-size: 15px; }
    .sig-name { font-weight: bold; margin-top: 2px; font-size: 15px; }

    .approval-box {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 10px 0;
        justify-content: center;
        font-size: 14px;
    }
    .check-box {
        width: 14px;
        height: 14px;
        border: 1px solid #000;
        display: inline-block;
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
                
                <div class="flex-row">
                    <div class="flex-fixed">ชั้น</div>
                    <div class="flex-fill"><?= $level_name ?>/<?= $room_name ?></div>
                    <div class="flex-fixed">ภาคเรียนที่</div>
                    <div class="flex-fill"><?= $semester === 'annual' ? '1-2' : $semester ?></div>
                    <div class="flex-fixed">ปีการศึกษา</div>
                    <div class="flex-fill"><?= $year ?></div>
                </div>

                <div class="teacher-section">
                    <div class="teacher-row">
                        <div class="teacher-dotted"><?= $class_teacher_1 ?></div>
                        <div class="teacher-label">ครูประจำชั้น</div>
                    </div>
                    <?php if ($class_teacher_2): ?>
                    <div class="teacher-row">
                        <div class="teacher-dotted"><?= $class_teacher_2 ?></div>
                        <div class="teacher-label">ครูประจำชั้น</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <table class="stats-table">
            <tr>
                <td class="font-bold">จำนวนนักเรียนทั้งหมด</td>
                <td style="text-align: center;">ชาย <span style="display: inline-block; width: 40px; border-bottom: 1px dotted #000;"><?= $male_count ?></span> คน</td>
                <td style="text-align: center;">หญิง <span style="display: inline-block; width: 40px; border-bottom: 1px dotted #000;"><?= $female_count ?></span> คน</td>
                <td style="text-align: center;">รวม <span style="display: inline-block; width: 40px; border-bottom: 1px dotted #000;"><?= $total_count ?></span> คน</td>
            </tr>
        </table>

        <div class="section-title">สรุปผลสัมฤทธิ์ทางการเรียนรู้รายวิชา</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th rowspan="2" width="12%">รหัสวิชา</th>
                    <th rowspan="2">ชื่อรายวิชา</th>
                    <th colspan="10">จำนวนนักเรียนที่ได้ระดับผลการเรียน</th>
                </tr>
                <tr>
                    <th width="5%">4</th>
                    <th width="5%">3.5</th>
                    <th width="5%">3</th>
                    <th width="5%">2.5</th>
                    <th width="5%">2</th>
                    <th width="5%">1.5</th>
                    <th width="5%">1</th>
                    <th width="5%">0</th>
                    <th width="5%">ร</th>
                    <th width="5%">มส</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects_data as $sub): ?>
                <tr>
                    <td><?= $sub['code'] ?></td>
                    <td class="text-left"><?= $sub['name'] ?></td>
                    <td><?= $sub['grades']['4'] ?: '-' ?></td>
                    <td><?= $sub['grades']['3.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['3'] ?: '-' ?></td>
                    <td><?= $sub['grades']['2.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['2'] ?: '-' ?></td>
                    <td><?= $sub['grades']['1.5'] ?: '-' ?></td>
                    <td><?= $sub['grades']['1'] ?: '-' ?></td>
                    <td><?= $sub['grades']['0'] ?: '-' ?></td>
                    <td><?= $sub['grades']['ร'] ?: '-' ?></td>
                    <td><?= $sub['grades']['มส'] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr style="background-color: #f1f5f9;">
                    <td colspan="2" class="font-bold">กิจกรรมพัฒนาผู้เรียน</td>
                    <td colspan="5" class="font-bold">ผ่าน (คน)</td>
                    <td colspan="5" class="font-bold">ไม่ผ่าน (คน)</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">กิจกรรมแนะแนว</td>
                    <td colspan="5"><?= $ld_stats['guidance']['P'] ?: '-' ?></td>
                    <td colspan="5"><?= $ld_stats['guidance']['F'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">กิจกรรมนักเรียน (ลูกเสือ/เนตรนารี)</td>
                    <td colspan="5"><?= $ld_stats['scout']['P'] ?: '-' ?></td>
                    <td colspan="5"><?= $ld_stats['scout']['F'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">กิจกรรมชุมนุม</td>
                    <td colspan="5"><?= $ld_stats['club']['P'] ?: '-' ?></td>
                    <td colspan="5"><?= $ld_stats['club']['F'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">กิจกรรมเพื่อสังคมและสาธารณประโยชน์</td>
                    <td colspan="5"><?= $ld_stats['social']['P'] ?: '-' ?></td>
                    <td colspan="5"><?= $ld_stats['social']['F'] ?: '-' ?></td>
                </tr>
            </tbody>
        </table>

        <div class="approval-section">
            <div class="section-title" style="text-decoration: none; margin-bottom: 15px;">การอนุมัติผลการเรียน</div>
            
            <div class="signature-group">
                <div class="signature-item-container">
                    <div class="signature-row">
                        <div class="sig-label">ลงชื่อ</div>
                        <div class="sig-dotted"></div>
                        <div class="sig-pos">ครูประจำชั้น</div>
                    </div>
                    <div class="sig-name">( <?= $class_teacher_1 ?> )</div>
                </div>

                <?php
                // ตรวจสอบ รองผู้อำนวยการ
                $deputy = null;
                try {
                    $stmt_dep = $pdo->prepare("SELECT * FROM school_officials WHERE school_id = ? AND role_key = 'deputy_director' AND is_active = 1 LIMIT 1");
                    $stmt_dep->execute([$school_id]);
                    $deputy = $stmt_dep->fetch();
                } catch (Exception $e) {}

                if ($deputy): ?>
                    <div class="signature-item-container">
                        <div class="signature-row">
                            <div class="sig-label">ลงชื่อ</div>
                            <div class="sig-dotted"></div>
                            <div class="sig-pos"><?= formatTeacherPosition($deputy['position']) ?></div>
                        </div>
                        <div class="sig-name">( <?= $deputy['name'] ?> )</div>
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

            <div style="text-align: center; margin-top: 30px;">
                <p style="margin-bottom: 5px; visibility: hidden;">..........................................................</p>
                <p class="font-bold" style="font-size: 16px;">( <?= $director_name ?: '..........................................................' ?> )</p>
                <p style="font-size: 16px;">ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
                <p style="margin-top: 10px;">วันที่ <span style="display: inline-block; width: 40px; border-bottom: 1px dotted #000;"></span> เดือน <span style="display: inline-block; width: 100px; border-bottom: 1px dotted #000;"></span> พ.ศ. <span style="display: inline-block; width: 50px; border-bottom: 1px dotted #000;"></span></p>
            </div>
        </div>
    </div>
</div>
