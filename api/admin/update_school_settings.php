<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$affiliation = $data['affiliation'] ?? '';
$district = $data['district'] ?? '';
$province = $data['province'] ?? '';
$logo_url = $data['logo_url'] ?? '';
$director_name = $data['director_name'] ?? '';
$academic_head_name = $data['academic_head_name'] ?? '';
$academic_head_position = $data['academic_head_position'] ?? 'หัวหน้างานวิชาการ';

if (empty($name) || empty($province)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE schools SET name = ?, affiliation = ?, district = ?, province = ?, logo_url = ?, director_name = ?, academic_head_name = ?, academic_head_position = ? WHERE id = ?');
    $stmt->execute([$name, $affiliation, $district, $province, $logo_url, $director_name, $academic_head_name, $academic_head_position, $_SESSION['school_id']]);

    // Update session school name
    $_SESSION['school_name'] = $name;

    echo json_encode([
        'status' => 'success',
        'message' => 'อัปเดตข้อมูลโรงเรียนเรียบร้อยแล้ว'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
