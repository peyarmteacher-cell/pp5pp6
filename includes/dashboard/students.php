<!-- Academic/Admin: Manage Students -->
<?php if ($role === 'admin' || (isset($_SESSION['is_academic']) && $_SESSION['is_academic'])): ?>
<div id="manage-students" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold mb-4">เพิ่มนักเรียนใหม่</h3>
        <form id="addStudentForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="text" id="std_code" placeholder="รหัสประจำตัวนักเรียน" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
            <input type="text" id="std_national_id" placeholder="เลขบัตรประชาชน (13 หลัก)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
            <input type="text" id="std_name" placeholder="ชื่อ-นามสกุล" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
            <select id="std_level" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                <option value="">เลือกระดับชั้น</option>
                <option value="ป.1">ป.1</option><option value="ป.2">ป.2</option><option value="ป.3">ป.3</option>
                <option value="ป.4">ป.4</option><option value="ป.5">ป.5</option><option value="ป.6">ป.6</option>
                <option value="ม.1">ม.1</option><option value="ม.2">ม.2</option><option value="ม.3">ม.3</option>
            </select>
            <input type="text" id="std_room" placeholder="ห้อง (เช่น 1)" required class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all">บันทึก</button>
        </form>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">รายชื่อนักเรียน</h3>
            <div class="flex gap-2">
                <button onclick="downloadStudentTemplate()" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-200 cursor-pointer transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    ดาวน์โหลด Template
                </button>
                <input type="file" id="importExcel" accept=".xlsx, .xls" class="hidden" onchange="handleExcelImport(event)">
                <button onclick="document.getElementById('importExcel').click()" class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-green-700 cursor-pointer transition-all">นำเข้าจาก Excel</button>
                <button onclick="promoteStudents()" class="bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-amber-700 cursor-pointer transition-all">เลื่อนระดับชั้น</button>
            </div>
        </div>
        
        <!-- Student Filters -->
        <div id="studentFilters" class="mb-6 space-y-4 border-b border-slate-100 pb-6">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">เลือกระดับชั้น</p>
                <div id="studentLevelButtons" class="flex flex-wrap gap-2"></div>
            </div>
            <div id="studentRoomContainer" class="hidden">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">เลือกห้องเรียน</p>
                <div id="studentRoomButtons" class="flex flex-wrap gap-2"></div>
            </div>
        </div>

        <div id="studentsContainer" class="space-y-8">
            <!-- จะถูกเติมด้วย JavaScript แยกตามห้องเรียน -->
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-slate-800">แก้ไขข้อมูลนักเรียน</h3>
        <form id="editStudentForm" class="space-y-4">
            <input type="hidden" id="edit_std_id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                <input type="text" id="edit_std_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ระดับชั้น</label>
                    <select id="edit_std_level" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <option value="ป.1">ป.1</option><option value="ป.2">ป.2</option><option value="ป.3">ป.3</option>
                        <option value="ป.4">ป.4</option><option value="ป.5">ป.5</option><option value="ป.6">ป.6</option>
                        <option value="ม.1">ม.1</option><option value="ม.2">ม.2</option><option value="ม.3">ม.3</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ห้อง</label>
                    <input type="text" id="edit_std_room" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('editStudentModal')" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer transition-all">ยกเลิก</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 cursor-pointer transition-all shadow-lg shadow-blue-600/20">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Student Preview Modal -->
<div id="importPreviewModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-slate-800">ตรวจสอบข้อมูลก่อนนำเข้า</h3>
                <p id="importSummaryText" class="text-sm text-slate-500 mt-1"></p>
            </div>
            <button onclick="closeModal('importPreviewModal')" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="pb-3 font-medium">รหัส</th>
                        <th class="pb-3 font-medium">เลขบัตรฯ</th>
                        <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                        <th class="pb-3 font-medium">ระดับชั้น</th>
                        <th class="pb-3 font-medium">ห้อง</th>
                    </tr>
                </thead>
                <tbody id="importPreviewTableBody"></tbody>
            </table>
        </div>
        <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
            <button onclick="closeModal('importPreviewModal')" class="px-6 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer">ยกเลิก</button>
            <button id="confirmImportBtn" onclick="confirmStudentImport()" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">ยืนยันการนำเข้า</button>
        </div>
    </div>
</div>

<script>
    function downloadStudentTemplate() {
        const data = [
            ['รหัสประจำตัว', 'เลขบัตรประชาชน', 'ชื่อ-นามสกุล', 'ระดับชั้น', 'ห้อง'],
            ['67001', '1234567890123', 'เด็กชายสมชาย ใจดี', 'ป.1', '1'],
            ['67002', '9876543210987', 'เด็กหญิงสมศรี รักเรียน', 'ป.1', '2']
        ];
        const worksheet = XLSX.utils.aoa_to_sheet(data);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Template");
        XLSX.writeFile(workbook, "student_template.xlsx");
    }

    function handleExcelImport(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onerror = (err) => {
            console.error('FileReader error:', err);
            alert('ไม่สามารถอ่านไฟล์ได้');
        };
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const json = XLSX.utils.sheet_to_json(worksheet);

                studentsToImport = json.map(row => ({
                    student_code: String(row['รหัสประจำตัว'] || row['student_code'] || row['รหัส'] || ''),
                    national_id: String(row['เลขบัตรประชาชน'] || row['national_id'] || row['เลขบัตร'] || ''),
                    name: String(row['ชื่อ-นามสกุล'] || row['name'] || row['ชื่อ'] || ''),
                    level: String(row['ระดับชั้น'] || row['level'] || row['ชั้น'] || ''),
                    room: String(row['ห้อง'] || row['room'] || '1')
                })).filter(s => s.student_code && s.name && s.level);

                if (studentsToImport.length === 0) {
                    alert('ไม่พบข้อมูลนักเรียนที่ถูกต้องในไฟล์ Excel (กรุณาตรวจสอบหัวคอลัมน์)');
                    return;
                }

                renderImportPreview();
                openModal('importPreviewModal');
            } catch (err) {
                console.error('Excel processing error:', err);
                alert('เกิดข้อผิดพลาดในการประมวลผลไฟล์ Excel: ' + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
        event.target.value = ''; // Reset input
    }

    function renderImportPreview() {
        const tbody = document.getElementById('importPreviewTableBody');
        const summary = document.getElementById('importSummaryText');
        
        if (summary) {
            summary.innerText = `พบข้อมูลนักเรียนทั้งหมด ${studentsToImport.length} รายการ`;
        }

        tbody.innerHTML = studentsToImport.map(s => `
            <tr class="border-b border-slate-50">
                <td class="py-2">${s.student_code}</td>
                <td class="py-2">${s.national_id}</td>
                <td class="py-2">${s.name}</td>
                <td class="py-2">${s.level}</td>
                <td class="py-2">${s.room}</td>
            </tr>
        `).join('');
    }

    async function loadStudents() {
        try {
            const res = await fetch('api/academic/get_students.php');
            allStudents = await res.json();
            
            // Extract unique levels
            const levels = [...new Set(allStudents.map(s => s.level))].sort();
            renderStudentLevelButtons(levels);
            
            // If we have a selected level, update rooms and display
            if (selectedStudentLevel) {
                filterStudentsByLevel(selectedStudentLevel);
            } else {
                const container = document.getElementById('studentsContainer');
                if (container) container.innerHTML = '<div class="text-center py-8 text-slate-400">กรุณาเลือกระดับชั้นเพื่อดูข้อมูล</div>';
            }
        } catch (e) {
            console.error('Error in loadStudents:', e);
        }
    }

    function renderStudentLevelButtons(levels) {
        const container = document.getElementById('studentLevelButtons');
        if (!container) return;
        
        container.innerHTML = levels.map(level => `
            <button onclick="filterStudentsByLevel('${level}')" 
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer ${selectedStudentLevel === level ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">
                ${level}
            </button>
        `).join('');
    }

    function filterStudentsByLevel(level) {
        selectedStudentLevel = level;
        selectedStudentRoom = null; // Reset room when level changes
        
        // Update level buttons UI
        const levels = [...new Set(allStudents.map(s => s.level))].sort();
        renderStudentLevelButtons(levels);
        
        // Show room container
        const roomContainer = document.getElementById('studentRoomContainer');
        if (roomContainer) roomContainer.classList.remove('hidden');
        
        // Extract unique rooms for this level
        const rooms = [...new Set(allStudents.filter(s => s.level === level).map(s => s.room || '1'))].sort();
        renderStudentRoomButtons(rooms);
        
        const container = document.getElementById('studentsContainer');
        if (container) container.innerHTML = '<div class="text-center py-8 text-slate-400">กรุณาเลือกห้องเรียนเพื่อดูข้อมูล</div>';
    }

    function renderStudentRoomButtons(rooms) {
        const container = document.getElementById('studentRoomButtons');
        if (!container) return;
        
        container.innerHTML = rooms.map(room => `
            <button onclick="filterStudentsByRoom('${room}')" 
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer ${selectedStudentRoom === room ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">
                ห้อง ${room}
            </button>
        `).join('');
    }

    function filterStudentsByRoom(room) {
        selectedStudentRoom = room;
        
        // Update room buttons UI
        const rooms = [...new Set(allStudents.filter(s => s.level === selectedStudentLevel).map(s => s.room || '1'))].sort();
        renderStudentRoomButtons(rooms);
        
        const container = document.getElementById('studentsContainer');
        if (!container) return;
        
        const filtered = allStudents.filter(s => s.level === selectedStudentLevel && (s.room || '1') === room);
        
        if (filtered.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-slate-400">ไม่พบข้อมูลนักเรียนในห้องนี้</div>';
            return;
        }

        container.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-1 bg-blue-600 rounded-full"></div>
                        <h4 class="font-bold text-slate-800">ชั้น${selectedStudentLevel} ห้อง ${room} (${filtered.length} คน)</h4>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-100">
                                <th class="pb-3 font-medium">รหัส</th>
                                <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                                <th class="pb-3 font-medium text-right">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filtered.map(s => `
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                    <td class="py-3 text-slate-600 font-mono">${s.student_code}</td>
                                    <td class="py-3 font-medium text-slate-800">${s.name}</td>
                                    <td class="py-3 text-right flex gap-2 justify-end">
                                        <button onclick='editStudent(${JSON.stringify(s)})' class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer">แก้ไข</button>
                                        <button onclick="deleteStudent(${s.id})" class="text-red-600 hover:text-red-800 text-xs font-bold cursor-pointer">ลบ</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async function confirmStudentImport() {
        const confirmBtn = document.getElementById('confirmImportBtn');
        if (!confirmBtn) return;
        
        if (!studentsToImport || studentsToImport.length === 0) {
            alert('ไม่พบข้อมูลที่จะนำเข้า กรุณาเลือกไฟล์ใหม่อีกครั้ง');
            return;
        }

        confirmBtn.disabled = true;
        confirmBtn.innerText = 'กำลังนำเข้า...';

        try {
            const res = await fetch('api/academic/import_students.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ students: studentsToImport })
            });
            
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                closeModal('importPreviewModal');
                loadStudents();
            } else {
                alert(result.error || 'เกิดข้อผิดพลาดในการนำเข้า');
            }
        } catch (e) {
            console.error('Error in import:', e);
            alert('เกิดข้อผิดพลาดในการนำเข้าข้อมูล');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerText = 'ยืนยันการนำเข้า';
        }
    }

    async function promoteStudents() {
        if (!confirm('ยืนยันการเลื่อนระดับชั้นนักเรียนทั้งหมด? (ป.1 -> ป.2, ป.6 -> จบการศึกษา)')) return;
        const res = await fetch('api/academic/promote_students.php', { method: 'POST' });
        const result = await res.json();
        alert(result.message || result.error);
        loadStudents();
    }

    async function deleteStudent(id) {
        if (!confirm('ยืนยันการลบข้อมูลนักเรียน?')) return;
        const res = await fetch('api/academic/delete_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.message) {
            alert(result.message);
            loadStudents();
        } else {
            alert(result.error);
        }
    }

    function editStudent(s) {
        document.getElementById('edit_std_id').value = s.id;
        document.getElementById('edit_std_name').value = s.name;
        document.getElementById('edit_std_level').value = s.level;
        document.getElementById('edit_std_room').value = s.room || '1';
        openModal('editStudentModal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const addStudentForm = document.getElementById('addStudentForm');
        if (addStudentForm) {
            addStudentForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/academic/add_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_code: document.getElementById('std_code').value,
                        national_id: document.getElementById('std_national_id').value,
                        name: document.getElementById('std_name').value,
                        level: document.getElementById('std_level').value,
                        room: document.getElementById('std_room').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    addStudentForm.reset();
                    loadStudents();
                } else {
                    alert(result.error);
                }
            };
        }

        const editStudentForm = document.getElementById('editStudentForm');
        if (editStudentForm) {
            editStudentForm.onsubmit = async (e) => {
                e.preventDefault();
                const res = await fetch('api/academic/update_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: document.getElementById('edit_std_id').value,
                        name: document.getElementById('edit_std_name').value,
                        level: document.getElementById('edit_std_level').value,
                        room: document.getElementById('edit_std_room').value
                    })
                });
                const result = await res.json();
                if (result.message) {
                    alert(result.message);
                    closeModal('editStudentModal');
                    loadStudents();
                } else {
                    alert(result.error);
                }
            };
        }
    });
</script>
