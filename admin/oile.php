<?php


session_start();
include('../include/connected.php');

/* =========================
   اللغة
========================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}


/* =========================
   الوضع الليلي
========================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$dark = $_SESSION['theme'] ?? 0;


/* =========================
   الترجمة
========================= */

$text = [

    'ar' => [

        'title'         => 'سجلات تغيير الزيت',
        'subtitle'      => 'إدارة ومتابعة عمليات تغيير زيت المركبات',

        'add'           => 'إضافة تغيير زيت',

        'search'        => 'بحث...',
        'all_cars'      => 'جميع المركبات',
        'all_drivers'   => 'جميع السائقين',
        'from'          => 'من تاريخ',
        'to'            => 'إلى تاريخ',
        'filter'        => 'تطبيق الفلتر',
        'reset'          => 'إعادة ضبط',

        'total_records' => 'إجمالي السجلات',
        'total_cost'    => 'إجمالي التكلفة',
        'total_cars'    => 'المركبات',
        'total_drivers' => 'السائقين',

        'id'             => '#',
        'car'            => 'المركبة',
        'driver'         => 'السائق',
        'oil_type'       => 'نوع الزيت',
        'change_date'    => 'تاريخ التغيير',
        'next_change'    => 'التغيير القادم',
        'current_km'     => 'العداد الحالي',
        'next_km'        => 'العداد القادم',
        'cost'           => 'التكلفة',
        'remaining'      => 'المتبقي',
        'status'         => 'الحالة',
        'actions'        => 'العمليات',
        'notes'          => 'الملاحظات',

        'good'           => 'ممتاز',
        'soon'           => 'قريب',
        'late'           => 'متأخر',
        'expired'        => 'منتهي',
        'day'            => 'يوم',

        'view'           => 'عرض',
        'edit'           => 'تعديل',
        'delete'         => 'حذف',

        'no_data'        => 'لا توجد سجلات',

        'print'          => 'طباعة',
        'excel'          => 'Excel',

        'confirm_delete' => 'هل تريد حذف سجل تغيير الزيت؟',

        'sar'            => 'ريال',
        'reset'=>'إعادة ضبط',

    ],

    'en' => [

        'title'         => 'Oil Change Records',
        'subtitle'      => 'Manage and monitor vehicle oil changes',

        'add'           => 'Add Oil Change',

        'search'        => 'Search...',
        'all_cars'      => 'All Vehicles',
        'all_drivers'   => 'All Drivers',
        'from'          => 'From Date',
        'to'            => 'To Date',
        'filter'        => 'Apply Filter',
        'reset'         => 'Reset',

        'total_records' => 'Total Records',
        'total_cost'    => 'Total Cost',
        'total_cars'    => 'Vehicles',
        'total_drivers' => 'Drivers',

        'id'             => '#',
        'car'            => 'Vehicle',
        'driver'         => 'Driver',
        'oil_type'       => 'Oil Type',
        'change_date'    => 'Change Date',
        'next_change'    => 'Next Change',
        'current_km'     => 'Current KM',
        'next_km'        => 'Next KM',
        'cost'           => 'Cost',
        'remaining'      => 'Remaining',
        'status'         => 'Status',
        'actions'        => 'Actions',
        'notes'          => 'Notes',

        'good'           => 'Good',
        'soon'           => 'Soon',
        'late'           => 'Late',
        'expired'        => 'Expired',
        'day'            => 'Day',

        'view'           => 'View',
        'edit'           => 'Edit',
        'delete'         => 'Delete',

        'no_data'        => 'No records found',

        'print'          => 'Print',
        'excel'          => 'Excel',
        'reset'=>'Reset',

        'confirm_delete' => 'Do you want to delete this oil change record?',

        'sar'            => 'SAR',

    ]

];

$t = $text[$lang];


/* =========================
   الفلاتر
========================= */

$from      = trim($_GET['from'] ?? '');
$to        = trim($_GET['to'] ?? '');
$car_id    = (int)($_GET['car_id'] ?? 0);
$driver_id = (int)($_GET['driver_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');


/* =========================
   بناء الاستعلام
========================= */

$where = " WHERE 1=1 ";

$params = [];
$types  = "";


/* التاريخ */

if ($from !== '') {

    $where .= " AND t.change_date >= ? ";

    $params[] = $from;
    $types .= "s";
}


if ($to !== '') {

    $where .= " AND t.change_date <= ? ";

    $params[] = $to;
    $types .= "s";
}


/* المركبة */

if ($car_id > 0) {

    $where .= " AND t.car_id = ? ";

    $params[] = $car_id;
    $types .= "i";
}


/* السائق */

if ($driver_id > 0) {

    $where .= " AND t.driver_id = ? ";

    $params[] = $driver_id;
    $types .= "i";
}


/* البحث */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR d.name LIKE ?
            OR t.oil_type LIKE ?
            OR t.notes LIKE ?
        )
    ";

    $searchValue = "%".$search."%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}


/* =========================
   جلب السجلات
========================= */

$sql = "

SELECT

    t.*,

    f.plate AS vehicle_plate,

    d.name AS driver_name,

    COALESCE(
        NULLIF(d.name, ''),
        NULLIF(t.driver, ''),
        '-'
    ) AS display_driver

FROM oil_changes t

LEFT JOIN fleet f
    ON t.car_id = f.id

LEFT JOIN drivers d
    ON t.driver_id = d.id

$where

ORDER BY t.change_date DESC, t.id DESC

";


$stmt = $con->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $con->error);
}


if (!empty($params)) {

    $stmt->bind_param($types, ...$params);

}


$stmt->execute();

$result = $stmt->get_result();


/* =========================
   البيانات والإحصائيات
========================= */

$rows = [];

$totalCost = 0;

$late = 0;
$soon = 0;
$good = 0;


while ($row = $result->fetch_assoc()) {

    $cost = (float)($row['cost'] ?? 0);

    $totalCost += $cost;


    /* =========================
       حالة تغيير الزيت
    ========================= */

    $nextChange = $row['next_change'] ?? '';

    if ($nextChange !== '') {

        $daysLeft = ceil(
            (strtotime($nextChange) - strtotime(date('Y-m-d')))
            / 86400
        );

    } else {

        $daysLeft = 999999;

    }


    if ($daysLeft < 0) {

        $status = $t['late'];
        $badge  = 'danger';

        $late++;

    } elseif ($daysLeft <= 30) {

        $status = $t['soon'];
        $badge  = 'warning';

        $soon++;

    } else {

        $status = $t['good'];
        $badge  = 'success';

        $good++;

    }


    $row['days_left'] = $daysLeft;
    $row['status_text'] = $status;
    $row['badge'] = $badge;


    $rows[] = $row;
}


$totalRecords = count($rows);


/* =========================
   إجمالي المركبات
========================= */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
");

$totalCars = 0;

if ($q) {

    $totalCars = (int)$q->fetch_assoc()['total'];

}


/* =========================
   إجمالي السائقين
========================= */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
");

$totalDrivers = 0;

if ($q) {

    $totalDrivers = (int)$q->fetch_assoc()['total'];

}


/* =========================
   قوائم الفلاتر
========================= */

$cars = $con->query("
    SELECT id, plate
    FROM fleet
    ORDER BY plate ASC
");


$drivers = $con->query("
    SELECT id, name
    FROM drivers
    ORDER BY name ASC
");


/* =========================
   رابط Excel
========================= */

$excelParams = $_GET;

$excelParams['lang'] = $lang;

$excelUrl = 'oil_excel.php?' . http_build_query($excelParams);


/* =========================
   رابط الطباعة
========================= */

$printParams = $_GET;

$printParams['lang'] = $lang;

$printUrl = 'oil_print.php?' . http_build_query($printParams);

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
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>


<style>

/* =========================
   General
========================= */

body{

    background:
        <?= $dark ? '#121212' : '#f4f6f9' ?>;

    color:
        <?= $dark ? '#fff' : '#1f2937' ?>;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

}


.container-fluid{

    max-width:1500px;

}


/* =========================
   Header
========================= */

.page-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    margin-bottom:25px;

}


.page-title h2{

    margin:0;

    font-size:26px;

    font-weight:bold;

}


.page-title p{

    margin:7px 0 0;

    color:
        <?= $dark ? '#aaa' : '#7b8491' ?>;

    font-size:14px;

}


.header-actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;

}


/* =========================
   Cards
========================= */

.stat-card{

    border:none;

    border-radius:16px;

    padding:20px;

    color:#fff;

    min-height:125px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.08);

}


.stat-icon{

    width:45px;

    height:45px;

    border-radius:12px;

    background:rgba(255,255,255,.18);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

    margin-bottom:12px;

}


.stat-title{

    font-size:13px;

    opacity:.9;

}


.stat-value{

    font-size:25px;

    font-weight:bold;

    margin-top:5px;

}


/* =========================
   Main Card
========================= */

.main-card{

    background:
        <?= $dark ? '#1f1f1f' : '#fff' ?>;

    border:none;

    border-radius:17px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.07);

}


/* =========================
   Filters
========================= */

.filter-card{

    background:
        <?= $dark ? '#1f1f1f' : '#fff' ?>;

    border:none;

    border-radius:16px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.06);

}


.form-control,
.form-select{

    border-radius:9px;

    min-height:42px;

}


.dark-input{

    background:#2a2a2a;

    color:#fff;

    border-color:#555;

}


.dark-input:focus{

    background:#2a2a2a;

    color:#fff;

}


/* =========================
   Table
========================= */

.table{

    margin-bottom:0;

}


.table th{

    white-space:nowrap;

    font-size:13px;

    padding:13px 10px;

}


.table td{

    padding:12px 10px;

    font-size:13px;

}


.table tbody tr{

    transition:.15s;

}


.table tbody tr:hover{

    background:
        <?= $dark ? '#292929' : '#f8fafc' ?>;

}


.plate{

    display:inline-block;

    padding:6px 10px;

    border-radius:7px;

    background:
        <?= $dark ? '#333' : '#eef1f4' ?>;

    font-weight:bold;

}


.cost{

    color:#198754;

    font-weight:bold;

}


.days-good{

    color:#198754;

    font-weight:bold;

}


.days-soon{

    color:#d68910;

    font-weight:bold;

}


.days-late{

    color:#dc3545;

    font-weight:bold;

}


/* =========================
   Buttons
========================= */

.btn{

    border-radius:8px;

}


.action-btn{

    width:34px;

    height:34px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:0;

}


/* =========================
   Empty
========================= */

.empty-state{

    padding:55px 20px;

    text-align:center;

    color:#999;

}


.empty-state i{

    font-size:45px;

    display:block;

    margin-bottom:12px;

}


/* =========================
   Responsive
========================= */

@media(max-width:900px){

    .page-header{

        flex-direction:column;

        align-items:flex-start;

    }


    .header-actions{

        width:100%;

    }


}


@media(max-width:700px){

    .stat-card{

        min-height:110px;

    }


    .table-responsive{

        overflow-x:auto;

    }

}


/* =========================
   Print
========================= */

@media print{

    body{

        background:#fff !important;

        color:#000 !important;

    }


    .no-print{

        display:none !important;

    }


    .main-card{

        box-shadow:none;

        border:1px solid #ddd;

    }


    .table{

        color:#000 !important;

    }

}

</style>

</head>


<body>


<div class="container-fluid mt-4">


<!-- =========================
     Header
========================= -->

<div class="page-header">


    <div class="page-title">

        <h2>

            💧
            <?= htmlspecialchars($t['title']) ?>

        </h2>

        <p>

            <?= htmlspecialchars($t['subtitle']) ?>

        </p>

    </div>


    <div class="header-actions no-print">


      <a href="add_oil.php" class="btn btn-success">
    <i class="bi bi-plus-circle"></i>
    <?= $t['add'] ?>
</a>


        <a href="oil_excel.php?<?= http_build_query($_GET) ?>"
   class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i>
    Excel
</a>


        <a
            href="<?= htmlspecialchars($printUrl) ?>"
            target="_blank"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-printer"></i>

            <?= $t['print'] ?>

        </a>


        <a
            href="?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"
            class="btn btn-outline-secondary"
        >

            <?= $lang === 'ar' ? 'EN' : 'AR' ?>

        </a>


        <?php if ($dark): ?>

            <a
                href="?theme=0"
                class="btn btn-light"
            >

                ☀️

            </a>

        <?php else: ?>

            <a
                href="?theme=1"
                class="btn btn-dark"
            >

                🌙

            </a>

        <?php endif; ?>


    </div>

</div>


<!-- =========================
     Statistics
========================= -->

<div class="row g-3 mb-4">


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-primary">

            <div class="stat-icon">

                <i class="bi bi-droplet"></i>

            </div>

            <div class="stat-title">

                <?= $t['total_records'] ?>

            </div>

            <div class="stat-value">

                <?= number_format($totalRecords) ?>

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


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-info">

            <div class="stat-icon">

                <i class="bi bi-car-front"></i>

            </div>

            <div class="stat-title">

                <?= $t['total_cars'] ?>

            </div>

            <div class="stat-value">

                <?= number_format($totalCars) ?>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="stat-card bg-dark">

            <div class="stat-icon">

                <i class="bi bi-person-badge"></i>

            </div>

            <div class="stat-title">

                <?= $t['total_drivers'] ?>

            </div>

            <div class="stat-value">

                <?= number_format($totalDrivers) ?>

            </div>

        </div>

    </div>


</div>


<!-- =========================
     Status Summary
========================= -->

<div class="row g-3 mb-4">


    <div class="col-md-4">

        <div class="alert alert-success mb-0">

            <strong>
                <?= $t['good'] ?>
            </strong>

            :

            <?= number_format($good) ?>

        </div>

    </div>


    <div class="col-md-4">

        <div class="alert alert-warning mb-0">

            <strong>
                <?= $t['soon'] ?>
            </strong>

            :

            <?= number_format($soon) ?>

        </div>

    </div>


    <div class="col-md-4">

        <div class="alert alert-danger mb-0">

            <strong>
                <?= $t['late'] ?>
            </strong>

            :

            <?= number_format($late) ?>

        </div>

    </div>


</div>


<!-- =========================
     Filters
========================= -->

<div class="filter-card p-3 mb-4 no-print">


<form
    method="GET"
>


<input
    type="hidden"
    name="lang"
    value="<?= htmlspecialchars($lang) ?>"
>


<div class="row g-2">


    <!-- Search -->

    <div class="col-lg-3 col-md-6">

        <label class="form-label">

            <?= $t['search'] ?>

        </label>

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            class="form-control <?= $dark ? 'dark-input' : '' ?>"
            placeholder="<?= htmlspecialchars($t['search']) ?>"
        >

    </div>


    <!-- Vehicle -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['car'] ?>

        </label>

        <select
            name="car_id"
            class="form-select <?= $dark ? 'dark-input' : '' ?>"
        >

            <option value="0">

                <?= $t['all_cars'] ?>

            </option>


            <?php if ($cars): ?>

                <?php while ($car = $cars->fetch_assoc()): ?>

                    <option
                        value="<?= (int)$car['id'] ?>"
                        <?= $car_id == $car['id'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($car['plate']) ?>

                    </option>

                <?php endwhile; ?>

            <?php endif; ?>

        </select>

    </div>


    <!-- Driver -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['driver'] ?>

        </label>

        <select
            name="driver_id"
            class="form-select <?= $dark ? 'dark-input' : '' ?>"
        >

            <option value="0">

                <?= $t['all_drivers'] ?>

            </option>


            <?php if ($drivers): ?>

                <?php while ($driverRow = $drivers->fetch_assoc()): ?>

                    <option
                        value="<?= (int)$driverRow['id'] ?>"
                        <?= $driver_id == $driverRow['id'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($driverRow['name']) ?>

                    </option>

                <?php endwhile; ?>

            <?php endif; ?>

        </select>

    </div>


    <!-- From -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['from'] ?>

        </label>

        <input
            type="date"
            name="from"
            value="<?= htmlspecialchars($from) ?>"
            class="form-control <?= $dark ? 'dark-input' : '' ?>"
        >

    </div>


    <!-- To -->

    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['to'] ?>

        </label>

        <input
            type="date"
            name="to"
            value="<?= htmlspecialchars($to) ?>"
            class="form-control <?= $dark ? 'dark-input' : '' ?>"
        >

    </div>


    <!-- Buttons -->

    <div class="col-lg-1 col-md-6 d-flex align-items-end gap-1">

        <button
            type="submit"
            class="btn btn-primary w-100"
            title="<?= $t['filter'] ?>"
        >

            <i class="bi bi-search"></i>

        </button>

    </div>


</div>


<div class="mt-3">

    <a
    href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>?lang=<?= urlencode($lang) ?>&theme=<?= $dark ? 1 : 0 ?>"
    class="btn btn-outline-secondary btn-sm"
>
    <i class="bi bi-arrow-counterclockwise"></i>
    <?= $t['reset'] ?>
</a>

</div>


</form>

</div>


<!-- =========================
     Table
========================= -->

<div class="main-card p-3">


<div class="table-responsive">


<table
    class="table table-bordered table-hover text-center align-middle"
>


<thead class="table-dark">

<tr>

    <th>
        <?= $t['id'] ?>
    </th>

    <th>
        <?= $t['car'] ?>
    </th>

    <th>
        <?= $t['driver'] ?>
    </th>

    <th>
        <?= $t['oil_type'] ?>
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
        <?= $t['cost'] ?>
    </th>

    <th>
        <?= $t['remaining'] ?>
    </th>

    <th>
        <?= $t['status'] ?>
    </th>

    <th class="no-print">
        <?= $t['actions'] ?>
    </th>

</tr>

</thead>


<tbody>


<?php if (empty($rows)): ?>

<tr>

    <td
        colspan="12"
    >

        <div class="empty-state">

            <i class="bi bi-droplet-half"></i>

            <?= $t['no_data'] ?>

        </div>

    </td>

</tr>


<?php else: ?>


<?php foreach ($rows as $row): ?>


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
                $row['vehicle_plate'] ?? '-'
            ) ?>

        </span>

    </td>


    <!-- Driver -->

    <td>

        <?= htmlspecialchars(
    $row['display_driver'] ?? '-'
) ?>

    </td>


    <!-- Oil -->

    <td>

        <?= htmlspecialchars(
            $row['oil_type'] ?? '-'
        ) ?>

    </td>


    <!-- Change Date -->

    <td>

        <?= htmlspecialchars(
            $row['change_date'] ?? '-'
        ) ?>

    </td>


    <!-- Next Date -->

    <td>

        <?= htmlspecialchars(
            $row['next_change'] ?? '-'
        ) ?>

    </td>


    <!-- Current KM -->

    <td>

        <?= number_format(
            (int)($row['current_km'] ?? 0)
        ) ?>

        KM

    </td>


    <!-- Next KM -->

    <td>

        <?= number_format(
            (int)($row['next_km'] ?? 0)
        ) ?>

        KM

    </td>


    <!-- Cost -->

    <td class="cost">

        <?= number_format(
            (float)($row['cost'] ?? 0),
            2
        ) ?>

        <?= $t['sar'] ?>

    </td>


    <!-- Remaining -->

    <td>

        <?php if ($row['days_left'] < 0): ?>

            <span class="days-late">

                <?= $t['expired'] ?>

            </span>

        <?php elseif ($row['days_left'] <= 30): ?>

            <span class="days-soon">

                <?= number_format($row['days_left']) ?>

                <?= $t['day'] ?>

            </span>

        <?php else: ?>

            <span class="days-good">

                <?= number_format($row['days_left']) ?>

                <?= $t['day'] ?>

            </span>

        <?php endif; ?>

    </td>


    <!-- Status -->

    <td>

        <span
            class="badge bg-<?= htmlspecialchars($row['badge']) ?>"
        >

            <?= htmlspecialchars($row['status_text']) ?>

        </span>

    </td>


    <!-- Actions -->

    <td class="no-print">


        <a
            href="oil_details.php?id=<?= (int)$row['id'] ?>"
            class="btn btn-info btn-sm action-btn"
            title="<?= $t['view'] ?>"
        >

            <i class="bi bi-eye"></i>

        </a>


        <a
            href="edit_oil.php?id=<?= (int)$row['id'] ?>"
            class="btn btn-warning btn-sm action-btn"
            title="<?= $t['edit'] ?>"
        >

            <i class="bi bi-pencil"></i>

        </a>


        <a
            href="oil_delete.php?id=<?= (int)$row['id'] ?>"
            class="btn btn-danger btn-sm action-btn"
            title="<?= $t['delete'] ?>"
            onclick="return confirm('<?= htmlspecialchars($t['confirm_delete'], ENT_QUOTES) ?>');"
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


<!-- =========================
     Footer
========================= -->

<?php if (!empty($rows)): ?>

<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">


    <div class="text-muted">

        <?= $t['total_records'] ?>:

        <strong>

            <?= number_format($totalRecords) ?>

        </strong>

    </div>


    <div>

        <span class="badge bg-success">

            <?= $t['good'] ?>:
            <?= $good ?>

        </span>


        <span class="badge bg-warning text-dark">

            <?= $t['soon'] ?>:
            <?= $soon ?>

        </span>


        <span class="badge bg-danger">

            <?= $t['late'] ?>:
            <?= $late ?>

        </span>

    </div>


</div>

<?php endif; ?>


</div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>