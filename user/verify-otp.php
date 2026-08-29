<?php
session_start();

include('../include/connected.php');
include('mail.php');

if(!isset($_SESSION['reset_email'])){
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if(isset($_POST['verify'])){

    $otp = trim($_POST['otp']);

    $stmt = $con->prepare("
        SELECT * FROM users
        WHERE email=?
        AND otp_code=?
        AND otp_expire >= NOW()
        LIMIT 1
    ");

    $stmt->bind_param("ss",$email,$otp);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $_SESSION['otp_verified'] = true;

        header("Location: reset-password.php");
        exit();

    } else {

        $error = "OTP غير صحيح أو منتهي";

    }
}

/* إعادة إرسال OTP */

if(isset($_POST['resend'])){

    $otp = rand(100000,999999);

    date_default_timezone_set('Asia/Riyadh');

    $expire = date('Y-m-d H:i:s', time() + 600);

    $update = $con->prepare("
        UPDATE users
        SET otp_code=?,
            otp_expire=?
        WHERE email=?
    ");

    $update->bind_param("sss",$otp,$expire,$email);
    $update->execute();

    sendOTP($email,$otp);

    $success = "تم إعادة إرسال OTP";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تحقق OTP</title>

<style>

body{
    background:#f5f5f5;
    font-family:tahoma;
}

.box{
    width:400px;
    margin:80px auto;
    background:#fff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 0 10px rgba(0,0,0,.1);
    text-align:center;
}

input{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:1px solid #ccc;
    border-radius:10px;
    text-align:center;
    font-size:20px;
}

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    background:#007bff;
    color:#fff;
}

.resend{
    background:#28a745;
}

.timer{
    margin-top:15px;
    color:red;
    font-size:18px;
}

.error{
    color:red;
}

.success{
    color:green;
}

</style>

</head>
<body>

<div class="box">

<h2>رمز التحقق OTP</h2>

<?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

<?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>

<form method="POST">

<input
type="text"
name="otp"
maxlength="6"
placeholder="------"
required
>

<button type="submit" name="verify">
تحقق
</button>

</form>

<form method="POST">

<button class="resend" type="submit" name="resend">
إعادة إرسال OTP
</button>

</form>

<div class="timer">
الوقت المتبقي:
<span id="countdown">3:00</span>
</div>

</div>

<script>

let time = 180;

let x = setInterval(function(){

    let minutes = Math.floor(time / 60);
    let seconds = time % 60;

    seconds = seconds < 3 ? '0'+seconds : seconds;

    document.getElementById("countdown").innerHTML =
    minutes + ":" + seconds;

    time--;

    if(time < 0){

        clearInterval(x);

        document.getElementById("countdown").innerHTML =
        "انتهى الوقت";

    }

},1000);

</script>

</body>
</html>