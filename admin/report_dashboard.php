<?php include('../include/connected.php'); ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>لوحة التقارير</title>

    <style>
        body{
            font-family: Arial;
            direction: rtl;
            background:#f4f4f4;
        }

        .container{
            width: 90%;
            margin: auto;
        }

        h2{
            text-align:center;
            margin:20px 0;
        }

        .cards{
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap:20px;
        }

        .card{
            background:white;
            padding:20px;
            text-align:center;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
            transition:0.3s;
        }

        .card:hover{
            transform: scale(1.05);
        }

        .card a{
            text-decoration:none;
            color:white;
            background:#007bff;
            padding:10px 15px;
            border-radius:5px;
            display:inline-block;
            margin-top:10px;
        }

        .icon{
            font-size:40px;
        }
    </style>
</head>

<body>

<div class="container">

<h2>لوحة التحكم بالتقارير</h2>

<div class="cards">

    <!-- كل المركبات -->
    <div class="card">
        <div class="icon">🚗</div>
        <h3>جميع المركبات</h3>
        <a href="reportfleet.php">عرض</a>
    </div>

    <!-- حسب السائق -->
    <div class="card">
        <div class="icon">👨‍✈️</div>
        <h3>تقرير السائقين</h3>
        <a href="drivers_report.php">عرض</a>
    </div>

    <!--  الصيانة -->
    <div class="card">
        <div class="icon">🔧</div>
        <h3>تقرير الصيانة</h3>
        <a href="reportmaintenance.php">عرض</a>
    </div>

    <!--  الاطارات -->
    <div class="card">
        <div class="icon">🛞</div>
        <h3>تقرير الاطارات</h3>
        <a href="tires_report.php">عرض</a>
    </div>

    <!-- الزيوت  -->
    <div class="card">
        <div class="icon">

🛢️</div>
        <h3>تقرير الزيوت</h3>
        <a href="oile_report.php">تقرير الزيوت</a>
    </div>
    <!-- الفواتير  -->
    <div class="card">
        <div class="icon">🧾</div>
        <h3>تقرير الفواتير</h3>
        <a href="admin_invoices.php">تقرير الفواتير</a>
    </div>

</div>

</div>

</body>
</html>