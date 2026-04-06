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

if (empty($subject_id)) {
    echo json_encode(['error' => 'Missing subject_id']);
    exit;
}

try {
    // If classroom_id is missing, try to find it or fetch by level
    if (empty($classroom_id)) {
        $stmt = $pdo->prepare('SELECT level, school_id FROM subjects WHERE id = ?');
        $stmt->execute([$subject_id]);
        $subj = $stmt->fetch();
        if (!$subj) {
            echo json_encode(['error' => 'Subject not found']);
            exit;
        }
        $level = $subj['level'];
        $school_id = $subj['school_id'];
    }

    if ($semester === 'annual') {
        // ดึงข้อมูลรายปี (รวมทั้ง 2 ภาคเรียน)
        if (!empty($classroom_id)) {
            $stmt = $pdo->prepare('
                SELECT s.id, s.student_code, s.name, s.prefix,
                       g1.score_units as sem1_units, g1.score_percent as sem1_percent, g1.grade as sem1_grade,
                       g2.score_units as sem2_units, g2.score_percent as sem2_percent, g2.grade as sem2_grade,
                       ((IFNULL(g1.score_percent, 0) + IFNULL(g2.score_percent, 0)) / 2) as annual_percent
                FROM students s
                JOIN classrooms c_target ON c_target.id = ?
                LEFT JOIN grades g1 ON s.id = g1.student_id AND g1.subject_id = ? AND g1.classroom_id = c_target.id AND g1.academic_year = ? AND g1.semester = 1
                LEFT JOIN grades g2 ON s.id = g2.student_id AND g2.subject_id = ? AND g2.classroom_id = c_target.id AND g2.academic_year = ? AND g2.semester = 2
                WHERE (s.classroom_id = c_target.id OR (s.level = c_target.level AND s.room = c_target.room AND s.school_id = c_target.school_id))
                  AND s.academic_year = ?
                ORDER BY s.student_code ASC
            ');
            $stmt->execute([
                $classroom_id,
                $subject_id, $academic_year,
                $subject_id, $academic_year,
                $academic_year
            ]);
        } else {
            $stmt = $pdo->prepare('
                SELECT s.id, s.student_code, s.name, s.prefix,
                       g1.score_units as sem1_units, g1.score_percent as sem1_percent, g1.grade as sem1_grade,
                       g2.score_units as sem2_units, g2.score_percent as sem2_percent, g2.grade as sem2_grade,
                       ((IFNULL(g1.score_percent, 0) + IFNULL(g2.score_percent, 0)) / 2) as annual_percent
                FROM students s
                LEFT JOIN grades g1 ON s.id = g1.student_id AND g1.subject_id = ? AND g1.academic_year = ? AND g1.semester = 1
                LEFT JOIN grades g2 ON s.id = g2.student_id AND g2.subject_id = ? AND g2.academic_year = ? AND g2.semester = 2
                WHERE s.level = ? AND s.school_id = ? AND s.academic_year = ?
                ORDER BY s.student_code ASC
            ');
            $stmt->execute([
                $subject_id, $academic_year,
                $subject_id, $academic_year,
                $level, $school_id, $academic_year
            ]);
        }
        $students = $stmt->fetchAll();
    } else {
        // ดึงรายชื่อนักเรียนในห้องเรียนนั้น (รายภาคเรียน)
        if (!empty($classroom_id)) {
            $stmt = $pdo->prepare('
                SELECT s.id, s.student_code, s.name, s.prefix,
                       g.score_units, g.score_midterm, g.score_final, g.score_total, g.score_percent, g.grade,
                       cs.item1, cs.item2, cs.item3, cs.item4, cs.item5, cs.item6, cs.item7, cs.item8, cs.average_score,
                       ascore.score as analytical_score
                FROM students s
                JOIN classrooms c_target ON c_target.id = ?
                LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = ? AND g.classroom_id = c_target.id AND g.academic_year = ? AND g.semester = ?
                LEFT JOIN characteristics_scores cs ON s.id = cs.student_id AND cs.subject_id = ? AND cs.classroom_id = c_target.id AND cs.academic_year = ? AND cs.semester = ?
                LEFT JOIN analytical_scores ascore ON s.id = ascore.student_id AND ascore.subject_id = ? AND ascore.classroom_id = c_target.id AND ascore.academic_year = ? AND ascore.semester = ?
                WHERE (s.classroom_id = c_target.id OR (s.level = c_target.level AND s.room = c_target.room AND s.school_id = c_target.school_id))
                  AND s.academic_year = ?
                ORDER BY s.student_code ASC
            ');
            $stmt->execute([
                $classroom_id,
                $subject_id, $academic_year, $semester,
                $subject_id, $academic_year, $semester,
                $subject_id, $academic_year, $semester,
                $academic_year
            ]);
        } else {
            $stmt = $pdo->prepare('
                SELECT s.id, s.student_code, s.name, s.prefix,
                       g.score_units, g.score_midterm, g.score_final, g.score_total, g.score_percent, g.grade,
                       cs.item1, cs.item2, cs.item3, cs.item4, cs.item5, cs.item6, cs.item7, cs.item8, cs.average_score,
                       ascore.score as analytical_score
                FROM students s
                LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = ? AND g.academic_year = ? AND g.semester = ?
                LEFT JOIN characteristics_scores cs ON s.id = cs.student_id AND cs.subject_id = ? AND cs.academic_year = ? AND cs.semester = ?
                LEFT JOIN analytical_scores ascore ON s.id = ascore.student_id AND ascore.subject_id = ? AND ascore.academic_year = ? AND ascore.semester = ?
                WHERE s.level = ? AND s.school_id = ? AND s.academic_year = ?
                ORDER BY s.student_code ASC
            ');
            $stmt->execute([
                $subject_id, $academic_year, $semester,
                $subject_id, $academic_year, $semester,
                $subject_id, $academic_year, $semester,
                $level, $school_id, $academic_year
            ]);
        }
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
    }

    echo json_encode($students);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
