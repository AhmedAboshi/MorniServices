<?php
include('../include/connected.php');

/* ===== اللغة ===== */
$lang = $_GET['lang'] ?? 'ar';

/* ===== الترجمة ===== */
$trans = [
    'ar' => [
        'title' => 'تفاصيل السائق',
        'name' => 'الاسم',
        'national_id' => 'رقم الهوية',
        'phone' => 'الجوال',
        'truck_type' => 'نوع الشاحنة',
        'plate_number' => 'رقم اللوحة',
        'work_area' => 'منطقة العمل',
        'created_at' => 'تاريخ الإضافة',
        'back' => 'الرجوع للقائمة',
        'iqama_expiry_date'=> 'تاريخ انتهاء الاقامة',
        'license_expiry_date' => 'تاريخ انتهاء الرخصة',
        'driver_card_expiration_date' => 'تاريخ انتهاء بطاقة السائق'
    ],
    'en' => [
        'title' => 'Driver Details',
        'name' => 'Name',
        'national_id' => 'National ID',
        'phone' => 'Phone',
        'truck_type' => 'Truck Type',
        'plate_number' => 'Plate Number',
        'work_area' => 'Work Area',
        'created_at' => 'Created At',
        'back' => 'Back to List',
        'iqama_expiry_date' => 'iqama_expiry_date',
'license_expiry_date' => 'license_expiry_date',
'driver_card_expiration_date' => 'driver_card_expiration_date'
    ]
];

function t($key){
    global $trans, $lang;
    return $trans[$lang][$key] ?? $key;
}

/* 🆔 التحقق من ID */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = $con->query("SELECT * FROM drivers WHERE id = $id");

if ($result->num_rows == 0) {
    die("Driver not found");
}

$driver = $result->fetch_assoc();
$iqamaStatus  = documentStatus($driver['iqama_expiry_date']);
$licenseStatus = documentStatus($driver['license_expiry_date']);
$cardStatus = documentStatus($driver['driver_card_expiration_date']);

/* 🔐 حماية */
function e($value){
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function documentStatus($date){

    if(empty($date) || $date=='0000-00-00'){
        return [
            'text'=>'غير مسجل',
            'class'=>'secondary'
        ];
    }

    $today = new DateTime();
    $expiry = new DateTime($date);

    $days = (int)$today->diff($expiry)->format('%r%a');

    if($days < 0){
        return [
            'text'=>'منتهي منذ '.abs($days).' يوم',
            'class'=>'danger'
        ];
    }

    if($days <= 30){
        return [
            'text'=>'ينتهي خلال '.$days.' يوم',
            'class'=>'warning'
        ];
    }

    return [
        'text'=>'ساري',
        'class'=>'success'
    ];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= t('title') ?></title>

<style>
body {
    font-family: Arial;
    background: #f4f6f9;
}

.container {
    width: 50%;
    margin: 40px auto;
}

.card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

p {
    font-size: 16px;
    margin: 10px 0;
}

.lang {
    margin-bottom: 10px;
}

.lang a {
    margin: 0 5px;
    text-decoration: none;
}

.back-btn {
    display: inline-block;
    margin-top: 15px;
    background: #3498db;
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;

}
.driver-box{
    display:flex;
    align-items:flex-start;
    gap:30px;
}

.driver-image{
    width:220px;
    text-align:left;
}

.driver-image img{
    width:200px;
    height:200px;
    object-fit:cover;
    border-radius:12px;
    border:3px solid #3498db;
}

.driver-info{
    flex:1;
}
.document-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:15px;
    margin-top:25px;
}

.document-card{
    background:#fff;
    border-radius:12px;
    padding:18px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
}

.document-card h4{
    margin:0 0 10px;
}

.badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    color:#fff;
    font-size:13px;
    font-weight:bold;
}

.success{
    background:#198754;
}

.warning{
    background:#ffc107;
    color:#000;
}

.danger{
    background:#dc3545;
}

.secondary{
    background:#6c757d;
}
/* ===========================
   Driver Documents
=========================== */

.documents-section{
    margin-top:30px;
}

.documents-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:20px;
}

.document-card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    padding:20px;
    border-top:4px solid #0d6efd;
}

.document-card h4{
    margin:0 0 15px;
    font-size:18px;
}

.document-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:15px;
}

.document-actions a{
    text-decoration:none;
    padding:8px 14px;
    border-radius:6px;
    color:#fff;
    font-size:14px;
}

.btn-view{
    background:#0d6efd;
}

.btn-upload{
    background:#198754;
}

.btn-replace{
    background:#fd7e14;
}
</style>
<link rel="stylesheet" href="assets/dark-mode.css">
</head>
<body>

<div class="container">

<!-- 🌍 تغيير اللغة -->
<div class="lang">
    <a href="?id=<?= $id ?>&lang=ar">🇸🇦 عربي</a>
    <a href="?id=<?= $id ?>&lang=en">🇬🇧 English</a>
    <button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button>
</div>

<h2>👤 <?= t('title') ?></h2>
<a class="back-btn" href="driversview.php">
    <?= t('back') ?>
</a>

<div class="card">

<div class="driver-box">

<div class="driver-image">
    <?php if(!empty($driver['imagedriver'])){ ?>
        <img src="../uploads/<?= htmlspecialchars($driver['imagedriver']) ?>" alt="Driver Image">
    <?php } ?>
</div>

    <div class="driver-info">
        <p><strong><?= t('name') ?>:</strong> <?= e($driver['name']) ?></p>
        <p><strong><?= t('national_id') ?>:</strong> <?= e($driver['national_id']) ?></p>
        <p><strong><?= t('phone') ?>:</strong> <?= e($driver['phone']) ?></p>
        <p><strong><?= t('iqama_expiry_date') ?>:</strong> <?= e($driver['iqama_expiry_date']) ?></p>
        <p><strong><?= t('license_expiry_date') ?>:</strong> <?= e($driver['license_expiry_date']) ?></p>
        <p><strong><?= t('driver_card_expiration_date') ?>:</strong> <?= e($driver['driver_card_expiration_date']) ?></p>
        <p><strong><?= t('truck_type') ?>:</strong> <?= e($driver['truck_type']) ?></p>
        <p><strong><?= t('plate_number') ?>:</strong> <?= e($driver['plate_number']) ?></p>
        <p><strong><?= t('work_area') ?>:</strong> <?= e($driver['work_area']) ?></p>
        <p><strong><?= t('created_at') ?>:</strong> <?= e($driver['created_at']) ?></p>
    </div>

</div>

</div>
<hr style="margin:35px 0;">

<h2>📄 مستندات السائق</h2>

<div class="documents-section">

<div class="documents-grid">

    <div class="document-card">

        <h4>🪪 إقامة السائق</h4>

        <p>لا يوجد ملف مرفوع.</p>

        <div class="document-actions">

            <a href="#" class="btn-view">عرض</a>

            <a href="#" class="btn-upload">رفع</a>

            <a href="#" class="btn-replace">استبدال</a>

        </div>

    </div>

    <div class="document-card">

        <h4>🚗 رخصة القيادة</h4>

        <p>لا يوجد ملف مرفوع.</p>

        <div class="document-actions">

            <a href="#" class="btn-view">عرض</a>

            <a href="#" class="btn-upload">رفع</a>

            <a href="#" class="btn-replace">استبدال</a>

        </div>

    </div>

    <div class="document-card">

        <h4>💳 بطاقة السائق</h4>

        <p>لا يوجد ملف مرفوع.</p>

        <div class="document-actions">

            <a href="#" class="btn-view">عرض</a>

            <a href="#" class="btn-upload">رفع</a>

            <a href="#" class="btn-replace">استبدال</a>

        </div>

    </div>

</div>

</div>

<h3 style="margin-top:30px;">📄 المستندات</h3>

<div class="document-grid">

<div class="document-card">
<h4>🪪 الإقامة</h4>

<p><?= e($driver['iqama_expiry_date']) ?></p>

<span class="badge <?= $iqamaStatus['class'] ?>">
<?= $iqamaStatus['text'] ?>
</span>

</div>

<div class="document-card">
<h4>🚗 الرخصة</h4>

<p><?= e($driver['license_expiry_date']) ?></p>

<span class="badge <?= $licenseStatus['class'] ?>">
<?= $licenseStatus['text'] ?>
</span>

</div>

<div class="document-card">
<h4>💳 بطاقة السائق</h4>

<p><?= e($driver['driver_card_expiration_date']) ?></p>

<span class="badge <?= $cardStatus['class'] ?>">
<?= $cardStatus['text'] ?>
</span>

</div>

</div>



</div>

<script src="assets/dark-mode.js"></script>
</body>
</html>