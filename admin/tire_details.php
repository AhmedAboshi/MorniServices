<?php
session_start();
include('../include/connected.php');

/*=========================
  اللغة
=========================*/
if(isset($_GET['lang'])){
    $_SESSION['lang']=$_GET['lang'];
}
$lang=$_SESSION['lang'] ?? 'ar';

/*=========================
  الوضع الليلي
=========================*/
if(isset($_GET['theme'])){
    $_SESSION['theme']=$_GET['theme'];
}
$dark=$_SESSION['theme'] ?? 0;

/*=========================
  الترجمة
=========================*/
$text=[

'ar'=>[
'title'=>'تفاصيل الإطار',
'plate'=>'رقم اللوحة',
'driver'=>'السائق',
'type'=>'نوع الإطار',
'position'=>'مكان الإطار',
'change_date'=>'تاريخ التركيب',
'current'=>'العداد الحالي',
'next'=>'العداد القادم',
'next_date'=>'التاريخ القادم',
'cost'=>'التكلفة',
'notes'=>'الملاحظات',
'status'=>'الحالة',
'remaining'=>'المتبقي',
'edit'=>'تعديل',
'delete'=>'حذف',
'back'=>'رجوع',
'day'=>'يوم',
'expired'=>'منتهي',
'good'=>'ممتاز',
'soon'=>'قريب',
'late'=>'متأخر',
],

'en'=>[
'title'=>'Tire Details',
'plate'=>'Plate',
'driver'=>'Driver',
'type'=>'Tire Type',
'position'=>'Tire Position',
'change_date'=>'Install Date',
'current'=>'Current KM',
'next'=>'Next KM',
'next_date'=>'Next Change',
'cost'=>'Cost',
'notes'=>'Notes',
'status'=>'Status',
'remaining'=>'Remaining',
'edit'=>'Edit',
'delete'=>'Delete',
'back'=>'Back',
'day'=>'Day',
'expired'=>'Expired',
'good'=>'Good',
'soon'=>'Soon',
'late'=>'Late',
]

];

$t=$text[$lang];

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("Invalid ID");
}

$id=(int)$_GET['id'];

$stmt=$con->prepare("
SELECT
t.*,
f.plate,
d.name AS driver_name
FROM tires t
LEFT JOIN fleet f ON t.car_id=f.id
LEFT JOIN drivers d ON t.driver_id=d.id
WHERE t.id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$res=$stmt->get_result();

if($res->num_rows==0){
    die("No Data");
}

$row=$res->fetch_assoc();

$daysLeft=ceil(
(strtotime($row['next_change'])-time())/86400
);

if($daysLeft<0){

$status=$t['late'];
$badge="danger";

}elseif($daysLeft<=30){

$status=$t['soon'];
$badge="warning";

}else{

$status=$t['good'];
$badge="success";

}
?>

<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title><?= $t['title'] ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{

background:<?= $dark ? '#121212':'#f4f6f9' ?>;

color:<?= $dark ? '#fff':'#000' ?>;

}

.card{

background:<?= $dark ? '#1f1f1f':'#fff' ?>;

border:none;

border-radius:18px;

box-shadow:0 5px 20px rgba(0,0,0,.15);

}

.table{

color:inherit;

}

.table td{

padding:14px;

font-size:16px;

}

</style>

</head>

<body>

<div class="container mt-4">

<div class="d-flex justify-content-between mb-3">

<h3>🛞 <?= $t['title'] ?></h3>

<div>

<a href="?id=<?= $id ?>&lang=ar" class="btn btn-primary btn-sm">AR</a>

<a href="?id=<?= $id ?>&lang=en" class="btn btn-secondary btn-sm">EN</a>

<a href="?id=<?= $id ?>&theme=1" class="btn btn-dark btn-sm">🌙</a>

<a href="?id=<?= $id ?>&theme=0" class="btn btn-light btn-sm">☀</a>

</div>

</div>

<div class="card">

<div class="card-body">

<table class="table table-bordered">

<tr>
<th width="30%"><?= $t['plate'] ?></th>
<td><?= htmlspecialchars($row['plate']) ?></td>
</tr>

<tr>
<th><?= $t['driver'] ?></th>
<td><?= htmlspecialchars($row['driver_name']) ?></td>
</tr>

<tr>
    <th><?= $t['type'] ?></th>
    <td>
        <?= htmlspecialchars($row['tire_type'] ?? '-') ?>
    </td>
</tr>

<tr>
    <th>🛞 <?= $t['position'] ?></th>
    <td>
        <?php
        $position = $row['tire_position'] ?? '';

        $positionText = [
            'أمامي يمين' => 'أمامي يمين',
            'أمامي يسار' => 'أمامي يسار',
            'خلفي يمين' => 'خلفي يمين',
            'خلفي يسار' => 'خلفي يسار',
            'احتياطي'   => 'احتياطي'
        ];

        echo htmlspecialchars(
            $positionText[$position] ?? ($position ?: '-')
        );
        ?>
    </td>
</tr>

<tr>
<th><?= $t['change_date'] ?></th>
<td><?= $row['change_date'] ?></td>
</tr>

<tr>
<th><?= $t['current'] ?></th>
<td><?= number_format($row['current_km']) ?> KM</td>
</tr>

<tr>
<th><?= $t['next'] ?></th>
<td><?= number_format($row['next_km']) ?> KM</td>
</tr>

<tr>
<th><?= $t['next_date'] ?></th>
<td><?= $row['next_change'] ?></td>
</tr>

<tr>
<th><?= $t['remaining'] ?></th>
<td>

<?=

$daysLeft<0

?

$t['expired']

:

$daysLeft." ".$t['day']

?>

</td>

</tr>

<tr>

<th><?= $t['status'] ?></th>

<td>

<span class="badge bg-<?= $badge ?>">

<?= $status ?>

</span>

</td>

</tr>

<tr>

<th><?= $t['cost'] ?></th>

<td>

<?= number_format($row['cost'],2) ?> ريال

</td>

</tr>

<tr>

<th><?= $t['notes'] ?></th>

<td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>

</tr>

</table>

<div class="text-center mt-4">

<a href="edit_tire.php?id=<?= $id ?>" class="btn btn-warning">

✏ <?= $t['edit'] ?>

</a>

<a href="delete_tire.php?id=<?= $id ?>"
class="btn btn-danger"
onclick="return confirm('هل تريد حذف السجل؟');">

🗑 <?= $t['delete'] ?>

</a>

<a href="tire.php" class="btn btn-secondary">

⬅ <?= $t['back'] ?>

</a>

</div>

</div>

</div>

</div>

</body>

</html>