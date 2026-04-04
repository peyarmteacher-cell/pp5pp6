<?php
session_start();
// ตรวจสอบว่า Login หรือยัง
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบบริหารจัดการสถานศึกษา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-[#0f172a] text-white flex flex-col">
        <div class="p-6 text-center border-b border-slate-800">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <h1 class="font-bold text-lg">ระบบวัดผล ปพ.</h1>
            <p class="text-xs text-slate-400"><?php echo strtoupper($role); ?></p>
        </div>

        <nav class="flex-1 p-4 space-y-2">
            <!-- เมนูพื้นฐานสำหรับทุกคน -->
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-600 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>หน้าแรก</span>
            </a>

            <?php if ($role === 'super_admin'): ?>
                <!-- เมนู Super Admin -->
                <div class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Super Admin</div>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>จัดการโรงเรียน</span>
                </a>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>อนุมัติ Admin โรงเรียน</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin' || $role === 'super_admin'): ?>
                <!-- เมนู Admin โรงเรียน -->
                <div class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">School Admin</div>
                <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="16" x2="22" y1="11" y2="11"/></svg>
                    <span>อนุมัติครูในโรงเรียน</span>
                </a>
            <?php endif; ?>

            <!-- เมนูสำหรับครู (Teacher) -->
            <div class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Teacher Menu</div>
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                <span>บันทึกผลการเรียน ปพ.5</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>บันทึกผลการเรียน ปพ.6</span>
            </a>
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>จัดการข้อมูลนักเรียน</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-600/20 text-red-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 p-4 flex justify-between items-center sticky top-0 z-10">
            <div class="flex items-center space-x-4">
                <h2 class="text-xl font-semibold text-slate-800">ยินดีต้อนรับคุณ <?php echo htmlspecialchars($name); ?></h2>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($name); ?></p>
                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-500 font-bold">
                    <?php echo mb_substr($name, 0, 1, 'UTF-8'); ?>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stats Cards -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-1">จำนวนนักเรียนทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-slate-900">0</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-1">รายวิชาที่สอน</p>
                    <h3 class="text-3xl font-bold text-slate-900">0</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-1">สถานะระบบ</p>
                    <h3 class="text-3xl font-bold text-green-600">พร้อมใช้งาน</h3>
                </div>
            </div>

            <!-- Role Specific Content -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800">
                        <?php 
                            if($role === 'super_admin') echo "การจัดการระบบส่วนกลาง (Super Admin)";
                            else if($role === 'admin') echo "การจัดการภายในโรงเรียน (School Admin)";
                            else echo "รายการบันทึกผลการเรียนของคุณ (Teacher)";
                        ?>
                    </h3>
                </div>
                <div class="p-12 text-center text-slate-400">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto opacity-20"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <p>ยังไม่มีข้อมูลที่จะแสดงในขณะนี้</p>
                    <p class="text-sm">กรุณาเลือกเมนูจากแถบด้านซ้ายเพื่อเริ่มต้นใช้งาน</p>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
