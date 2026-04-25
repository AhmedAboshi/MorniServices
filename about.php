<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Morni</title>

<style>
body{
    margin:0;
    font-family: Arial;
    background:#f6f7fb;
}

/* الشريط العلوي */
.navbar{
    background:#0b1f3a;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 25px;
    flex-wrap:wrap;
}

.navbar .menu a{
    color:white;
    margin:0 10px;
    text-decoration:none;
    font-size:14px;
}

.navbar .right{
    font-size:13px;
}

/* الهيرو */
.hero{
    background:linear-gradient(120deg,#0b1f3a,#163a63);
    color:white;
    padding:60px 20px;
    text-align:center;
}

.hero h1{
    margin:0;
    font-size:32px;
}

.hero p{
    margin-top:10px;
    opacity:0.9;
}

/* الإحصائيات */
.stats{
    display:flex;
    justify-content:center;
    gap:20px;
    flex-wrap:wrap;
    margin-top:-40px;
}

.stat{
    background:white;
    padding:20px;
    width:150px;
    text-align:center;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

/* المحتوى */
.section{
    max-width:1000px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:12px;
}

.section h2{
    color:#0b1f3a;
}

/* الفريق */
.team{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:15px;
}

.card{
    background:#f9fafc;
    padding:15px;
    border-radius:10px;
    border:1px solid #eee;
}

/* الفوتر */
.footer{
    background:#0b1f3a;
    color:white;
    text-align:center;
    padding:20px;
    margin-top:40px;
}
</style>

</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <img src="img/logo.jpg" alt="">

    <div class="menu">
        <a href="index.php">الرئيسية</a>
       
        <a href="about.php">من نحن</a>
        
        <a href="contact.php">اتصل بنا</a>
    </div>

    <div class="right">
        +966550186105 | +966920003922 | WhatsApp
    </div>
</div>

<!-- Hero -->
<div class="hero">
    <h1>عقدٌ من التقدّم</h1>
    <p>منصة متكاملة لخدمات السيارات في المملكة العربية السعودية</p>
</div>



<!-- About -->
<div class="section">
    <h2>من الطريق إلى مستقبل التنقل</h2>
    <p>
        بدأت مرني عام 2015 لتقديم خدمات المساعدة على الطريق وتحولت إلى منصة شاملة
        لإدارة خدمات السيارات مدعومة بالتقنية ورؤية 2030.
    </p>
</div>

<!-- Mission Vision -->
<div class="section">
    <h2>المهمة والرؤية</h2>

    <p><b>المهمة:</b> تحويل تجربة خدمات السيارات عبر حلول تقنية موثوقة.</p>
    <p><b>الرؤية:</b> أن نكون المنصة الرائدة في الشرق الأوسط بحلول 2030.</p>
</div>

<!-- Team -->
<div class="section">
    <h2>قيادتنا</h2>

    <div class="team">
        <div class="card">
            <h3>سلمان السحيباني</h3>
            <p>المؤسس والمدير العام</p>
        </div>

        <div class="card">
            <h3>سعد الدحيم</h3>
            <p>الرئيس التشغيلي</p>
        </div>

        <div class="card">
            <h3>خالد الوهيبي</h3>
            <p>الرئيس التنفيذي للعمليات</p>
        </div>

        <div class="card">
            <h3>مهند النفّيعي</h3>
            <p>الرئيس التنفيذي</p>
        </div>

        <div class="card">
            <h3> سعود السحيباني</h3>
            <p>الرئيس تطوير الاعمال</p>
        </div>
    </div>
</div>

<!-- Footer -->
<?php
include('file/foter.php');
 ?>

</body>
</html>