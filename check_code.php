<?php
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['ok' => false, 'error' => 'لا يوجد بيانات']);
    exit;
}

$name = trim($data['name'] ?? '');
$code = trim($data['code'] ?? '');

// تحقق من بيانات الأدمن
if ($name === 'admin' && $code === '1234') {
    echo json_encode(['ok' => true]);
    exit;
}

// تحقق من المستخدمين العاديين
$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';

if (file_exists($codes_file)) {
    $codes = file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($codes as $line) {
        $parts = explode("|", $line);
        $saved_name = trim($parts[0] ?? '');
        $saved_code = trim($parts[2] ?? '');

        if ($saved_name === $name && $saved_code === $code) {
            echo json_encode(['ok' => true]);
            exit;
        }
    }
}

echo json_encode(['ok' => false, 'error' => 'رمز المرور أو الاسم غير صحيح']);
?>
