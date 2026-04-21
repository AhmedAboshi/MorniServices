<?php
include('../include/connected.php');

/* جلب الفواتير */
$result = mysqli_query($con, "
SELECT invoices.*, orders.full_name, orders.phone 
FROM invoices
LEFT JOIN orders ON invoices.order_id = orders.id
ORDER BY invoices.id DESC
");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إدارة الفواتير</title>

<style>
body{font-family:Arial;background:#f4f6f9;direction:rtl}
.container{width:90%;margin:auto}

h2{text-align:center;margin:20px 0}

table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

th,td{
    padding:10px;
    border:1px solid #ddd;
    text-align:center;
}

th{background:#007bff;color:#fff}

a.btn{
    padding:5px 10px;
    background:#28a745;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
}
</style>

</head>

<body>

<div class="container">

<h2>📋 إدارة الفواتير</h2>

<table>

<tr>
    <th>#</th>
    <th>رقم الفاتورة</th>
    <th>العميل</th>
    <th>الهاتف</th>
    <th>الإجمالي</th>
    <th>النوع</th>
    <th>التفاصيل</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['invoice_number'] ?></td>
    <td><?= $row['full_name'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= number_format($row['total_with_vat'],2) ?> $</td>
    <td><?= $row['order_type'] ?></td>

    <td>
        <a href="admin_invoice_view.php?id=<?= $row['id'] ?>" class="btn">
            عرض
        </a>
    </td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>