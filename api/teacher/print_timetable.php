<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$teacher_id = $_SESSION['user_id'];
$academic_year = $_GET['academic_year'] ?? '2567';
$semester = $_GET['semester'] ?? 1;

try {
    // Get Teacher Info
    $stmt = $pdo->prepare("SELECT name, last_name, position, school_id FROM users WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    $teacher_full_name = ($teacher['name'] ?? '') . ' ' . ($teacher['last_name'] ?? '');

    // Get School Info
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$teacher['school_id']]);
    $school = $stmt->fetch();

    // Get Timetable
    $stmt = $pdo->prepare('
        SELECT t.*, 
               s.name as subject_name, s.code as subject_code, 
               c.level, c.room
        FROM timetables t
        LEFT JOIN subjects s ON t.subject_id = s.id
        JOIN classrooms c ON t.classroom_id = c.id
        WHERE t.teacher_id = ? AND t.academic_year = ? AND t.semester = ?
        ORDER BY t.day_of_week ASC, t.period_number ASC
    ');
    $stmt->execute([$teacher_id, $academic_year, $semester]);
    $items = $stmt->fetchAll();

    $timetable = [];
    foreach ($items as $it) {
        if ($it['activity_type']) {
            $activities = [
                'guidance' => ['name' => 'กิจกรรมแนะแนว', 'code' => 'แนะแนว'],
                'scouts' => ['name' => 'กิจกรรมลูกเสือ/เนตรนารี', 'code' => 'ลูกเสือ'],
                'club' => ['name' => 'กิจกรรมชุมนุม', 'code' => 'ชุมนุม'],
                'social' => ['name' => 'กิจกรรมเพื่อสังคมฯ', 'code' => 'สังคมฯ'],
                'lunch' => ['name' => 'พักกลางวัน', 'code' => 'พักกลางวัน']
            ];
            $act = $activities[$it['activity_type']] ?? null;
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
    <title>ตารางสอน - <?= $teacher_full_name ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8fafc;
        }
        .a4-landscape {
            width: 297mm;
            height: 210mm;
            margin: 0 auto;
            background: white;
            padding: 1.5cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        @media print {
            .a4-landscape {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1.5px solid #64748b; padding: 6px; text-align: center; }
        th { background: #f1f5f9; font-weight: 700; color: #1e293b; font-size: 14px; }
        .period-header { font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px; }
    </style>
</head>
<body class="p-8">
    <div class="no-print mb-8 text-center">
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all cursor-pointer inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            สั่งพิมพ์หน้านี้ (A4 แนวนอน)
        </button>
    </div>

    <div class="a4-landscape shadow-xl">
        <div class="flex items-center justify-between mb-8 pb-4 border-b-2 border-slate-100">
            <?php if ($school['logo_url']): ?>
                <img src="<?= $school['logo_url'] ?>" class="w-24 h-24 object-contain" referrerPolicy="no-referrer">
            <?php else: ?>
                <div class="w-24 h-24"></div>
            <?php endif; ?>
            
            <div class="text-center flex-1">
                <h1 class="text-2xl font-bold text-slate-800 uppercase tracking-tight">ตารางสอนรายบุคคล</h1>
                <p class="text-lg font-medium text-slate-600 mt-1">ปีการศึกษา <?= $academic_year ?> ภาคเรียนที่ <?= $semester ?></p>
                <h2 class="text-xl font-bold text-blue-700 mt-2"><?= $teacher_full_name ?></h2>
                <p class="text-sm font-bold text-slate-500"><?= $school['name'] ?></p>
            </div>

            <div class="w-24 h-24"></div> <!-- Spacer to center the content -->
        </div>

        <div class="flex-1 overflow-hidden">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">วัน / คาบ</th>
                        <?php for($i=1; $i<=10; $i++): ?>
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
                        <?php for($p=1; $p<=10; $p++): 
                                $slot = $timetable[$dayId][$p] ?? null;
                                $isLunch = ($slot && isset($slot['activity_type']) && $slot['activity_type'] === 'lunch');
                        ?>
                            <td class="<?= $isLunch ? 'bg-orange-50' : '' ?>">
                                <?php if($slot): ?>
                                    <div class="text-[12px] font-bold text-blue-700 leading-tight"><?= $slot['subject_code'] ?></div>
                                    <div class="text-[10px] text-slate-800 my-0.5"><?= $slot['subject_name'] ?></div>
                                    <?php if(!$isLunch): ?>
                                        <div class="text-[10px] font-bold text-slate-500 italic">ป.<?= $slot['level'] ?>/<?= $slot['room'] ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-20">
            <div class="text-center">
                <p class="mb-8">ลงชื่อ..........................................................</p>
                <p class="font-bold">( <?= $teacher_full_name ?> )</p>
                <p class="text-sm">ครูผู้สอน</p>
            </div>
            <div class="text-center">
                <p class="mb-8">ลงชื่อ..........................................................</p>
                <p class="font-bold">( <?= $school['director_name'] ?: '..........................................................' ?> )</p>
                <p class="text-sm">ผู้อำนวยการโรงเรียน<?= $school['name'] ?></p>
            </div>
        </div>

        <div class="absolute bottom-4 right-8 text-[8px] text-slate-400">
            พิมพ์เมื่อ: <?= date('d/m/Y H:i') ?> | ระบบบริหารงานวิชาการดิจิทัล
        </div>
    </div>
</body>
</html>
