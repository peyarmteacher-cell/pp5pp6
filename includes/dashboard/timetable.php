<?php 
$is_admin_or_academic = ($_SESSION['role'] === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'] == 1)); 
?>
<!-- Timetable Management Section -->
<div id="manage-timetable" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800">จัดการตารางสอน</h3>
                <p class="text-sm text-slate-500">กำหนดวันและเวลาเรียนสำหรับแต่ละรายวิชา</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <?php if ($is_admin_or_academic): ?>
                    <select id="time_teacher_select" onchange="loadTimetable()" class="px-4 py-2 bg-blue-50 border border-blue-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-bold text-blue-700 cursor-pointer">
                        <option value="">-- เลือกครูผู้สอน --</option>
                    </select>
                <?php endif; ?>
                <select id="time_academic_year" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-bold text-slate-700 cursor-pointer">
                    <!-- Academic years will be loaded here -->
                </select>
                <select id="time_semester" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-bold text-slate-700 cursor-pointer">
                    <option value="1">ภาคเรียนที่ 1</option>
                    <option value="2">ภาคเรียนที่ 2</option>
                </select>
                <button onclick="printTeacherTimetable()" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-bold hover:bg-green-700 transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    พิมพ์ตารางสอน
                </button>
                <button onclick="clearMyTimetable()" class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 rounded-xl text-sm font-bold hover:bg-red-100 transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    ล้างตารางสอนทั้งหมด
                </button>
            </div>
        </div>

        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 mb-6">
            <div class="flex items-center gap-4">
                <div class="bg-blue-600 p-2 rounded-lg text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div>
                    <h4 class="font-bold text-blue-900 text-sm">คำแนะนำ</h4>
                    <p class="text-[10px] text-blue-600">คลิกที่ช่องว่างในตารางเพื่อกำหนดวิชาที่สอนในคาบนั้นๆ</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="p-3 border border-slate-200 text-slate-500 font-bold text-xs w-24">วัน / คาบ</th>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <th class="p-3 border border-slate-200 text-slate-500 font-bold text-xs">คาบที่ <?= $i ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody id="timetable-body">
                    <!-- Timetable rows will be generated here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Subject Modal -->
<div id="assignSubjectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-slate-800">กำหนดวิชาเรียน</h3>
            <button onclick="closeModal('assignSubjectModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <div class="space-y-4">
            <p id="assign-slot-info" class="text-sm font-bold text-blue-600 bg-blue-50 p-3 rounded-xl"></p>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">เลือกวิชาและห้องเรียน</label>
                <select id="assign_subject_classroom" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all cursor-pointer">
                    <option value="">-- ว่าง / ลบข้อมูล --</option>
                    <option value="LD:lunch" class="font-bold text-orange-600">🍴 พักรับประทานอาหาร</option>
                    <option value="LD:scouts" class="font-bold text-green-600">⚜️ กิจกรรมลูกเสือ-เนตรนารี</option>
                    <option value="LD:club" class="font-bold text-purple-600">🤝 กิจกรรมชุมนุม</option>
                    <option value="LD:guidance" class="font-bold text-blue-600">🧭 กิจกรรมแนะแนว</option>
                    <option value="LD:homeroom" class="font-bold text-blue-600">🏠 โฮมรูม (Home Room)</option>
                    <!-- Assignments will be loaded here -->
                </select>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button onclick="closeModal('assignSubjectModal')" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-semibold hover:bg-slate-50 transition-all cursor-pointer">ยกเลิก</button>
                <button onclick="saveTimetableSlot()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentTimetable = [];
    let myAssignments = [];
    let activeSlot = null;

    const days = [
        { id: 1, name: 'จันทร์', class: 'text-yellow-600 bg-yellow-50' },
        { id: 2, name: 'อังคาร', class: 'text-pink-600 bg-pink-50' },
        { id: 3, name: 'พุธ', class: 'text-green-600 bg-green-50' },
        { id: 4, name: 'พฤหัสบดี', class: 'text-orange-600 bg-orange-50' },
        { id: 5, name: 'ศุกร์', class: 'text-blue-600 bg-blue-50' }
    ];

    async function loadTimetable() {
        const yearEl = document.getElementById('time_academic_year');
        const semesterEl = document.getElementById('time_semester');
        if (!yearEl || !semesterEl) return;

        const year = yearEl.value || '2567';
        const semester = semesterEl.value || 1;

        const teacherSelect = document.getElementById('time_teacher_select');
        const teacher_id = teacherSelect ? teacherSelect.value : '<?= $_SESSION['user_id'] ?>';
        const teacherParam = teacher_id ? `&teacher_id=${teacher_id}` : '';
        
        try {
            // Load timetable data
            const res = await fetch(`api/teacher/get_timetable.php?academic_year=${year}&semester=${semester}${teacherParam}`);
            currentTimetable = await res.json();
            
            // Load selected teacher's assignments to populate modal
            const resAss = await fetch(`api/teacher/get_my_assignments.php?academic_year=${year}&semester=${semester}${teacherParam}`);
            if (resAss.ok) {
                myAssignments = await resAss.json();
            } else {
                myAssignments = [];
            }
            
            renderTimetable();
        } catch (e) {
            console.error('Error loading timetable:', e);
        }
    }

    function printTeacherTimetable() {
        const year = document.getElementById('time_academic_year').value;
        const semester = document.getElementById('time_semester').value;
        const teacherSelect = document.getElementById('time_teacher_select');
        const teacher_id = teacherSelect ? teacherSelect.value : '<?= $_SESSION['user_id'] ?>';
        window.open(`api/teacher/print_timetable.php?academic_year=${year}&semester=${semester}&target_type=teacher&target_id=${teacher_id}`, '_blank');
    }

    function renderTimetable() {
        const tbody = document.getElementById('timetable-body');
        if (!tbody) return;

        const getSubjectColor = (slot) => {
            if (!slot) return 'bg-white border-slate-200';
            if (slot.activity_type) {
                const actKey = slot.activity_type.toLowerCase();
                const actColors = {
                    'lunch': 'bg-orange-50 text-orange-700 border-orange-100',
                    'scouts': 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    'scout': 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    'club': 'bg-purple-50 text-purple-700 border-purple-100',
                    'homeroom': 'bg-indigo-50 text-indigo-700 border-indigo-100',
                    'guidance': 'bg-sky-50 text-sky-700 border-sky-100'
                };
                return actColors[actKey] || 'bg-slate-50 text-slate-700 border-slate-100';
            }
            
            const palette = [
                'bg-blue-50 text-blue-700 border-blue-100',
                'bg-indigo-50 text-indigo-700 border-indigo-100',
                'bg-cyan-50 text-cyan-700 border-cyan-100',
                'bg-teal-50 text-teal-700 border-teal-100',
                'bg-emerald-50 text-emerald-700 border-emerald-100',
                'bg-violet-50 text-violet-700 border-violet-100',
                'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-100',
                'bg-pink-50 text-pink-700 border-pink-100',
                'bg-rose-50 text-rose-700 border-rose-100',
                'bg-amber-50 text-amber-700 border-amber-100',
                'bg-orange-50 text-orange-700 border-orange-100',
                'bg-lime-50 text-lime-700 border-lime-100',
                'bg-sky-50 text-sky-700 border-sky-100',
                'bg-yellow-50 text-yellow-700 border-yellow-100',
                'bg-slate-100 text-slate-700 border-slate-200'
            ];
            
            // Use hash of subject code for consistent color mapping
            const code = slot.subject_code || '';
            let hash = 0;
            for (let i = 0; i < code.length; i++) {
                hash = ((hash << 5) - hash) + code.charCodeAt(i);
                hash |= 0;
            }
            const index = Math.abs(hash) % palette.length;
            return palette[index];
        };

        tbody.innerHTML = days.map(day => `
            <tr>
                <td class="p-3 border border-slate-200 font-bold text-xs text-center ${day.class}">${day.name}</td>
                ${[1, 2, 3, 4, 5, 6, 7, 8].map(period => {
                    const slot = currentTimetable.find(t => t.day_of_week == day.id && t.period_number == period);
                    const colorClass = getSubjectColor(slot);
                    
                    let displayCode = slot ? (slot.subject_code || '') : '';
                    let displayName = slot ? (slot.subject_name || '') : '';
                    const isActivity = slot && !!slot.activity_type;

                    // Fallback mapping if API provides empty strings
                    if (isActivity && (!displayCode || !displayName)) {
                        const actMap = {
                            'scouts': { code: 'ลูกเสือเนตรนารี', name: 'กิจกรรมลูกเสือเนตรนารี' },
                            'scout': { code: 'ลูกเสือเนตรนารี', name: 'กิจกรรมลูกเสือเนตรนารี' },
                            'club': { code: 'ชุมนุม', name: 'กิจกรรมชุมนุม' },
                            'homeroom': { code: 'โฮมรูม', name: 'Home Room' },
                            'lunch': { code: 'พักกลางวัน', name: 'พักรับประทานอาหาร' },
                            'guidance': { code: 'แนะแนว', name: 'กิจกรรมแนะแนว' }
                        };
                        const actKey = slot.activity_type.toLowerCase();
                        if (actMap[actKey]) {
                            displayCode = displayCode || actMap[actKey].code;
                            displayName = displayName || actMap[actKey].name;
                        }
                    }

                    const singleLineActs = ['scouts', 'scout', 'club', 'guidance'];
                    const isSingleLine = isActivity && singleLineActs.includes(slot.activity_type.toLowerCase());
                    
                    return `
                        <td class="p-2 border transition-all text-center relative group min-h-[85px] ${colorClass}">
                            <div onclick="openAssignModal(${day.id}, ${period}, '${day.name}')" class="cursor-pointer hover:opacity-80 transition-all h-full w-full min-h-[65px] flex flex-col justify-center gap-0.5">
                                ${slot ? (isSingleLine ? `
                                    <div class="text-[11px] font-bold leading-none">${displayCode}</div>
                                ` : `
                                    <div class="text-[10px] font-bold leading-none">${displayCode || 'กิจกรรม'}</div>
                                    <div class="text-[9px] opacity-80 truncate leading-none min-h-[12px]">${displayName || ''}</div>
                                    <div class="text-[9px] font-bold opacity-60 leading-none min-h-[12px]">${(!isActivity && slot.level) ? `${slot.level}/${slot.room}` : '&nbsp;'}</div>
                                `) : '<span class="text-[10px] text-slate-300 italic">ว่าง</span>'}
                            </div>
                        </td>
                    `;
                }).join('')}
            </tr>
        `).join('');
    }

    function openAssignModal(dayId, period, dayName) {
        activeSlot = { dayId, period };
        document.getElementById('assign-slot-info').innerText = `วัน${dayName} คาบที่ ${period}`;
        
        const select = document.getElementById('assign_subject_classroom');
        select.innerHTML = '<option value="">-- ว่าง / ลบข้อมูล --</option>' + 
            '<option value="LD:lunch" class="font-bold text-orange-600">🍴 พักรับประทานอาหาร</option>' +
            '<option value="LD:scouts" class="font-bold text-green-600">⚜️ กิจกรรมลูกเสือเนตรนารี</option>' +
            '<option value="LD:club" class="font-bold text-purple-600">🤝 กิจกรรมชุมนุม</option>' +
            '<option value="LD:guidance" class="font-bold text-blue-600">🧭 กิจกรรมแนะแนว</option>' +
            '<option value="LD:homeroom" class="font-bold text-blue-600">🏠 โฮมรูม (Home Room)</option>' +
            myAssignments.map(a => `<option value="${a.subject_id}|${a.classroom_id}">${a.subject_code} - ${a.subject_name} (${a.level}/${a.room})</option>`).join('');
        
        // Find current value if exists
        const current = currentTimetable.find(t => t.day_of_week == dayId && t.period_number == period);
        if (current) {
            if (current.activity_type) {
                select.value = 'LD:' + current.activity_type;
            } else {
                select.value = `${current.subject_id}|${current.classroom_id}`;
            }
        } else {
            select.value = "";
        }
        
        openModal('assignSubjectModal');
    }

    async function saveTimetableSlot() {
        if (!activeSlot) return;
        
        const val = document.getElementById('assign_subject_classroom').value;
        let subject_id = null;
        let classroom_id = null;
        
        if (val) {
            if (val.startsWith('LD:')) {
                subject_id = val;
                // สำหรับกิจกรรมพิเศษ ลองหาห้องเรียนที่คุณครูสอนอยู่สักห้อง
                if (myAssignments.length > 0) {
                    classroom_id = myAssignments[0].classroom_id;
                } else {
                    alert('กรุณาติดต่อเจ้าหน้าที่วิชาการเพื่อกำหนดห้องเรียนให้คุณครูอย่างน้อย 1 ห้องก่อนครับ');
                    return;
                }
            } else {
                const parts = val.split('|');
                subject_id = parts[0];
                classroom_id = parts[1];
            }
        }

        const currentSlot = currentTimetable.find(t => t.day_of_week == activeSlot.dayId && t.period_number == activeSlot.period);
        const teacherSelect = document.getElementById('time_teacher_select');
        const teacher_id = teacherSelect ? teacherSelect.value : '<?= $_SESSION['user_id'] ?>';

        const payload = {
            academic_year: document.getElementById('time_academic_year').value,
            semester: document.getElementById('time_semester').value,
            day_of_week: activeSlot.dayId,
            period_number: activeSlot.period,
            subject_id: subject_id,
            classroom_id: classroom_id || (currentSlot ? currentSlot.classroom_id : null),
            teacher_id: teacher_id
        };

        try {
            const res = await fetch('api/teacher/save_timetable.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.message) {
                closeModal('assignSubjectModal');
                loadTimetable();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error saving timetable slot:', e);
        }
    }

    async function deleteTimetableSlot(id) {
        if (!confirm('ยืนยันการลบคาบสอนนี้?')) return;
        
        try {
            const res = await fetch(`api/teacher/delete_timetable.php?id=${id}`, { method: 'DELETE' });
            const result = await res.json();
            if (result.message) {
                loadTimetable();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error deleting timetable slot:', e);
        }
    }

    async function clearMyTimetable() {
        const year = document.getElementById('time_academic_year').value;
        const semester = document.getElementById('time_semester').value;
        const teacherSelect = document.getElementById('time_teacher_select');
        const teacher_id = teacherSelect ? teacherSelect.value : '<?= $_SESSION['user_id'] ?>';
        
        if (!confirm(`!!! คำเตือน !!!\nคุณต้องการลบข้อมูลตารางสอนทั้งหมดของภาคเรียนที่ ${semester} ปีการศึกษา ${year} ใช่หรือไม่?\nการดำเนินการนี้ไม่สามารถย้อนกลับได้`)) return;
        
        const pass = prompt('กรุณาพิมพ์คำว่า "CONFIRM" เพื่อยืนยัน:');
        if (pass !== 'CONFIRM') return;

        try {
            const res = await fetch(`api/teacher/clear_timetable.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ academic_year: year, semester: semester, teacher_id: teacher_id })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadTimetable();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error clearing timetable:', e);
        }
    }

    async function initTimetableSection() {
        try {
            // Load teachers if admin or academic
            const is_admin_or_academic = <?= json_encode($is_admin_or_academic) ?>;
            if (is_admin_or_academic) {
                const schoolId = '<?= $_SESSION['school_id'] ?? '' ?>';
                const resTeachers = await fetch(`api/get_school_teachers.php?school_id=${schoolId}`);
                if (resTeachers.ok) {
                    const teachers = await resTeachers.json();
                    const selectTeacher = document.getElementById('time_teacher_select');
                    if (selectTeacher && Array.isArray(teachers)) {
                        selectTeacher.innerHTML = teachers.map(t => 
                            `<option value="${t.id}" ${t.id == '<?= $_SESSION['user_id'] ?>' ? 'selected' : ''}>${t.name} (${t.position || 'คุณครู'})</option>`
                        ).join('');
                    }
                }
            }

            const res = await fetch('api/academic/get_academic_years.php');
            const years = await res.json();
            const el = document.getElementById('time_academic_year');
            if (el) {
                el.innerHTML = years.map(y => `<option value="${y.year}" ${Number(y.is_current) === 1 ? 'selected' : ''}>ปีการศึกษา ${y.year}</option>`).join('');
                const current = years.find(y => Number(y.is_current) === 1);
                if (current) el.value = current.year;
            }
            // Trigger load timetable after setting the default academic year
            await loadTimetable();
        } catch (e) {
            console.error('Error initializing timetable section:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const yearEl = document.getElementById('time_academic_year');
        if (yearEl) yearEl.addEventListener('change', loadTimetable);
        const semEl = document.getElementById('time_semester');
        if (semEl) semEl.addEventListener('change', loadTimetable);
        
        // Initializing timetable section which will call loadTimetable
        initTimetableSection();
    });
</script>
