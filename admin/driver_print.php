<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if($id <= 0){

    die("رقم السائق غير صحيح");

}



/* ==========================
   بيانات السائق
========================== */


$stmt = $con->prepare("
SELECT *
FROM drivers
WHERE id=?
LIMIT 1
");


$stmt->bind_param("i",$id);

$stmt->execute();


$driver = $stmt->get_result()->fetch_assoc();



if(!$driver){

    die("السائق غير موجود");

}



/* ==========================
   إعدادات الشركة
========================== */


$settings=[];


$result=mysqli_query($con,"
SELECT setting_key,setting_value
FROM settings
");


while($row=mysqli_fetch_assoc($result)){

    $settings[$row['setting_key']]
    =
    $row['setting_value'];

}



$companyName = $settings['company_name'] ?? '';

$phone = $settings['company_phone'] ?? '';

$email = $settings['company_email'] ?? '';

$address = $settings['company_address'] ?? '';



$logo="../uploads/logo/".
($settings['company_logo'] ?? '');



/* ==========================
   مستندات السائق
========================== */


$documents=mysqli_query($con,"
SELECT *
FROM driver_documents
WHERE driver_id='$id'
ORDER BY expiry_date ASC
");



/* ==========================
   مركبات السائق
========================== */


$fleet=mysqli_query($con,"
SELECT *
FROM fleet
WHERE driver='".mysqli_real_escape_string($con,$driver['name'])."'
ORDER BY id DESC
");

?>
<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>
طباعة ملف السائق
</title>


<style>

body{

font-family:Arial;
background:#fff;
margin:20px;

}


.header{

display:flex;
justify-content:space-between;
align-items:center;

border-bottom:2px solid #0d6efd;

padding-bottom:10px;

}


.logo{

width:80px;
height:80px;
object-fit:contain;

}



.company{

text-align:center;

flex:1;

}


.company h2{

margin:0;

}



.report-title{

background:#0d6efd;

color:white;

text-align:center;

padding:12px;

margin:20px 0;

font-size:20px;

border-radius:8px;

}



.driver-box{

display:flex;

gap:25px;

align-items:center;

border:1px solid #ddd;

padding:20px;

border-radius:12px;

}



.driver-image img{

width:130px;

height:130px;

border-radius:50%;

object-fit:cover;

border:4px solid #eee;

}



.no-image{

width:130px;
height:130px;

border-radius:50%;

background:#eee;

display:flex;

align-items:center;

justify-content:center;

font-size:50px;

}



.info table{

width:100%;

border-collapse:collapse;

}



.info td{

border:1px solid #ddd;

padding:8px;

}



.label{

background:#f5f5f5;

font-weight:bold;

}



.qr{

text-align:center;

}



@media print{

.no-print{

display:none;

}

}


</style>


</head>


<body>



<div class="header">


<div>

<?php if(file_exists($logo)){ ?>

<img class="logo" src="<?= $logo ?>">

<?php } ?>

</div>



<div class="company">

<h2>
<?= $companyName ?>
</h2>


<p>

<?= $address ?>

<br>

هاتف:
<?= $phone ?>

<br>

<?= $email ?>

</p>


</div>



</div>



<div class="report-title">

👤 ملف السائق الإلكتروني

</div>



<div class="driver-box">



<div class="driver-image">


<?php

$image="../uploads/".$driver['imagedriver'];


if(!empty($driver['imagedriver']) && file_exists($image)){

?>


<img src="<?= $image ?>">


<?php }else{ ?>


<div class="no-image">
👤
</div>


<?php } ?>


</div>





<div class="info">


<table>


<tr>

<td class="label">
اسم السائق
</td>

<td>
<?= htmlspecialchars($driver['name']) ?>
</td>

</tr>



<tr>

<td class="label">
رقم الهوية
</td>

<td>
<?= htmlspecialchars($driver['national_id']) ?>
</td>

</tr>



<tr>

<td class="label">
الجوال
</td>

<td>
<?= htmlspecialchars($driver['phone']) ?>
</td>

</tr>



<tr>

<td class="label">
منطقة العمل
</td>

<td>
<?= htmlspecialchars($driver['work_area']) ?>
</td>

</tr>



<tr>

<td class="label">
نوع المركبة
</td>

<td>
<?= htmlspecialchars($driver['truck_type']) ?>
</td>

</tr>


</table>


</div>




<div class="qr">


<?php if(!empty($driver['qr_code'])){ ?>


<img 
src="../generate_qr.php?text=<?= urlencode($driver['qr_code']) ?>"
width="100"
>


<br>

QR


<?php } ?>


</div>



</div>
<br>


<h3 class="report-title">
📄 مستندات السائق
</h3>



<table style="width:100%;border-collapse:collapse;">


<tr style="background:#f2f2f2;font-weight:bold;">

<td style="border:1px solid #ddd;padding:8px;">
المستند
</td>

<td style="border:1px solid #ddd;padding:8px;">
الرقم
</td>

<td style="border:1px solid #ddd;padding:8px;">
الانتهاء
</td>

<td style="border:1px solid #ddd;padding:8px;">
الحالة
</td>

</tr>



<?php while($doc=mysqli_fetch_assoc($documents)){ 


$status="ساري";


if(!empty($doc['expiry_date'])){


$days=floor(
(strtotime($doc['expiry_date'])-time())/86400
);



if($days < 0){

$status="منتهي";

}
elseif($days <=30){

$status="قريب الانتهاء";

}


}


?>


<tr>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($doc['document_type']) ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($doc['document_number']) ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= $doc['expiry_date'] ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= $status ?>
</td>


</tr>


<?php } ?>


</table>





<br>



<h3 class="report-title">
🚚 المركبات المرتبطة بالسائق
</h3>



<table style="width:100%;border-collapse:collapse;">


<tr style="background:#f2f2f2;font-weight:bold;">


<td style="border:1px solid #ddd;padding:8px;">
اللوحة
</td>


<td style="border:1px solid #ddd;padding:8px;">
النوع
</td>


<td style="border:1px solid #ddd;padding:8px;">
الموديل
</td>


<td style="border:1px solid #ddd;padding:8px;">
اللون
</td>


</tr>



<?php while($car=mysqli_fetch_assoc($fleet)){ ?>


<tr>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($car['plate']) ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($car['typefleet']) ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($car['model']) ?>
</td>


<td style="border:1px solid #ddd;padding:8px;">
<?= htmlspecialchars($car['colorfleet']) ?>
</td>


</tr>


<?php } ?>


</table>




<br><br>



<div class="no-print" style="text-align:center;">


<button onclick="window.print()"
style="
padding:12px 30px;
background:#198754;
color:#fff;
border:none;
border-radius:8px;
font-size:16px;
cursor:pointer;
">

🖨 طباعة

</button>


</div>



<script>

window.onload=function(){

    window.print();

}

</script>



</body>

</html>