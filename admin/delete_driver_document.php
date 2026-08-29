<?php

session_start();

include('../include/connected.php');


$id = intval($_GET['id'] ?? 0);


if($id <= 0){
    die("رقم المستند غير صحيح");
}


/*==================================
جلب بيانات المستند
==================================*/

$sql = "
SELECT *
FROM driver_documents
WHERE id=?
LIMIT 1
";


$stmt = $con->prepare($sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();


$doc = $result->fetch_assoc();


if(!$doc){

    die("المستند غير موجود");

}


/*==================================
مسار الملف
==================================*/


$file = "../uploads/drivers/"
        .$doc['driver_id']."/"
        .$doc['file_name'];
/*==================================
حذف الملف من السيرفر
==================================*/

if(file_exists($file)){

    unlink($file);

}


/*==================================
حذف السجل من قاعدة البيانات
==================================*/

$delete = $con->prepare("
DELETE FROM driver_documents
WHERE id=?
");


$delete->bind_param("i",$id);


if($delete->execute()){


    header("Location: driver_profile.php?id=".$doc['driver_id']."&msg=document_deleted");

    exit;


}else{


    die("حدث خطأ أثناء حذف المستند");


}