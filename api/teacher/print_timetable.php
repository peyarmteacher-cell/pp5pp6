<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$current_user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin' || ($_SESSION['is_academic'] ?? false));

$target_id = $_GET['target_id'] ?? $current_user_id;
$target_type = $_GET['target_type'] ?? 'teacher'; // 'teacher' or 'classroom'

// Security check: If not admin, can only print own timetable
if (!$is_admin && $target_id != $current_user_id) {
    die("Unauthorized access to other's timetable");
}

$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

try {
    $teacher_full_name = "";
    $teacher_position = "";
    $classroom_name = "";
    $display_title = "";
    $school_id = $_SESSION['school_id'];
    
    if ($target_type === 'teacher') {
        // Get Teacher Info
        $stmt = $pdo->prepare("SELECT name, last_name, position, school_id FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $teacher = $stmt->fetch();
        $teacher_full_name = ($teacher['name'] ?? '') . ' ' . ($teacher['last_name'] ?? '');
        $teacher_position = $teacher['position'] ?: 'ครู';
        $display_title = "คุณครู" . $teacher_full_name;
        $school_id = $teacher['school_id'];
        
        $where = "t.teacher_id = ?";
        $params = [$target_id, $academic_year, $semester];
    } else {
        // Get Classroom Info
        $stmt = $pdo->prepare("SELECT level, room, school_id FROM classrooms WHERE id = ?");
        $stmt->execute([$target_id]);
        $classroom = $stmt->fetch();
        $classroom_name = $classroom['level'] . '/' . $classroom['room'];
        $display_title = "ห้องเรียนชั้นประถมศึกษาปีที่ " . $classroom_name;
        $school_id = $classroom['school_id'];
        
        $where = "t.classroom_id = ?";
        $params = [$target_id, $academic_year, $semester];
    }

    // Get School Info
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    
    // Fix Logo URL
    $logo_url = $school['logo_url'] ?? '';
    if ($logo_url && !preg_match('/^https?:\/\//', $logo_url)) {
        $logo_url = '../../' . $logo_url;
    }

    // Get Timetable
    $stmt = $pdo->prepare("
        SELECT t.*, 
               s.name as subject_name, s.code as subject_code, 
               c.level, c.room,
               u.name as teacher_name, u.last_name as teacher_last_name
        FROM timetables t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN classrooms c ON t.classroom_id = c.id
        LEFT JOIN users u ON t.teacher_id = u.id
        WHERE $where AND t.academic_year = ? AND t.semester = ?
        ORDER BY t.day_of_week ASC, t.period_number ASC
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $timetable = [];
    foreach ($items as $it) {
        if (!empty($it['activity_type'])) {
            $activities = [
                'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
                'scouts' => ['name' => 'กิจกรรมลูกเสือเนตรนารี', 'code' => 'ลูกเสือเนตรนารี'],
                'scout' => ['name' => 'กิจกรรมลูกเสือเนตรนารี', 'code' => 'ลูกเสือเนตรนารี'],
                'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
                'social' => ['name' => 'กิจกรรมเพื่อสังคมฯ', 'code' => 'สังคมฯ'],
                'lunch' => ['name' => 'พักรับประทานอาหาร', 'code' => 'พักกลางวัน'],
                'homeroom' => ['name' => 'Home Room', 'code' => 'โฮมรูม']
            ];
            $act = $activities[strtolower($it['activity_type'])] ?? null;
            if ($act) {
                $it['subject_name'] = $act['name'];
                $it['subject_code'] = $act['code'];
            }
        }
        $timetable[$it['day_of_week']][$it['period_number']] = $it;
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$days = [
    1 => ['name' => 'จันทร์', 'color' => '#fefce8'],
    2 => ['name' => 'อังคาร', 'color' => '#fdf2f8'],
    3 => ['name' => 'พุธ', 'color' => '#f0fdf4'],
    4 => ['name' => 'พฤหัสบดี', 'color' => '#fff7ed'],
    5 => ['name' => 'ศุกร์', 'color' => '#eff6ff']
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตารางสอน - <?= $display_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }
            body { 
                -webkit-print-color-adjust: exact; 
                padding: 0; 
                background: white !important;
            }
            .a4-landscape {
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none !important;
                border: none !important;
            }
            .no-print { display: none; }
        }
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8fafc;
        }
        .a4-landscape {
            width: 297mm;
            margin: 0 auto;
            background: white;
            padding: 0.8cm;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1.2px solid #64748b; padding: 2px; text-align: center; height: 50px; overflow: hidden; }
        th { background: #f1f5f9; font-weight: 700; color: #1e293b; font-size: 13px; height: 35px; }
        .period-header { font-size: 10px; color: #64748b; font-weight: normal; margin-top: 1px; }
    </style>
</head>
<body class="p-4">
    <div class="no-print mb-8 text-center text-slate-500">
        <p class="mb-2 text-sm italic">แนะนำ: ตั้งค่า "Margins" เป็น "None" หรือ "Minimum" ในหน้าต่างจัดการงานพิมพ์ เพื่อผลลัพธ์ที่ดีที่สุด</p>
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all cursor-pointer inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            สั่งพิมพ์ตารางสอน
        </button>
    </div>

    <div class="a4-landscape">
        <div class="relative mb-2 pb-2 border-b-2 border-slate-100 flex items-center min-h-[70px]">
            <!-- Logo positioned absolutely to keep text centered -->
            <div class="absolute left-0">
                <?php if (!empty($logo_url)): ?>
                    <img src="<?= $logo_url ?>" class="w-20 h-20 object-contain" referrerPolicy="no-referrer">
                <?php else: ?>
                    <div class="w-20 h-20 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center text-slate-300 text-[10px] text-center p-2 uppercase">Logo</div>
                <?php endif; ?>
            </div>
            
            <!-- Centered Header Text -->
            <div class="w-full text-center px-32">
                <h1 class="text-xl font-black text-slate-800 tracking-tight">ตารางสอนโรงเรียน<?= $school['name'] ?></h1>
                <div class="mt-0.5">
                    <p class="text-base font-bold text-blue-700"><?= $display_title ?> <?= $target_type === 'teacher' ? 'ตำแหน่ง: ' . $teacher_position : '' ?></p>
                </div>
                <div class="flex items-center justify-center gap-3 mt-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    <span class="bg-slate-100 px-3 py-0.5 rounded-full border border-slate-200">ปีการศึกษา <?= $academic_year ?></span>
                    <span class="bg-indigo-50 text-indigo-600 px-3 py-0.5 rounded-full border border-indigo-100">ภาคเรียนที่ <?= $semester ?></span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">วัน / คาบ</th>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <th>
                                <div>คาบที่ <?= $i ?></div>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($days as $dayId => $day): ?>
                    <tr>
                        <td style="background: <?= $day['color'] ?>; font-weight: bold; border-left: 5px solid #94a3b8;"><?= $day['name'] ?></td>
                        <?php for($p=1; $p<=8; $p++): 
                                $slot = $timetable[$dayId][$p] ?? null;
                                $isLunch = ($slot && isset($slot['activity_type']) && $slot['activity_type'] === 'lunch');
                        ?>
                            <td class="<?= $isLunch ? 'bg-orange-50' : '' ?>">
                                <?php if($slot): 
                                    $isActivity = !empty($slot['activity_type']);
                                    $singleLineActs = ['scouts', 'scout', 'club', 'guidance'];
                                    $isSingleLine = $isActivity && in_array(strtolower($slot['activity_type']), $singleLineActs);
                                ?>
                                    <?php if($isSingleLine): ?>
                                        <div class="text-[12px] font-bold text-blue-700 leading-tight"><?= $slot['subject_code'] ?></div>
                                    <?php else: ?>
                                        <div class="text-[12px] font-bold <?= $isLunch ? 'text-orange-700' : 'text-blue-700' ?> leading-tight"><?= $slot['subject_code'] ?></div>
                                        <div class="text-[10px] my-0.5"><?= $slot['subject_name'] ?></div>
                                        <?php if($target_type === 'teacher'): ?>
                                            <?php if(!$isActivity && $slot['level']): ?>
                                                <div class="text-[10px] font-bold text-slate-500 italic"><?= $slot['level'] ?>/<?= $slot['room'] ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-[10px] font-bold text-slate-500 italic"><?= ($slot['teacher_name'] ?? '') ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-10">
            <div class="text-center">
                <?php if($target_type === 'teacher'): ?>
                    <p class="mb-4">ลงชื่อ..........................................................</p>
                    <p class="font-bold text-sm">( <?= $teacher_full_name ?> )</p>
                    <p class="text-xs">ครูผู้สอน</p>
                <?php else: ?>
                    <p class="mb-4">ลงชื่อ..........................................................</p>
                    <p class="font-bold text-sm">( <?= $school['director_name'] ?: '..........................................................' ?> )</p>
                    <p class="text-xs">ผู้อำนวยการโรงเรียน</p>
                <?php endif; ?>
            </div>
            <div class="text-center">
                <p class="mb-4">ลงชื่อ..........................................................</p>
                <p class="font-bold text-sm">( <?= $school['director_name'] ?: '..........................................................' ?> )</p>
                <p class="text-xs">ผู้อำนวยการโรงเรียน<?= $school['name'] ?></p>
            </div>
        </div>

        <div class="absolute bottom-4 right-8 text-[8px] text-slate-400">
            พิมพ์เมื่อ: <?= date('d/m/Y H:i') ?> | ระบบบริหารงานวิชาการดิจิทัล
        </div>
    </div>
</body>
</html>
