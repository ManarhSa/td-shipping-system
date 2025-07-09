<?php
$path = __DIR__ . "/test_inside_public_html.txt";
$result = file_put_contents($path, "اختبار كتابة\n", FILE_APPEND);
if ($result === false) {
    echo "❌ فشل في إنشاء الملف";
} else {
    echo "✅ تم إنشاء الملف بنجاح: $path";
}
