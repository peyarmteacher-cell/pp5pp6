<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';
$transfer_to_id = $data['transfer_to_id'] ?? null;
$force_delete = $data['force'] ?? false;

if (empty($id)) {
    echo json_encode(['error' => 'ไม่พบรหัสผู้ใช้งาน']);
    exit;
}

try {
    // ถ้าเป็น Admin โรงเรียน ต้องเช็คว่าครูอยู่ในโรงเรียนตัวเอง และไม่ใช่ลบตัวเอง
    if ($_SESSION['role'] === 'admin') {
        if ((int)$id === (int)$_SESSION['user_id']) {
            echo json_encode(['error' => 'ไม่สามารถลบบัญชีตัวเองได้']);
            exit;
        }
        $stmt_check = $pdo->prepare("SELECT id, name, last_name FROM users WHERE id = ? AND school_id = ?");
        $stmt_check->execute([$id, $_SESSION['school_id']]);
        $target_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
        if (!$target_user) {
            echo json_encode(['error' => 'ไม่พบคุณครูในโรงเรียนของคุณ']);
            exit;
        }
    } else {
        $stmt_check = $pdo->prepare("SELECT id, name, last_name FROM users WHERE id = ?");
        $stmt_check->execute([$id]);
        $target_user = $stmt_check->fetch(PDO::FETCH_ASSOC);
        if (!$target_user) {
            echo json_encode(['error' => 'ไม่พบข้อมูลคุณครู']);
            exit;
        }
    }

    // ตรวจสอบว่ามีงานสอนหรือคะแนนที่เคยบันทึกไว้หรือไม่
    $stmt_ta = $pdo->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ?");
    $stmt_ta->execute([$id]);
    $ta_count = (int)$stmt_ta->fetchColumn();

    $stmt_gr = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE teacher_id = ?");
    $stmt_gr->execute([$id]);
    $gr_count = (int)$stmt_gr->fetchColumn();

    // หากมีการระบุคุณครูท่านใหม่เพื่อรับโอนงานก่อนลบ
    if (!empty($transfer_to_id)) {
        if ((int)$transfer_to_id === (int)$id) {
            echo json_encode(['error' => 'ไม่สามารถโอนย้ายงานสอนให้ตนเองได้']);
            exit;
        }

        $pdo->beginTransaction();

        // โอนย้ายงานสอนและคะแนน
        $pdo->prepare("UPDATE teacher_assignments SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]);
        $pdo->prepare("UPDATE grades SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]);
        try { $pdo->prepare("UPDATE characteristics_scores SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE analytical_scores SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE attendance SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE IGNORE timetables SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE learner_development_assignments SET teacher_id = ? WHERE teacher_id = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE classrooms SET teacher_id_1 = ? WHERE teacher_id_1 = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}
        try { $pdo->prepare("UPDATE classrooms SET teacher_id_2 = ? WHERE teacher_id_2 = ?")->execute([$transfer_to_id, $id]); } catch (Exception $e) {}

        // ลบบัญชีผู้ใช้
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'โอนย้ายงานสอนและลบข้อมูลคุณครูเรียบร้อยแล้ว']);
        exit;
    }

    // หากยังมีข้อมูลและไม่ได้ระบุผู้รับโอน
    if (($ta_count > 0 || $gr_count > 0) && !$force_delete) {
        echo json_encode([
            'error' => "คุณครูท่านนี้ยังมีภาระงานสอน ({$ta_count} วิชา) หรือคะแนนที่บันทึกไว้ ({$gr_count} รายการ) หากคุณครูย้ายโรงเรียน แนะนำให้โอนย้ายงานสอนไปยังคุณครูท่านอื่นก่อนลบ เพื่อป้องกันข้อมูลคะแนนสูญหาย",
            'has_active_data' => true,
            'ta_count' => $ta_count,
            'gr_count' => $gr_count
        ]);
        exit;
    }

    $sql = "DELETE FROM users WHERE id = ?";
    $params = [$id];
    
    if ($_SESSION['role'] === 'admin') {
        $sql .= " AND school_id = ?";
        $params[] = $_SESSION['school_id'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลคุณครูเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['error' => 'ไม่พบข้อมูลที่ต้องการลบ หรือไม่มีสิทธิ์']);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
