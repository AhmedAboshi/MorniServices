
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   حماية بسيطة
========================================================= */

// فعّلها إذا كانت صفحات التقارير لديك محمية بالأدمن
/*
if (!isset($_SESSION['admin_id'])) {
    header("Location: welcome.php");
    exit;
}
*/

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

        'title'             => 'تقرير تكاليف السائقين',

        'subtitle'          => 'تحليل تكاليف الزيت والإطارات والصيانة لكل سائق',

        'from'              => 'من تاريخ',

        'to'                => 'إلى تاريخ',

        'driver'            => 'السائق',

        'all_drivers'       => 'جميع السائقين',

        'search'            => 'بحث...',

        'filter'            => 'تطبيق الفلتر',

        'reset'             => 'إعادة ضبط',

        'print'             => 'طباعة',

        'excel'             => 'Excel',

        'pdf'               => 'PDF',

        'details'           => 'التفاصيل',

        'name'              => 'السائق',

        'plate'             => 'اللوحة',

        'oil'               => 'الزيت',

        'tires'             => 'الإطارات',

        'maintenance'       => 'الصيانة',

        'total'             => 'الإجمالي',

        'total_oil'         => 'إجمالي الزيت',

        'total_tires'       => 'إجمالي الإطارات',

        'total_maintenance' => 'إجمالي الصيانة',

        'grand_total'       => 'الإجمالي الكلي',

        'total_drivers'     => 'عدد السائقين',

        'top_driver'        => 'السائق الأعلى تكلفة',

        'top_cost'          => 'إجمالي تكلفته',

        'records'           => 'عدد السائقين حسب النتائج',

        'no_data'           => 'لا توجد بيانات مطابقة للفلاتر',

        'sar'               => 'ريال',

        'company'           => 'شركة الشرق لخدمات السيارات',

        'generated_at'      => 'تاريخ التقرير',

        'all_period'        => 'كل الفترات',

        'period'            => 'الفترة'

    ],

    'en' => [

        'title'             => 'Driver Cost Report',

        'subtitle'          => 'Analyze oil, tire and maintenance costs by driver',

        'from'              => 'From Date',

        'to'                => 'To Date',

        'driver'            => 'Driver',

        'all_drivers'       => 'All Drivers',

        'search'            => 'Search...',

        'filter'            => 'Apply Filter',

        'reset'             => 'Reset',

        'print'             => 'Print',

        'excel'             => 'Excel',

        'pdf'               => 'PDF',

        'details'           => 'Details',

        'name'              => 'Driver',

        'plate'             => 'Plate',

        'oil'               => 'Oil',

        'tires'             => 'Tires',

        'maintenance'       => 'Maintenance',

        'total'             => 'Total',

        'total_oil'         => 'Total Oil',

        'total_tires'       => 'Total Tires',

        'total_maintenance' => 'Total Maintenance',

        'grand_total'       => 'Grand Total',

        'total_drivers'     => 'Drivers',

        'top_driver'        => 'Highest Cost Driver',

        'top_cost'          => 'Total Cost',

        'records'           => 'Drivers in Result',

        'no_data'           => 'No data matches the selected filters',

        'sar'               => 'SAR',

        'company'           => 'Al Sharq Automotive Services Company',

        'generated_at'      => 'Report Date',

        'all_period'        => 'All Periods',

        'period'            => 'Period'

    ]

];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim(
    $_GET['search'] ?? ''
);

$from = trim(
    $_GET['from'] ?? ''
);

$to = trim(
    $_GET['to'] ?? ''
);

$driver_id = (int)(
    $_GET['driver_id'] ?? 0
);

/* =========================================================
   بناء شروط التاريخ لكل جدول
========================================================= */

$oilDateSql = '';

$oilParams = [];

$oilTypes = '';

if ($from !== '') {

    $oilDateSql .=
        " AND DATE(o.change_date) >= ? ";

    $oilParams[] =
        $from;

    $oilTypes .= 's';
}

if ($to !== '') {

    $oilDateSql .=
        " AND DATE(o.change_date) <= ? ";

    $oilParams[] =
        $to;

    $oilTypes .= 's';
}


/* =========================================================
   الإطارات
========================================================= */

$tireDateSql = '';

$tireParams = [];

$tireTypes = '';

if ($from !== '') {

    $tireDateSql .=
        " AND DATE(t.change_date) >= ? ";

    $tireParams[] =
        $from;

    $tireTypes .= 's';
}

if ($to !== '') {

    $tireDateSql .=
        " AND DATE(t.change_date) <= ? ";

    $tireParams[] =
        $to;

    $tireTypes .= 's';
}


/* =========================================================
   الصيانة
========================================================= */

$maintenanceDateSql = '';

$maintenanceParams = [];

$maintenanceTypes = '';

if ($from !== '') {

    $maintenanceDateSql .=
        " AND DATE(m.maintenance_date) >= ? ";

    $maintenanceParams[] =
        $from;

    $maintenanceTypes .= 's';
}

if ($to !== '') {

    $maintenanceDateSql .=
        " AND DATE(m.maintenance_date) <= ? ";

    $maintenanceParams[] =
        $to;

    $maintenanceTypes .= 's';
}

/* =========================================================
   البحث
========================================================= */

$driverWhere = '';

$driverParams = [];

$driverTypes = '';

if ($search !== '') {

    $driverWhere .= "
        AND (
            d.name LIKE ?
            OR d.plate_number LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
        )
    ";

    $value =
        '%' . $search . '%';

    $driverParams[] = $value;
    $driverParams[] = $value;
    $driverParams[] = $value;
    $driverParams[] = $value;

    $driverTypes .= 'ssss';
}

if ($driver_id > 0) {

    $driverWhere .=
        " AND d.id = ? ";

    $driverParams[] =
        $driver_id;

    $driverTypes .= 'i';
}

/* =========================================================
   الاستعلام الرئيسي
========================================================= */

$sql = "

    SELECT

        d.id,

        d.name,

        d.plate_number,

        d.phone,

        d.work_area,

        COALESCE(o.oil_total, 0) AS oil,

        COALESCE(t.tire_total, 0) AS tires,

        COALESCE(m.maint_total, 0) AS maintenance

    FROM drivers d

    LEFT JOIN (

        SELECT

            o.driver_id,

            SUM(o.cost) AS oil_total

        FROM oil_changes o

        WHERE 1=1

        $oilDateSql

        GROUP BY o.driver_id

    ) o

        ON o.driver_id = d.id

    LEFT JOIN (

        SELECT

            t.driver_id,

            SUM(t.cost) AS tire_total

        FROM tires t

        WHERE 1=1

        $tireDateSql

        GROUP BY t.driver_id

    ) t

        ON t.driver_id = d.id

    LEFT JOIN (

        SELECT

            m.driver_id,

            SUM(m.cost) AS maint_total

        FROM maintenance m

        WHERE 1=1

        $maintenanceDateSql

        GROUP BY m.driver_id

    ) m

        ON m.driver_id = d.id

    WHERE 1=1

    $driverWhere

    ORDER BY

        (
            COALESCE(o.oil_total, 0)
            +
            COALESCE(t.tire_total, 0)
            +
            COALESCE(m.maint_total, 0)
        ) DESC,

        d.name ASC

";

/* =========================================================
   تجهيز الاستعلام
========================================================= */

$stmt =
    $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
    );
}

/*
 * ترتيب المتغيرات في الاستعلام:
 *
 * oil
 * tires
 * maintenance
 * driver filters
 */

$allParams = array_merge(
    $oilParams,
    $tireParams,
    $maintenanceParams,
    $driverParams
);

$allTypes =
    $oilTypes .
    $tireTypes .
    $maintenanceTypes .
    $driverTypes;

if (!empty($allParams)) {

    if (
        strlen($allTypes) !==
        count($allParams)
    ) {

        die(
            'Filter parameters mismatch.'
        );
    }

    $stmt->bind_param(
        $allTypes,
        ...$allParams
    );
}

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars(
            $stmt->error
        )
    );
}

$result =
    $stmt->get_result();

/* =========================================================
   قراءة النتائج
========================================================= */

$rows = [];

$totalOil = 0;

$totalTires = 0;

$totalMaintenance = 0;

$totalGrand = 0;

$topDriverName = '';

$topDriverCost = 0;

while (
    $row =
    $result->fetch_assoc()
) {

    $oil =
        (float)(
            $row['oil'] ?? 0
        );

    $tires =
        (float)(
            $row['tires'] ?? 0
        );

    $maintenance =
        (float)(
            $row['maintenance'] ?? 0
        );

    $total =
        $oil +
        $tires +
        $maintenance;

    $row['total'] =
        $total;

    $totalOil +=
        $oil;

    $totalTires +=
        $tires;

    $totalMaintenance +=
        $maintenance;

    $totalGrand +=
        $total;

    if (
        $total >
        $topDriverCost
    ) {

        $topDriverCost =
            $total;

        $topDriverName =
            $row['name']
            ?? '';
    }

    $rows[] =
        $row;
}

$totalDrivers =
    count($rows);

/* =========================================================
   قائمة السائقين
========================================================= */

$allDrivers = [];

$driverResult =
    $con->query("
        SELECT
            id,
            name
        FROM drivers
        ORDER BY name ASC
    ");

if ($driverResult) {

    while (
        $driver =
        $driverResult->fetch_assoc()
    ) {

        $allDrivers[] =
            $driver;
    }
}

/* =========================================================
   روابط التصدير
========================================================= */

$currentParams =
    $_GET;

$currentParams['lang'] =
    $lang;

$currentParams['theme'] =
    $dark;

$excelUrl =
    'driverviewcost_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'driverviewcost_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'driverviewcost.php?' .
    http_build_query([
        'lang' =>
            $lang,
        'theme' =>
            $dark
    ]);

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
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

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
        'Cairo',
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
   Header
========================================================= */

.page-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        20px;

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
            #0d6efd,
            #084298
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
        26px;
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

    flex-wrap:
        wrap;

    gap:
        7px;
}

.header-actions .btn {

    border-radius:
        9px;
}

/* =========================================================
   Statistics
========================================================= */

.stats-grid {

    display:
        grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap:
        14px;

    margin-bottom:
        20px;
}

.stat-card {

    color:
        #fff;

    min-height:
        125px;

    padding:
        18px;

    border-radius:
        16px;

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
        23px;

    font-weight:
        800;
}

.top-card {

    background:
        linear-gradient(
            135deg,
            #6f42c1,
            #512da8
        );
}

/* =========================================================
   Filters
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
   Table
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
        13px 9px;
}

.table td {

    padding:
        12px 9px;

    font-size:
        12px;

    vertical-align:
        middle;
}

.money {

    font-weight:
        800;

    color:
        #198754;
}

.total-cell {

    font-size:
        14px;

    font-weight:
        800;
}

.top-driver-row td {

    background:
        <?= $dark
            ? '#3a2442'
            : '#fff3cd'
        ?> !important;

    font-weight:
        700;
}

.driver-name {

    font-weight:
        700;
}

.badge-cost {

    padding:
        6px 9px;

    border-radius:
        8px;

    font-size:
        11px;
}

/* =========================================================
   Footer totals
========================================================= */

.summary-table {

    width:
        100%;

    border-collapse:
        collapse;
}

.summary-table td {

    padding:
        12px;

    text-align:
        center;

    border:
        1px solid
        var(--border);

    background:
        var(--soft);
}

.summary-label {

    display:
        block;

    font-size:
        11px;

    color:
        var(--muted);
}

.summary-value {

    display:
        block;

    margin-top:
        4px;

    font-size:
        19px;

    font-weight:
        800;
}

.grand-total {

    color:
        #198754;
}

/* =========================================================
   Empty
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
   Responsive
========================================================= */

@media(max-width:1200px) {

    .stats-grid {

        grid-template-columns:
            repeat(3, 1fr);
    }
}

@media(max-width:800px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media(max-width:600px) {

    .stats-grid {

        grid-template-columns:
            1fr;
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
}

/* =========================================================
   Print
========================================================= */

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

    .main-card {

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
     Header
===================================================== -->

<div class="page-header">

    <div class="title-area">

        <div class="title-icon">

            <i class="bi bi-cash-stack"></i>

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
    href="<?= htmlspecialchars($excelUrl) ?>"
    class="btn btn-success"
>
    <i class="bi bi-file-earmark-excel"></i>
    <?= htmlspecialchars($t['excel']) ?>
</a>


        <a
            href="<?= htmlspecialchars(
                $pdfUrl
            ) ?>"
            target="_blank"
            class="btn btn-outline-danger"
        >

            <i class="bi bi-file-earmark-pdf"></i>

            <?= $t['pdf'] ?>

        </a>


        <button
            type="button"
            class="btn btn-outline-primary"
            onclick="window.print()"
        >

            <i class="bi bi-printer"></i>

            <?= $t['print'] ?>

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
     Statistics
===================================================== -->

<div class="stats-grid">

    <div class="stat-card bg-primary">

        <div class="stat-icon">

            <i class="bi bi-people"></i>

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


    <div class="stat-card bg-info">

        <div class="stat-icon">

            <i class="bi bi-droplet-fill"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_oil'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalOil,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-disc"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_tires'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalTires,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card bg-success">

        <div class="stat-icon">

            <i class="bi bi-tools"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_maintenance'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalMaintenance,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

    </div>


    <div class="stat-card top-card">

        <div class="stat-icon">

            <i class="bi bi-trophy-fill"></i>

        </div>

        <div class="stat-title">

            <?= $t['top_driver'] ?>

        </div>

        <div class="stat-value">

            <?= htmlspecialchars(
                $topDriverName ?: '-'
            ) ?>

        </div>

        <?php if ($topDriverName !== ''): ?>

            <small>

                <?= number_format(
                    $topDriverCost,
                    2
                ) ?>

                <?= $t['sar'] ?>

            </small>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     Filters
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

                <?= $t['all_drivers'] ?>

            </option>

            <?php foreach (
                $allDrivers
                as $driver
            ): ?>

                <option
                    value="<?= (int)$driver['id'] ?>"
                    <?= $driver_id ===
                        (int)$driver['id']
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


    <!-- من -->

    <div class="col-lg-2 col-md-6">

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

    <div class="col-lg-2 col-md-6">

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
            title="<?= htmlspecialchars(
                $t['filter']
            ) ?>"
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
     التقرير
===================================================== -->

<div class="main-card">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">

<tr>

    <th>#</th>

    <th>
        <?= $t['name'] ?>
    </th>

    <th>
        <?= $t['plate'] ?>
    </th>

    <th>
        <?= $t['oil'] ?>
    </th>

    <th>
        <?= $t['tires'] ?>
    </th>

    <th>
        <?= $t['maintenance'] ?>
    </th>

    <th>
        <?= $t['total'] ?>
    </th>

    <th class="no-print">
        <?= $t['details'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (
    empty($rows)
): ?>

<tr>

    <td colspan="8">

        <div class="empty-state">

            <i class="bi bi-bar-chart-line"></i>

            <?= htmlspecialchars(
                $t['no_data']
            ) ?>

        </div>

    </td>

</tr>

<?php else: ?>

<?php foreach (
    $rows
    as $index => $row
): ?>

<tr
    class="<?= (
        $topDriverName !== '' &&
        $row['name'] ===
        $topDriverName
        &&
        $row['total'] ==
        $topDriverCost
    )
        ? 'top-driver-row'
        : ''
    ?>"
>

    <td>

        <?= $index + 1 ?>

    </td>


    <td class="driver-name">

        <?= htmlspecialchars(
            $row['name']
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['plate_number']
            ?? '-'
        ) ?>

    </td>


    <td class="money">

        <?= number_format(
            (float)$row['oil'],
            2
        ) ?>

    </td>


    <td class="money">

        <?= number_format(
            (float)$row['tires'],
            2
        ) ?>

    </td>


    <td class="money">

        <?= number_format(
            (float)$row['maintenance'],
            2
        ) ?>

    </td>


    <td class="total-cell">

        <?= number_format(
            (float)$row['total'],
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <td class="no-print">

        <a
            href="drivercost.php?id=<?= (int)$row['id'] ?>"
            class="btn btn-warning btn-sm"
        >

            <i class="bi bi-eye"></i>

            <?= $t['details'] ?>

        </a>

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>


<!-- =====================================================
     Totals
===================================================== -->

<div class="mt-4">

<table class="summary-table">

<tr>

    <td>

        <span class="summary-label">

            <?= $t['total_oil'] ?>

        </span>

        <span class="summary-value">

            <?= number_format(
                $totalOil,
                2
            ) ?>

            <?= $t['sar'] ?>

        </span>

    </td>


    <td>

        <span class="summary-label">

            <?= $t['total_tires'] ?>

        </span>

        <span class="summary-value">

            <?= number_format(
                $totalTires,
                2
            ) ?>

            <?= $t['sar'] ?>

        </span>

    </td>


    <td>

        <span class="summary-label">

            <?= $t['total_maintenance'] ?>

        </span>

        <span class="summary-value">

            <?= number_format(
                $totalMaintenance,
                2
            ) ?>

            <?= $t['sar'] ?>

        </span>

    </td>


    <td>

        <span class="summary-label">

            <?= $t['grand_total'] ?>

        </span>

        <span class="summary-value grand-total">

            <?= number_format(
                $totalGrand,
                2
            ) ?>

            <?= $t['sar'] ?>

        </span>

    </td>

</tr>

</table>

</div>

</div>

</div>

</body>

</html>

