
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

        'title' => 'تقرير السائقين',

        'subtitle' =>
            'متابعة بيانات السائقين وحالة الإقامة والرخصة وبطاقة السائق',

        'search' => 'بحث...',

        'work_area' => 'منطقة العمل',

        'truck_type' => 'نوع السطحة',

        'all_areas' => 'جميع المناطق',

        'all_types' => 'جميع الأنواع',

        'all_status' => 'جميع الحالات',

        'document_status' => 'حالة الوثائق',

        'all' => 'الكل',

        'valid' => 'الوثائق سارية',

        'expired' => 'وثيقة منتهية',

        'near_expiry' => 'قريبة من الانتهاء',

        'from' => 'من تاريخ',

        'to' => 'إلى تاريخ',

        'filter' => 'تطبيق الفلتر',

        'reset' => 'إعادة ضبط',

        'print' => 'طباعة',

        'excel' => 'Excel',

        'pdf' => 'PDF',

        'total_drivers' => 'إجمالي السائقين',

        'iqama_expired' => 'الإقامات المنتهية',

        'license_expired' => 'الرخص المنتهية',

        'card_expired' => 'بطاقات السائقين المنتهية',

        'multi_expired' => 'أكثر من وثيقة منتهية',

        'name' => 'اسم السائق',

        'id' => 'الهوية',

        'phone' => 'الجوال',

        'type' => 'نوع السطحة',

        'area' => 'منطقة العمل',

        'image' => 'الصورة',

        'iqama_date' => 'انتهاء الإقامة',

        'license_date' => 'انتهاء الرخصة',

        'card_date' => 'انتهاء بطاقة السائق',

        'documents' => 'الوثائق',

        'status' => 'الحالة',

        'iqama' => 'الإقامة',

        'license' => 'الرخصة',

        'card' => 'بطاقة السائق',

        'expired_short' => 'منتهية',

        'valid_short' => 'سارية',

        'multiple' => 'أكثر من وثيقة',

        'no_data' => 'لا توجد سجلات مطابقة للفلاتر',

        'records' => 'سجل',

        'previous' => 'السابق',

        'next' => 'التالي'

    ],

    'en' => [

        'title' => 'Drivers Report',

        'subtitle' =>
            'Monitor driver information and document status',

        'search' => 'Search...',

        'work_area' => 'Work Area',

        'truck_type' => 'Truck Type',

        'all_areas' => 'All Areas',

        'all_types' => 'All Types',

        'all_status' => 'All Statuses',

        'document_status' => 'Document Status',

        'all' => 'All',

        'valid' => 'All Documents Valid',

        'expired' => 'Expired Document',

        'near_expiry' => 'Near Expiry',

        'from' => 'From Date',

        'to' => 'To Date',

        'filter' => 'Apply Filter',

        'reset' => 'Reset',

        'print' => 'Print',

        'excel' => 'Excel',

        'pdf' => 'PDF',

        'total_drivers' => 'Total Drivers',

        'iqama_expired' => 'Expired Iqamas',

        'license_expired' => 'Expired Licenses',

        'card_expired' => 'Expired Driver Cards',

        'multi_expired' => 'Multiple Expired Documents',

        'name' => 'Driver Name',

        'id' => 'National ID',

        'phone' => 'Phone',

        'type' => 'Truck Type',

        'area' => 'Work Area',

        'image' => 'Image',

        'iqama_date' => 'Iqama Expiry',

        'license_date' => 'License Expiry',

        'card_date' => 'Driver Card Expiry',

        'documents' => 'Documents',

        'status' => 'Status',

        'iqama' => 'Iqama',

        'license' => 'License',

        'card' => 'Driver Card',

        'expired_short' => 'Expired',

        'valid_short' => 'Valid',

        'multiple' => 'Multiple Documents',

        'no_data' => 'No drivers match the selected filters',

        'records' => 'records',

        'previous' => 'Previous',

        'next' => 'Next'

    ]

];

$t = $text[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$work_area = trim($_GET['work_area'] ?? '');

$truck_type = trim($_GET['truck_type'] ?? '');

$document_status = trim(
    $_GET['document_status'] ?? ''
);

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
   التاريخ
========================================================= */

$today = date('Y-m-d');

$nearDate = date(
    'Y-m-d',
    strtotime('+30 days')
);

/* =========================================================
   WHERE
========================================================= */

$where = " WHERE 1=1 ";

$params = [];

$types = "";

/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            d.name LIKE ?
            OR d.national_id LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
            OR d.truck_type LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}

/* =========================================================
   منطقة العمل
========================================================= */

if ($work_area !== '') {

    $where .= "
        AND d.work_area LIKE ?
    ";

    $params[] =
        '%' . $work_area . '%';

    $types .= "s";
}

/* =========================================================
   نوع السطحة
========================================================= */

if ($truck_type !== '') {

    $where .= "
        AND d.truck_type LIKE ?
    ";

    $params[] =
        '%' . $truck_type . '%';

    $types .= "s";
}

/* =========================================================
   حالة الوثائق
========================================================= */

if ($document_status === 'expired') {

    $where .= "
        AND (
            (
                d.iqama_expiry_date IS NOT NULL
                AND d.iqama_expiry_date <> ''
                AND d.iqama_expiry_date < ?
            )
            OR
            (
                d.license_expiry_date IS NOT NULL
                AND d.license_expiry_date <> ''
                AND d.license_expiry_date < ?
            )
            OR
            (
                d.driver_card_expiration_date IS NOT NULL
                AND d.driver_card_expiration_date <> ''
                AND d.driver_card_expiration_date < ?
            )
        )
    ";

    $params[] = $today;
    $params[] = $today;
    $params[] = $today;

    $types .= "sss";

} elseif ($document_status === 'valid') {

    $where .= "
        AND (
            d.iqama_expiry_date IS NULL
            OR d.iqama_expiry_date = ''
            OR d.iqama_expiry_date >= ?
        )
        AND (
            d.license_expiry_date IS NULL
            OR d.license_expiry_date = ''
            OR d.license_expiry_date >= ?
        )
        AND (
            d.driver_card_expiration_date IS NULL
            OR d.driver_card_expiration_date = ''
            OR d.driver_card_expiration_date >= ?
        )
    ";

    $params[] = $today;
    $params[] = $today;
    $params[] = $today;

    $types .= "sss";

} elseif ($document_status === 'near') {

    $where .= "
        AND (
            (
                d.iqama_expiry_date IS NOT NULL
                AND d.iqama_expiry_date >= ?
                AND d.iqama_expiry_date <= ?
            )
            OR
            (
                d.license_expiry_date IS NOT NULL
                AND d.license_expiry_date >= ?
                AND d.license_expiry_date <= ?
            )
            OR
            (
                d.driver_card_expiration_date IS NOT NULL
                AND d.driver_card_expiration_date >= ?
                AND d.driver_card_expiration_date <= ?
            )
        )
    ";

    $params[] = $today;
    $params[] = $nearDate;

    $params[] = $today;
    $params[] = $nearDate;

    $params[] = $today;
    $params[] = $nearDate;

    $types .= "ssssss";
}

/* =========================================================
   تاريخ إنشاء السائق
   نستخدم فقط إذا كان created_at موجودًا
========================================================= */

if (
    ($from !== '' || $to !== '')
) {

    /*
     * هذا الشرط يعتمد على وجود created_at.
     * بما أن التقرير خاص بوثائق السائقين، لا نطبق
     * التاريخ على السجلات إذا لم يكن المقصود ذلك.
     */
}

/* =========================================================
   SQL الأساسي
========================================================= */

$baseSql = "

    FROM drivers d

    $where

";

/* =========================================================
   إجمالي السجلات
========================================================= */

$countSql = "
    SELECT COUNT(*) AS total
    $baseSql
";

$countStmt =
    $con->prepare(
        $countSql
    );

if (!$countStmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
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
    (int)(
        $countRow['total']
        ?? 0
    );

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

    $page =
        $totalPages;
}

$offset =
    ($page - 1) *
    $limit;

/* =========================================================
   بيانات السائقين
========================================================= */

$sql = "

    SELECT

        d.id,
        d.name,
        d.national_id,
        d.phone,
        d.work_area,
        d.truck_type,
        d.imagedriver,

        d.iqama_expiry_date,
        d.license_expiry_date,
        d.driver_card_expiration_date

    $baseSql

    ORDER BY
        d.name ASC

    LIMIT ?
    OFFSET ?

";

$dataParams =
    $params;

$dataTypes =
    $types . 'ii';

$dataParams[] =
    $limit;

$dataParams[] =
    $offset;

$stmt =
    $con->prepare(
        $sql
    );

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
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
   الإحصائيات
========================================================= */

$rows = [];

$iqamaExpiredCount = 0;

$licenseExpiredCount = 0;

$cardExpiredCount = 0;

$multiExpiredCount = 0;

while (
    $row =
    $result->fetch_assoc()
) {

    $iqamaExpired =
        !empty(
            $row['iqama_expiry_date']
        )
        &&
        $row['iqama_expiry_date']
        < $today;

    $licenseExpired =
        !empty(
            $row['license_expiry_date']
        )
        &&
        $row['license_expiry_date']
        < $today;

    $cardExpired =
        !empty(
            $row['driver_card_expiration_date']
        )
        &&
        $row['driver_card_expiration_date']
        < $today;

    $expiredCount =
        ($iqamaExpired ? 1 : 0)
        +
        ($licenseExpired ? 1 : 0)
        +
        ($cardExpired ? 1 : 0);

    if ($iqamaExpired) {
        $iqamaExpiredCount++;
    }

    if ($licenseExpired) {
        $licenseExpiredCount++;
    }

    if ($cardExpired) {
        $cardExpiredCount++;
    }

    if ($expiredCount >= 2) {
        $multiExpiredCount++;
    }

    /*
     * الحالة العامة
     */

    if ($expiredCount >= 2) {

        $row['status_text'] =
            $t['multiple'];

        $row['row_class'] =
            'multi-expired';

    } elseif ($iqamaExpired) {

        $row['status_text'] =
            $t['iqama'];

        $row['row_class'] =
            'iqama-expired';

    } elseif ($licenseExpired) {

        $row['status_text'] =
            $t['license'];

        $row['row_class'] =
            'license-expired';

    } elseif ($cardExpired) {

        $row['status_text'] =
            $t['card'];

        $row['row_class'] =
            'card-expired';

    } else {

        $row['status_text'] =
            $t['valid'];

        $row['row_class'] =
            'all-valid';
    }

    $row['iqama_expired'] =
        $iqamaExpired;

    $row['license_expired'] =
        $licenseExpired;

    $row['card_expired'] =
        $cardExpired;

    $row['expired_count'] =
        $expiredCount;

    $rows[] =
        $row;
}

/* =========================================================
   إجمالي السائقين في النظام
========================================================= */

$totalDriversSystem = 0;

$q =
    $con->query("
        SELECT COUNT(*) AS total
        FROM drivers
    ");

if ($q) {

    $totalDriversSystem =
        (int)(
            $q->fetch_assoc()['total']
            ?? 0
        );
}

/* =========================================================
   الروابط
========================================================= */

$currentParams =
    $_GET;

$currentParams['lang'] =
    $lang;

$currentParams['theme'] =
    $dark;

$excelUrl =
    'drivers_report_excel.php?' .
    http_build_query(
        $currentParams
    );

$pdfUrl =
    'drivers_report_pdf.php?' .
    http_build_query(
        $currentParams
    );

$resetUrl =
    'drivers_report.php?' .
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

    margin: 0;

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
        1650px;

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
        120px;

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
        24px;

    font-weight:
        800;
}

.bg-purple {

    background:
        linear-gradient(
            135deg,
            #6f42c1,
            #512da8
        );
}

/* =========================================================
   FILTER
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
   MAIN CARD
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

/* =========================================================
   TABLE
========================================================= */

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
        52px;

    height:
        52px;

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

.driver-empty {

    width:
        52px;

    height:
        52px;

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

/* =========================================================
   الوثائق - بطاقات مستقلة
========================================================= */

.document-box {

    display:
        flex;

    flex-direction:
        column;

    gap:
        4px;

    min-width:
        115px;

    padding:
        8px;

    border-radius:
        9px;

    background:
        <?= $dark
            ? '#172033'
            : '#f8fafc'
        ?>;

    border:
        1px solid
        var(--border);
}

.document-title {

    font-size:
        11px;

    font-weight:
        800;
}

.document-date {

    font-size:
        11px;

    color:
        var(--muted);
}

.document-status {

    display:
        inline-block;

    width:
        fit-content;

    padding:
        3px 7px;

    border-radius:
        6px;

    font-size:
        10px;

    font-weight:
        700;
}

.doc-valid {

    background:
        #d1e7dd;

    color:
        #0f5132;
}

.doc-iqama {

    background:
        #dc3545;

    color:
        #fff;
}

.doc-license {

    background:
        #fd7e14;

    color:
        #fff;
}

.doc-card {

    background:
        #0d6efd;

    color:
        #fff;
}

/* =========================================================
   الحالة العامة
========================================================= */

.status-multi {

    background:
        #6f42c1 !important;

    color:
        #fff !important;
}

.status-valid {

    background:
        #198754 !important;

    color:
        #fff !important;
}

/* =========================================================
   ألوان الصف
========================================================= */

.iqama-expired td {

    background:
        <?= $dark
            ? '#57151d'
            : '#fff5f5'
        ?>;
}

.license-expired td {

    background:
        <?= $dark
            ? '#52200a'
            : '#fff8f0'
        ?>;
}

.card-expired td {

    background:
        <?= $dark
            ? '#112d63'
            : '#f2f7ff'
        ?>;
}

.multi-expired td {

    background:
        <?= $dark
            ? '#392064'
            : '#f7f0ff'
        ?>;
}

.all-valid td {

    background:
        <?= $dark
            ? 'rgba(25,135,84,.05)'
            : '#ffffff'
        ?>;
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

@media(max-width:1350px) {

    .stats-grid {

        grid-template-columns:
            repeat(3, 1fr);
    }
}

@media(max-width:850px) {

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

            <i class="bi bi-person-badge-fill"></i>

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

            <?= $t['excel'] ?>

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
                $totalRecords
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-danger">

        <div class="stat-icon">

            <i class="bi bi-person-vcard"></i>

        </div>

        <div class="stat-title">

            <?= $t['iqama_expired'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $iqamaExpiredCount
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-warning text-dark">

        <div class="stat-icon">

            <i class="bi bi-card-checklist"></i>

        </div>

        <div class="stat-title">

            <?= $t['license_expired'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $licenseExpiredCount
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-info">

        <div class="stat-icon">

            <i class="bi bi-credit-card"></i>

        </div>

        <div class="stat-title">

            <?= $t['card_expired'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $cardExpiredCount
            ) ?>

        </div>

    </div>


    <div class="stat-card bg-purple">

        <div class="stat-icon">

            <i class="bi bi-exclamation-diamond"></i>

        </div>

        <div class="stat-title">

            <?= $t['multi_expired'] ?>

        </div>

        <div class="stat-value">

            <?= number_format(
                $multiExpiredCount
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


    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['work_area'] ?>

        </label>

        <input
            type="text"
            name="work_area"
            value="<?= htmlspecialchars(
                $work_area
            ) ?>"
            class="form-control"
        >

    </div>


    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['truck_type'] ?>

        </label>

        <input
            type="text"
            name="truck_type"
            value="<?= htmlspecialchars(
                $truck_type
            ) ?>"
            class="form-control"
        >

    </div>


    <div class="col-lg-2 col-md-6">

        <label class="form-label">

            <?= $t['document_status'] ?>

        </label>

        <select
            name="document_status"
            class="form-select"
        >

            <option value="">

                <?= $t['all_status'] ?>

            </option>

            <option
                value="expired"
                <?= $document_status === 'expired'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['expired'] ?>

            </option>

            <option
                value="near"
                <?= $document_status === 'near'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['near_expiry'] ?>

            </option>

            <option
                value="valid"
                <?= $document_status === 'valid'
                    ? 'selected'
                    : ''
                ?>
            >

                <?= $t['valid'] ?>

            </option>

        </select>

    </div>


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
        <?= $t['id'] ?>
    </th>

    <th>
        <?= $t['phone'] ?>
    </th>

    <th>
        <?= $t['work_area'] ?>
    </th>

    <th>
        <?= $t['type'] ?>
    </th>

    <th>
        <?= $t['documents'] ?>
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

            <i class="bi bi-people"></i>

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

<tr
    class="<?= htmlspecialchars(
        $row['row_class']
    ) ?>"
>

    <!-- الرقم -->

    <td>

        <?= $serialStart + $index ?>

    </td>


    <!-- الصورة -->

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
                    basename(
                        $imageName
                    )
                ) ?>"
                class="driver-img"
                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='inline-flex';
                "
            >

            <span
                class="driver-empty"
                style="display:none;"
            >
                <i class="bi bi-person"></i>
            </span>

        <?php else: ?>

            <span class="driver-empty">

                <i class="bi bi-person"></i>

            </span>

        <?php endif; ?>

    </td>


    <!-- الاسم -->

    <td>

        <strong>

            <?= htmlspecialchars(
                $row['name']
                ?? '-'
            ) ?>

        </strong>

    </td>


    <!-- الهوية -->

    <td>

        <?= htmlspecialchars(
            $row['national_id']
            ?? '-'
        ) ?>

    </td>


    <!-- الجوال -->

    <td>

        <?= htmlspecialchars(
            $row['phone']
            ?? '-'
        ) ?>

    </td>


    <!-- المنطقة -->

    <td>

        <?= htmlspecialchars(
            $row['work_area']
            ?? '-'
        ) ?>

    </td>


    <!-- النوع -->

    <td>

        <?= htmlspecialchars(
            $row['truck_type']
            ?? '-'
        ) ?>

    </td>


    <!-- الوثائق -->

    <td>

        <div class="d-flex flex-wrap justify-content-center gap-2">


            <!-- الإقامة -->

            <div class="document-box">

                <div class="document-title">

                    🪪 <?= $t['iqama'] ?>

                </div>

                <div class="document-date">

                    <?= htmlspecialchars(
                        $row['iqama_expiry_date']
                        ?? '-'
                    ) ?>

                </div>

                <?php if (
                    $row['iqama_expired']
                ): ?>

                    <span class="document-status doc-iqama">

                        <?= $t['expired_short'] ?>

                    </span>

                <?php else: ?>

                    <span class="document-status doc-valid">

                        <?= $t['valid_short'] ?>

                    </span>

                <?php endif; ?>

            </div>


            <!-- الرخصة -->

            <div class="document-box">

                <div class="document-title">

                    🚘 <?= $t['license'] ?>

                </div>

                <div class="document-date">

                    <?= htmlspecialchars(
                        $row['license_expiry_date']
                        ?? '-'
                    ) ?>

                </div>

                <?php if (
                    $row['license_expired']
                ): ?>

                    <span class="document-status doc-license">

                        <?= $t['expired_short'] ?>

                    </span>

                <?php else: ?>

                    <span class="document-status doc-valid">

                        <?= $t['valid_short'] ?>

                    </span>

                <?php endif; ?>

            </div>


            <!-- بطاقة السائق -->

            <div class="document-box">

                <div class="document-title">

                    🪪 <?= $t['card'] ?>

                </div>

                <div class="document-date">

                    <?= htmlspecialchars(
                        $row['driver_card_expiration_date']
                        ?? '-'
                    ) ?>

                </div>

                <?php if (
                    $row['card_expired']
                ): ?>

                    <span class="document-status doc-card">

                        <?= $t['expired_short'] ?>

                    </span>

                <?php else: ?>

                    <span class="document-status doc-valid">

                        <?= $t['valid_short'] ?>

                    </span>

                <?php endif; ?>

            </div>


        </div>

    </td>


    <!-- الحالة العامة -->

    <td>

        <?php if (
            $row['expired_count'] >= 2
        ): ?>

            <span
                class="badge status-multi"
            >

                <?= $t['multiple'] ?>

                <br>

                <small>

                    <?= $row['expired_count'] ?>

                </small>

            </span>

        <?php elseif (
            $row['iqama_expired']
        ): ?>

            <span class="badge bg-danger">

                <?= $t['iqama'] ?>

            </span>

        <?php elseif (
            $row['license_expired']
        ): ?>

            <span class="badge bg-warning text-dark">

                <?= $t['license'] ?>

            </span>

        <?php elseif (
            $row['card_expired']
        ): ?>

            <span class="badge bg-primary">

                <?= $t['card'] ?>

            </span>

        <?php else: ?>

            <span class="badge status-valid">

                <?= $t['valid'] ?>

            </span>

        <?php endif; ?>

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

        <?= $t['total_drivers'] ?>:

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

</div>

</body>

</html>
