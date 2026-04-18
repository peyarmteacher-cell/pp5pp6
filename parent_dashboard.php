<?php
session_start();
if (!isset($_SESSION['parent_logged_in'])) {
    header('Location: parent_login.php');
    exit;
}
$student_name = $_SESSION['student_name'];
$school_name = $_SESSION['school_name'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no text-size-adjust=none">
    <title>ข้อมูลนักเรียน: <?= $student_name ?></title>
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; -webkit-tap-highlight-color: transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .pull-to-refresh { transition: transform 0.2s ease-out; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen pb-24">

    <!-- Header -->
    <header class="bg-blue-600 text-white rounded-b-[40px] p-6 pt-10 shadow-lg shadow-blue-500/20 sticky top-0 z-40">
        <div class="flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-blue-200 text-xs font-bold uppercase tracking-widest">ยินดีต้อนรับผู้ปกครอง</p>
                <h1 class="text-xl font-bold"><?= $student_name ?></h1>
                <p class="text-xs text-blue-100 opacity-80"><?= $school_name ?></p>
            </div>
            <button onclick="logout()" class="p-2 bg-white/10 rounded-2xl hover:bg-white/20 transition-all cursor-pointer">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </button>
        </div>
        
        <!-- Summary Quick View -->
        <div class="grid grid-cols-3 gap-3 mt-8">
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">เข้าเรียน</p>
                <p id="total_present" class="text-lg font-black mt-1">-</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">เกรดเฉลี่ย</p>
                <p id="avg_gpa" class="text-lg font-black mt-1">-</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">พฤติกรรม</p>
                <p id="behavior_score" class="text-lg font-black mt-1">-</p>
            </div>
        </div>
    </header>

    <main id="content" class="p-4 space-y-6 mt-4">
        <!-- Refresh Indicator (Simple) -->
        <div class="flex justify-center -mb-4">
             <button onclick="loadData()" class="px-4 py-2 bg-white rounded-full shadow-sm border border-slate-100 text-blue-600 text-xs font-bold flex items-center gap-2 hover:bg-blue-50 transition-all cursor-pointer active:scale-95">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                อัปเดตข้อมูล
             </button>
        </div>

        <!-- Attendance Section -->
        <section class="space-y-3">
            <div class="flex items-center gap-2 mx-2">
                <i data-lucide="calendar-check" class="w-4 h-4 text-blue-600"></i>
                <h2 class="font-bold text-slate-800">สถิติการเข้าเรียน</h2>
            </div>
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 grid grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-xl font-bold text-green-600" id="att_present">0</div>
                    <p class="text-[10px] text-slate-400 font-bold">มาเรียน</p>
                </div>
                <div>
                    <div class="text-xl font-bold text-amber-500" id="att_late">0</div>
                    <p class="text-[10px] text-slate-400 font-bold">มาสาย</p>
                </div>
                <div>
                    <div class="text-xl font-bold text-red-500" id="att_absent">0</div>
                    <p class="text-[10px] text-slate-400 font-bold">ขาดเรียน</p>
                </div>
                <div>
                    <div class="text-xl font-bold text-blue-500" id="att_leave">0</div>
                    <p class="text-[10px] text-slate-400 font-bold">ลา/ป่วย</p>
                </div>
            </div>
        </section>

        <!-- Grades Section -->
        <section class="space-y-3">
            <div class="flex items-center justify-between mx-2">
                <div class="flex items-center gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                    <h2 class="font-bold text-slate-800">ผลการเรียน</h2>
                </div>
                <!-- <span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-black">ล่าสุด</span> -->
            </div>
            
            <div id="grades_list" class="space-y-3">
                <!-- Data will be loaded here -->
                 <div class="animate-pulse flex flex-col gap-3">
                     <div class="h-16 bg-white rounded-2xl shadow-sm"></div>
                     <div class="h-16 bg-white rounded-2xl shadow-sm"></div>
                 </div>
            </div>
        </section>

        <!-- Health Section (Placeholder/Future) -->
        <section class="bg-gradient-to-br from-rose-50 to-pink-50 p-6 rounded-3xl border border-rose-100 relative overflow-hidden">
            <div class="relative z-10 flex items-center justify-between">
                <div class="space-y-1">
                    <h3 class="font-bold text-rose-800">สุขภาพนักเรียน</h3>
                    <p class="text-xs text-rose-600">น้ำหนัก: <span id="weight" class="font-bold">-</span> กก. | ส่วนสูง: <span id="height" class="font-bold">-</span> ซม.</p>
                </div>
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-rose-500 shadow-sm">
                    <i data-lucide="heart" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-5">
                <i data-lucide="activity" class="w-24 h-24"></i>
            </div>
        </section>

    </main>

    <!-- Navigation Bar -->
    <nav class="fixed bottom-6 left-1/2 -translate-x-1/2 w-[90%] max-w-sm bg-slate-900/90 backdrop-blur-xl rounded-full p-2 flex items-center justify-between shadow-2xl z-50 border border-white/10">
        <button class="nav-btn active w-14 h-14 flex items-center justify-center rounded-full bg-blue-600 text-white transition-all">
            <i data-lucide="home" class="w-6 h-6"></i>
        </button>
        <button onclick="alert('ฟีเจอร์นี้กำลังพัฒนา: ตารางเรียน')" class="nav-btn w-14 h-14 flex items-center justify-center rounded-full text-slate-400 hover:bg-white/10 transition-all">
            <i data-lucide="clock" class="w-6 h-6"></i>
        </button>
        <button onclick="alert('ฟีเจอร์นี้กำลังพัฒนา: ประกาศข่าว')" class="nav-btn w-14 h-14 flex items-center justify-center rounded-full text-slate-400 hover:bg-white/10 transition-all">
            <i data-lucide="bell" class="w-6 h-6"></i>
        </button>
        <button onclick="alert('ฟีเจอร์นี้กำลังพัฒนา: แชทกับครู')" class="nav-btn w-14 h-14 flex items-center justify-center rounded-full text-slate-400 hover:bg-white/10 transition-all">
            <i data-lucide="message-square" class="w-6 h-6"></i>
        </button>
    </nav>

    <script>
        lucide.createIcons();

        async function loadData() {
            try {
                const res = await fetch('api/parent/get_student_data.php');
                const data = await res.json();
                
                if (data.error) {
                    alert('ไม่สามารถโหลดข้อมูลได้: ' + data.error);
                    return;
                }

                // Update Attendance
                let counts = { present: 0, late: 0, absent: 0, leave: 0, sick: 0 };
                data.attendance.forEach(a => { counts[a.status] = a.count; });
                
                document.getElementById('att_present').innerText = counts.present;
                document.getElementById('att_late').innerText = counts.late;
                document.getElementById('att_absent').innerText = counts.absent;
                document.getElementById('att_leave').innerText = parseInt(counts.leave) + parseInt(counts.sick);
                document.getElementById('total_present').innerText = counts.present + ' วัน';

                // Update Behavior
                document.getElementById('behavior_score').innerText = (data.behavior ? data.behavior.score : '3') + '/3';

                // Update Grades
                const list = document.getElementById('grades_list');
                if (data.grades.length === 0) {
                    list.innerHTML = '<div class="bg-white p-8 text-center text-slate-400 rounded-2xl italic shadow-sm">ยังไม่มีข้อมูลเกรด</div>';
                } else {
                    list.innerHTML = data.grades.map(g => `
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-50 flex items-center justify-center rounded-xl font-bold text-slate-400 text-xs">
                                    ${g.subject_code}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700">${g.subject_name}</h4>
                                    <p class="text-[10px] text-slate-400">ปีการศึกษา ${g.academic_year} เทอม ${g.semester}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-blue-600">${g.grade_point}</div>
                                <p class="text-[10px] text-slate-400 uppercase font-black tracking-tighter">Grade</p>
                            </div>
                        </div>
                    `).join('');
                }

                // Calc average GPA if grades exist
                if (data.grades.length > 0) {
                    const latestYear = data.grades[0].academic_year;
                    const latestSemester = data.grades[0].semester;
                    const currentGrades = data.grades.filter(g => g.academic_year === latestYear && g.semester === latestSemester);
                    const avg = currentGrades.reduce((acc, curr) => acc + parseFloat(curr.grade_point), 0) / currentGrades.length;
                    document.getElementById('avg_gpa').innerText = avg.toFixed(2);
                }

            } catch (err) {
                console.error(err);
            }
        }

        function logout() {
            if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
                window.location.href = 'logout.php';
            }
        }

        // Auto load
        loadData();
    </script>
</body>
</html>
