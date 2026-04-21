<?php
include('../include/connected.php');

// التحقق من الاتصال

// أخذ ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب البيانات
$result = $con->query("SELECT * FROM drivers WHERE id = $id");

if ($result->num_rows == 0) {
    die("السائق غير موجود");
}

$driver = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تفاصيل السائق</title>
<style>
body { font-family: Arial; direction: rtl; }
.container { width: 50%; margin: auto; }
.card {
  border: 1px solid #ccc;
  padding: 20px;
  margin-top: 20px;
  border-radius: 10px;
}
p { font-size: 18px; margin: 8px 0; }
</style>
</head>
<body>

<div class="container">
<h2>👤 تفاصيل السائق</h2>

<div class="card">
  <p><strong>الاسم:</strong> <?= $driver['name'] ?></p>
  <p><strong>رقم الهوية:</strong> <?= $driver['national_id'] ?></p>
  <p><strong>الجوال:</strong> <?= $driver['phone'] ?></p>
  <p><strong>نوع الشاحنة:</strong> <?= $driver['truck_type'] ?></p>
  <p><strong>رقم اللوحة:</strong> <?= $driver['plate_number'] ?></p>
  <p><strong>منطقة العمل:</strong> <?= $driver['work_area'] ?></p>
  <p><strong>تاريخ الإضافة:</strong> <?= $driver['created_at'] ?></p>
</div>

<a href="driversview.php">⬅ الرجوع للقائمة</a>
</div>

</body>
</html>