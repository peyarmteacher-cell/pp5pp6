<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && !$_SESSION['is_academic'])) {
    http_response_code(403);
    exit;
}

try {
    // ป.1 -> ป.2, ..., ป.5 -> ป.6
    // ป.6 -> จบการศึกษา (หรือระดับถัดไปหากเป็นโรงเรียนขยายโอกาส)
    // สำหรับตัวอย่างนี้จะเลื่อนระดับชั้นพื้นฐาน
    
    $levels = ['ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6', 'ม.1', 'ม.2', 'ม.3'];
    
    foreach (array_reverse($levels) as $index => $level) {
        if ($level === 'ม.3' || $level === 'ป.6' /* ในกรณีโรงเรียนประถมล้วน */) {
            // นักเรียนที่จบการศึกษา
            $stmt = $pdo->prepare("UPDATE students SET level = 'จบการศึกษา' WHERE level = ? AND school_id = ?");
            $stmt->execute([$level, $_SESSION['school_id']]);
        } else {
            $next_level = $levels[array_search($level, $levels) + 1];
            $stmt = $pdo->prepare("UPDATE students SET level = ? WHERE level = ? AND school_id = ?");
            $stmt->execute([$next_level, $level, $_SESSION['school_id']]);
        }
    }
    
    echo json_encode(['message' => 'เลื่อนระดับชั้นนักเรียนสำเร็จแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
