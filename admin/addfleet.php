
<?php
session_start();
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
        echo "<script>alert('يرجى تعبئة جميع الحقول');</script>";
    }
    elseif(!in_array($ext, $allowed)){
        echo "<script>alert('نوع الصورة غير مسموح');</script>";
    }
    elseif($size > 2*1024*1024){
        echo "<script>alert('حجم الصورة كبير (أقصى 2MB)');</script>";
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
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إضافة مركبة</title>

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
    margin-top:15px;
    background:#3498db;
    color:#fff;
    border:none;
    padding:12px;
    cursor:pointer;
}

.button:hover{
    background:#2980b9;
}
</style>

</head>
<body>

<div class="form_product">
<h1>إضافة مركبة</h1>

<form method="post" enctype="multipart/form-data">

<label>المزود</label>
<input type="text" name="driver">

<label>صورة المركبة</label>
<input type="file" name="imgfleet">

<label>لوحة المركبة</label>
<input type="text" name="plate">

<label>طراز المركبة</label>
<input type="text" name="typefleet">

<label>نوع المركبة</label>
<input type="text" name="classify">

<label>موديل المركبة</label>
<input type="text" name="model">

<label>لون المركبة</label>
<input type="text" name="colorfleet">

<label>مدينة العمل</label>
<select name="work">
    <option value="الرياض">الرياض</option>
    <option value="جده">جده</option>
    <option value="الدمام">الدمام</option>
</select>

<button class="button" name="fleetadd">إضافة</button>

</form>
</div>

</body>
</html>

