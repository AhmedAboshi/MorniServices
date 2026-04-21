<?php
include('../include/connected.php');

$id = (int) $_GET['id'];

$result = mysqli_query($con, "SELECT * FROM orders WHERE id=$id");
$order = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

    $price = $_POST['price'];
    $status = $_POST['status'];

    mysqli_query($con, "
        UPDATE orders SET 
        price='$price',
        status='$status'
        WHERE id=$id
    ");

    header("Location: ordersview.php");
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">
<h3>✏️ تعديل الطلب</h3>

<form method="POST">

<div class="mb-3">
<label>السعر</label>
<input type="number" name="price" class="form-control"
value="<?= $order['price'] ?>">
</div>

<div class="mb-3">
<label>الحالة</label>
<select name="status" class="form-control">
<option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>قيد الانتظار</option>
<option value="assigned" <?= $order['status']=='assigned'?'selected':'' ?>>تم التعيين</option>
<option value="done" <?= $order['status']=='done'?'selected':'' ?>>مكتمل</option>
<option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>ملغي</option>
</select>
</div>

<button name="update" class="btn btn-success">حفظ</button>
<a href="orders.php" class="btn btn-secondary">رجوع</a>

</form>
</div>