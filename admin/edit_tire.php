<?php
session_start();
include('../include/connected.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("رقم السجل غير صحيح");
}

if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

$text = [

'ar'=>[
'title'=>'تعديل الإطار',
'car'=>'المركبة',
'driver'=>'السائق',
'type'=>'نوع الإطار',
'change_date'=>'تاريخ التركيب',
'current'=>'العداد الحالي',
'next'=>'العداد القادم',
'next_date'=>'التاريخ القادم',
'cost'=>'التكلفة',
'notes'=>'الملاحظات',
'save'=>'حفظ',
'back'=>'رجوع',
],

'en'=>[
'title'=>'Edit Tire',
'car'=>'Vehicle',
'driver'=>'Driver',
'type'=>'Tire Type',
'change_date'=>'Install Date',
'current'=>'Current KM',
'next'=>'Next KM',
'next_date'=>'Next Date',
'cost'=>'Cost',
'notes'=>'Notes',
'save'=>'Save',
'back'=>'Back',
]

];

/* هذا السطر يجب أن يكون بعد إنشاء المصفوفة */
$t = $text[$lang];

if(isset($_GET['theme'])){
    $_SESSION['theme'] = $_GET['theme'];
}

$dark = $_SESSION['theme'] ?? 0;

$id = (int)$_GET['id'];

/*=========================
  جلب بيانات الإطار
=========================*/
$stmt = $con->prepare("
SELECT *
FROM tires
WHERE id=?
LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$result=$stmt->get_result();

if($result->num_rows==0){
    die("السجل غير موجود");
}

$tire=$result->fetch_assoc();


/*=========================
  المركبات
=========================*/
$fleet=$con->query("
SELECT
id,
plate,
driver
FROM fleet
ORDER BY plate ASC
");


/*=========================
  السائقين
=========================*/
$drivers=$con->query("
SELECT
id,
name
FROM drivers
ORDER BY name ASC
");


/*=========================
  حفظ التعديل
=========================*/

if(isset($_POST['save'])){

$car_id        = (int)$_POST['car_id'];
$driver_id     = (int)$_POST['driver_id'];

$tire_type     = trim($_POST['tire_type']);
$change_date   = $_POST['change_date'];

$current_km    = (int)$_POST['current_km'];
$next_km       = (int)$_POST['next_km'];

$next_change   = $_POST['next_change'];

$cost          = $_POST['cost'];

$notes         = trim($_POST['notes']);

$tire_position = trim($_POST['tire_position'] ?? '');

if ($tire_position === '') {
    $error = 'يرجى اختيار مكان الإطار.';
}
/* اسم السائق */

$nameStmt=$con->prepare("
SELECT name
FROM drivers
WHERE id=?
");

$nameStmt->bind_param("i",$driver_id);
$nameStmt->execute();

$nameRes=$nameStmt->get_result();

$driver='';

if($nameRes->num_rows){

    $driver=$nameRes->fetch_assoc()['name'];

}


/* =========================
   تحديث بيانات الإطار
========================= */

$update = $con->prepare("

    UPDATE tires SET

        car_id=?,
        driver=?,
        driver_id=?,
        tire_type=?,
        tire_position=?,
        change_date=?,
        current_km=?,
        next_km=?,
        next_change=?,
        notes=?,
        cost=?

    WHERE id=?

");


$update->bind_param(

    "isisssiissdi",

    $car_id,          // i
    $driver,          // s
    $driver_id,       // i
    $tire_type,       // s
    $tire_position,   // s
    $change_date,     // s
    $current_km,      // i
    $next_km,         // i
    $next_change,     // s
    $notes,           // s
    $cost,            // d
    $id               // i

);


if ($update->execute()) {

    echo "<script>

        alert('تم تحديث بيانات الإطار بنجاح');

        location='tire.php';

    </script>";

    exit;

} else {

    echo "<div class='alert alert-danger'>
        حدث خطأ أثناء حفظ بيانات الإطار
    </div>";

}
}
?>

    
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>تعديل الإطار</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{

background:<?= $dark ? '#121212':'#f4f6f9' ?>;

color:<?= $dark ? '#fff':'#000' ?>;

}

.card{

background:<?= $dark ? '#1e1e1e':'#fff' ?>;

border:none;

border-radius:15px;

}

.form-control,
.form-select{

background:<?= $dark ? '#2a2a2a':'#fff' ?>;

color:<?= $dark ? '#fff':'#000' ?>;

border-color:#666;

}
</style>

</head>

<body>

<div class="container mt-4">
<div class="d-flex justify-content-between mb-3">

<h3>

🛞 <?= $t['title'] ?>

</h3>

<div>

<a href="?id=<?= $id ?>&lang=ar"
class="btn btn-primary btn-sm">

AR

</a>

<a href="?id=<?= $id ?>&lang=en"
class="btn btn-secondary btn-sm">

EN

</a>

<a href="?id=<?= $id ?>&theme=1"
class="btn btn-dark btn-sm">

🌙

</a>

<a href="?id=<?= $id ?>&theme=0"
class="btn btn-light btn-sm">

☀

</a>

</div>

</div>
<div class="card">

<div class="card-header bg-warning text-dark">

<h4 class="mb-0">
✏ <?= $t['title'] ?>
</h4>

</div>

<div class="card-body">

<form method="post">

<div class="row">

<!-- المركبة -->

<div class="col-md-6 mb-3">

<label class="form-label">
<?= $t['car'] ?>
</label>

<select
name="car_id"
class="form-select"
required>

<?php

$fleet->data_seek(0);

while($car=$fleet->fetch_assoc()){

?>

<option
value="<?= $car['id']; ?>"

<?= $tire['car_id']==$car['id'] ? 'selected':'' ?>

>

<?= $car['plate']; ?>

</option>

<?php } ?>

</select>

</div>

<!-- السائق -->

<div class="col-md-6 mb-3">

<label class="form-label">

<?= $t['driver'] ?>

</label>

<select
name="driver_id"
class="form-select"
required>

<?php

$drivers->data_seek(0);

while($dr=$drivers->fetch_assoc()){

?>

<option
value="<?= $dr['id']; ?>"

<?= $tire['driver_id']==$dr['id'] ? 'selected':'' ?>

>

<?= $dr['name']; ?>

</option>

<?php } ?>

</select>

</div>

<!-- نوع الإطار -->

<div class="col-md-6 mb-3">

<label>

<?= $t['type'] ?>

</label>

<input
type="text"
name="tire_type"
class="form-control"
required

value="<?= htmlspecialchars($tire['tire_type']) ?>">

</div>

<div class="mb-3">

    <label class="form-label">
        مكان الإطار
    </label>

    <select
        name="tire_position"
        class="form-select"
        required
    >

        <option value="">
            -- اختر مكان الإطار --
        </option>

        <option
            value="أمامي يمين"
            <?= ($tire['tire_position'] ?? '') === 'أمامي يمين' ? 'selected' : '' ?>
        >
            أمامي يمين
        </option>

        <option
            value="أمامي يسار"
            <?= ($tire['tire_position'] ?? '') === 'أمامي يسار' ? 'selected' : '' ?>
        >
            أمامي يسار
        </option>

        <option
            value="خلفي يمين"
            <?= ($tire['tire_position'] ?? '') === 'خلفي يمين' ? 'selected' : '' ?>
        >
            خلفي يمين
        </option>

        <option
            value="خلفي يسار"
            <?= ($tire['tire_position'] ?? '') === 'خلفي يسار' ? 'selected' : '' ?>
        >
            خلفي يسار
        </option>

        <option
            value="احتياطي"
            <?= ($tire['tire_position'] ?? '') === 'احتياطي' ? 'selected' : '' ?>
        >
            احتياطي
        </option>

    </select>

</div>

<!-- تاريخ التغيير -->

<div class="col-md-6 mb-3">

<label>

<?= $t['change_date'] ?>

</label>

<input
type="date"
name="change_date"
class="form-control"

value="<?= $tire['change_date'] ?>">

</div>

<!-- العداد الحالي -->

<div class="col-md-6 mb-3">

<label>

<?= $t['current'] ?>

</label>

<input
type="number"
name="current_km"
class="form-control"

value="<?= $tire['current_km'] ?>">

</div>

<!-- العداد القادم -->

<div class="col-md-6 mb-3">

<label>

<?= $t['next'] ?>
 (KM)

</label>

<input
type="number"
name="next_km"
class="form-control"

value="<?= $tire['next_km'] ?>">

</div>

<!-- التاريخ القادم -->

<div class="col-md-6 mb-3">

<label>

<?= $t['next_date'] ?>

</label>

<input
type="date"
name="next_change"
class="form-control"

value="<?= $tire['next_change'] ?>">

</div>

<!-- التكلفة -->

<div class="col-md-6 mb-3">

<label>

<?= $t['cost'] ?>


</label>

<input
type="number"
step="0.01"
name="cost"
class="form-control"

value="<?= $tire['cost'] ?>">

</div>

<!-- الملاحظات -->

<div class="col-md-12 mb-3">

<label>

<?= $t['notes'] ?>


</label>

<textarea
name="notes"
rows="4"
class="form-control"><?= htmlspecialchars($tire['notes']) ?></textarea>

</div>

</div>

<div class="text-center">

<button
type="submit"
name="save"
class="btn btn-success">

💾  <?= $t['save'] ?>


</button>

<a
href="tire.php"
class="btn btn-secondary">

⬅ <?= $t['back'] ?>

</a>

</div>

</form>

</div>

</div>

</div>

</body>

</html>