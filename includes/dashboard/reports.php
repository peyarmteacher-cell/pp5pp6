<?php
// Reports Section
?>
<div id="reports" class="section hidden space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- ปพ.5 Report Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition-all">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                    <i data-lucide="file-text"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">พิมพ์เอกสาร ปพ.5</h3>
                    <p class="text-xs text-slate-500">แบบบันทึกผลการพัฒนาคุณภาพผู้เรียน</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500">ปีการศึกษา</label>
                        <select id="report_p5_year" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-green-500/20">
                            <!-- Populated by JS -->
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500">ภาคเรียน</label>
                        <select id="report_p5_semester" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-green-500/20">
                            <option value="1">ภาคเรียนที่ 1</option>
                            <option value="2">ภาคเรียนที่ 2</option>
                            <option value="annual">รวมทั้งปีการศึกษา</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500">ประเภทรายงาน</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="setP5Type('subject')" id="btn_p5_subject" class="p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-green-600 text-white shadow-lg shadow-green-600/20">รายวิชา</button>
                        <button onclick="setP5Type('class')" id="btn_p5_class" class="p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">รายชั้นเรียน</button>
                    </div>
                </div>

                <div id="p5_subject_select" class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500">เลือกวิชาที่สอน</label>
                    <select id="report_p5_assignment" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-green-500/20">
                        <!-- Populated by JS -->
                    </select>
                </div>

                <div id="p5_class_select" class="space-y-1 hidden">
                    <label class="text-xs font-semibold text-slate-500">เลือกห้องเรียน</label>
                    <select id="report_p5_classroom" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-green-500/20">
                        <!-- Populated by JS -->
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500">วันที่อนุมัติผลการเรียน</label>
                    <input type="date" id="report_p5_approval_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-green-500/20">
                </div>

                <div class="pt-4 grid grid-cols-2 gap-2">
                    <button onclick="printP5()" class="bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        พิมพ์ ปพ.5
                    </button>
                    <button onclick="printP5Cover()" class="bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 shadow-lg shadow-slate-800/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        พิมพ์ปก
                    </button>
                </div>
            </div>
        </div>

        <!-- ปพ.6 Report Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition-all">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="user-check"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">พิมพ์เอกสาร ปพ.6</h3>
                    <p class="text-xs text-slate-500">แบบรายงานประจำตัวนักเรียนรายบุคคล</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500">ปีการศึกษา</label>
                        <select id="report_p6_year" onchange="loadP6Students()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                            <!-- Populated by JS -->
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-500">ห้องเรียน</label>
                        <select id="report_p6_classroom" onchange="loadP6Students()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                            <!-- Populated by JS -->
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500">เลือกนักเรียน</label>
                    <select id="report_p6_student" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">พิมพ์ทุกคนในห้อง</option>
                        <!-- Populated by JS -->
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500">ภาคเรียน</label>
                    <select id="report_p6_semester" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="1">ภาคเรียนที่ 1</option>
                        <option value="2">ภาคเรียนที่ 2</option>
                        <option value="annual">รวมทั้งปีการศึกษา</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button onclick="printP6()" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        พิมพ์เอกสาร ปพ.6
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let p5Type = 'subject';

    function setP5Type(type) {
        p5Type = type;
        const btnSubject = document.getElementById('btn_p5_subject');
        const btnClass = document.getElementById('btn_p5_class');
        const subjectSelect = document.getElementById('p5_subject_select');
        const classSelect = document.getElementById('p5_class_select');

        if (type === 'subject') {
            btnSubject.className = 'p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-green-600 text-white shadow-lg shadow-green-600/20';
            btnClass.className = 'p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200';
            subjectSelect.classList.remove('hidden');
            classSelect.classList.add('hidden');
        } else {
            btnSubject.className = 'p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200';
            btnClass.className = 'p5-type-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-green-600 text-white shadow-lg shadow-green-600/20';
            subjectSelect.classList.add('hidden');
            classSelect.classList.remove('hidden');
        }
    }

    async function loadReportOptions() {
        console.log('Loading report options...');
        try {
            // Load Academic Years
            const yearRes = await fetch('api/academic/get_academic_years.php');
            const years = await yearRes.json();
            
            if (!Array.isArray(years)) {
                console.error('Years is not an array:', years);
                return;
            }

            const yearP5 = document.getElementById('report_p5_year');
            const yearP6 = document.getElementById('report_p6_year');
            
            if (!yearP5 || !yearP6) {
                console.warn('Report year dropdowns not found');
                return;
            }
            
            const yearOptions = years.map(y => `<option value="${y.year}" ${y.is_current ? 'selected' : ''}>${y.year}</option>`).join('');
            yearP5.innerHTML = yearOptions;
            yearP6.innerHTML = yearOptions;

            const currentYear = years.find(y => y.is_current)?.year || '2567';

            // Load Assignments (for P5 Subject)
            const assignRes = await fetch(`api/teacher/get_my_assignments.php?academic_year=${currentYear}&semester=1`);
            const assignments = await assignRes.json();
            
            if (Array.isArray(assignments)) {
                const assignP5 = document.getElementById('report_p5_assignment');
                if (assignP5) {
                    assignP5.innerHTML = assignments.map(a => `
                        <option value="${a.assignment_id || a.subject_id}" data-subject="${a.subject_id}" data-classroom="${a.classroom_id}">
                            ${a.subject_code} ${a.subject_name} (${a.level}/${a.room})
                        </option>
                    `).join('');
                }
            } else {
                console.error('Assignments is not an array:', assignments);
            }

            // Load Classrooms (for P5 Class and P6)
            const classRes = await fetch('api/academic/get_classrooms.php');
            const classrooms = await classRes.json();
            
            if (Array.isArray(classrooms)) {
                const classP5 = document.getElementById('report_p5_classroom');
                const classP6 = document.getElementById('report_p6_classroom');
                
                const classOptions = classrooms.map(c => `<option value="${c.id}">${c.level}/${c.room}</option>`).join('');
                if (classP5) classP5.innerHTML = classOptions;
                if (classP6) classP6.innerHTML = classOptions;
            } else {
                console.error('Classrooms is not an array:', classrooms);
            }

            loadP6Students();
        } catch (e) {
            console.error('Error loading report options:', e);
        }
    }

    async function loadP6Students() {
        const classroomId = document.getElementById('report_p6_classroom').value;
        const year = document.getElementById('report_p6_year').value;
        if (!classroomId) return;

        try {
            const res = await fetch(`api/academic/get_students.php?academic_year=${year}&status=studying`);
            const allStudents = await res.json();
            const filtered = allStudents.filter(s => s.classroom_id == classroomId);
            
            const studentSelect = document.getElementById('report_p6_student');
            studentSelect.innerHTML = '<option value="all">พิมพ์ทุกคนในห้อง</option>' + 
                filtered.map(s => `<option value="${s.id}">${s.prefix}${s.name} ${s.last_name}</option>`).join('');
        } catch (e) {
            console.error('Error loading P6 students:', e);
        }
    }

    function printP5() {
        console.log('Printing P5...');
        const year = document.getElementById('report_p5_year').value;
        const semester = document.getElementById('report_p5_semester').value;
        const approvalDate = document.getElementById('report_p5_approval_date').value;
        let url = `reports/p5_report.php?year=${year}&semester=${semester}&type=${p5Type}&approval_date=${approvalDate}`;

        if (p5Type === 'subject') {
            const assignId = document.getElementById('report_p5_assignment').value;
            if (!assignId) { alert('กรุณาเลือกวิชา'); return; }
            url += `&assignment_id=${assignId}`;
        } else {
            const classroomId = document.getElementById('report_p5_classroom').value;
            if (!classroomId) { alert('กรุณาเลือกห้องเรียน'); return; }
            url += `&classroom_id=${classroomId}`;
        }

        window.open(url, '_blank');
    }

    function printP5Cover() {
        console.log('Printing P5 Cover...');
        const year = document.getElementById('report_p5_year').value;
        const semester = document.getElementById('report_p5_semester').value;
        const approvalDate = document.getElementById('report_p5_approval_date').value;
        let url = `reports/p5_cover.php?year=${year}&semester=${semester}&type=${p5Type}&approval_date=${approvalDate}`;

        if (p5Type === 'subject') {
            const assignId = document.getElementById('report_p5_assignment').value;
            if (!assignId) { alert('กรุณาเลือกวิชา'); return; }
            url += `&assignment_id=${assignId}`;
        } else {
            const classroomId = document.getElementById('report_p5_classroom').value;
            if (!classroomId) { alert('กรุณาเลือกห้องเรียน'); return; }
            url += `&classroom_id=${classroomId}`;
        }

        window.open(url, '_blank');
    }

    function printP6() {
        console.log('Printing P6...');
        const year = document.getElementById('report_p6_year').value;
        const semester = document.getElementById('report_p6_semester').value;
        const classroomId = document.getElementById('report_p6_classroom').value;
        const studentId = document.getElementById('report_p6_student').value;

        if (!classroomId) { alert('กรุณาเลือกห้องเรียน'); return; }

        let url = `reports/p6_report.php?year=${year}&semester=${semester}&classroom_id=${classroomId}&student_id=${studentId}`;
        window.open(url, '_blank');
    }
</script>
