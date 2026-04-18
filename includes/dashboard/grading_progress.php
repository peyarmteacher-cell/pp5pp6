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

        <div class="relative overflow-hidden group">
            <div id="progress_loading_state" class="hidden absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-2xl">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-bold text-slate-600 animate-pulse">กำลังดึงข้อมูล...</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100">รายวิชา / ห้องเรียน</th>
                            <th class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100">ครูผู้สอน</th>
                            <th class="px-6 py-5 text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-100 w-1/3">ความคืบหน้าการบันทึกคะแนน</th>
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
    async function initGradingProgress() {
        console.log('Initializing Grading Progress Monitoring...');
        await loadGradingProgress();
    }

    async function loadGradingProgress() {
        const loading = document.getElementById('progress_loading_state');
        const empty = document.getElementById('progress_empty_state');
        const tbody = document.getElementById('gradingProgressTableBody');
        const level = document.getElementById('progress_level_filter').value;
        
        if (loading) loading.classList.remove('hidden');
        if (empty) empty.classList.add('hidden');
        
        try {
            const url = `api/admin/get_grading_progress.php?academic_year=${currentAcademicYear}&semester=${currentSemester}&level=${encodeURIComponent(level)}`;
            const res = await fetch(url);
            const data = await res.json();
            
            if (data.error) throw new Error(data.error);
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '';
                if (empty) empty.classList.remove('hidden');
                return;
            }

            tbody.innerHTML = data.map(item => {
                const total = parseInt(item.total_units) || 0;
                const completed = parseInt(item.completed_units) || 0;
                const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
                
                // Color logic based on percentage
                let barColor = 'bg-blue-500';
                let textColor = 'text-blue-600';
                let bgColor = 'bg-blue-50';
                
                if (percent >= 100) {
                    barColor = 'bg-emerald-500';
                    textColor = 'text-emerald-600';
                    bgColor = 'bg-emerald-50';
                } else if (percent > 0 && percent < 50) {
                    barColor = 'bg-amber-500';
                    textColor = 'text-amber-600';
                    bgColor = 'bg-amber-50';
                } else if (percent === 0) {
                    barColor = 'bg-slate-300';
                    textColor = 'text-slate-400';
                    bgColor = 'bg-slate-50';
                }

                return `
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1">${item.subject_code}</span>
                                <span class="text-sm font-bold text-slate-800 line-clamp-1">${item.subject_name}</span>
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-bold border border-slate-200">${item.subject_level}</span>
                                    ${item.room ? `<span class="px-2 py-0.5 bg-white text-indigo-500 rounded-md text-[10px] font-black border border-indigo-100 shadow-sm">/ ${item.room}</span>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                    ${item.teacher_name ? item.teacher_name.charAt(0) : '?'}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700">${item.teacher_name} ${item.teacher_last_name || ''}</p>
                                    <p class="text-[10px] text-slate-400 font-medium">ครูผู้รับผิดชอบ</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="space-y-2">
                                <div class="flex justify-between items-end">
                                    <span class="text-[10px] font-black tracking-widest ${textColor} uppercase">${completed} / ${total} หน่วยการเรียนรู้</span>
                                    <span class="text-sm font-black ${textColor}">${percent}%</span>
                                </div>
                                <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden border border-slate-200/50 p-[2px]">
                                    <div class="${barColor} h-full rounded-full shadow-lg transition-all duration-1000 ease-out" 
                                         style="width: 0%" 
                                         data-percent="${percent}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Trigger animations
            setTimeout(() => {
                document.querySelectorAll('#gradingProgressTableBody [data-percent]').forEach(el => {
                    el.style.width = el.getAttribute('data-percent');
                });
            }, 100);

            if (typeof lucide !== 'undefined') lucide.createIcons();
            
        } catch (e) {
            console.error('Error loading grading progress:', e);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + e.message);
        } finally {
            if (loading) loading.classList.add('hidden');
        }
    }
</script>
