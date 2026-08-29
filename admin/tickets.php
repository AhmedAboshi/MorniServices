
<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, "utf8mb4");

/* =========================================================
   حماية الأدمن
========================================================= */

// فعّلها إذا كانت الصفحة داخل لوحة الأدمن
/*
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}
*/

$admin_id = (int)($_SESSION['admin_id'] ?? 0);

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
        $_GET['dark'] === '1'
            ? '1'
            : '0';
}

$dark = $_SESSION['dark'] ?? '0';

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'title' => 'مركز الدعم الفني',

        'subtitle' =>
            'إدارة ومتابعة تذاكر الدعم الفني',

        'all' => 'الكل',

        'open' => 'مفتوحة',

        'pending' => 'قيد المعالجة',

        'closed' => 'مغلقة',

        'status' => 'الحالة',

        'priority' => 'الأولوية',

        'low' => 'منخفضة',

        'medium' => 'متوسطة',

        'high' => 'عالية',

        'filter' => 'تصفية',

        'search' => 'بحث',

        'search_placeholder' =>
            'الاسم أو الإيميل أو رقم التذكرة أو الموضوع',

        'name' => 'الاسم',

        'email' => 'الإيميل',

        'subject' => 'الموضوع',

        'ticket_no' => 'رقم التذكرة',

        'created_at' => 'تاريخ الإنشاء',

        'replies' => 'الردود',

        'action' => 'الإجراء',

        'view' => 'عرض',

        'tickets' => 'التذاكر',

        'total' => 'إجمالي التذاكر',

        'open_count' => 'المفتوحة',

        'pending_count' => 'قيد المعالجة',

        'closed_count' => 'المغلقة',

        'reset' => 'إعادة ضبط',

        'previous' => 'السابق',

        'next' => 'التالي',

        'page' => 'صفحة',

        'no_data' =>
            'لا توجد تذاكر مطابقة للفلاتر',

        'today' => 'اليوم',

        'system' => 'نظام الدعم',

        'active' => 'نشط',

        'language' => 'اللغة'
    ],

    'en' => [

        'title' => 'Support Center',

        'subtitle' =>
            'Manage and monitor support tickets',

        'all' => 'All',

        'open' => 'Open',

        'pending' => 'Pending',

        'closed' => 'Closed',

        'status' => 'Status',

        'priority' => 'Priority',

        'low' => 'Low',

        'medium' => 'Medium',

        'high' => 'High',

        'filter' => 'Filter',

        'search' => 'Search',

        'search_placeholder' =>
            'Name, email, ticket number or subject',

        'name' => 'Name',

        'email' => 'Email',

        'subject' => 'Subject',

        'ticket_no' => 'Ticket Number',

        'created_at' => 'Created At',

        'replies' => 'Replies',

        'action' => 'Action',

        'view' => 'View',

        'tickets' => 'Tickets',

        'total' => 'Total Tickets',

        'open_count' => 'Open',

        'pending_count' => 'Pending',

        'closed_count' => 'Closed',

        'reset' => 'Reset',

        'previous' => 'Previous',

        'next' => 'Next',

        'page' => 'Page',

        'no_data' =>
            'No tickets match the selected filters',

        'today' => 'Today',

        'system' => 'Support System',

        'active' => 'Active',

        'language' => 'Language'
    ]
];

$tr = $t[$lang];

/* =========================================================
   الفلاتر
========================================================= */

$status =
    $_GET['status'] ?? 'all';

$priority =
    $_GET['priority'] ?? 'all';

$search =
    trim($_GET['search'] ?? '');

/*
 * ندعم pending وكذلك in_progress
 * حتى لا تضيع التذاكر القديمة.
 */

$allowedStatusFilters = [
    'all',
    'open',
    'pending',
    'closed'
];

if (
    !in_array(
        $status,
        $allowedStatusFilters,
        true
    )
) {
    $status = 'all';
}

$allowedPriorityFilters = [
    'all',
    'low',
    'medium',
    'high'
];

if (
    !in_array(
        $priority,
        $allowedPriorityFilters,
        true
    )
) {
    $priority = 'all';
}

/* =========================================================
   Pagination
========================================================= */

$page =
    max(
        1,
        (int)(
            $_GET['page'] ?? 1
        )
    );

$limit = 20;

$offset =
    ($page - 1) * $limit;

/* =========================================================
   بناء WHERE
========================================================= */

$where = [];

$params = [];

$types = '';

/* الحالة */

if ($status === 'open') {

    $where[] =
        "tickets.status = ?";

    $params[] =
        'open';

    $types .= 's';

} elseif ($status === 'pending') {

    /*
     * pending الحالية أو in_progress القديمة
     */

    $where[] =
        "(tickets.status = ? OR tickets.status = ?)";

    $params[] =
        'pending';

    $params[] =
        'in_progress';

    $types .= 'ss';

} elseif ($status === 'closed') {

    $where[] =
        "tickets.status = ?";

    $params[] =
        'closed';

    $types .= 's';
}

/* الأولوية */

if ($priority !== 'all') {

    $where[] =
        "tickets.priority = ?";

    $params[] =
        $priority;

    $types .= 's';
}

/* البحث */

if ($search !== '') {

    $where[] = "
        (
            tickets.name LIKE ?
            OR tickets.email LIKE ?
            OR tickets.subject LIKE ?
            OR tickets.ticket_number LIKE ?
            OR CAST(tickets.id AS CHAR) LIKE ?
        )
    ";

    $searchValue =
        '%' .
        $search .
        '%';

    for (
        $i = 0;
        $i < 5;
        $i++
    ) {

        $params[] =
            $searchValue;

        $types .= 's';
    }
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );
}

/* =========================================================
   إجمالي النتائج
========================================================= */

$countSql = "

    SELECT
        COUNT(*) AS total

    FROM tickets

    $whereSql

";

$countStmt =
    $con->prepare(
        $countSql
    );

if (!$countStmt) {

    die(
        "COUNT SQL ERROR: " .
        htmlspecialchars(
            $con->error
        )
    );
}

if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}

$countStmt->execute();

$countResult =
    $countStmt
        ->get_result()
        ->fetch_assoc();

$totalFiltered =
    (int)(
        $countResult['total']
        ?? 0
    );

$countStmt->close();

/* =========================================================
   عدد الصفحات
========================================================= */

$totalPages =
    max(
        1,
        (int)ceil(
            $totalFiltered / $limit
        )
    );

if ($page > $totalPages) {

    $page =
        $totalPages;

    $offset =
        ($page - 1) * $limit;
}

/* =========================================================
   إحصائيات عامة
========================================================= */

function ticketCount(
    mysqli $con,
    string $status = ''
): int {

    if ($status === 'pending') {

        $stmt =
            $con->prepare("
                SELECT COUNT(*) AS total
                FROM tickets
                WHERE status = ?
                   OR status = ?
            ");

        $oldStatus =
            'in_progress';

        $newStatus =
            'pending';

        $stmt->bind_param(
            'ss',
            $newStatus,
            $oldStatus
        );

    } elseif ($status !== '') {

        $stmt =
            $con->prepare("
                SELECT COUNT(*) AS total
                FROM tickets
                WHERE status = ?
            ");

        $stmt->bind_param(
            's',
            $status
        );

    } else {

        $stmt =
            $con->prepare("
                SELECT COUNT(*) AS total
                FROM tickets
            ");
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
        $result['total']
        ?? 0
    );
}

$totalTickets =
    ticketCount(
        $con
    );

$openCount =
    ticketCount(
        $con,
        'open'
    );

$pendingCount =
    ticketCount(
        $con,
        'pending'
    );

$closedCount =
    ticketCount(
        $con,
        'closed'
    );

/* =========================================================
   استعلام التذاكر
========================================================= */

$sql = "

    SELECT

        tickets.id,

        tickets.ticket_number,

        tickets.name,

        tickets.email,

        tickets.subject,

        tickets.status,

        tickets.priority,

        tickets.created_at,

        (
            SELECT COUNT(*)
            FROM ticket_replies
            WHERE ticket_replies.ticket_id =
                  tickets.id
        ) AS reply_count

    FROM tickets

    $whereSql

    ORDER BY

        CASE tickets.priority

            WHEN 'high' THEN 1

            WHEN 'medium' THEN 2

            WHEN 'low' THEN 3

            ELSE 4

        END,

        tickets.id DESC

    LIMIT ?

    OFFSET ?

";

/*
 * إضافة LIMIT و OFFSET
 */

$listParams =
    $params;

$listTypes =
    $types . 'ii';

$listParams[] =
    $limit;

$listParams[] =
    $offset;

$stmt =
    $con->prepare(
        $sql
    );

if (!$stmt) {

    die(
        "LIST SQL ERROR: " .
        htmlspecialchars(
            $con->error
        )
    );
}

$stmt->bind_param(
    $listTypes,
    ...$listParams
);

$stmt->execute();

$result =
    $stmt->get_result();

/* =========================================================
   helper status
========================================================= */

function statusLabel(
    string $status,
    array $tr
): string {

    if (
        $status === 'in_progress' ||
        $status === 'pending'
    ) {
        return $tr['pending'];
    }

    return
        $tr[$status]
        ?? $status;
}

function statusClass(
    string $status
): string {

    if (
        $status === 'in_progress' ||
        $status === 'pending'
    ) {
        return 'pending';
    }

    if ($status === 'closed') {
        return 'closed';
    }

    return 'open';
}

function priorityLabel(
    string $priority,
    array $tr
): string {

    return
        $tr[$priority]
        ?? $priority;
}

/* =========================================================
   روابط
========================================================= */

function buildPageUrl(
    int $pageNumber
): string {

    global
        $lang,
        $dark,
        $status,
        $priority,
        $search;

    return '?' .
        http_build_query([

            'lang' =>
                $lang,

            'dark' =>
                $dark,

            'status' =>
                $status,

            'priority' =>
                $priority,

            'search' =>
                $search,

            'page' =>
                $pageNumber
        ]);
}

function buildFilterUrl(
    array $extra = []
): string {

    global
        $lang,
        $dark,
        $status,
        $priority,
        $search;

    return '?' .
        http_build_query(
            array_merge(

                [

                    'lang' =>
                        $lang,

                    'dark' =>
                        $dark,

                    'status' =>
                        $status,

                    'priority' =>
                        $priority,

                    'search' =>
                        $search,

                    'page' =>
                        1

                ],

                $extra
            )
        );
}

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar'
        ? 'rtl'
        : 'ltr'
    ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars(
        $tr['tickets']
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
        1500px;

    margin:
        25px auto;

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

    flex-wrap:
        wrap;

    gap:
        15px;
}

.title-area{

    display:flex;

    align-items:
        center;

    gap:
        13px;
}

.title-icon{

    width:
        55px;

    height:
        55px;

    border-radius:
        14px;

    background:
        rgba(255,255,255,.16);

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        25px;
}

.header h1{

    margin:0;

    font-size:
        24px;

    font-weight:
        800;
}

.header p{

    margin:
        4px 0 0;

    opacity:
        .85;

    font-size:
        12px;
}

.header-actions{

    display:flex;

    gap:
        7px;

    flex-wrap:
        wrap;
}

.header-actions a{

    text-decoration:
        none;
}

/* =========================================================
   STATS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        14px;

    margin-bottom:
        18px;
}

.stat-card{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        15px;

    padding:
        18px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);
}

.stat-icon{

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

    color:#fff;

    margin-bottom:
        8px;
}

.stat-title{

    font-size:
        11px;

    color:
        var(--muted);

    font-weight:
        700;
}

.stat-number{

    font-size:
        27px;

    font-weight:
        800;
}

.blue{
    background:
        #0d6efd;
}

.green{
    background:
        #198754;
}

.orange{
    background:
        #fd7e14;
}

.red{
    background:
        #dc3545;
}

/* =========================================================
   FILTER
========================================================= */

.filter-card{

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

.form-control,
.form-select{

    min-height:
        43px;

    border-radius:
        9px;

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        var(--border);
}

/* =========================================================
   TABLE
========================================================= */

.table-card{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        16px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);
}

.table{

    margin-bottom:
        0;

    color:
        var(--text);
}

.table th{

    background:
        #0d6efd !important;

    color:#fff !important;

    white-space:
        nowrap;

    font-size:
        11px;

    padding:
        12px 9px;

    border-color:
        #0d6efd;
}

.table td{

    font-size:
        11px;

    padding:
        11px 8px;

    vertical-align:
        middle;

    border-color:
        var(--border);
}

.table tbody tr:hover{

    background:
        <?= $dark === '1'
            ? '#263449'
            : '#f8fbff'
        ?>;
}

.subject{

    max-width:
        250px;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

    font-weight:
        700;
}

.email{

    font-size:
        10px;

    color:
        var(--muted);
}

/* =========================================================
   BADGES
========================================================= */

.status-badge,
.priority-badge{

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        6px 10px;

    border-radius:
        20px;

    font-size:
        10px;

    font-weight:
        700;

    white-space:
        nowrap;
}

.status-open{

    background:
        rgba(13,110,253,.12);

    color:
        #0d6efd;
}

.status-pending{

    background:
        rgba(253,126,20,.13);

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
        rgba(253,126,20,.13);

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
   EMPTY
========================================================= */

.empty{

    text-align:
        center;

    padding:
        55px 20px;

    color:
        var(--muted);
}

.empty i{

    display:
        block;

    font-size:
        45px;

    margin-bottom:
        10px;
}

/* =========================================================
   PAGINATION
========================================================= */

.pagination{

    margin-top:
        18px;

    margin-bottom:
        0;
}

.page-link{

    background:
        var(--card);

    border-color:
        var(--border);

    color:
        var(--text);

    border-radius:
        8px !important;

    margin:
        0 2px;
}

.page-item.active
.page-link{

    background:
        #0d6efd;

    border-color:
        #0d6efd;

    color:#fff;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .stats{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:650px){

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

        flex-direction:
            column;

        align-items:
            stretch;
    }

    .header-actions{

        width:
            100%;
    }

    .header-actions .btn{

        flex:
            1;
    }

    .table-card{

        padding:
            10px;
    }
}

/* =========================================================
   PRINT
========================================================= */

@media print{

    .no-print{

        display:
            none !important;
    }

    body{

        background:#fff !important;

        color:#000 !important;
    }

    .page{

        max-width:
            100%;

        margin:0;

        padding:0;
    }

    .header,
    .stat-card,
    .table-card{

        box-shadow:none;
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

<i class="bi bi-headset"></i>

</div>

<div>

<h1>

<?= htmlspecialchars(
    $tr['tickets']
) ?>

</h1>

<p>

<?= htmlspecialchars(
    $tr['subtitle']
) ?>

</p>

</div>

</div>

<div class="header-actions no-print">

<a
    href="<?= htmlspecialchars(
        buildFilterUrl([
            'lang' => 'ar'
        ])
    ) ?>"
    class="btn btn-light btn-sm"
>

🇸🇦 AR

</a>

<a
    href="<?= htmlspecialchars(
        buildFilterUrl([
            'lang' => 'en'
        ])
    ) ?>"
    class="btn btn-light btn-sm"
>

🇬🇧 EN

</a>

<?php if ($dark === '1'): ?>

<a
    href="<?= htmlspecialchars(
        buildFilterUrl([
            'dark' => '0'
        ])
    ) ?>"
    class="btn btn-light btn-sm"
>

<i class="bi bi-sun"></i>

</a>

<?php else: ?>

<a
    href="<?= htmlspecialchars(
        buildFilterUrl([
            'dark' => '1'
        ])
    ) ?>"
    class="btn btn-dark btn-sm"
>

<i class="bi bi-moon-stars"></i>

</a>

<?php endif; ?>

<a
    href="support_dashboard.php?lang=<?= urlencode($lang) ?>&dark=<?= urlencode($dark) ?>"
    class="btn btn-outline-light btn-sm"
>

<i class="bi bi-speedometer2"></i>

<?= $lang === 'ar'
    ? 'لوحة الدعم'
    : 'Dashboard'
?>

</a>

</div>

</div>

</div>

<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats no-print">

<div class="stat-card">

<div class="stat-icon blue">

<i class="bi bi-ticket-detailed"></i>

</div>

<div class="stat-title">

<?= $tr['total'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $totalTickets
) ?>

</div>

</div>

<div class="stat-card">

<div class="stat-icon green">

<i class="bi bi-folder2-open"></i>

</div>

<div class="stat-title">

<?= $tr['open_count'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $openCount
) ?>

</div>

</div>

<div class="stat-card">

<div class="stat-icon orange">

<i class="bi bi-hourglass-split"></i>

</div>

<div class="stat-title">

<?= $tr['pending_count'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $pendingCount
) ?>

</div>

</div>

<div class="stat-card">

<div class="stat-icon red">

<i class="bi bi-check2-circle"></i>

</div>

<div class="stat-title">

<?= $tr['closed_count'] ?>

</div>

<div class="stat-number">

<?= number_format(
    $closedCount
) ?>

</div>

</div>

</div>

<!-- =====================================================
     FILTER
===================================================== -->

<div class="filter-card no-print">

<form method="GET">

<input
    type="hidden"
    name="lang"
    value="<?= htmlspecialchars($lang) ?>"
>

<input
    type="hidden"
    name="dark"
    value="<?= htmlspecialchars($dark) ?>"
>

<div class="row g-3">

<div class="col-lg-4 col-md-6">

<label class="form-label">

<?= $tr['search'] ?>

</label>

<input
    type="text"
    name="search"
    class="form-control"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="<?= htmlspecialchars(
        $tr['search_placeholder']
    ) ?>"
>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['status'] ?>

</label>

<select
    name="status"
    class="form-select"
>

<option value="all">

<?= $tr['all'] ?>

</option>

<option
    value="open"
    <?= $status === 'open'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['open'] ?>

</option>

<option
    value="pending"
    <?= $status === 'pending'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['pending'] ?>

</option>

<option
    value="closed"
    <?= $status === 'closed'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['closed'] ?>

</option>

</select>

</div>

<div class="col-lg-2 col-md-6">

<label class="form-label">

<?= $tr['priority'] ?>

</label>

<select
    name="priority"
    class="form-select"
>

<option value="all">

<?= $tr['all'] ?>

</option>

<option
    value="low"
    <?= $priority === 'low'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['low'] ?>

</option>

<option
    value="medium"
    <?= $priority === 'medium'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['medium'] ?>

</option>

<option
    value="high"
    <?= $priority === 'high'
        ? 'selected'
        : ''
    ?>
>

<?= $tr['high'] ?>

</option>

</select>

</div>

<div class="col-lg-2 col-md-6 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>

<i class="bi bi-search"></i>

<?= $tr['filter'] ?>

</button>

</div>

<div class="col-lg-2 col-md-6 d-flex align-items-end">

<a
    href="?lang=<?= urlencode($lang) ?>&dark=<?= urlencode($dark) ?>"
    class="btn btn-outline-secondary w-100"
>

<i class="bi bi-arrow-counterclockwise"></i>

<?= $tr['reset'] ?>

</a>

</div>

</div>

</form>

</div>

<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-card">

<div class="d-flex justify-content-between align-items-center mb-3">

<h5 class="mb-0">

<i class="bi bi-table text-primary"></i>

<?= $tr['tickets'] ?>

</h5>

<span class="badge bg-primary">

<?= number_format(
    $totalFiltered
) ?>

</span>

</div>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead>

<tr>

<th>#</th>

<th>
<?= $tr['ticket_no'] ?>
</th>

<th>
<?= $tr['name'] ?>
</th>

<th>
<?= $tr['email'] ?>
</th>

<th>
<?= $tr['subject'] ?>
</th>

<th>
<?= $tr['status'] ?>
</th>

<th>
<?= $tr['priority'] ?>
</th>

<th>
<?= $tr['replies'] ?>
</th>

<th>
<?= $tr['created_at'] ?>
</th>

<th class="no-print">
<?= $tr['action'] ?>
</th>

</tr>

</thead>

<tbody>

<?php if (
    $result->num_rows === 0
): ?>

<tr>

<td colspan="10">

<div class="empty">

<i class="bi bi-ticket-perforated"></i>

<?= htmlspecialchars(
    $tr['no_data']
) ?>

</div>

</td>

</tr>

<?php else: ?>

<?php

$rowNumber =
    $offset + 1;

?>

<?php while (
    $row =
    $result->fetch_assoc()
): ?>

<?php

$currentStatus =
    $row['status']
    ?? 'open';

$currentPriority =
    $row['priority']
    ?? 'medium';

?>

<tr>

<td>

<?= $rowNumber++ ?>

</td>

<td>

<strong>

#
<?= htmlspecialchars(
    $row['ticket_number']
    ?? $row['id']
) ?>

</strong>

</td>

<td>

<strong>

<?= htmlspecialchars(
    $row['name']
    ?? '-'
) ?>

</strong>

</td>

<td>

<span class="email">

<?= htmlspecialchars(
    $row['email']
    ?? '-'
) ?>

</span>

</td>

<td>

<div class="subject">

<?= htmlspecialchars(
    $row['subject']
    ?? '-'
) ?>

</div>

</td>

<td>

<span
    class="status-badge status-<?= statusClass(
        $currentStatus
    ) ?>"
>

<i class="bi bi-circle-fill"></i>

<?= htmlspecialchars(
    statusLabel(
        $currentStatus,
        $tr
    )
) ?>

</span>

</td>

<td>

<span
    class="priority-badge priority-<?= htmlspecialchars(
        $currentPriority
    ) ?>"
>

<?= htmlspecialchars(
    priorityLabel(
        $currentPriority,
        $tr
    )
) ?>

</span>

</td>

<td>

<span class="badge bg-secondary">

<?= number_format(
    (int)(
        $row['reply_count']
        ?? 0
    )
) ?>

</span>

</td>

<td>

<?= htmlspecialchars(
    $row['created_at']
    ?? '-'
) ?>

</td>

<td class="no-print">

<a
    href="ticket_view.php?id=<?= (int)$row['id'] ?>&lang=<?= urlencode($lang) ?>&dark=<?= urlencode($dark) ?>"
    class="btn btn-info btn-sm text-white"
>

<i class="bi bi-eye"></i>

<?= $tr['view'] ?>

</a>

</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</tbody>

</table>

</div>

<!-- =====================================================
     PAGINATION
===================================================== -->

<?php if (
    $totalPages > 1
): ?>

<nav class="no-print">

<ul class="pagination justify-content-center">

<li
    class="page-item
    <?= $page <= 1
        ? 'disabled'
        : ''
    ?>"
>

<a
    class="page-link"
    href="<?= $page > 1
        ? htmlspecialchars(
            buildPageUrl(
                $page - 1
            )
        )
        : '#'
    ?>"
>

<?= $tr['previous'] ?>

</a>

</li>

<?php

$startPage =
    max(
        1,
        $page - 2
    );

$endPage =
    min(
        $totalPages,
        $page + 2
    );

for (
    $p = $startPage;
    $p <= $endPage;
    $p++
):
?>

<li
    class="page-item
    <?= $p === $page
        ? 'active'
        : ''
    ?>"
>

<a
    class="page-link"
    href="<?= htmlspecialchars(
        buildPageUrl($p)
    ) ?>"
>

<?= $p ?>

</a>

</li>

<?php endfor; ?>

<li
    class="page-item
    <?= $page >= $totalPages
        ? 'disabled'
        : ''
    ?>"
>

<a
    class="page-link"
    href="<?= $page < $totalPages
        ? htmlspecialchars(
            buildPageUrl(
                $page + 1
            )
        )
        : '#'
    ?>"
>

<?= $tr['next'] ?>

</a>

</li>

</ul>

<div
    class="text-center text-muted"
    style="font-size:11px;"
>

<?= $tr['page'] ?>

<?= $page ?>

/

<?= $totalPages ?>

</div>

<?php endif; ?>

</div>

</div>

</body>

</html>


