<?php
include('../include/connected.php');

$drivers_count = mysqli_fetch_assoc(
    mysqli_query($con,"SELECT COUNT(*) total FROM drivers")
)['total'];

$fleet_count = mysqli_fetch_assoc(
    mysqli_query($con,"SELECT COUNT(*) total FROM fleet")
)['total'];

$notifications_count = mysqli_fetch_assoc(
    mysqli_query($con,"SELECT COUNT(*) total FROM notifications WHERE is_read=0")
)['total'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>نظام إدارة الأسطول</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>

/* ===== أساسيات ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Tahoma;
}

body{
    overflow-x:hidden;
}

/* ===== الهيرو ===== */
.hero{
    height:100vh;
    position:relative;
    display:flex;
    justify-content:center;
    align-items:center;
    text-align:center;
    color:white;
}

/* ===== السلايدر ===== */
.slider{
    position:absolute;
    top:0;
    left:0;
    width:77%;
    height:100%;
    z-index:1;
    overflow:hidden;
}

.slide{
    position:absolute;
    width:55%;
    height:100%;
    object-fit:cover;
    opacity:0;
    animation: fade 10s infinite;
}

/* توقيت الصور */
.slide:nth-child(1){ animation-delay:0s; }
.slide:nth-child(2){ animation-delay:5s; }

@keyframes fade{
    0%{opacity:0;}
    10%{opacity:1;}
    40%{opacity:1;}
    50%{opacity:0;}
    100%{opacity:0;}
}

/* طبقة تغبيش */
.hero::after{
    content:"";
    position:absolute;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.6);
    z-index:2;
}

/* محتوى النص */
.hero-content{
    position:relative;
    z-index:3;
}

.hero-content h1{
    font-size:55px;
    margin-bottom:15px;
}

.hero-content p{
    font-size:22px;
    margin-bottom:30px;
}

/* أزرار */
.btn{
    padding:14px 30px;
    border:none;
    border-radius:8px;
    text-decoration:none;
    color:white;
    margin:5px;
    font-size:18px;
}

.login{ background:#0d6efd; }
.about{ background:#198754; }

/* ===== الإحصائيات ===== */
.stats{
    display:flex;
    justify-content:center;
    gap:25px;
    padding:60px 20px;
    flex-wrap:wrap;
    background:#f5f6fa;
}

.card{
    background:white;
    width:250px;
    text-align:center;
    padding:30px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
    transition:.3s;
}

.card:hover{
    transform:translateY(-8px);
}

.card i{
    font-size:45px;
    margin-bottom:15px;
    color:#0d6efd;
}

.card span{
    font-size:32px;
    color:#198754;
    font-weight:bold;
}

/* ===== المميزات ===== */
.features{
    padding:80px 20px;
    text-align:center;
}

.feature-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
}

.feature{
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
}

.feature i{
    font-size:40px;
    color:#0d6efd;
    margin-bottom:15px;
}

/* ===== الفوتر ===== */
footer{
    background:#111;
    color:#fff;
    text-align:center;
    padding:20px;
}

</style>
</head>

<body>

<!-- ===== HERO ===== -->
<section class="hero">

    <div class="slider">

        <img src="../img/boxred.jpeg" class="slide">
        <img src="../img/file.jpg" class="slide">
    </div>

    <br>
    <div class="slider">

        <img src="../img/boxred.jpeg" class="slide">
        <img src="../img/file.jpg" class="slide">
    </div>

    <div class="hero-content">
        <h1>🚛 نظام إدارة الأسطول الذكي</h1>
        <p>إدارة السائقين والمركبات والإشعارات من مكان واحد</p>

        <a href="admin.php" class="btn login">
            <i class="fa fa-right-to-bracket"></i> دخول النظام
        </a>

        <a href="#features" class="btn about">
            <i class="fa fa-circle-info"></i> المميزات
        </a>
    </div>

</section>

<!-- ===== الإحصائيات ===== -->
<section class="stats">

    <div class="card">
        <i class="fa fa-users"></i>
        <h2>السائقين</h2>
        <span><?= $drivers_count ?></span>
    </div>

    <div class="card">
        <i class="fa fa-truck"></i>
        <h2>المركبات</h2>
        <span><?= $fleet_count ?></span>
    </div>

    <div class="card">
        <i class="fa fa-bell"></i>
        <h2>الإشعارات</h2>
        <span><?= $notifications_count ?></span>
    </div>

</section>

<!-- ===== المميزات ===== -->
<section class="features" id="features">

<h2>مميزات النظام</h2>

<div class="feature-grid">

    <div class="feature">
        <i class="fa fa-id-card"></i>
        <h3>متابعة الإقامات</h3>
        <p>تنبيه تلقائي عند انتهاء الإقامة.</p>
    </div>

    <div class="feature">
        <i class="fa fa-address-card"></i>
        <h3>بطاقات السائقين</h3>
        <p>متابعة بطاقات السائقين المنتهية.</p>
    </div>

    <div class="feature">
        <i class="fa fa-truck-moving"></i>
        <h3>الفحص الدوري</h3>
        <p>تنبيهات المركبات المنتهي فحصها.</p>
    </div>

    <div class="feature">
        <i class="fa fa-screwdriver-wrench"></i>
        <h3>الصيانة</h3>
        <p>الصيانة والزيوت والإطارات</p>
    </div>
<div class="feature">
     <i class="fa fa-chart-column"></i> 
     <h3>التقارير</h3>
      <p>إحصائيات وتقارير متقدمة.</p> 
    </div>
</div>

</section>

<footer>
جميع الحقوق محفوظة © 2026
</footer>

</body>
</html>