<?php

session_start();

include('../include/connected.php');

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
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'            => 'تقرير المركبات',
        'subtitle'         => 'تقرير شامل عن المركبات والوثائق والحالة التشغيلية',

        'search'           => 'بحث...',
        'driver'           => 'السائق',
        'plate'            => 'رقم اللوحة',
        'city'             => 'المدينة',
        'status'           => 'الحالة',

        'all_drivers'      => 'جميع السائقين',
        'all_status'       => 'جميع الحالات',

        'all'              => 'الكل',
        'healthy'          => 'سليم',
        'inspection_expired' => 'الفحص منتهي',
        'insurance_expired'  => 'التأمين منتهي',
        'operation_expired'  => 'كرت التشغيل منتهي',

        'filter'           => 'تطبيق الفلتر',
        'reset'            => 'إعادة ضبط',

        'print'            => 'طباعة',
        'excel'            => 'Excel',
        'pdf'              => 'PDF',

        'add'              => 'إضافة مركبة',

        'total_fleet'      => 'إجمالي المركبات',
        'expired_inspection' => 'فحص منتهي',
        'expired_insurance'  => 'تأمين منتهي',
        'expired_operation'  => 'كرت تشغيل منتهي',

        'id'               => '#',
        'vehicle'          => 'المركبة',
        'image'            => 'الصورة',
        'type'             => 'النوع',
        'model'            => 'الموديل',
        'color'            => 'اللون',
        'work'             => 'المدينة',

        'inspection'       => 'الفحص',
        'insurance'        => 'التأمين',
        'operation'        => 'التشغيل',

        'no_data'          => 'لا توجد مركبات مطابقة للفلاتر',

        'vehicle_count'    => 'مركبة',

        'sar'              => 'ريال',

    ],

    'en' => [

        'title'            => 'Fleet Report',
        'subtitle'         => 'Comprehensive report of vehicles, documents and operational status',

        'search'           => 'Search...',
        'driver'           => 'Driver',
        'plate'            => 'Plate Number',
        'city'             => 'City',
        'status'           => 'Status',

        'all_drivers'      => 'All Drivers',
        'all_status'       => 'All Statuses',

        'all'              => 'All',
        'healthy'          => 'Healthy',
        'inspection_expired' => 'Inspection Expired',
        'insurance_expired'  => 'Insurance Expired',
        'operation_expired'  => 'Operation Card Expired',

        'filter'           => 'Apply Filter',
        'reset'            => 'Reset',

        'print'            => 'Print',
        'excel'            => 'Excel',
        'pdf'              => 'PDF',

        'add'              => 'Add Vehicle',

        'total_fleet'      => 'Total Vehicles',
        'expired_inspection' => 'Inspection Expired',
        'expired_insurance'  => 'Insurance Expired',
        'expired_operation'  => 'Operation Expired',

        'id'               => '#',
        'vehicle'          => 'Vehicle',
        'image'            => 'Image',
        'type'             => 'Type',
        'model'            => 'Model',
        'color'            => 'Color',
        'work'             => 'City',

        'inspection'       => 'Inspection',
        'insurance'        => 'Insurance',
        'operation'        => 'Operation',

        'no_data'          => 'No vehicles match the selected filters',

        'vehicle_count'    => 'Vehicles',

        'sar'              => 'SAR',

    ]

];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$plate = trim($_GET['plate'] ?? '');

$work = trim($_GET['work'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

/* =========================================================
   بناء الاستعلام
========================================================= */

$where = " WHERE 1=1 ";

$params = [];

$types = "";

/* البحث */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR f.driver LIKE ?
            OR f.model LIKE ?
            OR f.typefleet LIKE ?
            OR f.classify LIKE ?
            OR f.colorfleet LIKE ?
            OR f.work LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    for ($i = 0; $i < 7; $i++) {
        $params[] = $value;
    }

    $types .= "sssssss";
}

/* السائق */

if ($driver_id > 0) {

    /*
     * جدول fleet عندك يحتوي driver كنص،
     * لذلك نستخدم اسم السائق بعد جلبه.
     */

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    $driverNameFilter = '';

    if ($driverStmt) {

        $driverStmt->bind_param(
            "i",
            $driver_id
        );

        $driverStmt->execute();

        $driverRow =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverRow) {
            $driverNameFilter = $driverRow['name'];
        }

        $driverStmt->close();
    }

    if ($driverNameFilter !== '') {

        $where .= " AND f.driver LIKE ? ";

        $params[] =
            '%' . $driverNameFilter . '%';

        $types .= "s";

    } else {

        $where .= " AND 1=0 ";
    }
}

/* اللوحة */

if ($plate !== '') {

    $where .= "
        AND f.plate LIKE ?
    ";

    $params[] =
        '%' . $plate . '%';

    $types .= "s";
}

/* المدينة */

if ($work !== '') {

    $where .= "
        AND f.work LIKE ?
    ";

    $params[] =
        '%' . $work . '%';

    $types .= "s";
}

/* الحالة */

if ($status_filter !== '') {

    switch ($status_filter) {

        case 'healthy':

            $where .= "
                AND (
                    f.inspection_expiry IS NULL
                    OR f.inspection_expiry >= CURDATE()
                )
                AND (
                    f.insurance_expiration_date IS NULL
                    OR f.insurance_expiration_date >= CURDATE()
                )
                AND (
                    f.operation_expiry IS NULL
                    OR f.operation_expiry >= CURDATE()
                )
            ";

            break;

        case 'inspection_expired':

            $where .= "
                AND f.inspection_expiry IS NOT NULL
                AND f.inspection_expiry < CURDATE()
            ";

            break;

        case 'insurance_expired':

            $where .= "
                AND f.insurance_expiration_date IS NOT NULL
                AND f.insurance_expiration_date < CURDATE()
            ";

            break;

        case 'operation_expired':

            $where .= "
                AND f.operation_expiry IS NOT NULL
                AND f.operation_expiry < CURDATE()
            ";

            break;
    }
}

/* =========================================================
   جلب المركبات
========================================================= */

$sql = "
    SELECT
        f.*
    FROM fleet f

    $where

    ORDER BY
        f.id DESC
";

$stmt = $con->prepare($sql);

if (!$stmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die(
            'Filter parameters mismatch.'
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$expiredInspection = 0;
$expiredInsurance = 0;
$expiredOperation = 0;
$healthy = 0;

while ($row = $result->fetch_assoc()) {

    $inspectionExpired =
        !empty($row['inspection_expiry']) &&
        $row['inspection_expiry'] < date('Y-m-d');

    $insuranceExpired =
        !empty($row['insurance_expiration_date']) &&
        $row['insurance_expiration_date'] < date('Y-m-d');

    $operationExpired =
        !empty($row['operation_expiry']) &&
        $row['operation_expiry'] < date('Y-m-d');

    if ($inspectionExpired) {
        $expiredInspection++;
    }

    if ($insuranceExpired) {
        $expiredInsurance++;
    }

    if ($operationExpired) {
        $expiredOperation++;
    }

    if (
        !$inspectionExpired &&
        !$insuranceExpired &&
        !$operationExpired
    ) {
        $healthy++;
    }

    $row['inspection_expired'] = $inspectionExpired;
    $row['insurance_expired'] = $insuranceExpired;
    $row['operation_expired'] = $operationExpired;

    $row['is_healthy'] =
        !$inspectionExpired &&
        !$insuranceExpired &&
        !$operationExpired;

    if ($inspectionExpired) {

        $row['status_text'] =
            $t['inspection_expired'];

        $row['status_badge'] =
            'danger';

    } elseif ($insuranceExpired) {

        $row['status_text'] =
            $t['insurance_expired'];

        $row['status_badge'] =
            'warning';

    } elseif ($operationExpired) {

        $row['status_text'] =
            $t['operation_expired'];

        $row['status_badge'] =
            'danger';

    } else {

        $row['status_text'] =
            $t['healthy'];

        $row['status_badge'] =
            'success';
    }

    $rows[] = $row;
}

$totalFiltered = count($rows);

/* =========================================================
   إجمالي الأسطول
========================================================= */

$totalFleet = 0;

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
");

if ($q) {

    $totalFleet =
        (int)($q->fetch_assoc()['total'] ?? 0);
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

    while ($driverRow =
        $driverResult->fetch_assoc()
    ) {

        $drivers[] = $driverRow;
    }
}

/* =========================================================
   روابط Excel و PDF
========================================================= */

$currentParams = $_GET;

$currentParams['lang'] = $lang;
$currentParams['theme'] = $dark;

$excelUrl =
    'fleet_excel.php?' .
    http_build_query($currentParams);

$pdfUrl =
    'fleet_pdf.php?' .
    http_build_query($currentParams);

$resetUrl =
    'reportfleet.php?' .
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

/* =========================================================
   PAGE
========================================================= */

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

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0ea5e9
        );

    color: #fff;

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

    color: var(--muted);

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
   STAT CARDS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 20px;

}

.stat-card {

    position: relative;

    overflow: hidden;

    color: #fff;

    border-radius: 16px;

    padding: 19px;

    min-height: 125px;

    box-shadow:
        0 6px 20px rgba(0,0,0,.08);

}

.stat-icon {

    width: 44px;

    height: 44px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

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
   FILTER CARD
========================================================= */

.filter-card {

    background: var(--card);

    border: 1px solid var(--border);

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

.form-control,
.form-select {

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

    border: 1px solid var(--border);

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

    padding: 10px 8px;

    font-size: 12px;

    vertical-align: middle;

}

.vehicle-image {

    width: 72px;

    height: 50px;

    border-radius: 8px;

    object-fit: cover;

    border: 1px solid var(--border);

    background: #eef1f4;

}

.plate {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 7px;

    background:
        <?= $dark ? '#334155' : '#eef1f4' ?>;

    font-weight: 800;

}

.expired-row {

    background:
        <?= $dark ? 'rgba(220,53,69,.10)' : '#fff5f5' ?>;

}

.document-expired {

    color: #dc3545;

    font-weight: 800;

}

.document-valid {

    color: #198754;

    font-weight: 700;

}

.status-badge {

    font-size: 11px;

    padding: 6px 9px;

    border-radius: 8px;

}

/* =========================================================
   EMPTY
========================================================= */

.empty-state {

    padding: 55px 20px;

    text-align: center;

    color: var(--muted);

}

.empty-state i {

    display: block;

    font-size: 45px;

    margin-bottom: 10px;

}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}

@media(max-width:700px) {

    .page-header {

        align-items: flex-start;

    }

    .stats-grid {

        grid-template-columns: 1fr;

    }

    .header-actions {

        width: 100%;

    }

    .header-actions .btn {

        flex: 1;

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

    .main-card {

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

            <i class="bi bi-car-front-fill"></i>

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
            href="<?= htmlspecialchars($resetUrl) ?>"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-list"></i>

            <?= htmlspecialchars(
                $t['all']
            ) ?>

        </a>


        <a
    href="reportfleet_excel.php?<?= http_build_query(array_merge($_GET, [
        'lang' => $lang,
        'theme' => $dark
    ])) ?>"
    class="btn btn-success"
>
    <i class="bi bi-file-earmark-excel"></i>
    <?= htmlspecialchars($t['excel']) ?>
</a>


        <a
    href="reportfleet_pdf.php?<?= http_build_query(array_merge(
        $_GET,
        [
            'lang'  => $lang,
            'theme' => $dark
        ]
    )) ?>"
    target="_blank"
    class="btn btn-outline-danger"
>
    <i class="bi bi-file-earmark-pdf"></i>
    <?= htmlspecialchars($t['pdf']) ?>
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
     STATS
===================================================== -->

<div class="stats-grid">

    <div class="stat-card bg-primary">

        <div class="stat-icon">

            <i class="bi bi-car-front"></i>

        </div>

        <div class="stat-title">

            <?= $t['total_fleet'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $totalFleet
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-danger">

        <div class="stat-icon">

            <i class="bi bi-clipboard-x"></i>

        </div>

        <div class="stat-title">

            <?= $t['expired_inspection'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $expiredInspection
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-shield-x"></i>

        </div>

        <div class="stat-title">

            <?= $t['expired_insurance'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $expiredInsurance
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-secondary">

        <div class="stat-icon">

            <i class="bi bi-file-earmark-x"></i>

        </div>

        <div class="stat-title">

            <?= $t['expired_operation'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $expiredOperation
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
            placeholder="<?= htmlspecialchars(
                $t['plate']
            ) ?>"
        >

    </div>


    <!-- المدينة -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['city'] ?>

        </label>

        <input
            type="text"
            name="work"
            value="<?= htmlspecialchars(
                $work
            ) ?>"
            class="form-control"
            placeholder="<?= htmlspecialchars(
                $t['city']
            ) ?>"
        >

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
                value="healthy"
                <?= $status_filter === 'healthy'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['healthy'] ?>

            </option>

            <option
                value="inspection_expired"
                <?= $status_filter === 'inspection_expired'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['inspection_expired'] ?>

            </option>

            <option
                value="insurance_expired"
                <?= $status_filter === 'insurance_expired'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['insurance_expired'] ?>

            </option>

            <option
                value="operation_expired"
                <?= $status_filter === 'operation_expired'
                    ? 'selected'
                    : '' ?>
            >

                <?= $t['operation_expired'] ?>

            </option>

        </select>

    </div>


    <!-- زر -->

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
        <?= $t['id'] ?>
    </th>

    <th>
        <?= $t['image'] ?>
    </th>

    <th>
        <?= $t['driver'] ?>
    </th>

    <th>
        <?= $t['plate'] ?>
    </th>

    <th>
        <?= $t['type'] ?>
    </th>

    <th>
        <?= $t['model'] ?>
    </th>

    <th>
        <?= $t['color'] ?>
    </th>

    <th>
        <?= $t['work'] ?>
    </th>

    <th>
        <?= $t['inspection'] ?>
    </th>

    <th>
        <?= $t['insurance'] ?>
    </th>

    <th>
        <?= $t['operation'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

</tr>

</thead>

<tbody>

<?php if (empty($rows)): ?>

<tr>

    <td colspan="12">

        <div class="empty-state">

            <i class="bi bi-car-front"></i>

            <?= htmlspecialchars(
                $t['no_data']
            ) ?>

        </div>

    </td>

</tr>

<?php else: ?>

<?php foreach ($rows as $row): ?>

<tr class="<?= (
    !$row['is_healthy']
        ? 'expired-row'
        : ''
) ?>">

    <!-- ID -->

    <td>

        <strong>

            #<?= (int)$row['id'] ?>

        </strong>

    </td>


    <!-- Image -->

    <td>

        <?php

        $imageName =
            trim(
                (string)(
                    $row['imgfleet'] ?? ''
                )
            );

        $imagePath =
            '../fleetimg/img/' .
            $imageName;

        if (
            $imageName === '' ||
            !file_exists(
                __DIR__ .
                '/../fleetimg/img/' .
                $imageName
            )
        ) {

            $imagePath =
                '../assets/img/no-car.png';
        }

        ?>

        <img
            src="<?= htmlspecialchars(
                $imagePath
            ) ?>"
            class="vehicle-image"
            alt="<?= htmlspecialchars(
                $row['plate'] ?? ''
            ) ?>"
        >

    </td>


    <!-- Driver -->

    <td>

        <?= htmlspecialchars(
            $row['driver'] ?? '-'
        ) ?>

    </td>


    <!-- Plate -->

    <td>

        <span class="plate">

            <?= htmlspecialchars(
                $row['plate'] ?? '-'
            ) ?>

        </span>

    </td>


    <!-- Type -->

    <td>

        <?= htmlspecialchars(
            $row['typefleet'] ?? '-'
        ) ?>

    </td>


    <!-- Model -->

    <td>

        <?= htmlspecialchars(
            $row['model'] ?? '-'
        ) ?>

    </td>


    <!-- Color -->

    <td>

        <?= htmlspecialchars(
            $row['colorfleet'] ?? '-'
        ) ?>

    </td>


    <!-- City -->

    <td>

        <?= htmlspecialchars(
            $row['work'] ?? '-'
        ) ?>

    </td>


    <!-- Inspection -->

    <td class="<?= $row['inspection_expired']
        ? 'document-expired'
        : 'document-valid'
    ?>">

        <?= htmlspecialchars(
            $row['inspection_expiry'] ?? '-'
        ) ?>

    </td>


    <!-- Insurance -->

    <td class="<?= $row['insurance_expired']
        ? 'document-expired'
        : 'document-valid'
    ?>">

        <?= htmlspecialchars(
            $row['insurance_expiration_date'] ?? '-'
        ) ?>

    </td>


    <!-- Operation -->

    <td class="<?= $row['operation_expired']
        ? 'document-expired'
        : 'document-valid'
    ?>">

        <?= htmlspecialchars(
            $row['operation_expiry'] ?? '-'
        ) ?>

    </td>


    <!-- Status -->

    <td>

        <span
            class="badge bg-<?= htmlspecialchars(
                $row['status_badge']
            ) ?> status-badge"
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


<?php if (!empty($rows)): ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">

    <div class="text-muted">

        <?= $t['vehicle_count'] ?>:

        <strong>

            <?= number_format(
                $totalFiltered
            ) ?>

        </strong>

    </div>

    <div>

        <span class="badge bg-success me-1">

            <?= $t['healthy'] ?>:

            <?= number_format(
                $healthy
            ) ?>

        </span>

        <span class="badge bg-danger me-1">

            <?= $t['inspection_expired'] ?>:

            <?= number_format(
                $expiredInspection
            ) ?>

        </span>

        <span class="badge bg-warning text-dark me-1">

            <?= $t['insurance_expired'] ?>:

            <?= number_format(
                $expiredInsurance
            ) ?>

        </span>

        <span class="badge bg-secondary">

            <?= $t['operation_expired'] ?>:

            <?= number_format(
                $expiredOperation
            ) ?>

        </span>

    </div>

</div>

<?php endif; ?>

</div>

</div>

</body>

</html>