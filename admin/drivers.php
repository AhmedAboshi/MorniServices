<?php
include('../include/connected.php');

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;
$alert = $_GET['alert'] ?? 0;
$lang = $_GET['lang'] ?? 'ar';
$lang = in_array($lang,['ar','en']) ? $lang : 'ar';

/* =========================
   السائق المحدد والتنبيه
========================= */

$driver_id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';


$stmt = $con->prepare("SELECT * FROM drivers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

/* ===== الترجمة ===== */
function t($key){
    global $lang;

    $trans = [
        'ar'=>[
            'title'=>'إدارة السائقين',
            'name'=>'اسم السائق',
            'phone'=>'الجوال',
            'truck'=>'نوع السطحة',
            'plate'=>'لوحة السطحة',
            'area'=>'منطقة العمل',
            'actions'=>'إجراءات',
            'edit'=>'تعديل',
            'delete'=>'حذف',
            'save'=>'حفظ',
            'print'=>'طباعة الكرت',
            'copy'=>'نسخ الرابط',
            'iqama_expiry_date'=> 'تاريخ انتهاء اقامة السائق',
            'license_expiry_date'=> 'تاريخ انتهاء رخصة السائق',
            'Image'=> 'صورة السائق',
            'driver_card_expiration_date'=>'تاريخ انتهاء بطاقة السائق'
        ],
        'en'=>[
            'title'=>'Drivers Management',
            'name'=>'Driver Name',
            'phone'=>'Phone',
            'truck'=>'Truck Type',
            'plate'=>'Plate Number',
            'area'=>'Work Area',
            'actions'=>'Actions',
            'edit'=>'Edit',
            'delete'=>'Delete',
            'save'=>'Save',
            'print'=>'Print Card',
            'copy'=>'Copy Link',
            'iqama_expiry_date'=> 'iqama_expiry_date',
            'license_expiry_date'=> 'license_expiry_date',
             'Image'=> 'Image',
            'driver_card_expiration_date'=> 'driver_card_expiration_date'
        ]
    ];

    return $trans[$lang][$key] ?? $key;
}

/* ===== حذف ===== */
if(isset($_POST['delete_id'])){
    $id = (int)$_POST['delete_id'];
    mysqli_query($con,"DELETE FROM drivers WHERE id=$id");
    header("Location: drivers.php?lang=$lang");
    exit();
}

/* ===== تعديل ===== */
$edit = null;

if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM drivers WHERE id=$id"));
}

/* ===== حفظ ===== */
if(isset($_POST['save'])){

    $id = (int)($_POST['id'] ?? 0);

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $national_id = $_POST['national_id'];

    $iqama_expiry_date = $_POST['iqama_expiry_date'];
    $license_expiry_date = $_POST['license_expiry_date'];
    $driver_card_expiration_date = $_POST['driver_card_expiration_date'];

    $truck_type = $_POST['truck_type'];
    $plate_number = $_POST['plate_number'];
    $work_area = $_POST['work_area'];

    $imagedriver = '';

    if(!empty($_FILES['imagedriver']['name'])){
        $imagedriver = time().'_'.$_FILES['imagedriver']['name'];
        move_uploaded_file($_FILES['imagedriver']['tmp_name'],'../uploads/'.$imagedriver);
    }

    /* ===== إضافة ===== */
    if($id == 0){

        mysqli_query($con,"
        INSERT INTO drivers(name,national_id,iqama_expiry_date,license_expiry_date,driver_card_expiration_date,phone,truck_type,plate_number,work_area,imagedriver)
        VALUES('$name','$national_id','$iqama_expiry_date','$license_expiry_date','$driver_card_expiration_date','$phone','$truck_type','$plate_number','$work_area','$imagedriver')
        ");

        $driver_id = mysqli_insert_id($con);
        $qr_code = "DRIVER_".$driver_id;

        mysqli_query($con,"UPDATE drivers SET qr_code='$qr_code' WHERE id=$driver_id");
    }

    /* ===== تعديل ===== */
    else {
        mysqli_query($con,"
        UPDATE drivers SET
        name='$name',
        national_id='$national_id',
        iqama_expiry_date= '$iqama_expiry_date',
        license_expiry_date= '$license_expiry_date',
        driver_card_expiration_date ='$driver_card_expiration_date',
        phone='$phone',
        truck_type='$truck_type',
        plate_number='$plate_number',
        work_area='$work_area'
        WHERE id=$id
        ");
    }

    header("Location: drivers.php?lang=$lang");
    exit();
}

/* ===== عرض ===== */
if($id > 0){
    $result = mysqli_query($con,"SELECT * FROM drivers WHERE id=$id");
} else {
    $result = mysqli_query($con,"SELECT * FROM drivers ORDER BY id DESC");
}?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>
<meta charset="UTF-8">
<title><?= t('title') ?></title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.container{width:95%;margin:auto}

table{width:100%;background:#fff;margin-top:20px;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:10px;text-align:center}
th{background:#28a745;color:#fff}

.driver-img{width:60px;height:60px;border-radius:50%}
.qr-img{width:80px}

.btn{
    padding:5px 8px;
    border:none;
    border-radius:5px;
    color:#fff;
    font-size:12px;
    cursor:pointer;
    margin:2px;
}

.btn-copy{background:#28a745}
.btn-wa{background:#25D366}
.btn-print{background:#ff9800}

.edit{
    background:#007bff;
    padding:5px 8px;
    border-radius:5px;
    color:#fff;
    text-decoration:none;
    font-size:12px;
    margin:2px;
}

.delete{
    background:red;
    padding:5px 8px;
    border:none;
    border-radius:5px;
    color:#fff;
    font-size:12px;
}
.card-count{
    background:#fff;
    padding:15px;
    border-radius:10px;
    min-width:180px;
    text-align:center;
    box-shadow:0 2px 8px #ccc;
    font-size:18px;
}

.card-count h3{
    font-size:30px;
    margin:5px;
}

.card-count.danger{
    border-right:5px solid red;
}
.filter-btn{
    padding:8px 15px;
    border:none;
    border-radius:6px;
    background:#007bff;
    color:#fff;
    margin:5px;
    cursor:pointer;
}

.filter-btn.expired{
    background:#dc3545;
}
/* =========================
   نموذج السائق
========================= */

.driver-form-card{
    background:#fff;
    border-radius:16px;
    margin:25px 0;
    padding:0;
    box-shadow:0 4px 18px rgba(0,0,0,.08);
    overflow:hidden;
    border:1px solid #e8ebef;
}

.driver-form-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:20px 25px;
    background:linear-gradient(135deg,#198754,#28a745);
    color:#fff;
}

.driver-form-header h3{
    margin:0 0 5px;
    font-size:21px;
}

.driver-form-header p{
    margin:0;
    opacity:.9;
    font-size:13px;
}

.driver-form-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    background:rgba(255,255,255,.18);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:27px;
}

.driver-form-card form{
    padding:25px;
}

.form-section{
    margin-bottom:25px;
}

.form-section-title{
    font-size:16px;
    font-weight:bold;
    color:#198754;
    border-bottom:1px solid #e5e7eb;
    padding-bottom:10px;
    margin-bottom:18px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}

.form-group{
    display:flex;
    flex-direction:column;
    gap:7px;
}

.form-group label{
    font-size:13px;
    font-weight:bold;
    color:#374151;
}

.form-control{
    width:100%;
    box-sizing:border-box;
    min-height:43px;
    padding:10px 12px;
    border:1px solid #d7dce1;
    border-radius:9px;
    background:#fff;
    color:#222;
    font-size:14px;
    outline:none;
    transition:.2s;
}

.form-control:focus{
    border-color:#198754;
    box-shadow:0 0 0 3px rgba(25,135,84,.10);
}

.driver-upload{
    display:flex;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.current-driver-image{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:7px;
    font-size:12px;
    color:#6b7280;
}

.current-driver-image img{
    width:80px;
    height:80px;
    object-fit:cover;
    border-radius:50%;
    border:3px solid #198754;
}

.upload-box{
    flex:1;
    min-height:85px;
    border:2px dashed #cfd6dc;
    border-radius:12px;
    display:flex;
    align-items:center;
    gap:15px;
    padding:15px;
    position:relative;
    cursor:pointer;
}

.upload-icon{
    font-size:30px;
}

.upload-box strong{
    display:block;
    margin-bottom:5px;
}

.upload-box small{
    display:block;
    color:#777;
}

.upload-box input[type="file"]{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
}

.driver-form-actions{
    display:flex;
    gap:10px;
    justify-content:flex-start;
    border-top:1px solid #eee;
    padding-top:20px;
}

.btn-save-driver,
.btn-cancel-driver{
    border:none;
    border-radius:9px;
    padding:11px 22px;
    font-size:14px;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
}

.btn-save-driver{
    background:#198754;
    color:#fff;
}

.btn-save-driver:hover{
    background:#157347;
}

.btn-cancel-driver{
    background:#6c757d;
    color:#fff;
}

.btn-cancel-driver:hover{
    background:#5c636a;
}


/* =========================
   Responsive
========================= */

@media(max-width:900px){

    .form-grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:600px){

    .form-grid{
        grid-template-columns:1fr;
    }

    .driver-form-card form{
        padding:18px;
    }

    .driver-form-header{
        padding:16px;
    }

    .driver-form-actions{
        flex-direction:column;
    }

    .btn-save-driver,
    .btn-cancel-driver{
        width:100%;
    }

}
</style>
<link rel="stylesheet" href="assets/dark-mode.css">
</head>

<body>

<!-- 🌍 تغيير اللغة -->
<div class="lang">
    <a href="?lang=ar">🌍🇸🇦 عربي</a>
<a href="?lang=en">🌍🇬🇧 English</a>
    <button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button>
</div>

<div class="container">

<h2>🚗 <?= t('title') ?></h2>

<?php
$today = date('Y-m-d');

/* العدادات */
$total_drivers = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total FROM drivers
"))['total'];

$expired_iqama = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total FROM drivers
WHERE iqama_expiry_date < '$today'
"))['total'];

$expired_license = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total FROM drivers
WHERE license_expiry_date < '$today'
"))['total'];

$expired_card = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total FROM drivers
WHERE driver_card_expiration_date < '$today'
"))['total'];
?>

<div style="display:flex;gap:15px;flex-wrap:wrap;margin:20px 0">

<div class="card-count">
👨‍✈️
<h3><?= $total_drivers ?></h3>
<p>إجمالي السائقين</p>
</div>

<div class="card-count danger">
🪪
<h3><?= $expired_iqama ?></h3>
<p>الإقامات المنتهية</p>
</div>

<div class="card-count danger">
🚗
<h3><?= $expired_license ?></h3>
<p>الرخص المنتهية</p>
</div>

<div class="card-count danger">
💳
<h3><?= $expired_card ?></h3>
<p>بطاقات السائق المنتهية</p>
</div>

</div>

<div style="margin-top:20px;text-align:center">

<input 
type="text" 
id="searchDriver"
placeholder="🔍 بحث باسم السائق أو الجوال أو الهوية أو اللوحة"
style="width:60%;padding:10px;border-radius:8px;border:1px solid #ccc"
>

</div>


<div style="text-align:center;margin-top:15px">

<button class="filter-btn" onclick="filterDrivers('all')">
كل السائقين
</button>

<button class="filter-btn expired" onclick="filterDrivers('iqama')">
🪪 إقامة منتهية
</button>

<button class="filter-btn expired" onclick="filterDrivers('license')">
🚗 رخصة منتهية
</button>

<button class="filter-btn expired" onclick="filterDrivers('card')">
💳 بطاقة منتهية
</button>

</div>

<!-- الفورم -->
<!-- =========================
     نموذج إضافة / تعديل السائق
========================= -->

<div class="driver-form-card">

    <div class="driver-form-header">

        <div>
            <h3>
                <?= $edit ? '✏️ تعديل بيانات السائق' : '➕ إضافة سائق جديد' ?>
            </h3>

            <p>
                <?= $edit
                    ? 'تعديل بيانات السائق والمستندات'
                    : 'إضافة سائق جديد إلى النظام'
                ?>
            </p>
        </div>

        <div class="driver-form-icon">
            👨‍✈️
        </div>

    </div>


    <form method="post" enctype="multipart/form-data">

        <input
            type="hidden"
            name="id"
            value="<?= (int)($edit['id'] ?? 0) ?>"
        >


        <!-- =========================
             البيانات الأساسية
        ========================= -->

        <div class="form-section">

            <div class="form-section-title">
                👤 البيانات الأساسية
            </div>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        اسم السائق
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['name'] ?? '') ?>"
                        placeholder="أدخل اسم السائق"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        رقم الجوال
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"
                        placeholder="05xxxxxxxx"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        رقم الهوية
                    </label>

                    <input
                        type="text"
                        name="national_id"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['national_id'] ?? '') ?>"
                        placeholder="رقم الهوية / الإقامة"
                    >

                </div>


                <div class="form-group">

    <label>
        🌍 الجنسية
    </label>

    <select
        name="nationality"
        class="form-control"
        required
    >

        <option value="">
            اختر الجنسية
        </option>

        <option
            value="سعودي"
            <?= (($edit['nationality'] ?? '') === 'سعودي') ? 'selected' : '' ?>
        >
            سعودي
        </option>

        <option
            value="باكستاني"
            <?= (($edit['nationality'] ?? '') === 'باكستاني') ? 'selected' : '' ?>
        >
            باكستاني
        </option>

        <option
            value="هندي"
            <?= (($edit['nationality'] ?? '') === 'هندي') ? 'selected' : '' ?>
        >
            هندي
        </option>

        <option
            value="بنغلاديشي"
            <?= (($edit['nationality'] ?? '') === 'بنغلاديشي') ? 'selected' : '' ?>
        >
            بنغلاديشي
        </option>

        <option
            value="مصري"
            <?= (($edit['nationality'] ?? '') === 'مصري') ? 'selected' : '' ?>
        >
            مصري
        </option>

        <option
            value="سوداني"
            <?= (($edit['nationality'] ?? '') === 'سوداني') ? 'selected' : '' ?>
        >
            سوداني
        </option>

        <option
            value="يمني"
            <?= (($edit['nationality'] ?? '') === 'يمني') ? 'selected' : '' ?>
        >
            يمني
        </option>

        <option
            value="سوري"
            <?= (($edit['nationality'] ?? '') === 'سوري') ? 'selected' : '' ?>
        >
            سوري
        </option>

        <option
            value="أردني"
            <?= (($edit['nationality'] ?? '') === 'أردني') ? 'selected' : '' ?>
        >
            أردني
        </option>

        <option
            value="فلسطيني"
            <?= (($edit['nationality'] ?? '') === 'فلسطيني') ? 'selected' : '' ?>
        >
            فلسطيني
        </option>

        <option
            value="أخرى"
            <?= (($edit['nationality'] ?? '') === 'أخرى') ? 'selected' : '' ?>
        >
            أخرى
        </option>

    </select>

</div>

            </div>

        </div>


        <!-- =========================
             المستندات
        ========================= -->

        <div class="form-section">

            <div class="form-section-title">
                🪪 المستندات والتواريخ
            </div>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        تاريخ انتهاء الإقامة
                    </label>

                    <input
                        type="date"
                        name="iqama_expiry_date"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['iqama_expiry_date'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        تاريخ انتهاء الرخصة
                    </label>

                    <input
                        type="date"
                        name="license_expiry_date"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['license_expiry_date'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        تاريخ انتهاء بطاقة السائق
                    </label>

                    <input
                        type="date"
                        name="driver_card_expiration_date"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['driver_card_expiration_date'] ?? '') ?>"
                    >

                </div>

            </div>

        </div>


        <!-- =========================
             بيانات السطحة
        ========================= -->

        <div class="form-section">

            <div class="form-section-title">
                🚚 بيانات السطحة
            </div>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        نوع السطحة
                    </label>

                    <input
                        type="text"
                        name="truck_type"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['truck_type'] ?? '') ?>"
                        placeholder="مثال: سطحة هيدروليك"
                    >

                </div>


                <div class="form-group">

                    <label>
                        لوحة السطحة
                    </label>

                    <input
                        type="text"
                        name="plate_number"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['plate_number'] ?? '') ?>"
                        placeholder="رقم اللوحة"
                    >

                </div>


                <div class="form-group">

                    <label>
                        منطقة العمل
                    </label>

                    <input
                        type="text"
                        name="work_area"
                        class="form-control"
                        value="<?= htmlspecialchars($edit['work_area'] ?? '') ?>"
                        placeholder="مثال: الرياض"
                    >

                </div>

            </div>

        </div>


        <!-- =========================
             صورة السائق
        ========================= -->

        <div class="form-section">

            <div class="form-section-title">
                📷 صورة السائق
            </div>

            <div class="driver-upload">

                <?php if (!empty($edit['imagedriver'])): ?>

                    <div class="current-driver-image">

                        <img
                            src="../uploads/<?= htmlspecialchars($edit['imagedriver']) ?>"
                            alt="صورة السائق"
                        >

                        <span>
                            الصورة الحالية
                        </span>

                    </div>

                <?php endif; ?>


                <div class="upload-box">

                    <span class="upload-icon">
                        📷
                    </span>

                    <div>

                        <strong>
                            <?= !empty($edit['imagedriver'])
                                ? 'تغيير صورة السائق'
                                : 'رفع صورة السائق'
                            ?>
                        </strong>

                        <small>
                            JPG / PNG
                        </small>

                    </div>

                    <input
                        type="file"
                        name="imagedriver"
                        accept="image/*"
                    >

                </div>

            </div>

        </div>


        <!-- =========================
             أزرار
        ========================= -->

        <div class="driver-form-actions">

            <button
                type="submit"
                name="save"
                class="btn-save-driver"
            >
                💾
                <?= $edit ? 'حفظ التعديلات' : 'إضافة السائق' ?>
            </button>


            <?php if ($edit): ?>

                <a
                    href="drivers.php?lang=<?= urlencode($lang) ?>"
                    class="btn-cancel-driver"
                >
                    ↩️ إلغاء التعديل
                </a>

            <?php else: ?>

                <button
                    type="reset"
                    class="btn-cancel-driver"
                >
                    🔄 مسح الحقول
                </button>

            <?php endif; ?>

        </div>

    </form>

</div>

<!-- الجدول -->
<table>

<tr id="driver<?= $row['id'] ?>"><th>ID</th>
<th><?= t('Image') ?></th>
<th>QR</th>
<th><?= t('name') ?></th>
<th><?= t('phone') ?></th>
<th><?= t('truck') ?></th>
<th><?= t('plate') ?></th>
<th><?= t('area') ?></th>
<th><?= t('actions') ?></th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){
$attendance_url = "http://10.66.154.149/MorniServices/attendance.php?code=".$row['qr_code'];

$phone_clean = ltrim($row['phone'],'0');
$wa_link = "https://wa.me/966".$phone_clean."?text=".urlencode("مرحباً ".$row['name']." 👋\n".$attendance_url);

?>

<?php

/* =========================
   حالة مستندات السائق
========================= */

$driver_status = [];

/* الإقامة */
if (
    !empty($row['iqama_expiry_date']) &&
    $row['iqama_expiry_date'] < $today
) {
    $driver_status[] = 'iqama';
}

/* الرخصة */
if (
    !empty($row['license_expiry_date']) &&
    $row['license_expiry_date'] < $today
) {
    $driver_status[] = 'license';
}

/* بطاقة السائق */
if (
    !empty($row['driver_card_expiration_date']) &&
    $row['driver_card_expiration_date'] < $today
) {
    $driver_status[] = 'card';
}

?>

<tr
    id="driver<?= (int)$row['id'] ?>"
    data-status="<?= htmlspecialchars(implode(' ', $driver_status)) ?>"
>

<td><?= $row['id'] ?></td>

<td>
<?php if(!empty($row['imagedriver'])){ ?>
<img src="../uploads/<?= $row['imagedriver'] ?>" class="driver-img">
<?php } ?>
</td>

<td>
<?php if(!empty($row['qr_code'])){ ?>

<img 
src="../generate_qr.php?text=<?= urlencode($row['qr_code']) ?>" 
class="qr-img"
>

<br>

<?= $row['qr_code'] ?>

<?php } ?>
</td>

<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['phone']) ?></td>
<td><?= htmlspecialchars($row['truck_type']) ?></td>
<td><?= htmlspecialchars($row['plate_number']) ?></td>
<td><?= htmlspecialchars($row['work_area']) ?></td>

<td>

<a class="edit"
href="edit-driver.php?id=<?= $row['id'] ?>&lang=<?= $lang ?>">
<?= t('edit') ?>
</a>

<form method="post" style="display:inline;">
<input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
<button class="delete"><?= t('delete') ?></button>
</form>

<a class="btn btn-wa" href="<?= $wa_link ?>" target="_blank">
📲 <?= $row['name'] ?>
</a>

<button class="btn btn-copy" onclick="copyLink('<?= $attendance_url ?>')">
📋 <?= t('copy') ?>
</button>

<a class="btn btn-print" target="_blank"
href="driver_card.php?id=<?= $row['id'] ?>">
🖨 <?= t('print') ?>
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

<script>
function copyLink(link){
    navigator.clipboard.writeText(link);
    alert("تم النسخ ✔");
}
</script>
<script src="assets/dark-mode.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){

    let driverId = "<?= $driver_id ?>";
    let type = "<?= $type ?>";

    if(driverId){

        let row = document.getElementById("driver" + driverId);

        if(row){

            row.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            // تلوين الصف بالكامل
            row.style.backgroundColor = "#ccc";
            row.style.border = "3px solid red";

            row.querySelectorAll("td").forEach(td => {
                td.style.backgroundColor = "#ffcccc";
                td.style.fontWeight = "bold";
            });

            let msg = "";

            if(type === "iqama") msg = "تنبيه: الإقامة منتهية";
            if(type === "license") msg = "تنبيه: الرخصة منتهية";
            if(type === "card") msg = "تنبيه: بطاقة السائق منتهية";
            if(type === "fleet") msg = "تنبيه: الفحص الدوري منتهي";

            if(msg){
                alert(msg);
            }
        }
    }

});


const searchInput = document.getElementById("searchDriver");

searchInput.addEventListener("keyup", function(){

    let value = this.value.toLowerCase();

    document.querySelectorAll("table tr").forEach((row,index)=>{

        if(index===0) return;

        let text=row.innerText.toLowerCase();

        row.style.display =
        text.includes(value) ? "" : "none";

    });

});


function filterDrivers(type){

document.querySelectorAll("table tr").forEach((row,index)=>{

if(index===0) return;


let status=row.dataset.status;


if(type==="all"){
    row.style.display="";
}
else{

    row.style.display =
    status===type ? "" : "none";

}

});

}

</script>

</body>
</html>