<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include('../include/connected.php');
include('mail.php');

date_default_timezone_set('Asia/Riyadh');

$error = '';

if(isset($_POST['send'])){

    $email = trim($_POST['email']);

    /* التحقق من وجود الأدمن */

    $stmt = $con->prepare("
        SELECT id,email
        FROM users
        WHERE email=?
        LIMIT 1
    ");

    if(!$stmt){
        die($con->error);
    }

    $stmt->bind_param("s",$email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $admin = $result->fetch_assoc();

        $admin_id = $admin['id'];

        /* إنشاء OTP */

        $otp = rand(100000,999999);

        /* وقت الانتهاء */

        $expire = date(
            'Y-m-d H:i:s',
            strtotime('+3 minutes')
        );

        /* حفظ OTP */

        $update = $con->prepare("
            UPDATE users
            SET otp_code=?,
                otp_expire=?
            WHERE id=?
        ");

        if(!$update){
            die($con->error);
        }

        $update->bind_param(
            "ssi",
            $otp,
            $expire,
            $admin_id
        );

        if($update->execute()){

            /* حفظ الإيميل بالجلسة */

            $_SESSION['reset_email'] = $email;

            /* إرسال OTP */

            $send = sendOTP($email,$otp);

            if($send){

                header("Location: verify-otp.php");
                exit();

            }else{

                $error = "فشل إرسال الإيميل";

            }

        }else{

            die($update->error);

        }

    }else{

        $error = "الإيميل غير موجود";

    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>نسيت كلمة المرور</title>

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:tahoma;
    background:
    linear-gradient(135deg,#0f172a,#1e293b);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.box{

    width:420px;

    background:
    rgba(255,255,255,.08);

    backdrop-filter:blur(10px);

    border-radius:25px;

    padding:40px;

    box-shadow:
    0 10px 40px rgba(0,0,0,.4);
}

h2{
    color:#fff;
    text-align:center;
    margin-bottom:15px;
}

.desc{
    color:#cbd5e1;
    text-align:center;
    margin-bottom:30px;
}

input{

    width:100%;

    padding:15px;

    border:none;

    border-radius:12px;

    margin-bottom:20px;

    background:
    rgba(255,255,255,.1);

    color:#fff;

    outline:none;
}

button{

    width:100%;

    padding:15px;

    border:none;

    border-radius:12px;

    background:#2563eb;

    color:#fff;

    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

.error{

    background:#dc2626;

    color:#fff;

    padding:12px;

    border-radius:10px;

    margin-bottom:20px;

    text-align:center;
}

.back{
    text-align:center;
    margin-top:20px;
}

.back a{
    color:#cbd5e1;
    text-decoration:none;
}

</style>

</head>

<body>

<div class="box">

<h2>
نسيت كلمة المرور
</h2>

<div class="desc">

أدخل بريدك  لإرسال رمز OTP

</div>

<?php if($error != ''){ ?>

<div class="error">

<?php echo $error; ?>

</div>

<?php } ?>

<form method="POST">

<input
type="email"
name="email"
placeholder="البريد الإلكتروني"
required
>

<button
type="submit"
name="send"
>

إرسال رمز التحقق

</button>

</form>

<div class="back">

<a href="admin.php">

العودة لتسجيل الدخول

</a>

</div>

</div>

</body>
</html>