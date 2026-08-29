<?php

session_start();

include('../include/connected.php');

include('../include/settings.php');

require_once '../vendor/autoload.php';

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


date_default_timezone_set('Asia/Riyadh');


// الفلاتر القادمة من الصفحة

$search = $_GET['search'] ?? '';

$date_from = $_GET['date_from'] ?? '';

$date_to = $_GET['date_to'] ?? '';

$status_filter = $_GET['status'] ?? '';



// بناء شرط البحث

$where = " WHERE 1 ";



if($search != ''){

    $search_safe = mysqli_real_escape_string($con,$search);

    $where .= "
    AND drivers.name LIKE '%$search_safe%'
    ";

}



if($date_from != '' && $date_to != ''){

    $where .= "
    AND attendance.attendance_date
    BETWEEN '$date_from' AND '$date_to'
    ";

}
elseif($date_from != ''){

    $where .= "
    AND attendance.attendance_date >= '$date_from'
    ";

}
elseif($date_to != ''){

    $where .= "
    AND attendance.attendance_date <= '$date_to'
    ";

}



if($status_filter != ''){

    $where .= "
    AND attendance.status='$status_filter'
    ";

}



// جلب البيانات

$query = mysqli_query($con,"

SELECT

attendance.*,

drivers.name,
drivers.id AS driver_number

FROM attendance

LEFT JOIN drivers

ON attendance.driver_id = drivers.id


$where


ORDER BY attendance.attendance_date DESC,
attendance.id DESC

");



if(!$query){

    die(mysqli_error($con));

}



// بيانات التقرير



$report_date = date('Y-m-d');




// بداية تصميم التقرير

$html = '

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

body{

font-family:dejavusans;

direction:rtl;

font-size:12px;

}


.header{

text-align:center;

margin-bottom:20px;

}


.header img{

width:80px;

}


h2{

color:#0d6efd;

}


.info{

margin-bottom:15px;

font-size:13px;

}


table{

width:100%;

border-collapse:collapse;

}


th{

background:#0d6efd;

color:#fff;

padding:8px;

}


td{

border:1px solid #ddd;

padding:7px;

text-align:center;

}


.footer{

margin-top:20px;

font-size:11px;

text-align:center;

color:#666;

}


</style>

</head>


<body>


<div class="header">

';

if(file_exists($logo)){

$html .= '

<img class="logo" src="'.$logo.'">

';

}


$html .= '

<h2>'.$companyName.'</h2>

<p>

'.$address.'

<br>

هاتف: '.$phone.'

<br>

البريد: '.$email.'

</p>

<h3>

تقرير حضور السائقين

</h3>






<div class="info">

تاريخ التقرير:
'.$report_date.'
<br>
';

if($date_from != '' || $date_to != ''){

$html .= '

الفترة:
'.$date_from.'
 إلى 
'.$date_to.'

';

}


$html .= '

</div>


<table>

<thead>

<tr>

<th>#</th>

<th>السائق</th>

<th>رقم السائق</th>

<th>التاريخ</th>

<th>الدخول</th>

<th>الخروج</th>

<th>مدة العمل</th>

<th>الحالة</th>

</tr>

</thead>


<tbody>
';

$i = 1;

while($row = mysqli_fetch_assoc($query)){


    // حساب مدة العمل

    $workDuration = '-';


    if(!empty($row['check_in']) && !empty($row['check_out'])){


        $start = new DateTime($row['check_in']);

        $end = new DateTime($row['check_out']);


        $diff = $start->diff($end);


        $workDuration =
        $diff->h . " ساعة " .
        $diff->i . " دقيقة";

    }



    // حالة الحضور

    switch($row['status']){

        case 'present':
            $statusText="🟢 حاضر";
        break;


        case 'late':
            $statusText="🟡 متأخر";
        break;


        case 'absent':
            $statusText="🔴 غائب";
        break;


        default:
            $statusText=$row['status'];

    }



$html .= '

<tr>

<td>'.$i++.'</td>

<td>'.htmlspecialchars($row['name']).'</td>

<td>'.$row['driver_number'].'</td>

<td>'.$row['attendance_date'].'</td>

<td>'.$row['check_in'].'</td>

<td>'.$row['check_out'].'</td>

<td>'.$workDuration.'</td>

<td>'.$statusText.'</td>

</tr>

';


}



$html .= '

</tbody>

</table>


<div class="footer">

تم إنشاء التقرير بتاريخ '.$report_date.'

</div>


</body>

</html>

';



// إنشاء PDF

$mpdf = new \Mpdf\Mpdf([
    'mode'=>'utf-8',
    'format'=>'A4',
    'directionality'=>'rtl'
]);


$mpdf->WriteHTML($html);


$mpdf->Output(
    'attendance_report.pdf',
    'I'
);

?>