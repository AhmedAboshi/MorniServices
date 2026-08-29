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

require_once __DIR__ . '/../include/connected.php';


/* =========================================================
   SETTINGS
========================================================= */

require_once __DIR__ . '/../include/settings.php';


/* =========================================================
   LANGUAGE
========================================================= */

if (isset($_GET['lang'])) {

    $newLang = $_GET['lang'];

    if (in_array($newLang, ['ar', 'en'], true)) {
        $_SESSION['lang'] = $newLang;
    }
}

$lang = $_SESSION['lang'] ?? setting('default_language', 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}


/* =========================================================
   USER
========================================================= */

$user_id = (int)($_SESSION['user_id'] ?? 0);

$user_name = '';
$user_email = '';

if ($user_id > 0) {

    $stmt = $con->prepare("
        SELECT username, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param("i", $user_id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $user_name =
                trim((string)($row['username'] ?? ''));

            $user_email =
                trim((string)($row['email'] ?? ''));
        }

        $stmt->close();
    }
}


/* =========================================================
   TRANSLATIONS
========================================================= */

$t = [

    'ar' => [

        'home'               => 'الرئيسية',
        'search'             => 'بحث',
        'search_placeholder' => 'ابحث عن خدمة أو منتج...',
        'between_cities'     => 'سطحة بين المدن',
        'about'              => 'من نحن',
        'contact'            => 'تواصل معنا',
        'orders'             => 'طلباتي',
        'profile'            => 'حسابي',
        'cart'               => 'السلة',
        'notifications'      => 'الإشعارات',
        'login'              => 'تسجيل الدخول',
        'logout'             => 'تسجيل الخروج',
        'menu'               => 'القائمة',
        'sections'           => 'الخدمات',
        'latest'             => 'المضاف حديثاً',
        'no_products'        => 'لا توجد منتجات حالياً',
        'language'           => 'اللغة',
        'welcome'            => 'مرحباً',
        'account'            => 'الحساب',
        'view_orders'        => 'عرض طلباتي',
        'close'              => 'إغلاق'

    ],

    'en' => [

        'home'               => 'Home',
        'search'             => 'Search',
        'search_placeholder' => 'Search services or products...',
        'between_cities'     => 'Tow Between Cities',
        'about'              => 'About Us',
        'contact'            => 'Contact Us',
        'orders'             => 'My Orders',
        'profile'            => 'My Account',
        'cart'               => 'Cart',
        'notifications'      => 'Notifications',
        'login'              => 'Login',
        'logout'             => 'Logout',
        'menu'               => 'Menu',
        'sections'           => 'Services',
        'latest'             => 'Recently Added',
        'no_products'        => 'No products available',
        'language'           => 'Language',
        'welcome'            => 'Welcome',
        'account'            => 'Account',
        'view_orders'        => 'View My Orders',
        'close'              => 'Close'

    ]

];



/* =========================================================
   SYSTEM DATA
========================================================= */

$system_name = trim((string)setting(
    'system_name',
    'Al-Sharq Smart Platform for Services and Fleet Management'
));

$company_name = trim((string)setting(
    'company_name',
    'منصة الشرق الذكية للخدمات وإدارة الأسطول'
));

$company_logo = trim((string)setting(
    'company_logo',
    ''
));

$company_phone = trim((string)setting(
    'company_phone',
    ''
));

$company_email = trim((string)setting(
    'company_email',
    ''
));

$company_address = trim((string)setting(
    'company_address',
    ''
));

$company_website = trim((string)setting(
    'company_website',
    ''
));

$company_favicon = trim((string)setting(
    'company_favicon',
    ''
));

$primary_color = trim((string)setting(
    'primary_color',
    '#0d6efd'
));


/* =========================================================
   LOGO PATH
========================================================= */

$logoPath = '';

if ($company_logo !== '') {

    $logoPath =
        'uploads/logo/' .
        basename($company_logo);

} elseif (file_exists(__DIR__ . '/img/logo.jpg')) {

    $logoPath = 'img/logo.jpg';
}


/* =========================================================
   FAVICON
========================================================= */

$faviconPath = '';

if ($company_favicon !== '') {

    $faviconPath =
        'uploads/logo/' .
        basename($company_favicon);
}


/* =========================================================
   ORDERS COUNT
========================================================= */

$order_count = 0;

if ($user_id > 0) {

    $stmt = $con->prepare("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE user_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $order_count =
                (int)$row['total'];
        }

        $stmt->close();
    }
}


/* =========================================================
   CART COUNT
========================================================= */

$row_count = 0;

if ($user_id > 0) {

    $stmt = $con->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE user_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $row_count =
                (int)$row['total'];
        }

        $stmt->close();
    }
}


/* =========================================================
   NOTIFICATIONS COUNT
========================================================= */

$notification_count = 0;

if ($user_id > 0) {

    $stmt = $con->prepare("
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $notification_count =
                (int)$row['total'];
        }

        $stmt->close();
    }
}


/* =========================================================
   SECTIONS
========================================================= */

$sections = [];

$sectionQuery = $con->query("
    SELECT *
    FROM section
    ORDER BY id ASC
");

if ($sectionQuery) {

    while ($sectionRow =
        $sectionQuery->fetch_assoc()
    ) {

        $sections[] =
            $sectionRow;
    }
}


/* =========================================================
   LATEST PRODUCTS
========================================================= */

$latestProducts = [];

$productQuery = $con->query("
    SELECT id, proimg
    FROM product
    ORDER BY id DESC
    LIMIT 8
");

if ($productQuery) {

    while ($productRow =
        $productQuery->fetch_assoc()
    ) {

        $latestProducts[] =
            $productRow;
    }
}


/* =========================================================
   CURRENT PAGE
========================================================= */

$currentPage =
    basename($_SERVER['PHP_SELF'] ?? '');


?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="<?= htmlspecialchars(
            $primary_color,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <?php if ($faviconPath !== ''): ?>

        <link
            rel="icon"
            href="<?= htmlspecialchars(
                $faviconPath,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    <?php endif; ?>


    <link
        rel="stylesheet"
        href="style.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous"
    >


    <style>

/* =========================================================
   AL SHARQ CLIENT HEADER
========================================================= */

:root {

    --header-primary:
        <?= htmlspecialchars(
            $primary_color,
            ENT_QUOTES,
            'UTF-8'
        ) ?>;

    --header-dark:#102a56;

    --header-text:#1f2937;

    --header-muted:#64748b;

    --header-border:#e5e7eb;

    --header-bg:#ffffff;

    --header-soft:#f5f8fc;

    --header-danger:#dc3545;

    --header-radius:14px;
}


/* =========================================================
   RESET
========================================================= */

.client-header *,
.client-header *::before,
.client-header *::after {

    box-sizing:border-box;
}


/* =========================================================
   TOP BAR
========================================================= */

.client-topbar {

    width:100%;

    background:
        linear-gradient(
            135deg,
            #0f2f61,
            #173f7a
        );

    color:#fff;

    min-height:38px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:
        0
        4%;

    gap:15px;

    font-size:13px;
}


.client-topbar-info {

    display:flex;

    align-items:center;

    gap:18px;

    flex-wrap:wrap;
}


.client-topbar-info a,
.client-topbar-info span {

    color:#fff;

    text-decoration:none;

    display:flex;

    align-items:center;

    gap:6px;

    opacity:.92;
}


.client-topbar-info a:hover {

    opacity:1;
}


.client-language {

    display:flex;

    align-items:center;

    gap:7px;
}


.client-language a {

    color:#dbeafe;

    text-decoration:none;

    padding:5px 9px;

    border-radius:8px;

    transition:.2s;
}


.client-language a:hover,
.client-language a.active {

    background:
        rgba(255,255,255,.15);

    color:#fff;

}


/* =========================================================
   MAIN HEADER
========================================================= */

.client-header-main {

    width:100%;

    background:
        rgba(255,255,255,.98);

    border-bottom:
        1px solid
        var(--header-border);

    position:sticky;

    top:0;

    z-index:1000;

    box-shadow:
        0 3px 20px
        rgba(15,42,86,.06);
}


.client-header-inner {

    width:92%;

    max-width:1450px;

    min-height:92px;

    margin:auto;

    display:grid;

    grid-template-columns:
        auto
        minmax(260px, 1fr)
        auto;

    align-items:center;

    gap:28px;
}


/* =========================================================
   LOGO
========================================================= */

.client-logo {

    display:flex;

    align-items:center;

    min-width:230px;
}


.client-logo a {

    display:flex;

    align-items:center;

    gap:12px;

    color:inherit;

    text-decoration:none;

}


.client-logo-image {

    width:58px;

    height:58px;

    border-radius:14px;

    object-fit:contain;

    background:#fff;

    border:
        1px solid
        var(--header-border);

    padding:4px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.07);
}


.client-logo-placeholder {

    width:58px;

    height:58px;

    border-radius:14px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            var(--header-primary),
            #173f7a
        );

    color:#fff;

    font-size:24px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.12);
}


.client-logo-text {

    min-width:0;
}


.client-logo-text strong {

    display:block;

    color:var(--header-dark);

    font-size:17px;

    line-height:1.4;

    font-weight:800;

    max-width:260px;
}


.client-logo-text span {

    display:block;

    color:var(--header-muted);

    font-size:11px;

    margin-top:3px;

    max-width:270px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;
}


/* =========================================================
   SEARCH
========================================================= */

.client-search {

    width:100%;

    max-width:650px;

    justify-self:center;
}


.client-search form {

    position:relative;

    width:100%;
}


.client-search input {

    width:100%;

    height:50px;

    border:
        1px solid
        var(--header-border);

    border-radius:15px;

    background:#f8fafc;

    padding:
        0 55px
        0 18px;

    outline:none;

    font-family:inherit;

    font-size:14px;

    color:var(--header-text);

    transition:.2s;
}


html[dir="ltr"]
.client-search input {

    padding:
        0 55px
        0 18px;
}


.client-search input:focus {

    background:#fff;

    border-color:
        var(--header-primary);

    box-shadow:
        0 0 0 4px
        rgba(13,110,253,.08);
}


.client-search button {

    position:absolute;

    top:5px;

    right:5px;

    width:40px;

    height:40px;

    border:0;

    border-radius:11px;

    background:
        var(--header-primary);

    color:#fff;

    cursor:pointer;

    font-size:16px;

    transition:.2s;
}


html[dir="ltr"]
.client-search button {

    right:auto;

    left:5px;
}


.client-search button:hover {

    transform:translateY(-1px);

    filter:brightness(.95);
}


/* =========================================================
   HEADER ACTIONS
========================================================= */

.client-actions {

    display:flex;

    align-items:center;

    justify-content:flex-end;

    gap:8px;
}


.header-action {

    width:46px;

    height:46px;

    border-radius:13px;

    display:flex;

    align-items:center;

    justify-content:center;

    position:relative;

    text-decoration:none;

    color:var(--header-dark);

    background:#f6f8fb;

    border:
        1px solid
        #edf0f4;

    transition:.2s;
}


.header-action:hover {

    background:
        var(--header-primary);

    color:#fff;

    transform:translateY(-2px);
}


.header-action i {

    font-size:17px;
}


.header-badge {

    position:absolute;

    top:-5px;

    right:-5px;

    min-width:20px;

    height:20px;

    padding:0 5px;

    border-radius:20px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:var(--header-danger);

    color:#fff;

    border:2px solid #fff;

    font-size:10px;

    font-weight:800;
}


html[dir="ltr"]
.header-badge {

    right:auto;

    left:-5px;
}


/* =========================================================
   USER
========================================================= */

.header-user {

    position:relative;
}


.header-user-link {

    min-height:46px;

    padding:
        6px
        11px;

    display:flex;

    align-items:center;

    gap:9px;

    border-radius:13px;

    background:#f6f8fb;

    border:
        1px solid
        #edf0f4;

    text-decoration:none;

    color:var(--header-text);

    transition:.2s;

    max-width:180px;
}


.header-user-link:hover {

    background:#eef4ff;

    border-color:#dbe7ff;
}


.header-user-avatar {

    width:34px;

    height:34px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            var(--header-primary),
            #173f7a
        );

    color:#fff;

    font-size:14px;

    flex-shrink:0;
}


.header-user-text {

    min-width:0;

    text-align:right;
}


html[dir="ltr"]
.header-user-text {

    text-align:left;
}


.header-user-text strong {

    display:block;

    font-size:12px;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;
}


.header-user-text span {

    display:block;

    color:var(--header-muted);

    font-size:10px;

    margin-top:2px;
}


/* =========================================================
   NAVIGATION
========================================================= */

.client-navigation {

    width:100%;

    background:#fff;

    border-bottom:
        1px solid
        var(--header-border);
}


.client-navigation-inner {

    width:92%;

    max-width:1450px;

    min-height:55px;

    margin:auto;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;
}


.client-nav-list {

    display:flex;

    align-items:center;

    gap:4px;

    margin:0;

    padding:0;

    list-style:none;

    flex-wrap:wrap;
}


.client-nav-list > li {

    position:relative;
}


.client-nav-list > li > a {

    display:flex;

    align-items:center;

    gap:7px;

    min-height:43px;

    padding:
        0 13px;

    border-radius:10px;

    color:#334155;

    text-decoration:none;

    font-size:13px;

    font-weight:600;

    white-space:nowrap;

    transition:.2s;
}


.client-nav-list > li > a:hover,
.client-nav-list > li > a.active {

    background:#eef4ff;

    color:
        var(--header-primary);
}


.client-nav-list > li > a i {

    font-size:14px;
}


/* =========================================================
   SERVICES DROPDOWN
========================================================= */

.services-dropdown {

    position:relative;
}


.services-dropdown-menu {

    position:absolute;

    top:calc(100% + 7px);

    right:0;

    min-width:230px;

    max-height:400px;

    overflow:auto;

    background:#fff;

    border:
        1px solid
        var(--header-border);

    border-radius:14px;

    box-shadow:
        0 15px 40px
        rgba(15,23,42,.12);

    padding:7px;

    display:none;

    z-index:1100;
}


html[dir="ltr"]
.services-dropdown-menu {

    right:auto;

    left:0;
}


.services-dropdown:hover
.services-dropdown-menu {

    display:block;
}


.services-dropdown-menu a {

    display:flex;

    align-items:center;

    gap:9px;

    padding:
        11px 12px;

    border-radius:9px;

    color:#334155;

    text-decoration:none;

    font-size:13px;

    transition:.2s;
}


.services-dropdown-menu a:hover {

    background:#f1f5f9;

    color:var(--header-primary);
}


/* =========================================================
   QUICK LINKS
========================================================= */

.client-quick-links {

    display:flex;

    align-items:center;

    gap:7px;

    flex-shrink:0;
}


.quick-link {

    display:flex;

    align-items:center;

    gap:7px;

    padding:
        8px 11px;

    border-radius:10px;

    background:#f8fafc;

    color:#475569;

    text-decoration:none;

    font-size:12px;

    font-weight:600;

    border:
        1px solid
        #edf0f4;
}


.quick-link:hover {

    color:
        var(--header-primary);

    border-color:#dbe7ff;

    background:#eef4ff;
}


/* =========================================================
   MOBILE BUTTON
========================================================= */

.mobile-menu-btn {

    display:none;

    width:46px;

    height:46px;

    border:0;

    border-radius:13px;

    background:
        var(--header-primary);

    color:#fff;

    cursor:pointer;

    font-size:18px;
}


/* =========================================================
   MOBILE MENU
========================================================= */

.mobile-navigation {

    display:none;

    width:100%;

    background:#fff;

    border-bottom:
        1px solid
        var(--header-border);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.08);

    padding:12px;

}


.mobile-navigation.open {

    display:block;
}


.mobile-nav-list {

    list-style:none;

    margin:0;

    padding:0;

    display:flex;

    flex-direction:column;

    gap:5px;
}


.mobile-nav-list a {

    min-height:46px;

    display:flex;

    align-items:center;

    gap:10px;

    padding:
        0 13px;

    border-radius:10px;

    color:#334155;

    text-decoration:none;

    font-size:14px;

    font-weight:600;
}


.mobile-nav-list a:hover {

    background:#eef4ff;

    color:var(--header-primary);
}


.mobile-services {

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:5px;

    padding:
        5px 0 5px 20px;
}


html[dir="rtl"]
.mobile-services {

    padding:
        5px 20px 5px 0;
}


.mobile-services a {

    min-height:40px;

    font-size:12px;

    background:#f8fafc;
}


/* =========================================================
   LATEST PRODUCTS BAR
========================================================= */

.latest-products-bar {

    width:92%;

    max-width:1450px;

    margin:
        12px auto;

    padding:
        9px 12px;

    background:#fff;

    border:
        1px solid
        var(--header-border);

    border-radius:13px;

    display:flex;

    align-items:center;

    gap:13px;

    overflow:hidden;
}


.latest-products-title {

    flex-shrink:0;

    color:var(--header-dark);

    font-size:12px;

    font-weight:800;

    display:flex;

    align-items:center;

    gap:7px;
}


.latest-products-list {

    display:flex;

    align-items:center;

    gap:7px;

    overflow:auto;

    scrollbar-width:none;
}


.latest-products-list::-webkit-scrollbar {

    display:none;
}


.latest-product-item {

    flex-shrink:0;

    width:42px;

    height:42px;

    border-radius:10px;

    overflow:hidden;

    border:
        1px solid
        #e5e7eb;

    background:#f8fafc;
}


.latest-product-item img {

    width:100%;

    height:100%;

    object-fit:cover;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1150px){

    .client-header-inner {

        grid-template-columns:
            auto
            1fr
            auto;

        gap:15px;
    }


    .client-logo {

        min-width:auto;
    }


    .client-logo-text span {

        display:none;
    }


    .client-logo-text strong {

        max-width:180px;
    }


    .client-nav-list > li > a {

        padding:
            0 9px;

        font-size:12px;
    }


    .quick-link span {

        display:none;
    }

}


@media(max-width:900px){

    .client-header-inner {

        width:94%;

        min-height:75px;

        grid-template-columns:
            auto
            1fr
            auto;
    }


    .client-logo-image,
    .client-logo-placeholder {

        width:48px;

        height:48px;
    }


    .client-logo-text strong {

        font-size:14px;

        max-width:160px;
    }


    .client-logo-text span {

        display:none;
    }


    .client-search {

        max-width:none;
    }


    .client-search input {

        height:44px;

        font-size:13px;
    }


    .client-search button {

        width:36px;

        height:36px;
    }


    .header-user {

        display:none;
    }


    .client-navigation {

        display:none;
    }


    .mobile-menu-btn {

        display:flex;

        align-items:center;

        justify-content:center;
    }


    .client-actions {

        gap:5px;
    }


    .header-action {

        width:42px;

        height:42px;
    }


    .client-quick-links {

        display:none;
    }


    .latest-products-bar {

        width:94%;
    }

}


@media(max-width:650px){

    .client-topbar {

        min-height:34px;

        padding:
            0 3%;

        font-size:11px;
    }


    .client-topbar-info {

        gap:10px;
    }


    .client-topbar-info .address-info {

        display:none;
    }


    .client-header-inner {

        width:94%;

        grid-template-columns:
            auto
            1fr
            auto;

        gap:7px;
    }


    .client-logo-text {

        display:none;
    }


    .client-logo-image,
    .client-logo-placeholder {

        width:44px;

        height:44px;

        border-radius:11px;
    }


    .client-search input {

        height:42px;

        padding:
            0 45px
            0 12px;

        border-radius:12px;
    }


    html[dir="ltr"]
    .client-search input {

        padding:
            0 45px
            0 12px;
    }


    .client-search button {

        top:4px;

        right:4px;

        width:34px;

        height:34px;

        border-radius:9px;
    }


    html[dir="ltr"]
    .client-search button {

        right:auto;

        left:4px;
    }


    .header-action {

        width:40px;

        height:40px;

        border-radius:11px;
    }


    .header-action:nth-child(3) {

        display:none;
    }


    .mobile-menu-btn {

        width:40px;

        height:40px;

        border-radius:11px;
    }


    .latest-products-bar {

        margin-top:9px;

        padding:8px;

        gap:8px;
    }


    .latest-products-title {

        font-size:10px;
    }


    .latest-product-item {

        width:36px;

        height:36px;
    }

}


@media(max-width:430px){

    .client-topbar-info a {

        display:none;
    }


    .client-topbar-info a:first-child {

        display:flex;
    }


    .client-language a {

        padding:
            4px 6px;

        font-size:10px;
    }


    .client-header-inner {

        min-height:68px;
    }


    .client-logo-image,
    .client-logo-placeholder {

        width:40px;

        height:40px;
    }


    .header-action {

        width:36px;

        height:36px;
    }


    .mobile-menu-btn {

        width:36px;

        height:36px;
    }

}


/* =========================================================
   BODY OFFSET
========================================================= */

body {

    margin:0;
}


    </style>


    <title>
        <?= htmlspecialchars(
            $system_name,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

</head>


<body>


<div class="client-header">


    <!-- =====================================================
         TOP BAR
    ====================================================== -->

    <div class="client-topbar">

        <div class="client-topbar-info">

            <?php if ($company_phone !== ''): ?>

                <a
                    href="tel:<?= htmlspecialchars(
                        $company_phone,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <i class="fa-solid fa-phone"></i>

                    <?= htmlspecialchars(
                        $company_phone,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            <?php endif; ?>


            <?php if ($company_email !== ''): ?>

                <a
                    href="mailto:<?= htmlspecialchars(
                        $company_email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <i class="fa-solid fa-envelope"></i>

                    <span>
                        <?= htmlspecialchars(
                            $company_email,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </a>

            <?php endif; ?>


            <?php if ($company_address !== ''): ?>

                <span class="address-info">

                    <i class="fa-solid fa-location-dot"></i>

                    <?= htmlspecialchars(
                        $company_address,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            <?php endif; ?>

        </div>


        <div class="client-language">

            <span>
                <?= htmlspecialchars(
                    $t[$lang]['language'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>:
            </span>

            <a
                href="?lang=ar"
                class="<?= $lang === 'ar' ? 'active' : '' ?>"
            >
                🇸🇦 عربي
            </a>

            <a
                href="?lang=en"
                class="<?= $lang === 'en' ? 'active' : '' ?>"
            >
                🇬🇧 English
            </a>

        </div>

    </div>



    <!-- =====================================================
         MAIN HEADER
    ====================================================== -->

    <div class="client-header-main">

        <div class="client-header-inner">


            <!-- LOGO -->

            <div class="client-logo">

                <a href="index.php">

                    <?php if ($logoPath !== ''): ?>

                        <img
                            class="client-logo-image"
                            src="<?= htmlspecialchars(
                                $logoPath,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $company_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    <?php else: ?>

                        <div class="client-logo-placeholder">

                            <i class="fa-solid fa-building"></i>

                        </div>

                    <?php endif; ?>


                    <div class="client-logo-text">

                        <strong>

                            <?= htmlspecialchars(
                                $company_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>

                        <span>

                            <?= htmlspecialchars(
                                $system_name,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>

                </a>

            </div>



            <!-- SEARCH -->

            <div class="client-search">

                <form
                    action="search.php"
                    method="get"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="<?= htmlspecialchars(
                            $t[$lang]['search_placeholder'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        autocomplete="off"
                    >

                    <button
                        type="submit"
                        name="btn_search"
                        aria-label="<?= htmlspecialchars(
                            $t[$lang]['search'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <i class="fa-solid fa-magnifying-glass"></i>

                    </button>

                </form>

            </div>



            <!-- ACTIONS -->

            <div class="client-actions">


                <!-- NOTIFICATIONS -->

                <?php if ($user_id > 0): ?>

                    <a
                        href="notifications.php"
                        class="header-action"
                        title="<?= htmlspecialchars(
                            $t[$lang]['notifications'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <i class="fa-solid fa-bell"></i>

                        <?php if ($notification_count > 0): ?>

                            <span class="header-badge">

                                <?= $notification_count > 99
                                    ? '99+'
                                    : $notification_count ?>

                            </span>

                        <?php endif; ?>

                    </a>

                <?php endif; ?>



                <!-- CART -->

                <a
                    href="cart.php"
                    class="header-action"
                    title="<?= htmlspecialchars(
                        $t[$lang]['cart'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <i class="fa-solid fa-cart-shopping"></i>

                    <?php if ($row_count > 0): ?>

                        <span class="header-badge">

                            <?= $row_count > 99
                                ? '99+'
                                : $row_count ?>

                        </span>

                    <?php endif; ?>

                </a>



                <!-- USER -->

                <div class="header-user">

                    <?php if ($user_id > 0): ?>

                        <a
                            href="Profile.php"
                            class="header-user-link"
                        >

                            <span class="header-user-avatar">

                                <i class="fa-solid fa-user"></i>

                            </span>


                            <span class="header-user-text">

                                <strong>

                                    <?= htmlspecialchars(
                                        $user_name !== ''
                                            ? $user_name
                                            : $t[$lang]['account'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>

                                <span>

                                    <?= htmlspecialchars(
                                        $t[$lang]['profile'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </span>

                        </a>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="header-user-link"
                        >

                            <span class="header-user-avatar">

                                <i class="fa-solid fa-right-to-bracket"></i>

                            </span>


                            <span class="header-user-text">

                                <strong>

                                    <?= htmlspecialchars(
                                        $t[$lang]['login'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>

                                <span>

                                    <?= htmlspecialchars(
                                        $t[$lang]['account'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </span>

                        </a>

                    <?php endif; ?>

                </div>



                <!-- MOBILE MENU -->

                <button
                    type="button"
                    class="mobile-menu-btn"
                    onclick="toggleClientMobileMenu()"
                    aria-label="<?= htmlspecialchars(
                        $t[$lang]['menu'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    aria-expanded="false"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>


            </div>

        </div>

    </div>



    <!-- =====================================================
         DESKTOP NAVIGATION
    ====================================================== -->

    <nav class="client-navigation">

        <div class="client-navigation-inner">


            <ul class="client-nav-list">


                <!-- HOME -->

                <li>

                    <a
                        href="index.php"
                        class="<?= $currentPage === 'index.php'
                            ? 'active'
                            : '' ?>"
                    >

                        <i class="fa-solid fa-house"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['home'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>



                <!-- SERVICES -->

                <li class="services-dropdown">

                    <a href="javascript:void(0);">

                        <i class="fa-solid fa-layer-group"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['sections'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <i
                            class="fa-solid fa-chevron-down"
                            style="font-size:9px;"
                        ></i>

                    </a>


                    <div class="services-dropdown-menu">

                        <?php if (!empty($sections)): ?>

                            <?php foreach ($sections as $section): ?>

                                <?php

                                $sectionName =
                                    trim(
                                        (string)(
                                            $section['sectionname']
                                            ?? ''
                                        )
                                    );

                                if ($sectionName === '') {
                                    continue;
                                }

                                ?>

                                <a
                                    href="section.php?section=<?= urlencode(
                                        $sectionName
                                    ) ?>"
                                >

                                    <i
                                        class="fa-solid fa-angle-left"
                                    ></i>

                                    <?= htmlspecialchars(
                                        $sectionName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </a>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <a href="section.php">

                                <?= htmlspecialchars(
                                    $t[$lang]['no_products'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </a>

                        <?php endif; ?>

                    </div>

                </li>



                <!-- TOW -->

                <li>

                    <a
                        href="tow_order.php?type=tow_city"
                        class="<?= $currentPage === 'tow_order.php'
                            ? 'active'
                            : '' ?>"
                    >

                        <i class="fa-solid fa-truck-moving"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['between_cities'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>



                <!-- ORDERS -->

                <?php if ($user_id > 0): ?>

                    <li>

                        <a
                            href="myorders.php"
                            class="<?= $currentPage === 'myorders.php'
                                ? 'active'
                                : '' ?>"
                        >

                            <i class="fa-solid fa-box-open"></i>

                            <?= htmlspecialchars(
                                $t[$lang]['orders'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                            <?php if ($order_count > 0): ?>

                                <span
                                    style="
                                        background:#dc3545;
                                        color:#fff;
                                        border-radius:20px;
                                        padding:2px 7px;
                                        font-size:10px;
                                    "
                                >

                                    <?= $order_count > 99
                                        ? '99+'
                                        : $order_count ?>

                                </span>

                            <?php endif; ?>

                        </a>

                    </li>

                <?php endif; ?>



                <!-- ABOUT -->

                <li>

                    <a
                        href="about.php"
                        class="<?= $currentPage === 'about.php'
                            ? 'active'
                            : '' ?>"
                    >

                        <i class="fa-solid fa-circle-info"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['about'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>



                <!-- CONTACT -->

                <li>

                    <a
                        href="contact.php"
                        class="<?= $currentPage === 'contact.php'
                            ? 'active'
                            : '' ?>"
                    >

                        <i class="fa-solid fa-headset"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['contact'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>

            </ul>



            <!-- QUICK LINKS -->

            <div class="client-quick-links">


                <?php if ($company_phone !== ''): ?>

                    <a
                        href="https://wa.me/<?= preg_replace(
                            '/[^0-9]/',
                            '',
                            $company_phone
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="quick-link"
                    >

                        <i class="fa-brands fa-whatsapp"></i>

                        <span>WhatsApp</span>

                    </a>

                <?php endif; ?>


                <?php if ($company_website !== ''): ?>

                    <a
                        href="<?= htmlspecialchars(
                            $company_website,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="quick-link"
                    >

                        <i class="fa-solid fa-globe"></i>

                        <span>
                            <?= htmlspecialchars(
                                $lang === 'ar'
                                    ? 'الموقع'
                                    : 'Website',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </a>

                <?php endif; ?>


            </div>

        </div>

    </nav>



    <!-- =====================================================
         MOBILE NAVIGATION
    ====================================================== -->

    <div
        id="mobileClientNavigation"
        class="mobile-navigation"
    >

        <ul class="mobile-nav-list">


            <li>

                <a href="index.php">

                    <i class="fa-solid fa-house"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['home'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            </li>


            <li>

                <a href="section.php">

                    <i class="fa-solid fa-layer-group"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['sections'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>


                <?php if (!empty($sections)): ?>

                    <div class="mobile-services">

                        <?php foreach ($sections as $section): ?>

                            <?php

                            $sectionName =
                                trim(
                                    (string)(
                                        $section['sectionname']
                                        ?? ''
                                    )
                                );

                            if ($sectionName === '') {
                                continue;
                            }

                            ?>

                            <a
                                href="section.php?section=<?= urlencode(
                                    $sectionName
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $sectionName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </li>


            <li>

                <a href="tow_order.php?type=tow_city">

                    <i class="fa-solid fa-truck-moving"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['between_cities'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            </li>


            <?php if ($user_id > 0): ?>

                <li>

                    <a href="myorders.php">

                        <i class="fa-solid fa-box-open"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['orders'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>


                <li>

                    <a href="Profile.php">

                        <i class="fa-solid fa-user"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['profile'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>


                <li>

                    <a href="notifications.php">

                        <i class="fa-solid fa-bell"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['notifications'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <?php if ($notification_count > 0): ?>

                            <span
                                style="
                                    margin-inline-start:auto;
                                    background:#dc3545;
                                    color:#fff;
                                    padding:3px 8px;
                                    border-radius:20px;
                                    font-size:10px;
                                "
                            >

                                <?= $notification_count ?>

                            </span>

                        <?php endif; ?>

                    </a>

                </li>

            <?php else: ?>

                <li>

                    <a href="login.php">

                        <i class="fa-solid fa-right-to-bracket"></i>

                        <?= htmlspecialchars(
                            $t[$lang]['login'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                </li>

            <?php endif; ?>


            <li>

                <a href="cart.php">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['cart'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <?php if ($row_count > 0): ?>

                        <span
                            style="
                                margin-inline-start:auto;
                                background:#dc3545;
                                color:#fff;
                                padding:3px 8px;
                                border-radius:20px;
                                font-size:10px;
                            "
                        >

                            <?= $row_count ?>

                        </span>

                    <?php endif; ?>

                </a>

            </li>


            <li>

                <a href="about.php">

                    <i class="fa-solid fa-circle-info"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['about'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            </li>


            <li>

                <a href="contact.php">

                    <i class="fa-solid fa-headset"></i>

                    <?= htmlspecialchars(
                        $t[$lang]['contact'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            </li>

        </ul>

    </div>



    <!-- =====================================================
         LATEST PRODUCTS
    ====================================================== -->

    <?php if (!empty($latestProducts)): ?>

        <div class="latest-products-bar">

            <div class="latest-products-title">

                <i class="fa-solid fa-bolt"></i>

                <?= htmlspecialchars(
                    $t[$lang]['latest'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <div class="latest-products-list">

                <?php foreach ($latestProducts as $product): ?>

                    <?php

                    $productId =
                        (int)(
                            $product['id'] ?? 0
                        );

                    $productImage =
                        trim(
                            (string)(
                                $product['proimg'] ?? ''
                            )
                        );

                    if ($productId <= 0) {
                        continue;
                    }

                    ?>

                    <a
                        href="detalis.php?id=<?= $productId ?>"
                        class="latest-product-item"
                        title="<?= htmlspecialchars(
                            $t[$lang]['latest'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <?php if ($productImage !== ''): ?>

                            <img
                                src="uploads/img/<?= htmlspecialchars(
                                    basename($productImage),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt=""
                                loading="lazy"
                            >

                        <?php else: ?>

                            <span
                                style="
                                    width:100%;
                                    height:100%;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    color:#94a3b8;
                                "
                            >

                                <i class="fa-solid fa-image"></i>

                            </span>

                        <?php endif; ?>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

</div>



<script>

/* =========================================================
   MOBILE MENU
========================================================= */

function toggleClientMobileMenu() {

    const menu =
        document.getElementById(
            'mobileClientNavigation'
        );

    const button =
        document.querySelector(
            '.mobile-menu-btn'
        );

    if (!menu) {
        return;
    }

    menu.classList.toggle('open');


    if (button) {

        const isOpen =
            menu.classList.contains('open');

        button.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );


        const icon =
            button.querySelector('i');

        if (icon) {

            icon.className =
                isOpen
                    ? 'fa-solid fa-xmark'
                    : 'fa-solid fa-bars';
        }
    }
}


/* =========================================================
   CLOSE MOBILE MENU WHEN CLICK OUTSIDE
========================================================= */

document.addEventListener(
    'click',
    function(event) {

        const menu =
            document.getElementById(
                'mobileClientNavigation'
            );

        const button =
            document.querySelector(
                '.mobile-menu-btn'
            );

        if (!menu || !button) {
            return;
        }


        if (
            menu.classList.contains('open') &&
            !menu.contains(event.target) &&
            !button.contains(event.target)
        ) {

            menu.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

            const icon =
                button.querySelector('i');

            if (icon) {

                icon.className =
                    'fa-solid fa-bars';
            }
        }

    }
);


/* =========================================================
   CLOSE MOBILE MENU AFTER LINK
========================================================= */

document.querySelectorAll(
    '#mobileClientNavigation a'
).forEach(function(link) {

    link.addEventListener(
        'click',
        function() {

            const menu =
                document.getElementById(
                    'mobileClientNavigation'
                );

            const button =
                document.querySelector(
                    '.mobile-menu-btn'
                );

            if (menu) {
                menu.classList.remove('open');
            }

            if (button) {

                button.setAttribute(
                    'aria-expanded',
                    'false'
                );

                const icon =
                    button.querySelector('i');

                if (icon) {
                    icon.className =
                        'fa-solid fa-bars';
                }
            }

        }
    );

});

</script>

</body>

</html>