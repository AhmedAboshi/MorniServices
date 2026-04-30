
<?php
session_start();
include('../include/core.php');
include('../include/connected.php');

if(isset($_POST['fleetadd'])){

    $driver     = mysqli_real_escape_string($con, $_POST['driver']);
    $plate      = mysqli_real_escape_string($con, $_POST['plate']);
    $typefleet  = mysqli_real_escape_string($con, $_POST['typefleet']);
    $classify   = mysqli_real_escape_string($con, $_POST['classify']);
    $model      = mysqli_real_escape_string($con, $_POST['model']);
    $colorfleet = mysqli_real_escape_string($con, $_POST['colorfleet']);
    $work       = mysqli_real_escape_string($con, $_POST['work']);

    // 📸 الصورة
    $imgname = $_FILES['imgfleet']['name'];
    $tmp     = $_FILES['imgfleet']['tmp_name'];
    $size    = $_FILES['imgfleet']['size'];

    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($imgname, PATHINFO_EXTENSION));

    if(empty($driver) || empty($plate) || empty($typefleet) || empty($classify) || empty($model)){
        echo "<script>alert('".t('fill_fields')."');</script>";
    }
    elseif(!in_array($ext, $allowed)){
        echo "<script>alert('".t('image_error')."');</script>";
    }
    elseif($size > 2*1024*1024){
        echo "<script>alert('".t('image_size')."');</script>";
    }
    else{

        $newName = time() . "_" . $imgname;
        move_uploaded_file($tmp, "../fleetimg/img/".$newName);

        $query = "INSERT INTO fleet 
        (driver, imgfleet, plate, typefleet, classify, model, colorfleet, work)
        VALUES 
        ('$driver','$newName','$plate','$typefleet','$classify','$model','$colorfleet','$work')";

        if(mysqli_query($con,$query)){
            echo "<script>alert('تم إضافة المركبة بنجاح');</script>";
            header("Location: fleet.php");
        }else{
            echo "<script>alert('حدث خطأ');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= t('add_vehicle') ?></title>

<style>
body{
    font-family: 'Cairo';
    background:#f4f6f9;
}

.form_product{
    width: 50%;
    margin: 40px auto;
    padding: 20px;
    background:#fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-radius:10px;
}

h1{
    text-align:center;
}

label{
    display:block;
    margin-top:10px;
}

input, select{
    width:100%;
    padding:10px;
    margin-top:5px;
    border:1px solid #ccc;
    border-radius:5px;
}

.button{
    width:100%;
    margin-top:20px;
    padding:14px;
    background:linear-gradient(135deg,#3498db,#2980b9);
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
}

.button:hover{
    background:#2980b9;
    transform:translateY(-2px);
}
</style>

</head>
<body>

<a href="?lang=ar">🇸🇦 عربي</a>
<a href="?lang=en">🇬🇧 English</a>

<div class="form_product">
<h1><?= t('add_vehicle') ?></h1>

<label><?= t('driver') ?></label>
<input type="text" name="driver">

<label><?= t('vehicle_image') ?></label>
<input type="file" name="imgfleet">

<label><?= t('plate') ?></label>
<input type="text" name="plate">

<label><?= t('type') ?></label>
<input type="text" name="typefleet">

<label><?= t('class') ?></label>
<input type="text" name="classify">

<label><?= t('model') ?></label>
<input type="text" name="model">

<label><?= t('color') ?></label>
<input type="text" name="colorfleet">

<label><?= t('work_city') ?></label>
<select name="work">
    <option value="riyadh"><?= $lang=='ar' ? 'الرياض' : 'Riyadh' ?></option>
    <option value="jeddah"><?= $lang=='ar' ? 'جدة' : 'Jeddah' ?></option>
    <option value="dammam"><?= $lang=='ar' ? 'الدمام' : 'Dammam' ?></option>
</select>

<button class="button" name="fleetadd"><?= t('add') ?></button>

</form>
</div>

</body>
</html>

