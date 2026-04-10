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
    
    // ดึงตำแหน่งครู
    $stmt_p = $pdo->prepare('SELECT position FROM users WHERE id = ?');
    $stmt_p->execute([$assignment['teacher_id']]);
    $u_pos = $stmt_p->fetch();
    $teacher_position = formatTeacherPosition($u_pos['position'] ?? 'ครู');
} else {
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    $title = "แบบบันทึกผลการพัฒนาคุณภาพผู้เรียน (รายชั้น)";
    $subtitle = "ชั้น {$classroom['level']}/{$classroom['room']}";
    
    // ดึงชื่อครูประจำชั้น
    $stmt_t = $pdo->prepare('
        SELECT u1.id as t1_id, u1.name as t1_name, u1.last_name as t1_last, u1.position as t1_pos,
               u2.id as t2_id, u2.name as t2_name, u2.last_name as t2_last, u2.position as t2_pos
        FROM classrooms c
        LEFT JOIN users u1 ON c.teacher_id_1 = u1.id
        LEFT JOIN users u2 ON c.teacher_id_2 = u2.id
        WHERE c.id = ?
    ');
    $stmt_t->execute([$classroom_id]);
    $ct = $stmt_t->fetch();
    
    $teacher = $ct['t1_name'] ? $ct['t1_name'] . ' ' . $ct['t1_last'] : '';
    $teacher_position = formatTeacherPosition($ct['t1_pos'] ?? 'ครู');
    
    if ($ct['t2_name']) {
        $teacher .= ($teacher ? ' และ ' : '') . $ct['t2_name'] . ' ' . $ct['t2_last'];
        // ถ้ามี 2 คน อาจจะแสดงตำแหน่งรวมๆ หรือของคนแรก
    }
    
    if (!$teacher) {
        $teacher = $_SESSION['name'];
        $teacher_position = formatTeacherPosition($_SESSION['position'] ?? 'ครู');
    }
}
?>

<div class="page" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px solid black; padding: 50px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" style="width: 150px; height: 150px; object-fit: contain; margin-bottom: 20px;" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h1 style="font-size: 32px; margin: 5px 0;">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.5)</h1>
        <h2 style="font-size: 24px; margin: 5px 0;"><?= $title ?></h2>
        <h3 style="font-size: 22px; margin: 5px 0;"><?= $subtitle ?></h3>
    </div>

    <div style="text-align: center; margin-bottom: 40px; width: 100%;">
        <div style="border-top: 1px solid black; border-bottom: 1px solid black; padding: 15px 0; margin: 15px 0;">
            <p style="font-size: 20px; margin: 5px 0;">ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?> ปีการศึกษา <?= $year ?></p>
            <p style="font-size: 20px; margin: 5px 0;">โรงเรียน<?= $school_name ?></p>
            <p style="font-size: 18px; margin: 5px 0;"><?= $affiliation ?></p>
            <p style="font-size: 18px; margin: 5px 0;">อำเภอ<?= $district ?> จังหวัด<?= $province ?></p>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 25px; width: 100%; align-items: center; margin-top: 20px;">
        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $teacher ?> )</p>
            <p style="font-size: 16px;">ตำแหน่ง <?= $teacher_position ?></p>
            <p style="font-size: 14px; color: #666;"><?= $type === 'subject' ? 'ครูผู้สอน' : 'ครูประจำชั้น' ?></p>
        </div>

        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $academic_head_name ?: '..........................................................' ?> )</p>
            <p style="font-size: 16px;">ตำแหน่ง <?= $academic_head_position ?></p>
        </div>

        <div style="text-align: center;">
            <p style="font-size: 18px; margin-bottom: 5px;">ลงชื่อ..........................................................</p>
            <p style="font-size: 20px; font-weight: bold;">( <?= $director_name ?: '..........................................................' ?> )</p>
            <p style="font-size: 16px;">ตำแหน่ง ผู้อำนวยการโรงเรียน<?= $school_name ?></p>
        </div>
    </div>
</div>

</body>
</html>
