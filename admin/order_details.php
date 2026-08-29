<?php
session_start();



include('../include/core.php');
include('../include/connected.php');
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(!$order_id){
    die("❌ ".__('no_order'));
}

/*=========================
    جلب بيانات الطلب
=========================*/

$sql = "
SELECT
orders.*,
drivers.name  AS driver_name,
drivers.phone AS driver_phone
FROM orders
LEFT JOIN drivers
ON drivers.id=orders.driver_id
WHERE orders.id='$order_id'
";

$result = mysqli_query($con,$sql);

$data = mysqli_fetch_assoc($result);

if(!$data){
    die("❌ ".__('no_order'));
}

$order_type = $data['order_type'] ?? 'cart';

/*=========================
    تحديث الحالة
=========================*/

if(isset($_POST['update_status'])){

    $status=mysqli_real_escape_string(
        $con,
        $_POST['status']
    );

    mysqli_query($con,"
    UPDATE orders
    SET status='$status'
    WHERE id='$order_id'
    ");

    header("Location: ".$_SERVER['PHP_SELF']."?id=".$order_id);
    exit();
}

/*=========================
    ألوان الحالة
=========================*/

switch($data['status']){

case 'pending':
$statusColor='warning';
$statusText='⏳ '.__('pending');
break;

case 'assigned':
$statusColor='primary';
$statusText='🚚 '.__('assigned');
break;

case 'on_the_way':
$statusColor='info';
$statusText='🚗 '.__('on_the_way');
break;

case 'done':
$statusColor='success';
$statusText='✅ '.__('done');
break;

case 'cancelled':
$statusColor='danger';
$statusText='❌ '.__('cancelled');
break;

default:
$statusColor='secondary';
$statusText=$data['status'];

}
?>
<!DOCTYPE html>

<html lang="<?= $lang ?>"
dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title><?= __('order_details') ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="assets/dark-mode.css">

<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>

body{

background:#f4f6f9;

}

.page-header{

background:linear-gradient(135deg,#0d6efd,#0b5ed7);

color:#fff;

padding:25px;

border-radius:18px;

margin-bottom:25px;

box-shadow:0 10px 30px rgba(0,0,0,.12);

}

.info-card{

border:none;

border-radius:18px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

transition:.3s;

}

.info-card:hover{

transform:translateY(-3px);

}

.icon-box{

width:55px;

height:55px;

display:flex;

align-items:center;

justify-content:center;

border-radius:15px;

background:#0d6efd;

color:#fff;

font-size:22px;

}

.card-title{

font-size:14px;

color:#888;

margin-bottom:6px;

}

.card-value{

font-size:18px;

font-weight:bold;

}

#map{

height:420px;

border-radius:15px;

}

body.dark-mode .info-card{

background:#1f1f1f;

color:#fff;

}

body.dark-mode .card-title{

color:#bbb;

}

</style>

</head>

<body>

<div class="container py-4">

<div class="page-header d-flex justify-content-between align-items-center">

<div>

<h2 class="mb-2">

<i class="bi bi-receipt"></i>

<?= __('order_details') ?>

#<?= $order_id ?>

</h2>

<div class="badge bg-<?= $statusColor ?> fs-6">

<?= $statusText ?>

</div>

</div>

<div class="d-flex align-items-center gap-2">

<a href="?id=<?= $order_id ?>&lang=ar"
class="btn btn-light btn-sm">

🇸🇦 عربي

</a>

<a href="?id=<?= $order_id ?>&lang=en"
class="btn btn-light btn-sm">

🇬🇧 English

</a>

<button
onclick="toggleDarkMode()"
class="btn btn-warning btn-sm">

🌙

</button>

</div>

</div>

<div class="row g-3 mb-4">

<div class="col-lg-3">

<div class="card info-card">

<div class="card-body d-flex">

<div class="icon-box me-3">

<i class="bi bi-person"></i>

</div>

<div>

<div class="card-title">

<?= __('customer') ?>

</div>

<div class="card-value">

<?= htmlspecialchars($data['full_name']) ?>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card info-card">

<div class="card-body d-flex">

<div class="icon-box me-3 bg-success">

<i class="bi bi-telephone"></i>

</div>

<div>

<div class="card-title">

<?= __('phone') ?>

</div>

<div class="card-value">

<?= htmlspecialchars($data['phone']) ?>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card info-card">

<div class="card-body d-flex">

<div class="icon-box me-3 bg-warning">

<i class="bi bi-cash-stack"></i>

</div>

<div>

<div class="card-title">

<?= __('price') ?>

</div>

<div class="card-value">

<?= number_format($data['price'],2) ?>

<?= __('currency') ?>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card info-card">

<div class="card-body d-flex">

<div class="icon-box me-3 bg-danger">

<i class="bi bi-truck"></i>

</div>

<div>

<div class="card-title">

<?= __('driver') ?>

</div>

<div class="card-value">

<?= $data['driver_name'] ?: __('not_assigned') ?>

</div>

</div>

</div>

</div>

</div>

</div>


<?php if($order_type === 'tow'){ ?>

<!-- 🚚 طلب سطحة -->
<div style="border:1px solid #ddd;padding:15px;border-radius:10px">

<h3>🚚 <?= __('intercity_order') ?></h3>

<p>📍 <?= __('from') ?>: <?= $data['from_city'] ?></p>
<p>📍 <?= __('to') ?>: <?= $data['to_city'] ?></p>

<p>🚗 <?= __('car_type') ?>: <?= $data['car_type'] ?></p>
<p>📏 <?= __('distance') ?>: <?= $data['distance'] ?> <?= __('km') ?></p>
<p>💰 <?= __('price') ?>: <?= $data['price'] ?> <?= __('currency') ?></p>

<hr>

<p>📍 <?= __('pickup') ?>: <?= $data['pickup_lat'] ?> , <?= $data['pickup_lng'] ?></p>
<p>📍 <?= __('delivery') ?>: <?= $data['delivery_lat'] ?> , <?= $data['delivery_lng'] ?></p>

<!-- 🗺️ الخريطة -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div id="map" style="height:350px;border-radius:10px;margin-top:10px"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const txtPickup = "<?= __('pickup') ?>";
const txtDelivery = "<?= __('delivery') ?>";

let fromLat = <?= $data['pickup_lat'] ?? 0 ?>;
let fromLng = <?= $data['pickup_lng'] ?? 0 ?>;
let toLat   = <?= $data['delivery_lat'] ?? 0 ?>;
let toLng   = <?= $data['delivery_lng'] ?? 0 ?>;

let map = L.map('map').setView([fromLat, fromLng], 6);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

if(fromLat && fromLng){
    L.marker([fromLat, fromLng]).addTo(map)
    .bindPopup("📍 " + txtPickup);
}

if(toLat && toLng){
    L.marker([toLat, toLng]).addTo(map)
    .bindPopup("📍 " + txtDelivery);
}

if(fromLat && toLat){
    L.polyline([
        [fromLat, fromLng],
        [toLat, toLng]
    ], {color:'blue'}).addTo(map);
}

</script>

</div>

<?php } else { ?>

<!-- 🛒 طلب سلة -->
<?php
$items = mysqli_query($con,"
SELECT order_details.*, product.proname, product.proimg
FROM order_details
LEFT JOIN product ON product.id = order_details.product_id
WHERE order_details.order_id='$order_id'
");

$total = 0;
?>

<div style="border:1px solid #ddd;padding:15px;border-radius:10px">

<h3>🛒 <?= __('product_order') ?></h3>

<p>🏠 <?= __('address') ?>: <?= $data['address'] ?></p>
<p>🏙️ <?= __('city') ?>: <?= $data['city'] ?></p>

<hr>

<table border="1" width="100%" cellpadding="5">

<tr>
<th>🖼️ <?= __('image') ?></th>
<th>📦 <?= __('product') ?></th>
<th>🔢 <?= __('quantity') ?></th>
<th>💰 <?= __('price') ?></th>
<th>🧾 <?= __('total') ?></th>
</tr>

<?php while($row = mysqli_fetch_assoc($items)){

$sub = $row['quantity'] * $row['price'];
$total += $sub;

?>

<tr>

<td>
<?php if(!empty($row['proimg'])){ ?>
<img src="../uploads/img/<?= $row['proimg'] ?>" width="60">
<?php } ?>
</td>

<td><?= $row['proname'] ?></td>
<td><?= $row['quantity'] ?></td>
<td><?= $row['price'] ?></td>
<td><?= $sub ?></td>

</tr>

<?php } ?>

<tr>
<td colspan="4"><b>💰 <?= __('total') ?></b></td>
<td><b><?= $total ?></b></td>
</tr>

</table>

</div>

<?php } ?>

<hr>

<!-- 🔄 تحديث الحالة -->
<h3>🔄 <?= __('update_status') ?></h3>

<form method="POST">

<select name="status" style="width:100%;padding:10px">

<option value="pending" <?= $data['status']=='pending'?'selected':'' ?>>
    ⏳ <?= __('pending') ?>
</option>

<option value="assigned" <?= $data['status']=='assigned'?'selected':'' ?>>
    🚚 <?= __('assigned') ?>
</option>

<option value="on_the_way" <?= $data['status']=='on_the_way'?'selected':'' ?>>
    🚗 <?= __('on_the_way') ?>
</option>

<option value="done" <?= $data['status']=='done'?'selected':'' ?>>
    ✅ <?= __('done') ?>
</option>

<option value="cancelled" <?= $data['status']=='cancelled'?'selected':'' ?>>
    ❌ <?= __('cancelled') ?>
</option>

</select>

<br><br>

<button type="submit" name="update_status"
style="width:100%;padding:12px;background:green;color:white;border:none">
<?= __('update') ?>
</button>

</form>

<script src="assets/dark-mode.js"></script>
</body>