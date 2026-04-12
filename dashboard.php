<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'api/config.php';

$username = $_SESSION['name']; // ใช้ชื่อเต็มจาก Session
$position = $_SESSION['position'] ?? '';
$role = $_SESSION['role'];
$affiliation = $_SESSION['affiliation'] ?? 'ไม่มีสังกัด';
$school_name = $_SESSION['school_name'] ?? $affiliation;

// ดึงการตั้งค่าแอป
$app_name = 'ระบบบริหารงานวิชาการ';
$app_logo = '';
try {
    $stmt_app = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt_app->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($settings['app_name'])) $app_name = $settings['app_name'];
    if (isset($settings['app_logo'])) $app_logo = $settings['app_logo'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= $app_name ?></title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        button, a, select, input[type="checkbox"], input[type="radio"], input[type="submit"], input[type="button"] { cursor: pointer; }
        
        /* Sidebar Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Sidebar Item Hover Effect */
        .nav-item {
            position: relative;
            overflow: hidden;
        }
        .nav-item::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: #3b82f6;
            transition: width 0.3s ease;
            opacity: 0.1;
            pointer-events: none;
        }
        .nav-item:hover::after {
            width: 100%;
        }
        .nav-item.active {
            background: #1e293b;
            border-left: 4px solid #3b82f6;
        }
        .nav-item.active i {
            color: #3b82f6;
        }
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

        console.log('Dashboard loaded. Session Info:', {
            user_id: '<?= $_SESSION['user_id'] ?? '' ?>',
            username: '<?= $_SESSION['username'] ?? '' ?>',
            role: '<?= $_SESSION['role'] ?? '' ?>',
            school_id: '<?= $_SESSION['school_id'] ?? '' ?>',
            school_name: '<?= $_SESSION['school_name'] ?? '' ?>',
            is_academic: '<?= $_SESSION['is_academic'] ?? '' ?>'
        });

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
                } else if (sectionId === 'academic-management') {
                    targetId = sectionId;
                    if (typeof loadAcademicYears === 'function') loadAcademicYears();
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

                // Update Sidebar Active State
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('onclick')?.includes(`'${sectionId}'`)) {
                        item.classList.add('active');
                    }
                });
                
                // Special case for profile button at bottom
                const profileBtn = document.getElementById('profile-btn-bottom');
                if (profileBtn) {
                    if (sectionId === 'profile') {
                        profileBtn.classList.add('bg-blue-600/20', 'text-blue-400', 'border-blue-500/30');
                        profileBtn.classList.remove('bg-slate-700/50', 'text-slate-300');
                    } else {
                        profileBtn.classList.remove('bg-blue-600/20', 'text-blue-400', 'border-blue-500/30');
                        profileBtn.classList.add('bg-slate-700/50', 'text-slate-300');
                    }
                }
            } catch (e) {
                console.error('Error in showSection:', e);
            }
        }
    </script>

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col h-screen sticky top-0">
        <div class="p-6 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center font-bold overflow-hidden shadow-lg shadow-blue-900/20">
                    <?php if ($app_logo): ?>
                        <img src="<?= $app_logo ?>" alt="App Logo" class="w-full h-full object-cover" referrerPolicy="no-referrer">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden">
                    <h1 class="text-lg font-bold text-blue-400 truncate"><?= $app_name ?></h1>
                </div>
            </div>
        </div>
        
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto custom-scrollbar">
            <?php if ($role !== 'teacher' || $_SESSION['is_academic']): ?>
                <a href="javascript:void(0)" onclick="showSection('overview')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ภาพรวม</span>
                </a>
            <?php endif; ?>
            
            <?php if ($role === 'super_admin'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Super Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-schools')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="school" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการโรงเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('approve-admins')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="user-check" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">อนุมัติ Admin โรงเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('manage-super-admins')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="shield-check" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการ Super Admin</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('app-settings')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="settings" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ตั้งค่าระบบ</span>
                </a>
                <a href="javascript:void(0)" onclick="fixDatabase()" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group text-yellow-500/80 hover:text-yellow-400">
                    <i data-lucide="database" class="w-4 h-4 transition-colors"></i>
                    <span class="text-sm font-medium">ปรับปรุงฐานข้อมูล</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">School Admin</div>
                <a href="javascript:void(0)" onclick="showSection('manage-teachers')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="users" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการข้อมูลครู</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('approve-teachers')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="user-plus" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">อนุมัติครู</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการนักเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="book-open" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการรายวิชา</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('academic-management')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการปีการศึกษา</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('school-settings')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="settings-2" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ตั้งค่าโรงเรียน</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'teacher' && $_SESSION['is_academic']): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">งานวิชาการ</div>
                <a href="javascript:void(0)" onclick="showSection('manage-students')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการนักเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('manage-subjects')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="book-open" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการรายวิชา</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('academic-management')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการปีการศึกษา</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'teacher' || $role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">เมนูครู</div>
                <a href="javascript:void(0)" onclick="showSection('record-grades')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="edit-3" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">บันทึกผลการเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('record-learner-development')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="star" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">กิจกรรมพัฒนาผู้เรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('record-health')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="activity" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">น้ำหนัก-ส่วนสูง</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('manage-timetable')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="clock" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการตารางสอน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('record-attendance')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="clipboard-check" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">บันทึกการมาเรียน</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('record-behavior')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="smile" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">บันทึกพฤติกรรม</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('reports')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group text-green-400/90 hover:text-green-400">
                    <i data-lucide="file-text" class="w-4 h-4 transition-colors"></i>
                    <span class="text-sm font-medium">รายงานเอกสาร (ปพ.)</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-900/50">
            <div class="bg-slate-800/40 rounded-2xl p-4 border border-slate-700/50 shadow-inner">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-sm font-bold shadow-lg border-2 border-slate-700">
                        <?= mb_substr($username, 0, 1) ?>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-[10px] text-blue-400 font-medium uppercase tracking-wider mb-0.5"><?= $position ?: str_replace('_', ' ', $role) ?></p>
                        <p class="text-sm font-bold text-white truncate leading-tight"><?= $username ?></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button id="profile-btn-bottom" onclick="showSection('profile')" class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-slate-700/50 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-[10px] font-semibold transition-all border border-slate-600/50">
                        <i data-lucide="user" class="w-3 h-3"></i> โปรไฟล์
                    </button>
                    <a href="logout.php" class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl text-[10px] font-semibold transition-all border border-red-500/20">
                        <i data-lucide="log-out" class="w-3 h-3"></i> ออก
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-slate-50">
        <!-- Top Bar Decor -->
        <div class="w-full h-1 bg-gradient-to-r from-blue-600 via-blue-400 to-blue-600"></div>
        
        <div class="p-8">
            <div class="flex items-center gap-2 mb-6">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-[0.2em] bg-blue-50 px-2 py-1 rounded border border-blue-100">Academic Management</span>
                <div class="flex-1 h-[1px] bg-slate-200"></div>
            </div>

            <header class="flex justify-between items-center mb-8">
                <h2 id="section-title" class="text-2xl font-bold text-slate-800">ภาพรวมระบบ</h2>
                <div class="text-sm text-slate-500 flex items-center gap-2">
                    <i data-lucide="school" class="w-4 h-4 text-blue-500"></i>
                    โรงเรียน: <span class="font-semibold text-slate-700"><?= $school_name ?></span>
                </div>
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

        <div id="profile" class="section hidden space-y-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center">
                        <i data-lucide="user-cog" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">แก้ไขโปรไฟล์ส่วนตัว</h3>
                        <p class="text-sm text-slate-500">จัดการข้อมูลชื่อและรหัสผ่านของคุณ</p>
                    </div>
                </div>

                <form id="updateProfileForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700 ml-1">ชื่อ-นามสกุล</label>
                            <div class="relative">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <input type="text" id="prof_name" value="<?= $username ?>" required class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700 ml-1">รหัสผ่านใหม่ <span class="text-slate-400 font-normal">(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</span></label>
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <input type="password" id="prof_password" placeholder="ระบุรหัสผ่านใหม่" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all cursor-pointer flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Super Admin: Manage Schools -->
        <?php if ($role === 'super_admin'): ?>
        <div id="manage-schools" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">สร้างโรงเรียนใหม่</h3>
                <form id="createSchoolForm" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <input type="text" id="schoolCode" placeholder="รหัสโรงเรียน 8 หลัก" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="text" id="schoolName" placeholder="ชื่อโรงเรียน" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="text" id="schoolAffiliation" placeholder="สังกัด (เช่น สพป.บุรีรัมย์ เขต 3)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="text" id="schoolDistrict" placeholder="อำเภอ" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
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
                                <th class="pb-3 font-medium">สังกัด</th>
                                <th class="pb-3 font-medium">อำเภอ/จังหวัด</th>
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">สังกัด</label>
                        <input type="text" id="edit_school_affiliation" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">อำเภอ</label>
                            <input type="text" id="edit_school_district" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">จังหวัด</label>
                            <input type="text" id="edit_school_province" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        </div>
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

        <div id="app-settings" class="section hidden space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold mb-4">ตั้งค่าแอปพลิเคชัน</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- แก้ไขชื่อแอป -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-slate-700">ชื่อแอปพลิเคชัน</h4>
                        <form id="saveAppNameForm" class="space-y-3">
                            <input type="text" id="app_name_input" value="<?= $app_name ?>" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">บันทึกชื่อแอป</button>
                        </form>
                    </div>
                    
                    <!-- อัปโหลดโลโก้แอป -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-slate-700">โลโก้แอปพลิเคชัน</h4>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden">
                                <?php if ($app_logo): ?>
                                    <img src="<?= $app_logo ?>" id="app_logo_preview" class="w-full h-full object-cover" referrerPolicy="no-referrer">
                                <?php else: ?>
                                    <div id="app_logo_placeholder" class="text-slate-300">
                                        <i data-lucide="image" class="w-8 h-8"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" id="app_logo_file" accept="image/*" class="hidden">
                                <button type="button" onclick="document.getElementById('app_logo_file').click()" class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-medium hover:bg-slate-50 transition-all cursor-pointer">เลือกรูปภาพ</button>
                                <p class="text-[10px] text-slate-400 mt-2">แนะนำขนาด 200x200px (JPG, PNG, WEBP)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        <?php include 'includes/dashboard/reports.php'; ?>
        <?php include 'includes/dashboard/school_settings.php'; ?>

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

        // App Settings Logic
        const saveAppNameForm = document.getElementById('saveAppNameForm');
        if (saveAppNameForm) {
            saveAppNameForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/admin/save_app_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        app_name: document.getElementById('app_name_input').value
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

        const appLogoFile = document.getElementById('app_logo_file');
        if (appLogoFile) {
            appLogoFile.onchange = async (e) => {
                if (e.target.files.length > 0) {
                    const formData = new FormData();
                    formData.append('logo', e.target.files[0]);
                    
                    try {
                        const res = await fetch('api/admin/upload_app_logo.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await res.json();
                        if (result.url) {
                            alert('อัปโหลดโลโก้สำเร็จ');
                            location.reload();
                        } else {
                            alert(result.error || 'เกิดข้อผิดพลาดในการอัปโหลด');
                        }
                    } catch (err) {
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    }
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
                        password: document.getElementById('prof_password').value
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
                        <td class="py-3 text-slate-500">${s.affiliation || '-'}</td>
                        <td class="py-3 text-slate-500">${s.district || ''} ${s.province || ''}</td>
                        <td class="py-3 flex gap-2">
                            <button onclick="editSchool(${s.id}, '${s.name}', '${s.affiliation || ''}', '${s.district || ''}', '${s.province || ''}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium cursor-pointer">แก้ไข</button>
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

        async function editSchool(id, currentName, currentAffiliation, currentDistrict, currentProvince) {
            document.getElementById('edit_school_id').value = id;
            document.getElementById('edit_school_name').value = currentName;
            document.getElementById('edit_school_affiliation').value = currentAffiliation;
            document.getElementById('edit_school_district').value = currentDistrict;
            document.getElementById('edit_school_province').value = currentProvince;
            openModal('editSchoolModal');
        }

        const editSchoolForm = document.getElementById('editSchoolForm');
        if (editSchoolForm) {
            editSchoolForm.onsubmit = async (e) => {
                e.preventDefault();
                const id = document.getElementById('edit_school_id').value;
                const name = document.getElementById('edit_school_name').value;
                const affiliation = document.getElementById('edit_school_affiliation').value;
                const district = document.getElementById('edit_school_district').value;
                const province = document.getElementById('edit_school_province').value;

                if (!confirm('ยืนยันการบันทึกการแก้ไขข้อมูลโรงเรียน?')) return;

                const res = await fetch('api/update_school.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, name, affiliation, district, province })
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
            console.log('viewTeachers: schoolId =', schoolId, 'schoolName =', schoolName);
            window.currentViewingSchool = { id: schoolId, name: schoolName }; // Store for refresh
            document.getElementById('modalSchoolName').innerText = `รายชื่อคุณครู - ${schoolName}`;
            const addBtn = document.getElementById('addTeacherBtnSuperAdmin');
            if (addBtn) {
                addBtn.onclick = () => {
                    console.log('Super Admin: Add Teacher clicked for schoolId:', schoolId);
                    openEditTeacherModal(null, schoolId);
                };
            }
            const mockRole = new URLSearchParams(window.location.search).get('mock_role') || '';
            try {
                const res = await fetch(`api/get_school_teachers.php?school_id=${schoolId}&mock_role=${mockRole}`);
                const teachers = await res.json();
                console.log('viewTeachers: Received teachers:', teachers);
                
                if (teachers.error) {
                    console.error('viewTeachers: API Error:', teachers.error);
                    alert(teachers.error);
                    return;
                }

                const tbody = document.getElementById('modalTeacherTableBody');
                if (!tbody) {
                    console.error('viewTeachers: modalTeacherTableBody not found');
                    return;
                }
                
                if (!Array.isArray(teachers) || teachers.length === 0) {
                    console.log('viewTeachers: No teachers found or invalid response');
                    tbody.innerHTML = `<tr><td colspan="${'<?= $role ?>' === 'super_admin' ? 4 : 3}" class="py-4 text-center text-slate-400">ไม่พบรายชื่อคุณครูในโรงเรียนนี้</td></tr>`;
                } else {
                    // Store teachers globally for safer access from onclick
                    window.lastLoadedTeachers = teachers;
                    
                    tbody.innerHTML = teachers.map((t, index) => {
                        const teacherName = t.name || 'ไม่ระบุชื่อ';
                        const safeSchoolName = schoolName.replace(/'/g, "\\'");
                        return `
                        <tr class="border-b border-slate-50 group">
                            <td class="py-3">
                                <div class="font-medium text-slate-800">${teacherName}</div>
                                <div class="text-[10px] text-slate-400">ID: ${t.username || '-'}</div>
                            </td>
                            <td class="py-3 text-slate-500">${t.position || '-'}</td>
                            <td class="py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold w-fit ${t.is_approved == 1 || t.is_approved === true || t.is_approved === '1' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                                        ${t.is_approved == 1 || t.is_approved === true || t.is_approved === '1' ? 'อนุมัติแล้ว' : 'รออนุมัติ'}
                                    </span>
                                    ${t.is_academic == 1 || t.is_academic === true || t.is_academic === '1' ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 w-fit">งานวิชาการ</span>' : ''}
                                </div>
                            </td>
                            <?php if ($role === 'super_admin'): ?>
                            <td class="py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    ${t.role !== 'admin' ? `
                                        <button onclick="promoteToAdmin(${t.id}, '${safeSchoolName}')" class="text-blue-600 hover:text-blue-800 text-[10px] font-bold cursor-pointer">กำหนดเป็น Admin</button>
                                    ` : '<span class="text-slate-400 text-[10px]">เป็น Admin แล้ว</span>'}
                                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                        <button onclick="openEditTeacherModal(window.lastLoadedTeachers[${index}], ${schoolId})" class="text-blue-500 hover:text-blue-700 text-[10px] font-bold cursor-pointer">แก้ไข</button>
                                        <button onclick="deleteTeacher(${t.id})" class="text-red-500 hover:text-red-700 text-[10px] font-bold cursor-pointer">ลบ</button>
                                    </div>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    `}).join('');
                }
                
                openModal('teacherModal');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } catch (e) {
                console.error('Error in viewTeachers:', e);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลคุณครู: ' + e.message);
            }
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
                        affiliation: document.getElementById('schoolAffiliation').value,
                        district: document.getElementById('schoolDistrict').value,
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

        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>
