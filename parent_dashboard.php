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
    <header id="app_header" class="bg-blue-600 text-white rounded-b-[40px] p-6 pt-10 shadow-lg shadow-blue-500/20 sticky top-0 z-40">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div id="student_avatar" class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 shadow-inner">
                    <i id="avatar_icon" data-lucide="user" class="w-10 h-10"></i>
                </div>
                <div class="space-y-0.5">
                    <p class="text-blue-200 text-[10px] font-bold uppercase tracking-widest">ข้อมูลนักเรียน</p>
                    <h1 id="header_student_name" class="text-lg font-bold leading-tight"><?= $student_name ?></h1>
                    <div class="flex items-center gap-2">
                        <span id="header_student_level" class="bg-white/20 px-2 py-0.5 rounded-lg text-[10px] font-bold backdrop-blur-sm">ชั้น: -</span>
                        <span id="header_student_id" class="text-[10px] text-blue-100 opacity-80">รหัส: <?= $_SESSION['student_code'] ?></span>
                    </div>
                </div>
            </div>
            <button onclick="logout()" class="p-2 bg-white/10 rounded-2xl hover:bg-white/20 transition-all cursor-pointer">
                <i data-lucide="log-out" class="w-5 h-5 text-blue-100"></i>
            </button>
        </div>
        
        <!-- Summary Quick View -->
        <div class="grid grid-cols-3 gap-3 mt-6">
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">เข้าเรียนรวม</p>
                <p id="total_present" class="text-lg font-black mt-1">-</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">GPA เทอมนี้</p>
                <p id="avg_gpa" class="text-lg font-black mt-1">-</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                <p class="text-[10px] text-blue-200 uppercase font-bold">ผลพฤติกรรม</p>
                <p id="behavior_score" class="text-lg font-black mt-1">-</p>
            </div>
        </div>
    </header>

    <main id="content" class="p-4 space-y-6 mt-2">
        <!-- Academic Filters -->
        <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-3">
             <div class="flex-1 space-y-1">
                 <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">ปีการศึกษา</label>
                 <select id="filter_year" onchange="loadData()" class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-0">
                     <!-- Options will be loaded -->
                 </select>
             </div>
             <div class="flex-1 space-y-1">
                 <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">ภาคเรียน</label>
                 <select id="filter_semester" onchange="loadData()" class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-0">
                     <option value="1">ภาคเรียนที่ 1</option>
                     <option value="2">ภาคเรียนที่ 2</option>
                 </select>
             </div>
             <button onclick="loadData()" class="mt-5 p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition-all">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
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

        <!-- Parent Feedback Section -->
        <section class="space-y-4">
            <div class="flex items-center gap-2 mx-2">
                <i data-lucide="message-circle" class="w-4 h-4 text-purple-600"></i>
                <h2 class="font-bold text-slate-800">ความคิดเห็นผู้ปกครอง (ปพ.6)</h2>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-6">
                <p class="text-xs text-slate-500 italic">ข้อมูลนี้จะถูกนำไปพิมพ์ลงในเอกสาร ปพ.6 ของนักเรียนครับ</p>
                
                <div class="space-y-3">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">พฤติกรรมที่บ้าน (เลือกได้)</label>
                    <div class="flex flex-wrap gap-2" id="feedback_tags">
                        <?php 
                        $tags = ["ช่วยงานบ้าน", "ขยันอ่านหนังสือ", "ตรงต่อเวลา", "มีวินัย", "กตัญญู", "สวดมนต์บ่อย", "รักความสะอาด"];
                        foreach($tags as $tag): ?>
                        <button onclick="toggleTag('<?= $tag ?>')" class="tag-btn text-xs px-3 py-1.5 bg-slate-50 text-slate-600 border border-slate-100 rounded-full transition-all">
                            <?= $tag ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">ความคิดเห็นเพิ่มเติมจากท่าน</label>
                    <textarea id="feedback_text" rows="3" placeholder="ระบุสิ่งที่ท่านอยากบอกคุณครูเกี่ยวกับนักเรียน..." 
                        class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                </div>

                <button onclick="saveFeedback()" id="saveFeedbackBtn" class="w-full bg-purple-600 text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-purple-600/20 active:scale-95 transition-all">
                    บันทึกข้อมูล ปพ.6
                </button>
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

        let selectedTags = [];
        let isFirstLoad = true;

        async function loadData() {
            const year = document.getElementById('filter_year').value;
            const semester = document.getElementById('filter_semester').value;
            
            const url = `api/parent/get_student_data.php?academic_year=${year}&semester=${semester}`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.error) {
                    alert('ไม่สามารถโหลดข้อมูลได้: ' + data.error);
                    return;
                }

                // Update Profile Header
                const student = data.student;
                document.getElementById('header_student_name').innerText = student.name;
                document.getElementById('header_student_level').innerText = 'ชั้น: ' + (student.level || '-');
                
                // Gender/Title Icon Logic
                const avatarDiv = document.getElementById('student_avatar');
                const avatarIcon = document.getElementById('avatar_icon');
                if (student.name.includes('เด็กชาย') || student.name.includes('นาย') || student.name.includes('ด.ช.')) {
                    avatarDiv.className = "w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center border border-blue-100 shadow-sm text-blue-500";
                    avatarIcon.setAttribute('data-lucide', 'user');
                } else {
                    avatarDiv.className = "w-16 h-16 bg-pink-50 rounded-2xl flex items-center justify-center border border-pink-100 shadow-sm text-pink-500";
                    avatarIcon.setAttribute('data-lucide', 'user-round-plus'); // Simple placeholder for female icon
                }
                
                // Update Filters
                if (isFirstLoad) {
                    const yearSelect = document.getElementById('filter_year');
                    yearSelect.innerHTML = data.filters.available_years.map(y => `<option value="${y}" ${y === data.filters.current_year ? 'selected' : ''}>ปีการศึกษา ${y}</option>`).join('');
                    document.getElementById('filter_semester').value = data.filters.current_semester;
                    isFirstLoad = false;
                }

                // Update Attendance (Total/Current - depends on interpretation, keep it total for now)
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
                    list.innerHTML = '<div class="bg-white p-8 text-center text-slate-400 rounded-2xl italic shadow-sm">ยังไม่มีข้อมูลเกรดในเทอมนี้</div>';
                } else {
                    list.innerHTML = data.grades.map(g => `
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-50 flex items-center justify-center rounded-xl font-bold text-slate-400 text-xs text-center p-1 uppercase leading-tight">
                                    ${g.subject_code}
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-700 leading-tight">${g.subject_name}</h4>
                                    <p class="text-[10px] text-slate-400 uppercase font-black">เทอม ${g.semester}/${g.academic_year}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-blue-600">${g.grade_point}</div>
                                <p class="text-[9px] text-slate-400 uppercase font-black tracking-tighter">Grade</p>
                            </div>
                        </div>
                    `).join('');
                }

                // Calc average GPA
                if (data.grades.length > 0) {
                    const avg = data.grades.reduce((acc, curr) => acc + parseFloat(curr.grade_point), 0) / data.grades.length;
                    document.getElementById('avg_gpa').innerText = avg.toFixed(2);
                } else {
                    document.getElementById('avg_gpa').innerText = '-';
                }

                // Update Feedback
                if (data.parent_feedback) {
                    document.getElementById('feedback_text').value = data.parent_feedback.feedback_text || '';
                    selectedTags = data.parent_feedback.tags ? data.parent_feedback.tags.split(',') : [];
                    updateTagUI();
                } else {
                    document.getElementById('feedback_text').value = '';
                    selectedTags = [];
                    updateTagUI();
                }

                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error(err);
            }
        }

        function toggleTag(tag) {
            const index = selectedTags.indexOf(tag);
            if (index === -1) {
                selectedTags.push(tag);
            } else {
                selectedTags.splice(index, 1);
            }
            updateTagUI();
        }

        function updateTagUI() {
            document.querySelectorAll('.tag-btn').forEach(btn => {
                const tag = btn.innerText.trim();
                if (selectedTags.includes(tag)) {
                    btn.classList.add('bg-purple-600', 'text-white', 'border-purple-600');
                    btn.classList.remove('bg-slate-50', 'text-slate-600', 'border-slate-100');
                } else {
                    btn.classList.remove('bg-purple-600', 'text-white', 'border-purple-600');
                    btn.classList.add('bg-slate-50', 'text-slate-600', 'border-slate-100');
                }
            });
        }

        async function saveFeedback() {
            const btn = document.getElementById('saveFeedbackBtn');
            const original = btn.innerText;
            const year = document.getElementById('filter_year').value;
            const semester = document.getElementById('filter_semester').value;
            
            if (!year || !semester) return alert('กรุณาเลือกปีการศึกษาและเทอม');

            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';

            try {
                const res = await fetch('api/parent/save_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        academic_year: year,
                        semester: semester,
                        feedback_text: document.getElementById('feedback_text').value,
                        tags: selectedTags.join(',')
                    })
                });
                const result = await res.json();
                if (result.success) {
                    alert('บันทึกข้อมูลเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลครับ');
                } else {
                    alert(result.message);
                }
            } catch (e) {
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                btn.disabled = false;
                btn.innerText = original;
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
