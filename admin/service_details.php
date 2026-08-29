<?php
session_start();

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $con->prepare("
SELECT *
FROM product
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){

    die("الخدمة غير موجودة");

}

$row = $result->fetch_assoc();

?>
<!doctype html>

<html lang="<?= $lang ?>"
dir="<?= $lang=="ar" ? "rtl":"ltr" ?>">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>

<?= setting('system_name') ?>

| تفاصيل الخدمة

</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

body{

background:#eef2f7;

font-family:Cairo,Tahoma;

}

.header{

background:linear-gradient(135deg,#0d6efd,#003b8f);

padding:25px;

border-radius:18px;

color:#fff;

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

margin-left:15px;

}

.card{

border:none;

border-radius:18px;

box-shadow:0 10px 25px rgba(0,0,0,.08);

overflow:hidden;

}

.service-image{

width:100%;

height:420px;

object-fit:cover;

background:#fafafa;

}

.info-box{

padding:25px;

}

.badge-ok{

background:#198754;

padding:8px 14px;

font-size:15px;

}

.badge-no{

background:#dc3545;

padding:8px 14px;

font-size:15px;

}

.info-item{

padding:12px 0;

border-bottom:1px solid #eee;

}

.info-title{

font-weight:bold;

color:#666;

}

.price{

font-size:30px;

font-weight:bold;

color:#198754;

}

</style>

</head>

<body>

<div class="container py-4">

<div class="header">

<div class="d-flex align-items-center">

<?php if(setting('company_logo')){ ?>

<img
src="../uploads/logo/<?= setting('company_logo')?>"
class="logo">

<?php } ?>

<div>

<h2 class="mb-1">

<?= setting('system_name') ?>

</h2>

<p class="mb-0">

عرض تفاصيل الخدمة

</p>

</div>

</div>

</div>

<div class="card">

<div class="row g-0">

<div class="col-lg-5">

<?php

$image = !empty($row['proimg'])
? "../uploads/img/".$row['proimg']
: "../img/no-image.png";

?>

<img
src="<?= $image ?>"
class="service-image">

</div>

<div class="col-lg-7">

<div class="info-box">

<h2 class="mb-3">

<?= htmlspecialchars($row['proname']) ?>

</h2>

<div class="info-item">

<span class="info-title">

القسم :

</span>

<?= htmlspecialchars($row['prosection']) ?>

</div>

<div class="info-item">

<span class="info-title">

السعر :

</span>

<div class="price">

<?= number_format($row['proprice'],2) ?>

ر.س

</div>

</div>

<div class="info-item">

<span class="info-title">

الحالة :

</span>

<?php

if($row['prounv']=="متوفر"){

echo '<span class="badge badge-ok">متوفر</span>';

}else{

echo '<span class="badge badge-no">غير متوفر</span>';

}

?>

</div>
<div class="info-item">

<span class="info-title">

وصف الخدمة :

</span>

<div class="mt-3">

<?= nl2br(htmlspecialchars($row['prodescrip'])) ?>

</div>

</div>

<?php if(!empty($row['created_at'])){ ?>

<div class="info-item">

<span class="info-title">

تاريخ الإضافة :

</span>

<?= date("Y-m-d h:i A",strtotime($row['created_at'])) ?>

</div>

<?php } ?>

<div class="mt-5 d-flex gap-2 flex-wrap">

<a
href="update.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-lg">

<i class="bi bi-pencil-square"></i>

تعديل الخدمة

</a>

<a
href="services.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-lg"
onclick="return confirm('هل تريد حذف هذه الخدمة؟');">

<i class="bi bi-trash"></i>

حذف

</a>

<a
href="services.php"
class="btn btn-secondary btn-lg">

<i class="bi bi-arrow-right-circle"></i>

الرجوع

</a>

</div>

</div>

</div>

</div>

</div>

</div>

<script>

const img=document.querySelector(".service-image");

img.addEventListener("mousemove",function(){

this.style.transform="scale(1.05)";

this.style.transition=".3s";

});

img.addEventListener("mouseleave",function(){

this.style.transform="scale(1)";

});

</script>

</body>

</html>