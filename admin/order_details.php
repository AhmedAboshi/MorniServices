<?php
include('../include/core.php');
include('../include/connected.php');

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if(!$order_id){
   die("❌ " . __('no_order'));
}

/* جلب الطلب */
$order = mysqli_query($con,"SELECT * FROM orders WHERE id='$order_id'");
$data = mysqli_fetch_assoc($order);

if(!$data){
    die("❌ " . __('no_order'));
}

/* نوع الطلب */
$order_type = $data['order_type'] ?? 'cart';

/* =========================
   تحديث حالة الطلب
========================= */
if(isset($_POST['update_status'])){

    $status = $_POST['status'];

    mysqli_query($con,"
        UPDATE orders 
        SET status='$status'
        WHERE id='$order_id'
    ");

    header("Location: ".$_SERVER['PHP_SELF']."?id=".$order_id);
    exit();
}
?>

<h2>🧾 <?= __('order_details') ?> #<?= $order_id ?></h2>

<p>👤 <?= __('customer') ?>: <?= $data['full_name'] ?></p>
<p>📞 <?= __('phone') ?>: <?= $data['phone'] ?></p>

<hr>

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
<?= __('update_status') ?>
</button>

</form>