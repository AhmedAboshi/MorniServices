<?php
include('../include/connected.php');

/* ================= LANGUAGE ================= */
session_start();

if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

$T = [
    'ar' => [
        'title' => 'قائمة السائقين',
        'search' => 'ابحث عن سائق...',
        'name' => 'الاسم',
        'phone' => 'الجوال',
        'truck' => 'نوع السطحة',
        'plate' => 'اللوحة',
        'area' => 'منطقة العمل',
        'details' => 'تفاصيل',
        'view' => 'عرض',
        'no_data' => 'لا يوجد نتائج',
        'search_btn' => 'بحث',
        'national id'=> 'هوية المزود',
        'iqama_expiry_date'=> 'تاريخ انتهاء الاقامة',
        'License expiry date' => 'تاريخ انتهاء الرخصة',
        'driver_card_expiration_date' => 'تاريخ انتهاء بطاقة السائق',
        'imagedriver' => 'صورة السائق'

    ],
    'en' => [
        'title' => 'Drivers List',
        'search' => 'Search driver...',
        'name' => 'Name',
        'phone' => 'Phone',
        'truck' => 'Truck',
        'plate' => 'Plate',
        'area' => 'Area',
        'details' => 'Details',
        'view' => 'View',
        'no_data' => 'No results found',
        'search_btn' => 'Search',
        'national id' => 'national id',
'iqama_expiry_date' => 'iqama_expiry_date',
'License expiry date' => 'License expiry date',
'driver_card_expiration_date' => 'driver_card_expiration_date',
'imagedriver' => 'imagedriver'

        
    ]
];

function t($k){
    global $T, $lang;
    return $T[$lang][$k] ?? $k;
}

/* ================= SEARCH ================= */
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = [];

if($search != ''){

    $search_safe = $con->real_escape_string($search);

    $where[] = "(
        name LIKE '%$search_safe%'
        OR phone LIKE '%$search_safe%'
        OR plate_number LIKE '%$search_safe%'
        OR national_id LIKE '%$search_safe%'
    )";
}

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

switch($filter){

    case 'iqama':
        $where[] = "iqama_expiry_date < '$today'";
        break;

    case 'license':
        $where[] = "license_expiry_date < '$today'";
        break;

    case 'card':
        $where[] = "driver_card_expiration_date < '$today'";
        break;

    case 'expiring':
        $where[] = "(
            (iqama_expiry_date BETWEEN '$today' AND '$soon')
            OR
            (license_expiry_date BETWEEN '$today' AND '$soon')
            OR
            (driver_card_expiration_date BETWEEN '$today' AND '$soon')
        )";
        break;
}

$sql = "SELECT * FROM drivers";

if(count($where)){
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY id DESC";

$result = $con->query($sql);



$drivers = $result->fetch_all(MYSQLI_ASSOC);
function expiryStatus($date){

    if(empty($date) || $date == '0000-00-00'){
        return '<span style="color:#777">-</span>';
    }

    $today = new DateTime();
    $expiry = new DateTime($date);

    $days = (int)$today->diff($expiry)->format('%r%a');

    if($days < 0){
        return '<span style="
            background:#e74c3c;
            color:#fff;
            padding:5px 10px;
            border-radius:20px;
            font-weight:bold;
        ">
        منتهي منذ '.abs($days).' يوم
        </span>';
    }

    if($days <= 30){
        return '<span style="
            background:#f1c40f;
            color:#000;
            padding:5px 10px;
            border-radius:20px;
            font-weight:bold;
        ">
        متبقي '.$days.' يوم
        </span>';
    }

    return '<span style="
        background:#27ae60;
        color:#fff;
        padding:5px 10px;
        border-radius:20px;
        font-weight:bold;
    ">
    ساري - '.$days.' يوم
    </span>';
}
$today = date('Y-m-d');

/* إجمالي السائقين */
$total_drivers = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT COUNT(*) total FROM drivers")
)['total'];

/* الإقامات المنتهية */
$expired_iqama = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT COUNT(*) total FROM drivers
    WHERE iqama_expiry_date IS NOT NULL
    AND iqama_expiry_date < '$today'")
)['total'];

/* الرخص المنتهية */
$expired_license = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT COUNT(*) total FROM drivers
    WHERE license_expiry_date IS NOT NULL
    AND license_expiry_date < '$today'")
)['total'];

/* كروت بطاقة السائقين المنتهية */
$expired_card = mysqli_fetch_assoc(
    mysqli_query($con, "SELECT COUNT(*) total FROM drivers
    WHERE driver_card_expiration_date IS NOT NULL
    AND driver_card_expiration_date < '$today'")
)['total'];
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= t('title') ?></title>

<style>
body{
    font-family: Arial;
    background:#f4f6f9;
}

/* 🌍 اللغة */
.lang{
    text-align:center;
    margin:15px;
}
.lang a{
    margin:0 10px;
    text-decoration:none;
    font-weight:bold;
}

/* 🔍 البحث */
.search-box{
    text-align:center;
    margin:20px;
}

.search-box input{
    width:280px;
    padding:10px;
    border-radius:25px;
    border:1px solid #ccc;
}

.search-box button{
    padding:10px 15px;
    border:none;
    background:#3498db;
    color:white;
    border-radius:20px;
    cursor:pointer;
}

/* 📊 الجدول */
table{
    width:95%;
    margin:auto;
    border-collapse:collapse;
    background:#fff;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    border-radius:10px;
    overflow:hidden;
}

th{
    background:#2c3e50;
    color:white;
}

th,td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}

tr:nth-child(even){
    background:#f9f9f9;
}

tr:hover{
    background:#eef5ff;
}

/* 🔘 زر */
.btn{
    background:#27ae60;
    color:white;
    padding:6px 12px;
    border-radius:5px;
    text-decoration:none;
    display:inline-block;
}
.driver-img{
    width:80px;
    height:80px;
    object-fit:cover;
    border-radius:10px;
    border:1px solid #ddd;
}
.stats-container{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.stat-card{
    flex:1;
    min-width:200px;
    background:#fff;
    border-radius:12px;
    padding:20px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.stat-card h3{
    margin:0;
    font-size:32px;
    font-weight:bold;
}

.stat-card p{
    margin-top:10px;
    color:#666;
}

.danger{
    border-right:5px solid #dc3545;
}

.warning{
    border-right:5px solid #ffc107;
}

.info{
    border-right:5px solid #0d6efd;
}
.filter-btn{
    display:inline-block;
    padding:10px 18px;
    margin:5px;
    border-radius:8px;
    text-decoration:none;
    background:#f1f3f5;
    color:#333;
    border:1px solid #ddd;
    transition:.2s;
}

.filter-btn:hover{
    background:#0d6efd;
    color:#fff;
}

.filter-btn.active{
    background:#0d6efd;
    color:#fff;
    font-weight:bold;
}
</style>
   <link rel="stylesheet" href="assets/dark-mode.css">
</head>

<body>

<!-- 🌍 LANGUAGE -->
<div class="lang">
    <a href="?lang=ar">🇸🇦 عربي</a>
    <a href="?lang=en">🇬🇧 English</a>
    <button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button>

</div>

<h2 style="text-align:center;">🚛 <?= t('title') ?></h2>

<!-- 🔍 SEARCH -->
<div class="search-box">
<form method="GET">
<input type="text" name="search" placeholder="<?= t('search') ?>"
value="<?= htmlspecialchars($search) ?>">
<button type="submit"><?= t('search_btn') ?></button>
</form>
</div>
<div class="stats-container">

    <a href="?filter=all&search=<?= urlencode($search) ?>" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3><?= $total_drivers ?></h3>
        <p>إجمالي السائقين</p>
    </a>

    <a href="?filter=iqama&search=<?= urlencode($search) ?>" class="stat-card danger" style="text-decoration:none;color:inherit;">
        <h3><?= $expired_iqama ?></h3>
        <p>الإقامات المنتهية</p>
    </a>

    <a href="?filter=license&search=<?= urlencode($search) ?>" class="stat-card warning" style="text-decoration:none;color:inherit;">
        <h3><?= $expired_license ?></h3>
        <p>الرخص المنتهية</p>
    </a>

    <a href="?filter=card&search=<?= urlencode($search) ?>" class="stat-card info" style="text-decoration:none;color:inherit;">
        <h3><?= $expired_card ?></h3>
        <p>كروت بطاقة السائقين المنتهية</p>
    </a>

</div>
<div style="text-align:center;margin:20px 0;">

<a href="?filter=all&search=<?= urlencode($search) ?>"
class="filter-btn <?= $filter=='all' ? 'active' : '' ?>">
📋 كل السائقين
</a>

<a href="?filter=iqama&search=<?= urlencode($search) ?>"
class="filter-btn <?= $filter=='iqama' ? 'active' : '' ?>">
🪪 الإقامات المنتهية
</a>

<a href="?filter=license&search=<?= urlencode($search) ?>"
class="filter-btn <?= $filter=='license' ? 'active' : '' ?>">
🚗 الرخص المنتهية
</a>

<a href="?filter=card&search=<?= urlencode($search) ?>"
class="filter-btn <?= $filter=='card' ? 'active' : '' ?>">
💳 بطاقات السائق المنتهية
</a>

<a href="?filter=expiring&search=<?= urlencode($search) ?>"
class="filter-btn <?= $filter=='expiring' ? 'active' : '' ?>">
⏳ تنتهي خلال 30 يوم
</a>

</div>

<!-- 📊 TABLE -->
<table>
<tr>
  <th><?= t('name') ?></th>
  <th><?= t('imagedriver') ?></th>
  <th><?= t('phone') ?></th>
  <th><?= t('national id') ?></th>
<th><?= t('iqama_expiry_date') ?></th>
<th><?= t('License expiry date') ?></th>
<th><?= t('driver_card_expiration_date') ?></th>
  <th><?= t('truck') ?></th>
  <th><?= t('plate') ?></th>
  <th><?= t('area') ?></th>
  <th><?= t('details') ?></th>
</tr>

<?php if(count($drivers) > 0): ?>
    <?php foreach($drivers as $d): ?>
    <tr>
      <td><?= htmlspecialchars($d['name']) ?></td>
     <td>
<?php if(!empty($d['imagedriver'])){ ?>
    <img src="../uploads/<?= htmlspecialchars($d['imagedriver']) ?>" class="driver-img">
<?php } ?>
</td>

<td><?= htmlspecialchars($d['phone']) ?></td>
<td><?= htmlspecialchars($d['national_id']) ?></td>
<td>
    <?= htmlspecialchars($d['iqama_expiry_date']) ?>
    <br>
    <?= expiryStatus($d['iqama_expiry_date']) ?>
</td>
<td>
    <?= htmlspecialchars($d['license_expiry_date']) ?>
    <br>
    <?= expiryStatus($d['license_expiry_date']) ?>
</td>
<td>
    <?= htmlspecialchars($d['driver_card_expiration_date']) ?>
    <br>
    <?= expiryStatus($d['driver_card_expiration_date']) ?>
</td>
<td><?= htmlspecialchars($d['truck_type']) ?></td>
<td><?= htmlspecialchars($d['plate_number']) ?></td>
<td><?= htmlspecialchars($d['work_area']) ?></td>
      <td>
        <a class="btn" href="driver_profile.php?id=<?= (int)$d['id'] ?>">
            <?= t('view') ?>
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="6"><?= t('no_data') ?></td>
</tr>
<?php endif; ?>

</table>
<script src="assets/dark-mode.js"></script>

</body>
</html>