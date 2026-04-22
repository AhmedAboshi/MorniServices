<?php
include('../include/connected.php');
$id = $_GET['id'] ?? 0;

/* الفاتورة */
$invoice = mysqli_fetch_assoc(mysqli_query($con,
"SELECT * FROM invoices WHERE id='$id'"));

if(!$invoice){
    die("❌ الفاتورة غير موجودة");
}

$order_id = $invoice['order_id'];

/* الطلب */
$order = mysqli_fetch_assoc(mysqli_query($con,
"SELECT * FROM orders WHERE id='$order_id'"));

$order_type = $order['order_type'];

/* المنتجات */
$items = [];
$total = 0;

if($order_type == 'cart'){
    $res = mysqli_query($con,
    "SELECT * FROM order_details WHERE order_id='$order_id'");

    while($row = mysqli_fetch_assoc($res)){
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
}else{
    $total = $order['price'];
}

/* VAT */
$vat = $total * 0.15;
$total_with_vat = $total + $vat;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">

<title>تفاصيل الفاتورة</title>

<style>
body{font-family:Arial;background:#f4f6f9;direction:rtl}
.box{width:700px;margin:auto;background:#fff;padding:20px;margin-top:30px}

h2{text-align:center}

table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border:1px solid #ccc;padding:8px;text-align:center}

.total{text-align:center;margin-top:20px}

.btn{
    display:inline-block;
    padding:8px 12px;
    background:#007bff;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    margin-top:10px;
}
</style>

</head>

<body>

<div class="box">
<!-- 🔝 أزرار -->
<div class="top-actions">
 
 <div style="text-align:center;">
    <img src="../img/logo.jpg" width="80" alt="Logo">
</div>
    <button onclick="window.print()" class="btn print">
        🖨️ طباعة
    </button>

    <div style="display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:15px;">
       
    </div>

</div>
<h2>🧾 تفاصيل الفاتورة</h2>

<p>رقم الفاتورة: <?= $invoice['invoice_number'] ?></p>
<p>تاريخ الفاتورة: <?= $invoice['created_at'] ?></p>
<p>العميل: <?= $order['full_name'] ?></p>
<p>الهاتف: <?= $order['phone'] ?></p>
<p>نوع الطلب: <?= $order_type ?></p>

<hr>

<?php if($order_type == 'cart'){ ?>

<table>
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

<?php } else { ?>

<p>من: <?= $order['from_city'] ?></p>
<p>إلى: <?= $order['to_city'] ?></p>
<p>المسافة: <?= $order['distance'] ?> كم</p>
<p>السعر: <?= $order['price'] ?> $</p>

<?php } ?>

<div class="total">
<p>المجموع: <?= number_format($total,2) ?> ريال</p>
<p>VAT: <?= number_format($vat,2) ?> ريال</p>
<h3>الإجمالي: <?= number_format($total_with_vat,2) ?> ريال</h3>
</div>

<a href="admin_invoices.php" class="btn">⬅ رجوع</a>

</div>

</body>
</html>