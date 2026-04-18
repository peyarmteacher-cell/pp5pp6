<?php
/**
 * API to fetch all relevant data for a parent
 */
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['parent_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];

try {
    // 1. Student Info
    $stmt = $pdo->prepare("SELECT s.*, sch.name as school_name 
                           FROM students s 
                           JOIN schools sch ON s.school_id = sch.id 
                           WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    // 2. Attendance Summary
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
    $stmt->execute([$student_id]);
    $attendance = $stmt->fetchAll();

    // 3. Grades (Latest semester)
    $stmt = $pdo->prepare("SELECT g.*, sub.name as subject_name, sub.code as subject_code 
                           FROM grades g 
                           JOIN subjects sub ON g.subject_id = sub.id 
                           WHERE g.student_id = ? 
                           ORDER BY academic_year DESC, semester DESC");
    $stmt->execute([$student_id]);
    $grades = $stmt->fetchAll();

    // 4. Latest Behavior
    $stmt = $pdo->prepare("SELECT * FROM evaluation_scores 
                           WHERE student_id = ? AND category = 'characteristics' 
                           ORDER BY academic_year DESC, semester DESC LIMIT 1");
    $stmt->execute([$student_id]);
    $behavior = $stmt->fetch();

    echo json_encode([
        'student' => $student,
        'attendance' => $attendance,
        'grades' => $grades,
        'behavior' => $behavior
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
