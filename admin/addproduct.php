
<?php
session_start();
include('../include/connected.php');

if(isset($_POST['proadd'])){

    $proname    = mysqli_real_escape_string($con, $_POST['proname']);
    $proprice   = mysqli_real_escape_string($con, $_POST['proprice']);
    $prosection = mysqli_real_escape_string($con, $_POST['prosection']);
    $prodescrip = mysqli_real_escape_string($con, $_POST['prodescrip']);
    $prounv     = mysqli_real_escape_string($con, $_POST['prounv']);

    // 📸 الصورة
    $imgname = $_FILES['proimg']['name'];
    $tmp     = $_FILES['proimg']['tmp_name'];
    $size    = $_FILES['proimg']['size'];

    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($imgname, PATHINFO_EXTENSION));

    if(empty($proname) || empty($proprice) || empty($prosection) || empty($prodescrip)){
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
        move_uploaded_file($tmp, "../uploads/img/".$newName);

        $query = "INSERT INTO product 
        (proname, proimg, proprice, prosection, prodescrip, prounv)
        VALUES 
        ('$proname','$newName','$proprice','$prosection','$prodescrip','$prounv')";

        if(mysqli_query($con,$query)){
            echo "<script>alert('تم إضافة الخدمة بنجاح');</script>";
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
<title>إضافة خدمة</title>

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
<h1>إضافة خدمة</h1>

<form method="post" enctype="multipart/form-data">

<label>عنوان الخدمة</label>
<input type="text" name="proname">

<label>صورة الخدمة</label>
<input type="file" name="proimg">

<label>السعر</label>
<input type="text" name="proprice">

<label>التفاصيل</label>
<input type="text" name="prodescrip">

<label>التوفر</label>
<input type="text" name="prounv">

<label>القسم</label>
<select name="prosection">
<?php
$res = mysqli_query($con,"SELECT * FROM section");
while($row = mysqli_fetch_assoc($res)){
    echo "<option value='".$row['sectionname']."'>".$row['sectionname']."</option>";
}
?>
</select>

<button class="button" name="proadd">إضافة</button>

</form>
</div>

</body>
</html>

