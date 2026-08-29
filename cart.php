<?php
session_start();

/* =========================================================
   التحقق من تسجيل الدخول
========================================================= */

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {

    echo '<script>
        alert("يرجى تسجيل الدخول أولاً");
        window.location.href="user/login.php";
    </script>';

    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* =========================================================
   الاتصال بقاعدة البيانات
========================================================= */

include('file/header.php');

/* =========================================================
   إضافة خدمة إلى السلة
========================================================= */

if (isset($_POST['add'])) {

    $product_id       = (int)($_POST['product_id'] ?? 0);
    $productname      = trim($_POST['name'] ?? '');
    $productprice     = (float)($_POST['price'] ?? 0);
    $productimg       = trim($_POST['img'] ?? '');
    $productquantity  = (int)($_POST['quantity'] ?? 1);

    if ($productquantity < 1) {
        $productquantity = 1;
    }

    if ($product_id > 0 && $productname !== '') {

        /* التحقق هل الخدمة موجودة مسبقاً */

        $stmt = mysqli_prepare(
            $con,
            "SELECT id FROM cart WHERE product_id = ? AND user_id = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $product_id,
            $user_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            echo '<script>
                alert("هذه الخدمة مضافة مسبقاً إلى خدماتي");
                window.location.href="cart.php";
            </script>';

            exit();

        } else {

            /* إضافة الخدمة */

            $stmt = mysqli_prepare(
                $con,
                "INSERT INTO cart
                (product_id, name, price, img, quantity, user_id)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isdssi",
                $product_id,
                $productname,
                $productprice,
                $productimg,
                $productquantity,
                $user_id
            );

            if (mysqli_stmt_execute($stmt)) {

                echo '<script>
                    alert("تمت إضافة الخدمة بنجاح إلى خدماتي");
                    window.location.href="cart.php";
                </script>';

                exit();

            } else {

                echo '<script>
                    alert("عذراً، لم تتم إضافة الخدمة");
                </script>';
            }
        }
    }
}


/* =========================================================
   حذف خدمة
========================================================= */

if (isset($_POST['delete_c'])) {

    $cart_id = (int)($_POST['id'] ?? 0);

    if ($cart_id > 0) {

        $stmt = mysqli_prepare(
            $con,
            "DELETE FROM cart WHERE id = ? AND user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $cart_id,
            $user_id
        );

        if (mysqli_stmt_execute($stmt)) {

            echo '<script>
                alert("تم حذف الخدمة من خدماتي");
                window.location.href="cart.php";
            </script>';

            exit();

        } else {

            echo '<script>
                alert("عذراً، لم يتم حذف الخدمة");
            </script>';
        }
    }
}


/* =========================================================
   تحديث الكمية
========================================================= */

if (isset($_POST['update_qty'])) {

    $cart_id = (int)($_POST['id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($quantity < 1) {
        $quantity = 1;
    }

    if ($cart_id > 0) {

        $stmt = mysqli_prepare(
            $con,
            "UPDATE cart
             SET quantity = ?
             WHERE id = ? AND user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $quantity,
            $cart_id,
            $user_id
        );

        if (mysqli_stmt_execute($stmt)) {

            echo '<script>
                window.location.href="cart.php";
            </script>';

            exit();

        } else {

            echo '<script>
                alert("حدث خطأ أثناء تحديث الكمية");
            </script>';
        }
    }
}


/* =========================================================
   جلب اسم المستخدم
========================================================= */

$username = "عميلنا العزيز";

$stmt = mysqli_prepare(
    $con,
    "SELECT username FROM users WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$user_result = mysqli_stmt_get_result($stmt);

if ($user_result && mysqli_num_rows($user_result) > 0) {

    $user_row = mysqli_fetch_assoc($user_result);

    if (!empty($user_row['username'])) {
        $username = $user_row['username'];
    }
}


/* =========================================================
   جلب خدمات السلة
========================================================= */

$stmt = mysqli_prepare(
    $con,
    "SELECT * FROM cart
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$cart_result = mysqli_stmt_get_result($stmt);

$total = 0;
$item_count = 0;

$cart_items = [];

if ($cart_result && mysqli_num_rows($cart_result) > 0) {

    while ($row = mysqli_fetch_assoc($cart_result)) {

        $quantity = max(1, (int)$row['quantity']);
        $price = (float)$row['price'];

        $subtotal = $quantity * $price;

        $total += $subtotal;
        $item_count += $quantity;

        $row['quantity'] = $quantity;
        $row['subtotal'] = $subtotal;

        $cart_items[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>خدماتي</title>

<style>

/* =========================================================
   الإعدادات العامة
========================================================= */

.cart-page{
    width:100%;
    min-height:70vh;
    padding:30px 15px 50px;
    background:#f5f7fa;
    direction:rtl;
}

.cart-wrapper{
    max-width:1200px;
    margin:auto;
}


/* =========================================================
   العنوان
========================================================= */

.cart-header{
    background:#fff;
    border-radius:18px;
    padding:25px;
    margin-bottom:20px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    box-shadow:0 5px 20px rgba(0,0,0,.07);
}

.cart-header h1{
    margin:0;
    color:#1f2937;
    font-size:26px;
}

.cart-header p{
    margin:7px 0 0;
    color:#6b7280;
    font-size:14px;
}

.cart-count-title{
    background:#00bcd4;
    color:#fff;

    min-width:45px;
    height:45px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:18px;
    font-weight:bold;
}


/* =========================================================
   بطاقة الترحيب
========================================================= */

.welcome-box{
    background:linear-gradient(
        135deg,
        #00bcd4,
        #008fa3
    );

    color:#fff;

    border-radius:18px;

    padding:20px 25px;

    margin-bottom:20px;

    box-shadow:0 5px 20px rgba(0,188,212,.2);
}

.welcome-box h2{
    margin:0 0 5px;
    font-size:21px;
}

.welcome-box p{
    margin:0;
    opacity:.9;
}


/* =========================================================
   محتوى السلة
========================================================= */

.cart-content{
    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        320px;

    gap:20px;

    align-items:start;
}


/* =========================================================
   جدول السلة
========================================================= */

.cart-table-box{
    background:#fff;

    border-radius:18px;

    overflow:hidden;

    box-shadow:0 5px 20px rgba(0,0,0,.07);
}

.cart-table{
    width:100%;
    border-collapse:collapse;
}

.cart-table thead{
    background:#f1f5f9;
}

.cart-table th{
    padding:16px 10px;

    color:#374151;

    font-size:14px;

    border-bottom:1px solid #e5e7eb;
}

.cart-table td{
    padding:15px 10px;

    text-align:center;

    border-bottom:1px solid #eef0f3;

    vertical-align:middle;
}

.cart-table tr:last-child td{
    border-bottom:none;
}


/* =========================================================
   صورة الخدمة
========================================================= */

.service-image{
    width:70px;
    height:70px;

    object-fit:cover;

    border-radius:12px;

    border:1px solid #eee;

    display:block;

    margin:auto;
}


/* =========================================================
   اسم الخدمة
========================================================= */

.service-name{
    font-weight:bold;
    color:#1f2937;

    font-size:15px;

    max-width:180px;
}


/* =========================================================
   السعر
========================================================= */

.price{
    color:#111827;
    font-weight:bold;
}

.subtotal{
    color:#00a5bb;
    font-weight:bold;
}


/* =========================================================
   التحكم بالكمية
========================================================= */

.quantity-form{
    display:flex;

    align-items:center;

    justify-content:center;

    gap:5px;
}

.quantity-input{
    width:55px;

    height:38px;

    text-align:center;

    border:1px solid #d1d5db;

    border-radius:8px;

    font-size:15px;
}

.quantity-btn{
    width:35px;
    height:35px;

    border:none;

    border-radius:8px;

    background:#e5f9fc;

    color:#008fa3;

    font-size:18px;

    cursor:pointer;

    transition:.2s;
}

.quantity-btn:hover{
    background:#00bcd4;
    color:#fff;
}


/* =========================================================
   زر التحديث
========================================================= */

.update-btn{
    border:none;

    background:#2563eb;

    color:#fff;

    padding:8px 12px;

    border-radius:8px;

    cursor:pointer;

    font-size:13px;

    margin-top:5px;

    transition:.2s;
}

.update-btn:hover{
    background:#174ea6;
}


/* =========================================================
   زر الحذف
========================================================= */

.delete-btn{
    border:none;

    background:#fee2e2;

    color:#dc2626;

    width:38px;
    height:38px;

    border-radius:9px;

    cursor:pointer;

    font-size:16px;

    transition:.2s;
}

.delete-btn:hover{
    background:#dc2626;
    color:#fff;
}


/* =========================================================
   ملخص الطلب
========================================================= */

.summary{
    background:#fff;

    border-radius:18px;

    padding:25px;

    box-shadow:0 5px 20px rgba(0,0,0,.07);

    position:sticky;

    top:20px;
}

.summary h2{
    margin:0 0 20px;

    color:#1f2937;

    font-size:20px;

    border-bottom:1px solid #eee;

    padding-bottom:15px;
}

.summary-row{
    display:flex;

    justify-content:space-between;

    align-items:center;

    margin:15px 0;

    color:#6b7280;
}

.summary-row strong{
    color:#111827;
}

.summary-total{
    border-top:1px solid #eee;

    margin-top:20px;

    padding-top:20px;
}

.summary-total span{
    color:#374151;

    font-weight:bold;
}

.summary-total strong{
    color:#00a5bb;

    font-size:25px;
}


/* =========================================================
   الأزرار
========================================================= */

.checkout-btn{
    width:100%;

    border:none;

    padding:14px;

    background:#00bcd4;

    color:#fff;

    border-radius:10px;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    margin-top:15px;

    transition:.3s;
}

.checkout-btn:hover{
    background:#0097a7;

    transform:translateY(-2px);
}

.continue-btn{
    width:100%;

    display:block;

    text-align:center;

    padding:12px;

    background:#f1f5f9;

    color:#374151;

    border-radius:10px;

    text-decoration:none;

    margin-top:10px;

    transition:.2s;
}

.continue-btn:hover{
    background:#e2e8f0;
}


/* =========================================================
   السلة الفارغة
========================================================= */

.empty-cart{
    background:#fff;

    border-radius:18px;

    padding:60px 20px;

    text-align:center;

    box-shadow:0 5px 20px rgba(0,0,0,.07);
}

.empty-cart-icon{
    width:80px;
    height:80px;

    margin:0 auto 20px;

    border-radius:50%;

    background:#e5f9fc;

    color:#00a5bb;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:35px;
}

.empty-cart h2{
    margin:0 0 10px;

    color:#374151;
}

.empty-cart p{
    color:#6b7280;

    margin-bottom:20px;
}

.empty-cart a{
    display:inline-block;

    padding:12px 25px;

    background:#00bcd4;

    color:#fff;

    text-decoration:none;

    border-radius:10px;
}


/* =========================================================
   الجوال
========================================================= */

@media(max-width:900px){

    .cart-content{
        grid-template-columns:1fr;
    }

    .summary{
        position:static;
    }
}


@media(max-width:700px){

    .cart-page{
        padding:15px 8px 30px;
    }

    .cart-header{
        padding:18px;

        flex-direction:column;

        align-items:flex-start;

        gap:15px;
    }

    .cart-header h1{
        font-size:22px;
    }

    .cart-table-box{
        overflow-x:auto;
    }

    .cart-table{
        min-width:850px;
    }

    .welcome-box{
        padding:18px;
    }

}


/* =========================================================
   تحسين شريط التمرير
========================================================= */

.cart-table-box::-webkit-scrollbar{
    height:7px;
}

.cart-table-box::-webkit-scrollbar-thumb{
    background:#00bcd4;

    border-radius:10px;
}

</style>

</head>


<body>

<div class="cart-page">

<div class="cart-wrapper">


<!-- =====================================================
     عنوان الصفحة
===================================================== -->

<div class="cart-header">

    <div>

        <h1>
            🛒 خدماتي
        </h1>

        <p>
            الخدمات التي اخترتها لإتمام طلبك
        </p>

    </div>

    <div class="cart-count-title">

        <?= $item_count ?>

    </div>

</div>


<!-- =====================================================
     الترحيب
===================================================== -->

<div class="welcome-box">

    <h2>
        أهلاً بك، <?= htmlspecialchars($username) ?> 👋
    </h2>

    <p>
        راجع خدماتك قبل إتمام الطلب
    </p>

</div>


<?php if (count($cart_items) > 0): ?>


<div class="cart-content">


<!-- =====================================================
     جدول الخدمات
===================================================== -->

<div class="cart-table-box">

<table class="cart-table">

<thead>

<tr>

    <th>الخدمة</th>

    <th>رقم الخدمة</th>

    <th>اسم الخدمة</th>

    <th>الكمية</th>

    <th>السعر</th>

    <th>الإجمالي</th>

    <th>حذف</th>

</tr>

</thead>

<tbody>


<?php foreach ($cart_items as $item): ?>

<tr>


<!-- الصورة -->

<td>

<a href="detalis.php?id=<?= (int)$item['product_id'] ?>">

<img
    class="service-image"
    src="uploads/img/<?= htmlspecialchars($item['img']) ?>"
    alt="<?= htmlspecialchars($item['name']) ?>"
>

</a>

</td>


<!-- رقم الخدمة -->

<td>

<strong>

<?= (int)$item['product_id'] ?>

</strong>

</td>


<!-- الاسم -->

<td>

<div class="service-name">

<?= htmlspecialchars($item['name']) ?>

</div>

</td>


<!-- الكمية -->

<td>

<form
    method="POST"
    action="cart.php"
    class="quantity-form"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$item['id'] ?>"
>

<button
    type="button"
    class="quantity-btn minus-btn"
>
−
</button>

<input
    class="quantity-input"
    type="number"
    name="quantity"
    value="<?= (int)$item['quantity'] ?>"
    min="1"
    max="99"
>

<button
    type="button"
    class="quantity-btn plus-btn"
>
+
</button>

<div>

<button
    type="submit"
    name="update_qty"
    class="update-btn"
>
تحديث
</button>

</div>

</form>

</td>


<!-- السعر -->

<td>

<span class="price">

<?= number_format((float)$item['price'], 2) ?>

 ريال

</span>

</td>


<!-- الإجمالي -->

<td>

<span class="subtotal">

<?= number_format((float)$item['subtotal'], 2) ?>

 ريال

</span>

</td>


<!-- حذف -->

<td>

<form
    method="POST"
    action="cart.php"
    onsubmit="return confirm('هل أنت متأكد من حذف هذه الخدمة؟');"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$item['id'] ?>"
>

<button
    type="submit"
    name="delete_c"
    class="delete-btn"
    title="حذف الخدمة"
>

🗑

</button>

</form>

</td>


</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<!-- =====================================================
     ملخص السلة
===================================================== -->

<div class="summary">

<h2>
    ملخص الطلب
</h2>


<div class="summary-row">

<span>
عدد الخدمات
</span>

<strong>

<?= count($cart_items) ?>

</strong>

</div>


<div class="summary-row">

<span>
إجمالي الكميات
</span>

<strong>

<?= $item_count ?>

</strong>

</div>


<div class="summary-row summary-total">

<span>
الإجمالي النهائي
</span>

<strong>

<?= number_format($total, 2) ?>

 ريال

</strong>

</div>


<a
    href="order.php"
    class="checkout-btn"
>
إتمام الطلب
</a>


<a
    href="index.php"
    class="continue-btn"
>
← متابعة تصفح الخدمات
</a>

</div>


</div>


<?php else: ?>


<!-- =====================================================
     السلة فارغة
===================================================== -->

<div class="empty-cart">

    <div class="empty-cart-icon">

        🛒

    </div>

    <h2>
        خدماتك فارغة
    </h2>

    <p>
        لم تقم بإضافة أي خدمة إلى خدماتي حتى الآن.
    </p>

    <a href="index.php">
        تصفح الخدمات
    </a>

</div>

<?php endif; ?>


</div>

</div>


<script>

/* =========================================================
   أزرار زيادة ونقصان الكمية
========================================================= */

document.querySelectorAll('.quantity-form').forEach(function(form){

    const input = form.querySelector('.quantity-input');

    const minus = form.querySelector('.minus-btn');

    const plus = form.querySelector('.plus-btn');


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


</body>

</html>