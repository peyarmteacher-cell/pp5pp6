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
        min-height: 100px;
    }
    .p6-logo-left {
        position: absolute;
        left: 0;
        top: 0;
        width: 80px;
        height: 80px;
        object-fit: contain;
    }
    .p6-logo-center {
        display: block;
        margin: 0 auto 15px;
        width: 100px;
        height: 100px;
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
    .text-center { text-align: center !important; }
    .dotted-line {
        border-bottom: 1px dotted black;
        display: inline-block;
        min-width: 100px;
        padding: 0 5px;
        text-align: center;
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
    .page-break {
        page-break-after: always;
    }
    .cover-page {
        text-align: center;
        padding: 20mm;
    }
    .guide-page {
        font-size: 14px;
        line-height: 1.6;
    }
    .guide-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 12px;
    }
    .guide-table td {
        border: 1px solid black;
        padding: 3px;
        text-align: center;
    }
    @media print {
        .page { margin: 0; padding: 10mm; border: none; box-shadow: none; }
    }
</style>

<?php
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

    // คำนวณข้อมูลเพิ่มเติมสำหรับหน้าปก
    $bday = formatThaiDate($student['birthday'] ?? '');
    $age_years = $student['age'] ?? 0;
    $age_months = 0;
    
    if (!empty($student['birthday'])) {
        $birthDate = new DateTime($student['birthday']);
        $today = new DateTime();
        $age_diff = $today->diff($birthDate);
        $age_years = $age_diff->y;
        $age_months = $age_diff->m;
    }
?>

<!-- หน้าที่ 1: ผลการเรียน (Score Summary) -->
<div class="page p6-container">
    <div class="p6-header">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" class="p6-logo-left" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h3 style="margin: 0; font-size: 18px; padding-top: 10px;">แบบรายงานประจำตัวนักเรียน : ผลการพัฒนาคุณภาพผู้เรียนรายบุคคล (ปพ.๖)</h3>
        <p style="margin: 5px 0; font-size: 16px;">โรงเรียน<?= $school_name ?> <?= $affiliation ?></p>
        <p style="margin: 5px 0; font-size: 16px;">ชั้นประถมศึกษาปีที่ <?= $classroom['level'] ?> ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?> ปีการศึกษา <?= $year ?></p>
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
                <td><?= $g ? '100.00' : '' ?></td>
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

<!-- หน้าที่ 2: ปก (Cover Page) -->
<div class="page cover-page">
    <?php if ($logo_url): ?>
        <img src="<?= $logo_url ?>" class="p6-logo-center" referrerPolicy="no-referrer">
    <?php endif; ?>
    
    <h2 style="margin: 20px 0 10px; font-size: 24px;">แบบรายงานประจำตัวนักเรียน</h2>
    <h3 style="margin: 0 0 20px; font-size: 20px;">ผลการพัฒนาคุณภาพผู้เรียนรายบุคคล (ปพ.๖)</h3>
    
    <h3 style="margin: 30px 0 10px; font-size: 22px;">โรงเรียน<?= $school_name ?></h3>
    <p style="font-size: 18px; margin: 5px 0;"><?= $affiliation ?></p>
    <p style="font-size: 18px; margin: 5px 0;"><?= $district ?> <?= $province ?></p>

    <div style="margin-top: 50px; text-align: left; padding-left: 50px; font-size: 18px; line-height: 2.5;">
        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
            <span>ชื่อ</span> <span class="dotted-line" style="flex: 1; min-width: 150px;"><?= $student['prefix'] ?><?= $student['name'] ?></span> 
            <span>นามสกุล</span> <span class="dotted-line" style="flex: 1; min-width: 150px;"><?= $student['last_name'] ?></span>
        </div>
        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
            <span>วันเกิด</span> <span class="dotted-line" style="min-width: 180px;"><?= $bday['day'] ?> <?= $bday['month'] ?> <?= $bday['year'] ?></span> 
            <span>อายุ</span> <span class="dotted-line" style="min-width: 40px;"><?= $age_years ?></span> <span>ปี</span> 
            <span class="dotted-line" style="min-width: 40px;"><?= $age_months ?></span> <span>เดือน</span>
        </div>
        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
            <span>เลขประจำตัวนักเรียน</span> <span class="dotted-line" style="min-width: 120px;"><?= $student['student_code'] ?></span> 
            <span>เลขประจำตัวประชาชน</span> <span class="dotted-line" style="min-width: 200px;"><?= $student['national_id'] ?></span>
        </div>
        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
            <span>ชั้น</span> <span class="dotted-line" style="min-width: 180px;">ประถมศึกษาปีที่ <?= $classroom['level'] ?></span> 
            <span>เลขที่</span> <span class="dotted-line" style="min-width: 80px;"><?= array_search($student['id'], array_column($students_to_print, 'id')) + 1 ?></span>
        </div>
        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
            <span>ปีการศึกษา</span> <span class="dotted-line" style="min-width: 120px;"><?= $year ?></span>
        </div>
    </div>

    <div style="margin-top: 60px; text-align: right; padding-right: 50px;">
        <div class="sig-block" style="display: inline-block; text-align: center; margin-left: 50px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $teacher_name ?> )</p>
            <p>ครูประจำชั้น/ครูที่ปรึกษา</p>
        </div>
        <br>
        <div class="sig-block" style="display: inline-block; text-align: center; margin-left: 50px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $acad_name ?> )</p>
            <p><?= $acad_pos ?></p>
        </div>
        <br>
        <div class="sig-block" style="display: inline-block; text-align: center; margin-left: 50px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $director_name ?> )</p>
            <p>ผู้อำนวยการโรงเรียน</p>
        </div>
        <div style="margin-top: 20px; text-align: center; width: 300px; float: right;">
            วันที่ <span class="dotted-line" style="min-width: 150px;"><?= $approval_date['day'] ?> <?= $approval_date['month'] ?> <?= $approval_date['year'] ?></span>
        </div>
    </div>
</div>

<!-- หน้าที่ 3: คู่มือสำหรับผู้ปกครอง (Parent Guide) -->
<div class="page guide-page">
    <h3 class="text-center" style="font-size: 20px; margin-bottom: 20px;">คำแนะนำสำหรับผู้ปกครอง</h3>
    <p>เรียน ท่านผู้ปกครองนักเรียน</p>
    <p style="text-indent: 50px;">เมื่อท่านได้รับแบบรายงานประจำตัวนักเรียนนี้แล้ว โปรดสละเวลาพิจารณาข้อมูลต่าง ๆ ดังนี้</p>
    <p>1. โปรดตรวจสอบความถูกต้องของข้อมูลนักเรียน และบันทึกหากมีการเปลี่ยนแปลงแก้ไขข้อมูล</p>
    <p>2. โปรดตรวจสอบผลการประเมินภาวะโภชนาการ จากน้ำหนัก - ส่วนสูง ตามเกณฑ์มาตรฐาน ถ้ามีผลผิดปกติ เช่น น้ำหนักน้อยกว่าเกณฑ์ เตี้ย อ้วน เริ่มอ้วน หรือ ผอม ควรหาทางช่วยเหลือ หรือปรึกษาแพทย์ ในกรณีที่บุตรหลานของท่านมีโรคประจำตัว หรือมีสิ่งผิดปกติโปรดแจ้งครูประจำชั้นทราบด้วย</p>
    <p>3. โปรดตรวจสอบผลการไปโรงเรียนของเด็กอย่างสม่ำเสมอ ติดต่อกับโรงเรียนทันทีเมื่อทราบว่าเด็กหยุดเรียนโดยไม่ได้รับอนุญาต ทั้งนี้เพื่อป้องกันไม่ให้เกิดปัญหาพฤติกรรมหรือขาดเรียนนาน</p>
    <p>4. โปรดตรวจสอบผลการเรียนรายวิชา ผลการประเมินกิจกรรมพัฒนาผู้เรียน ผลการประเมินคุณลักษณะอันพึงประสงค์ ผลการประเมินการอ่าน คิดวิเคราะห์และเขียน ผลการประเมินค่านิยม 12 ประการ และผลการประเมินสมรรถนะสำคัญของผู้เรียน โดยตรวจสอบว่านักเรียนมีผลการประเมินด้านใด อยู่ในระดับใด ผ่านเกณฑ์การประเมินการศึกษาของสถานศึกษาหรือไม่ และควรได้รับการช่วยเหลือด้านใด</p>
    <p>5. เกณฑ์การประเมิน</p>
    <div style="padding-left: 30px;">
        <p>5.1 นักเรียนต้องมีผลการประเมินรายวิชาตั้งแต่ระดับ 1 ขึ้นไปทุกวิชา</p>
        <p>5.2 นักเรียนมีผลการประเมินการอ่าน คิดวิเคราะห์ และเขียน ผ่านเกณฑ์การประเมินในระดับดีเยี่ยม/ดี/ผ่าน</p>
        <p>5.3 นักเรียนมีผลการประเมินคุณลักษณะอันพึงประสงค์ ผ่านเกณฑ์การประเมินในระดับ ดีเยี่ยม/ดี/ผ่าน</p>
        <p>5.4 นักเรียนเข้าร่วมกิจกรรมพัฒนาผู้เรียน และได้ผลการประเมิน 'ผ' ทุกกิจกรรม</p>
        <p>5.5 นักเรียนมีผลการประเมินค่านิยม 12 ประการ ผ่านเกณฑ์การประเมินในระดับ ดีเยี่ยม/ดี/ผ่าน</p>
        <p>5.6 นักเรียนมีผลการประเมินสมรรถนะสำคัญของผู้เรียน ผ่านเกณฑ์การประเมินในระดับ ดีเยี่ยม/ดี/ผ่าน</p>
    </div>
    <p>6. โปรดตรวจสอบความคิดเห็นและข้อเสนอแนะของครูประจำชั้นต่อนักเรียน</p>
    <p>7. โปรดสละเวลาให้ความคิดเห็นและเสนอแนะเกี่ยวกับตัวนักเรียน ความเห็นของท่านจะเป็นประโยชน์ต่อตัวนักเรียนในปกครองของท่านเอง เพราะจะช่วยให้ครูเข้าใจนักเรียนได้ดียิ่งขึ้น และจะช่วยพัฒนานักเรียนได้ถูกต้องต่อไป</p>
    
    <div style="margin-top: 20px;">
        <p class="font-bold">คำอธิบายเกณฑ์ ผลการประเมินรายวิชา</p>
        <div style="display: flex; justify-content: space-between;">
            <table class="guide-table" style="width: 32%;">
                <tr><td>คะแนน</td><td>ผลการเรียน</td><td>ความหมาย</td></tr>
                <tr><td>80-100</td><td>4</td><td>ดีเยี่ยม</td></tr>
                <tr><td>75-79</td><td>3.5</td><td>ดีมาก</td></tr>
                <tr><td>70-74</td><td>3</td><td>ดี</td></tr>
                <tr><td>65-69</td><td>2.5</td><td>ค่อนข้างดี</td></tr>
            </table>
            <table class="guide-table" style="width: 32%;">
                <tr><td>คะแนน</td><td>ผลการเรียน</td><td>ความหมาย</td></tr>
                <tr><td>60-64</td><td>2</td><td>ปานกลาง</td></tr>
                <tr><td>55-59</td><td>1.5</td><td>พอใช้</td></tr>
                <tr><td>50-54</td><td>1</td><td>ผ่านเกณฑ์ขั้นต่ำ</td></tr>
                <tr><td>0-49</td><td>0</td><td>ต่ำกว่าเกณฑ์</td></tr>
            </table>
            <table class="guide-table" style="width: 32%;">
                <tr><td>ผลการเรียน</td><td>ความหมาย</td></tr>
                <tr><td>ร</td><td>รอการตัดสิน</td></tr>
                <tr><td>มส</td><td>ไม่มีสิทธิ์สอบ</td></tr>
                <tr><td>ผ</td><td>ผ่าน</td></tr>
                <tr><td>มผ</td><td>ไม่ผ่าน</td></tr>
            </table>
        </div>
    </div>
    
    <p class="text-right" style="margin-top: 40px;">ขอขอบพระคุณ</p>
</div>

<?php endforeach; ?>

</body>
</html>
