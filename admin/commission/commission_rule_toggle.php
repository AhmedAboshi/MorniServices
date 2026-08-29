<?php

include('../../include/connected.php');

session_start();


/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';



/* =========================
   رقم السياسة
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if($id <= 0){

    die("رقم السياسة غير صحيح");

}



/* =========================
   جلب الحالة الحالية
========================= */

$stmt = $con->prepare("
SELECT status
FROM commission_rules
WHERE id=?
LIMIT 1
");


$stmt->bind_param("i",$id);

$stmt->execute();


$result = $stmt->get_result();


if($result->num_rows == 0){

    die("السياسة غير موجودة");

}


$rule = $result->fetch_assoc();



/* =========================
   تغيير الحالة
========================= */


$new_status = ($rule['status']=="active")
? "inactive"
: "active";



$update = $con->prepare("
UPDATE commission_rules
SET status=?
WHERE id=?
");


$update->bind_param(
"si",
$new_status,
$id
);



if($update->execute()){


header("Location: commission_rules.php?status_updated=1");

exit;


}else{


die("حدث خطأ أثناء تغيير الحالة");


}


?>