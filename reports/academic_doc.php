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
    <!-- ใบรับรองผลการเรียน -->
    <div class="doc-page">
        <div class="header-logo">
            <img src="<?= $garuda_url ?>" alt="Garuda" referrerPolicy="no-referrer">
        </div>
        <div class="doc-title">ใบรับรองผลการเรียน</div>
        
        <div class="content-row text-center" style="margin-bottom: 40px;">
            โรงเรียน <span class="dotted-line" style="min-width: 200px;"><?= $school_name ?></span>
        </div>
        
        <div class="content-row">
            <span class="indent"></span>หนังสือฉบับนี้ให้ไว้เพื่อรับรองว่า <span class="dotted-line" style="min-width: 200px;"><?= $student['prefix'] ?><?= $student['name'] ?> <?= $student['last_name'] ?></span>
        </div>
        <div class="content-row">
            เลขประจำตัวประชาชน <span class="dotted-line" style="min-width: 150px;"><?= $student['national_id'] ?></span> 
            เป็นนักเรียนชั้น <span class="dotted-line" style="min-width: 80px;"><?= formatLevelName($student['level']) ?></span> 
            ห้อง <span class="dotted-line" style="min-width: 30px;"><?= $student['room'] ?></span>
        </div>
        <div class="content-row">
            ปีการศึกษา <span class="dotted-line" style="min-width: 50px;"><?= $_GET['year'] ?? $currentAcademicYear ?></span> 
            ของโรงเรียน <span class="dotted-line" style="min-width: 200px;"><?= $school_name ?></span>
        </div>
        
        <div class="content-row" style="margin-top: 20px;">
            <span class="indent"></span>มีผลการเรียนเฉลี่ย ดังนี้
        </div>
        
        <table class="table-data">
            <thead>
                <tr>
                    <th>ภาคเรียน</th>
                    <th>ปีการศึกษา</th>
                    <th>เกรดเฉลี่ย (GPA)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sem = $_GET['semester'] ?? 'annual';
                $year = $_GET['year'] ?? '';
                
                if ($sem === 'annual') {
                    // Fetch for both semesters
                    $stmt_g = $pdo->prepare('SELECT semester, gpa FROM student_gpa WHERE student_id = ? AND academic_year = ? ORDER BY semester');
                    $stmt_g->execute([$student_id, $year]);
                    $gpas = $stmt_g->fetchAll();
                    if (count($gpas) > 0) {
                        foreach ($gpas as $g) {
                            echo "<tr>
                                    <td>{$g['semester']}</td>
                                    <td>{$year}</td>
                                    <td>".number_format($g['gpa'], 2)."</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>ไม่พบข้อมูลผลการเรียน</td></tr>";
                    }
                } else {
                    $stmt_g = $pdo->prepare('SELECT gpa FROM student_gpa WHERE student_id = ? AND academic_year = ? AND semester = ?');
                    $stmt_g->execute([$student_id, $year, $sem]);
                    $gpa = $stmt_g->fetchColumn();
                    echo "<tr>
                            <td>{$sem}</td>
                            <td>{$year}</td>
                            <td>".($gpa ? number_format($gpa, 2) : '-')."</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
        
        <div class="content-row" style="margin-top: 30px;">
            <span class="indent"></span>ให้ไว้ ณ วันที่ <span class="dotted-line"><?= $day ?></span> 
            เดือน <span class="dotted-line"><?= $month ?></span> 
            พ.ศ. <span class="dotted-line"><?= $year ?></span>
        </div>
        
        <div class="signature-section" style="margin-top: 80px;">
            (ลงชื่อ).......................................................<br>
            ( <?= $director_name ?> )<br>
            ผู้อำนวยการโรงเรียน <span class="dotted-line" style="min-width: 150px;"><?= $school_name ?></span>
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
