<?php

session_start();

include('../include/connected.php');

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
   الوضع الليلي
========================================================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$dark = $_SESSION['theme'] ?? 'light';

if (!in_array($dark, ['light', 'dark'], true)) {
    $dark = 'light';
}

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'         => 'تقرير تغيير الزيت',
        'subtitle'      => 'متابعة سجلات تغيير الزيت وتكاليف المركبات',

        'search'        => 'بحث...',
        'driver'        => 'السائق',
        'plate'         => 'رقم اللوحة',
        'vehicle'       => 'المركبة',
        'oil_type'      => 'نوع الزيت',

        'all'           => 'الكل',
        'week'          => 'آخر 7 أيام',
        'month'         => 'آخر 30 يوم',
        'custom'        => 'مخصص',

        'from'          => 'من تاريخ',
        'to'            => 'إلى تاريخ',

        'filter'        => 'تطبيق الفلتر',
        'reset'         => 'إعادة ضبط',

        'print'         => 'طباعة',
        'excel'         => 'Excel',
        'pdf'           => 'PDF',

        'total'         => 'إجمالي السجلات',
        'cost'          => 'إجمالي التكلفة',
        'avg'           => 'متوسط التكلفة',
        'cars'          => 'المركبات',

        'plate_head'    => 'رقم اللوحة',
        'model'         => 'الموديل',
        'driver_head'   => 'السائق',
        'oil_head'      => 'نوع الزيت',
        'date'          => 'تاريخ التغيير',
        'km'            => 'عداد التغيير',
        'current_km'    => 'العداد الحالي',
        'next_km'       => 'العداد القادم',
        'next_date'     => 'التغيير القادم',
        'notes'         => 'الملاحظات',
        'status'        => 'الحالة',

        'good'          => 'ممتاز',
        'soon'          => 'قريب',
        'late'          => 'متأخر',

        'overdue'       => 'متأخر',
        'urgent'        => 'قريب جداً',

        'no_data'       => 'لا توجد سجلات مطابقة للفلاتر',

        'sar'           => 'ريال',

        'previous'      => 'السابق',
        'next'          => 'التالي',

        'language_ar'   => 'عربي',
        'language_en'   => 'English',

        'dark_mode'     => 'الوضع الليلي',
        'light_mode'    => 'الوضع النهاري',

        'records'       => 'سجل'

    ],

    'en' => [

        'title'         => 'Oil Change Report',
        'subtitle'      => 'Monitor oil change records and vehicle costs',

        'search'        => 'Search...',
        'driver'        => 'Driver',
        'plate'         => 'Plate Number',
        'vehicle'       => 'Vehicle',
        'oil_type'      => 'Oil Type',

        'all'           => 'All',
        'week'          => 'Last 7 Days',
        'month'         => 'Last 30 Days',
        'custom'        => 'Custom',

        'from'          => 'From Date',
        'to'            => 'To Date',

        'filter'        => 'Apply Filter',
        'reset'         => 'Reset',

        'print'         => 'Print',
        'excel'         => 'Excel',
        'pdf'           => 'PDF',

        'total'         => 'Total Records',
        'cost'          => 'Total Cost',
        'avg'           => 'Average Cost',
        'cars'          => 'Vehicles',

        'plate_head'    => 'Plate',
        'model'         => 'Model',
        'driver_head'   => 'Driver',
        'oil_head'      => 'Oil Type',
        'date'          => 'Change Date',
        'km'            => 'Change KM',
        'current_km'    => 'Current KM',
        'next_km'       => 'Next KM',
        'next_date'     => 'Next Change',
        'notes'         => 'Notes',
        'status'        => 'Status',

        'good'          => 'Good',
        'soon'          => 'Soon',
        'late'          => 'Overdue',

        'overdue'       => 'Overdue',
        'urgent'        => 'Urgent',

        'no_data'       => 'No records match the selected filters',

        'sar'           => 'SAR',

        'previous'      => 'Previous',
        'next'          => 'Next',

        'language_ar'   => 'عربي',
        'language_en'   => 'English',

        'dark_mode'     => 'Dark Mode',
        'light_mode'    => 'Light Mode',

        'records'       => 'records'

    ]

];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$date_filter = $_GET['date_filter'] ?? 'all';

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$oil_type = trim($_GET['oil_type'] ?? '');

/* =========================================================
   Pagination
========================================================= */

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$limit = 20;

/* =========================================================
   WHERE
========================================================= */

$where = " WHERE 1 = 1 ";

$params = [];

$types = "";

/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR o.driver LIKE ?
            OR o.oil_type LIKE ?
            OR o.notes LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "ssssss";
}

/* =========================================================
   اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND f.plate LIKE ?
    ";

    $params[] =
        '%' . $plate . '%';

    $types .= "s";
}

/* =========================================================
   السائق
========================================================= */

if ($driver !== '') {

    $where .= "
        AND (
            d.name LIKE ?
            OR o.driver LIKE ?
        )
    ";

    $driverValue =
        '%' . $driver . '%';

    $params[] = $driverValue;
    $params[] = $driverValue;

    $types .= "ss";
}

/* =========================================================
   نوع الزيت
========================================================= */

if ($oil_type !== '') {

    $where .= "
        AND o.oil_type LIKE ?
    ";

    $params[] =
        '%' . $oil_type . '%';

    $types .= "s";
}

/* =========================================================
   التاريخ
========================================================= */

if ($date_filter === 'week') {

    $where .= "
        AND o.change_date >=
        DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ";

} elseif ($date_filter === 'month') {

    $where .= "
        AND o.change_date >=
        DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ";

} elseif (
    $date_filter === 'custom' &&
    $from !== '' &&
    $to !== ''
) {

    $where .= "
        AND DATE(o.change_date)
        BETWEEN ? AND ?
    ";

    $params[] = $from;
    $params[] = $to;

    $types .= "ss";
}

/* =========================================================
   SQL الأساسي
========================================================= */

$baseSql = "

    FROM oil_changes o

    LEFT JOIN fleet f
        ON f.id = o.car_id

    LEFT JOIN drivers d
        ON d.id = o.driver_id

    $where

";

/* =========================================================
   إجمالي السجلات
========================================================= */

$countSql = "
    SELECT COUNT(*) AS total
    $baseSql
";

$countStmt = $con->prepare($countSql);

if (!$countStmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}

$countStmt->execute();

$countRow =
    $countStmt
        ->get_result()
        ->fetch_assoc();

$totalRecords =
    (int)($countRow['total'] ?? 0);

$countStmt->close();

/* =========================================================
   الصفحات
========================================================= */

$totalPages = max(
    1,
    (int)ceil(
        $totalRecords / $limit
    )
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) * $limit;

/* =========================================================
   البيانات
========================================================= */

$dataSql = "

    SELECT

        o.id,
        o.car_id,
        o.driver_id,
        o.driver,
        o.oil_type,
        o.change_date,
        o.next_change,
        o.km_change,
        o.current_km,
        o.next_km,
        o.cost,
        o.notes,

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            NULLIF(o.driver, ''),
            '-'
        ) AS driver_name

    $baseSql

    ORDER BY
        o.change_date DESC,
        o.id DESC

    LIMIT ?
    OFFSET ?

";

$dataParams = $params;

$dataTypes =
    $types . "ii";

$dataParams[] =
    $limit;

$dataParams[] =
    $offset;

$stmt = $con->prepare(
    $dataSql
);

if (!$stmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param(
    $dataTypes,
    ...$dataParams
);

$stmt->execute();

$result =
    $stmt->get_result();

/* =========================================================
   الإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$vehicleIds = [];

$overdue = 0;

$urgent = 0;

$good = 0;

$chartLabels = [];

$chartValues = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $cost =
        (float)(
            $row['cost'] ?? 0
        );

    $totalCost += $cost;

    $rows[] = $row;

    if (!empty($row['car_id'])) {

        $vehicleIds[
            $row['car_id']
        ] = true;
    }

    /* حالة الزيت */

    $currentKm =
        (int)(
            $row['current_km']
            ?? $row['km_change']
            ?? 0
        );

    $nextKm =
        (int)(
            $row['next_km']
            ?? 0
        );

    if ($nextKm > 0) {

        $remaining =
            $nextKm - $currentKm;

        if ($remaining <= 0) {

            $overdue++;

        } elseif ($remaining <= 1000) {

            $urgent++;

        } else {

            $good++;
        }
    } else {

        $good++;
    }

    $chartLabels[] =
        $row['plate']
        ?? ($row['model'] ?? '-');

    $chartValues[] =
        $cost;
}

$totalVehicles =
    count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost /
          $totalRecords
        : 0;

/* =========================================================
   روابط Excel / PDF
========================================================= */

$currentParams = $_GET;

$currentParams['lang'] =
    $lang;

$currentParams['theme'] =
    $dark;

$excelUrl =
    'oile_report_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'oile_report_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'oile_report.php?' .
    http_build_query([
        'lang' =>
            $lang,
        'theme' =>
            $dark
    ]);

$themeUrl =
    '?' .
    http_build_query([
        'lang' =>
            $lang,
        'theme' =>
            $dark === 'dark'
                ? 'light'
                : 'dark'
    ]);

/* =========================================================
   Pagination URL
========================================================= */

function pageUrl($pageNumber)
{
    $params = $_GET;

    $params['page'] =
        $pageNumber;

    return '?' .
        http_build_query(
            $params
        );
}

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar'
        ? 'rtl'
        : 'ltr'
    ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars(
        $t['title']
    ) ?>
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
    href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap"
    rel="stylesheet"
>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>

<style>

* {
    box-sizing: border-box;
}

:root {

    --bg:
        <?= $dark === 'dark'
            ? '#0f172a'
            : '#f4f7fb'
        ?>;

    --card:
        <?= $dark === 'dark'
            ? '#1e293b'
            : '#ffffff'
        ?>;

    --soft:
        <?= $dark === 'dark'
            ? '#172033'
            : '#f8fafc'
        ?>;

    --text:
        <?= $dark === 'dark'
            ? '#f8fafc'
            : '#1f2937'
        ?>;

    --muted:
        <?= $dark === 'dark'
            ? '#94a3b8'
            : '#6b7280'
        ?>;

    --border:
        <?= $dark === 'dark'
            ? '#334155'
            : '#e5e7eb'
        ?>;

}

body {

    margin: 0;

    background:
        var(--bg);

    color:
        var(--text);

    font-family:
        'Tajawal',
        Tahoma,
        Arial,
        sans-serif;
}

.page-container {

    max-width: 1550px;

    margin: 30px auto;

    padding: 0 18px;
}

/* =========================================================
   HEADER
========================================================= */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    flex-wrap: wrap;

    gap: 20px;

    margin-bottom: 22px;
}

.title-area {

    display: flex;

    align-items: center;

    gap: 14px;
}

.title-icon {

    width: 58px;

    height: 58px;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #0a58ca
        );

    color:
        #fff;

    display: flex;

    align-items:
        center;

    justify-content:
        center;

    font-size: 26px;
}

.page-title h1 {

    margin: 0;

    font-size: 27px;

    font-weight: 800;
}

.page-title p {

    margin: 6px 0 0;

    font-size: 13px;

    color:
        var(--muted);
}

.header-actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

.header-actions .btn {

    border-radius:
        9px;
}

/* =========================================================
   STATS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 20px;
}

.stat-card {

    color: #fff;

    min-height: 120px;

    padding: 19px;

    border-radius: 16px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.08);
}

.stat-icon {

    width: 44px;

    height: 44px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items:
        center;

    justify-content:
        center;

    font-size: 21px;

    margin-bottom: 9px;
}

.stat-title {

    font-size: 12px;

    opacity: .9;
}

.stat-value {

    font-size: 24px;

    font-weight: 800;

    margin-top: 3px;
}

/* =========================================================
   ALERTS
========================================================= */

.alerts {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-bottom: 20px;
}

.alert-box {

    border-radius: 12px;

    padding: 13px 16px;

    font-size: 13px;

    font-weight: 700;
}

.alert-late {

    background:
        <?= $dark === 'dark'
            ? '#451a1a'
            : '#fff1f2'
        ?>;

    color:
        #dc3545;

    border:
        1px solid
        <?= $dark === 'dark'
            ? '#7f1d1d'
            : '#fecdd3'
        ?>;
}

.alert-urgent {

    background:
        <?= $dark === 'dark'
            ? '#422006'
            : '#fff7ed'
        ?>;

    color:
        #d97706;

    border:
        1px solid
        <?= $dark === 'dark'
            ? '#78350f'
            : '#fed7aa'
        ?>;
}

.alert-good {

    background:
        <?= $dark === 'dark'
            ? '#052e16'
            : '#f0fdf4'
        ?>;

    color:
        #198754;

    border:
        1px solid
        <?= $dark === 'dark'
            ? '#166534'
            : '#bbf7d0'
        ?>;
}

/* =========================================================
   FILTER CARD
========================================================= */

.filter-card {

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        16px;

    padding:
        18px;

    margin-bottom:
        20px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);
}

.form-label {

    font-size:
        12px;

    font-weight:
        700;

    margin-bottom:
        6px;
}

.form-control,
.form-select {

    min-height:
        43px;

    border-radius:
        9px;

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        var(--border);
}

/* =========================================================
   TABLE
========================================================= */

.main-card {

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        17px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);
}

.table {

    margin-bottom:
        0;
}

.table th {

    white-space:
        nowrap;

    font-size:
        12px;

    padding:
        12px 8px;
}

.table td {

    padding:
        10px 8px;

    font-size:
        12px;

    vertical-align:
        middle;
}

.plate {

    display:
        inline-block;

    padding:
        5px 9px;

    border-radius:
        7px;

    background:
        <?= $dark === 'dark'
            ? '#334155'
            : '#eef1f4'
        ?>;

    font-weight:
        800;
}

.cost {

    color:
        #198754;

    font-weight:
        800;
}

.status-badge {

    display:
        inline-block;

    padding:
        6px 9px;

    border-radius:
        8px;

    font-size:
        11px;
}

.status-good {

    background:
        #d1e7dd;

    color:
        #0f5132;
}

.status-soon {

    background:
        #fff3cd;

    color:
        #664d03;
}

.status-late {

    background:
        #f8d7da;

    color:
        #842029;
}

.row-late td {

    background:
        <?= $dark === 'dark'
            ? 'rgba(220,53,69,.08)'
            : '#fff8f8'
        ?>;
}

.row-soon td {

    background:
        <?= $dark === 'dark'
            ? 'rgba(245,158,11,.06)'
            : '#fffdf5'
        ?>;
}

/* =========================================================
   CHART
========================================================= */

.chart-card {

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        18px;

    margin-top:
        20px;
}

.chart-title {

    font-size:
        16px;

    font-weight:
        800;

    margin-bottom:
        15px;
}

.chart-wrapper {

    height:
        340px;

    position:
        relative;
}

/* =========================================================
   EMPTY
========================================================= */

.empty-state {

    padding:
        55px 20px;

    text-align:
        center;

    color:
        var(--muted);
}

.empty-state i {

    font-size:
        45px;

    display:
        block;

    margin-bottom:
        10px;
}

/* =========================================================
   PAGINATION
========================================================= */

.pagination {

    margin-bottom:
        0;
}

.pagination .page-link {

    background:
        var(--card);

    color:
        var(--text);

    border-color:
        var(--border);
}

.pagination .active
.page-link {

    background:
        #0d6efd;

    border-color:
        #0d6efd;

    color:
        #fff;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(2,1fr);
    }

    .alerts {

        grid-template-columns:
            1fr;
    }
}

@media(max-width:700px) {

    .page-header {

        align-items:
            flex-start;
    }

    .header-actions {

        width:
            100%;
    }

    .header-actions .btn {

        flex:
            1;
    }

    .stats-grid {

        grid-template-columns:
            1fr;
    }

    .table-responsive {

        overflow-x:
            auto;
    }

    .chart-wrapper {

        height:
            280px;
    }
}

@media print {

    .no-print {

        display:
            none !important;
    }

    body {

        background:
            #fff !important;

        color:
            #000 !important;
    }

    .main-card,
    .chart-card {

        box-shadow:
            none;

        border:
            1px solid #ddd;
    }

}

</style>

</head>

<body>

<div class="page-container">

<!-- =====================================================
     HEADER
===================================================== -->

<div class="page-header">

    <div class="title-area">

        <div class="title-icon">

            <i class="bi bi-droplet-fill"></i>

        </div>

        <div class="page-title">

            <h1>

                <?= htmlspecialchars(
                    $t['title']
                ) ?>

            </h1>

            <p>

                <?= htmlspecialchars(
                    $t['subtitle']
                ) ?>

            </p>

        </div>

    </div>


    <div class="header-actions no-print">

        <a
            href="<?= htmlspecialchars(
                $excelUrl
            ) ?>"
            class="btn btn-success"
        >

            <i class="bi bi-file-earmark-excel"></i>

            <?= htmlspecialchars(
                $t['excel']
            ) ?>

        </a>


        <a
            href="<?= htmlspecialchars(
                $pdfUrl
            ) ?>"
            target="_blank"
            class="btn btn-outline-danger"
        >

            <i class="bi bi-file-earmark-pdf"></i>

            <?= htmlspecialchars(
                $t['pdf']
            ) ?>

        </a>


        <button
            type="button"
            onclick="window.print()"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-printer"></i>

            <?= htmlspecialchars(
                $t['print']
            ) ?>

        </button>


        <?php if ($lang === 'ar'): ?>

            <a
                href="?<?= http_build_query([
                    'lang' =>
                        'en',
                    'theme' =>
                        $dark
                ]) ?>"
                class="btn btn-outline-secondary"
            >
                EN
            </a>

        <?php else: ?>

            <a
                href="?<?= http_build_query([
                    'lang' =>
                        'ar',
                    'theme' =>
                        $dark
                ]) ?>"
                class="btn btn-outline-secondary"
            >
                AR
            </a>

        <?php endif; ?>


        <a
            href="<?= htmlspecialchars(
                $themeUrl
            ) ?>"
            class="btn <?= $dark === 'dark'
                ? 'btn-light'
                : 'btn-dark'
            ?>"
        >

            <i class="bi <?= $dark === 'dark'
                ? 'bi-sun'
                : 'bi-moon-stars'
            ?>"></i>

        </a>

    </div>

</div>


<!-- =====================================================
     STATS
===================================================== -->

<div class="stats-grid">

    <div class="stat-card bg-primary">

        <div class="stat-icon">

            <i class="bi bi-droplet-fill"></i>

        </div>

        <div class="stat-title">

            <?= $t['total'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalRecords
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-success">

        <div class="stat-icon">

            <i class="bi bi-cash-stack"></i>

        </div>

        <div class="stat-title">

            <?= $t['cost'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalCost,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-calculator"></i>

        </div>

        <div class="stat-title">

            <?= $t['avg'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $averageCost,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card bg-info">

        <div class="stat-icon">

            <i class="bi bi-car-front"></i>

        </div>

        <div class="stat-title">

            <?= $t['cars'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalVehicles
            ) ?>

        </div>

    </div>

</div>


<!-- =====================================================
     ALERTS
===================================================== -->

<div class="alerts">

    <div class="alert-box alert-late">

        🔴
        <?= htmlspecialchars(
            $t['overdue']
        ) ?>:

        <strong>
            <?= number_format(
                $overdue
            ) ?>
        </strong>

    </div>


    <div class="alert-box alert-urgent">

        🟠
        <?= htmlspecialchars(
            $t['urgent']
        ) ?>:

        <strong>
            <?= number_format(
                $urgent
            ) ?>
        </strong>

    </div>


    <div class="alert-box alert-good">

        🟢
        <?= htmlspecialchars(
            $t['good']
        ) ?>:

        <strong>
            <?= number_format(
                $good
            ) ?>
        </strong>

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
    value="<?= htmlspecialchars(
        $lang
    ) ?>"
>

<input
    type="hidden"
    name="theme"
    value="<?= htmlspecialchars(
        $dark
    ) ?>"
>

<div class="row g-3">


    <!-- البحث -->

    <div class="col-lg-3 col-md-6">

        <label class="form-label">

            <?= $t['search'] ?>

        </label>

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars(
                $search
            ) ?>"
            class="form-control"
            placeholder="<?= htmlspecialchars(
                $t['search']
            ) ?>"
        >

    </div>


    <!-- اللوحة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['plate'] ?>

        </label>

        <input
            type="text"
            name="plate"
            value="<?= htmlspecialchars(
                $plate
            ) ?>"
            class="form-control"
        >

    </div>


    <!-- السائق -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['driver'] ?>

        </label>

        <input
            type="text"
            name="driver"
            value="<?= htmlspecialchars(
                $driver
            ) ?>"
            class="form-control"
        >

    </div>


    <!-- نوع الزيت -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['oil_type'] ?>

        </label>

        <input
            type="text"
            name="oil_type"
            value="<?= htmlspecialchars(
                $oil_type
            ) ?>"
            class="form-control"
        >

    </div>


    <!-- الفترة -->

    <div class="col-lg-1 col-md-6">

        <label class="form-label">

            <?= $t['from'] ?>

        </label>

        <input
            type="date"
            name="from"
            value="<?= htmlspecialchars(
                $from
            ) ?>"
            class="form-control"
        >

    </div>


    <div class="col-lg-1 col-md-6">

        <label class="form-label">

            <?= $t['to'] ?>

        </label>

        <input
            type="date"
            name="to"
            value="<?= htmlspecialchars(
                $to
            ) ?>"
            class="form-control"
        >

    </div>


    <!-- التاريخ -->

    <div class="col-lg-1 col-md-6">

        <label class="form-label">

            <?= $t['date'] ?>

        </label>

        <select
            name="date_filter"
            class="form-select"
        >

            <option
                value="all"
                <?= $date_filter === 'all'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['all'] ?>

            </option>

            <option
                value="week"
                <?= $date_filter === 'week'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['week'] ?>

            </option>

            <option
                value="month"
                <?= $date_filter === 'month'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['month'] ?>

            </option>

            <option
                value="custom"
                <?= $date_filter === 'custom'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['custom'] ?>

            </option>

        </select>

    </div>

</div>


<div class="mt-3 d-flex gap-2">

    <button
        type="submit"
        class="btn btn-primary"
    >

        <i class="bi bi-search"></i>

        <?= $t['filter'] ?>

    </button>


    <a
        href="<?= htmlspecialchars(
            $resetUrl
        ) ?>"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-arrow-counterclockwise"></i>

        <?= $t['reset'] ?>

    </a>

</div>

</form>

</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="main-card">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">

<tr>

    <th>#</th>

    <th>
        <?= $t['plate_head'] ?>
    </th>

    <th>
        <?= $t['model'] ?>
    </th>

    <th>
        <?= $t['driver_head'] ?>
    </th>

    <th>
        <?= $t['oil_head'] ?>
    </th>

    <th>
        <?= $t['date'] ?>
    </th>

    <th>
        <?= $t['km'] ?>
    </th>

    <th>
        <?= $t['current_km'] ?>
    </th>

    <th>
        <?= $t['next_km'] ?>
    </th>

    <th>
        <?= $t['next_date'] ?>
    </th>

    <th>
        <?= $t['cost'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

    <th>
        <?= $t['notes'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="13">

        <div class="empty-state">

            <i class="bi bi-droplet-half"></i>

            <?= htmlspecialchars(
                $t['no_data']
            ) ?>

        </div>

    </td>

</tr>

<?php else: ?>

<?php

$serialStart =
    ($page - 1) *
    $limit + 1;

?>

<?php foreach (
    $rows
    as $index => $row
): ?>

<?php

$currentKm =
    (int)(
        $row['current_km']
        ??
        $row['km_change']
        ??
        0
    );

$nextKm =
    (int)(
        $row['next_km']
        ??
        0
    );

$statusClass =
    'status-good';

$statusText =
    $t['good'];

$rowClass = '';

if ($nextKm > 0) {

    $remainingKm =
        $nextKm -
        $currentKm;

    if ($remainingKm <= 0) {

        $statusClass =
            'status-late';

        $statusText =
            $t['late'];

        $rowClass =
            'row-late';

    } elseif (
        $remainingKm <= 1000
    ) {

        $statusClass =
            'status-soon';

        $statusText =
            $t['soon'];

        $rowClass =
            'row-soon';
    }
}

?>

<tr class="<?= $rowClass ?>">

    <td>

        <strong>

            <?= $serialStart + $index ?>

        </strong>

    </td>


    <td>

        <span class="plate">

            <?= htmlspecialchars(
                $row['plate'] ?? '-'
            ) ?>

        </span>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['model'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['driver_name'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['oil_type'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['change_date'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= number_format(
            (int)(
                $row['km_change'] ?? 0
            )
        ) ?>

        KM

    </td>


    <td>

        <?= number_format(
            $currentKm
        ) ?>

        KM

    </td>


    <td>

        <?= number_format(
            $nextKm
        ) ?>

        KM

    </td>


    <td>

        <?= htmlspecialchars(
            $row['next_change'] ?? '-'
        ) ?>

    </td>


    <td class="cost">

        <?= number_format(
            (float)(
                $row['cost'] ?? 0
            ),
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <td>

        <span
            class="status-badge
                <?= htmlspecialchars(
                    $statusClass
                )
            ?>"
        >

            <?= htmlspecialchars(
                $statusText
            ) ?>

        </span>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['notes'] ?? '-'
        ) ?>

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>


<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if (
    $totalPages > 1
): ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3">

    <div class="text-muted">

        <?= $t['total'] ?>:

        <strong>

            <?= number_format(
                $totalRecords
            ) ?>

        </strong>

        <?= $t['records'] ?>

    </div>


    <nav>

        <ul class="pagination">


            <?php if ($page > 1): ?>

                <li class="page-item">

                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            pageUrl(
                                $page - 1
                            )
                        ) ?>"
                    >

                        <?= $t['previous'] ?>

                    </a>

                </li>

            <?php endif; ?>


            <?php

            $startPage =
                max(
                    1,
                    $page - 2
                );

            $endPage =
                min(
                    $totalPages,
                    $page + 2
                );

            ?>


            <?php for (
                $p = $startPage;
                $p <= $endPage;
                $p++
            ): ?>

                <li
                    class="page-item
                        <?= $p === $page
                            ? 'active'
                            : ''
                        ?>"
                >

                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            pageUrl($p)
                        ) ?>"
                    >

                        <?= $p ?>

                    </a>

                </li>

            <?php endfor; ?>


            <?php if (
                $page <
                $totalPages
            ): ?>

                <li class="page-item">

                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            pageUrl(
                                $page + 1
                            )
                        ) ?>"
                    >

                        <?= $t['next'] ?>

                    </a>

                </li>

            <?php endif; ?>

        </ul>

    </nav>

</div>

<?php endif; ?>

</div>


<!-- =====================================================
     CHART
===================================================== -->

<?php if (!empty($rows)): ?>

<div class="chart-card">

    <div class="chart-title">

        <i class="bi bi-bar-chart-fill text-primary"></i>

        <?= $lang === 'ar'
            ? 'تكلفة تغيير الزيت حسب المركبة'
            : 'Oil Change Cost by Vehicle'
        ?>

    </div>

    <div class="chart-wrapper">

        <canvas
            id="oilChart"
        ></canvas>

    </div>

</div>

<?php endif; ?>


</div>


<script>

const oilLabels =
    <?= json_encode(
        $chartLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const oilValues =
    <?= json_encode(
        $chartValues
    ) ?>;

const chartCanvas =
    document.getElementById(
        'oilChart'
    );

if (chartCanvas) {

    new Chart(
        chartCanvas,
        {

            type: 'bar',

            data: {

                labels: oilLabels,

                datasets: [

                    {

                        label:
                            '<?= htmlspecialchars(
                                $t['cost']
                            ) ?>',

                        data:
                            oilValues

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio:
                    false,

                plugins: {

                    legend: {

                        display: true

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true

                    }

                }

            }

        }
    );

}

</script>

</body>

</html>