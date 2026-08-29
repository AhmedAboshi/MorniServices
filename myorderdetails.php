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
   التحقق من رقم الطلب
========================================================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    die("طلب غير موجود");

}

$order_id = (int)$_GET['id'];

if ($order_id <= 0) {

    die("رقم الطلب غير صحيح");

}


/* =========================================================
   جلب الطلب
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

    die("لا يوجد طلب أو ليس لديك صلاحية لعرضه");

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
   بيانات المزود
========================================================= */

$provider_name =
    trim(
        $order['provider_name'] ?? ''
    );

$provider_phone =
    trim(
        $order['provider_phone'] ?? ''
    );


/* =========================================================
   حالة الموافقة + حالة التنفيذ
========================================================= */

$approval_value = strtolower(
    trim(
        $order['approval_status'] ?? 'pending'
    )
);

$status_value = strtolower(
    trim(
        $order['status'] ?? 'pending'
    )
);


/* =========================================================
   تحديد الحالة الرئيسية التي تظهر للعميل
========================================================= */

$status_text = 'قيد الانتظار';
$status_class = 'pending';


/*
|--------------------------------------------------------------------------
| أولاً: الطلب المرفوض
|--------------------------------------------------------------------------
*/

if (
    $approval_value === 'rejected' ||
    $status_value === 'cancelled'
) {

    $status_text = '❌ تم رفض / إلغاء الطلب';
    $status_class = 'cancelled';

}


/*
|--------------------------------------------------------------------------
| ثانياً: الطلب مكتمل
|--------------------------------------------------------------------------
*/

elseif ($status_value === 'done') {

    $status_text = '🏆 الطلب مكتمل';
    $status_class = 'done';

}


/*
|--------------------------------------------------------------------------
| ثالثاً: تمت الموافقة وتم تعيين المزود
|--------------------------------------------------------------------------
*/

elseif (
    $approval_value === 'approved' &&
    $status_value === 'assigned'
) {

    $status_text = '🚚 تمت الموافقة وتم تعيين المزود';
    $status_class = 'assigned';

}


/*
|--------------------------------------------------------------------------
| رابعاً: تمت الموافقة ولكن لم يتم تعيين المزود
|--------------------------------------------------------------------------
*/

elseif ($approval_value === 'approved') {

    $status_text = '✅ تمت الموافقة على الطلب';
    $status_class = 'approved';

}


/*
|--------------------------------------------------------------------------
| خامساً: بانتظار الموافقة
|--------------------------------------------------------------------------
*/

elseif ($approval_value === 'pending') {

    $status_text = '⏳ بانتظار موافقة الإدارة';
    $status_class = 'pending';

}


/*
|--------------------------------------------------------------------------
| أي حالة أخرى
|--------------------------------------------------------------------------
*/

else {

    $status_text = '⏳ قيد الانتظار';
    $status_class = 'pending';

}

/* =========================================================
   نوع الطلب للعرض
========================================================= */

if ($order_type === 'cart') {

    $order_type_text =
        '🛒 طلب سلة خدمات';

} elseif ($order_type === 'tow') {

    $order_type_text =
        '🚚 طلب سطحة';

} else {

    $order_type_text =
        '📦 طلب';

}


/* =========================================================
   عناصر السلة
========================================================= */

$cart_items = [];

$cart_quantity = 0;

$cart_subtotal = 0;


if ($order_type === 'cart') {

    $stmt_items = $con->prepare("

        SELECT

            order_details.id,
            order_details.product_id,
            order_details.quantity,
            order_details.price,

            product.proname,
            product.proimg

        FROM order_details

        LEFT JOIN product
            ON order_details.product_id = product.id

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

            $quantity =
                (int)(
                    $item['quantity'] ?? 0
                );

            $unit_price =
                (float)(
                    $item['price'] ?? 0
                );

            $line_total =
                $quantity * $unit_price;

            $item['line_total'] =
                $line_total;

            $cart_quantity +=
                $quantity;

            $cart_subtotal +=
                $line_total;

            $cart_items[] =
                $item;

        }

        $stmt_items->close();

    }

}


/* =========================================================
   السعر
========================================================= */

$order_price =
    (float)(
        $order['price'] ?? 0
    );


/* =========================================================
   البحث عن الفاتورة
   مهم: البحث باستخدام order_id
========================================================= */

$invoice_id = 0;

$invoice_number = '';

$stmt_invoice = $con->prepare("

    SELECT

        id,
        invoice_number

    FROM invoices

    WHERE order_id = ?

    ORDER BY id DESC

    LIMIT 1

");

if ($stmt_invoice) {

    $stmt_invoice->bind_param(
        "i",
        $order_id
    );

    $stmt_invoice->execute();

    $invoice_result =
        $stmt_invoice->get_result();

    if (
        $invoice_row =
        $invoice_result->fetch_assoc()
    ) {

        $invoice_id =
            (int)(
                $invoice_row['id'] ?? 0
            );

        $invoice_number =
            $invoice_row['invoice_number']
            ?? '';

    }

    $stmt_invoice->close();

}


/* =========================================================
   نوع الحجز
========================================================= */

$booking_type =
    strtolower(
        trim(
            $order['booking_type']
            ?? 'instant'
        )
    );


/* =========================================================
   التاريخ
========================================================= */

$created_at = '---';

if (!empty($order['created_at'])) {

    $created_timestamp =
        strtotime(
            $order['created_at']
        );

    if (
        $created_timestamp !== false
    ) {

        $created_at =
            date(
                'Y-m-d H:i',
                $created_timestamp
            );

    }

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
    تفاصيل الطلب #<?= $order_id ?>
</title>


<style>

*{
    box-sizing:border-box;
}


body{

    margin:0;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

    background:#f5f7fb;

    color:#333;

}


/* =========================================================
   PAGE
========================================================= */

.page{

    width:95%;

    max-width:1100px;

    margin:25px auto;

}


/* =========================================================
   HEADER
========================================================= */

.header-box{

    background:#fff;

    padding:25px;

    border-radius:18px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.08);

    margin-bottom:18px;

}


.header-top{

    display:flex;

    justify-content:
        space-between;

    align-items:center;

    gap:15px;

    flex-wrap:wrap;

}


.header-box h2{

    margin:0;

    font-size:24px;

}


.order-number{

    color:#777;

    margin-top:8px;

}


/* =========================================================
   TYPE
========================================================= */

.type-box{

    display:inline-block;

    padding:
        8px 16px;

    border-radius:30px;

    font-weight:bold;

    margin-top:14px;

}


.type-cart{

    background:#e7f7fc;

    color:#087c9c;

}


.type-tow{

    background:#fff1df;

    color:#d97706;

}


/* =========================================================
   GRID
========================================================= */

.grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:16px;

}


/* =========================================================
   CARD
========================================================= */

.card{

    background:#fff;

    padding:20px;

    border-radius:16px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.07);

}


.card h3{

    margin:
        0 0 15px;

    padding-bottom:10px;

    border-bottom:
        1px solid #eee;

}


/* =========================================================
   INFO
========================================================= */

.info-row{

    display:flex;

    justify-content:
        space-between;

    align-items:center;

    gap:15px;

    padding:11px 0;

    border-bottom:
        1px solid #f0f0f0;

}


.info-row:last-child{

    border-bottom:none;

}


.label{

    color:#777;

}


.value{

    font-weight:bold;

    text-align:left;

}


/* =========================================================
   STATUS
========================================================= */

.status{

    display:inline-block;

    padding:
        7px 15px;

    border-radius:25px;

    font-weight:bold;

    font-size:13px;

}


.pending{

    color:#c27a00;

    background:#fff4d6;

}


.assigned{

    color:#1769aa;

    background:#e5f2ff;

}


.done{

    color:#16803c;

    background:#e5f8ec;

}


.cancelled{

    color:#c62828;

    background:#ffe7e7;

}

.approved{
    color:#087f5b;
    background:#dff8ef;
    border:1px solid #b7ead8;
}

/* =========================================================
   PRODUCTS
========================================================= */

.products{

    width:100%;

    border-collapse:
        collapse;

}


.products th{

    background:#00bcd4;

    color:#fff;

    padding:12px;

}


.products td{

    padding:12px;

    border-bottom:
        1px solid #eee;

    text-align:center;

}


.product-name{

    font-weight:bold;

}


.product-image{

    width:45px;

    height:45px;

    object-fit:cover;

    border-radius:8px;

    vertical-align:middle;

    margin-left:7px;

}


.total{

    margin-top:18px;

    padding:15px;

    background:#f8fafc;

    border-radius:10px;

    font-size:20px;

    font-weight:bold;

    color:#e67e22;

    text-align:center;

}


/* =========================================================
   ROUTE
========================================================= */

.route{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:20px;

    margin:20px 0;

}


.city{

    background:#f5f7fb;

    padding:16px 25px;

    border-radius:12px;

    font-weight:bold;

    text-align:center;

    min-width:130px;

}


.arrow{

    font-size:25px;

    color:#00a6bd;

}


/* =========================================================
   PROVIDER
========================================================= */

.provider{

    text-align:center;

    margin-top:16px;

}


.provider-name{

    font-size:21px;

    font-weight:bold;

    margin-bottom:12px;

}


.phone{

    display:inline-block;

    background:#00bcd4;

    color:#fff;

    text-decoration:none;

    padding:
        10px 20px;

    border-radius:9px;

    font-weight:bold;

}


/* =========================================================
   INVOICE
========================================================= */

.invoice-box{

    margin-top:16px;

    background:
        linear-gradient(
            135deg,
            #f0fff7,
            #ffffff
        );

    border:
        1px solid #cdebd9;

}


.invoice-info{

    display:flex;

    align-items:center;

    justify-content:
        space-between;

    gap:15px;

    flex-wrap:wrap;

}


.invoice-icon{

    font-size:42px;

}


.invoice-title{

    flex:1;

}


.invoice-title h3{

    border:none;

    padding:0;

    margin:0 0 5px;

}


.invoice-title p{

    margin:0;

    color:#777;

}


.invoice-number{

    font-weight:bold;

    color:#198754;

}


.invoice-btn{

    display:inline-block;

    background:#198754;

    color:#fff;

    text-decoration:none;

    padding:
        11px 22px;

    border-radius:9px;

    font-weight:bold;

}


.invoice-btn:hover{

    background:#146c43;

}


/* =========================================================
   NO INVOICE
========================================================= */

.no-invoice{

    margin-top:16px;

    background:#fffaf0;

    border:
        1px solid #f4dfb1;

    border-radius:16px;

    padding:18px;

    text-align:center;

    color:#856404;

}


.no-invoice-icon{

    font-size:35px;

    margin-bottom:7px;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions{

    display:flex;

    justify-content:center;

    gap:10px;

    flex-wrap:wrap;

    margin-top:20px;

}


.action-btn{

    display:inline-block;

    text-decoration:none;

    padding:
        11px 22px;

    border-radius:9px;

    font-weight:bold;

}


.back{

    background:#333;

    color:#fff;

}


.back:hover{

    background:#111;

}


/* =========================================================
   EMPTY
========================================================= */

.empty{

    text-align:center;

    padding:25px;

    color:#777;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:700px){

    .page{

        width:94%;

        margin:15px auto;

    }


    .grid{

        grid-template-columns:1fr;

    }


    .header-box{

        padding:18px;

    }


    .header-box h2{

        font-size:20px;

    }


    .route{

        flex-direction:column;

        gap:10px;

    }


    .arrow{

        transform:
            rotate(90deg);

    }


    .info-row{

        flex-direction:column;

        align-items:flex-start;

        gap:5px;

    }


    .value{

        text-align:right;

    }


    .products th,
    .products td{

        padding:9px;

        font-size:13px;

    }


    .invoice-info{

        flex-direction:column;

        text-align:center;

    }


    .invoice-title{

        flex:none;

    }

}

</style>

</head>


<body>


<div class="page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header-box">


<div class="header-top">


<div>

    <h2>
        تفاصيل الطلب
    </h2>


    <div class="order-number">

        رقم الطلب:

        <strong>
            #<?= $order_id ?>
        </strong>

    </div>

</div>


<div
    style="
        text-align:center;
        padding:15px 0;
    "
>

    <!-- الحالة الرئيسية -->

    <span class="status <?= $status_class ?>">

        <?= htmlspecialchars($status_text) ?>

    </span>

</div>


<!-- حالة الموافقة -->

<div class="info-row">

    <span class="label">
        موافقة الإدارة
    </span>

    <span class="value">

        <?php if ($approval_value === 'approved'): ?>

            <span class="status approved">
                ✅ تمت الموافقة
            </span>

        <?php elseif ($approval_value === 'rejected'): ?>

            <span class="status cancelled">
                ❌ مرفوض
            </span>

        <?php else: ?>

            <span class="status pending">
                ⏳ بانتظار الموافقة
            </span>

        <?php endif; ?>

    </span>

</div>


<!-- حالة التنفيذ -->

<div class="info-row">

    <span class="label">
        حالة التنفيذ
    </span>

    <span class="value">

        <?php

        switch ($status_value) {

            case 'pending':
                echo '<span class="status pending">⏳ قيد الانتظار</span>';
                break;

            case 'assigned':
                echo '<span class="status assigned">🚚 تم تعيين المزود</span>';
                break;

            case 'done':
                echo '<span class="status done">🏆 مكتمل</span>';
                break;

            case 'cancelled':
                echo '<span class="status cancelled">❌ ملغي</span>';
                break;

            default:
                echo '<span class="status pending">⏳ غير محدد</span>';
                break;
        }

        ?>

    </span>

</div>


</div>


<span
    class="
        type-box
        <?= $order_type === 'tow'
            ? 'type-tow'
            : 'type-cart'
        ?>
    "
>

    <?= $order_type_text ?>

</span>


</div>


<!-- =====================================================
     المعلومات العامة
===================================================== -->

<div class="grid">


<!-- معلومات الطلب -->

<div class="card">

    <h3>
        📋 معلومات الطلب
    </h3>


    <div class="info-row">

        <span class="label">
            رقم الطلب
        </span>

        <span class="value">
            #<?= $order_id ?>
        </span>

    </div>


    <div class="info-row">

        <span class="label">
            العميل
        </span>

        <span class="value">

            <?= htmlspecialchars(
                $order['full_name']
                ?? '---'
            ) ?>

        </span>

    </div>


    <div class="info-row">

        <span class="label">
            الجوال
        </span>

        <span class="value">

            <?= htmlspecialchars(
                $order['phone']
                ?? '---'
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
            تاريخ الطلب
        </span>

        <span class="value">

            <?= htmlspecialchars(
                $created_at
            ) ?>

        </span>

    </div>

</div>


<!-- حالة الطلب -->

<div class="card">

    <h3>
        📊 حالة الطلب
    </h3>


    <div
        style="
            text-align:center;
            padding:15px 0;
        "
    >

        <span class="status <?= $status_class ?>">

            <?= $status_text ?>

        </span>

    </div>


    <?php if ($invoice_id > 0): ?>

        <div class="info-row">

            <span class="label">
                رقم الفاتورة
            </span>

            <span class="value">

                <?= htmlspecialchars(
                    $invoice_number
                    ?: '#'.$invoice_id
                ) ?>

            </span>

        </div>

    <?php endif; ?>


</div>


</div>


<!-- =====================================================
     CART
===================================================== -->

<?php if ($order_type === 'cart'): ?>


<div
    class="card"
    style="margin-top:16px;"
>

    <h3>
        🛒 المنتجات والخدمات
    </h3>


    <?php if (!empty($cart_items)): ?>


    <div style="overflow-x:auto;">


        <table class="products">


            <thead>

            <tr>

                <th>
                    المنتج / الخدمة
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


            <?php foreach (
                $cart_items
                as $item
            ): ?>


                <?php

                $product_name =
                    $item['proname']
                    ?? 'منتج / خدمة';


                $image =
                    trim(
                        (string)(
                            $item['proimg']
                            ?? ''
                        )
                    );


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


                        <?php if (
                            $image !== ''
                        ): ?>


                            <img
                                src="uploads/img/<?= htmlspecialchars(
                                    basename($image)
                                ) ?>"
                                class="product-image"
                                alt=""
                            >


                        <?php endif; ?>


                        <span class="product-name">

                            <?= htmlspecialchars(
                                $product_name
                            ) ?>

                        </span>


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


            </tbody>


        </table>


    </div>


    <div class="total">

        إجمالي الطلب:

        <?= number_format(
            $order_price,
            2
        ) ?>

        ريال

    </div>


    <div
        class="info-row"
        style="margin-top:10px;"
    >

        <span class="label">
            عدد المنتجات
        </span>

        <span class="value">

            <?= count(
                $cart_items
            ) ?>

        </span>

    </div>


    <div class="info-row">

        <span class="label">
            إجمالي الكميات
        </span>

        <span class="value">

            <?= $cart_quantity ?>

        </span>

    </div>


    <?php else: ?>


        <div class="empty">

            لا توجد تفاصيل للمنتجات في هذا الطلب.

        </div>


    <?php endif; ?>


</div>


<?php endif; ?>


<!-- =====================================================
     TOW
===================================================== -->

<?php if ($order_type === 'tow'): ?>


<div
    class="card"
    style="margin-top:16px;"
>


    <h3>
        🚚 تفاصيل رحلة السطحة
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


    <div class="grid">


        <div>


            <div class="info-row">

                <span class="label">
                    نوع المركبة
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $order['car_type']
                        ?? '---'
                    ) ?>

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    المسافة
                </span>

                <span class="value">

                    <?= number_format(
                        (float)(
                            $order['distance']
                            ?? 0
                        ),
                        1
                    ) ?>

                    كم

                </span>

            </div>


            <div class="info-row">

                <span class="label">
                    تكلفة الطلب
                </span>

                <span class="value">

                    <?= number_format(
                        $order_price,
                        2
                    ) ?>

                    ريال

                </span>

            </div>


        </div>


        <div>


            <div class="info-row">

                <span class="label">
                    نوع الحجز
                </span>

                <span class="value">


                    <?php if (
                        $booking_type
                        === 'scheduled'
                    ): ?>

                        📅 حجز مجدول

                    <?php else: ?>

                        🚀 طلب فوري

                    <?php endif; ?>


                </span>

            </div>


            <?php if (
                $booking_type
                === 'scheduled'
            ): ?>


                <div class="info-row">

                    <span class="label">
                        التاريخ
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $order['scheduled_date']
                            ?? '---'
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        الوقت
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $order['scheduled_time']
                            ?? '---'
                        ) ?>

                    </span>

                </div>


            <?php endif; ?>


        </div>


    </div>


    <div class="total">

        تكلفة طلب السطحة:

        <?= number_format(
            $order_price,
            2
        ) ?>

        ريال

    </div>


</div>


<?php endif; ?>


<!-- =====================================================
     PROVIDER
===================================================== -->

<div class="card provider">


    <h3>


        <?php if (
            $order_type === 'tow'
        ): ?>

            👨‍🔧 سائق السطحة

        <?php else: ?>

            👨‍🔧 مزود الخدمة

        <?php endif; ?>


    </h3>


    <?php if ($provider_name): ?>


        <div class="provider-name">

            <?= htmlspecialchars(
                $provider_name
            ) ?>

        </div>


        <?php if (
            $provider_phone
        ): ?>


            <a
                class="phone"
                href="tel:<?= htmlspecialchars(
                    $provider_phone
                ) ?>"
            >

                📞

                <?= htmlspecialchars(
                    $provider_phone
                ) ?>

            </a>


        <?php endif; ?>


    <?php else: ?>


        <p>


            <?php if (
                $order_type === 'tow'
            ): ?>

                🚚 لم يتم تعيين سائق للطلب حتى الآن.

            <?php else: ?>

                لم يتم تعيين مزود للخدمة حتى الآن.

            <?php endif; ?>


        </p>


    <?php endif; ?>


</div>


<!-- =====================================================
     الفاتورة
===================================================== -->

<?php if ($invoice_id > 0): ?>


<div class="card invoice-box">


    <div class="invoice-info">


        <div class="invoice-icon">
            🧾
        </div>


        <div class="invoice-title">


            <h3>
                الفاتورة جاهزة
            </h3>


            <p>

                تم إصدار فاتورة لهذا الطلب.

            </p>


            <div class="invoice-number">

                رقم الفاتورة:

                <?= htmlspecialchars(
                    $invoice_number
                    ?: '#'.$invoice_id
                ) ?>

            </div>


        </div>


        <div>


            <a
                href="invoice.php?id=<?= $order_id ?>"
                class="invoice-btn"
            >

                🧾 عرض الفاتورة

            </a>


        </div>


    </div>


</div>


<?php elseif (
    $status_value === 'done'
): ?>


<div class="no-invoice">


    <div class="no-invoice-icon">
        ⚠️
    </div>


    <strong>
        الفاتورة غير متاحة حالياً
    </strong>


    <p>

        الطلب مكتمل ولكن لم يتم العثور على فاتورة مرتبطة به.

        يرجى مراجعة الإدارة.

    </p>


</div>


<?php else: ?>


<div class="no-invoice">


    <div class="no-invoice-icon">
        🧾
    </div>


    <strong>
        الفاتورة لم تصدر بعد
    </strong>


    <p>

        ستظهر الفاتورة هنا بعد اكتمال الطلب وإصدارها.

    </p>


</div>


<?php endif; ?>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="actions">


<a
    href="myorders.php"
    class="action-btn back"
>

    ← العودة إلى طلباتي

</a>


</div>


</div>


</body>

</html>