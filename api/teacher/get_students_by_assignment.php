<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$classroom_id = $_GET['classroom_id'] ?? '';
if ($classroom_id === 'null' || $classroom_id === 'undefined' || $classroom_id === '0') {
    $classroom_id = '';
}
$subject_id = $_GET['subject_id'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';
$semester = $_GET['semester'] ?? 1;
$school_id = $_SESSION['school_id'];

if (empty($subject_id)) {
    echo json_encode(['error' => 'Missing subject_id']);
    exit;
}

try {
    // 1. Get Subject Details
    $stmt = $pdo->prepare('SELECT level, school_id FROM subjects WHERE id = ?');
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    if (!$subject) {
        echo json_encode(['error' => 'Subject not found']);
        exit;
    }
    $level = $subject['level'];
    
    // 2. Get Classroom Details if ID is provided
    $target_level = $level;
    $target_room = '';
    
    if (!empty($classroom_id)) {
        $stmt = $pdo->prepare('SELECT level, room FROM classrooms WHERE id = ?');
        $stmt->execute([$classroom_id]);
        $classroom = $stmt->fetch();
        if ($classroom) {
            $target_level = $classroom['level'];
            $target_room = $classroom['room'];
        }
    }

    // 3. Fetch Students
    // We match by school_id, academic_year, level, room, and status
    $query_where = "s.school_id = ? AND s.academic_year = ? AND s.status = 'studying'";
    $params = [$school_id, $academic_year];
    
    if (!empty($target_room)) {
        $query_where .= " AND s.level = ? AND s.room = ?";
        $params[] = $target_level;
        $params[] = $target_room;
    } else {
        $query_where .= " AND s.level = ?";
        $params[] = $target_level;
    }

    if ($semester === 'annual') {
        $sql = "
            SELECT s.id, s.student_code, s.name, s.prefix,
                   g1.score_units as sem1_units, g1.score_percent as sem1_percent, g1.grade as sem1_grade,
                   g2.score_units as sem2_units, g2.score_percent as sem2_percent, g2.grade as sem2_grade,
                   ((IFNULL(g1.score_percent, 0) + IFNULL(g2.score_percent, 0)) / 2) as annual_percent
            FROM students s
            LEFT JOIN grades g1 ON s.id = g1.student_id AND g1.subject_id = ? AND g1.academic_year = ? AND g1.semester = 1
            LEFT JOIN grades g2 ON s.id = g2.student_id AND g2.subject_id = ? AND g2.academic_year = ? AND g2.semester = 2
            WHERE $query_where
            ORDER BY s.student_code ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$subject_id, $academic_year, $subject_id, $academic_year], $params));
    } else {
        $sql = "
            SELECT s.id, s.student_code, s.name, s.prefix,
                   g.score_units, g.score_midterm, g.score_final, g.score_total, g.score_percent, g.grade,
                   cs.item1, cs.item2, cs.item3, cs.item4, cs.item5, cs.item6, cs.item7, cs.item8, cs.average_score,
                   ascore.score as analytical_score
            FROM students s
            LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = ? AND g.academic_year = ? AND g.semester = ?
            LEFT JOIN characteristics_scores cs ON s.id = cs.student_id AND cs.subject_id = ? AND cs.academic_year = ? AND cs.semester = ?
            LEFT JOIN analytical_scores ascore ON s.id = ascore.student_id AND ascore.subject_id = ? AND ascore.academic_year = ? AND ascore.semester = ?
            WHERE $query_where
            ORDER BY s.student_code ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([
            $subject_id, $academic_year, $semester,
            $subject_id, $academic_year, $semester,
            $subject_id, $academic_year, $semester
        ], $params));
    }
    
    $students = $stmt->fetchAll();
    
    // Fetch Unit Scores
    foreach ($students as &$student) {
        $stmt = $pdo->prepare('
            SELECT us.learning_unit_id, us.score
            FROM unit_scores us
            JOIN learning_units lu ON us.learning_unit_id = lu.id
            WHERE us.student_id = ? AND lu.subject_id = ? AND lu.academic_year = ? AND lu.semester = ?
        ');
        $stmt->execute([$student['id'], $subject_id, $academic_year, $semester]);
        $student['unit_scores'] = $stmt->fetchAll();
    }

    echo json_encode($students);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
