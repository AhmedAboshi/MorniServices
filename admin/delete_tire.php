<?php
session_start();
include('../include/connected.php');

/*=========================
  التحقق من رقم السجل
=========================*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "رقم السجل غير صحيح";
    header("Location: tire.php");
    exit;
}

$id = (int)$_GET['id'];

/*=========================
  التحقق من وجود السجل
=========================*/
$check = $con->prepare("SELECT id FROM tires WHERE id=? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "السجل غير موجود";
    header("Location: tire.php");
    exit;
}

/*=========================
  حذف السجل
=========================*/
$delete = $con->prepare("DELETE FROM tires WHERE id=?");
$delete->bind_param("i", $id);

if ($delete->execute()) {

    $_SESSION['success'] = "✅ تم حذف سجل الإطار بنجاح";

} else {

    $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف";

}

header("Location: tire.php");
exit;
?>