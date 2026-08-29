<?php

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

if (isset($con)) {
    mysqli_set_charset($con, 'utf8mb4');
}

/* =========================================================
   إعدادات الشركة
========================================================= */

$settings = [];

$settingsFile = __DIR__ . '/../include/settings.php';

if (file_exists($settingsFile)) {
    require_once $settingsFile;
}

/* الشعار */
$logo = trim($settings['company_logo'] ?? '');

/* اسم الشركة */
$companyName = trim(
    $settings['company_name']
    ?? $settings['system_name']
    ?? 'منصة الشرق'
);

/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$_SESSION['lang'] = $lang;

$isArabic = ($lang === 'ar');


/* =========================================================
   PAGE SETTINGS
========================================================= */

/*
|--------------------------------------------------------------------------
| صفحة المنتجات
|--------------------------------------------------------------------------
| غيّر هذا الاسم فقط إذا كانت صفحة المتجر الرئيسية عندك باسم مختلف.
*/

$productsPage = 'index.php';


/*
|--------------------------------------------------------------------------
| صفحة السطحة
|--------------------------------------------------------------------------
*/

$towPage = 'tow_order.php';


/*
|--------------------------------------------------------------------------
| صفحة تسجيل الدخول
|--------------------------------------------------------------------------
*/

$loginPage = 'user/login.php';


/* =========================================================
   COMPANY SETTINGS
========================================================= */

$settings = [];

$settingsFile = __DIR__ . '/include/settings.php';

if (file_exists($settingsFile)) {

    /*
     * settings.php قد يقوم بتحميل $settings مباشرة
     */

    include $settingsFile;
}


$companyName =
    trim(
        $settings['company_name']
        ?? $settings['system_name']
        ?? 'منصة الشرق'
    );


$companyLogo =
    trim(
        $settings['company_logo']
        ?? ''
    );


$companyPhone =
    trim(
        $settings['company_phone']
        ?? ''
    );


$currency =
    trim(
        $settings['currency']
        ?? 'ر.س'
    );

/* =========================================================
   معالجة مسار الشعار
========================================================= */

$logoPath = '';

if ($logo !== '') {

    /*
       إذا كان الشعار يحتوي على http
    */
    if (
        str_starts_with($logo, 'http://') ||
        str_starts_with($logo, 'https://')
    ) {

        $logoPath = $logo;

    } else {

        /*
           تنظيف المسار
        */
        $logo = ltrim($logo, '/');

        /*
           إذا كان المسار يبدأ بـ uploads
        */
        if (file_exists(__DIR__ . '/' . $logo)) {

            $logoPath = $logo;

        } elseif (
            file_exists(
                __DIR__ . '/uploads/' . basename($logo)
            )
        ) {

            $logoPath =
                'uploads/' . basename($logo);

        } elseif (
            file_exists(
                __DIR__ . '/admin/' . $logo
            )
        ) {

            $logoPath =
                'admin/' . $logo;

        } else {

            $logoPath = $logo;
        }
    }
}

/* =========================================================
   CART COUNT
========================================================= */

$cartCount = 0;


/*
 * نحاول قراءة أكثر من شكل محتمل للسلة
 * حتى لا تتسبب الصفحة في خطأ إذا كان نظام السلة الحالي
 * يستخدم session مختلفة.
 */

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        if (is_array($item)) {

            $quantity =
                (int)(
                    $item['quantity']
                    ?? $item['qty']
                    ?? 1
                );

            $cartCount += max(1, $quantity);

        } else {

            $cartCount++;
        }
    }
}


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn = false;

$customerName = '';

if (
    !empty($_SESSION['user_id']) ||
    !empty($_SESSION['customer_id']) ||
    !empty($_SESSION['user'])
) {

    $isLoggedIn = true;

    $customerName =
        trim(
            $_SESSION['username']
            ?? $_SESSION['user_name']
            ?? $_SESSION['name']
            ?? ''
        );
}


/* =========================================================
   TEXTS
========================================================= */

if ($isArabic) {

    $pageTitle =
        'اطلب خدمتك الآن';

    $pageSubtitle =
        'اختر نوع الخدمة التي تحتاجها وسنساعدك في إتمام طلبك بسهولة';

    $productsTitle =
        'طلب منتجات';

    $productsDescription =
        'تصفح الأقسام والمنتجات، أضف ما تحتاجه إلى السلة وأكمل طلبك بسهولة.';

    $productsButton =
        'تصفح المنتجات';

    $productsBadge =
        'المتجر';

    $productsIcon =
        '🛒';


    $towTitle =
        'سطحة بين المدن';

    $towDescription =
        'اطلب نقل مركبتك من مدينة إلى أخرى بسهولة، واختر بيانات الرحلة المناسبة لك.';

    $towButton =
        'طلب سطحة الآن';

    $towBadge =
        'نقل المركبات';

    $towIcon =
        '🚚';


    $ordersTitle =
        'طلباتي';

    $ordersDescription =
        'تابع حالة طلباتك السابقة والحالية من مكان واحد.';

    $ordersButton =
        'عرض طلباتي';


    $loginTitle =
        'تسجيل الدخول';

    $loginDescription =
        'سجّل الدخول للوصول إلى طلباتك ومتابعة حالتها.';

    $loginButton =
        'تسجيل الدخول';


    $cartTitle =
        'السلة';

    $cartDescription =
        'لديك منتجات في السلة. يمكنك مراجعتها قبل إكمال الطلب.';

    $cartButton =
        'عرض السلة';


    $welcomeUser =
        $customerName !== ''
            ? 'مرحبًا، ' . $customerName
            : 'مرحبًا بك';


    $chooseText =
        'كيف يمكننا خدمتك اليوم؟';


    $backText =
        'العودة للرئيسية';


    $languageText =
        'English';

} else {

    $pageTitle =
        'Request Your Service';

    $pageSubtitle =
        'Choose the service you need and complete your request easily.';

    $productsTitle =
        'Order Products';

    $productsDescription =
        'Browse categories and products, add what you need to your cart and complete your order easily.';

    $productsButton =
        'Browse Products';

    $productsBadge =
        'Store';

    $productsIcon =
        '🛒';


    $towTitle =
        'Intercity Tow Service';

    $towDescription =
        'Request vehicle transportation from one city to another with an easy booking process.';

    $towButton =
        'Request Tow Now';

    $towBadge =
        'Vehicle Transport';

    $towIcon =
        '🚚';


    $ordersTitle =
        'My Orders';

    $ordersDescription =
        'Track your current and previous orders from one place.';

    $ordersButton =
        'View My Orders';


    $loginTitle =
        'Login';

    $loginDescription =
        'Login to access your orders and track their status.';

    $loginButton =
        'Login';


    $cartTitle =
        'Cart';

    $cartDescription =
        'You have products in your cart. Review them before completing your order.';

    $cartButton =
        'View Cart';


    $welcomeUser =
        $customerName !== ''
            ? 'Welcome, ' . $customerName
            : 'Welcome';


    $chooseText =
        'How can we serve you today?';


    $backText =
        'Back to Home';


    $languageText =
        'العربية';
}


/* =========================================================
   SAFE LANGUAGE URL
========================================================= */

$currentUrl =
    strtok(
        $_SERVER['REQUEST_URI'] ?? 'request_service.php',
        '?'
    );


$langUrl =
    htmlspecialchars(
        $currentUrl .
        '?lang=' .
        ($isArabic ? 'en' : 'ar'),
        ENT_QUOTES,
        'UTF-8'
    );


/* =========================================================
   URLS
========================================================= */

$productsUrl =
    $productsPage .
    '?lang=' .
    urlencode($lang);


$towUrl =
    $towPage .
    '?lang=' .
    urlencode($lang);


$loginUrl =
    $loginPage .
    '?lang=' .
    urlencode($lang);


/*
 * صفحة الطلبات
 * نحاول استخدام orders.php كصفحة الطلبات.
 * يمكن تغييرها لاحقًا بسهولة.
 */

$ordersPage = 'orders.php';

$ordersUrl =
    $ordersPage .
    '?lang=' .
    urlencode($lang);


/*
 * صفحة السلة.
 * إذا كانت عندك cart.php استخدمها.
 */

$cartPage = 'cart.php';

$cartUrl =
    $cartPage .
    '?lang=' .
    urlencode($lang);

?>

<!DOCTYPE html>

<html
    lang="<?= $isArabic ? 'ar' : 'en' ?>"
    dir="<?= $isArabic ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="description"
    content="<?= htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8') ?>"
>

<title>
<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
 -
<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>
</title>


<!-- Bootstrap -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
>


<!-- Google Font -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    font-family: 'Cairo', sans-serif;

    background:
        radial-gradient(
            circle at top right,
            rgba(13,110,253,.10),
            transparent 32%
        ),
        radial-gradient(
            circle at bottom left,
            rgba(13,110,253,.07),
            transparent 30%
        ),
        #f6f8fc;

    color: #172033;

    min-height: 100vh;
}


/* =========================================================
   NAVBAR
========================================================= */

.top-navbar {

    background: rgba(255,255,255,.92);

    backdrop-filter: blur(15px);

    border-bottom: 1px solid rgba(0,0,0,.06);

    position: sticky;

    top: 0;

    z-index: 1000;
}


.navbar-inner {

    max-width: 1250px;

    margin: auto;

    padding: 14px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    text-decoration: none;

    color: #172033;
}


.brand-logo {

    width: 48px;

    height: 48px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #1646b8
        );

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    box-shadow:
        0 8px 20px rgba(13,110,253,.20);
}


.brand-logo img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    padding: 6px;
}


.brand-logo.no-image {

    color: white;

    font-size: 22px;

}


.brand-name {

    font-size: 19px;

    font-weight: 900;

    line-height: 1.2;
}


.brand-sub {

    color: #7b8495;

    font-size: 11px;

    margin-top: 2px;
}


.nav-actions {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;
}


.nav-btn {

    height: 42px;

    padding: 0 15px;

    border-radius: 11px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    text-decoration: none;

    font-weight: 700;

    transition: .25s;

    border: 1px solid #e3e7ef;

    background: white;

    color: #344054;
}


.nav-btn:hover {

    transform: translateY(-2px);

    border-color: #0d6efd;

    color: #0d6efd;
}


.nav-btn.primary {

    background: #0d6efd;

    border-color: #0d6efd;

    color: white;

    box-shadow:
        0 7px 18px rgba(13,110,253,.20);
}


.nav-btn.primary:hover {

    color: white;

    background: #0b5ed7;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    max-width: 1250px;

    margin: auto;

    padding:
        65px
        20px
        35px;

    text-align: center;
}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 8px 16px;

    border-radius: 50px;

    background: rgba(13,110,253,.09);

    color: #0d6efd;

    font-weight: 800;

    font-size: 13px;

    margin-bottom: 18px;
}


.hero h1 {

    margin: 0;

    font-size: clamp(32px, 5vw, 54px);

    font-weight: 900;

    line-height: 1.25;

    color: #172033;
}


.hero h1 span {

    color: #0d6efd;
}


.hero p {

    max-width: 720px;

    margin:
        18px
        auto
        0;

    font-size: 17px;

    line-height: 2;

    color: #667085;
}


.user-welcome {

    margin-top: 15px;

    color: #0d6efd;

    font-weight: 800;

    font-size: 15px;
}


/* =========================================================
   SERVICE SECTION
========================================================= */

.services-wrapper {

    max-width: 1100px;

    margin: auto;

    padding:
        10px
        20px
        50px;
}


.choose-title {

    text-align: center;

    font-size: 23px;

    font-weight: 900;

    margin-bottom: 28px;

    color: #172033;
}


.services-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 25px;
}


/* =========================================================
   SERVICE CARD
========================================================= */

.service-card {

    position: relative;

    background: white;

    border-radius: 26px;

    padding: 35px;

    min-height: 390px;

    overflow: hidden;

    border: 1px solid #edf0f5;

    box-shadow:
        0 15px 45px rgba(20,35,60,.07);

    transition:
        transform .3s,
        box-shadow .3s,
        border-color .3s;

    display: flex;

    flex-direction: column;
}


.service-card:hover {

    transform: translateY(-7px);

    box-shadow:
        0 25px 60px rgba(20,35,60,.13);

    border-color:
        rgba(13,110,253,.20);
}


.service-card::before {

    content: '';

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background:
        rgba(13,110,253,.055);

    top: -70px;

    right: -60px;
}


.service-card.tow::before {

    background:
        rgba(16,185,129,.07);
}


.service-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    position: relative;

    z-index: 1;
}


.service-icon {

    width: 76px;

    height: 76px;

    border-radius: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 37px;

    background:
        linear-gradient(
            135deg,
            #e8f1ff,
            #dbeafe
        );
}


.service-card.tow
.service-icon {

    background:
        linear-gradient(
            135deg,
            #e7faf3,
            #d1fae5
        );
}


.service-badge {

    padding: 7px 12px;

    border-radius: 30px;

    background: #f1f5f9;

    color: #64748b;

    font-size: 11px;

    font-weight: 800;
}


.service-content {

    position: relative;

    z-index: 1;

    flex: 1;
}


.service-content h2 {

    margin:
        28px
        0
        12px;

    font-size: 26px;

    font-weight: 900;
}


.service-content p {

    color: #667085;

    line-height: 2;

    font-size: 14px;

    margin: 0;

    max-width: 450px;
}


.service-features {

    margin:
        20px
        0
        25px;

    padding: 0;

    list-style: none;

    display: flex;

    flex-direction: column;

    gap: 9px;
}


.service-features li {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #475467;

    font-size: 13px;

    font-weight: 600;
}


.service-features i {

    color: #10b981;

    font-size: 13px;
}


.service-btn {

    width: 100%;

    min-height: 50px;

    border-radius: 13px;

    border: none;

    text-decoration: none;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #2563eb
        );

    color: white;

    font-weight: 800;

    transition: .25s;

    position: relative;

    z-index: 1;
}


.service-btn:hover {

    color: white;

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(13,110,253,.25);
}


.service-btn.green {

    background:
        linear-gradient(
            135deg,
            #10b981,
            #059669
        );
}


.service-btn.green:hover {

    box-shadow:
        0 10px 25px rgba(16,185,129,.25);
}


/* =========================================================
   EXTRA ACTIONS
========================================================= */

.extra-actions {

    max-width: 1100px;

    margin:
        0
        auto
        35px;

    padding:
        0
        20px;
}


.extra-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 15px;
}


.extra-card {

    background: white;

    border:
        1px solid #edf0f5;

    border-radius: 17px;

    padding: 18px;

    text-decoration: none;

    color: #172033;

    display: flex;

    align-items: center;

    gap: 13px;

    transition: .25s;

    box-shadow:
        0 8px 25px rgba(20,35,60,.04);
}


.extra-card:hover {

    transform: translateY(-3px);

    color: #0d6efd;

    border-color:
        rgba(13,110,253,.20);
}


.extra-icon {

    width: 45px;

    height: 45px;

    border-radius: 13px;

    background: #f1f5f9;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    flex-shrink: 0;
}


.extra-info {

    flex: 1;
}


.extra-info strong {

    display: block;

    font-size: 14px;

    font-weight: 800;
}


.extra-info small {

    display: block;

    color: #98a2b3;

    font-size: 11px;

    margin-top: 2px;
}


.cart-count {

    min-width: 25px;

    height: 25px;

    padding: 0 7px;

    border-radius: 20px;

    background: #ef4444;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 11px;

    font-weight: 900;
}


/* =========================================================
   INFO STRIP
========================================================= */

.info-strip {

    max-width: 1100px;

    margin:
        0
        auto
        55px;

    padding:
        0
        20px;
}


.info-box {

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #172033,
            #243453
        );

    color: white;

    padding: 25px 30px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.info-box h3 {

    font-size: 18px;

    font-weight: 900;

    margin: 0 0 6px;
}


.info-box p {

    margin: 0;

    color: rgba(255,255,255,.70);

    font-size: 13px;
}


.info-box-icon {

    width: 55px;

    height: 55px;

    border-radius: 16px;

    background:
        rgba(255,255,255,.10);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    flex-shrink: 0;
}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    background: #fff;

    border-top:
        1px solid #edf0f5;

    padding:
        25px
        20px;

    text-align: center;

    color: #98a2b3;

    font-size: 12px;
}


.footer strong {

    color: #344054;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px) {

    .services-grid {

        grid-template-columns: 1fr;
    }

    .extra-grid {

        grid-template-columns:
            1fr;
    }

    .service-card {

        min-height: auto;
    }
}


@media(max-width:600px) {

    .navbar-inner {

        padding:
            11px
            13px;
    }

    .brand-sub {

        display: none;
    }

    .brand-name {

        font-size: 16px;
    }

    .brand-logo {

        width: 42px;

        height: 42px;
    }

    .nav-btn {

        width: 42px;

        padding: 0;

        font-size: 0;
    }

    .nav-btn i {

        font-size: 16px;
    }

    .nav-btn.primary {

        width: auto;

        padding:
            0
            13px;

        font-size: 12px;
    }

    .hero {

        padding:
            45px
            15px
            25px;
    }

    .hero h1 {

        font-size: 32px;
    }

    .hero p {

        font-size: 14px;
    }

    .services-wrapper {

        padding:
            5px
            15px
            30px;
    }

    .service-card {

        padding: 25px;

        border-radius: 22px;
    }

    .service-content h2 {

        font-size: 23px;
    }

    .info-strip {

        padding:
            0
            15px;
    }

    .info-box {

        padding: 20px;

        align-items: flex-start;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="top-navbar">

<div class="navbar-inner">


<a
    href="welcome.php?lang=<?= urlencode($lang) ?>"
    class="brand"
>

<!-- <div
    class="brand-logo <?= $companyLogo === '' ? 'no-image' : '' ?>"
>

<?php if ($companyLogo !== ''): ?>

<img
    src="<?= htmlspecialchars($companyLogo, ENT_QUOTES, 'UTF-8') ?>"
    alt="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
> -->

<?php else: ?>

<i class="fa-solid fa-truck-fast"></i>

<?php endif; ?>

</div>


<div>

<div class="brand-name">

<?= htmlspecialchars(
    $companyName,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

<div class="brand-sub">

<?= $isArabic
    ? 'الخدمات والنقل الذكي'
    : 'Smart Services & Transport'
?>

</div>

</div>

</a>


<div class="nav-actions">


<?php if ($isLoggedIn): ?>

<a
    href="<?= htmlspecialchars($ordersUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="nav-btn"
    title="<?= htmlspecialchars($ordersTitle, ENT_QUOTES, 'UTF-8') ?>"
>

<i class="fa-solid fa-box"></i>

<span>
<?= htmlspecialchars($ordersTitle, ENT_QUOTES, 'UTF-8') ?>
</span>

</a>

<?php else: ?>

<a
    href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="nav-btn primary"
>

<i class="fa-solid fa-right-to-bracket"></i>

<?= htmlspecialchars($loginButton, ENT_QUOTES, 'UTF-8') ?>

</a>

<?php endif; ?>


<a
    href="<?= $langUrl ?>"
    class="nav-btn"
    title="<?= htmlspecialchars($languageText, ENT_QUOTES, 'UTF-8') ?>"
>

<i class="fa-solid fa-language"></i>

<span>
<?= htmlspecialchars($languageText, ENT_QUOTES, 'UTF-8') ?>
</span>

</a>


</div>

</div>

</header>


<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">


<div class="hero-badge">

<i class="fa-solid fa-sparkles"></i>

<?= $isArabic
    ? 'خدماتك أصبحت أسهل'
    : 'Your service made easier'
?>

</div>


<h1>

<?= htmlspecialchars(
    $pageTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

<br>

<span>

<?= $isArabic
    ? 'اختر الخدمة المناسبة لك'
    : 'Choose the service you need'
?>

</span>

</h1>


<p>

<?= htmlspecialchars(
    $pageSubtitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</p>


<?php if ($isLoggedIn): ?>

<div class="user-welcome">

<i class="fa-solid fa-circle-check"></i>

<?= htmlspecialchars(
    $welcomeUser,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

<?php endif; ?>


</section>


<!-- =========================================================
     SERVICES
========================================================= -->

<section class="services-wrapper">


<div class="choose-title">

<?= htmlspecialchars(
    $chooseText,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>


<div class="services-grid">


<!-- =====================================================
     PRODUCTS
===================================================== -->

<article class="service-card">


<div class="service-top">


<div class="service-icon">

<?= $productsIcon ?>

</div>


<div class="service-badge">

<?= htmlspecialchars(
    $productsBadge,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

</div>


<div class="service-content">


<h2>

<?= htmlspecialchars(
    $productsTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</h2>


<p>

<?= htmlspecialchars(
    $productsDescription,
    ENT_QUOTES,
    'UTF-8'
) ?>

</p>


<ul class="service-features">


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'تصفح الأقسام'
    : 'Browse categories'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'اختيار المنتجات'
    : 'Choose products'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'إضافة المنتجات إلى السلة'
    : 'Add products to cart'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'تأكيد الطلب بسهولة'
    : 'Complete your order'
?>

</li>


</ul>

</div>


<a
    href="<?= htmlspecialchars($productsUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="service-btn"
>

<i class="fa-solid fa-store"></i>

<?= htmlspecialchars(
    $productsButton,
    ENT_QUOTES,
    'UTF-8'
) ?>

<i class="fa-solid fa-arrow-left-long"></i>

</a>


</article>


<!-- =====================================================
     TOW
===================================================== -->

<article class="service-card tow">


<div class="service-top">


<div class="service-icon">

<?= $towIcon ?>

</div>


<div class="service-badge">

<?= htmlspecialchars(
    $towBadge,
    ENT_QUOTES,
    'UTF-8'
) ?>

</div>

</div>


<div class="service-content">


<h2>

<?= htmlspecialchars(
    $towTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</h2>


<p>

<?= htmlspecialchars(
    $towDescription,
    ENT_QUOTES,
    'UTF-8'
) ?>

</p>


<ul class="service-features">


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'من مدينة إلى مدينة'
    : 'City to city transportation'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'اختيار بيانات الرحلة'
    : 'Choose trip details'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'طلب فوري أو مجدول'
    : 'Instant or scheduled request'
?>

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

<?= $isArabic
    ? 'متابعة حالة الطلب'
    : 'Track your request'
?>

</li>


</ul>

</div>


<a
    href="<?= htmlspecialchars($towUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="service-btn green"
>

<i class="fa-solid fa-truck-fast"></i>

<?= htmlspecialchars(
    $towButton,
    ENT_QUOTES,
    'UTF-8'
) ?>

<i class="fa-solid fa-arrow-left-long"></i>

</a>


</article>


</div>

</section>


<!-- =========================================================
     EXTRA ACTIONS
========================================================= -->

<section class="extra-actions">


<div class="extra-grid">


<?php if ($isLoggedIn): ?>


<a
    href="<?= htmlspecialchars($ordersUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="extra-card"
>


<div class="extra-icon">

<i class="fa-solid fa-box-open"></i>

</div>


<div class="extra-info">

<strong>

<?= htmlspecialchars(
    $ordersTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</strong>

<small>

<?= htmlspecialchars(
    $ordersDescription,
    ENT_QUOTES,
    'UTF-8'
) ?>

</small>

</div>


<i class="fa-solid fa-chevron-left"></i>

</a>


<?php else: ?>


<a
    href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="extra-card"
>


<div class="extra-icon">

<i class="fa-solid fa-user"></i>

</div>


<div class="extra-info">

<strong>

<?= htmlspecialchars(
    $loginTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</strong>

<small>

<?= htmlspecialchars(
    $loginDescription,
    ENT_QUOTES,
    'UTF-8'
) ?>

</small>

</div>


<i class="fa-solid fa-chevron-left"></i>

</a>


<?php endif; ?>


<a
    href="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>"
    class="extra-card"
>


<div class="extra-icon">

<i class="fa-solid fa-cart-shopping"></i>

</div>


<div class="extra-info">

<strong>

<?= htmlspecialchars(
    $cartTitle,
    ENT_QUOTES,
    'UTF-8'
) ?>

</strong>

<small>

<?= htmlspecialchars(
    $cartDescription,
    ENT_QUOTES,
    'UTF-8'
) ?>

</small>

</div>


<?php if ($cartCount > 0): ?>

<span class="cart-count">

<?= $cartCount ?>

</span>

<?php endif; ?>


<i class="fa-solid fa-chevron-left"></i>

</a>


<a
    href="welcome.php?lang=<?= urlencode($lang) ?>"
    class="extra-card"
>


<div class="extra-icon">

<i class="fa-solid fa-house"></i>

</div>


<div class="extra-info">

<strong>

<?= htmlspecialchars(
    $backText,
    ENT_QUOTES,
    'UTF-8'
) ?>

</strong>

<small>

<?= $isArabic
    ? 'العودة إلى الصفحة الرئيسية'
    : 'Return to the home page'
?>

</small>

</div>


<i class="fa-solid fa-chevron-left"></i>

</a>


</div>

</section>


<!-- =========================================================
     INFO
========================================================= -->

<section class="info-strip">

<div class="info-box">


<div>

<h3>

<?= $isArabic
    ? 'خدمة سهلة من مكان واحد'
    : 'Simple services in one place'
?>

</h3>


<p>

<?= $isArabic
    ? 'اختر الخدمة، أكمل بياناتك، وتابع طلبك بكل سهولة.'
    : 'Choose a service, complete your details and track your request easily.'
?>

</p>

</div>


<div class="info-box-icon">

<i class="fa-solid fa-headset"></i>

</div>


</div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">

<strong>

<?= htmlspecialchars(
    $companyName,
    ENT_QUOTES,
    'UTF-8'
) ?>

</strong>

&nbsp; • &nbsp;

<?= $isArabic
    ? 'جميع الحقوق محفوظة'
    : 'All rights reserved'
?>

&nbsp; © <?= date('Y') ?>


<?php if ($companyPhone !== ''): ?>

&nbsp; • &nbsp;

<i class="fa-solid fa-phone"></i>

<?= htmlspecialchars(
    $companyPhone,
    ENT_QUOTES,
    'UTF-8'
) ?>

<?php endif; ?>


</footer>


</body>

</html>