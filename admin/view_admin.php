<?php
session_start();
include('../include/connected.php');
include('../include/settings.php');
include('../include/image_helper.php');

/*==================================
  حماية الصفحة
==================================*/
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

/*==================================
  التحقق من ID
==================================*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Admin ID");
}

$id = intval($_GET['id']);

/*==================================
  جلب بيانات المدير
==================================*/
$stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

if (!$admin) {
    die("Administrator not found");
}

/*==================================
  شعار الشركة
==================================*/
/*==================================
  شعار الشركة
==================================*/

/*==================================
  شعار الشركة
==================================*/

$logo = "uploads/logo.jpg";

/*==================================
  صورة المدير
==================================*/

/*==================================
  صورة المدير
==================================*/

// افتراضياً استخدم شعار الشركة
$image = adminImage($admin['image']);

/*==================================
  الحالة
==================================*/

$statusColor = ($admin['status'] == "Active")
    ? "success"
    : "danger";

$statusText = ($admin['status'] == "Active")
    ? "نشط"
    : "موقوف";

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>تفاصيل المدير</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<style>

body{

background:#edf2f7;

font-family:Tahoma;

}

.page-title{

font-size:28px;

font-weight:bold;

color:#0d6efd;

}

.logo{

height:75px;

}

.profile-card{

background:#fff;

border-radius:18px;

box-shadow:0 10px 30px rgba(0,0,0,.08);

overflow:hidden;

}

.profile-header{

background:linear-gradient(135deg,#0d6efd,#0b5ed7);

padding:40px;

text-align:center;

color:#fff;

}

.profile-img{

width:150px;

height:150px;

border-radius:50%;

border:6px solid #fff;

object-fit:cover;

box-shadow:0 0 20px rgba(0,0,0,.25);

margin-bottom:15px;

}

.profile-name{

font-size:30px;

font-weight:bold;

}

.profile-email{

opacity:.9;

font-size:17px;

}

.info-card{

background:#fff;

border-radius:15px;

padding:20px;

height:100%;

transition:.3s;

box-shadow:0 5px 15px rgba(0,0,0,.08);

}

.info-card:hover{

transform:translateY(-5px);

}

.info-icon{

font-size:32px;

margin-bottom:10px;

color:#0d6efd;

}

.info-title{

font-size:14px;

color:#777;

}

.info-value{

font-size:23px;

font-weight:bold;

margin-top:8px;

}

.table-details td{

padding:14px;

vertical-align:middle;

}

.table-details tr{

border-bottom:1px solid #eee;

}

.table-details strong{

color:#0d6efd;

}

.action-btn{

min-width:170px;

margin:5px;

}

.footer-space{

height:50px;

}

.badge{

font-size:15px;

padding:10px 18px;

}

@media(max-width:768px){

.profile-img{

width:120px;

height:120px;

}

.profile-name{

font-size:24px;

}

.page-title{

font-size:22px;

}

.action-btn{

width:100%;

}

}

</style>

</head>

<body>

<div class="container py-4">

<div class="text-center mb-4">

    <h2 class="page-title">
        <i class="fa-solid fa-user-shield"></i>
        تفاصيل المدير
    </h2>

</div>

<div class="profile-card">

    <!-- Header -->
    <div class="profile-header">

        <img
src="<?= $image ?>?v=<?= time() ?>"
class="profile-img">

        <div class="profile-name">
            <?= htmlspecialchars($admin['name']) ?>
        </div>

        <div class="profile-email">
            <i class="fa-solid fa-envelope"></i>
            <?= htmlspecialchars($admin['email']) ?>
        </div>

        <div class="mt-3">
            <span class="badge bg-<?= $statusColor ?>">
                <?= $statusText ?>
            </span>
        </div>

    </div>

    <!-- Statistics -->
    <div class="p-4">

        <div class="row g-4 mb-4">

            <div class="col-md-3">

                <div class="info-card text-center">

                    <div class="info-icon">
                        <i class="fa-solid fa-id-card"></i>
                    </div>

                    <div class="info-title">
                        رقم المدير
                    </div>

                    <div class="info-value">
                        <?= $admin['id'] ?>
                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="info-card text-center">

                    <div class="info-icon text-success">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>

                    <div class="info-title">
                        مرات الدخول
                    </div>

                    <div class="info-value">
                        <?= (int)$admin['login_count'] ?>
                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="info-card text-center">

                    <div class="info-icon text-warning">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div class="info-title">
                        تاريخ الإنشاء
                    </div>

                    <div class="info-value" style="font-size:16px">

                        <?= !empty($admin['created_at']) ? date('Y-m-d', strtotime($admin['created_at'])) : '-' ?>

                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="info-card text-center">

                    <div class="info-icon text-danger">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div class="info-title">
                        آخر دخول
                    </div>

                    <div class="info-value" style="font-size:16px">

                        <?= !empty($admin['last_login']) ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'لا يوجد' ?>

                    </div>

                </div>

            </div>

        </div>

        <!-- Details -->

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-primary text-white">

                <i class="fa-solid fa-circle-info"></i>

                معلومات المدير

            </div>

            <div class="card-body p-0">

                <table class="table table-hover table-details mb-0">

                    <tr>

                        <td width="35%">

                            <strong>
                                <i class="fa-solid fa-user"></i>
                                الاسم
                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars($admin['name']) ?>

                        </td>

                    </tr>

                    <tr>

                        <td>

                            <strong>

                                <i class="fa-solid fa-envelope"></i>

                                البريد الإلكتروني

                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars($admin['email']) ?>

                        </td>

                    </tr>

                    <tr>

                        <td>

                            <strong>

                                <i class="fa-solid fa-circle-check"></i>

                                الحالة

                            </strong>

                        </td>

                        <td>

                            <span class="badge bg-<?= $statusColor ?>">

                                <?= $statusText ?>

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <td>

                            <strong>

                                <i class="fa-solid fa-right-to-bracket"></i>

                                عدد مرات الدخول

                            </strong>

                        </td>

                        <td>

                            <?= (int)$admin['login_count'] ?>

                        </td>

                    </tr>

                    <tr>

                        <td>

                            <strong>

                                <i class="fa-solid fa-calendar-plus"></i>

                                تاريخ الإنشاء

                            </strong>

                        </td>

                        <td>

                            <?= !empty($admin['created_at']) ? date('Y-m-d H:i', strtotime($admin['created_at'])) : '-' ?>

                        </td>

                    </tr>

                    <tr>

                        <td>

                            <strong>

                                <i class="fa-solid fa-clock"></i>

                                آخر تسجيل دخول

                            </strong>

                        </td>

                        <td>

                            <?= !empty($admin['last_login']) ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'لا يوجد' ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

        <div class="text-center mt-4">
                        <a href="edit_admin.php?id=<?= $admin['id'] ?>"
               class="btn btn-warning action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                تعديل
            </a>

            

            <a href="delete_admin.php?id=<?= $admin['id'] ?>"
               class="btn btn-danger action-btn"
               onclick="return confirm('هل أنت متأكد من حذف هذا المدير؟');">
                <i class="fa-solid fa-trash"></i>
                حذف
            </a>

            <a href="adminview.php"
               class="btn btn-secondary action-btn">
                <i class="fa-solid fa-arrow-right"></i>
                رجوع
            </a>

        </div>

    </div>

</div>

<div class="footer-space"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

// تأثير بسيط عند تحميل الصفحة
document.addEventListener("DOMContentLoaded", function(){

    const cards = document.querySelectorAll(".info-card");

    cards.forEach(function(card,index){

        card.style.opacity = "0";
        card.style.transform = "translateY(20px)";

        setTimeout(function(){

            card.style.transition = "all .5s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";

        }, index * 120);

    });

});

</script>

</body>
</html>