<?php
session_start();
include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');


if(isset($_POST['save'])){

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $status   = $_POST['status'];
    $role     = $_POST['role'];

    if(
        empty($name) ||
        empty($email) ||
        empty($password)
    ){

        $msg = "يرجى تعبئة الحقول المطلوبة";

    }

    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){

        $msg = "البريد الإلكتروني غير صحيح";

    }

    else{

        /*=========================
            التحقق من الإيميل
        =========================*/

        $check = $con->prepare("
        SELECT id
        FROM admin
        WHERE email=?
        ");

        $check->bind_param("s",$email);
        $check->execute();

        if($check->get_result()->num_rows > 0){

            $msg = "البريد الإلكتروني مستخدم مسبقاً";

        }

        else{

            /*=========================
                رفع الصورة
            =========================*/
$image = "";

if(!empty($_FILES['image']['name'])){

    $folder = "uploads/admin/";

    if(!file_exists($folder)){
        mkdir($folder,0777,true);
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    $allow = ['jpg','jpeg','png','gif','webp'];

    if(in_array($ext,$allow)){

        $image = time().rand(1000,9999).".".$ext;

        if(move_uploaded_file($_FILES['image']['tmp_name'], $folder.$image)){

    echo "تم رفع الصورة: ".$folder.$image."<br>";

}else{

    die("فشل رفع الصورة");
}

    }

}

/* إذا لم يرفع صورة */
if(empty($image)){

    $image = setting('company_logo');

}
            /*=========================
                حفظ المدير
            =========================*/

            $stmt = $con->prepare("

            INSERT INTO admin(

                name,
                email,
                phone,
                password,
                image,
                role,
                status,
                login_count

            )

            VALUES(

                ?,?,?,?,?,?,?,0

            )

            ");

            $stmt->bind_param(

                "sssssss",

                $name,
                $email,
                $phone,
                $password,
                $image,
                $role,
                $status

            );

            if($stmt->execute()){

                header(
                "Location:addadmin.php?success=1"
                );

                exit();

            }else{

                $msg =
                "خطأ أثناء الحفظ";

            }

        }

    }

}
?>



<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('add_admin') ?></title>

<style>
body{
    font-family:'Cairo',sans-serif;
    background:#f4f6f9;
}

.form-box{
    width:40%;
    margin:60px auto;
    padding:25px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h1{
    text-align:center;
    margin-bottom:20px;
}

label{
    display:block;
    margin-top:12px;
    font-weight:bold;
}

input{
    width:100%;
    padding:12px;
    margin-top:5px;
    border:1px solid #ddd;
    border-radius:8px;
    outline:none;
}

.button{
    width:100%;
    margin-top:20px;
    padding:14px;
    background:linear-gradient(135deg,#3498db,#2980b9);
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
    transition:.3s;
}

.button:hover{
    transform:translateY(-2px);
}

.message{
    text-align:center;
    margin-top:10px;
    font-weight:bold;
}

.success{
    color:green;
}

.error{
    color:red;
}

.lang{
    text-align:center;
    margin-top:20px;
}
/* حل مشكلة الأعمدة البيضاء في جدول الطلبات */
body.dark-mode table,
body.dark-mode .table {
    background: #1e1e1e !important;
    color: #fff !important;
}

body.dark-mode .table td,
body.dark-mode .table th {
    background: #1e1e1e !important;
    color: #fff !important;
    border-color: #333 !important;
}

/* لو عندك Bootstrap striped */
body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
    background: #2a2a2a !important;
}

body.dark-mode .table-striped > tbody > tr:nth-of-type(even) > * {
    background: #1e1e1e !important;
}
</style>
   <link rel="stylesheet" href="assets/dark-mode.css">
</head>
<body>

<div class="lang">
    <a href="?lang=ar">🇸🇦 عربي</a> |
    <a href="?lang=en">🇬🇧 English</a>
    <!-- <button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button> -->

</div>

<div class="form-box">

<h1><?= t('add_admin') ?></h1>

<?php if(isset($_GET['success'])): ?>
    <p class="message success"><?= t('success_add') ?></p>
<?php endif; ?>

<?php if(!empty($msg)): ?>
    <p class="message error"><?= $msg ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div style="text-align:center;margin-bottom:25px;">

<?php

$logo = "../uploads/logo/" . setting('company_logo');

if(!file_exists($logo)){
    $logo = "../images/user.png";
}




?>

<img
    id="preview"
    src="<?= $logo ?>"
    class="rounded-circle border shadow"
    style="
        width:150px;
        height:150px;
        object-fit:contain;
        background:#fff;
        padding:8px;
        border:4px solid #0d6efd;
    ">

<br><br>

<input
type="file"
name="image"
id="image"
accept="image/*">

</div>


<label><?= t('username') ?></label>

<input
type="text"
name="name"
required>


<label><?= t('email') ?></label>

<input
type="email"
name="email"
required>


<label>رقم الجوال</label>

<input
type="text"
name="phone">


<label><?= t('password') ?></label>

<div style="position:relative;">

<input
type="password"
name="password"
id="password"
required>

<span
id="togglePassword"
style="
position:absolute;
left:15px;
top:14px;
cursor:pointer;
font-size:18px;
">
👁️
</span>

</div>


<label>نوع المدير</label>

<select
name="role"
style="
width:100%;
padding:12px;
border-radius:8px;
">

<option value="Super Admin">
Super Admin
</option>

<option value="Admin" selected>
Admin
</option>

<option value="Supervisor">
Supervisor
</option>

</select>

<br><br>

<label>الحالة</label>

<select
name="status"
style="
width:100%;
padding:12px;
border-radius:8px;
">

<option value="Active">
نشط
</option>

<option value="Inactive">
موقوف
</option>

</select>

<br>

<button
class="button"
type="submit"
name="save">

<i class="fa fa-save"></i>

<?= t('add_admin') ?>

</button>

</form>
</div>
<!-- FontAwesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

/*=========================================
      إظهار وإخفاء كلمة المرور
=========================================*/

const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click",function(){

    if(password.type==="password"){

        password.type="text";
        this.innerHTML="🙈";

    }else{

        password.type="password";
        this.innerHTML="👁️";

    }

});


/*=========================================
        معاينة الصورة
=========================================*/

const imageInput=document.getElementById("image");
const preview=document.getElementById("preview");

imageInput.addEventListener("change",function(){

    const file=this.files[0];

    if(!file) return;

    if(!file.type.startsWith("image/")){

        Swal.fire({

            icon:"error",
            title:"خطأ",
            text:"يرجى اختيار صورة فقط"

        });

        this.value="";
        return;

    }

    if(file.size>2*1024*1024){

        Swal.fire({

            icon:"warning",
            title:"تنبيه",
            text:"حجم الصورة يجب ألا يتجاوز 2MB"

        });

        this.value="";
        return;

    }

    const reader=new FileReader();

    reader.onload=function(e){

        preview.src=e.target.result;

    }

    reader.readAsDataURL(file);

});

</script>
</body>
</html>