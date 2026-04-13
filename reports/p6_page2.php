<!-- หน้าที่ 2: ปก (Cover Page) -->
<div class="page cover-page">
    <?php if ($logo_url): ?>
        <img src="<?= $logo_url ?>" class="p6-logo-center" referrerPolicy="no-referrer">
    <?php endif; ?>
    
    <h2 style="margin: 20px 0 10px; font-size: 24px;">แบบรายงานประจำตัวนักเรียน</h2>
    <h3 style="margin: 0 0 20px; font-size: 20px;">ผลการพัฒนาคุณภาพผู้เรียนรายบุคคล (ปพ.6)</h3>
    
    <h3 style="margin: 30px 0 10px; font-size: 22px;">โรงเรียน<?= $school_name ?></h3>
    <p style="font-size: 18px; margin: 5px 0;"><?= $affiliation ?></p>
    <p style="font-size: 18px; margin: 5px 0;">อำเภอ<?= $district ?> จังหวัด<?= $province ?></p>

    <div style="margin-top: 40px; text-align: left; padding: 0 20px; font-size: 17px; line-height: 1.2;">
        <div style="display: flex; margin-bottom: 25px;">
            <span style="white-space: nowrap;">ชื่อ</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['prefix'] ?><?= $student['name'] ?></span> 
            <span style="white-space: nowrap;">นามสกุล</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['last_name'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 25px;">
            <span style="white-space: nowrap;">วันเกิด</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $bday['day'] ?> <?= $bday['month'] ?> <?= $bday['year'] ?></span> 
            <span style="white-space: nowrap;">อายุ</span> 
            <span class="dotted-line" style="width: 60px; margin: 0 5px;"><?= $age_years ?></span> 
            <span style="white-space: nowrap;">ปี</span> 
            <span class="dotted-line" style="width: 60px; margin: 0 5px;"><?= $age_months ?></span> 
            <span style="white-space: nowrap;">เดือน</span>
        </div>
        <div style="display: flex; margin-bottom: 25px;">
            <span style="white-space: nowrap;">เลขประจำตัวนักเรียน</span> 
            <span class="dotted-line" style="width: 120px; margin: 0 5px;"><?= $student['student_code'] ?></span> 
            <span style="white-space: nowrap;">เลขประจำตัวประชาชน</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['national_id'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 25px;">
            <span style="white-space: nowrap;">ชั้น</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;">ประถมศึกษาปีที่ <?= $clean_level ?></span> 
            <span style="white-space: nowrap;">เลขที่</span> 
            <span class="dotted-line" style="width: 100px; margin-left: 5px;"><?= array_search($student['id'], array_column($students_to_print, 'id')) + 1 ?></span>
        </div>
        <div style="display: flex; margin-bottom: 25px;">
            <span style="white-space: nowrap;">ปีการศึกษา</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $year ?></span>
        </div>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        <div class="sig-block" style="margin-bottom: 25px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $teacher_name ?> )</p>
            <p>ครูประจำชั้น/ครูที่ปรึกษา</p>
        </div>
        <div class="sig-block" style="margin-bottom: 25px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $acad_name ?> )</p>
            <p><?= $acad_pos ?></p>
        </div>
        <div class="sig-block" style="margin-bottom: 25px;">
            <p>ลงชื่อ..........................................................</p>
            <p>( <?= $director_name ?> )</p>
            <p>ผู้อำนวยการโรงเรียน</p>
        </div>
        <div style="margin-top: 20px;">
            วันที่ <span class="dotted-line" style="min-width: 150px;"><?= $approval_date['day'] ?> <?= $approval_date['month'] ?> <?= $approval_date['year'] ?></span>
        </div>
    </div>
</div>
