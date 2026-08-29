<?php


session_start();
include('../admin/mail.php');
include('../include/connected.php');
require_once '../vendor/autoload.php';
include('../include/settings.php');




$permissions = ['email']; // الصلاحيات

$redirectUrl = "http://localhost/AlSharqPlatform/user/auth/fb-callback.php";

/* =========================
   🌐 تغيير اللغة
========================= */
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* =========================
   🈯 الترجمة
========================= */
$t = [

'ar'=>[
'title'=>'تسجيل الدخول',
'email'=>'البريد الإلكتروني',
'password'=>'كلمة المرور',
'login'=>'تسجيل الدخول',
'company'=>'منصة الشرق الذكية للخدمات وإدارة الأسطول',

'google'=>'تسجيل الدخول بجوجل',

'no_account'=>'ليس لديك حساب؟',

'create_account'=>'إنشاء حساب',

'empty'=>'الرجاء تعبئة جميع الحقول',

'wrong_pass'=>'كلمة المرور غير صحيحة',

'user_not_found'=>'المستخدم غير موجود'
],

'en'=>[
'title'=>'Login',
'email'=>'Email',
'password'=>'Password',
'login'=>'Login',
'company'=>'Al-Sharq Smart Platform for Services and Fleet Man.',

'google'=>'Login with Google',

'no_account'=>"Don't have an account?",

'create_account'=>'Create Account',

'empty'=>'Please fill all fields',

'wrong_pass'=>'Wrong password',

'user_not_found'=>'User not found'
]

];
/* =========================
   🔐 تسجيل الدخول بالإيميل
========================= */

if (
    isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] === "POST"
) {

    $email = trim($_POST['email']);

    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {

        echo '<script>alert("'.$t[$lang]['empty'].'");</script>';

    } else {

        $stmt = $con->prepare(

            "SELECT * FROM users WHERE email=?"

        );

        $stmt->bind_param("s",$email);

        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows > 0){

            $user = $result->fetch_assoc();

            /* =========================
               مقارنة كلمة المرور مباشرة
            ========================= */

           if($password === $user['password']){

    session_regenerate_id(true);

    /* إنشاء OTP */
    $otp = rand(100000, 999999);

    date_default_timezone_set('Asia/Riyadh');

    $expire = date(
        'Y-m-d H:i:s',
        time() + 600
    );

    /* حفظ OTP */
    $update = $con->prepare("
        UPDATE users
        SET otp_code = ?,
            otp_expire = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssi",
        $otp,
        $expire,
        $user['id']
    );

    $update->execute();

    /* إرسال OTP */
    sendOTP($user['email'], $otp);

    /* بيانات مؤقتة للتحقق */
    $_SESSION['login_otp_user_id'] = $user['id'];
    $_SESSION['login_otp_email'] = $user['email'];

    header("Location: verify-login-otp.php");
    exit();

} else {

                echo '<script>

                alert("'.$t[$lang]['wrong_pass'].'");

                </script>';
            }

        } else {

            echo '<script>

            alert("'.$t[$lang]['user_not_found'].'");

            </script>';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar' ? 'rtl':'ltr' ?>">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $t[$lang]['title'] ?></title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

body{
    margin:0;
    padding:0;
    font-family:Tahoma;
    background:#e9eef3;
}

/* =========================
   🏢 الهيدر
========================= */

.header{
    background: blue;
    color:#fff;
    padding:15px 30px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.company{
    display:flex;
    align-items:center;
    gap:15px;
}

.company img{
    width:55px;
    height:55px;
    border-radius:50%;
    background: #ccc;
    object-fit:cover;
}

.company h1{
    margin:0;
    font-size:22px;
}

/* =========================
   🌐 اللغة
========================= */

.lang-switch a{
    color:#fff;
    text-decoration:none;
    margin:0 5px;
    font-weight:bold;
}

/* =========================
   📦 الصندوق
========================= */

.form-box{
    width:370px;
    background:#fff;
    margin:70px auto;
    padding:30px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

.form-box h2{
    text-align:center;
    margin-bottom:25px;
    color:#333;
}

.form-box input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    box-sizing:border-box;
    font-size:15px;
}

.login-btn{
    width:100%;
    padding:12px;
    border:none;
    background:#2563eb;
    color:#fff;
    border-radius:10px;
    cursor:pointer;
    font-size:16px;
    transition:.3s;
}

.login-btn:hover{
    background:#1746a2;
}

/* =========================
   🔗 سوشيال
========================= */

.social-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    margin-top: 10px;
    border-radius: 8px;
    font-size: 16px;
    text-decoration: none;
    color: #fff;
    box-sizing: border-box;
}

/* Google */
.social-btn.google {
    background: #db4437;
}
.social-btn.google-btn {
  outline: none;
  box-shadow: none;
}

.social-btn.google-btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.3);
}

.google-btn:active {
  outline: none;
  box-shadow: none;
}

/
/* منع خروج العناصر خارج الصفحة */
.form-container {
    max-width: 400px;
    margin: auto;
}

.social-btn i{
    margin-left:10px;
}

/* =========================
   📝 الفوتر
========================= */

.footer{
    text-align:center;
    margin-top:20px;
}

.footer a{
    color:#2563eb;
    text-decoration:none;
    font-weight:bold;
}

.footer a:hover{
    text-decoration:underline;
}

@media(max-width:450px){

.form-box{
    width:90%;
    padding:20px;
}

.header{
    flex-direction:column;
    gap:10px;
    text-align:center;
}

}

.google-login-btn {
    width: 100%;
    min-height: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 10px;

    margin-top: 15px;

    background: #fff;

    color: #344054;

    border: 1px solid #d0d5dd;

    border-radius: 12px;

    text-decoration: none;

    font-weight: 700;

    transition: .25s;
}

.google-login-btn:hover {
    background: #f9fafb;
    border-color: #98a2b3;
    color: #111827;
    transform: translateY(-1px);
}

.google-login-btn i {
    font-size: 18px;
}
</style>
</head>
<body>

<!-- =========================
     🏢 الهيدر
========================= -->

<div class="header">

    <div class="company">

        <!-- ضع اللوقو هنا -->
       <img src="../uploads/logo/<?= setting('company_logo') ?: 'logo.jpg' ?>" alt="logo">

        <h1><?= setting('system_name') ?></h1>

    </div>

    <div class="lang-switch">

        <a href="?lang=ar">العربية</a> |
        <a href="?lang=en">English</a>

    </div>

</div>

<!-- =========================
     📦 فورم الدخول
========================= -->

<div class="form-box">

    <h2><?= $t[$lang]['title'] ?></h2>

    <form method="POST">

        <!-- البريد الإلكتروني -->

        <input
            type="email"
            name="email"
            placeholder="<?= $t[$lang]['email'] ?>"
            autocomplete="email"
            required
        >

        <!-- كلمة المرور -->

        <input
            type="password"
            name="password"
            placeholder="<?= $t[$lang]['password'] ?>"
            autocomplete="current-password"
            required
        >
<div style="text-align:left;margin-top:10px;margin-bottom: 15px; ">

<a href="forgot-password.php"
style="
text-decoration:none;
color:#007bff;
font-size:14px;
">
نسيت كلمة المرور؟
</a>

</div>
        <!-- زر الدخول -->

        <button class="login-btn" type="submit">

            <?= $t[$lang]['login'] ?>

        </button>
 <!-- =========================
         🔵 Google Login
    ========================= -->

    <a
    href="auth/google-login.php"
    class="google-login-btn"
>
    <i class="fab fa-google"></i>
    تسجيل الدخول باستخدام Google
</a>
    </form>

</div>


   

    

    <!-- =========================
         📝 إنشاء حساب
    ========================= -->

    <div class="footer">

        <p>
            <?= $t[$lang]['no_account'] ?>

            <a href="signup.php">
                <?= $t[$lang]['create_account'] ?>
            </a>
        </p>

    </div>

</div>

</body>
</html>