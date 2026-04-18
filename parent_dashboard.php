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
<body class="bg-slate-50 min-h-screen">

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
                     <option value="annual">รวมทั้งปีการศึกษา</option>
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
                <div class="flex items-center justify-between mx-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4 text-amber-500"></i>
                        <h2 class="font-bold text-slate-800">ผลการเรียนประจำภาคเรียน</h2>
                    </div>
                    <div class="flex gap-4 text-[10px] font-black text-slate-400 uppercase tracking-widest px-2">
                        <span class="w-16 text-center">คะแนนรวม</span>
                        <span class="w-12 text-center">เกรด</span>
                    </div>
                </div>
                <div id="grades_list" class="space-y-2">
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
                         <i data-lucide="trending-up" class="w-5 h-5 text-blue-500"></i>
                         <h2 class="font-bold text-slate-800">พัฒนาการด้านส่วนสูง (ซม.)</h2>
                    </div>
                </div>
                <div id="height_chart" class="w-full h-48 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 relative transition-all overflow-hidden">
                     <p class="text-xs italic">กำลังประมวลผลข้อมูลส่วนสูง...</p>
                </div>
            </section>

            <section class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                         <i data-lucide="activity" class="w-5 h-5 text-rose-500"></i>
                         <h2 class="font-bold text-slate-800">พัฒนาการด้านน้ำหนัก (กก.)</h2>
                    </div>
                </div>
                <div id="weight_chart" class="w-full h-48 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 relative transition-all overflow-hidden">
                     <p class="text-xs italic">กำลังประมวลผลข้อมูลน้ำหนัก...</p>
                </div>
            </section>

            <section class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100">
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

    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        lucide.createIcons();

        let isFirstLoad = true;
        let healthHistory = [];

        function switchTab(tabId) {
            document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById('tab_' + tabId).classList.add('active');
            event.currentTarget.classList.add('active');
            
            if (tabId === 'health') {
                // Render charts when tab is visible
                setTimeout(renderHealthCharts, 100);
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
                const text = await res.text();
                let data;
                
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Non-JSON Response:', text);
                    return alert('ไม่สามารถโหลดข้อมูลได้: รูปแบบข้อมูลไม่ถูกต้อง');
                }
                
                if (!res.ok) {
                    return alert('ไม่สามารถโหลดข้อมูลได้: ' + (data.error || 'Server Error (' + res.status + ')'));
                }
                
                if (data.error) {
                    console.error('API Error:', data.error);
                    return alert('ไม่สามารถโหลดข้อมูลได้: ' + data.error);
                }

                // Update Profile Header
                const student = data.student;
                const full_name = student.name + (student.last_name ? ' ' + student.last_name : '');
                document.getElementById('header_student_name').innerText = full_name;
                const level_info = 'ชั้น: ' + (student.level || '-') + (student.classroom_name ? ' ห้อง: ' + student.classroom_name : '');
                document.getElementById('header_student_level').innerText = level_info;
                
                // Gender Icon
                const avatarIcon = document.getElementById('avatar_icon');
                const nameStr = student.name || '';
                if (nameStr.includes('เด็กชาย') || nameStr.includes('นาย') || nameStr.includes('ด.ช.')) {
                    avatarIcon.setAttribute('data-lucide', 'user');
                } else {
                    avatarIcon.setAttribute('data-lucide', 'user-round-plus');
                }
                lucide.createIcons();
                
                // Filters
                if (isFirstLoad) {
                    if (data.filters && data.filters.available_years) {
                        const years = [...data.filters.available_years];
                        const currentY = data.filters.current_year ? data.filters.current_year.toString() : '';
                        
                        // If current year is not in the list of years with grades, add it as a default option
                        if (currentY && !years.includes(currentY)) {
                            years.unshift(currentY);
                        }
                        
                        yearSelect.innerHTML = years.map(y => `<option value="${y}" ${y == currentY ? 'selected' : ''}>ปีการศึกษา ${y}</option>`).join('');
                    }
                    if (data.filters.current_semester) {
                        semesterSelect.value = data.filters.current_semester;
                    }
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
                const showGrades = student.school_show_grades == 1;

                if (!showGrades) {
                    list.innerHTML = `
                        <div class="bg-amber-50 p-8 rounded-3xl border border-amber-100 text-center space-y-3">
                            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto shadow-sm">
                                <i data-lucide="lock" class="w-6 h-6"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-amber-900 text-sm">ยังไม่เปิดให้ดูผลการเรียน</h3>
                                <p class="text-xs text-amber-700 leading-relaxed px-4">เจ้าหน้าที่วิชาการกำลังอยู่ระหว่างการประมวลผลข้อมูล<br>กรุณาตรวจสอบอีกครั้งภายหลังครับ</p>
                            </div>
                        </div>
                    `;
                    document.getElementById('header_avg_gpa').innerText = '-';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } else if (!data.grades || data.grades.length === 0) {
                    list.innerHTML = '<div class="bg-white p-8 text-center text-slate-400 rounded-2xl italic shadow-sm">ไม่มีข้อมูลในเทอมนี้</div>';
                    document.getElementById('header_avg_gpa').innerText = '-';
                } else {
                    // Filter numeric grades for GPA calc
                    let totalWeightedGrade = 0;
                    let totalCredits = 0;

                    const processedGrades = data.grades.map(g => {
                        // Priority: grade string > grade_point
                        let displayGrade = '-';
                        let numericGrade = null;
                        const credits = parseFloat(g.credits) || 0;
                        
                        if (g.grade !== null && g.grade !== undefined && g.grade !== '') {
                            displayGrade = g.grade;
                            const parsed = parseFloat(g.grade);
                            if (!isNaN(parsed)) {
                                numericGrade = parsed;
                                totalWeightedGrade += (numericGrade * credits);
                                totalCredits += credits;
                            }
                        } else if (g.grade_point !== undefined && g.grade_point !== null) {
                            displayGrade = g.grade_point;
                            numericGrade = parseFloat(g.grade_point);
                            if (!isNaN(numericGrade)) {
                                totalWeightedGrade += (numericGrade * credits);
                                totalCredits += credits;
                            }
                        }
                        
                        return { ...g, displayGrade, numericGrade };
                    });

                    const avg = totalCredits > 0 ? totalWeightedGrade / totalCredits : 0;
                    document.getElementById('header_avg_gpa').innerText = totalCredits > 0 ? avg.toFixed(2) : '-';
                    
                    list.innerHTML = processedGrades.map(g => {
                        const score = g.score_total !== undefined && g.score_total !== null ? g.score_total : '0';
                        
                        return `
                            <div class="bg-white p-3 pr-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                                <div class="flex-1 flex items-center gap-3 overflow-hidden">
                                    <div class="w-8 h-8 shrink-0 bg-slate-50 flex items-center justify-center rounded-lg font-black text-blue-500 text-[9px] border border-slate-100 shadow-inner">
                                        ${g.subject_code}
                                    </div>
                                    <div class="truncate">
                                        <h4 class="text-xs font-bold text-slate-700 truncate leading-tight">${g.subject_name}</h4>
                                    </div>
                                </div>
                                <div class="w-16 text-center">
                                    <span class="bg-slate-50 px-2 py-1 rounded-lg text-xs font-black text-slate-600 border border-slate-100">${score}</span>
                                </div>
                                <div class="w-12 text-center">
                                    <div class="text-base font-black text-blue-600 leading-none">${g.displayGrade}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
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

                // Health - Robust lookup for latest non-zero measurements
                healthHistory = data.health_history || [];
                let displayWeight = 0;
                let displayHeight = 0;

                // 1. Check history first (it contains the most detailed records)
                if (healthHistory.length > 0) {
                    for (let i = healthHistory.length - 1; i >= 0; i--) {
                        const w = parseFloat(healthHistory[i].weight) || 0;
                        const h = parseFloat(healthHistory[i].height) || 0;
                        if (w > 0 && displayWeight === 0) displayWeight = w;
                        if (h > 0 && displayHeight === 0) displayHeight = h;
                        if (displayWeight > 0 && displayHeight > 0) break;
                    }
                }

                // 2. Fallback to student profile only if history didn't provide a value
                if (displayWeight === 0) displayWeight = parseFloat(student.weight) || 0;
                if (displayHeight === 0) displayHeight = parseFloat(student.height) || 0;
                
                // 3. Update UI - ensure we don't show literal "0" if we can show "-"
                document.getElementById('health_weight').textContent = displayWeight > 0 ? displayWeight : '-';
                document.getElementById('health_height').textContent = displayHeight > 0 ? displayHeight : '-';

                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error(err);
            }
        }

        function renderHealthCharts() {
            if (!healthHistory || healthHistory.length <= 1) {
                const msg = '<div class="text-center p-4"><p class="text-xs italic text-slate-400">ยังไม่มีพัฒนาการด้านการเจริญเติบโต</p><p class="text-[10px] text-slate-300 mt-1">(ต้องการข้อมูลอย่างน้อย 2 ครั้งเพื่อแสดงกราฟ)</p></div>';
                document.getElementById('height_chart').innerHTML = msg;
                document.getElementById('weight_chart').innerHTML = msg;
                return;
            }

            // Height Chart
            renderSingleChart('height_chart', healthHistory, 'height', '#3b82f6');
            // Weight Chart
            renderSingleChart('weight_chart', healthHistory, 'weight', '#f43f5e');
        }

        function renderSingleChart(containerId, data, key, color) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            const margin = {top: 20, right: 30, bottom: 35, left: 40},
                  width = container.clientWidth - margin.left - margin.right,
                  height = container.clientHeight - margin.top - margin.bottom;

            const svg = d3.select("#" + containerId)
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", `translate(${margin.left},${margin.top})`);

            // Order data by time if not already
            const chartData = data.map((d, i) => ({
                index: i,
                value: parseFloat(d[key]),
                label: d.record_number ? "ครั้งที่ " + d.record_number : "ครั้งที่ " + (i+1)
            })).filter(d => !isNaN(d.value));

            const x = d3.scaleLinear()
                .domain([0, chartData.length - 1])
                .range([0, width]);

            const minVal = d3.min(chartData, d => d.value);
            const maxVal = d3.max(chartData, d => d.value);
            const padding = (maxVal - minVal) * 0.2 || 5;

            const y = d3.scaleLinear()
                .domain([minVal - padding, maxVal + padding])
                .range([height, 0]);

            // Axes
            svg.append("g")
                .attr("transform", `translate(0,${height})`)
                .attr("class", "text-[8px] text-slate-300")
                .call(d3.axisBottom(x).ticks(Math.min(chartData.length, 5)).tickFormat(i => chartData[i] ? chartData[i].label : ''))
                .select(".domain").attr("stroke", "#e2e8f0");

            svg.append("g")
                .attr("class", "text-[8px] text-slate-300")
                .call(d3.axisLeft(y).ticks(5))
                .select(".domain").attr("stroke", "#e2e8f0");

            // Line
            const line = d3.line()
                .x(d => x(d.index))
                .y(d => y(d.value))
                .curve(d3.curveMonotoneX);

            // Gradient Area
            const area = d3.area()
                .x(d => x(d.index))
                .y1(d => y(d.value))
                .y0(height)
                .curve(d3.curveMonotoneX);

            const gradientId = "gradient-" + containerId;
            const defs = svg.append("defs");
            const gradient = defs.append("linearGradient")
                .attr("id", gradientId)
                .attr("x1", "0%").attr("y1", "0%")
                .attr("x2", "0%").attr("y2", "100%");
            gradient.append("stop").attr("offset", "0%").attr("stop-color", color).attr("stop-opacity", 0.2);
            gradient.append("stop").attr("offset", "100%").attr("stop-color", color).attr("stop-opacity", 0);

            svg.append("path")
                .datum(chartData)
                .attr("fill", "url(#" + gradientId + ")")
                .attr("d", area);

            svg.append("path")
                .datum(chartData)
                .attr("fill", "none")
                .attr("stroke", color)
                .attr("stroke-width", 2.5)
                .attr("stroke-linecap", "round")
                .attr("d", line);

            // Dots
            svg.selectAll("dot")
                .data(chartData)
                .enter().append("circle")
                .attr("cx", d => x(d.index))
                .attr("cy", d => y(d.value))
                .attr("r", 3.5)
                .attr("fill", "white")
                .attr("stroke", color)
                .attr("stroke-width", 2);

            // Labels on dots
            svg.selectAll("text.label")
                .data(chartData)
                .enter().append("text")
                .attr("x", d => x(d.index))
                .attr("y", d => y(d.value) - 10)
                .attr("text-anchor", "middle")
                .attr("class", "text-[9px] font-bold")
                .attr("fill", color)
                .text(d => d.value);
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
