<?php
require_once 'report_header.php';

$type = $_GET['type'] ?? '';
$student_id = $_GET['student_id'] ?? '';

if (!$student_id && $type !== 'transfer_request') {
    die('Student ID is required');
}

$student = null;
$school = null;

if ($student_id) {
    // Fetch student data
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        die('Student not found');
    }

    // Fetch school data
    $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = ?');
    $stmt->execute([$student['school_id']]);
    $school = $stmt->fetch();
} else {
    // For blank forms, use school from session
    $school_id = $_SESSION['school_id'] ?? null;
    if ($school_id) {
        $stmt = $pdo->prepare('SELECT * FROM schools WHERE id = ?');
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
    }
}

$school_name = $school['name'] ?? '';
$school_district = $school['district'] ?? '';
$school_province = $school['province'] ?? '';
$garuda_url = $school['garuda_url'] ?? '';

// Fallback to default if empty
if (empty($garuda_url)) {
    $garuda_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/Garuda_Emb_of_Thailand.svg/1200px-Garuda_Emb_of_Thailand.svg.png';
}

// Handle relative path
if ($garuda_url && !preg_match('/^https?:\/\//', $garuda_url)) {
    $garuda_url = '../' . $garuda_url;
}

// Fetch Director and Registrar names
$director_name = $director_name ?: '.......................................................';
$registrar_name = $registrar_name ?: '.......................................................';

// Re-fetch using school_id to ensure correctness
$target_school_id = $student['school_id'] ?? ($school['id'] ?? null);
if ($target_school_id) {
    $stmt_off = $pdo->prepare("SELECT name, role_key FROM school_officials WHERE school_id = ? AND is_active = 1");
    $stmt_off->execute([$target_school_id]);
    $officials = $stmt_off->fetchAll();
    foreach ($officials as $off) {
        if ($off['role_key'] === 'director') $director_name = $off['name'];
        if ($off['role_key'] === 'registrar') $registrar_name = $off['name'];
    }
}

// Helper to format date
function formatDocDateThai($dateStr = null) {
    if (!$dateStr) $dateStr = date('Y-m-d');
    $thai_months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    $d = date('d', strtotime($dateStr));
    $m = $thai_months[date('m', strtotime($dateStr))];
    $y = date('Y', strtotime($dateStr)) + 543;
    return [$d, $m, $y];
}

list($day, $month, $year) = formatDocDateThai();

?>
<style>
    .doc-page {
        width: 210mm;
        min-height: 297mm;
        padding: 15mm 25mm 10mm 25mm;
        margin: 10mm auto;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
        box-sizing: border-box;
        color: #000;
        line-height: 1.6;
    }
    
    @media print {
        body { background: white; }
        .doc-page { margin: 0; box-shadow: none; padding: 15mm 20mm; }
        .no-print { display: none; }
    }
    
    .header-logo {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .header-logo img {
        height: 3cm;
    }
    
    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 20px;
        margin-bottom: 20px;
    }
    
    .content-row {
        margin-bottom: 10px;
        text-align: justify;
    }
    
    .indent {
        display: inline-block;
        width: 2.5cm;
    }
    
    .signature-section {
        margin-top: 30px;
        float: right;
        width: 350px;
        text-align: center;
    }
    
    .dotted-line {
        border-bottom: 1px dotted #000;
        display: inline-block;
        min-width: 50px;
        padding: 0 5px;
        text-align: center;
    }
    
    .form-label {
        position: absolute;
        top: 15mm;
        right: 25mm;
        font-weight: bold;
        font-size: 14px;
    }
    
    .table-data {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .table-data th, .table-data td {
        border: 1px solid black;
        padding: 8px;
        text-align: center;
    }

    .photo-box {
        width: 3cm;
        height: 4cm;
        border: 1px solid black;
        margin-top: 20px;
        margin-left: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        text-align: center;
    }
</style>

<?php 
switch ($type) {
    case 'transfer_request':
        include 'bk19_transfer_request.php';
        break;
    case 'cert_performance':
        include 'p7_cert.php';
        break;
    case 'transfer_letter':
        include 'bk20_transfer_letter.php';
        break;
    case 'remove_request':
        include 'bk21_remove_request.php';
        break;
    case 'no_existence':
        include 'bk27_no_existence.php';
        break;
    case 'identity_cert':
        include 'identity_cert.php';
        break;
    default:
        echo '<div class="doc-page">ไม่พบประเภทเอกสารที่ระบุ</div>';
        break;
}
?>
</body>
</html>
