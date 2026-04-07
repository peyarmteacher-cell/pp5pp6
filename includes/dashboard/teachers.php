<!-- School Admin: Manage Teachers -->
<?php if ($role === 'admin'): ?>
<div id="manage-teachers" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold mb-4">ข้อมูลคุณครูในโรงเรียน</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                        <th class="pb-3 font-medium">ตำแหน่ง</th>
                        <th class="pb-3 font-medium">งานวิชาการ</th>
                        <th class="pb-3 font-medium">สถานะ</th>
                    </tr>
                </thead>
                <tbody id="schoolTeachersTableBody"></tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: รายชื่อคุณครู (Super Admin) -->
<div id="teacherModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 id="modalSchoolName" class="text-xl font-bold text-slate-800">รายชื่อคุณครู</h3>
            <button onclick="closeModal('teacherModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="pb-3 font-medium text-slate-800">ชื่อ-นามสกุล</th>
                        <th class="pb-3 font-medium text-slate-800">ตำแหน่ง</th>
                        <th class="pb-3 font-medium text-slate-800">สถานะ</th>
                        <th class="pb-3 font-medium text-right text-slate-800">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="modalTeacherTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: มอบหมายงานสอน -->
<div id="assignSubjectsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div>
                <h3 id="assignTeacherName" class="text-xl font-bold text-slate-800">มอบหมายงานสอน</h3>
                <p class="text-xs text-slate-500 mt-1">กำหนดรายวิชาที่คุณครูรับผิดชอบ</p>
            </div>
            <button onclick="closeModal('assignSubjectsModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer p-2 hover:bg-slate-200 rounded-full transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-8">
            <!-- ส่วนที่ 1: เลือกชั้นเรียนและวิชา -->
            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                <h4 class="text-sm font-bold text-blue-800 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    มอบหมายงานสอนรายวิชา
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-blue-700 mb-1 uppercase tracking-wider">ระดับชั้น</label>
                        <select id="assign_level" onchange="onLevelChange()" class="w-full px-4 py-2 bg-white border border-blue-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                            <option value="">เลือกระดับชั้น</option>
                            <?php foreach(['ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6', 'ม.1', 'ม.2', 'ม.3'] as $l): ?>
                                <option value="<?= $l ?>"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="room_selection_container" class="hidden">
                        <label class="block text-xs font-bold text-blue-700 mb-1 uppercase tracking-wider">ห้อง</label>
                        <select id="assign_room" class="w-full px-4 py-2 bg-white border border-blue-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                            <option value="">เลือกห้อง</option>
                        </select>
                    </div>
                </div>

                <div id="subject_selection_container" class="hidden space-y-4">
                    <div class="flex justify-between items-center">
                        <h5 class="text-sm font-bold text-slate-700">เลือกรายวิชา</h5>
                        <button onclick="toggleSelectAllSubjects()" class="text-xs font-bold text-blue-600 hover:text-blue-800 cursor-pointer">เลือกทั้งหมด / ยกเลิกทั้งหมด</button>
                    </div>
                    <div id="subject_list" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 bg-white rounded-xl border border-blue-100">
                        <!-- Subjects will be loaded here -->
                    </div>
                    <div class="pt-2">
                        <button onclick="submitAssignment()" class="w-full bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 cursor-pointer">
                            บันทึกการมอบหมายงานสอน
                        </button>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 2: รายการที่มอบหมายแล้ว -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        รายวิชาที่รับผิดชอบในปัจจุบัน
                    </h4>
                    <button onclick="copyPreviousAssignments()" class="text-xs bg-amber-500 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-amber-600 transition-all shadow-sm cursor-pointer flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        คัดลอกงานสอนจากปีที่แล้ว
                    </button>
                </div>
                <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-500 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 font-medium">รหัสวิชา</th>
                                <th class="px-4 py-3 font-medium">ชื่อวิชา</th>
                                <th class="px-4 py-3 font-medium">ระดับชั้น/ห้อง</th>
                                <th class="px-4 py-3 font-medium">ชั่วโมง/หน่วยกิต</th>
                                <th class="px-4 py-3 font-medium text-right">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="teacherAssignmentsTableBody" class="text-sm">
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadSchoolTeachers() {
        const schoolId = '<?= $_SESSION['school_id'] ?>';
        const mockRole = new URLSearchParams(window.location.search).get('mock_role') || '';
        console.log('Loading teachers for school_id:', schoolId);
        try {
            const res = await fetch(`api/get_school_teachers.php?school_id=${schoolId}&mock_role=${mockRole}`);
            const teachers = await res.json();
            console.log('Teachers loaded:', teachers);
            
            if (teachers.error) {
                console.error('API Error:', teachers.error);
                alert(teachers.error);
                return;
            }

            const tbody = document.getElementById('schoolTeachersTableBody');
            if (!tbody) return;
            
            if (teachers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-slate-400">ยังไม่มีข้อมูลคุณครูในโรงเรียนนี้</td></tr>`;
                return;
            }

            tbody.innerHTML = teachers.map(t => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="py-3 font-medium text-slate-800">${t.name}</td>
                <td class="py-3 text-slate-500">${t.position}</td>
                <td class="py-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" ${t.is_academic ? 'checked' : ''} onchange="toggleAcademic(${t.id}, this.checked)">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </td>
                <td class="py-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold ${t.is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                            ${t.is_approved ? 'อนุมัติแล้ว' : 'รออนุมัติ'}
                        </span>
                        ${t.is_approved ? `
                            <button onclick="openAssignSubjectsModal(${t.id}, '${t.name}')" class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                มอบหมายงานสอน
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
        } catch (e) {
            console.error('Error in loadSchoolTeachers:', e);
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูลคุณครู');
        }
    }

    async function toggleAcademic(userId, isAcademic) {
        const res = await fetch('api/admin/set_academic_role.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, is_academic: isAcademic })
        });
        const result = await res.json();
        if (!result.message) {
            alert(result.error);
            loadSchoolTeachers(); // Revert UI
        }
    }

    let currentAssignTeacherId = null;
    let currentAcademicYear = '2567';
    let currentSemester = 1;

    async function openAssignSubjectsModal(teacherId, teacherName) {
        currentAssignTeacherId = teacherId;
        const nameEl = document.getElementById('assignTeacherName');
        if (nameEl) nameEl.innerText = `มอบหมายงานสอน - ${teacherName}`;
        
        // Reset form
        document.getElementById('assign_level').value = '';
        document.getElementById('room_selection_container').classList.add('hidden');
        document.getElementById('subject_selection_container').classList.add('hidden');
        
        openModal('assignSubjectsModal');
        loadTeacherAssignments(teacherId);
    }

    async function onLevelChange() {
        const level = document.getElementById('assign_level').value;
        if (!level) {
            document.getElementById('room_selection_container').classList.add('hidden');
            document.getElementById('subject_selection_container').classList.add('hidden');
            return;
        }

        // Load rooms for this level
        const resRooms = await fetch('api/academic/get_classrooms.php');
        const allClassrooms = await resRooms.json();
        const rooms = allClassrooms.filter(c => c.level === level);

        const roomContainer = document.getElementById('room_selection_container');
        const roomSelect = document.getElementById('assign_room');
        
        if (rooms.length > 1) {
            roomContainer.classList.remove('hidden');
            roomSelect.innerHTML = '<option value="">เลือกห้อง</option>' + 
                rooms.map(r => `<option value="${r.id}">ห้อง ${r.room}</option>`).join('');
            roomSelect.required = true;
        } else if (rooms.length === 1) {
            roomContainer.classList.add('hidden');
            roomSelect.innerHTML = `<option value="${rooms[0].id}" selected>ห้อง ${rooms[0].room}</option>`;
            roomSelect.required = false;
        } else {
            roomContainer.classList.add('hidden');
            roomSelect.innerHTML = '<option value="">ไม่มีข้อมูลห้อง</option>';
            roomSelect.required = false;
        }

        // Load subjects for this level
        const resSubjects = await fetch('api/academic/get_subjects.php');
        const allSubjects = await resSubjects.json();
        const subjects = allSubjects.filter(s => s.level === level);

        const subjectContainer = document.getElementById('subject_selection_container');
        const subjectList = document.getElementById('subject_list');
        
        if (subjects.length > 0) {
            subjectContainer.classList.remove('hidden');
            subjectList.innerHTML = subjects.map(s => `
                <label class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-lg cursor-pointer transition-all">
                    <input type="checkbox" name="assign_subjects" value="${s.id}" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700">${s.code}</span>
                        <span class="text-xs text-slate-500">${s.name}</span>
                    </div>
                </label>
            `).join('');
        } else {
            subjectContainer.classList.add('hidden');
            alert('ไม่พบรายวิชาในระดับชั้นนี้ กรุณาเพิ่มรายวิชาก่อน');
        }
    }

    function toggleSelectAllSubjects() {
        const checkboxes = document.getElementsByName('assign_subjects');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
    }

    async function submitAssignment() {
        const level = document.getElementById('assign_level').value;
        const roomId = document.getElementById('assign_room').value;
        const checkboxes = document.getElementsByName('assign_subjects');
        const selectedSubjectIds = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (selectedSubjectIds.length === 0) {
            alert('กรุณาเลือกอย่างน้อย 1 รายวิชา');
            return;
        }

        const res = await fetch('api/admin/assign_subjects.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                teacher_id: currentAssignTeacherId,
                type: 'individual',
                subject_ids: selectedSubjectIds,
                classroom_id: roomId || null,
                academic_year: currentAcademicYear,
                semester: currentSemester
            })
        });

        const result = await res.json();
        if (result.message) {
            alert(result.message);
            loadTeacherAssignments(currentAssignTeacherId);
            // Reset selection
            checkboxes.forEach(cb => cb.checked = false);
        } else {
            alert(result.error);
        }
    }

    async function loadTeacherAssignments(teacherId) {
        const tbody = document.getElementById('teacherAssignmentsTableBody');
        if (!tbody) return;
        
        const res = await fetch(`api/admin/get_teacher_assignments.php?teacher_id=${teacherId}&academic_year=${currentAcademicYear}&semester=${currentSemester}`);
        const assignments = await res.json();
        
        if (assignments.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-400">ยังไม่มีงานสอนที่มอบหมาย</td></tr>`;
        } else {
            tbody.innerHTML = assignments.map(a => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="px-4 py-3 font-mono text-slate-600">${a.code}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">${a.name}</td>
                    <td class="px-4 py-3 text-slate-500">${a.level}${a.room ? ' / ห้อง ' + a.room : ''}</td>
                    <td class="px-4 py-3 text-slate-500">${a.hours} ชม. / ${a.credits} นก.</td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="removeAssignment(${a.assignment_id}, ${teacherId})" class="text-red-600 hover:text-red-800 font-bold cursor-pointer">ยกเลิก</button>
                    </td>
                </tr>
            `).join('');
        }
    }

    async function removeAssignment(assignmentId, teacherId) {
        if (!confirm('ยืนยันการยกเลิกงานสอนนี้?')) return;
        const res = await fetch('api/admin/remove_assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignment_id: assignmentId })
        });
        const result = await res.json();
        if (result.message) {
            alert(result.message);
            loadTeacherAssignments(teacherId);
        } else {
            alert(result.error);
        }
    }

    async function copyPreviousAssignments() {
        if (!currentAssignTeacherId) return;
        if (!confirm(`ยืนยันการคัดลอกงานสอนจากปีการศึกษา ${parseInt(currentAcademicYear)-1} ภาคเรียนที่ ${currentSemester} มายังปีปัจจุบัน?`)) return;

        try {
            const res = await fetch('api/admin/copy_assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_id: currentAssignTeacherId,
                    target_year: currentAcademicYear,
                    target_semester: currentSemester
                })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadTeacherAssignments(currentAssignTeacherId);
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error copying assignments:', e);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
    }
</script>
