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
    $teacher = $_SESSION['name'];
}
?>

<div class="page" style="display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px solid black; padding: 50px;">
    <div style="text-align: center; margin-bottom: 50px;">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" style="width: 150px; height: 150px; object-fit: contain; margin-bottom: 30px;" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h1 style="font-size: 36px; margin: 10px 0;">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.5)</h1>
        <h2 style="font-size: 28px; margin: 10px 0;"><?= $title ?></h2>
        <h3 style="font-size: 24px; margin: 10px 0;"><?= $subtitle ?></h3>
    </div>

    <div style="text-align: center; margin-bottom: 50px; width: 100%;">
        <div style="border-top: 1px solid black; border-bottom: 1px solid black; padding: 20px 0; margin: 20px 0;">
            <p style="font-size: 22px; margin: 10px 0;">ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?> ปีการศึกษา <?= $year ?></p>
            <p style="font-size: 22px; margin: 10px 0;">โรงเรียน<?= $school_name ?></p>
            <p style="font-size: 20px; margin: 10px 0;">สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 50px;">
        <p style="font-size: 20px;">ชื่อครูผู้สอน/ครูประจำชั้น</p>
        <p style="font-size: 24px; font-weight: bold; margin-top: 10px;"><?= $teacher ?></p>
    </div>
</div>

</body>
</html>
