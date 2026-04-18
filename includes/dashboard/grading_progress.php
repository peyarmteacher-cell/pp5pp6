<!-- Grading Progress Monitoring Section -->
<div id="grading-progress" class="section hidden space-y-6">
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-tr from-indigo-600 to-blue-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200/50">
                    <i data-lucide="trending-up" class="w-7 h-7"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">ความคืบหน้าการบันทึกคะแนน</h3>
                    <p class="text-slate-500 text-sm font-medium">ติดตามสถานะการบันทึกคะแนนรายวิชาจำแนกตามระดับชั้น</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                <div class="flex items-center gap-2 pl-2">
                    <i data-lucide="filter" class="w-4 h-4 text-slate-400"></i>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ระดับชั้น:</span>
                </div>
                <select id="progress_level_filter" onchange="loadGradingProgress()" class="min-w-[160px] px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 outline-none transition-all font-bold text-slate-700 shadow-sm">
                    <option value="">ทุกระดับชั้น</option>
                    <option value="ป.1">ประถมศึกษาปีที่ 1</option>
                    <option value="ป.2">ประถมศึกษาปีที่ 2</option>
                    <option value="ป.3">ประถมศึกษาปีที่ 3</option>
                    <option value="ป.4">ประถมศึกษาปีที่ 4</option>
                    <option value="ป.5">ประถมศึกษาปีที่ 5</option>
                    <option value="ป.6">ประถมศึกษาปีที่ 6</option>
                    <option value="ม.1">มัธยมศึกษาปีที่ 1</option>
                    <option value="ม.2">มัธยมศึกษาปีที่ 2</option>
                    <option value="ม.3">มัธยมศึกษาปีที่ 3</option>
                </select>
                <button onclick="loadGradingProgress()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-600 hover:bg-white hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Progress Tabs -->
        <div class="flex overflow-x-auto gap-2 mb-6 border-b border-slate-100 pb-1 custom-scrollbar">
            <button onclick="switchProgressTab('academics')" id="tab-academics" class="progress-tab active flex items-center gap-2 px-6 py-3 rounded-t-2xl font-bold text-sm transition-all border-b-2 border-transparent hover:bg-slate-50">
                <i data-lucide="book-open" class="w-4 h-4"></i>
                หน่วยการเรียนรู้และผลสอบปลายภาค
            </button>
            <button onclick="switchProgressTab('evaluations')" id="tab-evaluations" class="progress-tab flex items-center gap-2 px-6 py-3 rounded-t-2xl font-bold text-sm transition-all border-b-2 border-transparent hover:bg-slate-50">
                <i data-lucide="award" class="w-4 h-4"></i>
                กิจกรรมพัฒนาผู้เรียนและสมรรถนะ
            </button>
            <button onclick="switchProgressTab('characteristics')" id="tab-characteristics" class="progress-tab flex items-center gap-2 px-6 py-3 rounded-t-2xl font-bold text-sm transition-all border-b-2 border-transparent hover:bg-slate-50">
                <i data-lucide="user-check" class="w-4 h-4"></i>
                คุณลักษณะและอ่านคิดวิเคราะห์
            </button>
        </div>

        <style>
            .progress-tab.active {
                color: #2563eb;
                background: white;
                border-bottom-color: #2563eb;
            }
            .progress-tab:not(.active) {
                color: #64748b;
            }
        </style>

        <div class="relative overflow-hidden group">
            <div id="progress_loading_state" class="hidden absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-2xl">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-bold text-slate-600 animate-pulse">กำลังดึงข้อมูล...</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-100">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100">รายวิชา / ครูผู้สอน</th>
                            <th id="progress_header_dynamic" class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100">รายละเอียดความคืบหน้า</th>
                            <th class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100 w-48 text-right">ภาพรวม</th>
                        </tr>
                    </thead>
                    <tbody id="gradingProgressTableBody" class="divide-y divide-slate-50">
                        <!-- Content loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div id="progress_empty_state" class="hidden py-24 text-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i data-lucide="search-x" class="w-10 h-10"></i>
                </div>
                <h4 class="text-lg font-bold text-slate-700">ไม่พบรายวิชาที่ได้รับมอบหมาย</h4>
                <p class="text-slate-500 max-w-xs mx-auto mt-2">โปรดระบุระดับชั้นอื่นหรือตรวจสอบการมอบหมายงานหลักสูตร</p>
            </div>
        </div>
    </div>
</div>

<script>
    let currentProgressTab = 'academics';
    let progressDataCache = [];

    function switchProgressTab(tab) {
        currentProgressTab = tab;
        document.querySelectorAll('.progress-tab').forEach(t => t.classList.remove('active'));
        document.getElementById(`tab-${tab}`).classList.add('active');
        renderGradingProgress();
    }

    async function initGradingProgress() {
        console.log('Initializing Grading Progress Monitoring...');
        await loadGradingProgress();
    }

    async function loadGradingProgress() {
        const loading = document.getElementById('progress_loading_state');
        const empty = document.getElementById('progress_empty_state');
        const level = document.getElementById('progress_level_filter').value;
        
        if (loading) loading.classList.remove('hidden');
        if (empty) empty.classList.add('hidden');
        
        try {
            const url = `api/admin/get_grading_progress.php?academic_year=${currentAcademicYear}&semester=${currentSemester}&level=${encodeURIComponent(level)}`;
            const res = await fetch(url);
            progressDataCache = await res.json();
            
            if (progressDataCache.error) throw new Error(progressDataCache.error);
            
            renderGradingProgress();
            
        } catch (e) {
            console.error('Error loading grading progress:', e);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + e.message);
        } finally {
            if (loading) loading.classList.add('hidden');
        }
    }

    function renderGradingProgress() {
        const tbody = document.getElementById('gradingProgressTableBody');
        const empty = document.getElementById('progress_empty_state');
        const header = document.getElementById('progress_header_dynamic');

        if (!progressDataCache || progressDataCache.length === 0) {
            tbody.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');

        // Update dynamic header title
        if (currentProgressTab === 'academics') header.innerText = 'หน่วยการเรียนรู้ / ผลสอบปลายภาค';
        else if (currentProgressTab === 'evaluations') header.innerText = 'สมรรถนะ / กิจกรรมพัฒนาผู้เรียน';
        else header.innerText = 'คุณลักษณะพึงประสงค์ / อ่าน คิดวิเคราะห์';

        tbody.innerHTML = progressDataCache.map(item => {
            const studentCount = parseInt(item.student_count) || 0;
            const totalUnits = parseInt(item.total_units) || 0;
            const completedUnits = parseInt(item.completed_units) || 0;
            
            const unitsP = totalUnits > 0 ? (completedUnits / totalUnits) : 0;
            const finalP = studentCount > 0 ? (parseInt(item.final_count) / studentCount) : 0;
            const charP = studentCount > 0 ? (parseInt(item.characteristics_count) / studentCount) : 0;
            const analyticalP = studentCount > 0 ? (parseInt(item.analytical_count) / studentCount) : 0;
            const competencyP = studentCount > 0 ? (parseInt(item.competency_count) / studentCount) : 0;
            const learnerDevP = studentCount > 0 ? (parseInt(item.learner_dev_count) / studentCount) : 0;

            let tabContent = '';
            let overallP = 0;

            if (currentProgressTab === 'academics') {
                overallP = ((unitsP + finalP) / 2) * 100;
                tabContent = `
                    <div class="grid grid-cols-1 gap-3">
                        ${renderMiniBar('หน่วยการเรียน', unitsP, `${completedUnits}/${totalUnits} หน่วย`)}
                        ${renderMiniBar('สอบปลายภาค', finalP, `${item.final_count}/${studentCount} คน`)}
                    </div>
                `;
            } else if (currentProgressTab === 'evaluations') {
                overallP = ((competencyP + learnerDevP) / 2) * 100;
                tabContent = `
                    <div class="grid grid-cols-1 gap-3">
                        ${renderMiniBar('ประเมินสมรรถนะ', competencyP, `${item.competency_count}/${studentCount} คน`)}
                        ${renderMiniBar('กิจกรรมพัฒนาผู้เรียน', learnerDevP, `${item.learner_dev_count}/${studentCount} คน`)}
                    </div>
                `;
            } else {
                overallP = ((charP + analyticalP) / 2) * 100;
                tabContent = `
                    <div class="grid grid-cols-1 gap-3">
                        ${renderMiniBar('คุณลักษณะพึงประสงค์', charP, `${item.characteristics_count}/${studentCount} คน`)}
                        ${renderMiniBar('ประเมินการอ่าน คิดวิเคราะห์', analyticalP, `${item.analytical_count}/${studentCount} คน`)}
                    </div>
                `;
            }

            const percent = Math.round(overallP);
            let barColor = 'bg-blue-500';
            let textColor = 'text-blue-600';
            if (percent >= 100) { barColor = 'bg-emerald-500'; textColor = 'text-emerald-600'; }
            else if (percent > 0 && percent < 50) { barColor = 'bg-amber-500'; textColor = 'text-amber-600'; }
            else if (percent === 0) { barColor = 'bg-slate-300'; textColor = 'text-slate-400'; }

            return `
                <tr class="hover:bg-slate-50 transition-all group">
                    <td class="px-6 py-6 w-72">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex-shrink-0 flex items-center justify-center font-black text-slate-400 border border-slate-200">
                                ${item.teacher_name ? item.teacher_name.charAt(0) : '?'}
                            </div>
                            <div class="overflow-hidden">
                                <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest block mb-1">${item.subject_code}</span>
                                <h4 class="text-sm font-bold text-slate-800 truncate mb-1">${item.subject_name}</h4>
                                <p class="text-xs text-slate-500 font-medium truncate">${item.teacher_name} ${item.teacher_last_name || ''}</p>
                                <div class="flex gap-1.5 mt-2">
                                    <span class="px-2 py-0.5 bg-slate-50 border border-slate-200 rounded text-[9px] font-bold text-slate-500">${item.subject_level}</span>
                                    ${item.room ? `<span class="px-2 py-0.5 bg-blue-50 border border-blue-100 rounded text-[9px] font-black text-blue-600">/ ${item.room}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        ${tabContent}
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="inline-flex flex-col items-end gap-2">
                            <span class="text-2xl font-black ${textColor} tracking-tight">${percent}%</span>
                            <div class="w-32 h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200/50 p-[2px] shadow-inner">
                                <div class="${barColor} h-full rounded-full transition-all duration-1000 ease-out relative" 
                                     style="width: 0%" 
                                     data-percent="${percent}%">
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">สถานะปัจจุบัน</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        function renderMiniBar(label, ratio, detail) {
            const p = Math.round(ratio * 100);
            let color = 'bg-blue-400';
            if (p >= 100) color = 'bg-emerald-400';
            else if (p > 0 && p < 100) color = 'bg-amber-400';
            else color = 'bg-slate-200';

            return `
                <div class="flex items-center gap-4">
                    <div class="w-32 flex-shrink-0">
                        <p class="text-[11px] font-bold text-slate-600 line-clamp-1">${label}</p>
                    </div>
                    <div class="flex-1 max-w-[200px]">
                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="${color} h-full rounded-full transition-all duration-1000" style="width: ${p}%"></div>
                        </div>
                    </div>
                    <div class="w-20 text-right">
                        <span class="text-[10px] font-black text-slate-400 tracking-tighter">${detail}</span>
                    </div>
                    <div class="w-8 flex justify-end">
                        ${p >= 100 ? '<i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-500"></i>' : 
                          p > 0 ? '<i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500 animate-pulse"></i>' : 
                          '<i data-lucide="circle" class="w-3.5 h-3.5 text-slate-200"></i>'}
                    </div>
                </div>
            `;
        }

        // Trigger animations
        setTimeout(() => {
            document.querySelectorAll('#gradingProgressTableBody [data-percent]').forEach(el => {
                el.style.width = el.getAttribute('data-percent');
            });
        }, 100);

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
</script>
