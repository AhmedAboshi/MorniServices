<?php

session_start();

include('include/connected.php');

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   حماية المستخدم
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}

$user_id = (int)$_SESSION['user_id'];


/* =========================================================
   التحقق من الطلب
========================================================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die("رقم الطلب غير صحيح");

}

$order_id = (int)$_GET['id'];

if ($order_id <= 0) {

    die("رقم الطلب غير صحيح");

}


/* =========================================================
   جلب الطلب والتأكد أن الطلب يخص المستخدم
========================================================= */

$stmt = $con->prepare("
    SELECT
        orders.*,

        drivers.name AS provider_name,
        drivers.phone AS provider_phone

    FROM orders

    LEFT JOIN drivers
        ON orders.driver_id = drivers.id

    WHERE orders.id = ?
      AND orders.user_id = ?

    LIMIT 1
");

if (!$stmt) {

    die(
        "Database Error: "
        . htmlspecialchars($con->error)
    );

}

$stmt->bind_param(
    "ii",
    $order_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    die("الطلب غير موجود أو ليس لديك صلاحية لعرضه");

}

$order = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   نوع الطلب
========================================================= */

$order_type = strtolower(
    trim(
        $order['order_type'] ?? 'cart'
    )
);


/* =========================================================
   الحالة
========================================================= */

$status = strtolower(
    trim(
        $order['status'] ?? 'pending'
    )
);

$status_text = 'غير محدد';

switch ($status) {

    case 'pending':
        $status_text = 'قيد الانتظار';
        break;

    case 'assigned':
        $status_text = 'تم تعيين المزود';
        break;

    case 'done':
        $status_text = 'مكتمل';
        break;

    case 'cancelled':
        $status_text = 'ملغي';
        break;

}


/* =========================================================
   منع عرض فاتورة للطلب الملغي
========================================================= */

if ($status === 'cancelled') {

    die("لا يمكن إصدار فاتورة لطلب ملغي");

}


/* =========================================================
   جلب الفاتورة بواسطة رقم الطلب
========================================================= */

$invoice = null;

$stmt_invoice = $con->prepare("
    SELECT *
    FROM invoices
    WHERE order_id = ?
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmt_invoice) {

    die(
        "Invoice Database Error: "
        . htmlspecialchars($con->error)
    );

}

$stmt_invoice->bind_param(
    "i",
    $order_id
);

$stmt_invoice->execute();

$invoice_result =
    $stmt_invoice->get_result();

if ($invoice_result->num_rows > 0) {

    $invoice =
        $invoice_result->fetch_assoc();

}

$stmt_invoice->close();


/* =========================================================
   إذا لم توجد فاتورة
========================================================= */

if (!$invoice) {

    ?>

    <!DOCTYPE html>

    <html lang="ar" dir="rtl">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            الفاتورة غير متاحة
        </title>

        <style>

            body{

                margin:0;

                font-family:
                    Arial,
                    Tahoma,
                    sans-serif;

                background:#f5f7fb;

            }

            .box{

                max-width:600px;

                margin:100px auto;

                background:#fff;

                padding:40px;

                border-radius:18px;

                text-align:center;

                box-shadow:
                    0 5px 20px
                    rgba(0,0,0,.08);

            }

            .icon{

                font-size:60px;

                margin-bottom:20px;

            }

            h2{

                margin-bottom:10px;

            }

            p{

                color:#777;

                line-height:1.8;

            }

            .btn{

                display:inline-block;

                margin-top:20px;

                padding:12px 25px;

                background:#00a6bd;

                color:#fff;

                text-decoration:none;

                border-radius:9px;

            }

        </style>

    </head>

    <body>

        <div class="box">

            <div class="icon">
                🧾
            </div>

            <h2>
                الفاتورة غير متاحة حالياً
            </h2>

            <p>

                لا توجد فاتورة مسجلة لهذا الطلب حتى الآن.

                <?php if ($status !== 'done'): ?>

                    <br>

                    سيتم إصدار الفاتورة بعد اكتمال الطلب.

                <?php endif; ?>

            </p>

            <a
                href="myorderdetails.php?id=<?= $order_id ?>"
                class="btn"
            >

                ← العودة إلى تفاصيل الطلب

            </a>

        </div>

    </body>

    </html>

    <?php

    exit();

}


/* =========================================================
   بيانات الفاتورة
========================================================= */

$invoice_id =
    (int)($invoice['id'] ?? 0);

$invoice_number =
    $invoice['invoice_number']
    ?? ('INV-' . $invoice_id);


/* =========================================================
   حساب الفاتورة من orders.price
   ملاحظة:
   orders.price شامل ضريبة القيمة المضافة 15%
========================================================= */

$grand_total = (float)($order['price'] ?? 0);

/*
 * المبلغ قبل الضريبة
 */
$subtotal = round(
    $grand_total / 1.15,
    2
);

/*
 * قيمة الضريبة
 */
$vat = round(
    $grand_total - $subtotal,
    2
);

/*
 * الإجمالي النهائي
 * وهو نفس orders.price
 */
$total_with_vat = round(
    $grand_total,
    2
);

/* =========================================================
   بيانات العميل
========================================================= */

$customer_name =
    $order['full_name']
    ?? '---';

$customer_phone =
    $order['phone']
    ?? '---';

$customer_email =
    $order['email']
    ?? '---';

$customer_city =
    $order['city']
    ?? '---';

$customer_address =
    $order['address']
    ?? '---';


/* =========================================================
   تاريخ الطلب
========================================================= */

$created_at = '---';

if (!empty($order['created_at'])) {

    $timestamp =
        strtotime(
            $order['created_at']
        );

    if ($timestamp !== false) {

        $created_at =
            date(
                'Y-m-d H:i',
                $timestamp
            );

    }

}


/* =========================================================
   المنتجات - للسلة فقط
========================================================= */

$cart_items = [];

if ($order_type === 'cart') {

    $stmt_items = $con->prepare("

        SELECT

            order_details.quantity,
            order_details.price,

            product.proname

        FROM order_details

        LEFT JOIN product
            ON order_details.product_id =
               product.id

        WHERE order_details.order_id = ?

        ORDER BY order_details.id ASC

    ");

    if ($stmt_items) {

        $stmt_items->bind_param(
            "i",
            $order_id
        );

        $stmt_items->execute();

        $items_result =
            $stmt_items->get_result();

        while (
            $item =
            $items_result->fetch_assoc()
        ) {

            $cart_items[] =
                $item;

        }

        $stmt_items->close();

    }

}


/* =========================================================
   نوع الفاتورة
========================================================= */

if ($order_type === 'cart') {

    $invoice_type_text =
        '🛒 فاتورة سلة خدمات';

} elseif ($order_type === 'tow') {

    $invoice_type_text =
        '🚚 فاتورة خدمة سطحة';

} else {

    $invoice_type_text =
        '📄 فاتورة خدمة';

}

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    فاتورة <?= htmlspecialchars($invoice_number) ?>
</title>


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f1f4f8;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

    color:#333;

}


/* =========================================================
   PAGE
========================================================= */

.invoice-page{

    width:95%;

    max-width:900px;

    margin:30px auto;

}


/* =========================================================
   INVOICE
========================================================= */

.invoice{

    background:#fff;

    border-radius:18px;

    padding:35px;

    box-shadow:
        0 5px 25px
        rgba(0,0,0,.08);

}


/* =========================================================
   HEADER
========================================================= */

.invoice-header{

    display:flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    gap:20px;

    border-bottom:
        2px solid #eee;

    padding-bottom:25px;

}


.company-name{

    font-size:27px;

    font-weight:bold;

    color:#00a6bd;

}


.company-sub{

    color:#777;

    margin-top:7px;

}


.invoice-title{

    text-align:left;

}


.invoice-title h1{

    margin:0;

    font-size:30px;

}


.invoice-number{

    margin-top:8px;

    color:#777;

}


/* =========================================================
   TYPE
========================================================= */

.invoice-type{

    display:inline-block;

    margin-top:12px;

    padding:7px 15px;

    border-radius:20px;

    font-weight:bold;

    background:#e7f7fc;

    color:#087c9c;

}

.tow-type{

    background:#fff1df;

    color:#d97706;

}


/* =========================================================
   CUSTOMER
========================================================= */

.info-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:15px;

    margin-top:25px;

}


.info-card{

    background:#f8fafc;

    border-radius:12px;

    padding:18px;

}


.info-card h3{

    margin:
        0 0 12px;

    font-size:17px;

}


.info-row{

    display:flex;

    justify-content:
        space-between;

    gap:15px;

    padding:7px 0;

}


.label{

    color:#777;

}


.value{

    font-weight:bold;

    text-align:left;

}


/* =========================================================
   TOW ROUTE
========================================================= */

.route{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:20px;

    margin:25px 0;

}


.city{

    background:#f5f7fb;

    padding:15px 25px;

    border-radius:12px;

    font-weight:bold;

    min-width:150px;

    text-align:center;

}


.arrow{

    font-size:25px;

    color:#00a6bd;

}


/* =========================================================
   TABLE
========================================================= */

.invoice-table{

    width:100%;

    border-collapse:
        collapse;

    margin-top:30px;

}


.invoice-table th{

    background:#00a6bd;

    color:#fff;

    padding:13px;

}


.invoice-table td{

    padding:13px;

    text-align:center;

    border-bottom:
        1px solid #eee;

}


/* =========================================================
   TOTALS
========================================================= */

.totals{

    width:350px;

    max-width:100%;

    margin:
        25px 0 0 auto;

}


.total-row{

    display:flex;

    justify-content:
        space-between;

    padding:10px 0;

    border-bottom:
        1px solid #eee;

}


.final-total{

    font-size:21px;

    font-weight:bold;

    color:#198754;

    border-bottom:none;

}


/* =========================================================
   FOOTER
========================================================= */

.invoice-footer{

    margin-top:35px;

    padding-top:20px;

    border-top:
        1px solid #eee;

    text-align:center;

    color:#777;

    line-height:1.8;

}


/* =========================================================
   BUTTONS
========================================================= */

.actions{

    display:flex;

    justify-content:center;

    gap:10px;

    flex-wrap:wrap;

    margin-top:20px;

}


.btn{

    display:inline-block;

    padding:12px 25px;

    border-radius:9px;

    text-decoration:none;

    font-weight:bold;

}


.print-btn{

    background:#198754;

    color:#fff;

    border:none;

    cursor:pointer;

    font-size:15px;

}


.back-btn{

    background:#333;

    color:#fff;

}


/* =========================================================
   PRINT
========================================================= */

@media print{

    body{

        background:#fff;

    }

    .invoice-page{

        width:100%;

        max-width:none;

        margin:0;

    }

    .invoice{

        box-shadow:none;

        border-radius:0;

        padding:20px;

    }

    .actions{

        display:none;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:700px){

    .invoice-page{

        width:94%;

        margin:15px auto;

    }

    .invoice{

        padding:18px;

    }

    .invoice-header{

        flex-direction:column;

    }

    .invoice-title{

        text-align:right;

    }

    .info-grid{

        grid-template-columns:1fr;

    }

    .route{

        flex-direction:column;

    }

    .arrow{

        transform:
            rotate(90deg);

    }

    .invoice-table th,
    .invoice-table td{

        padding:8px;

        font-size:13px;

    }

}

</style>

</head>


<body>


<div class="invoice-page">


<div class="invoice">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="invoice-header">


<div>

    <div class="company-name">
        منصة الشرق
    </div>

    <div class="company-sub">
        للخدمات وإدارة الأسطول
    </div>

    <span
        class="invoice-type
        <?= $order_type === 'tow'
            ? 'tow-type'
            : ''
        ?>"
    >

        <?= $invoice_type_text ?>

    </span>

</div>


<div class="invoice-title">

    <h1>
        فاتورة
    </h1>

    <div class="invoice-number">

        رقم الفاتورة:

        <strong>
            <?= htmlspecialchars(
                $invoice_number
            ) ?>
        </strong>

    </div>

    <div class="invoice-number">

        رقم الطلب:

        <strong>
            #<?= $order_id ?>
        </strong>

    </div>

    <div class="invoice-number">

        التاريخ:

        <?= htmlspecialchars(
            $created_at
        ) ?>

    </div>

</div>


</div>


<!-- =====================================================
     CUSTOMER
===================================================== -->

<div class="info-grid">


<div class="info-card">

    <h3>
        👤 بيانات العميل
    </h3>


    <div class="info-row">

        <span class="label">
            الاسم
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $customer_name
            ) ?>
        </span>

    </div>


    <div class="info-row">

        <span class="label">
            الجوال
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $customer_phone
            ) ?>
        </span>

    </div>


    <div class="info-row">

        <span class="label">
            البريد
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $customer_email
            ) ?>
        </span>

    </div>

</div>


<div class="info-card">

    <h3>
        📍 بيانات الطلب
    </h3>


    <div class="info-row">

        <span class="label">
            المدينة
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $customer_city
            ) ?>
        </span>

    </div>


    <div class="info-row">

        <span class="label">
            طريقة الدفع
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $order['payment_method']
                ?? '---'
            ) ?>
        </span>

    </div>


    <div class="info-row">

        <span class="label">
            الحالة
        </span>

        <span class="value">
            <?= htmlspecialchars(
                $status_text
            ) ?>
        </span>

    </div>

</div>


</div>


<!-- =====================================================
     CART
===================================================== -->

<?php if ($order_type === 'cart'): ?>


<h3 style="margin-top:30px;">
    🛒 تفاصيل الخدمات
</h3>


<table class="invoice-table">

<thead>

<tr>

    <th>
        الخدمة
    </th>

    <th>
        الكمية
    </th>

    <th>
        سعر الوحدة
    </th>

    <th>
        الإجمالي
    </th>

</tr>

</thead>


<tbody>

<?php if (!empty($cart_items)): ?>


<?php foreach ($cart_items as $item): ?>


<?php

$quantity =
    (int)(
        $item['quantity']
        ?? 0
    );

$unit_price =
    (float)(
        $item['price']
        ?? 0
    );

$line_total =
    $quantity *
    $unit_price;

?>


<tr>

    <td>

        <?= htmlspecialchars(
            $item['proname']
            ?? 'خدمة'
        ) ?>

    </td>

    <td>
        <?= $quantity ?>
    </td>

    <td>

        <?= number_format(
            $unit_price,
            2
        ) ?>

        ريال

    </td>

    <td>

        <?= number_format(
            $line_total,
            2
        ) ?>

        ريال

    </td>

</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

    <td colspan="4">

        لا توجد تفاصيل للخدمات.

    </td>

</tr>


<?php endif; ?>


</tbody>

</table>


<?php endif; ?>


<!-- =====================================================
     TOW
===================================================== -->

<?php if ($order_type === 'tow'): ?>


<h3 style="margin-top:30px;">
    🚚 تفاصيل خدمة السطحة
</h3>


<div class="route">


<div class="city">

    📍 من

    <br>

    <?= htmlspecialchars(
        $order['from_city']
        ?? '---'
    ) ?>

</div>


<div class="arrow">
    ←
</div>


<div class="city">

    📍 إلى

    <br>

    <?= htmlspecialchars(
        $order['to_city']
        ?? '---'
    ) ?>

</div>


</div>


<table class="invoice-table">

<tr>

    <th>
        البيان
    </th>

    <th>
        التفاصيل
    </th>

</tr>


<tr>

    <td>
        نوع المركبة
    </td>

    <td>
        <?= htmlspecialchars(
            $order['car_type']
            ?? '---'
        ) ?>
    </td>

</tr>


<tr>

    <td>
        المسافة
    </td>

    <td>

        <?= number_format(
            (float)(
                $order['distance']
                ?? 0
            ),
            1
        ) ?>

        كم

    </td>

</tr>


<tr>

    <td>
        نوع الحجز
    </td>

    <td>

        <?php

        if (
            ($order['booking_type']
            ?? '') === 'scheduled'
        ) {

            echo '📅 حجز مجدول';

        } else {

            echo '🚀 طلب فوري';

        }

        ?>

    </td>

</tr>


<?php if (
    ($order['booking_type'] ?? '')
    === 'scheduled'
): ?>


<tr>

    <td>
        التاريخ المجدول
    </td>

    <td>

        <?= htmlspecialchars(
            $order['scheduled_date']
            ?? '---'
        ) ?>

    </td>

</tr>


<tr>

    <td>
        الوقت
    </td>

    <td>

        <?= htmlspecialchars(
            $order['scheduled_time']
            ?? '---'
        ) ?>

    </td>

</tr>


<?php endif; ?>


</table>


<?php endif; ?>


<!-- =====================================================
     TOTALS
===================================================== -->

<div class="totals">


<div class="total-row">

    <span>
        المبلغ قبل الضريبة
    </span>

    <strong>

        <?= number_format(
            $subtotal,
            2
        ) ?>

        ريال

    </strong>

</div>


<div class="total-row">

    <span>
        ضريبة القيمة المضافة 15%
    </span>

    <strong>

        <?= number_format(
            $vat,
            2
        ) ?>

        ريال

    </strong>

</div>


<div class="total-row final-total">

    <span>
        الإجمالي شامل الضريبة
    </span>

    <strong>

        <?= number_format(
            $total_with_vat,
            2
        ) ?>

        ريال

    </strong>

</div>


</div>


<!-- =====================================================
     PROVIDER
===================================================== -->

<?php if (
    !empty($order['provider_name'])
): ?>


<div class="info-card" style="margin-top:25px;">

    <h3>
        👨‍🔧 مزود الخدمة
    </h3>


    <div class="info-row">

        <span class="label">
            الاسم
        </span>

        <span class="value">

            <?= htmlspecialchars(
                $order['provider_name']
            ) ?>

        </span>

    </div>


    <?php if (
        !empty($order['provider_phone'])
    ): ?>

    <div class="info-row">

        <span class="label">
            الجوال
        </span>

        <span class="value">

            <?= htmlspecialchars(
                $order['provider_phone']
            ) ?>

        </span>

    </div>

    <?php endif; ?>


</div>


<?php endif; ?>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="invoice-footer">

    نشكرك لاختيارك منصة الشرق

    <br>

    هذه الفاتورة صادرة إلكترونياً من النظام.

</div>


</div>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="actions">


<button
    type="button"
    class="btn print-btn"
    onclick="window.print()"
>

    🖨️ طباعة / حفظ PDF

</button>


<a
    href="myorderdetails.php?id=<?= $order_id ?>"
    class="btn back-btn"
>

    ← تفاصيل الطلب

</a>


</div>


</div>


</body>

</html>