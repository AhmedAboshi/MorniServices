<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
    
    <link rel="stylesheet" href="style.css">
</head>
<style>
    body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    direction: rtl;
}

.container {
    padding: 20px;
}

h1 {
    text-align: center;
    margin-bottom: 30px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: 0.3s;
    text-align: center;
}

.card:hover {
    transform: translateY(-5px);
}

.card h2 {
    margin-bottom: 10px;
    color: #333;
}

.card p {
    font-size: 20px;
    color: #007bff;
    font-weight: bold;
}
 li a {
  color: black;              /* لون النص */
  text-decoration: none;    /* إزالة الخط السفلي */
  font-size: 18px;          /* حجم الخط */
  display: flex;            /* ترتيب النص مع الأيقونة */
  align-items: center;      /* توسيط عمودي */
  gap: 8px;                 /* مسافة بين النص والأيقونة */
  padding: 10px 15px;       /* مسافة داخلية */
  transition: 0.3s; 
  font-weight: bold;
 text-algin:center;
}
li a:hover {
  color: white;
  background-color: #007bff;
  border-radius: 5px;
} 
li a i {
  font-size: 16px;
  color: #555;
}
</style>
<body>

<div class="container">
    <h1>لوحة التحكم</h1>
       
    <div class="cards">
     
        <div class="card">
            <li><a href="../index.php" target_blank>الصفحة الرئيسية<i class="fa-solid fa-house"></i></a></li>
            
            
        </div>

        <div class="card">
            
            <li><a href="sectionadmin.php" target_blank> ادارة اقسام الموقع<i class="fa-solid fa-house"></i></a></li>
        </div>

        <div class="card">
            <li><a href="services.php" target_blank>خدمات الشركه<i class="fa fa-truck" aria-hidden="true"></i></a></li>
            
            
        </div>

        <div class="card">
            <li><a href="addproduct.php" target_blank>اضافة خدمة<i class="fa-solid fa-folder-plus"></i></a></li>
            
        </div>
 <div class="card">
            <li><a href="userview.php" target_blank>معلومات العملاء<i class="fa-solid fa-folder-plus"></i></a></li>
        </div>
<div class="card">
            
<li><a href="ordersview.php">
عرض الطلب
</a></li>
            
        </div>
        <div class="card">
            <li><a href="fleet.php" target_blank>مركبات الشركه</a></li>
        </div>

<div class="card">
            <li><a href="addfleet.php" target_blank>اضافة مركبة</a></li>
        </div>

<div class="card">
            <li><a href="addadmin.php" target_blank>اضافة مستخدم</a></li>
        </div>

        
        <div class="card">
            
<li><a href="driversview.php" target_blank>معلومات المزودين</a></li>
            
        </div>

        <div class="card">
            
            <li><a href="drivers.php" target_blank>اضافة مزود</a></li>
        </div>
        <div class="card">
            <li><a href="maintenanceview.php" target_blank>صيانات مركبات</a></li>
        </div>
        <div class="card">
             <li><a href="maintenance.php" target_blank>اضافة صيانة مركبة</a></li>
        </div>

<div class="card">
             <li><a href="tire.php" target_blank>ادارات الاطارات</a></li>
        </div>

        <div class="card">
             <li><a href="oile.php" target_blank>مراقبة تغير الزيت</a></li>
        </div>

        <div class="card">
                        <li><a href="report_dashboard.php" target_blank>لوحة تحكم التقارير</a></li>

        </div>
        <div class="card">
               <li><a href="logout.php" target_blank> تسجيل الخروج </a></li>

        </div>
    </div>
</div>

</body>
</html>

