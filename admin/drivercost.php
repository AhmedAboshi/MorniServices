<?php
include(__DIR__ . '/../include/connected.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* بيانات السائق */
$provider = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT * FROM drivers WHERE id=$id")
);

if(!$provider){
    echo "المزود غير موجود";
    exit;
    var_dump("no id");
    exit;
}

/* الزيت */
$oil = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT SUM(cost) AS total FROM oil_changes WHERE driver_id=$id")
);

/* الإطارات */
$tire = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT SUM(cost) AS total FROM tires WHERE driver_id=$id")
);

/* الصيانة */
$maint = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT SUM(cost) AS total FROM maintenance WHERE driver_id=$id")
);

/* القيم */
$oil_cost   = $oil['total'] ?? 0;
$tire_cost  = $tire['total'] ?? 0;
$maint_cost = $maint['total'] ?? 0;

$total = $oil_cost + $tire_cost + $maint_cost;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
<meta charset="UTF-8">
<title>تفاصيل المزود</title>

<style>
body{
    font-family: Arial;
    background:#f4f6f9;
    margin:0;
}

.box{
    width:60%;
    margin:50px auto;
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

.header{
    text-align:center;
    margin-bottom:20px;
}

.row{
    padding:15px;
    border-bottom:1px solid #eee;
    font-size:18px;
    display:flex;
    justify-content:space-between;
}

.total{
    margin-top:20px;
    font-size:22px;
    color:#28a745;
    text-align:center;
    font-weight:bold;
}

.badge{
    background:#007bff;
    color:#fff;
    padding:5px 10px;
    border-radius:6px;
}
</style>
</head>

<body>

<div class="box">

<div class="header">
    <h2>🚛 <?= $provider['name']; ?></h2>
    <span class="badge">رقم اللوحة: <?= $provider['plate_number']; ?></span>
</div>

<div class="row">
    <span>💧 الزيت</span>
    <span><?= $oil_cost ?> ريال</span>
</div>

<div class="row">
    <span>🛞 الإطارات</span>
    <span><?= $tire_cost ?> ريال</span>
</div>

<div class="row">
    <span>🔧 الصيانة</span>
    <span><?= $maint_cost ?> ريال</span>
</div>

<div class="total">
    الإجمالي: <?= $total ?> ريال
</div>

</div>

</body>
</html>
