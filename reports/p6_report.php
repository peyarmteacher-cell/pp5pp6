<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$classroom_id = $_GET['classroom_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';
$approval_date_raw = $_GET['approval_date'] ?? '';

if (!$classroom_id || !$student_id) die('Missing parameters');

$approval_date = formatThaiDate($approval_date_raw);

// ดึงข้อมูลโรงเรียน
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = ?');
$stmt->execute([$_SESSION['school_id']]);
$school = $stmt->fetch();

// ดึงข้อมูลห้องเรียน
$stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
$stmt->execute([$classroom_id]);
$classroom = $stmt->fetch();

// ดึงข้อมูลนักเรียน
$students_to_print = [];
if ($student_id === 'all') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE classroom_id = ? AND academic_year = ? AND status = "studying" ORDER BY student_code ASC');
    $stmt->execute([$classroom_id, $year]);
    $students_to_print = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $students_to_print = [$stmt->fetch()];
}

foreach ($students_to_print as $student):
    if (!$student) continue;
    
    // ดึงผลการเรียน
    $stmt = $pdo->prepare('
        SELECT s.code, s.name, s.hours, s.credits, g.score_total, g.grade, g.score_percent
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        LEFT JOIN grades g ON s.id = g.subject_id AND g.student_id = ? AND g.academic_year = ? AND g.semester = ?
        WHERE ta.classroom_id = ? AND ta.academic_year = ?
        GROUP BY s.id
        ORDER BY s.code ASC
    ');
    $stmt->execute([$student['id'], $year, $semester === 'annual' ? 0 : $semester, $classroom_id, $year]);
    $grades = $stmt->fetchAll();

    // ดึงกิจกรรมพัฒนาผู้เรียน
    $stmt = $pdo->prepare('SELECT * FROM learner_development_results WHERE student_id = ? AND academic_year = ? AND semester = ?');
    $stmt->execute([$student['id'], $year, $semester === 'annual' ? 0 : $semester]);
    $ld_result = $stmt->fetch();

    // ดึงคะแนนคุณลักษณะ
    $stmt = $pdo->prepare('SELECT average_score FROM characteristics_scores WHERE student_id = ? AND academic_year = ? AND semester = ? LIMIT 1');
    $stmt->execute([$student['id'], $year, $semester === 'annual' ? 0 : $semester]);
    $behavior = $stmt->fetch();

    // ดึงคะแนนอ่านคิดวิเคราะห์
    $stmt = $pdo->prepare('SELECT average_score FROM analytical_scores WHERE student_id = ? AND academic_year = ? AND semester = ? LIMIT 1');
    $stmt->execute([$student['id'], $year, $semester === 'annual' ? 0 : $semester]);
    $analytical = $stmt->fetch();

    // ดึงคะแนนสมรรถนะ
    $stmt = $pdo->prepare('SELECT average_score FROM competency_scores WHERE student_id = ? AND academic_year = ? AND semester = ? LIMIT 1');
    $stmt->execute([$student['id'], $year, $semester === 'annual' ? 0 : $semester]);
    $competency = $stmt->fetch();

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
    
    $teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    $teacher_pos = formatTeacherPosition($ct['t1_pos'] ?? '');

    // ดึงหัวหน้าวิชาการ
    $stmt_acad = $pdo->prepare('SELECT name, last_name, position FROM users WHERE role = "admin" LIMIT 1');
    $stmt_acad->execute();
    $acad = $stmt_acad->fetch();
    $acad_name = $acad ? $acad['name'] . ' ' . $acad['last_name'] : '';
    $acad_pos = $acad ? formatTeacherPosition($acad['position']) : 'หัวหน้างานวิชาการโรงเรียน';

    function getResultText($avg) {
        if (!$avg) return '-';
        if ($avg >= 2.5) return 'ดีเยี่ยม';
        if ($avg >= 1.5) return 'ดี';
        if ($avg >= 0.5) return 'ผ่าน';
        return 'ไม่ผ่าน';
    }

    function formatPassFail($val) {
        if (empty($val)) return '-';
        if ($val === 'P' || $val === 'ผ่าน') return 'ผ่าน';
        if ($val === 'F' || $val === 'ไม่ผ่าน') return 'ไม่ผ่าน';
        return $val;
    }
?>

<style>
    .p6-container {
        font-family: 'Sarabun', sans-serif;
        color: black;
    }
    .p6-header {
        position: relative;
        text-align: center;
        margin-bottom: 20px;
    }
    .p6-logo {
        position: absolute;
        left: 0;
        top: 0;
        width: 80px;
        height: 80px;
        object-fit: contain;
    }
    .p6-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .p6-table th, .p6-table td {
        border: 1px solid black;
        padding: 4px;
        text-align: center;
    }
    .p6-table th {
        background: #fff;
    }
    .text-left { text-align: left !important; }
    .dotted-line {
        border-bottom: 1px dotted black;
        display: inline-block;
        min-width: 100px;
        padding: 0 5px;
    }
    .summary-section {
        display: flex;
        margin-top: 10px;
        font-size: 13px;
    }
    .summary-left {
        width: 45%;
    }
    .summary-right {
        width: 55%;
        text-align: center;
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
    }
    .summary-table td {
        border: 1px solid black;
        padding: 2px 5px;
    }
    .sig-block {
        margin-top: 15px;
        text-align: center;
    }
    @media print {
        .page { margin: 0; padding: 10mm; }
    }
</style>

<div class="page p6-container">
    <div class="p6-header">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" class="p6-logo" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h3 style="margin: 0; font-size: 18px;">แบบรายงานประจำตัวนักเรียน : ผลการพัฒนาคุณภาพผู้เรียนรายบุคคล (ปพ.๖)</h3>
        <p style="margin: 5px 0; font-size: 16px;">โรงเรียน<?= $school_name ?> <?= $affiliation ?></p>
        <p style="margin: 5px 0; font-size: 16px;">ชั้นประถมศึกษาปีที่ <?= $classroom['level'] ?> ภาคเรียนที่ <?= $semester === 'annual' ? '๑-๒' : $semester ?> ปีการศึกษา <?= $year ?></p>
    </div>

    <div style="margin-bottom: 10px; font-size: 14px; display: flex; justify-content: space-between;">
        <div>ชื่อ-สกุล <span class="dotted-line" style="min-width: 250px;"><?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?></span></div>
        <div>เลขประจำตัว <span class="dotted-line" style="min-width: 100px;"><?= $student['student_code'] ?></span></div>
        <div>เลขที่ <span class="dotted-line" style="min-width: 50px;"><?= array_search($student['id'], array_column($students_to_print, 'id')) + 1 ?></span></div>
    </div>

    <table class="p6-table">
        <thead>
            <tr>
                <th style="width: 80px;">รหัสวิชา</th>
                <th>รายวิชา</th>
                <th style="width: 70px;">เวลาเรียน<br>(ชั่วโมง/ปี)</th>
                <th style="width: 60px;">คะแนน<br>เต็ม</th>
                <th style="width: 60px;">ค่าเฉลี่ย<br>ในชั้นเรียน</th>
                <th style="width: 60px;">คะแนน<br>ที่ได้</th>
                <th style="width: 60px;">คิดเป็น<br>ร้อยละ</th>
                <th style="width: 70px;">ระดับผล<br>การเรียน</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_hours = 0;
            $total_score = 0;
            $count_subjects = 0;
            $total_percent = 0;
            // สร้างแถวว่างให้ครบ 15 แถวเพื่อให้ดูเหมือนแบบฟอร์ม
            $display_grades = array_pad($grades, 15, null);
            foreach ($display_grades as $g): 
                if ($g) {
                    $total_hours += $g['hours'];
                    $total_score += $g['score_total'];
                    $total_percent += $g['score_percent'];
                    $count_subjects++;
                }
            ?>
            <tr style="height: 25px;">
                <td><?= $g ? $g['code'] : '' ?></td>
                <td class="text-left"><?= $g ? $g['name'] : '' ?></td>
                <td><?= $g ? $g['hours'] : '' ?></td>
                <td><?= $g ? '๑๐๐.๐๐' : '' ?></td>
                <td><?= $g ? '-' : '' ?></td>
                <td><?= $g ? number_format($g['score_total'], 2) : '' ?></td>
                <td><?= $g ? number_format($g['score_percent'], 2) : '' ?></td>
                <td><?= $g ? $g['grade'] : '' ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #eee;">
                <td colspan="2">รวม</td>
                <td><?= $total_hours ?></td>
                <td><?= number_format($count_subjects * 100, 2) ?></td>
                <td>-</td>
                <td><?= number_format($total_score, 2) ?></td>
                <td><?= $count_subjects > 0 ? number_format($total_percent / $count_subjects, 2) : '' ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-left">
            <table class="summary-table">
                <tr>
                    <td class="text-left">คะแนนคิดเป็นร้อยละ</td>
                    <td style="width: 80px;"><?= $count_subjects > 0 ? number_format($total_percent / $count_subjects, 2) : '-' ?></td>
                </tr>
                <tr>
                    <td class="text-left">คะแนนรวมได้ลำดับที่</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="text-left">ผลการเรียนเฉลี่ย</td>
                    <td><?= $count_subjects > 0 ? number_format($total_score / ($count_subjects * 100) * 4, 2) : '-' ?></td>
                </tr>
                <tr>
                    <td class="text-left">ผลการเรียนเฉลี่ยได้ลำดับที่</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td colspan="2" class="font-bold" style="background: #eee;">ผลการประเมินกิจกรรมพัฒนาผู้เรียน</td>
                </tr>
                <tr>
                    <td class="text-left">&nbsp;&nbsp;&nbsp;แนะแนว</td>
                    <td><?= formatPassFail($ld_result['guidance_result']) ?></td>
                </tr>
                <tr>
                    <td class="text-left">&nbsp;&nbsp;&nbsp;ลูกเสือ-เนตรนารี</td>
                    <td><?= formatPassFail($ld_result['scout_result']) ?></td>
                </tr>
                <tr>
                    <td class="text-left">&nbsp;&nbsp;&nbsp;ชุมนุม กีฬาและนันทนาการ</td>
                    <td><?= formatPassFail($ld_result['club_result']) ?></td>
                </tr>
                <tr>
                    <td class="text-left">&nbsp;&nbsp;&nbsp;กิจกรรมเพื่อสังคมและสาธารณประโยชน์</td>
                    <td><?= formatPassFail($ld_result['social_result']) ?></td>
                </tr>
                <tr>
                    <td class="text-left">ผลการประเมินคุณลักษณะอันพึงประสงค์</td>
                    <td><?= getResultText($behavior['average_score'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td class="text-left">ผลการประเมินการอ่าน คิดวิเคราะห์และเขียน</td>
                    <td><?= getResultText($analytical['average_score'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td class="text-left">ผลการประเมินสมรรถนะสำคัญของผู้เรียน</td>
                    <td><?= getResultText($competency['average_score'] ?? 0) ?></td>
                </tr>
            </table>
        </div>
        <div class="summary-right">
            <div class="sig-block">
                <p>ลงชื่อ..........................................................</p>
                <p>( <?= $teacher_name ?> )</p>
                <p>ครูประจำชั้น/ครูที่ปรึกษา</p>
                <p><?= $approval_date['day'] ?> <?= $approval_date['month'] ?> <?= $approval_date['year'] ?></p>
            </div>
            <div class="sig-block">
                <p>ลงชื่อ..........................................................</p>
                <p>( <?= $acad_name ?> )</p>
                <p><?= $acad_pos ?></p>
                <p><?= $approval_date['day'] ?> <?= $approval_date['month'] ?> <?= $approval_date['year'] ?></p>
            </div>
            <div class="sig-block">
                <p>ลงชื่อ..........................................................</p>
                <p>( <?= $director_name ?> )</p>
                <p>ผู้อำนวยการโรงเรียน</p>
                <p><?= $approval_date['day'] ?> <?= $approval_date['month'] ?> <?= $approval_date['year'] ?></p>
            </div>
            <div class="sig-block" style="margin-top: 25px;">
                <p>ลงชื่อ..........................................................</p>
                <p>(..........................................................)</p>
                <p>ผู้ปกครองนักเรียน</p>
            </div>
        </div>
    </div>
</div>

<?php endforeach; ?>

</body>
</html>
