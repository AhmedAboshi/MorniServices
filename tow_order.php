<?php
session_start();
include('include/connected.php');

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

/* =========================
   حفظ الطلب + الفاتورة
========================= */
if (isset($_POST['orderadd'])) {

    $user_id = $_SESSION['user_id'];

    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];

    $from_city = $_POST['from_city'];
    $to_city   = $_POST['to_city'];

    $from_lat = $_POST['from_lat'];
    $from_lng = $_POST['from_lng'];
    $to_lat   = $_POST['to_lat'];
    $to_lng   = $_POST['to_lng'];

    $car_type = $_POST['car_type'];

    /* 💳 طريقة الدفع */
    $payment_method = $_POST['payment_method'] ?? 'cash';

    if (!$from_lat || !$to_lat) {
        die("❌ يجب تحديد المواقع من الخريطة");
    }

    /* =========================
       حساب المسافة
    ========================= */
    function distance($lat1,$lon1,$lat2,$lon2){
        $R = 6371;
        $dLat = deg2rad($lat2-$lat1);
        $dLon = deg2rad($lon2-$lon1);

        $a = sin($dLat/2)**2 +
             cos(deg2rad($lat1))*cos(deg2rad($lat2)) *
             sin($dLon/2)**2;

        $c = 2*atan2(sqrt($a),sqrt(1-$a));
        return $R*$c;
    }

    $dist = distance($from_lat,$from_lng,$to_lat,$to_lng);

    /* =========================
       التسعير
    ========================= */
    $base_price = 50;
    $per_km = 1.5;

    if ($car_type == "normal") $extra = 150;
    elseif ($car_type == "covered") $extra = 1250;
    elseif ($car_type == "hydraulic") $extra = 300;
    else $extra = 0;

    $price = ($dist * $per_km) + $base_price + $extra;

    /* =========================
       1️⃣ إنشاء الطلب
    ========================= */
    mysqli_query($con,"
    INSERT INTO orders
    (full_name, phone,
    from_city, to_city,
    pickup_lat, pickup_lng,
    delivery_lat, delivery_lng,
    car_type, distance, price,
    order_type, status, user_id, payment_method)

    VALUES
    ('$full_name','$phone',
    '$from_city','$to_city',
    '$from_lat','$from_lng',
    '$to_lat','$to_lng',
    '$car_type','$dist','$price',
    'intercity','pending','$user_id','$payment_method')
    ");

    $order_id = mysqli_insert_id($con);

    /* =========================
       2️⃣ إنشاء الفاتورة
    ========================= */
    $invoice_number = 'INV-' . time();

    mysqli_query($con,"
    INSERT INTO invoices
    (order_id, order_type, invoice_number, total, vat, total_with_vat, payment_method)
    VALUES
    ('$order_id','intercity','$invoice_number','$price',0,'$price','$payment_method')
    ");

    $invoice_id = mysqli_insert_id($con);

    /* =========================
       3️⃣ تحويل لصفحة النجاح
    ========================= */
    header("Location: invoice.php?id=$invoice_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>طلب سطحة</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
body{font-family:Arial;background:#f5f7fb}
.container{max-width:600px;margin:auto;background:#fff;padding:20px;border-radius:15px}
input,select{width:100%;padding:10px;margin:5px 0}
button{width:100%;padding:15px;background:#007bff;color:#fff;border:none}
#map{height:300px;margin:10px 0}
</style>
</head>

<body>

<div class="container">

<h2>🚚 طلب سطحة بين المدن</h2>

<form method="POST">

<input type="text" name="full_name" placeholder="الاسم" required>
<input type="text" name="phone" placeholder="الجوال" required>

<select name="from_city" required>
    <option value="">مدينة التحميل</option>
    <option>الرياض</option>
    <option>جدة</option>
    <option>الدمام</option>
</select>

<select name="to_city" required>
    <option value="">مدينة التنزيل</option>
    <option>الرياض</option>
    <option>جدة</option>
    <option>الدمام</option>
</select>

<select name="car_type" required>
    <option value="">نوع السطحة</option>
    <option value="normal">عادية +150</option>
    <option value="covered">مغلقة +1250</option>
    <option value="hydraulic">هيدروليك +300</option>
</select>

<!-- 💳 الدفع -->
<h3>💳 طريقة الدفع</h3>
<select name="payment_method" required>
    <option value="cash">كاش عند الاستلام</option>
    <option value="card">بطاقة بنكية</option>
    <option value="bank">تحويل بنكي</option>
</select>

<div id="map"></div>

<input type="hidden" name="from_lat" id="from_lat">
<input type="hidden" name="from_lng" id="from_lng">
<input type="hidden" name="to_lat" id="to_lat">
<input type="hidden" name="to_lng" id="to_lng">

<button type="submit" name="orderadd">تأكيد الطلب 🚚</button>

</form>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map = L.map('map').setView([24.7,46.7],6);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap'
}).addTo(map);

let a,b,lat1,lng1,lat2,lng2;

map.on('click', function(e){

    if(!a){
        a = L.marker(e.latlng).addTo(map);
        lat1 = e.latlng.lat;
        lng1 = e.latlng.lng;

        document.getElementById("from_lat").value = lat1;
        document.getElementById("from_lng").value = lng1;

    } else {
        b = L.marker(e.latlng).addTo(map);
        lat2 = e.latlng.lat;
        lng2 = e.latlng.lng;

        document.getElementById("to_lat").value = lat2;
        document.getElementById("to_lng").value = lng2;
    }
});
</script>

</body>
</html>