<?php
session_start();
include('file/header.php');
include('include/connected.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =========================
   💾 إرسال الرسالة (FIXED)
========================= */
if(isset($_POST['send'])){

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    if(!empty($name) && !empty($email) && !empty($message)){

        $stmt = $con->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $message);
        $stmt->execute();

        $_SESSION['success'] = "✔ تم إرسال الرسالة بنجاح";

        // 🔥 منع التكرار (PRG Pattern)
        header("Location: contact.php");
        exit;

    } else {
        $error = "❌ يرجى تعبئة البيانات المطلوبة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اتصل بنا</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
    font-family: Arial;
    background:#f4f6f9;
    margin:0;
}

/* ===== Layout ===== */
.container{
    width:90%;
    margin:40px auto;
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
}

/* ===== Cards ===== */
.card{
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
    text-align:center;
}

.card i{
    font-size:28px;
    color:#0984e3;
    margin-bottom:10px;
}

/* ===== Form ===== */
.form-box{
    grid-column:span 2;
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

input,textarea{
    width:100%;
    padding:12px;
    margin:8px 0;
    border:1px solid #ddd;
    border-radius:8px;
}

button{
    width:100%;
    padding:12px;
    background:#0984e3;
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
}

button:hover{
    background:#0652dd;
}

/* ===== Messages ===== */
.success{color:green;text-align:center;}
.error{color:red;text-align:center;}

/* ===== Map ===== */
.map{
    grid-column:span 2;
    background:#fff;
    border-radius:15px;
    overflow:hidden;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

.map-header{
    padding:15px;
    text-align:center;
    font-weight:bold;
}

/* ===== Responsive ===== */
@media(max-width:768px){
    .container{grid-template-columns:1fr;}
    .form-box,.map{grid-column:span 1;}
}

</style>
</head>

<body>

<div class="container">

    <!-- البريد -->
    <div class="card">
        <i class="fa fa-envelope"></i>
        <h3>البريد الإلكتروني</h3>
        <p>info@morni.com</p>
    </div>

    <!-- الهاتف -->
    <div class="card">
        <i class="fa fa-phone"></i>
        <h3>اتصل بنا</h3>
        <p>+966920003922</p>
        <p>+966550186105</p>
        <p>24/7</p>
    </div>

    <!-- الموقع -->
    <div class="card">
        <i class="fa fa-location-dot"></i>
        <h3>موقعنا</h3>
        <p>Riyadh, Saudi Arabia</p>
    </div>

    <!-- السوشيال -->
<div class="card">
    <i class="fa fa-share-nodes"></i>
    <h3>تابعنا</h3>

    <div style="display:flex; justify-content:center; gap:15px; margin-top:10px; font-size:22px;">

        <a href="https://www.facebook.com/share/17CRN96Z5v/" target="_blank" style="color:#1877f2;">
            <i class="fab fa-facebook"></i>
        </a>

        <a href="https://www.instagram.com/morniksa/" target="_blank" style="color:#e1306c;">
            <i class="fab fa-instagram"></i>
        </a>

        <a href="https://x.com/MorniKSA" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
        </a>

    </div>
</div>

    <!-- الفورم -->
    <div class="form-box">

        <h2>📩 أرسل رسالة</h2>

        <?php 
        if(isset($_SESSION['success'])){
            echo "<p class='success'>".$_SESSION['success']."</p>";
            unset($_SESSION['success']);
        }
        if(isset($error)) echo "<p class='error'>$error</p>";
        ?>

        <form method="post">

            <input type="text" name="name" placeholder="الاسم" required>
            <input type="email" name="email" placeholder="الإيميل" required>
            <input type="text" name="phone" placeholder="الجوال">

            <textarea name="message" rows="5" placeholder="اكتب رسالتك..." required></textarea>

            <button type="submit" name="send">إرسال</button>

        </form>
    </div>

    <!-- الخريطة (مضمونة تعمل) -->
    <div class="map">

        <div class="map-header">
            📍 موقع الشركة على الخريطة
        </div>

        <iframe 
            src="https://www.google.com/maps?q=Morni%20corporate%20office%20Riyadh&output=embed"
            width="100%" 
            height="350" 
            style="border:0;"
            loading="lazy">
        </iframe>

    </div>

</div>

</body>
</html>

<?php include('file/foter.php'); ?>