<?php
include('../include/connected.php');
include('../include/settings.php');
$order_id = (int)($_GET['id'] ?? 0);

/* 🧾 الفاتورة */
$invoice = mysqli_fetch_assoc(mysqli_query($con,"
SELECT *
FROM invoices
WHERE order_id = '$order_id'
LIMIT 1
"));

if (!$invoice) {
    die("❌ لا توجد فاتورة لهذا الطلب");
}
$order_id = $invoice['order_id'];

/* 📦 الطلب */
$order = mysqli_fetch_assoc(mysqli_query($con,
"SELECT * FROM orders WHERE id='$order_id'"));

if (!$order) {
    die("❌ الطلب غير موجود");
}

$order_type = $order['order_type'] ?? 'cart';

/* 💰 حساب الإجمالي */
$total = 0;
$items = [];

if ($order_type == 'cart') {

    $res = mysqli_query($con,
    "SELECT * FROM order_details WHERE order_id='$order_id'");

    while($row = mysqli_fetch_assoc($res)){
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }

} else {
    $total = (float) $order['price'];
}

/* VAT */
$vat = $total * 0.15;
$total_with_vat = $total + $vat;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>فاتورة</title>

<style>
body{
    font-family: Arial;
    background:#f4f6f9;
    display:flex;
    justify-content:center;
    direction: rtl;
}

.invoice{
    width:850px;
    margin:30px 0;
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

/* 🔝 أزرار */
.top-actions{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.btn{
    padding:10px 15px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
    font-size:14px;
}

.print{background:#007bff;color:#fff}
.pdf{background:#ff9800;color:#fff}
.home{background:#28a745;color:#fff}

/* 🧾 هيدر */
.header{
    text-align:center;
    border-bottom:2px solid #eee;
    padding-bottom:15px;
}

/* 📦 Cards */
.grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
    margin-top:20px;
}

.card{
    background:#f9f9f9;
    padding:15px;
    border-radius:10px;
    text-align:center;
}

.card h3{
    margin-bottom:8px;
    color:#007bff;
}

.card.full{
    grid-column:span 2;
    text-align:right;
}

/* 💰 إجمالي */
.total{
    margin-top:20px;
    background:#eef2f7;
    padding:15px;
    border-radius:10px;
    text-align:center;
}

.total h2{
    color:#28a745;
}

/* 🖨️ طباعة */
@media print{
    .top-actions{display:none;}
    body{background:#fff;}
    .invoice{box-shadow:none;}
}
</style>

</head>

<body>

<div class="invoice">

<!-- 🔝 أزرار -->
<div class="top-actions">

    <button onclick="window.print()" class="btn print">
        🖨️ طباعة
    </button>

   <?php
$company_name = setting('company_name');
$logo = setting('company_logo');
?>

<div style="display:flex;justify-content:center;align-items:center;gap:15px;margin-bottom:20px;">

<?php if(!empty($logo)){ ?>

<img src="../uploads/logo/<?= $logo ?>" style="width:70px;height:70px;object-fit:contain;">

<?php } ?>

<div>
<h2><?= $company_name ?></h2>
</div>

</div>

    <a href="ordersview.php" class="btn home">
        🏠 عرض الطلبات
    </a>

</div>

<!-- 🧾 العنوان -->
<div class="header">
    <h2>🧾 فاتورة</h2>
    <p>رقم الفاتورة: <?= $invoice['invoice_number'] ?></p>
    <p>💳 الدفع: <?= $invoice['payment_method'] ?? 'cash' ?></p>
</div>

<!-- 📦 معلومات -->
<div class="grid">

    <div class="card">
        <h3>👤 العميل</h3>
        <p><?= $order['full_name'] ?></p>
    </div>

    <div class="card">
        <h3>📞 الهاتف</h3>
        <p><?= $order['phone'] ?></p>
    </div>

    <div class="card">
        <h3>📦 نوع الطلب</h3>
        <p><?= $order_type ?></p>
    </div>

    <div class="card">
        <h3>📊 الحالة</h3>
        <p><?= $order['status'] ?? 'pending' ?></p>
    </div>

</div>

<hr>

<!-- 🚚 تفاصيل -->
<div class="card full">

    <h3>🚚 تفاصيل الطلب</h3>

    <?php if ($order_type == 'intercity') { ?>

        <p>من: <?= $order['from_city'] ?></p>
        <p>إلى: <?= $order['to_city'] ?></p>
        <p>المسافة: <?= $order['distance'] ?> كم</p>
        <p>السيارة: <?= $order['car_type'] ?></p>

    <?php } ?>

    <?php if ($order_type == 'cart') { ?>

        <table width="100%" border="1" style="margin-top:10px;text-align:center">
            <tr>
                <th>المنتج</th>
                <th>الكمية</th>
                <th>السعر</th>
                <th>الإجمالي</th>
            </tr>

            <?php foreach($items as $item){ ?>
            <tr>
                <td><?= $item['product_id'] ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= $item['price'] ?></td>
                <td><?= $item['price'] * $item['quantity'] ?></td>
            </tr>
            <?php } ?>

        </table>

    <?php } ?>

</div>

<!-- 💰 الإجمالي -->
<div class="total">
    <p>المجموع: <?= number_format($total,2) ?> $</p>
    <p>VAT (15%): <?= number_format($vat,2) ?> $</p>
    <h2>الإجمالي: <?= number_format($total_with_vat,2) ?> $</h2>
</div>

</div>

</body>
</html>