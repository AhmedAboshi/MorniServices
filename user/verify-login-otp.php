<?php

session_start();

include('../include/connected.php');
include('../admin/mail.php');

date_default_timezone_set('Asia/Riyadh');


/* =========================
   حماية الصفحة
========================= */

if(
    !isset($_SESSION['login_otp_user_id']) ||
    !isset($_SESSION['login_otp_email'])
){
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['login_otp_user_id'];
$email   = $_SESSION['login_otp_email'];


/* =========================
   التحقق من OTP
========================= */

if(isset($_POST['verify'])){

    $otp = trim($_POST['otp']);

    $stmt = $con->prepare("
        SELECT id, username, image, email
        FROM users
        WHERE id = ?
        AND email = ?
        AND otp_code = ?
        AND otp_expire >= NOW()
        LIMIT 1
    ");

    $stmt->bind_param(
        "iss",
        $user_id,
        $email,
        $otp
    );

    $stmt->execute();

    $result = $stmt->get_result();


    if($result->num_rows > 0){

        $user = $result->fetch_assoc();


        /* =========================
           إنشاء جلسة المستخدم
        ========================= */

        session_regenerate_id(true);

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['image']    = $user['image'];


        /* =========================
           إلغاء OTP بعد الاستخدام
        ========================= */

        $clear = $con->prepare("
            UPDATE users
            SET otp_code = NULL,
                otp_expire = NULL
            WHERE id = ?
        ");

        $clear->bind_param("i", $user_id);
        $clear->execute();


        /* =========================
           تنظيف جلسة OTP
        ========================= */

        unset($_SESSION['login_otp_user_id']);
        unset($_SESSION['login_otp_email']);


        /* =========================
           دخول النظام
        ========================= */

        header("Location: ../index.php");
        exit();

    }else{

        $error = "رمز OTP غير صحيح أو منتهي الصلاحية";
    }
}


/* =========================
   إعادة إرسال OTP
========================= */

if(isset($_POST['resend'])){

    $otp = rand(100000, 999999);

    $expire = date(
        'Y-m-d H:i:s',
        time() + 600
    );


    $update = $con->prepare("
        UPDATE users
        SET otp_code = ?,
            otp_expire = ?
        WHERE id = ?
        AND email = ?
    ");

    $update->bind_param(
        "ssis",
        $otp,
        $expire,
        $user_id,
        $email
    );

    $update->execute();


    sendOTP($email, $otp);


    $success = "تم إرسال رمز OTP جديد إلى بريدك الإلكتروني";
}

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>التحقق من تسجيل الدخول</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:Tahoma,Arial;

    background:
    linear-gradient(
        135deg,
        #0f172a,
        #1e3a8a
    );

    min-height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;
}


.card{

    width:420px;

    max-width:92%;

    background:#fff;

    padding:35px;

    border-radius:20px;

    text-align:center;

    box-shadow:
    0 20px 60px
    rgba(0,0,0,.3);
}


.logo{

    width:75px;

    height:75px;

    object-fit:contain;

    margin-bottom:10px;
}


h2{

    color:#1e40af;

    margin-bottom:10px;
}


.info{

    color:#6b7280;

    font-size:14px;

    line-height:1.8;

    margin-bottom:20px;
}


.email{

    color:#1e40af;

    font-weight:bold;
}


input{

    width:100%;

    padding:15px;

    border:1px solid #ddd;

    border-radius:12px;

    text-align:center;

    font-size:22px;

    letter-spacing:7px;

    outline:none;
}


input:focus{

    border-color:#2563eb;
}


button{

    width:100%;

    padding:14px;

    margin-top:15px;

    border:none;

    border-radius:12px;

    font-size:16px;

    cursor:pointer;
}


.verify{

    background:#2563eb;

    color:#fff;
}


.verify:hover{

    background:#1d4ed8;
}


.resend{

    background:#16a34a;

    color:#fff;
}


.resend:hover{

    background:#15803d;
}


.resend:disabled{

    background:#94a3b8;

    cursor:not-allowed;
}


.timer{

    margin-top:18px;

    padding:12px;

    background:#eff6ff;

    border-radius:10px;

    color:#1e40af;

    font-weight:bold;
}


.error{

    background:#fee2e2;

    color:#b91c1c;

    padding:10px;

    border-radius:10px;

    margin-bottom:15px;
}


.success{

    background:#dcfce7;

    color:#15803d;

    padding:10px;

    border-radius:10px;

    margin-bottom:15px;
}

</style>

</head>

<body>


<div class="card">


<img
src="../img/logo.jpg"
class="logo"
alt="Logo"
>


<h2>
التحقق من تسجيل الدخول
</h2>


<div class="info">

تم إرسال رمز التحقق OTP إلى:

<br>

<span class="email">
<?= htmlspecialchars($email) ?>
</span>

<br>

أدخل الرمز لإكمال تسجيل الدخول.

</div>


<?php if(isset($error)): ?>

<div class="error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<?php if(isset($success)): ?>

<div class="success">
<?= htmlspecialchars($success) ?>
</div>

<?php endif; ?>


<form method="POST">

<input
type="text"
name="otp"
maxlength="6"
inputmode="numeric"
autocomplete="one-time-code"
placeholder="------"
required
>


<button
type="submit"
name="verify"
class="verify"
>
تحقق ودخول
</button>

</form>


<form method="POST">

<button
type="submit"
name="resend"
id="resendBtn"
class="resend"
disabled
>
إعادة إرسال OTP
</button>

</form>


<div class="timer">

الوقت المتبقي:

<span id="countdown">
10:00
</span>

</div>


</div>


<script>

let time = 600;

let btn = document.getElementById("resendBtn");

let timer = setInterval(function(){

    let minutes = Math.floor(time / 60);

    let seconds = time % 60;

    seconds =
        seconds < 10
        ? "0" + seconds
        : seconds;

    document.getElementById("countdown").textContent =
        minutes + ":" + seconds;


    if(time <= 0){

        clearInterval(timer);

        document.getElementById("countdown").textContent =
            "يمكنك إعادة إرسال OTP";

        btn.disabled = false;

    }

    time--;

},1000);

</script>

</body>

</html>