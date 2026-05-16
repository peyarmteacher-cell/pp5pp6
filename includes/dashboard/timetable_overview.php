<div id="timetable-overview" class="section hidden space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
        <div>
            <h3 class="text-xl font-black text-slate-800">สำรวจตารางสอนภาพรวม</h3>
            <p class="text-sm text-slate-500 font-medium">ดูตารางสอนรายครู หรือรายห้องเรียน</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select id="tt_view_type" onchange="toggleTTViewType()" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                <option value="teacher">ดูรายครู</option>
                <option value="classroom">ดูรายห้องเรียน</option>
            </select>
            <select id="tt_target_id" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 min-w-[200px]">
                <!-- Populated by JS -->
            </select>
            <button onclick="loadOverviewTimetable()" class="bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">
                แสดงข้อมูล
            </button>
        </div>
    </div>

    <!-- Timetable Display Area -->
    <div id="tt_overview_container" class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 hidden">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div id="tt_target_icon" class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="user" class="w-6 h-6"></i>
                </div>
                <div>
                    <h4 id="tt_target_name" class="text-lg font-black text-slate-800">-</h4>
                    <p id="tt_target_sub" class="text-xs text-slate-500 font-bold uppercase tracking-wider">-</p>
                </div>
            </div>
            <button onclick="printOverviewTimetable()" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-xs font-bold transition-all no-print shadow-sm border border-slate-200">
                <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ตารางสอน (PDF)
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-slate-200 table-fixed">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="p-3 border border-slate-200 text-xs font-bold w-20">วัน / คาบ</th>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <th class="p-3 border border-slate-200 text-xs font-bold">คาบที่ <?= $i ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody id="tt_overview_body">
                    <!-- Data rows injected here -->
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="tt_overview_empty" class="bg-white p-20 rounded-3xl shadow-sm border border-slate-200 flex flex-col items-center justify-center text-slate-300">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
            <i data-lucide="calendar" class="w-10 h-10"></i>
        </div>
        <p class="font-bold">กรุณาเลือก ครู/ห้องเรียน และกดปุ่มแสดงข้อมูล</p>
    </div>
</div>

<script>
    let overviewTeachers = [];
    let overviewClassrooms = [];

    async function initTimetableOverview() {
        console.log('Initializing Timetable Overview...');
        try {
            const [tRes, cRes] = await Promise.all([
                fetch('api/academic/get_teachers.php'),
                fetch('api/academic/get_classrooms.php')
            ]);
            overviewTeachers = await tRes.json();
            overviewClassrooms = await cRes.json();
            
            toggleTTViewType();
        } catch (e) {
            console.error('Error initTimetableOverview:', e);
        }
    }

    function toggleTTViewType() {
        const type = document.getElementById('tt_view_type').value;
        const targetSelect = document.getElementById('tt_target_id');
        
        if (type === 'teacher') {
            targetSelect.innerHTML = overviewTeachers.map(t => `<option value="${t.id}">${t.prefix}${t.name} ${t.last_name}</option>`).join('');
        } else {
            targetSelect.innerHTML = overviewClassrooms.map(c => `<option value="${c.id}">${c.level}/${c.room}</option>`).join('');
        }
    }

    async function loadOverviewTimetable() {
        const type = document.getElementById('tt_view_type').value;
        const targetId = document.getElementById('tt_target_id').value;
        const targetNameEl = document.getElementById('tt_target_name');
        const targetSubEl = document.getElementById('tt_target_sub');
        const container = document.getElementById('tt_overview_container');
        const empty = document.getElementById('tt_overview_empty');
        const targetIcon = document.getElementById('tt_target_icon');

        if (!targetId) return;

        try {
            let url = `api/academic/get_timetables.php?academic_year=${currentAcademicYear}&semester=${currentSemester}`;
            if (type === 'teacher') {
                url += `&teacher_id=${targetId}`;
                const t = overviewTeachers.find(i => i.id == targetId);
                targetNameEl.innerText = `${t.prefix}${t.name} ${t.last_name}`;
                targetSubEl.innerText = `ครูผู้สอน`;
                targetIcon.innerHTML = `<i data-lucide="user" class="w-6 h-6"></i>`;
            } else {
                url += `&classroom_id=${targetId}`;
                const c = overviewClassrooms.find(i => i.id == targetId);
                targetNameEl.innerText = `ห้อง ${c.level}/${c.room}`;
                targetSubEl.innerText = `ตารางสอนประจำชั้น`;
                targetIcon.innerHTML = `<i data-lucide="home" class="w-6 h-6"></i>`;
            }

            const res = await fetch(url);
            const data = await res.json();
            
            if (data.error) {
                alert(data.error);
                return;
            }

            renderOverviewTimetable(data);
            container.classList.remove('hidden');
            empty.classList.add('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (e) {
            console.error('Error loadOverviewTimetable:', e);
        }
    }

    function printOverviewTimetable() {
        const type = document.getElementById('tt_view_type').value;
        const targetId = document.getElementById('tt_target_id').value;
        if (!targetId) return;
        
        window.open(`api/teacher/print_timetable.php?academic_year=${currentAcademicYear}&semester=${currentSemester}&target_type=${type}&target_id=${targetId}`, '_blank');
    }

    function renderOverviewTimetable(timetable) {
        const tbody = document.getElementById('tt_overview_body');
        const days = [
            { id: 1, name: 'จันทร์', class: 'bg-yellow-50 text-yellow-700' },
            { id: 2, name: 'อังคาร', class: 'bg-pink-50 text-pink-700' },
            { id: 3, name: 'พุธ', class: 'bg-emerald-50 text-emerald-700' },
            { id: 4, name: 'พฤหัสบดี', class: 'bg-orange-50 text-orange-700' },
            { id: 5, name: 'ศุกร์', class: 'bg-sky-50 text-sky-700' }
        ];

        const getSubjectColor = (slot) => {
            if (!slot) return 'bg-white border-slate-200';
            if (slot.activity_type) {
                const actKey = slot.activity_type.toLowerCase();
                const actColors = {
                    'lunch': 'bg-orange-50 text-orange-700 border-orange-100',
                    'scouts': 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    'scout': 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    'club': 'bg-purple-50 text-purple-700 border-purple-100',
                    'homeroom': 'bg-indigo-50 text-indigo-700 border-indigo-100',
                    'guidance': 'bg-sky-50 text-sky-700 border-sky-100'
                };
                return actColors[actKey] || 'bg-slate-50 text-slate-700 border-slate-100';
            }
            
            const palette = [
                'bg-blue-50 text-blue-700 border-blue-100',
                'bg-indigo-50 text-indigo-700 border-indigo-100',
                'bg-cyan-50 text-cyan-700 border-cyan-100',
                'bg-teal-50 text-teal-700 border-teal-100',
                'bg-emerald-50 text-emerald-700 border-emerald-100',
                'bg-violet-50 text-violet-700 border-violet-100',
                'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-100',
                'bg-pink-50 text-pink-700 border-pink-100',
                'bg-rose-50 text-rose-700 border-rose-100',
                'bg-amber-50 text-amber-700 border-amber-100',
                'bg-orange-50 text-orange-700 border-orange-100',
                'bg-lime-50 text-lime-700 border-lime-100',
                'bg-sky-50 text-sky-700 border-sky-100',
                'bg-yellow-50 text-yellow-700 border-yellow-100',
                'bg-slate-100 text-slate-700 border-slate-200'
            ];
            
            const code = slot.subject_code || '';
            let hash = 0;
            for (let i = 0; i < code.length; i++) {
                hash = ((hash << 5) - hash) + code.charCodeAt(i);
                hash |= 0;
            }
            const index = Math.abs(hash) % palette.length;
            return palette[index];
        };

        tbody.innerHTML = days.map(day => `
            <tr>
                <td class="p-3 border border-slate-200 font-bold text-xs text-center ${day.class}">${day.name}</td>
                ${Array.from({length: 8}, (_, i) => i + 1).map(period => {
                    const slot = timetable.find(t => t.day_of_week == day.id && t.period_number == period);
                    const colorClass = getSubjectColor(slot);
                    
                    const isActivity = slot && !!slot.activity_type;
                    const singleLineActs = ['scouts', 'scout', 'club', 'guidance'];
                    const isSingleLine = isActivity && singleLineActs.includes(slot.activity_type.toLowerCase());
                    
                    const viewType = document.getElementById('tt_view_type').value;

                    return `
                        <td class="p-2 border transition-all text-center relative group min-h-[85px] ${colorClass}">
                            <div class="h-full w-full min-h-[65px] flex flex-col justify-center gap-0.5">
                                ${slot ? (isSingleLine ? `
                                    <div class="text-[11px] font-bold leading-none">${slot.subject_code}</div>
                                ` : `
                                    <div class="text-[10px] font-bold leading-none">${slot.subject_code || 'กิจกรรม'}</div>
                                    <div class="text-[9px] opacity-80 truncate leading-none min-h-[12px]">${slot.subject_name || ''}</div>
                                    <div class="text-[9px] font-bold opacity-60 leading-none min-h-[12px]">
                                        ${viewType === 'teacher' ? `${slot.level}/${slot.room}` : `${slot.teacher_prefix}${slot.teacher_name}`}
                                    </div>
                                `) : '<span class="text-[10px] text-slate-300 italic">ว่าง</span>'}
                            </div>
                        </td>
                    `;
                }).join('')}
            </tr>
        `).join('');
    }
</script>
