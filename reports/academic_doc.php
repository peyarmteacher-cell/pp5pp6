<?php
require_once 'report_header.php';

$type = $_GET['type'] ?? '';
$student_id = $_GET['student_id'] ?? '';

if (!$student_id) {
    die('Student ID is required');
}

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
        padding: 20mm 25mm;
        margin: 10mm auto;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
        box-sizing: border-box;
        color: #000;
        line-height: 1.8;
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
        width: 60px;
    }
    
    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 20px;
        margin-bottom: 30px;
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
        margin-top: 50px;
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

    .p7-header {
        position: absolute;
        top: 15mm;
        right: 25mm;
        font-weight: bold;
        font-size: 16px;
    }

    .p7-garuda {
        text-align: center;
        margin-bottom: 10px;
    }

    .p7-garuda img {
        height: 3cm;
    }

    .p7-title {
        text-align: center;
        font-weight: bold;
        font-size: 24px;
        margin-bottom: 20px;
    }

    .p7-content {
        font-size: 18px;
        line-height: 2.2;
    }

    .p7-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        align-items: baseline;
    }

    .p7-line {
        flex: 1;
        border-bottom: 1px dotted black;
        margin: 0 5px;
        padding: 0 10px;
        text-align: center;
        min-height: 1.2em;
    }

    .p7-footer-note {
        position: absolute;
        bottom: 20mm;
        width: calc(100% - 50mm);
        text-align: center;
        font-size: 14px;
    }
</style>

<?php if ($type === 'transfer_request'): ?>
    <!-- แบบ บค.๑๙ คำร้องขอย้ายนักเรียน -->
    <div class="doc-page">
        <div class="header-logo">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        <div class="form-label">แบบ บค.๑๙</div>
        <div class="doc-title">คำร้องขอย้ายนักเรียน</div>
        
        <div class="text-right" style="margin-bottom: 10px;">
            เขียนที่ <span class="dotted-line" style="min-width: 150px;"><?= $school_name ?></span>
        </div>
        <div class="text-right" style="margin-bottom: 30px;">
            วันที่ <span class="dotted-line" style="min-width: 30px;"><?= $day ?></span> 
            เดือน <span class="dotted-line" style="min-width: 80px;"><?= $month ?></span> 
            พ.ศ. <span class="dotted-line" style="min-width: 50px;"><?= $year ?></span>
        </div>
        
        <div class="content-row">
            <span class="font-bold">เรื่อง</span> ขอย้ายนักเรียน
        </div>
        <div class="content-row">
            <span class="font-bold">เรียน</span> ผู้อำนวยการสถานศึกษาโรงเรียน <span class="dotted-line" style="min-width: 200px;"><?= $school_name ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>ด้วยข้าพเจ้า <span class="dotted-line" style="min-width: 200px;"><?= $_GET['parent_name'] ?? '................................' ?></span> 
            อยู่บ้านเลขที่ <span class="dotted-line" style="min-width: 50px;"><?= $student['house_no'] ?? '.........' ?></span> 
            หมู่ที่ <span class="dotted-line" style="min-width: 30px;"><?= $student['moo'] ?? '.....' ?></span> 
            แขวง/ตำบล <span class="dotted-line" style="min-width: 100px;"><?= $student['sub_district'] ?? '................' ?></span>
        </div>
        <div class="content-row">
            เขต/อำเภอ <span class="dotted-line" style="min-width: 100px;"><?= $student['district'] ?? '................' ?></span> 
            จังหวัด <span class="dotted-line" style="min-width: 100px;"><?= $student['province_name'] ?? '................' ?></span> 
            มีความประสงค์ขอย้ายนักเรียนในปกครองของข้าพเจ้า ซึ่งปัจจุบันเรียนอยู่ในสถานศึกษานี้
        </div>
        <div class="content-row">
            ไปเข้าเรียนที่ <span class="dotted-line" style="min-width: 200px;"><?= $_GET['dest_school'] ?? '................................' ?></span> 
            แขวง/ตำบล <span class="dotted-line" style="min-width: 100px;">................</span> 
            เขต/อำเภอ <span class="dotted-line" style="min-width: 100px;">................</span>
        </div>
        <div class="content-row">
            จังหวัด <span class="dotted-line" style="min-width: 100px;">................</span> ดังนี้
        </div>
        
        <div class="content-row">
            <span class="indent"></span>๑. <?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?>
            เกิดวันที่ <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[0] ?></span> 
            เดือน <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[1] ?></span> 
            พ.ศ. <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[2] ?></span>
        </div>
        <div class="content-row">
            เลขประจำตัวประชาชน <span class="dotted-line" style="min-width: 150px;"><?= $student['national_id'] ?></span> 
            นักเรียนชั้น <span class="dotted-line" style="min-width: 50px;"><?= formatLevelName($student['level']) ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 10px;">
            <span class="indent"></span>ทั้งนี้ เนื่องจาก <span class="dotted-line" style="min-width: 400px;"><?= $_GET['reason'] ?? '................................' ?></span>
        </div>
        
        <div class="content-row">
            และการย้ายไปเข้าเรียนในโรงเรียนดังกล่าว นักเรียนจะพักอยู่บ้านเลขที่ <span class="dotted-line" style="min-width: 50px;">.........</span> 
            หมู่ที่ <span class="dotted-line" style="min-width: 30px;">.....</span> 
            แขวง/ตำบล <span class="dotted-line" style="min-width: 100px;">................</span>
        </div>
        <div class="content-row">
            เขต/อำเภอ <span class="dotted-line" style="min-width: 100px;">................</span> 
            จังหวัด <span class="dotted-line" style="min-width: 100px;">................</span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>จึงเรียนมาเพื่อโปรดพิจารณา
        </div>
        
        <div class="signature-section">
            ขอแสดงความนับถือ<br><br><br>
            (ลงชื่อ).......................................................<br>
            ( <span class="dotted-line" style="min-width: 150px;"><?= $_GET['parent_name'] ?? '................................' ?></span> )<br>
            ผู้ปกครอง
        </div>
    </div>

<?php elseif ($type === 'cert_performance'): ?>
    <!-- ใบรับรองผลการศึกษา (ปพ.7) -->
    <div class="doc-page">
        <div class="p7-header">ปพ.7</div>
        <div class="p7-garuda">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        <div class="p7-title">ใบรับรองผลการศึกษา</div>
        
        <div class="p7-content">
            <div class="p7-row">
                โรงเรียน <span class="p7-line"><?= $school_name ?></span>
            </div>
            <div class="p7-row">
                อำเภอ / เขต <span class="p7-line"><?= $school_district ?: '................' ?></span> 
                จังหวัด <span class="p7-line"><?= $school_province ?: '................' ?></span>
            </div>
            <div class="p7-row">
                ขอรับรองว่า <span class="p7-line"><?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?></span>
            </div>
            <div class="p7-row">
                เลขประจำตัวนักเรียน <span class="p7-line"><?= $student['student_code'] ?: '................' ?></span> 
                เลขประจำตัวประชาชน <span class="p7-line"><?= $student['national_id'] ?></span>
            </div>
            <div class="p7-row">
                เกิดเมื่อวันที่ <span class="p7-line" style="flex: 0 0 50px;"><?= formatDocDateThai($student['birthday'])[0] ?></span> 
                เดือน <span class="p7-line"><?= formatDocDateThai($student['birthday'])[1] ?></span> 
                พ.ศ. <span class="p7-line" style="flex: 0 0 80px;"><?= formatDocDateThai($student['birthday'])[2] ?></span>
                เชื้อชาติ <span class="p7-line" style="flex: 0 0 80px;"><?= $student['race'] ?: 'ไทย' ?></span>
                สัญชาติ <span class="p7-line" style="flex: 0 0 80px;"><?= $student['nationality'] ?: 'ไทย' ?></span>
            </div>
            <div class="p7-row">
                ชื่อ – ชื่อสกุลบิดา <span class="p7-line"><?= $student['father_name'] ?> <?= $student['father_last_name'] ?></span> 
                ชื่อ – ชื่อสกุลมารดา <span class="p7-line"><?= $student['mother_name'] ?> <?= $student['mother_last_name'] ?></span>
            </div>
            
            <div style="margin-top: 10px;">มีสภาพทางการเรียน ดังนี้</div>
            <div class="p7-row">
                กำลังศึกษาอยู่ในโรงเรียน <span class="p7-line"><?= $school_name ?></span>
            </div>
            
            <?php
            // Fetch GPA for level 4 and 5
            $stmt_gpa4 = $pdo->prepare('SELECT AVG(gpa) FROM student_gpa WHERE student_id = ? AND level = 4');
            $stmt_gpa4->execute([$student_id]);
            $gpa4 = $stmt_gpa4->fetchColumn();

            $stmt_gpa5 = $pdo->prepare('SELECT AVG(gpa) FROM student_gpa WHERE student_id = ? AND level = 5');
            $stmt_gpa5->execute([$student_id]);
            $gpa5 = $stmt_gpa5->fetchColumn();

            $avg_gpa = '-';
            if ($gpa4 && $gpa5) {
                $avg_gpa = number_format(($gpa4 + $gpa5) / 2, 2);
            } elseif ($gpa4 || $gpa5) {
                $avg_gpa = number_format($gpa4 ?: $gpa5, 2);
            }
            ?>
            <div class="p7-row">
                ได้ผลการเรียนเฉลี่ยสะสม ระดับชั้นประถมศึกษาปีที่ 4 และ 5 (2 ปีการศึกษา) <span class="p7-line" style="flex: 0 0 100px;"><?= $avg_gpa ?></span>
            </div>
            
            <div class="p7-row" style="margin-top: 20px;">
                ออกให้ ณ วันที่ <span class="p7-line" style="flex: 0 0 50px;"><?= $day ?></span> 
                เดือน <span class="p7-line"><?= $month ?></span> 
                พ.ศ. <span class="p7-line" style="flex: 0 0 80px;"><?= $year ?></span>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
            <div class="photo-box">
                รูปถ่าย<br>1.5 นิ้ว
            </div>
            <div class="signature-section" style="margin-top: 80px;">
                .......................................................<br>
                ( ....................................................... )<br>
                นายทะเบียน
            </div>
        </div>

        <div class="p7-footer-note">
            ( หมายเหตุ : ใบรับรองนี้มีกำหนดใช้ภายใน 120 วัน นับตั้งแต่วันออก )
        </div>
    </div>

<?php elseif ($type === 'transfer_letter'): ?>
    <!-- แบบ บค.๒๐ หนังสือส่งนักเรียน -->
    <div class="doc-page">
        <div class="form-label">แบบ บค.๒๐</div>
        <div class="header-logo">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        
        <div class="content-row">
            ที่ <span class="dotted-line" style="min-width: 150px;"><?= $_GET['no'] ?? '................' ?></span>
            <span style="float: right;">โรงเรียน <span class="dotted-line" style="min-width: 150px;"><?= $school_name ?></span></span>
        </div>
        <div class="text-right" style="margin-bottom: 30px;">
            วันที่ <span class="dotted-line" style="min-width: 30px;"><?= $day ?></span> 
            เดือน <span class="dotted-line" style="min-width: 80px;"><?= $month ?></span> 
            พ.ศ. <span class="dotted-line" style="min-width: 50px;"><?= $year ?></span>
        </div>
        
        <div class="content-row">
            <span class="font-bold">เรื่อง</span> ส่งนักเรียนขอย้ายมาเข้าเรียน
        </div>
        <div class="content-row">
            <span class="font-bold">เรียน</span> ผู้อำนวยการสถานศึกษาโรงเรียน <span class="dotted-line" style="min-width: 200px;"><?= $_GET['dest_school'] ?? '................................' ?></span>
        </div>
        
        <div class="content-row">
            <span class="font-bold">สิ่งที่ส่งมาด้วย</span> ๑. แบบ ปพ.๑ จำนวน ๑ ฉบับ
        </div>
        <div class="content-row">
            <span class="indent" style="width: 2.8cm;"></span> ๒. แบบ ปพ.๘ จำนวน ๑ ฉบับ
        </div>
        <div class="content-row">
            <span class="indent" style="width: 2.8cm;"></span> ๓. แบบ ปพ.๙ จำนวน ๑ ฉบับ
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>ด้วย <span class="dotted-line" style="min-width: 200px;"><?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?></span> 
            ได้ขอย้ายนักเรียนในปกครองมาเข้าเรียนในสถานศึกษานี้ ได้แก่
        </div>
        <div class="content-row">
            ๑. <?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?>
            เกิดวันที่ <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[0] ?></span> 
            เดือน <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[1] ?></span> 
            พ.ศ. <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[2] ?></span>
        </div>
        <div class="content-row">
            เลขประจำตัวประชาชน <span class="dotted-line" style="min-width: 150px;"><?= $student['national_id'] ?></span> 
            นักเรียนชั้น <span class="dotted-line" style="min-width: 50px;"><?= formatLevelName($student['level']) ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>จึงเรียนมาเพื่อโปรดทราบและดำเนินการต่อไป
        </div>
        
        <div class="signature-section">
            ขอแสดงความนับถือ<br><br><br>
            (ลงชื่อ).......................................................<br>
            ( <?= $director_name ?> )<br>
            ผู้อำนวยการโรงเรียน<?= $school_name ?>
        </div>
    </div>

<?php elseif ($type === 'remove_request'): ?>
    <!-- แบบ บค.๒๑ ขออนุญาตจำหน่ายนักเรียน -->
    <div class="doc-page">
        <div class="form-label">แบบ บค.๒๑</div>
        <div class="header-logo">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        
        <div class="content-row">
            ที่ <span class="dotted-line" style="min-width: 150px;"><?= $_GET['no'] ?? '................' ?></span>
            <span style="float: right;">โรงเรียน <span class="dotted-line" style="min-width: 150px;"><?= $school_name ?></span></span>
        </div>
        <div class="text-right" style="margin-bottom: 30px;">
            วันที่ <span class="dotted-line" style="min-width: 30px;"><?= $day ?></span> 
            เดือน <span class="dotted-line" style="min-width: 80px;"><?= $month ?></span> 
            พ.ศ. <span class="dotted-line" style="min-width: 50px;"><?= $year ?></span>
        </div>
        
        <div class="content-row">
            <span class="font-bold">เรื่อง</span> ขออนุญาตจำหน่ายนักเรียน
        </div>
        <div class="content-row">
            <span class="font-bold">เรียน</span> ผู้อำนวยการสำนักงานเขตพื้นที่การศึกษา <span class="dotted-line" style="min-width: 200px;">................................</span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>ด้วยโรงเรียน <span class="dotted-line" style="min-width: 150px;"><?= $school_name ?></span> 
            มีความประสงค์ขออนุญาตจำหน่ายนักเรียนออกจากทะเบียนนักเรียนด้วยสาเหตุ <span class="dotted-line" style="min-width: 150px;"><?= $_GET['reason'] ?? '................' ?></span> ดังนี้
        </div>
        
        <div class="content-row">
            <span class="indent"></span>๑. <?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?>
            เกิดวันที่ <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[0] ?></span> 
            เดือน <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[1] ?></span> 
            พ.ศ. <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[2] ?></span>
        </div>
        <div class="content-row">
            เลขประจำตัวประชาชน <span class="dotted-line" style="min-width: 150px;"><?= $student['national_id'] ?></span> 
            นักเรียนชั้น <span class="dotted-line" style="min-width: 50px;"><?= formatLevelName($student['level']) ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>จึงเรียนมาเพื่อโปรดพิจารณาอนุญาต
        </div>
        
        <div class="signature-section">
            ขอแสดงความนับถือ<br><br><br>
            (ลงชื่อ).......................................................<br>
            ( <?= $director_name ?> )<br>
            ผู้อำนวยการโรงเรียน<?= $school_name ?>
        </div>
    </div>

<?php elseif ($type === 'no_existence'): ?>
    <!-- แบบ บค.๒๗ หนังสือรับรองการไม่มีตัวตน -->
    <div class="doc-page">
        <div class="header-logo">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        <div class="form-label">แบบ บค.๒๗</div>
        <div class="doc-title">หนังสือรับรองการไม่มีตัวตน</div>
        
        <div class="text-right" style="margin-bottom: 10px;">
            เขียนที่ <span class="dotted-line" style="min-width: 150px;"><?= $_GET['location'] ?? $school_name ?></span>
        </div>
        <div class="text-right" style="margin-bottom: 30px;">
            วันที่ <span class="dotted-line" style="min-width: 30px;"><?= $day ?></span> 
            เดือน <span class="dotted-line" style="min-width: 80px;"><?= $month ?></span> 
            พ.ศ. <span class="dotted-line" style="min-width: 50px;"><?= $year ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>ด้วยข้าพเจ้า <span class="dotted-line" style="min-width: 200px;">................................</span> 
            ตำแหน่ง <span class="dotted-line" style="min-width: 150px;">................................</span>
        </div>
        
        <div class="content-row">
            <span class="indent"></span>ขอรับรองว่า <?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?>
            เกิดวันที่ <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[0] ?></span> 
            เดือน <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[1] ?></span> 
            พ.ศ. <span class="dotted-line"><?= formatDocDateThai($student['birthday'])[2] ?></span>
        </div>
        <div class="content-row">
            เลขประจำตัวประชาชน <span class="dotted-line" style="min-width: 150px;"><?= $student['national_id'] ?></span> 
            เป็นบุตรอยู่ในความปกครองของ <span class="dotted-line" style="min-width: 200px;">................................</span>
        </div>
        
        <div class="content-row">
            อาศัยอยู่บ้านเลขที่ <span class="dotted-line" style="min-width: 50px;">.........</span> 
            หมู่ที่ <span class="dotted-line" style="min-width: 30px;">.....</span> 
            แขวง/ตำบล <span class="dotted-line" style="min-width: 100px;">................</span>
            เขต/อำเภอ <span class="dotted-line" style="min-width: 100px;">................</span> 
            จังหวัด <span class="dotted-line" style="min-width: 100px;">................</span>
        </div>
        
        <div class="content-row" style="margin-top: 10px;">
            ซึ่งปัจจุบันไม่มีตัวตนผู้ปกครองและนักเรียนอยู่ในท้องที่ เนื่องจากได้อพยพไปอยู่ที่อื่น โดยไม่ได้แจ้งการย้ายออกจากทะเบียนบ้าน (ทร.๑๔)
        </div>
        
        <div class="signature-section" style="margin-top: 80px;">
            ขอแสดงความนับถือ<br><br><br>
            (ลงชื่อ).......................................................<br>
            (.......................................................)<br>
            ตำแหน่ง.......................................................
        </div>
    </div>

<?php endif; ?>
</body>
</html>
