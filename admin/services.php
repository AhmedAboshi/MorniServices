<?php
session_start();

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: admin.php");
    exit;
}

/*==============================
بحث
==============================*/

$search = trim($_GET['search'] ?? '');
$section = trim($_GET['section'] ?? '');

$where=[];

if($search!=""){
    $s=mysqli_real_escape_string($con,$search);

    $where[]="(
    proname LIKE '%$s%'
    OR prosection LIKE '%$s%'
    OR prodescrip LIKE '%$s%'
    )";
}

if($section!=""){

    $sec=mysqli_real_escape_string($con,$section);

    $where[]="prosection='$sec'";
}

$sql="SELECT * FROM product";

if(count($where)>0){

    $sql.=" WHERE ".implode(" AND ",$where);

}

$sql.=" ORDER BY id DESC";

$result=mysqli_query($con,$sql);

/*==============================
الإحصائيات
==============================*/

$total=mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM product
"))['total'];

$available=mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM product
WHERE prounv='متوفر'
"))['total'];

$unavailable=mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM product
WHERE prounv<>'متوفر'
"))['total'];

$sections=mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(DISTINCT prosection) total
FROM product
"))['total'];

?>
<!DOCTYPE html>

<html lang="<?= $lang ?>"
dir="<?= $lang=="ar"?"rtl":"ltr" ?>">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>

<?= setting('system_name') ?>

| إدارة الخدمات

</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

body{

background:#eef2f7;

font-family:Cairo,Tahoma;

}

.top-box{

background:linear-gradient(135deg,#0d6efd,#003b8f);

color:#fff;

padding:25px;

border-radius:18px;

margin-bottom:25px;

box-shadow:0 15px 30px rgba(0,0,0,.15);

}

.logo{

width:70px;

height:70px;

border-radius:50%;

background:#fff;

padding:5px;

object-fit:cover;

}

.stat-card{

border:none;

border-radius:18px;

padding:20px;

box-shadow:0 10px 20px rgba(0,0,0,.08);

transition:.3s;

}

.stat-card:hover{

transform:translateY(-5px);

}

.table-box{

background:#fff;

border-radius:18px;

padding:20px;

box-shadow:0 8px 18px rgba(0,0,0,.08);

}

.service-img{

width:70px;

height:70px;

border-radius:12px;

object-fit:cover;

}

.badge-ok{

background:#16a34a;

}

.badge-no{

background:#dc2626;

}

.action-btn{

margin:2px;

}

.search-box{

background:#fff;

padding:20px;

border-radius:18px;

margin-bottom:20px;

box-shadow:0 8px 18px rgba(0,0,0,.08);

}

</style>

</head>

<body>

<div class="container-fluid p-4">

<div class="top-box">

<div class="d-flex justify-content-between align-items-center">

<div class="d-flex align-items-center">

<?php if(setting('company_logo')){ ?>

<img
src="../uploads/logo/<?= setting('company_logo')?>"
class="logo ms-3">

<?php } ?>

<div>

<h2 class="mb-1">

<?= setting('system_name') ?>

</h2>

<p class="mb-0">

إدارة الخدمات

</p>

</div>

</div>

<div>

<a href="addproduct.php"
class="btn btn-light btn-lg">

<i class="bi bi-plus-circle"></i>

إضافة خدمة

</a>

</div>

</div>

</div>
<div class="row mb-4">

<div class="col-lg-3 col-md-6 mb-3">

<div class="stat-card bg-white">

<h6 class="text-muted">إجمالي الخدمات</h6>

<h2 class="fw-bold text-primary">

<?= $total ?>

</h2>

<i class="bi bi-box-seam fs-1 text-primary"></i>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-3">

<div class="stat-card bg-white">

<h6 class="text-muted">

الخدمات المتوفرة

</h6>

<h2 class="fw-bold text-success">

<?= $available ?>

</h2>

<i class="bi bi-check-circle-fill fs-1 text-success"></i>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-3">

<div class="stat-card bg-white">

<h6 class="text-muted">

الخدمات غير المتوفرة

</h6>

<h2 class="fw-bold text-danger">

<?= $unavailable ?>

</h2>

<i class="bi bi-x-circle-fill fs-1 text-danger"></i>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-3">

<div class="stat-card bg-white">

<h6 class="text-muted">

عدد الأقسام

</h6>

<h2 class="fw-bold text-warning">

<?= $sections ?>

</h2>

<i class="bi bi-grid fs-1 text-warning"></i>

</div>

</div>

</div>

<div class="search-box">

<form method="GET">

<div class="row">

<div class="col-lg-5">

<input

type="text"

name="search"

class="form-control"

placeholder="ابحث باسم الخدمة..."

value="<?= htmlspecialchars($search) ?>">

</div>

<div class="col-lg-4">

<select

name="section"

class="form-select">

<option value="">

كل الأقسام

</option>

<?php

$q=mysqli_query($con,"
SELECT DISTINCT prosection
FROM product
ORDER BY prosection
");

while($sec=mysqli_fetch_assoc($q)){

?>

<option

value="<?= $sec['prosection']?>"

<?= $section==$sec['prosection']?'selected':'' ?>>

<?= $sec['prosection']?>

</option>

<?php } ?>

</select>

</div>

<div class="col-lg-3 d-grid">

<button

class="btn btn-primary">

<i class="bi bi-search"></i>

بحث

</button>

</div>

</div>

</form>

</div>

<div class="table-box">

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-dark">

<tr>

<th>#</th>

<th>الصورة</th>

<th>الخدمة</th>

<th>القسم</th>

<th>السعر</th>

<th>الحالة</th>

<th width="240">

الإجراءات

</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td>

<?= $row['id'] ?>

</td>

<td>

<img

src="../uploads/img/<?= $row['proimg']?>"

class="service-img">

</td>

<td>

<strong>

<?= $row['proname'] ?>

</strong>

<br>

<small class="text-muted">

<?= mb_strimwidth(strip_tags($row['prodescrip']),0,60,"...") ?>

</small>

</td>

<td>

<?= $row['prosection'] ?>

</td>

<td>

<strong class="text-success">

<?= number_format($row['proprice'],2) ?>

ر.س

</strong>

</td>

<td>

<?php

if($row['prounv']=="متوفر"){

echo '<span class="badge badge-ok">متوفر</span>';

}else{

echo '<span class="badge badge-no">غير متوفر</span>';

}

?>

</td>

<td>
    <a href="service_details.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm action-btn">

<i class="bi bi-eye-fill"></i>

</a>

<a href="update.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-sm action-btn">

<i class="bi bi-pencil-fill"></i>

</a>

<a href="services.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm action-btn"
onclick="return confirm('هل أنت متأكد من حذف هذه الخدمة؟')">

<i class="bi bi-trash-fill"></i>

</a>

</td>

</tr>

<?php } ?>

<?php if(mysqli_num_rows($result)==0){ ?>

<tr>

<td colspan="7" class="text-center p-5">

<img src="../img/empty.png"
style="width:120px;opacity:.6"><br><br>

<h5 class="text-muted">

لا توجد خدمات

</h5>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<script>

document.querySelectorAll(".table tbody tr").forEach(function(row){

row.addEventListener("mouseenter",function(){

this.style.transition=".2s";

this.style.transform="scale(1.01)";

});

row.addEventListener("mouseleave",function(){

this.style.transform="scale(1)";

});

});

</script>

</body>

</html>