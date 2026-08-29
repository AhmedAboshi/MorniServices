<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once('../vendor/autoload.php');


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if($id <= 0){
    die("رقم السائق غير صحيح");
}


/* ==========================
   بيانات السائق
========================== */

$stmt = $con->prepare("
SELECT *
FROM drivers
WHERE id=?
LIMIT 1
");


$stmt->bind_param("i",$id);

$stmt->execute();


$driver = $stmt->get_result()->fetch_assoc();



if(!$driver){

    die("السائق غير موجود");

}



/* ==========================
   إعدادات الشركة
========================== */


$settings = [];


$result = $con->query("
SELECT setting_key, setting_value
FROM settings
");


while($row = $result->fetch_assoc()){

    $settings[$row['setting_key']] = $row['setting_value'];

}



$companyName = $settings['company_name'] ?? '';

$systemName  = $settings['system_name'] ?? '';

$phone       = $settings['company_phone'] ?? '';

$email       = $settings['company_email'] ?? '';

$address     = $settings['company_address'] ?? '';



$logo = "../uploads/logo/" . 
($settings['company_logo'] ?? '');



$reportDate = date('Y-m-d');

$reportTime = date('H:i');


$reportUser = $_SESSION['username'] ?? 'Administrator';



$report_title = "ملف السائق الإلكتروني (".$driver['name'].")";


$report_number = "DRV-".date('Ymd')."-".$driver['id'];




/* ==========================
   إنشاء PDF
========================== */


$mpdf = new \Mpdf\Mpdf([

    'mode'=>'utf-8',

    'format'=>'A4',

    'margin_top'=>20,

    'margin_bottom'=>20,

    'margin_left'=>10,

    'margin_right'=>10

]);



$mpdf->SetAutoPageBreak(true,20);
/* ==========================
   محتوى التقرير
========================== */


$html = '

<style>

body{
font-family:dejavusans;
direction:rtl;
font-size:12px;
}


.header-company{

width:100%;
border-bottom:1px solid #0d6efd;
padding-bottom:5px;
margin-bottom:10px;

}


.logo{

width:60px;
height:60px;

}


.header-company table{

border:none;

}


.header-company td{

border:none;
padding:3px;

}



.report-info{

background:#f8f9fa;
padding:10px;
margin-bottom:15px;

}



.title{

background:#0d6efd;
color:#fff;
padding:12px;
font-size:20px;
font-weight:bold;
text-align:center;
margin-bottom:20px;

}



table{

width:100%;
border-collapse:collapse;
margin-top:15px;

}



td{

border:1px solid #ddd;
padding:8px;

}



.label{

background:#f5f5f5;
font-weight:bold;
width:30%;

}



.photo{

text-align:center;
margin-bottom:15px;

}



.photo img{

width:120px;
height:120px;
object-fit:cover;
border-radius:50%;
border:2px solid #ccc;

}


</style>



<div class="header-company">

<table>

<tr>

<td width="25%">
';


if(file_exists($logo)){

$html .= '

<img class="logo" src="'.$logo.'">

';

}


$html .= '

</td>


<td>

<h2>'.$companyName.'</h2>

<p>

'.$address.'

<br>

هاتف: '.$phone.'

<br>

البريد: '.$email.'

</p>


</td>


</tr>

</table>

</div>



<div class="report-info">


<b>اسم التقرير:</b>
'.$report_title.'

<br>


<b>رقم التقرير:</b>
'.$report_number.'

<br>


<b>تاريخ التقرير:</b>
'.$reportDate.'
 '.$reportTime.'

<br>


<b>مصدر التقرير:</b>
'.$reportUser.'


</div>



<div class="title">

👤 ملف السائق الإلكتروني

</div>



<div class="photo">
';


$driverImage = "../uploads/".$driver['imagedriver'];


if(!empty($driver['imagedriver']) && file_exists($driverImage)){


$html .= '

<img src="'.$driverImage.'">

';


}


$html .= '

</div>



<table>


<tr>

<td class="label">

اسم السائق

</td>

<td>

'.$driver['name'].'

</td>

</tr>



<tr>

<td class="label">

رقم الهوية

</td>

<td>

'.$driver['national_id'].'

</td>

</tr>



<tr>

<td class="label">

رقم الجوال

</td>

<td>

'.$driver['phone'].'

</td>

</tr>



<tr>

<td class="label">

منطقة العمل

</td>

<td>

'.$driver['work_area'].'

</td>

</tr>



<tr>

<td class="label">

نوع المركبة

</td>

<td>

'.$driver['truck_type'].'

</td>

</tr>


</table>

';


/* ==========================
   مستندات السائق
========================== */

$documents = [];

$stmt = $con->prepare("
SELECT *
FROM driver_documents
WHERE driver_id=?
ORDER BY expiry_date ASC
");

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();


while($row = $result->fetch_assoc()){

    $documents[] = $row;

}



$html .= '

<br><br>

<h3 style="
background:#0d6efd;
color:white;
padding:8px;
text-align:center;
">

📄 مستندات السائق

</h3>


<table>


<tr style="background:#f2f2f2;font-weight:bold;">

<td>
النوع
</td>

<td>
الرقم
</td>

<td>
الإصدار
</td>

<td>
الانتهاء
</td>

<td>
الحالة
</td>


</tr>

';

$html .= '<h3>اختبار المستندات</h3>';

foreach($documents as $doc){


$status = "ساري";


if(!empty($doc['expiry_date'])){


$days = ceil(
(strtotime($doc['expiry_date']) - time()) / 86400
);



if($days < 0){

$status="منتهي";

}
elseif($days <=30){

$status="قريب الانتهاء";

}


}



$html .= '

<tr>


<td>
'.$doc['document_type'].'
</td>


<td>
'.$doc['document_number'].'
</td>


<td>
'.$doc['issue_date'].'
</td>


<td>
'.$doc['expiry_date'].'
</td>


<td>
'.$status.'
</td>


</tr>

';


}


$html .= '

</table>

';

/* ==========================
   مركبات السائق
========================== */

$vehicles = [];

$stmt = $con->prepare("
SELECT *
FROM fleet
WHERE driver=?
ORDER BY id DESC
");

$stmt->bind_param("s",$driver['name']);

$stmt->execute();

$result = $stmt->get_result();


while($row = $result->fetch_assoc()){

    $vehicles[] = $row;

}



$html .= '

<br><br>

<h3 style="
background:#198754;
color:white;
padding:8px;
text-align:center;
">

🚚 المركبات المرتبطة بالسائق

</h3>


<table>


<tr style="background:#f2f2f2;font-weight:bold;">


<td>
اللوحة
</td>


<td>
النوع
</td>


<td>
الموديل
</td>


<td>
اللون
</td>


<td>
منطقة العمل
</td>


</tr>

';



foreach($vehicles as $car){


$html .= '

<tr>


<td>
'.$car['plate'].'
</td>


<td>
'.$car['typefleet'].'
</td>


<td>
'.$car['model'].'
</td>


<td>
'.$car['colorfleet'].'
</td>


<td>
'.$car['work'].'
</td>


</tr>

';


}



$html .= '

</table>

';

/* ==========================
   طلبات السائق
========================== */

$orders = [];


$stmt = $con->prepare("
SELECT *
FROM orders
WHERE driver_id=?
ORDER BY id DESC
");


$stmt->bind_param("i",$id);

$stmt->execute();


$result = $stmt->get_result();


while($row = $result->fetch_assoc()){

    $orders[] = $row;

}



$html .= '

<br><br>

<h3 style="
background:#6f42c1;
color:white;
padding:8px;
text-align:center;
">

📦 سجل طلبات السائق

</h3>


<table>


<tr style="background:#f2f2f2;font-weight:bold;">


<td>
رقم الطلب
</td>


<td>
المسار
</td>


<td>
المبلغ
</td>


<td>
الحالة
</td>


<td>
التاريخ
</td>


</tr>

';



foreach($orders as $order){



$status = match($order['status']){

'done'=>'مكتمل',

'cancelled'=>'ملغي',

'assigned'=>'معين',

'on_the_way'=>'بالطريق',

default=>'انتظار'

};



$html .= '

<tr>


<td>
'.($order['order_number'] ?: '#'.$order['id']).'
</td>


<td>
'.$order['from_city'].' ➡ '.$order['to_city'].'
</td>


<td>
'.number_format($order['price'],2).' ر.س
</td>


<td>
'.$status.'
</td>


<td>
'.$order['created_at'].'
</td>


</tr>

';


}



$html .= '

</table>

';

/* ==========================
   سجل حضور السائق
========================== */

$attendance = [];


$stmt = $con->prepare("
SELECT *
FROM attendance
WHERE driver_id=?
ORDER BY attendance_date DESC
");


$stmt->bind_param("i",$id);

$stmt->execute();


$result = $stmt->get_result();



while($row = $result->fetch_assoc()){

    $attendance[] = $row;

}



$html .= '

<br><br>

<h3 style="
background:#fd7e14;
color:white;
padding:8px;
text-align:center;
">

📅 سجل حضور السائق

</h3>


<table>


<tr style="background:#f2f2f2;font-weight:bold;">


<td>
التاريخ
</td>


<td>
دخول
</td>


<td>
خروج
</td>


<td>
الحالة
</td>


</tr>

';



foreach($attendance as $att){


$status = match($att['status']){

'present'=>'حاضر',

'late'=>'متأخر',

'absent'=>'غائب',

default=>'غير محدد'

};



$html .= '

<tr>


<td>
'.$att['attendance_date'].'
</td>


<td>
'.($att['check_in'] ?? '-').'
</td>


<td>
'.($att['check_out'] ?? '-').'
</td>


<td>
'.$status.'
</td>


</tr>

';


}



$html .= '

</table>

';

/* ==========================
   ذيل صفحات التقرير
========================== */

$mpdf->SetHTMLFooter('

<table width="100%" style="font-size:9px;border-top:1px solid #ddd;">

<tr>

<td width="50%" align="right">

'.$companyName.'
<br>
'.$systemName.'

</td>


<td width="50%" align="left">

رقم التقرير:
'.$report_number.'

<br>

صفحة {PAGENO} من {nbpg}

</td>

</tr>

</table>

');

$mpdf->WriteHTML($html);

$mpdf->Output(
    'Driver_'.$driver['id'].'.pdf',
    'I'
);
