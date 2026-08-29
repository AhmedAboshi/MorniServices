<?php
session_start();
include('../include/connected.php');

$lang = $_SESSION['lang'] ?? 'ar';
$dark = isset($_GET['dark']);

$sql = "
SELECT
    t.*,
    f.plate,
    t.driver AS driver_name
FROM tires t
LEFT JOIN fleet f ON t.car_id = f.id
ORDER BY t.id DESC
";

$res = $con->query($sql);

$data=[];
$late=0;
$soon=0;
$ok=0;

while($row=$res->fetch_assoc()){

    $daysLeft = ceil(
        (strtotime($row['next_change']) - time())
        /86400
    );

    if($daysLeft < 0){
        $late++;
    }
    elseif($daysLeft <= 30){
        $soon++;
    }
    else{
        $ok++;
    }

    $data[]=$row;
}

$total=count($data);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>إدارة الإطارات</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:<?= $dark ? '#121212' : '#f4f6f9' ?>;
color:<?= $dark ? '#fff' : '#000' ?>;
}

.card{
background:<?= $dark ? '#1f1f1f' : '#fff' ?>;
border:none;
border-radius:15px;
}

.table{
color:<?= $dark ? '#fff' : '#000' ?>;
}

.stat{
padding:20px;
border-radius:15px;
color:#fff;
font-size:18px;
font-weight:bold;
}

</style>

</head>

<body>

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between mb-4">

<h2>🛞 إدارة الإطارات</h2>

<div>

<a href="?lang=ar" class="btn btn-primary btn-sm">
AR
</a>

<a href="?lang=en" class="btn btn-secondary btn-sm">
EN
</a>

<a href="?dark=1" class="btn btn-dark btn-sm">
🌙
</a>

<a href="tire.php" class="btn btn-light btn-sm">
☀
</a>

</div>

</div>

<!-- الإحصائيات -->

<div class="row mb-4">

<div class="col-md-3">
<div class="stat bg-primary">
الإجمالي
<br>
<h3><?= $total ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="stat bg-danger">
متأخر
<br>
<h3><?= $late ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="stat bg-warning">
قريب
<br>
<h3><?= $soon ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="stat bg-success">
ممتاز
<br>
<h3><?= $ok ?></h3>
</div>
</div>

</div>

<!-- البحث -->

<div class="card p-3 mb-3">

<input
type="text"
id="search"
class="form-control"
placeholder="بحث ..."
>

</div>

<!-- زر الإضافة -->

<a href="add_tire.php"
class="btn btn-success mb-3">

➕ إضافة إطار

</a>

<!-- الجدول -->

<div class="card p-3">

<table
class="table table-bordered table-hover text-center align-middle"
id="table">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>السائق</th>
<th>اللوحة</th>
<th>نوع الإطار</th>
<th>مكان الإطار</th>
<th>آخر تغيير</th>
<th>التغيير القادم</th>
<th>التكلفة</th>
<th>الملاحظات</th>
<th>المتبقي</th>
<th>الحالة</th>
<th>العمليات</th>

</tr>

</thead>

<tbody>

<?php foreach($data as $row):

$daysLeft = ceil(
(strtotime($row['next_change'])-time())
/86400
);

if($daysLeft < 0){

$status='متأخر';
$badge='danger';

}elseif($daysLeft <=30){

$status='قريب';
$badge='warning';

}else{

$status='ممتاز';
$badge='success';

}

?>

<tr>

<td><?= $row['id'] ?></td>

<td>
    <?= htmlspecialchars($row['driver_name'] ?? 'بدون سائق') ?>
</td>

<td><?= $row['plate'] ?></td>

<td><?= $row['tire_type'] ?></td>

<td>
    <?= htmlspecialchars($row['tire_position'] ?? '-') ?>
</td>

<td><?= $row['change_date'] ?></td>

<td><?= $row['next_change'] ?></td>

<td>
<?= number_format($row['cost'],2) ?>
ريال
</td>
<td>
    <?= !empty($row['notes'])
        ? htmlspecialchars($row['notes'])
        : 'لا توجد ملاحظات'
    ?>
</td>

<td>

<?= $daysLeft < 0
? 'منتهي'
: $daysLeft.' يوم'
?>

</td>

<td>

<span class="badge bg-<?= $badge ?>">
<?= $status ?>
</span>

</td>

<td>

<a
href="edit_tire.php?id=<?= $row['id'] ?>"
class="btn btn-warning btn-sm">
✏
</a>

<a
href="delete_tire.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('حذف السجل؟')">
🗑
</a>

<a
href="tire_details.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm">
👁
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

<script>

document.getElementById('search')
.addEventListener('keyup',function(){

let value=this.value.toLowerCase();

let rows=document.querySelectorAll(
'#table tbody tr'
);

rows.forEach(row=>{

row.style.display=
row.innerText.toLowerCase()
.includes(value)
?
''
:
'none';

});

});

</script>

</body>
</html>