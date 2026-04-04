<?php
// การตั้งค่าฐานข้อมูล MySQL สำหรับ Plesk
$host = 'localhost'; // ลองเปลี่ยนจาก 127.0.0.1 เป็น localhost
$user = 'schoolos_p6p6';
$pass = 'E%eRnbEs53m_5fak';
$db   = 'schoolos_p6p6';

try {
    // ตัด port ออกเพื่อให้ใช้ค่าเริ่มต้นของระบบ
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // ในกรณีที่เชื่อมต่อไม่ได้
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
