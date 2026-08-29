<?php
include('../include/connected.php');

$where = "WHERE 1=1";

if(!empty($_GET['from']) && !empty($_GET['to'])){
    $from = $_GET['from'];
    $to = $_GET['to'];
    $where .= " AND a.accident_date BETWEEN '$from' AND '$to'";
}

if(!empty($_GET['plate'])){
    $plate = $_GET['plate'];
    $where .= " AND f.plate LIKE '%$plate%'";
}

$topDrivers = $con->query("
SELECT 
    d.name,
    COUNT(a.id) AS total_accidents
FROM accidents a
LEFT JOIN drivers d ON a.driver_id = d.id
GROUP BY a.driver_id
ORDER BY total_accidents DESC
LIMIT 5
");

/* بيانات الحوادث */
$data = $con->query("
SELECT 
    a.*,
    f.plate,
    f.model,
    d.name AS driver_name
FROM accidents a
LEFT JOIN fleet f ON a.vehicle_id = f.id
LEFT JOIN drivers d ON a.driver_id = d.id
$where
ORDER BY a.accident_date DESC
");

/* إحصائيات */
$totalAccidents = $con->query("SELECT COUNT(*) AS c FROM accidents")->fetch_assoc()['c'];
$totalCost = $con->query("SELECT SUM(damage_cost) AS c FROM accidents")->fetch_assoc()['c'];
$lastAccident = $con->query("SELECT accident_date FROM accidents ORDER BY id DESC LIMIT 1")->fetch_assoc()['accident_date'] ?? '---';
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تقارير الحوادث</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f1f5f9;
    font-family:tahoma;
}

/* عنوان */
.title{
    font-weight:bold;
    margin-bottom:20px;
    color:#0f172a;
}

/* كروت */
.card-box{
    background:#fff;
    padding:18px;
    border-radius:15px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    height:100%;
}

.card-box h6{
    color:#64748b;
}

.card-box h3{
    margin-top:10px;
    color:#1e40af;
}

/* صندوق */
.box{
    background:#fff;
    padding:20px;
    border-radius:15px;
    margin-top:20px;
    box-shadow:0 6px 18px rgba(0,0,0,0.05);
}

/* جدول */
table th{
    background:#1e40af !important;
    color:#fff;
    text-align:center;
}

table td{
    text-align:center;
    vertical-align:middle;
}

table tbody tr:hover{
    background:#eef2ff;
}

/* زر */
.btn-primary{
    border-radius:10px;
}
</style>
</head>

<body>

<div class="container mt-4">

<h3 class="title">🚨 لوحة تقارير الحوادث</h3>

<!-- 🔵 كروت الإحصائيات -->
<div class="row g-3">

<div class="col-md-4">
<div class="card-box">
<h6>🚨 إجمالي الحوادث</h6>
<h3><?= $totalAccidents ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card-box">
<h6>💰 إجمالي الخسائر</h6>
<h3><?= $totalCost ?? 0 ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card-box">
<h6>📅 آخر حادث</h6>
<h3><?= $lastAccident ?></h3>
</div>
</div>

</div>

<!-- 🔍 الفلاتر -->
<div class="box">

<form method="GET" class="row g-2">

<div class="col-md-4">
<input type="date" name="from" class="form-control">
</div>

<div class="col-md-4">
<input type="date" name="to" class="form-control">
</div>

<div class="col-md-3">
<input type="text" name="plate" class="form-control" placeholder="رقم اللوحة">
</div>

<div class="col-md-1">
<button class="btn btn-primary w-100">بحث</button>
</div>

</form>

</div>

<!-- 🖨 أزرار -->
<div class="mt-3">
<button onclick="window.print()" class="btn btn-dark">🖨 طباعة</button>
</div>
<div class="box mt-4">

<h5 class="mb-3">🚨 أكثر السائقين حوادث</h5>

<table class="table table-striped">
<thead>
<tr>
<th>#</th>
<th>السائق</th>
<th>عدد الحوادث</th>
</tr>
</thead>

<tbody>
<?php $i=1; while($row = $topDrivers->fetch_assoc()){ ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $row['name'] ?></td>
<td>
<span class="badge bg-danger">
<?= $row['total_accidents'] ?>
</span>
</td>
</tr>
<?php } ?>
</tbody>
</table>

</div>

<!-- 📊 الجدول -->
<div class="box">

<table class="table table-bordered">
<thead>
<tr>
<th>#</th>
<th>السائق</th>
<th>السيارة</th>
<th>اللوحة</th>
<th>التاريخ</th>
<th>الموقع</th>
<th>التكلفة</th>
<th>الحالة</th>
</tr>
</thead>

<tbody>
<?php while($row = $data->fetch_assoc()){ ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['driver_name'] ?></td>
<td><?= $row['model'] ?></td>
<td><?= $row['plate'] ?></td>
<td><?= $row['accident_date'] ?></td>
<td><?= $row['location'] ?></td>
<td><?= $row['damage_cost'] ?></td>
<td>
<span class="badge bg-<?= $row['status']=='pending'?'warning':'success' ?>">
<?= $row['status'] ?>
</span>
</td>
</tr>
<?php } ?>
</tbody>

</table>

</div>

</div>

</body>
</html>