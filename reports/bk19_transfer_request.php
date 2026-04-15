<?php
// แบบ บค.๑๙ คำร้องขอย้ายนักเรียน
?>
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
