<?php
include('../include/connected.php');

if (!isset($_GET['id'])) {
    die("رقم المستند غير موجود");
}

$id = (int)$_GET['id'];

$result = mysqli_query($con, "
SELECT *
FROM vehicle_documents
WHERE id='$id'
");

if (mysqli_num_rows($result) == 0) {
    die("المستند غير موجود");
}

$document = mysqli_fetch_assoc($result);

// حذف الملف من السيرفر
if (!empty($document['file_path'])) {

    $file = "../" . $document['file_path'];

    if (file_exists($file)) {
        unlink($file);
    }
}

// حذف السجل من قاعدة البيانات
mysqli_query($con, "
DELETE FROM vehicle_documents
WHERE id='$id'
");

// الرجوع إلى صفحة تفاصيل المركبة
header("Location: fleet_details.php?id=" . $document['car_id']);
exit;
?>