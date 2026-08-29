<?php
session_start();

include('../include/connected.php');

if(!isset($_SESSION['otp_verified'])){
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if(isset($_POST['reset'])){

    $password = trim($_POST['password']);

    $update = $con->prepare("
        UPDATE users
        SET password=?,
            otp_code=NULL,
            otp_expire=NULL
        WHERE email=?
    ");

    $update->bind_param("ss",$password,$email);

    if($update->execute()){

        session_destroy();

        echo "
        <script>
            alert('تم تغيير كلمة المرور بنجاح');
            window.location='login.php';
        </script>
        ";

        exit();

    } else {

        echo "فشل تحديث كلمة المرور";

    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تغيير كلمة المرور</title>

<style>

body{
    font-family:tahoma;
    background:#f5f5f5;
}

.box{
    width:400px;
    margin:100px auto;
    background:#fff;
    padding:30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 0 10px rgba(0,0,0,.1);
}

input{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:1px solid #ccc;
    border-radius:10px;
}

button{
    width:100%;
    padding:12px;
    margin-top:20px;
    border:none;
    background:#007bff;
    color:#fff;
    border-radius:10px;
    cursor:pointer;
}

</style>

</head>
<body>

<div class="box">

<h2>تغيير كلمة المرور</h2>

<form method="POST">

<input
type="password"
name="password"
placeholder="كلمة المرور الجديدة"
required
>

<button type="submit" name="reset">
حفظ كلمة المرور
</button>

</form>

</div>

</body>
</html>