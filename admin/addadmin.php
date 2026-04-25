
<?php
session_start();
include('../include/connected.php');

if(isset($_POST['proadd'])){

    $email    = mysqli_real_escape_string($con, $_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)){
        echo "<script>alert('يرجى تعبئة جميع الحقول');</script>";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo "<script>alert('الإيميل غير صحيح');</script>";
    }
    else{

        // 🔐 تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO admin (email, password) 
                  VALUES ('$email', '$hashed_password')";

        if(mysqli_query($con, $query)){
            echo "<script>alert('تم إضافة المستخدم بنجاح');</script>";
        }else{
            echo "<script>alert('حدث خطأ');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إضافة مستخدم</title>

<style>
body{
    font-family: 'Cairo';
    background:#f4f6f9;
}

.form_product{
    width: 40%;
    margin: 60px auto;
    padding: 20px;
    background:#fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-radius:10px;
}

h1{
    text-align:center;
}

label{
    display:block;
    margin-top:10px;
}

input{
    width:100%;
    padding:10px;
    margin-top:5px;
    border:1px solid #ccc;
    border-radius:5px;
}

.button{
    margin-top:15px;
    background:#3498db;
    color:#fff;
    border:none;
    padding:12px;
    cursor:pointer;
}

.button:hover{
    background:#2980b9;
}
</style>
</head>

<body>

<div class="form_product">
<h1>إضافة مستخدم</h1>

<form method="post">

<label>الإيميل</label>
<input type="email" name="email">

<label>كلمة المرور</label>
<input type="password" name="password">

<button class="button" name="proadd">إضافة</button>

</form>
</div>

</body>
</html>

