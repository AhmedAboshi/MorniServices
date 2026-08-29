<?php

session_start();

include('../include/connected.php');

date_default_timezone_set('Asia/Riyadh');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم سجل الحضور غير صحيح");
}

/*=====================================
جلب بيانات سجل الحضور مع بيانات السائق
======================================*/

$stmt = $con->prepare("
SELECT

attendance.*,

drivers.name,
drivers.id AS driver_number,
drivers.phone,
drivers.work_area,
drivers.imagedriver

FROM attendance

LEFT JOIN drivers
ON attendance.driver_id = drivers.id

WHERE attendance.id=?

LIMIT 1
");

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

if(!$row){
    die("سجل الحضور غير موجود");
}

/*=====================================
الصورة
======================================*/

$image = "../assets/images/user.png";

if(!empty($row['imagedriver'])){

    $file = "../uploads/".$row['imagedriver'];

    if(file_exists($file)){
        $image = $file;
    }

}

/*=====================================
مدة العمل
======================================*/

$workDuration = "-";

if(!empty($row['check_in']) && !empty($row['check_out'])){

    $start = new DateTime($row['check_in']);

    $end = new DateTime($row['check_out']);

    $diff = $start->diff($end);

    $workDuration =
        $diff->h." ساعة ".
        $diff->i." دقيقة";

}

/*=====================================
الحالة
======================================*/

$statusText = "";

$statusClass = $row['status'];

switch($row['status']){

    case 'present':
        $statusText="🟢 حاضر";
    break;

    case 'late':
        $statusText="🟡 متأخر";
    break;

    case 'absent':
        $statusText="🔴 غائب";
    break;

    default:
        $statusText=$row['status'];

}

/*=====================================
إحصائيات السائق
======================================*/

$driverId = (int)$row['driver_id'];

$stats = mysqli_fetch_assoc(mysqli_query($con,"
SELECT

COUNT(*) total,

SUM(status='present') present,

SUM(status='late') late,

SUM(status='absent') absent

FROM attendance

WHERE driver_id='$driverId'
"));


/*=====================================
آخر 10 سجلات
======================================*/

$history = mysqli_query($con,"

SELECT *

FROM attendance

WHERE driver_id='$driverId'

ORDER BY attendance_date DESC,id DESC

LIMIT 10

");

?>
<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>تفاصيل سجل الحضور</title>

<style>

body{

margin:0;
padding:20px;
font-family:Arial;
background:#f4f6f9;

}

.container{

width:95%;
margin:auto;

}

.page-title{

font-size:28px;
font-weight:bold;
margin-bottom:20px;
color:#0d6efd;

}

.top-actions{

display:flex;
gap:10px;
margin-bottom:20px;
flex-wrap:wrap;

}

.top-actions a{

text-decoration:none;
padding:10px 18px;
border-radius:10px;
color:white;
font-weight:bold;

}

.back-btn{

background:#6c757d;

}

.edit-btn{

background:#ffc107;
color:#000 !important;

}

.print-btn{

background:#198754;

}

.pdf-btn{

background:#dc3545;

}

.card{

background:white;
border-radius:15px;
padding:25px;
box-shadow:0 5px 20px rgba(0,0,0,.08);
margin-bottom:20px;

}

.driver-box{

display:flex;
align-items:center;
gap:20px;

}

.driver-photo{

width:110px;
height:110px;
border-radius:50%;
object-fit:cover;
border:3px solid #ddd;

}

.driver-name{

font-size:24px;
font-weight:bold;

}

.driver-id{

color:#666;
margin-top:8px;

}

.info-table{

width:100%;
border-collapse:collapse;
margin-top:15px;

}

.info-table td{

padding:12px;
border-bottom:1px solid #eee;

}

.label{

font-weight:bold;
background:#fafafa;
width:220px;

}

.badge{

padding:8px 15px;
border-radius:20px;
color:#fff;

}

.present{

background:#198754;

}

.late{

background:#ffc107;
color:#000;

}

.absent{

background:#dc3545;

}

.stats-cards{

display:flex;
gap:20px;
flex-wrap:wrap;
margin-bottom:20px;

}

.stat-card{

flex:1;
min-width:180px;
background:#fff;
border-radius:15px;
padding:20px;
text-align:center;
box-shadow:0 3px 12px rgba(0,0,0,.08);

}

.stat-card h2{

margin:0;
font-size:32px;

}

.stat-card p{

margin-top:10px;
color:#666;
font-size:15px;

}

.blue{

border-top:5px solid #0d6efd;

}

.green{

border-top:5px solid #198754;

}

.orange{

border-top:5px solid #ffc107;

}

.red{

border-top:5px solid #dc3545;

}

</style>

</head>

<body>

<div class="container">

<div class="page-title">

📅 تفاصيل سجل الحضور

</div>

<?php if(isset($_GET['updated'])){ ?>

<div style="
background:#d1e7dd;
color:#0f5132;
padding:15px;
border-radius:10px;
margin-bottom:20px;
font-weight:bold;
">

✅ تم تحديث سجل الحضور بنجاح.

</div>

<?php } ?>
<div class="top-actions">

<a href="attendance_list.php" class="back-btn">
⬅ رجوع
</a>

<a href="attendance_edit.php?id=<?= $row['id'] ?>" class="edit-btn">
✏ تعديل
</a>

<a href="#" class="print-btn">
🖨 طباعة
</a>

<a href="attendance_pdf.php#" class="pdf-btn">
📄 PDF
</a>

</div>

<div class="card">

<div class="driver-box">

<img src="<?= $image ?>" class="driver-photo">

<div>

<div class="driver-name">

<?= htmlspecialchars($row['name']) ?>

</div>

<div class="driver-id">

رقم السائق :
<?= $row['driver_number'] ?>

</div>

<div class="driver-id">

📞
<?= $row['phone'] ?>

</div>

<div class="driver-id">

📍
<?= htmlspecialchars($row['work_area']) ?>

</div>

</div>

</div>

</div>

<div class="card">

<h3>

🕒 بيانات الحضور

</h3>

<table class="info-table">

<tr>

<td class="label">
التاريخ
</td>

<td>
<?= $row['attendance_date'] ?>
</td>

</tr>

<tr>

<td class="label">
وقت الدخول
</td>

<td>
<?= $row['check_in'] ?: '-' ?>
</td>

</tr>

<tr>

<td class="label">
وقت الخروج
</td>

<td>
<?= $row['check_out'] ?: '-' ?>
</td>

</tr>

<tr>

<td class="label">
مدة العمل
</td>

<td>
<?= $workDuration ?>
</td>

</tr>

<tr>

<td class="label">
الحالة
</td>

<td>

<span class="badge <?= $statusClass ?>">

<?= $statusText ?>

</span>

</td>

</tr>

</table>

</div>
<div class="stats-cards">

<div class="stat-card blue">

<h2><?= (int)$stats['total'] ?></h2>

<p>📅 إجمالي السجلات</p>

</div>

<div class="stat-card green">

<h2><?= (int)$stats['present'] ?></h2>

<p>✅ حاضر</p>

</div>

<div class="stat-card orange">

<h2><?= (int)$stats['late'] ?></h2>

<p>🟡 متأخر</p>

</div>

<div class="stat-card red">

<h2><?= (int)$stats['absent'] ?></h2>

<p>🔴 غائب</p>

</div>

</div>
<div class="card">

<h3>

🗓 آخر 10 سجلات حضور

</h3>

<table class="info-table">

<tr style="background:#0d6efd;color:#fff;">

<td>التاريخ</td>

<td>الدخول</td>

<td>الخروج</td>

<td>الحالة</td>

</tr>

<?php

while($att=mysqli_fetch_assoc($history)){

$status='';

switch($att['status']){

case 'present':
$status='🟢 حاضر';
break;

case 'late':
$status='🟡 متأخر';
break;

case 'absent':
$status='🔴 غائب';
break;

default:
$status=$att['status'];

}

?>

<tr>

<td><?= $att['attendance_date'] ?></td>

<td><?= $att['check_in'] ?: '-' ?></td>

<td><?= $att['check_out'] ?: '-' ?></td>

<td><?= $status ?></td>

</tr>

<?php } ?>

</table>

</div>
</div>

</body>

</html>