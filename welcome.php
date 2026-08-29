<?php
/* =========================================================
   AlSharqPlatform
   Public Welcome / Landing Page
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   LANGUAGE
========================================================= */

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], ['ar', 'en'], true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$isArabic = ($lang === 'ar');


/* =========================================================
   SETTINGS
========================================================= */

$settings = [];

/*
   إذا كان ملف settings.php موجودًا نستخدمه
*/
$settingsFile = __DIR__ . '/include/settings.php';

if (file_exists($settingsFile)) {
    $loadedSettings = include $settingsFile;

    if (is_array($loadedSettings)) {
        $settings = $loadedSettings;
    }
}


/* =========================================================
   قراءة الإعدادات من قاعدة البيانات إذا لم تكن محملة
========================================================= */

if (empty($settings)) {

    $settingsQuery = @mysqli_query(
        $con,
        "SELECT * FROM settings LIMIT 1"
    );

    if ($settingsQuery) {

        $settingsRow = mysqli_fetch_assoc(
            $settingsQuery
        );

        if ($settingsRow) {
            $settings = $settingsRow;
        }
    }
}


/* =========================================================
   بيانات المنصة
========================================================= */

$systemName = trim(
    $settings['system_name']
    ?? $settings['company_name']
    ?? 'AlSharqPlatform'
);

$companyName = trim(
    $settings['company_name']
    ?? $systemName
);

$logo = trim(
    $settings['company_logo']
    ?? ''
);

$phone = trim(
    $settings['company_phone']
    ?? ''
);

$email = trim(
    $settings['company_email']
    ?? ''
);

$address = trim(
    $settings['company_address']
    ?? ''
);

$website = trim(
    $settings['company_website']
    ?? ''
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
   إحصائيات المنصة
========================================================= */

$totalOrders = 0;
$totalCustomers = 0;
$totalDrivers = 0;
$totalCompleted = 0;


/* الطلبات */

$q = @mysqli_query(
    $con,
    "SELECT COUNT(*) AS total FROM orders"
);

if ($q) {

    $r = mysqli_fetch_assoc($q);

    $totalOrders = (int)($r['total'] ?? 0);
}


/* العملاء */

$q = @mysqli_query(
    $con,
    "SELECT COUNT(*) AS total FROM users"
);

if ($q) {

    $r = mysqli_fetch_assoc($q);

    $totalCustomers = (int)($r['total'] ?? 0);
}


/* مزودو الخدمة / السائقون */

$q = @mysqli_query(
    $con,
    "SELECT COUNT(*) AS total FROM drivers"
);

if ($q) {

    $r = mysqli_fetch_assoc($q);

    $totalDrivers = (int)($r['total'] ?? 0);
}


/* الطلبات المكتملة */

$q = @mysqli_query(
    $con,
    "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'done'
    "
);

if ($q) {

    $r = mysqli_fetch_assoc($q);

    $totalCompleted =
        (int)($r['total'] ?? 0);
}


/* =========================================================
   الخدمات
========================================================= */

/*
   نحاول قراءة الخدمات من الجداول الموجودة.
   إذا لم توجد الجداول، نستخدم الخدمات الافتراضية.
*/

$services = [];


/* ---------------------------------------------------------
   محاولة جدول services
--------------------------------------------------------- */

$servicesTableExists = false;

$tableCheck = @mysqli_query(
    $con,
    "SHOW TABLES LIKE 'services'"
);

if (
    $tableCheck &&
    mysqli_num_rows($tableCheck) > 0
) {
    $servicesTableExists = true;
}


/* ---------------------------------------------------------
   قراءة الخدمات
--------------------------------------------------------- */

if ($servicesTableExists) {

    /*
       نستخدم SELECT * حتى لا نعتمد
       على أسماء أعمدة كثيرة.
    */

    $serviceQuery = @mysqli_query(
        $con,
        "SELECT * FROM services ORDER BY id ASC"
    );

    if ($serviceQuery) {

        while (
            $serviceRow =
            mysqli_fetch_assoc($serviceQuery)
        ) {

            $serviceName =
                $serviceRow['name']
                ?? $serviceRow['title']
                ?? $serviceRow['service_name']
                ?? '';

            $serviceDescription =
                $serviceRow['description']
                ?? $serviceRow['details']
                ?? '';

            if (trim($serviceName) === '') {
                continue;
            }

            $services[] = [
                'name' =>
                    $serviceName,

                'description' =>
                    $serviceDescription,

                'icon' =>
                    $serviceRow['icon']
                    ?? 'fa-truck'
            ];
        }
    }
}


/* =========================================================
   الخدمات الافتراضية
========================================================= */

if (empty($services)) {

    if ($isArabic) {

        $services = [

            [
                'name' =>
                    'نقل المركبات',

                'description' =>
                    'خدمة نقل المركبات بسهولة وأمان داخل المدن والمناطق.',

                'icon' =>
                    'fa-truck'
            ],

            [
                'name' =>
                    'المساعدة على الطريق',

                'description' =>
                    'مساعدة سريعة عند الأعطال والحالات الطارئة على الطريق.',

                'icon' =>
                    'fa-road'
            ],

            [
                'name' =>
                    'النقل بين المدن',

                'description' =>
                    'نقل المركبات والأفراد بين المدن بطريقة منظمة وآمنة.',

                'icon' =>
                    'fa-route'
            ],

            [
                'name' =>
                    'خدمات السحب',

                'description' =>
                    'خدمة سحب ونقل المركبات عند الحاجة.',

                'icon' =>
                    'fa-car-burst'
            ],

            [
                'name' =>
                    'خدمات الأسطول',

                'description' =>
                    'حلول متكاملة لإدارة ومتابعة المركبات والأسطول.',

                'icon' =>
                    'fa-car'
            ],

            [
                'name' =>
                    'متابعة الطلبات',

                'description' =>
                    'تابع حالة طلبك خطوة بخطوة من خلال حسابك.',

                'icon' =>
                    'fa-location-dot'
            ]

        ];

    } else {

        $services = [

            [
                'name' =>
                    'Vehicle Transport',

                'description' =>
                    'Safe and reliable vehicle transportation inside cities and regions.',

                'icon' =>
                    'fa-truck'
            ],

            [
                'name' =>
                    'Roadside Assistance',

                'description' =>
                    'Fast assistance for breakdowns and roadside emergencies.',

                'icon' =>
                    'fa-road'
            ],

            [
                'name' =>
                    'Intercity Transport',

                'description' =>
                    'Organized and secure vehicle transportation between cities.',

                'icon' =>
                    'fa-route'
            ],

            [
                'name' =>
                    'Towing Services',

                'description' =>
                    'Professional towing and vehicle recovery services.',

                'icon' =>
                    'fa-car-burst'
            ],

            [
                'name' =>
                    'Fleet Services',

                'description' =>
                    'Integrated solutions for vehicle and fleet management.',

                'icon' =>
                    'fa-car'
            ],

            [
                'name' =>
                    'Order Tracking',

                'description' =>
                    'Track your service request step by step from your account.',

                'icon' =>
                    'fa-location-dot'
            ]

        ];
    }
}


/* =========================================================
   النصوص
========================================================= */

if ($isArabic) {

    $T = [

        'welcome' =>
            'مرحبًا بك في',

        'heroTitle' =>
            'منصة الشرق الذكية للخدمات وإدارة الأسطول',

        'heroText' =>
            'منصة رقمية متكاملة تساعدك على طلب خدمات النقل والمساعدة على الطريق ومتابعة طلباتك بسهولة وأمان.',

        'start' =>
            'اطلب خدمتك الآن',

        'login' =>
            'تسجيل الدخول',

        'register' =>
            'إنشاء حساب',

        'servicesTitle' =>
            'خدماتنا',

        'servicesText' =>
            'حلول وخدمات متكاملة مصممة لتوفير تجربة سهلة وسريعة لعملائنا.',

        'howTitle' =>
            'كيف تعمل المنصة؟',

        'howText' =>
            'احصل على الخدمة التي تحتاجها خلال خطوات بسيطة.',

        'step1Title' =>
            'اختر الخدمة',

        'step1Text' =>
            'حدد الخدمة المناسبة لاحتياجاتك.',

        'step2Title' =>
            'أرسل طلبك',

        'step2Text' =>
            'أدخل تفاصيل الطلب والموقع والبيانات المطلوبة.',

        'step3Title' =>
            'تابع الطلب',

        'step3Text' =>
            'تابع حالة طلبك حتى اكتمال الخدمة.',

        'whyTitle' =>
            'لماذا منصة الشرق؟',

        'whyText' =>
            'نقدم لك تجربة رقمية سهلة وموثوقة لإدارة طلبات الخدمات.',

        'feature1Title' =>
            'سرعة الاستجابة',

        'feature1Text' =>
            'إرسال الطلب ومتابعته بسهولة وسرعة.',

        'feature2Title' =>
            'أمان وموثوقية',

        'feature2Text' =>
            'نظام متكامل لإدارة ومتابعة الطلبات.',

        'feature3Title' =>
            'متابعة مباشرة',

        'feature3Text' =>
            'تعرف على حالة طلبك في كل مرحلة.',

        'feature4Title' =>
            'خدمات متنوعة',

        'feature4Text' =>
            'مجموعة واسعة من خدمات النقل والمساعدة.',

        'statsOrders' =>
            'إجمالي الطلبات',

        'statsCustomers' =>
            'العملاء',

        'statsDrivers' =>
            'مزودو الخدمة',

        'statsCompleted' =>
            'طلبات مكتملة',

        'ctaTitle' =>
            'هل تحتاج إلى خدمة الآن؟',

        'ctaText' =>
            'ابدأ طلبك الآن واستمتع بتجربة سهلة وسريعة.',

        'ctaButton' =>
            'ابدأ الآن',

        'about' =>
            'من نحن',

        'contact' =>
            'تواصل معنا',

        'privacy' =>
            'سياسة الخصوصية',

        'terms' =>
            'الشروط والأحكام',

        'copyright' =>
            'جميع الحقوق محفوظة',

        'language' =>
            'English'

    ];

} else {

    $T = [

        'welcome' =>
            'Welcome to',

        'heroTitle' =>
            'AlSharq Smart Platform for Services & Fleet Management',

        'heroText' =>
            'An integrated digital platform for transportation, roadside assistance and easy order tracking.',

        'start' =>
            'Request a Service',

        'login' =>
            'Login',

        'register' =>
            'Create Account',

        'servicesTitle' =>
            'Our Services',

        'servicesText' =>
            'Integrated services designed to provide our customers with a fast and seamless experience.',

        'howTitle' =>
            'How It Works',

        'howText' =>
            'Get the service you need in just a few simple steps.',

        'step1Title' =>
            'Choose a Service',

        'step1Text' =>
            'Select the service that fits your needs.',

        'step2Title' =>
            'Submit Your Request',

        'step2Text' =>
            'Enter your request details and required information.',

        'step3Title' =>
            'Track Your Request',

        'step3Text' =>
            'Follow your request until the service is completed.',

        'whyTitle' =>
            'Why AlSharqPlatform?',

        'whyText' =>
            'We provide a simple and reliable digital experience for managing service requests.',

        'feature1Title' =>
            'Fast Response',

        'feature1Text' =>
            'Submit and manage your request quickly and easily.',

        'feature2Title' =>
            'Secure & Reliable',

        'feature2Text' =>
            'An integrated system for managing and tracking requests.',

        'feature3Title' =>
            'Live Tracking',

        'feature3Text' =>
            'Know the status of your request at every stage.',

        'feature4Title' =>
            'Multiple Services',

        'feature4Text' =>
            'A wide range of transportation and assistance services.',

        'statsOrders' =>
            'Total Orders',

        'statsCustomers' =>
            'Customers',

        'statsDrivers' =>
            'Service Providers',

        'statsCompleted' =>
            'Completed Orders',

        'ctaTitle' =>
            'Need a Service Now?',

        'ctaText' =>
            'Start your request and enjoy a simple and fast experience.',

        'ctaButton' =>
            'Get Started',

        'about' =>
            'About Us',

        'contact' =>
            'Contact Us',

        'privacy' =>
            'Privacy Policy',

        'terms' =>
            'Terms & Conditions',

        'copyright' =>
            'All Rights Reserved',

        'language' =>
            'العربية'

    ];
}


/* =========================================================
   روابط
========================================================= */

$loginUrl =
    'user/login.php?lang=' .
    urlencode($lang);

$registerUrl =
    'request_service.php?lang=' .
    urlencode($lang);


/* =========================================================
   اسم الصفحة
========================================================= */

$pageTitle =
    $isArabic
    ? 'مرحبًا بك في ' . $systemName
    : 'Welcome to ' . $systemName;

?>
<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
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
    content="<?= htmlspecialchars($T['heroText']) ?>"
>

<title>
<?= htmlspecialchars($pageTitle) ?>
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
   ROOT
========================================================= */

:root {

    --primary: #0d6efd;

    --primary-dark: #084298;

    --secondary: #0f172a;

    --text: #1e293b;

    --muted: #64748b;

    --light: #f8fafc;

    --white: #ffffff;

    --border: #e2e8f0;

    --shadow:
        0 15px 40px rgba(15, 23, 42, .08);

    --radius: 22px;
}


/* =========================================================
   BODY
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    font-family:
        'Cairo',
        sans-serif;

    color: var(--text);

    background:
        #ffffff;

    transition:
        background .3s,
        color .3s;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar-custom {

    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(255,255,255,.92);

    backdrop-filter:
        blur(15px);

    border-bottom:
        1px solid rgba(226,232,240,.7);
}


.navbar-brand {

    display: flex;

    align-items: center;

    gap: 12px;

    font-weight: 800;

    color:
        var(--secondary);

    text-decoration: none;
}


.logo-box {

    width: 48px;

    height: 48px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    font-size: 22px;

    box-shadow:
        0 8px 20px
        rgba(13,110,253,.25);
}


.logo-box img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    background: white;
}


.brand-name {

    font-size: 17px;

    line-height: 1.2;
}


.brand-sub {

    display: block;

    color:
        var(--muted);

    font-size: 10px;

    font-weight: 500;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    position: relative;

    overflow: hidden;

    min-height:
        calc(100vh - 73px);

    display: flex;

    align-items: center;

    background:
        linear-gradient(
            135deg,
            #eff6ff 0%,
            #ffffff 48%,
            #eef6ff 100%
        );
}


.hero::before {

    content: '';

    position: absolute;

    width: 550px;

    height: 550px;

    border-radius: 50%;

    background:
        rgba(13,110,253,.08);

    top: -220px;

    right: -160px;

    filter:
        blur(2px);
}


.hero::after {

    content: '';

    position: absolute;

    width: 400px;

    height: 400px;

    border-radius: 50%;

    background:
        rgba(14,165,233,.07);

    bottom: -200px;

    left: -100px;
}


.hero-content {

    position: relative;

    z-index: 2;
}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        8px 15px;

    border-radius: 50px;

    background:
        rgba(13,110,253,.09);

    color:
        var(--primary);

    font-weight: 700;

    font-size: 13px;

    margin-bottom: 20px;
}


.hero-title {

    font-size:
        clamp(35px, 5vw, 64px);

    line-height: 1.25;

    font-weight: 900;

    color:
        var(--secondary);

    margin-bottom: 20px;
}


.hero-title span {

    color:
        var(--primary);
}


.hero-text {

    font-size: 18px;

    line-height: 2;

    color:
        var(--muted);

    max-width: 680px;

    margin-bottom: 32px;
}


.hero-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 12px;
}


.btn-main {

    min-height: 52px;

    padding:
        12px 25px;

    border-radius: 14px;

    border: none;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    font-weight: 800;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    box-shadow:
        0 10px 25px
        rgba(13,110,253,.25);

    transition:
        .25s;
}


.btn-main:hover {

    color: white;

    transform:
        translateY(-3px);

    box-shadow:
        0 15px 30px
        rgba(13,110,253,.35);
}


.btn-outline-main {

    min-height: 52px;

    padding:
        12px 25px;

    border-radius: 14px;

    border:
        1px solid var(--border);

    background:
        white;

    color:
        var(--secondary);

    font-weight: 700;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    transition:
        .25s;
}


.btn-outline-main:hover {

    border-color:
        var(--primary);

    color:
        var(--primary);

    transform:
        translateY(-3px);
}


/* =========================================================
   HERO VISUAL
========================================================= */

.hero-visual {

    position: relative;

    z-index: 2;

    display: flex;

    justify-content: center;

    align-items: center;
}


.hero-card {

    position: relative;

    width:
        min(430px, 90vw);

    min-height: 430px;

    border-radius: 40px;

    background:
        linear-gradient(
            145deg,
            #0d6efd,
            #084298
        );

    box-shadow:
        0 35px 70px
        rgba(13,110,253,.28);

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}


.hero-card::before {

    content: '';

    position: absolute;

    width: 320px;

    height: 320px;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.25);
}


.hero-card::after {

    content: '';

    position: absolute;

    width: 230px;

    height: 230px;

    border-radius: 50%;

    border:
        1px solid
        rgba(255,255,255,.2);
}


.hero-icon {

    position: relative;

    z-index: 2;

    width: 145px;

    height: 145px;

    border-radius: 38px;

    background:
        rgba(255,255,255,.15);

    border:
        1px solid
        rgba(255,255,255,.25);

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    font-size: 70px;

    backdrop-filter:
        blur(10px);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.15);
}


.floating-card {

    position: absolute;

    z-index: 4;

    background:
        white;

    border-radius: 18px;

    padding:
        14px 17px;

    display: flex;

    align-items: center;

    gap: 12px;

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.13);

    font-weight: 700;

    animation:
        float 4s ease-in-out infinite;
}


.floating-card i {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    background:
        #eff6ff;

    color:
        var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;
}


.float-one {

    top: 55px;

    right: -30px;
}


.float-two {

    bottom: 60px;

    left: -30px;

    animation-delay:
        1.2s;
}


@keyframes float {

    0%,100% {
        transform:
            translateY(0);
    }

    50% {
        transform:
            translateY(-10px);
    }
}


/* =========================================================
   SECTIONS
========================================================= */

.section {

    padding:
        90px 0;
}


.section-light {

    background:
        var(--light);
}


.section-heading {

    max-width: 750px;

    margin:
        0 auto 50px;

    text-align: center;
}


.section-heading h2 {

    font-size:
        clamp(28px,4vw,42px);

    font-weight: 900;

    margin-bottom: 14px;
}


.section-heading p {

    color:
        var(--muted);

    line-height: 1.9;

    margin: 0;
}


/* =========================================================
   SERVICES
========================================================= */

.service-card {

    height: 100%;

    padding: 28px;

    border:
        1px solid var(--border);

    border-radius:
        var(--radius);

    background:
        white;

    box-shadow:
        var(--shadow);

    transition:
        .3s;

    position: relative;

    overflow: hidden;
}


.service-card::before {

    content: '';

    position: absolute;

    top: 0;

    right: 0;

    width: 70px;

    height: 70px;

    border-radius:
        0 0 0 70px;

    background:
        rgba(13,110,253,.06);
}


.service-card:hover {

    transform:
        translateY(-8px);

    border-color:
        rgba(13,110,253,.25);

    box-shadow:
        0 25px 50px
        rgba(15,23,42,.12);
}


.service-icon {

    width: 62px;

    height: 62px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #dbeafe
        );

    color:
        var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 26px;

    margin-bottom: 20px;
}


.service-card h4 {

    font-size: 19px;

    font-weight: 800;

    margin-bottom: 10px;
}


.service-card p {

    color:
        var(--muted);

    line-height: 1.9;

    font-size: 14px;

    margin: 0;
}


/* =========================================================
   STEPS
========================================================= */

.step-card {

    text-align: center;

    padding:
        25px;
}


.step-number {

    width: 70px;

    height: 70px;

    margin:
        0 auto 20px;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    font-size: 25px;

    font-weight: 900;

    display: flex;

    align-items: center;

    justify-content: center;

    box-shadow:
        0 12px 25px
        rgba(13,110,253,.22);
}


.step-card h4 {

    font-weight: 800;

    margin-bottom: 10px;
}


.step-card p {

    color:
        var(--muted);

    line-height: 1.9;

    font-size: 14px;
}


/* =========================================================
   FEATURES
========================================================= */

.feature-box {

    display: flex;

    gap: 18px;

    padding: 20px;

    border-radius: 18px;

    background:
        white;

    border:
        1px solid var(--border);

    height: 100%;

    transition:
        .25s;
}


.feature-box:hover {

    transform:
        translateY(-5px);

    box-shadow:
        var(--shadow);
}


.feature-icon {

    flex:
        0 0 52px;

    width: 52px;

    height: 52px;

    border-radius: 15px;

    background:
        #eff6ff;

    color:
        var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;
}


.feature-box h5 {

    font-weight: 800;

    margin-bottom: 5px;
}


.feature-box p {

    color:
        var(--muted);

    font-size: 13px;

    line-height: 1.8;

    margin: 0;
}


/* =========================================================
   STATS
========================================================= */

.stats-section {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #172554
        );

    color:
        white;
}


.stat-box {

    text-align: center;

    padding: 20px;
}


.stat-icon {

    font-size: 28px;

    margin-bottom: 10px;

    opacity: .9;
}


.stat-number {

    font-size:
        clamp(30px,4vw,45px);

    font-weight: 900;
}


.stat-title {

    color:
        rgba(255,255,255,.7);

    font-size: 14px;
}


/* =========================================================
   CTA
========================================================= */

.cta {

    padding:
        70px 30px;

    border-radius:
        32px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:
        white;

    text-align: center;

    box-shadow:
        0 30px 60px
        rgba(13,110,253,.2);
}


.cta h2 {

    font-size:
        clamp(28px,4vw,42px);

    font-weight: 900;

    margin-bottom: 15px;
}


.cta p {

    color:
        rgba(255,255,255,.8);

    max-width: 650px;

    margin:
        0 auto 25px;

    line-height: 1.9;
}


.btn-white {

    background:
        white;

    color:
        #084298;

    padding:
        13px 27px;

    border-radius: 14px;

    font-weight: 800;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    gap: 9px;

    transition:
        .25s;
}


.btn-white:hover {

    color:
        #084298;

    transform:
        translateY(-3px);
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    background:
        #0b1120;

    color:
        white;

    padding:
        50px 0 25px;
}


.footer-brand {

    font-size: 20px;

    font-weight: 900;

    margin-bottom: 12px;
}


.footer-text {

    color:
        #94a3b8;

    font-size: 13px;

    line-height: 1.9;

    max-width: 450px;
}


.footer-title {

    font-weight: 800;

    margin-bottom: 15px;
}


.footer-link {

    display: block;

    color:
        #94a3b8;

    text-decoration: none;

    margin-bottom: 8px;

    font-size: 13px;

    transition:
        .2s;
}


.footer-link:hover {

    color:
        white;

    transform:
        translateX(
            <?= $isArabic ? '-3px' : '3px' ?>
        );
}


.footer-bottom {

    border-top:
        1px solid
        rgba(255,255,255,.1);

    margin-top: 35px;

    padding-top: 20px;

    color:
        #64748b;

    font-size: 12px;

    text-align: center;
}


/* =========================================================
   DARK MODE
========================================================= */

body.dark-mode {

    --text: #e5e7eb;

    --muted: #94a3b8;

    --light: #111827;

    --white: #1f2937;

    --border: #334155;

    background:
        #0f172a;

    color:
        #e5e7eb;
}


body.dark-mode .navbar-custom {

    background:
        rgba(15,23,42,.92);

    border-color:
        #1e293b;
}


body.dark-mode .navbar-brand {

    color:
        white;
}


body.dark-mode .hero {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #111827,
            #172554
        );
}


body.dark-mode .hero-title {

    color:
        white;
}


body.dark-mode .btn-outline-main {

    background:
        #1e293b;

    color:
        white;

    border-color:
        #334155;
}


body.dark-mode .service-card,
body.dark-mode .feature-box {

    background:
        #1e293b;

    border-color:
        #334155;
}


body.dark-mode .section-light {

    background:
        #111827;
}


body.dark-mode .floating-card {

    background:
        #1e293b;

    color:
        white;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:991px) {

    .hero {

        padding:
            70px 0;

        min-height:
            auto;
    }

    .hero-visual {

        margin-top:
            50px;
    }

    .float-one {

        right:
            0;
    }

    .float-two {

        left:
            0;
    }
}


@media(max-width:576px) {

    .section {

        padding:
            65px 0;
    }

    .hero-title {

        font-size:
            34px;
    }

    .hero-text {

        font-size:
            15px;
    }

    .hero-buttons {

        flex-direction:
            column;
    }

    .hero-buttons a {

        width:
            100%;
    }

    .hero-card {

        min-height:
            330px;

        border-radius:
            30px;
    }

    .hero-icon {

        width:
            110px;

        height:
            110px;

        font-size:
            52px;
    }

    .floating-card {

        font-size:
            11px;
    }

    .float-one {

        top:
            20px;
    }

    .float-two {

        bottom:
            20px;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg navbar-custom">

<div class="container py-2">


<a
    href="welcome.php?lang=<?= urlencode($lang) ?>"
    class="navbar-brand"
>

<div class="logo-box">

<?php if ($logoPath !== ''): ?>

<img
    src="<?= htmlspecialchars($logoPath) ?>"
    alt="<?= htmlspecialchars($systemName) ?>"
>

<?php else: ?>

<i class="fa-solid fa-truck-fast"></i>

<?php endif; ?>

</div>


<div>

<div class="brand-name">

<?= htmlspecialchars($systemName) ?>

</div>

<span class="brand-sub">

<?= $isArabic
    ? 'الخدمات وإدارة الأسطول'
    : 'Services & Fleet Management'
?>

</span>

</div>

</a>


<button
    class="navbar-toggler"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#mainNavbar"
>

<span class="navbar-toggler-icon"></span>

</button>


<div
    class="collapse navbar-collapse"
    id="mainNavbar"
>


<ul class="navbar-nav mx-auto mb-2 mb-lg-0">

<li class="nav-item">

<a
    class="nav-link"
    href="#services"
>
<?= $T['servicesTitle'] ?>
</a>

</li>


<li class="nav-item">

<a
    class="nav-link"
    href="#how"
>
<?= $T['howTitle'] ?>
</a>

</li>


<li class="nav-item">

<a
    class="nav-link"
    href="#why"
>
<?= $T['whyTitle'] ?>
</a>

</li>


<li class="nav-item">

<a
    class="nav-link"
    href="#contact"
>
<?= $T['contact'] ?>
</a>

</li>

</ul>


<div class="d-flex gap-2 align-items-center">


<a
    href="?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"
    class="btn btn-sm btn-outline-secondary"
>
<i class="fa-solid fa-language"></i>
<?= $T['language'] ?>
</a>


<button
    type="button"
    id="darkModeBtn"
    class="btn btn-sm btn-outline-secondary"
    onclick="toggleDarkMode()"
    title="Dark Mode"
>
<i class="fa-solid fa-moon"></i>
</button>


<a
    href="<?= htmlspecialchars($loginUrl) ?>"
    class="btn btn-sm btn-outline-primary px-3"
>
<?= $T['login'] ?>
</a>


<a
    href="<?= htmlspecialchars($registerUrl) ?>"
    class="btn btn-sm btn-primary px-3"
>
<?= $T['register'] ?>
</a>

</div>

</div>

</div>

</nav>


<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

<div class="container">

<div class="row align-items-center g-5">


<div class="col-lg-7">

<div class="hero-content">


<div class="hero-badge">

<i class="fa-solid fa-sparkles"></i>

<?= $T['welcome'] ?>

<?= htmlspecialchars($systemName) ?>

</div>


<h1 class="hero-title">

<?= $isArabic
    ? 'خدماتك أصبحت'
    : 'Your Services Are'
?>

<span>

<?= $isArabic
    ? 'أسهل وأسرع'
    : 'Easier & Faster'
?>

</span>

<br>

<?= $isArabic
    ? 'مع منصة الشرق الذكية'
    : 'with AlSharqPlatform'
?>

</h1>


<p class="hero-text">

<?= htmlspecialchars(
    $T['heroText']
) ?>

</p>


<div class="hero-buttons">


<a
    href="<?= htmlspecialchars($registerUrl) ?>"
    class="btn-main"
>

<i class="fa-solid fa-rocket"></i>

<?= $T['start'] ?>

</a>


<a
    href="<?= htmlspecialchars($loginUrl) ?>"
    class="btn-outline-main"
>

<i class="fa-solid fa-right-to-bracket"></i>

<?= $T['login'] ?>

</a>

</div>


</div>

</div>


<div class="col-lg-5">

<div class="hero-visual">


<div class="hero-card">


<div class="hero-icon">

<i class="fa-solid fa-truck-fast"></i>

</div>


<div class="floating-card float-one">

<i class="fa-solid fa-circle-check"></i>

<span>

<?= $isArabic
    ? 'خدمة موثوقة'
    : 'Reliable Service'
?>

</span>

</div>


<div class="floating-card float-two">

<i class="fa-solid fa-location-dot"></i>

<span>

<?= $isArabic
    ? 'متابعة الطلب'
    : 'Order Tracking'
?>

</span>

</div>


</div>

</div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     SERVICES
========================================================= -->

<section
    class="section"
    id="services"
>

<div class="container">


<div class="section-heading">

<h2>

<?= $T['servicesTitle'] ?>

</h2>

<p>

<?= $T['servicesText'] ?>

</p>

</div>


<div class="row g-4">


<?php foreach ($services as $service): ?>

<div class="col-xl-4 col-md-6">

<div class="service-card">


<div class="service-icon">

<i class="fa-solid <?= htmlspecialchars(
    $service['icon']
) ?>"></i>

</div>


<h4>

<?= htmlspecialchars(
    $service['name']
) ?>

</h4>


<p>

<?= htmlspecialchars(
    $service['description']
) ?>

</p>


</div>

</div>

<?php endforeach; ?>


</div>

</div>

</section>


<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
    class="section section-light"
    id="how"
>

<div class="container">


<div class="section-heading">

<h2>

<?= $T['howTitle'] ?>

</h2>

<p>

<?= $T['howText'] ?>

</p>

</div>


<div class="row g-4">


<div class="col-lg-4">

<div class="step-card">

<div class="step-number">
1
</div>

<h4>
<?= $T['step1Title'] ?>
</h4>

<p>
<?= $T['step1Text'] ?>
</p>

</div>

</div>


<div class="col-lg-4">

<div class="step-card">

<div class="step-number">
2
</div>

<h4>
<?= $T['step2Title'] ?>
</h4>

<p>
<?= $T['step2Text'] ?>
</p>

</div>

</div>


<div class="col-lg-4">

<div class="step-card">

<div class="step-number">
3
</div>

<h4>
<?= $T['step3Title'] ?>
</h4>

<p>
<?= $T['step3Text'] ?>
</p>

</div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     FEATURES
========================================================= -->

<section
    class="section"
    id="why"
>

<div class="container">


<div class="section-heading">

<h2>

<?= $T['whyTitle'] ?>

</h2>

<p>

<?= $T['whyText'] ?>

</p>

</div>


<div class="row g-4">


<div class="col-md-6">

<div class="feature-box">

<div class="feature-icon">

<i class="fa-solid fa-bolt"></i>

</div>

<div>

<h5>
<?= $T['feature1Title'] ?>
</h5>

<p>
<?= $T['feature1Text'] ?>
</p>

</div>

</div>

</div>


<div class="col-md-6">

<div class="feature-box">

<div class="feature-icon">

<i class="fa-solid fa-shield-halved"></i>

</div>

<div>

<h5>
<?= $T['feature2Title'] ?>
</h5>

<p>
<?= $T['feature2Text'] ?>
</p>

</div>

</div>

</div>


<div class="col-md-6">

<div class="feature-box">

<div class="feature-icon">

<i class="fa-solid fa-location-crosshairs"></i>

</div>

<div>

<h5>
<?= $T['feature3Title'] ?>
</h5>

<p>
<?= $T['feature3Text'] ?>
</p>

</div>

</div>

</div>


<div class="col-md-6">

<div class="feature-box">

<div class="feature-icon">

<i class="fa-solid fa-layer-group"></i>

</div>

<div>

<h5>
<?= $T['feature4Title'] ?>
</h5>

<p>
<?= $T['feature4Text'] ?>
</p>

</div>

</div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     STATISTICS
========================================================= -->

<section class="section stats-section">

<div class="container">

<div class="row g-4">


<div class="col-md-3 col-6">

<div class="stat-box">

<div class="stat-icon">

<i class="fa-solid fa-box"></i>

</div>

<div
    class="stat-number"
    data-count="<?= $totalOrders ?>"
>
0
</div>

<div class="stat-title">

<?= $T['statsOrders'] ?>

</div>

</div>

</div>


<div class="col-md-3 col-6">

<div class="stat-box">

<div class="stat-icon">

<i class="fa-solid fa-users"></i>

</div>

<div
    class="stat-number"
    data-count="<?= $totalCustomers ?>"
>
0
</div>

<div class="stat-title">

<?= $T['statsCustomers'] ?>

</div>

</div>

</div>


<div class="col-md-3 col-6">

<div class="stat-box">

<div class="stat-icon">

<i class="fa-solid fa-truck"></i>

</div>

<div
    class="stat-number"
    data-count="<?= $totalDrivers ?>"
>
0
</div>

<div class="stat-title">

<?= $T['statsDrivers'] ?>

</div>

</div>

</div>


<div class="col-md-3 col-6">

<div class="stat-box">

<div class="stat-icon">

<i class="fa-solid fa-circle-check"></i>

</div>

<div
    class="stat-number"
    data-count="<?= $totalCompleted ?>"
>
0
</div>

<div class="stat-title">

<?= $T['statsCompleted'] ?>

</div>

</div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="section">

<div class="container">


<div class="cta">


<h2>

<?= $T['ctaTitle'] ?>

</h2>


<p>

<?= $T['ctaText'] ?>

</p>


<a
    href="<?= htmlspecialchars($registerUrl) ?>"
    class="btn-white"
>

<i class="fa-solid fa-arrow-left"></i>

<?= $T['ctaButton'] ?>

</a>


</div>

</div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer id="contact">

<div class="container">

<div class="row g-5">


<div class="col-lg-5">

<div class="footer-brand">

<?= htmlspecialchars(
    $companyName
) ?>

</div>


<div class="footer-text">

<?= htmlspecialchars(
    $T['heroText']
) ?>

</div>

</div>


<div class="col-md-3">

<div class="footer-title">

<?= $T['servicesTitle'] ?>

</div>


<a
    href="#services"
    class="footer-link"
>
<?= $T['servicesTitle'] ?>
</a>


<a
    href="#how"
    class="footer-link"
>
<?= $T['howTitle'] ?>
</a>


<a
    href="#why"
    class="footer-link"
>
<?= $T['whyTitle'] ?>
</a>

</div>


<div class="col-md-4">

<div class="footer-title">

<?= $T['contact'] ?>

</div>


<?php if ($phone !== ''): ?>

<a
    href="tel:<?= htmlspecialchars($phone) ?>"
    class="footer-link"
>

<i class="fa-solid fa-phone"></i>

<?= htmlspecialchars($phone) ?>

</a>

<?php endif; ?>


<?php if ($email !== ''): ?>

<a
    href="mailto:<?= htmlspecialchars($email) ?>"
    class="footer-link"
>

<i class="fa-solid fa-envelope"></i>

<?= htmlspecialchars($email) ?>

</a>

<?php endif; ?>


<?php if ($address !== ''): ?>

<div class="footer-link">

<i class="fa-solid fa-location-dot"></i>

<?= htmlspecialchars($address) ?>

</div>

<?php endif; ?>

</div>


</div>


<div class="footer-bottom">

© <?= date('Y') ?>

<?= htmlspecialchars(
    $companyName
) ?>

—

<?= $T['copyright'] ?>

</div>


</div>

</footer>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   DARK MODE
========================================================= */

function toggleDarkMode() {

    document.body.classList.toggle(
        'dark-mode'
    );

    const enabled =
        document.body.classList.contains(
            'dark-mode'
        );

    localStorage.setItem(
        'welcomeDarkMode',
        enabled
            ? 'true'
            : 'false'
    );
}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (
            localStorage.getItem(
                'welcomeDarkMode'
            ) === 'true'
        ) {

            document.body.classList.add(
                'dark-mode'
            );
        }


        /* =================================================
           Animated Statistics
        ================================================= */

        const counters =
            document.querySelectorAll(
                '.stat-number'
            );


        counters.forEach(
            function (counter) {

                const target =
                    parseInt(
                        counter.dataset.count
                    ) || 0;


                if (target === 0) {

                    counter.textContent = '0';

                    return;
                }


                let current = 0;

                const duration = 1200;

                const startTime =
                    performance.now();


                function animate(
                    currentTime
                ) {

                    const progress =
                        Math.min(
                            (
                                currentTime -
                                startTime
                            ) / duration,
                            1
                        );


                    current =
                        Math.floor(
                            progress *
                            target
                        );


                    counter.textContent =
                        current.toLocaleString(
                            '<?= $lang ?>'
                        );


                    if (progress < 1) {

                        requestAnimationFrame(
                            animate
                        );

                    } else {

                        counter.textContent =
                            target.toLocaleString(
                                '<?= $lang ?>'
                            );
                    }
                }


                requestAnimationFrame(
                    animate
                );
            }
        );

    }
);


/* =========================================================
   Smooth Navigation
========================================================= */

document.querySelectorAll(
    'a[href^="#"]'
).forEach(
    function (link) {

        link.addEventListener(
            'click',
            function (e) {

                const target =
                    document.querySelector(
                        this.getAttribute(
                            'href'
                        )
                    );


                if (target) {

                    e.preventDefault();

                    target.scrollIntoView({
                        behavior:
                            'smooth',
                        block:
                            'start'
                    });
                }

            }
        );

    }
);

</script>


</body>

</html>