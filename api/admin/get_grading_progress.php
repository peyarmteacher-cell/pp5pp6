<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$role = $_SESSION['role'] ?? '';
$is_academic = $_SESSION['is_academic'] ?? false;
$position = $_SESSION['position'] ?? '';
$school_id = $_SESSION['school_id'] ?? null;

// Only admin, academic staff, or director can access
$is_director = (strpos($position, 'ผู้อำนวยการ') !== false);
if ($role !== 'admin' && $role !== 'super_admin' && !$is_academic && !$is_director) {
    die(json_encode(['error' => 'Permission denied']));
}

$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? '1';
$level = $_GET['level'] ?? '';

try {
    $sql = "SELECT 
                ta.id as assignment_id,
                sub.name AS subject_name,
                sub.code AS subject_code,
                sub.level AS subject_level,
                c.room,
                u.name AS teacher_name,
                u.last_name AS teacher_last_name,
                (SELECT COUNT(*) 
                 FROM learning_units lu 
                 WHERE lu.subject_id = ta.subject_id 
                 AND lu.classroom_id = ta.classroom_id 
                 AND lu.academic_year = ta.academic_year 
                 AND lu.semester = ta.semester) as total_units,
                (SELECT COUNT(DISTINCT lu.id) 
                 FROM learning_units lu 
                 JOIN unit_scores us ON lu.id = us.learning_unit_id 
                 WHERE lu.subject_id = ta.subject_id 
                 AND lu.classroom_id = ta.classroom_id 
                 AND lu.academic_year = ta.academic_year 
                 AND lu.semester = ta.semester) as completed_units
            FROM teacher_assignments ta
            JOIN subjects sub ON ta.subject_id = sub.id
            JOIN users u ON ta.teacher_id = u.id
            LEFT JOIN classrooms c ON ta.classroom_id = c.id
            WHERE ta.academic_year = ? AND ta.semester = ? AND u.school_id = ?";
    
    $params = [$academic_year, $semester, $school_id];
    
    if ($level) {
        $sql .= " AND sub.level = ?";
        $params[] = $level;
    }
    
    $sql .= " ORDER BY sub.level ASC, c.room ASC, sub.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
