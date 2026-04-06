<!-- Academic Management Section -->
<div id="academic-management" class="section hidden space-y-6">
    <!-- Academic Year Management -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">จัดการปีการศึกษา</h3>
            <button onclick="openModal('addYearModal')" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all">เพิ่มปีการศึกษา</button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="pb-3 font-medium">ปีการศึกษา</th>
                        <th class="pb-3 font-medium">สถานะ</th>
                        <th class="pb-3 font-medium text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="academicYearsTableBody">
                    <!-- จะถูกเติมด้วย JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Graduation Management -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold mb-4 text-slate-800">จัดการจบการศึกษา</h3>
        <p class="text-sm text-slate-500 mb-6">บันทึกการจบการศึกษาสำหรับนักเรียนชั้น ป.6 และ ม.3 เพื่อกำหนดรุ่นและเก็บเป็นประวัติ</p>
        
        <form id="graduationForm" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ระดับชั้นที่จบ</label>
                <select id="grad_level" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                    <option value="">เลือกระดับชั้น</option>
                    <option value="ป.6">ป.6</option>
                    <option value="ม.3">ม.3</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">รุ่นที่จบ (เช่น รุ่นที่ 50)</label>
                <input type="text" id="grad_generation" placeholder="ระบุรุ่น" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            <button type="submit" class="bg-amber-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-amber-700 transition-all h-[42px]">บันทึกการจบการศึกษา</button>
        </form>
    </div>
</div>

<!-- Add Academic Year Modal -->
<div id="addYearModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-xl font-bold mb-4 text-slate-800">เพิ่มปีการศึกษาใหม่</h3>
        <form id="addYearForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ปีการศึกษา (พ.ศ.)</label>
                <input type="text" id="new_academic_year" placeholder="เช่น 2568" required maxlength="4" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('addYearModal')" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-semibold hover:bg-slate-50 transition-all">ยกเลิก</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadAcademicYears() {
        try {
            const res = await fetch('api/academic/get_academic_years.php');
            const years = await res.json();
            const tbody = document.getElementById('academicYearsTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = years.map(y => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-4 font-medium text-slate-800">ปีการศึกษา ${y.year}</td>
                    <td class="py-4">
                        ${y.is_current ? 
                            '<span class="px-2 py-1 bg-green-100 text-green-600 text-xs font-bold rounded-full">ปัจจุบัน</span>' : 
                            '<span class="px-2 py-1 bg-slate-100 text-slate-400 text-xs font-bold rounded-full">ทั่วไป</span>'}
                    </td>
                    <td class="py-4 text-right">
                        ${!y.is_current ? 
                            `<button onclick="setCurrentYear(${y.id})" class="text-blue-600 hover:text-blue-800 text-xs font-bold cursor-pointer">ตั้งเป็นปีปัจจุบัน</button>` : 
                            '<span class="text-slate-300 text-xs font-bold">กำลังใช้งาน</span>'}
                    </td>
                </tr>
            `).join('');

            // Update dropdowns in other sections if they exist
            updateAcademicYearDropdowns(years);
        } catch (e) {
            console.error('Error loading academic years:', e);
        }
    }

    function updateAcademicYearDropdowns(years) {
        const dropdowns = ['std_academic_year', 'edit_std_academic_year', 'filter_academic_year', 'grade_academic_year', 'char_academic_year', 'anal_academic_year'];
        years.sort((a, b) => b.year - a.year);
        
        const currentYearObj = years.find(y => y.is_current);
        if (currentYearObj) {
            // Update global variables if they exist
            if (typeof currentAcademicYear !== 'undefined') currentAcademicYear = currentYearObj.year;
        }

        dropdowns.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const currentValue = el.value;
                el.innerHTML = years.map(y => `<option value="${y.year}" ${y.is_current ? 'selected' : ''}>ปีการศึกษา ${y.year}</option>`).join('');
                if (currentValue && years.some(y => y.year === currentValue)) {
                    el.value = currentValue;
                }
            }
        });
    }

    async function setCurrentYear(id) {
        if (!confirm('ยืนยันการเปลี่ยนปีการศึกษาปัจจุบัน?')) return;
        try {
            const res = await fetch('api/academic/set_current_year.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.message) {
                alert(result.message);
                loadAcademicYears();
                if (typeof loadStudents === 'function') loadStudents();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error setting current year:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const addYearForm = document.getElementById('addYearForm');
        if (addYearForm) {
            addYearForm.onsubmit = async (e) => {
                e.preventDefault();
                const year = document.getElementById('new_academic_year').value;
                try {
                    const res = await fetch('api/academic/add_academic_year.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ year })
                    });
                    const result = await res.json();
                    if (result.message) {
                        alert(result.message);
                        closeModal('addYearModal');
                        addYearForm.reset();
                        loadAcademicYears();
                    } else {
                        alert(result.error);
                    }
                } catch (e) {
                    console.error('Error adding academic year:', e);
                }
            };
        }

        const graduationForm = document.getElementById('graduationForm');
        if (graduationForm) {
            graduationForm.onsubmit = async (e) => {
                e.preventDefault();
                const level = document.getElementById('grad_level').value;
                const generation = document.getElementById('grad_generation').value;
                
                if (!confirm(`ยืนยันการจบการศึกษาสำหรับนักเรียนชั้น ${level} รุ่น ${generation}?\nการดำเนินการนี้จะเปลี่ยนสถานะนักเรียนเป็น "จบการศึกษา"`)) return;
                
                try {
                    const res = await fetch('api/academic/graduate_students.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ level, generation })
                    });
                    const result = await res.json();
                    if (result.message) {
                        alert(result.message);
                        graduationForm.reset();
                        if (typeof loadStudents === 'function') loadStudents();
                    } else {
                        alert(result.error);
                    }
                } catch (e) {
                    console.error('Error graduating students:', e);
                }
            };
        }

        // Initial load
        loadAcademicYears();
    });
</script>
