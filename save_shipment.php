<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

$debugPath = __DIR__ . "/shipments_data/debug.txt";
file_put_contents($debugPath, "✅ بدأ التنفيذ\n", FILE_APPEND);

if (!isset($_SESSION['barcode']) || !$_SESSION['barcode']) {
    echo json_encode(['ok' => false, 'error' => 'جلسة المستخدم غير صالحة']);
    exit;
}

$barcode = trim($_SESSION['barcode']);

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'بيانات JSON غير صالحة']);
    exit;
}

unset($data['g-recaptcha-response'], $data['user_code'], $data['user_name']);
date_default_timezone_set('Asia/Riyadh');
$time = date('Y-m-d h:i A');

$filename = __DIR__ . "/shipments_data/shipments_{$barcode}.php";
if (!file_exists($filename)) {
    file_put_contents($filename, "<?php exit;?>\n");
}

$log_line = $time . '|' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
file_put_contents($filename, $log_line, FILE_APPEND);

$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';
$lines = file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$name = null;
foreach ($lines as $line) {
    $parts = explode('|', $line);
    if (isset($parts[1]) && $parts[1] === $barcode) {
        $name = $parts[0];
        break;
    }
}

if ($name) {
    $filePath = __DIR__ . "/latest.json";
    $existing = [];

    if (file_exists($filePath)) {
        $json = file_get_contents($filePath);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (isset($item['id'], $item['barcode'], $item['name'], $item['time'])) {
                    $existing[] = $item;
                }
            }
        }
    }

    $newId = $barcode . '|' . $time;
    $alreadyExists = false;
    foreach ($existing as $item) {
        if ($item['id'] === $newId) {
            $alreadyExists = true;
            break;
        }
    }

    if (!$alreadyExists) {
        $existing[] = [
            'id' => $newId,
            'barcode' => $barcode,
            'name' => $name,
            'time' => $time
        ];

        usort($existing, function($a, $b) {
            return strtotime($b['time']) <=> strtotime($a['time']);
        });

        file_put_contents($filePath, json_encode(array_values($existing), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

file_put_contents($debugPath, "✅ تم الحفظ بنجاح\n", FILE_APPEND);
echo json_encode(['ok' => true]);
