
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   رقم السائق
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {

    die(
        $lang === 'ar'
            ? 'رقم السائق غير صحيح'
            : 'Invalid driver ID'
    );
}

/* =========================================================
   الفلاتر
========================================================= */

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'company'       => 'شركة الشرق لخدمات السيارات',
        'title'         => 'الملف المالي والتشغيلي للسائق',

        'driver'        => 'السائق',
        'plate'         => 'رقم اللوحة',
        'national_id'   => 'رقم الهوية',
        'phone'         => 'الجوال',
        'work_area'     => 'منطقة العمل',
        'truck_type'    => 'نوع السطحة',

        'period'        => 'الفترة',
        'all_period'    => 'كل الفترات',
        'report_date'   => 'تاريخ التقرير',

        'summary'       => 'ملخص التكاليف',

        'oil'           => 'الزيت',
        'tires'         => 'الإطارات',
        'maintenance'   => 'الصيانة',
        'grand_total'   => 'الإجمالي الكلي',

        'oil_records'   => 'سجل تغيير الزيت',
        'tire_records'  => 'سجل الإطارات',
        'maint_records' => 'سجل الصيانة',

        'date'          => 'التاريخ',
        'type'          => 'النوع',
        'km'            => 'العداد الحالي',
        'next_km'       => 'العداد القادم',
        'next_change'   => 'التغيير القادم',
        'vehicle'       => 'المركبة',
        'cost'          => 'التكلفة',
        'notes'         => 'الملاحظات',

        'no_data'       => 'لا توجد سجلات',

        'sar'           => 'ريال'
    ],

    'en' => [

        'company'       => 'Al Sharq Automotive Services Company',
        'title'         => 'Driver Financial & Operational Report',

        'driver'        => 'Driver',
        'plate'         => 'Plate Number',
        'national_id'   => 'National ID',
        'phone'         => 'Phone',
        'work_area'     => 'Work Area',
        'truck_type'    => 'Truck Type',

        'period'        => 'Period',
        'all_period'    => 'All Periods',
        'report_date'   => 'Report Date',

        'summary'       => 'Cost Summary',

        'oil'           => 'Oil',
        'tires'         => 'Tires',
        'maintenance'   => 'Maintenance',
        'grand_total'   => 'Grand Total',

        'oil_records'   => 'Oil Change Records',
        'tire_records'  => 'Tire Records',
        'maint_records' => 'Maintenance Records',

        'date'          => 'Date',
        'type'          => 'Type',
        'km'            => 'Current KM',
        'next_km'       => 'Next KM',
        'next_change'   => 'Next Change',
        'vehicle'       => 'Vehicle',
        'cost'          => 'Cost',
        'notes'         => 'Notes',

        'no_data'       => 'No records found',

        'sar'           => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   بيانات السائق
========================================================= */

$stmt = $con->prepare("
    SELECT
        id,
        name,
        national_id,
        phone,
        work_area,
        truck_type,
        plate_number
    FROM drivers
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$driver = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$driver) {
    die(
        $lang === 'ar'
            ? 'السائق غير موجود'
            : 'Driver not found'
    );
}

/* =========================================================
   دالة جلب البيانات
========================================================= */

function getReportRows(
    mysqli $con,
    string $sql,
    string $types,
    array $params
): array {

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        die(
            'SQL Error: ' .
            htmlspecialchars($con->error)
        );
    }

    if (!empty($params)) {

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

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

/* =========================================================
   سجل الزيت
========================================================= */

$oilSql = "
    SELECT
        id,
        change_date,
        oil_type,
        km_change,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM oil_changes
    WHERE driver_id = ?
";

$oilParams = [$id];
$oilTypes  = 'i';

if ($from !== '') {

    $oilSql .= "
        AND DATE(change_date) >= ?
    ";

    $oilParams[] = $from;
    $oilTypes .= 's';
}

if ($to !== '') {

    $oilSql .= "
        AND DATE(change_date) <= ?
    ";

    $oilParams[] = $to;
    $oilTypes .= 's';
}

$oilSql .= "
    ORDER BY change_date DESC, id DESC
";

$oilRows = getReportRows(
    $con,
    $oilSql,
    $oilTypes,
    $oilParams
);

/* =========================================================
   سجل الإطارات
========================================================= */

$tireSql = "
    SELECT
        id,
        change_date,
        tire_type,
        current_km,
        next_km,
        next_change,
        cost,
        notes
    FROM tires
    WHERE driver_id = ?
";

$tireParams = [$id];
$tireTypes  = 'i';

if ($from !== '') {

    $tireSql .= "
        AND DATE(change_date) >= ?
    ";

    $tireParams[] = $from;
    $tireTypes .= 's';
}

if ($to !== '') {

    $tireSql .= "
        AND DATE(change_date) <= ?
    ";

    $tireParams[] = $to;
    $tireTypes .= 's';
}

$tireSql .= "
    ORDER BY change_date DESC, id DESC
";

$tireRows = getReportRows(
    $con,
    $tireSql,
    $tireTypes,
    $tireParams
);

/* =========================================================
   سجل الصيانة
========================================================= */

$maintSql = "
    SELECT
        id,
        maintenance_date,
        maintenance_type,
        vehicle_name,
        cost,
        notes
    FROM maintenance
    WHERE driver_id = ?
";

$maintParams = [$id];
$maintTypes  = 'i';

if ($from !== '') {

    $maintSql .= "
        AND DATE(maintenance_date) >= ?
    ";

    $maintParams[] = $from;
    $maintTypes .= 's';
}

if ($to !== '') {

    $maintSql .= "
        AND DATE(maintenance_date) <= ?
    ";

    $maintParams[] = $to;
    $maintTypes .= 's';
}

$maintSql .= "
    ORDER BY maintenance_date DESC, id DESC
";

$maintenanceRows = getReportRows(
    $con,
    $maintSql,
    $maintTypes,
    $maintParams
);

/* =========================================================
   الإجماليات
========================================================= */

$totalOil = 0;
$totalTires = 0;
$totalMaintenance = 0;

foreach ($oilRows as $row) {
    $totalOil += (float)($row['cost'] ?? 0);
}

foreach ($tireRows as $row) {
    $totalTires += (float)($row['cost'] ?? 0);
}

foreach ($maintenanceRows as $row) {
    $totalMaintenance += (float)($row['cost'] ?? 0);
}

$grandTotal =
    $totalOil +
    $totalTires +
    $totalMaintenance;

/* =========================================================
   إعداد الفترة
========================================================= */

$periodText =
    ($from !== '' || $to !== '')
        ? (
            ($from !== '' ? $from : '...')
            . ' - '
            .
            ($to !== '' ? $to : '...')
        )
        : $tr['all_period'];

/* =========================================================
   إعداد mPDF
========================================================= */

$defaultConfig =
    (new ConfigVariables())->getDefaults();

$fontDirs =
    $defaultConfig['fontDir'];

$defaultFontConfig =
    (new FontVariables())->getDefaults();

$fontData =
    $defaultFontConfig['fontdata'];

$mpdf = new Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4-L',

    'orientation' => 'L',

    'margin_left' => 10,

    'margin_right' => 10,

    'margin_top' => 12,

    'margin_bottom' => 12,

    'fontDir' => array_merge(
        $fontDirs,
        [
            __DIR__ . '/../vendor/mpdf/mpdf/ttfonts'
        ]
    ),

    'fontdata' => $fontData + [

        'dejavusans' => [

            'R' =>
                'DejaVuSans.ttf',

            'B' =>
                'DejaVuSans-Bold.ttf'
        ]
    ],

    'default_font' =>
        'dejavusans'
]);

$mpdf->SetDisplayMode(
    'fullpage'
);

/* =========================================================
   HTML Header
========================================================= */

$styles = <<<HTML
<style>

body {
    font-family: dejavusans, sans-serif;
    
    color: #222;
    font-size: 10px;
}

.header {
    background: #0d6efd;
    color: #fff;
    padding: 14px;
    text-align: center;
    border-radius: 8px;
    margin-bottom: 12px;
}

.company {
    font-size: 18px;
    font-weight: bold;
}

.title {
    font-size: 14px;
    margin-top: 5px;
}

.meta {
    background: #f1f5f9;
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    margin-bottom: 12px;
}

.info-table,
.summary-table,
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.info-table td {
    border: 1px solid #ddd;
    padding: 7px;
}

.info-label {
    background: #f1f5f9;
    font-weight: bold;
    width: 15%;
}

.section-title {
    background: #343a40;
    color: #fff;
    padding: 8px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 12px;
}

.data-table th {
    background: #495057;
    color: #fff;
    padding: 7px;
    border: 1px solid #ddd;
    font-size: 9px;
}

.data-table td {
    padding: 6px;
    border: 1px solid #ddd;
    text-align: center;
    vertical-align: middle;
}

.summary-table th,
.summary-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}

.summary-table th {
    background: #198754;
    color: #fff;
}

.total {
    background: #e9f7ef;
    color: #198754;
    font-size: 14px;
    font-weight: bold;
}

.money {
    font-weight: bold;
}

.no-data {
    text-align: center;
    padding: 12px;
    color: #777;
}

</style>
HTML;

/* =========================================================
   Header
========================================================= */

$headerHtml = '

<div class="header">

    <div class="company">
        ' . htmlspecialchars($tr['company']) . '
    </div>

    <div class="title">
        ' . htmlspecialchars($tr['title']) . '
    </div>

</div>

<div class="meta">

    ' . htmlspecialchars($tr['period']) . ':
    <strong>
        ' . htmlspecialchars($periodText) . '
    </strong>

    &nbsp;&nbsp;&nbsp;

    ' . htmlspecialchars($tr['report_date']) . ':
    <strong>
        ' . date('Y-m-d H:i') . '
    </strong>

</div>
';

/* =========================================================
   Driver Information
========================================================= */

$driverInfoHtml = '

<table class="info-table">

<tr>

    <td class="info-label">
        ' . htmlspecialchars($tr['driver']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['name'] ?? '-') . '
    </td>

    <td class="info-label">
        ' . htmlspecialchars($tr['plate']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['plate_number'] ?? '-') . '
    </td>

</tr>

<tr>

    <td class="info-label">
        ' . htmlspecialchars($tr['national_id']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['national_id'] ?? '-') . '
    </td>

    <td class="info-label">
        ' . htmlspecialchars($tr['phone']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['phone'] ?? '-') . '
    </td>

</tr>

<tr>

    <td class="info-label">
        ' . htmlspecialchars($tr['work_area']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['work_area'] ?? '-') . '
    </td>

    <td class="info-label">
        ' . htmlspecialchars($tr['truck_type']) . '
    </td>

    <td>
        ' . htmlspecialchars($driver['truck_type'] ?? '-') . '
    </td>

</tr>

</table>
';

/* =========================================================
   Summary
========================================================= */

$summaryHtml = '

<div class="section-title">
    ' . htmlspecialchars($tr['summary']) . '
</div>

<table class="summary-table">

<tr>

    <th>
        ' . htmlspecialchars($tr['oil']) . '
    </th>

    <th>
        ' . htmlspecialchars($tr['tires']) . '
    </th>

    <th>
        ' . htmlspecialchars($tr['maintenance']) . '
    </th>

    <th>
        ' . htmlspecialchars($tr['grand_total']) . '
    </th>

</tr>

<tr>

    <td>
        ' . number_format($totalOil, 2) . '
        ' . htmlspecialchars($tr['sar']) . '
    </td>

    <td>
        ' . number_format($totalTires, 2) . '
        ' . htmlspecialchars($tr['sar']) . '
    </td>

    <td>
        ' . number_format($totalMaintenance, 2) . '
        ' . htmlspecialchars($tr['sar']) . '
    </td>

    <td class="total">
        ' . number_format($grandTotal, 2) . '
        ' . htmlspecialchars($tr['sar']) . '
    </td>

</tr>

</table>
';

/* =========================================================
   Oil Table
========================================================= */

$oilHtml = '

<div class="section-title">
    ' . htmlspecialchars($tr['oil_records']) . '
</div>

<table class="data-table">

<thead>

<tr>

    <th>#</th>

    <th>' . htmlspecialchars($tr['date']) . '</th>

    <th>' . htmlspecialchars($tr['type']) . '</th>

    <th>' . htmlspecialchars($tr['km']) . '</th>

    <th>' . htmlspecialchars($tr['next_km']) . '</th>

    <th>' . htmlspecialchars($tr['next_change']) . '</th>

    <th>' . htmlspecialchars($tr['cost']) . '</th>

    <th>' . htmlspecialchars($tr['notes']) . '</th>

</tr>

</thead>

<tbody>
';

if (empty($oilRows)) {

    $oilHtml .= '

    <tr>

        <td colspan="8" class="no-data">
            ' . htmlspecialchars($tr['no_data']) . '
        </td>

    </tr>
    ';

} else {

    $counter = 1;

    foreach ($oilRows as $row) {

        $oilHtml .= '

        <tr>

            <td>' . $counter++ . '</td>

            <td>' .
                htmlspecialchars(
                    $row['change_date'] ?? '-'
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['oil_type'] ?? '-'
                )
            . '</td>

            <td>' .
                number_format(
                    (float)(
                        $row['current_km']
                        ??
                        $row['km_change']
                        ??
                        0
                    )
                )
            . '</td>

            <td>' .
                number_format(
                    (float)(
                        $row['next_km'] ?? 0
                    )
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['next_change'] ?? '-'
                )
            . '</td>

            <td class="money">' .
                number_format(
                    (float)(
                        $row['cost'] ?? 0
                    ),
                    2
                )
            . ' ' .
                htmlspecialchars($tr['sar'])
            . '</td>

            <td>' .
                nl2br(
                    htmlspecialchars(
                        $row['notes'] ?? '-'
                    )
                )
            . '</td>

        </tr>
        ';
    }
}

$oilHtml .= '

</tbody>

</table>
';

/* =========================================================
   Tires Table
========================================================= */

$tireHtml = '

<div class="section-title">
    ' . htmlspecialchars($tr['tire_records']) . '
</div>

<table class="data-table">

<thead>

<tr>

    <th>#</th>

    <th>' . htmlspecialchars($tr['date']) . '</th>

    <th>' . htmlspecialchars($tr['type']) . '</th>

    <th>' . htmlspecialchars($tr['km']) . '</th>

    <th>' . htmlspecialchars($tr['next_km']) . '</th>

    <th>' . htmlspecialchars($tr['next_change']) . '</th>

    <th>' . htmlspecialchars($tr['cost']) . '</th>

    <th>' . htmlspecialchars($tr['notes']) . '</th>

</tr>

</thead>

<tbody>
';

if (empty($tireRows)) {

    $tireHtml .= '

    <tr>

        <td colspan="8" class="no-data">
            ' . htmlspecialchars($tr['no_data']) . '
        </td>

    </tr>
    ';

} else {

    $counter = 1;

    foreach ($tireRows as $row) {

        $tireHtml .= '

        <tr>

            <td>' . $counter++ . '</td>

            <td>' .
                htmlspecialchars(
                    $row['change_date'] ?? '-'
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['tire_type'] ?? '-'
                )
            . '</td>

            <td>' .
                number_format(
                    (float)(
                        $row['current_km'] ?? 0
                    )
                )
            . '</td>

            <td>' .
                number_format(
                    (float)(
                        $row['next_km'] ?? 0
                    )
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['next_change'] ?? '-'
                )
            . '</td>

            <td class="money">' .
                number_format(
                    (float)(
                        $row['cost'] ?? 0
                    ),
                    2
                )
            . ' ' .
                htmlspecialchars($tr['sar'])
            . '</td>

            <td>' .
                nl2br(
                    htmlspecialchars(
                        $row['notes'] ?? '-'
                    )
                )
            . '</td>

        </tr>
        ';
    }
}

$tireHtml .= '

</tbody>

</table>
';

/* =========================================================
   Maintenance Table
========================================================= */

$maintenanceHtml = '

<div class="section-title">
    ' . htmlspecialchars($tr['maint_records']) . '
</div>

<table class="data-table">

<thead>

<tr>

    <th>#</th>

    <th>' . htmlspecialchars($tr['date']) . '</th>

    <th>' . htmlspecialchars($tr['type']) . '</th>

    <th>' . htmlspecialchars($tr['vehicle']) . '</th>

    <th>' . htmlspecialchars($tr['cost']) . '</th>

    <th>' . htmlspecialchars($tr['notes']) . '</th>

</tr>

</thead>

<tbody>
';

if (empty($maintenanceRows)) {

    $maintenanceHtml .= '

    <tr>

        <td colspan="6" class="no-data">
            ' . htmlspecialchars($tr['no_data']) . '
        </td>

    </tr>
    ';

} else {

    $counter = 1;

    foreach ($maintenanceRows as $row) {

        $maintenanceHtml .= '

        <tr>

            <td>' . $counter++ . '</td>

            <td>' .
                htmlspecialchars(
                    $row['maintenance_date'] ?? '-'
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['maintenance_type'] ?? '-'
                )
            . '</td>

            <td>' .
                htmlspecialchars(
                    $row['vehicle_name'] ?? '-'
                )
            . '</td>

            <td class="money">' .
                number_format(
                    (float)(
                        $row['cost'] ?? 0
                    ),
                    2
                )
            . ' ' .
                htmlspecialchars($tr['sar'])
            . '</td>

            <td>' .
                nl2br(
                    htmlspecialchars(
                        $row['notes'] ?? '-'
                    )
                )
            . '</td>

        </tr>
        ';
    }
}

$maintenanceHtml .= '

</tbody>

</table>
';

/* =========================================================
   Final HTML
========================================================= */

$html =

    $styles .
    $headerHtml .
    $driverInfoHtml .
    $summaryHtml .
    $oilHtml .
    $tireHtml .
    $maintenanceHtml;

/* =========================================================
   mPDF
========================================================= */

$mpdf->SetTitle(
    $tr['title'] . ' - ' . ($driver['name'] ?? '')
);

$mpdf->SetAuthor(
    $tr['company']
);

/*
 * تقسيم WriteHTML إلى أجزاء صغيرة حتى لا يحدث
 * خطأ pcre.backtrack_limit مع كثرة البيانات.
 */

$mpdf->WriteHTML(
    $styles,
    \Mpdf\HTMLParserMode::HEADER_CSS
);

$mpdf->WriteHTML(
    $headerHtml
);

$mpdf->WriteHTML(
    $driverInfoHtml
);

$mpdf->WriteHTML(
    $summaryHtml
);

$mpdf->WriteHTML(
    $oilHtml
);

$mpdf->WriteHTML(
    $tireHtml
);

$mpdf->WriteHTML(
    $maintenanceHtml
);

/* =========================================================
   إخراج PDF
========================================================= */

$safePlate = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    (string)(
        $driver['plate_number'] ?? 'driver'
    )
);

$fileName =
    'driver_cost_' .
    $safePlate .
    '_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

$mpdf->Output(
    $fileName,
    \Mpdf\Output\Destination::INLINE
);

exit;

