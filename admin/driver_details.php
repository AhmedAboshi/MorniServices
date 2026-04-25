
<?php
include('../include/connected.php');

/* 🆔 التحقق من ID */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = $con->query("SELECT * FROM drivers WHERE id = $id");

if ($result->num_rows == 0) {
    die("السائق غير موجود");
}

$driver = $result->fetch_assoc();

/* 🔐 دالة حماية */
function e($value){
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تفاصيل السائق</title>

<style>
body {
    font-family: 'Cairo', sans-serif;
    direction: rtl;
    background: #f4f6f9;
}

/* 📦 الحاوية */
.container {
    width: 50%;
    margin: 40px auto;
}

/* 📇 الكرت */
.card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

/* 📄 النص */
p {
    font-size: 16px;
    margin: 10px 0;
}

/* 🔘 زر */
.back-btn {
    display: inline-block;
    margin-top: 15px;
    background: #3498db;
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
}
</style>

</head>
<body>

<div class="container">
<h2>👤 تفاصيل السائق</h2>

<div class="card">

<p><strong>الاسم:</strong> <?= e($driver['name']) ?></p>
<p><strong>رقم الهوية:</strong> <?= e($driver['national_id']) ?></p>
<p><strong>الجوال:</strong> <?= e($driver['phone']) ?></p>
<p><strong>نوع الشاحنة:</strong> <?= e($driver['truck_type']) ?></p>
<p><strong>رقم اللوحة:</strong> <?= e($driver['plate_number']) ?></p>
<p><strong>منطقة العمل:</strong> <?= e($driver['work_area']) ?></p>
<p><strong>تاريخ الإضافة:</strong> <?= e($driver['created_at']) ?></p>

</div>

<a class="back-btn" href="driversview.php">⬅ الرجوع للقائمة</a>

</div>

</body>
</html>

