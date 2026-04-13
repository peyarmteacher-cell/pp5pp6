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
$clean_level = str_replace(['ป.', 'ม.'], '', $classroom['level'] ?? '');

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
        line-height: 1.1;
        vertical-align: bottom;
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

    // ดึงหน้าต่างๆ มาแสดง
    include 'p6_page2.php'; // หน้าปก (ขึ้นก่อนเป็นหน้าที่ 1)
    include 'p6_page1.php'; // หน้าสรุปคะแนน (เป็นหน้าที่ 2)
    include 'p6_page3.php'; // หน้าคู่มือ (เป็นหน้าที่ 3)
endforeach;
?>
</body>
</html>
