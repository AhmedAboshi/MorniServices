
<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

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

/* =========================================================
   THEME
========================================================= */

if (isset($_GET['theme'])) {

    $_SESSION['theme'] =
        $_GET['theme'] === 'dark'
            ? 'dark'
            : 'light';
}

$theme =
    $_SESSION['theme'] ?? 'light';

$dark =
    $theme === 'dark';

/* =========================================================
   TRANSLATION
========================================================= */

$t = [

    'ar' => [

        'title' =>
            'لوحة الدعم الفني',

        'subtitle' =>
            'متابعة التذاكر وحالة طلبات الدعم الفني',

        'total' =>
            'إجمالي التذاكر',

        'open' =>
            'التذاكر المفتوحة',

        'pending' =>
            'قيد المعالجة',

        'closed' =>
            'التذاكر المغلقة',

        'tickets' =>
            'إدارة التذاكر',

        'open_tickets' =>
            'عرض التذاكر المفتوحة',

        'pending_tickets' =>
            'عرض التذاكر قيد المعالجة',

        'closed_tickets' =>
            'عرض التذاكر المغلقة',

        'all_tickets' =>
            'جميع التذاكر',

        'percentage' =>
            'النسبة',

        'system_status' =>
            'حالة نظام الدعم',

        'active' =>
            'النظام يعمل',

        'language' =>
            'اللغة',

        'dark_mode' =>
            'الوضع الليلي',

        'light_mode' =>
            'الوضع النهاري',

        'company' =>
            'منصة الشرق الذكية للخدمات وإدارة الأسطول',

        'quick_actions' =>
            'الوصول السريع',

        'support_center' =>
            'مركز الدعم الفني'
    ],

    'en' => [

        'title' =>
            'Support Dashboard',

        'subtitle' =>
            'Monitor support tickets and their current status',

        'total' =>
            'Total Tickets',

        'open' =>
            'Open Tickets',

        'pending' =>
            'Pending Tickets',

        'closed' =>
            'Closed Tickets',

        'tickets' =>
            'Manage Tickets',

        'open_tickets' =>
            'View Open Tickets',

        'pending_tickets' =>
            'View Pending Tickets',

        'closed_tickets' =>
            'View Closed Tickets',

        'all_tickets' =>
            'All Tickets',

        'percentage' =>
            'Percentage',

        'system_status' =>
            'Support System Status',

        'active' =>
            'System Active',

        'language' =>
            'Language',

        'dark_mode' =>
            'Dark Mode',

        'light_mode' =>
            'Light Mode',

        'company' =>
            'AlSharq Smart Services & Fleet Management',

        'quick_actions' =>
            'Quick Access',

        'support_center' =>
            'Support Center'
    ]
];

$tr = $t[$lang];

/* =========================================================
   HELPER
========================================================= */

function getTicketCount(
    mysqli $con,
    ?string $status = null
): int {

    if ($status === null) {

        $stmt = $con->prepare("
            SELECT COUNT(*) AS total
            FROM tickets
        ");

    } else {

        $stmt = $con->prepare("
            SELECT COUNT(*) AS total
            FROM tickets
            WHERE status = ?
        ");

        $stmt->bind_param(
            "s",
            $status
        );
    }

    if (!$stmt) {
        return 0;
    }

    $stmt->execute();

    $result =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    return (int)(
        $result['total'] ?? 0
    );
}

/* =========================================================
   STATISTICS
========================================================= */

$total =
    getTicketCount($con);

$open =
    getTicketCount(
        $con,
        'open'
    );

$pending =
    getTicketCount(
        $con,
        'pending'
    );

$closed =
    getTicketCount(
        $con,
        'closed'
    );

/* =========================================================
   PERCENTAGES
========================================================= */

$openPercent =
    $total > 0
        ? round(
            ($open / $total) * 100,
            1
        )
        : 0;

$pendingPercent =
    $total > 0
        ? round(
            ($pending / $total) * 100,
            1
        )
        : 0;

$closedPercent =
    $total > 0
        ? round(
            ($closed / $total) * 100,
            1
        )
        : 0;

/* =========================================================
   LINKS
========================================================= */

$arabicUrl =
    '?' .
    http_build_query([
        'lang' => 'ar',
        'theme' => $theme
    ]);

$englishUrl =
    '?' .
    http_build_query([
        'lang' => 'en',
        'theme' => $theme
    ]);

$themeUrl =
    '?' .
    http_build_query([
        'lang' =>
            $lang,
        'theme' =>
            $dark
                ? 'light'
                : 'dark'
    ]);

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
    <?= htmlspecialchars(
        $tr['title']
    ) ?>
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
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

*{
    box-sizing:border-box;
}

:root{

    --bg:
        <?= $dark
            ? '#0f172a'
            : '#f4f7fb'
        ?>;

    --card:
        <?= $dark
            ? '#1e293b'
            : '#ffffff'
        ?>;

    --soft:
        <?= $dark
            ? '#172033'
            : '#f8fafc'
        ?>;

    --text:
        <?= $dark
            ? '#f8fafc'
            : '#1f2937'
        ?>;

    --muted:
        <?= $dark
            ? '#94a3b8'
            : '#6b7280'
        ?>;

    --border:
        <?= $dark
            ? '#334155'
            : '#e5e7eb'
        ?>;
}

body{

    margin:0;

    background:
        var(--bg);

    color:
        var(--text);

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;
}

.page{

    max-width:
        1500px;

    margin:
        30px auto;

    padding:
        0 18px;
}

/* =========================================================
   HEADER
========================================================= */

.header{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:#fff;

    border-radius:
        20px;

    padding:
        24px;

    box-shadow:
        0 8px 28px
        rgba(0,0,0,.14);

    margin-bottom:
        20px;
}

.header-top{

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        20px;

    flex-wrap:
        wrap;
}

.header-title{

    display:flex;

    align-items:
        center;

    gap:
        14px;
}

.title-icon{

    width:
        58px;

    height:
        58px;

    border-radius:
        16px;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(255,255,255,.17);

    font-size:
        27px;
}

.header h1{

    margin:0;

    font-size:
        26px;

    font-weight:
        800;
}

.header p{

    margin:
        5px 0 0;

    font-size:
        12px;

    opacity:
        .9;
}

.header-actions{

    display:flex;

    gap:
        7px;

    flex-wrap:
        wrap;
}

.header-actions a{

    text-decoration:none;
}

/* =========================================================
   CARDS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        15px;

    margin-bottom:
        20px;
}

.stat-card{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        20px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.06);

    position:
        relative;

    overflow:
        hidden;
}

.stat-top{

    display:flex;

    align-items:
        center;

    justify-content:
        space-between;
}

.stat-icon{

    width:
        46px;

    height:
        46px;

    border-radius:
        13px;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    color:#fff;

    font-size:
        20px;
}

.stat-title{

    margin-top:
        14px;

    font-size:
        12px;

    color:
        var(--muted);

    font-weight:
        700;
}

.stat-number{

    font-size:
        30px;

    font-weight:
        800;

    margin-top:
        3px;
}

.stat-footer{

    margin-top:
        7px;

    font-size:
        11px;

    color:
        var(--muted);
}

.stat-blue .stat-icon{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );
}

.stat-green .stat-icon{

    background:
        linear-gradient(
            135deg,
            #198754,
            #146c43
        );
}

.stat-orange .stat-icon{

    background:
        linear-gradient(
            135deg,
            #fd7e14,
            #e8590c
        );
}

.stat-red .stat-icon{

    background:
        linear-gradient(
            135deg,
            #dc3545,
            #a71d2a
        );
}

/* =========================================================
   PROGRESS
========================================================= */

.progress-area{

    margin-top:
        10px;

    height:
        7px;

    background:
        var(--soft);

    border-radius:
        20px;

    overflow:
        hidden;
}

.progress{

    height:
        100%;

    border-radius:
        20px;
}

.progress-green{

    background:
        #198754;

    width:
        <?= $total > 0
            ? $openPercent
            : 0
        ?>%;
}

.progress-orange{

    background:
        #fd7e14;

    width:
        <?= $total > 0
            ? $pendingPercent
            : 0
        ?>%;
}

.progress-red{

    background:
        #dc3545;

    width:
        <?= $total > 0
            ? $closedPercent
            : 0
        ?>%;
}

/* =========================================================
   QUICK ACTIONS
========================================================= */

.section{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        20px;

    margin-bottom:
        20px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);
}

.section-title{

    display:flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        15px;

    font-size:
        17px;

    font-weight:
        800;
}

.quick-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        12px;
}

.quick-card{

    text-decoration:
        none;

    color:
        var(--text);

    background:
        var(--soft);

    border:
        1px solid
        var(--border);

    border-radius:
        14px;

    padding:
        16px;

    transition:
        .2s;

    display:flex;

    align-items:
        center;

    gap:
        12px;
}

.quick-card:hover{

    transform:
        translateY(-3px);

    border-color:
        #0d6efd;

    color:
        #0d6efd;
}

.quick-icon{

    width:
        42px;

    height:
        42px;

    border-radius:
        11px;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #0d6efd;

    color:#fff;
}

.quick-title{

    font-size:
        12px;

    font-weight:
        700;
}

/* =========================================================
   SYSTEM STATUS
========================================================= */

.status-box{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        15px;
}

.status-item{

    background:
        var(--soft);

    border:
        1px solid
        var(--border);

    border-radius:
        13px;

    padding:
        15px;

    display:flex;

    align-items:
        center;

    justify-content:
        space-between;
}

.status-right{

    display:flex;

    align-items:
        center;

    gap:
        9px;
}

.status-dot{

    width:
        11px;

    height:
        11px;

    background:
        #198754;

    border-radius:
        50%;

    box-shadow:
        0 0 0 5px
        rgba(25,135,84,.12);
}

.status-text{

    font-size:
        12px;

    font-weight:
        700;
}

.status-value{

    color:
        var(--muted);

    font-size:
        11px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .stats{

        grid-template-columns:
            repeat(2,1fr);
    }

    .quick-grid{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:650px){

    .page{

        margin:
            15px auto;

        padding:
            0 10px;
    }

    .stats{

        grid-template-columns:
            1fr;
    }

    .quick-grid{

        grid-template-columns:
            1fr;
    }

    .status-box{

        grid-template-columns:
            1fr;
    }

    .header h1{

        font-size:
            21px;
    }

    .header-actions{

        width:
            100%;
    }

    .header-actions .btn{

        flex:
            1;
    }
}

</style>

</head>

<body>

<div class="page">

<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<div class="header-top">

<div class="header-title">

<div class="title-icon">

<i class="bi bi-headset"></i>

</div>

<div>

<h1>

<?= htmlspecialchars(
    $tr['title']
) ?>

</h1>

<p>

<?= htmlspecialchars(
    $tr['subtitle']
) ?>

</p>

</div>

</div>

<div class="header-actions">

<a
    href="?<?= http_build_query([
        'lang' => 'ar',
        'theme' => $theme
    ]) ?>"
    class="btn btn-light btn-sm"
>

🇸🇦 AR

</a>

<a
    href="?<?= http_build_query([
        'lang' => 'en',
        'theme' => $theme
    ]) ?>"
    class="btn btn-light btn-sm"
>

🇬🇧 EN

</a>

<a
    href="<?= htmlspecialchars($themeUrl) ?>"
    class="btn <?= $dark
        ? 'btn-light'
        : 'btn-dark'
    ?> btn-sm"
>

<i class="bi <?= $dark
    ? 'bi-sun'
    : 'bi-moon-stars'
?>"></i>

</a>

</div>

</div>

</div>

<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">

<!-- TOTAL -->

<div class="stat-card stat-blue">

<div class="stat-top">

<div>

<div class="stat-title">

<?= $tr['total'] ?>

</div>

<div class="stat-number">

<?= number_format($total) ?>

</div>

</div>

<div class="stat-icon">

<i class="bi bi-ticket-detailed"></i>

</div>

</div>

<div class="stat-footer">

100%

</div>

</div>

<!-- OPEN -->

<div class="stat-card stat-green">

<div class="stat-top">

<div>

<div class="stat-title">

<?= $tr['open'] ?>

</div>

<div class="stat-number">

<?= number_format($open) ?>

</div>

</div>

<div class="stat-icon">

<i class="bi bi-folder2-open"></i>

</div>

</div>

<div class="progress-area">

<div class="progress progress-green"></div>

</div>

<div class="stat-footer">

<?= $openPercent ?>%

</div>

</div>

<!-- PENDING -->

<div class="stat-card stat-orange">

<div class="stat-top">

<div>

<div class="stat-title">

<?= $tr['pending'] ?>

</div>

<div class="stat-number">

<?= number_format($pending) ?>

</div>

</div>

<div class="stat-icon">

<i class="bi bi-hourglass-split"></i>

</div>

</div>

<div class="progress-area">

<div class="progress progress-orange"></div>

</div>

<div class="stat-footer">

<?= $pendingPercent ?>%

</div>

</div>

<!-- CLOSED -->

<div class="stat-card stat-red">

<div class="stat-top">

<div>

<div class="stat-title">

<?= $tr['closed'] ?>

</div>

<div class="stat-number">

<?= number_format($closed) ?>

</div>

</div>

<div class="stat-icon">

<i class="bi bi-check2-circle"></i>

</div>

</div>

<div class="progress-area">

<div class="progress progress-red"></div>

</div>

<div class="stat-footer">

<?= $closedPercent ?>%

</div>

</div>

</div>

<!-- =====================================================
     QUICK ACTIONS
===================================================== -->

<div class="section">

<div class="section-title">

<i class="bi bi-lightning-charge-fill text-warning"></i>

<?= $tr['quick_actions'] ?>

</div>

<div class="quick-grid">

<a
    href="tickets.php"
    class="quick-card"
>

<div class="quick-icon">

<i class="bi bi-ticket-perforated"></i>

</div>

<div class="quick-title">

<?= $tr['all_tickets'] ?>

</div>

</a>

<a
    href="tickets.php?status=open"
    class="quick-card"
>

<div
    class="quick-icon"
    style="background:#198754;"
>

<i class="bi bi-folder2-open"></i>

</div>

<div class="quick-title">

<?= $tr['open_tickets'] ?>

</div>

</a>

<a
    href="tickets.php?status=pending"
    class="quick-card"
>

<div
    class="quick-icon"
    style="background:#fd7e14;"
>

<i class="bi bi-hourglass-split"></i>

</div>

<div class="quick-title">

<?= $tr['pending_tickets'] ?>

</div>

</a>

<a
    href="tickets.php?status=closed"
    class="quick-card"
>

<div
    class="quick-icon"
    style="background:#dc3545;"
>

<i class="bi bi-check2-circle"></i>

</div>

<div class="quick-title">

<?= $tr['closed_tickets'] ?>

</div>

</a>

</div>

</div>

<!-- =====================================================
     SYSTEM STATUS
===================================================== -->

<div class="section">

<div class="section-title">

<i class="bi bi-activity text-success"></i>

<?= $tr['system_status'] ?>

</div>

<div class="status-box">

<div class="status-item">

<div class="status-right">

<span class="status-dot"></span>

<span class="status-text">

<?= $tr['support_center'] ?>

</span>

</div>

<span class="status-value">

<?= $tr['active'] ?>

</span>

</div>

<div class="status-item">

<div class="status-right">

<span
    class="status-dot"
    style="background:#0d6efd;"
></span>

<span class="status-text">

<?= $tr['language'] ?>

</span>

</div>

<span class="status-value">

<?= $lang === 'ar'
    ? 'العربية'
    : 'English'
?>

</span>

</div>

</div>

</div>

</div>

</body>

</html>

