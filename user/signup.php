<?php
session_start();
include('../include/connected.php');

// التحقق من تسجيل الدخول
if (isset($_SESSION['user_id'])) {
    echo '<script>alert("انت مسجل بالموقع بالفعل");
    window.location.href="index.php";</script>';
    exit();
}

// عند إرسال الفورم
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // التحقق من وجود المستخدم
    $user_query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($con, $user_query);

    if (mysqli_num_rows($result) > 0) {
        echo '<script>alert("انت مسجل بالفعل، قم بتسجيل الدخول مباشرة");</script>';
    }
    else {
        $query = "INSERT INTO users(username,email,password) VALUES('$username','$email','$password')";
        $result = mysqli_query($con, $query);
                echo '<script>alert("تم التسجيل بالموقع بنجاح قم بتسجيل الدخول");</script>';

    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل مستخدم جديد </title>

<style>
body {
    background: #cfd6dd;
    font-family: Arial;
}

/* الصندوق */
.form-box {
    width: 350px;
    margin: 150px auto;
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

/* العنوان */
.form-box h2 {
    text-align: center;
    margin-bottom: 20px;
}

/* الحقول */
.form-box input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* الزر */
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
.footer {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.footer a {
    color: #2d89ef;
    text-decoration: none;
    font-weight: bold;
}

.footer a:hover {
    text-decoration: underline;
}
</style>

</head>
<body>

<div class="form-box">
    <h2>تسجيل مستخدم جديد</h2>
    <form method="POST">

    <input type="text"  name="username" placeholder="ادخل اسم المستخدم">
    <input type="email"  name="email" placeholder="ادخل البريد الإلكتروني">
    <input type="password"  name ="password" placeholder="ادخل كلمة المرور">
    
    <button type="submit">تسجيل الآن</button>
     <div class="footer">
        <p>لديك حساب بالفعل ؟ <a href="login.php">تسجيل الدخول</a></p>
    </div>
</div>
</form>
</body>
</html>