<?php
session_start();
include('../include/connected.php');

/*=========================
  التحقق من رقم السجل
=========================*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "رقم السجل غير صحيح";
    header("Location: oile.php");
    exit;
}

$id = (int)$_GET['id'];

/*=========================
  التحقق من وجود السجل
=========================*/
$check = $con->prepare("
SELECT id
FROM oil_changes
WHERE id=?
LIMIT 1
");

$check->bind_param("i", $id);
$check->execute();

$result = $check->get_result();

if($result->num_rows == 0){

    $_SESSION['error'] = "السجل غير موجود";

    header("Location: oile.php");

    exit;

}

/*=========================
  حذف السجل
=========================*/

$delete = $con->prepare("
DELETE FROM oil_changes
WHERE id=?
");

$delete->bind_param("i", $id);

if($delete->execute()){

    $_SESSION['success'] = "✅ تم حذف سجل تغيير الزيت بنجاح";

}else{

    $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف";

}

header("Location: oile.php");
exit;
?>