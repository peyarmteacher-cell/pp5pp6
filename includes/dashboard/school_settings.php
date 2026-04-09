<?php
// School Settings Section
?>
<div id="school-settings" class="section hidden space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <i class="lucide-settings"></i>
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

            <div class="space-y-4">
                <label class="text-sm font-semibold text-slate-700">โลโก้โรงเรียน / โลโก้ สพฐ.</label>
                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div id="logo_preview_container" class="w-32 h-32 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50">
                        <img id="logo_preview" src="" alt="Logo Preview" class="max-w-full max-max-h-full object-contain hidden" referrerPolicy="no-referrer">
                        <div id="logo_placeholder" class="text-slate-400 text-center p-2">
                            <i class="lucide-image text-2xl mb-1"></i>
                            <p class="text-[10px]">ยังไม่มีโลโก้</p>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div class="flex gap-2">
                            <input type="text" id="setting_logo_url" placeholder="URL รูปภาพโลโก้ (เช่น https://...)" class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none">
                            <button type="button" onclick="previewLogo()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-semibold hover:bg-slate-200 transition-all cursor-pointer">Preview</button>
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
</div>

<script>
    async function loadSchoolSettings() {
        try {
            const res = await fetch('api/admin/get_school_info.php');
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('setting_school_name').value = data.school.name;
                document.getElementById('setting_school_province').value = data.school.province;
                document.getElementById('setting_logo_url').value = data.school.logo_url || '';
                
                if (data.school.logo_url) {
                    const img = document.getElementById('logo_preview');
                    img.src = data.school.logo_url;
                    img.classList.remove('hidden');
                    document.getElementById('logo_placeholder').classList.add('hidden');
                }
            }
        } catch (e) {
            console.error('Error loading school settings:', e);
        }
    }

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
                    logo_url: document.getElementById('setting_logo_url').value
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
