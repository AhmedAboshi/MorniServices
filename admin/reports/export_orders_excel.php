<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

include __DIR__ . '/../../include/connected.php';
include __DIR__ . '/../../include/settings.php';

/*=============================
    Excel Headers
=============================*/

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Orders_".date("Y-m-d").".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

/*=============================
    Query
=============================*/

$query = mysqli_query($con,"
SELECT
orders.*,
drivers.name AS driver_name
FROM orders
LEFT JOIN drivers
ON drivers.id = orders.driver_id
ORDER BY orders.id DESC
");
?>

<table border="1">

<tr style="background:#0d6efd;color:#ffffff;">

<th>#</th>
<th>رقم الطلب</th>
<th>العميل</th>
<th>الجوال</th>
<th>مدينة الانطلاق</th>
<th>مدينة الوصول</th>
<th>السعر</th>
<th>السائق</th>
<th>الحالة</th>
<th>نوع الحجز</th>
<th>تاريخ الطلب</th>

</tr>

<?php while($row=mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?= $row['id'] ?></td>

<td>#<?= $row['id'] ?></td>

<td><?= htmlspecialchars($row['full_name']) ?></td>

<td><?= htmlspecialchars($row['phone']) ?></td>

<td><?= htmlspecialchars($row['from_city']) ?></td>

<td><?= htmlspecialchars($row['to_city']) ?></td>

<td><?= number_format($row['price'],2) ?></td>

<td>

<?= !empty($row['driver_name'])
? htmlspecialchars($row['driver_name'])
: 'غير معين'
?>

</td>

<td><?= htmlspecialchars($row['status']) ?></td>

<td>

<?= $row['booking_type']=="instant"
? "فوري"
: "مجدول" ?>

</td>

<td><?= $row['created_at'] ?></td>

</tr>

<?php } ?>

</table>