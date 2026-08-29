
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   DIRECTION
========================================================= */

$direction = ($lang === 'ar') ? 'rtl' : 'ltr';

/* =========================================================
   FILTERS
========================================================= */

$search    = trim($_GET['search'] ?? '');
$from      = trim($_GET['from'] ?? '');
$to        = trim($_GET['to'] ?? '');
$orderType = trim($_GET['order_type'] ?? '');

/* =========================================================
   TRANSLATIONS
========================================================= */

$t = [

    'ar' => [

        'company'       => 'شركة الشرق لخدمات السيارات',
        'title'         => 'تقرير الفواتير',

        'subtitle'      => 'عرض وتحليل فواتير العملاء والطلبات',

        'period'        => 'الفترة',
        'all_period'    => 'كل الفترات',
        'report_date'   => 'تاريخ التقرير',

        'search'        => 'البحث',
        'invoice_no'    => 'رقم الفاتورة',
        'invoice_date'  => 'تاريخ الفاتورة',
        'customer'      => 'العميل',
        'phone'         => 'الهاتف',
        'total'         => 'الإجمالي',
        'type'          => 'نوع الطلب',

        'summary'       => 'ملخص التقرير',

        'count'         => 'عدد الفواتير',
        'sum'           => 'إجمالي المبالغ',
        'average'       => 'متوسط الفاتورة',

        'no_data'       => 'لا توجد فواتير مطابقة للفلاتر',

        'sar'           => 'ريال'
    ],

    'en' => [

        'company'       => 'Al Sharq Automotive Services Company',
        'title'         => 'Invoices Report',

        'subtitle'      => 'Customer invoices and order analysis',

        'period'        => 'Period',
        'all_period'    => 'All Periods',
        'report_date'   => 'Report Date',

        'search'        => 'Search',
        'invoice_no'    => 'Invoice Number',
        'invoice_date'  => 'Invoice Date',
        'customer'      => 'Customer',
        'phone'         => 'Phone',
        'total'         => 'Total',
        'type'          => 'Order Type',

        'summary'       => 'Report Summary',

        'count'         => 'Invoice Count',
        'sum'           => 'Total Amount',
        'average'       => 'Average Invoice',

        'no_data'       => 'No invoices match the selected filters',

        'sar'           => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   BUILD WHERE
========================================================= */

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {

    $where[] = "
        (
            invoices.invoice_number LIKE ?
            OR orders.full_name LIKE ?
            OR orders.phone LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= 'sss';
}

if ($from !== '') {

    $where[] =
        "DATE(invoices.created_at) >= ?";

    $params[] = $from;

    $types .= 's';
}

if ($to !== '') {

    $where[] =
        "DATE(invoices.created_at) <= ?";

    $params[] = $to;

    $types .= 's';
}

if ($orderType !== '') {

    $where[] =
        "orders.order_type = ?";

    $params[] = $orderType;

    $types .= 's';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );
}

/* =========================================================
   LOAD INVOICES
========================================================= */

$sql = "

    SELECT

        invoices.id,

        invoices.invoice_number,

        invoices.created_at,

        invoices.total_with_vat,

        orders.full_name,

        orders.phone,

        orders.order_type

    FROM invoices

    LEFT JOIN orders
        ON invoices.order_id = orders.id

    $whereSql

    ORDER BY
        invoices.id DESC
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

$rows = [];

$totalInvoices = 0;

$totalAmount = 0;

while ($row = $result->fetch_assoc()) {

    $row['total_with_vat'] =
        (float)(
            $row['total_with_vat']
            ?? 0
        );

    $rows[] = $row;

    $totalInvoices++;

    $totalAmount +=
        $row['total_with_vat'];
}

$stmt->close();

$averageInvoice =
    $totalInvoices > 0
        ? $totalAmount / $totalInvoices
        : 0;

/* =========================================================
   PERIOD
========================================================= */

$periodText =

    ($from !== '' || $to !== '')

        ? (
            ($from !== ''
                ? $from
                : '...')
            .
            ' - '
            .
            ($to !== ''
                ? $to
                : '...')
        )

        : $tr['all_period'];

/* =========================================================
   mPDF CONFIG
========================================================= */

$defaultConfig =
    (new ConfigVariables())
        ->getDefaults();

$fontDirs =
    $defaultConfig['fontDir'];

$defaultFontConfig =
    (new FontVariables())
        ->getDefaults();

$fontData =
    $defaultFontConfig['fontdata'];

$mpdf = new Mpdf([

    'mode' =>
        'utf-8',

    'format' =>
        'A4-L',

    'orientation' =>
        'L',

    'margin_left' =>
        8,

    'margin_right' =>
        8,

    'margin_top' =>
        10,

    'margin_bottom' =>
        10,

    'fontDir' =>
        array_merge(
            $fontDirs,
            [
                __DIR__ .
                '/../vendor/mpdf/mpdf/ttfonts'
            ]
        ),

    'fontdata' =>
        $fontData + [

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

$mpdf->SetDisplayMode('fullpage');

/* =========================================================
   CSS
========================================================= */

$css = <<<HTML
<style>

body {

    font-family:
        dejavusans,
        sans-serif;

    direction:
        {$direction};

    color:
        #222;

    font-size:
        9px;
}

.header {

    background:
        #0d6efd;

    color:
        #fff;

    padding:
        12px;

    text-align:
        center;

    margin-bottom:
        10px;
}

.company {

    font-size:
        17px;

    font-weight:
        bold;
}

.title {

    font-size:
        14px;

    margin-top:
        4px;

    font-weight:
        bold;
}

.subtitle {

    font-size:
        9px;

    margin-top:
        3px;
}

.meta {

    background:
        #f1f5f9;

    border:
        1px solid #ddd;

    padding:
        6px;

    text-align:
        center;

    margin-bottom:
        10px;
}

.filter-table {

    width:
        100%;

    border-collapse:
        collapse;

    margin-bottom:
        10px;
}

.filter-table td {

    border:
        1px solid #ddd;

    padding:
        6px;
}

.filter-label {

    background:
        #f1f5f9;

    font-weight:
        bold;

    width:
        15%;
}

.summary {

    width:
        100%;

    border-collapse:
        collapse;

    margin-bottom:
        12px;
}

.summary th,
.summary td {

    border:
        1px solid #ddd;

    padding:
        7px;

    text-align:
        center;
}

.summary th {

    background:
        #198754;

    color:
        #fff;
}

.summary .total {

    background:
        #e9f7ef;

    color:
        #198754;

    font-weight:
        bold;
}

.section-title {

    background:
        #343a40;

    color:
        #fff;

    padding:
        7px;

    font-size:
        11px;

    font-weight:
        bold;

    margin-top:
        10px;
}

.invoice-table {

    width:
        100%;

    border-collapse:
        collapse;

    margin-bottom:
        12px;
}

.invoice-table th {

    background:
        #007bff;

    color:
        #fff;

    border:
        1px solid #ddd;

    padding:
        6px;

    text-align:
        center;

    font-size:
        8px;
}

.invoice-table td {

    border:
        1px solid #ddd;

    padding:
        5px;

    text-align:
        center;

    font-size:
        8px;

    vertical-align:
        middle;
}

.money {

    color:
        #198754;

    font-weight:
        bold;
}

.no-data {

    padding:
        15px;

    text-align:
        center;

    color:
        #777;
}

.footer-total {

    background:
        #e9f7ef;

    color:
        #198754;

    font-weight:
        bold;

    font-size:
        11px;
}

</style>
HTML;

/* =========================================================
   HEADER
========================================================= */

$headerHtml = '

<div class="header">

    <div class="company">
        ' .
        htmlspecialchars(
            $tr['company']
        ) .
    '
    </div>

    <div class="title">
        ' .
        htmlspecialchars(
            $tr['title']
        ) .
    '
    </div>

    <div class="subtitle">
        ' .
        htmlspecialchars(
            $tr['subtitle']
        ) .
    '
    </div>

</div>

<div class="meta">

    ' .
    htmlspecialchars(
        $tr['period']
    ) .
    ':
    <strong>
        ' .
        htmlspecialchars(
            $periodText
        ) .
    '</strong>

    &nbsp;&nbsp;&nbsp;

    ' .
    htmlspecialchars(
        $tr['report_date']
    ) .
    ':
    <strong>
        ' .
        date('Y-m-d H:i') .
    '</strong>

</div>
';

/* =========================================================
   FILTERS
========================================================= */

$filtersHtml = '';

if (
    $search !== '' ||
    $orderType !== ''
) {

    $filtersHtml = '

    <table class="filter-table">

    <tr>
    ';

    if ($search !== '') {

        $filtersHtml .= '

        <td class="filter-label">
            ' .
            htmlspecialchars(
                $tr['search']
            ) .
        '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $search
            ) .
        '
        </td>

        ';
    }

    if ($orderType !== '') {

        $filtersHtml .= '

        <td class="filter-label">
            ' .
            htmlspecialchars(
                $tr['type']
            ) .
        '
        </td>

        <td>
            ' .
            htmlspecialchars(
                $orderType
            ) .
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
   SUMMARY
========================================================= */

$summaryHtml = '

<div class="section-title">

    ' .
    htmlspecialchars(
        $tr['summary']
    ) .

'</div>

<table class="summary">

<tr>

    <th>
        ' .
        htmlspecialchars(
            $tr['count']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['sum']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['average']
        ) .
    '
    </th>

</tr>

<tr>

    <td>
        ' .
        number_format(
            $totalInvoices
        ) .
    '
    </td>

    <td>
        ' .
        number_format(
            $totalAmount,
            2
        ) .
        ' ' .
        htmlspecialchars(
            $tr['sar']
        ) .
    '
    </td>

    <td>
        ' .
        number_format(
            $averageInvoice,
            2
        ) .
        ' ' .
        htmlspecialchars(
            $tr['sar']
        ) .
    '
    </td>

</tr>

</table>
';

/* =========================================================
   INVOICES TABLE - FIRST CHUNK
========================================================= */

$tableStart = '

<div class="section-title">

    ' .
    htmlspecialchars(
        $tr['title']
    ) .

'</div>

<table class="invoice-table">

<thead>

<tr>

    <th>#</th>

    <th>
        ' .
        htmlspecialchars(
            $tr['invoice_no']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['invoice_date']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['customer']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['phone']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['total']
        ) .
    '
    </th>

    <th>
        ' .
        htmlspecialchars(
            $tr['type']
        ) .
    '
    </th>

</tr>

</thead>

<tbody>
';

$mpdf->SetTitle(
    $tr['title']
);

$mpdf->SetAuthor(
    $tr['company']
);

/* =========================================================
   WRITE CSS + HEADER
========================================================= */

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
    $tableStart
);

/* =========================================================
   WRITE ROWS IN SMALL CHUNKS
========================================================= */

if (empty($rows)) {

    $mpdf->WriteHTML('

        <tr>

            <td
                colspan="7"
                class="no-data"
            >
                ' .
                htmlspecialchars(
                    $tr['no_data']
                ) .
            '
            </td>

        </tr>

    ');

} else {

    $counter = 1;

    $chunk = '';

    $chunkSize = 50;

    $counterRows = 0;

    foreach ($rows as $invoice) {

        $chunk .= '

        <tr>

            <td>
                ' .
                $counter++ .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $invoice['invoice_number']
                    ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $invoice['created_at']
                    ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $invoice['full_name']
                    ?? '-'
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $invoice['phone']
                    ?? '-'
                ) .
            '
            </td>

            <td class="money">
                ' .
                number_format(
                    (float)(
                        $invoice['total_with_vat']
                        ?? 0
                    ),
                    2
                ) .
                ' ' .
                htmlspecialchars(
                    $tr['sar']
                ) .
            '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $invoice['order_type']
                    ?? '-'
                ) .
            '
            </td>

        </tr>

        ';

        $counterRows++;

        if (
            $counterRows >=
            $chunkSize
        ) {

            $mpdf->WriteHTML(
                $chunk
            );

            $chunk = '';

            $counterRows = 0;
        }
    }

    if ($chunk !== '') {

        $mpdf->WriteHTML(
            $chunk
        );
    }
}

/* =========================================================
   TABLE FOOTER
========================================================= */

$tableFooter = '

</tbody>

<tfoot>

<tr>

    <th
        colspan="5"
        class="footer-total"
    >
        ' .
        htmlspecialchars(
            $tr['sum']
        ) .
    '
    </th>

    <th
        class="footer-total"
    >
        ' .
        number_format(
            $totalAmount,
            2
        ) .
        ' ' .
        htmlspecialchars(
            $tr['sar']
        ) .
    '
    </th>

    <th
        class="footer-total"
    >
        ' .
        number_format(
            $totalInvoices
        ) .
    '
    </th>

</tr>

</tfoot>

</table>
';

$mpdf->WriteHTML(
    $tableFooter
);

/* =========================================================
   OUTPUT
========================================================= */

$fileName =
    'invoices_report_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

$mpdf->Output(
    $fileName,
    \Mpdf\Output\Destination::INLINE
);

exit;

