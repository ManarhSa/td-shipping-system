<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.html");
    exit();
}

$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';
$show_toast = false;
$show_error = false;
$error_message = "";

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: login.html");
    exit();
}

function get_codes($codes_file) {
    $codes = [];
    foreach (file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $parts = explode("|", $line);
        $codes[] = [
            "name"    => isset($parts[0]) ? $parts[0] : "",
            "barcode" => isset($parts[1]) ? $parts[1] : "",
            "code"    => isset($parts[2]) ? $parts[2] : ""
        ];
    }
    return $codes;
}

if (isset($_POST["newname"], $_POST["newcode"])) {
    $nameRaw = trim($_POST["newname"]);
    $codeRaw = trim($_POST["newcode"]);
    if (preg_match('/^[\p{L} ]+$/u', $nameRaw) && preg_match('/^\d+$/', $codeRaw)) {
        $name = str_replace("|", "", $nameRaw);
        $code = str_replace("|", "", $codeRaw);

        $codes = get_codes($codes_file);
        $exists_pair = false;
        foreach ($codes as $c) {
            if ($c["name"] == $name && $c["code"] == $code) {
                $exists_pair = true;
                break;
            }
        }
        if ($exists_pair) {
            $show_error = true;
            $error_message = "يوجد مستخدم بنفس الاسم ورمز الدخول";
        } else {
            $barcodes = array_column($codes, 'barcode');
            do {
                $barcode = '';
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                for ($i = 0; $i < 10; $i++) $barcode .= $chars[rand(0, strlen($chars) - 1)];
            } while (in_array($barcode, $barcodes));

            file_put_contents($codes_file, "$name|$barcode|$code" . PHP_EOL, FILE_APPEND);
            $show_toast = true;
        }
    }
}

if (isset($_POST["edit_name"], $_POST["edit_code"], $_POST["original_code"])) {
    $new_name = trim($_POST["edit_name"]);
    $new_code = trim($_POST["edit_code"]);
    $original_code = trim($_POST["original_code"]);

    if ($new_name && $new_code) {
        $codes = get_codes($codes_file);
        foreach ($codes as &$c) {
            if ($c["code"] == $original_code) {
                $c["name"] = $new_name;
                $c["code"] = $new_code;
                break;
            }
        }
        $lines = [];
        foreach ($codes as $c) {
            $lines[] = $c["name"] . "|" . $c["barcode"] . "|" . $c["code"];
        }
        file_put_contents($codes_file, implode(PHP_EOL, $lines) . PHP_EOL);
        $show_toast = true;
    }
}

if (isset($_POST["delcode"])) {
    $delcode = $_POST["delcode"];
    $codesData = get_codes($codes_file);
    $codesFiltered = array_filter($codesData, function($c) use ($delcode) {
        return $c["code"] != $delcode;
    });
    $lines = [];
    foreach ($codesFiltered as $c) $lines[] = $c["name"] . "|" . $c["barcode"] . "|" . $c["code"];
    file_put_contents($codes_file, implode(PHP_EOL, $lines) . PHP_EOL);
}

$codes = get_codes($codes_file);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم - نسري للشحن السريع</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<audio id="notificationSound" src="bell.mp3" preload="auto"></audio>

<div id="latestShipmentInfo" class="notification-info">
    <span id="latestUserName" class="latest-user-name"></span>
    <a id="viewShipmentsLink" href="#" class="shipment-link">عرض الشحنات</a>
</div>

<div class="top-bar">
    <div class="notification-wrapper">
        <span id="notificationBell">🔔</span>
    </div>

    <h2 class="top-bar-title">لوحة تحكم</h2>

    <form method="post">
        <button type="submit" name="logout" class="logout-btn">تسجيل الخروج</button>
    </form>
</div>


<div class="container admin-box">
    <div class="section-block">
        <div class="section-title" id="addUserTitle">إضافة مستخدم جديد</div>
        <form method="post" class="add-code-row" id="addUserForm" style="display:none;margin-bottom:0;">
            <input type="text" name="newname" placeholder="اسم المستخدم" pattern="[A-Za-z\u0600-\u06FF ]+" required oninput="this.value=this.value.replace(/[^A-Za-z\u0600-\u06FF ]/g,'')">
            <input type="text" name="newcode" placeholder="رمز الدخول" pattern="\d+" required oninput="this.value=this.value.replace(/\D/g,'')">
            <button type="submit">إضافة</button>
        </form>
        <?php if ($show_error): ?>
            <div class="error-message">⚠️ <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <form action="users-list.php" method="get">
            <button type="submit" class="browse-users-btn">استعراض العملاء</button>
        </form>
        <div id="userToast">تم إضافة مستخدم جديد بنجاح!</div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded',function(){
    var title = document.getElementById('addUserTitle');
    var form = document.getElementById('addUserForm');
    title.onclick = function(){
        if(form.style.display === "none"){
            form.style.display = "flex";
            form.scrollIntoView({behavior: 'smooth', block: 'center'});
        } else {
            form.style.display = "none";
        }
    };

    <?php if($show_toast): ?>
    setTimeout(function(){
        var t = document.getElementById('userToast');
        if(t){
            t.style.display = "block";
            setTimeout(function(){ t.style.display = "none"; }, 2500);
        }
    }, 350);
    <?php endif; ?>

    document.body.addEventListener('click', () => {
        const sound = document.getElementById('notificationSound');
        sound.play().catch(() => {});
    }, { once: true });

    fetch('latest.json')
        .then(res => res.json())
        .then(data => {
            const bell = document.getElementById('notificationBell');
            const infoBox = document.getElementById('latestShipmentInfo');
            const userNameEl = document.getElementById('latestUserName');
            const linkEl = document.getElementById('viewShipmentsLink');
            const sound = document.getElementById('notificationSound');

            if (data && data.barcode && data.name) {
                const lastSeen = localStorage.getItem('last_seen_barcode');
                if (lastSeen !== data.barcode) {
                    sound.play();
                    setTimeout(() => sound.pause(), 3000);
                    localStorage.setItem('last_seen_barcode', data.barcode);
                }

                bell.addEventListener('click', () => {
                    userNameEl.textContent = data.name;
                    linkEl.href = 'admin-view-shipments.php?barcode=' + encodeURIComponent(data.barcode);
                    infoBox.style.display = 'block';
                });
            }
        });
});
</script>
</body>
</html>
