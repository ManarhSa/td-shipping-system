<?php
$codes_file = dirname(dirname(__DIR__)) . '/codes/codes.txt';
function get_codes($codes_file) {
    $codes = [];
    foreach(file($codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
        $parts = explode("|", $line);
        $codes[] = [
            "name"    => isset($parts[0]) ? $parts[0] : "",
            "barcode" => isset($parts[1]) ? $parts[1] : "",
            "code"    => isset($parts[2]) ? $parts[2] : ""
        ];
    }
    return $codes;
}

if(isset($_POST['delclient'])) {
    $delcode = $_POST['delclient'];
    $codes = get_codes($codes_file);
    $codes = array_filter($codes, function($c) use($delcode){ return $c["code"] != $delcode; });
    $lines = [];
    foreach($codes as $c) $lines[] = $c["name"] . "|" . $c["barcode"] . "|" . $c["code"];
    file_put_contents($codes_file, implode(PHP_EOL, $lines).PHP_EOL);
    header("Location: users-list.php?deleted=1");
    exit();
}

$codes = get_codes($codes_file);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة العملاء</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="users-list-box">
        <a href="admin-codes.php" class="back-btn">&#8592; رجوع للوحة التحكم</a>
        <button type="button" class="del-client-btn" id="delClientBtn">حذف عميل</button>
        <div class="choose-delete-msg" id="chooseDeleteMsg">اختر عميل لحذفه</div>
        <div class="users-title">قائمة العملاء</div>
        <ul class="codes-list" id="codesList">
        <?php foreach($codes as $c): ?>
            <li data-code="<?=htmlspecialchars($c['code'])?>" data-name="<?=htmlspecialchars($c['name'])?>">
                <input class="edit-name" value="<?=htmlspecialchars($c['name'])?>" disabled>
                <input class="edit-code" value="<?=htmlspecialchars($c['code'])?>" disabled>
                <a class='sh-link' href="admin-view-shipments.php?barcode=<?=urlencode($c['barcode'])?>" onclick='event.stopPropagation();'>عرض الشحنات</a>
                <button class="edit-btn" onclick="startEdit(this)">✏️</button>
                <button class="save-btn" disabled style="display:none;">✅</button>
            </li>
        <?php endforeach; ?>
        </ul>
        <form id="deleteForm" method="post" style="display:none;">
            <input type="hidden" name="delclient" id="delclientInput">
        </form>
    </div>

<script>
let deleteMode = false;
document.getElementById('delClientBtn').onclick = function(){
    deleteMode = !deleteMode;
    document.getElementById('chooseDeleteMsg').style.display = deleteMode ? 'block' : 'none';
    document.querySelectorAll("#codesList li").forEach(li => {
        li.classList.toggle('delete-mode', deleteMode);
    });
};
document.querySelectorAll('#codesList li').forEach(function(li){
    li.addEventListener('click', function(e){
        if(!deleteMode) return;
        let name = this.getAttribute('data-name');
        if(confirm("هل أنت متأكد من حذف العميل: " + name + " ؟")) {
            document.getElementById('delclientInput').value = this.getAttribute('data-code');
            document.getElementById('deleteForm').submit();
        }
    });
});

function startEdit(btn) {
    let li = btn.closest('li');
    li.querySelector('.edit-name').disabled = false;
    li.querySelector('.edit-code').disabled = false;
    let saveBtn = li.querySelector('.save-btn');
    saveBtn.style.display = 'inline-block';
    checkDuplicates(li);
}

function saveEdit(btn) {
    let li = btn.closest('li');
    let name = li.querySelector('.edit-name').value.trim();
    let code = li.querySelector('.edit-code').value.trim();
    let originalCode = li.dataset.code;
    fetch('update-user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({old_code: originalCode, new_name: name, new_code: code})
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) location.reload();
        else alert(data.error || 'حدث خطأ');
    });
}

function checkDuplicates(currentLi) {
    let allLis = document.querySelectorAll('#codesList li');
    let currentName = currentLi.querySelector('.edit-name').value.trim();
    let currentCode = currentLi.querySelector('.edit-code').value.trim();
    let hasConflict = false;

    allLis.forEach(li => {
        if (li === currentLi) return;
        let name = li.querySelector('.edit-name').value.trim();
        let code = li.querySelector('.edit-code').value.trim();
        if (name === currentName && code === currentCode) hasConflict = true;
    });

    let oldBtn = currentLi.querySelector('.save-btn');
    let newBtn = oldBtn.cloneNode(true);
    newBtn.style.display = 'inline-block';

    if (hasConflict) {
        newBtn.disabled = true;
        newBtn.classList.add('error');
        newBtn.innerText = "يوجد مستخدم بنفس الاسم ورمز الدخول";
    } else {
        newBtn.disabled = false;
        newBtn.classList.remove('error');
        newBtn.innerText = "✅";
        newBtn.addEventListener('click', function () {
            saveEdit(this);
        });
    }

    oldBtn.replaceWith(newBtn);
}

document.querySelectorAll('.edit-name, .edit-code').forEach(input => {
    input.addEventListener('input', function() {
        let li = this.closest('li');
        checkDuplicates(li);
    });
});
</script>
</body>
</html>
