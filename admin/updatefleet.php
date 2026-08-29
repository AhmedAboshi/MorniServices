<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

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
   📌 جلب البيانات
========================= */
$id = intval($_GET['id'] ?? 0);

$query = mysqli_query($con,"
SELECT * FROM fleet
WHERE id='$id'
LIMIT 1
");

$row = mysqli_fetch_assoc($query);

if(!$row){
    die("المركبة غير موجودة");
}

/* =========================
   ✏️ تعديل
========================= */
if(isset($_POST['update_pro'])){
    
if(!isset($_POST['work']) || empty($_POST['work'])){
    die("Work is empty");
}
    $driver      = mysqli_real_escape_string($con,$_POST['driver']);
    $plate       = mysqli_real_escape_string($con,$_POST['plate']);
    $typefleet   = mysqli_real_escape_string($con,$_POST['typefleet']);
    $classify    = mysqli_real_escape_string($con,$_POST['classify']);
    $model       = mysqli_real_escape_string($con,$_POST['model']);
    $colorfleet  = mysqli_real_escape_string($con,$_POST['colorfleet']);
    $work        = mysqli_real_escape_string($con,$_POST['work']);

    $operation_expiry    = $_POST['operation_expiry'];
    $insurance_expiration_date = $_POST['insurance_expiration_date'];
    $inspection_expiry   = $_POST['inspection_expiry'];

    /* 📸 الصورة */
    $imgfleet = $row['imgfleet'];

    if(!empty($_FILES['imgfleet']['name'])){

        $imgname = $_FILES['imgfleet']['name'];
        $tmp     = $_FILES['imgfleet']['tmp_name'];

        $imgfleet = time().'_'.$imgname;

        move_uploaded_file(
            $tmp,
            "../fleetimg/img/".$imgfleet
        );
    }

    $update = mysqli_query($con,"
    UPDATE fleet SET

    driver='$driver',
    imgfleet='$imgfleet',
    plate='$plate',
    typefleet='$typefleet',
    classify='$classify',
    model='$model',
    colorfleet='$colorfleet',
    work='$work',

    operation_expiry='$operation_expiry',
    insurance_expiration_date='$insurance_expiration_date',
    inspection_expiry='$inspection_expiry'

    WHERE id='$id'
    ");

    if($update){

       header("Location: fleet_details.php?id=".$id."&lang=".$lang."&updated=1");
exit();
       

    }else{

        $error = mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>"
dir="<?= $lang=='ar' ? 'rtl' : 'ltr' ?>">

<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>تعديل المركبة</title>

<style>

body{
    font-family:'Cairo',sans-serif;
    background:#f4f6f9;
    margin:0;
}

.form-box{

    width:50%;
    margin:30px auto;
    background:#fff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h1{
    text-align:center;
    margin-bottom:25px;
}

label{
    display:block;
    margin-top:15px;
    font-weight:bold;
}

input,
select{

    width:100%;
    padding:12px;
    margin-top:6px;
    border:1px solid #ccc;
    border-radius:10px;
    box-sizing:border-box;
}

img{
    width:120px;
    height:120px;
    object-fit:cover;
    border-radius:10px;
    margin-top:10px;
}

.button{

    width:100%;
    padding:14px;
    margin-top:25px;
    background:#27ae60;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
}

.button:hover{
    background:#219150;
}

.error{

    background:#ffe6e6;
    color:red;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

.lang-switch{

    text-align:center;
    margin-top:20px;
}

.lang-switch a{

    text-decoration:none;
    padding:8px 15px;
    background:#eee;
    border-radius:8px;
    color:#333;
    font-weight:bold;
}

.lang-switch a.active{

    background:#3498db;
    color:#fff;
}

</style>

</head>

<body>

<div class="lang-switch">

<a href="?id=<?= $id ?>&lang=ar"
class="<?= $lang=='ar'?'active':'' ?>">

🇸🇦 عربي

</a>

<a href="?id=<?= $id ?>&lang=en"
class="<?= $lang=='en'?'active':'' ?>">

🇬🇧 English

</a>

</div>

<div class="form-box">

<h1>🚚 تعديل بيانات المركبة</h1>

<?php if(isset($error)){ ?>

<div class="error">
<?= $error ?>
</div>

<?php } ?>

<form method="POST"
enctype="multipart/form-data">

<label>المزود</label>
<input
type="text"
name="driver"
value="<?= $row['driver'] ?>"
required>

<label>صورة المركبة</label>

<img src="../fleetimg/img/<?= $row['imgfleet'] ?>">

<input type="file" name="imgfleet">

<label>لوحة المركبة</label>
<input
type="text"
name="plate"
value="<?= $row['plate'] ?>"
required>

<label>طراز المركبة</label>
<input
type="text"
name="typefleet"
value="<?= $row['typefleet'] ?>"
required>

<label>نوع المركبة</label>
<input
type="text"
name="classify"
value="<?= $row['classify'] ?>"
required>

<label>موديل المركبة</label>
<input
type="text"
name="model"
value="<?= $row['model'] ?>"
required>

<label>لون المركبة</label>
<input
type="text"
name="colorfleet"
value="<?= $row['colorfleet'] ?>">

<label>منطقة العمل</label>

<select name="work" id="work">

<option value="الرياض">الرياض</option>
<option value="جدة">جدة</option>
<option value="الدمام">الدمام</option>

</select>

<script>
document.getElementById('work').value = "<?= $row['work'] ?>";
</script>

<hr>

<h3>📅 تواريخ الانتهاء</h3>

<label>انتهاء كرت التشغيل</label>
<input
type="date"
name="operation_expiry"
value="<?= $row['operation_expiry'] ?>">

<label>انتهاء التامين</label>
<input
type="date"
name="insurance_expiration_date"
value="<?= $row['insurance_expiration_date'] ?>">

<label>انتهاء الفحص الدوري</label>
<input
type="date"
name="inspection_expiry"
value="<?= $row['inspection_expiry'] ?>">

<button
class="button"
type="submit"
name="update_pro">

💾 حفظ التعديلات

</button>

</form>

</div>

</body>
</html>