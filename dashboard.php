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
            <a href="#" onclick="showSection('overview')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">ภาพรวม</a>
            
            <?php if ($role === 'super_admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Super Admin</div>
                <a href="#" onclick="showSection('manage-schools')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการโรงเรียน</a>
                <a href="#" onclick="showSection('approve-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">อนุมัติ Admin โรงเรียน</a>
                <a href="#" onclick="showSection('manage-super-admins')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการ Super Admin</a>
                <a href="#" onclick="showSection('profile')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">แก้ไขโปรไฟล์</a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">School Admin</div>
                <a href="#" onclick="showSection('approve-teachers')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">อนุมัติครู</a>
                <a href="#" onclick="showSection('manage-students')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการนักเรียน</a>
                <a href="#" onclick="showSection('manage-subjects')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">จัดการรายวิชา</a>
            <?php endif; ?>

            <?php if ($role === 'teacher' || $role === 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เมนูครู</div>
                <a href="#" onclick="showSection('record-grades')" class="block px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">บันทึกผลการเรียน (ปพ.5/6)</a>
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
                    <button onclick="closeTeacherModal()" class="text-slate-400 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                                <th class="pb-3 font-medium">ตำแหน่ง</th>
                                <th class="pb-3 font-medium">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="modalTeacherTableBody"></tbody>
                    </table>
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
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
            
            let targetId = sectionId;
            if (sectionId === 'approve-admins' || sectionId === 'approve-teachers') {
                targetId = 'approve-section';
                loadPendingUsers();
            } else if (sectionId === 'manage-schools') {
                loadSchools();
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
            const res = await fetch('api/get_schools.php');
            const schools = await res.json();
            const tbody = document.getElementById('schoolTableBody');
            tbody.innerHTML = schools.map(s => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 text-slate-600 font-mono">${s.code}</td>
                    <td class="py-3 font-medium text-slate-800 cursor-pointer hover:text-blue-600" onclick="viewTeachers(${s.id}, '${s.name}')">${s.name}</td>
                    <td class="py-3 text-slate-500">${s.province || '-'}</td>
                    <td class="py-3 flex gap-2">
                        <button onclick="editSchool(${s.id}, '${s.name}', '${s.province || ''}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">แก้ไข</button>
                        <button onclick="deleteSchool(${s.id})" class="text-red-600 hover:text-red-800 text-sm font-medium">ลบ</button>
                    </td>
                </tr>
            `).join('');
        }

        async function editSchool(id, currentName, currentProvince) {
            const newName = prompt('แก้ไขชื่อโรงเรียน:', currentName);
            if (newName === null) return;
            const newProvince = prompt('แก้ไขจังหวัด:', currentProvince);
            if (newProvince === null) return;

            const res = await fetch('api/update_school.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name: newName, province: newProvince })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadSchools();
            } else {
                alert(result.error);
            }
        }

        async function deleteSchool(id) {
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบโรงเรียนนี้?')) return;
            const res = await fetch('api/delete_school.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadSchools();
            } else {
                alert(result.error);
            }
        }

        async function viewTeachers(schoolId, schoolName) {
            document.getElementById('modalSchoolName').innerText = `รายชื่อคุณครู - ${schoolName}`;
            const res = await fetch(`api/get_school_teachers.php?school_id=${schoolId}`);
            const teachers = await res.json();
            const tbody = document.getElementById('modalTeacherTableBody');
            
            if (teachers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-slate-400">ไม่พบรายชื่อคุณครูในโรงเรียนนี้</td></tr>';
            } else {
                tbody.innerHTML = teachers.map(t => `
                    <tr class="border-b border-slate-50">
                        <td class="py-3 font-medium text-slate-800">${t.name}</td>
                        <td class="py-3 text-slate-500">${t.position}</td>
                        <td class="py-3">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold ${t.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                                ${t.is_approved ? 'อนุมัติแล้ว' : 'รออนุมัติ'}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
            
            document.getElementById('teacherModal').classList.remove('hidden');
            document.getElementById('teacherModal').classList.add('flex');
        }

        function closeTeacherModal() {
            document.getElementById('teacherModal').classList.add('hidden');
            document.getElementById('teacherModal').classList.remove('flex');
        }

        async function loadPendingUsers() {
            const res = await fetch('api/get_pending_users.php');
            const users = await res.json();
            const tbody = document.getElementById('pendingUsersTableBody');
            tbody.innerHTML = users.map(u => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 font-medium text-slate-800">${u.full_name}</td>
                    <td class="py-3 text-slate-500">${u.school_name || 'ไม่มีสังกัด'}</td>
                    <td class="py-3 text-slate-500">${u.position}</td>
                    <td class="py-3">
                        <button onclick="approveUser(${u.id}, '${u.role === 'super_admin' ? 'admin' : 'teacher'}')" class="bg-green-600 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-green-700">อนุมัติ</button>
                    </td>
                </tr>
            `).join('');
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
    </script>
</body>
</html>
