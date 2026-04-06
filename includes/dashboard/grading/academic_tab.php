<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 border-b border-slate-100">
                    <th class="pb-3 font-medium w-24">เลขที่</th>
                    <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                    <th class="pb-3 font-medium w-32">คะแนนกลางภาค</th>
                    <th class="pb-3 font-medium w-32">คะแนนปลายภาค</th>
                    <th class="pb-3 font-medium w-24">รวม</th>
                    <th class="pb-3 font-medium w-24">เกรด</th>
                </tr>
            </thead>
            <tbody id="academic-table-body">
                <!-- Students will be loaded here -->
            </tbody>
        </table>
    </div>
    <div class="flex justify-end pt-4">
        <button onclick="saveAcademicGrades()" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">บันทึกคะแนนผลการเรียน</button>
    </div>
</div>

<script>
    function renderAcademicTable() {
        const tbody = document.getElementById('academic-table-body');
        tbody.innerHTML = currentStudents.map((s, index) => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="py-3 text-slate-600 font-mono">${index + 1}</td>
                <td class="py-3 font-medium text-slate-800">${s.prefix}${s.name}</td>
                <td class="py-3">
                    <input type="number" step="0.1" value="${s.score_midterm || 0}" 
                        onchange="updateTotal(${s.id}, this.value, 'midterm')"
                        class="w-24 px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20">
                </td>
                <td class="py-3">
                    <input type="number" step="0.1" value="${s.score_final || 0}" 
                        onchange="updateTotal(${s.id}, this.value, 'final')"
                        class="w-24 px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20">
                </td>
                <td class="py-3 font-bold text-blue-600" id="total-${s.id}">${s.score_total || 0}</td>
                <td class="py-3 font-bold text-slate-800" id="grade-${s.id}">${s.grade || '0'}</td>
            </tr>
        `).join('');
    }

    function updateTotal(studentId, value, type) {
        const student = currentStudents.find(s => s.id == studentId);
        if (type === 'midterm') student.score_midterm = parseFloat(value) || 0;
        if (type === 'final') student.score_final = parseFloat(value) || 0;
        
        student.score_total = (student.score_midterm || 0) + (student.score_final || 0);
        
        // คำนวณเกรดเบื้องต้น
        let grade = '0';
        const total = student.score_total;
        if (total >= 80) grade = '4';
        else if (total >= 75) grade = '3.5';
        else if (total >= 70) grade = '3';
        else if (total >= 65) grade = '2.5';
        else if (total >= 60) grade = '2';
        else if (total >= 55) grade = '1.5';
        else if (total >= 50) grade = '1';
        else grade = '0';
        
        student.grade = grade;
        
        document.getElementById(`total-${studentId}`).innerText = student.score_total;
        document.getElementById(`grade-${studentId}`).innerText = grade;
    }

    async function saveAcademicGrades() {
        if (!currentAssignment) return;
        
        const payload = {
            subject_id: currentAssignment.subject_id,
            classroom_id: currentAssignment.classroom_id,
            academic_year: document.getElementById('grade_academic_year').value,
            semester: document.getElementById('grade_semester').value,
            grades: currentStudents.map(s => ({
                student_id: s.id,
                score_midterm: s.score_midterm,
                score_final: s.score_final
            }))
        };

        try {
            const res = await fetch('api/teacher/save_grades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadStudentsByAssignment();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error saving grades:', e);
        }
    }
</script>
