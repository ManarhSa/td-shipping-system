<?php
header("Content-Type: application/json; charset=utf-8");

$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['old_code'], $data['new_name'], $data['new_code'])) {
    echo json_encode(['ok' => false, 'error' => 'بيانات غير مكتملة']);
    exit;
}

$old_code = trim($data['old_code']);
$new_name = str_replace('|', '', trim($data['new_name']));
$new_code = trim($data['new_code']);

if (!$old_code || !$new_name || !$new_code) {
    echo json_encode(['ok' => false, 'error' => 'الحقول لا يمكن أن تكون فارغة']);
    exit;
}

$lines = file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$updated = false;
$barcodes = [];
$entries = [];

foreach ($lines as $line) {
    list($name, $barcode, $code) = explode("|", $line);
    $barcodes[] = $barcode;
    $entries[] = ['name' => $name, 'barcode' => $barcode, 'code' => $code];
}

foreach ($entries as $entry) {
    if ($entry['code'] !== $old_code && $entry['name'] === $new_name && $entry['code'] === $new_code) {
        echo json_encode(['ok' => false, 'error' => 'يوجد مستخدم بنفس الاسم ورمز الدخول']);
        exit;
    }
}

foreach ($entries as &$entry) {
    if ($entry['code'] === $old_code) {
        $entry['name'] = $new_name;
        $entry['code'] = $new_code;
        $updated = true;
        break;
    }
}

if (!$updated) {
    echo json_encode(['ok' => false, 'error' => 'المستخدم غير موجود']);
    exit;
}

file_put_contents($codes_file, implode(PHP_EOL, array_map(fn($e) => "{$e['name']}|{$e['barcode']}|{$e['code']}", $entries)) . PHP_EOL);
echo json_encode(['ok' => true]);
