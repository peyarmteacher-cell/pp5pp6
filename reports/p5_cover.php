<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$type = $_GET['type'] ?? 'subject';
$assignment_id = $_GET['assignment_id'] ?? '';
$classroom_id = $_GET['classroom_id'] ?? '';

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
    
    $subject_name = $assignment['subject_name'];
    $subject_code = $assignment['subject_code'];
    $subject_hours = $assignment['hours'];
    $learning_area = $assignment['learning_area'];
    $level_name = $assignment['level'];
    $room_name = $assignment['room'];
    $teacher_name = $assignment['teacher_name'] . ' ' . $assignment['teacher_last'];
    $teacher_position = formatTeacherPosition($assignment['teacher_pos']);
    $classroom_id = $assignment['cid'];
    $subject_id = $assignment['subject_id'];

    // ดึงชื่อครูประจำชั้น
    $stmt_ct = $pdo->prepare('
        SELECT u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos
        FROM classrooms c
        LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
        WHERE c.id = ?
    ');
    $stmt_ct->execute([$classroom_id]);
    $ct = $stmt_ct->fetch();
    $class_teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    $class_teacher_position = formatTeacherPosition($ct['t1_pos'] ?? '');
} else {
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    $level_name = $classroom['level'];
    $room_name = $classroom['room'];
    
    // ดึงชื่อครูประจำชั้น
    $stmt_t = $pdo->prepare('
        SELECT u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos
        FROM classrooms c
        LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
        WHERE c.id = ?
    ');
    $stmt_t->execute([$classroom_id]);
    $ct = $stmt_t->fetch();
    $class_teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    $class_teacher_position = formatTeacherPosition($ct['t1_pos'] ?? '');
    $teacher_name = $class_teacher_name;
    $teacher_position = $class_teacher_position;
}

// 1. สถิตินักเรียน
$stmt_stats = $pdo->prepare("SELECT gender, COUNT(*) as count FROM students WHERE classroom_id = ? AND status = 'studying' GROUP BY gender");
$stmt_stats->execute([$classroom_id]);
$stats_rows = $stmt_stats->fetchAll();
$male_count = 0;
$female_count = 0;
foreach ($stats_rows as $row) {
    if ($row['gender'] === 'ชาย') $male_count = $row['count'];
    if ($row['gender'] === 'หญิง') $female_count = $row['count'];
}
$total_count = $male_count + $female_count;

// 2. สรุปผลสัมฤทธิ์ (ถ้าเป็นรายวิชา)
$grade_dist = array_fill_keys(['4', '3.5', '3', '2.5', '2', '1.5', '1', '0', 'ร', 'มส'], 0);
if ($type === 'subject' && isset($subject_id)) {
    $stmt_grades = $pdo->prepare("SELECT grade, COUNT(*) as count FROM grades WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ? GROUP BY grade");
    $stmt_grades->execute([$subject_id, $classroom_id, $year, $semester]);
    while ($row = $stmt_grades->fetch()) {
        if (isset($grade_dist[$row['grade']])) $grade_dist[$row['grade']] = $row['count'];
    }
}

// 3. สรุปคุณลักษณะฯ และ อ่านคิดวิเคราะห์
$char_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$anal_dist = array_fill_keys(['0', '1', '2', '3'], 0);
$comp_dist = array_fill_keys(['0', '1', '2', '3'], 0);

if ($type === 'subject' && isset($subject_id)) {
    // คุณลักษณะ
    $stmt_c = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM characteristics_scores WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ? GROUP BY score");
    $stmt_c->execute([$subject_id, $classroom_id, $year, $semester]);
    while ($row = $stmt_c->fetch()) {
        if (isset($char_dist[$row['score']])) $char_dist[$row['score']] = $row['count'];
    }
    // อ่านคิดวิเคราะห์
    $stmt_a = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM analytical_scores WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ? GROUP BY score");
    $stmt_a->execute([$subject_id, $classroom_id, $year, $semester]);
    while ($row = $stmt_a->fetch()) {
        if (isset($anal_dist[$row['score']])) $anal_dist[$row['score']] = $row['count'];
    }
}

// สมรรถนะ (มักจะเป็นรายเทอม/รายปี ของห้องเรียน)
$stmt_comp = $pdo->prepare("SELECT ROUND(average_score) as score, COUNT(*) as count FROM competency_scores WHERE classroom_id = ? AND academic_year = ? AND semester = ? GROUP BY score");
$stmt_comp->execute([$classroom_id, $year, $semester]);
while ($row = $stmt_comp->fetch()) {
    if (isset($comp_dist[$row['score']])) $comp_dist[$row['score']] = $row['count'];
}

?>

<style>
    .cover-container {
        border: 2px solid #000;
        padding: 40px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .main-title {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 5px;
    }
    .school-name {
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 5px;
    }
    .affiliation {
        font-size: 18px;
        text-align: center;
        margin-bottom: 20px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 10px;
    }
    .info-line {
        border-bottom: 1px dotted #000;
        padding: 0 5px;
        display: inline-block;
        min-width: 50px;
        text-align: center;
    }
    .section-title {
        font-weight: bold;
        text-align: center;
        margin: 15px 0 10px 0;
        text-decoration: underline;
    }
    .stats-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .stats-table td {
        border: none;
        text-align: left;
        padding: 2px 5px;
        font-size: 16px;
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .summary-table th, .summary-table td {
        border: 1px solid #000;
        padding: 4px;
        font-size: 14px;
    }
    .approval-section {
        margin-top: auto;
        padding-top: 20px;
    }
    .signature-line {
        margin-bottom: 15px;
        text-align: right;
        padding-right: 50px;
    }
    .approval-box {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
        justify-content: center;
    }
    .check-box {
        width: 20px;
        height: 20px;
        border: 1px solid #000;
        display: inline-block;
    }
    .dotted-line {
        border-bottom: 1px dotted #000;
        display: inline-block;
        width: 250px;
    }
</style>

<div class="page">
    <div class="cover-container">
        <div class="main-title">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.๕)</div>
        <div class="school-name">โรงเรียน<?= $school_name ?></div>
        <div class="affiliation"><?= $affiliation ?></div>

        <div style="font-size: 18px; margin-bottom: 15px;">
            ชั้น <span class="info-line" style="width: 150px;"><?= $level_name ?>/<?= $room_name ?></span>
            ภาคเรียนที่ <span class="info-line" style="width: 50px;"><?= $semester === 'annual' ? '1-2' : $semester ?></span>
            ปีการศึกษา <span class="info-line" style="width: 80px;"><?= $year ?></span>
        </div>

        <div style="font-size: 18px; margin-bottom: 15px;">
            รายวิชา <span class="info-line" style="width: 200px;"><?= $subject_name ?></span>
            รหัส <span class="info-line" style="width: 100px;"><?= $subject_code ?></span>
            เวลาเรียน <span class="info-line" style="width: 50px;"><?= $subject_hours ?></span> ชั่วโมง/ปี
        </div>

        <div style="font-size: 18px; margin-bottom: 25px;">
            กลุ่มสาระการเรียนรู้ <span class="info-line" style="width: 300px;"><?= $learning_area ?></span>
        </div>

        <div style="text-align: center; margin-bottom: 30px;">
            <div style="margin-bottom: 10px;">
                <span class="dotted-line" style="width: 400px;"><?= $teacher_name ?></span> ครูประจำวิชา
            </div>
            <div>
                <span class="dotted-line" style="width: 400px;"><?= $class_teacher_name ?></span> ครูที่ปรึกษา/ครูประจำชั้น
            </div>
        </div>

        <table class="stats-table">
            <tr>
                <td width="30%">นักเรียนต้นปีการศึกษา</td>
                <td>ชาย <span class="info-line" style="width: 40px;"><?= $male_count ?></span> คน</td>
                <td>หญิง <span class="info-line" style="width: 40px;"><?= $female_count ?></span> คน</td>
                <td>รวม <span class="info-line" style="width: 40px;"><?= $total_count ?></span> คน</td>
            </tr>
            <tr>
                <td>ออกระหว่างปีการศึกษา</td>
                <td>ชาย <span class="info-line" style="width: 40px;">0</span> คน</td>
                <td>หญิง <span class="info-line" style="width: 40px;">0</span> คน</td>
                <td>รวม <span class="info-line" style="width: 40px;">0</span> คน</td>
            </tr>
            <tr>
                <td>เข้าระหว่างปีการศึกษา</td>
                <td>ชาย <span class="info-line" style="width: 40px;">0</span> คน</td>
                <td>หญิง <span class="info-line" style="width: 40px;">0</span> คน</td>
                <td>รวม <span class="info-line" style="width: 40px;">0</span> คน</td>
            </tr>
            <tr>
                <td class="font-bold">รวมสิ้นปีการศึกษา</td>
                <td>ชาย <span class="info-line" style="width: 40px;"><?= $male_count ?></span> คน</td>
                <td>หญิง <span class="info-line" style="width: 40px;"><?= $female_count ?></span> คน</td>
                <td>รวม <span class="info-line" style="width: 40px;"><?= $total_count ?></span> คน</td>
            </tr>
        </table>

        <div class="section-title">สรุปผลสัมฤทธิ์ทางการเรียนรู้</div>
        <table class="summary-table">
            <tr class="bg-slate-50">
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
                <td class="font-bold">จำนวนนักเรียน</td>
                <td><?= $grade_dist['มส'] ?: '-' ?></td>
                <td><?= $grade_dist['ร'] ?: '-' ?></td>
                <td><?= $grade_dist['0'] ?: '-' ?></td>
                <td><?= $grade_dist['1'] ?: '-' ?></td>
                <td><?= $grade_dist['1.5'] ?: '-' ?></td>
                <td><?= $grade_dist['2'] ?: '-' ?></td>
                <td><?= $grade_dist['2.5'] ?: '-' ?></td>
                <td><?= $grade_dist['3'] ?: '-' ?></td>
                <td><?= $grade_dist['3.5'] ?: '-' ?></td>
                <td><?= $grade_dist['4'] ?: '-' ?></td>
            </tr>
        </table>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <table class="summary-table">
                    <tr class="bg-slate-50">
                        <th rowspan="2">สรุปการประเมิน</th>
                        <th colspan="4">คุณลักษณะอันพึงประสงค์</th>
                    </tr>
                    <tr class="bg-slate-50">
                        <th width="20%">ไม่ผ่าน</th>
                        <th width="20%">ผ่าน</th>
                        <th width="20%">ดี</th>
                        <th width="20%">ดีเยี่ยม</th>
                    </tr>
                    <tr>
                        <td class="font-bold">จำนวนนักเรียน</td>
                        <td><?= $char_dist['0'] ?: '-' ?></td>
                        <td><?= $char_dist['1'] ?: '-' ?></td>
                        <td><?= $char_dist['2'] ?: '-' ?></td>
                        <td><?= $char_dist['3'] ?: '-' ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="summary-table">
                    <tr class="bg-slate-50">
                        <th rowspan="2">สรุปการประเมิน</th>
                        <th colspan="4">การอ่าน คิดวิเคราะห์ และเขียน</th>
                    </tr>
                    <tr class="bg-slate-50">
                        <th width="20%">ไม่ผ่าน</th>
                        <th width="20%">ผ่าน</th>
                        <th width="20%">ดี</th>
                        <th width="20%">ดีเยี่ยม</th>
                    </tr>
                    <tr>
                        <td class="font-bold">จำนวนนักเรียน</td>
                        <td><?= $anal_dist['0'] ?: '-' ?></td>
                        <td><?= $anal_dist['1'] ?: '-' ?></td>
                        <td><?= $anal_dist['2'] ?: '-' ?></td>
                        <td><?= $anal_dist['3'] ?: '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="summary-table">
            <tr class="bg-slate-50">
                <th rowspan="2" width="25%">สรุปการประเมิน</th>
                <th colspan="4">สมรรถนะสำคัญของผู้เรียน</th>
            </tr>
            <tr class="bg-slate-50">
                <th>ปรับปรุง</th>
                <th>พอใช้</th>
                <th>ดี</th>
                <th>ดีเยี่ยม</th>
            </tr>
            <tr>
                <td class="font-bold">จำนวนนักเรียน</td>
                <td><?= $comp_dist['0'] ?: '-' ?></td>
                <td><?= $comp_dist['1'] ?: '-' ?></td>
                <td><?= $comp_dist['2'] ?: '-' ?></td>
                <td><?= $comp_dist['3'] ?: '-' ?></td>
            </tr>
        </table>

        <div class="approval-section">
            <div class="section-title" style="text-decoration: none;">การอนุมัติผลการเรียน</div>
            
            <div style="margin-top: 10px;">
                <div class="signature-line">ลงชื่อ.......................................................... ครูประจำวิชา</div>
                <div class="signature-line">ลงชื่อ.......................................................... <?= $academic_head_position ?></div>
                <div class="signature-line">ลงชื่อ.......................................................... รองผู้อำนวยการโรงเรียน</div>
            </div>

            <div class="approval-box">
                <div class="check-box"></div> อนุมัติ
                <div style="width: 50px;"></div>
                <div class="check-box"></div> ไม่อนุมัติ
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <p>..........................................................</p>
                <p class="font-bold">( <?= $director_name ?: '..........................................................' ?> )</p>
                <p>ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
                <p>วันที่ <span class="info-line" style="width: 30px;"></span> เดือน <span class="info-line" style="width: 80px;"></span> พ.ศ. <span class="info-line" style="width: 50px;"></span></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
