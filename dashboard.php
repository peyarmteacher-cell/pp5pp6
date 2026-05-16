<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'api/config.php';

$username = $_SESSION['name']; // ใช้ชื่อเต็มจาก Session
$last_name = $_SESSION['last_name'] ?? '';
$position = $_SESSION['position'] ?? '';
$role = $_SESSION['role'];
$affiliation = $_SESSION['affiliation'] ?? 'ไม่มีสังกัด';
$school_name = $_SESSION['school_name'] ?? $affiliation;

// ดึงการตั้งค่าแอป
$app_name = 'ระบบบริหารงานวิชาการ';
$app_logo = '';
$current_academic_year = '2567';
$current_semester = 1;

try {
    $stmt_app = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt_app->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($settings['app_name'])) $app_name = $settings['app_name'];
    if (isset($settings['app_logo'])) $app_logo = $settings['app_logo'];
    
    // ดึงปีการศึกษาล่าสุดที่มีเครื่องหมาย is_current = 1 หรือล่าสุดจากตาราง
    $stmt_year = $pdo->prepare("SELECT year FROM academic_years WHERE school_id = ? AND is_current = 1 LIMIT 1");
    $stmt_year->execute([$_SESSION['school_id']]);
    $year_row = $stmt_year->fetch();
    if ($year_row) {
        $current_academic_year = $year_row['year'];
    } else {
        // ถ้าไม่มี is_current ให้ดึงปีล่าสุด
        $stmt_latest = $pdo->prepare("SELECT year FROM academic_years WHERE school_id = ? ORDER BY year DESC LIMIT 1");
        $stmt_latest->execute([$_SESSION['school_id']]);
        $latest_row = $stmt_latest->fetch();
        if ($latest_row) $current_academic_year = $latest_row['year'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= $app_name ?></title>
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#0f172a">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Collapsible Sidebar Styles */
        aside {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-item-container {
            width: 256px; /* 16rem = w-64 */
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
        var currentAcademicYear = '<?= $current_academic_year ?>';
        var currentSemester = <?= $current_semester ?>;

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
                } else if (sectionId === 'competency-assessment') {
                    targetId = sectionId;
                    if (typeof initCompetencySection === 'function') initCompetencySection();
                } else if (sectionId === 'academic-management') {
                    targetId = sectionId;
                    if (typeof loadAcademicYears === 'function') loadAcademicYears();
                } else if (sectionId === 'timetable-overview') {
                    targetId = sectionId;
                    if (typeof initTimetableOverview === 'function') initTimetableOverview();
                } else if (sectionId === 'academic-documents') {
                    targetId = sectionId;
                    if (typeof initAcademicDocuments === 'function') initAcademicDocuments();
                } else if (sectionId === 'reports') {
                    targetId = sectionId;
                    if (typeof loadReportOptions === 'function') loadReportOptions();
                } else if (sectionId === 'grading-progress') {
                    targetId = sectionId;
                    if (typeof initGradingProgress === 'function') initGradingProgress();
                } else if (sectionId === 'teacher-usage-stats') {
                    targetId = sectionId;
                    if (typeof loadTeacherUsageStats === 'function') loadTeacherUsageStats();
                } else if (sectionId === 'academic-achievement') {
                    targetId = sectionId;
                    if (typeof initAcademicAchievement === 'function') initAcademicAchievement();
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
                    'grading-progress': 'ความคืบหน้าการบันทึกคะแนน',
                    'teacher-usage-stats': 'สถิติการใช้งานครู',
                    'academic-achievement': 'ผลสัมฤทธิ์ทางการเรียน',
                    'profile': 'แก้ไขโปรไฟล์',
                    'academic-management': 'จัดการปีการศึกษา/จบการศึกษา',
                    'timetable-overview': 'สำรวจตารางสอนภาพรวม',
                    'national-test': 'บันทึกผลการทดสอบระดับชาติ'
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

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const isCollapsed = sidebar.classList.toggle('w-0');
            sidebar.classList.toggle('w-64');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }

        // Apply saved state on load
        window.addEventListener('DOMContentLoaded', () => {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-0');
                }
            }
        });
    </script>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-slate-900 text-white flex flex-col h-screen sticky top-0 transition-all duration-300 overflow-x-hidden z-50">
        <div class="sidebar-item-container flex flex-col h-full min-w-[256px]">
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
            <?php 
            $is_director = (isset($_SESSION['position']) && (strpos($_SESSION['position'], 'ผู้อำนวยการ') !== false || strpos($_SESSION['position'], 'ผู้บริหาร') !== false));
            $is_academic = (isset($_SESSION['is_academic']) && $_SESSION['is_academic'] || $_SESSION['role'] === 'admin');
            $is_restricted_director = $is_director && $role !== 'admin';

            if (!$is_restricted_director && ($role !== 'teacher' || $_SESSION['is_academic'])): ?>
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

            <?php 
            if ($role === 'admin' || $is_director): 
            ?>
                <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">เมนูบริหาร</div>
                
                <a href="javascript:void(0)" onclick="showSection('grading-progress')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="trending-up" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ความคืบหน้าการทำงาน</span>
                </a>

                <?php if ($role === 'admin' || $is_director): ?>
                <a href="javascript:void(0)" onclick="showSection('teacher-usage-stats')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="user-check" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">สถิติการใช้งานครู</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('academic-achievement')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ผลสัมฤทธิ์ทางการเรียน</span>
                </a>
                <?php endif; ?>

                <a href="javascript:void(0)" onclick="showSection('reports')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group text-green-400/90 hover:text-green-400">
                    <i data-lucide="file-text" class="w-4 h-4 transition-colors"></i>
                    <span class="text-sm font-medium">รายงานเอกสาร (ปพ.)</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('timetable-overview')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group text-blue-400/90 hover:text-blue-400">
                    <i data-lucide="eye" class="w-4 h-4 transition-colors"></i>
                    <span class="text-sm font-medium">ตารางสอนภาพรวม</span>
                </a>

                <?php if ($role === 'admin' && !$is_restricted_director): ?>
                <a href="javascript:void(0)" onclick="showSection('manage-teachers')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="users" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">จัดการข้อมูลครู</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('approve-teachers')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="user-plus" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">อนุมัติครู</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('school-settings')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="settings-2" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ตั้งค่าโรงเรียน</span>
                </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (($role === 'admin' || $is_academic) && !$is_restricted_director): ?>
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
                <a href="javascript:void(0)" onclick="showSection('academic-documents')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="file-text" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">เอกสารวิชาการ</span>
                </a>
                <a href="javascript:void(0)" onclick="showSection('national-test')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="award" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">บันทึกผลการสอบระดับชาติ</span>
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
                <a href="javascript:void(0)" onclick="showSection('competency-assessment')" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
                    <i data-lucide="award" class="w-4 h-4 text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    <span class="text-sm font-medium">ประเมินสมรรถนะ</span>
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

            <!-- PWA Install Button (Hidden by default) -->
            <div id="pwa-install-container" class="hidden px-4 pt-4 mt-2 border-t border-slate-800">
                <button id="pwa-install-btn" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl bg-blue-600/10 text-blue-400 hover:bg-blue-600/20 transition-all group border border-blue-500/20">
                    <i data-lucide="download" class="w-4 h-4 text-blue-400 group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] font-bold uppercase tracking-wider">ติดตั้งแอปบนหน้าจอ</span>
                </button>
            </div>
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
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="p-2 hover:bg-slate-200 rounded-xl text-slate-600 transition-all bg-white border border-slate-200 shadow-sm group" title="เปิด/ปิด เมนูข้าง">
                        <i data-lucide="menu" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    </button>
                    <h2 id="section-title" class="text-2xl font-bold text-slate-800 transition-all">ภาพรวมระบบ</h2>
                </div>
                <div class="text-sm text-slate-500 flex items-center gap-2">
                    <i data-lucide="school" class="w-4 h-4 text-blue-500"></i>
                    โรงเรียน: <span class="font-semibold text-slate-700"><?= $school_name ?></span>
                </div>
            </header>

        <!-- Sections -->
        <div id="overview" class="section space-y-6">
            <!-- Stats Cards (First Row) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Welcome Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-blue-500 p-6 rounded-3xl shadow-lg shadow-blue-200 text-white flex flex-col justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">ยินดีต้อนรับกลับมา,</p>
                        <h3 class="text-2xl font-black mt-1"><?= $username ?></h3>
                        <p class="text-blue-100/80 text-xs mt-1 uppercase tracking-widest font-bold"><?= $role ?></p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 text-blue-200"></i>
                            <span class="text-xs font-bold" id="ov_current_year">ปีการศึกษา -</span>
                        </div>
                        <i data-lucide="sparkles" class="w-6 h-6 text-white/20"></i>
                    </div>
                </div>

                <!-- Total Teachers Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-6 group hover:border-indigo-300 transition-all">
                    <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="briefcase" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">บุคลากรครู</p>
                        <div class="flex items-baseline gap-2">
                            <h4 class="text-3xl font-black text-slate-800" id="ov_teacher_count">0</h4>
                            <span class="text-xs font-bold text-slate-400">คน</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row: Chart & Student Breakdown Table -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- National Test Chart (Col Span 2) -->
                <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800">เปรียบเทียบผลทดสอบระดับชาติ</h3>
                            <p class="text-sm text-slate-500 font-medium">คะแนนเฉลี่ย RT, NT และ O-NET แต่ละปีการศึกษา</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3" id="chart_legend">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">RT</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">NT</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">O-NET (ป.6)</span>
                            </div>
                            <div class="flex items-center gap-1.5" id="legend_onet_m3">
                                <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">O-NET (ม.3)</span>
                            </div>
                        </div>
                    </div>
                    <div id="national_test_chart" class="w-full h-[300px] relative">
                        <div id="chart_empty_state" class="hidden absolute inset-0 flex flex-col items-center justify-center text-slate-300">
                            <i data-lucide="bar-chart-3" class="w-12 h-12 mb-2"></i>
                            <p class="text-sm font-bold">ยังไม่มีข้อมูลการสอบ</p>
                        </div>
                    </div>
                </div>

                <!-- Student Counts Table (Col Span 1) -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center justify-between mb-2">
                             <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">นักเรียนทั้งหมด</p>
                             <div class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-wider">Current Year</div>
                        </div>
                        <div class="flex items-baseline gap-2">
                             <h4 class="text-4xl font-black text-slate-800" id="ov_student_count">0</h4>
                             <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">คน</span>
                        </div>
                        <div class="flex gap-4 mt-3">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                <span class="text-[10px] font-bold text-slate-500">ชาย: <span id="ov_male_total" class="text-slate-800">0</span></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full bg-pink-400"></div>
                                <span class="text-[10px] font-bold text-slate-500">หญิง: <span id="ov_female_total" class="text-slate-800">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white border-b border-slate-100">
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    <th class="px-6 py-3">ระดับชั้น</th>
                                    <th class="px-4 py-3 text-center">ชาย/หญิง</th>
                                    <th class="px-6 py-3 text-right">รวม</th>
                                </tr>
                            </thead>
                            <tbody id="ov_student_table_body" class="divide-y divide-slate-50">
                                <!-- Data rows injected here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

                    </div>
                </div>
            </div>
        </div>

        <?php if ($role === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'])): ?>
        <div id="national-test" class="section hidden space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- RT Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="book-open" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">RT (ป.1)</h3>
                            <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Reading Test</p>
                        </div>
                    </div>
                    <form onsubmit="saveNationalTestDetail(event, 'rt')" class="space-y-4 flex-grow">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">ปีการศึกษา</label>
                            <input type="text" name="year" placeholder="2566" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                        </div>
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">การอ่านออกเสียง</label>
                                <input type="number" step="0.01" name="subject_เสียง" placeholder="คะแนนเฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">การอ่านรู้เรื่อง</label>
                                <input type="number" step="0.01" name="subject_รู้เรื่อง" placeholder="คะแนนเฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center justify-center gap-2 mt-4 text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> บันทึก RT
                        </button>
                    </form>
                </div>

                <!-- NT Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="microscope" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">NT (ป.3)</h3>
                            <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">National Test</p>
                        </div>
                    </div>
                    <form onsubmit="saveNationalTestDetail(event, 'nt')" class="space-y-4 flex-grow">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">ปีการศึกษา</label>
                            <input type="text" name="year" placeholder="2566" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                        </div>
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">ด้านภาษาไทย</label>
                                <input type="number" step="0.01" name="subject_ภาษาไทย" placeholder="คะแนนเฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">ด้านคณิตศาสตร์</label>
                                <input type="number" step="0.01" name="subject_คณิตศาสตร์" placeholder="คะแนนเฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2 mt-4 text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> บันทึก NT
                        </button>
                    </form>
                </div>

                <!-- O-NET Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col h-full" id="card_onet">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">O-NET</h3>
                            <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Ordinary National Educational Test</p>
                        </div>
                    </div>
                    <form onsubmit="saveNationalTestDetail(event, 'onet')" class="space-y-4 flex-grow">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">ปีการศึกษา</label>
                                <input type="text" name="year" placeholder="2566" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">ระดับชั้น</label>
                                <select name="onet_level" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm cursor-pointer">
                                    <option value="onet_p6">ป.6</option>
                                    <option value="onet_m3" id="opt_m3">ม.3</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">ภาษาไทย</label>
                                <input type="number" step="0.01" name="subject_ภาษาไทย" placeholder="เฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">คณิตศาสตร์</label>
                                <input type="number" step="0.01" name="subject_คณิตศาสตร์" placeholder="เฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">วิทยาศาสตร์</label>
                                <input type="number" step="0.01" name="subject_วิทยาศาสตร์" placeholder="เฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">ภาษาอังกฤษ</label>
                                <input type="number" step="0.01" name="subject_ภาษาอังกฤษ" placeholder="เฉลี่ย" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 outline-none transition-all font-bold text-slate-700 text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-amber-600 text-white py-3 rounded-xl font-bold hover:bg-amber-700 transition-all flex items-center justify-center gap-2 mt-4 text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> บันทึก O-NET
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
                            <label class="block text-sm font-semibold text-slate-700 ml-1">ชื่อ</label>
                            <div class="relative">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <input type="text" id="prof_name" value="<?= $username ?>" required class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700 ml-1">นามสกุล</label>
                            <div class="relative">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <input type="text" id="prof_last_name" value="<?= $last_name ?>" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        <?php include 'includes/dashboard/timetable_overview.php'; ?>
        <?php include 'includes/dashboard/academic_documents.php'; ?>
        <?php include 'includes/dashboard/grading_progress.php'; ?>
        <?php include 'includes/dashboard/teacher_usage_stats.php'; ?>
        <?php include 'includes/dashboard/academic_achievement.php'; ?>

        <!-- Teacher: Record Grades -->
        <?php include 'includes/dashboard/grading.php'; ?>
        <?php include 'includes/dashboard/learner_development.php'; ?>
        <?php include 'includes/dashboard/health_records.php'; ?>
        <?php include 'includes/dashboard/competency.php'; ?>
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
                        last_name: document.getElementById('prof_last_name').value,
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
                                    <div class="flex gap-2 transition-all">
                                        <button onclick="openEditTeacherModal(window.lastLoadedTeachers[${index}], ${schoolId})" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-all cursor-pointer border border-blue-100" title="แก้ไข">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button onclick="deleteTeacher(${t.id})" class="p-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-all cursor-pointer border border-red-100" title="ลบ">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
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
        
        // Data for National Test Chart
        let hasHighSchoolGlobal = false;

        async function loadOverviewData() {
            try {
                const res = await fetch('api/admin/get_overview_data.php');
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);

                // Update Stats
                const ovStudentCount = document.getElementById('ov_student_count');
                const ovMaleTotal = document.getElementById('ov_male_total');
                const ovFemaleTotal = document.getElementById('ov_female_total');
                const ovTeacherCount = document.getElementById('ov_teacher_count');
                const ovCurrentYear = document.getElementById('ov_current_year');

                if (ovStudentCount) ovStudentCount.innerText = data.stats.student_count.toLocaleString();
                if (ovMaleTotal) ovMaleTotal.innerText = data.stats.total_male.toLocaleString();
                if (ovFemaleTotal) ovFemaleTotal.innerText = data.stats.total_female.toLocaleString();
                if (ovTeacherCount) ovTeacherCount.innerText = data.stats.teacher_count.toLocaleString();
                if (ovCurrentYear) ovCurrentYear.innerText = `ปีการศึกษา ${data.stats.academic_year}`;
                
                hasHighSchoolGlobal = data.stats.has_high_school;

                // Student Breakdown Table
                const tableBody = document.getElementById('ov_student_table_body');
                if (tableBody) {
                    tableBody.innerHTML = data.stats.students_by_level.map(l => `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-3 font-bold text-slate-700 text-sm">${l.level}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">${l.male} ช.</span>
                                    <span class="text-[10px] font-bold text-pink-600 bg-pink-50 px-1.5 py-0.5 rounded border border-pink-100">${l.female} ญ.</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right font-black text-slate-800">${l.total}</td>
                        </tr>
                    `).join('');
                }
                
                // Hide M3 O-NET if no high school
                const legendM3 = document.getElementById('legend_onet_m3');
                const optM3 = document.getElementById('opt_m3');
                
                if (!hasHighSchoolGlobal) {
                    if (legendM3) legendM3.classList.add('hidden');
                    if (optM3) optM3.classList.add('hidden');
                } else {
                    if (legendM3) legendM3.classList.remove('hidden');
                    if (optM3) optM3.classList.remove('hidden');
                }

                // Render Chart
                renderNationalTestChart(data.chart_data);

            } catch (e) {
                console.error('Error loading overview data:', e);
            }
        }

        function renderNationalTestChart(data) {
            const container = d3.select("#national_test_chart");
            if (container.empty()) return;
            container.selectAll("*").remove();
            
            const emptyState = document.getElementById('chart_empty_state');
            if (!data || data.length === 0) {
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }
            if (emptyState) emptyState.classList.add('hidden');

            const margin = {top: 20, right: 30, bottom: 40, left: 40};
            const width = container.node().getBoundingClientRect().width - margin.left - margin.right;
            const height = 300 - margin.top - margin.bottom;

            const svg = container.append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", `translate(${margin.left},${margin.top})`);

            const x0 = d3.scaleBand()
                .rangeRound([0, width])
                .paddingInner(0.2)
                .domain(data.map(d => d.year));

            const testTypes = hasHighSchoolGlobal ? ['RT', 'NT', 'ONET_P6', 'ONET_M3'] : ['RT', 'NT', 'ONET_P6'];
            const colorsList = hasHighSchoolGlobal ? ["#3b82f6", "#10b981", "#f59e0b", "#f43f5e"] : ["#3b82f6", "#10b981", "#f59e0b"];

            const x1 = d3.scaleBand()
                .padding(0.05)
                .domain(testTypes)
                .rangeRound([0, x0.bandwidth()]);

            const y = d3.scaleLinear()
                .rangeRound([height, 0])
                .domain([0, 100]);

            const color = d3.scaleOrdinal()
                .domain(testTypes)
                .range(colorsList);

            // Axes
            svg.append("g")
                .attr("transform", `translate(0,${height})`)
                .call(d3.axisBottom(x0))
                .selectAll("text")
                .style("font-family", "Inter")
                .style("font-weight", "600")
                .attr("dy", "1.5em");

            svg.append("g")
                .attr("class", "y-axis")
                .call(d3.axisLeft(y).ticks(5).tickSize(-width))
                .call(g => g.select(".domain").remove())
                .call(g => g.selectAll(".tick line").attr("stroke", "#f1f5f9"))
                .selectAll("text")
                .style("font-family", "Inter")
                .attr("dx", "-0.5em");

            // Bars
            const yearGroup = svg.selectAll(".year-group")
                .data(data)
                .enter().append("g")
                .attr("class", "year-group")
                .attr("transform", d => `translate(${x0(d.year)},0)`);

            yearGroup.selectAll("rect")
                .data(d => testTypes.map(key => ({key, value: d[key] || 0})))
                .enter().append("rect")
                .attr("x", d => x1(d.key))
                .attr("y", height)
                .attr("width", x1.bandwidth())
                .attr("height", 0)
                .attr("fill", d => color(d.key))
                .attr("rx", 4)
                .transition()
                .duration(1000)
                .attr("y", d => y(d.value))
                .attr("height", d => height - y(d.value));

            // Tooltips or Value Labels
            yearGroup.selectAll(".bar-label")
                .data(d => testTypes.map(key => ({key, value: d[key] || 0})))
                .enter().append("text")
                .attr("x", d => x1(d.key) + x1.bandwidth()/2)
                .attr("y", d => y(d.value) - 5)
                .attr("text-anchor", "middle")
                .style("font-size", "9px")
                .style("font-weight", "800")
                .style("fill", "#64748b")
                .text(d => d.value > 0 ? d.value : '');
        }

        // Handle National Test Detailed Form
        async function saveNationalTestDetail(e, type) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;
            
            const formData = new FormData(form);
            const academic_year = formData.get('year');
            const test_type = type === 'onet' ? formData.get('onet_level') : type;
            
            const subjects = [];
            form.querySelectorAll('input[name^="subject_"]').forEach(input => {
                subjects.push({
                    name: input.getAttribute('name').replace('subject_', ''),
                    score: input.value
                });
            });

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> บันทึก...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const res = await fetch('api/admin/save_national_test.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        academic_year,
                        test_type,
                        subjects
                    })
                });
                const result = await res.json();
                if (result.success) {
                    alert(result.message);
                    loadOverviewData();
                } else {
                    alert(result.error);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาด: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        // Show default section
        <?php if ($is_restricted_director): ?>
            showSection('grading-progress');
        <?php elseif ($role === 'teacher' && !$_SESSION['is_academic']): ?>
            showSection('record-grades');
        <?php else: ?>
            showSection('overview');
            loadOverviewData();
        <?php endif; ?>

        // Initialize specialized sections
        if (typeof initHealthSection === 'function') initHealthSection();
        if (typeof initCompetencySection === 'function') initCompetencySection();
        if (typeof initLDSection === 'function') initLDSection();

        // PWA Service Worker & Install Logic
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker failed', err));
            });
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const container = document.getElementById('pwa-install-container');
            if (container) container.classList.remove('hidden');
        });

        const pwaBtn = document.getElementById('pwa-install-btn');
        if (pwaBtn) {
            pwaBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        const container = document.getElementById('pwa-install-container');
                        if (container) container.classList.add('hidden');
                    }
                    deferredPrompt = null;
                }
            });
        }

        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>
