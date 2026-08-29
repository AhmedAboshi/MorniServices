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

        'title'            => 'تقرير الصيانة',
        'subtitle'         => 'متابعة أعمال الصيانة وتكاليف المركبات',

        'search'           => 'بحث...',
        'plate'            => 'رقم اللوحة',
        'driver'           => 'السائق',
        'Workshop'          => 'الورشة',
        'from'             => 'من تاريخ',
        'to'               => 'إلى تاريخ',

        'filter'           => 'تطبيق الفلتر',
        'reset'            => 'إعادة ضبط',

        'print'             => 'طباعة',
        'excel'             => 'Excel',
        'pdf'               => 'PDF',

        'workshop'         => 'الورشة',
        'supplier'         => 'السائق',
        'maintenance'      => 'نوع الصيانة',
        'cost'             => 'التكلفة',
        'date'             => 'التاريخ',
        'whatsapp'         => 'واتساب',

        'total_records'    => 'إجمالي سجلات الصيانة',
        'total_cost'       => 'إجمالي التكلفة',
        'average_cost'     => 'متوسط التكلفة',

        'sar'              => 'ريال',
        'record'           => 'سجل',

        'previous'         => 'السابق',
        'next'             => 'التالي',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر',

        'all_records'      => 'جميع السجلات',

        'language_ar'      => 'عربي',
        'language_en'      => 'English',

        'light_mode'       => 'الوضع النهاري',
        'dark_mode'        => 'الوضع الليلي'

    ],

    'en' => [

        'title'            => 'Maintenance Report',
        'subtitle'         => 'Monitor vehicle maintenance operations and costs',

        'search'           => 'Search...',
        'plate'            => 'Plate Number',
        'driver'           => 'Driver',
        'Workshop'          => 'Workshop',
        'from'             => 'From Date',
        'to'               => 'To Date',

        'filter'           => 'Apply Filter',
        'reset'            => 'Reset',

        'print'             => 'Print',
        'excel'             => 'Excel',
        'pdf'               => 'PDF',

        'workshop'         => 'Vehicle',
        'supplier'         => 'Driver',
        'maintenance'      => 'Maintenance Type',
        'cost'             => 'Cost',
        'date'             => 'Date',
        'whatsapp'         => 'WhatsApp',

        'total_records'    => 'Total Maintenance Records',
        'total_cost'       => 'Total Cost',
        'average_cost'     => 'Average Cost',

        'sar'              => 'SAR',
        'record'           => 'Record',

        'previous'         => 'Previous',
        'next'             => 'Next',

        'no_data'          => 'No maintenance records match the selected filters',

        'all_records'      => 'All Records',

        'language_ar'      => 'عربي',
        'language_en'      => 'English',

        'light_mode'       => 'Light Mode',
        'dark_mode'        => 'Dark Mode'

    ]
];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$vehicle = trim($_GET['vehicle'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

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
   البحث العام
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            plate_number LIKE ?
            OR driver LIKE ?
            OR vehicle_name LIKE ?
            OR maintenance_type LIKE ?
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
   اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND plate_number LIKE ?
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
        AND driver LIKE ?
    ";

    $params[] =
        '%' . $driver . '%';

    $types .= "s";
}

/* =========================================================
   المركبة
========================================================= */

if ($vehicle !== '') {

    $where .= "
        AND vehicle_name LIKE ?
    ";

    $params[] =
        '%' . $vehicle . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(maintenance_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(maintenance_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   إجمالي السجلات
========================================================= */

$countSql = "
    SELECT COUNT(*) AS total
    FROM maintenance
    $where
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

$countResult =
    $countStmt
        ->get_result()
        ->fetch_assoc();

$totalRecords =
    (int)($countResult['total'] ?? 0);

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
   الاستعلام الرئيسي
========================================================= */

$sql = "
    SELECT
        id,
        vehicle_name,
        plate_number,
        driver,
        maintenance_type,
        cost,
        maintenance_date
    FROM maintenance
    $where
    ORDER BY
        maintenance_date DESC,
        id DESC
    LIMIT ?
    OFFSET ?
";

$dataParams = $params;
$dataTypes = $types . "ii";

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

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$chartLabels = [];

$chartValues = [];

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)($row['cost'] ?? 0);

    $totalCost += $cost;

    $rows[] = $row;

    $chartLabels[] =
        $row['vehicle_name']
        ?? '-';

    $chartValues[] =
        $cost;
}

$averageCost =
    $totalRecords > 0
        ? $totalCost / $totalRecords
        : 0;

/* =========================================================
   روابط
========================================================= */

$currentParams = $_GET;

$currentParams['lang'] = $lang;
$currentParams['theme'] = $dark;

$excelUrl =
    'reportmaintenance_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'reportmaintenance_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'reportmaintenance.php?' .
    http_build_query([
        'lang' => $lang,
        'theme' => $dark
    ]);

$themeUrl =
    '?' .
    http_build_query([
        'lang' => $lang,
        'theme' => $dark ? 0 : 1
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
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($t['title']) ?>
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
    box-sizing: border-box;
}

:root {

    --bg:
        <?= $dark ? '#0f172a' : '#f4f7fb' ?>;

    --card:
        <?= $dark ? '#1e293b' : '#ffffff' ?>;

    --soft:
        <?= $dark ? '#172033' : '#f8fafc' ?>;

    --text:
        <?= $dark ? '#f8fafc' : '#1f2937' ?>;

    --muted:
        <?= $dark ? '#94a3b8' : '#6b7280' ?>;

    --border:
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

}

body {

    margin: 0;

    background: var(--bg);

    color: var(--text);

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

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    flex-wrap: wrap;

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
            #f59e0b,
            #d97706
        );

    color: #fff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 26px;
}

.page-title h1 {

    margin: 0;

    font-size: 27px;

    font-weight: 800;
}

.page-title p {

    margin: 6px 0 0;

    color: var(--muted);

    font-size: 13px;
}

.header-actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

.header-actions .btn {

    border-radius: 9px;
}

/* =========================================================
   STATS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 20px;
}

.stat-card {

    color: #fff;

    min-height: 125px;

    padding: 20px;

    border-radius: 16px;

    box-shadow:
        0 6px 20px rgba(0,0,0,.08);
}

.stat-icon {

    width: 45px;

    height: 45px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    margin-bottom: 10px;
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
   FILTERS
========================================================= */

.filter-card {

    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 16px;

    padding: 18px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.05);
}

.form-label {

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 6px;
}

.form-control {

    min-height: 43px;

    border-radius: 9px;

    background: var(--soft);

    color: var(--text);

    border-color: var(--border);
}

/* =========================================================
   TABLE
========================================================= */

.main-card {

    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 17px;

    padding: 17px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.05);
}

.table {

    margin-bottom: 0;
}

.table th {

    white-space: nowrap;

    font-size: 12px;

    padding: 12px 9px;
}

.table td {

    padding: 11px 9px;

    font-size: 12px;

    vertical-align: middle;
}

.vehicle-name {

    font-weight: 800;
}

.cost {

    color: #198754;

    font-weight: 800;
}

.whatsapp-btn {

    width: 34px;

    height: 34px;

    border-radius: 8px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    color: #fff;

    background: #25d366;

    text-decoration: none;
}

.whatsapp-btn:hover {

    color: #fff;

    opacity: .85;
}

.empty-state {

    text-align: center;

    padding: 55px 20px;

    color: var(--muted);
}

.empty-state i {

    display: block;

    font-size: 45px;

    margin-bottom: 10px;
}

/* =========================================================
   CHART
========================================================= */

.chart-card {

    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 17px;

    padding: 18px;

    margin-top: 20px;
}

.chart-title {

    font-weight: 800;

    font-size: 16px;

    margin-bottom: 15px;
}

.chart-wrapper {

    position: relative;

    height: 350px;
}

/* =========================================================
   PAGINATION
========================================================= */

.pagination {

    margin-bottom: 0;
}

.pagination .page-link {

    background: var(--card);

    color: var(--text);

    border-color: var(--border);
}

.pagination .active .page-link {

    background: #0d6efd;

    border-color: #0d6efd;

    color: #fff;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px) {

    .stats-grid {

        grid-template-columns:
            1fr;
    }

}

@media(max-width:700px) {

    .page-header {

        align-items: flex-start;
    }

    .header-actions {

        width: 100%;
    }

    .header-actions .btn {

        flex: 1;
    }

    .table-responsive {

        overflow-x: auto;
    }

    .chart-wrapper {

        height: 280px;
    }
}

@media print {

    .no-print {

        display: none !important;
    }

    body {

        background: #fff !important;

        color: #000 !important;
    }

    .main-card,
    .chart-card {

        box-shadow: none;

        border: 1px solid #ddd;
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

            <i class="bi bi-tools"></i>

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


        <a
            href="<?= htmlspecialchars(
                $themeUrl
            ) ?>"
            class="btn <?= $dark
                ? 'btn-light'
                : 'btn-dark'
            ?>"
        >

            <i class="bi <?= $dark
                ? 'bi-sun'
                : 'bi-moon-stars'
            ?>"></i>

        </a>

    </div>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats-grid">

    <div class="stat-card bg-primary">

        <div class="stat-icon">

            <i class="bi bi-tools"></i>

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


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-calculator"></i>

        </div>

        <div class="stat-title">

            <?= $t['average_cost'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $averageCost,
                2
            ) ?>

            <?= $t['sar'] ?>

        </div>

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


    <!-- المركبة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['Workshop'] ?>

        </label>

        <input
            type="text"
            name="vehicle"
            value="<?= htmlspecialchars(
                $vehicle
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

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">

<tr>

    <th>
        #
    </th>

    <th>
        <?= $t['workshop'] ?>
    </th>

    <th>
        <?= $t['plate'] ?>
    </th>

    <th>
        <?= $t['supplier'] ?>
    </th>

    <th>
        <?= $t['maintenance'] ?>
    </th>

    <th>
        <?= $t['cost'] ?>
    </th>

    <th>
        <?= $t['date'] ?>
    </th>

    <th>
        <?= $t['whatsapp'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="8">

        <div class="empty-state">

            <i class="bi bi-tools"></i>

            <?= htmlspecialchars(
                $t['no_data']
            ) ?>

        </div>

    </td>

</tr>

<?php else: ?>

<?php

$serialStart =
    ($page - 1) * $limit + 1;

?>

<?php foreach ($rows as $index => $row): ?>

<?php

$serial =
    $serialStart + $index;

$waText =
    $lang === 'ar'
        ? 'تقرير صيانة - '
          . ($row['vehicle_name'] ?? '')
          . ' - '
          . ($row['maintenance_type'] ?? '')
          . ' - التكلفة: '
          . number_format(
                (float)($row['cost'] ?? 0),
                2
            )
        : 'Maintenance Report - '
          . ($row['vehicle_name'] ?? '')
          . ' - '
          . ($row['maintenance_type'] ?? '')
          . ' - Cost: '
          . number_format(
                (float)($row['cost'] ?? 0),
                2
            );

$waUrl =
    'https://wa.me/?text=' .
    urlencode($waText);

?>

<tr>

    <td>

        <strong>
            <?= $serial ?>
        </strong>

    </td>


    <td>

        <span class="vehicle-name">

            <?= htmlspecialchars(
                $row['vehicle_name'] ?? '-'
            ) ?>

        </span>

    </td>


    <td>

        <span class="badge bg-secondary">

            <?= htmlspecialchars(
                $row['plate_number'] ?? '-'
            ) ?>

        </span>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['driver'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['maintenance_type'] ?? '-'
        ) ?>

    </td>


    <td class="cost">

        <?= number_format(
            (float)($row['cost'] ?? 0),
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['maintenance_date'] ?? '-'
        ) ?>

    </td>


    <td>

        <a
            href="<?= htmlspecialchars(
                $waUrl
            ) ?>"
            target="_blank"
            class="whatsapp-btn"
            title="<?= htmlspecialchars(
                $t['whatsapp']
            ) ?>"
        >

            <i class="bi bi-whatsapp"></i>

        </a>

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>


<!-- =====================================================
     FOOTER + PAGINATION
===================================================== -->

<?php if ($totalPages > 1): ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3">


    <div class="text-muted">

        <?= $t['total_records'] ?>:

        <strong>

            <?= number_format(
                $totalRecords
            ) ?>

        </strong>

    </div>


    <nav>

        <ul class="pagination">


            <?php if ($page > 1): ?>

                <li class="page-item">

                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            pageUrl($page - 1)
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
                    class="page-item <?= $p === $page
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


            <?php if ($page < $totalPages): ?>

                <li class="page-item">

                    <a
                        class="page-link"
                        href="<?= htmlspecialchars(
                            pageUrl($page + 1)
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
            ? 'تكلفة الصيانة حسب المركبة'
            : 'Maintenance Cost by Vehicle'
        ?>

    </div>

    <div class="chart-wrapper">

        <canvas id="maintenanceChart"></canvas>

    </div>

</div>

<?php endif; ?>


</div>


<script>

const chartLabels =
    <?= json_encode(
        $chartLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const chartValues =
    <?= json_encode(
        $chartValues
    ) ?>;


if (
    document.getElementById(
        'maintenanceChart'
    )
) {

    new Chart(
        document.getElementById(
            'maintenanceChart'
        ),
        {

            type: 'bar',

            data: {

                labels: chartLabels,

                datasets: [

                    {
                        label:
                            '<?= htmlspecialchars(
                                $t['cost']
                            ) ?>',

                        data: chartValues
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

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