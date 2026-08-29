<?php

include('../include/core.php');
include('../include/connected.php');

/* =========================
   جلب الطلب
========================= */

$id = (int)($_GET['id'] ?? 0);

$query = mysqli_query($con,"
SELECT * FROM orders
WHERE id='$id'
");

$order = mysqli_fetch_assoc($query);

if(!$order){
    die("الطلب غير موجود");
}

/* =========================
   السائقين
========================= */

$drivers = mysqli_query($con,"
SELECT * FROM drivers
ORDER BY id DESC
");

/* =========================
   حفظ التعديل
========================= */

if(isset($_POST['save'])){

    $full_name = mysqli_real_escape_string($con,$_POST['full_name']);
    $phone = mysqli_real_escape_string($con,$_POST['phone']);

    $from_city = mysqli_real_escape_string($con,$_POST['from_city']);
    $to_city = mysqli_real_escape_string($con,$_POST['to_city']);

    $price = mysqli_real_escape_string($con,$_POST['price']);

    $status = mysqli_real_escape_string($con,$_POST['status']);

    $driver_id = (int)$_POST['driver_id'];

    $booking_type = mysqli_real_escape_string($con,$_POST['booking_type']);

    $scheduled_date = $_POST['scheduled_date'] ?? NULL;

    $scheduled_time = $_POST['scheduled_time'] ?? NULL;

    mysqli_query($con,"

    UPDATE orders SET

    full_name='$full_name',
    phone='$phone',

    from_city='$from_city',
    to_city='$to_city',

    price='$price',

    status='$status',

    driver_id='$driver_id',

    booking_type='$booking_type',

    scheduled_date='$scheduled_date',

    scheduled_time='$scheduled_time'

    WHERE id='$id'

    ");

    header("Location: ordersview.php?success=1");
    exit();
}

?>

<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>تعديل الطلب</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<link rel="stylesheet" href="assets/dark-mode.css">

<style>

body{
    background:#f5f5f5;
}

.card-box{

    background:#fff;

    padding:25px;

    border-radius:15px;

    margin-top:30px;

    box-shadow:0 0 10px rgba(0,0,0,.1);
}

.form-control,
.form-select{

    margin-bottom:15px;
}

.schedule-box{
    display:none;
}

</style>

</head>

<body>

<div class="container">

<div class="card-box">

<div class="d-flex justify-content-between align-items-center mb-3">

<h3>🚚 تعديل الطلب</h3>

<div>

<a href="?id=<?= $id ?>&lang=ar">🇸🇦 عربي</a>

<a href="?id=<?= $id ?>&lang=en">🇬🇧 English</a>

<button onclick="toggleDarkMode()" class="dark-btn">
🌙
</button>

</div>

</div>

<form method="POST">

<label>اسم العميل</label>

<input type="text"
name="full_name"
class="form-control"
value="<?= htmlspecialchars($order['full_name']) ?>">

<label>الجوال</label>

<input type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($order['phone']) ?>">

<label>مدينة الانطلاق</label>

<input type="text"
name="from_city"
class="form-control"
value="<?= htmlspecialchars($order['from_city']) ?>">

<label>مدينة الوصول</label>

<input type="text"
name="to_city"
class="form-control"
value="<?= htmlspecialchars($order['to_city']) ?>">

<label>السعر</label>

<input type="text"
name="price"
class="form-control"
value="<?= htmlspecialchars($order['price']) ?>">

<label>الحالة</label>

<select name="status" class="form-select">

<option value="pending"
<?= $order['status']=='pending'?'selected':'' ?>>
⏳ معلق
</option>

<option value="assigned"
<?= $order['status']=='assigned'?'selected':'' ?>>
🚚 تم التعيين
</option>

<option value="done"
<?= $order['status']=='done'?'selected':'' ?>>
✅ مكتمل
</option>

<option value="cancelled"
<?= $order['status']=='cancelled'?'selected':'' ?>>
❌ ملغي
</option>

</select>

<label>السائق</label>

<select name="driver_id" class="form-select">

<option value="">🚚 اختر سائق</option>

<?php while($driver = mysqli_fetch_assoc($drivers)){ ?>

<option value="<?= $driver['id'] ?>"

<?= $order['driver_id']==$driver['id'] ? 'selected' : '' ?>>

<?= $driver['name'] ?>

</option>

<?php } ?>

</select>

<label>نوع الحجز</label>

<select name="booking_type"
id="bookingType"
class="form-select">

<option value="instant"
<?= $order['booking_type']=='instant'?'selected':'' ?>>

🚀 فوري

</option>

<option value="scheduled"
<?= $order['booking_type']=='scheduled'?'selected':'' ?>>

📅 مجدول

</option>

</select>

<div id="scheduleBox" class="schedule-box">

<label>تاريخ الحجز</label>

<input type="date"
name="scheduled_date"
class="form-control"
value="<?= $order['scheduled_date'] ?>">

<label>وقت الحجز</label>

<input type="time"
name="scheduled_time"
class="form-control"
value="<?= $order['scheduled_time'] ?>">

</div>

<button type="submit"
name="save"
class="btn btn-success w-100">

💾 حفظ التعديلات

</button>

</form>

</div>

</div>

<script>

const bookingType =
document.getElementById('bookingType');

const scheduleBox =
document.getElementById('scheduleBox');

function toggleSchedule(){

if(bookingType.value === 'scheduled'){

    scheduleBox.style.display = 'block';

}else{

    scheduleBox.style.display = 'none';

}

}

toggleSchedule();

bookingType.addEventListener('change',toggleSchedule);

</script>

<script src="assets/dark-mode.js"></script>

</body>
</html>