<?php

session_start();

include('../include/connected.php');

/* =========================================================
   حماية الدخول
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: welcome.php");
    exit;
}

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
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
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'          => 'إدارة الحوادث',
        'subtitle'       => 'إدارة ومتابعة حوادث المركبات والسائقين',

        'add'            => 'تسجيل حادث',

        'search'         => 'بحث...',
        'all_vehicles'   => 'جميع المركبات',
        'all_drivers'    => 'جميع السائقين',
        'all_status'     => 'جميع الحالات',

        'from'            => 'من تاريخ',
        'to'              => 'إلى تاريخ',

        'filter'         => 'تطبيق الفلاتر',
        'reset'           => 'إعادة ضبط',

        'total'           => 'إجمالي الحوادث',
        'open'            => 'مفتوح',
        'progress'        => 'قيد المعالجة',
        'closed'          => 'مغلق',
        'total_cost'      => 'إجمالي الأضرار',

        'id'              => '#',
        'vehicle'         => 'المركبة',
        'plate'           => 'اللوحة',
        'driver'          => 'السائق',
        'date'            => 'التاريخ',
        'location'        => 'الموقع',
        'cost'            => 'التكلفة',
        'status'          => 'الحالة',
        'actions'         => 'العمليات',

        'view'            => 'عرض',
        'edit'            => 'تعديل',
        'delete'          => 'حذف',

        'no_data'         => 'لا توجد حوادث مطابقة للفلاتر',

        'excel'           => 'Excel',
        'pdf'             => 'PDF',

        'confirm_delete'  => 'هل أنت متأكد من حذف هذا الحادث؟',

        'sar'             => 'ريال',

        'updated'         => 'تم تحديث الحادث بنجاح',
        'success'         => 'تم تسجيل الحادث بنجاح',
        'imge' => 'صورة المركبة',

        'open_value'      => 'Open',
        'progress_value'  => 'In Progress',
        'closed_value'    => 'Closed'
    ],

    'en' => [

        'title'          => 'Accident Management',
        'subtitle'       => 'Manage and monitor vehicle and driver accidents',

        'add'            => 'Register Accident',

        'search'         => 'Search...',
        'all_vehicles'   => 'All Vehicles',
        'all_drivers'    => 'All Drivers',
        'all_status'     => 'All Statuses',

        'from'            => 'From Date',
        'to'              => 'To Date',

        'filter'         => 'Apply Filters',
        'reset'           => 'Reset',

        'total'           => 'Total Accidents',
        'open'            => 'Open',
        'progress'        => 'In Progress',
        'closed'          => 'Closed',
        'total_cost'      => 'Total Damage Cost',

        'id'              => '#',
        'vehicle'         => 'Vehicle',
        'plate'           => 'Plate',
        'driver'          => 'Driver',
        'date'            => 'Date',
        'location'        => 'Location',
        'cost'            => 'Cost',
        'status'          => 'Status',
        'actions'         => 'Actions',

        'view'            => 'View',
        'edit'            => 'Edit',
        'delete'          => 'Delete',

        'no_data'         => 'No accidents match the selected filters',

        'excel'           => 'Excel',
        'pdf'             => 'PDF',

        'confirm_delete'  => 'Are you sure you want to delete this accident?',

        'sar'             => 'SAR',

        'updated'         => 'Accident updated successfully',
        'success'         => 'Accident registered successfully',

        'open_value'      => 'Open',
        'progress_value'  => 'In Progress',
        'imge' => 'imge',
        'closed_value'    => 'Closed'
    ]
];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$vehicle_id = (int)($_GET['vehicle_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status_filter = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   WHERE
========================================================= */

$where = " WHERE 1=1 ";

$params = [];
$types = "";

/* البحث */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR a.location LIKE ?
            OR a.description LIKE ?
        )
    ";

    $value = "%{$search}%";

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}

/* المركبة */

if ($vehicle_id > 0) {

    $where .= " AND a.vehicle_id = ? ";

    $params[] = $vehicle_id;

    $types .= "i";
}

/* السائق */

if ($driver_id > 0) {

    $where .= " AND a.driver_id = ? ";

    $params[] = $driver_id;

    $types .= "i";
}

/* الحالة */

if ($status_filter !== '') {

    $allowedStatuses = [
        'Open',
        'In Progress',
        'Closed'
    ];

    if (in_array($status_filter, $allowedStatuses, true)) {

        $where .= " AND a.status = ? ";

        $params[] = $status_filter;

        $types .= "s";
    }
}

/* من */

if ($from !== '') {

    $where .= " AND DATE(a.accident_date) >= ? ";

    $params[] = $from;

    $types .= "s";
}

/* إلى */

if ($to !== '') {

    $where .= " AND DATE(a.accident_date) <= ? ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "
    SELECT
        a.*,
        f.plate,
        f.model,
        f.imgfleet,
        d.name AS driver_name
    FROM accidents a

    LEFT JOIN fleet f
        ON a.vehicle_id = f.id

    LEFT JOIN drivers d
        ON a.driver_id = d.id

    $where

    ORDER BY
        a.accident_date DESC,
        a.id DESC
";

$stmt = $con->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . htmlspecialchars($con->error));
}

if (!empty($params)) {

    if (strlen($types) !== count($params)) {
        die("Filter parameters mismatch.");
    }

    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$countOpen = 0;
$countProgress = 0;
$countClosed = 0;

while ($row = $result->fetch_assoc()) {

    $rows[] = $row;

    $totalCost += (float)($row['damage_cost'] ?? 0);

    switch ($row['status'] ?? '') {

        case 'Open':
            $countOpen++;
            break;

        case 'In Progress':
            $countProgress++;
            break;

        case 'Closed':
            $countClosed++;
            break;
    }
}

$totalRecords = count($rows);

/* =========================================================
   المركبات
========================================================= */

$vehicles = [];

$vehicleResult = $con->query("
    SELECT
        id,
        plate,
        model
    FROM fleet
    ORDER BY plate ASC
");

if ($vehicleResult) {

    while ($vehicle = $vehicleResult->fetch_assoc()) {
        $vehicles[] = $vehicle;
    }
}

/* =========================================================
   السائقين
========================================================= */

$drivers = [];

$driverResult = $con->query("
    SELECT
        id,
        name
    FROM drivers
    ORDER BY name ASC
");

if ($driverResult) {

    while ($driver = $driverResult->fetch_assoc()) {
        $drivers[] = $driver;
    }
}

/* =========================================================
   الروابط
========================================================= */

$currentParams = $_GET;

$currentParams['lang'] = $lang;
$currentParams['theme'] = $dark;

$excelUrl =
    'accidents_excel.php?' .
    http_build_query($currentParams);

$pdfUrl =
    'accidents_pdf.php?' .
    http_build_query($currentParams);

$resetUrl =
    'accidents.php?' .
    http_build_query([
        'lang' => $lang,
        'theme' => $dark
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

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background:
        <?= $dark ? '#0f172a' : '#f4f6f9' ?>;

    color:
        <?= $dark ? '#f8fafc' : '#1f2937' ?>;

    font-family:
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
   Header
========================================================= */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 22px;

    flex-wrap: wrap;
}

.page-title {

    display: flex;

    align-items: center;

    gap: 14px;
}

.title-icon {

    width: 58px;

    height: 58px;

    border-radius: 16px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #dc3545,
            #b02a37
        );

    color: white;

    font-size: 26px;

    box-shadow:
        0 8px 20px rgba(220,53,69,.2);
}

.page-title h2 {

    margin: 0;

    font-size: 27px;

    font-weight: 800;
}

.page-title p {

    margin: 6px 0 0;

    color:
        <?= $dark ? '#94a3b8' : '#6b7280' ?>;

    font-size: 14px;
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
   Statistics
========================================================= */

.stat-card {

    position: relative;

    overflow: hidden;

    min-height: 135px;

    padding: 20px;

    border-radius: 16px;

    color: #fff;

    box-shadow:
        0 6px 20px rgba(0,0,0,.08);
}

.stat-icon {

    width: 46px;

    height: 46px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.18);

    font-size: 21px;

    margin-bottom: 12px;
}

.stat-title {

    font-size: 13px;

    opacity: .9;
}

.stat-value {

    font-size: 25px;

    font-weight: 800;

    margin-top: 4px;
}

/* =========================================================
   Filter Card
========================================================= */

.filter-card {

    background:
        <?= $dark ? '#1e293b' : '#ffffff' ?>;

    border:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

    border-radius: 16px;

    padding: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.06);
}

.form-label {

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;
}

.form-control,
.form-select {

    min-height: 44px;

    border-radius: 9px;

    background:
        <?= $dark ? '#172033' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#1f2937' ?>;

    border-color:
        <?= $dark ? '#475569' : '#d7dce2' ?>;
}

.form-control:focus,
.form-select:focus {

    background:
        <?= $dark ? '#172033' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#1f2937' ?>;

    border-color: #0d6efd;

    box-shadow:
        0 0 0 .2rem rgba(13,110,253,.12);
}

/* =========================================================
   Main Table
========================================================= */

.main-card {

    background:
        <?= $dark ? '#1e293b' : '#ffffff' ?>;

    border:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

    border-radius: 17px;

    padding: 18px;

    box-shadow:
        0 6px 22px rgba(0,0,0,.06);
}

.table {

    margin-bottom: 0;
}

.table th {

    white-space: nowrap;

    font-size: 13px;

    padding: 13px 10px;
}

.table td {

    padding: 12px 10px;

    font-size: 13px;
}

.plate {

    display: inline-block;

    background:
        <?= $dark ? '#334155' : '#eef1f4' ?>;

    border-radius: 7px;

    padding: 6px 10px;

    font-weight: 700;
}

.cost {

    color: #198754;

    font-weight: 800;
}

.location {

    max-width: 180px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

    margin: auto;
}

.action-btn {

    width: 34px;

    height: 34px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 0;

    border-radius: 8px;
}

/* =========================================================
   Empty
========================================================= */

.empty-state {

    text-align: center;

    padding: 60px 20px;

    color:
        <?= $dark ? '#94a3b8' : '#9ca3af' ?>;
}

.empty-state i {

    font-size: 46px;

    display: block;

    margin-bottom: 10px;
}

/* =========================================================
   Responsive
========================================================= */

@media(max-width: 900px) {

    .header-actions {

        width: 100%;
    }

    .header-actions .btn {

        flex: 1;
    }
}

@media(max-width: 700px) {

    .page-title h2 {

        font-size: 22px;
    }

    .stat-card {

        min-height: 115px;
    }

    .table-responsive {

        overflow-x: auto;
    }
}

/* =========================================================
   Print
========================================================= */

@media print {

    body {

        background: #fff !important;

        color: #000 !important;
    }

    .no-print {

        display: none !important;
    }

}

.vehicle-thumb {
    width: 58px;
    height: 42px;
    border-radius: 8px;
    object-fit: cover;
    display: block;
    margin: 0 auto 6px;
    border: 1px solid #e5e7eb;
    background: #f1f3f5;
}

.vehicle-cell {
    min-width: 110px;
}

.vehicle-name {
    font-size: 12px;
    font-weight: 700;
}

</style>

</head>

<body>

<div class="page-container">

<!-- =====================================================
     Header
===================================================== -->

<div class="page-header">

    <div class="page-title">

        <div class="title-icon">

            <i class="bi bi-car-front-fill"></i>

        </div>

        <div>

            <h2>
                <?= htmlspecialchars($t['title']) ?>
            </h2>

            <p>
                <?= htmlspecialchars($t['subtitle']) ?>
            </p>

        </div>

    </div>


    <div class="header-actions no-print">

        <a
            href="add-accident.php?lang=<?= urlencode($lang) ?>"
            class="btn btn-danger"
        >

            <i class="bi bi-plus-circle"></i>

            <?= htmlspecialchars($t['add']) ?>

        </a>


        <a
            href="<?= htmlspecialchars($excelUrl) ?>"
            class="btn btn-success"
        >

            <i class="bi bi-file-earmark-excel"></i>

            <?= htmlspecialchars($t['excel']) ?>

        </a>


        <a
            href="<?= htmlspecialchars($pdfUrl) ?>"
            target="_blank"
            class="btn btn-outline-danger"
        >

            <i class="bi bi-file-earmark-pdf"></i>

            <?= htmlspecialchars($t['pdf']) ?>

        </a>


        <?php if ($lang === 'ar'): ?>

            <a
                href="?<?= http_build_query(array_merge(
                    $_GET,
                    [
                        'lang' => 'en',
                        'theme' => $dark
                    ]
                )) ?>"
                class="btn btn-outline-secondary"
            >
                EN
            </a>

        <?php else: ?>

            <a
                href="?<?= http_build_query(array_merge(
                    $_GET,
                    [
                        'lang' => 'ar',
                        'theme' => $dark
                    ]
                )) ?>"
                class="btn btn-outline-secondary"
            >
                AR
            </a>

        <?php endif; ?>


        <?php if ($dark): ?>

            <a
                href="?<?= http_build_query(array_merge(
                    $_GET,
                    [
                        'lang' => $lang,
                        'theme' => 0
                    ]
                )) ?>"
                class="btn btn-light"
            >

                <i class="bi bi-sun"></i>

            </a>

        <?php else: ?>

            <a
                href="?<?= http_build_query(array_merge(
                    $_GET,
                    [
                        'lang' => $lang,
                        'theme' => 1
                    ]
                )) ?>"
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

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-danger">

            <div class="stat-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <div class="stat-title">
                <?= $t['total'] ?>
            </div>

            <div class="stat-value">
                <?= number_format($totalRecords) ?>
            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-warning text-dark">

            <div class="stat-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div class="stat-title">
                <?= $t['open'] ?>
            </div>

            <div class="stat-value">
                <?= number_format($countOpen) ?>
            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-primary">

            <div class="stat-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>

            <div class="stat-title">
                <?= $t['progress'] ?>
            </div>

            <div class="stat-value">
                <?= number_format($countProgress) ?>
            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-success">

            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>

            <div class="stat-title">
                <?= $t['total_cost'] ?>
            </div>

            <div class="stat-value">

                <?= number_format($totalCost, 2) ?>

                <?= $t['sar'] ?>

            </div>

        </div>

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
    value="<?= htmlspecialchars($lang) ?>"
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
            value="<?= htmlspecialchars($search) ?>"
            class="form-control"
            placeholder="<?= htmlspecialchars($t['search']) ?>"
        >

    </div>


    <!-- المركبة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['vehicle'] ?>

        </label>

        <select
            name="vehicle_id"
            class="form-select"
        >

            <option value="0">

                <?= $t['all_vehicles'] ?>

            </option>

            <?php foreach ($vehicles as $vehicle): ?>

                <option
                    value="<?= (int)$vehicle['id'] ?>"
                    <?= $vehicle_id == $vehicle['id']
                        ? 'selected'
                        : '' ?>
                >

                    <?= htmlspecialchars(
                        $vehicle['plate']
                    ) ?>

                    <?php if (!empty($vehicle['model'])): ?>

                        -
                        <?= htmlspecialchars(
                            $vehicle['model']
                        ) ?>

                    <?php endif; ?>

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

                <?= $t['all_drivers'] ?>

            </option>

            <?php foreach ($drivers as $driver): ?>

                <option
                    value="<?= (int)$driver['id'] ?>"
                    <?= $driver_id == $driver['id']
                        ? 'selected'
                        : '' ?>
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
                value="Open"
                <?= $status_filter === 'Open'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['open'] ?>

            </option>

            <option
                value="In Progress"
                <?= $status_filter === 'In Progress'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['progress'] ?>

            </option>

            <option
                value="Closed"
                <?= $status_filter === 'Closed'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['closed'] ?>

            </option>

        </select>

    </div>


    <!-- من -->

    <div class="col-lg-1 col-md-6">

        <label class="form-label">

            <?= $t['from'] ?>

        </label>

        <input
            type="date"
            name="from"
            value="<?= htmlspecialchars($from) ?>"
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
            value="<?= htmlspecialchars($to) ?>"
            class="form-control"
        >

    </div>


    <!-- زر البحث -->

    <div class="col-lg-1 col-md-6 d-flex align-items-end">

        <button
            type="submit"
            class="btn btn-primary w-100"
            title="<?= htmlspecialchars($t['filter']) ?>"
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

        <?= $t['reset'] ?>

    </a>

</div>

</form>

</div>


<!-- =====================================================
     Table
===================================================== -->

<div class="main-card">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">

<tr>

    <th>
        <?= $t['id'] ?>
    </th>

    <th>
        <?= $t['vehicle'] ?>
    </th>

    <th>
        <?= $t['imge'] ?>
    </th>

    <th>
        <?= $t['driver'] ?>
    </th>

    <th>
        <?= $t['date'] ?>
    </th>

    <th>
        <?= $t['location'] ?>
    </th>

    <th>
        <?= $t['cost'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

    <th>
        <?= $t['actions'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="8">

        <div class="empty-state">

            <i class="bi bi-car-front"></i>

            <?= htmlspecialchars($t['no_data']) ?>

        </div>

    </td>

</tr>

<?php else: ?>

<?php foreach ($rows as $row): ?>

<?php

$statusValue = $row['status'] ?? '';

if ($statusValue === 'Open') {

    $badge = 'danger';
    $statusText = $t['open'];

} elseif ($statusValue === 'In Progress') {

    $badge = 'warning';
    $statusText = $t['progress'];

} elseif ($statusValue === 'Closed') {

    $badge = 'success';
    $statusText = $t['closed'];

} else {

    $badge = 'secondary';
    $statusText = $statusValue ?: '-';
}

?>

<tr>

    <!-- ID -->

    <td>

        <strong>
            #<?= (int)$row['id'] ?>
        </strong>

    </td>


    <!-- Vehicle -->

    <td>

        <span class="plate">

            <?= htmlspecialchars(
                $row['plate'] ?? '-'
            ) ?>

        </span>

        <?php if (!empty($row['model'])): ?>

            <div class="small text-muted mt-1">

                <?= htmlspecialchars(
                    $row['model']
                ) ?>

            </div>

        <?php endif; ?>

    </td>

    <td class="vehicle-cell">

    <?php

    $vehicleImage = trim($row['imgfleet'] ?? '');

    /*
     * عدّل هذا المسار إذا كانت صور الأسطول
     * محفوظة في مجلد مختلف.
     */
    $vehicleImagePath =
        '../fleetimg/img/' . $vehicleImage;

    $vehicleImageFile =
        __DIR__ .
        '/../fleetimg/img/' .
        $vehicleImage;

    if (
        $vehicleImage === '' ||
        !file_exists($vehicleImageFile)
    ) {

        $vehicleImagePath =
            '../assets/img/no-car.png';
    }

    ?>

    <img
        src="<?= htmlspecialchars($vehicleImagePath) ?>"
        alt="<?= htmlspecialchars($row['plate'] ?? '-') ?>"
        class="vehicle-thumb"
    >

    <span class="plate">

        <?= htmlspecialchars(
            $row['plate'] ?? '-'
        ) ?>

    </span>

    <?php if (!empty($row['model'])): ?>

        <div class="vehicle-name mt-1">

            <?= htmlspecialchars(
                $row['model']
            ) ?>

        </div>

    <?php endif; ?>

</td>

    <!-- Driver -->

    <td>

        <?= htmlspecialchars(
            $row['driver_name'] ?? '-'
        ) ?>

    </td>


    <!-- Date -->

    <td>

        <?= htmlspecialchars(
            $row['accident_date'] ?? '-'
        ) ?>

    </td>


    <!-- Location -->

    <td>

        <div class="location">

            <?= htmlspecialchars(
                $row['location'] ?? '-'
            ) ?>

        </div>

    </td>


    <!-- Cost -->

    <td class="cost">

        <?= number_format(
            (float)($row['damage_cost'] ?? 0),
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <!-- Status -->

    <td>

        <span class="badge bg-<?= $badge ?>">

            <?= htmlspecialchars($statusText) ?>

        </span>

    </td>


    <!-- Actions -->

    <td>

        <a
    href="/AlSharqPlatform/admin/accident-details.php?id=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
    class="btn btn-info btn-sm action-btn"
    title="<?= htmlspecialchars($t['view']) ?>"
>
    <i class="bi bi-eye"></i>
</a>


        <a
            href="edit-accident.php?id=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
            class="btn btn-warning btn-sm action-btn"
            title="<?= $t['edit'] ?>"
        >

            <i class="bi bi-pencil"></i>

        </a>


        <a
            href="accident_delete.php?id=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>"
            class="btn btn-danger btn-sm action-btn"
            title="<?= $t['delete'] ?? 'حذف' ?>"
            onclick="return confirm('<?= htmlspecialchars(
                $t['confirm_delete'],
                ENT_QUOTES
            ) ?>');"
        >

            <i class="bi bi-trash"></i>

        </a>

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>


<?php if (!empty($rows)): ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">

    <div class="text-muted">

        <?= $t['total'] ?>:

        <strong>
            <?= number_format($totalRecords) ?>
        </strong>

    </div>

    <div>

        <span class="badge bg-danger me-1">

            <?= $t['open'] ?>:
            <?= number_format($countOpen) ?>

        </span>

        <span class="badge bg-warning text-dark me-1">

            <?= $t['progress'] ?>:
            <?= number_format($countProgress) ?>

        </span>

        <span class="badge bg-success">

            <?= $t['closed'] ?>:
            <?= number_format($countClosed) ?>

        </span>

    </div>

</div>

<?php endif; ?>

</div>

</div>

</body>

</html>