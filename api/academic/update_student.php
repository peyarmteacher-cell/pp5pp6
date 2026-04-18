<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// ต้องเป็น Admin หรือ งานวิชาการ
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !$_SESSION['is_academic'])) {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';
$prefix = $data['prefix'] ?? '';
$name = $data['name'] ?? '';
$last_name = $data['last_name'] ?? '';
$student_code = $data['student_code'] ?? '';
$national_id = $data['national_id'] ?? '';
$level = $data['level'] ?? '';
$room = $data['room'] ?? '1';
$academic_year = $data['academic_year'] ?? '2567';
$school_id = $_SESSION['school_id'];

// DMC Fields
$gender = $data['gender'] ?? '';
$birthday = $data['birthday'] ?? null;
$age = intval($data['age'] ?? 0);
$weight = floatval($data['weight'] ?? 0);
$height = floatval($data['height'] ?? 0);
$blood_group = $data['blood_group'] ?? '';
$religion = $data['religion'] ?? '';
$race = $data['race'] ?? '';
$nationality = $data['nationality'] ?? '';
$house_no = $data['house_no'] ?? '';
$moo = $data['moo'] ?? '';
$road_soi = $data['road_soi'] ?? '';
$sub_district = $data['sub_district'] ?? '';
$district = $data['district'] ?? '';
$province_name = $data['province_name'] ?? '';
$parent_name = $data['parent_name'] ?? '';
$parent_last_name = $data['parent_last_name'] ?? '';
$parent_occupation = $data['parent_occupation'] ?? '';
$parent_relationship = $data['parent_relationship'] ?? '';
$father_name = $data['father_name'] ?? '';
$father_last_name = $data['father_last_name'] ?? '';
$father_occupation = $data['father_occupation'] ?? '';
$mother_name = $data['mother_name'] ?? '';
$mother_last_name = $data['mother_last_name'] ?? '';
$mother_occupation = $data['mother_occupation'] ?? '';
$disadvantage = $data['disadvantage'] ?? '';
$parent_telegram_id = $data['parent_telegram_id'] ?? '';

if (empty($id) || empty($name) || empty($level)) {
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

try {
    // 1. ตรวจสอบ/สร้างห้องเรียน
    $stmt = $pdo->prepare('SELECT id FROM classrooms WHERE school_id = ? AND level = ? AND room = ?');
    $stmt->execute([$school_id, $level, $room]);
    $classroom = $stmt->fetch();

    $classroom_id = null;
    if (!$classroom) {
        $stmt = $pdo->prepare('INSERT INTO classrooms (school_id, level, room) VALUES (?, ?, ?)');
        $stmt->execute([$school_id, $level, $room]);
        $classroom_id = $pdo->lastInsertId();
    } else {
        $classroom_id = $classroom['id'];
    }

    $sql = 'UPDATE students SET 
        prefix = ?, name = ?, last_name = ?, student_code = ?, national_id = ?, level = ?, room = ?, classroom_id = ?, academic_year = ?,
        gender = ?, birthday = ?, age = ?, weight = ?, height = ?, blood_group = ?, religion = ?, race = ?, nationality = ?,
        house_no = ?, moo = ?, road_soi = ?, sub_district = ?, district = ?, province_name = ?,
        parent_name = ?, parent_last_name = ?, parent_occupation = ?, parent_relationship = ?,
        father_name = ?, father_last_name = ?, father_occupation = ?,
        mother_name = ?, mother_last_name = ?, mother_occupation = ?,
        disadvantage = ?, parent_telegram_id = ?
        WHERE id = ? AND school_id = ?';
    
    $params = [
        $prefix, $name, $last_name, $student_code, $national_id, $level, $room, $classroom_id, $academic_year,
        $gender, $birthday, $age, $weight, $height, $blood_group, $religion, $race, $nationality,
        $house_no, $moo, $road_soi, $sub_district, $district, $province_name,
        $parent_name, $parent_last_name, $parent_occupation, $parent_relationship,
        $father_name, $father_last_name, $father_occupation,
        $mother_name, $mother_last_name, $mother_occupation,
        $disadvantage, $parent_telegram_id, $id, $school_id
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['message' => 'อัปเดตข้อมูลนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $e->getMessage()]);
}
?>
