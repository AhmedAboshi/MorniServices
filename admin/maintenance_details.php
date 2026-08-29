<?php

session_start();

include('../include/connected.php');


/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

$lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';


/* =========================
   رقم السجل
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($id <= 0) {

    die(
        $lang === 'ar'
        ? 'رقم سجل الصيانة غير صحيح.'
        : 'Invalid maintenance record ID.'
    );

}


/* =========================
   الترجمة
========================= */

$trans = [

    'ar' => [

        'title' => 'تفاصيل سجل الصيانة',
        'subtitle' => 'عرض جميع بيانات سجل صيانة المركبة',

        'record_number' => 'رقم السجل',

        'vehicle' => 'اسم المركبة',
        'plate' => 'رقم اللوحة',
        'driver' => 'السائق / المزود',
        'type' => 'نوع الصيانة',
        'cost' => 'التكلفة',
        'date' => 'تاريخ الصيانة',
        'notes' => 'الملاحظات',
        'created_at' => 'تاريخ إنشاء السجل',

        'back' => 'العودة للسجلات',
        'edit' => 'تعديل السجل',
        'print' => 'طباعة',

        'not_found' => 'سجل الصيانة غير موجود',

        'sar' => 'ريال',

        'no_notes' => 'لا توجد ملاحظات',

        'maintenance_summary' => 'ملخص صيانة المركبة',
'total_records' => 'عدد سجلات الصيانة',
'total_cost' => 'إجمالي تكاليف الصيانة',
'maintenance_history' => 'سجل صيانة نفس المركبة',
'view_record' => 'عرض السجل',
'no_history' => 'لا توجد سجلات صيانة أخرى',

    ],

    'en' => [

        'title' => 'Maintenance Record Details',
        'subtitle' => 'View all details of the vehicle maintenance record',

        'record_number' => 'Record Number',

        'vehicle' => 'Vehicle Name',
        'plate' => 'Plate Number',
        'driver' => 'Driver / Provider',
        'type' => 'Maintenance Type',
        'cost' => 'Cost',
        'date' => 'Maintenance Date',
        'notes' => 'Notes',
        'created_at' => 'Record Created',

        'back' => 'Back to Records',
        'edit' => 'Edit Record',
        'print' => 'Print',

        'not_found' => 'Maintenance record not found',

        'sar' => 'SAR',

        'no_notes' => 'No notes',
        'maintenance_summary' => 'Vehicle Maintenance Summary',
'total_records' => 'Maintenance Records',
'total_cost' => 'Total Maintenance Cost',
'maintenance_history' => 'Maintenance History',
'view_record' => 'View Record',
'no_history' => 'No maintenance records found',

    ]

];


function t($key)
{

    global $trans, $lang;

    return $trans[$lang][$key] ?? $key;

}


/* =========================
   جلب السجل
========================= */

$stmt = $con->prepare("

    SELECT

        id,
        vehicle_name,
        plate_number,
        driver,
        maintenance_type,
        cost,
        notes,
        maintenance_date,
        created_at

    FROM maintenance

    WHERE id = ?

    LIMIT 1

");


$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$maintenance = $result->fetch_assoc();

$stmt->close();


/* =========================
   التحقق
========================= */

if (!$maintenance) {

    die(t('not_found'));

}


/* =========================
   البيانات
========================= */

$vehicle_name =
    trim($maintenance['vehicle_name'] ?? '');

$plate_number =
    trim($maintenance['plate_number'] ?? '');

$driver =
    trim($maintenance['driver'] ?? '');

$maintenance_type =
    trim($maintenance['maintenance_type'] ?? '');

$notes =
    trim($maintenance['notes'] ?? '');

$cost =
    (float)($maintenance['cost'] ?? 0);

$maintenance_date =
    $maintenance['maintenance_date'] ?? '';

$created_at =
    $maintenance['created_at'] ?? '';

    /* =========================
   إحصائيات صيانة المركبة
========================= */

$vehicle_stats = [
    'total_records' => 0,
    'total_cost' => 0
];

$stmt_stats = $con->prepare("
    SELECT
        COUNT(*) AS total_records,
        COALESCE(SUM(cost), 0) AS total_cost
    FROM maintenance
    WHERE plate_number = ?
");

$stmt_stats->bind_param("s", $plate_number);
$stmt_stats->execute();

$stats_result = $stmt_stats->get_result();

if ($stats_row = $stats_result->fetch_assoc()) {

    $vehicle_stats['total_records'] =
        (int)($stats_row['total_records'] ?? 0);

    $vehicle_stats['total_cost'] =
        (float)($stats_row['total_cost'] ?? 0);

}

$stmt_stats->close();


/* =========================
   سجلات صيانة نفس المركبة
========================= */

$vehicle_maintenance = [];

$stmt_history = $con->prepare("
    SELECT
        id,
        maintenance_type,
        cost,
        notes,
        maintenance_date
    FROM maintenance
    WHERE plate_number = ?
    ORDER BY maintenance_date DESC, id DESC
");

$stmt_history->bind_param("s", $plate_number);
$stmt_history->execute();

$history_result = $stmt_history->get_result();

while ($history_row = $history_result->fetch_assoc()) {

    $vehicle_maintenance[] = $history_row;

}

$stmt_history->close();
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
    <?= htmlspecialchars(t('title')) ?>
</title>


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

    width:94%;

    max-width:1100px;

    margin:30px auto;

}


/* =========================
   Header
========================= */

.page-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-bottom:22px;

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


.header-actions{

    display:flex;

    align-items:center;

    gap:9px;

    flex-wrap:wrap;

}


/* =========================
   Language
========================= */

.lang-switch{

    display:flex;

    gap:6px;

}


.lang-switch a{

    text-decoration:none;

    padding:8px 12px;

    border-radius:20px;

    background:#e9ecef;

    color:#555;

    font-size:13px;

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
   Buttons
========================= */

.btn{

    height:40px;

    padding:0 14px;

    border-radius:9px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    text-decoration:none;

    border:1px solid transparent;

    font-size:13px;

    font-weight:bold;

    cursor:pointer;

    transition:.2s;

}


.back-btn{

    background:#fff;

    border-color:#ddd;

    color:#555;

}


.back-btn:hover{

    background:#f1f3f5;

}


.edit-btn{

    background:#f39c12;

    color:#fff;

}


.edit-btn:hover{

    background:#d68910;

}


.print-btn{

    background:#3498db;

    color:#fff;

}


.print-btn:hover{

    background:#217dbb;

}


/* =========================
   Main Card
========================= */

.details-card{

    background:#fff;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
        0 5px 22px rgba(0,0,0,.07);

    border:1px solid #edf0f2;

}


/* =========================
   Card Header
========================= */

.card-header{

    padding:22px 25px;

    background:

        linear-gradient(
            135deg,
            #28a745,
            #218838
        );

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.card-title{

    display:flex;

    align-items:center;

    gap:13px;

}


.card-icon{

    width:52px;

    height:52px;

    border-radius:13px;

    background:rgba(255,255,255,.18);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:26px;

}


.card-title h3{

    margin:0;

    font-size:20px;

}


.card-title p{

    margin:5px 0 0;

    font-size:12px;

    opacity:.85;

}


.record-number{

    background:rgba(255,255,255,.16);

    padding:8px 12px;

    border-radius:9px;

    font-size:13px;

    white-space:nowrap;

}


/* =========================
   Details
========================= */

.details-body{

    padding:25px;

}


.details-grid{

    display:grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:16px;

}


.detail-item{

    border:1px solid #edf0f2;

    border-radius:12px;

    padding:16px;

    background:#fafbfc;

}


.detail-label{

    color:#7a828b;

    font-size:12px;

    margin-bottom:8px;

}


.detail-value{

    color:#222;

    font-size:15px;

    font-weight:bold;

}


.plate-value{

    display:inline-block;

    padding:6px 12px;

    border-radius:7px;

    background:#eef1f3;

    color:#333;

}


.type-value{

    display:inline-block;

    padding:7px 13px;

    border-radius:20px;

    background:#eaf7ee;

    color:#218838;

}


.cost-value{

    color:#198754;

    font-size:20px;

}


.date-value{

    color:#444;

}


/* =========================
   Notes
========================= */

.notes-section{

    margin-top:18px;

    border:1px solid #edf0f2;

    border-radius:12px;

    padding:18px;

    background:#fafbfc;

}


.notes-label{

    color:#7a828b;

    font-size:12px;

    margin-bottom:10px;

}


.notes-content{

    color:#444;

    line-height:1.8;

    font-size:14px;

    white-space:pre-wrap;

}


/* =========================
   Footer
========================= */

.card-footer{

    padding:18px 25px;

    border-top:1px solid #eee;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

}


.created{

    color:#999;

    font-size:12px;

}


.footer-buttons{

    display:flex;

    gap:8px;

}


/* =========================
   Responsive
========================= */

@media(max-width:750px){

    .page-header{

        flex-direction:column;

        align-items:flex-start;
        

    }

.summary-grid{

    grid-template-columns:1fr;

}
    .header-actions{

        width:100%;

    }


    .details-grid{

        grid-template-columns:1fr;

    }


    .card-header{

        align-items:flex-start;

    }


    .card-footer{

        flex-direction:column;

        align-items:flex-start;

    }


    .footer-buttons{

        width:100%;

    }


    .footer-buttons .btn{

        flex:1;

    }

}


/* =========================
   Print
========================= */

@media print{

    body{

        background:#fff;

    }


    .container{

        width:100%;

        margin:0;

        max-width:none;

    }


    .page-header{

        display:none;

    }


    .details-card{

        box-shadow:none;

        border:1px solid #ddd;

    }


    .card-footer{

        display:none;

    }

}

/* =========================
   Maintenance Summary
========================= */

.summary-section{

    margin-top:22px;

}

.summary-title{

    margin-bottom:12px;

    font-size:18px;

    font-weight:bold;

    color:#1f2937;

}


.summary-grid{

    display:grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:16px;

}


.summary-card{

    background:#fff;

    border:1px solid #edf0f2;

    border-radius:14px;

    padding:18px;

    display:flex;

    align-items:center;

    gap:15px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.04);

}


.summary-icon{

    width:48px;

    height:48px;

    border-radius:12px;

    background:#eaf7ee;

    color:#218838;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

}


.summary-label{

    color:#7a828b;

    font-size:12px;

    margin-bottom:5px;

}


.summary-value{

    color:#1f2937;

    font-size:20px;

    font-weight:bold;

}


/* =========================
   Maintenance History
========================= */

.history-section{

    margin-top:22px;

    background:#fff;

    border:1px solid #edf0f2;

    border-radius:16px;

    overflow:hidden;

    box-shadow:
        0 5px 20px rgba(0,0,0,.05);

}


.history-header{

    padding:18px 20px;

    border-bottom:1px solid #eee;

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.history-header h3{

    margin:0;

    font-size:18px;

    color:#1f2937;

}


.history-count{

    background:#eaf7ee;

    color:#218838;

    padding:6px 10px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;

}


.history-table-wrapper{

    overflow-x:auto;

}


.history-table{

    width:100%;

    border-collapse:collapse;

    min-width:700px;

}


.history-table th{

    background:#f8f9fa;

    color:#6c757d;

    font-size:12px;

    padding:13px;

    text-align:center;

    white-space:nowrap;

}


.history-table td{

    padding:13px;

    border-top:1px solid #eee;

    text-align:center;

    font-size:13px;

    color:#333;

}


.history-table tr:hover{

    background:#fafdfb;

}


.history-cost{

    color:#198754;

    font-weight:bold;

}


.history-type{

    display:inline-block;

    padding:5px 10px;

    border-radius:20px;

    background:#eaf7ee;

    color:#218838;

    font-size:12px;

}


.history-notes{

    max-width:220px;

    overflow:hidden;

    text-overflow:ellipsis;

    white-space:nowrap;

}


.history-view{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    padding:7px 11px;

    border-radius:8px;

    background:#3498db;

    color:#fff;

    text-decoration:none;

    font-size:12px;

    font-weight:bold;

}


.history-view:hover{

    background:#217dbb;

}


.no-history{

    padding:30px;

    text-align:center;

    color:#999;

    font-size:14px;

}
</style>

</head>


<body>


<div class="container">


<!-- =========================
     Page Header
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


        <a
            href="maintenanceview.php?lang=<?= urlencode($lang) ?>"
            class="btn back-btn"
        >
            ↩️ <?= t('back') ?>
        </a>


        <a
            href="maintenance_edit.php?id=<?= (int)$id ?>&lang=<?= urlencode($lang) ?>"
            class="btn edit-btn"
        >
            ✏️ <?= t('edit') ?>
        </a>


        <button
            type="button"
            onclick="window.print()"
            class="btn print-btn"
        >
            🖨️ <?= t('print') ?>
        </button>


        <div class="lang-switch">

            <a
                href="?id=<?= (int)$id ?>&lang=ar"
                class="<?= $lang === 'ar' ? 'active' : '' ?>"
            >
                🇸🇦
            </a>

            <a
                href="?id=<?= (int)$id ?>&lang=en"
                class="<?= $lang === 'en' ? 'active' : '' ?>"
            >
                🇬🇧
            </a>

        </div>


    </div>

</div>


<!-- =========================
     Details Card
========================= -->

<div class="details-card">


    <!-- Card Header -->

    <div class="card-header">


        <div class="card-title">

            <div class="card-icon">
                🔧
            </div>


            <div>

                <h3>
                    <?= htmlspecialchars(
                        $vehicle_name ?: '-'
                    ) ?>
                </h3>

                <p>
                    <?= htmlspecialchars(t('type')) ?>:
                    <?= htmlspecialchars(
                        $maintenance_type ?: '-'
                    ) ?>
                </p>

            </div>

        </div>


        <div class="record-number">

            #<?= (int)$maintenance['id'] ?>

        </div>


    </div>


    <!-- Details -->

    <div class="details-body">


        <div class="details-grid">


            <!-- Vehicle -->

            <div class="detail-item">

                <div class="detail-label">
                    🚗 <?= t('vehicle') ?>
                </div>

                <div class="detail-value">

                    <?= htmlspecialchars(
                        $vehicle_name ?: '-'
                    ) ?>

                </div>

            </div>


            <!-- Plate -->

            <div class="detail-item">

                <div class="detail-label">
                    🔢 <?= t('plate') ?>
                </div>

                <div class="detail-value">

                    <span class="plate-value">

                        <?= htmlspecialchars(
                            $plate_number ?: '-'
                        ) ?>

                    </span>

                </div>

            </div>


            <!-- Driver -->

            <div class="detail-item">

                <div class="detail-label">
                    👤 <?= t('driver') ?>
                </div>

                <div class="detail-value">

                    <?= htmlspecialchars(
                        $driver ?: '-'
                    ) ?>

                </div>

            </div>


            <!-- Type -->

            <div class="detail-item">

                <div class="detail-label">
                    🔧 <?= t('type') ?>
                </div>

                <div class="detail-value">

                    <span class="type-value">

                        <?= htmlspecialchars(
                            $maintenance_type ?: '-'
                        ) ?>

                    </span>

                </div>

            </div>


            <!-- Cost -->

            <div class="detail-item">

                <div class="detail-label">
                    💰 <?= t('cost') ?>
                </div>

                <div class="detail-value cost-value">

                    <?= number_format($cost, 2) ?>

                    <?= t('sar') ?>

                </div>

            </div>


            <!-- Date -->

            <div class="detail-item">

                <div class="detail-label">
                    📅 <?= t('date') ?>
                </div>

                <div class="detail-value date-value">

                    <?= htmlspecialchars(
                        $maintenance_date ?: '-'
                    ) ?>

                </div>

            </div>


        </div>


        <!-- Notes -->

        <div class="notes-section">


            <div class="notes-label">

                📝 <?= t('notes') ?>

            </div>


            <div class="notes-content">

                <?= $notes !== ''
                    ? nl2br(htmlspecialchars($notes))
                    : t('no_notes')
                ?>

            </div>


        </div>


    </div>


    <!-- Footer -->

    <div class="card-footer">


        <div class="created">

            🕒 <?= t('created_at') ?>:

            <?= htmlspecialchars(
                $created_at ?: '-'
            ) ?>

        </div>


        <div class="footer-buttons">


            <a
                href="maintenanceview.php?lang=<?= urlencode($lang) ?>"
                class="btn back-btn"
            >
                ↩️ <?= t('back') ?>
            </a>


            <a
                href="maintenance_edit.php?id=<?= (int)$id ?>&lang=<?= urlencode($lang) ?>"
                class="btn edit-btn"
            >
                ✏️ <?= t('edit') ?>
            </a>


        </div>


    </div>


</div>


</div>

<!-- =========================
     Maintenance Summary
========================= -->

<div class="summary-section">

    <div class="summary-title">

        📊 <?= t('maintenance_summary') ?>

    </div>


    <div class="summary-grid">


        <!-- Total Records -->

        <div class="summary-card">

            <div class="summary-icon">
                🔧
            </div>

            <div>

                <div class="summary-label">
                    <?= t('total_records') ?>
                </div>

                <div class="summary-value">

                    <?= number_format(
                        $vehicle_stats['total_records']
                    ) ?>

                </div>

            </div>

        </div>


        <!-- Total Cost -->

        <div class="summary-card">

            <div class="summary-icon">
                💰
            </div>

            <div>

                <div class="summary-label">
                    <?= t('total_cost') ?>
                </div>

                <div class="summary-value">

                    <?= number_format(
                        $vehicle_stats['total_cost'],
                        2
                    ) ?>

                    <?= t('sar') ?>

                </div>

            </div>

        </div>


    </div>

</div>

<!-- =========================
     Maintenance History
========================= -->

<div class="history-section">


    <div class="history-header">

        <h3>
            📋 <?= t('maintenance_history') ?>
        </h3>


        <div class="history-count">

            <?= number_format(
                $vehicle_stats['total_records']
            ) ?>

        </div>

    </div>


    <?php if (!empty($vehicle_maintenance)): ?>


        <div class="history-table-wrapper">

            <table class="history-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>
                            <?= t('date') ?>
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

                        <th></th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $vehicle_maintenance
                    as $history
                ): ?>


                    <tr>


                        <td>

                            #<?= (int)$history['id'] ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $history['maintenance_date']
                                ?: '-'
                            ) ?>

                        </td>


                        <td>

                            <span class="history-type">

                                <?= htmlspecialchars(
                                    $history['maintenance_type']
                                    ?: '-'
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <span class="history-cost">

                                <?= number_format(
                                    (float)(
                                        $history['cost']
                                        ?? 0
                                    ),
                                    2
                                ) ?>

                                <?= t('sar') ?>

                            </span>

                        </td>


                        <td>

                            <div class="history-notes">

                                <?= htmlspecialchars(
                                    trim(
                                        $history['notes']
                                        ?? ''
                                    ) ?: '-'
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <a
                                href="maintenance_details.php?id=<?= (int)$history['id'] ?>&lang=<?= urlencode($lang) ?>"
                                class="history-view"
                            >

                                👁️
                                <?= t('view_record') ?>

                            </a>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php else: ?>


        <div class="no-history">

            <?= t('no_history') ?>

        </div>


    <?php endif; ?>


</div>

</body>

</html>