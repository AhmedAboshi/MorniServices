<?php
session_start();
include('../include/core.php');
include('../include/connected.php');



if(isset($_POST['proadd'])){

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)){
        $msg = t('fill_fields');
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $msg = t('invalid_email');
    }
    else{

        // التحقق من تكرار الإيميل
        $check = $con->prepare("SELECT id FROM admin WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if($res->num_rows > 0){
            $msg = t('email_exists');
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $con->prepare("INSERT INTO admin (email, password) VALUES (?,?)");
            $stmt->bind_param("ss", $email, $hashed_password);

            if($stmt->execute()){
                header("Location: adduser.php?success=1");
                exit;
            } else {
                $msg = t('error');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('add_admin') ?></title>

<style>
body{
    font-family:'Cairo',sans-serif;
    background:#f4f6f9;
}

.form-box{
    width:40%;
    margin:60px auto;
    padding:25px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h1{
    text-align:center;
    margin-bottom:20px;
}

label{
    display:block;
    margin-top:12px;
    font-weight:bold;
}

input{
    width:100%;
    padding:12px;
    margin-top:5px;
    border:1px solid #ddd;
    border-radius:8px;
    outline:none;
}

.button{
    width:100%;
    margin-top:20px;
    padding:14px;
    background:linear-gradient(135deg,#3498db,#2980b9);
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
    transition:.3s;
}

.button:hover{
    transform:translateY(-2px);
}

.message{
    text-align:center;
    margin-top:10px;
    font-weight:bold;
}

.success{
    color:green;
}

.error{
    color:red;
}

.lang{
    text-align:center;
    margin-top:20px;
}
</style>
</head>
<body>

<div class="lang">
    <a href="?lang=ar">🇸🇦 عربي</a> |
    <a href="?lang=en">🇬🇧 English</a>
</div>

<div class="form-box">

<h1><?= t('add_admin') ?></h1>

<?php if(isset($_GET['success'])): ?>
    <p class="message success"><?= t('success_add') ?></p>
<?php endif; ?>

<?php if(!empty($msg)): ?>
    <p class="message error"><?= $msg ?></p>
<?php endif; ?>

<form method="post">

<label><?= t('email') ?></label>
<input type="email" name="email" required>

<label><?= t('password') ?></label>
<input type="password" name="password" required>

<button class="button" name="proadd">
    <?= t('add') ?>
</button>

</form>
</div>

</body>
</html>