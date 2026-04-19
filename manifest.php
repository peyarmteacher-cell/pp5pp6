<?php
header('Content-Type: application/manifest+json');
require_once 'api/config.php';

$app_name = "ระบบติดตามนักเรียนสำหรับผู้ปกครอง";
$app_logo = "https://picsum.photos/seed/school/192/192";
$app_logo_512 = "https://picsum.photos/seed/school/512/512";

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($settings['app_name'])) {
        $app_name = $settings['app_name'];
    }
    
    if (isset($settings['app_logo'])) {
        // Use the uploaded logo if available
        $app_logo = $settings['app_logo'];
        $app_logo_512 = $settings['app_logo'];
    }
} catch (Exception $e) {
    // Fallback to defaults if something goes wrong
}

$manifest = [
    "name" => $app_name,
    "short_name" => $app_name,
    "description" => "ดูผลการเรียน การเข้าเรียน และพฤติกรรมนักเรียน",
    "start_url" => "/parent_login.php",
    "display" => "standalone",
    "background_color" => "#ffffff",
    "theme_color" => "#2563eb",
    "icons" => [
        [
            "src" => $app_logo,
            "sizes" => "192x192",
            "type" => "image/png"
        ],
        [
            "src" => $app_logo_512,
            "sizes" => "512x512",
            "type" => "image/png"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
