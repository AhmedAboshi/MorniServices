<?php

session_start();

include('file/header.php');

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   حماية المستخدم
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    (int)$_SESSION['user_id'] <= 0
) {

    echo '<script>
        alert("يرجى تسجيل الدخول أولاً");
        window.location.href="user/login.php";
    </script>';

    exit();

}

$user_id = (int)$_SESSION['user_id'];


/* =========================================================
   البحث
========================================================= */

$search = '';

if (isset($_GET['search'])) {

    $search = trim($_GET['search']);

}


/* =========================================================
   إضافة الخدمة إلى السلة
========================================================= */

if (isset($_POST['add_to_cart'])) {

    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id <= 0) {

        echo '<script>
            alert("الخدمة غير صحيحة");
            window.location.href="search.php";
        </script>';

        exit();

    }


    /* -----------------------------------------------------
       جلب الخدمة من قاعدة البيانات
       مهم: لا نثق بالسعر القادم من المتصفح
    ----------------------------------------------------- */

    $stmt = $con->prepare("
        SELECT
            id,
            proname,
            proprice,
            proimg
        FROM product
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $product_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        echo '<script>
            alert("الخدمة غير موجودة");
            window.location.href="search.php";
        </script>';

        exit();

    }

    $product = $result->fetch_assoc();

    $stmt->close();


    /* -----------------------------------------------------
       التحقق هل الخدمة موجودة في السلة
    ----------------------------------------------------- */

    $stmt = $con->prepare("
        SELECT id
        FROM cart
        WHERE product_id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "ii",
        $product_id,
        $user_id
    );

    $stmt->execute();

    $cart_result = $stmt->get_result();

    if ($cart_result->num_rows > 0) {

        echo '<script>
            alert("هذه الخدمة مضافة مسبقاً إلى خدماتي");
            window.location.href="cart.php";
        </script>';

        exit();

    }

    $stmt->close();


    /* -----------------------------------------------------
       إضافة الخدمة
    ----------------------------------------------------- */

    $product_name  = trim($product['proname'] ?? '');
    $product_price = (float)($product['proprice'] ?? 0);
    $product_img   = trim($product['proimg'] ?? '');
    $quantity      = 1;


    $stmt = $con->prepare("
        INSERT INTO cart
        (
            product_id,
            name,
            price,
            img,
            quantity,
            user_id
        )
        VALUES
        (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isdssi",
        $product_id,
        $product_name,
        $product_price,
        $product_img,
        $quantity,
        $user_id
    );


    if ($stmt->execute()) {

        echo '<script>
            alert("تمت إضافة الخدمة بنجاح إلى خدماتي");
            window.location.href="cart.php";
        </script>';

        exit();

    } else {

        echo '<script>
            alert("حدث خطأ أثناء إضافة الخدمة");
        </script>';

    }

    $stmt->close();

}


/* =========================================================
   جلب نتائج البحث
========================================================= */

$products = [];


if ($search !== '') {

    /*
     * البحث عن:
     * بداية الاسم
     * أي جزء من الاسم
     * الوصف
     * رقم الخدمة
     */

    $search_like = '%' . $search . '%';


    $stmt = $con->prepare("
        SELECT
            id,
            proname,
            proprice,
            proimg,
            prodescrip
        FROM product
        WHERE
            proname LIKE ?
            OR prodescrip LIKE ?
            OR CAST(id AS CHAR) LIKE ?
        ORDER BY
            CASE
                WHEN proname LIKE CONCAT(?, '%')
                THEN 0
                ELSE 1
            END,
            id DESC
    ");


    $stmt->bind_param(
        "ssss",
        $search_like,
        $search_like,
        $search_like,
        $search
    );


    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $products[] = $row;

    }

    $stmt->close();

}

?>

<!DOCTYPE html>

<html
    lang="ar"
    dir="rtl"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    البحث عن الخدمات
</title>


<style>

/* =========================================================
   الصفحة
========================================================= */

.search-page{

    min-height:70vh;

    background:#f5f7fa;

    padding:
        35px 15px 60px;

}


.search-wrapper{

    width:100%;

    max-width:1200px;

    margin:auto;

}


/* =========================================================
   رأس الصفحة
========================================================= */

.search-header{

    background:#fff;

    border-radius:20px;

    padding:25px;

    margin-bottom:20px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

    text-align:center;

}


.search-header h1{

    margin:0;

    color:#1f2937;

    font-size:28px;

}


.search-header p{

    margin:
        10px 0 0;

    color:#6b7280;

}


/* =========================================================
   مربع البحث
========================================================= */

.search-form{

    display:flex;

    gap:10px;

    margin-top:22px;

    max-width:800px;

    margin-left:auto;

    margin-right:auto;

}


.search-input{

    flex:1;

    height:52px;

    border:
        1px solid #d1d5db;

    border-radius:12px;

    padding:
        0 18px;

    font-size:16px;

    outline:none;

    transition:.2s;

}


.search-input:focus{

    border-color:#00bcd4;

    box-shadow:
        0 0 0 3px
        rgba(0,188,212,.12);

}


.search-button{

    min-width:120px;

    border:none;

    border-radius:12px;

    background:#00bcd4;

    color:#fff;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    padding:0 20px;

    transition:.2s;

}


.search-button:hover{

    background:#0097a7;

    transform:
        translateY(-1px);

}


/* =========================================================
   معلومات النتائج
========================================================= */

.results-info{

    background:#fff;

    border-radius:14px;

    padding:15px 20px;

    margin-bottom:20px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.05);

    color:#374151;

}


.results-info strong{

    color:#00a5bb;

}


/* =========================================================
   شبكة الخدمات
========================================================= */

.products-grid{

    display:grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:20px;

}


/* =========================================================
   بطاقة الخدمة
========================================================= */

.product-card{

    background:#fff;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

    transition:
        transform .2s,
        box-shadow .2s;

    display:flex;

    flex-direction:column;

}


.product-card:hover{

    transform:
        translateY(-4px);

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.12);

}


/* =========================================================
   صورة الخدمة
========================================================= */

.product-image-box{

    width:100%;

    height:210px;

    background:#f1f5f9;

    overflow:hidden;

}


.product-image{

    width:100%;

    height:100%;

    object-fit:cover;

    transition:
        transform .3s;

}


.product-card:hover
.product-image{

    transform:
        scale(1.04);

}


/* =========================================================
   محتوى البطاقة
========================================================= */

.product-body{

    padding:18px;

    display:flex;

    flex-direction:column;

    flex:1;

}


.product-id{

    font-size:12px;

    color:#9ca3af;

    margin-bottom:6px;

}


.product-name{

    margin:0;

    color:#1f2937;

    font-size:18px;

    line-height:1.5;

}


.product-description{

    color:#6b7280;

    font-size:13px;

    line-height:1.7;

    margin:
        10px 0;

    min-height:44px;

}


.product-price{

    color:#00a5bb;

    font-size:20px;

    font-weight:bold;

    margin:
        10px 0 15px;

}


.product-price small{

    font-size:13px;

    color:#6b7280;

}


/* =========================================================
   الأزرار
========================================================= */

.product-actions{

    display:flex;

    gap:8px;

    margin-top:auto;

}


.details-btn{

    flex:1;

    display:flex;

    align-items:center;

    justify-content:center;

    text-decoration:none;

    background:#f1f5f9;

    color:#374151;

    border-radius:10px;

    padding:11px 8px;

    font-weight:bold;

    font-size:13px;

    transition:.2s;

}


.details-btn:hover{

    background:#e2e8f0;

}


.add-btn{

    flex:1;

    border:none;

    background:#00bcd4;

    color:#fff;

    border-radius:10px;

    padding:11px 8px;

    font-weight:bold;

    font-size:13px;

    cursor:pointer;

    transition:.2s;

}


.add-btn:hover{

    background:#0097a7;

}


/* =========================================================
   لا توجد نتائج
========================================================= */

.no-results{

    background:#fff;

    border-radius:20px;

    padding:70px 20px;

    text-align:center;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

}


.no-results-icon{

    width:90px;

    height:90px;

    margin:
        0 auto 20px;

    border-radius:50%;

    background:#fff1f2;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:40px;

}


.no-results h2{

    margin:
        0 0 10px;

    color:#374151;

}


.no-results p{

    color:#6b7280;

}


/* =========================================================
   البداية
========================================================= */

.search-hint{

    background:#fff;

    border-radius:20px;

    padding:70px 20px;

    text-align:center;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

}


.search-hint-icon{

    font-size:55px;

    margin-bottom:15px;

}


.search-hint h2{

    margin:
        0 0 10px;

    color:#374151;

}


.search-hint p{

    margin:0;

    color:#6b7280;

}


/* =========================================================
   الجوال
========================================================= */

@media(max-width:1000px){

    .products-grid{

        grid-template-columns:
            repeat(3,1fr);

    }

}


@media(max-width:750px){

    .products-grid{

        grid-template-columns:
            repeat(2,1fr);

        gap:12px;

    }

    .product-image-box{

        height:170px;

    }

}


@media(max-width:550px){

    .search-page{

        padding:
            20px 10px 40px;

    }


    .search-header{

        padding:20px 15px;

    }


    .search-header h1{

        font-size:22px;

    }


    .search-form{

        flex-direction:column;

    }


    .search-button{

        height:48px;

    }


    .products-grid{

        grid-template-columns:1fr;

    }


    .product-image-box{

        height:220px;

    }

}

</style>

</head>


<body>


<div class="search-page">

<div class="search-wrapper">


<!-- =====================================================
     رأس البحث
===================================================== -->

<div class="search-header">

    <h1>
        🔎 البحث عن الخدمات
    </h1>

    <p>
        ابحث باسم الخدمة أو جزء من الاسم أو رقم الخدمة
    </p>


    <form
        method="GET"
        action="search.php"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            class="search-input"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="اكتب اسم الخدمة أو أول حرف منها..."
            autocomplete="off"
            autofocus
        >


        <button
            type="submit"
            class="search-button"
        >

            🔎 بحث

        </button>

    </form>

</div>


<?php if ($search !== ''): ?>


<!-- =====================================================
     النتائج
===================================================== -->

<div class="results-info">

    نتائج البحث عن:

    <strong>
        <?= htmlspecialchars($search) ?>
    </strong>

    —

    تم العثور على:

    <strong>
        <?= count($products) ?>
    </strong>

    خدمة

</div>


<?php if (!empty($products)): ?>


<div class="products-grid">


<?php foreach ($products as $product): ?>


<?php

$product_id =
    (int)$product['id'];

$product_name =
    trim(
        $product['proname'] ?? ''
    );

$product_price =
    (float)(
        $product['proprice'] ?? 0
    );

$product_image =
    trim(
        $product['proimg'] ?? ''
    );

$product_description =
    trim(
        $product['prodescrip'] ?? ''
    );

?>


<div class="product-card">


<!-- الصورة -->

<div class="product-image-box">

<a
    href="detalis.php?id=<?= $product_id ?>"
>

<?php if ($product_image !== ''): ?>

<img
    class="product-image"
    src="uploads/img/<?= htmlspecialchars(
        basename($product_image)
    ) ?>"
    alt="<?= htmlspecialchars($product_name) ?>"
>

<?php else: ?>

<div
    style="
        height:100%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:50px;
        color:#cbd5e1;
    "
>
    🛠️
</div>

<?php endif; ?>

</a>

</div>


<!-- المحتوى -->

<div class="product-body">


<div class="product-id">

    رقم الخدمة:
    #<?= $product_id ?>

</div>


<h2 class="product-name">

    <?= htmlspecialchars(
        $product_name
    ) ?>

</h2>


<p class="product-description">

<?php

if ($product_description !== '') {

    echo htmlspecialchars(
        mb_strimwidth(
            $product_description,
            0,
            100,
            '...'
        )
    );

} else {

    echo 'لا يوجد وصف للخدمة.';

}

?>

</p>


<div class="product-price">

    <?= number_format(
        $product_price,
        2
    ) ?>

    <small>
        ريال
    </small>

</div>


<div class="product-actions">


<a
    href="detalis.php?id=<?= $product_id ?>"
    class="details-btn"
>

    👁️ التفاصيل

</a>


<form
    method="POST"
    style="flex:1;"
>

    <input
        type="hidden"
        name="product_id"
        value="<?= $product_id ?>"
    >


    <button
        type="submit"
        name="add_to_cart"
        class="add-btn"
    >

        🛒 أضف إلى خدماتي

    </button>

</form>


</div>


</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<!-- =====================================================
     لا توجد نتائج
===================================================== -->

<div class="no-results">

    <div class="no-results-icon">
        🔍
    </div>

    <h2>
        لم يتم العثور على الخدمة
    </h2>

    <p>
        حاول البحث باسم مختلف أو اكتب جزءاً من اسم الخدمة.
    </p>

</div>


<?php endif; ?>


<?php else: ?>


<!-- =====================================================
     قبل البحث
===================================================== -->

<div class="search-hint">

    <div class="search-hint-icon">
        🔎
    </div>

    <h2>
        ابحث عن الخدمة التي تحتاجها
    </h2>

    <p>
        اكتب أول حرف من اسم الخدمة أو جزءاً من الاسم لعرض النتائج.
    </p>

</div>


<?php endif; ?>


</div>

</div>


</body>

</html>


<?php

include('file/foter.php');

?>