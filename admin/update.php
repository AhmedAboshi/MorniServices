<?php
session_start();

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');

/*=========================
  جلب الخدمة
=========================*/

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("رقم الخدمة غير صحيح");
}

$id = (int)$_GET['id'];

$stmt = $con->prepare("SELECT * FROM product WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if(!$product){
    die("الخدمة غير موجودة");
}

/*=========================
  حفظ التعديل
=========================*/

if(isset($_POST['update'])){

    $proname     = trim($_POST['proname']);
    $proprice    = trim($_POST['proprice']);
    $prosection  = trim($_POST['prosection']);
    $prodescrip  = trim($_POST['prodescrip']);
    $prounv      = trim($_POST['prounv']);

    $proimg = $product['proimg'];

    /*=========================
      رفع صورة جديدة
    =========================*/

    if(
        isset($_FILES['proimg']) &&
        $_FILES['proimg']['error']==0
    ){

        $ext = strtolower(pathinfo(
            $_FILES['proimg']['name'],
            PATHINFO_EXTENSION
        ));

        $allow = ['jpg','jpeg','png','webp'];

        if(in_array($ext,$allow)){

            $proimg = time()."_".$_FILES['proimg']['name'];

            move_uploaded_file(
                $_FILES['proimg']['tmp_name'],
                "../uploads/img/".$proimg
            );

        }

    }

    /*=========================
      تحديث البيانات
    =========================*/

    $stmt = $con->prepare("
    UPDATE product SET

    proname=?,
    proimg=?,
    proprice=?,
    prosection=?,
    prodescrip=?,
    prounv=?

    WHERE id=?

    ");

    $stmt->bind_param(

        "ssdsssi",

        $proname,
        $proimg,
        $proprice,
        $prosection,
        $prodescrip,
        $prounv,
        $id

    );

    if($stmt->execute()){

        header("Location: services.php?updated=1");
        exit();

    }

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>تعديل الخدمة</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body{
    background:#f4f6f9;
}

.page-header{
    background:linear-gradient(135deg,#0d6efd,#0b5ed7);
    color:#fff;
    padding:25px;
    border-radius:18px;
    margin-bottom:25px;
    box-shadow:0 10px 30px rgba(0,0,0,.15);
}

.card{
    border:none;
    border-radius:18px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.form-control,
.form-select{
    border-radius:12px;
}

.product-image{

    width:220px;
    height:220px;

    border-radius:15px;

    object-fit:cover;

    border:3px solid #dee2e6;

}

.preview{

    width:220px;
    height:220px;

    display:none;

    border-radius:15px;

    object-fit:cover;

    border:3px solid #198754;

}

</style>

</head>

<body>

<div class="container py-4">

<div class="page-header d-flex justify-content-between align-items-center">

<div>

<h2>

<i class="bi bi-pencil-square"></i>

تعديل الخدمة

</h2>

<p class="mb-0">

منصة الشرق الذكية للخدمات وإدارة الأسطول

</p>

</div>

<a href="products.php" class="btn btn-light">

<i class="bi bi-arrow-right-circle"></i>

رجوع

</a>

</div>

<div class="card">

<div class="card-body">

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-4 text-center">

<h5 class="mb-3">

الصورة الحالية

</h5>

<img

src="../uploads/img/<?= $product['proimg'] ?>"

class="product-image"

id="oldImage">

<br><br>

<img

id="preview"

class="preview">

<br><br>

<input

type="file"

name="proimg"

class="form-control"

accept="image/*"

onchange="previewImage(event)">

</div>

<div class="col-md-8">

<div class="mb-3">

<label class="form-label">

عنوان الخدمة

</label>

<input

type="text"

name="proname"

class="form-control"

required

value="<?= htmlspecialchars($product['proname']) ?>">

</div>

<div class="row">

<div class="col-md-6">

<label>

السعر

</label>

<input

type="number"

step="0.01"

name="proprice"

class="form-control"

required

value="<?= $product['proprice'] ?>">

</div>

<div class="col-md-6">

<label>

توفر الخدمة

</label>

<select

name="prounv"

class="form-select">

<option value="متوفر"

<?= $product['prounv']=="متوفر"?"selected":"" ?>>

متوفر

</option>

<option value="غير متوفر"

<?= $product['prounv']=="غير متوفر"?"selected":"" ?>>

غير متوفر

</option>

</select>

</div>

</div>

<br>

<div class="mb-3">

<label>

القسم

</label>

<select

name="prosection"

class="form-select">

<?php

$sec = mysqli_query($con,"SELECT * FROM section");

while($s = mysqli_fetch_assoc($sec)){

?>

<option

value="<?= $s['sectionname'] ?>"

<?= $s['sectionname']==$product['prosection']?'selected':'' ?>>

<?= $s['sectionname'] ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label>

تفاصيل الخدمة

</label>

<textarea

rows="6"

name="prodescrip"

class="form-control"

required><?= htmlspecialchars($product['prodescrip']) ?></textarea>

</div>

<div class="text-center">

<button

class="btn btn-success btn-lg"

name="update">

<i class="bi bi-check-circle"></i>

حفظ التعديلات

</button>

</div>

</div>

</div>

</form>

</div>

</div>

</div>
<script>

function previewImage(event){

    let file = event.target.files[0];

    if(!file) return;

    let reader = new FileReader();

    reader.onload = function(e){

        document.getElementById("preview").src = e.target.result;

        document.getElementById("preview").style.display = "inline-block";

        document.getElementById("oldImage").style.opacity = ".35";

    };

    reader.readAsDataURL(file);

}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>