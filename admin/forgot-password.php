<?php
session_start();
include('../include/connected.php');
include('mail.php');

if($_SERVER['REQUEST_METHOD'] === "POST"){

    $email = trim($_POST['email']);

    $stmt = $con->prepare("SELECT id FROM admin WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows == 0){
        die("الإيميل غير موجود");
    }

    $otp = rand(100000,999999);
    $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $up = $con->prepare("UPDATE admin SET otp_code=?, otp_expire=? WHERE email=?");
    $up->bind_param("sss",$otp,$expire,$email);
    $up->execute();

    sendOTP($email,$otp);

    $_SESSION['otp_type'] = 'reset';
    $_SESSION['otp_email'] = $email;

    header("Location: verify-otp.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>نسيت كلمة المرور</title>

<style>

body{
    margin:0;
    font-family:Tahoma;
    background:linear-gradient(135deg,#0f172a,#1e3a8a);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.card{
    width:400px;
    background:#fff;
    padding:35px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.3);
}

.logo{
    width:70px;
    margin-bottom:10px;
}

h2{
    color:#1e40af;
    margin-bottom:5px;
}

p{
    color:#6b7280;
    font-size:14px;
    margin-bottom:25px;
}

input{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid #ddd;
    text-align:center;
    font-size:15px;
    margin-bottom:15px;
}

input:focus{
    outline:none;
    border-color:#2563eb;
}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:#fff;
    font-size:16px;
    cursor:pointer;
}

button:hover{
    background:#1e40af;
}

.back{
    display:block;
    margin-top:15px;
    font-size:13px;
    color:#2563eb;
    text-decoration:none;
}

</style>
</head>

<body>

<div class="card">

    <img src="../img/logo.jpg" class="logo">

    <h2>نسيت كلمة المرور</h2>
    <p>أدخل بريدك الإلكتروني لإرسال رمز التحقق</p>

    <form method="POST">

        <input
        type="email"
        name="email"
        placeholder="البريد الإلكتروني"
        required>

        <button type="submit">إرسال رمز OTP</button>

    </form>

    <a href="admin.php" class="back">العودة لتسجيل الدخول</a>

</div>

</body>
</html>