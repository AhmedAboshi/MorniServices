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


/* =====================================================
   التحقق من ID الخدمة
===================================================== */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    echo '<script>
    alert("الخدمة غير موجودة");
    window.location.href="index.php";
    </script>';
    exit();
}


/* =====================================================
   جلب الخدمة
===================================================== */

$stmt = $con->prepare("
    SELECT *
    FROM product
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 0){

    echo '<script>
    alert("الخدمة غير موجودة");
    window.location.href="index.php";
    </script>';

    exit();
}

$product = $result->fetch_assoc();


/* =====================================================
   إضافة تقييم
===================================================== */

$comment_success = '';
$comment_error = '';

if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['add_comment'])
){

    $comment = trim($_POST['comment'] ?? '');

    if($comment === ''){

        $comment_error = "الرجاء كتابة تقييمك.";

    }else{

        $username = $_SESSION['username'] ?? 'عميل';

        /*
         * ملاحظة:
         * نحافظ على جدول comments الحالي
         * لأن الأعمدة التي نعرفها حاليًا هي:
         * usename / comment
         */

        $stmtComment = $con->prepare("
            INSERT INTO comments (usename, comment)
            VALUES (?, ?)
        ");

        if($stmtComment){

            $stmtComment->bind_param(
                "ss",
                $username,
                $comment
            );

            if($stmtComment->execute()){

                $comment_success =
                    "تم إرسال تقييمك بنجاح.";

            }else{

                $comment_error =
                    "حدث خطأ أثناء إرسال التقييم.";

            }

        }else{

            $comment_error =
                "تعذر تجهيز عملية التقييم.";

        }
    }
}

?>

<style>

/* =====================================================
   صفحة التفاصيل
===================================================== */

.details-page{
    width:100%;
    max-width:1200px;
    margin:30px auto;
    padding:0 20px;
}


/* =====================================================
   بطاقة التفاصيل
===================================================== */

.details-card{
    background:#fff;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    overflow:hidden;

    display:grid;
    grid-template-columns:1fr 1fr;

    min-height:450px;
}


/* =====================================================
   الصورة
===================================================== */

.details-image{
    background:#f6f8fa;

    display:flex;
    justify-content:center;
    align-items:center;

    padding:25px;

    min-height:450px;
}

.details-image img{

    width:100%;
    max-width:500px;

    height:420px;

    object-fit:cover;

    border-radius:15px;

    display:block;
}


/* =====================================================
   معلومات الخدمة
===================================================== */

.details-info{

    padding:40px;

    display:flex;
    flex-direction:column;

    justify-content:center;
}


.details-title{

    font-size:30px;

    color:#1f2937;

    margin-bottom:15px;

}


.details-category{

    display:inline-block;

    width:max-content;

    background:#e9f9fb;

    color:#008fa3;

    padding:7px 14px;

    border-radius:8px;

    text-decoration:none;

    font-size:14px;

    margin-bottom:20px;

}


.details-price{

    font-size:26px;

    color:#e53935;

    font-weight:bold;

    margin-bottom:20px;

}


.details-description-title{

    font-size:18px;

    color:#374151;

    margin-bottom:8px;

}


.details-description{

    color:#6b7280;

    line-height:1.9;

    font-size:15px;

    margin-bottom:25px;

}


/* =====================================================
   الكمية
===================================================== */

.details-quantity{

    display:flex;

    align-items:center;

    gap:8px;

    margin-bottom:20px;

}


.details-quantity button{

    width:40px;

    height:40px;

    border:1px solid #ddd;

    background:#f5f5f5;

    border-radius:8px;

    font-size:20px;

    cursor:pointer;

}


.details-quantity button:hover{

    background:#e9f9fb;

}


.details-quantity input{

    width:65px;

    height:40px;

    text-align:center;

    border:1px solid #ddd;

    border-radius:8px;

    font-size:16px;

}


/* =====================================================
   زر السلة
===================================================== */

.details-cart-btn{

    width:100%;

    height:50px;

    border:none;

    border-radius:10px;

    background:#00a9bd;

    color:#fff;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

    transition:.2s;

}


.details-cart-btn:hover{

    background:#008fa3;

}


/* =====================================================
   الخدمات الحديثة
===================================================== */

.recent-services{

    margin-top:25px;

    background:#fff;

    border-radius:18px;

    padding:20px;

    box-shadow:0 4px 15px rgba(0,0,0,.07);

}


.section-title{

    font-size:20px;

    color:#222;

    margin-bottom:18px;

}


.recent-grid{

    display:grid;

    grid-template-columns:repeat(5,1fr);

    gap:15px;

}


.recent-item{

    background:#f8fafc;

    border-radius:12px;

    overflow:hidden;

    text-decoration:none;

    transition:.2s;

}


.recent-item:hover{

    transform:translateY(-3px);

}


.recent-item img{

    width:100%;

    height:120px;

    object-fit:cover;

    display:block;

}


.recent-name{

    padding:10px;

    color:#333;

    font-size:14px;

    font-weight:bold;

    text-align:center;

}


/* =====================================================
   التقييمات
===================================================== */

.reviews-section{

    margin-top:25px;

    background:#fff;

    border-radius:18px;

    padding:25px;

    box-shadow:0 4px 15px rgba(0,0,0,.07);

}


.review-form textarea{

    width:100%;

    min-height:110px;

    resize:vertical;

    border:1px solid #ddd;

    border-radius:10px;

    padding:12px;

    font-family:Tahoma;

    font-size:14px;

    margin-bottom:10px;

}


.review-form button{

    width:160px;

    height:42px;

    border:none;

    border-radius:8px;

    background:#00a9bd;

    color:#fff;

    cursor:pointer;

    font-weight:bold;

}


.review-success{

    background:#ecfdf5;

    color:#15803d;

    padding:10px;

    border-radius:8px;

    margin-bottom:15px;

}


.review-error{

    background:#fef2f2;

    color:#dc2626;

    padding:10px;

    border-radius:8px;

    margin-bottom:15px;

}


.reviews-list{

    margin-top:25px;

}


.review{

    border:1px solid #eee;

    border-radius:12px;

    padding:15px;

    margin-bottom:12px;

    background:#fafafa;

}


.review-user{

    color:#2563eb;

    font-weight:bold;

    margin-bottom:7px;

}


.review-text{

    color:#444;

    line-height:1.8;

}


/* =====================================================
   الجوال
===================================================== */

@media(max-width:800px){

    .details-card{

        grid-template-columns:1fr;

    }


    .details-image{

        min-height:auto;

        padding:15px;

    }


    .details-image img{

        height:300px;

    }


    .details-info{

        padding:25px;

    }


    .details-title{

        font-size:24px;

    }


    .recent-grid{

        grid-template-columns:repeat(2,1fr);

    }

}


@media(max-width:450px){

    .details-page{

        padding:0 10px;

        margin-top:15px;

    }


    .details-image img{

        height:230px;

    }


    .details-info{

        padding:18px;

    }


    .details-title{

        font-size:21px;

    }


    .details-price{

        font-size:22px;

    }


    .recent-grid{

        grid-template-columns:repeat(2,1fr);

        gap:8px;

    }


    .recent-item img{

        height:100px;

    }

}

</style>


<div class="details-page">


    <!-- =================================================
         تفاصيل الخدمة
    ================================================== -->

    <div class="details-card">


        <!-- الصورة -->

        <div class="details-image">

            <img
                src="uploads/img/<?= htmlspecialchars($product['proimg']) ?>"
                alt="<?= htmlspecialchars($product['proname']) ?>"
            >

        </div>


        <!-- المعلومات -->

        <div class="details-info">


            <h1 class="details-title">

                <?= htmlspecialchars($product['proname']) ?>

            </h1>


            <!-- القسم -->

            <a
                class="details-category"
                href="section.php?section=<?= urlencode($product['prosection']) ?>"
            >

                <?= htmlspecialchars($product['prosection']) ?>

            </a>


            <!-- السعر -->

            <div class="details-price">

                <?= number_format((float)$product['proprice'],2) ?>

                ريال

            </div>


            <!-- الوصف -->

            <div class="details-description-title">

                تفاصيل الخدمة

            </div>


            <div class="details-description">

                <?= nl2br(htmlspecialchars($product['prodescrip'])) ?>

            </div>


            <!-- إضافة للسلة -->

            <form
                action="cart.php"
                method="POST"
            >


                <div class="details-quantity">


                    <button
                        type="button"
                        id="qtyMinus"
                    >

                        −

                    </button>


                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="1"
                        min="1"
                        max="99"
                    >


                    <button
                        type="button"
                        id="qtyPlus"
                    >

                        +

                    </button>


                </div>


                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int)$product['id'] ?>"
                >


                <input
                    type="hidden"
                    name="name"
                    value="<?= htmlspecialchars($product['proname']) ?>"
                >


                <input
                    type="hidden"
                    name="price"
                    value="<?= htmlspecialchars($product['proprice']) ?>"
                >


                <input
                    type="hidden"
                    name="img"
                    value="<?= htmlspecialchars($product['proimg']) ?>"
                >


                <button
                    type="submit"
                    name="add"
                    class="details-cart-btn"
                >

                    🛒 إضافة إلى خدماتي

                </button>


            </form>


        </div>

    </div>



    <!-- =================================================
         الخدمات الحديثة
    ================================================== -->

    <div class="recent-services">


        <h2 class="section-title">

            خدمات قد تهمك

        </h2>


        <div class="recent-grid">


            <?php

            $recentStmt = $con->prepare("
                SELECT id, proname, proimg
                FROM product
                WHERE id != ?
                ORDER BY id DESC
                LIMIT 5
            ");

            $recentStmt->bind_param("i",$id);

            $recentStmt->execute();

            $recentResult = $recentStmt->get_result();


            while($recent = $recentResult->fetch_assoc()){

            ?>


                <a
                    class="recent-item"
                    href="detalis.php?id=<?= (int)$recent['id'] ?>"
                >


                    <img
                        src="uploads/img/<?= htmlspecialchars($recent['proimg']) ?>"
                        alt="<?= htmlspecialchars($recent['proname']) ?>"
                    >


                    <div class="recent-name">

                        <?= htmlspecialchars($recent['proname']) ?>

                    </div>


                </a>


            <?php } ?>


        </div>

    </div>



    <!-- =================================================
         التقييمات
    ================================================== -->

    <div class="reviews-section">


        <h2 class="section-title">

            ⭐ تقييم الخدمة

        </h2>


        <?php if($comment_success): ?>

            <div class="review-success">

                <?= htmlspecialchars($comment_success) ?>

            </div>

        <?php endif; ?>


        <?php if($comment_error): ?>

            <div class="review-error">

                <?= htmlspecialchars($comment_error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="review-form"
        >


            <textarea
                name="comment"
                placeholder="اكتب تقييمك للخدمة..."
            ></textarea>


            <button
                type="submit"
                name="add_comment"
            >

                إرسال التقييم

            </button>


        </form>


        <!-- التقييمات الموجودة -->

        <div class="reviews-list">


            <?php

            $commentsResult = mysqli_query(
                $con,
                "
                SELECT usename, comment
                FROM comments
                ORDER BY id DESC
                LIMIT 10
                "
            );


            if(
                $commentsResult
                &&
                mysqli_num_rows($commentsResult) > 0
            ){

                while(
                    $comment = mysqli_fetch_assoc($commentsResult)
                ){

            ?>


                <div class="review">


                    <div class="review-user">

                        👤
                        <?= htmlspecialchars($comment['usename'] ?? 'عميل') ?>

                    </div>


                    <div class="review-text">

                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>

                    </div>


                </div>


            <?php

                }

            }else{

            ?>

                <p style="color:#777;">

                    لا توجد تقييمات حتى الآن.

                </p>

            <?php } ?>


        </div>


    </div>


</div>


<script>

/* =====================================================
   التحكم بالكمية
===================================================== */

const quantityInput = document.getElementById('quantity');

const minusButton = document.getElementById('qtyMinus');

const plusButton = document.getElementById('qtyPlus');


minusButton.addEventListener('click', function(){

    let value = parseInt(quantityInput.value) || 1;

    if(value > 1){

        quantityInput.value = value - 1;

    }

});


plusButton.addEventListener('click', function(){

    let value = parseInt(quantityInput.value) || 1;

    if(value < 99){

        quantityInput.value = value + 1;

    }

});

</script>


<?php include('file/foter.php'); ?>