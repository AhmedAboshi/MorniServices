<?php

include('file/header.php');

/* =========================================================
   بيانات القسم
========================================================= */

$section = trim($_GET['section'] ?? '');
$search  = trim($_GET['search'] ?? '');

$sectionTitle = $section !== ''
    ? $section
    : 'المنتجات';

/* =========================================================
   جلب المنتجات
========================================================= */

$sql = "
    SELECT
        id,
        proname,
        proprice,
        proimg,
        prosection
    FROM product
    WHERE prosection = ?
";

$params = [$section];
$types  = "s";

if ($search !== '') {

    $sql .= "
        AND proname LIKE ?
    ";

    $params[] = '%' . $search . '%';
    $types .= "s";
}

$sql .= "
    ORDER BY id DESC
";

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result = $stmt->get_result();

$productCount = $result->num_rows;

?>

<style>

/* =========================================================
   AL SHARQ - PRODUCTS PAGE
   CSS معزول عن بقية النظام
========================================================= */

.as-products-page,
.as-products-page * {
    box-sizing: border-box;
}

/* =========================================================
   الصفحة الرئيسية
========================================================= */

.as-products-page {

    width: 100% !important;

    max-width: 1450px !important;

    margin: 0 auto !important;

    padding: 25px !important;

    display: block !important;

    position: relative;

    direction: rtl;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    color: #1f2937;

    min-width: 0 !important;

    flex: 1 1 auto !important;
}

/* منع أي تنسيق من الأب من ضغط الصفحة */

.as-products-page::after {
    content: "";
    display: table;
    clear: both;
}

/* =========================================================
   HEADER
========================================================= */

.as-products-hero {

    width: 100% !important;

    min-height: 250px;

    background:
        linear-gradient(
            135deg,
            #0878f9 0%,
            #0b5ed7 50%,
            #084298 100%
        );

    border-radius: 24px;

    padding: 30px;

    margin-bottom: 25px;

    color: #fff;

    box-shadow:
        0 12px 35px rgba(13, 110, 253, .18);

    display: block !important;

    position: relative;

    overflow: hidden;
}

.as-products-hero::before {

    content: "";

    position: absolute;

    width: 240px;
    height: 240px;

    border-radius: 50%;

    background: rgba(255,255,255,.08);

    left: -80px;
    top: -80px;
}

.as-products-hero-content {

    position: relative;

    z-index: 2;

    display: flex !important;

    justify-content: space-between !important;

    align-items: flex-start !important;

    gap: 25px;

    flex-wrap: wrap !important;

    width: 100% !important;
}

.as-products-title-area {

    flex: 1 1 350px !important;

    min-width: 0 !important;
}

.as-products-title {

    margin: 0 !important;

    font-size: 32px !important;

    line-height: 1.5 !important;

    font-weight: 800 !important;

    color: #fff !important;
}

.as-products-subtitle {

    margin: 8px 0 0 !important;

    font-size: 14px !important;

    line-height: 1.8 !important;

    color: rgba(255,255,255,.9) !important;
}

/* =========================================================
   زر الرئيسية
========================================================= */

.as-home-btn {

    display: inline-flex !important;

    align-items: center !important;

    justify-content: center !important;

    gap: 7px;

    min-width: 145px;

    padding: 13px 22px !important;

    background: #fff !important;

    color: #0d6efd !important;

    border-radius: 13px !important;

    text-decoration: none !important;

    font-size: 14px !important;

    font-weight: 800 !important;

    border: none !important;

    transition: .2s ease;

    box-shadow:
        0 5px 15px rgba(0,0,0,.12);
}

.as-home-btn:hover {

    transform: translateY(-2px);

    background: #f8f9fa !important;

    color: #084298 !important;
}

/* =========================================================
   البحث
========================================================= */

.as-search-wrapper {

    position: relative;

    z-index: 3;

    margin-top: 25px;

    width: 100% !important;

    background: rgba(255,255,255,.12);

    padding: 12px;

    border-radius: 16px;

    backdrop-filter: blur(5px);
}

.as-search-form {

    width: 100% !important;

    display: flex !important;

    align-items: stretch !important;

    gap: 10px;

    flex-wrap: nowrap !important;
}

.as-search-input {

    flex: 1 1 auto !important;

    width: auto !important;

    min-width: 0 !important;

    height: 50px !important;

    padding: 0 18px !important;

    border: none !important;

    border-radius: 12px !important;

    outline: none !important;

    background: #fff !important;

    color: #333 !important;

    font-family: inherit !important;

    font-size: 14px !important;
}

.as-search-input::placeholder {

    color: #777;
}

.as-search-btn {

    flex: 0 0 100px !important;

    width: 100px !important;

    height: 50px !important;

    border: none !important;

    border-radius: 12px !important;

    background: #198754 !important;

    color: #fff !important;

    font-weight: 800 !important;

    cursor: pointer !important;

    font-family: inherit !important;

    transition: .2s ease;
}

.as-search-btn:hover {

    background: #157347 !important;
}

.as-reset-btn {

    flex: 0 0 90px !important;

    width: 90px !important;

    height: 50px !important;

    display: inline-flex !important;

    align-items: center !important;

    justify-content: center !important;

    border-radius: 12px !important;

    background: #fff !important;

    color: #0d6efd !important;

    text-decoration: none !important;

    font-weight: 800 !important;
}

/* =========================================================
   معلومات النتائج
========================================================= */

.as-products-info {

    width: 100% !important;

    display: flex !important;

    justify-content: space-between !important;

    align-items: center !important;

    gap: 12px;

    flex-wrap: wrap !important;

    margin-bottom: 20px !important;
}

.as-info-box {

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 11px 17px;

    font-size: 13px;

    font-weight: 700;

    box-shadow:
        0 3px 12px rgba(0,0,0,.04);
}

.as-info-number {

    color: #0d6efd;

    font-size: 16px;
}

/* =========================================================
   GRID
========================================================= */

.as-products-grid {

    width: 100% !important;

    display: grid !important;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        ) !important;

    gap: 22px !important;

    align-items: stretch !important;

    margin: 0 !important;

    padding: 0 !important;
}

/* =========================================================
   CARD
========================================================= */

.as-product-card {

    width: 100% !important;

    min-width: 0 !important;

    background: #fff !important;

    border: 1px solid #e5e7eb !important;

    border-radius: 20px !important;

    overflow: hidden !important;

    display: flex !important;

    flex-direction: column !important;

    box-shadow:
        0 5px 20px rgba(0,0,0,.06);

    transition:
        transform .25s ease,
        box-shadow .25s ease;

    position: relative;
}

.as-product-card:hover {

    transform: translateY(-5px);

    box-shadow:
        0 12px 30px rgba(0,0,0,.11);
}

/* =========================================================
   صورة المنتج
========================================================= */

.as-product-image {

    width: 100% !important;

    height: 250px !important;

    background:
        linear-gradient(
            180deg,
            #f8fafc,
            #eef2f7
        );

    display: flex !important;

    align-items: center !important;

    justify-content: center !important;

    overflow: hidden !important;

    position: relative;
}

.as-product-image img {

    width: 100% !important;

    height: 100% !important;

    object-fit: contain !important;

    padding: 15px !important;

    display: block !important;

    transition: transform .3s ease;
}

.as-product-card:hover
.as-product-image img {

    transform: scale(1.05);
}

/* =========================================================
   حالة عدم وجود صورة
========================================================= */

.as-no-image {

    color: #9ca3af;

    font-size: 14px;

    text-align: center;
}

/* =========================================================
   محتوى البطاقة
========================================================= */

.as-product-body {

    padding: 18px !important;

    display: flex !important;

    flex-direction: column !important;

    flex: 1 1 auto !important;

    min-width: 0 !important;
}

.as-product-name {

    font-size: 18px !important;

    font-weight: 800 !important;

    color: #111827 !important;

    line-height: 1.6 !important;

    margin: 0 0 8px !important;

    min-height: 58px;
}

.as-product-section {

    color: #6b7280;

    font-size: 12px;

    margin-bottom: 12px;

    line-height: 1.6;
}

/* =========================================================
   السعر
========================================================= */

.as-product-price {

    margin-top: auto;

    margin-bottom: 15px;

    color: #198754;

    font-size: 23px;

    font-weight: 900;
}

.as-product-currency {

    color: #6b7280;

    font-size: 12px;

    font-weight: 600;
}

/* =========================================================
   زر السلة
========================================================= */

.as-cart-form {

    width: 100% !important;

    margin: 0 !important;
}

.as-add-cart {

    width: 100% !important;

    height: 48px !important;

    border: none !important;

    border-radius: 12px !important;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #0b5ed7
        ) !important;

    color: #fff !important;

    font-family: inherit !important;

    font-size: 14px !important;

    font-weight: 800 !important;

    cursor: pointer !important;

    transition: .2s ease;
}

.as-add-cart:hover {

    background:
        linear-gradient(
            135deg,
            #084298,
            #0d6efd
        ) !important;

    transform: translateY(-1px);
}

/* =========================================================
   EMPTY
========================================================= */

.as-empty {

    width: 100% !important;

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 20px;

    padding: 70px 20px;

    text-align: center;

    box-shadow:
        0 5px 20px rgba(0,0,0,.05);
}

.as-empty-icon {

    font-size: 55px;

    margin-bottom: 15px;
}

.as-empty-title {

    font-size: 21px;

    font-weight: 800;

    margin-bottom: 8px;
}

.as-empty-text {

    color: #6b7280;

    font-size: 14px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .as-products-grid {

        grid-template-columns:
            repeat(
                3,
                minmax(
                    0,
                    1fr
                )
            ) !important;
    }
}

@media (max-width: 850px) {

    .as-products-page {

        padding: 15px !important;
    }

    .as-products-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            ) !important;
    }

    .as-products-title {

        font-size: 26px !important;
    }
}

@media (max-width: 600px) {

    .as-products-hero {

        padding: 20px !important;

        border-radius: 18px !important;
    }

    .as-products-hero-content {

        display: block !important;
    }

    .as-home-btn {

        margin-top: 15px;

        width: 100% !important;
    }

    .as-search-form {

        flex-wrap: wrap !important;
    }

    .as-search-input {

        flex: 1 1 100% !important;

        width: 100% !important;
    }

    .as-search-btn {

        flex: 1 1 calc(50% - 5px) !important;

        width: auto !important;
    }

    .as-reset-btn {

        flex: 1 1 calc(50% - 5px) !important;

        width: auto !important;
    }

    .as-products-grid {

        grid-template-columns:
            1fr !important;

        gap: 15px !important;
    }

    .as-product-image {

        height: 240px !important;
    }
}

</style>


<!-- =========================================================
     PAGE
========================================================= -->

<div class="as-products-page">

    <!-- =====================================================
         HEADER
    ===================================================== -->

    <section class="as-products-hero">

        <div class="as-products-hero-content">

            <div class="as-products-title-area">

                <h1 class="as-products-title">

                    🛍️
                    <?= htmlspecialchars(
                        $sectionTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </h1>

                <p class="as-products-subtitle">

                    اكتشف المنتجات والخدمات المتاحة داخل هذا القسم

                </p>

            </div>

            <div>

                <a
                    href="index.php"
                    class="as-home-btn"
                >
                    🏠 الرئيسية
                </a>

            </div>

        </div>


        <!-- =================================================
             SEARCH
        ================================================= -->

        <div class="as-search-wrapper">

            <form
                method="GET"
                class="as-search-form"
            >

                <input
                    type="hidden"
                    name="section"
                    value="<?= htmlspecialchars(
                        $section,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="text"
                    name="search"
                    class="as-search-input"
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="🔍 ابحث عن منتج داخل القسم..."
                >

                <button
                    type="submit"
                    class="as-search-btn"
                >
                    بحث
                </button>

                <?php if ($search !== ''): ?>

                    <a
                        href="?<?= http_build_query([
                            'section' => $section
                        ]) ?>"
                        class="as-reset-btn"
                    >
                        إعادة
                    </a>

                <?php endif; ?>

            </form>

        </div>

    </section>


    <!-- =====================================================
         INFO
    ===================================================== -->

    <div class="as-products-info">

        <div class="as-info-box">

            عدد المنتجات:

            <span class="as-info-number">

                <?= number_format(
                    $productCount
                ) ?>

            </span>

        </div>

        <?php if ($search !== ''): ?>

            <div class="as-info-box">

                نتائج البحث عن:

                <strong>
                    <?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================================
         PRODUCTS
    ===================================================== -->

    <?php if ($productCount > 0): ?>

        <div class="as-products-grid">

            <?php while (
                $row = $result->fetch_assoc()
            ): ?>

                <?php

                $productId =
                    (int)$row['id'];

                $productName =
                    trim(
                        $row['proname'] ?? ''
                    );

                if ($productName === '') {
                    $productName = 'منتج';
                }

                $productPrice =
                    (float)(
                        $row['proprice'] ?? 0
                    );

                $productImage =
                    trim(
                        (string)(
                            $row['proimg'] ?? ''
                        )
                    );

                $productImage =
                    basename(
                        $productImage
                    );

                $productSection =
                    $row['prosection']
                    ?? $section;

                ?>

                <article class="as-product-card">

                    <!-- الصورة -->

                    <div class="as-product-image">

                        <?php if (
                            $productImage !== ''
                        ): ?>

                            <img
                                src="uploads/img/<?= htmlspecialchars(
                                    $productImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $productName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                loading="lazy"
                                onerror="
                                    this.style.display='none';
                                    this.parentElement.innerHTML='<span class=&quot;as-no-image&quot;>لا توجد صورة</span>';
                                "
                            >

                        <?php else: ?>

                            <span class="as-no-image">
                                📷
                                <br>
                                لا توجد صورة
                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- محتوى المنتج -->

                    <div class="as-product-body">

                        <h2 class="as-product-name">

                            <?= htmlspecialchars(
                                $productName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h2>

                        <div class="as-product-section">

                            📁 القسم:

                            <?= htmlspecialchars(
                                $productSection,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <div class="as-product-price">

                            <?= number_format(
                                $productPrice,
                                2
                            ) ?>

                            <span class="as-product-currency">
                                ريال
                            </span>

                        </div>


                        <!-- السلة -->

                        <form
                            action="cart.php"
                            method="POST"
                            class="as-cart-form"
                        >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= $productId ?>"
                            >

                            <input
                                type="hidden"
                                name="name"
                                value="<?= htmlspecialchars(
                                    $productName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="price"
                                value="<?= htmlspecialchars(
                                    $productPrice,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="img"
                                value="<?= htmlspecialchars(
                                    $productImage,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <button
                                type="submit"
                                name="add"
                                class="as-add-cart"
                            >

                                🛒
                                إضافة للسلة

                            </button>

                        </form>

                    </div>

                </article>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="as-empty">

            <div class="as-empty-icon">
                🛒
            </div>

            <div class="as-empty-title">

                لا توجد منتجات

            </div>

            <div class="as-empty-text">

                <?php if ($search !== ''): ?>

                    لا توجد منتجات تطابق كلمة البحث
                    «<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>».

                <?php else: ?>

                    لا توجد منتجات في هذا القسم حالياً.

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

</div>

