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
   الفلاتر
========================================================= */

$search = trim($_GET['search'] ?? '');

$plate = trim($_GET['plate'] ?? '');

$driver = trim($_GET['driver'] ?? '');

$vehicle = trim($_GET['vehicle'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'           => 'تقرير الصيانة',

        'company_report'  => 'تقرير صيانة المركبات',

        'id'              => '#',

        'vehicle'         => 'المركبة',

        'plate'           => 'رقم اللوحة',

        'driver'          => 'السائق',

        'maintenance'     => 'نوع الصيانة',

        'cost'            => 'التكلفة',

        'date'             => 'التاريخ',

        'filters'         => 'الفلاتر المستخدمة',

        'search_filter'   => 'البحث',

        'plate_filter'    => 'اللوحة',

        'driver_filter'   => 'السائق',

        'vehicle_filter'  => 'المركبة',

        'from_filter'     => 'من',

        'to_filter'       => 'إلى',

        'all_records'     => 'جميع السجلات',

        'total_records'   => 'إجمالي سجلات الصيانة',

        'total_cost'      => 'إجمالي التكلفة',

        'average_cost'    => 'متوسط التكلفة',

        'no_data'         => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'             => 'ريال',

        'generated_at'    => 'تاريخ إنشاء التقرير',

        'page'            => 'صفحة'

    ],

    'en' => [

        'title'           => 'Maintenance Report',

        'company_report'  => 'Vehicle Maintenance Report',

        'id'              => '#',

        'vehicle'         => 'Vehicle',

        'plate'           => 'Plate Number',

        'driver'          => 'Driver',

        'maintenance'     => 'Maintenance Type',

        'cost'            => 'Cost',

        'date'             => 'Date',

        'filters'         => 'Applied Filters',

        'search_filter'   => 'Search',

        'plate_filter'    => 'Plate',

        'driver_filter'   => 'Driver',

        'vehicle_filter'  => 'Vehicle',

        'from_filter'     => 'From',

        'to_filter'       => 'To',

        'all_records'     => 'All Records',

        'total_records'   => 'Total Maintenance Records',

        'total_cost'      => 'Total Cost',

        'average_cost'    => 'Average Cost',

        'no_data'         => 'No maintenance records match the selected filters',

        'sar'             => 'SAR',

        'generated_at'    => 'Generated At',

        'page'            => 'Page'

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
            plate_number LIKE ?
            OR driver LIKE ?
            OR vehicle_name LIKE ?
            OR maintenance_type LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}

/* =========================================================
   اللوحة
========================================================= */

if ($plate !== '') {

    $where .= "
        AND plate_number LIKE ?
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
        AND driver LIKE ?
    ";

    $params[] =
        '%' . $driver . '%';

    $types .= "s";
}

/* =========================================================
   المركبة
========================================================= */

if ($vehicle !== '') {

    $where .= "
        AND vehicle_name LIKE ?
    ";

    $params[] =
        '%' . $vehicle . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(maintenance_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(maintenance_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        id,
        vehicle_name,
        plate_number,
        driver,
        maintenance_type,
        cost,
        maintenance_date

    FROM maintenance

    $where

    ORDER BY
        maintenance_date DESC,
        id DESC

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
        strlen($types) !== count($params)
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

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)($row['cost'] ?? 0);

    $totalCost += $cost;

    $rows[] = $row;
}

$totalRecords = count($rows);

$averageCost =
    $totalRecords > 0
        ? $totalCost / $totalRecords
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
    ?? 'AlSharqPlatform';

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
   اتجاه الصفحة
========================================================= */

$direction =
    $lang === 'ar'
        ? 'rtl'
        : 'ltr';

/* =========================================================
   HTML CSS
========================================================= */

$css = '
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

    border-bottom: 2px solid #f59e0b;

    padding-bottom: 8px;

    margin-bottom: 10px;

}

.header h1 {

    margin: 0;

    color: #d97706;

    font-size: 19px;

}

.header h2 {

    margin: 4px 0;

    color: #555;

    font-size: 11px;

}

.generated {

    color: #777;

    font-size: 8px;

}

.summary {

    width: 100%;

    border-collapse: collapse;

    margin-bottom: 10px;

}

.summary td {

    width: 33.33%;

    border: 1px solid #ddd;

    background: #f8f9fa;

    padding: 7px;

    text-align: center;

}

.summary-label {

    color: #666;

    font-size: 8px;

}

.summary-value {

    font-weight: bold;

    font-size: 13px;

}

.total {

    color: #198754;

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

    border: 1px solid #ddd;

    background: #fff;

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

    padding: 6px 4px;

    text-align: center;

    font-size: 8px;

}

.report td {

    border: 1px solid #ddd;

    padding: 5px 4px;

    text-align: center;

    vertical-align: middle;

    font-size: 8px;

}

.report tr:nth-child(even) td {

    background: #f8f9fa;

}

.cost-cell {

    color: #198754;

    font-weight: bold;

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
                        $t['total_records']
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

                <div class="summary-value total">

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

        </tr>

    </table>
    '
);

/* =========================================================
   الفلاتر المستخدمة
========================================================= */

$filterItems = [];

/* البحث */

if ($search !== '') {

    $filterItems[] = [

        'label' =>
            $t['search_filter'],

        'value' =>
            $search

    ];
}

/* اللوحة */

if ($plate !== '') {

    $filterItems[] = [

        'label' =>
            $t['plate_filter'],

        'value' =>
            $plate

    ];
}

/* السائق */

if ($driver !== '') {

    $filterItems[] = [

        'label' =>
            $t['driver_filter'],

        'value' =>
            $driver

    ];
}

/* المركبة */

if ($vehicle !== '') {

    $filterItems[] = [

        'label' =>
            $t['vehicle_filter'],

        'value' =>
            $vehicle

    ];
}

/* من */

if ($from !== '') {

    $filterItems[] = [

        'label' =>
            $t['from_filter'],

        'value' =>
            $from

    ];
}

/* إلى */

if ($to !== '') {

    $filterItems[] = [

        'label' =>
            $t['to_filter'],

        'value' =>
            $to

    ];
}

/* =========================================================
   HTML الفلاتر
========================================================= */

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

/* =========================================================
   الفلاتر
========================================================= */

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

                <th width="5%">
                    ' .
                    htmlspecialchars(
                        $t['id']
                    ) .
                    '
                </th>

                <th width="20%">
                    ' .
                    htmlspecialchars(
                        $t['vehicle']
                    ) .
                    '
                </th>

                <th width="13%">
                    ' .
                    htmlspecialchars(
                        $t['plate']
                    ) .
                    '
                </th>

                <th width="18%">
                    ' .
                    htmlspecialchars(
                        $t['driver']
                    ) .
                    '
                </th>

                <th width="20%">
                    ' .
                    htmlspecialchars(
                        $t['maintenance']
                    ) .
                    '
                </th>

                <th width="12%">
                    ' .
                    htmlspecialchars(
                        $t['cost']
                    ) .
                    '
                </th>

                <th width="12%">
                    ' .
                    htmlspecialchars(
                        $t['date']
                    ) .
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
                colspan="7"
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

    foreach ($rows as $row) {

        /*
         * إرسال كل صف منفصل إلى mPDF
         * لمنع مشكلة pcre.backtrack_limit
         */

        $rowHtml = '

            <tr>

                <td>

                    #' .
                    $counter .
                    '

                </td>

                <td>

                    ' .
                    htmlspecialchars(
                        $row['vehicle_name'] ?? '-'
                    ) .
                    '

                </td>

                <td>

                    <strong>

                        ' .
                        htmlspecialchars(
                            $row['plate_number'] ?? '-'
                        ) .
                        '

                    </strong>

                </td>

                <td>

                    ' .
                    htmlspecialchars(
                        $row['driver'] ?? '-'
                    ) .
                    '

                </td>

                <td>

                    ' .
                    htmlspecialchars(
                        $row['maintenance_type'] ?? '-'
                    ) .
                    '

                </td>

                <td class="cost-cell">

                    ' .
                    number_format(
                        (float)(
                            $row['cost'] ?? 0
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

                    ' .
                    htmlspecialchars(
                        $row['maintenance_date'] ?? '-'
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
       الإجمالي
    ===================================================== */

    $mpdf->WriteHTML(
        '
        <tr class="total-row">

            <td colspan="5">

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
    'reportmaintenance_' .
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