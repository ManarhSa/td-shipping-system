<?php
if (!isset($_GET['barcode']) || !trim($_GET['barcode'])) {
    die('الباركود غير موجود.');
}
$barcode = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($_GET['barcode'])));
$name = 'غير معروف';
$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';
$lines = file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $parts = explode('|', $line);
    if (isset($parts[1]) && $parts[1] === $barcode) {
        $name = $parts[0];
        break;
    }
}

$latest_file = __DIR__ . "/latest.json";
if (file_exists($latest_file)) {
    $json = file_get_contents($latest_file);
    $items = json_decode($json, true);
    if (is_array($items)) {
        $items = array_filter($items, function($item) use ($barcode) {
            return isset($item['barcode']) && $item['barcode'] !== $barcode;
        });
        file_put_contents($latest_file, json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل الشحنات</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .back-button {
            margin-bottom: 20px;
            background-color: #519fff;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            display: inline-block;
        }
        .back-button:hover {
            background-color: #4179cc;
        }
        .shipments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .shipments-table th,
        .shipments-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .shipments-table th {
            background-color: #519fff;
            color: white;
        }
        h2 {
            text-align: center;
            color: green;
        }
        #shipmentCount {
            text-align: center;
            margin: 10px 0;
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin-codes.php" class="back-button">⬅ رجوع</a>
        <h2>سجل الشحنات - <span style="color:#217a37;"><?= htmlspecialchars($name) ?></span></h2>
        <div id="shipmentCount"></div>
        <table class="shipments-table" id="shipmentsTable">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الاسم</th>
                    <th>رقم الجوال</th>
                    <th>المدينة</th>
                    <th>الحي</th>
                    <th>الشارع</th>
                    <th>عدد الطرود</th>
                    <th>الوزن</th>
                    <th>نوع الوزن</th>
                </tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>

    <script>
        const barcode = "<?= $barcode ?>";
        const userId = barcode + "|<?= date('Y-m-d h:i A') ?>";
        const seen = new Set(JSON.parse(localStorage.getItem('seen_shipments') || '[]'));
        seen.add(userId);
        localStorage.setItem('seen_shipments', JSON.stringify([...seen]));

        document.addEventListener('DOMContentLoaded', function () {
            fetch(`read_shipments.php?barcode=${encodeURIComponent(barcode)}`, {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                const tableBody = document.getElementById('tableBody');
                if (data.length === 0) {
                    document.getElementById('shipmentCount').innerText = 'لا توجد شحنات مسجلة لهذا المستخدم.';
                    return;
                }
                data.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item['التاريخ'] || ''}</td>
                        <td>${item['الاسم'] || ''}</td>
                        <td>${item['رقم الجوال'] || ''}</td>
                        <td>${item['المدينة'] || ''}</td>
                        <td>${item['الحي'] || ''}</td>
                        <td>${item['الشارع'] || ''}</td>
                        <td>${item['عدد الطرود'] || ''}</td>
                        <td>${item['الوزن'] || ''}</td>
                        <td>${item['نوع الوزن'] || ''}</td>
                    `;
                    tableBody.appendChild(row);
                });
                document.getElementById('shipmentCount').innerText = `عدد الشحنات: ${data.length}`;
            })
            .catch(error => {
                console.error('خطأ في جلب البيانات:', error);
            });
        });
    </script>
</body>
</html>
