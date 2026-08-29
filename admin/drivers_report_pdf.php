<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس drivers_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$work_area = trim($_GET['work_area'] ?? '');

$truck_type = trim($_GET['truck_type'] ?? '');

$document_status = trim(
    $_GET['document_status'] ?? ''
);

/* =========================================================
   التاريخ
========================================================= */

$today = date('Y-m-d');

$nearDate = date(
    'Y-m-d',
    strtotime('+30 days')
);

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title' => 'تقرير السائقين',

        'company_report' =>
            'تقرير بيانات السائقين والوثائق',

        'id' => '#',

        'image' => 'الصورة',

        'name' => 'اسم السائق',

        'national_id' => 'الهوية',

        'phone' => 'الجوال',

        'work_area' => 'منطقة العمل',

        'truck_type' => 'نوع السطحة',

        'iqama' => 'الإقامة',

        'license' => 'الرخصة',

        'card' => 'بطاقة السائق',

        'status' => 'الحالة',

        'expired' => 'منتهية',

        'valid' => 'سارية',

        'multiple' =>
            'أكثر من وثيقة منتهية',

        'filters' =>
            'الفلاتر المستخدمة',

        'search_filter' =>
            'البحث',

        'area_filter' =>
            'منطقة العمل',

        'type_filter' =>
            'نوع السطحة',

        'status_filter' =>
            'حالة الوثائق',

        'all_records' =>
            'جميع السجلات',

        'near' =>
            'قريبة من الانتهاء',

        'total_drivers' =>
            'إجمالي السائقين',

        'iqama_expired' =>
            'الإقامات المنتهية',

        'license_expired' =>
            'الرخص المنتهية',

        'card_expired' =>
            'البطاقات المنتهية',

        'multi_expired' =>
            'أكثر من وثيقة منتهية',

        'generated_at' =>
            'تاريخ إنشاء التقرير',

        'no_data' =>
            'لا توجد سجلات مطابقة للفلاتر'

    ],

    'en' => [

        'title' => 'Drivers Report',

        'company_report' =>
            'Driver Data and Document Report',

        'id' => '#',

        'image' => 'Image',

        'name' => 'Driver Name',

        'national_id' =>
            'National ID',

        'phone' => 'Phone',

        'work_area' =>
            'Work Area',

        'truck_type' =>
            'Truck Type',

        'iqama' =>
            'Iqama',

        'license' =>
            'License',

        'card' =>
            'Driver Card',

        'status' =>
            'Status',

        'expired' =>
            'Expired',

        'valid' =>
            'Valid',

        'multiple' =>
            'Multiple Expired Documents',

        'filters' =>
            'Applied Filters',

        'search_filter' =>
            'Search',

        'area_filter' =>
            'Work Area',

        'type_filter' =>
            'Truck Type',

        'status_filter' =>
            'Document Status',

        'all_records' =>
            'All Records',

        'near' =>
            'Near Expiry',

        'total_drivers' =>
            'Total Drivers',

        'iqama_expired' =>
            'Expired Iqamas',

        'license_expired' =>
            'Expired Licenses',

        'card_expired' =>
            'Expired Driver Cards',

        'multi_expired' =>
            'Multiple Expired Documents',

        'generated_at' =>
            'Generated At',

        'no_data' =>
            'No records match the selected filters'

    ]

];

$t = $text[$lang];

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
            OR d.national_id LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
            OR d.truck_type LIKE ?
        )
    ";

    $value =
        '%' . $search . '%';

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
   الاستعلام
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

    FROM drivers d

    $where

    ORDER BY
        d.name ASC
";

/* =========================================================
   Prepare
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   Bind
========================================================= */

if (!empty($params)) {

    if (
        strlen($types) !== count($params)
    ) {

        die(
            "Filter parameters mismatch."
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/* =========================================================
   Execute
========================================================= */

if (!$stmt->execute()) {

    die(
        "Execute Error: " .
        htmlspecialchars($stmt->error)
    );
}

$result =
    $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
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
        $row['iqama_expiry_date'] < $today;

    $licenseExpired =
        !empty(
            $row['license_expiry_date']
        )
        &&
        $row['license_expiry_date'] < $today;

    $cardExpired =
        !empty(
            $row['driver_card_expiration_date']
        )
        &&
        $row['driver_card_expiration_date'] < $today;

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

    $row['iqama_expired'] =
        $iqamaExpired;

    $row['license_expired'] =
        $licenseExpired;

    $row['card_expired'] =
        $cardExpired;

    $row['expired_count'] =
        $expiredCount;

    if ($expiredCount >= 2) {

        $row['status_text'] =
            $t['multiple'];

        $row['status_color'] =
            '#6f42c1';

    } elseif ($iqamaExpired) {

        $row['status_text'] =
            $t['iqama'];

        $row['status_color'] =
            '#dc3545';

    } elseif ($licenseExpired) {

        $row['status_text'] =
            $t['license'];

        $row['status_color'] =
            '#fd7e14';

    } elseif ($cardExpired) {

        $row['status_text'] =
            $t['card'];

        $row['status_color'] =
            '#0d6efd';

    } else {

        $row['status_text'] =
            $t['valid'];

        $row['status_color'] =
            '#198754';
    }

    $rows[] =
        $row;
}

$totalDrivers =
    count($rows);

/* =========================================================
   إعدادات الشركة
========================================================= */

$settingsData = [];

$settingsResult =
    $con->query("
        SELECT
            setting_key,
            setting_value
        FROM settings
    ");

if ($settingsResult) {

    while (
        $setting =
        $settingsResult->fetch_assoc()
    ) {

        $settingsData[
            $setting['setting_key']
        ] =
            $setting['setting_value'];
    }
}

$companyName =
    $settingsData['company_name']
    ?? 'AlSharqPlatform';

/* =========================================================
   إعداد mPDF
========================================================= */

$mpdf = new Mpdf([

    'mode' =>
        'utf-8',

    'format' =>
        'A4-L',

    'orientation' =>
        'L',

    'margin_left' =>
        6,

    'margin_right' =>
        6,

    'margin_top' =>
        10,

    'margin_bottom' =>
        10,

    'default_font' =>
        'dejavusans'

]);

$mpdf->SetTitle(
    $t['title']
);

$mpdf->SetAuthor(
    'AlSharqPlatform'
);

/* =========================================================
   Header
========================================================= */

$mpdf->SetHTMLHeader(
    '
    <div style="
        text-align:center;
        font-family:dejavusans;
        font-size:8px;
        color:#777;
        border-bottom:1px solid #ddd;
        padding-bottom:4px;
    ">
        ' .
        htmlspecialchars(
            $companyName
        ) .
        '
    </div>
    '
);

/* =========================================================
   Footer
========================================================= */

$mpdf->SetHTMLFooter(
    '
    <div style="
        text-align:center;
        font-family:dejavusans;
        font-size:7px;
        color:#777;
        border-top:1px solid #ddd;
        padding-top:4px;
    ">
        {PAGENO}
    </div>
    '
);

/* =========================================================
   اتجاه التقرير
========================================================= */

$direction =
    $lang === 'ar'
        ? 'rtl'
        : 'ltr';

/* =========================================================
   CSS
========================================================= */

$css = '
<style>

body {
    font-family: dejavusans;
    direction: ' . $direction . ';
    font-size: 7.5px;
    color: #222;
}

.header {
    text-align: center;
    border-bottom: 2px solid #0d6efd;
    padding-bottom: 7px;
    margin-bottom: 8px;
}

.header h1 {
    margin: 0;
    color: #0d6efd;
    font-size: 18px;
}

.header h2 {
    margin: 3px 0;
    color: #555;
    font-size: 11px;
}

.generated {
    color: #777;
    font-size: 7px;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}

.summary td {
    border: 1px solid #ddd;
    background: #f8f9fa;
    padding: 5px;
    text-align: center;
}

.summary-label {
    font-size: 7px;
    color: #666;
}

.summary-value {
    font-size: 11px;
    font-weight: bold;
}

.summary-danger {
    color: #dc3545;
}

.summary-warning {
    color: #fd7e14;
}

.summary-primary {
    color: #0d6efd;
}

.summary-purple {
    color: #6f42c1;
}

.filters {
    border: 1px solid #ddd;
    background: #fafafa;
    padding: 5px;
    margin-bottom: 8px;
}

.filters-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.filter-item {
    display: inline-block;
    border: 1px solid #ddd;
    background: #fff;
    padding: 2px 5px;
    margin: 1px;
    font-size: 7px;
}

.report {
    width: 100%;
    border-collapse: collapse;
}

.report thead {
    display: table-header-group;
}

.report th {
    background: #343a40;
    color: #fff;
    border: 1px solid #222;
    padding: 4px 3px;
    text-align: center;
    font-size: 7px;
}

.report td {
    border: 1px solid #ddd;
    padding: 4px 3px;
    text-align: center;
    vertical-align: middle;
    font-size: 6.8px;
}

.report tr:nth-child(even) td {
    background: #f8f9fa;
}

.driver-image {
    width: 34px;
    height: 34px;
}

.image-placeholder {
    width: 34px;
    height: 34px;
    line-height: 34px;
    display: inline-block;
    text-align: center;
    background: #e9ecef;
    color: #777;
}

.documents {
    width: 100%;
    border-collapse: collapse;
}

.documents td {
    border: 0 !important;
    padding: 2px 1px !important;
    font-size: 6.5px !important;
}

.doc-valid {
    color: #198754;
    font-weight: bold;
}

.doc-iqama {
    color: #dc3545;
    font-weight: bold;
}

.doc-license {
    color: #fd7e14;
    font-weight: bold;
}

.doc-card {
    color: #0d6efd;
    font-weight: bold;
}

.status-valid {
    background: #d1e7dd;
    color: #0f5132;
    padding: 3px 5px;
    font-weight: bold;
}

.status-iqama {
    background: #f8d7da;
    color: #842029;
    padding: 3px 5px;
    font-weight: bold;
}

.status-license {
    background: #ffe5d0;
    color: #9a3412;
    padding: 3px 5px;
    font-weight: bold;
}

.status-card {
    background: #cfe2ff;
    color: #084298;
    padding: 3px 5px;
    font-weight: bold;
}

.status-multiple {
    background: #e2d9f3;
    color: #4c2885;
    padding: 3px 5px;
    font-weight: bold;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #777;
}

</style>
';

$mpdf->WriteHTML(
    $css,
    \Mpdf\HTMLParserMode::HEADER_CSS
);

/* =========================================================
   رأس التقرير
========================================================= */

$mpdf->WriteHTML(
    '
    <div class="header">

        <h1>
            ' .
            htmlspecialchars(
                $t['title']
            ) .
            '
        </h1>

        <h2>
            ' .
            htmlspecialchars(
                $companyName
            ) .
            '
        </h2>

        <div class="generated">
            ' .
            htmlspecialchars(
                $t['generated_at']
            ) .
            ': ' .
            date('Y-m-d H:i') .
            '
        </div>

    </div>
    '
);

/* =========================================================
   الملخص
========================================================= */

$mpdf->WriteHTML(
    '
    <table class="summary">

        <tr>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_drivers']
                    ) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format(
                        $totalDrivers
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['iqama_expired']
                    ) .
                    '
                </div>

                <div class="summary-value summary-danger">
                    ' .
                    number_format(
                        $iqamaExpiredCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['license_expired']
                    ) .
                    '
                </div>

                <div class="summary-value summary-warning">
                    ' .
                    number_format(
                        $licenseExpiredCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['card_expired']
                    ) .
                    '
                </div>

                <div class="summary-value summary-primary">
                    ' .
                    number_format(
                        $cardExpiredCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['multi_expired']
                    ) .
                    '
                </div>

                <div class="summary-value summary-purple">
                    ' .
                    number_format(
                        $multiExpiredCount
                    ) .
                    '
                </div>

            </td>

        </tr>

    </table>
    '
);

/* =========================================================
   الفلاتر المستخدمة
========================================================= */

$filterItems = [];

if ($search !== '') {

    $filterItems[] = [
        'label' => $t['search_filter'],
        'value' => $search
    ];
}

if ($work_area !== '') {

    $filterItems[] = [
        'label' => $t['area_filter'],
        'value' => $work_area
    ];
}

if ($truck_type !== '') {

    $filterItems[] = [
        'label' => $t['type_filter'],
        'value' => $truck_type
    ];
}

if ($document_status !== '') {

    if ($document_status === 'expired') {

        $statusLabel =
            $t['expired'];

    } elseif ($document_status === 'near') {

        $statusLabel =
            $t['near'];

    } elseif ($document_status === 'valid') {

        $statusLabel =
            $t['valid'];

    } else {

        $statusLabel =
            $document_status;
    }

    $filterItems[] = [
        'label' => $t['status_filter'],
        'value' => $statusLabel
    ];
}

$filtersHtml = '

<div class="filters">

    <div class="filters-title">
        ' .
        htmlspecialchars(
            $t['filters']
        ) .
        '
    </div>
';

if (!empty($filterItems)) {

    foreach (
        $filterItems
        as $item
    ) {

        $filtersHtml .= '

        <span class="filter-item">

            <strong>
                ' .
                htmlspecialchars(
                    $item['label']
                ) .
                ':
            </strong>

            ' .
            htmlspecialchars(
                $item['value']
            ) .
            '

        </span>
        ';
    }

} else {

    $filtersHtml .= '

        <span class="filter-item">

            ' .
            htmlspecialchars(
                $t['all_records']
            ) .
            '

        </span>
    ';
}

$filtersHtml .= '
</div>
';

$mpdf->WriteHTML(
    $filtersHtml
);

/* =========================================================
   بداية الجدول
========================================================= */

$mpdf->WriteHTML(
    '
    <table class="report">

        <thead>

            <tr>

                <th width="4%">
                    ' .
                    htmlspecialchars($t['id']) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars($t['image']) .
                    '
                </th>

                <th width="16%">
                    ' .
                    htmlspecialchars($t['name']) .
                    '
                </th>

                <th width="11%">
                    ' .
                    htmlspecialchars($t['national_id']) .
                    '
                </th>

                <th width="11%">
                    ' .
                    htmlspecialchars($t['phone']) .
                    '
                </th>

                <th width="13%">
                    ' .
                    htmlspecialchars($t['work_area']) .
                    '
                </th>

                <th width="11%">
                    ' .
                    htmlspecialchars($t['truck_type']) .
                    '
                </th>

                <th width="17%">
                    ' .
                    htmlspecialchars($t['documents']) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars($t['status']) .
                    '
                </th>

            </tr>

        </thead>

        <tbody>
    '
);

/* =========================================================
   البيانات
========================================================= */

if (empty($rows)) {

    $mpdf->WriteHTML(
        '
        <tr>

            <td
                colspan="9"
                class="no-data"
            >

                ' .
                htmlspecialchars(
                    $t['no_data']
                ) .
                '

            </td>

        </tr>
        '
    );

} else {

    $counter = 1;

    foreach (
        $rows
        as $row
    ) {

        /* =================================================
           صورة السائق
        ================================================= */

        $imageHtml =
            '<span class="image-placeholder">👤</span>';

        $imageName =
            trim(
                (string)(
                    $row['imagedriver']
                    ?? ''
                )
            );

        if ($imageName !== '') {

            $candidates = [

                __DIR__ .
                '/../uploads/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/drivers/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/driver/' .
                basename($imageName),

                __DIR__ .
                '/../fleetimg/img/' .
                basename($imageName)

            ];

            $imageFile = '';

            foreach (
                $candidates
                as $candidate
            ) {

                $real =
                    realpath(
                        $candidate
                    );

                if (
                    $real !== false &&
                    is_file($real)
                ) {

                    $imageFile =
                        $real;

                    break;
                }
            }

            if ($imageFile !== '') {

                $imageHtml =
                    '<img
                        class="driver-image"
                        src="' .
                        htmlspecialchars(
                            'file://' .
                            str_replace(
                                '\\',
                                '/',
                                $imageFile
                            )
                        ) .
                    '">';
            }
        }

        /* =================================================
           حالة الوثائق
        ================================================= */

        $iqamaText =
            $row['iqama_expired']
                ? $t['expired']
                : $t['valid'];

        $licenseText =
            $row['license_expired']
                ? $t['expired']
                : $t['valid'];

        $cardText =
            $row['card_expired']
                ? $t['expired']
                : $t['valid'];

        $iqamaClass =
            $row['iqama_expired']
                ? 'doc-iqama'
                : 'doc-valid';

        $licenseClass =
            $row['license_expired']
                ? 'doc-license'
                : 'doc-valid';

        $cardClass =
            $row['card_expired']
                ? 'doc-card'
                : 'doc-valid';

        $documentsHtml = '

            <table class="documents">

                <tr>

                    <td>
                        <strong>
                            ' .
                            htmlspecialchars($t['iqama']) .
                            '
                        </strong>
                    </td>

                    <td>
                        ' .
                        htmlspecialchars(
                            $row['iqama_expiry_date'] ?? '-'
                        ) .
                    '
                    </td>

                    <td class="' .
                        $iqamaClass .
                    '">
                        ' .
                        htmlspecialchars(
                            $iqamaText
                        ) .
                    '
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            ' .
                            htmlspecialchars($t['license']) .
                            '
                        </strong>
                    </td>

                    <td>
                        ' .
                        htmlspecialchars(
                            $row['license_expiry_date'] ?? '-'
                        ) .
                    '
                    </td>

                    <td class="' .
                        $licenseClass .
                    '">
                        ' .
                        htmlspecialchars(
                            $licenseText
                        ) .
                    '
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            ' .
                            htmlspecialchars($t['card']) .
                            '
                        </strong>
                    </td>

                    <td>
                        ' .
                        htmlspecialchars(
                            $row['driver_card_expiration_date'] ?? '-'
                        ) .
                    '
                    </td>

                    <td class="' .
                        $cardClass .
                    '">
                        ' .
                        htmlspecialchars(
                            $cardText
                        ) .
                    '
                    </td>

                </tr>

            </table>
        ';

        /* =================================================
           الحالة العامة
        ================================================= */

        if (
            $row['expired_count'] >= 2
        ) {

            $statusClass =
                'status-multiple';

        } elseif (
            $row['iqama_expired']
        ) {

            $statusClass =
                'status-iqama';

        } elseif (
            $row['license_expired']
        ) {

            $statusClass =
                'status-license';

        } elseif (
            $row['card_expired']
        ) {

            $statusClass =
                'status-card';

        } else {

            $statusClass =
                'status-valid';
        }

        /* =================================================
           صف التقرير
        ================================================= */

        $rowHtml = '

        <tr>

            <td>
                #' .
                $counter .
            '
            </td>

            <td>
                ' .
                $imageHtml .
            '
            </td>

            <td>

                <strong>
                    ' .
                    htmlspecialchars(
                        $row['name'] ?? '-'
                    ) .
                '
                </strong>

            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['national_id'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['phone'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['work_area'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['truck_type'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                $documentsHtml .
            '
            </td>

            <td>

                <span class="' .
                    $statusClass .
                '">

                    ' .
                    htmlspecialchars(
                        $row['status_text']
                    ) .
                '

                    ' .
                    (
                        $row['expired_count'] >= 2
                        ? '<br><small>(' .
                          $row['expired_count'] .
                          ')</small>'
                        : ''
                    ) .
                '

                </span>

            </td>

        </tr>
        ';

        /*
         * كتابة كل صف منفصل
         * لمنع مشكلة pcre.backtrack_limit
         */

        $mpdf->WriteHTML(
            $rowHtml
        );

        $counter++;
    }
}

/* =========================================================
   نهاية الجدول
========================================================= */

$mpdf->WriteHTML(
    '
        </tbody>

    </table>
    '
);

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'drivers_report_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

/* =========================================================
   إخراج PDF
========================================================= */

$mpdf->Output(
    $fileName,
    'I'
);

$stmt->close();

exit;