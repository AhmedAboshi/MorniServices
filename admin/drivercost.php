
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   مكتبات Excel
========================================================= */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
   رقم السائق
========================================================= */

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die(
        $lang === 'ar'
            ? 'رقم السائق غير صحيح'
            : 'Invalid driver ID'
    );
}

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'              => 'تفاصيل تكاليف السائق',
        'subtitle'           => 'الملف المالي والتشغيلي للسائق',

        'back'               => 'العودة إلى تقرير التكاليف',
        'print'              => 'طباعة',
        'excel'              => 'Excel',
        'pdf'                => 'PDF',

        'driver'             => 'السائق',
        'plate'              => 'رقم اللوحة',
        'phone'              => 'الجوال',
        'work_area'          => 'منطقة العمل',
        'national_id'        => 'رقم الهوية',
        'truck_type'         => 'نوع السطحة',

        'oil'                => 'الزيت',
        'tires'              => 'الإطارات',
        'maintenance'        => 'الصيانة',
        'total'              => 'الإجمالي',

        'oil_records'        => 'سجلات تغيير الزيت',
        'tire_records'       => 'سجلات الإطارات',
        'maintenance_records'=> 'سجلات الصيانة',

        'date'               => 'التاريخ',
        'type'               => 'النوع',
        'km'                 => 'العداد',
        'next_km'            => 'العداد القادم',
        'next_change'        => 'التغيير القادم',
        'cost'               => 'التكلفة',
        'notes'              => 'الملاحظات',
        'vehicle'            => 'المركبة',

        'from'               => 'من تاريخ',
        'to'                 => 'إلى تاريخ',
        'filter'             => 'تطبيق',
        'reset'              => 'إعادة ضبط',

        'sar'                => 'ريال',
        'all_period'         => 'كل الفترات',
        'no_records'         => 'لا توجد سجلات',

        'generated'          => 'تاريخ التقرير',
        'cost_summary'       => 'ملخص التكاليف',

        'export_success'      => '',
        'company'            => 'شركة الشرق لخدمات السيارات'
    ],

    'en' => [

        'title'              => 'Driver Cost Details',
        'subtitle'           => 'Driver financial and operational cost profile',

        'back'               => 'Back to Cost Report',
        'print'              => 'Print',
        'excel'              => 'Excel',
        'pdf'                => 'PDF',

        'driver'             => 'Driver',
        'plate'              => 'Plate Number',
        'phone'              => 'Phone',
        'work_area'          => 'Work Area',
        'national_id'        => 'National ID',
        'truck_type'         => 'Truck Type',

        'oil'                => 'Oil',
        'tires'              => 'Tires',
        'maintenance'        => 'Maintenance',
        'total'              => 'Total',

        'oil_records'        => 'Oil Change Records',
        'tire_records'       => 'Tire Records',
        'maintenance_records'=> 'Maintenance Records',

        'date'               => 'Date',
        'type'               => 'Type',
        'km'                 => 'Current KM',
        'next_km'            => 'Next KM',
        'next_change'        => 'Next Change',
        'cost'               => 'Cost',
        'notes'              => 'Notes',
        'vehicle'            => 'Vehicle',

        'from'               => 'From Date',
        'to'                 => 'To Date',
        'filter'             => 'Apply',
        'reset'              => 'Reset',

        'sar'               => 'SAR',
        'all_period'        => 'All Periods',
        'no_records'        => 'No records found',

        'generated'         => 'Report Date',
        'cost_summary'      => 'Cost Summary',

        'export_success'    => '',
        'company'           => 'Al Sharq Automotive Services Company'
    ]
];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');

/* =========================================================
   بيانات السائق
========================================================= */

$stmtDriver = $con->prepare("
    SELECT
        id,
        name,
        national_id,
        phone,
        work_area,
        truck_type,
        plate_number,
        imagedriver
    FROM drivers
    WHERE id = ?
    LIMIT 1
");

if (!$stmtDriver) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmtDriver->bind_param("i", $id);

if (!$stmtDriver->execute()) {
    die(
        'Execute Error: ' .
        htmlspecialchars($stmtDriver->error)
    );
}

$driver = $stmtDriver
    ->get_result()
    ->fetch_assoc();

$stmtDriver->close();

if (!$driver) {
    die(
        $lang === 'ar'
            ? 'السائق غير موجود'
            : 'Driver not found'
    );
}

/* =========================================================
   شروط الزيت
========================================================= */

$oilWhere = "WHERE driver_id = ?";
$oilParams = [$id];
$oilTypes = "i";

if ($from !== '') {
    $oilWhere .= " AND DATE(change_date) >= ?";
    $oilParams[] = $from;
    $oilTypes .= "s";
}

if ($to !== '') {
    $oilWhere .= " AND DATE(change_date) <= ?";
    $oilParams[] = $to;
    $oilTypes .= "s";
}

/* =========================================================
   شروط الإطارات
========================================================= */

$tireWhere = "WHERE driver_id = ?";
$tireParams = [$id];
$tireTypes = "i";

if ($from !== '') {
    $tireWhere .= " AND DATE(change_date) >= ?";
    $tireParams[] = $from;
    $tireTypes .= "s";
}

if ($to !== '') {
    $tireWhere .= " AND DATE(change_date) <= ?";
    $tireParams[] = $to;
    $tireTypes .= "s";
}

/* =========================================================
   شروط الصيانة
========================================================= */

$maintenanceWhere = "WHERE driver_id = ?";
$maintenanceParams = [$id];
$maintenanceTypes = "i";

if ($from !== '') {
    $maintenanceWhere .= " AND DATE(maintenance_date) >= ?";
    $maintenanceParams[] = $from;
    $maintenanceTypes .= "s";
}

if ($to !== '') {
    $maintenanceWhere .= " AND DATE(maintenance_date) <= ?";
    $maintenanceParams[] = $to;
    $maintenanceTypes .= "s";
}

/* =========================================================
   إجمالي الزيت
========================================================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(cost),0) AS total
    FROM oil_changes
    $oilWhere
");

$stmt->bind_param(
    $oilTypes,
    ...$oilParams
);

$stmt->execute();

$oilTotal = (float)(
    $stmt
        ->get_result()
        ->fetch_assoc()['total'] ?? 0
);

$stmt->close();

/* =========================================================
   إجمالي الإطارات
========================================================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(cost),0) AS total
    FROM tires
    $tireWhere
");

$stmt->bind_param(
    $tireTypes,
    ...$tireParams
);

$stmt->execute();

$tireTotal = (float)(
    $stmt
        ->get_result()
        ->fetch_assoc()['total'] ?? 0
);

$stmt->close();

/* =========================================================
   إجمالي الصيانة
========================================================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(cost),0) AS total
    FROM maintenance
    $maintenanceWhere
");

$stmt->bind_param(
    $maintenanceTypes,
    ...$maintenanceParams
);

$stmt->execute();

$maintenanceTotal = (float)(
    $stmt
        ->get_result()
        ->fetch_assoc()['total'] ?? 0
);

$stmt->close();

/* =========================================================
   الإجمالي الكلي
========================================================= */

$grandTotal =
    $oilTotal +
    $tireTotal +
    $maintenanceTotal;

/* =========================================================
   سجل الزيت
========================================================= */

$oilRows = [];

$stmt = $con->prepare("
    SELECT
        id,
        change_date,
        oil_type,
        km_change,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM oil_changes
    $oilWhere
    ORDER BY change_date DESC, id DESC
");

$stmt->bind_param(
    $oilTypes,
    ...$oilParams
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $oilRows[] = $row;
}

$stmt->close();

/* =========================================================
   سجل الإطارات
========================================================= */

$tireRows = [];

$stmt = $con->prepare("
    SELECT
        id,
        change_date,
        tire_type,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM tires
    $tireWhere
    ORDER BY change_date DESC, id DESC
");

$stmt->bind_param(
    $tireTypes,
    ...$tireParams
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tireRows[] = $row;
}

$stmt->close();

/* =========================================================
   سجل الصيانة
========================================================= */

$maintenanceRows = [];

$stmt = $con->prepare("
    SELECT
        id,
        maintenance_date,
        maintenance_type,
        notes,
        cost,
        vehicle_name
    FROM maintenance
    $maintenanceWhere
    ORDER BY maintenance_date DESC, id DESC
");

$stmt->bind_param(
    $maintenanceTypes,
    ...$maintenanceParams
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $maintenanceRows[] = $row;
}

$stmt->close();

/* =========================================================
   تصدير Excel
   يجب أن يكون بعد تعريف اللغة والفلاتر والسائق والبيانات
========================================================= */



/* =========================================================
   روابط الصفحة
========================================================= */

$backUrl =
    'driverviewcost.php?' .
    http_build_query([
        'lang'  => $lang,
        'theme' => $dark,
        'from'  => $from,
        'to'    => $to
    ]);

$excelUrl =
    '?' .
    http_build_query([
        'id'     => $id,
        'from'   => $from,
        'to'     => $to,
        'lang'   => $lang,
        'theme'  => $dark,
        'export' => 'excel'
    ]);

$pdfUrl =
    'drivercost_pdf.php?' .
    http_build_query([
        'id'    => $id,
        'from'  => $from,
        'to'    => $to,
        'lang'  => $lang,
        'theme' => $dark
    ]);

$currentUrl = [
    'id'    => $id,
    'from'  => $from,
    'to'    => $to,
    'lang'  => $lang,
    'theme' => $dark
];

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
    box-sizing: border-box;
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

    margin: 0;

    background: var(--bg);

    color: var(--text);

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;
}

.page-container {

    max-width: 1500px;

    margin: 30px auto;

    padding: 0 18px;
}

/* HEADER */

.page-header {

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: #fff;

    border-radius: 18px;

    padding: 22px;

    margin-bottom: 20px;

    box-shadow:
        0 7px 25px
        rgba(0,0,0,.12);
}

.header-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;
}

.driver-info {

    display: flex;

    align-items: center;

    gap: 15px;
}

.driver-photo {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    object-fit: cover;

    border: 3px solid
        rgba(255,255,255,.65);

    background: #fff;
}

.driver-photo-empty {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.2);

    font-size: 30px;
}

.driver-info h1 {

    margin: 0;

    font-size: 25px;

    font-weight: 800;
}

.driver-info p {

    margin: 4px 0 0;

    font-size: 13px;

    opacity: .9;
}

.header-actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

.header-actions .btn {

    border-radius: 9px;
}

/* INFO */

.info-card {

    background: var(--card);

    border: 1px solid
        var(--border);

    border-radius: 16px;

    padding: 17px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);
}

.info-item {

    background: var(--soft);

    border: 1px solid
        var(--border);

    border-radius: 10px;

    padding: 12px;
}

.info-label {

    display: block;

    color: var(--muted);

    font-size: 11px;

    margin-bottom: 4px;
}

.info-value {

    font-weight: 700;

    font-size: 14px;
}

/* FILTER */

.filter-card {

    background: var(--card);

    border: 1px solid
        var(--border);

    border-radius: 16px;

    padding: 17px;

    margin-bottom: 20px;
}

.form-control {

    min-height: 42px;

    border-radius: 9px;

    background: var(--soft);

    color: var(--text);

    border-color: var(--border);
}

/* STATS */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 15px;

    margin-bottom: 20px;
}

.stat-card {

    color: #fff;

    border-radius: 16px;

    padding: 18px;

    min-height: 115px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.08);
}

.stat-icon {

    font-size: 22px;

    margin-bottom: 8px;
}

.stat-title {

    font-size: 12px;

    opacity: .9;
}

.stat-value {

    font-size: 23px;

    font-weight: 800;
}

/* SECTIONS */

.section-card {

    background: var(--card);

    border: 1px solid
        var(--border);

    border-radius: 17px;

    margin-bottom: 20px;

    overflow: hidden;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);
}

.section-header {

    padding: 15px 18px;

    background: var(--soft);

    border-bottom: 1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}

.section-header h3 {

    margin: 0;

    font-size: 16px;

    font-weight: 800;
}

.section-body {

    padding: 17px;
}

.table th {

    white-space: nowrap;

    font-size: 12px;
}

.table td {

    font-size: 12px;

    vertical-align: middle;
}

.cost {

    color: #198754;

    font-weight: 800;
}

/* EMPTY */

.empty-state {

    padding: 40px;

    text-align: center;

    color: var(--muted);
}

.empty-state i {

    display: block;

    font-size: 38px;

    margin-bottom: 8px;
}

/* RESPONSIVE */

@media(max-width:900px) {

    .stats-grid {

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:600px) {

    .stats-grid {

        grid-template-columns: 1fr;
    }

    .driver-info h1 {

        font-size: 20px;
    }

    .header-actions {

        width: 100%;
    }

    .header-actions .btn {

        flex: 1;
    }
}

/* PRINT */

@media print {

    .no-print {

        display: none !important;
    }

    body {

        background: #fff !important;

        color: #000 !important;
    }

    .page-container {

        max-width: 100%;

        margin: 0;
    }

    .page-header,
    .info-card,
    .section-card {

        box-shadow: none;
    }
}

</style>

</head>

<body>

<div class="page-container">

<!-- HEADER -->

<div class="page-header">

<div class="header-top">

<div class="driver-info">

<?php

$imageName =
    trim(
        (string)(
            $driver['imagedriver']
            ?? ''
        )
    );

?>

<?php if ($imageName !== ''): ?>

<img
    src="../uploads/<?= htmlspecialchars(
        basename($imageName)
    ) ?>"
    class="driver-photo"
    onerror="
        this.style.display='none';
        this.nextElementSibling.style.display='flex';
    "
>

<div
    class="driver-photo-empty"
    style="display:none;"
>
    <i class="bi bi-person"></i>
</div>

<?php else: ?>

<div class="driver-photo-empty">

    <i class="bi bi-person"></i>

</div>

<?php endif; ?>

<div>

<h1>

<i class="bi bi-person-badge"></i>

<?= htmlspecialchars(
    $driver['name']
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
        $backUrl
    ) ?>"
    class="btn btn-light"
>

<i class="bi bi-arrow-right"></i>

<?= $t['back'] ?>

</a>

<!-- Excel -->

<form
    method="GET"
    action="drivercost_excel.php"
    target="_blank"
    style="display:inline-block;margin:0;"
>
    <input
        type="hidden"
        name="id"
        value="<?= (int)$id ?>"
    >

    <input
        type="hidden"
        name="from"
        value="<?= htmlspecialchars($from ?? '', ENT_QUOTES) ?>"
    >

    <input
        type="hidden"
        name="to"
        value="<?= htmlspecialchars($to ?? '', ENT_QUOTES) ?>"
    >

    <input
        type="hidden"
        name="lang"
        value="<?= htmlspecialchars($lang, ENT_QUOTES) ?>"
    >

    <input
        type="hidden"
        name="theme"
        value="<?= (int)$dark ?>"
    >

    <button
        type="submit"
        class="btn btn-success"
    >
        <i class="bi bi-file-earmark-excel"></i>
        <?= htmlspecialchars($t['excel']) ?>
    </button>
</form>

<!-- PDF -->

<a
    href="<?= htmlspecialchars(
        $pdfUrl
    ) ?>"
    target="_blank"
    class="btn btn-danger"
>

<i class="bi bi-file-earmark-pdf"></i>

<?= $t['pdf'] ?>

</a>

<!-- Print -->

<button
    type="button"
    onclick="window.print()"
    class="btn btn-warning"
>

<i class="bi bi-printer"></i>

<?= $t['print'] ?>

</button>

<!-- Language -->

<?php if ($lang === 'ar'): ?>

<a
    href="?<?= http_build_query([
        'id' => $id,
        'from' => $from,
        'to' => $to,
        'lang' => 'en',
        'theme' => $dark
    ]) ?>"
    class="btn btn-light"
>
    EN
</a>

<?php else: ?>

<a
    href="?<?= http_build_query([
        'id' => $id,
        'from' => $from,
        'to' => $to,
        'lang' => 'ar',
        'theme' => $dark
    ]) ?>"
    class="btn btn-light"
>
    AR
</a>

<?php endif; ?>

<!-- Theme -->

<?php if ($dark): ?>

<a
    href="?<?= http_build_query([
        'id' => $id,
        'from' => $from,
        'to' => $to,
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
        'id' => $id,
        'from' => $from,
        'to' => $to,
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

</div>


<!-- بيانات السائق -->

<div class="info-card">

<div class="row g-3">

<div class="col-lg-3 col-md-6">

<div class="info-item">

<span class="info-label">
<?= $t['plate'] ?>
</span>

<span class="info-value">
<?= htmlspecialchars(
    $driver['plate_number']
    ?? '-'
) ?>
</span>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="info-item">

<span class="info-label">
<?= $t['national_id'] ?>
</span>

<span class="info-value">
<?= htmlspecialchars(
    $driver['national_id']
    ?? '-'
) ?>
</span>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="info-item">

<span class="info-label">
<?= $t['phone'] ?>
</span>

<span class="info-value">
<?= htmlspecialchars(
    $driver['phone']
    ?? '-'
) ?>
</span>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="info-item">

<span class="info-label">
<?= $t['work_area'] ?>
</span>

<span class="info-value">
<?= htmlspecialchars(
    $driver['work_area']
    ?? '-'
) ?>
</span>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="info-item">

<span class="info-label">
<?= $t['truck_type'] ?>
</span>

<span class="info-value">
<?= htmlspecialchars(
    $driver['truck_type']
    ?? '-'
) ?>
</span>

</div>

</div>

</div>

</div>


<!-- الإحصائيات -->

<div class="stats-grid">

<div class="stat-card bg-info">

<div class="stat-icon">
<i class="bi bi-droplet-fill"></i>
</div>

<div class="stat-title">
<?= $t['oil'] ?>
</div>

<div class="stat-value">

<?= number_format(
    $oilTotal,
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
<?= $t['tires'] ?>
</div>

<div class="stat-value">

<?= number_format(
    $tireTotal,
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
<?= $t['maintenance'] ?>
</div>

<div class="stat-value">

<?= number_format(
    $maintenanceTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>


<div
    class="stat-card"
    style="
        background:
        linear-gradient(
            135deg,
            #6f42c1,
            #512da8
        );
    "
>

<div class="stat-icon">
<i class="bi bi-cash-stack"></i>
</div>

<div class="stat-title">
<?= $t['total'] ?>
</div>

<div class="stat-value">

<?= number_format(
    $grandTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>

</div>


<!-- الفلاتر -->

<div class="filter-card no-print">

<form method="GET">

<input
    type="hidden"
    name="id"
    value="<?= $id ?>"
>

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
    value="<?= $dark ?>"
>

<div class="row g-3">

<div class="col-md-4">

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


<div class="col-md-4">

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


<div class="col-md-2 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>

<i class="bi bi-search"></i>

<?= $t['filter'] ?>

</button>

</div>


<div class="col-md-2 d-flex align-items-end">

<a
    href="?<?= http_build_query([
        'id' => $id,
        'lang' => $lang,
        'theme' => $dark
    ]) ?>"
    class="btn btn-outline-secondary w-100"
>

<i class="bi bi-arrow-counterclockwise"></i>

<?= $t['reset'] ?>

</a>

</div>

</div>

</form>

</div>


<!-- سجل الزيت -->

<div class="section-card">

<div class="section-header">

<h3>

<i class="bi bi-droplet-fill text-info"></i>

<?= $t['oil_records'] ?>

</h3>

<strong class="text-success">

<?= number_format(
    $oilTotal,
    2
) ?>

<?= $t['sar'] ?>

</strong>

</div>

<div class="section-body">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead class="table-dark">

<tr>

<th>#</th>

<th><?= $t['date'] ?></th>

<th><?= $t['type'] ?></th>

<th><?= $t['km'] ?></th>

<th><?= $t['next_km'] ?></th>

<th><?= $t['next_change'] ?></th>

<th><?= $t['cost'] ?></th>

<th><?= $t['notes'] ?></th>

</tr>

</thead>

<tbody>

<?php if (empty($oilRows)): ?>

<tr>

<td colspan="8">

<div class="empty-state">

<i class="bi bi-droplet"></i>

<?= $t['no_records'] ?>

</div>

</td>

</tr>

<?php else: ?>

<?php foreach (
    $oilRows
    as $index => $row
): ?>

<tr>

<td><?= $index + 1 ?></td>

<td>
<?= htmlspecialchars(
    $row['change_date']
    ?? '-'
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row['oil_type']
    ?? '-'
) ?>
</td>

<td>
<?= number_format(
    (int)(
        $row['current_km']
        ??
        $row['km_change']
        ?? 0
    )
) ?>
</td>

<td>
<?= number_format(
    (int)(
        $row['next_km']
        ?? 0
    )
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row['next_change']
    ?? '-'
) ?>
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

<td>
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

</div>

</div>


<!-- سجل الإطارات -->

<div class="section-card">

<div class="section-header">

<h3>

<i class="bi bi-disc text-warning"></i>

<?= $t['tire_records'] ?>

</h3>

<strong class="text-success">

<?= number_format(
    $tireTotal,
    2
) ?>

<?= $t['sar'] ?>

</strong>

</div>

<div class="section-body">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead class="table-dark">

<tr>

<th>#</th>

<th><?= $t['date'] ?></th>

<th><?= $t['type'] ?></th>

<th><?= $t['km'] ?></th>

<th><?= $t['next_km'] ?></th>

<th><?= $t['next_change'] ?></th>

<th><?= $t['cost'] ?></th>

<th><?= $t['notes'] ?></th>

</tr>

</thead>

<tbody>

<?php if (empty($tireRows)): ?>

<tr>

<td colspan="8">

<div class="empty-state">

<i class="bi bi-disc"></i>

<?= $t['no_records'] ?>

</div>

</td>

</tr>

<?php else: ?>

<?php foreach (
    $tireRows
    as $index => $row
): ?>

<tr>

<td><?= $index + 1 ?></td>

<td>
<?= htmlspecialchars(
    $row['change_date']
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
<?= number_format(
    (int)(
        $row['current_km']
        ?? 0
    )
) ?>
</td>

<td>
<?= number_format(
    (int)(
        $row['next_km']
        ?? 0
    )
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row['next_change']
    ?? '-'
) ?>
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

<td>
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

</div>

</div>


<!-- سجل الصيانة -->

<div class="section-card">

<div class="section-header">

<h3>

<i class="bi bi-tools text-success"></i>

<?= $t['maintenance_records'] ?>

</h3>

<strong class="text-success">

<?= number_format(
    $maintenanceTotal,
    2
) ?>

<?= $t['sar'] ?>

</strong>

</div>

<div class="section-body">

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead class="table-dark">

<tr>

<th>#</th>

<th><?= $t['date'] ?></th>

<th><?= $t['type'] ?></th>

<th><?= $t['vehicle'] ?></th>

<th><?= $t['cost'] ?></th>

<th><?= $t['notes'] ?></th>

</tr>

</thead>

<tbody>

<?php if (empty($maintenanceRows)): ?>

<tr>

<td colspan="6">

<div class="empty-state">

<i class="bi bi-tools"></i>

<?= $t['no_records'] ?>

</div>

</td>

</tr>

<?php else: ?>

<?php foreach (
    $maintenanceRows
    as $index => $row
): ?>

<tr>

<td><?= $index + 1 ?></td>

<td>
<?= htmlspecialchars(
    $row['maintenance_date']
    ?? '-'
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row['maintenance_type']
    ?? '-'
) ?>
</td>

<td>
<?= htmlspecialchars(
    $row['vehicle_name']
    ?? '-'
) ?>
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

<td>
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

</div>

</div>


<!-- الإجمالي النهائي -->

<div class="info-card">

<div class="row g-3 text-center">

<div class="col-md-4">

<strong><?= $t['oil'] ?></strong>

<div class="fs-4 text-info">

<?= number_format(
    $oilTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>


<div class="col-md-4">

<strong><?= $t['tires'] ?></strong>

<div class="fs-4 text-warning">

<?= number_format(
    $tireTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>


<div class="col-md-4">

<strong><?= $t['maintenance'] ?></strong>

<div class="fs-4 text-success">

<?= number_format(
    $maintenanceTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>

</div>

<hr>

<div class="text-center">

<div class="text-muted">
<?= $t['total'] ?>
</div>

<div class="display-6 fw-bold text-success">

<?= number_format(
    $grandTotal,
    2
) ?>

<?= $t['sar'] ?>

</div>

</div>

</div>

</div>

</body>

</html>

