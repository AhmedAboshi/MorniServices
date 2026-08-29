<?php

session_start();
include('../include/connected.php');

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';
$lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';


/* =========================
   الترجمة
========================= */

$trans = [

    'ar' => [

        'title' => 'سجل صيانة المركبات',
        'subtitle' => 'متابعة وإدارة عمليات صيانة المركبات',

        'workshop' => 'اسم الورشة',
        'plate' => 'رقم اللوحة',
        'provider' => 'المزود',
        'type' => 'نوع الصيانة',
        'cost' => 'التكلفة',
        'notes' => 'الملاحظات',
        'date' => 'تاريخ الصيانة',
        'action' => 'الإجراء',

        'delete' => 'حذف',
        'confirm' => 'هل تريد حذف سجل الصيانة؟',

        'total' => 'إجمالي تكلفة الصيانة',

        'total_records' => 'إجمالي السجلات',
        'this_month' => 'صيانة هذا الشهر',
        'total_cost' => 'إجمالي التكلفة',
        'vehicles' => 'المركبات التي تمت صيانتها',

        'sar' => 'ريال',

        'no_records' => 'لا توجد سجلات صيانة حالياً',

        'today' => 'اليوم',

        'search' => 'بحث',
'search_placeholder' => 'ابحث عن المركبة أو اللوحة أو السائق...',
'filter_type' => 'نوع الصيانة',
'all_types' => 'كل أنواع الصيانة',
'date_from' => 'من تاريخ',
'date_to' => 'إلى تاريخ',
'filter' => 'بحث وتصفية',
'reset' => 'إعادة ضبط',

    ],

    'en' => [

        'title' => 'Vehicle Maintenance Log',
        'subtitle' => 'Track and manage vehicle maintenance records',

        'workshop' => 'Workshop',
        'plate' => 'Plate Number',
        'provider' => 'Provider',
        'type' => 'Maintenance Type',
        'cost' => 'Cost',
        'notes' => 'Notes',
        'date' => 'Maintenance Date',
        'action' => 'Action',

        'delete' => 'Delete',
        'confirm' => 'Are you sure you want to delete this maintenance record?',

        'total' => 'Total Maintenance Cost',

        'total_records' => 'Total Records',
        'this_month' => 'This Month',
        'total_cost' => 'Total Cost',
        'vehicles' => 'Maintained Vehicles',

        'sar' => 'SAR',

        'no_records' => 'No maintenance records found',
        
        'search' => 'Search',
'search_placeholder' => 'Search vehicle, plate or driver...',
'filter_type' => 'Maintenance Type',
'all_types' => 'All Maintenance Types',
'date_from' => 'From Date',
'date_to' => 'To Date',
'filter' => 'Filter',
'reset' => 'Reset',

        'today' => 'Today',

    ]

];


function t($key)
{
    global $trans, $lang;

    return $trans[$lang][$key] ?? $key;
}


/* =========================
   حذف سجل
========================= */

if (isset($_POST['delete_id'])) {

    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("
        DELETE FROM maintenance
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $stmt->close();

    header("Location: maintenanceview.php?lang=" . urlencode($lang));
    exit;
}


/* =========================
   الإحصائيات
========================= */

/* إجمالي السجلات */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM maintenance
");

$stmt->execute();

$total_records = (int) $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


/* صيانة هذا الشهر */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM maintenance
    WHERE maintenance_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    AND maintenance_date < DATE_ADD(
        DATE_FORMAT(CURDATE(), '%Y-%m-01'),
        INTERVAL 1 MONTH
    )
");

$stmt->execute();

$this_month = (int) $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


/* إجمالي التكلفة */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(cost),0) AS total
    FROM maintenance
");

$stmt->execute();

$total_cost = (float) $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


/* عدد المركبات */

$stmt = $con->prepare("
    SELECT COUNT(DISTINCT plate_number) AS total
    FROM maintenance
    WHERE plate_number IS NOT NULL
    AND plate_number != ''
");

$stmt->execute();

$total_vehicles = (int) $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();


/* =========================
   فلاتر البحث
========================= */

$search = trim($_GET['search'] ?? '');
$type_filter = trim($_GET['maintenance_type'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');


/* =========================
   بناء الاستعلام
========================= */

$sql = "
    SELECT
        maintenance.id,
        maintenance.vehicle_name,
        maintenance.plate_number,
        maintenance.driver_id,
        drivers.name AS driver_name,
        maintenance.maintenance_type,
        maintenance.cost,
        maintenance.notes,
        maintenance.maintenance_date,
        maintenance.created_at

    FROM maintenance

    LEFT JOIN drivers
        ON maintenance.driver_id = drivers.id

    WHERE 1=1
";

$params = [];
$types = "";


/* البحث */

if ($search !== '') {

    $sql .= "
    AND (
        maintenance.vehicle_name LIKE ?
        OR maintenance.plate_number LIKE ?
        OR drivers.name LIKE ?
        OR maintenance.maintenance_type LIKE ?
        OR maintenance.notes LIKE ?
    )
";

    $search_value = "%{$search}%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}


/* نوع الصيانة */

if ($type_filter !== '') {

    $sql .= "
        AND maintenance_type = ?
    ";

    $params[] = $type_filter;

    $types .= "s";
}


/* من تاريخ */

if ($date_from !== '') {

    $sql .= "
        AND maintenance_date >= ?
    ";

    $params[] = $date_from;

    $types .= "s";
}


/* إلى تاريخ */

if ($date_to !== '') {

    $sql .= "
        AND maintenance_date <= ?
    ";

    $params[] = $date_to;

    $types .= "s";
}


$sql .= "
    ORDER BY maintenance_date DESC, id DESC
";


$stmt = $con->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$result = $stmt->get_result();


/* =========================
   أنواع الصيانة
========================= */

$type_result = $con->query("
    SELECT DISTINCT maintenance_type
    FROM maintenance
    WHERE maintenance_type IS NOT NULL
    AND maintenance_type != ''
    ORDER BY maintenance_type ASC
");

?>


<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars(t('title')) ?></title>


<style>

/* =========================
   General
========================= */

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f4f6f9;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

    color:#1f2937;

}

.container{

    width:95%;

    max-width:1500px;

    margin:30px auto;

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

    color:#1f2937;

}

.page-title p{

    margin:7px 0 0;

    color:#7b8491;

    font-size:14px;

}


/* =========================
   Language
========================= */

.lang-switch{

    display:flex;

    align-items:center;

    gap:7px;

}

.lang-switch a{

    text-decoration:none;

    padding:8px 14px;

    border-radius:20px;

    background:#e9ecef;

    color:#555;

    font-size:13px;

    transition:.2s;

}

.lang-switch a:hover{

    background:#28a745;

    color:#fff;

}

.lang-switch .active{

    background:#28a745;

    color:#fff;

    font-weight:bold;

}


/* =========================
   Statistics
========================= */

.stats-grid{

    display:grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:18px;

    margin-bottom:25px;

}


.stat-card{

    background:#fff;

    border-radius:16px;

    padding:20px;

    box-shadow:
        0 4px 18px rgba(0,0,0,.06);

    border:1px solid #edf0f2;

    position:relative;

    overflow:hidden;

}


.stat-card::before{

    content:"";

    position:absolute;

    top:0;

    right:0;

    width:5px;

    height:100%;

    background:#28a745;

}


.stat-card:nth-child(2)::before{

    background:#3498db;

}

.stat-card:nth-child(3)::before{

    background:#f39c12;

}

.stat-card:nth-child(4)::before{

    background:#8e44ad;

}


.stat-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:14px;

}


.stat-icon{

    width:46px;

    height:46px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:23px;

    background:#eef8f1;

}


.stat-card:nth-child(2) .stat-icon{

    background:#edf6fd;

}

.stat-card:nth-child(3) .stat-icon{

    background:#fff6e8;

}

.stat-card:nth-child(4) .stat-icon{

    background:#f5edfa;

}


.stat-title{

    color:#777;

    font-size:14px;

}


.stat-value{

    font-size:27px;

    font-weight:bold;

    color:#222;

}


.stat-footer{

    margin-top:8px;

    font-size:12px;

    color:#999;

}


/* =========================
   Table Card
========================= */

.table-card{

    background:#fff;

    border-radius:16px;

    box-shadow:
        0 4px 18px rgba(0,0,0,.06);

    border:1px solid #edf0f2;

    overflow:hidden;

}


.table-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:18px 20px;

    border-bottom:1px solid #eee;

}

.table-header h3{

    margin:0;

    font-size:18px;

    color:#333;

}


.table-responsive{

    width:100%;

    overflow-x:auto;

}


table{

    width:100%;

    border-collapse:collapse;

    min-width:1050px;

}


thead th{

    background:#28a745;

    color:#fff;

    padding:14px 10px;

    font-size:13px;

    font-weight:bold;

    white-space:nowrap;

}


tbody td{

    padding:13px 10px;

    border-bottom:1px solid #eee;

    text-align:center;

    font-size:13px;

    color:#444;

}


tbody tr{

    transition:.15s;

}


tbody tr:hover{

    background:#f8fbf9;

}


tbody tr:last-child td{

    border-bottom:none;

}


/* =========================
   ID
========================= */

.record-id{

    display:inline-flex;

    width:32px;

    height:32px;

    align-items:center;

    justify-content:center;

    background:#f0f2f4;

    border-radius:8px;

    font-weight:bold;

    color:#555;

}


/* =========================
   Vehicle
========================= */

.vehicle-name{

    font-weight:bold;

    color:#333;

}


.plate{

    display:inline-block;

    padding:5px 9px;

    border-radius:6px;

    background:#f1f3f5;

    font-weight:bold;

    color:#333;

}


/* =========================
   Maintenance Type
========================= */

.maintenance-type{

    display:inline-block;

    padding:6px 10px;

    border-radius:20px;

    background:#eaf7ee;

    color:#218838;

    font-size:12px;

    font-weight:bold;

}


/* =========================
   Cost
========================= */

.cost{

    font-weight:bold;

    color:#198754;

    white-space:nowrap;

}


/* =========================
   Notes
========================= */

.notes{

    max-width:220px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

}


/* =========================
   Date
========================= */

.date{

    white-space:nowrap;

    color:#555;

}


/* =========================
   Delete
========================= */

.delete-btn{

    background:#fff0f0;

    color:#dc3545;

    border:1px solid #ffd2d2;

    padding:7px 12px;

    border-radius:8px;

    cursor:pointer;

    font-size:12px;

    transition:.2s;

}


.delete-btn:hover{

    background:#dc3545;

    color:#fff;

}


/* =========================
   Empty
========================= */

.empty{

    padding:45px 20px;

    text-align:center;

    color:#999;

}


.empty-icon{

    font-size:45px;

    margin-bottom:10px;

}


/* =========================
   Total
========================= */

.total-box{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-top:18px;

    padding:18px 22px;

    background:#fff;

    border-radius:14px;

    box-shadow:
        0 3px 12px rgba(0,0,0,.05);

}


.total-label{

    color:#666;

    font-size:15px;

}


.total-value{

    font-size:22px;

    font-weight:bold;

    color:#198754;

}


/* =========================
   Responsive
========================= */

@media(max-width:1100px){

    .stats-grid{

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:700px){

    .container{

        width:94%;

    }

    .page-header{

        flex-direction:column;

        align-items:flex-start;

    }

    .stats-grid{

        grid-template-columns:1fr;

    }

    .table-header{

        align-items:flex-start;

    }

    .total-box{

        flex-direction:column;

        align-items:flex-start;

        gap:8px;

    }

}

.header-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.add-btn{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 16px;
    background:#28a745;
    color:#fff;
    text-decoration:none;
    border-radius:9px;
    font-size:14px;
    font-weight:bold;
    transition:.2s;
    box-shadow:0 3px 8px rgba(40,167,69,.18);
}

.add-btn:hover{
    background:#218838;
    color:#fff;
    transform:translateY(-1px);
}

/* =========================
   Filters
========================= */

.filters-card{

    background:#fff;

    border-radius:16px;

    padding:20px;

    margin-bottom:20px;

    border:1px solid #edf0f2;

    box-shadow:
        0 4px 18px rgba(0,0,0,.05);

}


.filters-card form{

    display:grid;

    grid-template-columns:
        2fr
        1.3fr
        1fr
        1fr
        auto;

    gap:14px;

    align-items:end;

}


.filter-group{

    display:flex;

    flex-direction:column;

    gap:7px;

}


.filter-group label{

    font-size:13px;

    font-weight:bold;

    color:#555;

}


.filter-group input,
.filter-group select{

    width:100%;

    height:42px;

    padding:0 12px;

    border:1px solid #ddd;

    border-radius:9px;

    background:#fff;

    color:#333;

    outline:none;

    font-family:inherit;

}


.filter-group input:focus,
.filter-group select:focus{

    border-color:#28a745;

    box-shadow:
        0 0 0 3px rgba(40,167,69,.08);

}


.filter-buttons{

    display:flex;

    gap:8px;

}


.filter-btn,
.reset-btn{

    height:42px;

    padding:0 15px;

    border-radius:9px;

    border:none;

    text-decoration:none;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    white-space:nowrap;

    font-family:inherit;

    font-size:13px;

    font-weight:bold;

    cursor:pointer;

}


.filter-btn{

    background:#28a745;

    color:#fff;

}


.filter-btn:hover{

    background:#218838;

}


.reset-btn{

    background:#f1f3f5;

    color:#555;

}


.reset-btn:hover{

    background:#e2e6ea;

}


@media(max-width:1100px){

    .filters-card form{

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width:650px){

    .filters-card form{

        grid-template-columns:1fr;

    }

    .filter-buttons{

        width:100%;

    }

    .filter-btn,
    .reset-btn{

        flex:1;

    }

}

/* =========================
   Action Buttons
========================= */

.action-buttons{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:7px;

}


.action-btn{

    width:36px;

    height:36px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    border-radius:8px;

    border:1px solid transparent;

    text-decoration:none;

    cursor:pointer;

    font-size:15px;

    transition:.2s;

}


.view-btn{

    background:#eef7ff;

    border-color:#cfe7ff;

}


.view-btn:hover{

    background:#3498db;

    border-color:#3498db;

    transform:translateY(-1px);

}


.edit-btn{

    background:#fff7e8;

    border-color:#ffe2a8;

}


.edit-btn:hover{

    background:#f39c12;

    border-color:#f39c12;

    transform:translateY(-1px);

}


.delete-btn{

    background:#fff0f0;

    border:1px solid #ffd2d2;

    color:#dc3545;

    padding:0;

}


.delete-btn:hover{

    background:#dc3545;

    border-color:#dc3545;

    color:#fff;

    transform:translateY(-1px);

}


.delete-form{

    margin:0;

    padding:0;

}

.driver-name{
    display:inline-block;
    padding:6px 10px;
    border-radius:8px;
    background:#f3f7f4;
    color:#333;
    font-weight:bold;
}

/* =========================
   Export Buttons
========================= */

.export-buttons{
    display:flex;
    align-items:center;
    gap:8px;
}

.export-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    padding:9px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
    transition:.2s;
}

.excel-btn{
    background:#eaf7ee;
    color:#198754;
    border:1px solid #c9ead5;
}

.excel-btn:hover{
    background:#198754;
    color:#fff;
}

.pdf-btn{
    background:#fff0f0;
    color:#dc3545;
    border:1px solid #ffd2d2;
}

.pdf-btn:hover{
    background:#dc3545;
    color:#fff;
}

@media(max-width:650px){

    .table-header{
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }

    .export-buttons{
        width:100%;
    }

    .export-btn{
        flex:1;
    }

}
</style>

</head>


<body>


<div class="container">


<!-- =========================
     Header
========================= -->

<div class="page-header">

    <div class="page-title">

        <h2>
            🔧 <?= htmlspecialchars(t('title')) ?>
        </h2>

        <p>
            <?= htmlspecialchars(t('subtitle')) ?>
        </p>

    </div>


    <div class="header-actions">

        <!-- إضافة سجل صيانة -->
        <a
            href="maintenance.php?lang=<?= urlencode($lang) ?>"
            class="add-btn"
        >
            ➕
            <?= $lang === 'ar' ? 'إضافة سجل صيانة' : 'Add Maintenance Record' ?>
        </a>


        <!-- اللغة -->
        <div class="lang-switch">

            <a
                href="?lang=ar"
                class="<?= $lang === 'ar' ? 'active' : '' ?>"
            >
                🇸🇦 عربي
            </a>

            <a
                href="?lang=en"
                class="<?= $lang === 'en' ? 'active' : '' ?>"
            >
                🇬🇧 English
            </a>

        </div>

    </div>

</div>


<!-- =========================
     Statistics
========================= -->

<div class="stats-grid">


    <!-- Total Records -->

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-title">
                <?= t('total_records') ?>
            </div>

            <div class="stat-icon">
                🔧
            </div>

        </div>

        <div class="stat-value">

            <?= number_format($total_records) ?>

        </div>

        <div class="stat-footer">
            <?= t('title') ?>
        </div>

    </div>


    <!-- This Month -->

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-title">
                <?= t('this_month') ?>
            </div>

            <div class="stat-icon">
                📅
            </div>

        </div>

        <div class="stat-value">

            <?= number_format($this_month) ?>

        </div>

        <div class="stat-footer">
            <?= t('today') ?>
        </div>

    </div>


    <!-- Total Cost -->

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-title">
                <?= t('total_cost') ?>
            </div>

            <div class="stat-icon">
                💰
            </div>

        </div>

        <div class="stat-value">

            <?= number_format($total_cost, 2) ?>

            <small style="font-size:13px;">
                <?= t('sar') ?>
            </small>

        </div>

        <div class="stat-footer">
            <?= t('total') ?>
        </div>

    </div>


    <!-- Vehicles -->

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-title">
                <?= t('vehicles') ?>
            </div>

            <div class="stat-icon">
                🚗
            </div>

        </div>

        <div class="stat-value">

            <?= number_format($total_vehicles) ?>

        </div>

        <div class="stat-footer">
            <?= t('title') ?>
        </div>

    </div>


</div>

<!-- =========================
     Filters
========================= -->

<div class="filters-card">

    <form method="get">

        <input
            type="hidden"
            name="lang"
            value="<?= htmlspecialchars($lang) ?>"
        >


        <!-- البحث -->

        <div class="filter-group search-group">

            <label>
                🔍 <?= t('search') ?>
            </label>

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="<?= t('search_placeholder') ?>"
            >

        </div>


        <!-- نوع الصيانة -->

        <div class="filter-group">

            <label>
                🔧 <?= t('filter_type') ?>
            </label>

            <select name="maintenance_type">

                <option value="">
                    <?= t('all_types') ?>
                </option>


                <?php while($type_row = $type_result->fetch_assoc()): ?>

                    <option
                        value="<?= htmlspecialchars($type_row['maintenance_type']) ?>"
                        <?= $type_filter === $type_row['maintenance_type'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($type_row['maintenance_type']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- من تاريخ -->

        <div class="filter-group">

            <label>
                📅 <?= t('date_from') ?>
            </label>

            <input
                type="date"
                name="date_from"
                value="<?= htmlspecialchars($date_from) ?>"
            >

        </div>


        <!-- إلى تاريخ -->

        <div class="filter-group">

            <label>
                📅 <?= t('date_to') ?>
            </label>

            <input
                type="date"
                name="date_to"
                value="<?= htmlspecialchars($date_to) ?>"
            >

        </div>


        <!-- الأزرار -->

        <div class="filter-buttons">

            <button
                type="submit"
                class="filter-btn"
            >
                🔍 <?= t('filter') ?>
            </button>


            <a
                href="maintenanceview.php?lang=<?= urlencode($lang) ?>"
                class="reset-btn"
            >
                🔄 <?= t('reset') ?>
            </a>

        </div>

    </form>

</div>

<!-- =========================
     Table
========================= -->

<div class="table-card">


    <div class="table-header">

    <h3>
        📋 <?= t('title') ?>
    </h3>

    <div class="export-buttons">

        <!-- Excel -->
        <a
            href="maintenance_excel.php?lang=<?= urlencode($lang) ?>&search=<?= urlencode($search) ?>&maintenance_type=<?= urlencode($type_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
            class="export-btn excel-btn"
        >
            📊
            <?= $lang === 'ar' ? 'Excel' : 'Excel' ?>
        </a>


        <!-- PDF -->
        <a
            href="maintenance_pdf.php?lang=<?= urlencode($lang) ?>&search=<?= urlencode($search) ?>&maintenance_type=<?= urlencode($type_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
            class="export-btn pdf-btn"
        >
            📄
            <?= $lang === 'ar' ? 'PDF' : 'PDF' ?>
        </a>

    </div>

</div>


    <div class="table-responsive">


        <table>


            <thead>

            <tr>

                <th>#</th>

                <th>
                    <?= t('workshop') ?>
                </th>

                <th>
                    <?= t('plate') ?>
                </th>

                <th>
                    <?= t('provider') ?>
                </th>

                <th>
                    <?= t('type') ?>
                </th>

                <th>
                    <?= t('cost') ?>
                </th>

                <th>
                    <?= t('notes') ?>
                </th>

                <th>
                    <?= t('date') ?>
                </th>

                <th>
                    <?= t('action') ?>
                </th>

            </tr>

            </thead>


            <tbody>


            <?php if($result->num_rows > 0): ?>


                <?php while($row = $result->fetch_assoc()): ?>


                    <tr>


                        <td>

                            <span class="record-id">
                                <?= (int)$row['id'] ?>
                            </span>

                        </td>


                        <td>

                            <div class="vehicle-name">

                                <?= htmlspecialchars(
                                    $row['vehicle_name'] ?? '-'
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <span class="plate">

                                <?= htmlspecialchars(
                                    $row['plate_number'] ?? '-'
                                ) ?>

                            </span>

                        </td>


                        <td>

    <span class="driver-name">

        <?= htmlspecialchars(
            $row['driver_name'] ?? '-'
        ) ?>

    </span>

</td>


                        <td>

                            <span class="maintenance-type">

                                <?= htmlspecialchars(
                                    $row['maintenance_type'] ?? '-'
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span class="cost">

                                <?= number_format(
                                    (float)($row['cost'] ?? 0),
                                    2
                                ) ?>

                                <?= t('sar') ?>

                            </span>

                        </td>


                        <td>

                            <div class="notes"
                                 title="<?= htmlspecialchars($row['notes'] ?? '') ?>">

                                <?= htmlspecialchars(
                                    $row['notes'] ?? '-'
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <span class="date">

                                <?= htmlspecialchars(
                                    $row['maintenance_date'] ?? '-'
                                ) ?>

                            </span>

                        </td>


                        <td>

    <div class="action-buttons">

        <!-- عرض -->
        <a
            href="maintenance_details.php?id=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>"
            class="action-btn view-btn"
            title="<?= $lang === 'ar' ? 'عرض التفاصيل' : 'View Details' ?>"
        >
            👁️
        </a>


        <!-- تعديل -->
        <a
    href="maintenance.php?edit=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>"
    class="action-btn edit-btn"
    title="<?= $lang === 'ar' ? 'تعديل السجل' : 'Edit Record' ?>"
>
    ✏️
</a>


        <!-- حذف -->
        <form
            method="post"
            class="delete-form"
            onsubmit="return confirm('<?= htmlspecialchars(t('confirm'), ENT_QUOTES) ?>')"
        >

            <input
                type="hidden"
                name="delete_id"
                value="<?= (int)$row['id'] ?>"
            >

            <button
                type="submit"
                class="action-btn delete-btn"
                title="<?= $lang === 'ar' ? 'حذف السجل' : 'Delete Record' ?>"
            >
                🗑️
            </button>

        </form>

    </div>

</td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td colspan="9">

                        <div class="empty">

                            <div class="empty-icon">
                                🔧
                            </div>

                            <?= t('no_records') ?>

                        </div>

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


</div>


<!-- =========================
     Total Cost
========================= -->

<div class="total-box">

    <div class="total-label">

        💰 <?= t('total') ?>

    </div>


    <div class="total-value">

        <?= number_format($total_cost, 2) ?>

        <?= t('sar') ?>

    </div>

</div>


</div>


</body>

</html>