<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

include __DIR__ . '/../../include/connected.php';
include __DIR__ . '/../../include/settings.php';

use Mpdf\Mpdf;

/*==================================
  إنشاء PDF
==================================*/

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_left' => 10,
    'margin_right' => 10
]);

/*==================================
  الفلاتر
==================================*/

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];

if($search != ''){

    $search = mysqli_real_escape_string($con,$search);

    $where[] = "(
        driver LIKE '%$search%'
        OR plate LIKE '%$search%'
        OR classify LIKE '%$search%'
        OR work LIKE '%$search%'
    )";
}

if($status == 'expired'){

    $where[] = "(
        operation_expiry < CURDATE()
        OR insurance_expiration_date < CURDATE()
        OR inspection_expiry < CURDATE()
    )";

}elseif($status == 'danger'){

    $where[] = "(
        operation_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
        OR insurance_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
        OR inspection_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
    )";

}elseif($status == 'warning'){

    $where[] = "(
        operation_expiry BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
        OR insurance_expiration_date BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
        OR inspection_expiry BETWEEN DATE_ADD(CURDATE(),INTERVAL 8 DAY) AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
    )";

}elseif($status == 'valid'){

    $where[] = "(
        operation_expiry > DATE_ADD(CURDATE(),INTERVAL 30 DAY)
        AND insurance_expiration_date > DATE_ADD(CURDATE(),INTERVAL 30 DAY)
        AND inspection_expiry > DATE_ADD(CURDATE(),INTERVAL 30 DAY)
    )";

}

$query = "SELECT * FROM fleet";

if(count($where)>0){

    $query .= " WHERE ".implode(" AND ",$where);

}

$query .= " ORDER BY id DESC";

$result = mysqli_query($con,$query);

/*==================================
  الإحصائيات
==================================*/

$total = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
"));

$expired = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry<CURDATE()
OR insurance_expiration_date<CURDATE()
OR inspection_expiry<CURDATE()
"));

$warning = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
OR insurance_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
OR inspection_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
"));

$valid = $total['total'] - $expired['total'];

/*==================================
  دالة الحالة
==================================*/

function expiryStatus($date){

    if(empty($date)){

        return [
            'text'=>'غير موجود',
            'color'=>'#95a5a6'
        ];

    }

    $today = strtotime(date('Y-m-d'));

    $expiry = strtotime($date);

    $days = floor(($expiry-$today)/86400);

    if($days<0){

        return [
            'text'=>'منتهي',
            'color'=>'#dc3545'
        ];

    }

    if($days<=7){

        return [
            'text'=>'7 أيام',
            'color'=>'#fd7e14'
        ];

    }

    if($days<=30){

        return [
            'text'=>'30 يوم',
            'color'=>'#ffc107'
        ];

    }

    return [
        'text'=>'ساري',
        'color'=>'#198754'
    ];

}

$html = '';
/*==================================
  بيانات الشركة
==================================*/

$company_name = setting('company_name','منصة الشرق الذكية للخدمات وإدارة الأسطول');

$company_logo = setting('company_logo','');

$logo_path = '../uploads/logo/'.$company_logo;


/*==================================
  بداية HTML
==================================*/

$html .= '

<style>

body{
    font-family: dejavusans;
    direction:rtl;
}

.header{
    text-align:center;
    border-bottom:2px solid #ddd;
    padding-bottom:10px;
}

.header img{

    width:80px;
    height:80px;

}

.company-name{

    font-size:22px;
    font-weight:bold;
    color:#0d6efd;

}

.report-title{

    font-size:18px;
    margin-top:10px;

}


.date{

    font-size:12px;
    color:#555;

}


.stats{

    width:100%;
    margin-top:20px;

}


.stat{

    width:23%;
    display:inline-block;
    text-align:center;
    padding:12px;
    margin:5px;
    color:#fff;
    border-radius:10px;
    font-size:14px;

}


.stat h2{

    margin:5px;
    font-size:25px;

}


.blue{

background:#0d6efd;

}

.red{

background:#dc3545;

}

.orange{

background:#fd7e14;

}

.green{

background:#198754;

}


</style>


<div class="header">
';


/* الشعار */

if(file_exists($logo_path)){

$html .= '

<img src="'.$logo_path.'">

';

}


$html .= '

<div class="company-name">
'.$company_name.'
</div>


<div class="report-title">
🚚 تقرير الأسطول
</div>


<div class="date">
تاريخ التقرير: '.date('Y-m-d H:i').'
</div>


</div>



<div class="stats">


<div class="stat blue">

إجمالي المركبات

<h2>
'.$total['total'].'
</h2>

</div>


<div class="stat red">

المركبات المنتهية

<h2>
'.$expired['total'].'
</h2>

</div>


<div class="stat orange">

قريبة الانتهاء

<h2>
'.$warning['total'].'
</h2>

</div>


<div class="stat green">

المركبات السارية

<h2>
'.$valid.'
</h2>

</div>


</div>


<br>
';
$html .= '

<table width="100%" border="1" cellspacing="0" cellpadding="6"
style="border-collapse:collapse;margin-top:15px;text-align:center;font-size:12px">

<thead>

<tr style="background:#2c3e50;color:white">

<th>#</th>

<th>المزود</th>

<th>اللوحة</th>

<th>النوع</th>

<th>التصنيف</th>

<th>الموديل</th>

<th>اللون</th>

<th>منطقة العمل</th>

<th>كرت التشغيل</th>

<th>التأمين</th>

<th>الفحص</th>

</tr>

</thead>

<tbody>
';


$count = 1;


while($row = mysqli_fetch_assoc($result)){


$operation = expiryStatus($row['operation_expiry']);

$insurance = expiryStatus($row['insurance_expiration_date']);

$inspection = expiryStatus($row['inspection_expiry']);



$html .= '

<tr>


<td>
'.$count.'
</td>


<td>
'.$row['driver'].'
</td>


<td>
'.$row['plate'].'
</td>


<td>
'.$row['typefleet'].'
</td>


<td>
'.$row['classify'].'
</td>


<td>
'.$row['model'].'
</td>


<td>
'.$row['colorfleet'].'
</td>


<td>
'.$row['work'].'
</td>



<td>

'.$row['operation_expiry'].'

<br>

<span style="
background:'.$operation['color'].';
color:white;
padding:3px 8px;
border-radius:10px">

'.$operation['text'].'

</span>


</td>



<td>

'.$row['insurance_expiration_date'].'

<br>

<span style="
background:'.$insurance['color'].';
color:white;
padding:3px 8px;
border-radius:10px">

'.$insurance['text'].'

</span>


</td>




<td>

'.$row['inspection_expiry'].'

<br>

<span style="
background:'.$inspection['color'].';
color:white;
padding:3px 8px;
border-radius:10px">

'.$inspection['text'].'

</span>


</td>



</tr>

';


$count++;

}


$html .= '

</tbody>

</table>

';
$html .= '

<br><br>

<div style="
text-align:center;
font-size:11px;
color:#777;
border-top:1px solid #ddd;
padding-top:8px">

منصة الشرق الذكية للخدمات وإدارة الأسطول
<br>
تقرير أسطول المركبات

</div>

';


/*==================================
  إعدادات الصفحة
==================================*/

$mpdf->SetHTMLFooter('

<table width="100%" style="font-size:10px;color:#777">

<tr>

<td width="50%" align="right">
منصة الشرق الذكية للخدمات وإدارة الأسطول
</td>


<td width="50%" align="left">

صفحة {PAGENO} من {nb}

</td>


</tr>

</table>

');


/*==================================
  إنشاء PDF
==================================*/

$mpdf->WriteHTML($html);


/*==================================
  إخراج الملف
==================================*/

$mpdf->Output(
    'fleet_report_'.date('Y-m-d').'.pdf',
    'I'
);

exit;

?>