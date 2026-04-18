<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && (!isset($_SESSION['is_academic']) || !$_SESSION['is_academic']))) {
    die(json_encode(['error' => 'Unauthorized or Insufficient Permissions']));
}

$school_id = $_SESSION['school_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['academic_year']) || !isset($data['test_type']) || !isset($data['score_avg'])) {
    die(json_encode(['error' => 'Invalid data provided']));
}

$academic_year = $data['academic_year'];
$test_type = strtolower($data['test_type']);
$score_avg = (float)$data['score_avg'];
$score_max = (float)($data['score_max'] ?? 100);

try {
    $stmt = $pdo->prepare("INSERT INTO national_test_results (school_id, academic_year, test_type, score_avg, score_max) 
                          VALUES (?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE score_avg = VALUES(score_avg), score_max = VALUES(score_max)");
    $stmt->execute([$school_id, $academic_year, $test_type, $score_avg, $score_max]);
    
    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลคะแนนสำเร็จ']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
