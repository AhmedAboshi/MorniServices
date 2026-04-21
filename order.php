<?php
session_start();
include('include/connected.php');

$user_id = $_SESSION['user_id'];

/* جلب السلة */
$result = mysqli_query($con, "SELECT * FROM cart WHERE user_id='$user_id'");

/* حساب الإجمالي للعرض */
$total = 0;
while($r = mysqli_fetch_assoc($result)){
    $total += $r['price'] * $r['quantity'];
}

/* إعادة الاستعلام للعرض */
$result = mysqli_query($con, "SELECT * FROM cart WHERE user_id='$user_id'");


/* =========================
   حفظ الطلب
========================= */
if (isset($_POST['orderadd'])) {

    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $city      = $_POST['city'];
    $address   = $_POST['address'];
    $payment_method = $_POST['payment_method'];

    /* 1️⃣ إنشاء الطلب */
    mysqli_query($con, "INSERT INTO orders 
    (full_name, email, phone, city, address, user_id, payment_method)
    VALUES 
    ('$full_name', '$email', '$phone', '$city', '$address', '$user_id', '$payment_method')");

    $order_id = mysqli_insert_id($con);

    /* 2️⃣ نقل المنتجات إلى order_details */
    $cart_items = mysqli_query($con, "SELECT * FROM cart WHERE user_id='$user_id'");

    $total = 0;

    while($row = mysqli_fetch_assoc($cart_items)){

        $price = (float)$row['price'];
        $qty   = (int)$row['quantity'];

        $total += $price * $qty;

        mysqli_query($con,"
            INSERT INTO order_details 
            (order_id, product_id, quantity, price, img)
            VALUES 
            ('$order_id','{$row['product_id']}','$qty','$price','{$row['img']}')
        ");
    }

    /* 3️⃣ حساب الضريبة */
    $vat = $total * 0.15;
    $total_with_vat = $total + $vat;

    /* 4️⃣ إنشاء الفاتورة */
    $invoice_number = 'INV-' . time();

    mysqli_query($con, "
    INSERT INTO invoices 
    (order_id, order_type, invoice_number, total, vat, total_with_vat)
    VALUES 
    ('$order_id', 'cart', '$invoice_number', '$total', '$vat', '$total_with_vat')
    ");

    $invoice_id = mysqli_insert_id($con);

    /* 5️⃣ حذف السلة */
    mysqli_query($con, "DELETE FROM cart WHERE user_id='$user_id'");

    /* 6️⃣ تحويل للفاتورة */
    header("Location: invoice.php?id=$invoice_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إتمام الطلب</title>
<link rel="stylesheet" href="order.css">
</head>

<body>

<div class="container">

<h1>إتمام الطلب</h1>

<form method="post">

<div class="display-order">

<?php if(mysqli_num_rows($result) > 0){ ?>
    <?php while($row = mysqli_fetch_assoc($result)){ ?>
        
        <div class="product-box">
            <img src="uploads/img/<?= $row['img'] ?>" width="80">
            <p>الكمية: <?= $row['quantity'] ?></p>
            <p>السعر: <?= $row['price'] ?></p>
        </div>

    <?php } ?>
<?php } else { ?>
    <p>السلة فارغة</p>
<?php } ?>

</div>

<div class="total-container">
    الإجمالي: <?= number_format($total,2) ?> $
</div>

<hr>

<input type="text" name="full_name" placeholder="الاسم" required><br>
<input type="email" name="email" placeholder="البريد" required><br>
<input type="text" name="phone" placeholder="الهاتف" required><br>
<input type="text" name="city" placeholder="المدينة" required><br>
<input type="text" name="address" placeholder="العنوان" required><br>

<select name="payment_method" required>
    <option value="">اختر طريقة الدفع</option>
    <option value="cash">كاش</option>
    <option value="card">بطاقة</option>
    <option value="bank">تحويل بنكي</option>
</select>

<button type="submit" name="orderadd">
    تأكيد الطلب
</button>

</form>

</div>

</body>
</html>