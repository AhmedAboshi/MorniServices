<?php
include('file/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاقسام</title>
</head>
<body>
<main>

<?php
$section = $_GET['section'] ?? '';

$query = "SELECT * FROM product WHERE prosection ='$section'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) > 0) {

    while($row = mysqli_fetch_assoc($result)) {
?>

<div class="product">

    <div class="product_img">
        <img src="uploads/img/<?php echo $row['proimg']; ?>">
    </div>

    <div class="product_name">
        <?php echo $row['proname']; ?>
    </div>

    <div class="product_price">
        <?php echo $row['proprice']; ?> السعر
    </div>

    <form action="cart.php" method="POST">

        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
        <input type="hidden" name="name" value="<?php echo $row['proname']; ?>">
        <input type="hidden" name="price" value="<?php echo $row['proprice']; ?>">
        <input type="hidden" name="img" value="<?php echo $row['proimg']; ?>">

        <button type="submit" name="add" class="add_cart">
            إضافة للسلة
        </button>

    </form>

</div>

<?php
    }

} else {
    echo '<div class="notification">الخدمة التي تبحث عنها غير موجودة</div>';
}
?>

</main>
</body>
</html>