<?php
session_start();
include('../include/connected.php');
include('../include/settings.php');
include('../include/image_helper.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

/* =========================
👨‍💼 جلب المدراء مع الامتيازات
========================= */

/*====================================
        إحصائيات المدراء
====================================*/

$totalAdmins = 0;
$activeAdmins = 0;
$inactiveAdmins = 0;
$totalLogins = 0;

$q = mysqli_query($con,"
SELECT
COUNT(*) total,
SUM(status='Active') active,
SUM(status='Inactive') inactive,
SUM(login_count) logins
FROM admin
");

if($row = mysqli_fetch_assoc($q)){

    $totalAdmins   = $row['total'] ?? 0;
    $activeAdmins  = $row['active'] ?? 0;
    $inactiveAdmins = $row['inactive'] ?? 0;
    $totalLogins   = $row['logins'] ?? 0;

}


/*====================================
        جلب المدراء
====================================*/
$companyLogo = setting('company_logo');
$admins = $con->query("

SELECT

id,
name,
email,
phone,
image,
status,
last_login,
login_count,
created_at

FROM admin

ORDER BY id DESC

");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Admins</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    .table img{

transition:.3s;

}

.table img:hover{

transform:scale(1.12);

}

.table td{

vertical-align:middle;

}

.table th{

white-space:nowrap;

}

.btn-sm{

border-radius:10px;

margin:2px;

}
body{
    background:#f4f6f9;
}
.card{
    border:none;
    border-radius:12px;
}
.badge-perm{
    background:#e5e7eb;
    color:#111;
    margin:2px;
}
.table td{
    vertical-align: middle;
}
.search-box{
    width:300px;
}
</style>
</head>

<body>

<div class="container py-4">

<!-- ================= HEADER ================= -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h2 class="fw-bold mb-1">
            👨‍💼 إدارة المدراء
        </h2>

        <small class="text-muted">
            إدارة جميع المدراء داخل النظام
        </small>
    </div>

    <div class="d-flex gap-2">

        <input
            type="text"
            id="search"
            class="form-control search-box"
            placeholder="🔍 بحث...">

        <a href="addadmin.php"
           class="btn btn-primary">

            ➕ إضافة مدير

        </a>

    </div>

</div>
<!-- ================= STATISTICS ================= -->

<div class="row mb-4">

    <div class="col-lg-3 col-md-6 mb-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <div style="font-size:45px;">
                    👨‍💼
                </div>

                <h3 class="fw-bold text-primary">

                    <?= $totalAdmins ?>

                </h3>

                <div class="text-muted">

                    إجمالي المدراء

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6 mb-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <div style="font-size:45px;">
                    🟢
                </div>

                <h3 class="fw-bold text-success">

                    <?= $activeAdmins ?>

                </h3>

                <div class="text-muted">

                    المدراء النشطون

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6 mb-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <div style="font-size:45px;">
                    🔴
                </div>

                <h3 class="fw-bold text-danger">

                    <?= $inactiveAdmins ?>

                </h3>

                <div class="text-muted">

                    المدراء الموقوفون

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6 mb-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <div style="font-size:45px;">
                    🔑
                </div>

                <h3 class="fw-bold text-dark">

                    <?= $totalLogins ?>

                </h3>

                <div class="text-muted">

                    إجمالي تسجيلات الدخول

                </div>

            </div>

        </div>

    </div>

</div>
<!-- ================= TABLE ================= -->
<div class="card shadow p-3">

<table class="table table-hover">
    <thead class="table-dark">

<tr>

<th>#</th>

<th>الصورة</th>

<th>المدير</th>

<th>البريد الإلكتروني</th>

<th>الجوال</th>

<th>الحالة</th>

<th>آخر دخول</th>

<th>عدد الدخول</th>

<th class="text-center">الإجراءات</th>

</tr>

</thead>

    <tbody id="adminsTable">

<?php while($a = $admins->fetch_assoc()){ ?>

<tr>

<td>
    <strong>#<?= $a['id'] ?></strong>
</td>

<td>

<?php $image = adminImage($a['image']); ?>

<img
src="<?= $image ?>?v=<?= time() ?>"
style="
width:55px;
height:55px;
border-radius:50%;
object-fit:cover;
border:2px solid #0d6efd;
background:#fff;
">

</td>

<td>
<strong><?= htmlspecialchars($a['name']) ?></strong>
</td>

<td>
<?= htmlspecialchars($a['email']) ?>
</td>

<td>
<?= !empty($a['phone']) ? htmlspecialchars($a['phone']) : '-' ?>
</td>

<td>

<?php if($a['status']=="Active"){ ?>

<span class="badge bg-success">
نشط
</span>

<?php }else{ ?>

<span class="badge bg-danger">
موقوف
</span>

<?php } ?>

</td>

<td>

<?php

if(!empty($a['last_login'])){
    echo date("Y-m-d H:i",strtotime($a['last_login']));
}else{
    echo "-";
}

?>

</td>

<td>

<span class="badge bg-primary">

<?= $a['login_count'] ?? 0 ?>

</span>

</td>

<td class="text-center">

<a
href="view_admin.php?id=<?= $a['id'] ?>"
class="btn btn-info btn-sm">
👁
</a>

<a
href="edit_admin.php?id=<?= $a['id'] ?>"
class="btn btn-warning btn-sm">
✏️
</a>

<a
href="delete_admin.php?id=<?= $a['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف المدير؟')">
🗑
</a>

</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

<!-- ================= SEARCH SCRIPT ================= -->
<script>
document.getElementById("search").addEventListener("keyup", function(){
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#adminsTable tr");

    rows.forEach(row => {
        row.style.display =
            row.innerText.toLowerCase().includes(value)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>