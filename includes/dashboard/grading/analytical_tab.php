<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 border-b border-slate-100">
                    <th class="pb-3 font-medium w-24">เลขที่</th>
                    <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                    <th class="pb-3 font-medium w-48 text-center">ระดับคุณภาพ (0-3)</th>
                </tr>
            </thead>
            <tbody id="analytical-table-body">
                <!-- Students will be loaded here -->
            </tbody>
        </table>
    </div>
    <div class="bg-slate-50 p-4 rounded-xl text-xs text-slate-500 space-y-1">
        <p><strong>เกณฑ์การให้คะแนน:</strong> 3 = ดีเยี่ยม, 2 = ดี, 1 = ผ่าน, 0 = ไม่ผ่าน</p>
    </div>
    <div class="flex justify-end pt-4">
        <button onclick="saveAnalytical()" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">บันทึกอ่านคิดวิเคราะห์</button>
    </div>
</div>

<script>
    function renderAnalyticalTable() {
        const tbody = document.getElementById('analytical-table-body');
        tbody.innerHTML = currentStudents.map((s, index) => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="py-3 text-slate-600 font-mono">${index + 1}</td>
                <td class="py-3 font-medium text-slate-800">${s.prefix}${s.name}</td>
                <td class="py-3 text-center">
                    <select onchange="updateAnalyticalScore(${s.id}, this.value)" 
                        class="w-32 px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 text-center text-sm">
                        <option value="0" ${s.analytical_score == 0 ? 'selected' : ''}>0 (ไม่ผ่าน)</option>
                        <option value="1" ${s.analytical_score == 1 ? 'selected' : ''}>1 (ผ่าน)</option>
                        <option value="2" ${s.analytical_score == 2 ? 'selected' : ''}>2 (ดี)</option>
                        <option value="3" ${s.analytical_score == 3 ? 'selected' : ''}>3 (ดีเยี่ยม)</option>
                    </select>
                </td>
            </tr>
        `).join('');
    }

    function updateAnalyticalScore(studentId, value) {
        const student = currentStudents.find(s => s.id == studentId);
        student.analytical_score = parseInt(value) || 0;
    }

    async function saveAnalytical() {
        const assignment = currentAnalAssignment || currentAssignment;
        if (!assignment) return;
        
        const yearEl = document.getElementById('anal_academic_year') || document.getElementById('grade_academic_year');
        const semEl = document.getElementById('anal_semester') || document.getElementById('grade_semester');
        
        const payload = {
            subject_id: assignment.subject_id,
            classroom_id: assignment.classroom_id,
            academic_year: yearEl.value,
            semester: semEl.value,
            scores: currentStudents.map(s => ({
                student_id: s.id,
                score: s.analytical_score || 0
            }))
        };

        try {
            const res = await fetch('api/teacher/save_analytical.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                if (typeof loadAnalStudents === 'function') {
                    loadAnalStudents();
                } else {
                    loadStudentsByAssignment();
                }
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error saving analytical scores:', e);
        }
    }
</script>
