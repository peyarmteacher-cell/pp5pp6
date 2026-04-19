<?php
session_start();
if (isset($_SESSION['parent_logged_in'])) {
    header('Location: parent_dashboard.php');
    exit;
}
require_once 'api/config.php';

$app_name = 'ระบบติดตามนักเรียนสำหรับผู้ปกครอง';
$app_logo = 'https://picsum.photos/seed/school/192/192';

try {
    $stmt_app = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt_app->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($settings['app_name'])) $app_name = $settings['app_name'];
    if (isset($settings['app_logo'])) $app_logo = $settings['app_logo'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เข้าสู่ระบบ - <?= $app_name ?></title>
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="<?= $app_logo ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4">
    
    <div class="w-full max-w-sm space-y-8">
        <div class="text-center space-y-2">
            <div class="w-20 h-20 bg-blue-600 rounded-3xl mx-auto flex items-center justify-center shadow-xl shadow-blue-500/20 mb-4 animate-bounce">
                <i data-lucide="graduation-cap" class="w-10 h-10 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">ระบบติดตามนักเรียน</h1>
            <p class="text-slate-500 text-sm">สำหรับผู้ปกครองเข้าดูข้อมูลการเรียน</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-slate-100 space-y-6">
            <form id="loginForm" class="space-y-4">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">เลขบัตรประชาชนนักเรียน</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                        <input type="text" id="national_id" inputmode="numeric" placeholder="ตัวเลข 13 หลัก" required 
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-4 focus:ring-blue-500/10 transition-all text-lg font-medium tracking-wider">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700 ml-1">รหัสนักเรียน</label>
                    <div class="relative">
                        <i data-lucide="key-round" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                        <input type="text" id="student_code" inputmode="numeric" placeholder="รหัสประจำตัวนักเรียน" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-4 focus:ring-blue-500/10 transition-all text-lg font-medium tracking-wider">
                    </div>
                </div>

                <button type="submit" id="loginBtn" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-lg hover:bg-blue-700 shadow-lg shadow-blue-600/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                    เข้าสู่ระบบ
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
            </form>
            
            <div id="errorMsg" class="hidden p-3 bg-red-50 text-red-600 rounded-xl text-center text-sm font-medium border border-red-100"></div>
        </div>

        <div class="text-center mt-8">
            <p class="text-xs text-slate-400 mb-4">โดย ครูสยาม เชียงเครือ</p>
            <button id="installBtn" class="hidden mx-auto flex items-center gap-2 px-6 py-2.5 bg-white border border-slate-200 rounded-full text-blue-600 font-bold text-sm shadow-sm hover:bg-blue-50 transition-all cursor-pointer">
                <i data-lucide="download" class="w-4 h-4"></i>
                ติดตั้งแอปไว้บนมือถือ
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const errorMsg = document.getElementById('errorMsg');

        // ตรวจสอบข้อมูลที่บันทึกไว้ (Auto-fill)
        window.onload = () => {
             const savedId = localStorage.getItem('parent_national_id');
             const savedCode = localStorage.getItem('parent_student_code');
             if (savedId) document.getElementById('national_id').value = savedId;
             if (savedCode) document.getElementById('student_code').value = savedCode;
        };

        form.onsubmit = async (e) => {
            e.preventDefault();
            const nid = document.getElementById('national_id').value;
            const scode = document.getElementById('student_code').value;

            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> กำลังตรวจสอบ...';
            lucide.createIcons();
            errorMsg.classList.add('hidden');

            try {
                const res = await fetch('api/parent/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ national_id: nid, student_code: scode })
                });
                const result = await res.json();

                if (result.success) {
                    // บันทึกข้อมูลไว้ใช้ครั้งหน้า
                    localStorage.setItem('parent_national_id', nid);
                    localStorage.setItem('parent_student_code', scode);
                    window.location.href = 'parent_dashboard.php';
                } else {
                    errorMsg.innerText = result.message;
                    errorMsg.classList.remove('hidden');
                }
            } catch (err) {
                errorMsg.innerText = 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่';
                errorMsg.classList.remove('hidden');
            } finally {
                loginBtn.disabled = false;
                loginBtn.innerHTML = 'เข้าสู่ระบบ <i data-lucide="arrow-right" class="w-5 h-5"></i>';
                lucide.createIcons();
            }
        };

        // ระบบติดตั้ง PWA
        let deferredPrompt;
        const installBtn = document.getElementById('installBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBtn.classList.remove('hidden');
        });

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBtn.classList.add('hidden');
                }
                deferredPrompt = null;
            }
        });
    </script>
</body>
</html>
