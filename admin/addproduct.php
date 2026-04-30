
<?php
 session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include('../include/core.php');
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
        move_uploaded_file($tmp, "../uploads/img/".$newName);

        $query = "INSERT INTO product 
        (proname, proimg, proprice, prosection, prodescrip, prounv)
        VALUES 
        ('$proname','$newName','$proprice','$prosection','$prodescrip','$prounv')";

        if(mysqli_query($con,$query)){
           echo "<script>alert('".t('success')."');</script>";
        }else{
            echo "<script>alert('".t('error')."');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= t('add_service') ?></title>

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
    width: 100%;
    margin-top: 20px;
    padding: 14px;
    background: linear-gradient(135deg, #4CAF50, #2ecc71);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.button:hover{
    background: linear-gradient(135deg, #43a047, #27ae60);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

.button:active{
    transform: scale(0.97);
}
</style>

</head>
<body>

<a href="?lang=ar">🇸🇦 عربي</a>
<a href="?lang=en">🇬🇧 English</a>

<div class="form_product">
<h1><?= t('add_service') ?></h1>

<form method="post" enctype="multipart/form-data">

<label><?= t('service_title') ?></label>
<input type="text" name="proname">

<label><?= t('service_image') ?></label>
<input type="file" name="proimg">

<label><?= t('price') ?></label>
<input type="text" name="proprice">

<label><?= t('details') ?></label>
<input type="text" name="prodescrip">

<label><?= t('availability') ?></label>
<input type="text" name="prounv">

<label><?= t('section') ?></label>
<select name="prosection">
<?php
$res = mysqli_query($con,"SELECT * FROM section");
while($row = mysqli_fetch_assoc($res)){
    echo "<option value='".$row['sectionname']."'>".$row['sectionname']."</option>";
}
?>
</select>

<button class="button" name="proadd"><?= t('add') ?></button>

</form>
</div>

</body>
</html>

