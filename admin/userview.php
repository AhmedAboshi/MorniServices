<?php
session_start();

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');
/*=========================
    البحث
=========================*/

$search = trim($_GET['search'] ?? '');

$where = "";

if($search != ""){

    $search = mysqli_real_escape_string($con,$search);

    $where = "
    WHERE username LIKE '%$search%'
    OR email LIKE '%$search%'
    ";

}

/*=========================
    حذف مستخدم
=========================*/

if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    mysqli_query($con,"
    DELETE FROM users
    WHERE id='$id'
    ");

    $_SESSION['success']="تم حذف العميل بنجاح";

    header("Location: userview.php");

    exit();

}

/*=========================
    الإحصائيات
=========================*/

$totalUsers = mysqli_fetch_assoc(

mysqli_query($con,"
SELECT COUNT(*) total
FROM users
")

)['total'];

$totalOrders = 0;

if(mysqli_query($con,"SHOW TABLES LIKE 'orders'")->num_rows){

$totalOrders = mysqli_fetch_assoc(

mysqli_query($con,"
SELECT COUNT(*) total
FROM orders
")

)['total'];

}

$newUsers = 0;

if(mysqli_query($con,"SHOW COLUMNS FROM users LIKE 'created_at'")->num_rows){

$newUsers = mysqli_fetch_assoc(

mysqli_query($con,"
SELECT COUNT(*) total
FROM users
WHERE DATE(created_at)=CURDATE()
")

)['total'];

}

/*=========================
    المستخدمون
=========================*/

$query = mysqli_query($con,"
SELECT *
FROM users
$where
ORDER BY id DESC
");
/* ==========================
   إجمالي الإشعارات
========================== */

$totalNotifications = 0;

$check = mysqli_query($con, "SHOW TABLES LIKE 'notifications'");

if ($check && mysqli_num_rows($check) > 0) {

    $sql = mysqli_query($con, "
        SELECT COUNT(*) AS total
        FROM notifications
    ");

    if ($sql) {
        $totalNotifications = mysqli_fetch_assoc($sql)['total'];
    }
}
?>
<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=="ar"?"rtl":"ltr" ?>">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title><?= t('users') ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link rel="stylesheet" href="assets/dark-mode.css">
<style>

body{
    background:#eef2f7;
    font-family:'Cairo',sans-serif;
}

/*==========================
        Header
==========================*/

.page-header{
    background:linear-gradient(135deg,#0d6efd,#0a58ca);
    color:#fff;
    padding:30px;
    border-radius:20px;
    margin-bottom:25px;
    box-shadow:0 15px 35px rgba(0,0,0,.12);
}

.page-header h2{
    margin:0;
    font-weight:700;
}

.page-header p{
    margin:8px 0 0;
    opacity:.9;
}

/*==========================
        Cards
==========================*/

.stat-card{
    border:none;
    border-radius:20px;
    overflow:hidden;
    transition:.3s;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
}

.stat-card:hover{
    transform:translateY(-6px);
}

.stat-card .card-body{
    padding:25px;
}

.stat-icon{
    width:70px;
    height:70px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:30px;
}

.bg-blue{
    background:#0d6efd;
}

.bg-green{
    background:#198754;
}

.bg-orange{
    background:#fd7e14;
}

.stat-number{
    font-size:34px;
    font-weight:bold;
}

/*==========================
        Search
==========================*/

.search-box{
    background:#fff;
    border-radius:20px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    margin-bottom:25px;
}

/*==========================
        Table
==========================*/

.table-card{
    border:none;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
}

.table thead{
    background:#1f2937;
    color:#fff;
}

.table th,
.table td{
    vertical-align:middle;
    text-align:center;
}

.user-avatar{

    width:45px;

    height:45px;

    border-radius:50%;

    object-fit:cover;

    border:3px solid #fff;

    box-shadow:0 3px 8px rgba(0,0,0,.15);

}

.btn-group .btn{

    border-radius:8px !important;

    margin:0 2px;

}

.badge-orders{

    background:#198754;

    font-size:14px;

    padding:8px 12px;

}

</style>

</head>

<div class="container-fluid py-4">

    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">

        <div>

            <h2 class="mb-2">
                <i class="bi bi-people-fill"></i>
                إدارة العملاء
            </h2>

            <p class="mb-0">
                إدارة جميع عملاء منصة الشرق الذكية للخدمات وإدارة الأسطول
            </p>

        </div>

        <div>

            <a href="adduser.php" class="btn btn-light btn-lg shadow-sm">

                <i class="bi bi-person-plus-fill"></i>

                إضافة عميل

            </a>

        </div>

    </div>

    <!-- الإحصائيات -->

    <div class="row g-4 mb-4">

        <div class="col-lg-3 col-md-6">

            <div class="card stat-card border-0">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            إجمالي العملاء
                        </div>

                        <div class="stat-number">
                            <?= $totalUsers ?>
                        </div>

                    </div>

                    <div class="stat-icon bg-primary">

                        <i class="bi bi-people-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card stat-card border-0">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            إجمالي الطلبات
                        </div>

                        <div class="stat-number">
                            <?= $totalOrders ?>
                        </div>

                    </div>

                    <div class="stat-icon bg-success">

                        <i class="bi bi-box-seam-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card stat-card border-0">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            عملاء اليوم
                        </div>

                        <div class="stat-number">
                            <?= $newUsers ?>
                        </div>

                    </div>

                    <div class="stat-icon bg-warning">

                        <i class="bi bi-person-check-fill"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card stat-card border-0">

                <div class="card-body d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            الإشعارات
                        </div>

                        <div class="stat-number">
                            
                            <?= $totalNotifications ?>
                        </div>

                    </div>

                    <div class="stat-icon bg-danger">

                        <i class="bi bi-bell-fill"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- البحث -->

    <div class="card shadow-sm border-0 rounded-4 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-10">

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            class="form-control form-control-lg"
                            placeholder="ابحث باسم العميل أو البريد الإلكتروني">

                    </div>

                    <div class="col-md-2 d-grid">

                        <button class="btn btn-primary btn-lg">

                            <i class="bi bi-search"></i>

                            بحث

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

<?php if(isset($_SESSION['success'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

    <i class="bi bi-check-circle-fill"></i>

    <?= $_SESSION['success']; ?>

    <button class="btn-close" data-bs-dismiss="alert"></button>

</div>

<?php unset($_SESSION['success']); } ?>
<div class="card border-0 shadow rounded-4">

    <div class="card-header bg-primary text-white py-3">

        <div class="d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                <i class="bi bi-people-fill"></i>
                قائمة العملاء
            </h4>

            <span class="badge bg-light text-dark fs-6">
                <?= mysqli_num_rows($query) ?> عميل
            </span>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-dark">

            <tr>

                <th>#</th>
                
                <th>الصورة</th>

                <th>اسم المستخدم</th>

                <th>البريد الإلكتروني</th>

                <th>عدد الطلبات</th>

                <th>الإجراءات</th>

            </tr>

            </thead>

            <tbody>

<?php

while($row=mysqli_fetch_assoc($query)){

    $userId=$row['id'];

    $orders=0;

    if(mysqli_query($con,"SHOW TABLES LIKE 'orders'")->num_rows){

        $o=mysqli_fetch_assoc(mysqli_query($con,"
        SELECT COUNT(*) total
        FROM orders
        WHERE user_id='$userId'
        "));

        $orders=$o['total'];

    }

?>

<tr>

<td>

<strong><?= $row['id'] ?></strong>

</td>

<?php

$companyLogo = "../uploads/logo/" . (setting('company_logo') ?: "logo.jpg");

$userImage = $companyLogo;

if(
    !empty($row['image']) &&
    file_exists("../upload/users/".$row['image'])
){
    $userImage = "../upload/users/".$row['image'];
}

?>

<td>

<img
src="<?= $userImage ?>"
class="user-avatar"
alt="User Image">

</td>

<td>

<strong>

<?= htmlspecialchars($row['username']) ?>

</strong>

</td>

<td>

<?= htmlspecialchars($row['email']) ?>

</td>

<td>

<span class="badge bg-success fs-6">

<?= $orders ?>

</span>

</td>

<td>

<div class="btn-group">

<a
href="user_details.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm">

<i class="bi bi-eye-fill"></i>

</a>

<a
href="updateuser.php?id=<?= $row['id'] ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil-fill"></i>

</a>

<a
href="?delete=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف العميل؟')">

<i class="bi bi-trash-fill"></i>

</a>

</div>

</td>

</tr>

<?php } ?>

            </tbody>

        </table>

    </div>

</div>

</div>