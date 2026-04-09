<?php
require_once 'report_header.php';

$year = $_GET['year'] ?? '';
$semester = $_GET['semester'] ?? '1';
$type = $_GET['type'] ?? 'subject';
$assignment_id = $_GET['assignment_id'] ?? '';
$classroom_id = $_GET['classroom_id'] ?? '';

if ($type === 'subject' && $assignment_id) {
    // ดึงข้อมูลการสอน
    $stmt = $pdo->prepare('
        SELECT ta.*, s.name as subject_name, s.code as subject_code, s.hours, s.credits,
               c.level, c.room, u.name as teacher_name
        FROM teacher_assignments ta
        JOIN subjects s ON ta.subject_id = s.id
        JOIN classrooms c ON ta.classroom_id = c.id
        JOIN users u ON ta.teacher_id = u.id
        WHERE ta.id = ?
    ');
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) die('Assignment not found');
    
    $classroom_id = $assignment['classroom_id'];
    $subject_id = $assignment['subject_id'];
    $level = $assignment['level'];
    $room = $assignment['room'];
    $subject_name = $assignment['subject_name'];
    $subject_code = $assignment['subject_code'];
    $teacher_name = $assignment['teacher_name'];
} else if ($type === 'class' && $classroom_id) {
    // ดึงข้อมูลห้องเรียน
    $stmt = $pdo->prepare('SELECT * FROM classrooms WHERE id = ?');
    $stmt->execute([$classroom_id]);
    $classroom = $stmt->fetch();
    
    if (!$classroom) die('Classroom not found');
    
    $level = $classroom['level'];
    $room = $classroom['room'];
    $teacher_name = $_SESSION['name']; // สมมติว่าเป็นครูประจำชั้นที่พิมพ์
} else {
    die('Invalid parameters');
}

// ดึงรายชื่อนักเรียน
$stmt = $pdo->prepare('SELECT * FROM students WHERE classroom_id = ? AND academic_year = ? AND status = "studying" ORDER BY student_code ASC');
$stmt->execute([$classroom_id, $year]);
$students = $stmt->fetchAll();

// สถิตินักเรียน
$total_students = count($students);
$male_students = count(array_filter($students, fn($s) => $s['gender'] === 'ชาย' || $s['prefix'] === 'เด็กชาย' || $s['prefix'] === 'นาย'));
$female_students = $total_students - $male_students;

?>

<div class="page">
    <div class="header">
        <?php if ($logo_url): ?>
            <img src="<?= $logo_url ?>" class="logo" referrerPolicy="no-referrer">
        <?php endif; ?>
        <h2 style="margin: 5px 0;">สมุดบันทึกการพัฒนาคุณภาพผู้เรียน (ปพ.5)</h2>
        <h3 style="margin: 5px 0;"><?= $school_name ?></h3>
        <p>สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3</p>
    </div>

    <div style="margin-bottom: 20px;">
        <table class="border-none no-border">
            <tr>
                <td class="text-left">ชั้น <?= $level ?>/<?= $room ?></td>
                <td class="text-left">ภาคเรียนที่ <?= $semester === 'annual' ? '1-2' : $semester ?></td>
                <td class="text-left">ปีการศึกษา <?= $year ?></td>
            </tr>
            <?php if ($type === 'subject'): ?>
            <tr>
                <td class="text-left" colspan="2">รายวิชา <?= $subject_code ?> <?= $subject_name ?></td>
                <td class="text-left">ครูผู้สอน <?= $teacher_name ?></td>
            </tr>
            <?php else: ?>
            <tr>
                <td class="text-left" colspan="3">ครูประจำชั้น <?= $teacher_name ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <table class="no-border" style="margin-bottom: 10px;">
        <tr>
            <td class="text-left">นักเรียนต้นปีการศึกษา</td>
            <td>ชาย <?= $male_students ?> คน</td>
            <td>หญิง <?= $female_students ?> คน</td>
            <td>รวม <?= $total_students ?> คน</td>
        </tr>
    </table>

    <h4 style="text-align: center; margin: 10px 0;">สรุปผลสัมฤทธิ์ทางการเรียนรู้</h4>
    <table>
        <thead>
            <tr>
                <th rowspan="2">รหัส</th>
                <th rowspan="2">รายวิชา</th>
                <th colspan="10">ระดับผลการเรียน</th>
            </tr>
            <tr>
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
        </thead>
        <tbody>
            <?php
            // ถ้าเป็นรายวิชาเดียว
            if ($type === 'subject') {
                $grades_count = array_fill_keys(['มส', 'ร', '0', '1', '1.5', '2', '2.5', '3', '3.5', '4'], 0);
                
                $stmt = $pdo->prepare('SELECT grade FROM grades WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?');
                $stmt->execute([$subject_id, $classroom_id, $year, $semester === 'annual' ? 0 : $semester]); // สมมติ 0 คือรายปี
                $grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($grades as $g) {
                    if (isset($grades_count[$g])) $grades_count[$g]++;
                }
                
                echo "<tr>";
                echo "<td>$subject_code</td>";
                echo "<td class='text-left'>$subject_name</td>";
                foreach ($grades_count as $count) {
                    echo "<td>" . ($count > 0 ? $count : '-') . "</td>";
                }
                echo "</tr>";
            } else {
                // ถ้าเป็นรายชั้น ต้องดึงทุกวิชาในห้องนั้น
                $stmt = $pdo->prepare('
                    SELECT DISTINCT s.id, s.code, s.name 
                    FROM teacher_assignments ta
                    JOIN subjects s ON ta.subject_id = s.id
                    WHERE ta.classroom_id = ? AND ta.academic_year = ?
                ');
                $stmt->execute([$classroom_id, $year]);
                $subjects = $stmt->fetchAll();
                
                foreach ($subjects as $s) {
                    $grades_count = array_fill_keys(['มส', 'ร', '0', '1', '1.5', '2', '2.5', '3', '3.5', '4'], 0);
                    $stmt_g = $pdo->prepare('SELECT grade FROM grades WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?');
                    $stmt_g->execute([$s['id'], $classroom_id, $year, $semester === 'annual' ? 0 : $semester]);
                    $grades = $stmt_g->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($grades as $g) {
                        if (isset($grades_count[$g])) $grades_count[$g]++;
                    }
                    
                    echo "<tr>";
                    echo "<td>{$s['code']}</td>";
                    echo "<td class='text-left'>{$s['name']}</td>";
                    foreach ($grades_count as $count) {
                        echo "<td>" . ($count > 0 ? $count : '-') . "</td>";
                    }
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <table class="border-none no-border">
            <tr>
                <td style="width: 50%;"></td>
                <td style="text-align: center;">
                    <p>ลงชื่อ..........................................................ครูประจำชั้น/ครูผู้สอน</p>
                    <p>(..........................................................)</p>
                    <p>วันที่ .......... เดือน .......................... พ.ศ. ...............</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; border: 1px solid black; padding: 20px; text-align: center;">
        <h4 style="margin: 0 0 10px 0;">การอนุมัติผลการเรียน</h4>
        <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 20px;">
            <label><input type="checkbox"> อนุมัติ</label>
            <label><input type="checkbox"> ไม่อนุมัติ</label>
        </div>
        <p style="margin-top: 30px;">(ลงชื่อ)..........................................................</p>
        <p>(นายสยาม เชียงเครือ)</p>
        <p>ผู้อำนวยการโรงเรียน</p>
    </div>
</div>

</body>
</html>
