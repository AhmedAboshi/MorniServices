<?php
session_start();
include('../include/connected.php');

/*==============================
   جلب رقم المستخدم
==============================*/

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id==0){
    die("رقم المستخدم غير صحيح");
}

/*==============================
   جلب بيانات المستخدم
==============================*/

$stmt = $con->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    die("المستخدم غير موجود");
}

/*==============================
   حفظ التعديل
==============================*/

if(isset($_POST['save'])){

    $username = trim($_POST['username']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if($username=="" || $phone=="" || $email==""){
        $msg="يرجى تعبئة جميع الحقول";
    }

    else{

        /*=========================
            البريد الإلكتروني
        =========================*/

        $check=$con->prepare("
        SELECT id
        FROM users
        WHERE email=?
        AND id<>?
        ");

        $check->bind_param("si",$email,$id);
        $check->execute();

        if($check->get_result()->num_rows>0){

            $msg="البريد الإلكتروني مستخدم مسبقاً";

        }else{

            /*=========================
                الصورة
            =========================*/

            $image = $user['image'];

            if(!empty($_FILES['image']['name'])){

                $folder="../upload/users/";

                if(!file_exists($folder)){
                    mkdir($folder,0777,true);
                }

                $ext = strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));

                $allow=['jpg','jpeg','png','gif','webp'];

                if(in_array($ext,$allow)){

                    $newImage=time().rand(1000,9999).".".$ext;

                    if(move_uploaded_file($_FILES['image']['tmp_name'],$folder.$newImage)){

                        $image=$newImage;

                    }

                }

            }

            /*=========================
             تحديث مع كلمة مرور
            =========================*/

            if($password!=""){

                $update=$con->prepare("

                UPDATE users SET

                username=?,
                phone=?,
                email=?,
                password=?,
                image=?

                WHERE id=?

                ");

                $update->bind_param(

                "sssssi",

                $username,
                $phone,
                $email,
                $password,
                $image,
                $id

                );

            }

            /*=========================
            بدون كلمة مرور
            =========================*/

            else{

                $update=$con->prepare("

                UPDATE users SET

                username=?,
                phone=?,
                email=?,
                image=?

                WHERE id=?

                ");

                $update->bind_param(

                "ssssi",

                $username,
                $phone,
                $email,
                $image,
                $id

                );

            }

         if($update->execute()){

    header("Location: userview.php?success=1");
    exit();

}else{

    die("خطأ MySQL: ".$update->error);

}

        }

    }

}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>تعديل المستخدم</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>

body{
    background:#f4f6f9;
    font-family:Tahoma;
}

.card{
    border:0;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,.12);
}

.card-header{
    border-radius:18px 18px 0 0 !important;
}

.avatar{

    width:180px;
    height:180px;

    object-fit:cover;

    border-radius:50%;

    border:5px solid #dee2e6;

}

</style>

</head>

<body>

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card">

<div class="card-header bg-primary text-white">

<h3>

<i class="fa-solid fa-user-pen"></i>

تعديل بيانات المستخدم

</h3>

</div>

<div class="card-body">

<?php if(isset($msg)){ ?>

<div class="alert alert-danger">

<?= $msg ?>

</div>

<?php } ?>

<form method="POST"
enctype="multipart/form-data">

<div class="text-center mb-4">

<?php

if($user['image']!=""){

?>

<img

src="../upload/users/<?= $user['image']; ?>"

id="preview"

class="avatar"

>

<?php

}else{

?>

<img

src="../images/user.png"

id="preview"

class="avatar"

>

<?php } ?>

<br><br>

<input

type="file"

class="form-control"

name="image"

id="image"

accept="image/*"

>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>

اسم المستخدم

</label>

<input

type="text"

name="username"

class="form-control"

required

value="<?= htmlspecialchars($user['username']); ?>"

>

</div>

<div class="col-md-6 mb-3">

<label>

رقم الجوال

</label>

<input

type="text"

name="phone"

class="form-control"

required

value="<?= htmlspecialchars($user['phone']); ?>"

>

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>

البريد الإلكتروني

</label>

<input

type="email"

name="email"

class="form-control"

required

value="<?= htmlspecialchars($user['email']); ?>"

>

</div>

<div class="col-md-6 mb-3">

<label>

كلمة المرور الجديدة

</label>

<input

type="password"

name="password"

class="form-control"

placeholder="اتركها فارغة إذا لم ترغب بالتغيير"

>

</div>

</div>

<div class="text-center mt-4">

<button

class="btn btn-success btn-lg"

name="save"

>

<i class="fa-solid fa-floppy-disk"></i>

حفظ التعديلات

</button>

<a

href="userview.php"

class="btn btn-secondary btn-lg"

>

رجوع

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>