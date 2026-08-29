<?php

session_start();

include('../include/connected.php');

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
   الفلاتر - نفس accidents.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$vehicle_id = (int)($_GET['vehicle_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'         => 'تقرير حوادث المركبات',

        'company'       => 'منصة الشرق الذكية للخدمات وإدارة الأسطول',

        'id'            => '#',

        'image'         => 'صورة المركبة',

        'vehicle'       => 'المركبة',

        'plate'         => 'رقم اللوحة',

        'driver'        => 'السائق',

        'date'          => 'تاريخ الحادث',

        'location'      => 'الموقع',

        'description'   => 'الوصف',

        'cost'          => 'التكلفة',

        'status'        => 'الحالة',

        'open'          => 'مفتوح',

        'progress'      => 'قيد المعالجة',

        'closed'        => 'مغلق',

        'total_records' => 'إجمالي الحوادث',

        'total_cost'    => 'إجمالي الأضرار',

        'filters'       => 'الفلاتر المستخدمة',

        'search'        => 'البحث',

        'vehicle_filter'=> 'المركبة',

        'driver_filter' => 'السائق',

        'status_filter' => 'الحالة',

        'from'          => 'من',

        'to'            => 'إلى',

        'all'           => 'جميع السجلات',

        'no_data'       => 'لا توجد حوادث مطابقة للفلاتر المحددة',

        'sar'           => 'ريال',

        'unknown'       => 'غير محدد',

    ],

    'en' => [

        'title'         => 'Vehicle Accidents Report',

        'company'       => 'AlSharq Smart Services & Fleet Management',

        'id'            => '#',

        'image'         => 'Vehicle Image',

        'vehicle'       => 'Vehicle',

        'plate'         => 'Plate',

        'driver'        => 'Driver',

        'date'          => 'Accident Date',

        'location'      => 'Location',

        'description'   => 'Description',

        'cost'          => 'Damage Cost',

        'status'        => 'Status',

        'open'          => 'Open',

        'progress'      => 'In Progress',

        'closed'        => 'Closed',

        'total_records' => 'Total Accidents',

        'total_cost'    => 'Total Damage',

        'filters'       => 'Applied Filters',

        'search'        => 'Search',

        'vehicle_filter'=> 'Vehicle',

        'driver_filter' => 'Driver',

        'status_filter' => 'Status',

        'from'          => 'From',

        'to'            => 'To',

        'all'           => 'All Records',

        'no_data'       => 'No accidents match the selected filters',

        'sar'           => 'SAR',

        'unknown'       => 'Unknown',

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء الفلاتر
========================================================= */

$where = " WHERE 1 = 1 ";

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

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sssss";
}

/* المركبة */

if ($vehicle_id > 0) {

    $where .= "
        AND a.vehicle_id = ?
    ";

    $params[] = $vehicle_id;

    $types .= "i";
}

/* السائق */

if ($driver_id > 0) {

    $where .= "
        AND a.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* الحالة */

if (
    $status !== '' &&
    in_array(
        $status,
        ['Open', 'In Progress', 'Closed'],
        true
    )
) {

    $where .= "
        AND a.status = ?
    ";

    $params[] = $status;

    $types .= "s";
}

/* من تاريخ */

if ($from !== '') {

    $where .= "
        AND DATE(a.accident_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* إلى تاريخ */

if ($to !== '') {

    $where .= "
        AND DATE(a.accident_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        a.id,

        a.vehicle_id,

        a.driver_id,

        a.accident_date,

        a.location,

        a.description,

        a.damage_cost,

        a.status,

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

    die(
        "SQL Error: " .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die(
            "Filter parameters mismatch."
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

if (!$stmt->execute()) {

    die(
        "Execute Error: " .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    $rows[] = $row;

    $totalCost +=
        (float)($row['damage_cost'] ?? 0);
}

$totalRecords = count($rows);

/* =========================================================
   دالة الحصول على مسار الصورة الحقيقي
========================================================= */

function getVehicleImagePath($imageName)
{
    $imageName = trim((string)$imageName);

    if ($imageName === '') {
        return '';
    }

    $imageName = str_replace('\\', '/', $imageName);

    /*
     * تنظيف بداية المسار
     */
    $cleanName = ltrim($imageName, '/');

    /*
     * مجموعة مسارات محتملة
     */
    $possiblePaths = [

        /* fleetimg/img/filename.jpg */
        __DIR__ .
        '/../fleetimg/img/' .
        basename($cleanName),

        /* fleetimg/filename.jpg */
        __DIR__ .
        '/../fleetimg/' .
        basename($cleanName),

        /* الاسم مخزن مع fleetimg/img */
        __DIR__ .
        '/../' .
        $cleanName,

        /* admin/fleetimg/img/filename */
        __DIR__ .
        '/fleetimg/img/' .
        basename($cleanName),

        /* admin/filename */
        __DIR__ .
        '/' .
        basename($cleanName),

    ];

    foreach ($possiblePaths as $path) {

        $realPath = realpath($path);

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
   دالة تحويل الصورة إلى Data URI
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

    $extension =
        strtolower(
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

        'webp' => 'image/webp',

    ];

    if (!isset($mimeTypes[$extension])) {
        return '';
    }

    $mime = $mimeTypes[$extension];

    $content = @file_get_contents($filePath);

    if ($content === false) {
        return '';
    }

    return 'data:' .
        $mime .
        ';base64,' .
        base64_encode($content);
}

/* =========================================================
   معلومات الفلاتر
========================================================= */

$filterItems = [];

/* البحث */

if ($search !== '') {

    $filterItems[] = [

        'label' => $t['search'],

        'value' => $search
    ];
}

/* المركبة */

if ($vehicle_id > 0) {

    $vehicleName = $t['unknown'];

    $vehicleStmt = $con->prepare("
        SELECT plate, model
        FROM fleet
        WHERE id = ?
        LIMIT 1
    ");

    if ($vehicleStmt) {

        $vehicleStmt->bind_param(
            "i",
            $vehicle_id
        );

        $vehicleStmt->execute();

        $vehicleRow =
            $vehicleStmt
                ->get_result()
                ->fetch_assoc();

        if ($vehicleRow) {

            $vehicleName =
                trim(
                    ($vehicleRow['plate'] ?? '') .
                    ' - ' .
                    ($vehicleRow['model'] ?? '')
                );
        }

        $vehicleStmt->close();
    }

    $filterItems[] = [

        'label' => $t['vehicle_filter'],

        'value' => $vehicleName
    ];
}

/* السائق */

if ($driver_id > 0) {

    $driverName = $t['unknown'];

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

        $driverRow =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverRow) {

            $driverName =
                $driverRow['name'];
        }

        $driverStmt->close();
    }

    $filterItems[] = [

        'label' => $t['driver_filter'],

        'value' => $driverName
    ];
}

/* الحالة */

if ($status !== '') {

    $statusText = $status;

    if ($status === 'Open') {

        $statusText = $t['open'];

    } elseif ($status === 'In Progress') {

        $statusText = $t['progress'];

    } elseif ($status === 'Closed') {

        $statusText = $t['closed'];
    }

    $filterItems[] = [

        'label' => $t['status_filter'],

        'value' => $statusText
    ];
}

/* من */

if ($from !== '') {

    $filterItems[] = [

        'label' => $t['from'],

        'value' => $from
    ];
}

/* إلى */

if ($to !== '') {

    $filterItems[] = [

        'label' => $t['to'],

        'value' => $to
    ];
}

/* =========================================================
   إعداد mPDF
========================================================= */

$mpdf = new Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4-L',

    'orientation' => 'L',

    'margin_left' => 7,

    'margin_right' => 7,

    'margin_top' => 10,

    'margin_bottom' => 10,

    'default_font' => 'dejavusans',

]);

$mpdf->SetTitle(
    $t['title']
);

$mpdf->SetAuthor(
    'AlSharqPlatform'
);

/* =========================================================
   HTML
========================================================= */

$direction =
    $lang === 'ar'
        ? 'rtl'
        : 'ltr';

$html = '';

$html .= '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body {

    font-family: dejavusans;

    direction: ' . $direction . ';

    font-size: 9px;

    color: #222;

}

.header {

    text-align: center;

    border-bottom: 2px solid #dc3545;

    padding-bottom: 9px;

    margin-bottom: 12px;

}

.header h1 {

    margin: 0;

    color: #dc3545;

    font-size: 20px;

}

.header h2 {

    margin: 4px 0;

    color: #555;

    font-size: 12px;

}

.header-date {

    color: #777;

    font-size: 8px;

}

.summary {

    width: 100%;

    border-collapse: collapse;

    margin-bottom: 10px;

}

.summary td {

    width: 50%;

    border: 1px solid #ddd;

    padding: 6px;

    background: #f8f9fa;

}

.summary-value {

    font-weight: bold;

    font-size: 13px;

}

.cost {

    color: #198754;

    font-weight: bold;

}

.filters {

    border: 1px solid #ddd;

    background: #fafafa;

    padding: 6px;

    margin-bottom: 10px;

}

.filters-title {

    font-weight: bold;

    margin-bottom: 5px;

}

.filter-item {

    display: inline-block;

    background: #fff;

    border: 1px solid #ddd;

    padding: 3px 6px;

    margin: 2px;

}

.report {

    width: 100%;

    border-collapse: collapse;

}

.report th {

    background: #343a40;

    color: #fff;

    border: 1px solid #222;

    padding: 5px 4px;

    text-align: center;

}

.report td {

    border: 1px solid #ddd;

    padding: 4px;

    vertical-align: middle;

}

.report tr:nth-child(even) td {

    background: #f8f9fa;

}

.vehicle-image {

    width: 75px;

    height: 55px;

    object-fit: contain;

}

.status-open {

    background: #f8d7da;

    color: #842029;

    padding: 3px 6px;

}

.status-progress {

    background: #fff3cd;

    color: #664d03;

    padding: 3px 6px;

}

.status-closed {

    background: #d1e7dd;

    color: #0f5132;

    padding: 3px 6px;

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

    margin-top: 10px;

    border-top: 1px solid #ddd;

    padding-top: 6px;

    text-align: center;

    color: #777;

    font-size: 8px;

}

</style>

</head>

<body>
';

/* =========================================================
   العنوان
========================================================= */

$html .= '

<div class="header">

    <h1>
        ' .
        htmlspecialchars($t['title']) .
        '
    </h1>

    <h2>
        ' .
        htmlspecialchars($t['company']) .
        '
    </h2>

    <div class="header-date">
        ' .
        date('Y-m-d H:i') .
        '
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

    <strong>
        ' .
        htmlspecialchars($t['total_records']) .
        '
    </strong>

    <br>

    <span class="summary-value">
        ' .
        number_format($totalRecords) .
        '
    </span>

</td>

<td>

    <strong>
        ' .
        htmlspecialchars($t['total_cost']) .
        '
    </strong>

    <br>

    <span class="summary-value cost">
        ' .
        number_format(
            $totalCost,
            2
        ) .
        ' ' .
        htmlspecialchars($t['sar']) .
        '
    </span>

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
        htmlspecialchars($t['filters']) .
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
                $t['all']
            ) .
            '

        </span>
    ';
}

$html .= '

</div>
';

/* =========================================================
   الجدول
========================================================= */

$html .= '

<table class="report">

<thead>

<tr>

    <th width="4%">
        ' .
        htmlspecialchars($t['id']) .
        '
    </th>

    <th width="10%">
        ' .
        htmlspecialchars($t['image']) .
        '
    </th>

    <th width="11%">
        ' .
        htmlspecialchars($t['vehicle']) .
        '
    </th>

    <th width="10%">
        ' .
        htmlspecialchars($t['plate']) .
        '
    </th>

    <th width="13%">
        ' .
        htmlspecialchars($t['driver']) .
        '
    </th>

    <th width="11%">
        ' .
        htmlspecialchars($t['date']) .
        '
    </th>

    <th width="11%">
        ' .
        htmlspecialchars($t['location']) .
        '
    </th>

    <th width="10%">
        ' .
        htmlspecialchars($t['cost']) .
        '
    </th>

    <th width="9%">
        ' .
        htmlspecialchars($t['status']) .
        '
    </th>

    <th width="16%">
        ' .
        htmlspecialchars($t['description']) .
        '
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
            colspan="10"
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

        $imageFile =
            getVehicleImagePath(
                $row['imgfleet'] ?? ''
            );

        $imageData =
            imageToDataUri(
                $imageFile
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

        $statusValue =
            $row['status'] ?? '';

        if ($statusValue === 'Open') {

            $statusText =
                $t['open'];

            $statusClass =
                'status-open';

        } elseif ($statusValue === 'In Progress') {

            $statusText =
                $t['progress'];

            $statusClass =
                'status-progress';

        } elseif ($statusValue === 'Closed') {

            $statusText =
                $t['closed'];

            $statusClass =
                'status-closed';

        } else {

            $statusText =
                $statusValue !== ''
                    ? $statusValue
                    : $t['unknown'];

            $statusClass =
                'status-progress';
        }

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

            <td style="text-align:center;">

                ' .
                $imageHtml .
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
                    $row['plate'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['driver_name'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['accident_date'] ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['location'] ?? '-'
                ) .
                '

            </td>

            <td class="cost">

                ' .
                number_format(
                    (float)(
                        $row['damage_cost'] ?? 0
                    ),
                    2
                ) .
                ' ' .
                htmlspecialchars(
                    $t['sar']
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

            <td>

                ' .
                nl2br(
                    htmlspecialchars(
                        $row['description'] ?? '-'
                    )
                ) .
                '

            </td>

        </tr>
        ';
    }

    /* ---------------------------------------------
       إجمالي
    --------------------------------------------- */

    $html .= '

    <tr class="total-row">

        <td colspan="7">

            ' .
            htmlspecialchars(
                $t['total_cost']
            ) .
            '

        </td>

        <td class="cost">

            ' .
            number_format(
                $totalCost,
                2
            ) .
            ' ' .
            htmlspecialchars(
                $t['sar']
            ) .
            '

        </td>

        <td colspan="2"></td>

    </tr>
    ';
}

$html .= '

</tbody>

</table>

<div class="footer">

    ' .
    htmlspecialchars(
        $t['company']
    ) .
    ' - ' .
    date('Y') .
    '

</div>

</body>

</html>
';

/* =========================================================
   إنشاء PDF
========================================================= */

$mpdf->WriteHTML($html);

/* =========================================================
   اسم الملف
========================================================= */

$filename =
    'accidents_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

/* =========================================================
   الإخراج
========================================================= */

$mpdf->Output(
    $filename,
    'I'
);

$stmt->close();

exit;