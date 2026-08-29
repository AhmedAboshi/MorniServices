<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   اللغة
========================================================= */

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], ['ar', 'en'], true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'title'             => 'تقرير الطلبات',
        'subtitle'          => 'تحليل الطلبات والإيرادات حسب السائق ونوع الطلب',

        'from'              => 'من تاريخ',
        'to'                => 'إلى تاريخ',

        'order_type'        => 'نوع الطلب',
        'driver'            => 'السائق',
        'all'               => 'الكل',
        'all_drivers'       => 'جميع السائقين',

        'search'            => 'بحث',
        'search_inside'     => 'بحث داخل التقرير...',

        'filter'            => 'تطبيق الفلاتر',
        'reset'             => 'إعادة ضبط',

        'print'             => 'طباعة',
        'excel'             => 'Excel',
        'pdf'               => 'PDF',

        'total_orders'      => 'إجمالي الطلبات',
        'total_revenue'     => 'إجمالي الإيرادات',
        'total_drivers'     => 'السائقون',
        'average_order'     => 'متوسط قيمة الطلب',
        'top_driver'        => 'أعلى سائق إيراداً',

        'summary'           => 'التقرير المجمع',

        'driver_code'       => 'رمز السائق',
        'name'              => 'اسم السائق',
        'phone'             => 'الجوال',
        'type'              => 'نوع الطلب',
        'orders'            => 'عدد الطلبات',
        'total'             => 'الإجمالي',

        'intercity'         => 'بين المدن',
        'cart'              => 'نقل',
        'tow'               => 'سطحة / سحب',

        'company'           => 'شركة الشرق لخدمات السيارات',

        'records'           => 'السجلات',
        'sar'               => 'ريال',
        'no_data'           => 'لا توجد نتائج مطابقة للفلاتر',

        'report_date'       => 'تاريخ التقرير'
    ],

    'en' => [

        'title'             => 'Orders Report',
        'subtitle'          => 'Orders and revenue analysis by driver and order type',

        'from'              => 'From Date',
        'to'                => 'To Date',

        'order_type'        => 'Order Type',
        'driver'            => 'Driver',
        'all'               => 'All',
        'all_drivers'       => 'All Drivers',

        'search'            => 'Search',
        'search_inside'     => 'Search inside report...',

        'filter'            => 'Apply Filters',
        'reset'             => 'Reset',

        'print'             => 'Print',
        'excel'             => 'Excel',
        'pdf'               => 'PDF',

        'total_orders'      => 'Total Orders',
        'total_revenue'     => 'Total Revenue',
        'total_drivers'     => 'Drivers',
        'average_order'     => 'Average Order Value',
        'top_driver'        => 'Top Revenue Driver',

        'summary'           => 'Summary Report',

        'driver_code'       => 'Driver Code',
        'name'              => 'Driver Name',
        'phone'             => 'Phone',
        'type'              => 'Order Type',
        'orders'            => 'Orders',
        'total'             => 'Total',

        'intercity'         => 'Intercity',
        'cart'              => 'Transport',
        'tow'               => 'Tow',

        'company'           => 'Al Sharq Automotive Services Company',

        'records'           => 'Records',
        'sar'               => 'SAR',
        'no_data'           => 'No results match the selected filters',

        'report_date'       => 'Report Date'
    ]
];

$tr = $t[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$from_date  = trim($_GET['from_date'] ?? '');
$to_date    = trim($_GET['to_date'] ?? '');
$order_type = trim($_GET['order_type'] ?? '');
$driver_id  = (int)($_GET['driver_id'] ?? 0);
$search     = trim($_GET['search'] ?? '');

/* =========================================================
   أنواع الطلبات
========================================================= */

$orderTypes = [

    'intercity' => $tr['intercity'],
    'cart'      => $tr['cart'],
    'tow'       => $tr['tow']
];

/* =========================================================
   شروط الاستعلام الرئيسي
========================================================= */

$where = [];
$params = [];
$types = '';

/*
 * البحث
 */

if ($search !== '') {

    $where[] = "
        (
            d.name LIKE ?
            OR d.phone LIKE ?
            OR CAST(d.id AS CHAR) LIKE ?
            OR o.order_type LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ssss';
}

/*
 * التاريخ
 */

if ($from_date !== '') {

    $where[] = "DATE(o.created_at) >= ?";

    $params[] = $from_date;

    $types .= 's';
}

if ($to_date !== '') {

    $where[] = "DATE(o.created_at) <= ?";

    $params[] = $to_date;

    $types .= 's';
}

/*
 * نوع الطلب
 */

if (
    $order_type !== '' &&
    array_key_exists($order_type, $orderTypes)
) {

    $where[] = "o.order_type = ?";

    $params[] = $order_type;

    $types .= 's';
}

/*
 * السائق
 */

if ($driver_id > 0) {

    $where[] = "d.id = ?";

    $params[] = $driver_id;

    $types .= 'i';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}

/* =========================================================
   التقرير المجمع
========================================================= */

$sql = "

SELECT

    d.id AS driver_code,

    d.name,

    d.phone,

    o.order_type,

    COUNT(DISTINCT o.id) AS total_orders,

    COALESCE(
        SUM(o.price),
        0
    ) AS total_revenue

FROM drivers d

INNER JOIN orders o
    ON d.id = o.driver_id

$whereSql

GROUP BY
    d.id,
    d.name,
    d.phone,
    o.order_type

ORDER BY
    total_revenue DESC,
    d.name ASC

";

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   النتائج
========================================================= */

$rows = [];

$totalOrders = 0;
$totalRevenue = 0;
$driversSet = [];

while ($row = $result->fetch_assoc()) {

    $row['total_orders'] =
        (int)($row['total_orders'] ?? 0);

    $row['total_revenue'] =
        (float)($row['total_revenue'] ?? 0);

    $rows[] = $row;

    $totalOrders +=
        $row['total_orders'];

    $totalRevenue +=
        $row['total_revenue'];

    $driversSet[
        $row['driver_code']
    ] = true;
}

$stmt->close();

$totalDrivers =
    count($driversSet);

$averageOrder =
    $totalOrders > 0
        ? $totalRevenue / $totalOrders
        : 0;

/* =========================================================
   أعلى سائق
========================================================= */

$driverTotals = [];

foreach ($rows as $row) {

    $driverCode =
        $row['driver_code'];

    if (!isset($driverTotals[$driverCode])) {

        $driverTotals[$driverCode] = [

            'name' =>
                $row['name'],

            'revenue' =>
                0
        ];
    }

    $driverTotals[$driverCode]['revenue']
        += $row['total_revenue'];
}

$topDriverName = '-';
$topDriverRevenue = 0;

foreach ($driverTotals as $item) {

    if (
        $item['revenue']
        >
        $topDriverRevenue
    ) {

        $topDriverRevenue =
            $item['revenue'];

        $topDriverName =
            $item['name'];
    }
}

/* =========================================================
   قائمة السائقين للفلاتر
========================================================= */

$drivers = [];

$driversResult = $con->query("
    SELECT
        id,
        name
    FROM drivers
    ORDER BY name ASC
");

if ($driversResult) {

    while (
        $driver =
        $driversResult->fetch_assoc()
    ) {

        $drivers[] = $driver;
    }
}

/* =========================================================
   روابط التصدير
========================================================= */

$exportParams = [

    'lang'       => $lang,
    'from_date'  => $from_date,
    'to_date'    => $to_date,
    'order_type' => $order_type,
    'driver_id'  => $driver_id,
    'search'     => $search
];

$excelUrl =
    'orders_report_excel.php?' .
    http_build_query($exportParams);

$pdfUrl =
    'orders_report_pdf.php?' .
    http_build_query($exportParams);

$resetUrl =
    'driver_revenue.php?' .
    http_build_query([
        'lang' => $lang
    ]);

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($tr['title']) ?>
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f4f7fb;

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;

    color:#1f2937;
}

.container-report{

    max-width:1550px;

    margin:30px auto;

    padding:0 18px;
}

/* HEADER */

.header{

    background:
        linear-gradient(
            135deg,
            #007bff,
            #0047ab
        );

    color:#fff;

    border-radius:20px;

    padding:25px;

    margin-bottom:20px;

    text-align:center;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.12);
}

.logo{

    width:80px;

    height:80px;

    object-fit:contain;

    background:#fff;

    padding:8px;

    border-radius:20px;
}

.company{

    margin-top:10px;

    font-size:25px;

    font-weight:800;
}

.title{

    margin-top:5px;

    font-size:19px;

    opacity:.95;
}

.subtitle{

    margin-top:7px;

    font-size:12px;

    opacity:.85;
}

/* TOP ACTIONS */

.top-actions{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:10px;

    flex-wrap:wrap;

    margin-bottom:15px;
}

.lang-btns{

    display:flex;

    gap:6px;
}

.lang-btns a{

    text-decoration:none;

    padding:8px 15px;

    border-radius:9px;

    background:#fff;

    color:#007bff;

    font-weight:700;

    border:1px solid #ddd;
}

.lang-btns a:hover{

    background:#007bff;

    color:#fff;
}

.tools{

    display:flex;

    gap:7px;

    flex-wrap:wrap;
}

/* FILTER */

.filter-card{

    background:#fff;

    padding:20px;

    border-radius:18px;

    margin-bottom:20px;

    box-shadow:
        0 4px 18px
        rgba(0,0,0,.07);
}

.form-control,
.form-select{

    min-height:43px;

    border-radius:9px;
}

/* BUTTONS */

.btn{

    border-radius:9px;

    font-weight:700;
}

/* STATS */

.stats{

    display:grid;

    grid-template-columns:
        repeat(5,1fr);

    gap:14px;

    margin-bottom:20px;
}

.stat-card{

    color:#fff;

    border-radius:16px;

    padding:18px;

    min-height:125px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.08);
}

.stat-icon{

    width:44px;

    height:44px;

    border-radius:12px;

    background:
        rgba(255,255,255,.18);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;

    margin-bottom:8px;
}

.stat-title{

    font-size:12px;

    opacity:.9;
}

.stat-value{

    font-size:22px;

    font-weight:800;

    margin-top:3px;
}

.card-top{

    background:
        linear-gradient(
            135deg,
            #6f42c1,
            #512da8
        );
}

/* SEARCH */

.table-tools{

    background:#fff;

    padding:15px;

    border-radius:15px;

    margin-bottom:15px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);
}

/* TABLE */

.table-card{

    background:#fff;

    padding:18px;

    border-radius:18px;

    box-shadow:
        0 4px 18px
        rgba(0,0,0,.06);
}

.table{

    margin-bottom:0;
}

.table th{

    background:#007bff !important;

    color:#fff;

    font-size:12px;

    white-space:nowrap;

    padding:13px;
}

.table td{

    font-size:12px;

    padding:12px;

    vertical-align:middle;
}

.table tbody tr:hover{

    background:#f8fbff;
}

.money{

    color:#198754;

    font-weight:800;
}

.total-cell{

    font-size:14px;

    font-weight:800;
}

.driver-code{

    font-weight:800;

    color:#0d6efd;
}

/* EMPTY */

.empty-state{

    padding:50px;

    text-align:center;

    color:#777;
}

.empty-state i{

    display:block;

    font-size:42px;

    margin-bottom:10px;
}

/* RESPONSIVE */

@media(max-width:1200px){

    .stats{

        grid-template-columns:
            repeat(3,1fr);
    }
}

@media(max-width:800px){

    .stats{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:600px){

    .stats{

        grid-template-columns:1fr;
    }

    .company{

        font-size:20px;
    }

    .title{

        font-size:16px;
    }
}

/* PRINT */

@media print{

    .no-print,
    .filter-card,
    .table-tools,
    .top-actions{

        display:none !important;
    }

    body{

        background:#fff;
    }

    .container-report{

        max-width:100%;

        padding:0;
    }

    .header{

        box-shadow:none;
    }

    .table-card{

        box-shadow:none;
    }
}

</style>

</head>

<body>

<div class="container-report">

<!-- =====================================================
     TOP ACTIONS
===================================================== -->

<div class="top-actions no-print">

<div class="lang-btns">

<a
    href="?<?= http_build_query([
        'lang' => 'ar',
        'from_date' => $from_date,
        'to_date' => $to_date,
        'order_type' => $order_type,
        'driver_id' => $driver_id,
        'search' => $search
    ]) ?>"
>
    🇸🇦 العربية
</a>

<a
    href="?<?= http_build_query([
        'lang' => 'en',
        'from_date' => $from_date,
        'to_date' => $to_date,
        'order_type' => $order_type,
        'driver_id' => $driver_id,
        'search' => $search
    ]) ?>"
>
    🇬🇧 English
</a>

</div>

<div class="tools">

<a
    href="<?= htmlspecialchars($excelUrl) ?>"
    class="btn btn-success"
>
    <i class="bi bi-file-earmark-excel"></i>
    <?= $tr['excel'] ?>
</a>

<a
    href="<?= htmlspecialchars($pdfUrl) ?>"
    target="_blank"
    class="btn btn-danger"
>
    <i class="bi bi-file-earmark-pdf"></i>
    <?= $tr['pdf'] ?>
</a>

<button
    type="button"
    onclick="window.print()"
    class="btn btn-warning"
>
    <i class="bi bi-printer"></i>
    <?= $tr['print'] ?>
</button>

</div>

</div>

<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<img
    src="../img/logo.jpg"
    class="logo"
    alt="Logo"
>

<div class="company">

<?= htmlspecialchars(
    $tr['company']
) ?>

</div>

<div class="title">

📊 <?= htmlspecialchars(
    $tr['title']
) ?>

</div>

<div class="subtitle">

<?= htmlspecialchars(
    $tr['subtitle']
) ?>

</div>

</div>

<!-- =====================================================
     FILTERS
===================================================== -->

<div class="filter-card no-print">

<form method="GET">

<input
    type="hidden"
    name="lang"
    value="<?= htmlspecialchars($lang) ?>"
>

<div class="row g-3">

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['from'] ?>

</label>

<input
    type="date"
    name="from_date"
    class="form-control"
    value="<?= htmlspecialchars($from_date) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['to'] ?>

</label>

<input
    type="date"
    name="to_date"
    class="form-control"
    value="<?= htmlspecialchars($to_date) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['order_type'] ?>

</label>

<select
    name="order_type"
    class="form-select"
>

<option value="">

<?= $tr['all'] ?>

</option>

<?php foreach ($orderTypes as $value => $label): ?>

<option
    value="<?= htmlspecialchars($value) ?>"
    <?= $order_type === $value
        ? 'selected'
        : ''
    ?>
>
    <?= htmlspecialchars($label) ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-lg-3 col-md-6">

<label class="form-label">

<?= $tr['driver'] ?>

</label>

<select
    name="driver_id"
    class="form-select"
>

<option value="0">

<?= $tr['all_drivers'] ?>

</option>

<?php foreach ($drivers as $driver): ?>

<option
    value="<?= (int)$driver['id'] ?>"
    <?= $driver_id === (int)$driver['id']
        ? 'selected'
        : ''
    ?>
>

<?= htmlspecialchars(
    $driver['name']
) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['search'] ?>

</label>

<input
    type="text"
    name="search"
    class="form-control"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="<?= htmlspecialchars($tr['search']) ?>"
>

</div>

<div class="col-lg-1 col-md-6 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>
    <i class="bi bi-search"></i>
</button>

</div>

</div>

<div class="mt-3">

<a
    href="<?= htmlspecialchars($resetUrl) ?>"
    class="btn btn-outline-secondary btn-sm"
>

<i class="bi bi-arrow-counterclockwise"></i>

<?= $tr['reset'] ?>

</a>

</div>

</form>

</div>

<!-- =====================================================
     STATS
===================================================== -->

<div class="stats">

<div class="stat-card bg-primary">

<div class="stat-icon">

<i class="bi bi-receipt"></i>

</div>

<div class="stat-title">

<?= $tr['total_orders'] ?>

</div>

<div class="stat-value">

<?= number_format($totalOrders) ?>

</div>

</div>


<div class="stat-card bg-success">

<div class="stat-icon">

<i class="bi bi-cash-stack"></i>

</div>

<div class="stat-title">

<?= $tr['total_revenue'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $totalRevenue,
    2
) ?>

<?= $tr['sar'] ?>

</div>

</div>


<div class="stat-card bg-info">

<div class="stat-icon">

<i class="bi bi-people"></i>

</div>

<div class="stat-title">

<?= $tr['total_drivers'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $totalDrivers
) ?>

</div>

</div>


<div class="stat-card bg-warning text-dark">

<div class="stat-icon">

<i class="bi bi-calculator"></i>

</div>

<div class="stat-title">

<?= $tr['average_order'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $averageOrder,
    2
) ?>

<?= $tr['sar'] ?>

</div>

</div>


<div class="stat-card card-top">

<div class="stat-icon">

<i class="bi bi-trophy"></i>

</div>

<div class="stat-title">

<?= $tr['top_driver'] ?>

</div>

<div class="stat-value">

<?= htmlspecialchars(
    $topDriverName
) ?>

</div>

<?php if ($topDriverRevenue > 0): ?>

<small>

<?= number_format(
    $topDriverRevenue,
    2
) ?>

<?= $tr['sar'] ?>

</small>

<?php endif; ?>

</div>

</div>

<!-- =====================================================
     TABLE SEARCH
===================================================== -->

<div class="table-tools no-print">

<div class="row align-items-center">

<div class="col-md-6">

<input
    type="text"
    id="tableSearch"
    class="form-control"
    placeholder="🔍 <?= htmlspecialchars($tr['search_inside']) ?>"
>

</div>

<div class="col-md-6 text-md-end mt-2 mt-md-0">

<span class="badge bg-primary fs-6">

<?= $tr['records'] ?>:

<span id="recordCount">

<?= count($rows) ?>

</span>

</span>

</div>

</div>

</div>

<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-card">

<h2 class="mb-3">

<?= $tr['summary'] ?>

</h2>

<div class="table-responsive">

<table
    class="table table-bordered table-hover text-center"
    id="tableData"
>

<thead>

<tr>

<th>#</th>

<th>
<?= $tr['driver_code'] ?>
</th>

<th>
<?= $tr['name'] ?>
</th>

<th>
<?= $tr['phone'] ?>
</th>

<th>
<?= $tr['type'] ?>
</th>

<th>
<?= $tr['orders'] ?>
</th>

<th>
<?= $tr['total'] ?>
</th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

<td colspan="7">

<div class="empty-state">

<i class="bi bi-receipt-cutoff"></i>

<?= htmlspecialchars(
    $tr['no_data']
) ?>

</div>

</td>

</tr>

<?php else: ?>

<?php foreach ($rows as $index => $row): ?>

<?php

$orderTypeLabel =
    $orderTypes[
        $row['order_type']
    ]
    ??
    $row['order_type']
    ??
    '-';

?>

<tr>

<td>

<?= $index + 1 ?>

</td>

<td class="driver-code">

DRV-<?= (int)$row['driver_code'] ?>

</td>

<td>

<strong>

<?= htmlspecialchars(
    $row['name']
) ?>

</strong>

</td>

<td>

<?= htmlspecialchars(
    $row['phone']
    ?? '-'
) ?>

</td>

<td>

<span class="badge bg-secondary">

<?= htmlspecialchars(
    $orderTypeLabel
) ?>

</span>

</td>

<td>

<?= number_format(
    (int)$row['total_orders']
) ?>

</td>

<td class="money">

<?= number_format(
    (float)$row['total_revenue'],
    2
) ?>

<?= $tr['sar'] ?>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

<tfoot>

<tr class="table-success">

<th colspan="5" class="text-end">

<?= $tr['total'] ?>

</th>

<th>

<?= number_format(
    $totalOrders
) ?>

</th>

<th>

<?= number_format(
    $totalRevenue,
    2
) ?>

<?= $tr['sar'] ?>

</th>

</tr>

</tfoot>

</table>

</div>

</div>

</div>

<script>

/* =========================================================
   البحث داخل الجدول
========================================================= */

const tableSearch =
    document.getElementById(
        'tableSearch'
    );

const tableBody =
    document.querySelector(
        '#tableData tbody'
    );

const recordCount =
    document.getElementById(
        'recordCount'
    );

if (tableSearch) {

    tableSearch.addEventListener(
        'input',
        function () {

            const value =
                this.value
                    .toLowerCase()
                    .trim();

            const rows =
                tableBody
                    ? tableBody.querySelectorAll('tr')
                    : [];

            let visible = 0;

            rows.forEach(
                function (row) {

                    const text =
                        row.innerText
                            .toLowerCase();

                    if (
                        text.includes(value)
                    ) {

                        row.style.display = '';

                        visible++;

                    } else {

                        row.style.display =
                            'none';
                    }
                }
            );

            if (recordCount) {

                recordCount.textContent =
                    visible;
            }

        }
    );
}

</script>

</body>

</html>