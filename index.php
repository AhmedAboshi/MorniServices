<?php
session_start();

if(!isset($_SESSION['user_id'])){
    echo '<script>
    alert("يرجى تسجيل الدخول أولاً");
    window.location.href="user/login.php";
    </script>';
    exit();
}

include('file/header.php');
?>


<main>

<?php
$query="SELECT * FROM product";
$result=mysqli_query($con,$query);

while($row=mysqli_fetch_assoc($result)){
?>

<div class="product">

    <!-- الصورة -->
    <div class="product_img">
        <a href="detalis.php?id=<?php echo $row['id']; ?>">
            <img src="uploads/img/<?php echo $row['proimg']; ?>">
        </a>
    </div>

    <!-- القسم -->
    <div class="product_section">
        <?php echo $row['prosection']; ?>
    </div>

    <!-- الاسم -->
    <div class="product_name">
        <?php echo $row['proname']; ?>
    </div>

    <!-- السعر -->
    <div class="product_price">
        <?php echo $row['proprice']; ?> ريال
    </div>

    <!-- التفاصيل -->
    <div class="product_description">
        <a href="detalis.php?id=<?php echo $row['id']; ?>">
            عرض التفاصيل
        </a>
    </div>

    <!-- الفورم -->
    <form action="cart.php" method="POST">

    <div class="qty_input">
        <button type="button">-</button>
        <input type="number" name="quantity" value="1" min="1">
        <button type="button">+</button>
    </div>

    <!-- بيانات المنتج -->
    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
    <input type="hidden" name="name" value="<?php echo $row['proname']; ?>">
    <input type="hidden" name="price" value="<?php echo $row['proprice']; ?>">
    <input type="hidden" name="img" value="<?php echo $row['proimg']; ?>">

    <button class="addto_cart" type="submit" name="add">
        اضف إلى خدماتي
    </button>

</form>

</div>

<?php } ?>

</main>

<?php include('file/foter.php'); ?>