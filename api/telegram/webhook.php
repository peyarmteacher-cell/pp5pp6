<?php
/**
 * Telegram Webhook Handler for Auto-Linking Parents
 */
require_once '../config.php';

// รับข้อมูลที่ส่งมาจาก Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;

// ระบุ School ID จาก URL (ตอนตั้ง Webhook เราจะใส่ ?school_id=X)
$school_id = $_GET['school_id'] ?? null;

if (!$school_id) exit;

// ดึง Token ของโรงเรียนนี้มาใช้ส่งข้อความกลับ
$stmt = $pdo->prepare("SELECT telegram_bot_token FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$bot_token = $stmt->fetchColumn();

if (!$bot_token) exit;

function sendMessage($chat_id, $text, $keyboard = null) {
    global $bot_token;
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// 1. กรณีเป็นข้อความพิมพ์เข้ามา
if ($message) {
    $chat_id = $message['chat']['id'];
    $text = trim($message['text']);

    if ($text === '/start') {
        $msg = "🏫 <b>ยินดีต้อนรับสู่ระบบแจ้งเตือนของโรงเรียน</b>\n\n";
        $msg .= "กรุณาพิมพ์ <b>หมายเลขประจำตัวประชาชน 13 หลัก</b> ของนักเรียน เพื่อลงทะเบียนรับข่าวสารและการแจ้งเตือนการเข้าเรียนจากทางโรงเรียนครับ";
        sendMessage($chat_id, $msg);
    } 
    else if (preg_match('/^[0-9]{13}$/', $text)) {
        // ค้นหานักเรียนในโรงเรียนนี้
        $stmt = $pdo->prepare("SELECT id, name, last_name, prefix FROM students WHERE school_id = ? AND national_id = ? LIMIT 1");
        $stmt->execute([$school_id, $text]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $fullName = ($student['prefix'] ?? '') . $student['name'] . ' ' . ($student['last_name'] ?? '');
            $msg = "🔍 <b>พบข้อมูลนักเรียน</b>\n\n";
            $msg .= "ชื่อ-นามสกุล: <b>{$fullName}</b>\n";
            $msg .= "คุณคือผู้ปกครองของนักเรียนคนนี้ใช่หรือไม่?";
            
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => '✅ ใช่ ยืนยัน', 'callback_data' => "confirm_{$student['id']}"],
                    ['text' => '❌ ไม่ใช่/ยกเลิก', 'callback_data' => "cancel"]
                ]]
            ];
            sendMessage($chat_id, $msg, $keyboard);
        } else {
            sendMessage($chat_id, "❌ <b>ไม่พบข้อมูล</b>\nไม่พบเลขบัตรประชาชนนี้ในฐานข้อมูลนักเรียนของโรงเรียนปีการศึกษาปัจจุบัน กรุณาตรวจสอบเลขและพิมพ์อีกครั้ง หรือติดต่อครูประจำชั้นครับ");
        }
    }
}

// 2. กรณีผู้ปกครองกดปุ่มยืนยัน (Callback Query)
if ($callback_query) {
    $chat_id = $callback_query['message']['chat']['id'];
    $data = $callback_query['data'];
    $message_id = $callback_query['message']['message_id'];

    if (strpos($data, 'confirm_') === 0) {
        $student_id = str_replace('confirm_', '', $data);
        
        // อัปเดตข้อมูลนักเรียน
        $stmt = $pdo->prepare("UPDATE students SET parent_telegram_id = ? WHERE id = ? AND school_id = ?");
        $success = $stmt->execute([$chat_id, $student_id, $school_id]);
        
        if ($success) {
            sendMessage($chat_id, "🎉 <b>ลงทะเบียนสำเร็จ!</b>\nคุณจะได้รับการแจ้งเตือนเมื่อคุณครูบันทึกการมาเรียนของนักเรียนตั้งแต่นี้เป็นต้นไปครับ");
        } else {
            sendMessage($chat_id, "⚠️ เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง");
        }
    } else if ($data === 'cancel') {
        sendMessage($chat_id, "ยกเลิกรายการแล้วครับ ท่านสามารถส่งเลขบัตรประชาชนใหม่ได้เสมอ");
    }
}
