<?php

session_start();

include('../include/connected.php');

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الوضع الليلي
========================================================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = (int)$_GET['theme'];
}

$dark = (int)($_SESSION['theme'] ?? 0);

/* =========================================================
   الترجمة
========================================================= */

$translations = [

    'ar' => [

        'title' => 'لوحة التقارير',
        'subtitle' => 'مركز موحد لإدارة ومتابعة تقارير منصة الشرق',

        'search_placeholder' => 'ابحث عن تقرير...',

        'view' => 'عرض التقرير',
        'open' => 'فتح',

        'fleet_group' => 'الأسطول والمركبات',
        'operations_group' => 'التشغيل والمتابعة',
        'drivers_group' => 'السائقون',
        'financial_group' => 'التقارير المالية',
        'support_group' => 'الدعم والخدمات',

        'fleet' => 'تقرير المركبات',
        'drivers' => 'تقرير السائقين',
        'maintenance' => 'تقرير الصيانة',
        'tires' => 'تقرير الإطارات',
        'oil' => 'تقرير تغيير الزيوت',
        'driver_cost' => 'تكاليف السائقين',
        'driver_revenue' => 'إيرادات السائقين',
        'invoices' => 'الفواتير',
        'attendance' => 'الحضور والانصراف',
        'accidents' => 'تقارير الحوادث',
        'support' => 'الدعم الفني',

        'fleet_desc' => 'عرض ومتابعة بيانات المركبات والأسطول.',
        'drivers_desc' => 'تقرير شامل عن السائقين وبياناتهم.',
        'maintenance_desc' => 'متابعة أعمال الصيانة وتكاليفها.',
        'tires_desc' => 'متابعة الإطارات وتغييراتها.',
        'oil_desc' => 'متابعة سجلات تغيير الزيت والتكاليف.',
        'driver_cost_desc' => 'تحليل ومتابعة تكاليف السائقين.',
        'driver_revenue_desc' => 'متابعة إيرادات السائقين.',
        'invoices_desc' => 'عرض ومتابعة الفواتير.',
        'attendance_desc' => 'تقرير الحضور والانصراف.',
        'accidents_desc' => 'إدارة ومتابعة حوادث المركبات.',
        'support_desc' => 'متابعة طلبات الدعم الفني.',

        'total_vehicles' => 'المركبات',
        'total_drivers' => 'السائقون',
        'total_accidents' => 'الحوادث',
        'total_oil' => 'تغييرات الزيت',

        'lang_ar' => 'عربي',
        'lang_en' => 'English',

        'light_mode' => 'الوضع النهاري',
        'dark_mode' => 'الوضع الليلي',

        'all_reports' => 'جميع التقارير',
        'no_reports' => 'لا توجد تقارير مطابقة للبحث'

    ],

    'en' => [

        'title' => 'Reports Dashboard',
        'subtitle' => 'Central reporting center for AlSharq Platform',

        'search_placeholder' => 'Search reports...',

        'view' => 'View Report',
        'open' => 'Open',

        'fleet_group' => 'Fleet & Vehicles',
        'operations_group' => 'Operations & Monitoring',
        'drivers_group' => 'Drivers',
        'financial_group' => 'Financial Reports',
        'support_group' => 'Support & Services',

        'fleet' => 'Vehicles Report',
        'drivers' => 'Drivers Report',
        'maintenance' => 'Maintenance Report',
        'tires' => 'Tires Report',
        'oil' => 'Oil Changes Report',
        'driver_cost' => 'Driver Costs',
        'driver_revenue' => 'Driver Revenue',
        'invoices' => 'Invoices',
        'attendance' => 'Attendance',
        'accidents' => 'Accidents Report',
        'support' => 'Technical Support',

        'fleet_desc' => 'View and monitor vehicle and fleet information.',
        'drivers_desc' => 'Comprehensive driver information report.',
        'maintenance_desc' => 'Monitor maintenance operations and costs.',
        'tires_desc' => 'Monitor tire records and changes.',
        'oil_desc' => 'Monitor oil changes and related costs.',
        'driver_cost_desc' => 'Analyze and monitor driver costs.',
        'driver_revenue_desc' => 'Monitor driver revenues.',
        'invoices_desc' => 'View and monitor invoices.',
        'attendance_desc' => 'Attendance and departure report.',
        'accidents_desc' => 'Manage and monitor vehicle accidents.',
        'support_desc' => 'Monitor technical support requests.',

        'total_vehicles' => 'Vehicles',
        'total_drivers' => 'Drivers',
        'total_accidents' => 'Accidents',
        'total_oil' => 'Oil Changes',

        'lang_ar' => 'عربي',
        'lang_en' => 'English',

        'light_mode' => 'Light Mode',
        'dark_mode' => 'Dark Mode',

        'all_reports' => 'All Reports',
        'no_reports' => 'No reports match your search'

    ]

];

function t($key)
{
    global $translations, $lang;

    return $translations[$lang][$key] ?? $key;
}

/* =========================================================
   إحصائيات سريعة
========================================================= */

$totalVehicles = 0;
$totalDrivers = 0;
$totalAccidents = 0;
$totalOil = 0;

/* المركبات */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM fleet
");

if ($q) {
    $row = $q->fetch_assoc();
    $totalVehicles = (int)($row['total'] ?? 0);
}

/* السائقون */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
");

if ($q) {
    $row = $q->fetch_assoc();
    $totalDrivers = (int)($row['total'] ?? 0);
}

/* الحوادث */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM accidents
");

if ($q) {
    $row = $q->fetch_assoc();
    $totalAccidents = (int)($row['total'] ?? 0);
}

/* الزيوت */

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM oil_changes
");

if ($q) {
    $row = $q->fetch_assoc();
    $totalOil = (int)($row['total'] ?? 0);
}

/* =========================================================
   روابط اللغة والوضع الليلي
========================================================= */

$langArUrl =
    '?' .
    http_build_query([
        'lang' => 'ar',
        'theme' => $dark
    ]);

$langEnUrl =
    '?' .
    http_build_query([
        'lang' => 'en',
        'theme' => $dark
    ]);

$themeUrl =
    '?' .
    http_build_query([
        'lang' => $lang,
        'theme' => $dark ? 0 : 1
    ]);

/* =========================================================
   التقارير
========================================================= */

$reports = [

    [
        'group' => 'fleet_group',
        'title' => 'fleet',
        'desc' => 'fleet_desc',
        'icon' => 'bi-car-front-fill',
        'color' => 'blue',
        'url' => 'reportfleet.php'
    ],

    [
        'group' => 'fleet_group',
        'title' => 'maintenance',
        'desc' => 'maintenance_desc',
        'icon' => 'bi-tools',
        'color' => 'orange',
        'url' => 'reportmaintenance.php'
    ],

    [
        'group' => 'fleet_group',
        'title' => 'tires',
        'desc' => 'tires_desc',
        'icon' => 'bi-circle',
        'color' => 'red',
        'url' => 'tires_report.php'
    ],

    [
        'group' => 'fleet_group',
        'title' => 'oil',
        'desc' => 'oil_desc',
        'icon' => 'bi-droplet-fill',
        'color' => 'purple',
        'url' => 'oile_report.php'
    ],

    [
        'group' => 'operations_group',
        'title' => 'attendance',
        'desc' => 'attendance_desc',
        'icon' => 'bi-clock-history',
        'color' => 'cyan',
        'url' => 'attendance_report.php'
    ],

    [
        'group' => 'operations_group',
        'title' => 'accidents',
        'desc' => 'accidents_desc',
        'icon' => 'bi-exclamation-triangle-fill',
        'color' => 'danger',
        'url' => 'accidents.php'
    ],

    [
        'group' => 'drivers_group',
        'title' => 'drivers',
        'desc' => 'drivers_desc',
        'icon' => 'bi-person-badge-fill',
        'color' => 'green',
        'url' => 'drivers_report.php'
    ],

    [
        'group' => 'financial_group',
        'title' => 'driver_cost',
        'desc' => 'driver_cost_desc',
        'icon' => 'bi-wallet2',
        'color' => 'orange',
        'url' => 'driverviewcost.php'
    ],

    [
        'group' => 'financial_group',
        'title' => 'driver_revenue',
        'desc' => 'driver_revenue_desc',
        'icon' => 'bi-graph-up-arrow',
        'color' => 'green',
        'url' => 'driver_revenue.php'
    ],

    [
        'group' => 'financial_group',
        'title' => 'invoices',
        'desc' => 'invoices_desc',
        'icon' => 'bi-receipt',
        'color' => 'blue',
        'url' => 'admin_invoices.php'
    ],

    [
        'group' => 'support_group',
        'title' => 'support',
        'desc' => 'support_desc',
        'icon' => 'bi-headset',
        'color' => 'purple',
        'url' => 'support_dashboard.php'
    ]

];

$groupOrder = [
    'fleet_group',
    'operations_group',
    'drivers_group',
    'financial_group',
    'support_group'
];

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars(t('title')) ?>
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap"
    rel="stylesheet"
>

<style>

* {
    box-sizing: border-box;
}

:root {

    --primary: #2563eb;

    --bg: <?= $dark ? '#0f172a' : '#f4f7fb' ?>;

    --card: <?= $dark ? '#1e293b' : '#ffffff' ?>;

    --card-soft: <?= $dark ? '#172033' : '#f8fafc' ?>;

    --text: <?= $dark ? '#f8fafc' : '#1f2937' ?>;

    --muted: <?= $dark ? '#94a3b8' : '#6b7280' ?>;

    --border: <?= $dark ? '#334155' : '#e5e7eb' ?>;

}

body {

    margin: 0;

    min-height: 100vh;

    background: var(--bg);

    color: var(--text);

    font-family:
        'Tajawal',
        Tahoma,
        Arial,
        sans-serif;

}

/* =========================================================
   HEADER
========================================================= */

.dashboard-header {

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            <?= $dark ? '#111827' : '#0f172a' ?>,
            <?= $dark ? '#1e293b' : '#1e3a5f' ?>
        );

    color: #fff;

    padding: 32px 25px 75px;

}

.dashboard-header::after {

    content: '';

    position: absolute;

    width: 300px;

    height: 300px;

    border-radius: 50%;

    background: rgba(255,255,255,.05);

    top: -140px;

    left: -70px;

}

.header-content {

    position: relative;

    z-index: 2;

    max-width: 1450px;

    margin: auto;

}

.top-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

}

.title-area {

    display: flex;

    align-items: center;

    gap: 16px;

}

.title-icon {

    width: 62px;

    height: 62px;

    border-radius: 18px;

    background: rgba(255,255,255,.12);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 29px;

    border: 1px solid rgba(255,255,255,.12);

}

.title-area h1 {

    margin: 0;

    font-size: 29px;

    font-weight: 800;

}

.title-area p {

    margin: 6px 0 0;

    color: rgba(255,255,255,.72);

    font-size: 14px;

}

.header-actions {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-wrap: wrap;

}

.header-actions a {

    color: #fff;

    text-decoration: none;

    padding: 8px 13px;

    border-radius: 9px;

    border: 1px solid rgba(255,255,255,.15);

    background: rgba(255,255,255,.07);

    font-size: 13px;

}

.header-actions a:hover {

    background: rgba(255,255,255,.15);

}

.header-actions .active {

    background: #198754;

    border-color: #198754;

}

/* =========================================================
   SEARCH
========================================================= */

.search-wrap {

    max-width: 1450px;

    margin: -28px auto 0;

    position: relative;

    z-index: 5;

    padding: 0 18px;

}

.search-card {

    background: var(--card);

    border: 1px solid var(--border);

    border-radius: 17px;

    padding: 17px;

    box-shadow:
        0 8px 28px rgba(0,0,0,.08);

}

.search-box {

    position: relative;

}

.search-box i {

    position: absolute;

    top: 50%;

    transform: translateY(-50%);

    color: var(--muted);

    font-size: 17px;

}

html[dir="rtl"] .search-box i {

    right: 15px;

}

html[dir="ltr"] .search-box i {

    left: 15px;

}

.search-box input {

    width: 100%;

    min-height: 48px;

    border-radius: 11px;

    border: 1px solid var(--border);

    background: var(--card-soft);

    color: var(--text);

    padding: 0 45px;

    outline: none;

}

/* =========================================================
   CONTENT
========================================================= */

.page-content {

    max-width: 1450px;

    margin: 28px auto 50px;

    padding: 0 18px;

}

/* =========================================================
   QUICK STATS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 32px;

}

.stat-card {

    background: var(--card);

    border: 1px solid var(--border);

    border-radius: 15px;

    padding: 18px;

    display: flex;

    align-items: center;

    gap: 14px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.05);

}

.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #fff;

    font-size: 21px;

    flex-shrink: 0;

}

.stat-card:nth-child(1) .stat-icon {
    background: #2563eb;
}

.stat-card:nth-child(2) .stat-icon {
    background: #198754;
}

.stat-card:nth-child(3) .stat-icon {
    background: #dc3545;
}

.stat-card:nth-child(4) .stat-icon {
    background: #7c3aed;
}

.stat-label {

    color: var(--muted);

    font-size: 12px;

}

.stat-value {

    font-size: 24px;

    font-weight: 800;

    margin-top: 2px;

}

/* =========================================================
   GROUP
========================================================= */

.report-group {

    margin-bottom: 34px;

}

.group-header {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 14px;

}

.group-line {

    width: 5px;

    height: 25px;

    border-radius: 5px;

    background: var(--primary);

}

.group-title {

    margin: 0;

    font-size: 19px;

    font-weight: 800;

}

.group-count {

    color: var(--muted);

    font-size: 12px;

}

/* =========================================================
   CARDS
========================================================= */

.report-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

}

.report-card {

    position: relative;

    overflow: hidden;

    background: var(--card);

    border: 1px solid var(--border);

    border-radius: 16px;

    padding: 20px;

    transition: .2s ease;

    min-height: 210px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.04);

}

.report-card:hover {

    transform: translateY(-4px);

    box-shadow:
        0 12px 28px rgba(0,0,0,.08);

}

.report-icon {

    width: 50px;

    height: 50px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #fff;

    font-size: 22px;

    margin-bottom: 15px;

}

.report-card h3 {

    margin: 0 0 7px;

    font-size: 16px;

    font-weight: 800;

}

.report-card p {

    margin: 0;

    color: var(--muted);

    font-size: 12px;

    line-height: 1.8;

    min-height: 43px;

}

.report-footer {

    margin-top: 18px;

}

.report-btn {

    width: 100%;

    min-height: 40px;

    border-radius: 9px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    text-decoration: none;

    color: #fff;

    font-size: 13px;

    font-weight: 700;

}

.report-btn:hover {

    color: #fff;

    opacity: .9;

}

/* COLORS */

.blue .report-icon,
.blue .report-btn {
    background: #2563eb;
}

.green .report-icon,
.green .report-btn {
    background: #198754;
}

.orange .report-icon,
.orange .report-btn {
    background: #f59e0b;
}

.red .report-icon,
.red .report-btn {
    background: #dc3545;
}

.purple .report-icon,
.purple .report-btn {
    background: #7c3aed;
}

.cyan .report-icon,
.cyan .report-btn {
    background: #0891b2;
}

.danger .report-icon,
.danger .report-btn {
    background: #dc2626;
}

/* =========================================================
   EMPTY
========================================================= */

.no-results {

    display: none;

    text-align: center;

    padding: 60px 20px;

    background: var(--card);

    border: 1px solid var(--border);

    border-radius: 15px;

    color: var(--muted);

}

.no-results i {

    display: block;

    font-size: 45px;

    margin-bottom: 10px;

}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .report-grid {

        grid-template-columns:
            repeat(3, 1fr);

    }

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}

@media(max-width:800px) {

    .report-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .top-row {

        flex-direction: column;

        align-items: flex-start;

    }

}

@media(max-width:560px) {

    .dashboard-header {

        padding: 25px 16px 65px;

    }

    .page-content {

        padding: 0 12px;

    }

    .search-wrap {

        padding: 0 12px;

    }

    .report-grid {

        grid-template-columns: 1fr;

    }

    .stats-grid {

        grid-template-columns: 1fr;

    }

    .title-area h1 {

        font-size: 23px;

    }

    .title-icon {

        width: 52px;

        height: 52px;

    }

}

</style>

</head>

<body>

<!-- =========================================================
     HEADER
========================================================= -->

<header class="dashboard-header">

    <div class="header-content">

        <div class="top-row">

            <div class="title-area">

                <div class="title-icon">

                    <i class="bi bi-bar-chart-line-fill"></i>

                </div>

                <div>

                    <h1>

                        <?= htmlspecialchars(t('title')) ?>

                    </h1>

                    <p>

                        <?= htmlspecialchars(t('subtitle')) ?>

                    </p>

                </div>

            </div>

            <div class="header-actions">

                <a
                    href="<?= htmlspecialchars($langArUrl) ?>"
                    class="<?= $lang === 'ar' ? 'active' : '' ?>"
                >

                    🇸🇦 <?= htmlspecialchars(t('lang_ar')) ?>

                </a>

                <a
                    href="<?= htmlspecialchars($langEnUrl) ?>"
                    class="<?= $lang === 'en' ? 'active' : '' ?>"
                >

                    🇬🇧 <?= htmlspecialchars(t('lang_en')) ?>

                </a>

                <a
                    href="<?= htmlspecialchars($themeUrl) ?>"
                    title="<?= htmlspecialchars(
                        $dark
                            ? t('light_mode')
                            : t('dark_mode')
                    ) ?>"
                >

                    <?php if ($dark): ?>

                        ☀️

                    <?php else: ?>

                        🌙

                    <?php endif; ?>

                </a>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     SEARCH
========================================================= -->

<div class="search-wrap">

    <div class="search-card">

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                id="reportSearch"
                placeholder="<?= htmlspecialchars(
                    t('search_placeholder')
                ) ?>"
                autocomplete="off"
            >

        </div>

    </div>

</div>


<!-- =========================================================
     CONTENT
========================================================= -->

<main class="page-content">


    <!-- =====================================================
         QUICK STATS
    ====================================================== -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">

                <i class="bi bi-car-front-fill"></i>

            </div>

            <div>

                <div class="stat-label">

                    <?= htmlspecialchars(
                        t('total_vehicles')
                    ) ?>

                </div>

                <div class="stat-value">

                    <?= number_format(
                        $totalVehicles
                    ) ?>

                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="bi bi-person-badge-fill"></i>

            </div>

            <div>

                <div class="stat-label">

                    <?= htmlspecialchars(
                        t('total_drivers')
                    ) ?>

                </div>

                <div class="stat-value">

                    <?= number_format(
                        $totalDrivers
                    ) ?>

                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="bi bi-exclamation-triangle-fill"></i>

            </div>

            <div>

                <div class="stat-label">

                    <?= htmlspecialchars(
                        t('total_accidents')
                    ) ?>

                </div>

                <div class="stat-value">

                    <?= number_format(
                        $totalAccidents
                    ) ?>

                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="bi bi-droplet-fill"></i>

            </div>

            <div>

                <div class="stat-label">

                    <?= htmlspecialchars(
                        t('total_oil')
                    ) ?>

                </div>

                <div class="stat-value">

                    <?= number_format(
                        $totalOil
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         REPORT GROUPS
    ====================================================== -->

    <?php foreach ($groupOrder as $groupKey): ?>

        <?php

        $groupReports = array_filter(
            $reports,
            function ($report) use ($groupKey) {
                return $report['group'] === $groupKey;
            }
        );

        ?>

        <section class="report-group">

            <div class="group-header">

                <div class="group-line"></div>

                <h2 class="group-title">

                    <?= htmlspecialchars(
                        t($groupKey)
                    ) ?>

                </h2>

                <span class="group-count">

                    <?= count($groupReports) ?>

                </span>

            </div>


            <div class="report-grid">

                <?php foreach ($groupReports as $report): ?>

                    <article
                        class="report-card <?= htmlspecialchars(
                            $report['color']
                        ) ?>"
                        data-report-search="<?= htmlspecialchars(
                            strtolower(
                                t($report['title']) .
                                ' ' .
                                t($report['desc'])
                            )
                        ) ?>"
                    >

                        <div class="report-icon">

                            <i class="bi <?= htmlspecialchars(
                                $report['icon']
                            ) ?>"></i>

                        </div>


                        <h3>

                            <?= htmlspecialchars(
                                t($report['title'])
                            ) ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                t($report['desc'])
                            ) ?>

                        </p>


                        <div class="report-footer">

                            <a
                                href="<?= htmlspecialchars(
                                    $report['url']
                                ) ?>?lang=<?= urlencode(
                                    $lang
                                ) ?>&theme=<?= (int)$dark ?>"
                                class="report-btn"
                            >

                                <i class="bi bi-arrow-left-circle"></i>

                                <?= htmlspecialchars(
                                    t('view')
                                ) ?>

                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endforeach; ?>


    <!-- =====================================================
         No Results
    ====================================================== -->

    <div
        class="no-results"
        id="noResults"
    >

        <i class="bi bi-search"></i>

        <?= htmlspecialchars(
            t('no_reports')
        ) ?>

    </div>

</main>


<script>

const searchInput =
    document.getElementById('reportSearch');

const reportCards =
    document.querySelectorAll(
        '.report-card'
    );

const reportGroups =
    document.querySelectorAll(
        '.report-group'
    );

const noResults =
    document.getElementById('noResults');


searchInput.addEventListener(
    'input',
    function () {

        const term =
            this.value
                .trim()
                .toLowerCase();

        let visibleCount = 0;


        reportCards.forEach(function(card) {

            const text =
                card.dataset.reportSearch
                    .toLowerCase();

            const match =
                term === '' ||
                text.includes(term);

            card.style.display =
                match
                    ? ''
                    : 'none';

            if (match) {
                visibleCount++;
            }

        });


        /*
         * إخفاء المجموعة إذا لم يبق فيها تقارير
         */

        reportGroups.forEach(function(group) {

            const cards =
                group.querySelectorAll(
                    '.report-card'
                );

            let visible =
                false;

            cards.forEach(function(card) {

                if (
                    card.style.display !== 'none'
                ) {
                    visible = true;
                }

            });

            group.style.display =
                visible
                    ? ''
                    : 'none';

        });


        noResults.style.display =
            visibleCount === 0
                ? 'block'
                : 'none';

    }
);

</script>

</body>

</html>