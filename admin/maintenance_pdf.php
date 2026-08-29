<?php

session_start();

include('../include/connected.php');

require_once '../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;


/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

$lang = in_array($lang, ['ar', 'en'])
    ? $lang
    : 'ar';


/* =========================
   الفلاتر
========================= */

$search = trim($_GET['search'] ?? '');

$type_filter = trim(
    $_GET['maintenance_type'] ?? ''
);

$date_from = trim(
    $_GET['date_from'] ?? ''
);

$date_to = trim(
    $_GET['date_to'] ?? ''
);


/* =========================
   الاستعلام
========================= */

$sql = "
    SELECT
        maintenance.id,
        maintenance.vehicle_name,
        maintenance.plate_number,
        drivers.name AS driver_name,
        maintenance.maintenance_type,
        maintenance.cost,
        maintenance.notes,
        maintenance.maintenance_date

    FROM maintenance

    LEFT JOIN drivers
        ON maintenance.driver_id = drivers.id

    WHERE 1=1
";


$params = [];

$types = "";


/* =========================
   البحث
========================= */

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

    $value = "%{$search}%";

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sssss";
}


/* =========================
   نوع الصيانة
========================= */

if ($type_filter !== '') {

    $sql .= "
        AND maintenance.maintenance_type = ?
    ";

    $params[] = $type_filter;

    $types .= "s";
}


/* =========================
   من تاريخ
========================= */

if ($date_from !== '') {

    $sql .= "
        AND maintenance.maintenance_date >= ?
    ";

    $params[] = $date_from;

    $types .= "s";
}


/* =========================
   إلى تاريخ
========================= */

if ($date_to !== '') {

    $sql .= "
        AND maintenance.maintenance_date <= ?
    ";

    $params[] = $date_to;

    $types .= "s";
}


/* =========================
   الترتيب
========================= */

$sql .= "
    ORDER BY
        maintenance.maintenance_date DESC,
        maintenance.id DESC
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
   إجمالي البيانات
========================= */

$total_cost = 0;

$total_records = 0;


/* =========================
   إعداد mPDF
========================= */

$defaultConfig = (new ConfigVariables())->getDefaults();

$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new FontVariables())->getDefaults();

$fontData = $defaultFontConfig['fontdata'];


/*
 * نستخدم خط DejaVuSans
 * لأنه يدعم العربية بشكل جيد
 */

$mpdf = new Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4-L',

    'orientation' => 'L',

    'margin_left' => 10,

    'margin_right' => 10,

    'margin_top' => 15,

    'margin_bottom' => 15,

    'margin_header' => 5,

    'margin_footer' => 8,

    'default_font' => 'dejavusans',

    'fontDir' => array_merge(
        $fontDirs,
        [
            __DIR__ . '/../vendor/mpdf/mpdf/ttfonts'
        ]
    ),

    'fontdata' => $fontData + [

        'dejavusans' => [
            'R' => 'DejaVuSans.ttf',
            'B' => 'DejaVuSans-Bold.ttf'
        ]

    ]

]);


/* =========================
   اتجاه الصفحة
========================= */

$mpdf->SetDirectionality(
    $lang === 'ar' ? 'rtl' : 'ltr'
);


/* =========================
   عنوان التقرير
========================= */

$title = $lang === 'ar'
    ? 'تقرير سجلات صيانة المركبات'
    : 'Vehicle Maintenance Report';


$generated = $lang === 'ar'
    ? 'تاريخ إنشاء التقرير'
    : 'Report Date';


$vehicle = $lang === 'ar'
    ? 'المركبة'
    : 'Vehicle';


$plate = $lang === 'ar'
    ? 'رقم اللوحة'
    : 'Plate Number';


$driver = $lang === 'ar'
    ? 'السائق'
    : 'Driver';


$type = $lang === 'ar'
    ? 'نوع الصيانة'
    : 'Maintenance Type';


$cost = $lang === 'ar'
    ? 'التكلفة'
    : 'Cost';


$notes = $lang === 'ar'
    ? 'الملاحظات'
    : 'Notes';


$date = $lang === 'ar'
    ? 'تاريخ الصيانة'
    : 'Maintenance Date';


$total = $lang === 'ar'
    ? 'إجمالي التكلفة'
    : 'Total Cost';


$records = $lang === 'ar'
    ? 'عدد السجلات'
    : 'Total Records';


$currency = $lang === 'ar'
    ? 'ريال'
    : 'SAR';


/* =========================
   الفلاتر المستخدمة
========================= */

$filterHtml = '';


if ($search !== '') {

    $filterHtml .=
        '<span>'
        . ($lang === 'ar' ? 'البحث: ' : 'Search: ')
        . htmlspecialchars($search)
        . '</span>';
}


if ($type_filter !== '') {

    $filterHtml .=
        '<span>'
        . ($lang === 'ar' ? 'نوع الصيانة: ' : 'Type: ')
        . htmlspecialchars($type_filter)
        . '</span>';
}


if ($date_from !== '') {

    $filterHtml .=
        '<span>'
        . ($lang === 'ar' ? 'من: ' : 'From: ')
        . htmlspecialchars($date_from)
        . '</span>';
}


if ($date_to !== '') {

    $filterHtml .=
        '<span>'
        . ($lang === 'ar' ? 'إلى: ' : 'To: ')
        . htmlspecialchars($date_to)
        . '</span>';
}


/* =========================
   HTML
========================= */

$html = '
<style>

body {
    font-family: dejavusans;
    font-size: 10px;
    color: #333;
}

.header {
    text-align: center;
    margin-bottom: 15px;
}

.title {
    font-size: 20px;
    font-weight: bold;
    color: #198754;
    margin-bottom: 6px;
}

.date-created {
    font-size: 9px;
    color: #777;
}

.summary {
    width: 100%;
    margin-bottom: 12px;
}

.summary td {
    width: 50%;
    padding: 8px;
    background: #f5f7f8;
    border: 1px solid #ddd;
}

.summary strong {
    font-size: 12px;
}

.filters {
    background: #eef8f1;
    border: 1px solid #cfe8d5;
    padding: 7px;
    margin-bottom: 12px;
    font-size: 9px;
}

.filters span {
    margin-left: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #28A745;
    color: #fff;
    padding: 8px 5px;
    font-weight: bold;
    text-align: center;
    border: 1px solid #218838;
}

td {
    padding: 7px 5px;
    border: 1px solid #ddd;
    text-align: center;
    vertical-align: middle;
}

tr:nth-child(even) td {
    background: #f8faf9;
}

.cost {
    color: #198754;
    font-weight: bold;
}

.total-row td {
    background: #eaf7ee !important;
    font-weight: bold;
    font-size: 11px;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #888;
}

.footer {
    text-align: center;
    font-size: 8px;
    color: #888;
}

</style>


<div class="header">

    <div class="title">
        ' . htmlspecialchars($title) . '
    </div>

    <div class="date-created">
        ' . htmlspecialchars($generated) . ':
        ' . date('Y-m-d H:i') . '
    </div>

</div>


<table class="summary">

<tr>

    <td>
        ' . htmlspecialchars($records) . ':
        <strong>{TOTAL_RECORDS}</strong>
    </td>

    <td>
        ' . htmlspecialchars($total) . ':
        <strong>{TOTAL_COST}
        ' . htmlspecialchars($currency) . '</strong>
    </td>

</tr>

</table>
';


/* =========================
   الفلاتر
========================= */

if ($filterHtml !== '') {

    $html .= '
    <div class="filters">
        <strong>
            ' . ($lang === 'ar'
                ? 'الفلاتر المستخدمة:'
                : 'Applied Filters:') . '
        </strong>

        ' . $filterHtml . '
    </div>
    ';
}


/* =========================
   الجدول
========================= */

$html .= '

<table>

<thead>

<tr>

    <th>#</th>

    <th>' . htmlspecialchars($vehicle) . '</th>

    <th>' . htmlspecialchars($plate) . '</th>

    <th>' . htmlspecialchars($driver) . '</th>

    <th>' . htmlspecialchars($type) . '</th>

    <th>' . htmlspecialchars($cost) . '</th>

    <th>' . htmlspecialchars($notes) . '</th>

    <th>' . htmlspecialchars($date) . '</th>

</tr>

</thead>

<tbody>
';


/* =========================
   البيانات
========================= */

while ($row = $result->fetch_assoc()) {

    $total_records++;

    $row_cost = (float)($row['cost'] ?? 0);

    $total_cost += $row_cost;


    $html .= '

    <tr>

        <td>
            ' . (int)$row['id'] . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row['vehicle_name'] ?? '-'
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row['plate_number'] ?? '-'
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row['driver_name'] ?? '-'
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row['maintenance_type'] ?? '-'
            ) . '
        </td>

        <td class="cost">
            ' . number_format($row_cost, 2) . '
            ' . htmlspecialchars($currency) . '
        </td>

        <td>
            ' . nl2br(
                htmlspecialchars(
                    $row['notes'] ?? '-'
                )
            ) . '
        </td>

        <td>
            ' . htmlspecialchars(
                $row['maintenance_date'] ?? '-'
            ) . '
        </td>

    </tr>

    ';
}


/* =========================
   لا توجد بيانات
========================= */

if ($total_records === 0) {

    $html .= '

    <tr>

        <td colspan="8" class="no-data">

            ' .
            ($lang === 'ar'
                ? 'لا توجد سجلات صيانة مطابقة للفلاتر'
                : 'No maintenance records match the selected filters')
            . '

        </td>

    </tr>

    ';
}


/* =========================
   الإجمالي
========================= */

$html .= '

<tr class="total-row">

    <td colspan="5">
        ' . htmlspecialchars($total) . '
    </td>

    <td colspan="3">
        ' . number_format($total_cost, 2) . '
        ' . htmlspecialchars($currency) . '
    </td>

</tr>

</tbody>

</table>


<div class="footer">

    ' .
    ($lang === 'ar'
        ? 'تقرير صادر من منصة الشرق الذكية للخدمات وإدارة الأسطول'
        : 'Report generated by AlSharq Smart Platform')
    . '

</div>
';


/* =========================
   استبدال الملخص
========================= */

$html = str_replace(
    '{TOTAL_RECORDS}',
    number_format($total_records),
    $html
);


$html = str_replace(
    '{TOTAL_COST}',
    number_format($total_cost, 2),
    $html
);


/* =========================
   Header / Footer
========================= */

$mpdf->SetHTMLHeader('
<div style="
    text-align:center;
    font-size:8px;
    color:#999;
    border-bottom:1px solid #ddd;
    padding-bottom:5px;
">
    ' .
    ($lang === 'ar'
        ? 'منصة الشرق الذكية للخدمات وإدارة الأسطول'
        : 'AlSharq Smart Platform')
    . '
</div>
');


$mpdf->SetHTMLFooter('
<div style="
    text-align:center;
    font-size:8px;
    color:#888;
">
    ' .
    ($lang === 'ar'
        ? 'صفحة'
        : 'Page')
    . '

    {PAGENO}

    /

    {nbpg}

</div>
');


/* =========================
   إنشاء PDF
========================= */

$mpdf->WriteHTML($html);


/* =========================
   اسم الملف
========================= */

$filename =
    'maintenance_report_'
    . date('Y-m-d')
    . '.pdf';


/* =========================
   إخراج PDF
========================= */

$mpdf->Output(
    $filename,
    'D'
);

exit;