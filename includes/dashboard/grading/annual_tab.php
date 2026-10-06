<!-- Annual Tab: Flexible Calculation (Average 50% vs Direct Sum) -->
<div class="space-y-4">
    <!-- Mode Switcher Banner -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-purple-50 text-purple-700 rounded-xl border border-purple-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="16" x2="16" y1="14" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-slate-800">การคิดคะแนนรวมทั้งปีการศึกษา (ระดับประถมศึกษา)</h4>
                    <span id="active-mode-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">แบ่งครึ่ง 50%</span>
                </div>
                <p id="annual-mode-desc" class="text-xs text-slate-500 mt-0.5">สูตร: (ร้อยละเทอม 1 + ร้อยละเทอม 2) ÷ 2 เหมาะสำหรับโรงเรียนที่เก็บคะแนนเต็ม 100 ทั้ง 2 ภาคเรียน</p>
            </div>
        </div>

        <div class="flex items-center bg-white p-1 rounded-xl border border-slate-200 shadow-sm self-stretch md:self-auto">
            <button type="button" onclick="setAnnualCalculationMode('average')" id="btn-mode-average" class="flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-blue-600 text-white shadow-sm">
                แบ่งครึ่ง 50% ((ท1+ท2)/2)
            </button>
            <button type="button" onclick="setAnnualCalculationMode('sum')" id="btn-mode-sum" class="flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-50">
                รวมคะแนนตรง (ท1+ท2 ไม่หาร)
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1000px]">
            <thead id="annual-grading-table-header">
                <!-- Will be dynamically rendered -->
            </thead>
            <tbody id="annual-grading-table-body">
                <!-- Populated by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<script>
let annualCalculationMode = 'average';

function setAnnualCalculationMode(mode) {
    annualCalculationMode = mode;
    
    const btnAvg = document.getElementById('btn-mode-average');
    const btnSum = document.getElementById('btn-mode-sum');
    const badge = document.getElementById('active-mode-badge');
    const desc = document.getElementById('annual-mode-desc');
    
    if (mode === 'sum') {
        if (btnSum) {
            btnSum.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-emerald-600 text-white shadow-sm';
        }
        if (btnAvg) {
            btnAvg.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-50';
        }
        if (badge) {
            badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700';
            badge.innerText = 'รวมคะแนนตรง (ไม่หาร 50%)';
        }
        if (desc) {
            desc.innerText = 'สูตร: คะแนนรวมเทอม 1 + คะแนนรวมเทอม 2 (เช่น เทอม 1 เต็ม 50 + เทอม 2 เต็ม 50 = รวม 100 คะแนน)';
        }
    } else {
        if (btnAvg) {
            btnAvg.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-blue-600 text-white shadow-sm';
        }
        if (btnSum) {
            btnSum.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-50';
        }
        if (badge) {
            badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700';
            badge.innerText = 'แบ่งครึ่งเฉลี่ย 50%';
        }
        if (desc) {
            desc.innerText = 'สูตร: (ร้อยละเทอม 1 + ร้อยละเทอม 2) ÷ 2 เหมาะสำหรับโรงเรียนที่เก็บคะแนนเต็ม 100 ทั้ง 2 ภาคเรียน';
        }
    }

    renderAnnualTable();
}

function renderAnnualTable() {
    const thead = document.getElementById('annual-grading-table-header');
    const tbody = document.getElementById('annual-grading-table-body');
    if (!tbody || !thead) return;

    // Detect school default mode from loaded student data if not set manually
    if (currentStudents.length > 0 && currentStudents[0].primary_grading_mode && !window._userChangedAnnualMode) {
        annualCalculationMode = currentStudents[0].primary_grading_mode;
        // Update button states once
        const btnAvg = document.getElementById('btn-mode-average');
        const btnSum = document.getElementById('btn-mode-sum');
        const badge = document.getElementById('active-mode-badge');
        const desc = document.getElementById('annual-mode-desc');
        if (annualCalculationMode === 'sum') {
            if (btnSum) btnSum.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-emerald-600 text-white shadow-sm';
            if (btnAvg) btnAvg.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-50';
            if (badge) { badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700'; badge.innerText = 'รวมคะแนนตรง (ไม่หาร 50%)'; }
            if (desc) desc.innerText = 'สูตร: คะแนนรวมเทอม 1 + คะแนนรวมเทอม 2 (เช่น เทอม 1 เต็ม 50 + เทอม 2 เต็ม 50 = รวม 100 คะแนน)';
        } else {
            if (btnAvg) btnAvg.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-blue-600 text-white shadow-sm';
            if (btnSum) btnSum.className = 'flex-1 md:flex-none px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-800 hover:bg-slate-50';
            if (badge) { badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700'; badge.innerText = 'แบ่งครึ่งเฉลี่ย 50%'; }
            if (desc) desc.innerText = 'สูตร: (ร้อยละเทอม 1 + ร้อยละเทอม 2) ÷ 2 เหมาะสำหรับโรงเรียนที่เก็บคะแนนเต็ม 100 ทั้ง 2 ภาคเรียน';
        }
    }

    if (currentStudents.length === 0) {
        thead.innerHTML = '';
        tbody.innerHTML = '<tr><td colspan="10" class="p-8 text-center text-slate-400">ไม่พบข้อมูลนักเรียน</td></tr>';
        return;
    }

    // Render Headers based on mode
    if (annualCalculationMode === 'sum') {
        thead.innerHTML = `
            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                <th rowspan="2" class="p-4 font-bold text-sm border-r border-slate-200 w-16 text-center">ที่</th>
                <th rowspan="2" class="p-4 font-bold text-sm border-r border-slate-200">ชื่อ-นามสกุล</th>
                <th colspan="2" class="p-2 font-bold text-sm border-r border-slate-200 text-center bg-blue-50/50">ภาคเรียนที่ 1</th>
                <th colspan="2" class="p-2 font-bold text-sm border-r border-slate-200 text-center bg-green-50/50">ภาคเรียนที่ 2</th>
                <th colspan="2" class="p-2 font-bold text-sm text-center bg-emerald-50/60">รวมทั้งปีการศึกษา (ไม่หาร 50%)</th>
            </tr>
            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-24">คะแนนที่ได้ (ท1)</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-16">เกรด ท1</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-24">คะแนนที่ได้ (ท2)</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-16">เกรด ท2</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-28 text-emerald-800 bg-emerald-50/40">คะแนนสะสม (ท1 + ท2)</th>
                <th class="p-2 font-bold text-xs text-center w-20 text-emerald-800 bg-emerald-50/40">เกรดทั้งปี</th>
            </tr>
        `;
    } else {
        thead.innerHTML = `
            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                <th rowspan="2" class="p-4 font-bold text-sm border-r border-slate-200 w-16 text-center">ที่</th>
                <th rowspan="2" class="p-4 font-bold text-sm border-r border-slate-200">ชื่อ-นามสกุล</th>
                <th colspan="3" class="p-2 font-bold text-sm border-r border-slate-200 text-center bg-blue-50/50">ภาคเรียนที่ 1</th>
                <th colspan="3" class="p-2 font-bold text-sm border-r border-slate-200 text-center bg-green-50/50">ภาคเรียนที่ 2</th>
                <th colspan="2" class="p-2 font-bold text-sm text-center bg-purple-50/50">รวมทั้งปีการศึกษา (เฉลี่ย 50%:50%)</th>
            </tr>
            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-20">คะแนนรวม</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-20">ร้อยละ</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-16">เกรด</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-20">คะแนนรวม</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-20">ร้อยละ</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-16">เกรด</th>
                <th class="p-2 font-bold text-xs border-r border-slate-200 text-center w-24 text-purple-800 bg-purple-50/30">ร้อยละเฉลี่ย</th>
                <th class="p-2 font-bold text-xs text-center w-20 text-purple-800 bg-purple-50/30">เกรดเฉลี่ย</th>
            </tr>
        `;
    }

    tbody.innerHTML = currentStudents.map((s, index) => {
        // Values for Term 1
        const sem1Units = parseFloat(s.sem1_units || 0);
        const sem1Total = parseFloat(s.sem1_total !== null && s.sem1_total !== undefined ? s.sem1_total : (s.sem1_percent || 0));
        const sem1Percent = parseFloat(s.sem1_percent !== null && s.sem1_percent !== undefined ? s.sem1_percent : sem1Total);
        const sem1Grade = s.sem1_grade || '-';

        // Values for Term 2
        const sem2Units = parseFloat(s.sem2_units || 0);
        const sem2Total = parseFloat(s.sem2_total !== null && s.sem2_total !== undefined ? s.sem2_total : (s.sem2_percent || 0));
        const sem2Percent = parseFloat(s.sem2_percent !== null && s.sem2_percent !== undefined ? s.sem2_percent : sem2Total);
        const sem2Grade = s.sem2_grade || '-';

        if (annualCalculationMode === 'sum') {
            // Mode Sum: Direct accumulation of scores (Sem 1 + Sem 2)
            const annualSum = sem1Total + sem2Total;
            const annualGrade = calculateGradeFromPercent(annualSum);

            return `
                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                    <td class="p-3 text-center text-sm text-slate-500 border-r border-slate-100">${index + 1}</td>
                    <td class="p-3 border-r border-slate-100">
                        <div class="font-medium text-slate-800">${s.prefix || ''}${s.name || ''}&nbsp;${s.last_name || ''}</div>
                        <div class="text-[10px] text-slate-400 font-mono">${s.student_code}</div>
                    </td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-blue-50/10 font-bold text-blue-700">${sem1Total.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-blue-50/10 font-bold">${sem1Grade}</td>
                    
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-green-50/10 font-bold text-green-700">${sem2Total.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-green-50/10 font-bold">${sem2Grade}</td>
                    
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-emerald-50/20 font-bold text-emerald-700 text-base">${annualSum.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm bg-emerald-50/20 font-bold text-emerald-800 text-lg">${annualGrade}</td>
                </tr>
            `;
        } else {
            // Mode Average: 50% Sem 1 + 50% Sem 2
            const annualPercent = (sem1Percent + sem2Percent) / 2;
            const annualGrade = calculateGradeFromPercent(annualPercent);

            return `
                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                    <td class="p-3 text-center text-sm text-slate-500 border-r border-slate-100">${index + 1}</td>
                    <td class="p-3 border-r border-slate-100">
                        <div class="font-medium text-slate-800">${s.prefix || ''}${s.name || ''}&nbsp;${s.last_name || ''}</div>
                        <div class="text-[10px] text-slate-400 font-mono">${s.student_code}</div>
                    </td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-blue-50/10">${sem1Total.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-blue-50/10 font-bold text-blue-600">${sem1Percent.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-blue-50/10 font-bold">${sem1Grade}</td>
                    
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-green-50/10">${sem2Total.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-green-50/10 font-bold text-green-600">${sem2Percent.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-green-50/10 font-bold">${sem2Grade}</td>
                    
                    <td class="p-3 text-center text-sm border-r border-slate-100 bg-purple-50/10 font-bold text-purple-600">${annualPercent.toFixed(1)}</td>
                    <td class="p-3 text-center text-sm bg-purple-50/10 font-bold text-purple-700 text-lg">${annualGrade}</td>
                </tr>
            `;
        }
    }).join('');
}

function calculateGradeFromPercent(percent) {
    if (percent >= 80) return '4';
    if (percent >= 75) return '3.5';
    if (percent >= 70) return '3';
    if (percent >= 65) return '2.5';
    if (percent >= 60) return '2';
    if (percent >= 55) return '1.5';
    if (percent >= 50) return '1';
    return '0';
}
</script>
