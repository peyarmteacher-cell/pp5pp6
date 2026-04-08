<div id="record-behavior" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800">บันทึกพฤติกรรมนักเรียน</h3>
                <p class="text-sm text-slate-500">เลือกห้องเรียนและวันที่เพื่อบันทึกข้อมูลพฤติกรรม</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-xl border border-slate-200">
                    <input type="date" id="behavior-date" class="bg-transparent border-none text-sm focus:ring-0" value="<?= date('Y-m-d') ?>">
                </div>
                <button onclick="saveBehavior()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg shadow-blue-900/20">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    บันทึกทั้งหมด
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500 uppercase">ปีการศึกษา</label>
                <select id="behavior-year" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <?php
                    $currentYear = date('Y') + 543;
                    for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                        echo "<option value='$i'>$i</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500 uppercase">ภาคเรียน</label>
                <select id="behavior-semester" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <option value="1">1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div class="md:col-span-2 space-y-1">
                <label class="text-xs font-semibold text-slate-500 uppercase">เลือกห้องเรียน</label>
                <div id="behavior-classroom-list" class="flex flex-wrap gap-2">
                    <!-- Classrooms will be loaded here -->
                </div>
            </div>
        </div>

        <div id="behavior-table-container" class="hidden overflow-x-auto border border-slate-200 rounded-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-bottom border-slate-200">
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200 w-12">ที่</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200 min-w-[200px]">ชื่อ-นามสกุล</th>
                        <th id="behavior-cat-headers" class="contents">
                            <!-- Category headers will be loaded here -->
                        </th>
                    </tr>
                </thead>
                <tbody id="behavior-table-body">
                    <!-- Students will be loaded here -->
                </tbody>
            </table>
        </div>

        <div id="behavior-empty-state" class="py-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="users" class="w-8 h-8 text-slate-400"></i>
            </div>
            <h4 class="text-slate-800 font-bold">ยังไม่ได้เลือกห้องเรียน</h4>
            <p class="text-slate-500 text-sm">กรุณาเลือกห้องเรียนด้านบนเพื่อเริ่มบันทึกพฤติกรรม</p>
        </div>
    </div>
</div>

<!-- Behavior Selection Modal -->
<div id="behavior-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 id="modal-cat-name" class="text-xl font-bold text-slate-800">เลือกพฤติกรรม</h3>
                <p id="modal-student-name" class="text-sm text-slate-500">นักเรียน: -</p>
            </div>
            <button onclick="closeBehaviorModal()" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                <i data-lucide="x" class="w-6 h-6 text-slate-400"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="behavior-option-search" placeholder="ค้นหาหรือเพิ่มพฤติกรรมใหม่..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
            </div>
            
            <div id="behavior-options-list" class="max-h-[300px] overflow-y-auto space-y-2 pr-2">
                <!-- Options will be loaded here -->
            </div>

            <div id="add-option-container" class="hidden pt-4 border-t border-slate-100">
                <button onclick="addNewBehaviorOption()" class="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-medium transition-all flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    เพิ่มเป็นตัวเลือกใหม่: <span id="new-option-text" class="font-bold"></span>
                </button>
            </div>
        </div>
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
            <button onclick="closeBehaviorModal()" class="px-6 py-2 text-slate-600 font-semibold hover:bg-slate-200 rounded-xl transition-all">ยกเลิก</button>
            <button onclick="confirmBehaviorSelection()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold shadow-lg shadow-blue-900/20 transition-all">ตกลง</button>
        </div>
    </div>
</div>

<script>
    let behaviorCategories = [];
    let behaviorStudents = [];
    let behaviorRecords = [];
    let currentBehaviorClassroom = null;
    let activeBehaviorCell = null; // { studentId, categoryId }
    let selectedOptionsInModal = [];

    async function initBehaviorSection() {
        try {
            // Load categories and options
            const configRes = await fetch('api/teacher/get_behavior_config.php');
            const configData = await configRes.json();
            behaviorCategories = configData.categories;

            // Load classrooms
            await loadBehaviorClassrooms();

            // Render category headers
            const headerContainer = document.getElementById('behavior-cat-headers');
            headerContainer.innerHTML = '';
            behaviorCategories.forEach(cat => {
                const th = document.createElement('th');
                th.className = 'px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-r border-slate-200 min-w-[150px]';
                th.textContent = cat.name;
                headerContainer.appendChild(th);
            });

            // Search listener
            document.getElementById('behavior-option-search').oninput = (e) => {
                const text = e.target.value.trim();
                filterBehaviorOptions(text);
            };

        } catch (e) {
            console.error('Error initializing behavior section:', e);
        }
    }

    async function loadBehaviorClassrooms() {
        const year = document.getElementById('behavior-year').value;
        const semester = document.getElementById('behavior-semester').value;
        
        try {
            const res = await fetch(`api/teacher/get_my_ld_classrooms.php?academic_year=${year}&semester=${semester}`);
            const classrooms = await res.json();
            
            const container = document.getElementById('behavior-classroom-list');
            container.innerHTML = '';
            
            if (classrooms.length === 0) {
                container.innerHTML = '<p class="text-sm text-red-500 font-bold italic">ยังไม่มีการกำหนดห้องเรียนที่รับผิดชอบ</p>';
                document.getElementById('behavior-table-container').classList.add('hidden');
                document.getElementById('behavior-empty-state').classList.remove('hidden');
                return;
            }

            classrooms.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium hover:bg-blue-50 hover:border-blue-200 transition-all';
                btn.textContent = `${c.level}/${c.room}`;
                btn.onclick = () => selectBehaviorClassroom(c, btn);
                container.appendChild(btn);
            });
        } catch (e) {
            console.error('Error loading behavior classrooms:', e);
        }
    }

    // Add listeners for year and semester
    document.getElementById('behavior-year').onchange = loadBehaviorClassrooms;
    document.getElementById('behavior-semester').onchange = loadBehaviorClassrooms;

    async function selectBehaviorClassroom(classroom, btn) {
        document.querySelectorAll('#behavior-classroom-list button').forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
            b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
        btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
        
        currentBehaviorClassroom = classroom;
        loadBehaviorData();
    }

    async function loadBehaviorData() {
        if (!currentBehaviorClassroom) return;
        
        const checkDate = document.getElementById('behavior-date').value;
        const container = document.getElementById('behavior-table-container');
        const emptyState = document.getElementById('behavior-empty-state');
        
        try {
            const res = await fetch(`api/teacher/get_behavior_data.php?classroom_id=${currentBehaviorClassroom.id}&check_date=${checkDate}`);
            const result = await res.json();
            
            behaviorStudents = result.students;
            behaviorRecords = result.records;
            
            renderBehaviorTable();
            
            container.classList.remove('hidden');
            emptyState.classList.add('hidden');
            
        } catch (e) {
            console.error('Error loading behavior data:', e);
        }
    }

    function renderBehaviorTable() {
        const tbody = document.getElementById('behavior-table-body');
        tbody.innerHTML = '';
        
        behaviorStudents.forEach((s, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50/50 transition-colors';
            
            // #
            const tdIdx = document.createElement('td');
            tdIdx.className = 'px-4 py-3 text-sm text-slate-500 text-center border-r border-slate-200';
            tdIdx.textContent = idx + 1;
            tr.appendChild(tdIdx);
            
            // Name
            const tdName = document.createElement('td');
            tdName.className = 'px-4 py-3 text-sm font-medium text-slate-800 border-r border-slate-200';
            tdName.textContent = `${s.prefix}${s.name} ${s.lastname}`;
            tr.appendChild(tdName);
            
            // Categories
            behaviorCategories.forEach(cat => {
                const td = document.createElement('td');
                td.className = 'px-4 py-3 text-sm text-slate-600 border-r border-slate-200 cursor-pointer hover:bg-blue-50/50 transition-all min-h-[40px]';
                
                const record = behaviorRecords.find(r => r.student_id == s.id && r.category_id == cat.id);
                const text = record ? record.behavior_text : '';
                
                td.innerHTML = text ? `<div class="flex flex-wrap gap-1">${text.split(',').map(t => `<span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md text-[11px] font-medium">${t.trim()}</span>`).join('')}</div>` : '<span class="text-slate-300 italic">คลิกเพื่อบันทึก...</span>';
                
                td.onclick = () => openBehaviorModal(s, cat);
                tr.appendChild(td);
            });
            
            tbody.appendChild(tr);
        });
    }

    function openBehaviorModal(student, category) {
        activeBehaviorCell = { studentId: student.id, categoryId: category.id };
        document.getElementById('modal-cat-name').textContent = category.name;
        document.getElementById('modal-student-name').textContent = `นักเรียน: ${student.prefix}${student.name} ${student.lastname}`;
        
        const record = behaviorRecords.find(r => r.student_id == student.id && r.category_id == category.id);
        selectedOptionsInModal = record ? record.behavior_text.split(',').map(t => t.trim()).filter(t => t !== '') : [];
        
        document.getElementById('behavior-option-search').value = '';
        renderBehaviorOptions(category.id);
        
        document.getElementById('behavior-modal').classList.remove('hidden');
        document.getElementById('behavior-modal').classList.add('flex');
        lucide.createIcons();
    }

    function closeBehaviorModal() {
        document.getElementById('behavior-modal').classList.add('hidden');
        document.getElementById('behavior-modal').classList.remove('flex');
    }

    function renderBehaviorOptions(categoryId, filterText = '') {
        const category = behaviorCategories.find(c => c.id == categoryId);
        const list = document.getElementById('behavior-options-list');
        list.innerHTML = '';
        
        let filtered = category.options;
        if (filterText) {
            filtered = category.options.filter(o => o.option_text.toLowerCase().includes(filterText.toLowerCase()));
        }

        filtered.forEach(opt => {
            const isSelected = selectedOptionsInModal.includes(opt.option_text);
            const div = document.createElement('div');
            div.className = `p-3 rounded-xl border cursor-pointer transition-all flex items-center justify-between ${isSelected ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-white border-slate-200 text-slate-700 hover:border-blue-200'}`;
            div.innerHTML = `
                <span class="text-sm font-medium">${opt.option_text}</span>
                ${isSelected ? '<i data-lucide="check-circle-2" class="w-4 h-4"></i>' : '<div class="w-4 h-4 rounded-full border border-slate-300"></div>'}
            `;
            div.onclick = () => toggleOption(opt.option_text);
            list.appendChild(div);
        });

        const addContainer = document.getElementById('add-option-container');
        if (filterText && !category.options.some(o => o.option_text === filterText)) {
            addContainer.classList.remove('hidden');
            document.getElementById('new-option-text').textContent = filterText;
        } else {
            addContainer.classList.add('hidden');
        }
        
        lucide.createIcons();
    }

    function filterBehaviorOptions(text) {
        if (!activeBehaviorCell) return;
        renderBehaviorOptions(activeBehaviorCell.categoryId, text);
    }

    function toggleOption(text) {
        const idx = selectedOptionsInModal.indexOf(text);
        if (idx > -1) {
            selectedOptionsInModal.splice(idx, 1);
        } else {
            selectedOptionsInModal.push(text);
        }
        renderBehaviorOptions(activeBehaviorCell.categoryId, document.getElementById('behavior-option-search').value);
    }

    async function addNewBehaviorOption() {
        const text = document.getElementById('behavior-option-search').value.trim();
        if (!text) return;

        try {
            const res = await fetch('api/teacher/manage_behavior_options.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    category_id: activeBehaviorCell.categoryId,
                    option_text: text
                })
            });
            const result = await res.json();
            if (result.status === 'success') {
                // Update local state
                const cat = behaviorCategories.find(c => c.id == activeBehaviorCell.categoryId);
                cat.options.push({ id: result.id, category_id: activeBehaviorCell.categoryId, option_text: text });
                
                toggleOption(text);
                document.getElementById('behavior-option-search').value = '';
                renderBehaviorOptions(activeBehaviorCell.categoryId);
            }
        } catch (e) {
            console.error('Error adding option:', e);
        }
    }

    function confirmBehaviorSelection() {
        const { studentId, categoryId } = activeBehaviorCell;
        const text = selectedOptionsInModal.join(', ');
        
        let recordIdx = behaviorRecords.findIndex(r => r.student_id == studentId && r.category_id == categoryId);
        if (recordIdx > -1) {
            behaviorRecords[recordIdx].behavior_text = text;
        } else {
            behaviorRecords.push({
                student_id: studentId,
                category_id: categoryId,
                behavior_text: text
            });
        }
        
        renderBehaviorTable();
        closeBehaviorModal();
    }

    async function saveBehavior() {
        if (!currentBehaviorClassroom) return;
        
        const year = document.getElementById('behavior-year').value;
        const semester = document.getElementById('behavior-semester').value;
        const checkDate = document.getElementById('behavior-date').value;

        try {
            const res = await fetch('api/teacher/save_behavior.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    records: behaviorRecords,
                    check_date: checkDate,
                    academic_year: year,
                    semester: semester
                })
            });
            const result = await res.json();
            if (result.status === 'success') {
                alert('บันทึกข้อมูลพฤติกรรมเรียบร้อยแล้ว');
            } else {
                alert('เกิดข้อผิดพลาด: ' + result.error);
            }
        } catch (e) {
            console.error('Error saving behavior:', e);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        }
    }

    // Listen for date change
    document.getElementById('behavior-date').onchange = loadBehaviorData;

    document.addEventListener('DOMContentLoaded', initBehaviorSection);
</script>
