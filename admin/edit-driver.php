<?php
session_start();
include('../include/connected.php');

/* =========================
   اللغة
========================= */
$lang = $_GET['lang'] ?? 'ar';
$lang = in_array($lang,['ar','en']) ? $lang : 'ar';

/* =========================
   الترجمة
========================= */
function t($key){

    global $lang;

    $trans = [

        'ar'=>[
            'title'=>'تعديل السائق',
            'name'=>'اسم السائق',
            'phone'=>'الجوال',
            'national_id'=>'رقم الهوية',
            'truck'=>'نوع السطحة',
            'plate'=>'لوحة السطحة',
            'area'=>'منطقة العمل',
            'image'=>'صورة السائق',
            'save'=>'حفظ التعديلات',
            'back'=>'رجوع'
        ],

        'en'=>[
            'title'=>'Edit Driver',
            'name'=>'Driver Name',
            'phone'=>'Phone',
            'national_id'=>'National ID',
            'truck'=>'Truck Type',
            'plate'=>'Plate Number',
            'area'=>'Work Area',
            'image'=>'Driver Image',
            'save'=>'Save Changes',
            'back'=>'Back'
        ]

    ];

    return $trans[$lang][$key] ?? $key;
}

/* =========================
   جلب بيانات السائق
========================= */

$id = (int)($_GET['id'] ?? 0);

$query = mysqli_query($con,"SELECT * FROM drivers WHERE id='$id'");
$driver = mysqli_fetch_assoc($query);

if(!$driver){
    die("Driver Not Found");
}

/* =========================
   حفظ التعديل
========================= */

if(isset($_POST['save'])){

    $name = mysqli_real_escape_string($con,$_POST['name']);
    $phone = mysqli_real_escape_string($con,$_POST['phone']);
    $national_id = mysqli_real_escape_string($con,$_POST['national_id']);
    $iqama_expiry_date =mysqli_real_escape_string($con,$_POST['iqama_expiry_date']);
    $license_expiry_date = mysqli_real_escape_string($con,$_POST['license_expiry_date']);
        $driver_card_expiration_date=mysqli_real_escape_string($con,$_POST['driver_card_expiration_date']);
    $truck_type = mysqli_real_escape_string($con,$_POST['truck_type']);
    $plate_number = mysqli_real_escape_string($con,$_POST['plate_number']);
    $work_area = mysqli_real_escape_string($con,$_POST['work_area']);
    
    
    $imgQuery = "";

if(!empty($_FILES['imagedriver']['name'])){

    $image_name = time().'_'.$_FILES['imagedriver']['name'];

    move_uploaded_file(
        $_FILES['imagedriver']['tmp_name'],
        '../uploads/'.$image_name
    );

    $imgQuery = ", imagedriver='$image_name'";
}

mysqli_query($con,"
UPDATE drivers SET
name='$name',
phone='$phone',
national_id='$national_id',
iqama_expiry_date = '$iqama_expiry_date',
license_expiry_date = '$license_expiry_date',
driver_card_expiration_date= '$driver_card_expiration_date',
truck_type='$truck_type',
plate_number='$plate_number',
work_area='$work_area'


$imgQuery
WHERE id='$id'
");
    header("Location: drivers.php?lang=$lang");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= t('title') ?></title>

<link rel="stylesheet" href="assets/dark-mode.css">

<style>

body{
    font-family:Arial;
    background:#f4f6f9;
    margin:0;
    padding:0;
}

.container{
    width:90%;
    max-width:700px;
    margin:40px auto;
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,.1);
}

h2{
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:8px;
    font-size:15px;
}

img{
    width:120px;
    height:120px;
    border-radius:50%;
    object-fit:cover;
    margin-bottom:15px;
}

.btn{
    padding:12px 18px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    color:#fff;
    font-size:15px;
}

.save-btn{
    background:#28a745;
}

.back-btn{
    background:#007bff;
    text-decoration:none;
    display:inline-block;
}

.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.lang a{
    text-decoration:none;
    margin:0 5px;
    font-size:14px;
}

.dark-btn{
    width:45px;
    height:45px;
    border:none;
    border-radius:50%;
    background:#111;
    color:#fff;
    cursor:pointer;
    font-size:18px;
}

</style>

</head>

<body>

<div class="container">

<div class="top-bar">

<div class="lang">
    <a href="?id=<?= $id ?>&lang=ar">🇸🇦 عربي</a>
    <a href="?id=<?= $id ?>&lang=en">🇬🇧 English</a>
</div>

<button onclick="toggleDarkMode()" class="dark-btn">
🌙
</button>

</div>

<h2>🚗 <?= t('title') ?></h2>

<form method="post" enctype="multipart/form-data">

<?php if(!empty($driver['image'])){ ?>

<img src="../uploads/<?= $driver['image'] ?>">

<?php } ?>

<input
type="text"
name="name"
placeholder="<?= t('name') ?>"
value="<?= htmlspecialchars($driver['name']) ?>"
required
>

<input
type="text"
name="phone"
placeholder="<?= t('phone') ?>"
value="<?= htmlspecialchars($driver['phone']) ?>"
required
>

<input
type="text"
name="national_id"
placeholder="<?= t('national_id') ?>"
value="<?= htmlspecialchars($driver['national_id']) ?>"
required
>


<input
type="date"
name="iqama_expiry_date"
placeholder="<?= t('iqama_expiry_date') ?>"
value="<?= htmlspecialchars($driver['iqama_expiry_date']) ?>"
required
>

<input
type="date"
name="license_expiry_date"
placeholder="<?= t('license_expiry_date') ?>"
value="<?= htmlspecialchars($driver['license_expiry_date']) ?>"
required
>

<input
type="date"
name="driver_card_expiration_date"
placeholder="<?= t('driver_card_expiration_date') ?>"
value="<?= htmlspecialchars($driver['driver_card_expiration_date']) ?>"
required
>


<input
type="text"
name="truck_type"
placeholder="<?= t('truck') ?>"
value="<?= htmlspecialchars($driver['truck_type']) ?>"
required
>

<input
type="text"
name="plate_number"
placeholder="<?= t('plate') ?>"
value="<?= htmlspecialchars($driver['plate_number']) ?>"
required
>

<input
type="text"
name="work_area"
placeholder="<?= t('area') ?>"
value="<?= htmlspecialchars($driver['work_area']) ?>"
required
>

<label><?= t('imagedriver') ?></label>

<input type="file" name="imagedriver">

<br><br>

<button type="submit" name="save" class="btn save-btn">
💾 <?= t('save') ?>
</button>

<a href="drivers.php?lang=<?= $lang ?>" class="btn back-btn">
⬅ <?= t('back') ?>
</a>

</form>

</div>

<script src="assets/dark-mode.js"></script>

</body>
</html>