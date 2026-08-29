<?php

session_start();

include('../include/connected.php');
include('../include/notifications.php');
include('../include/ticket_mail.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   حماية الدخول
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}

$admin_id = (int)$_SESSION['admin_id'];

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
    $_SESSION['dark'] =
        $_GET['dark'] === '1' ? '1' : '0';
}

$dark = $_SESSION['dark'] ?? '0';

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['ticket_csrf'])) {
    $_SESSION['ticket_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['ticket_csrf'];

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'title' => 'عرض التذكرة',
        'back' => 'رجوع للتذاكر',

        'name' => 'الاسم',
        'email' => 'الإيميل',
        'subject' => 'الموضوع',
        'status' => 'الحالة',
        'priority' => 'الأولوية',
        'message' => 'الرسالة',
        'replies' => 'الردود',

        'send' => 'إرسال الرد',
        'update' => 'تحديث التذكرة',

        'ticket_no' => 'رقم التذكرة',
        'created_at' => 'تاريخ الإنشاء',

        'open' => 'مفتوحة',
        'pending' => 'قيد المعالجة',
        'closed' => 'مغلقة',

        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',

        'admin' => 'الإدارة',
        'user' => 'العميل',

        'reply_placeholder' =>
            'اكتب ردك على التذكرة هنا...',

        'status_updated' =>
            'تم تحديث حالة التذكرة',

        'reply_sent' =>
            'تم إرسال الرد بنجاح',

        'invalid_ticket' =>
            'رقم التذكرة غير صحيح',

        'ticket_not_found' =>
            'التذكرة غير موجودة',

        'empty_reply' =>
            'يرجى كتابة الرد أولاً',

        'error' =>
            'حدث خطأ أثناء تنفيذ العملية',

        'ticket_info' =>
            'بيانات التذكرة',

        'conversation' =>
            'المحادثة',

        'actions' =>
            'إجراءات التذكرة',

        'system_note' =>
            'يمكن تغيير الحالة والأولوية ثم حفظ التحديث.',

        'send_email' =>
            'تم إرسال الرد وحفظه في التذكرة.',

        'email_failed' =>
            'تم حفظ الرد، ولكن تعذر إرسال البريد الإلكتروني.'
    ],

    'en' => [

        'title' => 'Ticket View',
        'back' => 'Back to Tickets',

        'name' => 'Name',
        'email' => 'Email',
        'subject' => 'Subject',
        'status' => 'Status',
        'priority' => 'Priority',
        'message' => 'Message',
        'replies' => 'Replies',

        'send' => 'Send Reply',
        'update' => 'Update Ticket',

        'ticket_no' => 'Ticket Number',
        'created_at' => 'Created At',

        'open' => 'Open',
        'pending' => 'Pending',
        'closed' => 'Closed',

        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',

        'admin' => 'Admin',
        'user' => 'Customer',

        'reply_placeholder' =>
            'Write your reply here...',

        'status_updated' =>
            'Ticket status updated successfully',

        'reply_sent' =>
            'Reply sent successfully',

        'invalid_ticket' =>
            'Invalid ticket ID',

        'ticket_not_found' =>
            'Ticket not found',

        'empty_reply' =>
            'Please write a reply first',

        'error' =>
            'An error occurred while processing the request',

        'ticket_info' =>
            'Ticket Information',

        'conversation' =>
            'Conversation',

        'actions' =>
            'Ticket Actions',

        'system_note' =>
            'You can change the ticket status and priority.',

        'send_email' =>
            'Reply saved successfully.',

        'email_failed' =>
            'Reply was saved, but email delivery failed.'
    ]
];

$tr = $t[$lang];

/* =========================================================
   رقم التذكرة
========================================================= */

$ticket_id =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;

if ($ticket_id <= 0) {

    die(
        htmlspecialchars(
            $tr['invalid_ticket']
        )
    );
}

/* =========================================================
   جلب التذكرة
========================================================= */

$stmt = $con->prepare("
    SELECT *
    FROM tickets
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        "SQL ERROR: " .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param(
    "i",
    $ticket_id
);

$stmt->execute();

$ticket =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$ticket) {

    die(
        htmlspecialchars(
            $tr['ticket_not_found']
        )
    );
}

/* =========================================================
   رسائل الصفحة
========================================================= */

$flashMessage = '';
$flashType = 'success';

/* =========================================================
   معالجة POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedCsrf =
        $_POST['csrf'] ?? '';

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {

        $flashMessage =
            $tr['error'];

        $flashType =
            'danger';

    } else {

        /* =================================================
           تحديث التذكرة
        ================================================= */

        if (
            isset(
                $_POST['update_ticket']
            )
        ) {

            $status =
                trim(
                    $_POST['status'] ?? ''
                );

            $priority =
                trim(
                    $_POST['priority'] ?? ''
                );

            /*
             * الحالات متوافقة مع لوحة الدعم:
             * open / pending / closed
             */

            $allowedStatuses = [
                'open',
                'pending',
                'closed'
            ];

            $allowedPriorities = [
                'low',
                'medium',
                'high'
            ];

            if (
                !in_array(
                    $status,
                    $allowedStatuses,
                    true
                )
            ) {

                $status =
                    $ticket['status'] ?? 'open';
            }

            if (
                !in_array(
                    $priority,
                    $allowedPriorities,
                    true
                )
            ) {

                $priority =
                    $ticket['priority'] ?? 'medium';
            }

            $up = $con->prepare("
                UPDATE tickets
                SET
                    status = ?,
                    priority = ?
                WHERE id = ?
            ");

            if (!$up) {

                $flashMessage =
                    "UPDATE ERROR: " .
                    $con->error;

                $flashType =
                    'danger';

            } else {

                $up->bind_param(
                    "ssi",
                    $status,
                    $priority,
                    $ticket_id
                );

                if ($up->execute()) {

                    $flashMessage =
                        $tr['status_updated'];

                    $flashType =
                        'success';

                    $ticket['status'] =
                        $status;

                    $ticket['priority'] =
                        $priority;

                } else {

                    $flashMessage =
                        "UPDATE ERROR: " .
                        $up->error;

                    $flashType =
                        'danger';
                }

                $up->close();
            }
        }

        /* =================================================
           إرسال رد
        ================================================= */

        if (
            isset(
                $_POST['send_reply']
            )
        ) {

            $message =
                trim(
                    $_POST['message'] ?? ''
                );

            if ($message === '') {

                $flashMessage =
                    $tr['empty_reply'];

                $flashType =
                    'warning';

            } else {

                $sender =
                    'admin';

                $ins = $con->prepare("
                    INSERT INTO ticket_replies
                    (
                        ticket_id,
                        admin_id,
                        sender,
                        message
                    )
                    VALUES (?, ?, ?, ?)
                ");

                if (!$ins) {

                    $flashMessage =
                        "REPLY ERROR: " .
                        $con->error;

                    $flashType =
                        'danger';

                } else {

                    $ins->bind_param(
                        "iiss",
                        $ticket_id,
                        $admin_id,
                        $sender,
                        $message
                    );

                    if ($ins->execute()) {

                        /* =========================
                           Notification
                        ========================= */

                        if (
                            !empty(
                                $ticket['user_id']
                            )
                        ) {

                            addNotification(
                                $con,
                                (int)$ticket['user_id'],
                                "💬 رد جديد على التذكرة",
                                "تم الرد على تذكرتك رقم #" .
                                ($ticket['ticket_number'] ?? $ticket_id),
                                "ticket",
                                $ticket_id
                            );
                        }

                        /* =========================
                           Email
                        ========================= */

                        $mailResult = false;

                        if (
                            !empty(
                                $ticket['email']
                            )
                        ) {

                            $mailResult =
                                sendTicketReplyMail(
                                    $ticket['email'],
                                    $ticket['name'] ?? '',
                                    $ticket['ticket_number'] ?? $ticket_id,
                                    $message
                                );
                        }

                        if ($mailResult) {

                            $flashMessage =
                                $tr['reply_sent'];

                            $flashType =
                                'success';

                        } else {

                            /*
                             * لا نحذف الرد لأن البريد فشل.
                             * الرد محفوظ بالفعل في قاعدة البيانات.
                             */

                            $flashMessage =
                                $tr['email_failed'];

                            $flashType =
                                'warning';
                        }

                    } else {

                        $flashMessage =
                            "REPLY ERROR: " .
                            $ins->error;

                        $flashType =
                            'danger';
                    }

                    $ins->close();
                }
            }
        }
    }
}

/* =========================================================
   جلب الردود
========================================================= */

$replyStmt = $con->prepare("
    SELECT *
    FROM ticket_replies
    WHERE ticket_id = ?
    ORDER BY id ASC
");

if (!$replyStmt) {

    die(
        "REPLIES SQL ERROR: " .
        htmlspecialchars($con->error)
    );
}

$replyStmt->bind_param(
    "i",
    $ticket_id
);

$replyStmt->execute();

$replies =
    $replyStmt
        ->get_result();

/* =========================================================
   Status badge
========================================================= */

$statusValue =
    $ticket['status']
    ?? 'open';

$statusClass = match ($statusValue) {

    'closed' =>
        'status-closed',

    'pending' =>
        'status-pending',

    default =>
        'status-open'
};

$statusText =
    $tr[$statusValue]
    ?? $statusValue;

/* =========================================================
   Priority badge
========================================================= */

$priorityValue =
    $ticket['priority']
    ?? 'medium';

$priorityClass = match ($priorityValue) {

    'high' =>
        'priority-high',

    'low' =>
        'priority-low',

    default =>
        'priority-medium'
};

$priorityText =
    $tr[$priorityValue]
    ?? $priorityValue;

/* =========================================================
   روابط الهيدر
========================================================= */

$backUrl =
    'tickets.php?' .
    http_build_query([
        'lang' =>
            $lang,
        'dark' =>
            $dark
    ]);

$langArUrl =
    '?' .
    http_build_query([
        'id' =>
            $ticket_id,
        'lang' =>
            'ar',
        'dark' =>
            $dark
    ]);

$langEnUrl =
    '?' .
    http_build_query([
        'id' =>
            $ticket_id,
        'lang' =>
            'en',
        'dark' =>
            $dark
    ]);

$darkUrl =
    '?' .
    http_build_query([
        'id' =>
            $ticket_id,
        'lang' =>
            $lang,
        'dark' =>
            '1'
    ]);

$lightUrl =
    '?' .
    http_build_query([
        'id' =>
            $ticket_id,
        'lang' =>
            $lang,
        'dark' =>
            '0'
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
        <?= $dark === '1'
            ? '#0f172a'
            : '#f4f7fb'
        ?>;

    --card:
        <?= $dark === '1'
            ? '#1e293b'
            : '#ffffff'
        ?>;

    --soft:
        <?= $dark === '1'
            ? '#172033'
            : '#f8fafc'
        ?>;

    --text:
        <?= $dark === '1'
            ? '#f8fafc'
            : '#1f2937'
        ?>;

    --muted:
        <?= $dark === '1'
            ? '#94a3b8'
            : '#6b7280'
        ?>;

    --border:
        <?= $dark === '1'
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
        1100px;

    margin:
        25px auto;

    padding:
        0 15px;
}

/* =========================================================
   TOP BAR
========================================================= */

.topbar{

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        10px;

    margin-bottom:
        15px;
}

.topbar-left,
.topbar-right{

    display:flex;

    align-items:
        center;

    gap:
        7px;

    flex-wrap:
        wrap;
}

.topbar a{

    text-decoration:none;
}

/* =========================================================
   MAIN HEADER
========================================================= */

.ticket-header{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:#fff;

    border-radius:
        18px;

    padding:
        22px;

    margin-bottom:
        18px;

    box-shadow:
        0 7px 25px
        rgba(0,0,0,.12);
}

.ticket-header-top{

    display:flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    gap:
        15px;
}

.ticket-heading{

    display:flex;

    gap:
        13px;

    align-items:
        center;
}

.ticket-icon{

    width:
        55px;

    height:
        55px;

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
        25px;
}

.ticket-heading h1{

    margin:0;

    font-size:
        22px;

    font-weight:
        800;
}

.ticket-heading p{

    margin:
        4px 0 0;

    opacity:
        .85;

    font-size:
        12px;
}

.ticket-number{

    margin-top:
        7px;

    display:inline-block;

    background:
        rgba(255,255,255,.16);

    padding:
        6px 13px;

    border-radius:
        20px;

    font-size:
        12px;
}

/* =========================================================
   CARD
========================================================= */

.card-box{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        16px;

    padding:
        18px;

    margin-bottom:
        18px;

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
        16px;

    font-weight:
        800;
}

/* =========================================================
   INFO GRID
========================================================= */

.info-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        12px;
}

.info-item{

    background:
        var(--soft);

    border:
        1px solid
        var(--border);

    border-radius:
        11px;

    padding:
        12px;
}

.info-label{

    display:block;

    font-size:
        10px;

    color:
        var(--muted);

    margin-bottom:
        4px;
}

.info-value{

    font-size:
        13px;

    font-weight:
        700;

    word-break:
        break-word;
}

/* =========================================================
   MESSAGE
========================================================= */

.ticket-message{

    background:
        var(--soft);

    border:
        1px solid
        var(--border);

    border-radius:
        13px;

    padding:
        16px;

    line-height:
        1.9;

    white-space:
        normal;

    word-break:
        break-word;
}

/* =========================================================
   BADGES
========================================================= */

.badge-status,
.badge-priority{

    display:inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        6px 11px;

    border-radius:
        20px;

    font-size:
        11px;

    font-weight:
        700;
}

.status-open{

    background:
        rgba(25,135,84,.12);

    color:
        #198754;
}

.status-pending{

    background:
        rgba(253,126,20,.12);

    color:
        #fd7e14;
}

.status-closed{

    background:
        rgba(220,53,69,.12);

    color:
        #dc3545;
}

.priority-low{

    background:
        rgba(13,110,253,.12);

    color:
        #0d6efd;
}

.priority-medium{

    background:
        rgba(253,126,20,.12);

    color:
        #fd7e14;
}

.priority-high{

    background:
        rgba(220,53,69,.12);

    color:
        #dc3545;
}

/* =========================================================
   CHAT
========================================================= */

.chat{

    max-height:
        520px;

    overflow-y:
        auto;

    padding:
        5px;
}

.chat-empty{

    text-align:
        center;

    color:
        var(--muted);

    padding:
        35px;
}

.message-wrap{

    display:flex;

    margin:
        12px 0;
}

.message-wrap.admin{

    justify-content:
        flex-start;
}

.message-wrap.user{

    justify-content:
        flex-end;
}

.message-bubble{

    max-width:
        75%;

    padding:
        12px 15px;

    border-radius:
        15px;

    line-height:
        1.8;

    word-break:
        break-word;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,.05);
}

.message-wrap.admin
.message-bubble{

    background:
        #0d6efd;

    color:#fff;

    border-bottom-right-radius:
        5px;
}

html[dir="ltr"]
.message-wrap.admin
.message-bubble{

    border-bottom-left-radius:
        5px;

    border-bottom-right-radius:
        15px;
}

.message-wrap.user
.message-bubble{

    background:
        var(--soft);

    color:
        var(--text);

    border:
        1px solid
        var(--border);

    border-bottom-left-radius:
        5px;
}

html[dir="ltr"]
.message-wrap.user
.message-bubble{

    border-bottom-right-radius:
        5px;

    border-bottom-left-radius:
        15px;
}

.message-meta{

    margin-bottom:
        5px;

    font-size:
        10px;

    font-weight:
        700;

    opacity:
        .75;
}

/* =========================================================
   FORMS
========================================================= */

.form-control,
.form-select,
textarea{

    border-radius:
        9px;

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        var(--border);
}

.form-control:focus,
.form-select:focus,
textarea:focus{

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        #0d6efd;

    box-shadow:
        0 0 0 .15rem
        rgba(13,110,253,.12);
}

textarea{

    min-height:
        130px;

    resize:
        vertical;
}

/* =========================================================
   ALERT
========================================================= */

.alert{

    border-radius:
        11px;

    font-size:
        12px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px){

    .info-grid{

        grid-template-columns:
            repeat(2,1fr);
    }

    .ticket-header-top{

        flex-direction:
            column;
    }
}

@media(max-width:600px){

    .page{

        padding:
            0 9px;
    }

    .info-grid{

        grid-template-columns:
            1fr;
    }

    .message-bubble{

        max-width:
            88%;
    }

    .topbar-left,
    .topbar-right{

        width:
            100%;
    }

    .topbar-right .btn{

        flex:
            1;
    }
}

</style>

</head>

<body>

<div class="page">

<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="topbar">

<div class="topbar-left">

<a
    href="<?= htmlspecialchars($backUrl) ?>"
    class="btn btn-outline-primary"
>
    <i class="bi bi-arrow-right"></i>
    <?= $tr['back'] ?>
</a>

</div>

<div class="topbar-right">

<a
    href="<?= htmlspecialchars($lang === 'ar' ? $langEnUrl : $langArUrl) ?>"
    class="btn btn-outline-secondary"
>
    <?= $lang === 'ar'
        ? '🇬🇧 EN'
        : '🇸🇦 AR'
    ?>
</a>

<?php if ($dark === '1'): ?>

<a
    href="<?= htmlspecialchars($lightUrl) ?>"
    class="btn btn-light"
>
    <i class="bi bi-sun"></i>
</a>

<?php else: ?>

<a
    href="<?= htmlspecialchars($darkUrl) ?>"
    class="btn btn-dark"
>
    <i class="bi bi-moon-stars"></i>
</a>

<?php endif; ?>

</div>

</div>

<!-- =====================================================
     HEADER
===================================================== -->

<div class="ticket-header">

<div class="ticket-header-top">

<div class="ticket-heading">

<div class="ticket-icon">

<i class="bi bi-ticket-detailed"></i>

</div>

<div>

<h1>

<?= htmlspecialchars(
    $tr['title']
) ?>

</h1>

<p>

<?= htmlspecialchars(
    $ticket['subject'] ?? '-'
) ?>

</p>

<span class="ticket-number">

#<?= htmlspecialchars(
    $ticket['ticket_number']
    ?? $ticket_id
) ?>

</span>

</div>

</div>

<div>

<span
    class="badge-status <?= $statusClass ?>"
>

<i class="bi bi-circle-fill"></i>

<?= htmlspecialchars(
    $statusText
) ?>

</span>

<br><br>

<span
    class="badge-priority <?= $priorityClass ?>"
>

<i class="bi bi-flag-fill"></i>

<?= htmlspecialchars(
    $priorityText
) ?>

</span>

</div>

</div>

</div>

<!-- =====================================================
     FLASH MESSAGE
===================================================== -->

<?php if ($flashMessage !== ''): ?>

<div
    class="alert alert-<?= htmlspecialchars($flashType) ?>"
>

<?= htmlspecialchars(
    $flashMessage
) ?>

</div>

<?php endif; ?>

<!-- =====================================================
     TICKET INFO
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-info-circle text-primary"></i>

<?= $tr['ticket_info'] ?>

</div>

<div class="info-grid">

<div class="info-item">

<span class="info-label">

<?= $tr['ticket_no'] ?>

</span>

<span class="info-value">

#<?= htmlspecialchars(
    $ticket['ticket_number']
    ?? $ticket_id
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['name'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $ticket['name']
    ?? '-'
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['email'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $ticket['email']
    ?? '-'
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['created_at'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $ticket['created_at']
    ?? '-'
) ?>

</span>

</div>

</div>

<br>

<div class="info-grid">

<div class="info-item">

<span class="info-label">

<?= $tr['subject'] ?>

</span>

<span class="info-value">

<?= htmlspecialchars(
    $ticket['subject']
    ?? '-'
) ?>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['status'] ?>

</span>

<span class="info-value">

<span
    class="badge-status <?= $statusClass ?>"
>

<?= htmlspecialchars(
    $statusText
) ?>

</span>

</span>

</div>

<div class="info-item">

<span class="info-label">

<?= $tr['priority'] ?>

</span>

<span class="info-value">

<span
    class="badge-priority <?= $priorityClass ?>"
>

<?= htmlspecialchars(
    $priorityText
) ?>

</span>

</span>

</div>

</div>

<br>

<div class="section-title">

<i class="bi bi-chat-left-text"></i>

<?= $tr['message'] ?>

</div>

<div class="ticket-message">

<?= nl2br(
    htmlspecialchars(
        $ticket['message']
        ?? '-'
    )
) ?>

</div>

</div>

<!-- =====================================================
     UPDATE
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-sliders"></i>

<?= $tr['actions'] ?>

</div>

<p
    class="text-muted"
    style="font-size:11px;"
>

<?= htmlspecialchars(
    $tr['system_note']
) ?>

</p>

<form method="POST">

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars($csrf) ?>"
>

<div class="row g-3">

<div class="col-md-5">

<label class="form-label">

<?= $tr['status'] ?>

</label>

<select
    name="status"
    class="form-select"
>

<option
    value="open"
    <?= $statusValue === 'open'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['open'] ?>

</option>

<option
    value="pending"
    <?= $statusValue === 'pending'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['pending'] ?>

</option>

<option
    value="closed"
    <?= $statusValue === 'closed'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['closed'] ?>

</option>

</select>

</div>

<div class="col-md-5">

<label class="form-label">

<?= $tr['priority'] ?>

</label>

<select
    name="priority"
    class="form-select"
>

<option
    value="low"
    <?= $priorityValue === 'low'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['low'] ?>

</option>

<option
    value="medium"
    <?= $priorityValue === 'medium'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['medium'] ?>

</option>

<option
    value="high"
    <?= $priorityValue === 'high'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['high'] ?>

</option>

</select>

</div>

<div class="col-md-2 d-flex align-items-end">

<button
    type="submit"
    name="update_ticket"
    class="btn btn-primary w-100"
>

<i class="bi bi-check2-circle"></i>

<?= $tr['update'] ?>

</button>

</div>

</div>

</form>

</div>

<!-- =====================================================
     CONVERSATION
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-chat-dots text-primary"></i>

<?= $tr['conversation'] ?>

</div>

<div class="chat">

<?php if ($replies->num_rows === 0): ?>

<div class="chat-empty">

<i
    class="bi bi-chat-square-text"
    style="font-size:35px;"
></i>

<br>

<?= $tr['replies'] ?>: 0

</div>

<?php else: ?>

<?php while (
    $r =
    $replies->fetch_assoc()
): ?>

<?php

$sender =
    $r['sender']
    ?? 'user';

$isAdmin =
    $sender === 'admin';

?>

<div
    class="message-wrap <?= $isAdmin
        ? 'admin'
        : 'user'
    ?>"
>

<div class="message-bubble">

<div class="message-meta">

<?= $isAdmin
    ? '👨‍💼 ' . $tr['admin']
    : '👤 ' . $tr['user']
?>

<?php if (!empty($r['created_at'])): ?>

&nbsp; • &nbsp;

<?= htmlspecialchars(
    $r['created_at']
) ?>

<?php endif; ?>

</div>

<div>

<?= nl2br(
    htmlspecialchars(
        $r['message']
        ?? ''
    )
) ?>

</div>

</div>

</div>

<?php endwhile; ?>

<?php endif; ?>

</div>

</div>

<!-- =====================================================
     SEND REPLY
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-reply-fill text-success"></i>

<?= $tr['send'] ?>

</div>

<form method="POST">

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars($csrf) ?>"
>

<textarea
    name="message"
    class="form-control"
    placeholder="<?= htmlspecialchars(
        $tr['reply_placeholder']
    ) ?>"
    required
></textarea>

<div
    class="d-flex justify-content-end mt-3"
>

<button
    type="submit"
    name="send_reply"
    class="btn btn-success px-4"
>

<i class="bi bi-send-fill"></i>

<?= $tr['send'] ?>

</button>

</div>

</form>

</div>

</div>

</body>

</html>