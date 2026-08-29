
<?php

session_start();

include('include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

/* =========================================================
   حماية المستخدم
========================================================= */

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {

    header("Location: login.php");
    exit();
}

/* =========================================================
   اللغة
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
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'title' => 'الإشعارات',
        'subtitle' => 'آخر التنبيهات والتحديثات الخاصة بك',

        'all' => 'جميع الإشعارات',
        'unread' => 'غير المقروءة',

        'mark_all' => 'تحديد الكل كمقروء',
        'mark_read' => 'تحديد كمقروء',

        'back' => 'الرئيسية',

        'no_notifications' =>
            'لا توجد إشعارات حاليًا',

        'new' => 'جديد',

        'confirm_mark_all' =>
            'هل تريد تحديد جميع الإشعارات كمقروءة؟',

        'error' =>
            'حدث خطأ أثناء تنفيذ العملية',

        'success' =>
            'تم تحديث الإشعارات بنجاح'
    ],

    'en' => [

        'title' => 'Notifications',
        'subtitle' => 'Your latest alerts and updates',

        'all' => 'All Notifications',
        'unread' => 'Unread',

        'mark_all' => 'Mark All as Read',
        'mark_read' => 'Mark as Read',

        'back' => 'Home',

        'no_notifications' =>
            'No notifications available',

        'new' => 'New',

        'confirm_mark_all' =>
            'Mark all notifications as read?',

        'error' =>
            'An error occurred',

        'success' =>
            'Notifications updated successfully'
    ]
];

$tr = $t[$lang];

/* =========================================================
   إنشاء CSRF
========================================================= */

if (empty($_SESSION['notification_csrf'])) {

    $_SESSION['notification_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf =
    $_SESSION['notification_csrf'];

/* =========================================================
   Action
========================================================= */

$action =
    $_POST['action']
    ?? '';

/* =========================================================
   تحديد إشعار كمقروء
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $action === 'mark_read'
) {

    $postedCsrf =
        $_POST['csrf'] ?? '';

    $notification_id =
        (int)(
            $_POST['notification_id']
            ?? 0
        );

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {

        $error =
            $tr['error'];

    } elseif (
        $notification_id <= 0
    ) {

        $error =
            $tr['error'];

    } else {

        $stmt = $con->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                'ii',
                $notification_id,
                $user_id
            );

            $stmt->execute();

            $stmt->close();
        }
    }

    header(
        "Location: notifications.php?" .
        http_build_query([
            'lang' => $lang
        ])
    );

    exit();
}

/* =========================================================
   تحديد الكل كمقروء
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $action === 'mark_all'
) {

    $postedCsrf =
        $_POST['csrf'] ?? '';

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {

        $error =
            $tr['error'];

    } else {

        $stmt = $con->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        if ($stmt) {

            $stmt->bind_param(
                'i',
                $user_id
            );

            $stmt->execute();

            $stmt->close();
        }
    }

    header(
        "Location: notifications.php?" .
        http_build_query([
            'lang' => $lang
        ])
    );

    exit();
}

/* =========================================================
   Filter
========================================================= */

$filter =
    $_GET['filter']
    ?? 'all';

if (
    !in_array(
        $filter,
        ['all', 'unread'],
        true
    )
) {

    $filter = 'all';
}

/* =========================================================
   جلب الإشعارات
========================================================= */

$whereExtra = '';

if ($filter === 'unread') {

    $whereExtra =
        "AND is_read = 0";
}

$stmt = $con->prepare("
    SELECT
        id,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    $whereExtra
    ORDER BY
        is_read ASC,
        id DESC
");

$stmt->bind_param(
    'i',
    $user_id
);

$stmt->execute();

$result =
    $stmt->get_result();

/* =========================================================
   العدادات
========================================================= */

$countStmt = $con->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN is_read = 0
                THEN 1
                ELSE 0
            END
        ) AS unread
    FROM notifications
    WHERE user_id = ?
");

$countStmt->bind_param(
    'i',
    $user_id
);

$countStmt->execute();

$counts =
    $countStmt
        ->get_result()
        ->fetch_assoc();

$countStmt->close();

$totalNotifications =
    (int)(
        $counts['total']
        ?? 0
    );

$unreadNotifications =
    (int)(
        $counts['unread']
        ?? 0
    );

/* =========================================================
   روابط اللغة
========================================================= */

$arUrl =
    '?' .
    http_build_query([
        'lang' =>
            'ar',
        'filter' =>
            $filter
    ]);

$enUrl =
    '?' .
    http_build_query([
        'lang' =>
            'en',
        'filter' =>
            $filter
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

body{

    margin:0;

    background:#f4f7fb;

    color:#1f2937;

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;
}

.page{

    max-width:
        1000px;

    margin:
        30px auto;

    padding:
        0 15px;
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

    padding:
        22px;

    border-radius:
        18px;

    margin-bottom:
        18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.12);
}

.header-top{

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    flex-wrap:
        wrap;
}

.title-area{

    display:flex;

    align-items:
        center;

    gap:
        12px;
}

.title-icon{

    width:
        52px;

    height:
        52px;

    border-radius:
        14px;

    background:
        rgba(255,255,255,.18);

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        24px;
}

.header h1{

    margin:0;

    font-size:
        25px;

    font-weight:
        800;
}

.header p{

    margin:
        5px 0 0;

    font-size:
        12px;

    opacity:
        .85;
}

/* =========================================================
   STATS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:
        12px;

    margin-bottom:
        18px;
}

.stat{

    background:#fff;

    border:
        1px solid #e5e7eb;

    border-radius:
        14px;

    padding:
        16px;

    display:flex;

    align-items:
        center;

    gap:
        12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);
}

.stat-icon{

    width:
        44px;

    height:
        44px;

    border-radius:
        11px;

    background:
        #0d6efd;

    color:#fff;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        19px;
}

.stat-number{

    font-size:
        23px;

    font-weight:
        800;
}

.stat-label{

    color:#6b7280;

    font-size:
        11px;
}

/* =========================================================
   FILTER BAR
========================================================= */

.toolbar{

    background:#fff;

    border:
        1px solid #e5e7eb;

    border-radius:
        15px;

    padding:
        13px;

    margin-bottom:
        15px;

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        10px;

    flex-wrap:
        wrap;
}

.filters{

    display:flex;

    gap:
        7px;

    flex-wrap:
        wrap;
}

.filter-btn{

    text-decoration:none;

    padding:
        8px 13px;

    border-radius:
        9px;

    font-size:
        11px;

    font-weight:
        700;

    background:
        #f1f5f9;

    color:
        #475569;
}

.filter-btn.active{

    background:
        #0d6efd;

    color:#fff;
}

.mark-all{

    border:
        0;

    background:
        #198754;

    color:#fff;

    padding:
        9px 13px;

    border-radius:
        9px;

    font-size:
        11px;

    font-weight:
        700;

    cursor:pointer;
}

/* =========================================================
   NOTIFICATIONS
========================================================= */

.notifications{

    display:flex;

    flex-direction:
        column;

    gap:
        10px;
}

.notification{

    position:
        relative;

    background:#fff;

    border:
        1px solid #e5e7eb;

    border-right:
        5px solid #0d6efd;

    border-radius:
        14px;

    padding:
        16px;

    box-shadow:
        0 4px 14px
        rgba(0,0,0,.04);

    transition:
        .2s;
}

[dir="ltr"] .notification{

    border-right:
        1px solid #e5e7eb;

    border-left:
        5px solid #0d6efd;
}

.notification:hover{

    transform:
        translateY(-2px);

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.07);
}

.notification.unread{

    background:
        #eef5ff;

    border-color:
        #b8d4ff;
}

.notification-top{

    display:flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    gap:
        10px;
}

.notification-icon{

    width:
        38px;

    height:
        38px;

    border-radius:
        10px;

    background:
        #e8f1ff;

    color:
        #0d6efd;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;
}

.notification-content{

    flex:1;
}

.notification-title{

    font-size:
        14px;

    font-weight:
        800;

    margin-bottom:
        5px;
}

.notification-message{

    font-size:
        13px;

    line-height:
        1.8;

    color:
        #4b5563;

    word-break:
        break-word;
}

.notification.unread
.notification-message{

    color:
        #374151;
}

.notification-time{

    margin-top:
        9px;

    color:
        #9ca3af;

    font-size:
        10px;
}

.unread-badge{

    display:inline-block;

    background:
        #dc3545;

    color:#fff;

    padding:
        4px 7px;

    border-radius:
        10px;

    font-size:
        9px;

    font-weight:
        700;
}

.read-button{

    border:
        0;

    background:
        transparent;

    color:
        #0d6efd;

    font-size:
        10px;

    cursor:pointer;

    padding:
        3px 0;

    margin-top:
        7px;
}

/* =========================================================
   EMPTY
========================================================= */

.empty{

    background:#fff;

    border:
        1px solid #e5e7eb;

    border-radius:
        16px;

    text-align:
        center;

    padding:
        55px 20px;

    color:#6b7280;
}

.empty i{

    display:block;

    font-size:
        46px;

    margin-bottom:
        10px;

    color:
        #94a3b8;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:600px){

    .page{

        margin:
            12px auto;

        padding:
            0 8px;
    }

    .stats{

        grid-template-columns:
            1fr;
    }

    .header-top{

        align-items:
            stretch;
    }

    .toolbar{

        align-items:
            stretch;
    }

    .filters{

        width:
            100%;
    }

    .filter-btn{

        flex:
            1;

        text-align:
            center;
    }

    .mark-all{

        width:
            100%;
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

<div class="title-area">

<div class="title-icon">

<i class="bi bi-bell-fill"></i>

</div>

<div>

<h1>
<?= $tr['title'] ?>
</h1>

<p>
<?= $tr['subtitle'] ?>
</p>

</div>

</div>

<div>

<a
    href="<?= htmlspecialchars(
        $lang === 'ar'
            ? $enUrl
            : $arUrl
    ) ?>"
    class="btn btn-light btn-sm"
>

<?= $lang === 'ar'
    ? '🇬🇧 EN'
    : '🇸🇦 AR'
?>

</a>

</div>

</div>

</div>

<!-- =====================================================
     STATS
===================================================== -->

<div class="stats">

<div class="stat">

<div class="stat-icon">

<i class="bi bi-bell"></i>

</div>

<div>

<div class="stat-label">

<?= $tr['all'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $totalNotifications
) ?>

</div>

</div>

</div>

<div class="stat">

<div class="stat-icon">

<i class="bi bi-envelope"></i>

</div>

<div>

<div class="stat-label">

<?= $tr['unread'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $unreadNotifications
) ?>

</div>

</div>

</div>

</div>

<!-- =====================================================
     TOOLBAR
===================================================== -->

<div class="toolbar">

<div class="filters">

<a
    href="?<?= http_build_query([
        'lang' =>
            $lang,
        'filter' =>
            'all'
    ]) ?>"
    class="filter-btn <?= $filter === 'all'
        ? 'active'
        : ''
    ?>"
>

<i class="bi bi-list"></i>

<?= $tr['all'] ?>

</a>

<a
    href="?<?= http_build_query([
        'lang' =>
            $lang,
        'filter' =>
            'unread'
    ]) ?>"
    class="filter-btn <?= $filter === 'unread'
        ? 'active'
        : ''
    ?>"
>

<i class="bi bi-envelope"></i>

<?= $tr['unread'] ?>

<?php if (
    $unreadNotifications > 0
): ?>

(
<?= number_format(
    $unreadNotifications
) ?>
)

<?php endif; ?>

</a>

</div>

<?php if (
    $unreadNotifications > 0
): ?>

<form
    method="POST"
    style="margin:0;"
    onsubmit="return confirm(
        '<?= htmlspecialchars(
            $tr['confirm_mark_all'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>'
    );"
>

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars(
        $csrf
    ) ?>"
>

<input
    type="hidden"
    name="action"
    value="mark_all"
>

<button
    type="submit"
    class="mark-all"
>

<i class="bi bi-check2-all"></i>

<?= $tr['mark_all'] ?>

</button>

</form>

<?php endif; ?>

</div>

<!-- =====================================================
     LIST
===================================================== -->

<div class="notifications">

<?php if (
    $result->num_rows === 0
): ?>

<div class="empty">

<i class="bi bi-bell-slash"></i>

<strong>

<?= $tr['no_notifications'] ?>

</strong>

</div>

<?php else: ?>

<?php while (
    $n =
    $result->fetch_assoc()
): ?>

<div
    class="notification <?= (int)$n['is_read'] === 0
        ? 'unread'
        : ''
    ?>"
>

<div class="notification-top">

<div class="notification-icon">

<i class="bi bi-bell"></i>

</div>

<div class="notification-content">

<div class="notification-title">

<?= htmlspecialchars(
    $n['title']
    ?? ''
) ?>

<?php if (
    (int)$n['is_read'] === 0
): ?>

<span class="unread-badge">

<?= $tr['new'] ?>

</span>

<?php endif; ?>

</div>

<div class="notification-message">

<?= nl2br(
    htmlspecialchars(
        $n['message']
        ?? ''
    )
) ?>

</div>

<div class="notification-time">

<i class="bi bi-clock"></i>

<?= htmlspecialchars(
    $n['created_at']
    ?? ''
) ?>

</div>

<?php if (
    (int)$n['is_read'] === 0
): ?>

<form
    method="POST"
    style="margin:0;"
>

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars(
        $csrf
    ) ?>"
>

<input
    type="hidden"
    name="notification_id"
    value="<?= (int)$n['id'] ?>"
>

<input
    type="hidden"
    name="action"
    value="mark_read"
>

<button
    type="submit"
    class="read-button"
>

<i class="bi bi-check2"></i>

<?= $tr['mark_read'] ?>

</button>

</form>

<?php endif; ?>

</div>

</div>

</div>

<?php endwhile; ?>

<?php endif; ?>

</div>

</div>

</body>

</html>

