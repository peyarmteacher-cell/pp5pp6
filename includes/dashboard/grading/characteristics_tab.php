<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1000px]">
            <thead>
                <tr class="text-slate-500 border-b border-slate-100">
                    <th class="pb-3 font-medium w-12">เลขที่</th>
                    <th class="pb-3 font-medium w-48">ชื่อ-นามสกุล</th>
                    <th class="pb-3 font-medium text-center w-12">1</th>
                    <th class="pb-3 font-medium text-center w-12">2</th>
                    <th class="pb-3 font-medium text-center w-12">3</th>
                    <th class="pb-3 font-medium text-center w-12">4</th>
                    <th class="pb-3 font-medium text-center w-12">5</th>
                    <th class="pb-3 font-medium text-center w-12">6</th>
                    <th class="pb-3 font-medium text-center w-12">7</th>
                    <th class="pb-3 font-medium text-center w-12">8</th>
                    <th class="pb-3 font-medium text-center w-16">เฉลี่ย</th>
                </tr>
            </thead>
            <tbody id="characteristics-table-body">
                <!-- Students will be loaded here -->
            </tbody>
        </table>
    </div>
    <div class="bg-slate-50 p-4 rounded-xl text-xs text-slate-500 space-y-1">
        <p><strong>คำอธิบายคุณลักษณะ 8 ข้อ:</strong> 1.รักชาติ ศาสน์ กษัตริย์ 2.ซื่อสัตย์สุจริต 3.มีวินัย 4.ใฝ่เรียนรู้ 5.อยู่อย่างพอเพียง 6.มุ่งมั่นในการทำงาน 7.รักความเป็นไทย 8.มีจิตสาธารณะ</p>
        <p><strong>เกณฑ์การให้คะแนน:</strong> 3 = ดีเยี่ยม, 2 = ดี, 1 = ผ่าน, 0 = ไม่ผ่าน</p>
    </div>
    <div class="flex justify-end pt-4">
        <button onclick="saveCharacteristics()" class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">บันทึกคุณลักษณะอันพึงประสงค์</button>
    </div>
</div>

<script>
    function renderCharacteristicsTable() {
        const tbody = document.getElementById('characteristics-table-body');
        tbody.innerHTML = currentStudents.map((s, index) => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="py-3 text-slate-600 font-mono">${index + 1}</td>
                <td class="py-3 font-medium text-slate-800">${s.prefix}${s.name}</td>
                ${[1,2,3,4,5,6,7,8].map(i => `
                    <td class="py-3 text-center">
                        <select onchange="updateCharScore(${s.id}, ${i-1}, this.value)" 
                            class="w-12 px-1 py-1 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-500/20 text-center text-sm">
                            <option value="0" ${s['item'+i] == 0 ? 'selected' : ''}>0</option>
                            <option value="1" ${s['item'+i] == 1 ? 'selected' : ''}>1</option>
                            <option value="2" ${s['item'+i] == 2 ? 'selected' : ''}>2</option>
                            <option value="3" ${s['item'+i] == 3 ? 'selected' : ''}>3</option>
                        </select>
                    </td>
                `).join('')}
                <td class="py-3 text-center font-bold text-blue-600" id="char-avg-${s.id}">${(s.average_score || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    function updateCharScore(studentId, itemIndex, value) {
        const student = currentStudents.find(s => s.id == studentId);
        student['item' + (itemIndex + 1)] = parseInt(value) || 0;
        
        let total = 0;
        for (let i = 1; i <= 8; i++) {
            total += (student['item' + i] || 0);
        }
        student.average_score = total / 8;
        
        document.getElementById(`char-avg-${studentId}`).innerText = student.average_score.toFixed(2);
    }

    async function saveCharacteristics() {
        if (!currentAssignment) return;
        
        const payload = {
            subject_id: currentAssignment.subject_id,
            classroom_id: currentAssignment.classroom_id,
            academic_year: document.getElementById('grade_academic_year').value,
            semester: document.getElementById('grade_semester').value,
            scores: currentStudents.map(s => ({
                student_id: s.id,
                items: [s.item1||0, s.item2||0, s.item3||0, s.item4||0, s.item5||0, s.item6||0, s.item7||0, s.item8||0]
            }))
        };

        try {
            const res = await fetch('api/teacher/save_characteristics.php', {
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
            console.error('Error saving characteristics:', e);
        }
    }
</script>
