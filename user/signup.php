<?php
session_start();
include('../include/connected.php');
include('../include/settings.php');

/* 🌐 اللغة */
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ar';

/* الترجمة */
$t = [
'ar'=>[
'title'=>'تسجيل مستخدم جديد',
'username'=>'اسم المستخدم',
'phone'=> 'جوال العميل',
'email'=>'البريد الإلكتروني',
'password'=>'كلمة المرور',
'register'=>'تسجيل الآن',
'login'=>'تسجيل الدخول',
'already'=>'لديك حساب بالفعل؟'
],
'en'=>[
'title'=>'Create New Account',
'username'=>'Username',
'phone'=>'phone',
'email'=>'Email',
'password'=>'Password',
'register'=>'Register',
'login'=>'Login',
'already'=>'Already have an account?'
]
];

/* التسجيل */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = mysqli_real_escape_string($con, $_POST['username']);

    $email = mysqli_real_escape_string($con, $_POST['email']);

    $phone = mysqli_real_escape_string($con, $_POST['phone']);

    
$password = $_POST['password'];



/* =========================
   رفع الصورة
========================= */

$image = 'default-user.png';

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

    $image_name = time().'_'.basename($_FILES['image']['name']);

    $target = "uploads/".$image_name;

    if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){

        $image = $image_name;

    }

}


    /* =========================
       التحقق من البريد
    ========================= */

    $check = mysqli_query(
        $con,
        "SELECT id FROM users WHERE email='$email'"
    );

    if (mysqli_num_rows($check) > 0) {

        echo "<script>alert('هذا البريد مستخدم بالفعل');</script>";

    } else {

        /* =========================
           إضافة المستخدم
        ========================= */

        mysqli_query($con,

            "INSERT INTO users
            (username,phone,email,password,image,login_type)

            VALUES

            ('$username','$phone','$email','$password','$image','normal')"
        );

        echo "<script>

        alert('تم إنشاء الحساب بنجاح');

        window.location.href='login.php';

        </script>";
    }
}
    ?>


<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= $t[$lang]['title'] ?></title>

<style>
body {
    margin: 0;
    font-family: Arial;
    background: #cfd6dd;
}

/* 🔵 الهيدر */
.header {
    background: #2d89ef;
    color: #fff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* اللوجو */
.header .logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.header img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
}

/* 🌐 اللغة في الهيدر */
.lang-switch {
    display: flex;
    gap: 10px;
}

.lang-switch a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 6px;
    transition: 0.3s;
}

.lang-switch a:hover {
    background: rgba(255,255,255,0.2);
}

/* 🟦 الكرت */
.form-box {
    width: 350px;
    margin: 120px auto;
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.form-box h2 {
    text-align: center;
    margin-bottom: 20px;
}

.form-box input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.form-box button {
    width: 100%;
    padding: 10px;
    background: #2d89ef;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.form-box button:hover {
    background: #1b5fbf;
}

/* الفوتر */
.footer {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.footer a {
    color: #2d89ef;
    font-weight: bold;
    text-decoration: none;
}
</style>

</head>

<body>

<!-- 🔵 الهيدر -->
<div class="header">
    <div class="logo">
         <img src="../img/logo.jpg" alt="logo">
        <span><?= setting('system_name') ?></span>
    </div>

    <!-- 🌐 اللغة في الهيدر -->
    <div class="lang-switch">
        <a href="?lang=ar">العربية</a>
        <a href="?lang=en">English</a>
    </div>
</div>

<!-- 🟦 الفورم -->
<div class="form-box">

<h2><?= $t[$lang]['title'] ?></h2>

<form method="POST" enctype="multipart/form-data">

    <!-- اسم المستخدم -->

    <input
    type="text"
    name="username"
    placeholder="<?= $t[$lang]['username'] ?>"
    autocomplete="username"
    required>

    <!-- رقم الجوال -->

    <input
    type="tel"
    name="phone"
    placeholder="<?= $t[$lang]['phone'] ?>"
    autocomplete="tel"
    required>

    <!-- البريد -->

    <input
    type="email"
    name="email"
    placeholder="<?= $t[$lang]['email'] ?>"
    autocomplete="email"
    required>

    <!-- كلمة المرور -->

    <input
    type="password"
    name="password"
    placeholder="<?= $t[$lang]['password'] ?>"
    autocomplete="new-password"
    required>

    <!-- صورة المستخدم -->

  

<div class="upload-box">

<label for="image">

📷 <?= $lang=='ar'
? 'صورة الملف الشخصي'
: 'Profile Image' ?>

</label>

<input
type="file"
id="image"
name="image"
accept="image/*">

</div>





    <!-- زر التسجيل -->

    <button type="submit">

        <?= $t[$lang]['register'] ?>

    </button>

</form>

</div>



    <div class="footer">
        <?= $t[$lang]['already'] ?>
        <a href="login.php"><?= $t[$lang]['login'] ?></a>
    </div>

</div>

</body>
</html>