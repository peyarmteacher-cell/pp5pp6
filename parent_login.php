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
    <meta name="theme-color" content="#f59e0b">
    <link rel="apple-touch-icon" href="<?= $app_logo ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            font-family: 'Sarabun', sans-serif;
            background-color: #0c0a09;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(249, 115, 22, 0.1) 0%, transparent 50%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .glow-border:focus-within {
            border-color: rgba(245, 158, 11, 0.5);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }
        .animate-subtle-glow {
            animation: subtle-glow 3s ease-in-out infinite alternate;
        }
        @keyframes subtle-glow {
            from { filter: drop-shadow(0 0 5px rgba(245, 158, 11, 0.2)); }
            to { filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.5)); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 relative overflow-x-hidden">
    <!-- Decorative Blurs -->
    <div class="absolute top-1/4 -left-32 w-96 h-96 bg-orange-600/10 rounded-full blur-[128px]"></div>
    <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-amber-500/10 rounded-full blur-[128px]"></div>
    
    <div class="w-full max-w-sm space-y-10 relative z-10">
        <div class="text-center space-y-3">
            <div class="w-24 h-24 bg-gradient-to-br from-orange-400 to-amber-600 rounded-[2.5rem] mx-auto flex items-center justify-center shadow-2xl shadow-orange-500/40 mb-6 group transition-all hover:scale-105 hover:rotate-3 animate-subtle-glow">
                <i data-lucide="user" class="w-12 h-12 text-white"></i>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">ระบบติดตามนักเรียน</h1>
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20">
                <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                <p class="text-orange-400 font-bold text-xs">สำหรับผู้ปกครองและนักเรียน</p>
            </div>
        </div>

        <div class="glass-card p-8 rounded-[2.5rem] space-y-8">
            <form id="loginForm" class="space-y-6">
                <div class="space-y-2.5">
                    <label class="block text-xs font-black text-orange-500/80 uppercase tracking-widest ml-1">เลขบัตรประชาชนนักเรียน</label>
                    <div class="relative group glow-border border border-white/10 rounded-2xl transition-all">
                        <i data-lucide="credit-card" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-orange-400 transition-colors"></i>
                        <input type="text" id="national_id" inputmode="numeric" placeholder="ตัวเลข 13 หลัก" required 
                            class="w-full pl-12 pr-4 py-4 bg-transparent outline-none text-white font-bold tracking-widest text-lg placeholder:text-white/10">
                    </div>
                </div>

                <div class="space-y-2.5">
                    <label class="block text-xs font-black text-orange-500/80 uppercase tracking-widest ml-1">รหัสนักเรียน</label>
                    <div class="relative group glow-border border border-white/10 rounded-2xl transition-all">
                        <i data-lucide="key-round" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-orange-400 transition-colors"></i>
                        <input type="text" id="student_code" inputmode="numeric" placeholder="รหัสประจำตัวนักเรียน" required
                            class="w-full pl-12 pr-4 py-4 bg-transparent outline-none text-white font-bold tracking-widest text-lg placeholder:text-white/10">
                    </div>
                </div>

                <button type="submit" id="loginBtn" class="w-full bg-gradient-to-r from-orange-500 via-amber-500 to-orange-400 text-black py-4 rounded-2xl font-black text-lg hover:brightness-110 shadow-2xl shadow-orange-500/25 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                    เข้าสู่ระบบ
                    <i data-lucide="arrow-right" class="w-6 h-6"></i>
                </button>
            </form>
            
            <div id="errorMsg" class="hidden p-4 bg-red-500/10 text-red-400 rounded-2xl text-center text-sm font-bold border border-red-500/20"></div>
        </div>

        <div class="text-center">
            <p class="text-xs text-white/20 font-bold mb-8">โดย ครูสยาม เชียงเครือ</p>
            <button id="installBtn" class="hidden mx-auto flex items-center gap-3 px-8 py-3 bg-white/5 border border-white/10 rounded-full text-orange-400 font-black text-xs shadow-sm hover:bg-white/10 transition-all cursor-pointer">
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
