<?php
session_start();

include('../include/core.php');
include('../include/connected.php');

if(isset($_POST['proadd'])){

    $proname     = trim($_POST['proname']);
    $proprice    = trim($_POST['proprice']);
    $prosection  = trim($_POST['prosection']);
    $prodescrip  = trim($_POST['prodescrip']);
    $prounv      = trim($_POST['prounv']);

    if(empty($proname) || empty($proprice) || empty($prosection)){

        $_SESSION['msg']="يرجى تعبئة جميع الحقول";

    }else{

        $image="";

        if(isset($_FILES['proimg']) && $_FILES['proimg']['error']==0){

            $ext=strtolower(pathinfo($_FILES['proimg']['name'],PATHINFO_EXTENSION));

            $allow=['jpg','jpeg','png','gif','webp'];

            if(in_array($ext,$allow)){

                $image=time().'_'.rand(1000,9999).".".$ext;

                move_uploaded_file(
                    $_FILES['proimg']['tmp_name'],
                    "../uploads/img/".$image
                );

            }

        }

        $stmt=$con->prepare("
        INSERT INTO product
        (
            proname,
            proimg,
            proprice,
            prosection,
            prodescrip,
            prounv
        )
        VALUES
        (?,?,?,?,?,?)
        ");

        $stmt->bind_param(
        "ssssss",
        $proname,
        $image,
        $proprice,
        $prosection,
        $prodescrip,
        $prounv
        );

        if($stmt->execute()){

            $_SESSION['success']="تم إضافة الخدمة بنجاح";

            header("Location: services.php");

            exit();

        }else{

            $_SESSION['msg']="حدث خطأ أثناء الحفظ";

        }

    }

}
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $lang=="ar"?"rtl":"ltr" ?>">
<head>

<meta charset="utf-8">

<title>إضافة خدمة</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

body{
background:#f4f6f9;
}

.header{
background:linear-gradient(135deg,#0d6efd,#0048b3);
padding:25px;
border-radius:18px;
color:#fff;
margin-bottom:25px;
box-shadow:0 10px 25px rgba(0,0,0,.12);
}

.card{
border:none;
border-radius:18px;
box-shadow:0 5px 20px rgba(0,0,0,.1);
}

.upload-box{
border:2px dashed #0d6efd;
border-radius:15px;
padding:25px;
text-align:center;
cursor:pointer;
transition:.3s;
}

.upload-box:hover{
background:#eef5ff;
}

#preview{
max-width:220px;
display:none;
margin:auto;
border-radius:12px;
margin-top:15px;
}

</style>
</head>

<body>

<div class="container py-4">

<div class="header">

<h2>

<i class="bi bi-box-seam"></i>

إضافة خدمة جديدة

</h2>

<p class="mb-0">

منصة الشرق الذكية للخدمات وإدارة الأسطول

</p>

</div>
<?php if(isset($_SESSION['msg'])){ ?>

<div class="alert alert-danger">

<?= $_SESSION['msg']; ?>

</div>

<?php unset($_SESSION['msg']); } ?>

<?php if(isset($_SESSION['success'])){ ?>

<div class="alert alert-success">

<?= $_SESSION['success']; ?>

</div>

<?php unset($_SESSION['success']); } ?>

<div class="card">

<div class="card-body">

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-8">

<label class="form-label">

اسم الخدمة

</label>

<input
type="text"
name="proname"
class="form-control"
required>

</div>

<div class="col-md-4">

<label class="form-label">

السعر

</label>

<input
type="number"
step="0.01"
name="proprice"
class="form-control"
required>

</div>

<div class="col-md-6 mt-3">

<label class="form-label">

القسم

</label>

<select
name="prosection"
class="form-select"
required>

<option value="">اختر القسم</option>

<?php

$res=mysqli_query($con,"SELECT * FROM section ORDER BY sectionname");

while($sec=mysqli_fetch_assoc($res)){

?>

<option value="<?= $sec['sectionname'] ?>">

<?= $sec['sectionname'] ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-6 mt-3">

<label class="form-label">

توفر الخدمة

</label>

<select
name="prounv"
class="form-select">

<option value="متوفر">

متوفر

</option>

<option value="غير متوفر">

غير متوفر

</option>

</select>

</div>

<div class="col-md-12 mt-3">

<label class="form-label">

تفاصيل الخدمة

</label>

<textarea
name="prodescrip"
rows="5"
class="form-control"
required>

</textarea>

</div>

<div class="col-md-12 mt-4">

<label class="form-label">

صورة الخدمة

</label>

<div
class="upload-box"
onclick="document.getElementById('proimg').click()">

<i class="bi bi-cloud-arrow-up"
style="font-size:60px;color:#0d6efd"></i>

<h5 class="mt-3">

اضغط لاختيار الصورة

</h5>

<p>

JPG - PNG - WEBP

</p>

<input
type="file"
name="proimg"
id="proimg"
accept="image/*"
hidden>

<img
id="preview">

</div>

</div>

<div class="col-md-12 mt-4 text-center">

<button
class="btn btn-success btn-lg"
name="proadd">

<i class="bi bi-check-circle"></i>

حفظ الخدمة

</button>

<a
href="products.php"
class="btn btn-secondary btn-lg">

رجوع

</a>

</div>

</div>

</form>

</div>

</div>

</div>

<script>

document
.getElementById("proimg")
.onchange=function(e){

const file=e.target.files[0];

if(!file)return;

const reader=new FileReader();

reader.onload=function(){

document
.getElementById("preview")
.src=reader.result;

document
.getElementById("preview")
.style.display="block";

}

reader.readAsDataURL(file);

}

</script>

</body>

</html>