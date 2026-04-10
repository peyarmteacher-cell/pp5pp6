<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$school_id = $_SESSION['school_id'];
$stmt = $pdo->prepare('SELECT * FROM schools WHERE id = ?');
$stmt->execute([$school_id]);
$school = $stmt->fetch();

$logo_url = $school['logo_url'] ?? '';
if ($logo_url && !preg_match('/^https?:\/\//', $logo_url)) {
    $logo_url = '../' . $logo_url;
}
$school_name = $school['name'] ?? '';
$province = $school['province'] ?? '';
$director_name = $school['director_name'] ?? '';
$academic_head_name = $school['academic_head_name'] ?? '';
$academic_head_position = $school['academic_head_position'] ?? 'หัวหน้างานวิชาการ';

// Common report header/styles
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานเอกสาร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }
        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .no-print { display: none; }
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 104px;
            height: 104px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: center;
            font-size: 14px;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-none { border: none; }
        .no-border td { border: none; }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; rounded: 8px; cursor: pointer; font-weight: bold;">พิมพ์เอกสาร</button>
    </div>
