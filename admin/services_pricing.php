<?php
session_start();
include('../include/connected.php');

/*=========================================
   اللغة
=========================================*/
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/*=========================================
   الوضع الليلي
=========================================*/
if(isset($_GET['theme'])){
    $_SESSION['theme'] = $_GET['theme'];
}

$theme = $_SESSION['theme'] ?? 'light';

/*=========================================
   الترجمة
=========================================*/
$txt = [

'ar'=>[

'title'=>'إدارة أسعار الخدمات',

'service'=>'الخدمة',

'customer_price'=>'سعر العميل',

'driver_price'=>'سعر السائق',

'profit'=>'ربح الشركة',

'status'=>'الحالة',

'actions'=>'العمليات',

'add'=>'إضافة خدمة',

'edit'=>'تعديل',

'delete'=>'حذف',

'active'=>'مفعل',

'inactive'=>'موقوف',

'total'=>'عدد الخدمات',

'avg_profit'=>'متوسط الربح',

'active_services'=>'الخدمات المفعلة'

],

'en'=>[

'title'=>'Services Pricing',

'service'=>'Service',

'customer_price'=>'Customer Price',

'driver_price'=>'Driver Price',

'profit'=>'Company Profit',

'status'=>'Status',

'actions'=>'Actions',

'add'=>'Add Service',

'edit'=>'Edit',

'delete'=>'Delete',

'active'=>'Active',

'inactive'=>'Inactive',

'total'=>'Total Services',

'avg_profit'=>'Average Profit',

'active_services'=>'Active Services'

]

];

/*=========================================
   جلب الخدمات
=========================================*/

$result = $con->query("
SELECT *,
(customer_price-driver_price) AS profit
FROM services_pricing
ORDER BY sort_order,id
");

/*=========================================
   الإحصائيات
=========================================*/

$totalServices = 0;
$activeServices = 0;
$totalProfit = 0;

$list = [];

while($row = $result->fetch_assoc()){

    $list[] = $row;

    $totalServices++;

    if($row['active']==1){
        $activeServices++;
    }

    $totalProfit += $row['profit'];

}

$avgProfit = 0;

if($totalServices>0){
    $avgProfit = $totalProfit/$totalServices;
}

?>
<!DOCTYPE html>

<html lang="<?= $lang ?>"
dir="<?= $lang=='ar' ? 'rtl' : 'ltr' ?>">

<head>

<meta charset="utf-8">

<title><?= $txt[$lang]['title']; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>

body.dark-mode{
background:#121212!important;
color:#fff!important;
}

body.dark-mode .card{
background:#1e1e1e!important;
color:#fff!important;
border-color:#333!important;
}

body.dark-mode .table{
color:#fff!important;
}

body.dark-mode table.dataTable tbody tr{
background:#1e1e1e!important;
}

.stat-card{

border-radius:15px;

padding:20px;

color:#fff;

}

</style>

</head>

<body class="<?= ($theme=='dark') ? 'dark-mode' : 'bg-light'; ?>">

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between mb-3">

<div>

<a href="?lang=ar" class="btn btn-primary btn-sm">
العربية
</a>

<a href="?lang=en" class="btn btn-success btn-sm">
English
</a>

</div>

<div>

<a href="?theme=dark" class="btn btn-dark btn-sm">
🌙
</a>

<a href="?theme=light" class="btn btn-light btn-sm">
☀️
</a>

</div>

</div>

<div class="row">

<div class="col-md-4">

<div class="card bg-primary text-white shadow">

<div class="card-body">

<h6><?= $txt[$lang]['total']; ?></h6>

<h2><?= $totalServices; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-success text-white shadow">

<div class="card-body">

<h6><?= $txt[$lang]['active_services']; ?></h6>

<h2><?= $activeServices; ?></h2>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card bg-dark text-white shadow">

<div class="card-body">

<h6><?= $txt[$lang]['avg_profit']; ?></h6>

<h2><?= number_format($avgProfit,2); ?> ريال</h2>

</div>

</div>

</div>

</div>

<div class="card shadow mt-4">

<div class="card-header d-flex justify-content-between align-items-center">

<h4>
💰 <?= $txt[$lang]['title']; ?>
</h4>

<a href="service_add.php"
class="btn btn-success">

➕ <?= $txt[$lang]['add']; ?>

</a>

</div>

<div class="card-body">
    <?php
if(isset($_GET['added'])){
?>
<div class="alert alert-success alert-dismissible fade show">
    ✅ تم إضافة الخدمة بنجاح.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<?php
if(isset($_GET['updated'])){
?>
<div class="alert alert-success alert-dismissible fade show">
    ✅ تم تحديث الخدمة بنجاح.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<?php
if(isset($_GET['deleted'])){
?>
<div class="alert alert-danger alert-dismissible fade show">
    🗑 تم حذف الخدمة بنجاح.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<div class="table-responsive">

<table id="servicesTable"
class="table table-bordered table-hover table-striped align-middle">

<thead class="table-dark">

<tr>

<th width="70">#</th>

<th><?= $txt[$lang]['service']; ?></th>

<th><?= $txt[$lang]['customer_price']; ?></th>

<th><?= $txt[$lang]['driver_price']; ?></th>

<th><?= $txt[$lang]['profit']; ?></th>

<th><?= $txt[$lang]['status']; ?></th>

<th width="170"><?= $txt[$lang]['actions']; ?></th>

</tr>

</thead>

<tbody>

<?php
foreach($list as $row){

$profit = $row['customer_price']-$row['driver_price'];
?>

<tr>

<td>

<?= $row['id']; ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['service_name']); ?>

</strong>

<br>

<small class="text-muted">

<?= htmlspecialchars($row['service_code']); ?>

</small>

</td>

<td>

<span class="badge bg-primary fs-6">

<?= number_format($row['customer_price'],2); ?>

ريال

</span>

</td>

<td>

<span class="badge bg-warning text-dark fs-6">

<?= number_format($row['driver_price'],2); ?>

ريال

</span>

</td>

<td>

<?php
if($profit>=0){
?>

<span class="badge bg-success fs-6">

<?= number_format($profit,2); ?>

ريال

</span>

<?php
}else{
?>

<span class="badge bg-danger fs-6">

<?= number_format($profit,2); ?>

ريال

</span>

<?php
}
?>

</td>

<td>

<?php
if($row['active']==1){
?>

<span class="badge bg-success">

<?= $txt[$lang]['active']; ?>

</span>

<?php
}else{
?>

<span class="badge bg-secondary">

<?= $txt[$lang]['inactive']; ?>

</span>

<?php
}
?>

</td>

<td>

<a
href="service_edit.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm">

✏️

<?= $txt[$lang]['edit']; ?>

</a>

<a
href="service_delete.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('هل تريد حذف هذه الخدمة؟');">

🗑

<?= $txt[$lang]['delete']; ?>

</a>

</td>

</tr>

<?php
}
?>

</tbody>

</table>

</div>

</div>

</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>

$(document).ready(function(){

    $('#servicesTable').DataTable({

        pageLength:25,

        responsive:true,

        order:[[0,'asc']],

        language:{

            url:<?= ($lang=='ar')
            ? "'//cdn.datatables.net/plug-ins/1.13.8/i18n/ar.json'"
            : "''"; ?>

        }

    });

});

</script>

<style>

.table td{
    vertical-align:middle;
}

.badge{
    font-size:13px;
}

.card{
    border-radius:15px;
}

.btn{
    border-radius:10px;
}

body.dark-mode .table-striped>tbody>tr:nth-of-type(odd){
    background:#202020!important;
}

body.dark-mode .table-striped>tbody>tr:nth-of-type(even){
    background:#2b2b2b!important;
}

body.dark-mode .table-dark{
    background:#000!important;
}

body.dark-mode .dataTables_filter input{

    background:#2c2c2c!important;

    color:#fff!important;

    border:1px solid #555!important;

}

body.dark-mode .dataTables_length select{

    background:#2c2c2c!important;

    color:#fff!important;

}

body.dark-mode .page-link{

    background:#2c2c2c!important;

    color:#fff!important;

    border-color:#555!important;

}

body.dark-mode .page-item.active .page-link{

    background:#0d6efd!important;

    border-color:#0d6efd!important;

}

body.dark-mode .text-muted{

    color:#bbb!important;

}

</style>

</body>
</html>