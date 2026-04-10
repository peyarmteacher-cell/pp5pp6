<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$type = $_GET['type'] ?? 'subject';
$assignment_id = $_GET['assignment_id'] ?? '';
$classroom_id = $_GET['classroom_id'] ?? '';

if ($type === 'subject' && $assignment_id) {
    $stmt = $pdo->prepare('
        SELECT ta.*, s.name as subject_name, s.code as subject_code,
               c.level, c.room, u.name as teacher_name
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        JOIN classrooms c ON ta.classroom_id = c.id
        JOIN users u ON ta.teacher_id = u.id
        WHERE ta.id = ?
    ');
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    $title = "รายวิชา {$assignment['subject_code']} {$assignment['subject_name']}";
    $subtitle = "ชั้น {$assignment['level']}/{$assignment['room']}";
    $teacher = $assignment['teacher_name'];
} else {
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    $title = "แบบบันทึกผลการพัฒนาคุณภาพผู้เรียน (รายชั้น)";
    $subtitle = "ชั้น {$classroom['level']}/{$classroom['room']}";
    
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
    
    $teacher = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    if ($ct['t2_name']) {
        $teacher .= ($teacher ? ' และ ' : '') . $ct['t2_name'] . ' ' . $ct['t2_last'];
    }
    if (!$teacher) $teacher = $_SESSION['name'];
}
?>

<div class="page" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px solid black; padding: 50px;">
    <div style="text-align: center; margin-bottom: 50px;">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" style="width: 195px; height: 195px; object-fit: contain; margin-bottom: 30px;" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h1 style="font-size: 36px; margin: 10px 0;">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.5)</h1>
        <h2 style="font-size: 28px; margin: 10px 0;"><?= $title ?></h2>
        <h3 style="font-size: 24px; margin: 10px 0;"><?= $subtitle ?></h3>
    </div>

    <div style="text-align: center; margin-bottom: 50px; width: 100%;">
        <div style="border-top: 1px solid black; border-bottom: 1px solid black; padding: 20px 0; margin: 20px 0;">
            <p style="font-size: 22px; margin: 10px 0;">ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?> ปีการศึกษา <?= $year ?></p>
            <p style="font-size: 22px; margin: 10px 0;">โรงเรียน<?= $school_name ?></p>
            <p style="font-size: 20px; margin: 10px 0;"><?= $affiliation ?></p>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 30px; width: 100%; align-items: center; margin-top: 30px;">
        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $teacher ?> )</p>
            <p style="font-size: 16px;"><?= $type === 'subject' ? 'ครูผู้สอน' : 'ครูประจำชั้น' ?></p>
        </div>

        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $academic_head_name ?: '..........................................................' ?> )</p>
            <p style="font-size: 16px;"><?= $academic_head_position ?></p>
        </div>

        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $director_name ?: '..........................................................' ?> )</p>
            <p style="font-size: 16px;">ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
        </div>
    </div>
</div>

</body>
</html>
