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
   الفلاتر - نفس tires_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$car_id = (int)($_GET['car_id'] ?? 0);

$driver_id = (int)($_GET['driver_id'] ?? 0);

$tire_type = trim($_GET['tire_type'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'            => 'تقرير الإطارات',
        'company_report'   => 'تقرير إطارات المركبات',

        'id'               => '#',
        'vehicle'          => 'المركبة',
        'plate'            => 'رقم اللوحة',
        'model'            => 'الموديل',
        'driver'           => 'السائق',
        'type'             => 'نوع الإطار',
        'change_date'      => 'تاريخ التركيب',
        'next_change'      => 'التغيير القادم',
        'current_km'       => 'العداد الحالي',
        'next_km'          => 'العداد القادم',
        'remaining'        => 'المتبقي',
        'cost'             => 'التكلفة',
        'notes'            => 'الملاحظات',
        'status'           => 'الحالة',

        'good'             => 'ممتاز',
        'soon'             => 'قريب',
        'late'             => 'متأخر',
        'expired'          => 'منتهي',
        'day'              => 'يوم',

        'filters'          => 'الفلاتر المستخدمة',
        'search_filter'    => 'البحث',
        'car_filter'       => 'المركبة',
        'driver_filter'    => 'السائق',
        'type_filter'      => 'نوع الإطار',
        'from_filter'      => 'من',
        'to_filter'        => 'إلى',
        'all_records'      => 'جميع السجلات',

        'total_records'    => 'إجمالي السجلات',
        'total_cost'       => 'إجمالي التكلفة',
        'average_cost'     => 'متوسط التكلفة',
        'total_cars'       => 'عدد المركبات',

        'good_total'       => 'ممتاز',
        'soon_total'       => 'قريب',
        'late_total'       => 'متأخر',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'              => 'ريال',

        'generated_at'     => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'            => 'Tire Report',
        'company_report'   => 'Vehicle Tire Report',

        'id'               => '#',
        'vehicle'          => 'Vehicle',
        'plate'            => 'Plate Number',
        'model'            => 'Model',
        'driver'           => 'Driver',
        'type'             => 'Tire Type',
        'change_date'      => 'Install Date',
        'next_change'      => 'Next Change',
        'current_km'       => 'Current KM',
        'next_km'          => 'Next KM',
        'remaining'        => 'Remaining',
        'cost'             => 'Cost',
        'notes'            => 'Notes',
        'status'           => 'Status',

        'good'             => 'Good',
        'soon'             => 'Soon',
        'late'             => 'Overdue',
        'expired'          => 'Expired',
        'day'              => 'Days',

        'filters'          => 'Applied Filters',
        'search_filter'    => 'Search',
        'car_filter'       => 'Vehicle',
        'driver_filter'    => 'Driver',
        'type_filter'      => 'Tire Type',
        'from_filter'      => 'From',
        'to_filter'        => 'To',
        'all_records'      => 'All Records',

        'total_records'    => 'Total Records',
        'total_cost'       => 'Total Cost',
        'average_cost'     => 'Average Cost',
        'total_cars'       => 'Vehicles',

        'good_total'       => 'Good',
        'soon_total'       => 'Soon',
        'late_total'       => 'Overdue',

        'no_data'          => 'No tire records match the selected filters',

        'sar'              => 'SAR',

        'generated_at'     => 'Generated At'

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
            f.plate LIKE ?
            OR f.model LIKE ?
            OR d.name LIKE ?
            OR t.tire_type LIKE ?
            OR t.notes LIKE ?
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
   المركبة
========================================================= */

if ($car_id > 0) {

    $where .= "
        AND t.car_id = ?
    ";

    $params[] = $car_id;

    $types .= "i";
}

/* =========================================================
   السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND t.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   نوع الإطار
========================================================= */

if ($tire_type !== '') {

    $where .= "
        AND t.tire_type LIKE ?
    ";

    $params[] =
        '%' . $tire_type . '%';

    $types .= "s";
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND DATE(t.change_date) >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND DATE(t.change_date) <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   SQL
========================================================= */

$sql = "

    SELECT

        t.id,
        t.car_id,
        t.driver_id,
        t.tire_type,
        t.change_date,
        t.next_change,
        t.current_km,
        t.next_km,
        t.cost,
        t.notes,

        f.plate,
        f.model,

        COALESCE(
            NULLIF(d.name, ''),
            '-'
        ) AS driver_name

    FROM tires t

    LEFT JOIN fleet f
        ON f.id = t.car_id

    LEFT JOIN drivers d
        ON d.id = t.driver_id

    $where

    ORDER BY
        t.change_date DESC,
        t.id DESC
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

$vehicleIds = [];

$good = 0;

$soon = 0;

$late = 0;

while ($row = $result->fetch_assoc()) {

    $cost =
        (float)($row['cost'] ?? 0);

    $totalCost += $cost;

    if (!empty($row['car_id'])) {
        $vehicleIds[$row['car_id']] = true;
    }

    /* ---------------------------------------------
       حساب الحالة
    --------------------------------------------- */

    $days = null;

    $statusText = $t['good'];

    $statusClass = 'status-good';

    if (!empty($row['next_change'])) {

        $nextDate = strtotime(
            $row['next_change']
        );

        if ($nextDate !== false) {

            $days = (int)ceil(
                (
                    $nextDate -
                    strtotime(date('Y-m-d'))
                ) / 86400
            );

            if ($days < 0) {

                $statusText = $t['late'];

                $statusClass = 'status-late';

                $late++;

            } elseif ($days <= 30) {

                $statusText = $t['soon'];

                $statusClass = 'status-soon';

                $soon++;

            } else {

                $statusText = $t['good'];

                $statusClass = 'status-good';

                $good++;
            }
        }

    } else {

        /*
         * في حالة عدم وجود تاريخ قادم
         * نستخدم العداد
         */

        $currentKm =
            (int)($row['current_km'] ?? 0);

        $nextKm =
            (int)($row['next_km'] ?? 0);

        if ($nextKm > 0) {

            $remainingKm =
                $nextKm - $currentKm;

            if ($remainingKm <= 0) {

                $statusText = $t['late'];

                $statusClass = 'status-late';

                $late++;

            } elseif ($remainingKm <= 1000) {

                $statusText = $t['soon'];

                $statusClass = 'status-soon';

                $soon++;

            } else {

                $statusText = $t['good'];

                $statusClass = 'status-good';

                $good++;
            }

        } else {

            $good++;
        }
    }

    $row['days'] = $days;

    $row['status_text'] = $statusText;

    $row['status_class'] = $statusClass;

    $rows[] = $row;
}

$totalRecords = count($rows);

$totalCars = count($vehicleIds);

$averageCost =
    $totalRecords > 0
        ? $totalCost / $totalRecords
        : 0;

/* =========================================================
   معلومات الفلاتر
========================================================= */

$selectedCarPlate = '';

if ($car_id > 0) {

    $carStmt = $con->prepare("
        SELECT plate
        FROM fleet
        WHERE id = ?
        LIMIT 1
    ");

    if ($carStmt) {

        $carStmt->bind_param(
            "i",
            $car_id
        );

        $carStmt->execute();

        $carRow =
            $carStmt
                ->get_result()
                ->fetch_assoc();

        if ($carRow) {

            $selectedCarPlate =
                $carRow['plate'];
        }

        $carStmt->close();
    }
}

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

        $driverRow =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverRow) {

            $selectedDriverName =
                $driverRow['name'];
        }

        $driverStmt->close();
    }
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

    while ($setting = $settingsResult->fetch_assoc()) {

        $settingsData[
            $setting['setting_key']
        ] = $setting['setting_value'];
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

    'margin_left' => 6,

    'margin_right' => 6,

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
    font-size: 8px;
    color: #222;
}

.header {
    text-align: center;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 8px;
    margin-bottom: 9px;
}

.header h1 {
    margin: 0;
    color: #dc3545;
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
            htmlspecialchars($t['title']) .
            '
        </h1>

        <h2>
            ' .
            htmlspecialchars($companyName) .
            '
        </h2>

        <div class="generated">
            ' .
            htmlspecialchars($t['generated_at']) .
            ':
            ' .
            date('Y-m-d H:i') .
            '
        </div>

    </div>
    '
);

/* =========================================================
   ملخص
========================================================= */

$mpdf->WriteHTML(
    '
    <table class="summary">

        <tr>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars($t['total_records']) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format($totalRecords) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars($t['total_cost']) .
                    '
                </div>

                <div class="summary-value summary-good">
                    ' .
                    number_format($totalCost, 2) .
                    ' '
                    .
                    htmlspecialchars($t['sar']) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars($t['average_cost']) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format($averageCost, 2) .
                    ' '
                    .
                    htmlspecialchars($t['sar']) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars($t['total_cars']) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format($totalCars) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars($t['late_total']) .
                    '
                </div>

                <div class="summary-value summary-danger">
                    ' .
                    number_format($late) .
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
        'label' => $t['search_filter'],
        'value' => $search
    ];
}

if ($selectedCarPlate !== '') {

    $filterItems[] = [
        'label' => $t['car_filter'],
        'value' => $selectedCarPlate
    ];
}

if ($selectedDriverName !== '') {

    $filterItems[] = [
        'label' => $t['driver_filter'],
        'value' => $selectedDriverName
    ];
}

if ($tire_type !== '') {

    $filterItems[] = [
        'label' => $t['type_filter'],
        'value' => $tire_type
    ];
}

if ($from !== '') {

    $filterItems[] = [
        'label' => $t['from_filter'],
        'value' => $from
    ];
}

if ($to !== '') {

    $filterItems[] = [
        'label' => $t['to_filter'],
        'value' => $to
    ];
}

$filtersHtml = '

<div class="filters">

    <div class="filters-title">
        ' .
        htmlspecialchars($t['filters']) .
        '
    </div>
';

if (!empty($filterItems)) {

    foreach ($filterItems as $item) {

        $filtersHtml .= '

        <span class="filter-item">

            <strong>
                ' .
                htmlspecialchars($item['label']) .
                ':
            </strong>

            ' .
            htmlspecialchars($item['value']) .
            '

        </span>
        ';
    }

} else {

    $filtersHtml .= '

        <span class="filter-item">

            ' .
            htmlspecialchars($t['all_records']) .
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
                    ' . htmlspecialchars($t['id']) . '
                </th>

                <th width="10%">
                    ' . htmlspecialchars($t['plate']) . '
                </th>

                <th width="9%">
                    ' . htmlspecialchars($t['model']) . '
                </th>

                <th width="12%">
                    ' . htmlspecialchars($t['driver']) . '
                </th>

                <th width="10%">
                    ' . htmlspecialchars($t['type']) . '
                </th>

                <th width="9%">
                    ' . htmlspecialchars($t['change_date']) . '
                </th>

                <th width="8%">
                    ' . htmlspecialchars($t['current_km']) . '
                </th>

                <th width="8%">
                    ' . htmlspecialchars($t['next_km']) . '
                </th>

                <th width="8%">
                    ' . htmlspecialchars($t['remaining']) . '
                </th>

                <th width="9%">
                    ' . htmlspecialchars($t['next_change']) . '
                </th>

                <th width="8%">
                    ' . htmlspecialchars($t['cost']) . '
                </th>

                <th width="7%">
                    ' . htmlspecialchars($t['status']) . '
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
                colspan="12"
                class="no-data"
            >

                ' .
                htmlspecialchars($t['no_data']) .
                '

            </td>

        </tr>
        '
    );

} else {

    $counter = 1;

    foreach ($rows as $row) {

        $currentKm =
            (int)($row['current_km'] ?? 0);

        $nextKm =
            (int)($row['next_km'] ?? 0);

        $remainingKm =
            $nextKm > 0
                ? $nextKm - $currentKm
                : null;

        if ($row['days'] !== null) {

            $remainingText =
                $row['days'] < 0
                    ? $t['expired']
                    : number_format($row['days']) .
                      ' ' .
                      $t['day'];

        } elseif ($remainingKm !== null) {

            $remainingText =
                number_format($remainingKm) .
                ' KM';

        } else {

            $remainingText = '-';
        }

        $rowHtml = '

        <tr>

            <td>
                #' . $counter . '
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
                    $row['model'] ?? '-'
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
                    $row['tire_type'] ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['change_date'] ?? '-'
                ) .
                '
            </td>

            <td>
                ' .
                number_format(
                    $currentKm
                ) .
                '
            </td>

            <td>
                ' .
                number_format(
                    $nextKm
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $remainingText
                ) .
                '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $row['next_change'] ?? '-'
                ) .
                '
            </td>

            <td class="cost-cell">
                ' .
                number_format(
                    (float)($row['cost'] ?? 0),
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
                    ) .
                '">

                    ' .
                    htmlspecialchars(
                        $row['status_text']
                    ) .
                    '

                </span>

            </td>

        </tr>
        ';

        /*
         * كل صف في WriteHTML منفصل
         * لتجنب pcre.backtrack_limit
         */

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
    'tires_report_' .
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