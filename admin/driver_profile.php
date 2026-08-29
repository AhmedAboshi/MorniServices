<?php

include('../include/connected.php');

session_start();

$lang = $_GET['lang'] ?? 'ar';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم السائق غير صحيح");
}





/* =========================
   جلب بيانات السائق
========================= */

$stmt = $con->prepare("
    SELECT *
    FROM drivers
    WHERE id = ?
");

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

$driver = $result->fetch_assoc();

/*==================================
تنبيهات انتهاء مستندات السائق
==================================*/

$alerts = [];

$today = new DateTime();

$warning_date = date('Y-m-d', strtotime('+60 days'));


/* فحص مستندات السائق */

$stmt_alert = $con->prepare("
    SELECT document_type, expiry_date
    FROM driver_documents
    WHERE driver_id = ?
    AND expiry_date IS NOT NULL
    AND expiry_date <= ?
");

$stmt_alert->bind_param(
    "is",
    $id,
    $warning_date
);

$stmt_alert->execute();

$result_alert = $stmt_alert->get_result();


$documentNames = [

    'license'=>'رخصة القيادة',
    'iqama'=>'الإقامة',
    'driver_card'=>'بطاقة السائق',
    'passport'=>'جواز السفر'

];


while($doc_alert = $result_alert->fetch_assoc()){


    $expiry = new DateTime($doc_alert['expiry_date']);

    $days = (int)$today->diff($expiry)->format('%r%a');


    $name = $documentNames[$doc_alert['document_type']]
            ?? $doc_alert['document_type'];


    if($days < 0){

        $alerts[] = [
            'class'=>'danger',
            'icon'=>'❌',
            'text'=>"{$name} منتهية منذ ".abs($days)." يوم"
        ];

    }
    elseif($days <=30){

        $alerts[] = [
            'class'=>'warning',
            'icon'=>'⚠️',
            'text'=>"{$name} تنتهي بعد {$days} يوم"
        ];

    }
    else{

        $alerts[] = [
            'class'=>'info',
            'icon'=>'ℹ️',
            'text'=>"{$name} تنتهي بعد {$days} يوم"
        ];

    }

}
if(!$driver){
    die("السائق غير موجود");
}

/* =========================
   مستندات السائق
========================= */

$documents = mysqli_query($con,"
SELECT *
FROM driver_documents
WHERE driver_id='{$driver['id']}'
ORDER BY id DESC
");

$totalDocuments = 0;
$activeDocuments = 0;
$nearDocuments = 0;
$expiredDocuments = 0;

if($documents){

    while($doc = mysqli_fetch_assoc($documents)){

        $totalDocuments++;

        $status = documentStatus($doc['expiry_date']);

        if($status['class']=="success"){
            $activeDocuments++;
        }elseif($status['class']=="warning"){
            $nearDocuments++;
        }elseif($status['class']=="danger"){
            $expiredDocuments++;
        }

    }

    mysqli_data_seek($documents,0);
}

$iqamaStatus = getStatus($driver['iqama_expiry_date']);

$licenseStatus = getStatus($driver['license_expiry_date']);

$cardStatus = getStatus($driver['driver_card_expiration_date']);



function e($value){

    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

}


function getStatus($expiryDate){

    if(empty($expiryDate)){
        return [
            'text' => 'غير محدد',
            'class' => 'secondary',
            'days' => '-'
        ];
    }

    $today = new DateTime();
    $expiry = new DateTime($expiryDate);

    $days = (int)$today->diff($expiry)->format('%r%a');

    if($days < 0){
        return [
            'text' => 'منتهية',
            'class' => 'danger',
            'days' => abs($days).' يوم'
        ];
    }

    if($days <= 30){
        return [
            'text' => 'تنتهي قريباً',
            'class' => 'warning',
            'days' => $days.' يوم'
        ];
    }

    return [
        'text' => 'سارية',
        'class' => 'success',
        'days' => $days.' يوم'
    ];

}
function documentStatus($date){

    if(empty($date)){
        return [
            'text'=>'غير محدد',
            'class'=>'secondary'
        ];
    }


    $today = new DateTime();
    $expiry = new DateTime($date);

    $days = (int)$today->diff($expiry)->format('%r%a');


    if($days < 0){

        return [
            'text'=>'منتهي',
            'class'=>'danger'
        ];

    }


    if($days <= 30){

        return [
            'text'=>'ينتهي قريباً',
            'class'=>'warning'
        ];

    }


    return [
        'text'=>'ساري',
        'class'=>'success'
    ];

}




?>
<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>
ملف السائق الإلكتروني
</title>
<?php if(!empty($alerts)): ?>

<div class="shadow-sm">

<h5>
⚠️ تنبيهات المستندات
</h5>


<?php foreach($alerts as $alert): ?>

<div class="alert alert-<?= $alert['class'] ?> mb-2">

    <?= $alert['icon'] ?>

    <?= htmlspecialchars($alert['text']) ?>

</div>

<?php endforeach; ?>


</div>

<?php endif; ?>

<link rel="stylesheet" href="assets/dark-mode.css">


<style>

body{

font-family:Arial;
background:#f4f6f9;

}


.container{

width:95%;
margin:30px auto;

}


.profile-header{

background:#fff;
padding:20px;
border-radius:15px;
box-shadow:0 3px 10px rgba(0,0,0,.1);

display:flex;
justify-content:space-between;
align-items:center;

}


.profile-header h2{

margin:0;

}


.actions{

display:flex;
gap:8px;
flex-wrap:wrap;

}


.actions a{

padding:8px 15px;
border-radius:6px;
color:white;
text-decoration:none;

}


.btn-edit{

background:#0d6efd;

}


.btn-print{

background:#198754;

}


.btn-back{

background:#6c757d;

}
.btn-excel{

background:#198754;

}

.btn-pdf{

background:#dc3545;

}

.driver-card{

background:#fff;
margin-top:20px;
padding:25px;
border-radius:15px;
box-shadow:0 3px 10px rgba(0,0,0,.1);

display:flex;
gap:30px;
align-items:center;

}


.driver-image img{

width:170px;
height:170px;
border-radius:50%;
object-fit:cover;
border:5px solid #eee;

}


.driver-image img,
.no-image{

width:140px;
height:140px;

}

.driver-info{

flex:1;

}


.driver-info h3{

font-size:26px;
margin-bottom:20px;

}


.info-grid{

display:grid;
grid-template-columns:repeat(2,1fr);
gap:15px;

}


.info-grid div{

background:#f8f9fa;
padding:15px;
border-radius:10px;

}

.status-cards{

display:grid;
grid-template-columns:repeat(4,1fr);
gap:12px;
margin-top:15px;

}


.status-card{

background:#fff;
padding:18px;
border-radius:14px;
box-shadow:0 4px 12px rgba(0,0,0,.08);
text-align:center;
transition:.3s;

}

.status-card:hover{

transform:translateY(-4px);

}


.status-card h4{

font-size:15px;
margin:5px 0 8px;

}


.badge{

padding:5px 12px;
font-size:13px;

}


.days{

font-size:13px;
color:#666;
margin-top:5px;

}

.status-card h4{

margin-bottom:10px;

}

.badge{

display:inline-block;
padding:6px 14px;
border-radius:30px;
color:#fff;
font-size:14px;
margin:10px 0;

}

.success{

background:#198754;

}

.warning{

background:#ffc107;
color:#000;

}

.danger{

background:#dc3545;

}

.secondary{

background:#6c757d;

}


.driver-tabs{

margin-top:15px;
display:flex;
gap:12px;
flex-wrap:wrap;

}


.tab-btn{

background:#6c757d;
color:white;
border:none;

padding:12px 22px;

border-radius:12px;

cursor:pointer;

font-size:15px;

transition:.3s;

box-shadow:0 2px 5px rgba(0,0,0,.15);

}


.tab-btn:hover{

transform:translateY(-2px);

}


/* التبويب النشط */

.tab-btn.active{

background:linear-gradient(135deg,#0d6efd,#0056d6);

box-shadow:
0 5px 15px rgba(13,110,253,.35);

font-weight:bold;

position:relative;

}


/* خط سفلي */

.tab-btn.active:after{

content:"";

position:absolute;

bottom:-6px;

left:25%;

width:50%;

height:3px;

background:white;

border-radius:5px;

}
.vehicle-table{

width:100%;
border-collapse:collapse;
margin-top:20px;
background:#fff;
border-radius:12px;
overflow:hidden;
box-shadow:0 3px 10px rgba(0,0,0,.08);

}

.vehicle-table th{

background:#0d6efd;
color:#fff;
padding:14px;

}

.vehicle-table td{

padding:12px;
border-bottom:1px solid #eee;
text-align:center;

}

.vehicle-table tr:hover{

background:#f8f9fa;

}

.car-thumb{

width:70px;
height:50px;
object-fit:cover;
border-radius:8px;

}

.view-btn{

background:#198754;
color:#fff;
padding:6px 12px;
border-radius:6px;
text-decoration:none;

}

.empty-box{

background:#fff3cd;
padding:20px;
border-radius:10px;
text-align:center;
margin-top:20px;
font-size:17px;

}

.vehicle-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:20px;
    margin-top:20px;
}

.vehicle-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
    transition:.3s;
}

.vehicle-card:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 25px rgba(0,0,0,.15);
}

.vehicle-card img{
    width:100%;
    height:180px;
    object-fit:cover;
}

.vehicle-body{
    padding:18px;
}

.vehicle-title{
    font-size:20px;
    font-weight:bold;
    margin-bottom:12px;
}

.vehicle-item{
    margin:8px 0;
    color:#555;
}

.vehicle-footer{
    margin-top:18px;
}

.view-btn{
    display:block;
    width:100%;
    text-align:center;
    background:#0d6efd;
    color:#fff;
    padding:10px;
    border-radius:10px;
    text-decoration:none;
    transition:.3s;
}

.view-btn:hover{
    background:#084298;
}

.fleet-summary{

display:grid;
grid-template-columns:repeat(3,1fr);
gap:10px;
margin:15px 0;

}


.summary-card{

padding:10px;
border-radius:12px;
color:#fff;
text-align:center;
box-shadow:0 3px 8px rgba(0,0,0,.10);

}


.summary-card h2{

margin:0;
font-size:24px;

}


.summary-card span{

display:block;
margin-top:5px;
font-size:13px;

}
.blue{

background:linear-gradient(135deg,#0d6efd,#4b8cff);

}

.green{

background:linear-gradient(135deg,#198754,#43c47a);

}

.red{

background:linear-gradient(135deg,#dc3545,#ff6b7d);

}
.vehicle-item .badge{

font-size:12px;
padding:4px 10px;
border-radius:20px;

}
/* ==========================
   Driver Documents Cards
========================== */

.document-grid{

display:grid;
grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
gap:20px;
margin-top:20px;

}


.document-card{

background:#fff;
border-radius:18px;
overflow:hidden;
box-shadow:0 4px 15px rgba(0,0,0,.08);
transition:.3s;

}


.document-card:hover{

transform:translateY(-5px);
box-shadow:0 8px 25px rgba(0,0,0,.15);

}



.document-preview{

height:150px;
background:#f8f9fa;
display:flex;
align-items:center;
justify-content:center;
overflow:hidden;

}



.document-preview img{

width:100%;
height:100%;
object-fit:cover;

}


.pdf-icon{

font-size:70px;

}



.document-body{

padding:15px;

}



.document-title{

font-size:18px;
font-weight:bold;
margin-bottom:12px;

}



.document-actions{

display:flex;
gap:8px;
margin-top:15px;

}



.doc-btn{

width:38px;
height:38px;

display:flex;
align-items:center;
justify-content:center;

border-radius:50%;

color:#fff;
text-decoration:none;

font-size:18px;

position:relative;

}



.doc-btn:hover{

opacity:.85;

}



.doc-view{

background:#0d6efd;

}


.doc-download{

background:#198754;

}


.doc-print{

background:#6c757d;

}


.doc-replace{

background:#ffc107;
color:#000;

}


.doc-delete{

background:#dc3545;

}


/* Tooltip */

.doc-btn::after{

content:attr(data-title);

position:absolute;

bottom:-35px;

background:#333;
color:#fff;

padding:5px 8px;

font-size:12px;

border-radius:5px;

opacity:0;

white-space:nowrap;

pointer-events:none;

transition:.3s;

}


.doc-btn:hover::after{

opacity:1;

}
.doc-upload{

background:#0d6efd;

}
@media print {

.actions,
.driver-tabs,
.document-actions,
.view-btn{
    display:none !important;
}


body{
    background:white;
}


.card,
.driver-card,
.status-card,
.vehicle-card,
.document-card{
    box-shadow:none !important;
}


}
</style>


</head>


<body>

<div class="container">
    <div class="profile-header">


<div>

<h2>
👤 ملف السائق الإلكتروني
</h2>

<h4>
<?= e($driver['name']) ?>
</h4>


</div>


<div class="actions">


<a class="btn-edit"
href="edit-driver.php?id=<?= $driver['id'] ?>">
✏️ تعديل
</a>


<a class="btn-pdf"
href="driver_pdf.php?id=<?= $driver['id'] ?>"
target="_blank">
📄 PDF 
</a>


<a class="btn-excel"
href="driver_excel.php?id=<?= $driver['id'] ?>"
target="_blank">
📊 Excel 
</a>


<a class="btn-print"
href="driver_print.php?id=<?= $driver['id'] ?>"
target="_blank">
🖨 طباعة
</a>


<a class="btn-back"
href="driversview.php">
⬅ رجوع
</a>


</div>
</div>
<!-- =========================
     بطاقة معلومات السائق
========================= -->

<div class="driver-card">


<div class="driver-image">

<?php
$image = "../uploads/" . $driver['imagedriver'];

if (!empty($driver['imagedriver']) && file_exists($image)) {
?>

    <img src="<?= $image ?>" alt="صورة السائق">

<?php } else { ?>

    <div class="no-image">
        👤
    </div>

<?php } ?>

</div>



<div class="driver-info">


<h3>
<?= e($driver['name']) ?>
</h3>


<div class="info-grid">


<div>
<strong>🪪 رقم الهوية</strong>
<br>
<?= e($driver['national_id']) ?>
</div>


<div>
<strong>📱 الجوال</strong>
<br>
<?= e($driver['phone']) ?>
</div>


<div>
<strong>📍 منطقة العمل</strong>
<br>
<?= e($driver['work_area']) ?>
</div>


<div>
<strong>🚚 نوع المركبة</strong>
<br>
<?= e($driver['truck_type']) ?>
</div>


</div>

</div>



</div>


<div class="status-cards">

<div class="status-card">

<h4>🪪 الإقامة</h4>

<span class="badge <?= $iqamaStatus['class'] ?>">
<?= $iqamaStatus['text'] ?>
</span>

<div class="days">
<?= $iqamaStatus['days'] ?>
</div>

</div>


<div class="status-card">

<h4>🚗 الرخصة</h4>

<span class="badge <?= $licenseStatus['class'] ?>">
<?= $licenseStatus['text'] ?>
</span>

<div class="days">
<?= $licenseStatus['days'] ?>
</div>

</div>


<div class="status-card">

<h4>🎫 بطاقة السائق</h4>

<span class="badge <?= $cardStatus['class'] ?>">
<?= $cardStatus['text'] ?>
</span>

<div class="days">
<?= $cardStatus['days'] ?>
</div>

</div>


<div class="status-card">

<h4>📱 QR Code</h4>

<?php if(!empty($driver['qr_code'])){ ?>

<img 
src="../generate_qr.php?text=<?= urlencode($driver['qr_code']) ?>"
width="95"
>

<?php } ?>

</div>

</div>

<div class="driver-tabs">

<button type="button" class="tab-btn" onclick="showTab(this,'info')">
📋 المعلومات
</button>

<button type="button" class="tab-btn" onclick="showTab(this,'vehicles')">
    🚚 المركبات

</button>

<button type="button" class="tab-btn" onclick="showTab(this,'orders')">
    📦 الطلبات
</button>


<button class="tab-btn" onclick="showTab(this,'attendance')">
📅 الحضور
</button>

<button class="tab-btn" onclick="showTab(this,'documents')">
📁 المستندات
</button>

</div>

<div id="info" class="tab-content">

<h3>📋 معلومات السائق</h3>

<p>
🪪 الهوية:
<?= e($driver['national_id']) ?>
</p>

<p>
📱 الجوال:
<?= e($driver['phone']) ?>
</p>

<p>
📍 منطقة العمل:
<?= e($driver['work_area']) ?>
</p>

</div>

<div id="orders" class="tab-content" style="display:none">

<h3>📦 طلبات السائق</h3>


<?php

/* =========================
   إحصائيات طلبات السائق
========================= */

$orderStats = mysqli_fetch_assoc(mysqli_query($con,"
SELECT

COUNT(*) total_orders,

SUM(status='done') completed_orders,

SUM(status IN('pending','assigned','on_the_way')) active_orders,

SUM(status='cancelled') cancelled_orders,

SUM(price) total_amount

FROM orders

WHERE driver_id='{$driver['id']}'

"));


$totalDriverOrders = $orderStats['total_orders'] ?? 0;
$completedOrders   = $orderStats['completed_orders'] ?? 0;
$activeOrders      = $orderStats['active_orders'] ?? 0;
$cancelledOrders   = $orderStats['cancelled_orders'] ?? 0;
$totalAmount       = $orderStats['total_amount'] ?? 0;


/* =========================
   جلب طلبات السائق
========================= */

$driverOrders = mysqli_query($con,"

SELECT

orders.*,

fleet.typefleet,

fleet.plate,

invoices.id AS invoice_id

FROM orders


LEFT JOIN fleet

ON fleet.id = orders.fleet_id


LEFT JOIN invoices

ON invoices.order_id = orders.id


WHERE orders.driver_id='{$driver['id']}'

ORDER BY orders.id DESC

");

?>


<div class="fleet-summary">


<div class="summary-card blue">

<h2>
<?= $totalDriverOrders ?>
</h2>

<span>
📦 إجمالي الطلبات
</span>

</div>



<div class="summary-card green">

<h2>
<?= $completedOrders ?>
</h2>

<span>
✅ مكتملة
</span>

</div>



<div class="summary-card blue">

<h2>
<?= $activeOrders ?>
</h2>

<span>
🚚 قيد التنفيذ
</span>

</div>



<div class="summary-card red">

<h2>
<?= $cancelledOrders ?>
</h2>

<span>
❌ ملغاة
</span>

</div>


<div class="summary-card green">

<h2>
<?= number_format($totalAmount,2) ?>
</h2>

<span>
💰 إجمالي القيمة
</span>

</div>


</div>



<?php if(mysqli_num_rows($driverOrders)==0){ ?>


<div class="empty-box">

🚫 لا توجد طلبات لهذا السائق.

</div>


<?php }else{ ?>


<div class="table-responsive">


<table class="vehicle-table">


<thead>

<tr>

<th>
رقم الطلب
</th>

<th>
المسار
</th>



<th>
المبلغ
</th>

<th>
الحالة
</th>

<th>
التاريخ
</th>

<th>
الإجراءات
</th>

</tr>

</thead>


<tbody>


<?php while($order=mysqli_fetch_assoc($driverOrders)){ 



$statusClass = match($order['status']){

'done'=>'success',

'cancelled'=>'danger',

'assigned'=>'primary',

'on_the_way'=>'warning',

default=>'secondary'

};


$statusText = match($order['status']){

'done'=>'✅ مكتمل',

'cancelled'=>'❌ ملغي',

'assigned'=>'🚚 معين',

'on_the_way'=>'🚗 بالطريق',

default=>'⏳ انتظار'

};


?>


<tr>


<td>

<?= $order['order_number'] ?: '#'.$order['id'] ?>

</td>



<td>

<?= $order['from_city'] ?>

➡️

<?= $order['to_city'] ?>

</td>







<td>

<?= number_format($order['price'],2) ?>

ر.س

</td>



<td>

<span class="badge <?= $statusClass ?>">

<?= $statusText ?>

</span>

</td>



<td>

<?= date('Y-m-d',strtotime($order['created_at'])) ?>

</td>



<td>


<a class="view-btn"

href="order_details.php?id=<?= $order['id'] ?>">

👁 تفاصيل

</a>



<?php

$invoice = mysqli_fetch_assoc(mysqli_query($con,"
SELECT id
FROM invoices
WHERE order_id='{$order['id']}'
LIMIT 1
"));

if($invoice){

?>

<a class="view-btn"
style="background:#198754"
href="../invoice.php?id=<?= $order['id'] ?>"
target="_blank">

📄 فاتورة

</a>

<?php } ?>





</td>


</tr>


<?php } ?>


</tbody>


</table>


</div>


<?php } ?>


</div>


<div id="vehicles" class="tab-content" style="display:none">

<h3>🚚 المركبات المرتبطة بالسائق</h3>

<?php

$stmtFleet = $con->prepare("
SELECT *
FROM fleet
WHERE driver = ?
ORDER BY id DESC
");

$stmtFleet->bind_param("s",$driver['name']);

$stmtFleet->execute();

$fleet = $stmtFleet->get_result();




$totalFleet = $fleet->num_rows;

$activeDocs = 0;
$expiredDocs = 0;
$nearDocs = 0;


$fleet->data_seek(0);


while($row = $fleet->fetch_assoc()){


    foreach([
        $row['insurance_expiration_date'],
        $row['inspection_expiry'],
        $row['operation_expiry']
    ] as $date){


        $status = documentStatus($date);


        if($status['class']=='success'){
            $activeDocs++;
        }

        elseif($status['class']=='warning'){
            $nearDocs++;
        }

        elseif($status['class']=='danger'){
            $expiredDocs++;
        }

    }

}


$fleet->data_seek(0);

?>


<div class="fleet-summary">


<div class="summary-card blue">

<h2>
<?= $totalFleet ?>
</h2>

<span>
🚚 إجمالي المركبات
</span>

</div>



<div class="summary-card green">

<h2>
<?= $activeDocs ?>
</h2>

<span>
🟢 مستندات سارية
</span>

</div>



<div class="summary-card red">

<h2>
<?= $expiredDocs ?>
</h2>

<span>
🔴 مستندات منتهية
</span>

</div>


</div>





<?php if($totalFleet == 0){ ?>


<div class="empty-box">
🚫 لا توجد مركبات مرتبطة بهذا السائق.
</div>


<?php }else{ ?>


<div class="vehicle-grid">


<?php while($car = $fleet->fetch_assoc()){ ?>


<?php

$insurance = documentStatus($car['insurance_expiration_date']);

$inspection = documentStatus($car['inspection_expiry']);

$operation = documentStatus($car['operation_expiry']);

?>

<div class="vehicle-card">



<?php

$image="../fleetimg/img/".$car['imgfleet'];

if(!empty($car['imgfleet']) && file_exists($image)){

?>

<img src="<?= $image ?>">

<?php }else{ ?>

<img src="../assets/no-image.png">

<?php } ?>


<div class="vehicle-body">


<div class="vehicle-title">
🚚 <?= e($car['typefleet']) ?>
</div>


<div class="vehicle-item">
🚛 اللوحة:
<?= e($car['plate']) ?>
</div>


<div class="vehicle-item">
📅 الموديل:
<?= e($car['model']) ?>
</div>


<div class="vehicle-item">
🎨 اللون:
<?= e($car['colorfleet']) ?>
</div>


<div class="vehicle-item">
⚙️ منطقة العمل:
<?= e($car['work']) ?>
</div>
<?php

$insurance = documentStatus($car['insurance_expiration_date']);

$inspection = documentStatus($car['inspection_expiry']);

$operation = documentStatus($car['operation_expiry']);

?>


<div class="vehicle-item">
🛡 التأمين:

<span class="badge <?= $insurance['class'] ?>">
<?= $insurance['text'] ?>
</span>

</div>


<div class="vehicle-item">
🔍 الفحص:

<span class="badge <?= $inspection['class'] ?>">
<?= $inspection['text'] ?>
</span>

</div>


<div class="vehicle-item">
📄 التشغيل:

<span class="badge <?= $operation['class'] ?>">
<?= $operation['text'] ?>
</span>

</div>

<div class="vehicle-footer">

<a class="view-btn"
href="../admin/fleet_details.php?id=<?= $car['id'] ?>">
🔍 فتح الملف
</a>

</div>


</div>


</div>


<?php } ?>


</div>


<?php } ?>


</div>

<div id="attendance" class="tab-content" style="display:none">

<h3>📅 سجل حضور السائق</h3>

<?php

$attendanceStats = mysqli_fetch_assoc(mysqli_query($con,"
SELECT

COUNT(*) total,

SUM(status='present') present_count,

SUM(status='late') late_count,

SUM(status='absent') absent_count

FROM attendance

WHERE driver_id='{$driver['id']}'

"));


$totalAttendance = $attendanceStats['total'] ?? 0;
$presentCount    = $attendanceStats['present_count'] ?? 0;
$lateCount       = $attendanceStats['late_count'] ?? 0;
$absentCount     = $attendanceStats['absent_count'] ?? 0;


?>
<div class="fleet-summary">


<div class="summary-card blue">

<h2>
<?= $totalAttendance ?>
</h2>

<span>
📅 إجمالي السجلات
</span>

</div>



<div class="summary-card green">

<h2>
<?= $presentCount ?>
</h2>

<span>
✅ حاضر
</span>

</div>



<div class="summary-card blue">

<h2>
<?= $lateCount ?>
</h2>

<span>
⏰ متأخر
</span>

</div>



<div class="summary-card red">

<h2>
<?= $absentCount ?>
</h2>

<span>
❌ غياب
</span>

</div>


</div>
<?php

$attendance = mysqli_query($con,"
SELECT *
FROM attendance
WHERE driver_id='{$driver['id']}'
ORDER BY attendance_date DESC
");


?>


<?php if(mysqli_num_rows($attendance)==0){ ?>


<div class="empty-box">

🚫 لا توجد سجلات حضور لهذا السائق.

</div>


<?php }else{ ?>


<div class="table-responsive">


<table class="vehicle-table">


<thead>

<tr>

<th>
📅 التاريخ
</th>

<th>
🟢 الدخول
</th>

<th>
🔴 الخروج
</th>

<th>
الحالة
</th>

</tr>

</thead>


<tbody>


<?php while($att=mysqli_fetch_assoc($attendance)){ ?>


<tr>


<td>

<?= $att['attendance_date'] ?>

</td>


<td>

<?= $att['check_in'] ?: '-' ?>

</td>


<td>

<?= $att['check_out'] ?: '-' ?>

</td>


<td>


<?php

$statusText = match($att['status']){

'present'=>'✅ حاضر',

'late'=>'⏰ متأخر',

'absent'=>'❌ غائب',

default=>'غير محدد'

};


$statusClass = match($att['status']){

'present'=>'success',

'late'=>'warning',

'absent'=>'danger',

default=>'secondary'

};


?>


<span class="badge <?= $statusClass ?>">

<?= $statusText ?>

</span>


</td>


</tr>


<?php } ?>


</tbody>


</table>


</div>


<?php } ?>


</div>


<div id="documents" class="tab-content" style="display:none">

<h3>📁 مستندات السائق</h3>


<?php

$documentTypes = [

    'license'=>[
        'name'=>'رخصة القيادة',
        'icon'=>'🪪'
    ],

    'iqama'=>[
        'name'=>'الإقامة',
        'icon'=>'🆔'
    ],

    'driver_card'=>[
        'name'=>'بطاقة السائق',
        'icon'=>'🎫'
    ],

    'passport'=>[
        'name'=>'جواز السفر',
        'icon'=>'📘'
    ]

];

?>


<div class="fleet-summary">


<div class="summary-card blue">

<h2>
<?= $totalDocuments ?>
</h2>

<span>
📁 إجمالي المستندات
</span>

</div>


<div class="summary-card green">

<h2>
<?= $activeDocuments ?>
</h2>

<span>
🟢 سارية
</span>

</div>


<div class="summary-card blue">

<h2>
<?= $nearDocuments ?>
</h2>

<span>
🟡 تنتهي قريباً
</span>

</div>


<div class="summary-card red">

<h2>
<?= $expiredDocuments ?>
</h2>

<span>
🔴 منتهية
</span>

</div>


</div>



<div class="document-grid">


<?php foreach($documentTypes as $type=>$info){ ?>


<?php

$found = null;


/* البحث عن المستند */

mysqli_data_seek($documents,0);

while($d = mysqli_fetch_assoc($documents)){

    if($d['document_type']==$type){

        $found=$d;
        break;

    }

}



if($found){

$status = documentStatus($found['expiry_date']);

}else{

$status=[
'text'=>'غير مرفوع',
'class'=>'secondary'
];

}

?>


<div class="document-card">


<div class="document-preview">


<?php if($found){ ?>


<?php

$file = "../uploads/drivers/".$driver['id']."/".$found['file_name'];

$ext = strtolower(pathinfo($found['file_name'],PATHINFO_EXTENSION));


if(in_array($ext,['jpg','jpeg','png'])){

?>

<img src="<?= $file ?>">


<?php }else{ ?>

<div class="pdf-icon">
📄
</div>

<?php } ?>


<?php }else{ ?>


<div class="pdf-icon">
📁
</div>


<?php } ?>


</div>



<div class="document-body">


<div class="document-title">

<?= $info['icon'] ?>

<?= $info['name'] ?>

</div>



<div class="vehicle-item">

📄 الرقم:

<br>

<?= $found['document_number'] ?? '---' ?>

</div>



<div class="vehicle-item">

📅 الانتهاء:

<br>

<?= $found['expiry_date'] ?? '---' ?>

</div>



<div class="vehicle-item">

<span class="badge <?= $status['class'] ?>">

<?= $status['text'] ?>

</span>

</div>




<div class="document-actions">


<?php if($found){ ?>


<a class="doc-btn doc-view"
data-title="عرض"
href="view_driver_document.php?id=<?= $found['id'] ?>">
👁
</a>



<a class="doc-btn doc-download"
data-title="تحميل"
href="<?= $file ?>"
download>
⬇
</a>



<a class="doc-btn doc-print"
data-title="طباعة"
href="view_driver_document.php?id=<?= $found['id'] ?>"
target="_blank">
🖨
</a>



<a class="doc-btn doc-replace"
data-title="استبدال"
href="edit_driver_document.php?id=<?= $found['id'] ?>">
🔄
</a>



<a class="doc-btn doc-delete"
data-title="حذف"
href="delete_driver_document.php?id=<?= $found['id'] ?>"
onclick="return confirm('حذف المستند؟')">
🗑
</a>


<?php }else{ ?>


<a class="doc-btn doc-upload"
data-title="رفع المستند"
href="add_driver_document.php?driver_id=<?= $driver['id'] ?>&type=<?= $type ?>">
⬆
</a>


<?php } ?>


</div>


</div>


</div>


<?php } ?>


</div>


</div>

<script>
function showTab(button, tab){

    console.log("فتح التبويب:", tab);

    document.querySelectorAll(".tab-content").forEach(function(el){
        el.style.display = "none";
    });

    document.querySelectorAll(".tab-btn").forEach(function(btn){
        btn.classList.remove("active");
    });

    var page = document.getElementById(tab);

    if(page){
        page.style.display = "block";
    }

    button.classList.add("active");
}
</script>
</body>

</html>