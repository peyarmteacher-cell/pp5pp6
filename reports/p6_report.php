<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$classroom_id = $_GET['classroom_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';

if (!$classroom_id || !$student_id) die('Missing parameters');

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
    $students_to_print = $students_to_print = $stmt->fetchAll();
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

    // ดึงชื่อครูประจำชั้น
    $stmt_t = $pdo->prepare('
        SELECT u1.name as t1_name, u1.last_name as t1_last,
               u2.name as t2_name, u2.last_name as t2_last
        FROM classrooms c
        LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
        LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
        WHERE c.id = ?
    ');
    $stmt_t->execute([$classroom_id]);
    $ct = $stmt_t->fetch();
    
    $teacher_name = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    if ($ct['t2_name']) {
        $teacher_name .= ($teacher_name ? ' และ ' : '') . $ct['t2_name'] . ' ' . $ct['t2_last'];
    }
    if (!$teacher_name) $teacher_name = '..........................................................';
?>

<div class="page">
    <div class="header">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" class="logo" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h3 style="margin: 5px 0;">แบบรายงานประจำตัวนักเรียน : ผลการพัฒนาคุณภาพผู้เรียนรายบุคคล (ปพ.6)</h3>
        <p style="margin: 5px 0;"><?= $school_name ?> <?= $affiliation ?></p>
        <p>ชั้น <?= $classroom['level'] ?>/<?= $classroom['room'] ?> ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?> ปีการศึกษา <?= $year ?></p>
    </div>

    <div style="margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <table class="border-none no-border">
            <tr>
                <td class="text-left">ชื่อ-นามสกุล: <span class="font-bold"><?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?></span></td>
                <td class="text-left">เลขประจำตัว: <span class="font-bold"><?= $student['student_code'] ?></span></td>
                <td class="text-left">เลขที่: <span class="font-bold"><?= array_search($student['id'], array_column($students_to_print, 'id')) + 1 ?></span></td>
            </tr>
        </table>
    </div>

    <h4 class="text-left" style="margin: 10px 0;">ผลการเรียนรายวิชา</h4>
    <table>
        <thead>
            <tr style="background: #f8fafc;">
                <th>รหัสวิชา</th>
                <th>รายวิชา</th>
                <th>เวลาเรียน (ชม.)</th>
                <th>คะแนนเต็ม</th>
                <th>คะแนนที่ได้</th>
                <th>ร้อยละ</th>
                <th>ระดับผลการเรียน</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_hours = 0;
            $total_score = 0;
            $count_subjects = 0;
            foreach ($grades as $g): 
                $total_hours += $g['hours'];
                $total_score += $g['score_total'];
                $count_subjects++;
            ?>
            <tr>
                <td><?= $g['code'] ?></td>
                <td class="text-left"><?= $g['name'] ?></td>
                <td><?= $g['hours'] ?></td>
                <td>100</td>
                <td><?= $g['score_total'] ?: '-' ?></td>
                <td><?= $g['score_percent'] ?: '-' ?></td>
                <td class="font-bold"><?= $g['grade'] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="2">รวม</td>
                <td><?= $total_hours ?></td>
                <td><?= $count_subjects * 100 ?></td>
                <td><?= $total_score ?></td>
                <td colspan="2">เกรดเฉลี่ย: <?= $count_subjects > 0 ? number_format($total_score / ($count_subjects * 100) * 4, 2) : '-' ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="display: grid; grid-cols-2; gap: 20px; margin-top: 20px;">
        <div>
            <h4 class="text-left" style="margin: 5px 0;">ผลการประเมินกิจกรรมพัฒนาผู้เรียน</h4>
            <table style="width: 100%;">
                <tr>
                    <td class="text-left">กิจกรรมแนะแนว</td>
                    <td style="width: 80px;"><?= $ld_result['guidance_result'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td class="text-left">กิจกรรมนักเรียน (ลูกเสือ/เนตรนารี)</td>
                    <td><?= $ld_result['scout_result'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td class="text-left">กิจกรรมชุมนุม</td>
                    <td><?= $ld_result['club_result'] ?: '-' ?></td>
                </tr>
                <tr>
                    <td class="text-left">กิจกรรมเพื่อสังคมและสาธารณประโยชน์</td>
                    <td><?= $ld_result['social_result'] ?: '-' ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <div style="display: flex; justify-content: space-between; gap: 20px;">
            <div style="text-align: center; flex: 1;">
                <p>ลงชื่อ..........................................................</p>
                <p>( <?= $teacher_name ?> )</p>
                <p>ครูประจำชั้น/ครูที่ปรึกษา</p>
            </div>
            <div style="text-align: center; flex: 1;">
                <p>ลงชื่อ..........................................................</p>
                <p>( <?= $director_name ?: '..........................................................' ?> )</p>
                <p>ผู้อำนวยการโรงเรียน</p>
            </div>
        </div>
    </div>
</div>

<?php endforeach; ?>

</body>
</html>
