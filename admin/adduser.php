<?php
session_start();
include('../include/connected.php');
include('../include/settings.php');

/*=================================
        إضافة مستخدم جديد
==================================*/

if(isset($_POST['save'])){

    $username = trim($_POST['username']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    /*=============================
        التحقق من الحقول
    =============================*/

    if(empty($username) || empty($phone) || empty($email) || empty($password)){

        $msg = "يرجى تعبئة جميع الحقول";

    }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){

        $msg = "البريد الإلكتروني غير صحيح";

    }else{

        /*=============================
            التحقق من البريد
        =============================*/

        $check = $con->prepare("
        SELECT id
        FROM users
        WHERE email=?
        LIMIT 1
        ");

        $check->bind_param("s",$email);
        $check->execute();

        if($check->get_result()->num_rows>0){

            $msg="البريد الإلكتروني مستخدم مسبقاً";

        }else{

            /*=============================
                التحقق من الجوال
            =============================*/

            $checkPhone = $con->prepare("
            SELECT id
            FROM users
            WHERE phone=?
            LIMIT 1
            ");

            $checkPhone->bind_param("s",$phone);
            $checkPhone->execute();

            if($checkPhone->get_result()->num_rows>0){

                $msg="رقم الجوال مستخدم مسبقاً";

            }else{

                /*=============================
                    الصورة (اختيارية)
                =============================*/

                $image="";

                if(isset($_FILES['image']) && $_FILES['image']['error']==0){

                    $folder="../upload/users/";

                    if(!is_dir($folder)){
                        mkdir($folder,0777,true);
                    }

                    $ext=strtolower(pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    ));

                    $allow=['jpg','jpeg','png','gif','webp'];

                    if(in_array($ext,$allow)){

                        $newImage=time().rand(1000,9999).".".$ext;

                        if(move_uploaded_file(
                            $_FILES['image']['tmp_name'],
                            $folder.$newImage
                        )){

                            $image=$newImage;

                        }

                    }

                }

                /*=============================
                    حفظ المستخدم
                =============================*/

                $login_type="normal";

                $stmt=$con->prepare("

                INSERT INTO users

                (

                    username,
                    phone,
                    email,
                    password,
                    image,
                    login_type

                )

                VALUES

                (?,?,?,?,?,?)

                ");

                $stmt->bind_param(

                "ssssss",

                $username,
                $phone,
                $email,
                $password,
                $image,
                $login_type

                );

                if($stmt->execute()){

                    header("Location: userview.php?added=1");
                    exit();

                }else{

                    $msg="حدث خطأ أثناء إضافة المستخدم";

                }

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

<title>إضافة مستخدم جديد</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

box-shadow:0 5px 15px rgba(0,0,0,.15);

}

.form-control{

border-radius:10px;

min-height:45px;

}

.btn{

border-radius:10px;

padding:10px 25px;

}

.required{

color:red;

font-weight:bold;

}

</style>

</head>

<body>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card">

<div class="card-header bg-primary text-white">

<h3 class="mb-0">

<i class="fa-solid fa-user-plus"></i>

إضافة مستخدم جديد

</h3>

</div>

<div class="card-body">

<?php if(isset($msg)){ ?>

<div class="alert alert-danger">

<?= $msg ?>

</div>

<?php } ?>

<form
method="POST"
enctype="multipart/form-data">

<div class="text-center mb-4">

<img

src="../uploads/logo/<?= setting('company_logo') ?: 'logo.jpg' ?>"

id="preview"

class="avatar"

>

<br><br>

<input

type="file"

name="image"

id="image"

class="form-control"

accept="image/*"

>

<small class="text-muted">

الصورة اختيارية

</small>

</div>
<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

اسم المستخدم

<span class="required">*</span>

</label>

<input
type="text"
name="username"
class="form-control"
required
value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

رقم الجوال

<span class="required">*</span>

</label>

<input
type="text"
name="phone"
class="form-control"
required
value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

البريد الإلكتروني

<span class="required">*</span>

</label>

<input
type="email"
name="email"
class="form-control"
required
value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

كلمة المرور

<span class="required">*</span>

</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

</div>

<hr>

<div class="text-center">

<button
type="submit"
name="save"
class="btn btn-success btn-lg">

<i class="fa-solid fa-floppy-disk"></i>

حفظ المستخدم

</button>

<button
type="reset"
class="btn btn-warning btn-lg">

<i class="fa-solid fa-rotate-left"></i>

إعادة تعيين

</button>

<a
href="userview.php"
class="btn btn-secondary btn-lg">

<i class="fa-solid fa-arrow-right"></i>

رجوع

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

/*=========================================
        معاينة الصورة
=========================================*/

const imageInput = document.getElementById("image");
const preview = document.getElementById("preview");

imageInput.addEventListener("change",function(){

    const file = this.files[0];

    if(!file) return;

    /*=========================
        نوع الملف
    =========================*/

    if(!file.type.startsWith("image/")){

        Swal.fire({

            icon:'error',

            title:'خطأ',

            text:'يرجى اختيار صورة فقط'

        });

        this.value="";

        return;

    }

    /*=========================
        الحجم
    =========================*/

    if(file.size > 2 * 1024 * 1024){

        Swal.fire({

            icon:'warning',

            title:'تنبيه',

            text:'حجم الصورة يجب ألا يتجاوز 2MB'

        });

        this.value="";

        return;

    }

    /*=========================
        المعاينة
    =========================*/

    const reader = new FileReader();

    reader.onload = function(e){

        preview.src = e.target.result;

    }

    reader.readAsDataURL(file);

});

</script>

</body>
</html>
