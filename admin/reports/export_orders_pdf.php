<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

include __DIR__ . '/../../include/connected.php';
include __DIR__ . '/../../include/settings.php';

use Mpdf\Mpdf;

// if(!isset($_SESSION['admin_id'])){
//     header("Location: login.php");
//     exit();
// }


// =========================
// فلاتر التقرير
// =========================

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$status    = $_GET['status'] ?? '';


$where = " WHERE 1 ";


if(!empty($from_date)){
    $where .= " AND DATE(o.created_at) >= '$from_date' ";
}


if(!empty($to_date)){
    $where .= " AND DATE(o.created_at) <= '$to_date' ";
}


if(!empty($status)){
    $where .= " AND o.status = '$status' ";
}


// =========================
// فلاتر التقرير
// =========================

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$status    = $_GET['status'] ?? '';
$driver_id = $_GET['driver_id'] ?? '';

$driver_name = '';

if(!empty($driver_id)){

    $driver_query = mysqli_query($con,"
    SELECT name 
    FROM drivers 
    WHERE id='$driver_id'
    ");

    $driver_data = mysqli_fetch_assoc($driver_query);

    if($driver_data){
        $driver_name = $driver_data['name'];
    }

}

$where = " WHERE 1 ";

if(!empty($from_date)){
    $where .= " AND DATE(o.created_at) >= '$from_date' ";
}

if(!empty($to_date)){
    $where .= " AND DATE(o.created_at) <= '$to_date' ";
}

if(!empty($status)){
    $where .= " AND o.status = '$status' ";
}
if(!empty($driver_id)){
    $where .= " AND o.driver_id = '$driver_id' ";
}
// =========================
// إحصائيات حالات الطلبات
// =========================

$status_summary = mysqli_query($con,"
SELECT 
status,
COUNT(*) AS total
FROM orders
GROUP BY status
");

$statuses = [];

while($s = mysqli_fetch_assoc($status_summary)){
    $statuses[$s['status']] = $s['total'];
}

$pending   = $statuses['pending'] ?? 0;
$assigned  = $statuses['assigned'] ?? 0;
$done      = $statuses['done'] ?? 0;
$cancelled = $statuses['cancelled'] ?? 0;
// =========================
// جلب الطلبات
// =========================

$orders = mysqli_query($con,"
SELECT 
    o.id,
    o.order_number,
    o.full_name,
    o.phone,
    o.from_city,
    o.to_city,
    o.truck_type,
    o.price,
    o.status,
    o.created_at,
    d.name AS driver_name
FROM orders o
LEFT JOIN drivers d 
ON o.driver_id = d.id
$where
ORDER BY o.id DESC
");

$statuses = [];

while($s = mysqli_fetch_assoc($status_summary)){
    $statuses[$s['status']] = $s['total'];
}

$pending   = $statuses['pending'] ?? 0;
$assigned  = $statuses['assigned'] ?? 0;
$done      = $statuses['done'] ?? 0;
$cancelled = $statuses['cancelled'] ?? 0;

mysqli_data_seek($orders,0);


/* =========================
   إعداد PDF
========================= */

$mpdf = new Mpdf([
    'mode'=>'utf-8',
    'format'=>'A4',
    'orientation'=>'P',
    'margin_top'=>35,
    'margin_bottom'=>15,
    'margin_left'=>10,
    'margin_right'=>10
]);

$mpdf->SetDirectionality('rtl');

// =========================
// إجمالي الطلبات والدخل
// =========================

$summary = mysqli_query($con,"
SELECT 
COUNT(*) AS total,
SUM(o.price) AS total_price
FROM orders o
$where
");

$sum = mysqli_fetch_assoc($summary);
/* =========================
   الشعار
========================= */

$logo = '../uploads/logo/logo.jpg';


$html = '
<style>

body{
    font-family: dejavusans;
    direction:rtl;
}

.header{
    text-align:center;
}

.logo{
    width:100px;
}

h2{
    color:#333;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

th{
    background:#333;
    color:white;
    padding:8px;
}

td{
    border:1px solid #ccc;
    padding:7px;
    text-align:center;
    font-size:12px;
}

</style>


<div class="header">
<img class="logo" src="'.$logo.'">

<h2>
منصة الشرق الذكية للخدمات وإدارة الأسطول
</h2>

<h3>
تقرير الطلبات
</h3>


</div>
<div style="margin-top:15px;text-align:center">

<table style="width:100%;border:1px solid #ccc">

<tr>

<td>
من تاريخ:
<br>
<b>'.$from_date.'</b>
</td>

<td>
إلى تاريخ:
<br>
<b>'.$to_date.'</b>
</td>

<td>
الحالة:
<br>
<b>'.($status ? $status : 'كل الحالات').'</b>
</td>

<td>
السائق:
<br>
<b>'.($driver_name ? $driver_name : 'كل السائقين').'</b>
</td>

</tr>

</table>

</div>
<div style="margin-top:15px;text-align:center">

<table style="width:100%;border:1px solid #ccc">

<tr>

<td>
عدد الطلبات
<br>
<b>'.$sum['total'].'</b>
</td>


<td>
إجمالي المبالغ
<br>
<b>'.number_format($sum['total_price'],2).' ريال</b>
</td>

</tr>

</table>

</div>
<div style="margin-top:15px;text-align:center">

<table style="width:100%;border:1px solid #ccc">

<tr>

<td>
قيد الانتظار
<br>
<b>'.$pending.'</b>
</td>

<td>
تم التعيين
<br>
<b>'.$assigned.'</b>
</td>

<td>
مكتملة
<br>
<b>'.$done.'</b>
</td>

<td>
ملغاة
<br>
<b>'.$cancelled.'</b>
</td>

</tr>

</table>

</div>
<table>

<tr>
<th>رقم الطلب</th>
<th>العميل</th>
<th>الهاتف</th>
<th>من</th>
<th>إلى</th>
<th>نوع السطحة</th>
<th>السائق</th>
<th>السعر</th>
<th>الحالة</th>
<th>التاريخ</th>

</tr>
';


while($row=mysqli_fetch_assoc($orders)){

$html .= '

<tr>

<td>'.$row['id'].'</td>

<td>'.$row['full_name'].'</td>

<td>'.$row['phone'].'</td>

<td>'.$row['from_city'].'</td>

<td>'.$row['to_city'].'</td>

<td>'.$row['truck_type'].'</td>

<td>'.$row['driver_name'].'</td>

<td>'.$row['price'].'</td>

<td>';

if($row['status']=='done'){

    $html .= '<span style="color:green;font-weight:bold">
    مكتملة
    </span>';

}elseif($row['status']=='assigned'){

    $html .= '<span style="color:blue;font-weight:bold">
    تم التعيين
    </span>';

}elseif($row['status']=='cancelled'){

    $html .= '<span style="color:red;font-weight:bold">
    ملغاة
    </span>';

}else{

    $html .= '<span style="color:#d68910;font-weight:bold">
    قيد الانتظار
    </span>';

}


$html .= '

</td>

<td>'.$row['created_at'].'</td>

</tr>

';

}



$html .= '

</table>
';


$mpdf->WriteHTML($html);


$mpdf->Output(
    'orders_report.pdf',
    'I'
);

?>