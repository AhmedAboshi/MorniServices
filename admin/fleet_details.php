<?php
session_start();


include('../include/core.php');
include('../include/connected.php');
$updated = isset($_GET['updated']) && $_GET['updated'] == 1;
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}



/*=========================
  جلب رقم المركبة
=========================*/
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die(t('not_found'));
}

/*=========================
  تنبيهات
=========================*/
$type  = $_GET['type'] ?? '';
$alert = $_GET['alert'] ?? '';

/*=========================
  بيانات المركبة
=========================*/
$stmt = $con->prepare("SELECT * FROM fleet WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die(t('not_found'));
}

$row = $result->fetch_assoc();

/* =========================
🛠 جلب سجلات صيانة المركبة
========================= */

$plate = $row['plate'];


/* عدد سجلات الصيانة */

$maintenance_count = $con->query("
    SELECT COUNT(*) AS total
    FROM maintenance
    WHERE plate_number='$plate'
")->fetch_assoc()['total'];



/* =========================
🛠 جلب سجلات صيانة المركبة
========================= */

$plate = $row['plate'];

$maintenance_count = $con->query("
    SELECT COUNT(*) AS total
    FROM maintenance
    WHERE plate_number='$plate'
")->fetch_assoc()['total'];

$maintenance = $con->query("
    SELECT *
    FROM maintenance
    WHERE plate_number='$plate'
    ORDER BY maintenance_date DESC, id DESC
");
$maintenance_stats = $con->query("
    SELECT
        COUNT(*) AS total,
        IFNULL(SUM(cost),0) AS total_cost,
        MAX(maintenance_date) AS last_date
    FROM maintenance
    WHERE plate_number='$plate'
")->fetch_assoc();

// إحصائيات الإطارات

$tire_stats_sql = "
SELECT 
    COUNT(*) AS total,
    SUM(cost) AS total_cost,
    MAX(change_date) AS last_change
FROM tires
WHERE car_id = '$id'
";

$tire_stats_result = $con->query($tire_stats_sql);

$tire_stats = $tire_stats_result->fetch_assoc();


// سجل الإطارات

$tires_sql = "
SELECT *
FROM tires
WHERE car_id = '$id'
ORDER BY change_date DESC
";

$tires = $con->query($tires_sql);

$next_tire = $con->query("
SELECT next_km,next_change
FROM tires
WHERE car_id='$id'
ORDER BY next_km ASC
LIMIT 1
")->fetch_assoc();
/*=========================================
  إحصائيات المركبة
=========================================*/

// عدد الصيانات
$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM maintenance
    WHERE plate_number = ?
");
$stmt->bind_param("s", $row['plate']);
$stmt->execute();
$maintenance_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


// عدد تغييرات الزيت
$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM oil_changes
    WHERE car_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$oil_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


// عدد تغييرات الإطارات
$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM tires
    WHERE car_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$tire_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


$documents_count = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM vehicle_documents
WHERE car_id='$id'
"));

/*=========================================
  آخر صيانة
=========================================*/

$stmt = $con->prepare("
SELECT maintenance_date
FROM maintenance
WHERE plate_number=?
ORDER BY maintenance_date DESC
LIMIT 1
");

$stmt->bind_param("s",$row['plate']);
$stmt->execute();

$lastMaintenance =
$stmt->get_result()->fetch_assoc();


/*=========================================
  آخر تغيير زيت
=========================================*/

$stmt = $con->prepare("
SELECT change_date,next_change
FROM oil_changes
WHERE car_id=?
ORDER BY change_date DESC
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$lastOil =
$stmt->get_result()->fetch_assoc();

/* =========================
🛢 بيانات تغييرات الزيت
========================= */

/* عدد تغييرات الزيت */

$oil_count = $con->query("
    SELECT COUNT(*) AS total
    FROM oil_changes
    WHERE car_id='$id'
")->fetch_assoc()['total'];


/* سجلات تغييرات الزيت */

$oil_changes = $con->query("
    SELECT *
    FROM oil_changes
    WHERE car_id='$id'
    ORDER BY change_date DESC, id DESC
");


/* إحصائيات تغييرات الزيت */

$oil_stats = $con->query("
    SELECT
        COUNT(*) AS total,
        IFNULL(SUM(cost),0) AS total_cost,
        MAX(change_date) AS last_change
    FROM oil_changes
    WHERE car_id='$id'
")->fetch_assoc();

/* =========================
🛞 بيانات الإطارات
========================= */

/* عدد تغييرات الإطارات */

$tire_count = $con->query("
    SELECT COUNT(*) AS total
    FROM tires
    WHERE car_id='$id'
")->fetch_assoc()['total'];

$documents_count = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM vehicle_documents
WHERE car_id='$id'
"));
// جلب مستندات المركبة
$documents = mysqli_query($con,"
    SELECT *
    FROM vehicle_documents
    WHERE car_id='$id'
    ORDER BY id DESC
");
/* سجلات الإطارات */

$tires = $con->query("
    SELECT *
    FROM tires
    WHERE car_id='$id'
    ORDER BY change_date DESC, id DESC
");


/* إحصائيات الإطارات */

$tire_stats = $con->query("
    SELECT
        COUNT(*) AS total,
        IFNULL(SUM(cost),0) AS total_cost,
        MAX(change_date) AS last_change
    FROM tires
    WHERE car_id='$id'
")->fetch_assoc();

/*=========================================
  آخر تغيير إطار
=========================================*/

$stmt = $con->prepare("
SELECT change_date,next_change
FROM tires
WHERE car_id=?
ORDER BY change_date DESC
LIMIT 1
");


/*=========================
  صورة المركبة
=========================*/
$image = "../fleetimg/img/no-image.png";

if (!empty($row['imgfleet']) && file_exists("../fleetimg/img/" . $row['imgfleet'])) {
    $image = "../fleetimg/img/" . $row['imgfleet'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= ($lang == 'ar') ? 'rtl' : 'ltr'; ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= t('fleet_details') ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="assets/dark-mode.css">

<style>

body{
    background:#f4f6f9;
    font-family:Cairo,sans-serif;
}

.page-title{
    font-size:28px;
    font-weight:bold;
}

.vehicle-header{

    background:#ffffff;

    border-radius:18px;

    padding:25px;

    box-shadow:0 8px 25px rgba(0,0,0,.08);

    margin-bottom:25px;
}

.vehicle-image{

    width:170px;

    height:170px;

    border-radius:15px;

    object-fit:cover;

    border:4px solid #f2f2f2;
}

.vehicle-name{

    font-size:28px;

    font-weight:bold;
}

.vehicle-plate{

    font-size:20px;

    color:#0d6efd;

    font-weight:bold;
}

.summary-card{
    height:140px;
    border:none;
    border-radius:16px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
    transition:.3s;
}

.summary-card .card-body{
    display:flex;
    align-items:center;
    height:100%;
}

.summary-card:hover{

    transform:translateY(-4px);
}

.summary-icon{

    width:55px;

    height:55px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#fff;

    font-size:22px;
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

.bg-red{

    background:#dc3545;
}

.action-btn{

    margin:5px;
}

.nav-tabs .nav-link{

    font-weight:bold;
}
.tab-content{
    min-height:350px;
}

.nav-tabs .nav-link{
    color:#555;
    font-weight:600;
}

.nav-tabs .nav-link.active{
    color:#0d6efd;
    border-top:3px solid #0d6efd;
}

.card{
    border-radius:15px;
}

.badge{
    font-size:.9rem;
}

@media(max-width:991px){

.vehicle-header{
    text-align:center;
}

.vehicle-image{
    margin-bottom:20px;
}

.summary-card{
    margin-bottom:15px;
}

.action-btn{
    width:100%;
}

}
.document-card {
     min-height: 430px;
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-5px);
}

.document-card .card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.document-card .document-actions {
    margin-top: auto;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: center;
    gap: 8px;
}

.document-card .document-actions .btn {
    transition: all .25s ease;
    border-radius: 8px;
}

.document-card .document-actions .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(0,0,0,.15);
}

.document-card {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.nav-link .badge{
    font-size:12px;
    padding:5px 9px;
    border-radius:12px;
    vertical-align:middle;
}
</style>

</head>

<body>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<div class="page-title">



<h3 class="mb-0">
        <i class="bi bi-truck"></i>
        الملف الإلكتروني للمركبة
    </h3>

</div>
<?php if($updated): ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="bi bi-check-circle"></i>

تم تحديث بيانات المركبة بنجاح

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php endif; ?>
<div>

<a href="fleet.php" class="btn btn-secondary">

<i class="bi bi-arrow-right-circle"></i>

<?= t('back') ?>

</a>

<button onclick="toggleDarkMode()" class="btn btn-dark">

<i class="bi bi-moon"></i>

</button>

</div>

</div>

<?php if($alert==1 && $type=="inspection"){ ?>

<div class="alert alert-warning">

<i class="bi bi-exclamation-triangle-fill"></i>

الفحص الدوري للمركبة منتهي

</div>

<?php } ?>

<?php if($alert==1 && $type=="operation"){ ?>

<div class="alert alert-danger">

<i class="bi bi-x-octagon-fill"></i>

كرت تشغيل المركبة منتهي

</div>

<?php } ?>

<div class="vehicle-header">

<div class="row align-items-center">

<div class="col-lg-2 text-center">

<img src="<?= $image ?>" class="vehicle-image">

</div>

<div class="col-lg-6">

<div class="vehicle-name">

<?= htmlspecialchars($row['driver']) ?>

</div>

<div class="vehicle-plate mt-2">

<i class="bi bi-credit-card"></i>

<?= htmlspecialchars($row['plate']) ?>

</div>

<div class="mt-3">

<span class="badge bg-primary">

<?= htmlspecialchars($row['typefleet']) ?>

</span>

<span class="badge bg-success">

<?= htmlspecialchars($row['model']) ?>

</span>

<span class="badge bg-dark">

<?= htmlspecialchars($row['classify']) ?>

</span>

</div>

</div>

<div class="col-lg-4 text-lg-end mt-4 mt-lg-0">

<a href="updatefleet.php?id=<?= $id ?>"
   class="btn btn-warning action-btn">

    <i class="bi bi-pencil-square"></i>

    تعديل

</a>

 <a href="fleet_pdf.php?id=<?= $id ?>" 
class="btn btn-danger"
target="_blank">

<i class="bi bi-file-earmark-pdf"></i>

PDF

</a>

        <a href="fleet_excel.php?id=<?= $id ?>"
           class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i>
            Excel
        </a>
<button class="btn btn-success action-btn">

<i class="bi bi-printer"></i>

طباعة

</button>

<a href="maintenance.php?car_id=<?= $id ?>" 
   class="btn btn-success">

<i class="bi bi-plus-circle"></i>
إضافة صيانة

</a>

<a href="add_oil.php?car_id=<?= $id ?>" 
   class="btn btn-info text-white action-btn">

<i class="bi bi-droplet-half"></i>

زيت

</a>


<a href="add_tire.php?car_id=<?= $id ?>"
   class="btn btn-dark action-btn">

<i class="bi bi-circle"></i>

إطار

</a>

</div>

</div>

</div>

<div class="row g-4 mb-4">

<div class="col-xl-2 col-lg-4 col-md-6">

<div class="card summary-card">

<div class="card-body d-flex align-items-center">

<div class="summary-icon bg-blue">

<i class="bi bi-truck"></i>

</div>

<div class="ms-3">

<div class="text-muted">

بيانات المركبة

</div>

<h5 class="mb-0">

<?= htmlspecialchars($row['typefleet']) ?>

</h5>

</div>

</div>

</div>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<div class="card summary-card">

<div class="card-body d-flex align-items-center">

<div class="summary-icon bg-green">

<i class="bi bi-file-earmark-text"></i>

</div>

<div class="ms-3">

<div class="text-muted">

المستندات

</div>

<h5 class="mb-0">

جاهزة

</h5>

</div>

</div>

</div>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<div class="card summary-card">

<div class="card-body d-flex align-items-center">

<div class="summary-icon bg-orange">

<i class="bi bi-tools"></i>

</div>

<div class="ms-3">

<div class="text-muted">

عدد عمليات الصيانة

</div>

<h4>

<?= $maintenance_count ?>

</h4>

<small class="text-muted">

<?= $lastMaintenance['maintenance_date'] ?? 'لا توجد بيانات' ?>

</small>


</div>

</div>

</div>

</div>

<div class="col-xl-2 col-lg-4 col-md-6">

<div class="card summary-card">

<div class="card-body d-flex align-items-center">

<div class="summary-icon bg-red">

<i class="bi bi-droplet-half"></i>

</div>

<div class="text-muted">

تغييرات الزيت

</div>

<h4>

<?= $oil_count ?>

</h4>

<small class="text-muted">

<?= $lastOil['change_date'] ?? 'لا توجد بيانات' ?>

</small>

</div>

</div>

</div>
<div class="col-xl-2 col-lg-4 col-md-6">

    <div class="card summary-card">

        <div class="card-body d-flex align-items-center">

            <div class="summary-icon bg-dark">

                <i class="bi bi-circle"></i>

            </div>

            <div class="ms-3">

                <div class="text-muted">
                    الإطارات
                </div>

                <h4>
                    <?= $tire_count ?>
                </h4>

                <small class="text-muted">
                    <?= $lastTire['change_date'] ?? 'لا توجد بيانات' ?>
                </small>

            </div>

        </div>

    </div>

</div>
<div class="col-xl-2 col-lg-4 col-md-6">

    <div class="card summary-card">

        <div class="card-body d-flex align-items-center">

            <div class="summary-icon bg-success">

                <i class="bi bi-fuel-pump"></i>

            </div>

            <div class="ms-3">

                <div class="text-muted">
                    الوقود
                </div>

                <h4>
                    --
                </h4>

                <small class="text-muted">
                    قريباً
                </small>

            </div>

        </div>

    </div>

</div>
</div>

<!-- يبدأ هنا قسم التبويبات -->
<div class="card border-0 shadow-sm">
<div class="card-body">
    <ul class="nav nav-tabs" id="fleetTabs" role="tablist">

    <li class="nav-item" role="presentation">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#info"
                type="button">
            <i class="bi bi-info-circle"></i>
            المعلومات
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link"
        data-bs-toggle="tab"
        data-bs-target="#documents"
        type="button">

    <i class="bi bi-folder2-open"></i>

    المستندات

    <span class="badge bg-primary ms-1">
        <?= $documents_count['total']; ?>
    </span>

</button>
    </li>

    <li class="nav-item" role="presentation">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#maintenance"
            type="button">

        <i class="bi bi-tools"></i>
        الصيانة

        <span class="badge bg-primary">
            <?= $maintenance_count ?>
        </span>

    </button>
</li>

   <li class="nav-item" role="presentation">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#oils"
            type="button">

        <i class="bi bi-droplet-half"></i>
        الزيوت

        <span class="badge bg-primary ms-1">
            <?= $oil_count ?>
        </span>

    </button>
</li>


   <li class="nav-item" role="presentation">
    <button class="nav-link"
            data-bs-toggle="tab"
            data-bs-target="#tires"
            type="button">

        <i class="bi bi-circle"></i>
        الإطارات

        <span class="badge bg-primary ms-1">
            <?= $tire_count ?>
        </span>

    </button>
</li>
    <!-- جاهز للمستقبل -->
    <!--
    <li class="nav-item">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#fuel">
            الوقود
        </button>
    </li>
    -->

</ul>

<div class="tab-content pt-4">

    <!-- المعلومات -->
    <div class="tab-pane fade show active" id="info">

        <?php include 'fleet_tabs/info.php'; ?>

    </div>

  <!-- المستندات -->
<div class="tab-pane fade" id="documents">
    <?php include 'fleet_tabs/documents.php'; ?>

</div>


<!-- تبويب الصيانة -->
<div class="tab-pane fade" id="maintenance">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h5>
            <i class="bi bi-tools"></i>
            سجل الصيانة
        </h5>


        <a href="maintenance.php?car_id=<?= $id ?>" 
   class="btn btn-success">

<i class="bi bi-plus-circle"></i>
إضافة صيانة

</a>

    </div>


<div class="row mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6 class="text-muted">🛠 عدد الصيانات</h6>
                <h3><?= $maintenance_stats['total']; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6 class="text-muted">💰 إجمالي التكلفة</h6>
                <h3><?= number_format($maintenance_stats['total_cost'],2); ?> ريال</h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6 class="text-muted">📅 آخر صيانة</h6>
                <h5><?= $maintenance_stats['last_date'] ?: 'لا يوجد'; ?></h5>
            </div>
        </div>
    </div>

</div>
    

<?php if($maintenance && $maintenance->num_rows > 0){ ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-dark">

<tr>
    <th>التاريخ</th>
    <th>نوع الصيانة</th>
    <th>السائق</th>
    <th>التكلفة</th>
    <th>الملاحظات</th>
</tr>

</thead>


<tbody>

<?php while($m = $maintenance->fetch_assoc()){ ?>

<tr>

<td>
<?= $m['maintenance_date'] ?>
</td>


<td>
<?= $m['maintenance_type'] ?>
</td>


<td>
<?= $m['driver'] ?>
</td>


<td>
<?= number_format($m['cost'],2) ?> ريال
</td>


<td>
<?= $m['notes'] ?>
</td>


</tr>

<?php } ?>

</tbody>

</table>

</div>


<?php } else { ?>


<div class="alert alert-info text-center">

<i class="bi bi-info-circle"></i>

لا توجد سجلات صيانة لهذه المركبة



</div>

<?php } ?>

   

       

</div>



<!-- تبويب الزيوت -->

<div class="tab-pane fade" id="oils">

<div class="row mb-4">

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">🛢 عدد تغييرات الزيت</h6>
                <h3><?= $oil_stats['total']; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">💰 إجمالي التكلفة</h6>
                <h3><?= number_format($oil_stats['total_cost']); ?> ريال</h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">📅 آخر تغيير</h6>
                <h5><?= $oil_stats['last_change'] ?: 'لا يوجد'; ?></h5>
            </div>
        </div>
    </div>

</div>
<?php if($oil_changes && $oil_changes->num_rows > 0){ ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

    <thead class="table-dark">

        <tr>

            <th>التاريخ</th>
            <th>نوع الزيت</th>
            <th>السائق</th>
            <th>الكم الحالي</th>
            <th>التغيير القادم</th>
            <th>التكلفة</th>

        </tr>

    </thead>

    <tbody>

<?php while($oil = $oil_changes->fetch_assoc()){ ?>

<tr>

<td>
<?= $oil['change_date']; ?>
</td>

<td>
<?= $oil['oil_type']; ?>
</td>

<td>
<?= $oil['driver']; ?>
</td>

<td>
<?= number_format($oil['current_km']); ?> كم
</td>

<td>
<?= number_format($oil['next_km']); ?> كم
</td>

<td>
<?= number_format($oil['cost']); ?> ريال
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php } else { ?>

<div class="alert alert-info text-center">

<i class="bi bi-info-circle"></i>

لا توجد سجلات تغيير زيت لهذه المركبة

</div>

<?php } ?>
</div>




   


<!-- الإطارات -->
<div class="tab-pane fade" id="tires">

    <div class="card shadow-sm mt-3">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                <i class="bi bi-circle"></i>
                سجل الإطارات
            </h5>

            <a href="add_tire.php?car_id=<?= $id ?>"
               class="btn btn-success btn-sm">

                <i class="bi bi-plus-circle"></i>
                إضافة إطار

            </a>

        </div>

        <div class="card-body">

            <?php include 'fleet_tabs/tires.php'; ?>

        </div>

    </div>

</div>

    <!-- الوقود -->
    <!--
    <div class="tab-pane fade" id="fuel">

        <?php // include 'fleet_tabs/fuel.php'; ?>

    </div>
    -->

</div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dark Mode -->
<script src="assets/dark-mode.js"></script>

</body>
</html>