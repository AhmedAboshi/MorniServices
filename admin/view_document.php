<?php
include('../include/connected.php');
if (!isset($_GET['id'])) {
    die("رقم المستند غير موجود");
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM vehicle_documents WHERE id='$id'";
$result = mysqli_query($con, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("المستند غير موجود");
}

$document = mysqli_fetch_assoc($result);

$file = "../" . ltrim($document['file_path'], '/');

$file = "../" . $document['file_path'];

$today = strtotime(date('Y-m-d'));
$expiry = strtotime($document['expiry_date']);

$status = "غير محدد";
$badge = "secondary";

if (!empty($document['expiry_date'])) {

    $days = floor(($expiry - $today) / 86400);

    if ($days < 0) {
        $status = "منتهي";
        $badge = "danger";
    } elseif ($days <= 30) {
        $status = "ينتهي خلال {$days} يوم";
        $badge = "warning";
    } else {
        $status = "ساري";
        $badge = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="utf-8">

<title>عرض المستند</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>

<body class="bg-light">

<div class="container py-4">

<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-folder2-open"></i>

<?= $document['document_type']; ?>

</h4>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-6">

<table class="table table-bordered">

<tr>

<th width="35%">رقم المستند</th>

<td><?= $document['document_number']; ?></td>

</tr>

<tr>

<th>تاريخ الإصدار</th>

<td><?= $document['issue_date']; ?></td>

</tr>

<tr>

<th>تاريخ الانتهاء</th>

<td><?= $document['expiry_date']; ?></td>

</tr>

<tr>

<th>الحالة</th>

<td>

<span class="badge bg-<?= $badge ?>">

<?= $status ?>

</span>

</td>

</tr>

<tr>

<th>ملاحظات</th>

<td>

<?= $document['notes'] ?: 'لا توجد'; ?>

</td>

</tr>

</table>

</div>
<div class="col-md-6 text-center">

<?php

$extension = strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION));

if(in_array($extension,['jpg','jpeg','png','gif','webp'])){

?>

<img src="<?= $file; ?>"
     class="img-fluid rounded shadow"
     style="max-width:100%; max-height:600px;">

<?php

}elseif($extension=='pdf'){

?>

<iframe src="<?= $file; ?>">
        width="100%"
        height="600"
        style="border:1px solid #ddd;border-radius:8px;">
</iframe>

<?php

}else{

?>

<div class="alert alert-warning">

<i class="bi bi-exclamation-triangle"></i>

لا يمكن معاينة هذا النوع من الملفات.

</div>

<?php } ?>

</div>

</div>
</div>

<div class="card-footer text-center">

<a href="<?= $file; ?>"
   target="_blank"
   class="btn btn-primary">

    <i class="bi bi-arrows-fullscreen"></i>

    فتح بالحجم الكامل

</a>

<a href="<?= $file; ?>"
   download="<?= $document['file_name']; ?>"
   class="btn btn-success">

    <i class="bi bi-download"></i>

    تحميل

</a>

<button onclick="window.print()"
        class="btn btn-secondary">

<i class="bi bi-printer"></i>

طباعة

</button>

<a href="javascript:history.back()"
   class="btn btn-danger">

<i class="bi bi-arrow-right"></i>

رجوع

</a>

</div>

</div>

</div>

</body>

</html>