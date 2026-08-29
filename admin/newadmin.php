<?php
session_start();

include('../include/core.php');
include('../include/connected.php');

/* =========================================================
   🔒 تسجيل الدخول
========================================================= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: welcome.php");
    exit();
}

$admin_id = (int) $_SESSION['admin_id'];

/* =========================================================
   🌍 اللغة
========================================================= */
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$_SESSION['lang'] = $lang;

$direction = ($lang === 'ar') ? 'rtl' : 'ltr';

/* =========================================================
   👤 اسم المستخدم
========================================================= */
$name = "Admin";

$stmt = $con->prepare("
    SELECT name
    FROM admin
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (!empty($row['name'])) {
            $name = $row['name'];
        }
    }

    $stmt->close();
}

/* =========================================================
   📊 الإحصائيات
========================================================= */

/* 🚗 المركبات */
$fleetCount = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
");

if ($result) {
    $fleetCount = (int)$result->fetch_assoc()['total'];
}


/* 🚚 السائقين */
$driversCount = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
");

if ($result) {
    $driversCount = (int)$result->fetch_assoc()['total'];
}


/* 🔧 الصيانة */
$maintenanceCount = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM maintenance
");

if ($result) {
    $maintenanceCount = (int)$result->fetch_assoc()['total'];
}


/* 🔔 الإشعارات */
$notifCount = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE is_read = 0
");

if ($result) {
    $notifCount = (int)$result->fetch_assoc()['total'];
}


/* ⚠️ الفحص الدوري المنتهي */
$inspectionExpired = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
    WHERE inspection_expiry IS NOT NULL
    AND inspection_expiry <> ''
    AND inspection_expiry < CURDATE()
");

if ($result) {
    $inspectionExpired = (int)$result->fetch_assoc()['total'];
}


/* 🛡️ التأمين المنتهي */
$insuranceExpired = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
    WHERE insurance_expiration_date IS NOT NULL
    AND insurance_expiration_date <> ''
    AND insurance_expiration_date < CURDATE()
");

if ($result) {
    $insuranceExpired = (int)$result->fetch_assoc()['total'];
}


/* 📄 تشغيل المركبة المنتهي */
$operationExpired = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
    WHERE operation_expiry IS NOT NULL
    AND operation_expiry <> ''
    AND operation_expiry < CURDATE()
");

if ($result) {
    $operationExpired = (int)$result->fetch_assoc()['total'];
}


/* 🪪 إقامات السائقين المنتهية */
$iqamaExpired = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
    WHERE iqama_expiry_date IS NOT NULL
    AND iqama_expiry_date <> ''
    AND iqama_expiry_date < CURDATE()
");

if ($result) {
    $iqamaExpired = (int)$result->fetch_assoc()['total'];
}


/* 🚘 رخص السائقين المنتهية */
$licenseExpired = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
    WHERE license_expiry_date IS NOT NULL
    AND license_expiry_date <> ''
    AND license_expiry_date < CURDATE()
");

if ($result) {
    $licenseExpired = (int)$result->fetch_assoc()['total'];
}


/* 🛞 الإطارات */
$tiresChanged = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM tires
");

if ($result) {
    $tiresChanged = (int)$result->fetch_assoc()['total'];
}


/* 🛢️ الزيوت */
$oilChanged = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM oil_changes
");

if ($result) {
    $oilChanged = (int)$result->fetch_assoc()['total'];
}


/* 💥 الحوادث */
$accidents = 0;

$result = $con->query("
    SELECT COUNT(*) AS total
    FROM accidents
");

if ($result) {
    $accidents = (int)$result->fetch_assoc()['total'];
}


/* =========================================================
   💰 العمولات - معلومات إضافية بسيطة
========================================================= */

$totalCommissions = 0;

$commissionTableCheck = $con->query("
    SHOW TABLES LIKE 'driver_commissions'
");

if ($commissionTableCheck && $commissionTableCheck->num_rows > 0) {

    $result = $con->query("
        SELECT COALESCE(SUM(net_commission),0) AS total
        FROM driver_commissions
    ");

    if ($result) {
        $totalCommissions = (float)$result->fetch_assoc()['total'];
    }
}


/* =========================================================
   📅 الحضور اليوم
========================================================= */

$attendanceToday = 0;

$attendanceTableCheck = $con->query("
    SHOW TABLES LIKE 'attendance'
");

if ($attendanceTableCheck && $attendanceTableCheck->num_rows > 0) {

    $result = $con->query("
        SELECT COUNT(*) AS total
        FROM attendance
        WHERE attendance_date = CURDATE()
    ");

    if ($result) {
        $attendanceToday = (int)$result->fetch_assoc()['total'];
    }
}


/* =========================================================
   🧮 إجمالي التنبيهات الحرجة
========================================================= */

$totalExpired =
    $inspectionExpired +
    $insuranceExpired +
    $operationExpired +
    $iqamaExpired +
    $licenseExpired;

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
    dir="<?= $direction ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
<?= __('Control Panel - Fleet Management System') ?>
</title>


<style>

/* =========================================================
   BASIC
========================================================= */

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{

    font-family:
        Arial,
        Tahoma,
        sans-serif;

    background:#f4f6f9;

    margin:0;

    color:#333;
}


/* =========================================================
   TOP BAR
========================================================= */

.top-bar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    padding:15px 20px;

    background:#fff;

    box-shadow:
        0 2px 10px rgba(0,0,0,.08);

    position:relative;

    z-index:1000;
}


/* =========================================================
   LANGUAGE
========================================================= */

.lang-switch{

    display:flex;

    align-items:center;

    gap:7px;
}

.lang-switch a{

    text-decoration:none;

    padding:8px 15px;

    border-radius:6px;

    background:#eee;

    color:#333;

    font-weight:bold;

    font-size:13px;

    transition:.2s;
}

.lang-switch a:hover{

    background:#ddd;
}

.lang-switch a.active{

    background:#28a745;

    color:#fff;

}


/* =========================================================
   WELCOME
========================================================= */

.welcome-user{

    font-weight:bold;

    color:#333;

    font-size:15px;
}


/* =========================================================
   HEADER ACTIONS
========================================================= */

.header-actions{

    display:flex;

    align-items:center;

    gap:15px;
}

.header-actions a{

    text-decoration:none;
}


/* =========================================================
   NOTIFICATION
========================================================= */

.notif{

    position:relative;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    width:38px;

    height:38px;

    border-radius:8px;

    background:#f5f5f5;

    font-size:20px;

    transition:.2s;
}

.notif:hover{

    background:#e9ecef;

    transform:translateY(-1px);
}

.badge{

    position:absolute;

    top:-5px;

    right:-5px;

    background:red;

    color:white;

    min-width:19px;

    height:19px;

    padding:0 5px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:10px;

    font-weight:bold;

    border:2px solid white;
}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title{

    padding:20px;

    font-size:22px;

    font-weight:bold;

    color:#333;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;
}

.page-title-main{

    display:flex;

    align-items:center;

    gap:8px;
}

.page-date{

    font-size:12px;

    color:#777;

    font-weight:normal;

    background:#fff;

    padding:8px 12px;

    border-radius:8px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.05);
}


/* =========================================================
   STATUS BAR
========================================================= */

.status-bar{

    margin:0 20px 5px;

    padding:10px 15px;

    background:#fff;

    border-radius:10px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.04);

    font-size:12px;
}

.status-ok{

    color:#198754;

    font-weight:bold;
}

.status-warning{

    color:#dc3545;

    font-weight:bold;
}


/* =========================================================
   STATS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(auto-fit,minmax(180px,1fr));

    gap:15px;

    padding:20px;
}


/* =========================================================
   STAT
========================================================= */

.stat{

    padding:18px;

    border-radius:15px;

    color:#fff;

    font-weight:bold;

    text-align:center;

    box-shadow:
        0 6px 15px rgba(0,0,0,.1);

    transition:
        transform .2s,
        box-shadow .2s;

    text-decoration:none;

    display:block;
}

.stat:hover{

    transform:translateY(-4px);

    box-shadow:
        0 10px 22px rgba(0,0,0,.16);

    color:#fff;
}

.stat span{

    display:block;

    font-size:26px;

    margin-top:8px;
}


/* =========================================================
   COLORS
========================================================= */

.green{

    background:
        linear-gradient(
            135deg,
            #28a745,
            #1e7e34
        );
}

.blue{

    background:
        linear-gradient(
            135deg,
            #007bff,
            #0056b3
        );
}

.orange{

    background:
        linear-gradient(
            135deg,
            #fd7e14,
            #e8590c
        );
}

.red{

    background:
        linear-gradient(
            135deg,
            #dc3545,
            #a71d2a
        );
}

.dark-red{

    background:
        linear-gradient(
            135deg,
            #b02a37,
            #721c24
        );
}

.purple{

    background:
        linear-gradient(
            135deg,
            #6f42c1,
            #4e2a8e
        );
}

.black{

    background:
        linear-gradient(
            135deg,
            #343a40,
            #000
        );
}


/* =========================================================
   QUICK SUMMARY
========================================================= */

.summary{

    display:grid;

    grid-template-columns:
        repeat(auto-fit,minmax(220px,1fr));

    gap:15px;

    padding:
        0 20px 20px;
}

.summary-card{

    background:#fff;

    border-radius:12px;

    padding:15px;

    box-shadow:
        0 3px 10px rgba(0,0,0,.06);

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;
}

.summary-title{

    color:#777;

    font-size:12px;

    margin-bottom:5px;
}

.summary-value{

    font-size:22px;

    font-weight:bold;

    color:#333;
}

.summary-icon{

    width:42px;

    height:42px;

    border-radius:10px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#f1f3f5;

    font-size:20px;
}


/* =========================================================
   CARDS
========================================================= */

.cards{

    display:grid;

    grid-template-columns:
        repeat(auto-fit,minmax(220px,1fr));

    gap:20px;

    padding:20px;
}

.card{

    background:#fff;

    padding:20px;

    border-radius:12px;

    text-align:center;

    box-shadow:
        0 3px 10px rgba(0,0,0,.05);

    transition:
        transform .2s,
        box-shadow .2s;
}

.card:hover{

    transform:translateY(-4px);

    box-shadow:
        0 8px 18px rgba(0,0,0,.10);
}

.card a{

    text-decoration:none;

    color:#333;

    font-weight:bold;

    display:block;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.services-title{

    margin:
        5px 20px 0;

    padding:
        15px;

    background:#fff;

    border-radius:12px 12px 0 0;

    font-weight:bold;

    color:#333;
}


/* =========================================================
   LOADER
========================================================= */

#loader{

    position:fixed;

    inset:0;

    background:#0f172a;

    display:flex;

    justify-content:center;

    align-items:center;

    z-index:999999;

    opacity:1;

    visibility:visible;

    transition:
        opacity .3s ease,
        visibility .3s ease;
}

#loader.hidden{

    opacity:0;

    visibility:hidden;

    pointer-events:none;
}

.loader-box{

    text-align:center;

    color:#fff;
}

.loader-logo{

    width:80px;

    height:80px;

    object-fit:cover;

    border-radius:15px;

    margin-bottom:18px;
}

.spinner{

    width:45px;

    height:45px;

    border:4px solid #ffffff33;

    border-top-color:#fff;

    border-radius:50%;

    margin:auto;

    animation:
        spin 1s linear infinite;
}

.loader-box p{

    font-size:13px;

    margin-top:14px;
}

@keyframes spin{

    to{
        transform:rotate(360deg);
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:800px){

    .top-bar{

        flex-wrap:wrap;

        justify-content:center;
    }

    .page-title{

        align-items:flex-start;

        flex-direction:column;
    }

    .status-bar{

        align-items:flex-start;

        flex-direction:column;
    }

}

@media(max-width:500px){

    .stats{

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        padding:12px;

        gap:10px;
    }

    .stat{

        padding:14px 8px;

        border-radius:12px;

        font-size:13px;
    }

    .stat span{

        font-size:23px;
    }

    .cards{

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        gap:10px;

        padding:12px;
    }

    .card{

        padding:15px 8px;

        font-size:12px;
    }

    .summary{

        padding:
            0 12px 12px;

        grid-template-columns:1fr;
    }

    .page-title{

        padding:15px;

        font-size:19px;
    }

    .page-date{

        font-size:11px;
    }

}


/* =========================================================
   ACCESSIBILITY
========================================================= */

a:focus-visible{

    outline:
        3px solid rgba(0,123,255,.35);

    outline-offset:3px;
}

</style>

</head>


<body>


<!-- =========================================================
     🔊 NOTIFICATION SOUND
========================================================= -->

<audio
    id="notifySound"
    preload="auto"
>

    <source
        src="../admin/sound/company_notification.wav"
        type="audio/wav"
    >

</audio>


<!-- =========================================================
     ⏳ LOADER
========================================================= -->

<div id="loader">

    <div class="loader-box">

        <img
            src="../admin/logo.jpg"
            class="loader-logo"
            alt="Logo"
            onerror="this.style.display='none';"
        >

        <div class="spinner"></div>

        <p>
            <?= $lang === 'ar'
                ? 'جاري التحميل...'
                : 'Loading...'
            ?>
        </p>

    </div>

</div>


<!-- =========================================================
     TOP BAR
========================================================= -->

<div class="top-bar">


    <!-- 🌍 LANGUAGE -->

    <div class="lang-switch">

        <a
            href="?lang=ar"
            class="<?= $lang === 'ar' ? 'active' : '' ?>"
        >
            🌍🇸🇦 عربي
        </a>

        <span>|</span>

        <a
            href="?lang=en"
            class="<?= $lang === 'en' ? 'active' : '' ?>"
        >
            🌍🇬🇧 English
        </a>

    </div>


    <!-- 👤 WELCOME -->

    <div class="welcome-user">

        👋
        <?= __('Welcome back') ?>:
        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>

    </div>


    <!-- 🔔 ACTIONS -->

    <div class="header-actions">

        <a
            href="notifications.php"
            class="notif"
            title="<?= __('Notifications') ?>"
        >

            🔔

            <?php if ($notifCount > 0): ?>

                <span class="badge">

                    <?= $notifCount > 99
                        ? '99+'
                        : $notifCount
                    ?>

                </span>

            <?php endif; ?>

        </a>


        <a
            href="settings.php"
            class="notif"
            title="<?= __('settings') ?>"
        >
            ⚙️
        </a>

    </div>

</div>


<!-- =========================================================
     PAGE TITLE
========================================================= -->

<div class="page-title">

    <div class="page-title-main">

        📊

        <?= __('Control Panel - Fleet Management System') ?>

    </div>

    <div class="page-date">

        📅 <?= date('Y-m-d') ?>

    </div>

</div>


<!-- =========================================================
     SYSTEM STATUS
========================================================= -->

<div class="status-bar">

    <div class="status-ok">

        🟢

        <?= $lang === 'ar'
            ? 'النظام يعمل بشكل طبيعي'
            : 'System is operating normally'
        ?>

    </div>


    <div class="<?= $totalExpired > 0
        ? 'status-warning'
        : 'status-ok'
    ?>">

        <?php if ($totalExpired > 0): ?>

            ⚠️

            <?= $totalExpired ?>

            <?= $lang === 'ar'
                ? 'تنبيه يحتاج إلى مراجعة'
                : 'alerts require attention'
            ?>

        <?php else: ?>

            ✓

            <?= $lang === 'ar'
                ? 'لا توجد وثائق منتهية'
                : 'No expired documents'
            ?>

        <?php endif; ?>

    </div>

</div>


<!-- =========================================================
     📊 الإحصائيات الأساسية
========================================================= -->

<div class="stats">


    <!-- 🚗 FLEET -->

    <a
        href="fleet.php"
        class="stat green"
    >

        🚗
        <?= __('company_fleet') ?>

        <span>
            <?= number_format($fleetCount) ?>
        </span>

    </a>


    <!-- 🚚 DRIVERS -->

    <a
        href="driversview.php"
        class="stat blue"
    >

        🚚
        <?= __('drivers') ?>

        <span>
            <?= number_format($driversCount) ?>
        </span>

    </a>


    <!-- 🔧 MAINTENANCE -->

    <a
        href="maintenanceview.php"
        class="stat orange"
    >

        🔧
        <?= __('maintenance_records') ?>

        <span>
            <?= number_format($maintenanceCount) ?>
        </span>

    </a>


    <!-- 🔔 NOTIFICATIONS -->

    <a
        href="notifications.php"
        class="stat red"
    >

        🔔
        <?= __('Notifications') ?>

        <span>
            <?= number_format($notifCount) ?>
        </span>

    </a>


    <!-- ⚠️ INSPECTION -->

    <a
        href="fleet.php?filter=inspection_expired"
        class="stat dark-red"
    >

        ⚠️
        <?= __('inspectionExpired') ?>

        <span>
            <?= number_format($inspectionExpired) ?>
        </span>

    </a>


    <!-- 🪪 IQAMA -->

    <a
        href="driversview.php?filter=iqama_expired"
        class="stat dark-red"
    >

        🪪
        <?= __('iqama') ?>

        <span>
            <?= number_format($iqamaExpired) ?>
        </span>

    </a>


    <!-- 🛡️ INSURANCE -->

    <a
        href="fleet.php?filter=insurance_expired"
        class="stat purple"
    >

        🛡️
        <?= __('insuranceExpired') ?>

        <span>
            <?= number_format($insuranceExpired) ?>
        </span>

    </a>


    <!-- 📄 OPERATION -->

    <a
        href="fleet.php?filter=operation_expired"
        class="stat black"
    >

        📄
        <?= __('operationExpired') ?>

        <span>
            <?= number_format($operationExpired) ?>
        </span>

    </a>


    <!-- 🚘 LICENSE -->

    <a
        href="driversview.php?filter=license_expired"
        class="stat purple"
    >

        🪪
        <?= __('driver_license_expired') ?>

        <span>
            <?= number_format($licenseExpired) ?>
        </span>

    </a>


    <!-- 🛞 TIRES -->

    <a
        href="tire.php"
        class="stat orange"
    >

        🛞
        <?= __('changed_tires') ?>

        <span>
            <?= number_format($tiresChanged) ?>
        </span>

    </a>


    <!-- 🛢️ OIL -->

    <a
        href="oile.php"
        class="stat green"
    >

        🛢️
        <?= __('oil_changes') ?>

        <span>
            <?= number_format($oilChanged) ?>
        </span>

    </a>


    <!-- 💥 ACCIDENTS -->

    <a
        href="accidents.php"
        class="stat black"
    >

        💥🚗
        <?= __('accidents') ?>

        <span>
            <?= number_format($accidents) ?>
        </span>

    </a>


</div>


<!-- =========================================================
     📌 ملخص إضافي
========================================================= -->

<div class="summary">


    <!-- 💰 COMMISSIONS -->

    <div class="summary-card">

        <div>

            <div class="summary-title">

                <?= $lang === 'ar'
                    ? 'إجمالي العمولات'
                    : 'Total Commissions'
                ?>

            </div>

            <div class="summary-value">

                <?= number_format(
                    $totalCommissions,
                    2
                ) ?>

            </div>

        </div>

        <div class="summary-icon">
            💰
        </div>

    </div>


    <!-- 📅 ATTENDANCE -->

    <div class="summary-card">

        <div>

            <div class="summary-title">

                <?= $lang === 'ar'
                    ? 'الحضور اليوم'
                    : 'Today Attendance'
                ?>

            </div>

            <div class="summary-value">

                <?= number_format($attendanceToday) ?>

            </div>

        </div>

        <div class="summary-icon">
            📅
        </div>

    </div>


    <!-- ⚠️ EXPIRED -->

    <div class="summary-card">

        <div>

            <div class="summary-title">

                <?= $lang === 'ar'
                    ? 'إجمالي التنبيهات'
                    : 'Total Alerts'
                ?>

            </div>

            <div class="summary-value">

                <?= number_format($totalExpired) ?>

            </div>

        </div>

        <div class="summary-icon">
            ⚠️
        </div>

    </div>


    <!-- 🔔 NOTIFICATIONS -->

    <div class="summary-card">

        <div>

            <div class="summary-title">

                <?= $lang === 'ar'
                    ? 'إشعارات غير مقروءة'
                    : 'Unread Notifications'
                ?>

            </div>

            <div class="summary-value">

                <?= number_format($notifCount) ?>

            </div>

        </div>

        <div class="summary-icon">
            🔔
        </div>

    </div>

</div>


<!-- =========================================================
     🧭 الخدمات
========================================================= -->

<div class="services-title">

    🧭

    <?= $lang === 'ar'
        ? 'الوصول إلى خدمات النظام'
        : 'System Services'
    ?>

</div>


<div class="cards">


    <!-- 🏠 HOME -->

    <div class="card">
        <a href="../index.php">
            🏠 <?= __('home') ?>
        </a>
    </div>


    <!-- ⚙️ SETTINGS -->

    <div class="card">
        <a href="settings.php">
            ⚙️ <?= __('settings') ?>
        </a>
    </div>


    <!-- 🏷️ SECTIONS -->

    <div class="card">
        <a href="sectionadmin.php">
            🏷️ <?= __('sections') ?>
        </a>
    </div>


    <!-- 🚚 SERVICES -->

    <div class="card">
        <a href="services.php">
            🚚 <?= __('company_services') ?>
        </a>
    </div>


    <!-- ➕ ADD SERVICE -->

    <div class="card">
        <a href="addproduct.php">
            ➕ <?= __('add_service') ?>
        </a>
    </div>


    <!-- 📝 CREATE ORDER -->

    <div class="card">
        <a href="create_order.php">
            📝 <?= __('create_order') ?>
        </a>
    </div>


    <!-- 👤 CUSTOMERS -->

    <div class="card">
        <a href="userview.php">
            👤 <?= __('customers_info') ?>
        </a>
    </div>


    <!-- 👤 ADD ADMIN -->

    <div class="card">
        <a href="addadmin.php">
            👤 <?= __('add_admin') ?>
        </a>
    </div>


    <!-- 👤 ADMIN VIEW -->

    <div class="card">
        <a href="adminview.php">
            👤 <?= __('adminview') ?>
        </a>
    </div>


    <!-- 📋 ORDERS -->

    <div class="card">
        <a href="ordersview.php">
            📋 <?= __('view_orders') ?>
        </a>
    </div>


    <!-- 💰 PRICING -->

    <div class="card">
        <a href="pricing.php">
            💰 <?= __('pricing') ?>
        </a>
    </div>


    <!-- 💰 SERVICES PRICING -->

    <div class="card">
        <a href="services_pricing.php">
            💰 <?= __('services_pricing') ?>
        </a>
    </div>


    <!-- 🚗 FLEET -->

    <div class="card">
        <a href="fleet.php">
            🚗 <?= __('company_fleet') ?>
        </a>
    </div>


    <!-- ➕ ADD VEHICLE -->

    <div class="card">
        <a href="addfleet.php">
            ➕ <?= __('add_vehicle') ?>
        </a>
    </div>


    <!-- 🚚 DRIVERS -->

    <div class="card">
        <a href="driversview.php">
            🚚 <?= __('drivers_info') ?>
        </a>
    </div>


    <!-- ➕ ADD DRIVER -->

    <div class="card">
        <a href="drivers.php">
            ➕ 🚚 <?= __('add_driver') ?>
        </a>
    </div>


    <!-- 📅 ATTENDANCE -->

    <div class="card">
        <a href="attendance_list.php">
            📅 🚚 <?= __('attendance_list') ?>
        </a>
    </div>


    <!-- 💰 COMMISSIONS -->

    <div class="card">
        <a href="commission/commissions_dashboard.php">
            💰 🚚 <?= __('commissions_dashboard') ?>
        </a>
    </div>


    <!-- 🔧 MAINTENANCE VIEW -->

    <div class="card">
        <a href="maintenanceview.php">
            🔧 <?= __('maintenance_records') ?>
        </a>
    </div>


    <!-- 🔧 ADD MAINTENANCE -->

    <div class="card">
        <a href="maintenance.php">
            🔧 <?= __('add_maintenance') ?>
        </a>
    </div>


    <!-- 🛞 TIRES -->

    <div class="card">
        <a href="tire.php">
            🛞 <?= __('tires_management') ?>
        </a>
    </div>


    <!-- 🛢️ OIL -->

    <div class="card">
        <a href="oile.php">
            🛢️ <?= __('oil_monitoring') ?>
        </a>
    </div>


    <!-- ➕ ADD ACCIDENT -->

    <div class="card">
        <a href="add-accident.php">
            ➕ <?= __('add-accident') ?>
        </a>
    </div>


    <!-- 💥 ACCIDENTS -->

    <div class="card">
        <a href="accidents.php">
            💥 <?= __('accidents') ?>
        </a>
    </div>


    <!-- 📊 REPORTS -->

    <div class="card">
        <a href="report_dashboard.php">
            📊 <?= __('reports_dashboard') ?>
        </a>
    </div>


    <!-- 📜 AUDIT -->

    <div class="card">
        <a href="audit_log.php">
            📜 <?= __('Update log') ?>
        </a>
    </div>


    <!-- 🔔 NOTIFICATIONS -->

    <div class="card">
        <a href="notifications.php">
            🔔 <?= __('Notifications') ?>
        </a>
    </div>


    <!-- 🚪 LOGOUT -->

    <div class="card">
        <a href="logout.php">
            🚪 <?= __('logout') ?>
        </a>
    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   🔊 تفعيل صوت الإشعارات بعد أول تفاعل
========================================================= */

(function(){

    const audio =
        document.getElementById('notifySound');

    if(!audio){
        return;
    }

    let unlocked = false;

    function unlockSound(){

        if(unlocked){
            return;
        }

        const promise =
            audio.play();

        if(promise){

            promise.then(function(){

                audio.pause();

                audio.currentTime = 0;

                unlocked = true;

            }).catch(function(){

                // المتصفح يمنع الصوت حتى تفاعل المستخدم

            });

        }

    }

    document.addEventListener(
        'click',
        unlockSound,
        {once:true}
    );

    document.addEventListener(
        'keydown',
        unlockSound,
        {once:true}
    );

    document.addEventListener(
        'touchstart',
        unlockSound,
        {once:true}
    );

})();


/* =========================================================
   ⏳ LOADER
========================================================= */

(function(){

    const loader =
        document.getElementById('loader');

    if(!loader){
        return;
    }


    function hideLoader(){

        loader.classList.add('hidden');

    }


    window.addEventListener(
        'load',
        function(){

            setTimeout(
                hideLoader,
                200
            );

        }
    );


    window.addEventListener(
        'pageshow',
        function(){

            setTimeout(
                hideLoader,
                100
            );

        }
    );


    /* =====================================================
       إظهار Loader عند الانتقال إلى صفحة أخرى
    ===================================================== */

    document.addEventListener(
        'click',
        function(event){

            const link =
                event.target.closest('a');

            if(!link){
                return;
            }

            const href =
                link.getAttribute('href');

            if(!href){
                return;
            }

            if(href === '#'){
                return;
            }

            if(
                href.startsWith('javascript:')
            ){
                return;
            }

            if(
                link.target === '_blank'
            ){
                return;
            }

            if(
                event.ctrlKey ||
                event.shiftKey ||
                event.metaKey ||
                event.altKey
            ){
                return;
            }

            loader.classList.remove(
                'hidden'
            );

        }
    );

})();


/* =========================================================
   DEBUG
========================================================= */

console.log(
    "AlSharqPlatform Dashboard Loaded Successfully"
);

</script>


</body>

</html>