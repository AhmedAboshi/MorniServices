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

        'title'            => 'تقرير الحضور والانصراف',

        'subtitle'         => 'متابعة حضور وانصراف السائقين والإحصائيات اليومية والشهرية',

        'search'           => 'بحث...',

        'driver'           => 'السائق',

        'date_from'        => 'من تاريخ',

        'date_to'          => 'إلى تاريخ',

        'status'           => 'الحالة',

        'all_drivers'      => 'جميع السائقين',

        'all_status'       => 'جميع الحالات',

        'all'              => 'الكل',

        'filter'           => 'تطبيق الفلتر',

        'reset'            => 'إعادة ضبط',

        'print'            => 'طباعة',

        'excel'            => 'Excel',

        'pdf'              => 'PDF',

        'total_drivers'    => 'عدد السائقين',

        'total_records'    => 'إجمالي السجلات',

        'total_present'    => 'إجمالي الحضور',

        'total_late'       => 'إجمالي التأخير',

        'name'             => 'اسم السائق',

        'phone'            => 'الجوال',

        'work_area'        => 'منطقة العمل',

        'image'            => 'الصورة',

        'date'             => 'التاريخ',

        'check_in'         => 'الحضور',

        'check_out'        => 'الانصراف',

        'working_days'     => 'أيام العمل',

        'late_days'        => 'أيام التأخير',

        'present'          => 'حاضر',

        'late'             => 'متأخر',

        'absent'           => 'غائب',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر',

        'daily_report'     => 'التقرير التفصيلي',

        'monthly_statistics'=> 'الإحصائيات',

        'month'            => 'الشهر الحالي',

        'sar'              => 'ريال',

        'records'          => 'سجل',

        'previous'         => 'السابق',

        'next'             => 'التالي'

    ],

    'en' => [

        'title'            => 'Attendance Report',

        'subtitle'         => 'Monitor driver attendance, check-in/out and statistics',

        'search'           => 'Search...',

        'driver'           => 'Driver',

        'date_from'        => 'From Date',

        'date_to'          => 'To Date',

        'status'           => 'Status',

        'all_drivers'      => 'All Drivers',

        'all_status'       => 'All Statuses',

        'all'              => 'All',

        'filter'           => 'Apply Filter',

        'reset'            => 'Reset',

        'print'            => 'Print',

        'excel'            => 'Excel',

        'pdf'              => 'PDF',

        'total_drivers'    => 'Total Drivers',

        'total_records'    => 'Total Records',

        'total_present'    => 'Total Present',

        'total_late'       => 'Total Late',

        'name'             => 'Driver Name',

        'phone'            => 'Phone',

        'work_area'        => 'Work Area',

        'image'            => 'Image',

        'date'             => 'Date',

        'check_in'         => 'Check In',

        'check_out'        => 'Check Out',

        'working_days'     => 'Working Days',

        'late_days'        => 'Late Days',

        'present'          => 'Present',

        'late'             => 'Late',

        'absent'           => 'Absent',

        'no_data'          => 'No records match the selected filters',

        'daily_report'     => 'Attendance Details',

        'monthly_statistics'=> 'Statistics',

        'month'            => 'Current Month',

        'sar'              => 'SAR',

        'records'          => 'records',

        'previous'         => 'Previous',

        'next'             => 'Next'

    ]

];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status_filter = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   Pagination
========================================================= */

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$limit = 25;

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
            d.name LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sss";
}

/* =========================================================
   السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND a.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   الحالة
========================================================= */

if ($status_filter !== '') {

    /*
     * ندعم الحالات الموجودة لديك
     */

    if (
        in_array(
            $status_filter,
            [
                'present',
                'late',
                'absent'
            ],
            true
        )
    ) {

        /*
         * إذا كانت قاعدة البيانات تخزن
         * present / late / absent
         */

        $where .= "
            AND a.status = ?
        ";

        $params[] =
            $status_filter;

        $types .= "s";

    } elseif (
        in_array(
            $status_filter,
            [
                'حاضر',
                'متأخر',
                'غائب'
            ],
            true
        )
    ) {

        /*
         * إذا كانت قاعدة البيانات تخزن
         * النص العربي
         */

        $where .= "
            AND a.status = ?
        ";

        $params[] =
            $status_filter;

        $types .= "s";
    }
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND a.attendance_date >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND a.attendance_date <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   SQL الأساسي
========================================================= */

$baseSql = "

    FROM attendance a

    INNER JOIN drivers d
        ON d.id = a.driver_id

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
        $totalRecords /
        $limit
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

$dataSql = "

    SELECT

        a.id,
        a.driver_id,
        a.attendance_date,
        a.check_in,
        a.check_out,
        a.status,

        d.name,
        d.phone,
        d.work_area,
        d.imagedriver

    $baseSql

    ORDER BY
        a.attendance_date DESC,
        a.id DESC

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
   البيانات والإحصائيات
========================================================= */

$rows = [];

$presentCount = 0;

$lateCount = 0;

$absentCount = 0;

while (
    $row =
    $result->fetch_assoc()
) {

    $status =
        trim(
            (string)(
                $row['status']
                ?? ''
            )
        );

    /*
     * توحيد الحالة
     */

    if (
        strtolower($status)
        === 'late' ||
        $status === 'متأخر'
    ) {

        $row['status_key'] =
            'late';

        $row['status_text'] =
            $t['late'];

        $row['status_class'] =
            'warning';

        $lateCount++;

    } elseif (
        strtolower($status)
        === 'absent' ||
        $status === 'غائب'
    ) {

        $row['status_key'] =
            'absent';

        $row['status_text'] =
            $t['absent'];

        $row['status_class'] =
            'danger';

        $absentCount++;

    } else {

        $row['status_key'] =
            'present';

        $row['status_text'] =
            $t['present'];

        $row['status_class'] =
            'success';

        $presentCount++;
    }

    $rows[] = $row;
}

/* =========================================================
   السائقون
========================================================= */

$driverList = [];

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

        $driverList[] =
            $driverRow;
    }
}

/* =========================================================
   إجمالي السائقين
========================================================= */

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
   إحصائيات كاملة حسب الفلاتر
========================================================= */

$statsSql = "

    SELECT

        COUNT(*) AS total_records,

        SUM(
            CASE
                WHEN a.status = 'متأخر'
                     OR a.status = 'late'
                THEN 1
                ELSE 0
            END
        ) AS late_count,

        SUM(
            CASE
                WHEN a.status = 'حاضر'
                     OR a.status = 'present'
                     OR (
                         a.status IS NULL
                         OR a.status = ''
                     )
                THEN 1
                ELSE 0
            END
        ) AS present_count,

        SUM(
            CASE
                WHEN a.status = 'غائب'
                     OR a.status = 'absent'
                THEN 1
                ELSE 0
            END
        ) AS absent_count

    $baseSql

";

$statsStmt =
    $con->prepare(
        $statsSql
    );

if (!$statsStmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    $statsStmt->bind_param(
        $types,
        ...$params
    );
}

$statsStmt->execute();

$stats =
    $statsStmt
        ->get_result()
        ->fetch_assoc();

$statsStmt->close();

$filteredTotal =
    (int)(
        $stats['total_records']
        ?? 0
    );

$filteredPresent =
    (int)(
        $stats['present_count']
        ?? 0
    );

$filteredLate =
    (int)(
        $stats['late_count']
        ?? 0
    );

$filteredAbsent =
    (int)(
        $stats['absent_count']
        ?? 0
    );

/* =========================================================
   روابط
========================================================= */

$currentParams =
    $_GET;

$currentParams['lang'] =
    $lang;

$currentParams['theme'] =
    $dark;

$excelUrl =
    'attendance_report_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'attendance_report_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'attendance_report.php?' .
    http_build_query([
        'lang' =>
            $lang,
        'theme' =>
            $dark
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
            #198754,
            #0f7a46
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
   STATS
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

    padding:
        19px;

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
        24px;

    font-weight:
        800;
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

.driver-img {

    width:
        48px;

    height:
        48px;

    border-radius:
        50%;

    object-fit:
        cover;

    border:
        2px solid
        <?= $dark
            ? '#475569'
            : '#e5e7eb'
        ?>;
}

.driver-img-empty {

    width:
        48px;

    height:
        48px;

    border-radius:
        50%;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        <?= $dark
            ? '#334155'
            : '#eef1f4'
        ?>;

    color:
        var(--muted);

    font-size:
        20px;
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
        #198754;

    border-color:
        #198754;

    color:
        #fff;
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
   RESPONSIVE
========================================================= */

@media(max-width:1000px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
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
}

/* =========================================================
   PRINT
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
     HEADER
===================================================== -->

<div class="page-header">

    <div class="title-area">

        <div class="title-icon">

            <i class="bi bi-calendar-check"></i>

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
            class="btn btn-outline-primary"
            onclick="window.print()"
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
     STATISTICS
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


    <div class="stat-card bg-success">

        <div class="stat-icon">

            <i class="bi bi-calendar-check"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_records'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $filteredTotal
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-info">

        <div class="stat-icon">

            <i class="bi bi-check-circle"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_present'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $filteredPresent
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-alarm"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_late'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $filteredLate
            ) ?>

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
                $driverList
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


    <!-- الحالة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['status'] ?>

        </label>

        <select
            name="status"
            class="form-select"
        >

            <option value="">

                <?= $t['all_status'] ?>

            </option>

            <option
                value="present"
                <?= $status_filter === 'present'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['present'] ?>

            </option>

            <option
                value="late"
                <?= $status_filter === 'late'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['late'] ?>

            </option>

            <option
                value="absent"
                <?= $status_filter === 'absent'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['absent'] ?>

            </option>

        </select>

    </div>


    <!-- من -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['date_from'] ?>

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

            <?= $t['date_to'] ?>

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
     TABLE
===================================================== -->

<div class="main-card">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">

<tr>

    <th>#</th>

    <th>
        <?= $t['image'] ?>
    </th>

    <th>
        <?= $t['name'] ?>
    </th>

    <th>
        <?= $t['phone'] ?>
    </th>

    <th>
        <?= $t['work_area'] ?>
    </th>

    <th>
        <?= $t['date'] ?>
    </th>

    <th>
        <?= $t['check_in'] ?>
    </th>

    <th>
        <?= $t['check_out'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="9">

        <div class="empty-state">

            <i class="bi bi-calendar-x"></i>

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

<tr>

    <td>

        <strong>

            <?= $serialStart + $index ?>

        </strong>

    </td>


    <td>

        <?php

        $imageName =
            trim(
                (string)(
                    $row['imagedriver']
                    ?? ''
                )
            );

        ?>

        <?php if (
            $imageName !== ''
        ): ?>

            <img
                src="../uploads/<?= htmlspecialchars(
                    basename($imageName)
                ) ?>"
                class="driver-img"
                onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"
            >

            <span
                class="driver-img-empty"
                style="display:none;"
            >

                <i class="bi bi-person"></i>

            </span>

        <?php else: ?>

            <span
                class="driver-img-empty"
            >

                <i class="bi bi-person"></i>

            </span>

        <?php endif; ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['name'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['phone'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['work_area'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['attendance_date'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['check_in'] ?? '-'
        ) ?>

    </td>


    <td>

        <?= htmlspecialchars(
            $row['check_out'] ?? '-'
        ) ?>

    </td>


    <td>

        <span
            class="badge bg-<?= htmlspecialchars(
                $row['status_class']
            ) ?>"
        >

            <?= htmlspecialchars(
                $row['status_text']
            ) ?>

        </span>

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
                $filteredTotal
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

</div>

</body>

</html>