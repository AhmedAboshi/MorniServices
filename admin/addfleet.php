<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../include/core.php');
include('../include/connected.php');

/* =========================
   🌐 اللغة
========================= */
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

$query = $_GET;

/* =========================
   🌍 الترجمة
========================= */
$translations = [

'ar'=>[
    'add_vehicle'=>'إضافة مركبة',
    'driver'=>'السائق',
    'vehicle_image'=>'صورة المركبة',
    'plate'=>'لوحة المركبة',
    'type'=>'النوع',
    'class'=>'التصنيف',
    'model'=>'الموديل',
    'color'=>'اللون',
    'work_city'=>'مدينة العمل',
    'add'=>'إضافة',
    'fill_fields'=>'يرجى تعبئة جميع الحقول',
    'image_error'=>'صيغة الصورة غير صحيحة',
    'image_size'=>'حجم الصورة كبير',
    'operation_expiry'=>'انتهاء كرت التشغيل',
'insurance_expiration_date'=>'انتهاء التامين',
'inspection_expiry'=>'انتهاء الفحص الدوري',
    'success'=>'تم إضافة المركبة بنجاح'
],

'en'=>[
    'add_vehicle'=>'Add Vehicle',
    'driver'=>'Driver',
    'vehicle_image'=>'Vehicle Image',
    'plate'=>'Plate Number',
    'type'=>'Type',
    'class'=>'Class',
    'model'=>'Model',
    'color'=>'Color',
    'work_city'=>'Work City',
    'add'=>'Add',
    'fill_fields'=>'Please fill all fields',
    'image_error'=>'Invalid image format',
    'image_size'=>'Image size too large',
    'operation_expiry'=>'Operation Card Expiry',
'insurance_expiration_date'=>'insurance_expiration_date',
'inspection_expiry'=>'Inspection Expiry',
    'success'=>'Vehicle Added Successfully'
]

];



/* =========================
   ➕ إضافة مركبة
========================= */
if(isset($_POST['fleetadd'])){


    $driver     = mysqli_real_escape_string($con, $_POST['driver']);
    $plate      = mysqli_real_escape_string($con, $_POST['plate']);
    $typefleet  = mysqli_real_escape_string($con, $_POST['typefleet']);
    $classify   = mysqli_real_escape_string($con, $_POST['classify']);
    $model      = mysqli_real_escape_string($con, $_POST['model']);
    $colorfleet = mysqli_real_escape_string($con, $_POST['colorfleet']);
    $work       = mysqli_real_escape_string($con, $_POST['work']);
    $operation_expiry   = $_POST['operation_expiry'];
    $insurance_expiration_date = $_POST['insurance_expiration_date'];
    $inspection_expiry   = $_POST['inspection_expiry'];

    /* 📸 الصورة */
    $imgname = $_FILES['imgfleet']['name'] ?? '';
    $tmp     = $_FILES['imgfleet']['tmp_name'] ?? '';
    $size    = $_FILES['imgfleet']['size'] ?? 0;

    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($imgname, PATHINFO_EXTENSION));

    if(
        empty($driver) ||
        empty($plate) ||
        empty($typefleet) ||
        empty($classify) ||
        empty($model)
    ){

        $error = t('fill_fields');

    }
    elseif(!empty($imgname) && !in_array($ext, $allowed)){

        $error = t('image_error');

    }
    elseif($size > 2*1024*1024){

        $error = t('image_size');

    }
    else{

        $newName = '';

        if(!empty($imgname)){

            if(!is_dir("../fleetimg/img")){
                mkdir("../fleetimg/img",0777,true);
            }

            $newName = time().'_'.$imgname;

            move_uploaded_file(
                $tmp,
                "../fleetimg/img/".$newName
            );
        }


        $check = $con->prepare("SELECT id FROM fleet WHERE plate = ?");
$check->bind_param("s", $plate);
$check->execute();
$result = $check->get_result();

if($result->num_rows > 0){

    $error = "هذه المركبة مسجلة مسبقاً (نفس رقم اللوحة)";

}else{

    $stmt = $con->prepare("
        INSERT INTO fleet
        (
            driver,
            imgfleet,
            plate,
            typefleet,
            classify,
            model,
            colorfleet,
            work,
            operation_expiry,
            insurance_expiration_date,
            inspection_expiry
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssssss",
        $driver,
        $newName,
        $plate,
        $typefleet,
        $classify,
        $model,
        $colorfleet,
        $work,
        $operation_expiry,
        $insurance_expiration_date,
        $inspection_expiry
    );

    if($stmt->execute()){

    /* =========================
       🧾 Audit Log (تسجيل العملية)
    ========================= */
    $log = $con->prepare("
        INSERT INTO audit_log (user, action, details)
        VALUES (?, ?, ?)
    ");

    $user = $_SESSION['admin_id'] ?? 'unknown';
    $action = "إضافة مركبة";
    $details = "تم إضافة مركبة لوحة: " . $plate;

    $log->bind_param("sss", $user, $action, $details);
    $log->execute();

    /* =========================
       ↪️ إعادة التوجيه
    ========================= */
    header("Location: fleet.php?lang=$lang&success=1");
    exit();

}
    }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>"
dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">

<head>

<meta charset="UTF-8">

<title><?= t('add_vehicle') ?></title>

<style>

body{
    font-family:'Cairo',sans-serif;
    background:#f4f6f9;
    margin:0;
}

.form_product{

    width:50%;
    margin:30px auto;
    padding:25px;
    background:#fff;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
    border-radius:12px;
}

h1{
    text-align:center;
    margin-bottom:20px;
}

label{
    display:block;
    margin-top:12px;
    font-weight:bold;
}

input,
select{

    width:100%;
    padding:12px;
    margin-top:5px;
    border:1px solid #ccc;
    border-radius:8px;
    box-sizing:border-box;
}

.button{

    width:100%;
    margin-top:25px;
    padding:14px;
    background:linear-gradient(135deg,#3498db,#2980b9);
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
    transition:0.3s;
}

.button:hover{

    background:#2980b9;
    transform:translateY(-2px);
}

.error{
    background:#ffe6e6;
    color:red;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
}

.success{
    background:#e6ffe6;
    color:green;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
}

.lang-switch{
    text-align:center;
    margin:15px 0;
}

.lang-switch a{
    text-decoration:none;
    padding:8px 15px;
    border-radius:6px;
    background:#eee;
    color:#333;
    font-weight:bold;
}

.lang-switch a.active{
    background:#28a745;
    color:#fff;
}

.lang-switch span{
    margin:0 10px;
    color:#999;
}
.btn-new{
    margin-top:10px;
    padding:10px 15px;
    background:#28a745;
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:14px;
}

.btn-new:hover{
    background:#218838;
}
</style>

</head>
<link rel="stylesheet" href="assets/dark-mode.css">
<body>

<!-- 🌍 اللغة -->
<div class="lang-switch">

<a href="?<?= http_build_query(array_merge($query,['lang'=>'ar'])) ?>"
class="<?= $lang=='ar'?'active':'' ?>">

🌍🇸🇦 عربي

</a>

<span>|</span>

<a href="?<?= http_build_query(array_merge($query,['lang'=>'en'])) ?>"
class="<?= $lang=='en'?'active':'' ?>">

🌍🇬🇧 English

</a>
<!-- <button onclick="toggleDarkMode()" class="dark-btn">
    🌙
</button> -->

</div>

<div class="form_product">

<h1><?= t('add_vehicle') ?></h1>

<?php if(isset($error)){ ?>

<div class="error">
<?= $error ?>
</div>

<?php } ?>

<?php if(isset($_GET['success'])){ ?>

<div class="success">
    <?= t('success') ?>

    <button type="button" onclick="resetForm()" class="btn-new">
        ➕ إضافة مركبة جديدة
    </button>
</div>

<?php } ?>

<form method="post" enctype="multipart/form-data" id="vehicleForm">

<label><?= t('driver') ?></label>
<input type="text" name="driver" required>

<label><?= t('vehicle_image') ?></label>
<input type="file" name="imgfleet">

<label><?= t('plate') ?></label>
<input type="text" name="plate" required>

<label><?= t('type') ?></label>
<input type="text" name="typefleet" required>

<label><?= t('class') ?></label>
<input type="text" name="classify" required>

<label><?= t('model') ?></label>
<input type="text" name="model" required>

<label><?= t('color') ?></label>
<input type="text" name="colorfleet">

<label><?= t('work_city') ?></label>

<select name="work">

<option value="riyadh">
<?= $lang=='ar' ? 'الرياض' : 'Riyadh' ?>
</option>

<option value="jeddah">
<?= $lang=='ar' ? 'جدة' : 'Jeddah' ?>
</option>

<option value="dammam">
<?= $lang=='ar' ? 'الدمام' : 'Dammam' ?>
</option>

</select>
<label><?= t('operation_expiry') ?></label>
<input type="date" name="operation_expiry">

<label><?= t('insurance_expiration_date') ?></label>
<input type="date" name="insurance_expiration_date">

<label><?= t('inspection_expiry') ?></label>
<input type="date" name="inspection_expiry">

<button
class="button"
type="submit"
name="fleetadd">

<?= t('add') ?>

</button>

</form>

</div>
<script>
function resetForm(){

    const form = document.getElementById("vehicleForm");

    if(form){
        form.reset();
    }

    // يرجع لأعلى الفورم
    window.scrollTo({ top: 0, behavior: 'smooth' });

}
</script>
<script src="assets/dark-mode.js"></script>
</body>
</html>