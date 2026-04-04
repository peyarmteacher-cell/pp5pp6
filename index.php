<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารจัดการสถานศึกษา - Login</title>
    <!-- ใช้ Tailwind CSS แบบ CDN เพื่อความง่ายและรวดเร็ว -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] min-h-screen flex items-center justify-center p-4">

    <div id="app" class="w-full max-w-md">
        <!-- ส่วนหัว -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-blue-900/40">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">ระบบวัดผล ปพ.</h2>
            <p class="text-slate-400">เข้าสู่ระบบเพื่อจัดการข้อมูลการเรียน</p>
        </div>

        <!-- ฟอร์ม Login -->
        <div class="bg-white rounded-3xl p-8 shadow-2xl">
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (เลขบัตรประชาชน)</label>
                    <input 
                        type="text" 
                        id="username"
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                        placeholder="กรอกชื่อผู้ใช้งาน"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน</label>
                    <input 
                        type="password" 
                        id="password"
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                        placeholder="กรอกรหัสผ่าน"
                    >
                </div>
                <div id="errorMessage" class="text-red-500 text-sm hidden"></div>
                
                <button type="submit" id="loginBtn" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all flex items-center justify-center">
                    <span>เข้าสู่ระบบ</span>
                </button>
                
                <div class="text-center pt-4">
                    <button type="button" class="text-blue-600 font-medium hover:underline">
                        สมัครขอใช้งานระบบ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMessage.classList.add('hidden');
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span>กำลังตรวจสอบ...</span>';

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                // เรียกใช้ API (ในที่นี้ระบบจะส่งไปที่ Node.js ชั่วคราวเพื่อ Preview)
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (response.ok) {
                    alert('เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ ' + data.name);
                    // เก็บข้อมูลผู้ใช้ใน LocalStorage
                    localStorage.setItem('user', JSON.stringify(data));
                    // ไปที่หน้า Dashboard (จะสร้างต่อไป)
                    // window.location.href = 'dashboard.php';
                } else {
                    errorMessage.innerText = data.error || 'เกิดข้อผิดพลาด';
                    errorMessage.classList.remove('hidden');
                }
            } catch (err) {
                errorMessage.innerText = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                errorMessage.classList.remove('hidden');
            } finally {
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<span>เข้าสู่ระบบ</span>';
            }
        });
    </script>
</body>
</html>
