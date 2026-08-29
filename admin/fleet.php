
<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$GLOBALS['translations'] = $translations;
$GLOBALS['lang'] = $lang;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? '';
$alert = $_GET['alert'] ?? 0;

include('../include/core.php');
include('../include/connected.php');

/* =========================
   🌐 اللغة
========================= */
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* =========================
   🌍 الترجمة
========================= */
$translations = [

'ar'=>[

'fleet_title'=>'إدارة المركبات',
'search_vehicle'=>'بحث عن مركبة',
'search'=>'بحث',
'id'=>'رقم المركبة',
'image'=>'صورة المركبة',
'driver'=>'المزود',
'plate'=>'لوحة المركبة',
'type'=>'طراز المركبة',
'class'=>'نوع المركبة',
'model'=>'موديل المركبة',
'color'=>'لون المركبة',
'work'=>'منطقة العمل',
'delete'=>'حذف',
'edit'=>'تعديل',
'confirm_delete_vehicle'=>'هل أنت متأكد من حذف المركبة؟',
'operation_card'=>'كرت التشغيل',
'insurance_expiration_date'=>'انتهاء التامين',
'inspection'=>'الفحص الدوري',
'expired_vehicles'=>'المركبات المنتهية',
'near_expiry'=>'قريبة الانتهاء',
'expired'=>'منتهي',
'warning7'=>'تنبيه 7 أيام',
'warning30'=>'تنبيه 30 يوم',
'valid'=>'ساري',
'days'=>'يوم',
'detais'=>'الملف الإلكتروني للمركبة',
'not_found'=>'لا يوجد'
],

'en'=>[

'fleet_title'=>'Fleet Management',
'search_vehicle'=>'Search Vehicle',
'search'=>'Search',
'id'=>'ID',
'image'=>'Vehicle Image',
'driver'=>'Driver',
'plate'=>'Plate Number',
'type'=>'Vehicle Type',
'class'=>'Class',
'model'=>'Model',
'color'=>'Color',
'work'=>'Work Area',
'delete'=>'Delete',
'edit'=>'Edit',
'confirm_delete_vehicle'=>'Are you sure you want to delete this vehicle?',
'operation_card'=>'Operation Card',
'insurance_expiration_date
'=>'insurance_expiration_date',
'inspection'=>'Inspection',
'expired_vehicles'=>'Expired Vehicles',
'near_expiry'=>'Near Expiry',
'expired'=>'Expired',
'warning7'=>'7 Days Alert',
'warning30'=>'30 Days Alert',
'valid'=>'Valid',
'days'=>'Days',
'detais'=>'detais',
'not_found'=>'No Data'
]

];


/* =========================
   🔍 البحث
========================= */
$search = $_GET['search'] ?? '';
$work_filter   = $_GET['work'] ?? '';
$type_filter   = $_GET['typefleet'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = [];

if($search != ''){

    $search_safe = mysqli_real_escape_string($con,$search);

    $where[] = "(
        driver LIKE '%$search_safe%'
        OR plate LIKE '%$search_safe%'
        OR classify LIKE '%$search_safe%'
        OR typefleet LIKE '%$search_safe%'
        OR model LIKE '%$search_safe%'
        OR colorfleet LIKE '%$search_safe%'
    )";

}

if($work_filter != ''){

    $work_filter = mysqli_real_escape_string($con,$work_filter);

    $where[] = "work='$work_filter'";

}

if($type_filter != ''){

    $type_filter = mysqli_real_escape_string($con,$type_filter);

    $where[] = "typefleet='$type_filter'";

}

$status = $_GET['status'] ?? '';

$query = "SELECT * FROM fleet";


/* =========================
   إضافة شروط البحث والفلاتر
========================= */

if(count($where) > 0){

    $query .= " WHERE " . implode(" AND ", $where);

}


/* =========================
   حالات المركبات
========================= */

if($status=='expired'){

    $query .= (strpos($query,'WHERE') !== false ? " AND " : " WHERE ") . "
    (
    operation_expiry<CURDATE()
    OR insurance_expiration_date<CURDATE()
    OR inspection_expiry<CURDATE()
    )";

}
elseif($status=='danger'){

    $query .= (strpos($query,'WHERE') !== false ? " AND " : " WHERE ") . "
    (
    operation_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)

    OR insurance_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)

    OR inspection_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
    )";

}
elseif($status=='warning'){

    $query .= (strpos($query,'WHERE') !== false ? " AND " : " WHERE ") . "
    (
    operation_expiry BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)

    OR insurance_expiration_date BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)

    OR inspection_expiry BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
    )";

}
elseif($status=='valid'){

    $query .= (strpos($query,'WHERE') !== false ? " AND " : " WHERE ") . "
    (
    operation_expiry>DATE_ADD(CURDATE(),INTERVAL 30 DAY)

    AND insurance_expiration_date>DATE_ADD(CURDATE(),INTERVAL 30 DAY)

    AND inspection_expiry>DATE_ADD(CURDATE(),INTERVAL 30 DAY)
    )";

}


$query .= " ORDER BY id DESC";




$result = mysqli_query($con,$query);

/* =========================
   📅 حالة الانتهاء
========================= */
function expiryStatus($date){

    global $lang;

    if(empty($date)){

        return [
            'text'  => t('not_found'),
            'class' => 'normal',
            'days'  => ''
        ];
    }

    $today  = strtotime(date('Y-m-d'));
    $expiry = strtotime($date);

    $days = floor(($expiry - $today) / 86400);

    if($days < 0){

        return [
            'text'  => t('expired'),
            'class' => 'expired',
            'days'  => $days
        ];
    }

    if($days <= 7){

        return [
            'text'  => t('warning7'),
            'class' => 'danger',
            'days'  => $days
        ];
    }

    if($days <= 30){

        return [
            'text'  => t('warning30'),
            'class' => 'warning',
            'days'  => $days
        ];
    }

    return [
        'text'  => t('valid'),
        'class' => 'success',
        'days'  => $days
    ];
}



$expiredInsurance = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE insurance_expiration_date < CURDATE()
"))['total'];

$expiredInspection = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE inspection_expiry < CURDATE()
"))['total'];

$expiredOperation = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE operation_expiry < CURDATE()
"))['total'];


/* =========================
   🔔 الإحصائيات
========================= */
$today = date('Y-m-d');

$expired_count = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry < '$today'
OR insurance_expiration_date < '$today'
OR inspection_expiry < '$today'
"))['total'];

$warning_count = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 30 DAY)
OR insurance_expiration_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 30 DAY)
OR inspection_expiry BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 30 DAY)
"))['total'];

/* =========================
   📊 إحصائيات الوثائق
========================= */

$operation_expired = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE operation_expiry < CURDATE()
"))['total'];

$insurance_expired = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE insurance_expiration_date < CURDATE()
"))['total'];

$inspection_expired = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE inspection_expiry < CURDATE()
"))['total'];

$warning7 = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
OR insurance_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
OR inspection_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
"))['total'];


/* =========================
   🗑️ حذف
========================= */
if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    mysqli_query($con, "DELETE FROM fleet WHERE id=$id");

    header("Location: fleet.php?lang=$lang");
    exit;
}

?>


<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= t('fleet_title') ?></title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<style>

*{
    box-sizing:border-box;
}

body{
    font-family:'Cairo',sans-serif;
    background:#f4f6f9;
    margin:0;
}

.topbar{
    background:#fff;
    padding:15px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.title{
    font-size:24px;
    font-weight:bold;
    color:#2c3e50;
}

.lang-switch a{
    text-decoration:none;
    padding:8px 14px;
    border-radius:8px;
    background:#ecf0f1;
    color:#333;
    margin:0 5px;
    font-weight:bold;
}

.lang-switch a.active{
    background:#3498db;
    color:#fff;
}

.search-box{
    width:95%;
    margin:25px auto;
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

.search-box form{
    display:flex;
    gap:10px;
}

.search-box input{
    flex:1;
    padding:14px;
    border:1px solid #ddd;
    border-radius:10px;
    font-size:15px;
    
}
.search-box select{

padding:14px;
border:1px solid #ddd;
border-radius:10px;
font-size:15px;
min-width:180px;

}
.search-box button{
    border:none;
    padding:14px 25px;
    background:#3498db;
    color:#fff;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.stats{
    width:95%;
    margin:25px auto;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:18px;
}

/* .card{
    padding:18px;
    border-radius:14px;
    color:#fff;
    text-align:center;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    transition:.3s;
    min-height:150px;

    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
} */

.card:hover{
    transform:translateY(-4px);
}

.card-icon{
    font-size:36px;
    margin-bottom:10px;
}

.card-title{
    font-size:16px;
    font-weight:600;
}

.card-number{
    margin-top:10px;
    font-size:40px;
    font-weight:bold;
}

.blue{
    background:linear-gradient(135deg,#0d6efd,#0b5ed7);
}

.red{
    background:linear-gradient(135deg,#dc3545,#b02a37);
}

.orange{
    background:linear-gradient(135deg,#fd7e14,#e8590c);
}

.green{
    background:linear-gradient(135deg,#198754,#157347);
}

.card:hover{

transform:translateY(-4px);

}

.red{
    background:linear-gradient(135deg,#e74c3c,#c0392b);
}

.orange{
    background:linear-gradient(135deg,#f39c12,#d68910);
}

.table-container{
    width:95%;
    margin:25px auto;
    overflow:auto;
    border-radius:14px;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

.table-container table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

th{
    background:#2c3e50;
    color:#fff;
    padding:15px;
    font-size:15px;
    white-space:nowrap;
}

 td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

tr:nth-child(even){
    background:#fafafa;
}

tr:hover{
    background:#f1f7ff;
}

/* =========================
   Row Status Colors
========================= */

.row-expired td{
    background:#ffe5e5 !important;
}

.row-danger td{
    background:#fff3cd !important;
}

.row-warning td{
    background:#fff8e1 !important;
}

.row-valid td{
    background:#f4fff4 !important;
}
.row-expired:hover,
.row-danger:hover,
.row-warning:hover,
.row-valid:hover{

filter:brightness(.98);

}

img{
    width:80px;
    height:80px;
    border-radius:10px;
    object-fit:cover;
}

.badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    color:#fff;
    font-size:12px;
    font-weight:bold;
    margin-top:5px;
}

.success{
    background:#27ae60;
}

.warning{
    background:#f39c12;
}

.danger{
    background:#d35400;
}

.expired{
    background:#c0392b;
}

.normal{
    background:#7f8c8d;
}

.days{
    display:block;
    margin-top:4px;
    font-size:11px;
}

.btn{
    border:none;
    padding:10px 14px;
    border-radius:8px;
    color:#fff;
    cursor:pointer;
    font-weight:bold;
}

.detais{
    background:#27ae60;
}
.delete{
    background:#e74c3c;
}

.update{
    background:#27ae60;
}

@media(max-width:768px){

    .stats{
        flex-direction:column;
    }

    .search-box form{
        flex-direction:column;
    }

}
/* =========================
   Toolbar
========================= */

.toolbar{
    width:95%;
    margin:20px auto;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}

.toolbar-left,
.toolbar-right{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.action-btn{
    padding:12px 18px;
    border:none;
    border-radius:10px;
    color:#fff;
    font-weight:bold;
    text-decoration:none;
    transition:.3s;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.add{
    background:#28a745;
}

.pdf{
    background:#dc3545;
}

.excel{
    background:#198754;
}

.print{
    background:#0d6efd;
}

.refresh{
    background:#6c757d;
}

.action-btn:hover{
    transform:translateY(-2px);
    opacity:.9;
}

/* تحسين البطاقات */


/*=========================
 Fleet Dashboard Cards
=========================*/

.alert-dashboard{

width:95%;
margin:25px auto;

display:grid;
grid-template-columns:repeat(auto-fit,minmax(240px,1fr));

gap:18px;

}

.alert-card{

background:#fff;

border-radius:16px;

padding:18px;

box-shadow:0 5px 15px rgba(0,0,0,.08);

display:flex;

justify-content:space-between;

align-items:center;

transition:.3s;

border-right:6px solid #3498db;

}

.alert-card:hover{

transform:translateY(-4px);

box-shadow:0 10px 20px rgba(0,0,0,.12);

}

.alert-info h4{

margin:0;
font-size:15px;
color:#555;
font-weight:600;

}

.alert-info h2{

margin:8px 0 0;
font-size:34px;
color:#222;

}

.alert-icon{

font-size:42px;

}

.border-red{

border-color:#e74c3c;

}

.border-blue{

border-color:#3498db;

}

.border-orange{

border-color:#f39c12;

}

.border-green{

border-color:#2ecc71;

}



.alert-box:hover{

transform:translateY(-4px);

}

.alert-red{

background:linear-gradient(135deg,#e53935,#b71c1c);

}

.alert-blue{

background:linear-gradient(135deg,#1976d2,#0d47a1);

}

.alert-orange{

background:linear-gradient(135deg,#fb8c00,#ef6c00);

}

.alert-green{

background:linear-gradient(135deg,#43a047,#1b5e20);

}



.alert-title{

font-size:15px;

font-weight:bold;

line-height:1.5;

}

.row-expired td{
    background:red !important;
    color:white !important;
}

.row-danger td{
    background:orange !important;
}

.row-warning td{
    background:yellow !important;
}

.row-valid td{
    background:lightgreen !important;
}
/*=========================
 Fleet Filters
=========================*/

.fleet-filter{

width:95%;

margin:20px auto;

display:flex;

gap:12px;

flex-wrap:wrap;

}

.fleet-filter a{

padding:10px 18px;

background:#fff;

border-radius:30px;

text-decoration:none;

color:#333;

font-weight:bold;

box-shadow:0 3px 10px rgba(0,0,0,.08);

transition:.3s;

}

.fleet-filter a:hover{

background:#3498db;

color:#fff;

}

.fleet-filter .active{

background:#3498db;

color:#fff;

}
.fleet-alerts{

width:95%;

margin:15px auto;

display:flex;

flex-direction:column;

gap:10px;

}

.fleet-alert{

padding:14px 18px;

border-radius:10px;

font-weight:bold;

}

.fleet-alert.danger{

background:#fdeaea;

color:#b71c1c;

border-right:5px solid #e53935;

}

.fleet-alert.warning{

background:#fff8e1;

color:#ef6c00;

border-right:5px solid #fb8c00;

}

.fleet-alert.info{

background:#e8f4fd;

color:#1565c0;

border-right:5px solid #1e88e5;

}
</style>
<link rel="stylesheet" href="assets/dark-mode.css">
</head>
<body>

<div class="topbar">

<div class="title">
🚚 <?= t('fleet_title') ?>
</div>

<div class="lang-switch">

<a href="?lang=ar" class="<?= $lang=='ar'?'active':'' ?>">
🇸🇦 عربي
</a>

<a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">
🇬🇧 English
</a>

</div>

<button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button>

</div>

<div class="search-box">

<form method="GET">

<input
type="text"
name="search"
placeholder="<?= t('search_vehicle') ?>"
value="<?= htmlspecialchars($search) ?>">

<select name="work">

<option value="">كل مناطق العمل</option>

<?php

$q=mysqli_query($con,"
SELECT DISTINCT work
FROM fleet
ORDER BY work
");

while($r=mysqli_fetch_assoc($q)){

?>

<option
value="<?= $r['work'] ?>"
<?= $work_filter==$r['work']?'selected':'' ?>>

<?= $r['work'] ?>

</option>

<?php } ?>

</select>

<select name="typefleet">

<option value="">كل أنواع المركبات</option>

<?php

$q=mysqli_query($con,"
SELECT DISTINCT typefleet
FROM fleet
ORDER BY typefleet
");

while($r=mysqli_fetch_assoc($q)){

?>

<option
value="<?= $r['typefleet'] ?>"
<?= $type_filter==$r['typefleet']?'selected':'' ?>>

<?= $r['typefleet'] ?>

</option>

<?php } ?>

</select>

<button type="submit">

🔍 بحث

</button>

<a href="fleet.php" class="action-btn refresh">

إعادة تعيين

</a>

</form>

</div>
<div class="toolbar">

<div class="toolbar-left">

<a href="addfleet.php" class="action-btn add">
➕ إضافة مركبة
</a>

<a href="reports/export_fleet_pdf.php" class="action-btn pdf">
📄 PDF
</a>

<a href="reports/export_fleet_excel.php" class="action-btn excel">
📊 Excel
</a>

</div>

<div class="toolbar-right">

<button onclick="window.print()" class="action-btn print">
🖨️ طباعة
</button>

<a href="fleet.php" class="action-btn refresh">
🔄 تحديث
</a>

</div>

</div>

<div class="stats">

<div class="alert-card border-blue">
    <div class="alert-info">
        <h4>إجمالي المركبات</h4>
        <h2>
        <?php
        $total=mysqli_fetch_assoc(mysqli_query($con,"
        SELECT COUNT(*) total FROM fleet
        "));
        echo $total['total'];
        ?>
        </h2>
    </div>
    <div class="alert-icon">🚚</div>
</div>

<div class="alert-card border-red">
    <div class="alert-info">
        <h4><?= t('expired_vehicles') ?></h4>
        <h2><?= $expired_count ?></h2>
    </div>
    <div class="alert-icon">🚨</div>
</div>

<div class="alert-card border-orange">
    <div class="alert-info">
        <h4><?= t('near_expiry') ?></h4>
        <h2><?= $warning_count ?></h2>
    </div>
    <div class="alert-icon">⚠️</div>
</div>

<div class="alert-card border-green">
    <div class="alert-info">
        <h4>المركبات السارية</h4>
        <h2><?= $total['total']-$expired_count ?></h2>
    </div>
    <div class="alert-icon">✅</div>
</div>

</div>

</div>

<div class="alert-dashboard">

<div class="alert-card border-red">

<div class="alert-info">

<h4>كروت التشغيل المنتهية</h4>

<h2><?= $operation_expired ?></h2>

</div>

<div class="alert-icon">
🚨
</div>

</div>

<div class="alert-card border-blue">

<div class="alert-info">

<h4>التامين المنتهية</h4>

<h2><?= $insurance_expired ?></h2>

</div>

<div class="alert-icon">
🛡️
</div>

</div>

<div class="alert-card border-orange">

<div class="alert-info">

<h4>الفحص الدوري المنتهي</h4>

<h2><?= $inspection_expired ?></h2>

</div>

<div class="alert-icon">
🔧
</div>

</div>

<div class="alert-card border-green">

<div class="alert-info">

<h4>تنتهي خلال 7 أيام</h4>

<h2><?= $warning7 ?></h2>

</div>

<div class="alert-icon">
⏳
</div>

</div>

</div>
<div class="fleet-filter">

<a href="fleet.php" class="<?= empty($_GET['status']) ? 'active' : '' ?>">
🚚 الكل
</a>

<a href="fleet.php?status=expired"
class="<?= ($_GET['status'] ?? '')=='expired' ? 'active' : '' ?>">
🚨 المنتهية
</a>

<a href="fleet.php?status=danger"
class="<?= ($_GET['status'] ?? '')=='danger' ? 'active' : '' ?>">
⚠️ خلال 7 أيام
</a>

<a href="fleet.php?status=warning"
class="<?= ($_GET['status'] ?? '')=='warning' ? 'active' : '' ?>">
🟡 خلال 30 يوم
</a>

<a href="fleet.php?status=valid"
class="<?= ($_GET['status'] ?? '')=='valid' ? 'active' : '' ?>">
✅ السارية
</a>

</div>
<div class="fleet-alerts">

<?php if($expiredInsurance>0){ ?>

<div class="fleet-alert danger">
🛡️ يوجد <b><?= $expiredInsurance ?></b> مركبة انتهى تأمينها.
</div>

<?php } ?>

<?php if($expiredInspection>0){ ?>

<div class="fleet-alert warning">
🔍 يوجد <b><?= $expiredInspection ?></b> مركبة انتهى فحصها الدوري.
</div>

<?php } ?>

<?php if($expiredOperation>0){ ?>

<div class="fleet-alert info">
📄 يوجد <b><?= $expiredOperation ?></b> مركبة انتهى كرت تشغيلها.
</div>

<?php } ?>

</div>

<div class="table-container">

<table>
<thead>
<tr>
<th><?= t('id') ?></th>
<th><?= t('image') ?></th>
<th><?= t('driver') ?></th>
<th><?= t('plate') ?></th>
<th><?= t('type') ?></th>
<th><?= t('class') ?></th>
<th><?= t('model') ?></th>
<th><?= t('color') ?></th>
<th><?= t('work') ?></th>
<th><?= t('operation_card') ?></th>
<th><?= t('insurance_expiration_date') ?></th>
<th><?= t('inspection') ?></th>
<th><?= t('delete') ?></th>
<th><?= t('edit') ?></th>
<th><?= t('detais') ?></th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($result)) { ?>

<?php
$operation   = expiryStatus($row['operation_expiry']);
$registration = expiryStatus($row['insurance_expiration_date']);
$inspection   = expiryStatus($row['inspection_expiry']);

/* =========================
   لون الصف
========================= */

$rowClass = '';

if(
    $operation['class'] == 'expired' ||
    $registration['class'] == 'expired' ||
    $inspection['class'] == 'expired'
){

    $rowClass = 'row-expired';

}elseif(

    $operation['class'] == 'danger' ||
    $registration['class'] == 'danger' ||
    $inspection['class'] == 'danger'

){

    $rowClass = 'row-danger';

}elseif(

    $operation['class'] == 'warning' ||
    $registration['class'] == 'warning' ||
    $inspection['class'] == 'warning'

){

    $rowClass = 'row-warning';

}else{

    $rowClass = 'row-valid';

}
?>

<tr class="<?= $rowClass ?>">

<td><?= $row['id'] ?></td>

<td>
<a href="fleet_details.php?id=<?= $row['id'] ?>">
<img src="../fleetimg/img/<?= $row['imgfleet'] ?>">
</a>
</td>

<td><?= $row['driver'] ?></td>
<td><?= $row['plate'] ?></td>
<td><?= $row['typefleet'] ?></td>
<td><?= $row['classify'] ?></td>
<td><?= $row['model'] ?></td>
<td><?= $row['colorfleet'] ?></td>
<td><?= $row['work'] ?></td>

<td>
<?= $row['operation_expiry'] ?>
<br>
<span class="badge <?= $operation['class'] ?>">
<?= $operation['text'] ?>

<?php if($operation['days'] !== ''){ ?>

<span class="days">
<?= $operation['days'] >= 0 ? $operation['days'].' '.t('days') : '' ?>
</span>

<?php } ?>

</span>
</td>

<td>
<?= $row['insurance_expiration_date'] ?>
<br>
<span class="badge <?= $registration['class'] ?>">
<?= $registration['text'] ?>

<?php if($registration['days'] !== ''){ ?>

<span class="days">
<?= $registration['days'] >= 0 ? $registration['days'].' '.t('days') : '' ?>
</span>

<?php } ?>

</span>
</td>

<td>
<?= $row['inspection_expiry'] ?>
<br>
<span class="badge <?= $inspection['class'] ?>">
<?= $inspection['text'] ?>

<?php if($inspection['days'] !== ''){ ?>

<span class="days">
<?= $inspection['days'] >= 0 ? $inspection['days'].' '.t('days') : '' ?>
</span>

<?php } ?>

</span>
</td>

<td>
<a href="fleet.php?id=<?= $row['id'] ?>&lang=<?= $lang ?>"
onclick="return confirm('<?= t('confirm_delete_vehicle') ?>')">
<button class="btn delete">
<?= t('delete') ?>
</button>
</a>
</td>

<td>
<a href="updatefleet.php?id=<?= $row['id'] ?>">
<button class="btn update">
<?= t('edit') ?>
</button>
</a>
</td>

<td>
<a href="fleet_details.php?id=<?= $row['id'] ?>">
<button class="btn detais">
<?= t('detais') ?>
</button>
</a>
</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
<script src="assets/dark-mode.js"></script>
</body>
</html>

