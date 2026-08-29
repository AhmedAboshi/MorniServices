<?php

session_start();

include('../include/connected.php');

date_default_timezone_set('Asia/Riyadh');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم سجل الحضور غير صحيح");
}

/*====================================
جلب سجل الحضور
====================================*/

$stmt = $con->prepare("
SELECT

attendance.*,

drivers.name,
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

/*====================================
صورة السائق
====================================*/

$image="../assets/images/user.png";

if(!empty($row['imagedriver'])){

    $file="../uploads/".$row['imagedriver'];

    if(file_exists($file)){
        $image=$file;
    }

}
/*====================================
حفظ التعديلات
====================================*/

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $attendance_date = $_POST['attendance_date'] ?? '';
    $check_in        = $_POST['check_in'] ?? '';
    $check_out       = $_POST['check_out'] ?? '';
    $status          = $_POST['status'] ?? '';

    // تحويل القيم الفارغة إلى NULL
    $check_in  = $check_in  ?: null;
    $check_out = $check_out ?: null;

    // التحقق من صحة وقت الخروج
    if($check_in && $check_out){

        if(strtotime($check_out) < strtotime($check_in)){

            $message = "وقت الخروج لا يمكن أن يكون قبل وقت الدخول.";

        }

    }

    if($message == ''){

        $stmt = $con->prepare("
        UPDATE attendance

        SET

        attendance_date=?,
        check_in=?,
        check_out=?,
        status=?

        WHERE id=?
        ");

        $stmt->bind_param(
            "ssssi",
            $attendance_date,
            $check_in,
            $check_out,
            $status,
            $id
        );

        if($stmt->execute()){

            header("Location: attendance_view.php?id=".$id."&updated=1");
            exit;

        }else{

            $message = "حدث خطأ أثناء حفظ البيانات.";

        }

    }

}
?>
<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>تعديل سجل الحضور</title>

<style>

body{

margin:0;
padding:20px;
font-family:Arial;
background:#f4f6f9;

}

.container{

width:90%;
max-width:900px;
margin:auto;

}

.card{

background:#fff;
padding:25px;
border-radius:15px;
box-shadow:0 5px 20px rgba(0,0,0,.08);

}

.driver-box{

display:flex;
align-items:center;
gap:20px;
margin-bottom:25px;

}

.driver-photo{

width:90px;
height:90px;
border-radius:50%;
object-fit:cover;
border:2px solid #ddd;

}

.driver-name{

font-size:22px;
font-weight:bold;

}

.form-group{

margin-bottom:18px;

}

label{

display:block;
margin-bottom:8px;
font-weight:bold;

}

input,
select{

width:100%;
padding:11px;
border:1px solid #ddd;
border-radius:10px;
font-size:15px;
box-sizing:border-box;

}

.buttons{

display:flex;
gap:10px;
margin-top:25px;

}

button{

background:#198754;
color:#fff;
border:none;
padding:12px 25px;
border-radius:10px;
cursor:pointer;

}

a{

background:#6c757d;
color:#fff;
padding:12px 25px;
border-radius:10px;
text-decoration:none;

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>✏️ تعديل سجل الحضور</h2>

<div class="driver-box">

<img src="<?= $image ?>" class="driver-photo">

<div>

<div class="driver-name">

<?= htmlspecialchars($row['name']) ?>

</div>

<div>

سجل رقم #

<?= $row['id'] ?>

</div>

</div>

</div>

<form method="post">

<div class="form-group">

<label>تاريخ الحضور</label>

<input
type="date"
name="attendance_date"
value="<?= $row['attendance_date'] ?>"
required>

</div>

<div class="form-group">

<label>وقت الدخول</label>

<input
type="datetime-local"
name="check_in"
value="<?= !empty($row['check_in']) ? date('Y-m-d\TH:i',strtotime($row['check_in'])) : '' ?>">

</div>

<div class="form-group">

<label>وقت الخروج</label>

<input
type="datetime-local"
name="check_out"
value="<?= !empty($row['check_out']) ? date('Y-m-d\TH:i',strtotime($row['check_out'])) : '' ?>">

</div>

<div class="form-group">

<label>الحالة</label>

<select name="status">

<option value="present" <?= $row['status']=='present'?'selected':'' ?>>
حاضر
</option>

<option value="late" <?= $row['status']=='late'?'selected':'' ?>>
متأخر
</option>

<option value="absent" <?= $row['status']=='absent'?'selected':'' ?>>
غائب
</option>

</select>

</div>

<div class="buttons">

<button type="submit">

💾 حفظ التعديلات

</button>

<a href="attendance_view.php?id=<?= $row['id'] ?>">

إلغاء

</a>

</div>

</form>

</div>

</div>

</body>

</html>