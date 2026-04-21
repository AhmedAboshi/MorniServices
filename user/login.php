<?php
session_start();
include('../include/connected.php');

// if (isset($_SESSION['user_id'])) {
//     header("Location: ../index.php");
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo '<script>alert("يرجى إدخال البيانات");</script>';
        exit();
    }

    $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();

        if ($password == $user_data['password']) { // مؤقتًا

            $_SESSION['user_id'] = $user_data['id'];
            header("Location: ../index.php");
            exit();

        } else {
            echo '<script>alert("كلمة المرور غير صحيحة");</script>';
        }

    } else {
        echo '<script>alert("المستخدم غير موجود");</script>';
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
    <h2>تسجيل دخول</h2>
    <form action="login.php" method="post">
    <input type="text"  name="username" placeholder="ادخل اسم المستخدم">
    <input type="password"  name ="password" placeholder="ادخل كلمة المرور">
    
    <button type="submit">تسجيل الآن</button>
     <div class="footer">
        <p>ليس لديك حساب بالموقع ؟<a href="signup.php">دخول</a></p>
    </div>
</div>
</form>
</body>
</html>