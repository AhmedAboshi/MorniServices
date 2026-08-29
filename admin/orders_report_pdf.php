
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
   الاتجاه
========================================================= */

$direction = ($lang === 'ar') ? 'rtl' : 'ltr';

/* =========================================================
   الفلاتر
========================================================= */

$from_date  = trim($_GET['from_date'] ?? '');
$to_date    = trim($_GET['to_date'] ?? '');
$order_type = trim($_GET['order_type'] ?? '');
$driver_id  = (int)($_GET['driver_id'] ?? 0);
$search     = trim($_GET['search'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'company' => 'شركة الشرق لخدمات السيارات',
        'title' => 'تقرير الطلبات',
        'subtitle' => 'تحليل الطلبات والإيرادات حسب السائق ونوع الطلب',

        'period' => 'الفترة',
        'all_period' => 'كل الفترات',
        'report_date' => 'تاريخ التقرير',

        'search' => 'البحث',
        'selected_driver' => 'السائق المحدد',

        'driver_code' => 'رمز السائق',
        'name' => 'اسم السائق',
        'phone' => 'الجوال',
        'type' => 'نوع الطلب',
        'orders' => 'عدد الطلبات',
        'total' => 'الإجمالي',

        'intercity' => 'بين المدن',
        'cart' => 'نقل',
        'tow' => 'سطحة / سحب',

        'summary' => 'ملخص التقرير',
        'total_orders' => 'إجمالي الطلبات',
        'total_revenue' => 'إجمالي الإيرادات',
        'total_drivers' => 'عدد السائقين',
        'average_order' => 'متوسط قيمة الطلب',
        'top_driver' => 'أعلى سائق إيراداً',

        'no_data' => 'لا توجد نتائج مطابقة للفلاتر',
        'sar' => 'ريال'
    ],

    'en' => [

        'company' => 'Al Sharq Automotive Services Company',
        'title' => 'Orders Report',
        'subtitle' => 'Orders and revenue analysis by driver and order type',

        'period' => 'Period',
        'all_period' => 'All Periods',
        'report_date' => 'Report Date',

        'search' => 'Search',
        'selected_driver' => 'Selected Driver',

        'driver_code' => 'Driver Code',
        'name' => 'Driver Name',
        'phone' => 'Phone',
        'type' => 'Order Type',
        'orders' => 'Orders',
        'total' => 'Total',

        'intercity' => 'Intercity',
        'cart' => 'Transport',
        'tow' => 'Tow',

        'summary' => 'Report Summary',
        'total_orders' => 'Total Orders',
        'total_revenue' => 'Total Revenue',
        'total_drivers' => 'Drivers',
        'average_order' => 'Average Order Value',
        'top_driver' => 'Top Revenue Driver',

        'no_data' => 'No results match the selected filters',
        'sar' => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   أنواع الطلبات
========================================================= */

$orderTypes = [
    'intercity' => $tr['intercity'],
    'cart' => $tr['cart'],
    'tow' => $tr['tow']
];

/* =========================================================
   شروط البحث
========================================================= */

$where = [];
$params = [];
$types = '';

if ($search !== '') {

    $where[] = "
        (
            d.name LIKE ?
            OR d.phone LIKE ?
            OR CAST(d.id AS CHAR) LIKE ?
            OR o.order_type LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= 'ssss';
}

if ($from_date !== '') {

    $where[] = "DATE(o.created_at) >= ?";

    $params[] = $from_date;

    $types .= 's';
}

if ($to_date !== '') {

    $where[] = "DATE(o.created_at) <= ?";

    $params[] = $to_date;

    $types .= 's';
}

if (
    $order_type !== '' &&
    array_key_exists($order_type, $orderTypes)
) {

    $where[] = "o.order_type = ?";

    $params[] = $order_type;

    $types .= 's';
}

if ($driver_id > 0) {

    $where[] = "d.id = ?";

    $params[] = $driver_id;

    $types .= 'i';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

SELECT
    d.id AS driver_code,
    d.name,
    d.phone,
    o.order_type,
    COUNT(DISTINCT o.id) AS total_orders,
    COALESCE(SUM(o.price), 0) AS total_revenue

FROM drivers d

INNER JOIN orders o
    ON d.id = o.driver_id

$whereSql

GROUP BY
    d.id,
    d.name,
    d.phone,
    o.order_type

ORDER BY
    total_revenue DESC,
    d.name ASC
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
        die('Filter parameters mismatch.');
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
   النتائج
========================================================= */

$rows = [];

$totalOrders = 0;
$totalRevenue = 0;

$driversSet = [];
$driverTotals = [];

while ($row = $result->fetch_assoc()) {

    $row['total_orders'] =
        (int)($row['total_orders'] ?? 0);

    $row['total_revenue'] =
        (float)($row['total_revenue'] ?? 0);

    $rows[] = $row;

    $totalOrders += $row['total_orders'];
    $totalRevenue += $row['total_revenue'];

    $driversSet[$row['driver_code']] = true;

    $code = $row['driver_code'];

    if (!isset($driverTotals[$code])) {

        $driverTotals[$code] = [
            'name' => $row['name'],
            'revenue' => 0
        ];
    }

    $driverTotals[$code]['revenue'] +=
        $row['total_revenue'];
}

$stmt->close();

$totalDrivers = count($driversSet);

$averageOrder =
    $totalOrders > 0
        ? $totalRevenue / $totalOrders
        : 0;

/* =========================================================
   أعلى سائق
========================================================= */

$topDriverName = '-';
$topDriverRevenue = 0;

foreach ($driverTotals as $item) {

    if ($item['revenue'] > $topDriverRevenue) {

        $topDriverRevenue = $item['revenue'];
        $topDriverName = $item['name'];
    }
}

/* =========================================================
   السائق المحدد
========================================================= */

$selectedDriverName = '';

if ($driver_id > 0) {

    $driverStmt = $con->prepare("
        SELECT name, phone
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            'i',
            $driver_id
        );

        $driverStmt->execute();

        $selectedDriver =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        $driverStmt->close();

        if ($selectedDriver) {

            $selectedDriverName =
                $selectedDriver['name']
                . ' - '
                . ($selectedDriver['phone'] ?? '');
        }
    }
}

/* =========================================================
   الفترة
========================================================= */

$periodText =
    ($from_date !== '' || $to_date !== '')
        ? (
            ($from_date !== '' ? $from_date : '...')
            . ' - '
            . ($to_date !== '' ? $to_date : '...')
        )
        : $tr['all_period'];

/* =========================================================
   mPDF
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
            __DIR__ .
            '/../vendor/mpdf/mpdf/ttfonts'
        ]
    ),

    'fontdata' => $fontData + [

        'dejavusans' => [

            'R' => 'DejaVuSans.ttf',
            'B' => 'DejaVuSans-Bold.ttf'
        ]
    ],

    'default_font' => 'dejavusans'
]);

/* =========================================================
   CSS
========================================================= */

$css = <<<HTML
<style>

body {
    font-family: dejavusans, sans-serif;
    direction: {$direction};
    color: #222;
    font-size: 9.5px;
}

.header {
    background: #0d6efd;
    color: #fff;
    padding: 12px;
    text-align: center;
    margin-bottom: 10px;
}

.company {
    font-size: 17px;
    font-weight: bold;
}

.title {
    font-size: 14px;
    margin-top: 4px;
}

.subtitle {
    font-size: 9px;
    margin-top: 3px;
}

.meta {
    background: #f1f5f9;
    border: 1px solid #ddd;
    padding: 7px;
    text-align: center;
    margin-bottom: 10px;
}

.filters {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.filters td {
    border: 1px solid #ddd;
    padding: 6px;
}

.label {
    background: #f1f5f9;
    font-weight: bold;
    width: 15%;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.summary th,
.summary td {
    border: 1px solid #ddd;
    padding: 7px;
    text-align: center;
}

.summary th {
    background: #198754;
    color: #fff;
}

.summary .grand {
    background: #e9f7ef;
    color: #198754;
    font-weight: bold;
}

.section-title {
    background: #343a40;
    color: #fff;
    padding: 7px;
    font-size: 11px;
    font-weight: bold;
    margin-top: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.data-table th {
    background: #495057;
    color: #fff;
    border: 1px solid #ddd;
    padding: 6px;
    font-size: 8.5px;
    text-align: center;
}

.data-table td {
    border: 1px solid #ddd;
    padding: 5px;
    text-align: center;
    vertical-align: middle;
    font-size: 8.5px;
}

.money {
    font-weight: bold;
    color: #198754;
}

.no-data {
    text-align: center;
    padding: 12px;
    color: #777;
}

.top-driver {
    background: #f3e8ff;
    color: #6f42c1;
    font-weight: bold;
}

</style>
HTML;

/* =========================================================
   Header
========================================================= */

$headerHtml = '
<div class="header">

    <div class="company">
        ' .
        htmlspecialchars($tr['company']) .
    '
    </div>

    <div class="title">
        ' .
        htmlspecialchars($tr['title']) .
    '
    </div>

    <div class="subtitle">
        ' .
        htmlspecialchars($tr['subtitle']) .
    '
    </div>

</div>

<div class="meta">

    ' .
    htmlspecialchars($tr['period']) .
    ':
    <strong>
        ' .
        htmlspecialchars($periodText) .
    '
    </strong>

    &nbsp;&nbsp;&nbsp;

    ' .
    htmlspecialchars($tr['report_date']) .
    ':
    <strong>
        ' .
        date('Y-m-d H:i') .
    '
    </strong>

</div>
';

/* =========================================================
   الفلاتر
========================================================= */

$filtersHtml = '';

if (
    $search !== '' ||
    $order_type !== '' ||
    $selectedDriverName !== ''
) {

    $filtersHtml = '
    <table class="filters">
    <tr>
    ';

    if ($search !== '') {

        $filtersHtml .= '
        <td class="label">
            ' .
            htmlspecialchars($tr['search']) .
        '
        </td>

        <td>
            ' .
            htmlspecialchars($search) .
        '
        </td>
        ';
    }

    if ($order_type !== '') {

        $filtersHtml .= '
        <td class="label">
            ' .
            htmlspecialchars($tr['type']) .
        '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $orderTypes[$order_type] ?? $order_type
            ) .
        '
        </td>
        ';
    }

    if ($selectedDriverName !== '') {

        $filtersHtml .= '
        <td class="label">
            ' .
            htmlspecialchars($tr['selected_driver']) .
        '
        </td>

        <td>
            ' .
            htmlspecialchars($selectedDriverName) .
        '
        </td>
        ';
    }

    $filtersHtml .= '
    </tr>
    </table>
    ';
}

/* =========================================================
   Summary
========================================================= */

$summaryHtml = '

<div class="section-title">
    ' .
    htmlspecialchars($tr['summary']) .
'
</div>

<table class="summary">

<tr>

    <th>
        ' .
        htmlspecialchars($tr['total_orders']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['total_revenue']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['total_drivers']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['average_order']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['top_driver']) .
    '
    </th>

</tr>

<tr>

    <td>
        ' .
        number_format($totalOrders) .
    '
    </td>

    <td>
        ' .
        number_format($totalRevenue, 2) .
        ' ' .
        htmlspecialchars($tr['sar']) .
    '
    </td>

    <td>
        ' .
        number_format($totalDrivers) .
    '
    </td>

    <td>
        ' .
        number_format($averageOrder, 2) .
        ' ' .
        htmlspecialchars($tr['sar']) .
    '
    </td>

    <td class="grand">

        ' .
        htmlspecialchars($topDriverName) .
    '
        <br>
        ' .
        (
            $topDriverRevenue > 0
                ? number_format($topDriverRevenue, 2)
                . ' ' .
                htmlspecialchars($tr['sar'])
                : ''
        ) .
    '

    </td>

</tr>

</table>
';

/* =========================================================
   جدول التقرير
========================================================= */

$tableHtml = '

<div class="section-title">
    ' .
    htmlspecialchars($tr['title']) .
'
</div>

<table class="data-table">

<thead>

<tr>

    <th>#</th>

    <th>
        ' .
        htmlspecialchars($tr['driver_code']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['name']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['phone']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['type']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['orders']) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars($tr['total']) .
    '
    </th>

</tr>

</thead>

<tbody>
';

if (empty($rows)) {

    $tableHtml .= '

    <tr>

        <td colspan="7" class="no-data">

            ' .
            htmlspecialchars($tr['no_data']) .
        '

        </td>

    </tr>
    ';

} else {

    $counter = 1;

    foreach ($rows as $item) {

        $isTop =
            $item['name'] === $topDriverName;

        $tableHtml .= '

        <tr class="' .
            ($isTop ? 'top-driver' : '') .
        '">

            <td>
                ' .
                $counter++ .
            '
            </td>

            <td>
                DRV-' .
                (int)$item['driver_code'] .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $item['name'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $item['phone'] ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $orderTypes[
                        $item['order_type']
                    ]
                    ??
                    $item['order_type']
                    ??
                    '-'
                ) .
            '
            </td>

            <td>
                ' .
                number_format(
                    (int)$item['total_orders']
                ) .
            '
            </td>

            <td class="money">
                ' .
                number_format(
                    (float)$item['total_revenue'],
                    2
                ) .
                ' ' .
                htmlspecialchars($tr['sar']) .
            '
            </td>

        </tr>

        ';
    }
}

$tableHtml .= '

</tbody>

<tfoot>

<tr>

    <th
        colspan="5"
        style="
            background:#198754;
            color:#fff;
        "
    >
        ' .
        htmlspecialchars($tr['total']) .
    '
    </th>

    <th
        style="
            background:#198754;
            color:#fff;
        "
    >
        ' .
        number_format($totalOrders) .
    '
    </th>

    <th
        style="
            background:#198754;
            color:#fff;
        "
    >
        ' .
        number_format($totalRevenue, 2) .
        ' ' .
        htmlspecialchars($tr['sar']) .
    '
    </th>

</tr>

</tfoot>

</table>
';

/* =========================================================
   PDF
========================================================= */

$mpdf->SetTitle(
    $tr['title']
);

$mpdf->SetAuthor(
    $tr['company']
);

/*
 * كتابة كل جزء منفصلًا
 * لتجنب مشاكل PCRE مع HTML الكبير.
 */

$mpdf->WriteHTML(
    $css,
    \Mpdf\HTMLParserMode::HEADER_CSS
);

$mpdf->WriteHTML(
    $headerHtml
);

if ($filtersHtml !== '') {

    $mpdf->WriteHTML(
        $filtersHtml
    );
}

$mpdf->WriteHTML(
    $summaryHtml
);

$mpdf->WriteHTML(
    $tableHtml
);

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'orders_report_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

/* =========================================================
   إخراج
========================================================= */

$mpdf->Output(
    $fileName,
    \Mpdf\Output\Destination::INLINE
);

exit;

