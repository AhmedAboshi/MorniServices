<?php
session_start();

include('../include/connected.php');
include('../include/settings.php');
include('../include/report_header.php');
require_once('../vendor/autoload.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم المركبة غير صحيح");
}

/* ==========================
   بيانات المركبة
========================== */

$stmt = $con->prepare("
SELECT *
FROM fleet
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$fleet = $stmt->get_result()->fetch_assoc();

if(!$fleet){
    die("المركبة غير موجودة");
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

$phone    = $settings['company_phone'] ?? '';
$email    = $settings['company_email'] ?? '';
$address  = $settings['company_address'] ?? '';
$website  = $settings['company_website'] ?? '';

$logo = "../uploads/logo/" . ($settings['company_logo'] ?? '');

$reportDate = date('Y-m-d');
$reportTime = date('H:i');

$reportUser = $_SESSION['username'] ?? 'Administrator';
/* ==========================
   المستندات
========================== */

$documents = [];

$stmt = $con->prepare("
SELECT *
FROM vehicle_documents
WHERE car_id=?
ORDER BY expiry_date ASC
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $documents[] = $row;
}

/* ==========================
   إنشاء PDF
========================== */

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_left' => 10,
    'margin_right' => 10
]);

$mpdf->SetAutoPageBreak(true,20);


/* ==========================
   تذييل صفحات التقرير
========================== */


/* ==========================
   الصيانة
========================== */

$maintenance = [];

$stmt = $con->prepare("
SELECT *
FROM maintenance
WHERE plate_number=?
ORDER BY maintenance_date DESC
");

$stmt->bind_param("s",$fleet['plate']);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $maintenance[] = $row;
}


/* ==========================
   تغييرات الزيت
========================== */

$oilChanges = [];

$stmt = $con->prepare("
SELECT *
FROM oil_changes
WHERE car_id=?
ORDER BY change_date DESC
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $oilChanges[] = $row;
}


/* ==========================
   الإطارات
========================== */

$tires = [];

$stmt = $con->prepare("
SELECT *
FROM tires
WHERE car_id=?
ORDER BY change_date DESC
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $tires[] = $row;
}
$report_title = "تقرير المركبة (لوحة ".$fleet['plate'].")";

$report_number = "FLT-".date('Ymd')."-".$fleet['id'];

$plate = $fleet['plate'];

$driver = $fleet['driver'];



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
margin-bottom:5px;
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
margin-top:5px;
margin-bottom:5px;
}

.photo img{
width:140px;
height:100px;
object-fit:cover;
border:1px solid #ccc;
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

ملف المركبة الإلكتروني

</div>



<div class="photo">';
if(!empty($fleet['imgfleet']) && file_exists("../fleetimg/img/".$fleet['imgfleet'])){

$html .= '<img src="../fleetimg/img/'.$fleet['imgfleet'].'">';

}
$html .= '

<table>

<tr>

<td class="label">رقم اللوحة</td>

<td>'.$fleet['plate'].'</td>

</tr>

<tr>

<td class="label">السائق</td>

<td>'.$fleet['driver'].'</td>

</tr>

<tr>

<td class="label">الطراز</td>

<td>'.$fleet['typefleet'].'</td>

</tr>

<tr>

<td class="label">التصنيف</td>

<td>'.$fleet['classify'].'</td>

</tr>

<tr>

<td class="label">الموديل</td>

<td>'.$fleet['model'].'</td>

</tr>

<tr>

<td class="label">اللون</td>

<td>'.$fleet['colorfleet'].'</td>

</tr>

<tr>

<td class="label">منطقة العمل</td>

<td>'.$fleet['work'].'</td>

</tr>

</table>

';
$html .= '

<br><br>

<h3 style="
background:#0d6efd;
color:white;
padding:8px;
text-align:center;
">
📄 المستندات
</h3>

<table>

<tr style="background:#f2f2f2;font-weight:bold;">

<td>النوع</td>
<td>الرقم</td>
<td>الإصدار</td>
<td>الانتهاء</td>
<td>الحالة</td>

</tr>

';

foreach($documents as $doc){

    $status = "ساري";

    if(!empty($doc['expiry_date'])){

        $days = ceil((strtotime($doc['expiry_date']) - time()) / 86400);

        if($days < 0){

            $status = "منتهي";

        }elseif($days <= 30){

            $status = "قريب الانتهاء";

        }

    }

    $html .= '

    <tr>

    <td>'.$doc['document_type'].'</td>

    <td>'.$doc['document_number'].'</td>

    <td>'.$doc['issue_date'].'</td>

    <td>'.$doc['expiry_date'].'</td>

    <td>'.$status.'</td>

    </tr>

    ';

}

$html .= '</table>';

$html .= '

<br><br>

<h3 style="background:#198754;color:#fff;padding:8px;text-align:center;">
🔧 سجل الصيانة
</h3>

<table>

<tr style="background:#f2f2f2;font-weight:bold;">
<td>التاريخ</td>
<td>النوع</td>
<td>التكلفة</td>
<td>الملاحظات</td>
</tr>';

foreach($maintenance as $m){

$html .= '

<tr>

<td>'.$m['maintenance_date'].'</td>

<td>'.$m['maintenance_type'].'</td>

<td>'.number_format($m['cost'],2).'</td>

<td>'.$m['notes'].'</td>

</tr>';

}

$html .= '</table>';

$html .= '

<br><br>

<h3 style="background:#0dcaf0;color:#000;padding:8px;text-align:center;">
🛢️ سجل تغييرات الزيت
</h3>

<table>

<tr style="background:#f2f2f2;font-weight:bold;">
<td>التاريخ</td>
<td>نوع الزيت</td>
<td>العداد</td>
<td>التغيير القادم</td>
<td>التكلفة</td>
</tr>';

foreach($oilChanges as $o){

$html .= '

<tr>

<td>'.$o['change_date'].'</td>

<td>'.$o['oil_type'].'</td>

<td>'.number_format($o['current_km']).'</td>

<td>'.$o['next_change'].'</td>

<td>'.number_format($o['cost']).'</td>

</tr>';

}

$html .= '</table>';

$html .= '

<br><br>

<h3 style="background:#ffc107;color:#000;padding:8px;text-align:center;">
🛞 سجل الإطارات
</h3>

<table>

<tr style="background:#f2f2f2;font-weight:bold;">
<td>تاريخ التركيب</td>
<td>نوع الإطار</td>
<td>العداد</td>
<td>التغيير القادم</td>
<td>التكلفة</td>
</tr>';

foreach($tires as $t){

$html .= '

<tr>

<td>'.$t['change_date'].'</td>

<td>'.$t['tire_type'].'</td>

<td>'.number_format($t['current_km']).'</td>

<td>'.$t['next_change'].'</td>

<td>'.number_format($t['cost']).'</td>

</tr>';

}


$html .= '</table>';



$mpdf->WriteHTML($html);

/* ==========================
   تذييل صفحات التقرير
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

$mpdf->Output(
'Fleet_'.$fleet['plate'].'.pdf',
'I'
);
