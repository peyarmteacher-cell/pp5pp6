<?php
/**
 * API Router in PHP
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'OPTIONS') {
    exit;
}

try {
    switch ($action) {
        case 'get_schools':
            $stmt = $pdo->query('SELECT * FROM schools ORDER BY created_at DESC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_school':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('INSERT INTO schools (name, code, province) VALUES (?, ?, ?)');
            $stmt->execute([$data['name'], $data['code'], $data['province']]);
            echo json_encode(['status' => 'success']);
            break;

        case 'get_teachers':
            $stmt = $pdo->query('SELECT * FROM users WHERE role = "teacher"');
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_students':
            $stmt = $pdo->query('SELECT * FROM students ORDER BY student_code ASC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_student':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('INSERT INTO students (name, student_code, level, school_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$data['name'], $data['student_code'], $data['level'], $data['school_id']]);
            echo json_encode(['status' => 'success']);
            break;

        case 'promote_students':
            $data = json_decode(file_get_contents('php://input'), true);
            $ids = implode(',', array_map('intval', $data['studentIds']));
            $pdo->exec("UPDATE students SET level = '{$data['nextLevel']}' WHERE id IN ($ids)");
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
