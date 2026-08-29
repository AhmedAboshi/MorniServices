
<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

/* =========================================================
   حماية الأدمن
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
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
   الوضع الليلي
========================================================= */

if (isset($_GET['dark'])) {
    $_SESSION['dark'] = $_GET['dark'] === '1' ? '1' : '0';
}

$dark = $_SESSION['dark'] ?? '0';

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['notifications_csrf'])) {
    $_SESSION['notifications_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['notifications_csrf'];

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [
        'title' => 'إدارة الإشعارات',
        'subtitle' => 'مراجعة التنبيهات والأحداث الجديدة في النظام',

        'unread' => 'غير المقروءة',
        'count' => 'عدد الإشعارات غير المقروءة',

        'mark_read' => 'تمت القراءة',
        'mark_all' => 'تحديد الكل كمقروء',
        'details' => 'تفاصيل',

        'new' => 'جديد',
        'empty' => 'لا توجد إشعارات جديدة',

        'dashboard' => 'لوحة التحكم',

        'confirm_all' =>
            'هل تريد تحديد جميع الإشعارات كمقروءة؟',

        'general' => 'عام',
        'iqama' => 'الإقامة',
        'card' => 'بطاقة السائق',
        'fleet' => 'الأسطول',
        'operation' => 'التشغيل',
        'ticket' => 'الدعم الفني',

        'invalid' => 'طلب غير صالح',
        'error' => 'حدث خطأ أثناء تنفيذ العملية'
    ],

    'en' => [
        'title' => 'Notifications Management',
        'subtitle' => 'Review system alerts and new events',

        'unread' => 'Unread',
        'count' => 'Unread Notifications',

        'mark_read' => 'Mark as Read',
        'mark_all' => 'Mark All as Read',
        'details' => 'Details',

        'new' => 'New',
        'empty' => 'No new notifications',

        'dashboard' => 'Dashboard',

        'confirm_all' =>
            'Mark all notifications as read?',

        'general' => 'General',
        'iqama' => 'Iqama',
        'card' => 'Driver Card',
        'fleet' => 'Fleet',
        'operation' => 'Operation',
        'ticket' => 'Support',

        'invalid' => 'Invalid request',
        'error' => 'An error occurred'
    ]
];

$tr = $text[$lang];

/* =========================================================
   Functions
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function getTypeLabel($type, $tr)
{
    switch ($type) {

        case 'iqama':
            return $tr['iqama'];

        case 'card':
            return $tr['card'];

        case 'fleet':
            return $tr['fleet'];

        case 'operation':
            return $tr['operation'];

        case 'ticket':
            return $tr['ticket'];

        default:
            return $tr['general'];
    }
}

function getTypeClass($type)
{
    switch ($type) {

        case 'iqama':
            return 'type-iqama';

        case 'card':
            return 'type-card';

        case 'fleet':
            return 'type-fleet';

        case 'operation':
            return 'type-operation';

        case 'ticket':
            return 'type-ticket';

        default:
            return 'type-general';
    }
}

function getTypeIcon($type)
{
    switch ($type) {

        case 'iqama':
            return 'bi-person-vcard';

        case 'card':
            return 'bi-credit-card';

        case 'fleet':
            return 'bi-truck';

        case 'operation':
            return 'bi-gear';

        case 'ticket':
            return 'bi-headset';

        default:
            return 'bi-bell';
    }
}

/* =========================================================
   POST ACTIONS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $postedCsrf = $_POST['csrf'] ?? '';

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {
        header(
            "Location: notifications.php?lang=" .
            urlencode($lang) .
            "&dark=" .
            urlencode($dark)
        );
        exit();
    }

    /* =============================================
       تعليم إشعار واحد كمقروء
    ============================================= */

    if ($action === 'mark_read') {

        $notificationId =
            (int)($_POST['notification_id'] ?? 0);

        if ($notificationId > 0) {

            $stmt = $con->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = ?
                LIMIT 1
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $notificationId
                );

                $stmt->execute();

                $stmt->close();
            }
        }

        header(
            "Location: notifications.php?lang=" .
            urlencode($lang) .
            "&dark=" .
            urlencode($dark)
        );

        exit();
    }

    /* =============================================
       تعليم جميع الإشعارات كمقروءة
    ============================================= */

    if ($action === 'mark_all') {

        $stmt = $con->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE is_read = 0
        ");

        if ($stmt) {

            $stmt->execute();
            $stmt->close();
        }

        header(
            "Location: notifications.php?lang=" .
            urlencode($lang) .
            "&dark=" .
            urlencode($dark)
        );

        exit();
    }
}

/* =========================================================
   UNREAD COUNT
========================================================= */

$countResult = $con->query("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE is_read = 0
");

$countRow = $countResult
    ? $countResult->fetch_assoc()
    : [];

$unreadCount =
    (int)($countRow['total'] ?? 0);

/* =========================================================
   NOTIFICATIONS
========================================================= */

$result = $con->query("
    SELECT
        id,
        title,
        message,
        type,
        ref_id,
        is_read,
        created_at
    FROM notifications
    WHERE is_read = 0
    ORDER BY id DESC
");

/* =========================================================
   URLs
========================================================= */

$arUrl =
    'notifications.php?' .
    http_build_query([
        'lang' => 'ar',
        'dark' => $dark
    ]);

$enUrl =
    'notifications.php?' .
    http_build_query([
        'lang' => 'en',
        'dark' => $dark
    ]);

$themeUrl =
    'notifications.php?' .
    http_build_query([
        'lang' => $lang,
        'dark' => ($dark === '1' ? '0' : '1')
    ]);

?>

<!DOCTYPE html>

<html
    lang="<?= e($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title><?= e($tr['title']) ?></title>

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

    --bg: <?= $dark === '1'
        ? '#0f172a'
        : '#f4f7fb'
    ?>;

    --card: <?= $dark === '1'
        ? '#1e293b'
        : '#ffffff'
    ?>;

    --soft: <?= $dark === '1'
        ? '#172033'
        : '#f8fafc'
    ?>;

    --text: <?= $dark === '1'
        ? '#f8fafc'
        : '#1f2937'
    ?>;

    --muted: <?= $dark === '1'
        ? '#94a3b8'
        : '#6b7280'
    ?>;

    --border: <?= $dark === '1'
        ? '#334155'
        : '#e5e7eb'
    ?>;
}

body{

    margin:0;

    background:var(--bg);

    color:var(--text);

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;
}

.page{

    max-width:1100px;

    margin:30px auto;

    padding:0 15px;
}

/* ===============================
   Header
================================ */

.page-header{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:#fff;

    border-radius:20px;

    padding:22px;

    margin-bottom:18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.12);
}

.header-top{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    flex-wrap:wrap;
}

.title-area{

    display:flex;

    align-items:center;

    gap:12px;
}

.title-icon{

    width:55px;

    height:55px;

    border-radius:15px;

    background:
        rgba(255,255,255,.17);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;
}

.page-title h1{

    margin:0;

    font-size:25px;

    font-weight:800;
}

.page-title p{

    margin:5px 0 0;

    font-size:12px;

    opacity:.88;
}

.header-actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;
}

.header-actions a{

    text-decoration:none;
}

/* ===============================
   Stats
================================ */

.stats-card{

    background:var(--card);

    border:1px solid var(--border);

    border-radius:16px;

    padding:17px;

    margin-bottom:18px;

    display:flex;

    align-items:center;

    gap:12px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);
}

.stats-icon{

    width:46px;

    height:46px;

    border-radius:12px;

    background:#dc3545;

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;
}

.stats-label{

    color:var(--muted);

    font-size:11px;

    font-weight:700;
}

.stats-number{

    font-size:25px;

    font-weight:800;
}

/* ===============================
   Toolbar
================================ */

.toolbar{

    background:var(--card);

    border:1px solid var(--border);

    border-radius:15px;

    padding:13px;

    margin-bottom:16px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:10px;

    flex-wrap:wrap;
}

.mark-all{

    border:0;

    padding:9px 14px;

    border-radius:9px;

    background:#198754;

    color:#fff;

    font-size:11px;

    font-weight:700;

    cursor:pointer;
}

/* ===============================
   Notification
================================ */

.notification{

    background:var(--card);

    border:1px solid var(--border);

    border-right:5px solid #94a3b8;

    border-radius:15px;

    padding:17px;

    margin-bottom:12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);
}

[dir="ltr"] .notification{

    border-right:1px solid var(--border);

    border-left:5px solid #94a3b8;
}

.type-iqama{
    border-right-color:#dc3545;
}

.type-card{
    border-right-color:#0d6efd;
}

.type-fleet{
    border-right-color:#fd7e14;
}

.type-operation{
    border-right-color:#ffc107;
}

.type-ticket{
    border-right-color:#6f42c1;
}

.type-general{
    border-right-color:#6c757d;
}

[dir="ltr"] .type-iqama{
    border-left-color:#dc3545;
}

[dir="ltr"] .type-card{
    border-left-color:#0d6efd;
}

[dir="ltr"] .type-fleet{
    border-left-color:#fd7e14;
}

[dir="ltr"] .type-operation{
    border-left-color:#ffc107;
}

[dir="ltr"] .type-ticket{
    border-left-color:#6f42c1;
}

.notification-top{

    display:flex;

    align-items:flex-start;

    gap:12px;
}

.notification-icon{

    width:43px;

    height:43px;

    flex-shrink:0;

    border-radius:11px;

    background:var(--soft);

    display:flex;

    align-items:center;

    justify-content:center;

    color:#0d6efd;

    font-size:19px;
}

.notification-main{

    flex:1;

    min-width:0;
}

.notification-title{

    font-size:14px;

    font-weight:800;

    margin-bottom:6px;

    color:var(--text);
}

.new-badge{

    display:inline-block;

    margin-right:6px;

    margin-left:6px;

    background:#dc3545;

    color:#fff;

    border-radius:20px;

    padding:3px 7px;

    font-size:9px;

    font-weight:700;
}

.notification-message{

    font-size:12px;

    line-height:1.9;

    color:var(--muted);

    word-break:break-word;
}

.notification-meta{

    display:flex;

    align-items:center;

    gap:8px;

    flex-wrap:wrap;

    margin-top:10px;

    color:var(--muted);

    font-size:10px;
}

.type-label{

    background:var(--soft);

    border:1px solid var(--border);

    padding:4px 8px;

    border-radius:20px;

    font-size:9px;

    font-weight:700;
}

.actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;

    margin-top:13px;
}

.action-btn{

    display:inline-flex;

    align-items:center;

    gap:5px;

    padding:7px 11px;

    border-radius:8px;

    border:0;

    text-decoration:none;

    font-size:10px;

    font-weight:700;

    cursor:pointer;
}

.details-btn{

    background:#0d6efd;

    color:#fff;
}

.read-btn{

    background:#198754;

    color:#fff;
}

/* ===============================
   Empty
================================ */

.empty{

    background:var(--card);

    border:1px solid var(--border);

    border-radius:16px;

    padding:60px 20px;

    text-align:center;

    color:var(--muted);
}

.empty i{

    display:block;

    font-size:48px;

    margin-bottom:10px;
}

/* ===============================
   Responsive
================================ */

@media(max-width:650px){

    .page{

        margin:12px auto;

        padding:0 8px;
    }

    .header-actions{

        width:100%;
    }

    .header-actions .btn{

        flex:1;
    }

    .toolbar{

        align-items:stretch;
    }

    .mark-all{

        width:100%;
    }

    .notification-title{

        font-size:13px;
    }
}

/* ===============================
   Print
================================ */

@media print{

    .no-print{

        display:none !important;
    }

    body{

        background:#fff !important;

        color:#000 !important;
    }

    .page{

        max-width:100%;

        margin:0;

        padding:0;
    }

    .page-header,
    .stats-card,
    .notification{

        box-shadow:none;
    }
}

</style>

<audio
    id="notifSound"
    src="../sound/notify.mp3"
    preload="auto"
></audio>

</head>

<body>

<div class="page">

<!-- ===============================
     HEADER
================================ -->

<div class="page-header">

<div class="header-top">

<div class="title-area">

<div class="title-icon">

<i class="bi bi-bell-fill"></i>

</div>

<div class="page-title">

<h1>
<?= e($tr['title']) ?>
</h1>

<p>
<?= e($tr['subtitle']) ?>
</p>

</div>

</div>

<div class="header-actions no-print">

<a
    href="<?= e($arUrl) ?>"
    class="btn btn-light btn-sm"
>
🇸🇦 AR
</a>

<a
    href="<?= e($enUrl) ?>"
    class="btn btn-light btn-sm"
>
🇬🇧 EN
</a>

<a
    href="<?= e($themeUrl) ?>"
    class="btn <?= $dark === '1'
        ? 'btn-light'
        : 'btn-dark'
    ?> btn-sm"
>

<i class="bi <?= $dark === '1'
    ? 'bi-sun'
    : 'bi-moon-stars'
?>"></i>

</a>

<a
    href="newadmin.php"
    class="btn btn-outline-light btn-sm"
>

<i class="bi bi-speedometer2"></i>

<?= e($tr['dashboard']) ?>

</a>

</div>

</div>

</div>

<!-- ===============================
     STATS
================================ -->

<div class="stats-card no-print">

<div class="stats-icon">

<i class="bi bi-bell"></i>

</div>

<div>

<div class="stats-label">

<?= e($tr['count']) ?>

</div>

<div
    class="stats-number"
    id="notificationCount"
>

<?= number_format(
    $unreadCount
) ?>

</div>

</div>

</div>

<!-- ===============================
     TOOLBAR
================================ -->

<div class="toolbar no-print">

<div>

<strong>

<i class="bi bi-list-check"></i>

<?= e($tr['unread']) ?>

</strong>

</div>

<?php if ($unreadCount > 0): ?>

<form
    method="POST"
    style="margin:0;"
    onsubmit="return confirmMarkAll();"
>

<input
    type="hidden"
    name="csrf"
    value="<?= e($csrf) ?>"
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

<?= e($tr['mark_all']) ?>

</button>

</form>

<?php endif; ?>

</div>

<!-- ===============================
     LIST
================================ -->

<div id="notificationsList">

<?php if (
    !$result ||
    $result->num_rows === 0
): ?>

<div class="empty">

<i class="bi bi-bell-slash"></i>

<?= e($tr['empty']) ?>

</div>

<?php else: ?>

<?php while (
    $n = $result->fetch_assoc()
): ?>

<?php

$type =
    $n['type'] ?? 'general';

$refId =
    (int)($n['ref_id'] ?? 0);

$typeClass =
    getTypeClass($type);

$typeIcon =
    getTypeIcon($type);

$typeLabel =
    getTypeLabel(
        $type,
        $tr
    );

$detailsUrl =
    '../get_details.php?' .
    http_build_query([
        'type' => $type,
        'id' => $refId
    ]);

?>

<div
    class="notification <?= e($typeClass) ?>"
    data-id="<?= (int)$n['id'] ?>"
>

<div class="notification-top">

<div class="notification-icon">

<i class="bi <?= e($typeIcon) ?>"></i>

</div>

<div class="notification-main">

<div class="notification-title">

<?= e(
    $n['title'] ?? ''
) ?>

<span class="new-badge">
<?= e($tr['new']) ?>
</span>

</div>

<div class="notification-message">

<?= nl2br(
    e(
        $n['message'] ?? ''
    )
) ?>

</div>

<div class="notification-meta">

<span>

<i class="bi bi-clock"></i>

<?= e(
    $n['created_at'] ?? ''
) ?>

</span>

<span class="type-label">

<?= e($typeLabel) ?>

</span>

</div>

<div class="actions">

<?php if ($refId > 0): ?>

<a
    href="<?= e($detailsUrl) ?>"
    class="action-btn details-btn"
>

<i class="bi bi-eye"></i>

<?= e($tr['details']) ?>

</a>

<?php endif; ?>

<form
    method="POST"
    style="margin:0;"
>

<input
    type="hidden"
    name="csrf"
    value="<?= e($csrf) ?>"
>

<input
    type="hidden"
    name="action"
    value="mark_read"
>

<input
    type="hidden"
    name="notification_id"
    value="<?= (int)$n['id'] ?>"
>

<button
    type="submit"
    class="action-btn read-btn"
>

<i class="bi bi-check2"></i>

<?= e($tr['mark_read']) ?>

</button>

</form>

</div>

</div>

</div>

</div>

<?php endwhile; ?>

<?php endif; ?>

</div>

</div>

<script>

/* =========================================================
   Confirm Mark All
========================================================= */

function confirmMarkAll()
{
    return confirm(
        <?= json_encode(
            $tr['confirm_all'],
            JSON_UNESCAPED_UNICODE
        ) ?>
    );
}

/* =========================================================
   Sound
========================================================= */

let lastCount =
    <?= (int)$unreadCount ?>;

function playNotificationSound()
{
    const audio =
        document.getElementById('notifSound');

    if (!audio) {
        return;
    }

    audio.currentTime = 0;

    audio.play().catch(
        function () {}
    );
}

/* =========================================================
   Enable Sound after user interaction
========================================================= */

document.addEventListener(
    'click',
    function enableNotificationSound()
    {
        const audio =
            document.getElementById(
                'notifSound'
            );

        if (!audio) {
            return;
        }

        audio.muted = true;

        audio.play()
            .then(
                function ()
                {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.muted = false;
                }
            )
            .catch(
                function () {}
            );

        document.removeEventListener(
            'click',
            enableNotificationSound
        );
    }
);

/* =========================================================
   Polling
========================================================= */

async function checkNotifications()
{
    try {

        const response =
            await fetch(
                'get_notifications_count.php',
                {
                    cache: 'no-store'
                }
            );

        if (!response.ok) {
            return;
        }

        const data =
            await response.json();

        const currentCount =
            parseInt(
                data.count || 0,
                10
            );

        const countElement =
            document.getElementById(
                'notificationCount'
            );

        if (countElement) {

            countElement.textContent =
                currentCount;
        }

        if (currentCount > lastCount) {

            playNotificationSound();

            setTimeout(
                function ()
                {
                    window.location.reload();
                },
                800
            );
        }

        lastCount =
            currentCount;

    } catch (error) {

        console.log(
            'Notification polling error:',
            error
        );
    }
}

setInterval(
    checkNotifications,
    5000
);

</script>

</body>

</html>

