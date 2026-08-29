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

<style>

/* ===============================
   صفحة المنتجات
================================ */

main.products-grid{
    width:100%;
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
    gap:20px;
    padding:20px;
    align-items:start;
}

/* ===============================
   كرت المنتج
================================ */

main.products-grid .product{
    width:100%;
    max-width:280px;
    margin:0 auto;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,.10);
    display:flex;
    flex-direction:column;
}

/* ===============================
   صورة المنتج
================================ */

main.products-grid .product .product_img{
    width:100%;
    height:160px;
    max-height:160px;
    overflow:hidden;
    flex-shrink:0;
}

main.products-grid .product .product_img a{
    display:block;
    width:100%;
    height:160px;
}

main.products-grid .product .product_img img{
    display:block;
    width:100%;
    height:160px;
    max-width:100%;
    max-height:160px;
    object-fit:cover;
}

/* ===============================
   القسم
================================ */

main.products-grid .product .product_section{
    float:none;
    width:max-content;
    margin:10px 12px 0;
    padding:5px 9px;
    background:#eef8fa;
    color:#008fa3;
    border-radius:6px;
    font-size:12px;
}

/* ===============================
   الاسم
================================ */

main.products-grid .product .product_name{
    padding:0 12px;
    margin-top:8px;
    font-size:16px;
    font-weight:bold;
    color:#222;
}

/* ===============================
   السعر
================================ */

main.products-grid .product .product_price{
    padding:0 12px;
    margin-top:6px;
    color:#e53935;
    font-size:16px;
    font-weight:bold;
}

/* ===============================
   التفاصيل
================================ */

main.products-grid .product .product_description{
    padding:0 12px;
    margin-top:7px;
}

main.products-grid .product .product_description a{
    color:#2563eb;
    text-decoration:none;
    font-size:13px;
}

/* ===============================
   الكمية
================================ */

main.products-grid .product .qty_input{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
    margin:12px;
}

main.products-grid .product .qty_input input{
    width:55px;
    height:35px;
    padding:5px;
    text-align:center;
    border:1px solid #ddd;
    border-radius:6px;
}

main.products-grid .product .qty_input button{
    width:35px;
    height:35px;
    border:1px solid #ddd;
    background:#f5f5f5;
    border-radius:6px;
    cursor:pointer;
}

/* ===============================
   زر إضافة
================================ */

main.products-grid .product .addto_cart{
    width:100%;
    height:45px;
    border:none;
    background:#00a9bd;
    color:#fff;
    font-size:15px;
    font-weight:bold;
    cursor:pointer;
}

main.products-grid .product .addto_cart:hover{
    background:#008fa3;
}

/* ===============================
   الجوال
================================ */

@media(max-width:600px){

    main.products-grid{
        grid-template-columns:repeat(2,1fr);
        gap:10px;
        padding:10px;
    }

    main.products-grid .product{
        max-width:none;
    }

    main.products-grid .product .product_img,
    main.products-grid .product .product_img a,
    main.products-grid .product .product_img img{
        height:130px;
        max-height:130px;
    }

}

</style>


<main class="products-grid">

<?php

$query = "SELECT * FROM product";
$result = mysqli_query($con,$query);

if(!$result){
    die("Database Error: " . mysqli_error($con));
}

while($row = mysqli_fetch_assoc($result)){

?>

<div class="product">

    <!-- صورة المنتج -->
    <div class="product_img">

        <a href="detalis.php?id=<?= (int)$row['id'] ?>">

            <img
                src="uploads/img/<?= htmlspecialchars($row['proimg']) ?>"
                alt="<?= htmlspecialchars($row['proname']) ?>"
            >

        </a>

    </div>


    <!-- القسم -->
    <div class="product_section">

        <?= htmlspecialchars($row['prosection']) ?>

    </div>


    <!-- الاسم -->
    <div class="product_name">

        <?= htmlspecialchars($row['proname']) ?>

    </div>


    <!-- السعر -->
    <div class="product_price">

        <?= number_format((float)$row['proprice'],2) ?> ريال

    </div>


    <!-- التفاصيل -->
    <div class="product_description">

        <a href="detalis.php?id=<?= (int)$row['id'] ?>">

            عرض التفاصيل

        </a>

    </div>


    <!-- السلة -->
    <form action="cart.php" method="POST">

        <div class="qty_input">

            <button type="button" class="qty-minus">−</button>

            <input
                type="number"
                name="quantity"
                value="1"
                min="1"
                max="99"
            >

            <button type="button" class="qty-plus">+</button>

        </div>


        <input
            type="hidden"
            name="product_id"
            value="<?= (int)$row['id'] ?>"
        >

        <input
            type="hidden"
            name="name"
            value="<?= htmlspecialchars($row['proname']) ?>"
        >

        <input
            type="hidden"
            name="price"
            value="<?= htmlspecialchars($row['proprice']) ?>"
        >

        <input
            type="hidden"
            name="img"
            value="<?= htmlspecialchars($row['proimg']) ?>"
        >


        <button
            class="addto_cart"
            type="submit"
            name="add"
        >

            أضف إلى خدماتي

        </button>

    </form>

</div>

<?php } ?>

</main>


<script>

/* ===============================
   أزرار الكمية
================================ */

document.querySelectorAll('.qty_input').forEach(function(box){

    const input = box.querySelector('input');
    const minus = box.querySelector('.qty-minus');
    const plus  = box.querySelector('.qty-plus');

    minus.addEventListener('click', function(){

        let value = parseInt(input.value) || 1;

        if(value > 1){
            input.value = value - 1;
        }

    });


    plus.addEventListener('click', function(){

        let value = parseInt(input.value) || 1;

        if(value < 99){
            input.value = value + 1;
        }

    });

});

</script>


<?php include('file/foter.php'); ?>