<!-- หน้าที่ 4: ข้อมูลนักเรียน และ บันทึกการเปลี่ยนแปลง -->
<div class="page student-info-page">
    <h3 class="text-center" style="font-size: 20px; margin-bottom: 30px;">ข้อมูลนักเรียน</h3>

    <div style="text-align: left; padding: 0 40px; font-size: 16px; line-height: 2.2;">
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ชื่อ</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['prefix'] ?><?= $student['name'] ?></span> 
            <span style="white-space: nowrap;">สกุล</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['last_name'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">วันเกิด</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $bday['day'] ?> <?= $bday['month'] ?> <?= $bday['year'] ?></span> 
            <span style="white-space: nowrap;">อายุ</span> 
            <span class="dotted-line" style="width: 50px; margin: 0 5px;"><?= $age_years ?></span> 
            <span style="white-space: nowrap;">ปี</span> 
            <span class="dotted-line" style="width: 50px; margin: 0 5px;"><?= $age_months ?></span> 
            <span style="white-space: nowrap;">เดือน</span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">เพศ</span> 
            <span class="dotted-line" style="width: 100px; margin: 0 5px;"><?= $student['gender'] === 'male' ? 'ชาย' : 'หญิง' ?></span> 
            <span style="white-space: nowrap;">เชื้อชาติ</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['race'] ?? 'ไทย' ?></span> 
            <span style="white-space: nowrap;">สัญชาติ</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['nationality'] ?? 'ไทย' ?></span> 
            <span style="white-space: nowrap;">ศาสนา</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['religion'] ?? 'พุทธ' ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">เลขประจำตัวนักเรียน</span> 
            <span class="dotted-line" style="width: 150px; margin: 0 5px;"><?= $student['student_code'] ?></span> 
            <span style="white-space: nowrap;">เลขประจำตัวประชาชน</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['national_id'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ที่อยู่ปัจจุบัน</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['address'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ชื่อ-สกุลบิดา</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['father_name'] ?></span> 
            <span style="white-space: nowrap;">อาชีพ</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['father_job'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ชื่อ-สกุลมารดา</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['mother_name'] ?></span> 
            <span style="white-space: nowrap;">อาชีพ</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['mother_job'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">สถานภาพสมรสของบิดา-มารดา</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['parents_status'] ?? '' ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ชื่อ-สกุลผู้ปกครอง</span> 
            <span class="dotted-line" style="flex: 1; margin: 0 5px;"><?= $student['guardian_name'] ?></span> 
            <span style="white-space: nowrap;">อาชีพ</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['guardian_job'] ?></span>
        </div>
        <div style="display: flex; margin-bottom: 5px;">
            <span style="white-space: nowrap;">ความเกี่ยวข้องกับนักเรียน</span> 
            <span class="dotted-line" style="flex: 1; margin-left: 5px;"><?= $student['guardian_relation'] ?></span>
        </div>
    </div>

    <h3 class="text-center" style="font-size: 18px; margin-top: 40px; margin-bottom: 20px;">บันทึกการเปลี่ยนแปลงหรือแก้ไขข้อมูล</h3>
    <div style="padding: 0 40px;">
        <?php for($i=0; $i<12; $i++): ?>
            <div class="dotted-line" style="width: 100%; height: 30px; margin-bottom: 5px;"></div>
        <?php endfor; ?>
    </div>
</div>
