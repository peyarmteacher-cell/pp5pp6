<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    die('กรุณาเข้าสู่ระบบก่อนใช้งาน');
}

$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    die('ไม่พบข้อมูลโรงเรียนในเซสชัน');
}

$school = null;
try {
    $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = ?');
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
} catch (Exception $e) {
    // Handle error or leave $school as null
}

$logo_url = $school['logo_url'] ?? '';
if ($logo_url && !preg_match('/^https?:\/\//', $logo_url)) {
    $logo_url = '../' . $logo_url;
}
$school_name = $school['name'] ?? '';
$affiliation = $school['affiliation'] ?? '';
$district = $school['district'] ?? '';
$province = $school['province'] ?? '';

/**
 * ฟังก์ชันจัดรูปแบบตำแหน่งครูให้เป็นทางการ
 * เช่น "ครูชำนาญการ" -> "ครู วิทยฐานะชำนาญการ"
 */
function formatTeacherPosition($position) {
    if (empty($position)) return '';
    
    // รายการวิทยฐานะ
    $levels = ['ชำนาญการ', 'ชำนาญการพิเศษ', 'เชี่ยวชาญ', 'เชี่ยวชาญพิเศษ'];
    
    foreach ($levels as $level) {
        if (strpos($position, $level) !== false && strpos($position, 'วิทยฐานะ') === false) {
            // ถ้ามีคำว่า "ครู" อยู่ข้างหน้า ให้แทรก "วิทยฐานะ" เข้าไป
            if (strpos($position, 'ครู') === 0) {
                return str_replace('ครู', 'ครู วิทยฐานะ', $position);
            } else {
                return 'ครู วิทยฐานะ' . $position;
            }
        }
    }
    
    return $position;
}

// ดึงข้อมูลผู้บริหารจากตาราง school_officials
$director_name = '';
$academic_head_name = '';
$academic_head_position = 'หัวหน้างานวิชาการ';
$deputy_director_name = '';
$deputy_director_position = 'รองผู้อำนวยการโรงเรียน';

try {
    $stmt_off = $pdo->prepare("SELECT * FROM school_officials WHERE school_id = ? AND is_active = 1");
    $stmt_off->execute([$school_id]);
    $officials = $stmt_off->fetchAll();
    
    foreach ($officials as $off) {
        if ($off['role_key'] === 'director' && empty($director_name)) {
            $director_name = $off['name'];
        } else if ($off['role_key'] === 'deputy_director' && empty($deputy_director_name)) {
            $deputy_director_name = $off['name'];
            $deputy_director_position = formatTeacherPosition($off['position']);
        } else if (($off['role_key'] === 'academic_head' || $off['role_key'] === 'deputy_academic') && empty($academic_head_name)) {
            $academic_head_name = $off['name'];
            $academic_head_position = formatTeacherPosition($off['position']);
        }
    }
} catch (Exception $e) {
    // Fallback to schools table if error
    $director_name = $school['director_name'] ?? '';
    $academic_head_name = $school['academic_head_name'] ?? '';
    $academic_head_position = $school['academic_head_position'] ?? 'หัวหน้างานวิชาการ';
}

// Common report header/styles
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานเอกสาร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .no-print { display: none; }
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 104px;
            height: 104px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: center;
            font-size: 14px;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-none { border: none; }
        .no-border td { border: none; }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; rounded: 8px; cursor: pointer; font-weight: bold;">พิมพ์เอกสาร</button>
    </div>
