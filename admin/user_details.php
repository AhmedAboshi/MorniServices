<?php
session_start();

include('../include/core.php');
include('../include/connected.php');

/*=================================
        التحقق من رقم العميل
==================================*/

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){

    header("Location: userview.php");
    exit();

}

$id = (int)$_GET['id'];

/*=================================
        بيانات العميل
==================================*/

$stmt = $con->prepare("
SELECT *
FROM users
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if(!$user){

    $_SESSION['msg']="العميل غير موجود";

    header("Location: userview.php");

    exit();

}

/*=================================
        عدد الطلبات
==================================*/

$totalOrders = 0;

if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'orders'"))){

$q = mysqli_query($con,"
SELECT COUNT(*) total
FROM orders
WHERE user_id='$id'
");

$totalOrders = mysqli_fetch_assoc($q)['total'];

}

/*=================================
        إجمالي قيمة الطلبات
==================================*/

$totalAmount = 0;

if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'orders'"))){

$q = mysqli_query($con,"
SELECT SUM(price) total
FROM orders
WHERE user_id='$id'
");

$r = mysqli_fetch_assoc($q);

$totalAmount = $r['total'] ?? 0;

}

/*=================================
        السلة
==================================*/

$totalCart = 0;

if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'cart'"))){

$q = mysqli_query($con,"
SELECT COUNT(*) total
FROM cart
WHERE user_id='$id'
");

$totalCart = mysqli_fetch_assoc($q)['total'];

}

/*=================================
        الإشعارات
==================================*/

$totalNotifications = 0;

if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'notifications'"))){

$q = mysqli_query($con,"
SELECT COUNT(*) total
FROM notifications
WHERE user_id='$id'
");

$totalNotifications = mysqli_fetch_assoc($q)['total'];

}
?>
<!doctype html>

<html lang="<?= $lang ?>" dir="<?= $lang=="ar"?"rtl":"ltr" ?>">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>بيانات العميل</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link rel="stylesheet" href="assets/dark-mode.css">
<body>

<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="fw-bold">

<i class="bi bi-person-circle"></i>

بيانات العميل

</h2>

<p class="text-muted mb-0">

عرض جميع بيانات العميل وإحصائياته

</p>

</div>

<div>

<a href="userview.php" class="btn btn-secondary">

<i class="bi bi-arrow-right-circle"></i>

رجوع

</a>

<a href="updateuser.php?id=<?= $user['id'] ?>" class="btn btn-primary">

<i class="bi bi-pencil-square"></i>

تعديل

</a>

</div>

</div>

<!-- بطاقة العميل -->

<div class="card shadow border-0 rounded-4 mb-4">

<div class="card-body">

<div class="row align-items-center">

<div class="col-md-2 text-center">

<?php

$userImage = "../upload/users/" . $user['image'];

if(!empty($user['image']) && file_exists($userImage)){

?>

<img
src="<?= $userImage ?>"
class="rounded-circle shadow"
style="width:110px;height:110px;object-fit:cover;border:4px solid #0d6efd;">

<?php } else { ?>

<div style="width:110px;height:110px;background:#0d6efd;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;">

<i class="bi bi-person-fill text-white" style="font-size:60px;"></i>

</div>

<?php } ?>

</div>

<div class="col-md-10">

<h3 class="fw-bold">

<?= htmlspecialchars($user['username']) ?>

</h3>

<p class="text-muted mb-2">

<i class="bi bi-envelope-fill"></i>

<?= htmlspecialchars($user['email']) ?>

</p>

<p class="mb-1">

<strong>رقم العميل :</strong>

<?= $user['id'] ?>

</p>

<?php if(isset($user['phone']) && $user['phone']!=""){ ?>

<p class="mb-1">

<strong>رقم الجوال :</strong>

<?= htmlspecialchars($user['phone']) ?>

</p>

<?php } ?>

<?php if(isset($user['created_at'])){ ?>

<p class="mb-0">

<strong>تاريخ التسجيل :</strong>

<?= $user['created_at'] ?>

</p>

<?php } ?>

</div>

</div>

</div>

</div>

<!-- الإحصائيات -->

<div class="row g-4 mb-4">

<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 rounded-4">

<div class="card-body text-center">

<i class="bi bi-box-seam text-primary" style="font-size:40px"></i>

<h2 class="mt-2">

<?= $totalOrders ?>

</h2>

<div class="text-muted">

عدد الطلبات

</div>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 rounded-4">

<div class="card-body text-center">

<i class="bi bi-cash-stack text-success" style="font-size:40px"></i>

<h2 class="mt-2">

<?= number_format($totalAmount,2) ?>

</h2>

<div class="text-muted">

إجمالي الطلبات

</div>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 rounded-4">

<div class="card-body text-center">

<i class="bi bi-cart-fill text-warning" style="font-size:40px"></i>

<h2 class="mt-2">

<?= $totalCart ?>

</h2>

<div class="text-muted">

السلة

</div>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="card shadow border-0 rounded-4">

<div class="card-body text-center">

<i class="bi bi-bell-fill text-danger" style="font-size:40px"></i>

<h2 class="mt-2">

<?= $totalNotifications ?>

</h2>

<div class="text-muted">

الإشعارات

</div>

</div>

</div>

</div>

</div>
<!-- ==========================
        طلبات العميل
========================== -->

<div class="card shadow border-0 rounded-4">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-box-seam"></i>

طلبات العميل

</h4>

</div>

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th>#</th>


<th>التاريخ</th>

<th>الحالة</th>

<th>الإجمالي</th>

<th>عرض</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows(mysqli_query($con,"SHOW TABLES LIKE 'orders'"))){

$orders = mysqli_query($con,"
SELECT *
FROM orders
WHERE user_id='$id'
ORDER BY id DESC
");

if(mysqli_num_rows($orders)>0){

while($order=mysqli_fetch_assoc($orders)){

?>

<tr>



<td>#<?= $order['id'] ?></td>

<td>

<?= $order['created_at'] ?? '-' ?>

</td>

<td>

<?php

$status = $order['status'] ?? '';

switch($status){

case 'pending':

echo '<span class="badge bg-warning">قيد الانتظار</span>';

break;

case 'processing':

echo '<span class="badge bg-info">جاري التنفيذ</span>';

break;

case 'completed':

echo '<span class="badge bg-success">مكتمل</span>';

break;

case 'cancelled':

echo '<span class="badge bg-danger">ملغي</span>';

break;

default:

echo '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';

}

?>

</td>

<td>

<?= number_format($order['total_price'] ?? 0,2) ?>

ر.س

</td>

<td>

<a href="order_details.php?id=<?= $order['id'] ?>"

class="btn btn-sm btn-primary">

<i class="bi bi-eye-fill"></i>

عرض

</a>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="6" class="text-center py-4">

<div class="text-muted">

<i class="bi bi-inbox fs-1 d-block mb-2"></i>

لا توجد طلبات لهذا العميل

</div>

</td>

</tr>

<?php

}

}

?>

</tbody>

</table>

</div>

</div>