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
        <div class="flex justify-between items-center gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <div id="student_avatar" class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 shadow-inner shrink-0">
                    <i id="avatar_icon" data-lucide="user" class="w-8 h-8"></i>
                </div>
                <div class="space-y-0.5 min-w-0">
                    <h1 id="header_student_name" class="text-base font-bold leading-tight truncate"><?= $student_name ?></h1>
                    <div class="flex items-center gap-2">
                        <span id="header_student_level" class="bg-white/20 px-2 py-0.5 rounded-lg text-[9px] font-bold backdrop-blur-sm">ชั้น: -</span>
                        <span id="header_student_id" class="text-[9px] text-blue-100/70">รหัส: <?= $_SESSION['student_code'] ?></span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 shrink-0">
                <!-- Compact GPA Pill -->
                <div class="bg-white/10 backdrop-blur-xl border border-white/30 px-4 py-2 rounded-2xl text-center shadow-lg relative overflow-hidden">
                    <p class="text-[8px] text-blue-100 font-black uppercase tracking-widest mb-1 opacity-80">GPA เทอมนี้</p>
                    <div id="header_avg_gpa" class="text-xl font-black text-white leading-none tracking-tighter">-</div>
                    <div class="absolute -right-2 -top-2 w-6 h-6 bg-white/10 rounded-full blur-md"></div>
                </div>
                
                <button onclick="logout()" class="p-2.5 bg-white/10 rounded-2xl hover:bg-white/20 transition-all cursor-pointer">
                    <i data-lucide="log-out" class="w-4 h-4 text-blue-100"></i>
                </button>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="flex items-center mt-8 bg-black/10 rounded-2xl p-1 backdrop-blur-md">
            <button onclick="switchTab('academic')" class="tab-btn active flex-1 py-2 text-xs font-bold rounded-xl transition-all">ผลการเรียน</button>
            <button onclick="switchTab('behavior')" class="tab-btn flex-1 py-2 text-xs font-bold rounded-xl transition-all">บันทึกพฤติกรรม</button>
            <button onclick="switchTab('health')" class="tab-btn flex-1 py-2 text-xs font-bold rounded-xl transition-all">สุขภาพ</button>
        </div>
    </header>

    <style>
        .tab-btn { color: rgba(255,255,255,0.6); }
        .tab-btn.active { background: white; color: #2563eb; }
        .section-tab { display: none; }
        .section-tab.active { display: block; }
    </style>

    <main id="content" class="p-4 space-y-6 mt-2">
        <!-- Academic Filters (Persistent) -->
        <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-3">
             <div class="flex-1 space-y-1">
                 <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">ปีการศึกษา</label>
                 <select id="filter_year" onchange="loadData()" class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-0">
                 </select>
             </div>
             <div class="flex-1 space-y-1">
                 <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">ภาคเรียน</label>
                 <select id="filter_semester" onchange="loadData()" class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-0">
                     <option value="1">ภาคเรียนที่ 1</option>
                     <option value="2">ภาคเรียนที่ 2</option>
                 </select>
             </div>
        </div>

        <!-- TAB 1: ผลการเรียน -->
        <div id="tab_academic" class="section-tab active space-y-6">
            <!-- Attendance Section (Restored) -->
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

            <section class="space-y-3">
                <div class="flex items-center mx-2 gap-2">
                    <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                    <h2 class="font-bold text-slate-800">ผลการเรียนประจำภาคเรียน</h2>
                </div>
                <div id="grades_list" class="space-y-3">
                     <div class="animate-pulse flex flex-col gap-3">
                         <div class="h-16 bg-white rounded-2xl shadow-sm"></div>
                         <div class="h-16 bg-white rounded-2xl shadow-sm"></div>
                     </div>
                </div>
            </section>
        </div>

        <!-- TAB 2: บันทึกพฤติกรรม -->
        <div id="tab_behavior" class="section-tab space-y-6">
            <section class="space-y-4">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-purple-600"></i>
                        <h2 class="font-bold text-slate-800">บันทึกพฤติกรรมโดยผู้ปกครอง</h2>
                    </div>
                    <p class="text-xs text-slate-500 italic">กรุณาระบุความคิดเห็นของท่านในแต่ละด้าน เพื่อใช้ประกอบในเล่ม ปพ.6 ครับ</p>
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700">1. ด้านหน้าที่รับผิดชอบ ความเอาใจใส่การเรียน</label>
                            <textarea id="fb_responsibility" rows="2" placeholder="เช่น ตั้งใจทำการบ้านด้วยตนเอง..." class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700">2. ด้านการใช้เวลาว่าง</label>
                            <textarea id="fb_spare_time" rows="2" placeholder="เช่น ชอบอ่านหนังสือนิทาน วาดรูป..." class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700">3. ด้านความสัมพันธ์กับบุคคลรอบข้าง</label>
                            <textarea id="fb_relationship" rows="2" placeholder="เช่น เข้ากับเพื่อนบ้านและพี่น้องได้ดี..." class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700">4. ด้านอุปนิสัย บุคลิกภาพ</label>
                            <textarea id="fb_personality" rows="2" placeholder="เช่น ร่าเริง แจ่มใส มีระเบียบวินัย..." class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700">5. ด้านสุขภาพ</label>
                            <textarea id="fb_health" rows="2" placeholder="เช่น ร่างกายแข็งแรง นอนหลับพักผ่อนเพียงพอ..." class="w-full p-4 bg-slate-50 border-none rounded-2xl text-sm outline-none focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <button onclick="saveFeedback()" id="saveFeedbackBtn" class="w-full bg-purple-600 text-white py-4 rounded-2xl font-bold hover:bg-purple-700 shadow-lg shadow-purple-600/20 active:scale-95 transition-all">
                        บันทึกข้อมูล ปพ.6
                    </button>
                </div>
            </section>
        </div>

        <!-- TAB 3: สุขภาพ -->
        <div id="tab_health" class="section-tab space-y-6">
            <section class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                         <i data-lucide="activity" class="w-5 h-5 text-rose-500"></i>
                         <h2 class="font-bold text-slate-800">กราฟแสดงการเจริญเติบโต</h2>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase font-black">ตลอดปีการศึกษา</p>
                </div>
                
                <div id="growth_chart" class="w-full h-48 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 relative">
                     <!-- Chart will be rendered here -->
                     <p class="text-xs italic">กำลังประมวลผลกราฟ...</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100 text-center">
                        <p class="text-[10px] text-rose-400 font-bold uppercase mb-1">น้ำหนักล่าสุด</p>
                        <p class="text-xl font-black text-rose-600"><span id="health_weight">-</span> กก.</p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 text-center">
                        <p class="text-[10px] text-blue-400 font-bold uppercase mb-1">ส่วนสูงล่าสุด</p>
                        <p class="text-xl font-black text-blue-600"><span id="health_height">-</span> ซม.</p>
                    </div>
                </div>
            </section>
        </div>

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

    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        lucide.createIcons();

        let isFirstLoad = true;

        function switchTab(tabId) {
            document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById('tab_' + tabId).classList.add('active');
            event.currentTarget.classList.add('active');
            
            if (tabId === 'health') {
                // Render chart when tab is visible
                setTimeout(renderGrowthChart, 100);
            }
        }

        async function loadData() {
            const yearSelect = document.getElementById('filter_year');
            const semesterSelect = document.getElementById('filter_semester');
            
            let year = yearSelect ? yearSelect.value : '';
            let semester = semesterSelect ? semesterSelect.value : '';
            
            const url = `api/parent/get_student_data.php?academic_year=${year}&semester=${semester}`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.error) return alert('ไม่สามารถโหลดข้อมูลได้');

                // Update Profile Header
                const student = data.student;
                const full_name = student.name + (student.last_name ? ' ' + student.last_name : '');
                document.getElementById('header_student_name').innerText = full_name;
                document.getElementById('header_student_level').innerText = 'ชั้น: ' + (student.level || '-');
                
                // Gender Icon
                const avatarIcon = document.getElementById('avatar_icon');
                if (student.name.includes('เด็กชาย') || student.name.includes('นาย') || student.name.includes('ด.ช.')) {
                    avatarIcon.setAttribute('data-lucide', 'user');
                } else {
                    avatarIcon.setAttribute('data-lucide', 'user-round-plus');
                }
                
                // Filters
                if (isFirstLoad) {
                    yearSelect.innerHTML = data.filters.available_years.map(y => `<option value="${y}" ${y === data.filters.current_year ? 'selected' : ''}>ปีการศึกษา ${y}</option>`).join('');
                    semesterSelect.value = data.filters.current_semester;
                    isFirstLoad = false;
                }

                // Update Attendance (Restored)
                let counts = { present: 0, late: 0, absent: 0, leave: 0, sick: 0 };
                data.attendance.forEach(a => { counts[a.status] = a.count; });
                
                document.getElementById('att_present').innerText = counts.present;
                document.getElementById('att_late').innerText = counts.late;
                document.getElementById('att_absent').innerText = counts.absent;
                document.getElementById('att_leave').innerText = parseInt(counts.leave) + parseInt(counts.sick);

                // Calc average GPA
                const list = document.getElementById('grades_list');
                if (data.grades.length === 0) {
                    list.innerHTML = '<div class="bg-white p-8 text-center text-slate-400 rounded-2xl italic shadow-sm">ไม่มีข้อมูลในเทอมนี้</div>';
                    document.getElementById('header_avg_gpa').innerText = '-';
                } else {
                    const avg = data.grades.reduce((acc, curr) => acc + (parseFloat(curr.grade) || 0), 0) / data.grades.length;
                    document.getElementById('header_avg_gpa').innerText = avg.toFixed(2);
                    
                    list.innerHTML = data.grades.map(g => `
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-50 flex items-center justify-center rounded-xl font-extrabold text-blue-400/50 text-[10px] border border-slate-100">
                                    ${g.subject_code}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700 leading-tight">${g.subject_name}</h4>
                                    <p class="text-[10px] text-slate-400 font-bold">คะแนนรวม: ${g.score_total || '0'}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-black text-blue-600">${g.grade || '-'}</div>
                                <p class="text-[9px] text-slate-400 uppercase font-bold tracking-tighter">Grade</p>
                            </div>
                        </div>
                    `).join('');
                }

                // Feedback
                if (data.parent_feedback) {
                    document.getElementById('fb_responsibility').value = data.parent_feedback.responsibility_comment || '';
                    document.getElementById('fb_spare_time').value = data.parent_feedback.spare_time_comment || '';
                    document.getElementById('fb_relationship').value = data.parent_feedback.relationship_comment || '';
                    document.getElementById('fb_personality').value = data.parent_feedback.personality_comment || '';
                    document.getElementById('fb_health').value = data.parent_feedback.health_comment || '';
                } else {
                    ['fb_responsibility','fb_spare_time','fb_relationship','fb_personality','fb_health'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                }

                // Health
                document.getElementById('health_weight').innerText = student.weight || '-';
                document.getElementById('health_height').innerText = student.height || '-';

                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error(err);
            }
        }

        function renderGrowthChart() {
            const container = document.getElementById('growth_chart');
            container.innerHTML = '';
            
            const margin = {top: 20, right: 30, bottom: 30, left: 40},
                  width = container.clientWidth - margin.left - margin.right,
                  height = container.clientHeight - margin.top - margin.bottom;

            const svg = d3.select("#growth_chart")
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", `translate(${margin.left},${margin.top})`);

            // Mock Data for growth over 1 year (Monthly)
            const data = [
                {month: 1, height: 120}, {month: 2, height: 121}, {month: 3, height: 121.5},
                {month: 4, height: 122}, {month: 5, height: 123}, {month: 6, height: 123.5},
                {month: 7, height: 124}, {month: 8, height: 124.2}, {month: 9, height: 125},
                {month: 10, height: 125.8}, {month: 11, height: 126}, {month: 12, height: 127}
            ];

            const x = d3.scaleLinear().domain([1, 12]).range([0, width]);
            const y = d3.scaleLinear().domain([115, 135]).range([height, 0]);

            svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x).ticks(6).tickFormat(d => 'ด.' + d));
            svg.append("g").call(d3.axisLeft(y).ticks(5));

            const line = d3.line()
                .x(d => x(d.month))
                .y(d => y(d.height))
                .curve(d3.curveMonotoneX);

            svg.append("path")
                .datum(data)
                .attr("fill", "none")
                .attr("stroke", "#f43f5e")
                .attr("stroke-width", 3)
                .attr("d", line);

            svg.selectAll("dot")
                .data(data)
                .enter().append("circle")
                .attr("cx", d => x(d.month))
                .attr("cy", d => y(d.height))
                .attr("r", 4)
                .attr("fill", "white")
                .attr("stroke", "#f43f5e")
                .attr("stroke-width", 2);
        }

        async function saveFeedback() {
            const btn = document.getElementById('saveFeedbackBtn');
            const original = btn.innerText;
            const year = document.getElementById('filter_year').value;
            const semester = document.getElementById('filter_semester').value;
            
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';

            const payload = {
                academic_year: year,
                semester: semester,
                responsibility: document.getElementById('fb_responsibility').value,
                spare_time: document.getElementById('fb_spare_time').value,
                relationship: document.getElementById('fb_relationship').value,
                personality: document.getElementById('fb_personality').value,
                health_comment: document.getElementById('fb_health').value
            };

            try {
                const res = await fetch('api/parent/save_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (result.success) alert('บันทึกข้อมูลเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลครับ');
            } catch (e) {
                alert('เกิดข้อผิดพลาด');
            } finally {
                btn.disabled = false;
                btn.innerText = original;
            }
        }

        function logout() {
            if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
                window.location.href = 'parent_logout.php';
            }
        }

        // Auto load
        loadData();
    </script>
</body>
</html>
