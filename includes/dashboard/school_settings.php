<?php
// School Settings Section
?>
<div id="school-settings" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <i data-lucide="settings"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">ตั้งค่าโรงเรียนและโลโก้</h3>
        </div>

        <form id="schoolSettingsForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">ชื่อโรงเรียน</label>
                    <input type="text" id="setting_school_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">จังหวัด</label>
                    <input type="text" id="setting_school_province" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-slate-100">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">ชื่อผู้อำนวยการโรงเรียน</label>
                    <input type="text" id="setting_director_name" placeholder="เช่น นายสยาม เชียงเครือ" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">ชื่อหัวหน้างานวิชาการ / รองฯ วิชาการ</label>
                    <input type="text" id="setting_academic_head_name" placeholder="เช่น นางสาวสมศรี รักเรียน" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700">ตำแหน่งงานวิชาการ</label>
                    <select id="setting_academic_head_position" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <option value="หัวหน้างานวิชาการ">หัวหน้างานวิชาการ</option>
                        <option value="รองผู้อำนวยการฝ่ายวิชาการ">รองผู้อำนวยการฝ่ายวิชาการ</option>
                        <option value="ผู้ช่วยผู้อำนวยการฝ่ายวิชาการ">ผู้ช่วยผู้อำนวยการฝ่ายวิชาการ</option>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <label class="text-sm font-semibold text-slate-700">โลโก้โรงเรียน / โลโก้ สพฐ.</label>
                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div id="logo_preview_container" class="w-32 h-32 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50">
                        <img id="logo_preview" src="" alt="Logo Preview" class="max-w-full max-max-h-full object-contain hidden" referrerPolicy="no-referrer">
                        <div id="logo_placeholder" class="text-slate-400 text-center p-2">
                            <i data-lucide="image" class="w-8 h-8 mx-auto mb-1"></i>
                            <p class="text-[10px]">ยังไม่มีโลโก้</p>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div class="flex flex-col gap-3">
                            <div class="flex gap-2">
                                <input type="text" id="setting_logo_url" placeholder="URL รูปภาพโลโก้ (เช่น https://...)" class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                                <button type="button" onclick="previewLogo()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-semibold hover:bg-slate-200 transition-all cursor-pointer">Preview</button>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500">หรือ</span>
                                <input type="file" id="logo_file_input" accept="image/*" class="hidden" onchange="handleLogoUpload(this)">
                                <button type="button" onclick="document.getElementById('logo_file_input').click()" class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl font-semibold hover:bg-blue-100 transition-all cursor-pointer">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                    อัปโหลดไฟล์จากเครื่อง
                                </button>
                                <span id="upload_status" class="text-xs text-slate-500 italic"></span>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                            <h4 class="text-sm font-bold text-blue-800 mb-1">คำแนะนำขนาดโลโก้</h4>
                            <ul class="text-xs text-blue-700 space-y-1 list-disc ml-4">
                                <li>ควรใช้รูปภาพที่มีพื้นหลังโปร่งใส (PNG)</li>
                                <li>ขนาดที่แนะนำคือ 200 x 200 พิกเซล หรืออัตราส่วน 1:1</li>
                                <li>ขนาดไฟล์ไม่ควรเกิน 500KB เพื่อความรวดเร็วในการโหลดเอกสาร</li>
                                <li>หากใช้โลโก้ สพฐ. สามารถค้นหา URL รูปภาพมาตรฐานจากอินเทอร์เน็ตมาใส่ได้เลย</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all cursor-pointer">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>

    <!-- School Officials Management -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <i data-lucide="users"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">รายชื่อผู้บริหารและหัวหน้างาน</h3>
                    <p class="text-xs text-slate-500">จัดการรายชื่อที่จะปรากฏในลายเซ็นท้ายเอกสาร ปพ. ต่างๆ</p>
                </div>
            </div>
            <button onclick="openOfficialModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer shadow-md shadow-blue-600/10">
                <i data-lucide="plus" class="w-4 h-4"></i>
                เพิ่มรายชื่อ
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-100">
                        <th class="pb-3 font-medium text-xs uppercase tracking-wider">บทบาท</th>
                        <th class="pb-3 font-medium text-xs uppercase tracking-wider">ชื่อ-นามสกุล</th>
                        <th class="pb-3 font-medium text-xs uppercase tracking-wider">ตำแหน่ง</th>
                        <th class="pb-3 font-medium text-xs uppercase tracking-wider text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="officialsTableBody">
                    <!-- จะถูกเติมด้วย JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Official Modal -->
<div id="officialModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 id="officialModalTitle" class="text-xl font-bold text-slate-800">เพิ่มรายชื่อผู้บริหาร</h3>
            <button onclick="closeModal('officialModal')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <form id="officialForm" class="space-y-4">
            <input type="hidden" id="official_id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">บทบาทในเอกสาร</label>
                <select id="official_role_key" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all cursor-pointer">
                    <option value="director">ผู้อำนวยการโรงเรียน</option>
                    <option value="academic_head">หัวหน้างานวิชาการ</option>
                    <option value="deputy_academic">รองผู้อำนวยการฝ่ายวิชาการ</option>
                    <option value="assistant_academic">ผู้ช่วยผู้อำนวยการฝ่ายวิชาการ</option>
                    <option value="registrar">นายทะเบียน</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                <input type="text" id="official_name" required placeholder="เช่น นายสยาม เชียงเครือ" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">ตำแหน่งที่แสดงในเอกสาร</label>
                <input type="text" id="official_position" required placeholder="เช่น ผู้อำนวยการโรงเรียนบ้านหนองบัว" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('officialModal')" class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-semibold hover:bg-slate-50 transition-all cursor-pointer">ยกเลิก</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all cursor-pointer">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadSchoolSettings() {
        console.log('Loading school settings...');
        try {
            const res = await fetch('api/admin/get_school_info.php');
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('setting_school_name').value = data.school.name;
                document.getElementById('setting_school_province').value = data.school.province;
                document.getElementById('setting_logo_url').value = data.school.logo_url || '';
                document.getElementById('setting_director_name').value = data.school.director_name || '';
                document.getElementById('setting_academic_head_name').value = data.school.academic_head_name || '';
                document.getElementById('setting_academic_head_position').value = data.school.academic_head_position || 'หัวหน้างานวิชาการ';
                
                if (data.school.logo_url) {
                    const img = document.getElementById('logo_preview');
                    img.src = data.school.logo_url;
                    img.classList.remove('hidden');
                    document.getElementById('logo_placeholder').classList.add('hidden');
                }
            }
            
            // Load officials list
            loadSchoolOfficials();
        } catch (e) {
            console.error('Error loading school settings:', e);
        }
    }

    async function loadSchoolOfficials() {
        try {
            const res = await fetch('api/admin/get_school_officials.php');
            const officials = await res.json();
            const tbody = document.getElementById('officialsTableBody');
            if (!tbody) return;

            const roleLabels = {
                'director': 'ผู้อำนวยการ',
                'academic_head': 'หัวหน้าวิชาการ',
                'deputy_academic': 'รองฯ วิชาการ',
                'assistant_academic': 'ผู้ช่วยฯ วิชาการ',
                'registrar': 'นายทะเบียน'
            };

            if (officials.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="py-8 text-center text-slate-400 italic">ยังไม่มีข้อมูลรายชื่อผู้บริหาร</td></tr>';
                return;
            }

            tbody.innerHTML = officials.map(o => `
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-all group">
                    <td class="py-4">
                        <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase">
                            ${roleLabels[o.role_key] || o.role_key}
                        </span>
                    </td>
                    <td class="py-4 font-medium text-slate-800">${o.name}</td>
                    <td class="py-4 text-sm text-slate-500">${o.position}</td>
                    <td class="py-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                            <button onclick='editOfficial(${JSON.stringify(o).replace(/'/g, "&apos;")})' class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all cursor-pointer" title="แก้ไข">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteOfficial(${o.id})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-all cursor-pointer" title="ลบ">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (e) {
            console.error('Error loading officials:', e);
        }
    }

    function openOfficialModal(o = null) {
        const modal = document.getElementById('officialModal');
        const title = document.getElementById('officialModalTitle');
        const form = document.getElementById('officialForm');
        
        form.reset();
        document.getElementById('official_id').value = '';
        
        if (o) {
            title.innerText = 'แก้ไขรายชื่อผู้บริหาร';
            document.getElementById('official_id').value = o.id;
            document.getElementById('official_role_key').value = o.role_key;
            document.getElementById('official_name').value = o.name;
            document.getElementById('official_position').value = o.position;
        } else {
            title.innerText = 'เพิ่มรายชื่อผู้บริหาร';
        }
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function editOfficial(o) {
        openOfficialModal(o);
    }

    async function deleteOfficial(id) {
        if (!confirm('คุณต้องการลบรายชื่อนี้ใช่หรือไม่?')) return;
        
        try {
            const res = await fetch('api/admin/delete_school_official.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const result = await res.json();
            if (result.status === 'success') {
                loadSchoolOfficials();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error deleting official:', e);
        }
    }

    document.getElementById('officialForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            id: document.getElementById('official_id').value,
            role_key: document.getElementById('official_role_key').value,
            name: document.getElementById('official_name').value,
            position: document.getElementById('official_position').value
        };

        try {
            const res = await fetch('api/admin/save_school_official.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.status === 'success') {
                closeModal('officialModal');
                loadSchoolOfficials();
            } else {
                alert(result.error);
            }
        } catch (e) {
            console.error('Error saving official:', e);
        }
    });

    function previewLogo() {
        const url = document.getElementById('setting_logo_url').value;
        const img = document.getElementById('logo_preview');
        const placeholder = document.getElementById('logo_placeholder');
        
        if (url) {
            img.src = url;
            img.classList.remove('hidden');
            placeholder.classList.add('hidden');
        } else {
            img.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
    }

    async function handleLogoUpload(input) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        const status = document.getElementById('upload_status');
        const urlInput = document.getElementById('setting_logo_url');
        
        status.textContent = 'กำลังอัปโหลด...';
        status.className = 'text-xs text-blue-600 italic';
        
        const formData = new FormData();
        formData.append('logo', file);
        
        try {
            const res = await fetch('api/admin/upload_logo.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.status === 'success') {
                urlInput.value = result.url;
                previewLogo();
                status.textContent = 'อัปโหลดสำเร็จ!';
                status.className = 'text-xs text-green-600 italic';
            } else {
                status.textContent = result.error || 'อัปโหลดไม่สำเร็จ';
                status.className = 'text-xs text-red-600 italic';
            }
        } catch (e) {
            console.error('Upload error:', e);
            status.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
            status.className = 'text-xs text-red-600 italic';
        }
    }

    document.getElementById('schoolSettingsForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'กำลังบันทึก...';

        try {
            const res = await fetch('api/admin/update_school_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: document.getElementById('setting_school_name').value,
                    province: document.getElementById('setting_school_province').value,
                    logo_url: document.getElementById('setting_logo_url').value,
                    director_name: document.getElementById('setting_director_name').value,
                    academic_head_name: document.getElementById('setting_academic_head_name').value,
                    academic_head_position: document.getElementById('setting_academic_head_position').value
                })
            });
            const result = await res.json();
            if (result.status === 'success') {
                alert('บันทึกการตั้งค่าเรียบร้อยแล้ว');
                location.reload(); // Reload to update school name in header
            } else {
                alert(result.error || 'เกิดข้อผิดพลาด');
            }
        } catch (e) {
            console.error('Error saving school settings:', e);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    });
</script>
