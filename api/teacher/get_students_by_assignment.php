<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$classroom_id = $_GET['classroom_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

if (empty($classroom_id) || empty($subject_id)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    // ดึงรายชื่อนักเรียนในห้องเรียนนั้น
    $stmt = $pdo->prepare('
        SELECT s.id, s.student_code, s.name, s.prefix,
               g.score_midterm, g.score_final, g.score_total, g.grade,
               g.score_semester1, g.score_semester2, g.score_annual_avg,
               cs.item1, cs.item2, cs.item3, cs.item4, cs.item5, cs.item6, cs.item7, cs.item8, cs.average_score,
               ascore.score as analytical_score
        FROM students s
        LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = ? AND g.classroom_id = ? AND g.academic_year = ? AND g.semester = ?
        LEFT JOIN characteristics_scores cs ON s.id = cs.student_id AND cs.subject_id = ? AND cs.classroom_id = ? AND cs.academic_year = ? AND cs.semester = ?
        LEFT JOIN analytical_scores ascore ON s.id = ascore.student_id AND ascore.subject_id = ? AND ascore.classroom_id = ? AND ascore.academic_year = ? AND ascore.semester = ?
        WHERE s.classroom_id = ?
        ORDER BY s.student_code ASC
    ');
    $stmt->execute([
        $subject_id, $classroom_id, $academic_year, $semester,
        $subject_id, $classroom_id, $academic_year, $semester,
        $subject_id, $classroom_id, $academic_year, $semester,
        $classroom_id
    ]);
    $students = $stmt->fetchAll();

    // ดึงคะแนนหน่วยการเรียนรู้ของนักเรียนแต่ละคน
    foreach ($students as &$student) {
        $stmt = $pdo->prepare('
            SELECT us.learning_unit_id, us.score
            FROM unit_scores us
            JOIN learning_units lu ON us.learning_unit_id = lu.id
            WHERE us.student_id = ? AND lu.subject_id = ? AND lu.classroom_id = ? AND lu.academic_year = ? AND lu.semester = ?
        ');
        $stmt->execute([$student['id'], $subject_id, $classroom_id, $academic_year, $semester]);
        $unit_scores = $stmt->fetchAll();
        $student['unit_scores'] = $unit_scores;
    }

    echo json_encode($students);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
