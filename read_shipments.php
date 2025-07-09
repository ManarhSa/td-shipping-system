<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'طريقة الطلب غير مسموحة']);
    exit;
}

if (!isset($_GET['barcode']) || !trim($_GET['barcode'])) {
    echo json_encode([]);
    exit;
}

$barcode = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($_GET['barcode']))); // تأمين الباركود

$filepath = __DIR__ . "/shipments_data/shipments_{$barcode}.php";

if (!file_exists($filepath)) {
    echo json_encode([]);
    exit;
}

$lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
array_shift($lines);

$data = [];

foreach ($lines as $line) {
    $parts = explode('|', $line, 2);
    if (count($parts) === 2) {
        $date = trim($parts[0]);
        $shipmentData = json_decode($parts[1], true);
        if (is_array($shipmentData)) {
            $shipmentData['التاريخ'] = $date;
            $data[] = $shipmentData;
        }
    }
}

$data = array_reverse($data);
echo json_encode($data, JSON_UNESCAPED_UNICODE);
