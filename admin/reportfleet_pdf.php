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
   الفلاتر - نفس reportfleet.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$plate = trim($_GET['plate'] ?? '');

$work = trim($_GET['work'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'              => 'تقرير المركبات',
        'company'            => 'منصة الشرق الذكية للخدمات وإدارة الأسطول',

        'id'                 => '#',
        'image'              => 'الصورة',
        'driver'             => 'السائق',
        'plate'              => 'رقم اللوحة',
        'type'               => 'النوع',
        'model'              => 'الموديل',
        'color'              => 'اللون',
        'city'               => 'المدينة',

        'inspection'         => 'الفحص',
        'insurance'          => 'التأمين',
        'operation'          => 'كرت التشغيل',
        'status'             => 'الحالة',

        'healthy'            => 'سليم',
        'inspection_expired' => 'الفحص منتهي',
        'insurance_expired'  => 'التأمين منتهي',
        'operation_expired'  => 'كرت التشغيل منتهي',

        'filters'            => 'الفلاتر المستخدمة',
        'search_filter'      => 'البحث',
        'driver_filter'      => 'السائق',
        'plate_filter'       => 'اللوحة',
        'city_filter'        => 'المدينة',
        'status_filter'      => 'الحالة',

        'all_records'        => 'جميع السجلات',

        'total'              => 'إجمالي المركبات',
        'healthy_total'      => 'المركبات السليمة',
        'inspection_total'   => 'فحص منتهي',
        'insurance_total'    => 'تأمين منتهي',
        'operation_total'    => 'تشغيل منتهي',

        'no_data'            => 'لا توجد مركبات مطابقة للفلاتر المحددة',

        'unknown'            => 'غير محدد',

        'generated_at'        => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'              => 'Fleet Report',
        'company'            => 'AlSharq Smart Services & Fleet Management',

        'id'                 => '#',
        'image'              => 'Image',
        'driver'             => 'Driver',
        'plate'              => 'Plate',
        'type'               => 'Type',
        'model'              => 'Model',
        'color'              => 'Color',
        'city'               => 'City',

        'inspection'         => 'Inspection',
        'insurance'          => 'Insurance',
        'operation'          => 'Operation Card',
        'status'             => 'Status',

        'healthy'            => 'Healthy',
        'inspection_expired' => 'Inspection Expired',
        'insurance_expired'  => 'Insurance Expired',
        'operation_expired'  => 'Operation Expired',

        'filters'            => 'Applied Filters',
        'search_filter'      => 'Search',
        'driver_filter'      => 'Driver',
        'plate_filter'       => 'Plate',
        'city_filter'        => 'City',
        'status_filter'      => 'Status',

        'all_records'        => 'All Records',

        'total'              => 'Total Vehicles',
        'healthy_total'      => 'Healthy Vehicles',
        'inspection_total'   => 'Inspection Expired',
        'insurance_total'    => 'Insurance Expired',
        'operation_total'    => 'Operation Expired',

        'no_data'            => 'No vehicles match the selected filters',

        'unknown'            => 'Unknown',

        'generated_at'       => 'Generated At'

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء الفلاتر
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
            f.plate LIKE ?
            OR f.driver LIKE ?
            OR f.model LIKE ?
            OR f.typefleet LIKE ?
            OR f.classify LIKE ?
            OR f.colorfleet LIKE ?
            OR f.work LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    for ($i = 0; $i < 7; $i++) {
        $params[] = $searchValue;
    }

    $types .= "sssssss";
}

/* =========================================================
   فلتر السائق
========================================================= */

$selectedDriverName = '';

if ($driver_id > 0) {

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            "i",
            $driver_id
        );

        $driverStmt->execute();

        $driverRow = $driverStmt
            ->get_result()
            ->fetch_assoc();

        if ($driverRow) {
            $selectedDriverName = trim(
                $driverRow['name'] ?? ''
            );
        }

        $driverStmt->close();
    }

    if ($selectedDriverName !== '') {

        $where .= "
            AND f.driver LIKE ?
        ";

        $params[] =
            '%' . $selectedDriverName . '%';

        $types .= "s";

    } else {

        $where .= " AND 1=0 ";
    }
}

/* =========================================================
   فلتر اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND f.plate LIKE ?
    ";

    $params[] =
        '%' . $plate . '%';

    $types .= "s";
}

/* =========================================================
   فلتر المدينة
========================================================= */

if ($work !== '') {

    $where .= "
        AND f.work LIKE ?
    ";

    $params[] =
        '%' . $work . '%';

    $types .= "s";
}

/* =========================================================
   فلتر الحالة
========================================================= */

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

/* =========================================================
   الاستعلام
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

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$healthyCount = 0;

$inspectionExpiredCount = 0;

$insuranceExpiredCount = 0;

$operationExpiredCount = 0;

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

    $row['inspection_expired'] =
        $inspectionExpired;

    $row['insurance_expired'] =
        $insuranceExpired;

    $row['operation_expired'] =
        $operationExpired;

    if ($inspectionExpired) {
        $inspectionExpiredCount++;
    }

    if ($insuranceExpired) {
        $insuranceExpiredCount++;
    }

    if ($operationExpired) {
        $operationExpiredCount++;
    }

    if (
        !$inspectionExpired &&
        !$insuranceExpired &&
        !$operationExpired
    ) {
        $healthyCount++;
    }

    if ($inspectionExpired) {

        $row['status_text'] =
            $t['inspection_expired'];

        $row['status_class'] =
            'status-expired';

    } elseif ($insuranceExpired) {

        $row['status_text'] =
            $t['insurance_expired'];

        $row['status_class'] =
            'status-warning';

    } elseif ($operationExpired) {

        $row['status_text'] =
            $t['operation_expired'];

        $row['status_class'] =
            'status-expired';

    } else {

        $row['status_text'] =
            $t['healthy'];

        $row['status_class'] =
            'status-good';
    }

    $rows[] = $row;
}

$totalVehicles = count($rows);

/* =========================================================
   دالة مسار صورة المركبة
========================================================= */

function getFleetImagePath($imageName)
{
    $imageName = trim((string)$imageName);

    if ($imageName === '') {
        return '';
    }

    $imageName = str_replace(
        '\\',
        '/',
        $imageName
    );

    $cleanName = ltrim(
        $imageName,
        '/'
    );

    $possiblePaths = [

        __DIR__ .
        '/../fleetimg/img/' .
        basename($cleanName),

        __DIR__ .
        '/../uploads/fleet/' .
        basename($cleanName),

        __DIR__ .
        '/../uploads/' .
        basename($cleanName),

        __DIR__ .
        '/../' .
        $cleanName,

        __DIR__ .
        '/uploads/fleet/' .
        basename($cleanName)

    ];

    foreach ($possiblePaths as $possiblePath) {

        $realPath = realpath($possiblePath);

        if (
            $realPath !== false &&
            is_file($realPath)
        ) {

            return $realPath;
        }
    }

    return '';
}

/* =========================================================
   تحويل الصورة إلى Base64
   لضمان ظهورها في mPDF
========================================================= */

function imageToDataUri($filePath)
{
    if (
        $filePath === '' ||
        !is_file($filePath)
    ) {
        return '';
    }

    $extension = strtolower(
        pathinfo(
            $filePath,
            PATHINFO_EXTENSION
        )
    );

    $mimeTypes = [

        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp'

    ];

    if (
        !isset(
            $mimeTypes[$extension]
        )
    ) {
        return '';
    }

    $content =
        @file_get_contents(
            $filePath
        );

    if ($content === false) {
        return '';
    }

    return
        'data:' .
        $mimeTypes[$extension] .
        ';base64,' .
        base64_encode(
            $content
        );
}

/* =========================================================
   إعدادات الشركة
========================================================= */

$settingsData = [];

$settingsResult = $con->query("
    SELECT
        setting_key,
        setting_value
    FROM settings
");

if ($settingsResult) {

    while ($setting =
        $settingsResult->fetch_assoc()
    ) {

        $settingsData[
            $setting['setting_key']
        ] = $setting['setting_value'];
    }
}

$companyName =
    $settingsData['company_name']
    ??
    $t['company'];

/* =========================================================
   إعداد mPDF
========================================================= */

$mpdf = new Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4-L',

    'orientation' => 'L',

    'margin_left' => 7,

    'margin_right' => 7,

    'margin_top' => 9,

    'margin_bottom' => 10,

    'default_font' => 'dejavusans'

]);

$mpdf->SetTitle(
    $t['title']
);

$mpdf->SetAuthor(
    'AlSharqPlatform'
);

/* =========================================================
   الاتجاه
========================================================= */

$direction =
    $lang === 'ar'
        ? 'rtl'
        : 'ltr';

/* =========================================================
   الفلاتر المستخدمة
========================================================= */

$filterItems = [];

if ($search !== '') {

    $filterItems[] = [

        'label' =>
            $t['search_filter'],

        'value' =>
            $search
    ];
}

if ($selectedDriverName !== '') {

    $filterItems[] = [

        'label' =>
            $t['driver_filter'],

        'value' =>
            $selectedDriverName
    ];
}

if ($plate !== '') {

    $filterItems[] = [

        'label' =>
            $t['plate_filter'],

        'value' =>
            $plate
    ];
}

if ($work !== '') {

    $filterItems[] = [

        'label' =>
            $t['city_filter'],

        'value' =>
            $work
    ];
}

if ($status_filter !== '') {

    $statusLabel =
        $status_filter;

    if ($status_filter === 'healthy') {

        $statusLabel =
            $t['healthy'];

    } elseif (
        $status_filter ===
        'inspection_expired'
    ) {

        $statusLabel =
            $t['inspection_expired'];

    } elseif (
        $status_filter ===
        'insurance_expired'
    ) {

        $statusLabel =
            $t['insurance_expired'];

    } elseif (
        $status_filter ===
        'operation_expired'
    ) {

        $statusLabel =
            $t['operation_expired'];
    }

    $filterItems[] = [

        'label' =>
            $t['status_filter'],

        'value' =>
            $statusLabel
    ];
}

/* =========================================================
   HTML
========================================================= */

$html = '';

$html .= '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body {

    font-family: dejavusans;

    direction: ' .
    $direction .
    ';

    font-size: 8.5px;

    color: #222;

}

.header {

    text-align: center;

    border-bottom:
        2px solid #2563eb;

    padding-bottom: 8px;

    margin-bottom: 10px;

}

.header h1 {

    margin: 0;

    color: #2563eb;

    font-size: 18px;

}

.header h2 {

    margin:
        4px 0;

    color: #555;

    font-size: 11px;

}

.header-date {

    color: #777;

    font-size: 8px;

}

.summary {

    width: 100%;

    border-collapse:
        collapse;

    margin-bottom: 9px;

}

.summary td {

    border:
        1px solid #ddd;

    background:
        #f8f9fa;

    padding: 6px;

    text-align: center;

}

.summary-label {

    font-size: 8px;

    color: #666;

}

.summary-value {

    font-size: 12px;

    font-weight: bold;

}

.filters {

    border:
        1px solid #ddd;

    background:
        #fafafa;

    padding: 5px;

    margin-bottom: 9px;

}

.filters-title {

    font-weight: bold;

    margin-bottom: 4px;

}

.filter-item {

    display: inline-block;

    border:
        1px solid #ddd;

    background:
        #fff;

    padding: 3px 5px;

    margin: 1px;

}

.report {

    width: 100%;

    border-collapse:
        collapse;

}

.report th {

    background:
        #343a40;

    color:
        #fff;

    border:
        1px solid #222;

    padding:
        5px 3px;

    text-align:
        center;

    font-size:
        8px;

}

.report td {

    border:
        1px solid #ddd;

    padding:
        4px 3px;

    vertical-align:
        middle;

    text-align:
        center;

    font-size:
        7.7px;

}

.vehicle-image {

    width:
        60px;

    height:
        45px;

    object-fit:
        contain;

}

.status-good {

    background:
        #d1e7dd;

    color:
        #0f5132;

    padding:
        3px 5px;

}

.status-expired {

    background:
        #f8d7da;

    color:
        #842029;

    padding:
        3px 5px;

}

.status-warning {

    background:
        #fff3cd;

    color:
        #664d03;

    padding:
        3px 5px;

}

.expired-date {

    color:
        #dc3545;

    font-weight:
        bold;

}

.valid-date {

    color:
        #198754;

    font-weight:
        bold;

}

.total-row td {

    background:
        #e9ecef !important;

    font-weight:
        bold;

}

.no-data {

    text-align:
        center;

    padding:
        25px;

    color:
        #777;

}

.footer {

    margin-top:
        8px;

    padding-top:
        5px;

    border-top:
        1px solid #ddd;

    text-align:
        center;

    color:
        #777;

    font-size:
        7px;

}

</style>

</head>

<body>

';

/* =========================================================
   رأس التقرير
========================================================= */

$html .= '

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

    <div class="header-date">

        ' .
        htmlspecialchars(
            $t['generated_at']
        ) .
        ':
        ' .
        date('Y-m-d H:i')
        . '

    </div>

</div>
';

/* =========================================================
   الملخص
========================================================= */

$html .= '

<table class="summary">

<tr>

<td>

    <div class="summary-label">
        ' .
        htmlspecialchars(
            $t['total']
        ) .
        '
    </div>

    <div class="summary-value">
        ' .
        number_format(
            $totalVehicles
        ) .
        '
    </div>

</td>

<td>

    <div class="summary-label">
        ' .
        htmlspecialchars(
            $t['healthy_total']
        ) .
        '
    </div>

    <div class="summary-value"
         style="color:#198754;">
        ' .
        number_format(
            $healthyCount
        ) .
        '
    </div>

</td>

<td>

    <div class="summary-label">
        ' .
        htmlspecialchars(
            $t['inspection_total']
        ) .
        '
    </div>

    <div class="summary-value"
         style="color:#dc3545;">
        ' .
        number_format(
            $inspectionExpiredCount
        ) .
        '
    </div>

</td>

<td>

    <div class="summary-label">
        ' .
        htmlspecialchars(
            $t['insurance_total']
        ) .
        '
    </div>

    <div class="summary-value"
         style="color:#d97706;">
        ' .
        number_format(
            $insuranceExpiredCount
        ) .
        '
    </div>

</td>

<td>

    <div class="summary-label">
        ' .
        htmlspecialchars(
            $t['operation_total']
        ) .
        '
    </div>

    <div class="summary-value"
         style="color:#dc3545;">
        ' .
        number_format(
            $operationExpiredCount
        ) .
        '
    </div>

</td>

</tr>

</table>
';

/* =========================================================
   الفلاتر
========================================================= */

$html .= '

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

    foreach ($filterItems as $item) {

        $html .= '

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

    $html .= '

        <span class="filter-item">

            ' .
            htmlspecialchars(
                $t['all_records']
            ) .
            '

        </span>
    ';
}

$html .= '

</div>
';

/* =========================================================
   جدول التقرير
========================================================= */

$html .= '

<table class="report">

<thead>

<tr>

<th width="4%">
    ' . htmlspecialchars(
        $t['id']
    ) . '
</th>

<th width="9%">
    ' . htmlspecialchars(
        $t['image']
    ) . '
</th>

<th width="11%">
    ' . htmlspecialchars(
        $t['driver']
    ) . '
</th>

<th width="9%">
    ' . htmlspecialchars(
        $t['plate']
    ) . '
</th>

<th width="9%">
    ' . htmlspecialchars(
        $t['type']
    ) . '
</th>

<th width="9%">
    ' . htmlspecialchars(
        $t['model']
    ) . '
</th>

<th width="8%">
    ' . htmlspecialchars(
        $t['color']
    ) . '
</th>

<th width="9%">
    ' . htmlspecialchars(
        $t['city']
    ) . '
</th>

<th width="10%">
    ' . htmlspecialchars(
        $t['inspection']
    ) . '
</th>

<th width="10%">
    ' . htmlspecialchars(
        $t['insurance']
    ) . '
</th>

<th width="10%">
    ' . htmlspecialchars(
        $t['operation']
    ) . '
</th>

<th width="10%">
    ' . htmlspecialchars(
        $t['status']
    ) . '
</th>

</tr>

</thead>

<tbody>
';

/* =========================================================
   البيانات
========================================================= */

if (empty($rows)) {

    $html .= '

    <tr>

        <td
            colspan="12"
            class="no-data"
        >

            ' .
            htmlspecialchars(
                $t['no_data']
            ) .
            '

        </td>

    </tr>
    ';

} else {

    foreach ($rows as $row) {

        /* ---------------------------------------------
           الصورة
        --------------------------------------------- */

        $imagePath =
            getFleetImagePath(
                $row['imgfleet'] ?? ''
            );

        $imageData =
            imageToDataUri(
                $imagePath
            );

        if ($imageData !== '') {

            $imageHtml =
                '<img
                    src="' .
                    $imageData .
                    '"
                    class="vehicle-image"
                >';

        } else {

            $imageHtml = '-';
        }

        /* ---------------------------------------------
           الحالة
        --------------------------------------------- */

        $statusText =
            $row['status_text']
            ?? $t['unknown'];

        $statusClass =
            $row['status_class']
            ?? 'status-warning';

        /* ---------------------------------------------
           تواريخ المستندات
        --------------------------------------------- */

        $inspectionDate =
            $row['inspection_expiry']
            ?? '';

        $insuranceDate =
            $row['insurance_expiration_date']
            ?? '';

        $operationDate =
            $row['operation_expiry']
            ?? '';

        $inspectionClass =
            !empty($row['inspection_expired'])
                ? 'expired-date'
                : 'valid-date';

        $insuranceClass =
            !empty($row['insurance_expired'])
                ? 'expired-date'
                : 'valid-date';

        $operationClass =
            !empty($row['operation_expired'])
                ? 'expired-date'
                : 'valid-date';

        /* ---------------------------------------------
           الصف
        --------------------------------------------- */

        $html .= '

        <tr>

            <td>
                #' .
                (int)$row['id'] .
                '
            </td>

            <td>

                ' .
                $imageHtml .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['driver'] ?? '-'
                ) .
                '

            </td>

            <td>

                <strong>
                    ' .
                    htmlspecialchars(
                        $row['plate'] ?? '-'
                    ) .
                    '
                </strong>

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['typefleet'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['model'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['colorfleet'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['work'] ?? '-'
                ) .
                '

            </td>

            <td class="' .
                $inspectionClass .
                '">

                ' .
                htmlspecialchars(
                    $inspectionDate !== ''
                        ? $inspectionDate
                        : '-'
                ) .
                '

            </td>

            <td class="' .
                $insuranceClass .
                '">

                ' .
                htmlspecialchars(
                    $insuranceDate !== ''
                        ? $insuranceDate
                        : '-'
                ) .
                '

            </td>

            <td class="' .
                $operationClass .
                '">

                ' .
                htmlspecialchars(
                    $operationDate !== ''
                        ? $operationDate
                        : '-'
                ) .
                '

            </td>

            <td>

                <span class="' .
                    $statusClass .
                '">

                    ' .
                    htmlspecialchars(
                        $statusText
                    ) .
                    '

                </span>

            </td>

        </tr>
        ';
    }

    /* =====================================================
       الإجمالي
    ===================================================== */

    $html .= '

    <tr class="total-row">

        <td colspan="2">

            ' .
            htmlspecialchars(
                $t['total']
            ) .
            '

        </td>

        <td colspan="2">

            ' .
            number_format(
                $totalVehicles
            ) .
            '

        </td>

        <td colspan="2">

            ' .
            htmlspecialchars(
                $t['healthy_total']
            ) .
            ':
            ' .
            number_format(
                $healthyCount
            ) .
            '

        </td>

        <td>

            ' .
            htmlspecialchars(
                $t['inspection_total']
            ) .
            ':
            ' .
            number_format(
                $inspectionExpiredCount
            ) .
            '

        </td>

        <td>

            ' .
            htmlspecialchars(
                $t['insurance_total']
            ) .
            ':
            ' .
            number_format(
                $insuranceExpiredCount
            ) .
            '

        </td>

        <td colspan="3">

            ' .
            htmlspecialchars(
                $t['operation_total']
            ) .
            ':
            ' .
            number_format(
                $operationExpiredCount
            ) .
            '

        </td>

        <td></td>

    </tr>
    ';
}

$html .= '

</tbody>

</table>

<div class="footer">

    ' .
    htmlspecialchars(
        $companyName
    ) .
    ' -
    ' .
    date('Y')
    . '

</div>

</body>

</html>
';

/* =========================================================
   إنشاء PDF
========================================================= */

/* =========================================================
   إرسال HTML إلى mPDF على أجزاء
   لمنع تجاوز pcre.backtrack_limit
========================================================= */

/* ---------------------------------------------------------
   CSS
--------------------------------------------------------- */

$mpdf->WriteHTML(
    '
    <style>
        body {
            font-family: dejavusans;
            direction: ' . $direction . ';
            font-size: 8.5px;
            color: #222;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 18px;
        }

        .header h2 {
            margin: 4px 0;
            color: #555;
            font-size: 11px;
        }

        .header-date {
            color: #777;
            font-size: 8px;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
        }

        .summary td {
            border: 1px solid #ddd;
            background: #f8f9fa;
            padding: 6px;
            text-align: center;
        }

        .summary-label {
            font-size: 8px;
            color: #666;
        }

        .summary-value {
            font-size: 12px;
            font-weight: bold;
        }

        .filters {
            border: 1px solid #ddd;
            background: #fafafa;
            padding: 5px;
            margin-bottom: 9px;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .filter-item {
            display: inline-block;
            border: 1px solid #ddd;
            background: #fff;
            padding: 3px 5px;
            margin: 1px;
        }

        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .report th {
            background: #343a40;
            color: #fff;
            border: 1px solid #222;
            padding: 5px 3px;
            text-align: center;
            font-size: 8px;
        }

        .report td {
            border: 1px solid #ddd;
            padding: 4px 3px;
            vertical-align: middle;
            text-align: center;
            font-size: 7.7px;
        }

        .vehicle-image {
            width: 55px;
            height: 40px;
            object-fit: contain;
        }

        .status-good {
            background: #d1e7dd;
            color: #0f5132;
            padding: 3px 5px;
        }

        .status-expired {
            background: #f8d7da;
            color: #842029;
            padding: 3px 5px;
        }

        .status-warning {
            background: #fff3cd;
            color: #664d03;
            padding: 3px 5px;
        }

        .total-row td {
            background: #e9ecef !important;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 25px;
            color: #777;
        }

        .footer {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #777;
            font-size: 7px;
        }
    </style>
    ',
    \Mpdf\HTMLParserMode::HEADER_CSS
);

/* ---------------------------------------------------------
   بداية HTML
--------------------------------------------------------- */

$mpdf->WriteHTML(
    '
    <div class="header">

        <h1>
            ' . htmlspecialchars($t['title']) . '
        </h1>

        <h2>
            ' . htmlspecialchars($companyName) . '
        </h2>

        <div class="header-date">
            ' . htmlspecialchars($t['generated_at']) . ':
            ' . date('Y-m-d H:i') . '
        </div>

    </div>
    '
);

/* ---------------------------------------------------------
   الملخص
--------------------------------------------------------- */

$mpdf->WriteHTML(
    '
    <table class="summary">
        <tr>

            <td>
                <div class="summary-label">
                    ' . htmlspecialchars($t['total']) . '
                </div>

                <div class="summary-value">
                    ' . number_format($totalVehicles) . '
                </div>
            </td>

            <td>
                <div class="summary-label">
                    ' . htmlspecialchars($t['healthy_total']) . '
                </div>

                <div class="summary-value" style="color:#198754;">
                    ' . number_format($healthyCount) . '
                </div>
            </td>

            <td>
                <div class="summary-label">
                    ' . htmlspecialchars($t['inspection_total']) . '
                </div>

                <div class="summary-value" style="color:#dc3545;">
                    ' . number_format($inspectionExpiredCount) . '
                </div>
            </td>

            <td>
                <div class="summary-label">
                    ' . htmlspecialchars($t['insurance_total']) . '
                </div>

                <div class="summary-value" style="color:#d97706;">
                    ' . number_format($insuranceExpiredCount) . '
                </div>
            </td>

            <td>
                <div class="summary-label">
                    ' . htmlspecialchars($t['operation_total']) . '
                </div>

                <div class="summary-value" style="color:#dc3545;">
                    ' . number_format($operationExpiredCount) . '
                </div>
            </td>

        </tr>
    </table>
    '
);

/* ---------------------------------------------------------
   الفلاتر
--------------------------------------------------------- */

$filtersHtml = '
<div class="filters">

    <div class="filters-title">
        ' . htmlspecialchars($t['filters']) . '
    </div>
';

if (!empty($filterItems)) {

    foreach ($filterItems as $item) {

        $filtersHtml .= '
            <span class="filter-item">
                <strong>
                    ' . htmlspecialchars($item['label']) . ':
                </strong>
                ' . htmlspecialchars($item['value']) . '
            </span>
        ';
    }

} else {

    $filtersHtml .= '
        <span class="filter-item">
            ' . htmlspecialchars($t['all_records']) . '
        </span>
    ';
}

$filtersHtml .= '</div>';

$mpdf->WriteHTML($filtersHtml);

/* ---------------------------------------------------------
   بداية الجدول
--------------------------------------------------------- */

$mpdf->WriteHTML(
    '
    <table class="report">

        <thead>

            <tr>

                <th width="4%">' . htmlspecialchars($t['id']) . '</th>

                <th width="9%">' . htmlspecialchars($t['image']) . '</th>

                <th width="11%">' . htmlspecialchars($t['driver']) . '</th>

                <th width="9%">' . htmlspecialchars($t['plate']) . '</th>

                <th width="9%">' . htmlspecialchars($t['type']) . '</th>

                <th width="9%">' . htmlspecialchars($t['model']) . '</th>

                <th width="8%">' . htmlspecialchars($t['color']) . '</th>

                <th width="9%">' . htmlspecialchars($t['city']) . '</th>

                <th width="10%">' . htmlspecialchars($t['inspection']) . '</th>

                <th width="10%">' . htmlspecialchars($t['insurance']) . '</th>

                <th width="10%">' . htmlspecialchars($t['operation']) . '</th>

                <th width="10%">' . htmlspecialchars($t['status']) . '</th>

            </tr>

        </thead>

        <tbody>
    '
);

/* ---------------------------------------------------------
   كل مركبة في WriteHTML مستقل
--------------------------------------------------------- */

if (empty($rows)) {

    $mpdf->WriteHTML(
        '
        <tr>
            <td colspan="12" class="no-data">
                ' . htmlspecialchars($t['no_data']) . '
            </td>
        </tr>
        '
    );

} else {

    foreach ($rows as $row) {

        /* الصورة */

        $imagePath = getFleetImagePath(
            $row['imgfleet'] ?? ''
        );

        $imageData = imageToDataUri(
            $imagePath
        );

        if ($imageData !== '') {

            $imageHtml = '
                <img
                    src="' . $imageData . '"
                    class="vehicle-image"
                >
            ';

        } else {

            $imageHtml = '-';
        }

        /* الحالة */

        $statusText =
            $row['status_text']
            ?? $t['unknown'];

        $statusClass =
            $row['status_class']
            ?? 'status-warning';

        /* التواريخ */

        $inspectionDate =
            $row['inspection_expiry']
            ?? '';

        $insuranceDate =
            $row['insurance_expiration_date']
            ?? '';

        $operationDate =
            $row['operation_expiry']
            ?? '';

        $inspectionClass =
            !empty($row['inspection_expired'])
                ? 'expired-date'
                : 'valid-date';

        $insuranceClass =
            !empty($row['insurance_expired'])
                ? 'expired-date'
                : 'valid-date';

        $operationClass =
            !empty($row['operation_expired'])
                ? 'expired-date'
                : 'valid-date';

        $rowHtml = '

            <tr>

                <td>
                    #' . (int)$row['id'] . '
                </td>

                <td>
                    ' . $imageHtml . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $row['driver'] ?? '-'
                    ) . '
                </td>

                <td>
                    <strong>
                        ' . htmlspecialchars(
                            $row['plate'] ?? '-'
                        ) . '
                    </strong>
                </td>

                <td>
                    ' . htmlspecialchars(
                        $row['typefleet'] ?? '-'
                    ) . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $row['model'] ?? '-'
                    ) . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $row['colorfleet'] ?? '-'
                    ) . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $row['work'] ?? '-'
                    ) . '
                </td>

                <td class="' . $inspectionClass . '">
                    ' . htmlspecialchars(
                        $inspectionDate !== ''
                            ? $inspectionDate
                            : '-'
                    ) . '
                </td>

                <td class="' . $insuranceClass . '">
                    ' . htmlspecialchars(
                        $insuranceDate !== ''
                            ? $insuranceDate
                            : '-'
                    ) . '
                </td>

                <td class="' . $operationClass . '">
                    ' . htmlspecialchars(
                        $operationDate !== ''
                            ? $operationDate
                            : '-'
                    ) . '
                </td>

                <td>
                    <span class="' . $statusClass . '">
                        ' . htmlspecialchars($statusText) . '
                    </span>
                </td>

            </tr>
        ';

        $mpdf->WriteHTML($rowHtml);
    }
}

/* ---------------------------------------------------------
   نهاية الجدول
--------------------------------------------------------- */

$mpdf->WriteHTML(
    '
        <tr class="total-row">

            <td colspan="2">
                ' . htmlspecialchars($t['total']) . '
            </td>

            <td colspan="2">
                ' . number_format($totalVehicles) . '
            </td>

            <td colspan="2">
                ' . htmlspecialchars($t['healthy_total']) . ':
                ' . number_format($healthyCount) . '
            </td>

            <td>
                ' . htmlspecialchars($t['inspection_total']) . ':
                ' . number_format($inspectionExpiredCount) . '
            </td>

            <td>
                ' . htmlspecialchars($t['insurance_total']) . ':
                ' . number_format($insuranceExpiredCount) . '
            </td>

            <td colspan="3">
                ' . htmlspecialchars($t['operation_total']) . ':
                ' . number_format($operationExpiredCount) . '
            </td>

            <td></td>

        </tr>

        </tbody>

    </table>

    <div class="footer">
        ' . htmlspecialchars($companyName) . '
        -
        ' . date('Y') . '
    </div>
    '
);

/* =========================================================
   إخراج الملف
========================================================= */

$fileName =
    'reportfleet_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

$mpdf->Output(
    $fileName,
    'I'
);

$stmt->close();

exit;

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'reportfleet_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

/* =========================================================
   إخراج الملف
========================================================= */

$mpdf->Output(
    $fileName,
    'I'
);

$stmt->close();

exit;