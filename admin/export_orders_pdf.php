<?php

/* =========================================================
   EXPORT ORDERS PDF
   يحافظ على نفس البحث والفلاتر الموجودة في ordersview.php
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   الملفات الأساسية
========================================================= */

require_once __DIR__ . '/../include/core.php';
require_once __DIR__ . '/../include/connected.php';


/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

$lang = in_array($lang, ['ar', 'en'], true)
    ? $lang
    : 'ar';

$_SESSION['lang'] = $lang;


/* =========================================================
   تحميل mPDF
========================================================= */

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {

    die(
        'خطأ: لم يتم العثور على Composer autoload.'
    );
}

require_once $autoload;


/* =========================================================
   إعداد الاتصال
========================================================= */

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   دالة تنظيف
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   استقبال نفس الفلاتر من ordersview.php
========================================================= */

$search =
    trim($_GET['search'] ?? '');

$filter_status =
    trim($_GET['status'] ?? '');

$filter =
    trim($_GET['filter'] ?? 'all');

$approval_filter =
    trim($_GET['approval_status'] ?? '');

$order_type =
    trim($_GET['order_type'] ?? '');


/* =========================================================
   بناء شروط البحث والفلاتر
========================================================= */

$where = "WHERE 1";


/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $search_safe =
        mysqli_real_escape_string(
            $con,
            $search
        );

    $where .= "
        AND (
            orders.full_name LIKE '%$search_safe%'
            OR orders.phone LIKE '%$search_safe%'
            OR orders.from_city LIKE '%$search_safe%'
            OR orders.to_city LIKE '%$search_safe%'
            OR orders.order_number LIKE '%$search_safe%'
        )
    ";
}


/* =========================================================
   حالة الطلب
========================================================= */

$allowed_status = [
    'pending',
    'assigned',
    'done',
    'cancelled'
];

if (
    $filter_status !== '' &&
    in_array(
        $filter_status,
        $allowed_status,
        true
    )
) {

    $status_safe =
        mysqli_real_escape_string(
            $con,
            $filter_status
        );

    $where .= "
        AND orders.status = '$status_safe'
    ";
}


/* =========================================================
   نوع الحجز
========================================================= */

if ($filter === 'scheduled') {

    $where .= "
        AND orders.booking_type = 'scheduled'
    ";

} elseif ($filter === 'instant') {

    $where .= "
        AND orders.booking_type = 'instant'
    ";
}


/* =========================================================
   نوع الطلب
========================================================= */

if ($order_type !== '') {

    $order_type_safe =
        mysqli_real_escape_string(
            $con,
            $order_type
        );

    $where .= "
        AND orders.order_type = '$order_type_safe'
    ";
}


/* =========================================================
   حالة الموافقة
========================================================= */

if (
    in_array(
        $approval_filter,
        [
            'pending',
            'approved',
            'rejected'
        ],
        true
    )
) {

    $approval_safe =
        mysqli_real_escape_string(
            $con,
            $approval_filter
        );

    $where .= "
        AND orders.approval_status = '$approval_safe'
    ";
}


/* =========================================================
   جلب الطلبات
   بدون Pagination
========================================================= */

$query = "

    SELECT

        orders.*,

        drivers.name AS driver_name

    FROM orders

    LEFT JOIN drivers
        ON drivers.id = orders.driver_id

    $where

    ORDER BY orders.id DESC

";


$result =
    mysqli_query(
        $con,
        $query
    );


if (!$result) {

    die(
        'Database Error: ' .
        e(mysqli_error($con))
    );
}


/* =========================================================
   الإحصائيات الخاصة بالنتائج المفلترة
========================================================= */

$totalOrders = 0;
$totalSales  = 0;

$pendingOrders   = 0;
$assignedOrders  = 0;
$doneOrders      = 0;
$cancelledOrders = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $totalOrders++;

    $totalSales +=
        (float)($row['price'] ?? 0);

    switch (
        $row['status'] ?? ''
    ) {

        case 'pending':
            $pendingOrders++;
            break;

        case 'assigned':
            $assignedOrders++;
            break;

        case 'done':
            $doneOrders++;
            break;

        case 'cancelled':
            $cancelledOrders++;
            break;
    }

}


/* =========================================================
   إعادة تنفيذ الاستعلام للحصول على البيانات
========================================================= */

$result =
    mysqli_query(
        $con,
        $query
    );


/* =========================================================
   عنوان الفلاتر
========================================================= */

$filterDescription = [];


if ($search !== '') {

    $filterDescription[] =
        'البحث: ' . e($search);
}


if ($filter_status !== '') {

    $statusNames = [

        'pending'   => 'قيد الانتظار',
        'assigned'  => 'تم الإسناد',
        'done'      => 'مكتمل',
        'cancelled' => 'ملغي'

    ];

    $filterDescription[] =
        'الحالة: ' .
        e(
            $statusNames[$filter_status]
            ?? $filter_status
        );
}


if ($filter === 'instant') {

    $filterDescription[] =
        'نوع الحجز: فوري';

} elseif ($filter === 'scheduled') {

    $filterDescription[] =
        'نوع الحجز: مجدول';
}


if ($approval_filter !== '') {

    $approvalNames = [

        'pending'  => 'بانتظار الموافقة',
        'approved' => 'تمت الموافقة',
        'rejected' => 'مرفوض'

    ];

    $filterDescription[] =
        'الموافقة: ' .
        e(
            $approvalNames[$approval_filter]
            ?? $approval_filter
        );
}


if ($order_type !== '') {

    $filterDescription[] =
        'نوع الطلب: ' .
        e($order_type);
}


$filterText =
    !empty($filterDescription)
    ? implode(' | ', $filterDescription)
    : 'جميع الطلبات';


/* =========================================================
   HTML PDF
========================================================= */

ob_start();

?>

<!DOCTYPE html>

<html
    lang="ar"
    dir="rtl"
>

<head>

<meta charset="UTF-8">

<style>

@page {

    margin:
        12mm
        10mm
        12mm
        10mm;

}

body {

    font-family:
        dejavusans,
        sans-serif;

    direction: rtl;

    text-align: right;

    font-size: 9px;

    color: #222;

}


.header {

    text-align: center;

    margin-bottom: 15px;

    border-bottom:
        2px solid #222;

    padding-bottom: 10px;

}


.header h1 {

    margin: 0;

    font-size: 20px;

}


.header p {

    margin:
        5px 0 0;

    color: #666;

    font-size: 9px;

}


.filters {

    background: #f5f5f5;

    border:
        1px solid #ddd;

    padding: 8px;

    margin-bottom: 12px;

    font-size: 9px;

}


.stats {

    width: 100%;

    border-collapse:
        collapse;

    margin-bottom: 12px;

}


.stats td {

    border:
        1px solid #ddd;

    padding: 7px;

    text-align: center;

}


.stats-title {

    display: block;

    color: #666;

    font-size: 8px;

}


.stats-value {

    display: block;

    font-size: 12px;

    font-weight: bold;

    margin-top: 3px;

}


.orders {

    width: 100%;

    border-collapse:
        collapse;

    table-layout: fixed;

}


.orders th {

    background: #eeeeee;

    border:
        1px solid #999;

    padding: 6px 3px;

    text-align: center;

    font-weight: bold;

}


.orders td {

    border:
        1px solid #ccc;

    padding: 5px 3px;

    text-align: center;

    vertical-align: middle;

    word-wrap: break-word;

}


.orders tr:nth-child(even) td {

    background: #fafafa;

}


.status {

    font-weight: bold;

}


.footer {

    margin-top: 15px;

    padding-top: 8px;

    border-top:
        1px solid #ccc;

    text-align: center;

    font-size: 8px;

    color: #777;

}


.total {

    font-weight: bold;

    font-size: 10px;

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<h1>
تقرير الطلبات
</h1>

<p>
منصة الشرق الذكية للخدمات وإدارة الأسطول
</p>

<p>
تاريخ التقرير:
<?= date('Y-m-d H:i') ?>
</p>

</div>


<!-- =====================================================
     FILTERS
===================================================== -->

<div class="filters">

<strong>
الفلاتر المطبقة:
</strong>

<?= $filterText ?>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<table class="stats">

<tr>

<td>

<span class="stats-title">
إجمالي الطلبات
</span>

<span class="stats-value">
<?= number_format($totalOrders) ?>
</span>

</td>


<td>

<span class="stats-title">
قيد الانتظار
</span>

<span class="stats-value">
<?= number_format($pendingOrders) ?>
</span>

</td>


<td>

<span class="stats-title">
تم الإسناد
</span>

<span class="stats-value">
<?= number_format($assignedOrders) ?>
</span>

</td>


<td>

<span class="stats-title">
مكتملة
</span>

<span class="stats-value">
<?= number_format($doneOrders) ?>
</span>

</td>


<td>

<span class="stats-title">
ملغاة
</span>

<span class="stats-value">
<?= number_format($cancelledOrders) ?>
</span>

</td>


<td>

<span class="stats-title">
إجمالي الإيرادات
</span>

<span class="stats-value">
<?= number_format($totalSales, 2) ?>
 ر.س
</span>

</td>

</tr>

</table>


<!-- =====================================================
     ORDERS TABLE
===================================================== -->

<table class="orders">

<thead>

<tr>

<th style="width:4%;">
#
</th>

<th style="width:8%;">
رقم الطلب
</th>

<th style="width:10%;">
العميل
</th>

<th style="width:9%;">
الجوال
</th>

<th style="width:9%;">
من
</th>

<th style="width:9%;">
إلى
</th>

<th style="width:7%;">
السعر
</th>

<th style="width:8%;">
الحالة
</th>

<th style="width:8%;">
الموافقة
</th>

<th style="width:9%;">
المزود
</th>

<th style="width:9%;">
تاريخ الطلب
</th>

<th style="width:7%;">
الحجز
</th>

<th style="width:8%;">
موعد الخدمة
</th>

</tr>

</thead>


<tbody>


<?php if (
    !$result ||
    mysqli_num_rows($result) === 0
): ?>

<tr>

<td
    colspan="13"
    style="padding:20px;"
>

لا توجد طلبات مطابقة للفلاتر الحالية.

</td>

</tr>

<?php endif; ?>


<?php if ($result): ?>

<?php while (
    $row =
    mysqli_fetch_assoc($result)
): ?>


<?php

$status =
    $row['status'] ?? 'pending';

$approval =
    $row['approval_status'] ?? 'pending';


$statusNames = [

    'pending' =>
        'انتظار',

    'assigned' =>
        'معين',

    'done' =>
        'مكتمل',

    'cancelled' =>
        'ملغي'

];


$approvalNames = [

    'pending' =>
        'بانتظار الموافقة',

    'approved' =>
        'تمت الموافقة',

    'rejected' =>
        'مرفوض'

];


$bookingType =
    ($row['booking_type'] ?? '') === 'instant'
    ? 'فوري'
    : 'مجدول';


$scheduledDate = '-';


if (
    ($row['booking_type'] ?? '') ===
    'scheduled'
) {

    $scheduledDate =
        ($row['scheduled_date'] ?? '-') .
        ' ' .
        ($row['scheduled_time'] ?? '');
}

?>


<tr>


<td>

<?= (int)$row['id'] ?>

</td>


<td>

<strong>

<?= e(
    $row['order_number']
    ?? ('#' . $row['id'])
) ?>

</strong>

</td>


<td>

<?= e(
    $row['full_name'] ?? ''
) ?>

</td>


<td>

<?= e(
    $row['phone'] ?? ''
) ?>

</td>


<td>

<?= e(
    $row['from_city'] ?? ''
) ?>

</td>


<td>

<?= e(
    $row['to_city'] ?? ''
) ?>

</td>


<td>

<strong>

<?= number_format(
    (float)(
        $row['price'] ?? 0
    ),
    2
) ?>

<br>

ر.س

</strong>

</td>


<td class="status">

<?= e(
    $statusNames[$status]
    ?? $status
) ?>

</td>


<td class="status">

<?php

if ($status === 'done') {

    echo 'مكتمل';

} elseif (
    $status === 'cancelled'
) {

    echo 'ملغي';

} else {

    echo e(
        $approvalNames[$approval]
        ?? $approval
    );
}

?>

</td>


<td>

<?= !empty($row['driver_name'])
    ? e($row['driver_name'])
    : 'غير محدد'
?>

</td>


<td>

<?= e(
    $row['created_at'] ?? ''
) ?>

</td>


<td>

<?= e($bookingType) ?>

</td>


<td>

<?= e($scheduledDate) ?>

</td>


</tr>


<?php endwhile; ?>

<?php endif; ?>


</tbody>

</table>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="footer">

<div class="total">

إجمالي الطلبات:
<?= number_format($totalOrders) ?>

&nbsp;&nbsp; | &nbsp;&nbsp;

إجمالي الإيرادات:
<?= number_format($totalSales, 2) ?>
ر.س

</div>

<br>

تم إنشاء التقرير آليًا بواسطة منصة الشرق الذكية

</div>


</body>

</html>

<?php

$html =
    ob_get_clean();


/* =========================================================
   إنشاء PDF
========================================================= */

try {

    $mpdf = new \Mpdf\Mpdf([

        'mode' =>
            'utf-8',

        'format' =>
            'A4-L',

        'orientation' =>
            'L',

        'margin_top' =>
            10,

        'margin_bottom' =>
            10,

        'margin_left' =>
            8,

        'margin_right' =>
            8,

        'default_font' =>
            'dejavusans'

    ]);


    $mpdf->SetTitle(
        'تقرير الطلبات'
    );


    $mpdf->SetAuthor(
        'AlSharqPlatform'
    );


    $mpdf->WriteHTML(
        $html
    );


    $filename =
        'orders_' .
        date('Y-m-d_H-i-s') .
        '.pdf';


    $mpdf->Output(
        $filename,
        \Mpdf\Output\Destination::INLINE
    );

    exit;


} catch (Throwable $e) {

    error_log(
        'Orders PDF Error: ' .
        $e->getMessage()
    );


    die(
        'حدث خطأ أثناء إنشاء ملف PDF: ' .
        e($e->getMessage())
    );
}