
<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   LANGUAGE
========================================================= */

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], ['ar', 'en'], true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   TRANSLATIONS
========================================================= */

$t = [

    'ar' => [

        'title' => 'إدارة الفواتير',
        'subtitle' => 'عرض ومتابعة فواتير العملاء والطلبات',

        'invoice_no' => 'رقم الفاتورة',
        'invoice_date' => 'تاريخ الفاتورة',
        'customer' => 'العميل',
        'phone' => 'الهاتف',
        'total' => 'الإجمالي',
        'type' => 'نوع الطلب',
        'details' => 'التفاصيل',
        'view' => 'عرض',

        'company' => 'شركة الشرق لخدمات السيارات',

        'search' => 'بحث',
        'search_placeholder' => 'رقم الفاتورة أو اسم العميل أو الهاتف',

        'from' => 'من تاريخ',
        'to' => 'إلى تاريخ',

        'all_types' => 'جميع الأنواع',

        'print' => 'طباعة',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'reset' => 'إعادة ضبط',

        'count' => 'عدد الفواتير',
        'sum' => 'إجمالي المبالغ',
        'average' => 'متوسط الفاتورة',
        'filtered' => 'الفواتير الظاهرة',

        'sar' => 'ريال',

        'report' => 'تقرير الفواتير',
        'no_data' => 'لا توجد فواتير مطابقة للفلاتر',

        'previous' => 'السابق',
        'next' => 'التالي',
        'page' => 'صفحة',

        'rows' => 'عدد السجلات',

        'all' => 'الكل'
    ],

    'en' => [

        'title' => 'Invoices Management',
        'subtitle' => 'View and manage customer invoices and orders',

        'invoice_no' => 'Invoice Number',
        'invoice_date' => 'Invoice Date',
        'customer' => 'Customer',
        'phone' => 'Phone',
        'total' => 'Total',
        'type' => 'Order Type',
        'details' => 'Details',
        'view' => 'View',

        'company' => 'Al Sharq Automotive Services Company',

        'search' => 'Search',
        'search_placeholder' => 'Invoice number, customer name or phone',

        'from' => 'From Date',
        'to' => 'To Date',

        'all_types' => 'All Types',

        'print' => 'Print',
        'excel' => 'Excel',
        'pdf' => 'PDF',
        'reset' => 'Reset',

        'count' => 'Invoices Count',
        'sum' => 'Total Amount',
        'average' => 'Average Invoice',
        'filtered' => 'Displayed Invoices',

        'sar' => 'SAR',

        'report' => 'Invoices Report',
        'no_data' => 'No invoices match the selected filters',

        'previous' => 'Previous',
        'next' => 'Next',
        'page' => 'Page',

        'rows' => 'Records',

        'all' => 'All'
    ]
];

$tr = $t[$lang];

/* =========================================================
   FILTERS
========================================================= */

$search = trim($_GET['search'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

$orderType = trim($_GET['order_type'] ?? '');

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$limit = 20;

$offset = ($page - 1) * $limit;

/* =========================================================
   AVAILABLE ORDER TYPES
========================================================= */

$orderTypes = [];

$typeResult = $con->query("
    SELECT DISTINCT order_type
    FROM orders
    WHERE order_type IS NOT NULL
      AND order_type <> ''
    ORDER BY order_type ASC
");

if ($typeResult) {

    while ($typeRow = $typeResult->fetch_assoc()) {

        $orderTypes[] =
            $typeRow['order_type'];
    }
}

/* =========================================================
   BUILD WHERE
========================================================= */

$where = [];

$params = [];

$types = '';

if ($search !== '') {

    $where[] = "
        (
            invoices.invoice_number LIKE ?
            OR orders.full_name LIKE ?
            OR orders.phone LIKE ?
        )
    ";

    $value =
        '%' . $search . '%';

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

    $params[] =
        $orderType;

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
   COUNT
========================================================= */

$countSql = "

    SELECT
        COUNT(invoices.id) AS total_invoices

    FROM invoices

    LEFT JOIN orders
        ON invoices.order_id = orders.id

    $whereSql

";

$countStmt =
    $con->prepare($countSql);

if (!$countStmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
    );
}

if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}

$countStmt->execute();

$countResult =
    $countStmt
        ->get_result()
        ->fetch_assoc();

$totalInvoices =
    (int)(
        $countResult['total_invoices']
        ?? 0
    );

$countStmt->close();

/* =========================================================
   TOTAL AMOUNT
========================================================= */

$sumSql = "

    SELECT
        COALESCE(
            SUM(invoices.total_with_vat),
            0
        ) AS total_amount

    FROM invoices

    LEFT JOIN orders
        ON invoices.order_id = orders.id

    $whereSql

";

$sumStmt =
    $con->prepare($sumSql);

if (!$sumStmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
    );
}

if (!empty($params)) {

    $sumStmt->bind_param(
        $types,
        ...$params
    );
}

$sumStmt->execute();

$sumResult =
    $sumStmt
        ->get_result()
        ->fetch_assoc();

$totalAmount =
    (float)(
        $sumResult['total_amount']
        ?? 0
    );

$sumStmt->close();

/* =========================================================
   AVERAGE
========================================================= */

$averageInvoice =
    $totalInvoices > 0
        ? $totalAmount / $totalInvoices
        : 0;

/* =========================================================
   MAIN QUERY
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

    LIMIT ?
    OFFSET ?

";

$stmt =
    $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
    );
}

/*
 * LIMIT/OFFSET إضافتها كـ integers
 */

$mainParams = $params;

$mainTypes = $types . 'ii';

$mainParams[] = $limit;
$mainParams[] = $offset;

$stmt->bind_param(
    $mainTypes,
    ...$mainParams
);

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars(
            $stmt->error
        )
    );
}

$result =
    $stmt->get_result();

$invoiceRows = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $invoiceRows[] =
        $row;
}

$stmt->close();

/* =========================================================
   PAGINATION
========================================================= */

$totalPages =
    max(
        1,
        (int)ceil(
            $totalInvoices / $limit
        )
    );

/* =========================================================
   EXPORT LINKS
========================================================= */

$exportParams = [

    'lang' =>
        $lang,

    'search' =>
        $search,

    'from' =>
        $from,

    'to' =>
        $to,

    'order_type' =>
        $orderType
];

$excelUrl =
    'admin_invoices_excel.php?' .
    http_build_query(
        $exportParams
    );

$pdfUrl =
    'admin_invoices_pdf.php?' .
    http_build_query(
        $exportParams
    );

/* =========================================================
   HELPER PAGINATION
========================================================= */

function pageUrl(
    int $pageNumber
): string {

    global
        $lang,
        $search,
        $from,
        $to,
        $orderType;

    return '?' .
        http_build_query([

            'lang' =>
                $lang,

            'search' =>
                $search,

            'from' =>
                $from,

            'to' =>
                $to,

            'order_type' =>
                $orderType,

            'page' =>
                $pageNumber
        ]);
}

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($tr['title']) ?>
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f4f7fb;

    color:#1f2937;

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;
}

.page{

    max-width:1550px;

    margin:30px auto;

    padding:0 18px;
}

/* HEADER */

.header{

    background:
        linear-gradient(
            135deg,
            #007bff,
            #0047ab
        );

    color:#fff;

    border-radius:20px;

    padding:25px;

    margin-bottom:20px;

    text-align:center;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.12);
}

.logo{

    width:78px;

    height:78px;

    object-fit:contain;

    background:#fff;

    padding:8px;

    border-radius:18px;
}

.company{

    margin-top:9px;

    font-size:25px;

    font-weight:800;
}

.title{

    font-size:19px;

    margin-top:4px;

    opacity:.95;
}

.subtitle{

    font-size:12px;

    margin-top:5px;

    opacity:.85;
}

/* LANGUAGE */

.topbar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:10px;

    flex-wrap:wrap;

    margin-bottom:15px;
}

.lang-switch{

    display:flex;

    gap:6px;
}

.lang-switch a{

    text-decoration:none;

    padding:8px 14px;

    border-radius:9px;

    background:#fff;

    border:1px solid #ddd;

    color:#007bff;

    font-weight:700;
}

.lang-switch a:hover{

    background:#007bff;

    color:#fff;
}

/* ACTIONS */

.actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;
}

.actions .btn{

    border-radius:9px;

    font-weight:700;
}

/* STATS */

.stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:14px;

    margin-bottom:20px;
}

.stat{

    background:#fff;

    border-radius:16px;

    padding:18px;

    min-height:125px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.07);

    position:relative;

    overflow:hidden;
}

.stat-icon{

    width:45px;

    height:45px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:12px;

    color:#fff;

    margin-bottom:10px;

    font-size:20px;
}

.stat-title{

    color:#6b7280;

    font-size:12px;

    font-weight:700;
}

.stat-value{

    margin-top:4px;

    font-size:24px;

    font-weight:800;
}

.blue .stat-icon{

    background:#0d6efd;
}

.green .stat-icon{

    background:#198754;
}

.orange .stat-icon{

    background:#fd7e14;
}

.purple .stat-icon{

    background:#6f42c1;
}

/* FILTER */

.filter-card{

    background:#fff;

    padding:20px;

    border-radius:18px;

    margin-bottom:20px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.06);
}

.form-control,
.form-select{

    min-height:43px;

    border-radius:9px;
}

/* TABLE */

.table-card{

    background:#fff;

    padding:18px;

    border-radius:18px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.06);
}

.table{

    margin-bottom:0;
}

.table th{

    background:#007bff !important;

    color:#fff;

    white-space:nowrap;

    font-size:12px;

    padding:13px;
}

.table td{

    font-size:12px;

    padding:12px;

    vertical-align:middle;
}

.table tbody tr:hover{

    background:#f7fbff;
}

.money{

    color:#198754;

    font-weight:800;
}

.invoice-no{

    color:#0d6efd;

    font-weight:800;
}

/* PAGINATION */

.pagination{

    margin:20px 0 0;

}

.page-link{

    border-radius:8px !important;

    margin:0 2px;
}

/* EMPTY */

.empty{

    padding:50px;

    text-align:center;

    color:#777;
}

.empty i{

    display:block;

    font-size:42px;

    margin-bottom:10px;
}

/* MOBILE */

@media(max-width:1000px){

    .stats{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:600px){

    .stats{

        grid-template-columns:
            1fr;
    }

    .company{

        font-size:20px;
    }

    .title{

        font-size:16px;
    }
}

/* PRINT */

@media print{

    .no-print{

        display:none !important;
    }

    body{

        background:#fff;
    }

    .page{

        max-width:100%;

        padding:0;
    }

    .header,
    .stat,
    .table-card{

        box-shadow:none;
    }

}

</style>

</head>

<body>

<div class="page">

<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="topbar no-print">

<div class="lang-switch">

<a
    href="?<?= http_build_query([
        'lang'=>'ar',
        'search'=>$search,
        'from'=>$from,
        'to'=>$to,
        'order_type'=>$orderType,
        'page'=>1
    ]) ?>"
>
    🇸🇦 العربية
</a>

<a
    href="?<?= http_build_query([
        'lang'=>'en',
        'search'=>$search,
        'from'=>$from,
        'to'=>$to,
        'order_type'=>$orderType,
        'page'=>1
    ]) ?>"
>
    🇬🇧 English
</a>

</div>

<div class="actions">

<a
    href="<?= htmlspecialchars($excelUrl) ?>"
    class="btn btn-success"
>
    <i class="bi bi-file-earmark-excel"></i>
    <?= $tr['excel'] ?>
</a>

<a
    href="<?= htmlspecialchars($pdfUrl) ?>"
    target="_blank"
    class="btn btn-danger"
>
    <i class="bi bi-file-earmark-pdf"></i>
    <?= $tr['pdf'] ?>
</a>

<button
    type="button"
    onclick="window.print()"
    class="btn btn-warning"
>
    <i class="bi bi-printer"></i>
    <?= $tr['print'] ?>
</button>

</div>

</div>

<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<img
    src="../img/logo.jpg"
    alt="Logo"
    class="logo"
>

<div class="company">

<?= htmlspecialchars(
    $tr['company']
) ?>

</div>

<div class="title">

🧾 <?= htmlspecialchars(
    $tr['title']
) ?>

</div>

<div class="subtitle">

<?= htmlspecialchars(
    $tr['subtitle']
) ?>

</div>

</div>

<!-- =====================================================
     STATS
===================================================== -->

<div class="stats no-print">

<div class="stat blue">

<div class="stat-icon">

<i class="bi bi-receipt"></i>

</div>

<div class="stat-title">

<?= $tr['count'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $totalInvoices
) ?>

</div>

</div>

<div class="stat green">

<div class="stat-icon">

<i class="bi bi-cash-stack"></i>

</div>

<div class="stat-title">

<?= $tr['sum'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $totalAmount,
    2
) ?>

<?= $tr['sar'] ?>

</div>

</div>

<div class="stat orange">

<div class="stat-icon">

<i class="bi bi-calculator"></i>

</div>

<div class="stat-title">

<?= $tr['average'] ?>

</div>

<div class="stat-value">

<?= number_format(
    $averageInvoice,
    2
) ?>

<?= $tr['sar'] ?>

</div>

</div>

<div class="stat purple">

<div class="stat-icon">

<i class="bi bi-filter-circle"></i>

</div>

<div class="stat-title">

<?= $tr['filtered'] ?>

</div>

<div class="stat-value">

<?= count($invoiceRows) ?>

</div>

</div>

</div>

<!-- =====================================================
     FILTERS
===================================================== -->

<div class="filter-card no-print">

<form method="GET">

<input
    type="hidden"
    name="lang"
    value="<?= htmlspecialchars($lang) ?>"
>

<div class="row g-3">

<div class="col-lg-3 col-md-6">

<label class="form-label">

<?= $tr['search'] ?>

</label>

<input
    type="text"
    name="search"
    class="form-control"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="<?= htmlspecialchars($tr['search_placeholder']) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['from'] ?>

</label>

<input
    type="date"
    name="from"
    class="form-control"
    value="<?= htmlspecialchars($from) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['to'] ?>

</label>

<input
    type="date"
    name="to"
    class="form-control"
    value="<?= htmlspecialchars($to) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['type'] ?>

</label>

<select
    name="order_type"
    class="form-select"
>

<option value="">

<?= $tr['all_types'] ?>

</option>

<?php foreach (
    $orderTypes
    as $type
): ?>

<option
    value="<?= htmlspecialchars($type) ?>"
    <?= $orderType === $type
        ? 'selected'
        : ''
    ?>
>

<?= htmlspecialchars($type) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="col-lg-1 col-md-6 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>

<i class="bi bi-search"></i>

</button>

</div>

<div class="col-lg-2 col-md-6 d-flex align-items-end">

<a
    href="?lang=<?= urlencode($lang) ?>"
    class="btn btn-outline-secondary w-100"
>

<i class="bi bi-arrow-counterclockwise"></i>

<?= $tr['reset'] ?>

</a>

</div>

</div>

</form>

</div>

<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-card">

<div class="d-flex justify-content-between align-items-center mb-3">

<h4 class="mb-0">

<i class="bi bi-table"></i>

<?= $tr['title'] ?>

</h4>

<span class="badge bg-primary">

<?= $tr['count'] ?>:

<?= number_format($totalInvoices) ?>

</span>

</div>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead>

<tr>

<th>#</th>

<th>
<?= $tr['invoice_no'] ?>
</th>

<th>
<?= $tr['invoice_date'] ?>
</th>

<th>
<?= $tr['customer'] ?>
</th>

<th>
<?= $tr['phone'] ?>
</th>

<th>
<?= $tr['total'] ?>
</th>

<th>
<?= $tr['type'] ?>
</th>

<th class="no-print">
<?= $tr['details'] ?>
</th>

</tr>

</thead>

<tbody>

<?php if (
    empty($invoiceRows)
): ?>

<tr>

<td colspan="8">

<div class="empty">

<i class="bi bi-receipt-cutoff"></i>

<?= htmlspecialchars(
    $tr['no_data']
) ?>

</div>

</td>

</tr>

<?php else: ?>

<?php foreach (
    $invoiceRows
    as $index => $row
): ?>

<tr>

<td>

<?= (($page - 1) * $limit)
    + $index
    + 1 ?>

</td>

<td class="invoice-no">

<?= htmlspecialchars(
    $row['invoice_number']
    ?? '-'
) ?>

</td>

<td>

<?= htmlspecialchars(
    $row['created_at']
    ?? '-'
) ?>

</td>

<td>

<?= htmlspecialchars(
    $row['full_name']
    ?? '-'
) ?>

</td>

<td>

<?= htmlspecialchars(
    $row['phone']
    ?? '-'
) ?>

</td>

<td class="money">

<?= number_format(
    (float)(
        $row['total_with_vat']
        ?? 0
    ),
    2
) ?>

<?= $tr['sar'] ?>

</td>

<td>

<span class="badge bg-secondary">

<?= htmlspecialchars(
    $row['order_type']
    ?? '-'
) ?>

</span>

</td>

<td class="no-print">

<a
    href="admin_invoice_view.php?id=<?= (int)$row['id'] ?>"
    class="btn btn-info btn-sm text-white"
>

<i class="bi bi-eye"></i>

<?= $tr['view'] ?>

</a>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

<?php if (
    !empty($invoiceRows)
): ?>

<tfoot>

<tr class="table-success">

<th colspan="5" class="text-end">

<?= $tr['sum'] ?>

</th>

<th>

<?= number_format(
    $totalAmount,
    2
) ?>

<?= $tr['sar'] ?>

</th>

<th colspan="2"></th>

</tr>

</tfoot>

<?php endif; ?>

</table>

</div>

<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if (
    $totalPages > 1
): ?>

<nav
    class="no-print"
    aria-label="Pagination"
>

<ul class="pagination justify-content-center">

<li
    class="page-item
    <?= $page <= 1 ? 'disabled' : '' ?>"
>

<a
    class="page-link"
    href="<?= $page > 1
        ? htmlspecialchars(
            pageUrl($page - 1)
        )
        : '#'
    ?>"
>

<?= $tr['previous'] ?>

</a>

</li>

<?php

$startPage =
    max(
        1,
        $page - 2
    );

$endPage =
    min(
        $totalPages,
        $page + 2
    );

for (
    $p = $startPage;
    $p <= $endPage;
    $p++
):
?>

<li
    class="page-item
    <?= $p === $page ? 'active' : '' ?>"
>

<a
    class="page-link"
    href="<?= htmlspecialchars(
        pageUrl($p)
    ) ?>"
>

<?= $p ?>

</a>

</li>

<?php endfor; ?>

<li
    class="page-item
    <?= $page >= $totalPages
        ? 'disabled'
        : '' ?>"
>

<a
    class="page-link"
    href="<?= $page < $totalPages
        ? htmlspecialchars(
            pageUrl($page + 1)
        )
        : '#'
    ?>"
>

<?= $tr['next'] ?>

</a>

</li>

</ul>

<div class="text-center text-muted">

<?= $tr['page'] ?>

<?= $page ?>

/

<?= $totalPages ?>

</div>

</nav>

<?php endif; ?>

</div>

</div>

</body>

</html>
