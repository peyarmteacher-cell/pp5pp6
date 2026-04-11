<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารจัดการสถานศึกษา - Login / Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-[#0f172a] min-h-screen flex items-center justify-center p-4">

    <div id="app" class="w-full max-w-md">
        <!-- ส่วนหัว -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-900/40">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">ระบบวัดผล ปพ.</h2>
            <p class="text-slate-400" id="formTitle">เข้าสู่ระบบเพื่อจัดการข้อมูลการเรียน</p>
        </div>

        <!-- ฟอร์ม Login -->
        <div id="loginSection" class="bg-white rounded-3xl p-8 shadow-2xl transition-all">
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (เลขบัตรประชาชน)</label>
                    <input type="text" id="login_username" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกชื่อผู้ใช้งาน">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="login_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกรหัสผ่าน">
                </div>
                <div id="loginError" class="text-red-500 text-sm hidden"></div>
                <button type="submit" id="loginBtn" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all">เข้าสู่ระบบ</button>
                <div class="text-center pt-4">
                    <button type="button" onclick="toggleForm('check_school')" class="text-blue-600 font-medium hover:underline">สมัครขอใช้งานระบบ</button>
                </div>
            </form>
        </div>

        <!-- ฟอร์ม ตรวจสอบรหัสโรงเรียน -->
        <div id="schoolCheckSection" class="bg-white rounded-3xl p-8 shadow-2xl hidden transition-all">
            <h3 class="text-xl font-bold text-slate-800 mb-4">ตรวจสอบสิทธิ์การสมัคร</h3>
            <p class="text-sm text-slate-500 mb-6">กรุณากรอกรหัสโรงเรียน 8 หลัก เพื่อตรวจสอบข้อมูลในระบบก่อนสมัครสมาชิก</p>
            <form id="schoolCheckForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">รหัสโรงเรียน 8 หลัก</label>
                    <input type="text" id="check_school_code" maxlength="8" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกรหัสโรงเรียน 8 หลัก">
                </div>
                <div id="schoolCheckError" class="text-red-500 text-sm hidden"></div>
                <button type="submit" id="checkSchoolBtn" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all">ตรวจสอบรหัสโรงเรียน</button>
                <div class="text-center pt-4">
                    <button type="button" onclick="toggleForm('login')" class="text-slate-500 font-medium hover:underline">ยกเลิก</button>
                </div>
            </form>
        </div>

        <!-- ฟอร์ม Register -->
        <div id="registerSection" class="bg-white rounded-3xl p-8 shadow-2xl hidden transition-all">
            <div id="reg_school_name_display" class="mb-4 p-3 bg-blue-50 text-blue-700 rounded-xl text-sm font-semibold"></div>
            <form id="registerForm" class="space-y-4">
                <input type="hidden" id="reg_school_code">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">เลขบัตรประชาชน 13 หลัก</label>
                    <input type="text" id="reg_username" maxlength="13" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกเลขบัตรประชาชน">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" id="reg_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกชื่อ-นามสกุล">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ตำแหน่ง</label>
                    <select id="reg_position" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                        <option value="">เลือกตำแหน่ง</option>
                        <option value="ครูอัตราจ้าง">ครูอัตราจ้าง</option>
                        <option value="พนักงานราชการ">พนักงานราชการ</option>
                        <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                        <option value="ครู">ครู</option>
                        <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                        <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                        <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                        <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                        <option value="รองผู้อำนวยการโรงเรียน">รองผู้อำนวยการโรงเรียน</option>
                        <option value="ผู้อำนวยการโรงเรียน">ผู้อำนวยการโรงเรียน</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">กำหนดรหัสผ่าน</label>
                    <input type="password" id="reg_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all" placeholder="กรอกรหัสผ่าน">
                </div>
                <div id="registerError" class="text-red-500 text-sm hidden"></div>
                <div id="registerSuccess" class="text-green-500 text-sm hidden"></div>
                <button type="submit" id="registerBtn" class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all">ส่งข้อมูลสมัครสมาชิก</button>
                <div class="text-center pt-4">
                    <button type="button" onclick="toggleForm('login')" class="text-blue-600 font-medium hover:underline">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleForm(type) {
            const loginSection = document.getElementById('loginSection');
            const registerSection = document.getElementById('registerSection');
            const schoolCheckSection = document.getElementById('schoolCheckSection');
            const formTitle = document.getElementById('formTitle');
            
            loginSection.classList.add('hidden');
            registerSection.classList.add('hidden');
            schoolCheckSection.classList.add('hidden');

            if (type === 'login') {
                loginSection.classList.remove('hidden');
                formTitle.innerText = 'เข้าสู่ระบบเพื่อจัดการข้อมูลการเรียน';
            } else if (type === 'check_school') {
                schoolCheckSection.classList.remove('hidden');
                formTitle.innerText = 'ตรวจสอบรหัสโรงเรียน';
            } else if (type === 'register') {
                registerSection.classList.remove('hidden');
                formTitle.innerText = 'สมัครสมาชิก';
            }
        }

        // Check School Code Logic
        document.getElementById('schoolCheckForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = document.getElementById('check_school_code').value;
            const error = document.getElementById('schoolCheckError');
            const btn = document.getElementById('checkSchoolBtn');
            error.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = 'กำลังตรวจสอบ...';

            try {
                const res = await fetch(`api/check_school.php?code=${code}`);
                const data = await res.json();
                if (data.exists) {
                    document.getElementById('reg_school_code').value = code;
                    document.getElementById('reg_school_name_display').innerText = `โรงเรียน: ${data.name}`;
                    toggleForm('register');
                } else {
                    error.innerText = data.error || 'ไม่พบรหัสโรงเรียนนี้ในระบบ';
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.innerText = 'เกิดข้อผิดพลาดในการตรวจสอบ';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'ตรวจสอบรหัสโรงเรียน';
            }
        });

        // Login Logic
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const error = document.getElementById('loginError');
            const btn = document.getElementById('loginBtn');
            error.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = 'กำลังตรวจสอบ...';
            
            const username = document.getElementById('login_username').value;
            const password = document.getElementById('login_password').value;

            try {
                const res = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await res.json();
                if (res.ok) {
                    localStorage.setItem('user', JSON.stringify(data));
                    window.location.href = 'dashboard.php';
                } else {
                    error.innerText = data.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.innerText = 'ไม่สามารถเชื่อมต่อกับ API ได้';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ';
            }
        });

        // Register Logic
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const error = document.getElementById('registerError');
            const success = document.getElementById('registerSuccess');
            const btn = document.getElementById('registerBtn');
            error.classList.add('hidden');
            success.classList.add('hidden');
            btn.disabled = true;

            const payload = {
                school_code: document.getElementById('reg_school_code').value,
                username: document.getElementById('reg_username').value,
                name: document.getElementById('reg_name').value,
                position: document.getElementById('reg_position').value,
                password: document.getElementById('reg_password').value
            };

            try {
                const res = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    success.innerText = data.message;
                    success.classList.remove('hidden');
                    document.getElementById('registerForm').reset();
                    setTimeout(() => toggleForm('login'), 3000);
                } else {
                    error.innerText = data.error;
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.innerText = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
