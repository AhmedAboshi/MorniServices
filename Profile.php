<?php
session_start();
include('include/connected.php');

/* =========================
   التحقق من تسجيل الدخول
========================= */

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

/* =========================
   اللغة
========================= */

if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* =========================
   الترجمة
========================= */

$t = [

'ar'=>[
'title'=>'الملف الشخصي',
'welcome'=>'مرحباً',
'username'=>'الاسم',
'email'=>'البريد الإلكتروني',
'phone'=>'رقم الجوال',
'edit'=>'تعديل الملف',
'logout'=>'تسجيل الخروج',
'back'=>'الرئيسية'
],

'en'=>[
'title'=>'Profile',
'welcome'=>'Welcome',
'username'=>'Name',
'email'=>'Email',
'phone'=>'Phone',
'edit'=>'Edit Profile',
'logout'=>'Logout',
'back'=>'Home'
]

];

/* =========================
   بيانات المستخدم
========================= */

$user_id = (int)$_SESSION['user_id'];

$query = mysqli_query(
    $con,
    "SELECT * FROM users WHERE id = $user_id"
);

$user = mysqli_fetch_assoc($query);

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar' ? 'rtl' : 'ltr' ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $t[$lang]['title'] ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Cairo',sans-serif;
}

body{
background:#f3f4f6;
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:20px;
}

/* ======================
PROFILE CARD
====================== */

.profile-container{
width:100%;
max-width:450px;
}

.profile-card{
background:#fff;
border-radius:25px;
overflow:hidden;
box-shadow:0 10px 30px rgba(0,0,0,.1);
animation:fade .5s ease;
}

@keyframes fade{
from{
opacity:0;
transform:translateY(20px);
}
to{
opacity:1;
transform:translateY(0);
}
}

/* ======================
HEADER
====================== */

.profile-header{
background:linear-gradient(135deg,#ff9800,#ff5722);
padding:35px 20px;
text-align:center;
position:relative;
}

.lang-switch{
position:absolute;
top:15px;
<?= $lang=='ar' ? 'left' : 'right' ?>:15px;
display:flex;
gap:8px;
}

.lang-switch a{
text-decoration:none;
background:#fff;
color:#111;
padding:6px 12px;
border-radius:8px;
font-size:13px;
font-weight:bold;
transition:.3s;
}

.lang-switch a:hover{
background:#111;
color:#fff;
}

.avatar{
width:120px;
height:120px;
border-radius:50%;
border:5px solid #fff;
object-fit:cover;
background:#fff;
box-shadow:0 5px 15px rgba(0,0,0,.2);
}

.profile-header h2{
margin-top:15px;
color:#fff;
font-size:24px;
}

/* ======================
BODY
====================== */

.profile-body{
padding:30px;
}

.info{
margin-bottom:20px;
}

.info label{
display:block;
margin-bottom:7px;
font-size:14px;
color:#888;
font-weight:600;
}

.info div{
background:#f7f7f7;
padding:14px;
border-radius:12px;
font-size:16px;
color:#333;
}

/* ======================
BUTTONS
====================== */

.buttons{
display:flex;
gap:12px;
margin-top:30px;
flex-wrap:wrap;
}

.btn{
flex:1;
min-width:120px;
text-align:center;
padding:13px;
border-radius:12px;
text-decoration:none;
color:#fff;
font-size:15px;
font-weight:600;
transition:.3s;
}

.edit-btn{
background:#ff9800;
}

.edit-btn:hover{
background:#e68900;
transform:translateY(-2px);
}

.logout-btn{
background:#e53935;
}

.logout-btn:hover{
background:#c62828;
transform:translateY(-2px);
}

.home-btn{
background:#333;
}

.home-btn:hover{
background:#111;
transform:translateY(-2px);
}

/* ======================
RESPONSIVE
====================== */

@media(max-width:500px){

.profile-header{
padding-top:70px;
}

.buttons{
flex-direction:column;
}

}

</style>

</head>

<body>

<div class="profile-container">

<div class="profile-card">

<div class="profile-header">

<!-- اللغة -->

<div class="lang-switch">

<a href="?lang=ar">🇸🇦 عربي</a>

<a href="?lang=en">🇬🇧 English</a>

</div>

<!-- صورة المستخدم -->


<?php

/* =========================================================
   USER IMAGE
========================================================= */

$user_image = 'assets/default-user.png';

$saved_image = trim((string)($user['image'] ?? ''));

if ($saved_image !== '') {

    $saved_image = basename($saved_image);

    $physical_path =
        __DIR__ . '/upload/users/' . $saved_image;

    if (is_file($physical_path)) {

        $user_image =
            'upload/users/' .
            rawurlencode($saved_image);
    }
}

?>

<img
    src="<?= htmlspecialchars($user_image, ENT_QUOTES, 'UTF-8') ?>"
    class="avatar"
    alt="<?= htmlspecialchars($user['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>"
    onerror="this.onerror=null;this.src='assets/default-user.png';"
>


<h2>

<?= $t[$lang]['welcome'] ?>

<?= htmlspecialchars($user['username']) ?>

</h2>

</div>

<div class="profile-body">

<!-- الاسم -->

<div class="info">

<label>
<?= $t[$lang]['username'] ?>
</label>

<div>
<?= htmlspecialchars($user['username']) ?>
</div>

</div>

<!-- البريد -->

<div class="info">

<label>
<?= $t[$lang]['email'] ?>
</label>

<div>
<?= htmlspecialchars($user['email']) ?>
</div>

</div>

<!-- الجوال -->

<div class="info">

<label>
<?= $t[$lang]['phone'] ?>
</label>

<div>
<?= htmlspecialchars($user['phone']) ?>
</div>

</div>

<!-- الأزرار -->

<div class="buttons">

<a href="edit-profile.php" class="btn edit-btn">

<?= $t[$lang]['edit'] ?>

</a>

<a href="index.php" class="btn home-btn">

<?= $t[$lang]['back'] ?>

</a>

<a href="user/logout.php" class="btn logout-btn">

<?= $t[$lang]['logout'] ?>

</a>

</div>

</div>

</div>

</div>

</body>
</html>

