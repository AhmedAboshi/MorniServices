<?php
session_start();

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');
/* =========================
   🌍 اللغة
========================= */

if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

/* =========================
   🌙 الوضع الليلي
========================= */

if(isset($_GET['theme'])){
    $_SESSION['theme'] = $_GET['theme'];
}

$theme = $_SESSION['theme'] ?? 'light';

/* =========================
   🚚 السائقين
========================= */

$drivers = mysqli_query($con,"
SELECT id,name
FROM drivers
ORDER BY name
");

/* =========================
   🏙️ المدن
========================= */

$routes = mysqli_query($con,"
SELECT DISTINCT from_city, to_city
FROM transport_pricing
ORDER BY from_city, to_city
");

/* =========================
   💾 حفظ الطلب
========================= */

if(isset($_POST['save'])){

    $full_name  = mysqli_real_escape_string($con,$_POST['full_name']);
    $phone      = mysqli_real_escape_string($con,$_POST['phone']);

    $from_city  = mysqli_real_escape_string($con,$_POST['from_city']);
    $to_city    = mysqli_real_escape_string($con,$_POST['to_city']);

    $truck_type = mysqli_real_escape_string($con,$_POST['truck_type']);

    $price          = floatval($_POST['price']);
    $driver_price   = floatval($_POST['driver_price']);
    $company_profit = floatval($_POST['company_profit']);

    $driver_id = (int)$_POST['driver_id'];

    $status = mysqli_real_escape_string($con,$_POST['status']);

    $booking_type = mysqli_real_escape_string($con,$_POST['booking_type']);

    $scheduled_date = !empty($_POST['scheduled_date'])
        ? $_POST['scheduled_date']
        : NULL;

    $scheduled_time = !empty($_POST['scheduled_time'])
        ? $_POST['scheduled_time']
        : NULL;

    $pickup_location = mysqli_real_escape_string($con,$_POST['from_location']);
    $delivery_location = mysqli_real_escape_string($con,$_POST['to_location']);

    $pickup_lat = mysqli_real_escape_string($con,$_POST['pickup_lat']);
    $pickup_lng = mysqli_real_escape_string($con,$_POST['pickup_lng']);

    $delivery_lat = mysqli_real_escape_string($con,$_POST['delivery_lat']);
    $delivery_lng = mysqli_real_escape_string($con,$_POST['delivery_lng']);

    $pricing_type = $truck_type;

    mysqli_query($con,"
    INSERT INTO orders(

        full_name,
        phone,

        from_city,
        to_city,

        pickup_location,
        delivery_location,

        pickup_lat,
        pickup_lng,

        delivery_lat,
        delivery_lng,

        truck_type,

        price,
        driver_price,
        company_profit,
        pricing_type,

        driver_id,

        status,

        booking_type,

        scheduled_date,
        scheduled_time,

        service_type

    )VALUES(

        '$full_name',
        '$phone',

        '$from_city',
        '$to_city',

        '$pickup_location',
        '$delivery_location',

        '$pickup_lat',
        '$pickup_lng',

        '$delivery_lat',
        '$delivery_lng',

        '$truck_type',

        '$price',
        '$driver_price',
        '$company_profit',
        '$pricing_type',

        '$driver_id',

        '$status',

        '$booking_type',

        ".($scheduled_date ? "'$scheduled_date'" : "NULL").",

        ".($scheduled_time ? "'$scheduled_time'" : "NULL").",

        'intercity'

    )
    ");

    header("Location:create_order.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>
<?= $lang=='ar' ? 'إنشاء طلب جديد' : 'Create Order' ?>
</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>

:root{

--primary:#0d6efd;
--success:#198754;
--warning:#ffc107;
--danger:#dc3545;

--bg:#f4f7fb;
--card:#ffffff;

--text:#2d3436;

--border:#e5e7eb;

}

/* ===========================
   الصفحة
=========================== */

body{

margin:0;

font-family:'Tahoma',sans-serif;

background:var(--bg);

color:var(--text);

transition:.3s;

}

/* ===========================
   الوضع الليلي
=========================== */

body.dark{

background:#161b22;

color:#fff;

}

body.dark .card-box{

background:#202938;

}

body.dark .form-control,
body.dark .form-select{

background:#2b3446;

color:#fff;

border-color:#3e4a61;

}

body.dark .table{

color:#fff;

}

/* ===========================
   Header
=========================== */

.topbar{

background:linear-gradient(135deg,#0d6efd,#0b5ed7);

padding:18px 25px;

display:flex;

justify-content:space-between;

align-items:center;

color:#fff;

box-shadow:0 3px 12px rgba(0,0,0,.15);

}

.logo{

font-size:24px;

font-weight:bold;

letter-spacing:.5px;

}

/* ===========================
   Card
=========================== */

.card-box{

background:#fff;

border-radius:18px;

padding:30px;

margin-top:25px;

box-shadow:0 10px 25px rgba(0,0,0,.08);

}

/* ===========================
   Labels
=========================== */

label{

font-weight:bold;

margin-bottom:5px;

}

/* ===========================
   Inputs
=========================== */

.form-control,
.form-select{

height:48px;

border-radius:10px;

margin-bottom:15px;

}

/* ===========================
   Price Cards
=========================== */

.price-box{

margin-top:20px;

padding:15px;

background:#eef5ff;

border-radius:15px;

}

.price-card{

background:#fff;

border-radius:12px;

padding:18px;

text-align:center;

box-shadow:0 3px 8px rgba(0,0,0,.08);

height:100%;

}

.price-card i{

font-size:28px;

color:#0d6efd;

margin-bottom:10px;

}

.price-card h5{

font-size:14px;

margin-bottom:10px;

}

.price-card input{

text-align:center;

font-weight:bold;

font-size:18px;

}

/* ===========================
   الخريطة
=========================== */

#map{

height:500px;

border-radius:15px;

margin-top:20px;

border:3px solid #dbe7ff;

}

/* ===========================
   Buttons
=========================== */

.btn-save{

height:58px;

font-size:18px;

font-weight:bold;

border-radius:10px;

}

.btn-save:hover{

transform:scale(1.01);

}

/* ===========================
   Animation
=========================== */

.card-box{

animation:fade .5s;

}

@keyframes fade{

from{

opacity:0;

transform:translateY(15px);

}

to{

opacity:1;

transform:none;

}

}

</style>

</head>

<body class="<?= $theme=='dark'?'dark':'' ?>">


<div class="topbar">

<div class="logo">

🚚  <?= setting('system_name') ?>

</div>

<div>

<a class="btn btn-light btn-sm"
href="?lang=ar">

🇸🇦 عربي

</a>

<a class="btn btn-light btn-sm"
href="?lang=en">

🇺🇸 English

</a>

<a class="btn btn-warning btn-sm"
href="?theme=<?= $theme=='dark'?'light':'dark' ?>&lang=<?= $lang ?>">

<?= $theme=='dark' ? '☀️' : '🌙' ?>

</a>

</div>

</div>


<div class="container">

<?php if(isset($_GET['success'])){ ?>

<div class="alert alert-success mt-3">

✅ <?= $lang=='ar'
?'تم إنشاء الطلب بنجاح'
:'Order Saved Successfully' ?>

</div>

<?php } ?>


<div class="card-box">

<h3 class="mb-4">

<i class="bi bi-truck"></i>

<?= $lang=='ar'
?'إنشاء طلب جديد'
:'Create New Order' ?>

</h3>

<form method="POST">

<div class="row">

<div class="col-md-6">

<label>

<?= $lang=='ar'
?'اسم العميل'
:'Customer Name' ?>

</label>

<input
type="text"
name="full_name"
class="form-control"
required>

</div>


<div class="col-md-6">

<label>

<?= $lang=='ar'
?'رقم الجوال'
:'Phone Number' ?>

</label>

<input
type="text"
name="phone"
class="form-control"
required>

</div>

</div>
<!-- =========================
🏙️ المدن
========================= -->

<div class="row mt-3">

<div class="col-md-6">

<label>

🏙️

<?= $lang=='ar'
?'مدينة الانطلاق'
:'From City' ?>

</label>

<select name="from_city" id="from_city" class="form-select" required>

<option value="">اختر الانطلاق</option>

<?php
$froms = mysqli_query($con,"
SELECT DISTINCT from_city
FROM transport_pricing
ORDER BY from_city
");

while($r=mysqli_fetch_assoc($froms)){
?>
<option value="<?= $r['from_city'] ?>">
<?= $r['from_city'] ?>
</option>
<?php } ?>

</select>

</div>

<div class="col-md-6">

<label>

🏁

<?= $lang=='ar'
?'مدينة الوصول'
:'To City' ?>

</label>

<select
name="to_city"
id="to_city"
class="form-select"
required>

<option value="">

<?= $lang=='ar'
?'اختر المدينة'
:'Select City' ?>

</option>

<?php

$c=mysqli_query($con,"
SELECT DISTINCT to_city
FROM transport_pricing
ORDER BY to_city
");

while($city=mysqli_fetch_assoc($c)){

?>

<option
value="<?= $city['to_city'] ?>">

<?= $city['to_city'] ?>

</option>

<?php } ?>

</select>

</div>

</div>

<!-- =========================
🚛 نوع السطحة
========================= -->

<div class="row">

<div class="col-md-6">

<label>

🚛

<?= $lang=='ar'
?'نوع السطحة'
:'Truck Type' ?>

</label>

<select
id="truck_type"
name="truck_type"
class="form-select">

<option value="regular">

🚚
<?= $lang=='ar'
?'سطحة عادية'
:'Regular' ?>

</option>

<option value="hydraulic">

🛻
<?= $lang=='ar'
?'سطحة هيدروليك'
:'Hydraulic' ?>

</option>

<option value="covered">

🚛
<?= $lang=='ar'
?'سطحة مغطاة'
:'Covered' ?>

</option>

</select>

</div>

<div class="col-md-6">

<label>

🚗

<?= $lang=='ar'
?'نوع المركبة'
:'Vehicle Type' ?>

</label>

<select
name="car_type"
class="form-select">

<option value="sedan">
سيدان
</option>

<option value="suv">
SUV
</option>

<option value="pickup">
Pickup
</option>

<option value="van">
Van
</option>

</select>

</div>

</div>

<!-- =========================
💰 الأسعار
========================= -->

<div class="price-box">

<div class="row g-3">

<div class="col-md-4">

<div class="price-card">

<i class="bi bi-cash-stack"></i>

<h5>سعر العميل</h5>

<input
id="price"
name="price"
class="form-control"
readonly>

</div>

</div>

<div class="col-md-4">

<div class="price-card">

<i class="bi bi-truck"></i>

<h5>أجرة السائق</h5>

<input
id="driver_price"
name="driver_price"
class="form-control"
readonly>

</div>

</div>

<div class="col-md-4">

<div class="price-card">

<i class="bi bi-graph-up-arrow"></i>

<h5>ربح الشركة</h5>

<input
id="company_profit"
name="company_profit"
class="form-control"
readonly>

</div>

</div>

</div>

</div>
<!-- =========================
   📊 ملخص الرحلة
========================= -->

<div class="card-box mt-4" id="tripSummary" style="display:none;">

    <div class="row text-center">

        <div class="col-md-3">

            <h6>📍 <?= $lang=='ar'?'من':'From' ?></h6>

            <h4 id="summary_from">-</h4>

        </div>

        <div class="col-md-3">

            <h6>🏁 <?= $lang=='ar'?'إلى':'To' ?></h6>

            <h4 id="summary_to">-</h4>

        </div>

        <div class="col-md-2">

            <h6>🚛 <?= $lang=='ar'?'السطحة':'Truck' ?></h6>

            <h4 id="summary_type">-</h4>

        </div>

        <div class="col-md-2">

            <h6>💰 <?= $lang=='ar'?'السعر':'Price' ?></h6>

            <h4 id="summary_price">0</h4>

        </div>

        <div class="col-md-2">

            <h6>📈 <?= $lang=='ar'?'الربح':'Profit' ?></h6>

            <h4 id="summary_profit">0</h4>

        </div>

    </div>

</div>
<!-- =========================
📍 مواقع الانطلاق والوصول
========================= -->

<div class="row mt-4">

<div class="col-md-6">

<label>
📍 <?= $lang=='ar' ? 'موقع الانطلاق' : 'Pickup Location' ?>
</label>

<input
type="text"
id="from_location"
name="from_location"
class="form-control"
readonly>

<input type="hidden" id="pickup_lat" name="pickup_lat">
<input type="hidden" id="pickup_lng" name="pickup_lng">

</div>


<div class="col-md-6">

<label>
📍 <?= $lang=='ar' ? 'موقع الوصول' : 'Delivery Location' ?>
</label>

<input
type="text"
id="to_location"
name="to_location"
class="form-control"
readonly>

<input type="hidden" id="delivery_lat" name="delivery_lat">
<input type="hidden" id="delivery_lng" name="delivery_lng">

</div>

</div>

<!-- =========================
🗺️ الخريطة
========================= -->

<div id="map"></div>

<!-- =========================
📅 نوع الحجز
========================= -->

<div class="row mt-4">

<div class="col-md-6">

<label>

<?= $lang=='ar'
?'نوع الحجز'
:'Booking Type' ?>

</label>

<select
name="booking_type"
id="bookingType"
class="form-select">

<option value="instant">
🚀
<?= $lang=='ar'
?'فوري'
:'Instant' ?>
</option>

<option value="scheduled">
📅
<?= $lang=='ar'
?'مجدول'
:'Scheduled' ?>
</option>

</select>

</div>


<div class="col-md-6">

<label>

<?= $lang=='ar'
?'السائق'
:'Driver' ?>

</label>

<select
name="driver_id"
class="form-select">

<option value="">

<?= $lang=='ar'
?'اختر السائق'
:'Select Driver' ?>

</option>

<?php while($driver=mysqli_fetch_assoc($drivers)){ ?>

<option value="<?= $driver['id'] ?>">

<?= $driver['name'] ?>

</option>

<?php } ?>

</select>

</div>

</div>

<!-- =========================
📆 الحجز المجدول
========================= -->

<div
id="scheduleBox"
style="display:none;">

<div class="row mt-3">

<div class="col-md-6">

<label>

<?= $lang=='ar'
?'تاريخ التنفيذ'
:'Execution Date' ?>

</label>

<input
type="date"
name="scheduled_date"
class="form-control">

</div>

<div class="col-md-6">

<label>

<?= $lang=='ar'
?'وقت التنفيذ'
:'Execution Time' ?>

</label>

<input
type="time"
name="scheduled_time"
class="form-control">

</div>

</div>

</div>

<!-- =========================
📋 الحالة
========================= -->

<label class="mt-3">

<?= $lang=='ar'
?'الحالة'
:'Status' ?>

</label>

<select
name="status"
class="form-select">

<option value="pending">

⏳
<?= $lang=='ar'
?'معلق'
:'Pending' ?>

</option>

<option value="assigned">

🚚
<?= $lang=='ar'
?'تم التعيين'
:'Assigned' ?>

</option>

<option value="done">

✅
<?= $lang=='ar'
?'مكتمل'
:'Completed' ?>

</option>

<option value="cancelled">

❌
<?= $lang=='ar'
?'ملغي'
:'Cancelled' ?>

</option>

</select>

<hr>

<button
type="submit"
name="save"
class="btn btn-success btn-save w-100">

💾

<?= $lang=='ar'
?'حفظ الطلب'
:'Save Order' ?>

</button>

</form>

</div>

</div>
<script>

// =========================
// الحجز المجدول
// =========================

const bookingType=document.getElementById("bookingType");
const scheduleBox=document.getElementById("scheduleBox");

function toggleSchedule(){

if(bookingType.value=="scheduled"){

scheduleBox.style.display="block";

}else{

scheduleBox.style.display="none";

}

}

toggleSchedule();

bookingType.addEventListener("change",toggleSchedule);

// =========================
// إنشاء الخريطة
// =========================

var map=L.map("map").setView([24.7136,46.6753],6);

L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
{
attribution:'© OpenStreetMap'
}).addTo(map);

let pickupMarker=null;
let deliveryMarker=null;
let routeLine=null;

// =========================
// اختيار المواقع
// =========================

map.on("click",function(e){

const lat=e.latlng.lat;
const lng=e.latlng.lng;

const text=lat+","+lng;

// نقطة الانطلاق

if(pickupMarker==null){

pickupMarker=L.marker([lat,lng]).addTo(map);

document.getElementById("from_location").value=text;

document.getElementById("pickup_lat").value=lat;

document.getElementById("pickup_lng").value=lng;

return;

}

// نقطة الوصول

if(deliveryMarker==null){

deliveryMarker=L.marker([lat,lng]).addTo(map);

document.getElementById("to_location").value=text;

document.getElementById("delivery_lat").value=lat;

document.getElementById("delivery_lng").value=lng;

routeLine=L.polyline([
pickupMarker.getLatLng(),
deliveryMarker.getLatLng()
]).addTo(map);

map.fitBounds(routeLine.getBounds());

return;

}

// إعادة الاختيار

map.removeLayer(pickupMarker);
map.removeLayer(deliveryMarker);

if(routeLine){

map.removeLayer(routeLine);

}

pickupMarker=null;
deliveryMarker=null;

document.getElementById("from_location").value="";
document.getElementById("to_location").value="";

document.getElementById("pickup_lat").value="";
document.getElementById("pickup_lng").value="";

document.getElementById("delivery_lat").value="";
document.getElementById("delivery_lng").value="";

});

// =========================
// جلب الأسعار تلقائياً
// =========================

function loadPrice(){

let from=document.getElementById("from_city").value;
let to=document.getElementById("to_city").value;
let type=document.getElementById("truck_type").value;

if(from=="" || to=="") return;

fetch(
"get_transport_price.php?from_city="+encodeURIComponent(from)
+"&to_city="+encodeURIComponent(to)
+"&truck_type="+encodeURIComponent(type)
)

.then(r=>r.json())

.then(function(res){

if(res.status==1){

document.getElementById("price").value=res.customer;

document.getElementById("driver_price").value=res.driver;

document.getElementById("company_profit").value=res.profit;

}else{

document.getElementById("price").value="";
document.getElementById("driver_price").value="";
document.getElementById("company_profit").value="";

alert("لا يوجد تسعير لهذا المسار");

}

});

}

document.getElementById("from_city").addEventListener("change",loadPrice);
document.getElementById("to_city").addEventListener("change",loadPrice);
document.getElementById("truck_type").addEventListener("change",loadPrice);

</script>

</body>
</html>