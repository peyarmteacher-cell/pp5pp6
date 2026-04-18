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

// รับพารามิเตอร์ตัวกรอง
$academic_year = $_GET['academic_year'] ?? null;
$semester = $_GET['semester'] ?? null;

try {
    // 0. Fetch Available Academic Years
    $stmt = $pdo->prepare("SELECT DISTINCT academic_year FROM grades WHERE student_id = ? ORDER BY academic_year DESC");
    $stmt->execute([$student_id]);
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If no year/semester provided, use the most recent ones
    if (!$academic_year && !empty($available_years)) {
        $academic_year = $available_years[0];
        
        $stmt = $pdo->prepare("SELECT MAX(semester) FROM grades WHERE student_id = ? AND academic_year = ?");
        $stmt->execute([$student_id, $academic_year]);
        $semester = $stmt->fetchColumn();
    }

    // 1. Student Info
    $stmt = $pdo->prepare("SELECT s.*, sch.name as school_name 
                           FROM students s 
                           JOIN schools sch ON s.school_id = sch.id 
                           WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    // 2. Attendance Summary
    $att_sql = "SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ?";
    $att_params = [$student_id];
    
    // Note: Attendance might not be strictly linked to semester in schema, 
    // but we can filter by date if needed. For now summary is global or we could add academic_year to attendance later.
    // However, for dashboard we'll keep it total unless specific dates are needed.
    
    $stmt = $pdo->prepare("$att_sql GROUP BY status");
    $stmt->execute($att_params);
    $attendance = $stmt->fetchAll();

    // 3. Grades
    $grade_sql = "SELECT g.*, sub.name as subject_name, sub.code as subject_code 
                  FROM grades g 
                  JOIN subjects sub ON g.subject_id = sub.id 
                  WHERE g.student_id = ? 
                  AND (g.score_total > 0 OR (g.grade IS NOT NULL AND g.grade != ''))";
    $grade_params = [$student_id];
    
    if ($academic_year) {
        $grade_sql .= " AND g.academic_year = ?";
        $grade_params[] = $academic_year;
    }
    if ($semester) {
        $grade_sql .= " AND g.semester = ?";
        $grade_params[] = $semester;
    }
    
    $grade_sql .= " GROUP BY g.subject_id ORDER BY sub.code ASC";
    $stmt = $pdo->prepare($grade_sql);
    $stmt->execute($grade_params);
    $grades = $stmt->fetchAll();

    // 4. Latest Behavior
    $stmt = $pdo->prepare("SELECT * FROM evaluation_scores 
                           WHERE student_id = ? AND category = 'characteristics' 
                           AND academic_year = ? AND semester = ? LIMIT 1");
    $stmt->execute([$student_id, $academic_year, $semester]);
    $behavior = $stmt->fetch();

    // 5. Parent Feedback
    $stmt = $pdo->prepare("SELECT * FROM parent_feedback 
                           WHERE student_id = ? AND academic_year = ? AND semester = ? LIMIT 1");
    $stmt->execute([$student_id, $academic_year, $semester]);
    $parent_feedback = $stmt->fetch();

    echo json_encode([
        'student' => $student,
        'attendance' => $attendance,
        'grades' => $grades,
        'behavior' => $behavior,
        'parent_feedback' => $parent_feedback,
        'filters' => [
            'available_years' => $available_years,
            'current_year' => $academic_year,
            'current_semester' => $semester
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
