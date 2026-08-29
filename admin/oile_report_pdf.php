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
   الفلاتر - نفس oile_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$date_filter = $_GET['date_filter'] ?? 'all';

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$oil_type = trim($_GET['oil_type'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'          => 'تقرير تغيير الزيت',

        'company_report' => 'تقرير تغيير زيت المركبات',

        'id'             => '#',

        'plate'          => 'رقم اللوحة',

        'model'          => 'الموديل',

        'driver'         => 'السائق',

        'oil_type'       => 'نوع الزيت',

        'date'            => 'تاريخ التغيير',

        'km_change'      => 'عداد التغيير',

        'current_km'     => 'العداد الحالي',

        'next_km'        => 'العداد القادم',

        'next_change'    => 'التغيير القادم',

        'cost'            => 'التكلفة',

        'notes'           => 'الملاحظات',

        'status'          => 'الحالة',

        'good'            => 'ممتاز',

        'soon'            => 'قريب',

        'late'            => 'متأخر',

        'filters'         => 'الفلاتر المستخدمة',

        'search_filter'   => 'البحث',

        'plate_filter'    => 'اللوحة',

        'driver_filter'   => 'السائق',

        'oil_filter'      => 'نوع الزيت',

        'period_filter'   => 'الفترة',

        'from_filter'     => 'من',

        'to_filter'       => 'إلى',

        'all'             => 'الكل',

        'week'            => 'آخر 7 أيام',

        'month'           => 'آخر 30 يوم',

        'custom'          => 'مخصص',

        'total'            => 'إجمالي السجلات',

        'total_cost'       => 'إجمالي التكلفة',

        'average_cost'     => 'متوسط التكلفة',

        'vehicles'         => 'المركبات',

        'overdue'          => 'متأخر',

        'urgent'           => 'قريب جداً',

        'good_total'       => 'ممتاز',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'              => 'ريال',

        'generated_at'     => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'          => 'Oil Change Report',

        'company_report' => 'Vehicle Oil Change Report',

        'id'             => '#',

        'plate'          => 'Plate',

        'model'          => 'Model',

        'driver'         => 'Driver',

        'oil_type'       => 'Oil Type',

        'date'            => 'Change Date',

        'km_change'      => 'Change KM',

        'current_km'     => 'Current KM',

        'next_km'        => 'Next KM',

        'next_change'    => 'Next Change',

        'cost'            => 'Cost',

        'notes'           => 'Notes',

        'status'          => 'Status',

        'good'            => 'Good',

        'soon'            => 'Soon',

        'late'            => 'Overdue',

        'filters'         => 'Applied Filters',

        'search_filter'   => 'Search',

        'plate_filter'    => 'Plate',

        'driver_filter'   => 'Driver',

        'oil_filter'      => 'Oil Type',

        'period_filter'   => 'Period',

        'from_filter'     => 'From',

        'to_filter'       => 'To',

        'all'             => 'All',

        'week'            => 'Last 7 Days',

        'month'           => 'Last 30 Days',

        'custom'          => 'Custom',

        'total'            => 'Total Records',

        'total_cost'       => 'Total Cost',

        'average_cost'     => 'Average Cost',

        'vehicles'         => 'Vehicles',

        'overdue'          => 'Overdue',

        'urgent'           => 'Urgent',

        'good_total'       => 'Good',

        'no_data'          => 'No records match the selected filters',

        'sar'              => 'SAR',

        'generated_at'     => 'Generated At'

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء WHERE
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
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR o.driver LIKE ?
            OR o.oil_type LIKE ?
            OR o.notes LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "ssssss";
}

/* =========================================================
   اللوحة
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
   السائق
========================================================= */

if ($driver !== '') {

    $where .= "
        AND (
            d.name LIKE ?
            OR o.driver LIKE ?
        )
    ";

    $driverValue =
        '%' . $driver . '%';

    $params[] = $driverValue;
    $params[] = $driverValue;

    $types .= "ss";
}

/* =========================================================
   نوع الزيت
========================================================= */

if ($oil_type !== '') {

    $where .= "
        AND o.oil_type LIKE ?
    ";

    $params[] =
        '%' . $oil_type . '%';

    $types .= "s";
}

/* =========================================================
   الفترة
========================================================= */

switch ($date_filter) {

    case 'week':

        $where .= "
            AND o.change_date >=
            DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ";

        break;

    case 'month':

        $where .= "
            AND o.change_date >=
            DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ";

        break;

    case 'custom':

        if (
            $from !== '' &&
            $to !== ''
        ) {

            $where .= "
                AND DATE(o.change_date)
                BETWEEN ? AND ?
            ";

            $params[] = $from;
            $params[] = $to;

            $types .= "ss";
        }

        break;
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        o.id,
        o.car_id,
        o.driver_id,
        o.driver,
        o.oil_type,
        o.change_date,
        o.next_change,
        o.km_change,
        o.current_km,
        o.next_km,
        o.cost,
        o.notes,

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            NULLIF(o.driver, ''),
            '-'
        ) AS driver_name

    FROM oil_changes o

    LEFT JOIN fleet f
        ON f.id = o.car_id

    LEFT JOIN drivers d
        ON d.id = o.driver_id

    $where

    ORDER BY
        o.change_date DESC,
        o.id DESC
";

/* =========================================================
   Prepare
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   Bind
========================================================= */

if (!empty($params)) {

    if (
        strlen($types) !==
        count($params)
    ) {

        die(
            'Filter parameters mismatch.'
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
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result =
    $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

$vehicleIds = [];

$overdue = 0;

$urgent = 0;

$good = 0;

while (
    $row =
    $result->fetch_assoc()
) {

    $cost =
        (float)(
            $row['cost'] ?? 0
        );

    $totalCost += $cost;

    if (!empty($row['car_id'])) {

        $vehicleIds[
            $row['car_id']
        ] = true;
    }

    $currentKm =
        (int)(
            $row['current_km']
            ??
            $row['km_change']
            ??
            0
        );

    $nextKm =
        (int)(
            $row['next_km']
            ??
            0
        );

    /* حالة التغيير */

    if ($nextKm > 0) {

        $remainingKm =
            $nextKm -
            $currentKm;

        if ($remainingKm <= 0) {

            $row['status_text'] =
                $t['late'];

            $row['status_class'] =
                'status-late';

            $overdue++;

        } elseif (
            $remainingKm <= 1000
        ) {

            $row['status_text'] =
                $t['soon'];

            $row['status_class'] =
                'status-soon';

            $urgent++;

        } else {

            $row['status_text'] =
                $t['good'];

            $row['status_class'] =
                'status-good';

            $good++;
        }

    } else {

        $row['status_text'] =
            $t['good'];

        $row['status_class'] =
            'status-good';

        $good++;
    }

    $rows[] = $row;
}

$totalRecords =
    count($rows);

$totalVehicles =
    count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost /
          $totalRecords
        : 0;

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
    ??
    'AlSharqPlatform';

/* =========================================================
   mPDF
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
        9,

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
   Header / Footer
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
        htmlspecialchars($companyName) .
        '
    </div>
    '
);

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
   الاتجاه
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

    direction: ' .
    $direction .
    ';

    font-size: 8px;

    color: #222;

}

.header {

    text-align: center;

    border-bottom: 2px solid #0d6efd;

    padding-bottom: 8px;

    margin-bottom: 9px;

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

    font-size: 7.5px;

}

.summary {

    width: 100%;

    border-collapse: collapse;

    margin-bottom: 9px;

}

.summary td {

    width: 20%;

    border: 1px solid #ddd;

    background: #f8f9fa;

    padding: 5px;

    text-align: center;

}

.summary-label {

    font-size: 7.5px;

    color: #666;

}

.summary-value {

    font-size: 11px;

    font-weight: bold;

}

.summary-good {

    color: #198754;

}

.summary-warning {

    color: #d97706;

}

.summary-danger {

    color: #dc3545;

}

.filters {

    border: 1px solid #ddd;

    background: #fafafa;

    padding: 5px;

    margin-bottom: 9px;

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

    font-size: 7.5px;

}

.report {

    width: 100%;

    border-collapse: collapse;

}

.report th {

    background: #343a40;

    color: #fff;

    border: 1px solid #222;

    padding: 4px 2px;

    text-align: center;

    font-size: 7px;

}

.report td {

    border: 1px solid #ddd;

    padding: 3px 2px;

    text-align: center;

    vertical-align: middle;

    font-size: 7px;

}

.report tr:nth-child(even) td {

    background: #f8f9fa;

}

.cost-cell {

    color: #198754;

    font-weight: bold;

}

.status-good {

    background: #d1e7dd;

    color: #0f5132;

    padding: 2px 4px;

}

.status-soon {

    background: #fff3cd;

    color: #664d03;

    padding: 2px 4px;

}

.status-late {

    background: #f8d7da;

    color: #842029;

    padding: 2px 4px;

}

.total-row td {

    background: #e9ecef !important;

    font-weight: bold;

}

.no-data {

    text-align: center;

    padding: 20px;

    color: #777;

}

</style>
';

/* =========================================================
   CSS
========================================================= */

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
            ':
            ' .
            date('Y-m-d H:i')
            . '

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
                        $t['total']
                    ) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format(
                        $totalRecords
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_cost']
                    ) .
                    '
                </div>

                <div class="summary-value summary-good">
                    ' .
                    number_format(
                        $totalCost,
                        2
                    ) .
                    '
                    ' .
                    htmlspecialchars(
                        $t['sar']
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['average_cost']
                    ) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format(
                        $averageCost,
                        2
                    ) .
                    '
                    ' .
                    htmlspecialchars(
                        $t['sar']
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['vehicles']
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
                        $t['overdue']
                    ) .
                    '
                </div>

                <div class="summary-value summary-danger">
                    ' .
                    number_format(
                        $overdue
                    ) .
                    '
                </div>

            </td>

        </tr>

    </table>
    '
);

/* =========================================================
   الفلاتر
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

if ($plate !== '') {

    $filterItems[] = [

        'label' =>
            $t['plate_filter'],

        'value' =>
            $plate
    ];
}

if ($driver !== '') {

    $filterItems[] = [

        'label' =>
            $t['driver_filter'],

        'value' =>
            $driver
    ];
}

if ($oil_type !== '') {

    $filterItems[] = [

        'label' =>
            $t['oil_filter'],

        'value' =>
            $oil_type
    ];
}

if ($date_filter !== 'all') {

    $periodLabel =
        $date_filter;

    if ($date_filter === 'week') {

        $periodLabel =
            $t['week'];

    } elseif ($date_filter === 'month') {

        $periodLabel =
            $t['month'];

    } elseif ($date_filter === 'custom') {

        $periodLabel =
            $t['custom'];
    }

    $filterItems[] = [

        'label' =>
            $t['period_filter'],

        'value' =>
            $periodLabel
    ];
}

if ($from !== '') {

    $filterItems[] = [

        'label' =>
            $t['from_filter'],

        'value' =>
            $from
    ];
}

if ($to !== '') {

    $filterItems[] = [

        'label' =>
            $t['to_filter'],

        'value' =>
            $to
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
                $t['all']
            ) .
            '

        </span>
    ';
}

$filtersHtml .= '</div>';

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
                    htmlspecialchars(
                        $t['id']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['plate']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['model']
                    ) .
                    '
                </th>

                <th width="12%">
                    ' .
                    htmlspecialchars(
                        $t['driver']
                    ) .
                    '
                </th>

                <th width="10%">
                    ' .
                    htmlspecialchars(
                        $t['oil_type']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['date']
                    ) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars(
                        $t['km_change']
                    ) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars(
                        $t['current_km']
                    ) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars(
                        $t['next_km']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['next_change']
                    ) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars(
                        $t['cost']
                    ) .
                    '
                </th>

                <th width="7%">
                    ' .
                    htmlspecialchars(
                        $t['status']
                    ) .
                    '
                </th>

                <th width="12%">
                    ' .
                    htmlspecialchars(
                        $t['notes']
                    ) .
                    '
                </th>

            </tr>

        </thead>

        <tbody>
    '
);

/* =========================================================
   البيانات - كل صف مستقل لتجنب pcre.backtrack_limit
========================================================= */

if (empty($rows)) {

    $mpdf->WriteHTML(
        '
        <tr>

            <td colspan="13" class="no-data">

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

        $rowHtml = '

        <tr>

            <td>
                #' .
                $counter .
                '
            </td>

            <td>
                <strong>
                    ' .
                    htmlspecialchars(
                        $row['plate']
                        ?? '-'
                    ) .
                    '
                </strong>
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['model']
                    ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['driver_name']
                    ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['oil_type']
                    ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['change_date']
                    ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                number_format(
                    (int)(
                        $row['km_change']
                        ?? 0
                    )
                ) .
                '
            </td>

            <td>
                ' .
                number_format(
                    (int)(
                        $row['current_km']
                        ??
                        $row['km_change']
                        ??
                        0
                    )
                ) .
                '
            </td>

            <td>
                ' .
                number_format(
                    (int)(
                        $row['next_km']
                        ?? 0
                    )
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['next_change']
                    ?? '-'
                ) .
                '
            </td>

            <td class="cost-cell">
                ' .
                number_format(
                    (float)(
                        $row['cost']
                        ?? 0
                    ),
                    2
                ) .
                '
                ' .
                htmlspecialchars(
                    $t['sar']
                ) .
                '
            </td>

            <td>

                <span class="' .
                    htmlspecialchars(
                        $row['status_class']
                        ?? 'status-good'
                    ) .
                '">

                    ' .
                    htmlspecialchars(
                        $row['status_text']
                        ?? $t['good']
                    ) .
                    '

                </span>

            </td>

            <td>
                ' .
                nl2br(
                    htmlspecialchars(
                        $row['notes']
                        ?? '-'
                    )
                ) .
                '
            </td>

        </tr>
        ';

        $mpdf->WriteHTML(
            $rowHtml
        );

        $counter++;
    }

    /* =====================================================
       صف الإجمالي
    ===================================================== */

    $mpdf->WriteHTML(
        '
        <tr class="total-row">

            <td colspan="10">

                ' .
                htmlspecialchars(
                    $t['total_cost']
                ) .
                '

            </td>

            <td class="cost-cell">

                ' .
                number_format(
                    $totalCost,
                    2
                ) .
                '
                ' .
                htmlspecialchars(
                    $t['sar']
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $t['average_cost']
                ) .
                ':
                ' .
                number_format(
                    $averageCost,
                    2
                ) .
                '

            </td>

            <td></td>

        </tr>
        '
    );
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
    'oile_report_' .
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