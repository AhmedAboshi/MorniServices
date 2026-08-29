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
    $_SESSION['theme'] = (int)$_GET['theme'];
}

$dark = (int)($_SESSION['theme'] ?? 0);

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'            => 'تقرير الإطارات',
        'subtitle'         => 'متابعة عمليات تغيير الإطارات وتكاليف المركبات',

        'search'           => 'بحث...',
        'plate'            => 'رقم اللوحة',
        'driver'           => 'السائق',
        'type'             => 'نوع الإطار',

        'from'             => 'من تاريخ',
        'to'               => 'إلى تاريخ',

        'filter'           => 'تطبيق الفلتر',
        'reset'            => 'إعادة ضبط',

        'print'            => 'طباعة',
        'excel'            => 'Excel',
        'pdf'              => 'PDF',

        'total_records'    => 'عدد سجلات الإطارات',
        'total_cost'       => 'إجمالي التكلفة',
        'total_cars'       => 'عدد المركبات',
        'total_drivers'    => 'عدد السائقين',
        'average_cost'    => 'متوسط التكلفة',

        'vehicle'          => 'المركبة',
        'change_date'      => 'تاريخ التركيب',
        'next_change'      => 'التغيير القادم',
        'current_km'       => 'العداد الحالي',
        'next_km'          => 'العداد القادم',
        'remaining'        => 'المتبقي',
        'cost'             => 'التكلفة',
        'notes'            => 'الملاحظات',
        'status'           => 'الحالة',

        'good'             => 'ممتاز',
        'soon'             => 'قريب',
        'late'             => 'متأخر',
        'expired'          => 'منتهي',
        'day'              => 'يوم',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر',

        'previous'         => 'السابق',
        'next'             => 'التالي',

        'records'          => 'سجل',
        'sar'              => 'ريال',

        'language_ar'      => 'عربي',
        'language_en'      => 'English',

        'light_mode'       => 'الوضع النهاري',
        'dark_mode'        => 'الوضع الليلي'

    ],

    'en' => [

        'title'            => 'Tire Report',
        'subtitle'         => 'Monitor tire changes and vehicle costs',

        'search'           => 'Search...',
        'plate'            => 'Plate Number',
        'driver'           => 'Driver',
        'type'             => 'Tire Type',

        'from'             => 'From Date',
        'to'               => 'To Date',

        'filter'           => 'Apply Filter',
        'reset'            => 'Reset',

        'print'            => 'Print',
        'excel'            => 'Excel',
        'pdf'              => 'PDF',

        'total_records'    => 'Total Tire Records',
        'total_cost'       => 'Total Cost',
        'total_cars'       => 'Vehicles',
        'total_drivers'    => 'Drivers',
        'average_cost'    => 'Average Cost',

        'vehicle'          => 'Vehicle',
        'change_date'      => 'Install Date',
        'next_change'      => 'Next Change',
        'current_km'       => 'Current KM',
        'next_km'          => 'Next KM',
        'remaining'        => 'Remaining',
        'cost'             => 'Cost',
        'notes'            => 'Notes',
        'status'           => 'Status',

        'good'             => 'Good',
        'soon'             => 'Soon',
        'late'             => 'Overdue',
        'expired'          => 'Expired',
        'day'              => 'Days',

        'no_data'          => 'No tire records match the selected filters',

        'previous'         => 'Previous',
        'next'             => 'Next',

        'records'          => 'records',
        'sar'              => 'SAR',

        'language_ar'      => 'عربي',
        'language_en'      => 'English',

        'light_mode'       => 'Light Mode',
        'dark_mode'        => 'Dark Mode'

    ]

];

$t = $text[$lang];

/* توافق اسم car مع vehicle */
$t['car'] = $t['vehicle'];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$car_id = (int)($_GET['car_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$tire_type = trim($_GET['tire_type'] ?? '');

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
            OR d.name LIKE ?
            OR t.tire_type LIKE ?
            OR t.notes LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "ssss";
}

/* =========================================================
   المركبة
========================================================= */

if ($car_id > 0) {

    $where .= "
        AND t.car_id = ?
    ";

    $params[] = $car_id;

    $types .= "i";
}

/* =========================================================
   السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND t.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   نوع الإطار
========================================================= */

if ($tire_type !== '') {

    $where .= "
        AND t.tire_type LIKE ?
    ";

    $params[] =
        '%' . $tire_type . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(t.change_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(t.change_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام الأساسي
========================================================= */

$baseSql = "

    FROM tires t

    LEFT JOIN fleet f
        ON f.id = t.car_id

    LEFT JOIN drivers d
        ON d.id = t.driver_id

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

    if (
        strlen($types) !==
        count($params)
    ) {

        die(
            'Filter parameters mismatch.'
        );
    }

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
   Pagination
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
   جلب البيانات
========================================================= */

$sql = "

    SELECT

        t.id,
        t.car_id,
        t.driver_id,
        t.tire_type,
        t.change_date,
        t.next_change,
        t.current_km,
        t.next_km,
        t.cost,
        t.notes,

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            '-'
        ) AS driver_name

    $baseSql

    ORDER BY
        t.change_date DESC,
        t.id DESC

    LIMIT ?
    OFFSET ?

";

$dataParams = $params;

$dataTypes =
    $types . 'ii';

$dataParams[] = $limit;
$dataParams[] = $offset;

$stmt = $con->prepare($sql);

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
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$vehicleIds = [];

$good = 0;

$soon = 0;

$late = 0;

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

    /* ---------------------------------------------
       الحالة
    --------------------------------------------- */

    $days = null;

    $statusText =
        $t['good'];

    $badge =
        'success';

    $rowClass = '';

    if (
        !empty(
            $row['next_change']
        )
    ) {

        $nextChangeTime =
            strtotime(
                $row['next_change']
            );

        if ($nextChangeTime !== false) {

            $days = (int)ceil(
                (
                    $nextChangeTime -
                    strtotime(
                        date('Y-m-d')
                    )
                ) / 86400
            );

            if ($days < 0) {

                $statusText =
                    $t['late'];

                $badge =
                    'danger';

                $rowClass =
                    'row-late';

                $late++;

            } elseif (
                $days <= 30
            ) {

                $statusText =
                    $t['soon'];

                $badge =
                    'warning';

                $rowClass =
                    'row-soon';

                $soon++;

            } else {

                $statusText =
                    $t['good'];

                $badge =
                    'success';

                $good++;
            }

        } else {

            $good++;
        }

    } else {

        /*
         * إذا لم يوجد تاريخ تغيير قادم
         * نستخدم العداد كمرجع إضافي
         */

        $currentKm =
            (int)(
                $row['current_km'] ?? 0
            );

        $nextKm =
            (int)(
                $row['next_km'] ?? 0
            );

        if ($nextKm > 0) {

            $remainingKm =
                $nextKm -
                $currentKm;

            if (
                $remainingKm <= 0
            ) {

                $statusText =
                    $t['late'];

                $badge =
                    'danger';

                $rowClass =
                    'row-late';

                $late++;

            } elseif (
                $remainingKm <= 1000
            ) {

                $statusText =
                    $t['soon'];

                $badge =
                    'warning';

                $rowClass =
                    'row-soon';

                $soon++;

            } else {

                $good++;
            }

        } else {

            $good++;
        }
    }

    $row['days'] =
        $days;

    $row['status_text'] =
        $statusText;

    $row['badge'] =
        $badge;

    $row['row_class'] =
        $rowClass;

    /* بيانات الرسم */

    $chartLabels[] =
        $row['plate'] ??
        '-';

    $chartValues[] =
        $cost;

    $rows[
        count($rows) - 1
    ] = $row;
}

$totalCars =
    count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost /
          $totalRecords
        : 0;

/* =========================================================
   إجمالي المركبات والسائقين من النظام
========================================================= */

$totalFleet = 0;

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
");

if ($q) {

    $totalFleet =
        (int)(
            $q->fetch_assoc()['total']
            ?? 0
        );
}

$totalDrivers = 0;

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
");

if ($q) {

    $totalDrivers =
        (int)(
            $q->fetch_assoc()['total']
            ?? 0
        );
}

/* =========================================================
   قوائم الفلاتر
========================================================= */

$cars = [];

$carResult = $con->query("
    SELECT
        id,
        plate
    FROM fleet
    ORDER BY plate ASC
");

if ($carResult) {

    while (
        $carRow =
        $carResult->fetch_assoc()
    ) {

        $cars[] = $carRow;
    }
}

$drivers = [];

$driverResult = $con->query("
    SELECT
        id,
        name
    FROM drivers
    ORDER BY name ASC
");

if ($driverResult) {

    while (
        $driverRow =
        $driverResult->fetch_assoc()
    ) {

        $drivers[] =
            $driverRow;
    }
}

/* =========================================================
   روابط
========================================================= */

$currentParams = $_GET;

$currentParams['lang'] =
    $lang;

$currentParams['theme'] =
    $dark;

$excelUrl =
    'tires_report_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'tires_report_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'tires_report.php?' .
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
            $dark ? 0 : 1
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

* {
    box-sizing:
        border-box;
}

:root {

    --bg:
        <?= $dark
            ? '#0f172a'
            : '#f4f7fb'
        ?>;

    --card:
        <?= $dark
            ? '#1e293b'
            : '#ffffff'
        ?>;

    --soft:
        <?= $dark
            ? '#172033'
            : '#f8fafc'
        ?>;

    --text:
        <?= $dark
            ? '#f8fafc'
            : '#1f2937'
        ?>;

    --muted:
        <?= $dark
            ? '#94a3b8'
            : '#6b7280'
        ?>;

    --border:
        <?= $dark
            ? '#334155'
            : '#e5e7eb'
        ?>;
}

body {

    margin:
        0;

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

    max-width:
        1550px;

    margin:
        30px auto;

    padding:
        0 18px;
}

/* =========================================================
   HEADER
========================================================= */

.page-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        20px;

    flex-wrap:
        wrap;

    margin-bottom:
        22px;
}

.title-area {

    display:
        flex;

    align-items:
        center;

    gap:
        14px;
}

.title-icon {

    width:
        58px;

    height:
        58px;

    border-radius:
        16px;

    background:
        linear-gradient(
            135deg,
            #dc3545,
            #b02a37
        );

    color:
        #fff;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        27px;
}

.page-title h1 {

    margin:
        0;

    font-size:
        27px;

    font-weight:
        800;
}

.page-title p {

    margin:
        6px 0 0;

    color:
        var(--muted);

    font-size:
        13px;
}

.header-actions {

    display:
        flex;

    gap:
        7px;

    flex-wrap:
        wrap;
}

.header-actions .btn {

    border-radius:
        9px;
}

/* =========================================================
   STATISTICS
========================================================= */

.stats-grid {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        15px;

    margin-bottom:
        20px;
}

.stat-card {

    color:
        #fff;

    min-height:
        120px;

    border-radius:
        16px;

    padding:
        19px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.08);
}

.stat-icon {

    width:
        44px;

    height:
        44px;

    border-radius:
        12px;

    background:
        rgba(255,255,255,.18);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        20px;

    margin-bottom:
        9px;
}

.stat-title {

    font-size:
        12px;

    opacity:
        .9;
}

.stat-value {

    font-size:
        24px;

    font-weight:
        800;

    margin-top:
        3px;
}

/* =========================================================
   STATUS ALERTS
========================================================= */

.alerts-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        12px;

    margin-bottom:
        20px;
}

.alert-box {

    padding:
        13px 16px;

    border-radius:
        12px;

    font-size:
        13px;

    font-weight:
        700;
}

.alert-good {

    background:
        <?= $dark
            ? '#052e16'
            : '#f0fdf4'
        ?>;

    color:
        #198754;

    border:
        1px solid
        <?= $dark
            ? '#166534'
            : '#bbf7d0'
        ?>;
}

.alert-soon {

    background:
        <?= $dark
            ? '#422006'
            : '#fff7ed'
        ?>;

    color:
        #d97706;

    border:
        1px solid
        <?= $dark
            ? '#78350f'
            : '#fed7aa'
        ?>;
}

.alert-late {

    background:
        <?= $dark
            ? '#451a1a'
            : '#fff1f2'
        ?>;

    color:
        #dc3545;

    border:
        1px solid
        <?= $dark
            ? '#7f1d1d'
            : '#fecdd3'
        ?>;
}

/* =========================================================
   FILTERS
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
        <?= $dark
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

.row-late td {

    background:
        <?= $dark
            ? 'rgba(220,53,69,.08)'
            : '#fff8f8'
        ?>;
}

.row-soon td {

    background:
        <?= $dark
            ? 'rgba(245,158,11,.07)'
            : '#fffdf5'
        ?>;
}

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

    position:
        relative;

    height:
        340px;
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
            repeat(2, 1fr);
    }

    .alerts-grid {

        grid-template-columns:
            1fr;
    }
}

@media(max-width:700px) {

    .stats-grid {

        grid-template-columns:
            1fr;
    }

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

            <i class="bi bi-circle"></i>

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
                    'lang' => 'en',
                    'theme' => $dark
                ]) ?>"
                class="btn btn-outline-secondary"
            >

                EN

            </a>

        <?php else: ?>

            <a
                href="?<?= http_build_query([
                    'lang' => 'ar',
                    'theme' => $dark
                ]) ?>"
                class="btn btn-outline-secondary"
            >

                AR

            </a>

        <?php endif; ?>


        <?php if ($dark): ?>

            <a
                href="?<?= http_build_query([
                    'lang' => $lang,
                    'theme' => 0
                ]) ?>"
                class="btn btn-light"
            >

                <i class="bi bi-sun"></i>

            </a>

        <?php else: ?>

            <a
                href="?<?= http_build_query([
                    'lang' => $lang,
                    'theme' => 1
                ]) ?>"
                class="btn btn-dark"
            >

                <i class="bi bi-moon-stars"></i>

            </a>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     STATS
===================================================== -->

<div class="stats-grid">

    <div class="stat-card bg-primary">

        <div class="stat-icon">

            <i class="bi bi-circle"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_records'] ?>

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

            <?= $t['total_cost'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalCost,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card bg-info">

        <div class="stat-icon">

            <i class="bi bi-car-front-fill"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_cars'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalCars
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-dark">

        <div class="stat-icon">

            <i class="bi bi-person-fill"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_drivers'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalDrivers
            ) ?>

        </div>

    </div>

</div>


<!-- =====================================================
     ALERTS
===================================================== -->

<div class="alerts-grid">

    <div class="alert-box alert-good">

        🟢

        <?= $t['good'] ?>:

        <strong>

            <?= number_format(
                $good
            ) ?>

        </strong>

    </div>


    <div class="alert-box alert-soon">

        🟠

        <?= $t['soon'] ?>:

        <strong>

            <?= number_format(
                $soon
            ) ?>

        </strong>

    </div>


    <div class="alert-box alert-late">

        🔴

        <?= $t['late'] ?>:

        <strong>

            <?= number_format(
                $late
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
    value="<?= (int)$dark ?>"
>

<div class="row g-3">


    <!-- بحث -->

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


    <!-- المركبة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['vehicle'] ?>

        </label>

        <select
            name="car_id"
            class="form-select"
        >

            <option value="0">

                -

            </option>

            <?php foreach (
                $cars
                as $car
            ): ?>

                <option
                    value="<?= (int)$car['id'] ?>"
                    <?= $car_id === (int)$car['id']
                        ? 'selected'
                        : ''
                    ?>
                >

                    <?= htmlspecialchars(
                        $car['plate']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- السائق -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['driver'] ?>

        </label>

        <select
            name="driver_id"
            class="form-select"
        >

            <option value="0">

                -

            </option>

            <?php foreach (
                $drivers
                as $driver
            ): ?>

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


    <!-- نوع الإطار -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['type'] ?>

        </label>

        <input
            type="text"
            name="tire_type"
            value="<?= htmlspecialchars(
                $tire_type
            ) ?>"
            class="form-control"
        >

    </div>


    <!-- من -->

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


    <!-- إلى -->

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


    <!-- بحث -->

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
        href="<?= htmlspecialchars(
            $resetUrl
        ) ?>"
        class="btn btn-outline-secondary btn-sm"
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

<table class="table table-bordered table-hover align-middle text-center">

<thead class="table-dark">

<tr>

    <th>#</th>

    <th>
        <?= $t['car'] ?>
    </th>

    <th>
        <?= $t['driver'] ?>
    </th>

    <th>
        <?= $t['type'] ?>
    </th>

    <th>
        <?= $t['change_date'] ?>
    </th>

    <th>
        <?= $t['next_change'] ?>
    </th>

    <th>
        <?= $t['current_km'] ?>
    </th>

    <th>
        <?= $t['next_km'] ?>
    </th>

    <th>
        <?= $t['remaining'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

    <th>
        <?= $t['cost'] ?>
    </th>

    <th>
        <?= $t['notes'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="12">

        <div class="empty-state">

            <i class="bi bi-circle"></i>

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
        ?? 0
    );

$nextKm =
    (int)(
        $row['next_km']
        ?? 0
    );

$remainingKm =
    $nextKm > 0
        ? $nextKm -
          $currentKm
        : null;

?>

<tr
    class="<?= htmlspecialchars(
        $row['row_class']
    ) ?>"
>

    <td>

        <strong>

            <?= $serialStart + $index ?>

        </strong>

    </td>


    <td>

        <span class="plate">

            <?= htmlspecialchars(
                $row['plate']
                ?? '-'
            ) ?>

        </span>

        <?php if (
            !empty(
                $row['model']
            )
        ): ?>

            <div class="small text-muted mt-1">

                <?= htmlspecialchars(
                    $row['model']
                ) ?>

            </div>

        <?php endif; ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['driver_name']
            ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['tire_type']
            ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['change_date']
            ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['next_change']
            ?? '-'
        ) ?>

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

        <?php if (
            $row['days'] !== null
        ): ?>

            <?php if (
                $row['days'] < 0
            ): ?>

                <span class="text-danger fw-bold">

                    <?= $t['expired'] ?>

                </span>

            <?php else: ?>

                <span>

                    <?= number_format(
                        $row['days']
                    ) ?>

                    <?= $t['day'] ?>

                </span>

            <?php endif; ?>

        <?php elseif (
            $remainingKm !== null
        ): ?>

            <span>

                <?= number_format(
                    $remainingKm
                ) ?>

                KM

            </span>

        <?php else: ?>

            -

        <?php endif; ?>

    </td>


    <td>

        <span
            class="badge
                bg-<?= htmlspecialchars(
                    $row['badge']
                ) ?>
                status-badge"
        >

            <?= htmlspecialchars(
                $row['status_text']
            ) ?>

        </span>

    </td>


    <td class="cost">

        <?= number_format(
            (float)(
                $row['cost']
                ?? 0
            ),
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <td
        style="max-width:250px"
    >

        <?= nl2br(
            htmlspecialchars(
                $row['notes']
                ?? '-'
            )
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

        <?= $t['total_records'] ?>:

        <strong>

            <?= number_format(
                $totalRecords
            ) ?>

        </strong>

        <?= $t['records'] ?>

    </div>


    <nav>

        <ul class="pagination">


            <?php if (
                $page > 1
            ): ?>

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
            ? 'تكلفة الإطارات حسب المركبة'
            : 'Tire Cost by Vehicle'
        ?>

    </div>

    <div class="chart-wrapper">

        <canvas
            id="tireChart"
        ></canvas>

    </div>

</div>

<?php endif; ?>


</div>


<script>

const tireLabels =
    <?= json_encode(
        $chartLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const tireValues =
    <?= json_encode(
        $chartValues
    ) ?>;

const tireCanvas =
    document.getElementById(
        'tireChart'
    );

if (tireCanvas) {

    new Chart(
        tireCanvas,
        {

            type: 'bar',

            data: {

                labels:
                    tireLabels,

                datasets: [

                    {

                        label:
                            '<?= htmlspecialchars(
                                $t['cost']
                            ) ?>',

                        data:
                            tireValues

                    }

                ]

            },

            options: {

                responsive:
                    true,

                maintainAspectRatio:
                    false,

                plugins: {

                    legend: {

                        display:
                            true

                    }

                },

                scales: {

                    y: {

                        beginAtZero:
                            true

                    }

                }

            }

        }
    );
}

</script>

</body>

</html>