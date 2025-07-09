<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

$debugPath = __DIR__ . "/shipments_data/debug.txt";
file_put_contents($debugPath, "✅ بدأ get_username.php\n", FILE_APPEND);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data["code"]) || !isset($data["name"])) {
    echo json_encode(["error" => "بيانات غير صالحة"]);
    exit;
}

$userCode = trim($data["code"]);
$userName = trim($data["name"]);

$response = ["username" => "", "barcode" => ""];

$codesPath = dirname(__DIR__, 2) . "/codes/codes.txt";
$lines = file($codesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim(preg_replace('/\x{FEFF}/u', '', $line));
    $line = mb_convert_encoding($line, 'UTF-8', 'UTF-8');

    if ($line === '') continue;

    $parts = explode("|", $line);
    if (count($parts) < 3) continue;

    $name = trim($parts[0]);
    $barcode = trim($parts[1]);
    $code = trim($parts[2]);

    if (
        strcasecmp($code, $userCode) === 0 &&
        strcasecmp($name, $userName) === 0
    ) {
        $_SESSION["barcode"] = $barcode;
        $response["username"] = $name;
        $response["barcode"] = $barcode;
        file_put_contents($debugPath, "✅ تم الحفظ في الجلسة بنجاح\n", FILE_APPEND);
        break;
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
