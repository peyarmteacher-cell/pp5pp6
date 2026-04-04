<?php
// การตั้งค่าฐานข้อมูล MySQL สำหรับ Plesk
$host = '127.0.0.1';
$user = 'schoolos_p6p6';
$pass = 'E%eRnbEs53m_5fak';
$db   = 'schoolos_p6p6';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // ในกรณีที่เชื่อมต่อไม่ได้
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
