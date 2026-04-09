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
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        button, a, select, input[type="checkbox"], input[type="radio"], input[type="submit"], input[type="button"] { cursor: pointer; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex">

    <script>
        var studentsToImport = [];
        var subjectsToImport = [];
        var allStudents = [];
        var allSubjects = [];
        var selectedStudentLevel = null;
        var selectedStudentRoom = null;
        var selectedSubjectLevel = null;
        var currentAcademicYear = '2567';
        var currentSemester = 1;

        function showSection(sectionId) {
            console.log('Showing section:', sectionId);
            try {
                document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
                
                let targetId = sectionId;
                if (sectionId === 'approve-admins' || sectionId === 'approve-teachers') {
                    targetId = 'approve-section';
                    if (typeof loadPendingUsers === 'function') loadPendingUsers();
                } else if (sectionId === 'manage-schools') {
                    if (typeof loadSchools === 'function') loadSchools();
                } else if (sectionId === 'manage-teachers') {
                    if (typeof loadSchoolTeachers === 'function') loadSchoolTeachers();
                } else if (sectionId === 'manage-students') {
                    if (typeof loadStudents === 'function') loadStudents();
                } else if (sectionId === 'manage-subjects') {
                    if (typeof loadSubjects === 'function') loadSubjects();
                } else if (sectionId === 'record-grades') {
                    targetId = sectionId;
                    if (typeof loadMyAssignments === 'function') loadMyAssignments();
                } else if (sectionId === 'record-learner-development') {
                    targetId = sectionId;
                    if (typeof loadLearnerDevClassrooms === 'function') loadLearnerDevClassrooms();
                } else if (sectionId === 'record-health') {
                    targetId = sectionId;
                    if (typeof loadHealthClassrooms === 'function') loadHealthClassrooms();
                } else if (sectionId === 'manage-timetable') {
                    targetId = sectionId;
                    if (typeof loadTimetable === 'function') loadTimetable();
                } else if (sectionId === 'record-attendance') {
                    targetId = sectionId;
                    if (typeof loadAttendanceClassrooms === 'function') loadAttendanceClassrooms();
                } else if (sectionId === 'record-behavior') {
                    targetId = sectionId;
                    if (typeof initBehaviorSection === 'function') initBehaviorSection();
                } else if (sectionId === 'reports') {
                    targetId = sectionId;
                    if (typeof loadReportOptions === 'function') loadReportOptions();
                } else if (sectionId === 'school-settings') {
                    targetId = sectionId;
                    if (typeof loadSchoolSettings === 'function') loadSchoolSettings();
                }
                
                const target = document.getElementById(targetId);
                if (target) {
                    target.classList.remove('hidden');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } else {
                    console.warn('Section target not found:', targetId);
                }
                
                const titles = {
                    'overview': 'ภาพรวมระบบ',
                    'manage-schools': 'จัดการโรงเรียน',
                    'approve-section': 'อนุมัติผู้ใช้งาน',
                    'manage-students': 'จัดการนักเรียน',
                    'manage-subjects': 'จัดการรายวิชา',
                    'record-grades': 'บันทึกผลการเรียน',
                    'record-learner-development': 'บันทึกกิจกรรมพัฒนาผู้เรียน',
                    'record-health': 'บันทึกน้ำหนัก-ส่วนสูง',
                    'manage-timetable': 'จัดการตารางสอน',
                    'record-attendance': 'บันทึกการมาเรียน',
                    'record-behavior': 'บันทึกพฤติกรรม',
                    'reports': 'รายงานเอกสาร (ปพ.5/ปพ.6)',
                    'school-settings': 'ตั้งค่าโรงเรียน/โลโก้',
                    'manage-super-admins': 'จัดการ Super Admin',
                    'profile': 'แก้ไขโปรไฟล์',
                    'academic-management': 'จัดการปีการศึกษา/จบการศึกษา'
                };
                const titleEl = document.getElementById('section-title');
                if (titleEl) titleEl.innerText = titles[targetId] || 'ระบบบริหารจัดการ';
            } catch (e) {
                console.error('Error in showSection:', e);
            }
        }
    </script>

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col">
        <div class="p-6 border-b border-slate-800">
            <h1 class="text-xl font-bold text-blue-400">SchoolOS</h1>
            <p class="text-xs text-slate-400 mt-1">ระบบบริหารจัดการสถานศึกษา</p>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <?php if ($role !== 'teacher' || $_SESSION['is_academic']): ?>
                <a href="javascript:void(0)" onclick="showSection('overview')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">ภาพรวม</a>
            <?php endif; ?>
            
            <?php if ($role === 'super_admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Super Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-schools')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการโรงเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('approve-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">อนุมัติ Admin โรงเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-super-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการ Super Admin</a>
                <a href="javascript:void(0)" onclick="fixDatabase()" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors text-yellow-400 cursor-pointer">ปรับปรุงฐานข้อมูล</a>
                <a href="javascript:void(0)" onclick="showSection('profile')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">แก้ไขโปรไฟล์</a>
<?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">School Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-teachers')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการข้อมูลครู</a>
                <a href="javascript:void(0)" onclick="showSection('approve-teachers')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">อนุมัติครู</a>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการนักเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการรายวิชา</a>
                <a href="javascript:void(0)" onclick="showSection('academic-management')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการปีการศึกษา/จบการศึกษา</a>
                <a href="javascript:void(0)" onclick="showSection('school-settings')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">ตั้งค่าโรงเรียน/โลโก้</a>
            <?php endif; ?>

            <?php if ($role === 'teacher' && $_SESSION['is_academic']): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">งานวิชาการ</div>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการนักเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการรายวิชา</a>
                <a href="javascript:void(0)" onclick="showSection('academic-management')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการปีการศึกษา/จบการศึกษา</a>
            <?php endif; ?>

            <?php if ($role === 'teacher' || $role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เมนูครู</div>
                <a href="javascript:void(0)" onclick="showSection('record-grades')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">บันทึกผลการเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('record-learner-development')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">บันทึกกิจกรรมพัฒนาผู้เรียน</a>
                <a href="javascript:void(0)" onclick="showSection('record-health')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">บันทึกน้ำหนัก-ส่วนสูง</a>
                <a href="javascript:void(0)" onclick="showSection('manage-timetable')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">จัดการตารางสอน</a>
                <a href="javascript:void(0)" onclick="showSection('record-attendance')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">บันทึกการมาเรียน</a>
                <a href="javascript:void(0)" onclick="showSection('record-behavior')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">บันทึกพฤติกรรม</a>
                <a href="javascript:void(0)" onclick="showSection('reports')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer text-green-400">รายงานเอกสาร (ปพ.5/ปพ.6)</a>
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
            <a href="logout.php" class="block w-full text-center py-2 bg-red-600/20 text-red-400 hover:bg-red-600/30 rounded-lg text-sm font-medium transition-all cursor-pointer">ออกจากระบบ</a>
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
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">บันทึก</button>
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
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">เพิ่มผู้ช่วย</button>
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
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">บันทึกการเปลี่ยนแปลง</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- School Admin: Manage Teachers -->
        <?php include 'includes/dashboard/teachers.php'; ?>

        <!-- Academic/Admin: Manage Students -->
        <?php include 'includes/dashboard/students.php'; ?>

        <!-- Academic/Admin: Manage Subjects -->
        <?php include 'includes/dashboard/subjects.php'; ?>

        <!-- Academic Management -->
        <?php include 'includes/dashboard/academic_management.php'; ?>

        <!-- Teacher: Record Grades -->
        <?php include 'includes/dashboard/grading.php'; ?>
        <?php include 'includes/dashboard/learner_development.php'; ?>
        <?php include 'includes/dashboard/health_records.php'; ?>
        <?php include 'includes/dashboard/timetable.php'; ?>
        <?php include 'includes/dashboard/attendance.php'; ?>
        <?php include 'includes/dashboard/behavior.php'; ?>

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
    </main>

    <script>
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

        const editSchoolForm = document.getElementById('editSchoolForm');
        if (editSchoolForm) {
            editSchoolForm.onsubmit = async (e) => {
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
        }

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

        // Initialize Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Other initialization if needed
        });

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
        <?php if ($role === 'teacher' && !$_SESSION['is_academic']): ?>
            showSection('record-grades');
        <?php else: ?>
            showSection('overview');
        <?php endif; ?>
    </script>
</body>
</html>
