<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$teacher_id = $_SESSION['user_id'];
$subject_id = $data['subject_id'] ?? '';
$classroom_id = $data['classroom_id'] ?? '';
$academic_year = $data['academic_year'] ?? '2567';
$semester = $data['semester'] ?? 1;
$scores = $data['scores'] ?? []; // [{student_id, unit_id, score}]

if (empty($subject_id) || empty($classroom_id) || empty($scores)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($scores as $s) {
        $student_id = $s['student_id'];
        $unit_id = $s['unit_id'];
        $score = $s['score'] ?? 0;

        $stmt = $pdo->prepare('
            INSERT INTO unit_scores (student_id, learning_unit_id, score)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score)
        ');
        $stmt->execute([$student_id, $unit_id, $score]);
    }

    // คำนวณคะแนนรวมเป็นเปอร์เซ็นต์ (100%) และบันทึกลงใน grades
    // 1. ดึงคะแนนเต็มทั้งหมดของหน่วยการเรียนรู้ในเทอมนี้
    $stmt = $pdo->prepare('SELECT SUM(max_score) as total_max FROM learning_units WHERE subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?');
    $stmt->execute([$subject_id, $classroom_id, $academic_year, $semester]);
    $total_max = $stmt->fetch()['total_max'] ?: 0;

    if ($total_max > 0) {
        // 2. ดึงคะแนนที่นักเรียนแต่ละคนได้
        $stmt = $pdo->prepare('
            SELECT s.id as student_id, SUM(us.score) as student_total
            FROM students s
            JOIN unit_scores us ON s.id = us.student_id
            JOIN learning_units lu ON us.learning_unit_id = lu.id
            WHERE lu.subject_id = ? AND lu.classroom_id = ? AND lu.academic_year = ? AND lu.semester = ?
            GROUP BY s.id
        ');
        $stmt->execute([$subject_id, $classroom_id, $academic_year, $semester]);
        $student_totals = $stmt->fetchAll();

        foreach ($student_totals as $st) {
            $percent_score = ($st['student_total'] / $total_max) * 100;
            
            // บันทึกลงใน grades
            $col = $semester == 1 ? 'score_semester1' : 'score_semester2';
            
            $stmt = $pdo->prepare("
                INSERT INTO grades (student_id, subject_id, classroom_id, teacher_id, academic_year, semester, $col)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE $col = VALUES($col), teacher_id = VALUES(teacher_id)
            ");
            $stmt->execute([$st['student_id'], $subject_id, $classroom_id, $teacher_id, $academic_year, $semester, $percent_score]);

            // คำนวณคะแนนเฉลี่ยรายปีและเกรด
            $stmt = $pdo->prepare('SELECT score_semester1, score_semester2 FROM grades WHERE student_id = ? AND subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?');
            $stmt->execute([$st['student_id'], $subject_id, $classroom_id, $academic_year, $semester]);
            $g = $stmt->fetch();
            
            $avg = ($g['score_semester1'] + $g['score_semester2']) / 2;
            
            // คำนวณเกรด
            $grade = '0';
            if ($avg >= 80) $grade = '4';
            else if ($avg >= 75) $grade = '3.5';
            else if ($avg >= 70) $grade = '3';
            else if ($avg >= 65) $grade = '2.5';
            else if ($avg >= 60) $grade = '2';
            else if ($avg >= 55) $grade = '1.5';
            else if ($avg >= 50) $grade = '1';
            else $grade = '0';

            $stmt = $pdo->prepare('UPDATE grades SET score_annual_avg = ?, grade = ? WHERE student_id = ? AND subject_id = ? AND classroom_id = ? AND academic_year = ? AND semester = ?');
            $stmt->execute([$avg, $grade, $st['student_id'], $subject_id, $classroom_id, $academic_year, $semester]);
        }
    }

    $pdo->commit();
    echo json_encode(['message' => 'บันทึกคะแนนหน่วยการเรียนรู้สำเร็จ']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
