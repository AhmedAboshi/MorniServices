<?php
session_start();
include('include/connected.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $con->prepare("
    SELECT 
        orders.*,
        drivers.name AS provider_name,
        drivers.phone AS provider_phone
    FROM orders
    LEFT JOIN drivers 
        ON orders.driver_id = drivers.id
    WHERE orders.user_id = ?
    ORDER BY orders.id DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>طلباتي - منصة الشرق</title>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#f5f7fb;
    color:#333;
}

/* =========================
   الصفحة
========================= */

.orders-page{
    width:95%;
    max-width:1200px;
    margin:30px auto;
}

.page-title{
    background:#fff;
    padding:20px;
    border-radius:15px;
    margin-bottom:20px;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
}

.page-title h2{
    margin:0;
    color:#222;
}

.page-title p{
    margin:8px 0 0;
    color:#777;
}

/* =========================
   الإحصائيات
========================= */

.orders-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
    margin-bottom:20px;
}

.stat-box{
    background:#fff;
    padding:18px;
    border-radius:15px;
    box-shadow:0 3px 12px rgba(0,0,0,.07);
}

.stat-title{
    color:#777;
    font-size:14px;
}

.stat-number{
    font-size:26px;
    font-weight:bold;
    margin-top:8px;
}

/* =========================
   جدول الطلبات
========================= */

.orders-box{
    background:#fff;
    border-radius:15px;
    padding:15px;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#00bcd4;
    color:#fff;
    padding:13px;
    white-space:nowrap;
}

td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    text-align:center;
    white-space:nowrap;
}

tr:hover{
    background:#f8fcfd;
}

/* =========================
   نوع الطلب
========================= */

.order-type{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.type-cart{
    background:#e8f7ff;
    color:#087ca1;
}

.type-tow{
    background:#fff1df;
    color:#d97706;
}

/* =========================
   الحالة
========================= */

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.status-pending{
    background:#fff4d6;
    color:#c27a00;
}

.status-assigned{
    background:#e5f2ff;
    color:#1769aa;
}

.status-done{
    background:#e5f8ec;
    color:#16803c;
}

.status-cancelled{
    background:#ffe7e7;
    color:#c62828;
}

.status-approved{
    background:#dff8ef;
    color:#087f5b;
    border:1px solid #b7ead8;
}

/* =========================
   زر التفاصيل
========================= */

.details-btn{
    display:inline-block;
    background:#00bcd4;
    color:#fff;
    text-decoration:none;
    padding:8px 15px;
    border-radius:8px;
    transition:.2s;
}

.details-btn:hover{
    background:#0097a7;
}

/* =========================
   عدم وجود طلبات
========================= */

.empty{
    text-align:center;
    padding:50px 20px;
    color:#777;
}

.empty-icon{
    font-size:50px;
    margin-bottom:10px;
}

/* =========================
   الموبايل
========================= */

@media(max-width:700px){

    .orders-page{
        width:96%;
        margin:15px auto;
    }

    .orders-stats{
        grid-template-columns:1fr;
    }

    .page-title{
        text-align:center;
    }

    th,
    td{
        padding:10px 8px;
        font-size:13px;
    }

}

</style>

</head>

<body>

<div class="orders-page">

    <!-- العنوان -->

    <div class="page-title">

        <h2>📋 طلباتي</h2>

        <p>
            متابعة جميع طلباتك في منصة الشرق
        </p>

    </div>


    <?php

    /* =========================
       إحصائيات
    ========================= */

    $total_orders = 0;
    $cart_orders = 0;
    $tow_orders = 0;

    $stats_stmt = $con->prepare("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(order_type = 'cart') AS cart_orders,
            SUM(order_type = 'tow') AS tow_orders
        FROM orders
        WHERE user_id = ?
    ");

    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();

    $stats = $stats_stmt->get_result()->fetch_assoc();

    $total_orders = (int)($stats['total_orders'] ?? 0);
    $cart_orders  = (int)($stats['cart_orders'] ?? 0);
    $tow_orders   = (int)($stats['tow_orders'] ?? 0);

    ?>

    <!-- الإحصائيات -->

    <div class="orders-stats">

        <div class="stat-box">

            <div class="stat-title">
                إجمالي الطلبات
            </div>

            <div class="stat-number">
                <?= $total_orders ?>
            </div>

        </div>


        <div class="stat-box">

            <div class="stat-title">
                🛒 طلبات السلة
            </div>

            <div class="stat-number">
                <?= $cart_orders ?>
            </div>

        </div>


        <div class="stat-box">

            <div class="stat-title">
                🚚 طلبات السطحة
            </div>

            <div class="stat-number">
                <?= $tow_orders ?>
            </div>

        </div>

    </div>


    <!-- الطلبات -->

    <div class="orders-box">

        <?php if ($result->num_rows == 0): ?>

            <div class="empty">

                <div class="empty-icon">
                    📦
                </div>

                <h3>
                    لا توجد طلبات حالياً
                </h3>

                <p>
                    عند إنشاء طلب سيظهر هنا
                </p>

            </div>

        <?php else: ?>

        <table>

            <thead>

            <tr>

                <th>رقم الطلب</th>

                <th>نوع الطلب</th>

                <th>الطلب</th>

                <th>السعر</th>

                <th>الحالة</th>

                <th>المزود</th>

                <th>الجوال</th>

                <th>التاريخ</th>

                <th>التفاصيل</th>

            </tr>

            </thead>

            <tbody>

            <?php while ($row = $result->fetch_assoc()): ?>

                <?php

/* =========================================================
   تحديد حالة الطلب التي تظهر للعميل
========================================================= */

$approval_value = strtolower(
    trim(
        $row['approval_status'] ?? 'pending'
    )
);

$status_value = strtolower(
    trim(
        $row['status'] ?? 'pending'
    )
);

$status_text = 'قيد الانتظار';
$status_class = 'status-pending';


/* الطلب مرفوض أو ملغي */
if (
    $approval_value === 'rejected' ||
    $status_value === 'cancelled'
) {

    $status_text = '❌ تم رفض / إلغاء الطلب';
    $status_class = 'status-cancelled';

}


/* الطلب مكتمل */
elseif ($status_value === 'done') {

    $status_text = '🏆 الطلب مكتمل';
    $status_class = 'status-done';

}


/* تمت الموافقة وتم تعيين المزود */
elseif (
    $approval_value === 'approved' &&
    $status_value === 'assigned'
) {

    $status_text = '🚚 تمت الموافقة وتم تعيين المزود';
    $status_class = 'status-assigned';

}


/* تمت الموافقة ولكن لم يتم تعيين مزود */
elseif ($approval_value === 'approved') {

    $status_text = '✅ تمت الموافقة على الطلب';
    $status_class = 'status-approved';

}


/* بانتظار موافقة الإدارة */
elseif ($approval_value === 'pending') {

    $status_text = '⏳ بانتظار موافقة الإدارة';
    $status_class = 'status-pending';

}

?>
            <tr>

                <!-- رقم الطلب -->

                <td>

                    #<?= (int)$row['id'] ?>

                </td>


                <!-- نوع الطلب -->

                <td>

                <?php if ($row['order_type'] === 'cart'): ?>

                    <span class="order-type type-cart">
                        🛒 سلة خدمات
                    </span>

                <?php elseif ($row['order_type'] === 'tow'): ?>

                    <span class="order-type type-tow">
                        🚚 سطحة
                    </span>

                <?php else: ?>

                    <span class="order-type">
                        طلب
                    </span>

                <?php endif; ?>

                </td>


                <!-- تفاصيل مختصرة -->

                <td>

                <?php if ($row['order_type'] === 'cart'): ?>

                    طلب منتجات / خدمات

                <?php elseif ($row['order_type'] === 'tow'): ?>

                    <?= htmlspecialchars($row['from_city'] ?? '') ?>

                    →

                    <?= htmlspecialchars($row['to_city'] ?? '') ?>

                <?php else: ?>

                    طلب

                <?php endif; ?>

                </td>


                <!-- السعر -->

                <td>

                    <?= number_format((float)($row['price'] ?? 0),2) ?>

                    ريال

                </td>


                <!-- الحالة -->

<td>

    <span class="status <?= htmlspecialchars($status_class) ?>">

        <?= htmlspecialchars($status_text) ?>

    </span>

</td>

                

                <!-- المزود -->

                <td>

                    <?php

                    echo !empty($row['provider_name'])

                        ? htmlspecialchars($row['provider_name'])

                        : 'لم يتم التعيين';

                    ?>

                </td>


                <!-- جوال المزود -->

                <td>

                    <?php

                    echo !empty($row['provider_phone'])

                        ? htmlspecialchars($row['provider_phone'])

                        : '---';

                    ?>

                </td>


                <!-- التاريخ -->

                <td>

                    <?php

                    echo !empty($row['created_at'])

                        ? date(
                            'Y-m-d H:i',
                            strtotime($row['created_at'])
                          )

                        : '---';

                    ?>

                </td>


                <!-- التفاصيل -->

                <td>

                    <a
                        class="details-btn"
                        href="myorderdetails.php?id=<?= (int)$row['id'] ?>"
                    >
                        عرض
                    </a>

                </td>

            </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php endif; ?>

    </div>

</div>

</body>

</html>