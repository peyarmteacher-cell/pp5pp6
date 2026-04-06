<div id="record-grades" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">บันทึกผลการเรียน</h3>
                <p class="text-sm text-slate-500">เลือกรายวิชาที่ต้องการบันทึกคะแนน</p>
            </div>
            <div class="flex gap-2">
                <select id="grade_academic_year" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-sm" onchange="loadMyAssignments()">
                    <!-- จะถูกเติมด้วย JavaScript -->
                </select>
                <select id="grade_semester" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-sm" onchange="loadMyAssignments()">
                    <option value="1">ภาคเรียนที่ 1</option>
                    <option value="2">ภาคเรียนที่ 2</option>
                </select>
            </div>
        </div>

        <div id="assignment-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Assignments will be loaded here -->
        </div>
    </div>

    <!-- Grading Interface (Hidden until assignment selected) -->
    <div id="grading-interface" class="hidden space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <button onclick="backToAssignments()" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        กลับไปหน้ารายวิชา
                    </button>
                    <h3 id="selected-subject-title" class="text-xl font-bold text-slate-800">รายวิชา: -</h3>
                    <p id="selected-classroom-title" class="text-sm text-slate-500">ชั้น: - ห้อง: -</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-slate-100 mb-6 overflow-x-auto">
                <button onclick="switchGradingTab('academic')" id="tab-academic" class="px-6 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap">คะแนนผลการเรียน</button>
                <button onclick="switchGradingTab('characteristics')" id="tab-characteristics" class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap">คุณลักษณะอันพึงประสงค์ (8 ข้อ)</button>
                <button onclick="switchGradingTab('analytical')" id="tab-analytical" class="px-6 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap">อ่าน คิดวิเคราะห์ และเขียน</button>
            </div>

            <!-- Tab Contents -->
            <div id="academic-content" class="grading-tab-content">
                <?php include 'includes/dashboard/grading/academic_tab.php'; ?>
            </div>
            <div id="characteristics-content" class="grading-tab-content hidden">
                <?php include 'includes/dashboard/grading/characteristics_tab.php'; ?>
            </div>
            <div id="analytical-content" class="grading-tab-content hidden">
                <?php include 'includes/dashboard/grading/analytical_tab.php'; ?>
            </div>
        </div>
    </div>
</div>

<script>
    let currentAssignment = null;
    let currentStudents = [];

    async function loadMyAssignments() {
        const year = document.getElementById('grade_academic_year').value;
        const semester = document.getElementById('grade_semester').value;
        
        try {
            const res = await fetch(`api/teacher/get_my_assignments.php?academic_year=${year}&semester=${semester}`);
            const assignments = await res.json();
            
            const container = document.getElementById('assignment-list');
            if (assignments.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">ไม่พบรายวิชาที่ได้รับมอบหมายในภาคเรียนนี้</div>';
                return;
            }

            container.innerHTML = assignments.map(a => `
                <div onclick="selectAssignment(${JSON.stringify(a).replace(/"/g, '&quot;')})" class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-md transition-all cursor-pointer group">
                    <div class="flex justify-between items-start mb-3">
                        <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-lg uppercase">${a.code}</span>
                        <span class="text-xs text-slate-400">${a.level}</span>
                    </div>
                    <h4 class="font-bold text-slate-800 group-hover:text-blue-600 transition-colors mb-1">${a.subject_name}</h4>
                    <p class="text-sm text-slate-500">ห้องเรียน: ${a.room}</p>
                    <div class="mt-4 flex items-center gap-1 text-blue-600 text-xs font-bold">
                        บันทึกคะแนน
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            console.error('Error loading assignments:', e);
        }
    }

    function selectAssignment(assignment) {
        currentAssignment = assignment;
        document.getElementById('assignment-list').parentElement.classList.add('hidden');
        document.getElementById('grading-interface').classList.remove('hidden');
        
        document.getElementById('selected-subject-title').innerText = `รายวิชา: ${assignment.code} ${assignment.subject_name}`;
        document.getElementById('selected-classroom-title').innerText = `ชั้น: ${assignment.level} ห้อง: ${assignment.room}`;
        
        loadStudentsByAssignment();
    }

    function backToAssignments() {
        document.getElementById('assignment-list').parentElement.classList.remove('hidden');
        document.getElementById('grading-interface').classList.add('hidden');
        currentAssignment = null;
    }

    async function loadStudentsByAssignment() {
        if (!currentAssignment) return;
        
        const year = document.getElementById('grade_academic_year').value;
        const semester = document.getElementById('grade_semester').value;
        
        try {
            const res = await fetch(`api/teacher/get_students_by_assignment.php?classroom_id=${currentAssignment.classroom_id}&subject_id=${currentAssignment.subject_id}&academic_year=${year}&semester=${semester}`);
            currentStudents = await res.json();
            
            // Load Learning Units for Academic Tab
            if (typeof loadLearningUnits === 'function') {
                await loadLearningUnits();
            } else {
                renderAcademicTable();
            }
            
            renderCharacteristicsTable();
            renderAnalyticalTable();
        } catch (e) {
            console.error('Error loading students:', e);
        }
    }

    function switchGradingTab(tab) {
        document.querySelectorAll('.grading-tab-content').forEach(c => c.classList.add('hidden'));
        document.getElementById(`${tab}-content`).classList.remove('hidden');
        
        document.querySelectorAll('[id^="tab-"]').forEach(t => {
            t.classList.remove('border-blue-600', 'text-blue-600');
            t.classList.add('border-transparent', 'text-slate-500');
        });
        
        const activeTab = document.getElementById(`tab-${tab}`);
        activeTab.classList.remove('border-transparent', 'text-slate-500');
        activeTab.classList.add('border-blue-600', 'text-blue-600');
    }
</script>
