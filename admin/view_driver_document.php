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
SELECT
dd.*,
d.name AS driver_name
FROM driver_documents dd
INNER JOIN drivers d
ON d.id=dd.driver_id
WHERE dd.id=?
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
الملف
==================================*/

$file = "../uploads/drivers/".$doc['driver_id']."/".$doc['file_name'];

$file_url = "http://localhost/AlSharqPlatform/uploads/drivers/".$doc['driver_id']."/".$doc['file_name'];

$file_exists = file_exists($file);

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>

معاينة المستند

</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{

background:#f5f6fa;

}

.card{

max-width:1100px;

margin:30px auto;

border-radius:15px;

}

.preview{

max-width:100%;

border-radius:10px;

border:1px solid #ddd;

}

iframe{

width:100%;

height:750px;

border:none;

}

.table td{

vertical-align:middle;

}

</style>

</head>

<body>

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

👁 معاينة المستند

</h4>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-4">

<table class="table table-bordered">

<tr>

<th>السائق</th>

<td><?= htmlspecialchars($doc['driver_name']) ?></td>

</tr>

<tr>

<th>نوع المستند</th>

<td><?= htmlspecialchars($doc['document_type']) ?></td>

</tr>

<tr>

<th>رقم المستند</th>

<td><?= htmlspecialchars($doc['document_number']) ?></td>

</tr>

<tr>

<th>الإصدار</th>

<td><?= $doc['issue_date'] ?: "-" ?></td>

</tr>

<tr>

<th>الانتهاء</th>

<td><?= $doc['expiry_date'] ?: "-" ?></td>

</tr>

<tr>

<th>الملاحظات</th>

<td><?= nl2br(htmlspecialchars($doc['notes'])) ?></td>

</tr>

</table>

</div>

<div class="col-md-8">
    <?php
/* =====================================
   عرض المستند حسب نوع الملف
===================================== */

$file_exists = file_exists($file);

?>

<div class="card shadow-sm mt-4">

    <div class="card-header bg-primary text-white">
        <i class="fa-solid fa-file"></i>
        معاينة المستند
    </div>


    <div class="card-body text-center">


        <?php if(!$file_exists): ?>

            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                الملف غير موجود أو تم حذفه من الخادم
            </div>


        <?php else: ?>


            <?php

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            ?>


            <?php if(in_array($extension,['jpg','jpeg','png'])): ?>


                <!-- عرض الصور -->

                <img src="<?= htmlspecialchars($file_url) ?>"
     class="img-fluid rounded shadow"
     style="max-height:600px;">



            <?php elseif($extension == 'pdf'): ?>


                <!-- عرض PDF -->

                <iframe src="<?= htmlspecialchars($file_url) ?>"
                        width="100%"
                        height="650px"
                        style="border:1px solid #ddd;border-radius:10px;">
                </iframe>



            <?php else: ?>


                <div class="alert alert-warning">

                    <i class="fa-solid fa-file-circle-question"></i>

                    لا يمكن معاينة هذا النوع من الملفات

                    <br>

                    نوع الملف:
                    <?= htmlspecialchars($extension) ?>

                </div>


            <?php endif; ?>


        <?php endif; ?>


    </div>



    <!-- الأزرار -->

    <div class="card-footer text-center">


        <?php if($file_exists): ?>

           <a href="<?= htmlspecialchars($file_url) ?>"
               download
               class="btn btn-success">

                <i class="fa-solid fa-download"></i>
                تحميل الملف

            </a>


        <?php endif; ?>


        <a href="edit_driver_document.php?id=<?= $doc['id'] ?>"
           class="btn btn-warning">

            <i class="fa-solid fa-rotate"></i>
            استبدال الملف

        </a>

<a href="delete_driver_document.php?id=<?= $doc['id'] ?>"
   class="btn btn-danger"
   onclick="return confirm('هل أنت متأكد من حذف هذا المستند؟ لا يمكن التراجع عن العملية');">

    <i class="fa-solid fa-trash"></i>
    حذف المستند

</a>

        <a href="driver_profile.php?id=<?= $doc['driver_id'] ?>"
           class="btn btn-secondary">

            <i class="fa-solid fa-arrow-right"></i>
            رجوع

        </a>


    </div>


</div>