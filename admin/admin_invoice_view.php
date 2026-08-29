
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$_SESSION['lang'] = $lang;

/* =========================================================
   INVOICE ID
========================================================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {

    die(
        $lang === 'ar'
            ? 'رقم الفاتورة غير صحيح'
            : 'Invalid invoice ID'
    );
}

/* =========================================================
   TRANSLATION
========================================================= */

$t = [

    'ar' => [

        'title'        => 'تفاصيل الفاتورة',

        'invoice'      => 'رقم الفاتورة',
        'date'         => 'تاريخ الفاتورة',

        'customer'     => 'العميل',
        'phone'        => 'الهاتف',

        'type'         => 'نوع الطلب',

        'from'         => 'من',
        'to'           => 'إلى',

        'distance'     => 'المسافة',
        'price'        => 'السعر',

        'product'      => 'المنتج',
        'qty'          => 'الكمية',

        'subtotal'     => 'المبلغ قبل الضريبة',
        'vat'          => 'ضريبة القيمة المضافة 15%',
        'final'        => 'الإجمالي النهائي',

        'details'      => 'تفاصيل الطلب',
        'customer_info'=> 'بيانات العميل',

        'back'         => 'رجوع',
        'print'        => 'طباعة',

        'sar'          => 'ريال',

        'cart'         => 'طلب منتجات',
        'intercity'    => 'نقل بين المدن',
        'tow'          => 'سطحة / سحب',

        'invoice_not_found'
                     => 'الفاتورة غير موجودة',

        'order_not_found'
                     => 'الطلب المرتبط بالفاتورة غير موجود',

        'no_items'     => 'لا توجد منتجات في الفاتورة',

        'company'      => 'شركة الشرق لخدمات السيارات'
    ],

    'en' => [

        'title'        => 'Invoice Details',

        'invoice'      => 'Invoice Number',
        'date'         => 'Invoice Date',

        'customer'     => 'Customer',
        'phone'        => 'Phone',

        'type'         => 'Order Type',

        'from'         => 'From',
        'to'           => 'To',

        'distance'     => 'Distance',
        'price'        => 'Price',

        'product'      => 'Product',
        'qty'          => 'Quantity',

        'subtotal'     => 'Subtotal',
        'vat'          => 'VAT 15%',
        'final'        => 'Final Total',

        'details'      => 'Order Details',
        'customer_info'=> 'Customer Information',

        'back'         => 'Back',
        'print'        => 'Print',

        'sar'          => 'SAR',

        'cart'         => 'Products Order',
        'intercity'    => 'Intercity Transport',
        'tow'          => 'Tow / Roadside',

        'invoice_not_found'
                     => 'Invoice not found',

        'order_not_found'
                     => 'Related order not found',

        'no_items'     => 'No products found in this invoice',

        'company'      => 'Al Sharq Automotive Services Company'
    ]
];

$tr = $t[$lang];

/* =========================================================
   INVOICE
========================================================= */

$stmt = $con->prepare("
    SELECT *
    FROM invoices
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param(
    'i',
    $id
);

$stmt->execute();

$invoice =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$invoice) {

    die(
        htmlspecialchars(
            $tr['invoice_not_found']
        )
    );
}

/* =========================================================
   ORDER
========================================================= */

$orderId =
    (int)($invoice['order_id'] ?? 0);

if ($orderId <= 0) {

    die(
        htmlspecialchars(
            $tr['order_not_found']
        )
    );
}

$stmt = $con->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    'i',
    $orderId
);

$stmt->execute();

$order =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$order) {

    die(
        htmlspecialchars(
            $tr['order_not_found']
        )
    );
}

/* =========================================================
   ORDER TYPE
========================================================= */

$type =
    $order['order_type']
    ?? '';

$typeLabels = [

    'cart' =>
        $tr['cart'],

    'intercity' =>
        $tr['intercity'],

    'tow' =>
        $tr['tow']
];

$typeLabel =
    $typeLabels[$type]
    ?? $type
    ?? '-';

/* =========================================================
   ITEMS
========================================================= */

$items = [];

$subtotal = 0;

if ($type === 'cart') {

    $stmt = $con->prepare("
        SELECT *
        FROM order_details
        WHERE order_id = ?
        ORDER BY id ASC
    ");

    $stmt->bind_param(
        'i',
        $orderId
    );

    $stmt->execute();

    $itemsResult =
        $stmt->get_result();

    while (
        $item =
        $itemsResult->fetch_assoc()
    ) {

        $quantity =
            (float)(
                $item['quantity']
                ?? 0
            );

        $price =
            (float)(
                $item['price']
                ?? 0
            );

        $itemTotal =
            $quantity * $price;

        $item['_calculated_total'] =
            $itemTotal;

        $subtotal +=
            $itemTotal;

        $items[] =
            $item;
    }

    $stmt->close();

} else {

    $subtotal =
        (float)(
            $order['price']
            ?? 0
        );
}

/* =========================================================
   VAT
========================================================= */

$vatRate = 0.15;

$vat =
    $subtotal *
    $vatRate;

/* =========================================================
   FINAL TOTAL
========================================================= */

/* =========================================================
   VAT + FINAL TOTAL
========================================================= */

$subtotal = (float)$subtotal;

/* ضريبة القيمة المضافة 15% */
$vatRate = 0.15;

$vat = round(
    $subtotal * $vatRate,
    2
);

/* الإجمالي النهائي */
$finalTotal = round(
    $subtotal + $vat,
    2
);

/* =========================================================
   بيانات العرض
========================================================= */

$invoiceNumber =
    $invoice['invoice_number']
    ?? '-';

$invoiceDate =
    $invoice['created_at']
    ?? '-';

$customerName =
    $order['full_name']
    ?? '-';

$phone =
    $order['phone']
    ?? '-';

$fromCity =
    $order['from_city']
    ?? '-';

$toCity =
    $order['to_city']
    ?? '-';

$distance =
    $order['distance']
    ?? 0;

$orderPrice =
    (float)(
        $order['price']
        ?? 0
    );

/* =========================================================
   BACK URL
========================================================= */

$backUrl =
    'admin_invoices.php?' .
    http_build_query([
        'lang' => $lang
    ]);

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
    <?= htmlspecialchars(
        $tr['title']
    ) ?>
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

    max-width:1150px;

    margin:30px auto;

    padding:0 18px;
}

/* =========================================================
   TOP BAR
========================================================= */

.topbar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    flex-wrap:wrap;

    gap:8px;

    margin-bottom:15px;
}

.lang-buttons,
.actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;
}

.lang-buttons a{

    text-decoration:none;

    padding:8px 14px;

    background:#fff;

    color:#007bff;

    border:1px solid #ddd;

    border-radius:9px;

    font-weight:700;
}

.lang-buttons a:hover{

    background:#007bff;

    color:#fff;
}

/* =========================================================
   INVOICE
========================================================= */

.invoice{

    background:#fff;

    border-radius:20px;

    overflow:hidden;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.08);
}

/* =========================================================
   HEADER
========================================================= */

.invoice-header{

    background:
        linear-gradient(
            135deg,
            #007bff,
            #0047ab
        );

    color:#fff;

    padding:28px;

    text-align:center;
}

.logo{

    width:85px;

    height:85px;

    object-fit:contain;

    background:#fff;

    padding:8px;

    border-radius:20px;

    margin-bottom:10px;
}

.company{

    font-size:24px;

    font-weight:800;
}

.document-title{

    font-size:19px;

    margin-top:4px;
}

/* =========================================================
   INVOICE NUMBER
========================================================= */

.invoice-badge{

    margin-top:12px;

    display:inline-block;

    padding:8px 18px;

    border-radius:30px;

    background:
        rgba(255,255,255,.18);

    border:
        1px solid
        rgba(255,255,255,.25);

    font-weight:700;
}

/* =========================================================
   CONTENT
========================================================= */

.content{

    padding:25px;
}

/* =========================================================
   INFO CARDS
========================================================= */

.section-title{

    font-size:17px;

    font-weight:800;

    margin-bottom:12px;

    display:flex;

    align-items:center;

    gap:8px;

    color:#0d6efd;
}

.info-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:12px;

    margin-bottom:25px;
}

.info-item{

    background:#f8fafc;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:13px;
}

.info-label{

    display:block;

    font-size:11px;

    color:#6b7280;

    margin-bottom:4px;
}

.info-value{

    font-size:14px;

    font-weight:700;

    word-break:break-word;
}

/* =========================================================
   ORDER TYPE
========================================================= */

.type-badge{

    display:inline-block;

    background:#0d6efd;

    color:#fff;

    padding:5px 12px;

    border-radius:20px;

    font-size:12px;

    font-weight:700;
}

/* =========================================================
   CART TABLE
========================================================= */

.table{

    margin-bottom:20px;
}

.table th{

    background:#007bff !important;

    color:#fff;

    font-size:12px;

    white-space:nowrap;
}

.table td{

    font-size:12px;

    vertical-align:middle;
}

.money{

    font-weight:800;

    color:#198754;
}

/* =========================================================
   ORDER DETAILS
========================================================= */

.order-box{

    background:#f8fafc;

    border:1px solid #e5e7eb;

    border-radius:14px;

    padding:18px;

    margin-bottom:25px;
}

.order-detail-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:12px;
}

/* =========================================================
   TOTALS
========================================================= */

.totals{

    max-width:520px;

    margin:
        25px
        0
        0
        auto;
}

.total-row{

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:12px 15px;

    border-bottom:1px solid #eee;

    font-size:14px;
}

.final-row{

    margin-top:5px;

    background:#e9f7ef;

    color:#198754;

    border-radius:12px;

    font-size:19px;

    font-weight:800;
}

/* =========================================================
   FOOTER
========================================================= */

.invoice-footer{

    border-top:1px solid #eee;

    padding:15px 25px;

    color:#777;

    font-size:11px;

    text-align:center;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px){

    .info-grid{

        grid-template-columns:
            repeat(2,1fr);
    }

    .order-detail-grid{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:600px){

    .page{

        margin:10px auto;

        padding:0 8px;
    }

    .content{

        padding:15px;
    }

    .info-grid,
    .order-detail-grid{

        grid-template-columns:1fr;
    }

    .company{

        font-size:19px;
    }

    .document-title{

        font-size:16px;
    }

    .actions{

        width:100%;
    }

    .actions .btn{

        flex:1;
    }

    .totals{

        max-width:100%;
    }
}

/* =========================================================
   PRINT
========================================================= */

@media print{

    body{

        background:#fff;

    }

    .no-print{

        display:none !important;
    }

    .page{

        max-width:100%;

        margin:0;

        padding:0;
    }

    .invoice{

        box-shadow:none;

        border:none;
    }

    .invoice-header{

        print-color-adjust:exact;

        -webkit-print-color-adjust:exact;
    }

    .table th{

        print-color-adjust:exact;

        -webkit-print-color-adjust:exact;
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

<div class="lang-buttons">

<a
    href="?<?= http_build_query([
        'id' => $id,
        'lang' => 'ar'
    ]) ?>"
>
    🇸🇦 العربية
</a>

<a
    href="?<?= http_build_query([
        'id' => $id,
        'lang' => 'en'
    ]) ?>"
>
    🇬🇧 English
</a>

</div>

<div class="actions">

<a
    href="<?= htmlspecialchars($backUrl) ?>"
    class="btn btn-outline-secondary"
>
    <i class="bi bi-arrow-right"></i>
    <?= $tr['back'] ?>
</a>

<button
    type="button"
    onclick="window.print()"
    class="btn btn-success"
>
    <i class="bi bi-printer"></i>
    <?= $tr['print'] ?>
</button>

</div>

</div>

<!-- =====================================================
     INVOICE
===================================================== -->

<div class="invoice">

<!-- HEADER -->

<div class="invoice-header">

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

<div class="document-title">

<?= htmlspecialchars(
    $tr['title']
) ?>

</div>

<div class="invoice-badge">

<?= htmlspecialchars(
    $tr['invoice']
) ?>

:
<?= htmlspecialchars(
    $invoiceNumber
) ?>

</div>

</div>

<div class="content">

<!-- =====================================================
     CUSTOMER INFORMATION
===================================================== -->

<div class="section-title">

<i class="bi bi-person-vcard"></i>

<?= $tr['customer_info'] ?>

</div>

<div class="info-grid">

<div class="info-item">

<span class="info-label">

<?= $tr['invoice'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $invoiceNumber
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['date'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $invoiceDate
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['customer'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $customerName
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['phone'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $phone
) ?>

</span>

</div>

</div>

<!-- =====================================================
     ORDER TYPE
===================================================== -->

<div class="info-grid">

<div class="info-item">

<span class="info-label">

<?= $tr['type'] ?>

</span>

<span class="info-value">

<span class="type-badge">

<?= htmlspecialchars(
    $typeLabel
) ?>

</span>

</span>

</div>

</div>

<!-- =====================================================
     ORDER DETAILS
===================================================== -->

<?php if ($type !== 'cart'): ?>

<div class="section-title">

<i class="bi bi-geo-alt"></i>

<?= $tr['details'] ?>

</div>

<div class="order-box">

<div class="order-detail-grid">

<div class="info-item">

<span class="info-label">

<?= $tr['from'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $fromCity
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['to'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $toCity
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['distance'] ?>

</span>

<span class="info-value">

<?= number_format(
    (float)$distance
) ?>

 KM

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['price'] ?>

</span>

<span class="info-value money">

<?= number_format(
    $orderPrice,
    2
) ?>

<?= $tr['sar'] ?>

</span>

</div>

</div>

</div>

<?php endif; ?>

<!-- =====================================================
     CART ITEMS
===================================================== -->

<?php if ($type === 'cart'): ?>

<div class="section-title">

<i class="bi bi-cart3"></i>

<?= $tr['details'] ?>

</div>

<?php if (empty($items)): ?>

<div class="alert alert-secondary">

<?= $tr['no_items'] ?>

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead>

<tr>

<th>
<?= $tr['product'] ?>
</th>

<th>
<?= $tr['qty'] ?>
</th>

<th>
<?= $tr['price'] ?>
</th>



</tr>

</thead>

<tbody>

<?php foreach (
    $items
    as $item
): ?>

<tr>

<td>

<?= htmlspecialchars(
    $item['product_id']
    ?? '-'
) ?>

</td>

<td>

<?= number_format(
    (float)(
        $item['quantity']
        ?? 0
    )
) ?>

</td>

<td>

<?= number_format(
    (float)(
        $item['price']
        ?? 0
    ),
    2
) ?>

<?= $tr['sar'] ?>

</td>



</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

<?php endif; ?>

<!-- =====================================================
     TOTALS
===================================================== -->

<div class="totals">

<div class="total-row">

<span>

<?= $tr['subtotal'] ?>

</span>

<strong>

<?= number_format(
    $subtotal,
    2
) ?>

<?= $tr['sar'] ?>

</strong>

</div>

<div class="total-row">

<span>

<?= $tr['vat'] ?>

</span>

<strong>

<?= number_format(
    $vat,
    2
) ?>

<?= $tr['sar'] ?>

</strong>

</div>

<div class="total-row final-row">

<span>

<?= $tr['final'] ?>

</span>

<strong>

<?= number_format(
    $finalTotal,
    2
) ?>

<?= $tr['sar'] ?>

</strong>

</div>

</div>

</div>

<div class="invoice-footer">

<?= htmlspecialchars(
    $tr['company']
) ?>

&nbsp; | &nbsp;

<?= htmlspecialchars(
    $invoiceDate
) ?>

</div>

</div>

</div>

</body>

</html>

