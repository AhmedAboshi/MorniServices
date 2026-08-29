<?php
session_start();

include('../include/connected.php');
include('../include/settings.php');

/*=========================
    حماية الصفحة
=========================*/
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

/*=========================
    التحقق من ID
=========================*/
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: adminview.php?error=invalid");
    exit();
}

$id = intval($_GET['id']);

/*=========================
    منع حذف نفسك
=========================*/
if($id == $_SESSION['admin_id']){
    header("Location: adminview.php?error=self_delete");
    exit();
}

/*=========================
    جلب صورة المدير
=========================*/
$stmt = $con->prepare("
SELECT image
FROM admin
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){

    header("Location: adminview.php?error=notfound");
    exit();

}

$row = $result->fetch_assoc();

$image = $row['image'];

/*=========================
    حذف الصورة
=========================*/

$companyLogo = setting('company_logo');

if(
    !empty($image) &&
    $image != $companyLogo
){

    $file = "uploads/admin/".$image;

    if(file_exists($file)){
        unlink($file);
    }

}

/*=========================
    حذف المدير
=========================*/

$delete = $con->prepare("
DELETE FROM admin
WHERE id=?
");

$delete->bind_param("i",$id);

if($delete->execute()){

    header("Location: adminview.php?deleted=1");
    exit();

}else{

    header("Location: adminview.php?error=delete");
    exit();

}
?>