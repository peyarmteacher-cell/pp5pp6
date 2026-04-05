<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['name']; // ใช้ชื่อเต็มจาก Session
$role = $_SESSION['role'];
$affiliation = $_SESSION['affiliation'] ?? 'ไม่มีสังกัด';
$school_name = $_SESSION['school_name'] ?? $affiliation;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบบริหารจัดการสถานศึกษา</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col">
        <div class="p-6 border-b border-slate-800">
            <h1 class="text-xl font-bold text-blue-400">SchoolOS</h1>
            <p class="text-xs text-slate-400 mt-1">ระบบบริหารจัดการสถานศึกษา</p>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <a href="javascript:void(0)" onclick="showSection('overview')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">ภาพรวม</a>
            
            <?php if ($role === 'super_admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Super Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-schools')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการโรงเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('approve-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">อนุมัติ Admin โรงเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-super-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการ Super Admin</a>
                <a href="javascript:void(0)" onclick="fixDatabase()" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors text-yellow-400">ปรับปรุงฐานข้อมูล</a>
                <a href="javascript:void(0)" onclick="showSection('profile')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">แก้ไขโปรไฟล์</a>
<?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">School Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-teachers')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการข้อมูลครู</a>
                <a href="javascript:void(0)" onclick="showSection('approve-teachers')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">อนุมัติครู</a>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการนักเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการรายวิชา</a>
            <?php endif; ?>

            <?php if ($role === 'teacher' && $_SESSION['is_academic']): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">งานวิชาการ</div>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการนักเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการรายวิชา</a>
            <?php endif; ?>

            <?php if ($role === 'teacher' || $role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เมนูครู</div>
                <a href="javascript:void(0)" onclick="showSection('record-grades')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">บันทึกผลการเรียน (ปพ.5/6)</a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-bold">
                    <?= mb_substr($username, 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium truncate"><?= $username ?></p>
                    <p class="text-xs text-slate-400 truncate"><?= $role ?></p>
                </div>
            </div>
            <a href="logout.php" class="block w-full text-center py-2 bg-red-600/20 text-red-400 hover:bg-red-600/30 rounded-lg text-sm font-medium transition-all">ออกจากระบบ</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h2 id="section-title" class="text-2xl font-bold text-slate-800">ภาพรวมระบบ</h2>
            <div class="text-sm text-slate-500">สังกัด: <span class="font-semibold text-blue-600"><?= $school_name ?></span></div>
        </header>

        <!-- Sections -->
        <div id="overview" class="section space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500 mb-1">ยินดีต้อนรับ</p>
                    <h3 class="text-xl font-bold text-slate-800"><?= $username ?></h3>
                    <p class="text-xs text-slate-400 mt-2">คุณกำลังใช้งานในสิทธิ์: <span class="text-blue-600 font-semibold"><?= $role ?></span></p>
                </div>
            </div>
        </div>

        <!-- Super Admin: Manage Schools -->
        <?php if ($role === 'super_admin'): ?>
        <div id="manage-schools" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">สร้างโรงเรียนใหม่</h3>
                <form id="createSchoolForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text" id="schoolCode" placeholder="รหัสโรงเรียน 8 หลัก" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="text" id="schoolName" placeholder="ชื่อโรงเรียน" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="text" id="schoolProvince" placeholder="จังหวัด" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all">บันทึก</button>
                </form>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">รายชื่อโรงเรียน</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">รหัส</th>
                                <th class="pb-3 font-medium">ชื่อโรงเรียน</th>
                                <th class="pb-3 font-medium">จังหวัด</th>
                                <th class="pb-3 font-medium">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="schoolTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: รายชื่อคุณครู -->
        <div id="teacherModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 id="modalSchoolName" class="text-xl font-bold text-slate-800">รายชื่อคุณครู</h3>
                    <button onclick="closeModal('teacherModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium text-slate-800">ชื่อ-นามสกุล</th>
                                <th class="pb-3 font-medium text-slate-800">ตำแหน่ง</th>
                                <th class="pb-3 font-medium text-slate-800">สถานะ</th>
                                <th class="pb-3 font-medium text-right text-slate-800">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="modalTeacherTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: แก้ไขโรงเรียน -->
        <div id="editSchoolModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-slate-800">แก้ไขข้อมูลโรงเรียน</h3>
                    <button onclick="closeModal('editSchoolModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form id="editSchoolForm" class="p-6 space-y-4">
                    <input type="hidden" id="edit_school_id">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อโรงเรียน</label>
                        <input type="text" id="edit_school_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">จังหวัด</label>
                        <input type="text" id="edit_school_province" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    </div>
                    <div class="pt-2 flex gap-3">
                        <button type="button" onclick="closeModal('editSchoolModal')" class="flex-1 px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 cursor-pointer transition-all">ยกเลิก</button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: ยืนยันการลบ -->
        <div id="confirmDeleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">ยืนยันการลบ?</h3>
                <p class="text-slate-500 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลโรงเรียนนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
                <div class="flex gap-3">
                    <button onclick="closeModal('confirmDeleteModal')" class="flex-1 px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 cursor-pointer transition-all">ยกเลิก</button>
                    <button id="confirmDeleteBtn" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-red-700 cursor-pointer transition-all">ลบข้อมูล</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Super Admin: Manage Super Admins -->
        <?php if ($role === 'super_admin'): ?>
        <div id="manage-super-admins" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">เพิ่ม Super Admin ใหม่</h3>
                <form id="createSuperAdminForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" id="sa_username" placeholder="เลขบัตรประชาชน (13 หลัก)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <input type="password" id="sa_password" placeholder="รหัสผ่าน" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <input type="text" id="sa_name" placeholder="ชื่อ-นามสกุล" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <input type="text" id="sa_affiliation" placeholder="สังกัด (เช่น สพป.บุรีรัมย์ เขต 3)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all">เพิ่มผู้ช่วย</button>
                </form>
            </div>
        </div>

        <div id="profile" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">แก้ไขโปรไฟล์</h3>
                <form id="updateProfileForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                            <input type="text" id="prof_name" value="<?= $username ?>" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">สังกัด</label>
                            <input type="text" id="prof_affiliation" value="<?= $affiliation ?>" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- School Admin: Manage Teachers -->
        <?php if ($role === 'admin'): ?>
        <div id="manage-teachers" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">ข้อมูลคุณครูในโรงเรียน</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                                <th class="pb-3 font-medium">ตำแหน่ง</th>
                                <th class="pb-3 font-medium">งานวิชาการ</th>
                                <th class="pb-3 font-medium">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="schoolTeachersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Academic/Admin: Manage Students -->
        <?php if ($role === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'])): ?>
        <div id="manage-students" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">เพิ่มนักเรียนใหม่</h3>
                <form id="addStudentForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="text" id="std_code" placeholder="รหัสประจำตัวนักเรียน" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <input type="text" id="std_national_id" placeholder="เลขบัตรประชาชน (13 หลัก)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <input type="text" id="std_name" placeholder="ชื่อ-นามสกุล" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <select id="std_level" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                        <option value="">เลือกระดับชั้น</option>
                        <option value="ป.1">ป.1</option><option value="ป.2">ป.2</option><option value="ป.3">ป.3</option>
                        <option value="ป.4">ป.4</option><option value="ป.5">ป.5</option><option value="ป.6">ป.6</option>
                        <option value="ม.1">ม.1</option><option value="ม.2">ม.2</option><option value="ม.3">ม.3</option>
                    </select>
                    <input type="text" id="std_room" placeholder="ห้อง (เช่น 1)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all">บันทึก</button>
                </form>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">รายชื่อนักเรียน</h3>
                    <div class="flex gap-2">
                        <button onclick="downloadStudentTemplate()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-200 cursor-pointer transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            ดาวน์โหลด Template
                        </button>
                        <input type="file" id="importExcel" accept=".xlsx, .xls" class="hidden" onchange="handleExcelImport(event)">
                        <button onclick="document.getElementById('importExcel').click()" class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-green-700 cursor-pointer transition-all">นำเข้าจาก Excel</button>
                        <button onclick="promoteStudents()" class="bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-amber-700 cursor-pointer transition-all">เลื่อนระดับชั้น</button>
                    </div>
                </div>
                <div id="studentsContainer" class="space-y-8">
                    <!-- จะถูกเติมด้วย JavaScript แยกตามห้องเรียน -->
                </div>
            </div>
        </div>

        <!-- Academic/Admin: Manage Subjects -->
        <div id="manage-subjects" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">เพิ่มรายวิชา</h3>
                    <div class="flex gap-2">
                        <button onclick="downloadSubjectTemplate()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-200 cursor-pointer transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            ดาวน์โหลด Template
                        </button>
                        <input type="file" id="importSubjectExcel" accept=".xlsx, .xls" class="hidden" onchange="handleSubjectExcelImport(event)">
                        <button onclick="document.getElementById('importSubjectExcel').click()" class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-green-700 cursor-pointer transition-all">นำเข้าจาก Excel</button>
                    </div>
                </div>
                <form id="addSubjectForm" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <input type="text" id="sub_code" placeholder="รหัสวิชา" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <input type="text" id="sub_name" placeholder="ชื่อวิชา" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <select id="sub_level" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                        <option value="">ระดับชั้น</option>
                        <option value="ป.1">ป.1</option><option value="ป.2">ป.2</option><option value="ป.3">ป.3</option>
                        <option value="ป.4">ป.4</option><option value="ป.5">ป.5</option><option value="ป.6">ป.6</option>
                        <option value="ม.1">ม.1</option><option value="ม.2">ม.2</option><option value="ม.3">ม.3</option>
                    </select>
                    <input type="number" id="sub_hours" placeholder="ชั่วโมง" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <input type="number" step="0.5" id="sub_credits" placeholder="หน่วยกิต" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all">บันทึก</button>
                </form>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">รายชื่อวิชา</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">รหัสวิชา</th>
                                <th class="pb-3 font-medium">ชื่อวิชา</th>
                                <th class="pb-3 font-medium">ระดับชั้น</th>
                                <th class="pb-3 font-medium">ชั่วโมง/หน่วยกิต</th>
                                <th class="pb-3 font-medium">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="subjectsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Approve Users Section -->
        <div id="approve-section" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">ผู้ใช้งานรอการอนุมัติ</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                                <th class="pb-3 font-medium">โรงเรียน</th>
                                <th class="pb-3 font-medium">ตำแหน่ง</th>
                                <th class="pb-3 font-medium">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="pendingUsersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: มอบหมายงานสอน -->
        <div id="assignSubjectsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
            <div class="bg-white rounded-3xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 id="assignTeacherName" class="text-xl font-bold text-slate-800">มอบหมายงานสอน</h3>
                        <p class="text-xs text-slate-500 mt-1">กำหนดรายวิชาที่คุณครูรับผิดชอบ</p>
                    </div>
                    <button onclick="closeModal('assignSubjectsModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer p-2 hover:bg-slate-200 rounded-full transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 space-y-8">
                    <!-- ส่วนที่ 1: มอบหมายแบบเหมาจ่าย (ตามระดับชั้น) -->
                    <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                        <h4 class="text-sm font-bold text-blue-800 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            มอบหมายงานสอนแบบเหมาตามระดับชั้น (สำหรับครูประจำชั้น)
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach(['ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6', 'ม.1', 'ม.2', 'ม.3'] as $l): ?>
                                <button onclick="assignSubjectsBulk(currentAssignTeacherId, '<?= $l ?>')" class="px-4 py-2 bg-white border border-blue-200 text-blue-700 rounded-xl text-sm font-semibold hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                    มอบหมาย <?= $l ?> ทั้งหมด
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[10px] text-blue-500 mt-3">* ระบบจะดึงรายวิชาทั้งหมดในระดับชั้นที่เลือกมามอบหมายให้คุณครูทันที</p>
                    </div>

                    <!-- ส่วนที่ 2: รายการที่มอบหมายแล้ว -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            รายวิชาที่รับผิดชอบในปัจจุบัน
                        </h4>
                        <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50">
                                    <tr class="text-slate-500 text-xs uppercase tracking-wider">
                                        <th class="px-4 py-3 font-medium">รหัสวิชา</th>
                                        <th class="px-4 py-3 font-medium">ชื่อวิชา</th>
                                        <th class="px-4 py-3 font-medium">ระดับชั้น</th>
                                        <th class="px-4 py-3 font-medium">ชั่วโมง/หน่วยกิต</th>
                                        <th class="px-4 py-3 font-medium text-right">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherAssignmentsTableBody" class="text-sm">
                                    <tr><td colspan="5" class="py-8 text-center text-slate-400">กำลังโหลดข้อมูล...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-slate-800">แก้ไขข้อมูลนักเรียน</h3>
            <form id="editStudentForm" class="space-y-4">
                <input type="hidden" id="edit_std_id">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" id="edit_std_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ระดับชั้น</label>
                        <select id="edit_std_level" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                            <option value="ป.1">ป.1</option><option value="ป.2">ป.2</option><option value="ป.3">ป.3</option>
                            <option value="ป.4">ป.4</option><option value="ป.5">ป.5</option><option value="ป.6">ป.6</option>
                            <option value="ม.1">ม.1</option><option value="ม.2">ม.2</option><option value="ม.3">ม.3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ห้อง</label>
                        <input type="text" id="edit_std_room" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeModal('editStudentModal')" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer transition-all">ยกเลิก</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all shadow-lg shadow-blue-600/20">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    <div id="importPreviewModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">ตรวจสอบข้อมูลก่อนนำเข้า</h3>
                    <p id="importSummaryText" class="text-sm text-slate-500 mt-1"></p>
                </div>
                <button onclick="closeModal('importPreviewModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 border-b border-slate-100">
                            <th class="pb-3 font-medium">รหัส</th>
                            <th class="pb-3 font-medium">เลขบัตรฯ</th>
                            <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                            <th class="pb-3 font-medium">ระดับชั้น</th>
                            <th class="pb-3 font-medium">ห้อง</th>
                        </tr>
                    </thead>
                    <tbody id="importPreviewTableBody"></tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeModal('importPreviewModal')" class="px-6 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer">ยกเลิก</button>
                <button id="confirmImportBtn" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">ยืนยันการนำเข้า</button>
            </div>
        </div>
    </div>

    <div id="importSubjectPreviewModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">ตรวจสอบข้อมูลรายวิชาก่อนนำเข้า</h3>
                    <p id="importSubjectSummaryText" class="text-sm text-slate-500 mt-1"></p>
                </div>
                <button onclick="closeModal('importSubjectPreviewModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-500 border-b border-slate-100">
                            <th class="pb-3 font-medium">รหัสวิชา</th>
                            <th class="pb-3 font-medium">ชื่อวิชา</th>
                            <th class="pb-3 font-medium">ระดับชั้น</th>
                            <th class="pb-3 font-medium">ชั่วโมง</th>
                            <th class="pb-3 font-medium">หน่วยกิต</th>
                        </tr>
                    </thead>
                    <tbody id="importSubjectPreviewTableBody"></tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeModal('importSubjectPreviewModal')" class="px-6 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer">ยกเลิก</button>
                <button id="confirmSubjectImportBtn" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">ยืนยันการนำเข้า</button>
            </div>
        </div>
    </div>

    <script>
        var studentsToImport = [];
        var subjectsToImport = [];

        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
            
            let targetId = sectionId;
            if (sectionId === 'approve-admins' || sectionId === 'approve-teachers') {
                targetId = 'approve-section';
                loadPendingUsers();
            } else if (sectionId === 'manage-schools') {
                loadSchools();
            } else if (sectionId === 'manage-teachers') {
                loadSchoolTeachers();
            } else if (sectionId === 'manage-students') {
                loadStudents();
            } else if (sectionId === 'manage-subjects') {
                loadSubjects();
            }
            
            const target = document.getElementById(targetId);
            if (target) target.classList.remove('hidden');
            
            const titles = {
                'overview': 'ภาพรวมระบบ',
                'manage-schools': 'จัดการโรงเรียน',
                'approve-section': 'อนุมัติผู้ใช้งาน',
                'manage-students': 'จัดการนักเรียน',
                'manage-subjects': 'จัดการรายวิชา',
                'record-grades': 'บันทึกผลการเรียน',
                'manage-super-admins': 'จัดการ Super Admin',
                'profile': 'แก้ไขโปรไฟล์'
            };
            document.getElementById('section-title').innerText = titles[targetId] || 'ระบบบริหารจัดการ';
        }

        // Create Super Admin Logic
        const createSuperAdminForm = document.getElementById('createSuperAdminForm');
        if (createSuperAdminForm) {
            createSuperAdminForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/create_super_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('sa_username').value,
                        password: document.getElementById('sa_password').value,
                        name: document.getElementById('sa_name').value,
                        affiliation: document.getElementById('sa_affiliation').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    createSuperAdminForm.reset();
                } else {
                    alert(result.error);
                }
            };
        }

        // Update Profile Logic
        const updateProfileForm = document.getElementById('updateProfileForm');
        if (updateProfileForm) {
            updateProfileForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: document.getElementById('prof_name').value,
                        affiliation: document.getElementById('prof_affiliation').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error);
                }
            };
        }

        async function loadSchools() {
            try {
                const res = await fetch('api/get_schools.php');
                const schools = await res.json();
                const tbody = document.getElementById('schoolTableBody');
                if (!tbody) return;
                tbody.innerHTML = schools.map(s => `
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="py-3 text-slate-600 font-mono">${s.code}</td>
                        <td class="py-3 font-medium text-slate-800 cursor-pointer hover:text-blue-600" onclick="viewTeachers(${s.id}, '${s.name}')">${s.name}</td>
                        <td class="py-3 text-slate-500">${s.province || '-'}</td>
                        <td class="py-3 flex gap-2">
                            <button onclick="editSchool(${s.id}, '${s.name}', '${s.province || ''}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium cursor-pointer">แก้ไข</button>
                            <button onclick="deleteSchool(${s.id})" class="text-red-600 hover:text-red-800 text-sm font-medium cursor-pointer">ลบ</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error('Error in loadSchools:', e);
            }
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function editSchool(id, currentName, currentProvince) {
            document.getElementById('edit_school_id').value = id;
            document.getElementById('edit_school_name').value = currentName;
            document.getElementById('edit_school_province').value = currentProvince;
            openModal('editSchoolModal');
        }

        document.getElementById('editSchoolForm').onsubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit_school_id').value;
            const name = document.getElementById('edit_school_name').value;
            const province = document.getElementById('edit_school_province').value;

            if (!confirm('ยืนยันการบันทึกการแก้ไขข้อมูลโรงเรียน?')) return;

            const res = await fetch('api/update_school.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, province })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                closeModal('editSchoolModal');
                loadSchools();
            } else {
                alert(result.error);
            }
        };

        async function deleteSchool(id) {
            openModal('confirmDeleteModal');
            document.getElementById('confirmDeleteBtn').onclick = async () => {
                const res = await fetch('api/delete_school.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    closeModal('confirmDeleteModal');
                    loadSchools();
                } else {
                    alert(result.error);
                    closeModal('confirmDeleteModal');
                }
            };
        }

        async function viewTeachers(schoolId, schoolName) {
            document.getElementById('modalSchoolName').innerText = `รายชื่อคุณครู - ${schoolName}`;
            const mockRole = new URLSearchParams(window.location.search).get('mock_role') || '';
            try {
                const res = await fetch(`api/get_school_teachers.php?school_id=${schoolId}&mock_role=${mockRole}`);
                const teachers = await res.json();
                
                if (teachers.error) {
                    alert(teachers.error);
                    return;
                }

                const tbody = document.getElementById('modalTeacherTableBody');
                
                if (teachers.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="${'<?= $role ?>' === 'super_admin' ? 4 : 3}" class="py-4 text-center text-slate-400">ไม่พบรายชื่อคุณครูในโรงเรียนนี้</td></tr>`;
                } else {
                    tbody.innerHTML = teachers.map(t => `
                    <tr class="border-b border-slate-50">
                        <td class="py-3 font-medium text-slate-800">${t.name}</td>
                        <td class="py-3 text-slate-500">${t.position}</td>
                        <td class="py-3">
                            <div class="flex flex-col gap-1">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold w-fit ${t.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                                    ${t.is_approved ? 'อนุมัติแล้ว' : 'รออนุมัติ'}
                                </span>
                                ${t.is_academic ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 w-fit">งานวิชาการ</span>' : ''}
                            </div>
                        </td>
                        <?php if ($role === 'super_admin'): ?>
                        <td class="py-3 text-right">
                            ${t.role !== 'admin' ? `
                                <button onclick="promoteToAdmin(${t.id}, '${schoolName}')" class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer">กำหนดเป็น Admin</button>
                            ` : '<span class="text-slate-400 text-xs">เป็น Admin แล้ว</span>'}
                        </td>
                        <?php endif; ?>
                    </tr>
                `).join('');
                }
            } catch (e) {
                console.error('Error in viewTeachers:', e);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลคุณครู');
            }
            
            openModal('teacherModal');
        }

        async function promoteToAdmin(userId, schoolName) {
            if (!confirm(`ยืนยันการกำหนดให้คุณครูท่านนี้เป็น Admin ของโรงเรียน ${schoolName}?`)) return;
            
            const res = await fetch('api/admin/promote_to_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                // Refresh modal content - we need schoolId, but we don't have it here easily
                // Let's just reload the whole school list and close modal for simplicity
                closeModal('teacherModal');
                loadSchools();
            } else {
                alert(result.error);
            }
        }

        async function loadSchoolTeachers() {
            const schoolId = '<?= $_SESSION['school_id'] ?>';
            const mockRole = new URLSearchParams(window.location.search).get('mock_role') || '';
            console.log('Loading teachers for school_id:', schoolId);
            try {
                const res = await fetch(`api/get_school_teachers.php?school_id=${schoolId}&mock_role=${mockRole}`);
                const teachers = await res.json();
                console.log('Teachers loaded:', teachers);
                
                if (teachers.error) {
                    console.error('API Error:', teachers.error);
                    alert(teachers.error);
                    return;
                }

                const tbody = document.getElementById('schoolTeachersTableBody');
                if (!tbody) return;
                
                if (teachers.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-slate-400">ยังไม่มีข้อมูลคุณครูในโรงเรียนนี้</td></tr>`;
                    return;
                }

                tbody.innerHTML = teachers.map(t => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 font-medium text-slate-800">${t.name}</td>
                    <td class="py-3 text-slate-500">${t.position}</td>
                    <td class="py-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" ${t.is_academic ? 'checked' : ''} onchange="toggleAcademic(${t.id}, this.checked)">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </td>
                    <td class="py-3">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold ${t.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                                ${t.is_approved ? 'อนุมัติแล้ว' : 'รออนุมัติ'}
                            </span>
                            ${t.is_approved ? `
                                <button onclick="openAssignSubjectsModal(${t.id}, '${t.name}')" class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    มอบหมายงานสอน
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            } catch (e) {
                console.error('Error in loadSchoolTeachers:', e);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูลคุณครู');
            }
        }

        async function toggleAcademic(userId, isAcademic) {
            const res = await fetch('api/admin/set_academic_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, is_academic: isAcademic })
            });
            const result = await res.json();
            if (!result.message) {
                alert(result.error);
                loadSchoolTeachers(); // Revert UI
            }
        }

        function downloadStudentTemplate() {
            const data = [
                ['รหัสประจำตัว', 'เลขบัตรประชาชน', 'ชื่อ-นามสกุล', 'ระดับชั้น', 'ห้อง'],
                ['67001', '1234567890123', 'เด็กชายสมชาย ใจดี', 'ป.1', '1'],
                ['67002', '9876543210987', 'เด็กหญิงสมศรี รักเรียน', 'ป.1', '2']
            ];
            const worksheet = XLSX.utils.aoa_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Template");
            XLSX.writeFile(workbook, "student_template.xlsx");
        }

        function downloadSubjectTemplate() {
            const data = [
                ["รหัสวิชา", "ชื่อวิชา", "ระดับชั้น", "ชั่วโมง", "หน่วยกิต"],
                ["ท11101", "ภาษาไทย 1", "ป.1", "200", "5.0"],
                ["ค11101", "คณิตศาสตร์ 1", "ป.1", "200", "5.0"]
            ];
            const worksheet = XLSX.utils.aoa_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Template");
            XLSX.writeFile(workbook, "subject_template.xlsx");
        }

        function handleSubjectExcelImport(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const json = XLSX.utils.sheet_to_json(worksheet);

                    subjectsToImport = json.map(row => ({
                        code: String(row['รหัสวิชา'] || row['code'] || ''),
                        name: String(row['ชื่อวิชา'] || row['name'] || ''),
                        level: String(row['ระดับชั้น'] || row['level'] || ''),
                        hours: parseInt(row['ชั่วโมง'] || row['hours'] || '40'),
                        credits: parseFloat(row['หน่วยกิต'] || row['credits'] || '1.0')
                    })).filter(s => s.code && s.name && s.level);

                    if (subjectsToImport.length === 0) {
                        alert('ไม่พบข้อมูลรายวิชาที่ถูกต้องในไฟล์ Excel');
                        return;
                    }

                    renderSubjectImportPreview();
                    openModal('importSubjectPreviewModal');
                } catch (err) {
                    console.error('Excel processing error:', err);
                    alert('เกิดข้อผิดพลาดในการประมวลผลไฟล์ Excel: ' + err.message);
                }
            };
            reader.readAsArrayBuffer(file);
            event.target.value = '';
        }

        function renderSubjectImportPreview() {
            const tbody = document.getElementById('importSubjectPreviewTableBody');
            const summary = document.getElementById('importSubjectSummaryText');
            
            if (summary) {
                summary.innerText = `พบข้อมูลรายวิชาทั้งหมด ${subjectsToImport.length} รายการ`;
            }

            tbody.innerHTML = subjectsToImport.map(s => `
                <tr class="border-b border-slate-50">
                    <td class="py-2">${s.code}</td>
                    <td class="py-2">${s.name}</td>
                    <td class="py-2">${s.level}</td>
                    <td class="py-2">${s.hours}</td>
                    <td class="py-2">${s.credits}</td>
                </tr>
            `).join('');
        }

        // Initialize Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmImportBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', async () => {
                    if (!studentsToImport || studentsToImport.length === 0) {
                        alert('ไม่พบข้อมูลที่จะนำเข้า กรุณาเลือกไฟล์ใหม่อีกครั้ง');
                        return;
                    }

                    confirmBtn.disabled = true;
                    confirmBtn.innerText = 'กำลังนำเข้า...';

                    try {
                        const res = await fetch('api/academic/import_students.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ students: studentsToImport })
                        });
                        const result = await res.json();
                        if (result.message) {
                            alert(result.message);
                            closeModal('importPreviewModal');
                            loadStudents();
                        } else {
                            alert(result.error || 'เกิดข้อผิดพลาดในการนำเข้า');
                        }
                    } catch (e) {
                        console.error('Error in import:', e);
                        alert('เกิดข้อผิดพลาดในการนำเข้าข้อมูล');
                    } finally {
                        confirmBtn.disabled = false;
                        confirmBtn.innerText = 'ยืนยันการนำเข้า';
                    }
                });
            }

            const confirmSubBtn = document.getElementById('confirmSubjectImportBtn');
            if (confirmSubBtn) {
                confirmSubBtn.addEventListener('click', async () => {
                    if (!subjectsToImport || subjectsToImport.length === 0) {
                        alert('ไม่พบข้อมูลที่จะนำเข้า กรุณาเลือกไฟล์ใหม่อีกครั้ง');
                        return;
                    }

                    confirmSubBtn.disabled = true;
                    confirmSubBtn.innerText = 'กำลังนำเข้า...';

                    try {
                        const res = await fetch('api/academic/import_subjects.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ subjects: subjectsToImport })
                        });
                        const result = await res.json();
                        if (result.message) {
                            alert(result.message);
                            closeModal('importSubjectPreviewModal');
                            loadSubjects();
                        } else {
                            alert(result.error || 'เกิดข้อผิดพลาดในการนำเข้า');
                        }
                    } catch (e) {
                        console.error('Error in subject import:', e);
                        alert('เกิดข้อผิดพลาดในการนำเข้าข้อมูลรายวิชา');
                    } finally {
                        confirmSubBtn.disabled = false;
                        confirmSubBtn.innerText = 'ยืนยันการนำเข้า';
                    }
                });
            }
        });

        function handleExcelImport(event) {
            console.log('File selected:', event.target.files[0]);
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onerror = (err) => {
                console.error('FileReader error:', err);
                alert('ไม่สามารถอ่านไฟล์ได้');
            };
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const json = XLSX.utils.sheet_to_json(worksheet);
                    console.log('Excel JSON:', json);

                    // คาดหวังคอลัมน์: student_code, national_id, name, level, room
                    // หรือภาษาไทย: รหัสประจำตัว, เลขบัตรประชาชน, ชื่อ-นามสกุล, ระดับชั้น, ห้อง
                    studentsToImport = json.map(row => ({
                        student_code: String(row['รหัสประจำตัว'] || row['student_code'] || row['รหัส'] || ''),
                        national_id: String(row['เลขบัตรประชาชน'] || row['national_id'] || row['เลขบัตร'] || ''),
                        name: String(row['ชื่อ-นามสกุล'] || row['name'] || row['ชื่อ'] || ''),
                        level: String(row['ระดับชั้น'] || row['level'] || row['ชั้น'] || ''),
                        room: String(row['ห้อง'] || row['room'] || '1')
                    })).filter(s => s.student_code && s.name && s.level);

                    console.log('Filtered students:', studentsToImport);

                    if (studentsToImport.length === 0) {
                        alert('ไม่พบข้อมูลนักเรียนที่ถูกต้องในไฟล์ Excel (กรุณาตรวจสอบหัวคอลัมน์)');
                        return;
                    }

                    renderImportPreview();
                    openModal('importPreviewModal');
                } catch (err) {
                    console.error('Excel processing error:', err);
                    alert('เกิดข้อผิดพลาดในการประมวลผลไฟล์ Excel: ' + err.message);
                }
            };
            reader.readAsArrayBuffer(file);
            event.target.value = ''; // Reset input
        }

        function renderImportPreview() {
            const tbody = document.getElementById('importPreviewTableBody');
            const summary = document.getElementById('importSummaryText');
            
            if (summary) {
                summary.innerText = `พบข้อมูลนักเรียนทั้งหมด ${studentsToImport.length} รายการ`;
            }

            tbody.innerHTML = studentsToImport.map(s => `
                <tr class="border-b border-slate-50">
                    <td class="py-2">${s.student_code}</td>
                    <td class="py-2">${s.national_id}</td>
                    <td class="py-2">${s.name}</td>
                    <td class="py-2">${s.level}</td>
                    <td class="py-2">${s.room}</td>
                </tr>
            `).join('');
        }

        async function loadStudents() {
            try {
                const res = await fetch('api/academic/get_students.php');
                const students = await res.json();
                const container = document.getElementById('studentsContainer');
                if (!container) return;
                
                if (students.length === 0) {
                    container.innerHTML = '<div class="text-center py-8 text-slate-400">ไม่พบข้อมูลนักเรียน</div>';
                    return;
                }

                // จัดกลุ่มตาม ระดับชั้น และ ห้อง
                const groups = {};
                students.forEach(s => {
                    const key = `${s.level}/${s.room || '1'}`;
                    if (!groups[key]) groups[key] = [];
                    groups[key].push(s);
                });

                container.innerHTML = Object.keys(groups).sort().map(key => {
                    const [level, room] = key.split('/');
                    const groupStudents = groups[key];
                    return `
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-1 bg-blue-600 rounded-full"></div>
                                <h4 class="font-bold text-slate-800">ชั้น${level} ห้อง ${room} (${groupStudents.length} คน)</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-slate-500 border-b border-slate-100">
                                            <th class="pb-3 font-medium">รหัส</th>
                                            <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                                            <th class="pb-3 font-medium text-right">การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${groupStudents.map(s => `
                                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                                <td class="py-3 text-slate-600 font-mono">${s.student_code}</td>
                                                <td class="py-3 font-medium text-slate-800">${s.name}</td>
                                                <td class="py-3 text-right flex gap-2 justify-end">
                                                    <button onclick='editStudent(${JSON.stringify(s)})' class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer">แก้ไข</button>
                                                    <button onclick="deleteStudent(${s.id})" class="text-red-600 hover:text-red-800 text-xs font-bold cursor-pointer">ลบ</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error('Error in loadStudents:', e);
            }
        }

        async function loadSubjects() {
            try {
                const res = await fetch('api/academic/get_subjects.php');
                const subjects = await res.json();
                const tbody = document.getElementById('subjectsTableBody');
                if (!tbody) return;
                tbody.innerHTML = subjects.map(s => `
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="py-3 text-slate-600 font-mono">${s.code}</td>
                        <td class="py-3 font-medium text-slate-800">${s.name}</td>
                        <td class="py-3 text-slate-500">${s.level}</td>
                        <td class="py-3 text-slate-500">${s.hours} ชม. / ${s.credits} นก.</td>
                        <td class="py-3">
                            <button onclick="deleteSubject(${s.id})" class="text-red-600 hover:text-red-800 text-xs font-bold cursor-pointer">ลบ</button>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error('Error in loadSubjects:', e);
            }
        }

        const addStudentForm = document.getElementById('addStudentForm');
        if (addStudentForm) {
            addStudentForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/academic/add_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_code: document.getElementById('std_code').value,
                        national_id: document.getElementById('std_national_id').value,
                        name: document.getElementById('std_name').value,
                        level: document.getElementById('std_level').value,
                        room: document.getElementById('std_room').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    addStudentForm.reset();
                    loadStudents();
                } else {
                    alert(result.error);
                }
            };
        }

        const addSubjectForm = document.getElementById('addSubjectForm');
        if (addSubjectForm) {
            addSubjectForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/academic/add_subject.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: document.getElementById('sub_code').value,
                        name: document.getElementById('sub_name').value,
                        level: document.getElementById('sub_level').value,
                        hours: document.getElementById('sub_hours').value,
                        credits: document.getElementById('sub_credits').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    addSubjectForm.reset();
                    loadSubjects();
                } else {
                    alert(result.error);
                }
            };
        }

        async function promoteStudents() {
            if (!confirm('ยืนยันการเลื่อนระดับชั้นนักเรียนทั้งหมด? (ป.1 -> ป.2, ป.6 -> จบการศึกษา)')) return;
            const res = await fetch('api/academic/promote_students.php', { method: 'POST' });
            const result = await res.json();
            alert(result.message || result.error);
            loadStudents();
        }

        async function deleteStudent(id) {
            if (!confirm('ยืนยันการลบข้อมูลนักเรียน?')) return;
            const res = await fetch('api/academic/delete_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadStudents();
            } else {
                alert(result.error);
            }
        }

        function editStudent(s) {
            document.getElementById('edit_std_id').value = s.id;
            document.getElementById('edit_std_name').value = s.name;
            document.getElementById('edit_std_level').value = s.level;
            document.getElementById('edit_std_room').value = s.room || '1';
            openModal('editStudentModal');
        }

        const editStudentForm = document.getElementById('editStudentForm');
        if (editStudentForm) {
            editStudentForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/academic/update_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: document.getElementById('edit_std_id').value,
                        name: document.getElementById('edit_std_name').value,
                        level: document.getElementById('edit_std_level').value,
                        room: document.getElementById('edit_std_room').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    closeModal('editStudentModal');
                    loadStudents();
                } else {
                    alert(result.error);
                }
            };
        }

        async function deleteSubject(id) {
            if (!confirm('ยืนยันการลบรายวิชา?')) return;
            const res = await fetch('api/academic/delete_subject.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadSubjects();
            } else {
                alert(result.error);
            }
        }

        async function loadPendingUsers() {
            try {
                const mockRole = new URLSearchParams(window.location.search).get('mock_role') || '';
                const res = await fetch(`api/get_pending_users.php?mock_role=${mockRole}`);
                const users = await res.json();
                const tbody = document.getElementById('pendingUsersTableBody');
                if (!tbody) return;
                tbody.innerHTML = users.map(u => `
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="py-3 font-medium text-slate-800">${u.name}</td>
                        <td class="py-3 text-slate-500">${u.school_name || 'ไม่มีสังกัด'}</td>
                        <td class="py-3 text-slate-500">${u.position}</td>
                        <td class="py-3">
                            <div class="flex gap-2">
                                <button onclick="approveUser(${u.id}, '${u.role === 'super_admin' ? 'admin' : 'teacher'}')" class="bg-green-600 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-green-700 cursor-pointer">อนุมัติ</button>
                                <button onclick="rejectUser(${u.id})" class="bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-red-700 cursor-pointer">ไม่อนุมัติ</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } catch (e) {
                console.error('Error in loadPendingUsers:', e);
            }
        }

        async function rejectUser(userId) {
            if (!confirm('ยืนยันการปฏิเสธการสมัครและลบข้อมูลผู้ใช้งานนี้?')) return;
            const res = await fetch('api/reject_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadPendingUsers();
            } else {
                alert(result.error);
            }
        }

        let currentAssignTeacherId = null;
        async function openAssignSubjectsModal(teacherId, teacherName) {
            currentAssignTeacherId = teacherId;
            document.getElementById('assignTeacherName').innerText = `มอบหมายงานสอน - ${teacherName}`;
            openModal('assignSubjectsModal');
            loadTeacherAssignments(teacherId);
        }

        async function loadTeacherAssignments(teacherId) {
            const tbody = document.getElementById('teacherAssignmentsTableBody');
            const res = await fetch(`api/admin/get_teacher_assignments.php?teacher_id=${teacherId}`);
            const assignments = await res.json();
            
            if (assignments.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-400">ยังไม่มีงานสอนที่มอบหมาย</td></tr>`;
            } else {
                tbody.innerHTML = assignments.map(a => `
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-mono text-slate-600">${a.code}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">${a.name}</td>
                        <td class="px-4 py-3 text-slate-500">${a.level}</td>
                        <td class="px-4 py-3 text-slate-500">${a.hours} ชม. / ${a.credits} นก.</td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="removeAssignment(${a.assignment_id}, ${teacherId})" class="text-red-600 hover:text-red-800 font-bold cursor-pointer">ยกเลิก</button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function assignSubjectsBulk(teacherId, level) {
            if (!confirm(`ยืนยันการมอบหมายรายวิชาทั้งหมดในระดับชั้น ${level} ให้คุณครูท่านนี้?`)) return;
            const res = await fetch('api/admin/assign_subjects.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    type: 'bulk_level',
                    level: level
                })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadTeacherAssignments(teacherId);
            } else {
                alert(result.error);
            }
        }

        async function removeAssignment(assignmentId, teacherId) {
            if (!confirm('ยืนยันการยกเลิกงานสอนนี้?')) return;
            const res = await fetch('api/admin/remove_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assignment_id: assignmentId })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadTeacherAssignments(teacherId);
            } else {
                alert(result.error);
            }
        }

        async function fixDatabase() {
            if (!confirm('คุณต้องการปรับปรุงโครงสร้างฐานข้อมูลหรือไม่?')) return;
            try {
                const res = await fetch('api/admin/fix_database.php');
                const result = await res.json();
                if (result.status === 'success') {
                    alert(result.message + '\n\n' + result.details.join('\n'));
                    location.reload();
                } else {
                    alert(result.message + '\n\n' + result.error);
                }
            } catch (e) {
                console.error('Error in fixDatabase:', e);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }

        async function approveUser(userId, role) {
            const res = await fetch('api/approve_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, role: role })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadPendingUsers();
                if (typeof loadSchoolTeachers === 'function') {
                    loadSchoolTeachers();
                }
            } else {
                alert(result.error);
            }
        }

        const createSchoolForm = document.getElementById('createSchoolForm');
        if (createSchoolForm) {
            createSchoolForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/create_school.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: document.getElementById('schoolCode').value,
                        name: document.getElementById('schoolName').value,
                        province: document.getElementById('schoolProvince').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    createSchoolForm.reset();
                    loadSchools();
                } else {
                    alert(result.error);
                }
            };
        }
        
        // Show default section
        showSection('overview');
    </script>
</body>
</html>
