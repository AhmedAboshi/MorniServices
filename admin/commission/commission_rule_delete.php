<?php

include('../../include/connected.php');

session_start();

$lang = $_GET['lang'] ?? 'ar';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم السياسة غير صحيح");
}

/* التأكد من وجود السياسة */

$stmt = $con->prepare("
SELECT id
FROM commission_rules
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){
    die("السياسة غير موجودة");
}

/* حذف منطقي */

$update = $con->prepare("
UPDATE commission_rules
SET status='deleted'
WHERE id=?
");

$update->bind_param("i",$id);

if($update->execute()){

    header("Location: commission_rules.php?deleted=1");
    exit;

}else{

    die("حدث خطأ أثناء حذف السياسة.");

}