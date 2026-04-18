<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$school_id = $_SESSION['school_id'] ?? null;
$academic_year_query = $pdo->prepare("SELECT year FROM academic_years WHERE school_id = ? AND is_current = 1 LIMIT 1");
$academic_year_query->execute([$school_id]);
$current_year_row = $academic_year_query->fetch();
$current_year = $current_year_row ? $current_year_row['year'] : date('Y') + 543;

try {
    // 1. Student counts by level and total
    $stmt = $pdo->prepare("SELECT level, COUNT(*) as count FROM students WHERE school_id = ? AND academic_year = ? AND (status = 'studying' OR status IS NULL) GROUP BY level ORDER BY level");
    $stmt->execute([$school_id, $current_year]);
    $students_by_level = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_students = 0;
    $has_high_school = false; // Check if school has M.1-M.3
    foreach ($students_by_level as $row) {
        $total_students += (int)$row['count'];
        if (strpos($row['level'], 'ม.') !== false) {
            $has_high_school = true;
        }
    }

    // 2. Teacher Count
    $stmt = $pdo->prepare("SELECT COUNT(*) as teacher_count FROM users WHERE school_id = ? AND role = 'teacher'");
    $stmt->execute([$school_id]);
    $teacher_count = $stmt->fetch()['teacher_count'];

    // 3. National Test Results for Charts
    $stmt = $pdo->prepare("SELECT academic_year, test_type, score_avg FROM national_test_results WHERE school_id = ? ORDER BY academic_year ASC");
    $stmt->execute([$school_id]);
    $test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for charts
    $chart_data = [];
    foreach ($test_results as $row) {
        $year = $row['academic_year'];
        $type = strtoupper($row['test_type']);
        if (!isset($chart_data[$year])) {
            $chart_data[$year] = ['year' => $year, 'RT' => 0, 'NT' => 0, 'ONET_P6' => 0, 'ONET_M3' => 0];
        }
        $chart_data[$year][$type] = (float)$row['score_avg'];
    }
    
    // Sort by year and convert to list
    ksort($chart_data);
    $chart_list = array_values($chart_data);

    echo json_encode([
        'stats' => [
            'student_count' => $total_students,
            'students_by_level' => $students_by_level,
            'teacher_count' => $teacher_count,
            'academic_year' => $current_year,
            'has_high_school' => $has_high_school
        ],
        'chart_data' => $chart_list
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
