<?php

include('include/connected.php');

$code = $_GET['code'] ?? '';

if(empty($code)){
    die("QR Code غير موجود");
}

/* =========================
   جلب بيانات السائق
========================= */

$stmt = $con->prepare("
SELECT * FROM drivers
WHERE qr_code=?
");

$stmt->bind_param("s",$code);

$stmt->execute();

$result = $stmt->get_result();

$driver = $result->fetch_assoc();

if(!$driver){
    die("السائق غير موجود");
}

$driver_id = $driver['id'];

$today = date('Y-m-d');

$current_time = date('h:i:s A');

/* =========================
   التحقق من الحضور
========================= */

$stmt2 = $con->prepare("
SELECT * FROM attendance
WHERE driver_id=?
AND attendance_date=?
");

$stmt2->bind_param("is",$driver_id,$today);

$stmt2->execute();

$result2 = $stmt2->get_result();

/* =========================
   تسجيل حضور
========================= */

if($result2->num_rows == 0){

    $stmt3 = $con->prepare("
    INSERT INTO attendance
    (
        driver_id,
        check_in,
        attendance_date
    )
    VALUES
    (
        ?,
        NOW(),
        ?
    )
    ");

    $stmt3->bind_param("is",$driver_id,$today);

    $stmt3->execute();

    $status = "✅ تم تسجيل الحضور";

    $check_in = date('Y-m-d h:i:s A');

    $check_out = "--";

}

/* =========================
   تسجيل انصراف
========================= */

else{

    $row = $result2->fetch_assoc();

    if($row['check_out'] == NULL){

        $stmt4 = $con->prepare("
        UPDATE attendance
        SET check_out=NOW()
        WHERE id=?
        ");

        $stmt4->bind_param("i",$row['id']);

        $stmt4->execute();

        $status = "✅ تم تسجيل الانصراف";

        $check_in = $row['check_in'];

        $check_out = date('Y-m-d h:i:s A');

    }

    else{

        $status = "⚠️ تم تسجيل الحضور والانصراف مسبقاً";

        $check_in = $row['check_in'];

        $check_out = $row['check_out'];
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>الحضور والانصراف</title>

<style>

body{
    margin:0;
    padding:0;
    background:#f4f6f9;
    font-family:Arial;
}

.container{
    width:400px;
    margin:50px auto;
}

.card{
    background:#fff;
    border-radius:15px;
    padding:25px;
    box-shadow:0 0 15px rgba(0,0,0,0.1);
    text-align:center;
}

.driver-img{
    width:120px;
    height:120px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #28a745;
    margin-bottom:15px;
}

h2{
    margin:10px 0;
    color:#333;
}

.info{
    background:#f8f8f8;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    text-align:right;
}

.label{
    font-weight:bold;
    color:#555;
}

.status{
    margin-top:20px;
    padding:15px;
    background:#eafbea;
    color:green;
    border-radius:10px;
    font-size:20px;
    font-weight:bold;
}

.footer{
    margin-top:15px;
    color:#888;
    font-size:14px;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<?php if(!empty($driver['image'])){ ?>

<img
src="uploads/<?= $driver['image'] ?>"
class="driver-img">

<?php } ?>

<h2><?= htmlspecialchars($driver['name']) ?></h2>

<div class="info">
<span class="label">📞 رقم الجوال:</span>
<?= htmlspecialchars($driver['phone']) ?>
</div>

<div class="info">
<span class="label">📅 التاريخ:</span>
<?= date('Y-m-d') ?>
</div>

<div class="info">
<span class="label">🟢 وقت الحضور:</span>
<?= $check_in ?>
</div>

<div class="info">
<span class="label">🔴 وقت الانصراف:</span>
<?= $check_out ?>
</div>

<div class="status">
<?= $status ?>
</div>

<div class="footer">
نظام الحضور والانصراف
</div>

</div>

</div>

</body>
</html>