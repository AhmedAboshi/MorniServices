<?php
include('../include/core.php');

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<?php $currentLang = $_SESSION['lang'] ?? 'ar'; ?>

<div class="lang-box">

    <a href="?<?= http_build_query(array_merge($_GET, ['lang'=>'ar'])) ?>"
       class="lang-btn <?= $currentLang=='ar'?'active':'' ?>">
        🇸🇦 عربي
    </a>

    <a href="?<?= http_build_query(array_merge($_GET, ['lang'=>'en'])) ?>"
       class="lang-btn <?= $currentLang=='en'?'active':'' ?>">
        🇬🇧 English
    </a>

</div>
<head>
    <meta charset="UTF-8">
    <title> <?= __('Control panel') ?></title>
    
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
    <h1><?= __('control_panel') ?></h1>
       
    <div class="cards">
     
        <div class="card">
            <li><a href="../index.php" target_blank><?= __('home') ?><i class="fa-solid fa-house"></i></a></li>
            
            
        </div>

      <div class="card">
    <li>
        <a href="sectionadmin.php" target="_blank">
            🏠 <?= __('sections') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="services.php" target="_blank">
            🚚 <?= __('company_services') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="addproduct.php" target="_blank">
            ➕ 📦 <?= __('add_service') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="userview.php" target="_blank">
            👤 <?= __('customers_info') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="ordersview.php">
            📋 <?= __('view_orders') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="fleet.php" target="_blank">
            🚗 <?= __('company_fleet') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="addfleet.php" target="_blank">
            ➕ 🚙 <?= __('add_vehicle') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="addadmin.php" target="_blank">
            👨‍💼 <?= __('add_user') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="driversview.php" target="_blank">
            🚛 <?= __('drivers_info') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="drivers.php" target="_blank">
            ➕ 🚚 <?= __('add_driver') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="maintenanceview.php" target="_blank">
            🔧 <?= __('maintenance_records') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="maintenance.php" target="_blank">
            ➕ 🛠️ <?= __('add_maintenance') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="tire.php" target="_blank">
            🛞 <?= __('tires_management') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="oile.php" target="_blank">
            🛢️ <?= __('oil_monitoring') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="report_dashboard.php" target="_blank">
            📊 <?= __('reports_dashboard') ?>
        </a>
    </li>
</div>

<div class="card">
    <li>
        <a href="logout.php" target="_blank">
            🚪 <?= __('logout') ?>
        </a>
    </li>
</div>