<?php

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   الملفات الأساسية
========================================================= */

require_once __DIR__ . '/../include/core.php';
require_once __DIR__ . '/../include/connected.php';
require_once __DIR__ . '/../include/send_email.php';


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


/* =========================================================
   الترجمة
========================================================= */

$translations = [
    'ar' => [],
    'en' => []
];

$arLangFile = __DIR__ . '/lang/ar.php';
$enLangFile = __DIR__ . '/lang/en.php';

if (file_exists($arLangFile)) {
    $translations['ar'] = include $arLangFile;
}

if (file_exists($enLangFile)) {
    $translations['en'] = include $enLangFile;
}


/* =========================================================
   دالة إشعار العميل
========================================================= */

if (!function_exists('createOrderNotification')) {

    function createOrderNotification(
        $con,
        $title,
        $message,
        $type,
        $user_id,
        $ref_id
    ) {

        if (!$con || (int)$user_id <= 0) {
            return false;
        }

        try {

            $stmt = $con->prepare("
                INSERT INTO notifications
                (
                    title,
                    message,
                    type,
                    ref_id,
                    is_read,
                    user_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    0,
                    ?
                )
            ");

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param(
                "sssii",
                $title,
                $message,
                $type,
                $ref_id,
                $user_id
            );

            $result = $stmt->execute();

            $stmt->close();

            return $result;

        } catch (Throwable $e) {

            error_log(
                "Notification error: " . $e->getMessage()
            );

            return false;
        }
    }
}


/* =========================================================
   دعم addNotification القديمة
========================================================= */

if (!function_exists('addNotification')) {

    function addNotification(
        $con,
        $title,
        $message,
        $type,
        $user_id,
        $ref_id
    ) {

        return createOrderNotification(
            $con,
            $title,
            $message,
            $type,
            $user_id,
            $ref_id
        );
    }
}


/* =========================================================
   الموافقة / الرفض
   يجب أن تكون قبل البحث والفلاتر
========================================================= */

if (isset($_POST['approval_action'])) {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $action   = $_POST['approval_action'] ?? '';


    if (
        $order_id <= 0 ||
        !in_array($action, ['approve', 'reject'], true)
    ) {

        header(
            "Location: ordersview.php?error=invalid_action"
        );

        exit;
    }


    /* =====================================================
       جلب الطلب
    ===================================================== */

    $stmt = $con->prepare("
        SELECT
            id,
            order_number,
            user_id,
            full_name,
            email,
            phone,
            from_city,
            to_city,
            price,
            booking_type,
            scheduled_date,
            scheduled_time,
            approval_status,
            status
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");


    if (!$stmt) {

        error_log(
            "Order query error: " . $con->error
        );

        header(
            "Location: ordersview.php?error=database"
        );

        exit;
    }


    $stmt->bind_param(
        "i",
        $order_id
    );

    $stmt->execute();

    $result_order =
        $stmt->get_result();

    $order_data =
        $result_order->fetch_assoc();

    $stmt->close();


    if (!$order_data) {

        header(
            "Location: ordersview.php?error=order_not_found"
        );

        exit;
    }


    /* =====================================================
       الحصول على بيانات العميل
       
       الأولوية:
       1. orders.email
       2. users.email
    ===================================================== */

    $customerEmail =
        trim($order_data['email'] ?? '');

    $customerName =
        trim($order_data['full_name'] ?? '');


    /* =====================================================
       إذا كان Email الطلب فارغ
       نبحث عنه في users
    ===================================================== */

    if (
        empty($customerEmail) &&
        !empty($order_data['user_id'])
    ) {

        $stmt_user = $con->prepare("
            SELECT
                username,
                email
            FROM users
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt_user) {

            $user_id =
                (int)$order_data['user_id'];

            $stmt_user->bind_param(
                "i",
                $user_id
            );

            $stmt_user->execute();

            $user_result =
                $stmt_user->get_result();

            $user_data =
                $user_result->fetch_assoc();

            $stmt_user->close();


            if ($user_data) {

                $customerEmail =
                    trim($user_data['email'] ?? '');

                if (empty($customerName)) {

                    $customerName =
                        trim(
                            $user_data['username'] ?? ''
                        );
                }
            }
        }
    }


    /* =====================================================
       تسجيل البريد في Log
    ===================================================== */

    error_log(
        "ORDER #{$order_id} CUSTOMER EMAIL = " .
        (
            $customerEmail !== ''
            ? $customerEmail
            : 'EMPTY'
        )
    );


    /* =====================================================
   الموافقة
===================================================== */

if ($action === 'approve') {

    /* تحديث حالة الموافقة */
    $stmt_update = $con->prepare("
        UPDATE orders
        SET approval_status = 'approved'
        WHERE id = ?
    ");

    if (!$stmt_update) {

        error_log(
            "Approval update error: " . $con->error
        );

        header(
            "Location: ordersview.php?error=database"
        );

        exit;
    }

    $stmt_update->bind_param(
        "i",
        $order_id
    );

    if (!$stmt_update->execute()) {

        error_log(
            "Approval execute error: " .
            $stmt_update->error
        );

        $stmt_update->close();

        header(
            "Location: ordersview.php?error=approval_failed"
        );

        exit;
    }

    $stmt_update->close();


    /* =================================================
       بيانات العميل
    ================================================= */

    $customerName =
        trim($order_data['full_name'] ?? '');

    $customerEmail =
        trim($order_data['email'] ?? '');


    /* =================================================
       إذا كان بريد orders فارغًا
       نبحث في users
    ================================================= */

    if (
        empty($customerEmail) &&
        !empty($order_data['user_id'])
    ) {

        $stmt_user = $con->prepare("
            SELECT
                email,
                username
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt_user) {

            $user_id =
                (int)$order_data['user_id'];

            $stmt_user->bind_param(
                "i",
                $user_id
            );

            $stmt_user->execute();

            $user_result =
                $stmt_user->get_result();

            $user_data =
                $user_result->fetch_assoc();

            $stmt_user->close();


            if ($user_data) {

                $customerEmail =
                    trim(
                        $user_data['email'] ?? ''
                    );


                if (
                    empty($customerName) &&
                    !empty($user_data['username'])
                ) {

                    $customerName =
                        $user_data['username'];
                }
            }
        }
    }


    /* =================================================
       تسجيل البريد في Log
    ================================================= */

    error_log(
        "APPROVAL ORDER #" .
        $order_id .
        " | USER ID: " .
        ($order_data['user_id'] ?? 0) .
        " | EMAIL: " .
        ($customerEmail ?: 'EMPTY')
    );


    /* =================================================
       إرسال بريد الموافقة
    ================================================= */

    $emailSent = false;

    if (
        !empty($customerEmail) &&
        filter_var(
            $customerEmail,
            FILTER_VALIDATE_EMAIL
        ) &&
        function_exists(
            'sendOrderApprovedEmail'
        )
    ) {

        try {

            $emailSent =
                sendOrderApprovedEmail(
                    $customerEmail,
                    $order_id,
                    $customerName,
                    ''
                );

            if ($emailSent) {

                error_log(
                    "APPROVAL EMAIL SENT - ORDER #" .
                    $order_id .
                    " - EMAIL: " .
                    $customerEmail
                );

            } else {

                error_log(
                    "APPROVAL EMAIL FAILED - ORDER #" .
                    $order_id .
                    " - EMAIL: " .
                    $customerEmail
                );
            }

        } catch (Throwable $e) {

            error_log(
                "APPROVAL EMAIL ERROR - ORDER #" .
                $order_id .
                " - " .
                $e->getMessage()
            );
        }

    } else {

        error_log(
            "APPROVAL EMAIL NOT SENT - ORDER #" .
            $order_id .
            " - EMAIL EMPTY OR INVALID"
        );
    }


    /* =================================================
       إشعار العميل
    ================================================= */

    if (!empty($order_data['user_id'])) {

        addNotification(
            $con,
            "تمت الموافقة على الطلب",
            "تمت الموافقة على طلبك رقم #" .
            (
                $order_data['order_number']
                ?? $order_id
            ) .
            " وبدأت الإدارة في معالجة الطلب.",
            "order",
            (int)$order_data['user_id'],
            $order_id
        );
    }
}

    /* =====================================================
       الرفض
    ===================================================== */

    if ($action === 'reject') {

        $stmt_update = $con->prepare("
            UPDATE orders
            SET
                approval_status = 'rejected',
                status = 'cancelled'
            WHERE id = ?
        ");


        if ($stmt_update) {

            $stmt_update->bind_param(
                "i",
                $order_id
            );

            $stmt_update->execute();

            $stmt_update->close();
        }


        /* =================================================
           إشعار العميل
        ================================================= */

        if (!empty($order_data['user_id'])) {

            addNotification(
                $con,

                "تم رفض الطلب",

                "نعتذر، تم رفض طلبك رقم #" .
                (
                    $order_data['order_number']
                    ?? $order_id
                ) .
                " من قبل الإدارة.",

                "order",

                (int)$order_data['user_id'],

                $order_id
            );
        }


        /* =================================================
           بريد الرفض
        ================================================= */

        if (
            !empty($customerEmail) &&
            function_exists('sendOrderRejectedEmail')
        ) {

            try {

                sendOrderRejectedEmail(
                    $customerEmail,
                    $order_id,
                    $customerName,
                    ''
                );

            } catch (Throwable $e) {

                error_log(
                    "REJECTION EMAIL ERROR - ORDER #" .
                    $order_id .
                    " - " .
                    $e->getMessage()
                );
            }
        }


        header(
            "Location: ordersview.php?success=approval"
        );

        exit;
    }
}


/* =========================================================
   تعيين المزود / السائق + تحديث حالة الطلب
========================================================= */

if (
    isset($_POST['order_id']) &&
    !isset($_POST['approval_action'])
) {

    $order_id =
        (int)($_POST['order_id'] ?? 0);

    $driver_id =
        (int)($_POST['driver_id'] ?? 0);

    $new_status =
        $_POST['status'] ?? 'pending';


    $allowed_status = [
        'pending',
        'assigned',
        'done',
        'cancelled'
    ];


    if (
        !in_array(
            $new_status,
            $allowed_status,
            true
        )
    ) {

        $new_status = 'pending';
    }


    if ($order_id > 0) {

        /* =================================================
           جلب الطلب
        ================================================= */

        $stmt = $con->prepare("
            SELECT
                id,
                order_number,
                user_id,
                full_name,
                email,
                phone,
                driver_id,
                approval_status,
                status
            FROM orders
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $order_id
            );

            $stmt->execute();

            $order_result =
                $stmt->get_result();

            $order_data =
                $order_result->fetch_assoc();

            $stmt->close();


            if ($order_data) {

                /* =========================================
                   Email العميل
                ========================================= */

                $customerEmail =
                    trim($order_data['email'] ?? '');

                $customerName =
                    trim($order_data['full_name'] ?? '');


                if (
                    empty($customerEmail) &&
                    !empty($order_data['user_id'])
                ) {

                    $stmt_user =
                        $con->prepare("
                            SELECT
                                username,
                                email
                            FROM users
                            WHERE id = ?
                            LIMIT 1
                        ");


                    if ($stmt_user) {

                        $uid =
                            (int)$order_data['user_id'];

                        $stmt_user->bind_param(
                            "i",
                            $uid
                        );

                        $stmt_user->execute();

                        $user_result =
                            $stmt_user->get_result();

                        $user_data =
                            $user_result->fetch_assoc();

                        $stmt_user->close();


                        if ($user_data) {

                            $customerEmail =
                                trim(
                                    $user_data['email']
                                    ?? ''
                                );

                            if (
                                empty($customerName)
                            ) {

                                $customerName =
                                    trim(
                                        $user_data['username']
                                        ?? ''
                                    );
                            }
                        }
                    }
                }


                /* =========================================
                   اسم السائق
                ========================================= */

                $driverName = '';


                if ($driver_id > 0) {

                    $stmt_driver =
                        $con->prepare("
                            SELECT
                                name
                            FROM drivers
                            WHERE id = ?
                            LIMIT 1
                        ");


                    if ($stmt_driver) {

                        $stmt_driver->bind_param(
                            "i",
                            $driver_id
                        );

                        $stmt_driver->execute();

                        $driver_result =
                            $stmt_driver->get_result();

                        $driver_data =
                            $driver_result->fetch_assoc();

                        $stmt_driver->close();


                        $driverName =
                            $driver_data['name'] ?? '';
                    }
                }


                /* =========================================
                   تحديث الطلب
                ========================================= */

                $stmt_update =
                    $con->prepare("
                        UPDATE orders
                        SET
                            driver_id = ?,
                            status = ?
                        WHERE id = ?
                    ");


                if ($stmt_update) {

                    $stmt_update->bind_param(
                        "isi",
                        $driver_id,
                        $new_status,
                        $order_id
                    );

                    $stmt_update->execute();

                    $stmt_update->close();
                }

                /* =========================================================
   إرسال إيميل الموافقة
========================================================= */

$emailSent = false;

if (!empty($order_data['email'])) {

    $emailSent = sendOrderApprovedEmail(
        $order_data['email'],
        $order_data['id'],
        $order_data['full_name'] ?? '',
        ''
    );

    if (!$emailSent) {

        error_log(
            'Failed to send approval email for order #' .
            $order_id
        );
    }
}

                /* =========================================
                   Email تعيين السائق
                ========================================= */

                if (
                    $driver_id > 0 &&
                    !empty($driverName) &&
                    !empty($customerEmail) &&
                    function_exists(
                        'sendOrderAssignedEmail'
                    )
                ) {

                    try {

                        sendOrderAssignedEmail(
                            $customerEmail,
                            $order_id,
                            $customerName,
                            $driverName
                        );

                    } catch (Throwable $e) {

                        error_log(
                            "ASSIGN EMAIL ERROR - ORDER #" .
                            $order_id .
                            " - " .
                            $e->getMessage()
                        );
                    }
                }


                /* =========================================
                   إشعار تعيين السائق
                ========================================= */

                if (
                    $driver_id > 0 &&
                    !empty($driverName) &&
                    !empty($order_data['user_id'])
                ) {

                    addNotification(
                        $con,

                        "تم تعيين مزود الخدمة",

                        "تم تعيين " .
                        $driverName .
                        " لطلبك رقم #" .
                        (
                            $order_data['order_number']
                            ?? $order_id
                        ) .
                        ".",

                        "order",

                        (int)$order_data['user_id'],

                        $order_id
                    );
                }


                /* =========================================
                   Email اكتمال الطلب
                ========================================= */

                if (
                    $new_status === 'done' &&
                    !empty($customerEmail) &&
                    function_exists(
                        'sendOrderCompletedEmail'
                    )
                ) {

                    try {

                        sendOrderCompletedEmail(
                            $customerEmail,
                            $order_id,
                            $customerName,
                            $driverName
                        );

                    } catch (Throwable $e) {

                        error_log(
                            "COMPLETE EMAIL ERROR - ORDER #" .
                            $order_id .
                            " - " .
                            $e->getMessage()
                        );
                    }
                }


                /* =========================================
                   Email إلغاء الطلب
                ========================================= */

                if (
                    $new_status === 'cancelled' &&
                    !empty($customerEmail) &&
                    function_exists(
                        'sendOrderCancelledEmail'
                    )
                ) {

                    try {

                        sendOrderCancelledEmail(
                            $customerEmail,
                            $order_id,
                            $customerName,
                            $driverName
                        );

                    } catch (Throwable $e) {

                        error_log(
                            "CANCEL EMAIL ERROR - ORDER #" .
                            $order_id .
                            " - " .
                            $e->getMessage()
                        );
                    }
                }
            }
        }
    }


    header(
        "Location: ordersview.php?success=1"
    );

    exit;
}


/* =========================================================
   حذف الطلب
========================================================= */

if (isset($_GET['delete'])) {

    $id =
        (int)$_GET['delete'];


    if ($id > 0) {

        $stmt =
            $con->prepare("
                DELETE FROM orders
                WHERE id = ?
            ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $stmt->close();
        }
    }


    header(
        "Location: ordersview.php?success=deleted"
    );

    exit;
}


/* =========================================================
   البحث والفلاتر
========================================================= */

$search =
    trim($_GET['search'] ?? '');

$filter_status =
    trim($_GET['status'] ?? '');

$filter =
    trim($_GET['filter'] ?? 'all');

$approval_filter =
    trim($_GET['approval_status'] ?? '');

$order_type =
    trim($_GET['order_type'] ?? '');


$where = "WHERE 1";


/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $search_safe =
        mysqli_real_escape_string(
            $con,
            $search
        );


    $where .= "
        AND (
            orders.full_name LIKE '%$search_safe%'
            OR orders.phone LIKE '%$search_safe%'
            OR orders.from_city LIKE '%$search_safe%'
            OR orders.to_city LIKE '%$search_safe%'
            OR orders.order_number LIKE '%$search_safe%'
        )
    ";
}


/* =========================================================
   حالة الطلب
========================================================= */

$allowed_status = [
    'pending',
    'assigned',
    'done',
    'cancelled'
];


if (
    $filter_status !== '' &&
    in_array(
        $filter_status,
        $allowed_status,
        true
    )
) {

    $status_safe =
        mysqli_real_escape_string(
            $con,
            $filter_status
        );


    $where .= "
        AND orders.status = '$status_safe'
    ";
}


/* =========================================================
   نوع الحجز
========================================================= */

if ($filter === 'scheduled') {

    $where .= "
        AND orders.booking_type = 'scheduled'
    ";

} elseif ($filter === 'instant') {

    $where .= "
        AND orders.booking_type = 'instant'
    ";
}


/* =========================================================
   نوع الطلب
========================================================= */

if ($order_type !== '') {

    $order_type_safe =
        mysqli_real_escape_string(
            $con,
            $order_type
        );


    $where .= "
        AND orders.order_type = '$order_type_safe'
    ";
}


/* =========================================================
   حالة الموافقة
========================================================= */

if (
    in_array(
        $approval_filter,
        [
            'pending',
            'approved',
            'rejected'
        ],
        true
    )
) {

    $approval_safe =
        mysqli_real_escape_string(
            $con,
            $approval_filter
        );


    $where .= "
        AND orders.approval_status = '$approval_safe'
    ";
}


/* =========================================================
   الإحصائيات
========================================================= */

$stats_query =
    mysqli_query(
        $con,
        "
        SELECT

            COUNT(*) AS total,

            COALESCE(
                SUM(status = 'pending'),
                0
            ) AS pending,

            COALESCE(
                SUM(status = 'assigned'),
                0
            ) AS assigned,

            COALESCE(
                SUM(status = 'done'),
                0
            ) AS done_orders,

            COALESCE(
                SUM(status = 'cancelled'),
                0
            ) AS cancelled,

            COALESCE(
                SUM(booking_type = 'instant'),
                0
            ) AS instant_orders,

            COALESCE(
                SUM(booking_type = 'scheduled'),
                0
            ) AS scheduled_orders,

            COALESCE(
                SUM(approval_status = 'pending'),
                0
            ) AS approval_pending,

            COALESCE(
                SUM(approval_status = 'approved'),
                0
            ) AS approval_approved,

            COALESCE(
                SUM(approval_status = 'rejected'),
                0
            ) AS approval_rejected,

            COALESCE(
                SUM(price),
                0
            ) AS total_sales

        FROM orders
        "
    );


$stats =
    $stats_query
    ? mysqli_fetch_assoc($stats_query)
    : [];


$totalOrders =
    (int)($stats['total'] ?? 0);

$pendingOrders =
    (int)($stats['pending'] ?? 0);

$assignedOrders =
    (int)($stats['assigned'] ?? 0);

$doneOrders =
    (int)($stats['done_orders'] ?? 0);

$cancelledOrders =
    (int)($stats['cancelled'] ?? 0);

$instantOrders =
    (int)($stats['instant_orders'] ?? 0);

$scheduledOrders =
    (int)($stats['scheduled_orders'] ?? 0);

$approvalPending =
    (int)($stats['approval_pending'] ?? 0);

$approvalApproved =
    (int)($stats['approval_approved'] ?? 0);

$approvalRejected =
    (int)($stats['approval_rejected'] ?? 0);

$totalSales =
    (float)($stats['total_sales'] ?? 0);


/* =========================================================
   Pagination
========================================================= */

$limit = 10;

$page =
    isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;


if ($page < 1) {
    $page = 1;
}


$start =
    ($page - 1) * $limit;


/* =========================================================
   عدد النتائج
========================================================= */

$countQuery =
    mysqli_query(
        $con,
        "
        SELECT COUNT(*) AS total
        FROM orders
        $where
        "
    );


$countRow =
    $countQuery
    ? mysqli_fetch_assoc($countQuery)
    : ['total' => 0];


$totalRows =
    (int)($countRow['total'] ?? 0);


$totalPages =
    max(
        1,
        (int)ceil(
            $totalRows / $limit
        )
    );


if ($page > $totalPages) {

    $page = $totalPages;

    $start =
        ($page - 1) * $limit;
}


/* =========================================================
   جلب الطلبات
========================================================= */

$query = "
    SELECT
        orders.*,
        drivers.name AS driver_name
    FROM orders

    LEFT JOIN drivers
        ON drivers.id = orders.driver_id

    $where

    ORDER BY orders.id DESC

    LIMIT $start, $limit
";


$result =
    mysqli_query(
        $con,
        $query
    );


/* =========================================================
   جلب السائقين
========================================================= */

$drivers_list = [];


$drivers_result =
    mysqli_query(
        $con,
        "
        SELECT
            id,
            name
        FROM drivers
        ORDER BY name ASC
        "
    );


if ($drivers_result) {

    while (
        $d =
        mysqli_fetch_assoc(
            $drivers_result
        )
    ) {

        $drivers_list[] = $d;
    }
}

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
    content="width=device-width, initial-scale=1"
>

<title>إدارة الطلبات</title>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
>


<link
    rel="stylesheet"
    href="assets/dark-mode.css"
>


<style>

body {
    background:#f7f7f7;
}

.page-header {
    background:#fff;
    border-radius:18px;
    padding:20px;
    margin-bottom:20px;
}

.stats-card {
    border:none;
    border-radius:18px;
    overflow:hidden;
    transition:.3s;
    color:#fff;
}

.stats-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 30px rgba(0,0,0,.15);
}

.stats-icon {
    font-size:38px;
}

.stats-number {
    font-size:30px;
    font-weight:bold;
}

.stats-title {
    font-size:14px;
    opacity:.9;
}

.bg-blue {
    background:linear-gradient(135deg,#0d6efd,#2563eb);
}

.bg-orange {
    background:linear-gradient(135deg,#f59e0b,#d97706);
}

.bg-green {
    background:linear-gradient(135deg,#10b981,#059669);
}

.bg-red {
    background:linear-gradient(135deg,#ef4444,#dc2626);
}

.bg-cyan {
    background:linear-gradient(135deg,#06b6d4,#0891b2);
}

.bg-pink {
    background:linear-gradient(135deg,#ec4899,#db2777);
}

.card {
    border-radius:16px;
    border:none;
}

.form-control,
.form-select {
    border-radius:10px;
    min-height:45px;
}

.filters {
    margin-bottom:20px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.filters a {
    background:#0d6efd;
    color:#fff;
    padding:9px 15px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
}

.orders-table {
    white-space:nowrap;
}

.orders-table thead th {
    background:#f8f9fa;
    font-weight:bold;
    vertical-align:middle;
}

.orders-table tbody tr:hover {
    background:#eef6ff;
}

.action-buttons {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:4px;
    flex-wrap:wrap;
}

.action-buttons .btn {
    width:36px;
    height:36px;
    padding:0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:8px;
}

.driver-form {
    display:flex;
    align-items:center;
    gap:5px;
    min-width:250px;
}

body.dark-mode {
    background:#121212;
}

body.dark-mode .card,
body.dark-mode .page-header {
    background:#1f1f1f !important;
    color:#fff;
}

body.dark-mode .orders-table {
    color:#fff;
}

body.dark-mode .orders-table thead th {
    background:#2a2a2a !important;
    color:#fff;
}

body.dark-mode .orders-table tbody td {
    background:#1e1e1e !important;
    color:#fff;
    border-color:#333;
}

body.dark-mode .text-muted {
    color:#aaa !important;
}

@media(max-width:768px) {

    .page-header {
        text-align:center;
    }

    .driver-form {
        min-width:220px;
    }
}

</style>

</head>


<body>


<div class="container-fluid px-3 px-lg-4 py-4">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="page-header shadow-sm">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

<div>

<h2 class="fw-bold mb-1">
🚚 إدارة الطلبات
</h2>

<small class="text-muted">
إدارة جميع طلبات النقل داخل النظام
</small>

</div>


<div class="d-flex gap-2 flex-wrap">

<a
    href="create_order.php"
    class="btn btn-primary"
>
<i class="fa-solid fa-plus"></i>
إضافة طلب
</a>


<a
    href="export_orders_excel.php?<?= http_build_query([
        'search'         => $search,
        'status'         => $filter_status,
        'filter'         => $filter,
        'approval_status'=> $approval_filter,
        'order_type'     => $order_type,
        'lang'           => $lang
    ]) ?>"
    class="btn btn-success"
    title="تصدير الطلبات حسب الفلاتر الحالية"
>
    <i class="fa-solid fa-file-excel"></i>
    Excel
</a>


<a
    href="export_orders_pdf.php?<?= http_build_query([
        'search'          => $search,
        'status'          => $filter_status,
        'filter'          => $filter,
        'approval_status' => $approval_filter,
        'order_type'      => $order_type,
        'lang'            => $lang
    ]) ?>"
    class="btn btn-danger"
    title="تصدير النتائج الحالية إلى PDF"
>
    <i class="fa-solid fa-file-pdf"></i>
    PDF
</a>


<a
    href="?lang=ar"
    class="btn btn-outline-secondary"
>
🇸🇦
</a>


<a
    href="?lang=en"
    class="btn btn-outline-secondary"
>
🇬🇧
</a>


<button
    type="button"
    onclick="toggleDarkMode()"
    class="btn btn-outline-dark"
>
🌙
</button>

</div>

</div>

</div>


<!-- =====================================================
     ALERT
===================================================== -->

<?php if (isset($_GET['success'])): ?>

<div class="alert alert-success shadow-sm">

<?php

if (
    $_GET['success'] === 'approval'
) {

    echo "✅ تم تحديث حالة الموافقة بنجاح";

} elseif (
    $_GET['success'] === 'deleted'
) {

    echo "🗑️ تم حذف الطلب بنجاح";

} else {

    echo "✅ تم تحديث الطلب بنجاح";
}

?>

</div>

<?php endif; ?>


<?php if (isset($_GET['error'])): ?>

<div class="alert alert-danger shadow-sm">

حدث خطأ أثناء تنفيذ العملية.

</div>

<?php endif; ?>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-3 mb-4">


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-blue h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
إجمالي الطلبات
</div>

<div class="stats-number">
<?= $totalOrders ?>
</div>

</div>

<div class="stats-icon">
📦
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-orange h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
تحتاج موافقة
</div>

<div class="stats-number">
<?= $approvalPending ?>
</div>

</div>

<div class="stats-icon">
🔔
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-green h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
تمت الموافقة
</div>

<div class="stats-number">
<?= $approvalApproved ?>
</div>

</div>

<div class="stats-icon">
✅
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-red h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
مرفوضة
</div>

<div class="stats-number">
<?= $approvalRejected ?>
</div>

</div>

<div class="stats-icon">
❌
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-orange h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
قيد الانتظار
</div>

<div class="stats-number">
<?= $pendingOrders ?>
</div>

</div>

<div class="stats-icon">
⏳
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-cyan h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
تم إسنادها
</div>

<div class="stats-number">
<?= $assignedOrders ?>
</div>

</div>

<div class="stats-icon">
🚚
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-green h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
طلبات مكتملة
</div>

<div class="stats-number">
<?= $doneOrders ?>
</div>

</div>

<div class="stats-icon">
🏆
</div>

</div>

</div>

</div>


<div class="col-xl-3 col-lg-4 col-md-6">

<div class="card stats-card bg-pink h-100">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<div class="stats-title">
إجمالي الإيرادات
</div>

<div class="stats-number">
<?= number_format($totalSales, 2) ?>
</div>

</div>

<div class="stats-icon">
💰
</div>

</div>

</div>

</div>


</div>


<!-- =====================================================
     SEARCH + FILTERS
===================================================== -->

<div class="card shadow-sm mb-4">

<div class="card-body">

<form method="GET">

<div class="row g-3">


<div class="col-lg-4">

<label class="form-label fw-bold">
البحث
</label>

<input
    type="text"
    name="search"
    class="form-control"
    placeholder="🔍 الاسم أو الجوال أو المدينة أو رقم الطلب"
    value="<?= htmlspecialchars($search) ?>"
>

</div>


<div class="col-lg-2">

<label class="form-label fw-bold">
الحالة
</label>

<select
    name="status"
    class="form-select"
>

<option value="">
كل الحالات
</option>

<option
    value="pending"
    <?= $filter_status === 'pending' ? 'selected' : '' ?>
>
⏳ انتظار
</option>

<option
    value="assigned"
    <?= $filter_status === 'assigned' ? 'selected' : '' ?>
>
🚚 معين
</option>

<option
    value="done"
    <?= $filter_status === 'done' ? 'selected' : '' ?>
>
✅ مكتمل
</option>

<option
    value="cancelled"
    <?= $filter_status === 'cancelled' ? 'selected' : '' ?>
>
❌ ملغي
</option>

</select>

</div>


<div class="col-lg-2">

<label class="form-label fw-bold">
نوع الحجز
</label>

<select
    name="filter"
    class="form-select"
>

<option value="all">
كل الطلبات
</option>

<option
    value="instant"
    <?= $filter === 'instant' ? 'selected' : '' ?>
>
🚀 فوري
</option>

<option
    value="scheduled"
    <?= $filter === 'scheduled' ? 'selected' : '' ?>
>
📅 مجدول
</option>

</select>

</div>


<div class="col-lg-2">

<label class="form-label fw-bold">
حالة الموافقة
</label>

<select
    name="approval_status"
    class="form-select"
>

<option value="">
كل الحالات
</option>

<option
    value="pending"
    <?= $approval_filter === 'pending' ? 'selected' : '' ?>
>
⏳ تحتاج موافقة
</option>

<option
    value="approved"
    <?= $approval_filter === 'approved' ? 'selected' : '' ?>
>
✅ موافق عليها
</option>

<option
    value="rejected"
    <?= $approval_filter === 'rejected' ? 'selected' : '' ?>
>
❌ مرفوضة
</option>

</select>

</div>


<div class="col-lg-2 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>

<i class="fa-solid fa-search"></i>

بحث

</button>

</div>


</div>

</form>

</div>

</div>


<!-- =====================================================
     QUICK FILTERS
===================================================== -->

<div class="filters">

<a href="ordersview.php">
📋 كل الطلبات
</a>

<a href="?filter=instant">
🚀 الطلبات الفورية
</a>

<a href="?filter=scheduled">
📅 الطلبات المجدولة
</a>

<a href="?approval_status=pending">
🔔 تحتاج موافقة
</a>

<a href="?approval_status=approved">
✅ موافق عليها
</a>

<a href="?approval_status=rejected">
❌ مرفوضة
</a>

</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="card shadow border-0 mb-4">


<div class="card-header bg-white">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

<h5 class="mb-0 fw-bold">
🚚 قائمة الطلبات
</h5>

<span class="badge bg-primary">
<?= $totalRows ?>
طلب
</span>

</div>

</div>


<div class="table-responsive">

<table class="table table-hover align-middle mb-0 text-center orders-table">

<thead>

<tr>

<th>#</th>

<th>رقم الطلب</th>

<th>العميل</th>

<th>الجوال</th>

<th>من</th>

<th>إلى</th>

<th>السعر</th>

<th>الحالة</th>

<th>الموافقة</th>

<th>المزود</th>

<th>تاريخ الطلب</th>

<th>نوع الحجز</th>

<th>التاريخ</th>

<th>الوقت</th>

<th>الإجراءات</th>

<th>التحديث</th>

</tr>

</thead>


<tbody>


<?php if (!$result || mysqli_num_rows($result) === 0): ?>

<tr>

<td colspan="16" class="py-5 text-muted">

<i class="fa-solid fa-inbox fa-2x mb-3"></i>

<br>

لا توجد طلبات مطابقة للبحث

</td>

</tr>

<?php endif; ?>


<?php if ($result): ?>

<?php while ($row = mysqli_fetch_assoc($result)): ?>


<?php

$status =
    $row['status'] ?? 'pending';

$approval =
    $row['approval_status'] ?? 'pending';


switch ($status) {

    case 'pending':

        $statusClass = 'warning';
        $statusText = '⏳ انتظار';

        break;

    case 'assigned':

        $statusClass = 'primary';
        $statusText = '🚚 معين';

        break;

    case 'done':

        $statusClass = 'success';
        $statusText = '✅ مكتمل';

        break;

    case 'cancelled':

        $statusClass = 'danger';
        $statusText = '❌ ملغي';

        break;

    default:

        $statusClass = 'secondary';
        $statusText = $status;
}

?>


<tr>


<td>
<?= (int)$row['id'] ?>
</td>


<td>

<strong>

<?= htmlspecialchars(
    $row['order_number']
    ?? ('#' . $row['id'])
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $row['full_name'] ?? ''
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['phone'] ?? ''
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['from_city'] ?? ''
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['to_city'] ?? ''
) ?>

</td>


<td>

<span class="fw-bold text-success">

<?= number_format(
    (float)($row['price'] ?? 0),
    2
) ?>

ر.س

</span>

</td>


<td>

<span class="badge bg-<?= $statusClass ?>">

<?= $statusText ?>

</span>

</td>


<!-- =================================================
     الموافقة
================================================= -->

<td>

<?php if ($status === 'done'): ?>

<span class="badge bg-success">
✅ مكتمل
</span>


<?php elseif ($status === 'cancelled'): ?>

<span class="badge bg-danger">
❌ ملغي
</span>


<?php elseif ($approval === 'approved'): ?>

<span class="badge bg-success">
✅ تمت الموافقة
</span>


<?php elseif ($approval === 'rejected'): ?>

<span class="badge bg-danger">
❌ مرفوض
</span>


<?php elseif ($approval === 'pending'): ?>

<span class="badge bg-warning text-dark">
⏳ بانتظار الموافقة
</span>


<?php else: ?>

<span class="badge bg-secondary">

<?= htmlspecialchars($approval) ?>

</span>

<?php endif; ?>

</td>


<!-- =================================================
     المزود
================================================= -->

<td>

<?php if (!empty($row['driver_name'])): ?>

<span class="fw-bold">

<?= htmlspecialchars(
    $row['driver_name']
) ?>

</span>

<?php else: ?>

<span class="text-muted">
غير محدد
</span>

<?php endif; ?>

</td>


<td>

<?= htmlspecialchars(
    $row['created_at'] ?? ''
) ?>

</td>


<td>

<?php if (
    ($row['booking_type'] ?? '') === 'instant'
): ?>

<span class="text-success fw-bold">
🚀 فوري
</span>

<?php else: ?>

<span class="text-primary fw-bold">
📅 مجدول
</span>

<?php endif; ?>

</td>


<td>

<?php if (
    ($row['booking_type'] ?? '') === 'scheduled'
): ?>

<?= htmlspecialchars(
    $row['scheduled_date'] ?? '-'
) ?>

<?php else: ?>

-

<?php endif; ?>

</td>


<td>

<?php if (
    ($row['booking_type'] ?? '') === 'scheduled'
): ?>

<?= htmlspecialchars(
    $row['scheduled_time'] ?? '-'
) ?>

<?php else: ?>

-

<?php endif; ?>

</td>


<!-- =================================================
     الإجراءات
================================================= -->

<td>

<div class="action-buttons">


<a
    href="order_details.php?id=<?= (int)$row['id'] ?>"
    class="btn btn-info"
    title="عرض الطلب"
>

<i class="fa-solid fa-eye"></i>

</a>


<!-- الموافقة والرفض -->

<?php if (
    $approval === 'pending' &&
    $status !== 'done' &&
    $status !== 'cancelled'
): ?>


<form
    method="POST"
    style="display:inline;"
    onsubmit="return confirm('هل تريد الموافقة على هذا الطلب؟');"
>

<input
    type="hidden"
    name="order_id"
    value="<?= (int)$row['id'] ?>"
>

<input
    type="hidden"
    name="approval_action"
    value="approve"
>

<button
    type="submit"
    class="btn btn-success"
    title="موافقة"
>

<i class="fa-solid fa-check"></i>

</button>

</form>


<form
    method="POST"
    style="display:inline;"
    onsubmit="return confirm('هل تريد رفض هذا الطلب؟');"
>

<input
    type="hidden"
    name="order_id"
    value="<?= (int)$row['id'] ?>"
>

<input
    type="hidden"
    name="approval_action"
    value="reject"
>

<button
    type="submit"
    class="btn btn-danger"
    title="رفض"
>

<i class="fa-solid fa-xmark"></i>

</button>

</form>

<?php endif; ?>


<a
    href="edit_order.php?id=<?= (int)$row['id'] ?>"
    class="btn btn-warning"
    title="تعديل"
>

<i class="fa-solid fa-pen"></i>

</a>


<a
    href="invoice.php?id=<?= (int)$row['id'] ?>"
    class="btn btn-success"
    title="الفاتورة"
>

<i class="fa-solid fa-file-invoice"></i>

</a>


<a
    href="ordersview.php?delete=<?= (int)$row['id'] ?>"
    class="btn btn-danger"
    title="حذف"
    onclick="return confirm('هل تريد حذف الطلب؟');"
>

<i class="fa-solid fa-trash"></i>

</a>


</div>

</td>


<!-- =================================================
     تحديث المزود والحالة
================================================= -->

<td>

<form
    action="ordersview.php"
    method="POST"
    class="driver-form"
>


<input
    type="hidden"
    name="order_id"
    value="<?= (int)$row['id'] ?>"
>


<select
    name="driver_id"
    class="form-select form-select-sm"
>

<option value="0">
🚚 مزود
</option>


<?php foreach ($drivers_list as $d): ?>

<option
    value="<?= (int)$d['id'] ?>"
    <?= (
        (int)($row['driver_id'] ?? 0)
        ===
        (int)$d['id']
    )
        ? 'selected'
        : ''
    ?>
>

<?= htmlspecialchars(
    $d['name']
) ?>

</option>

<?php endforeach; ?>

</select>


<select
    name="status"
    class="form-select form-select-sm"
>

<option
    value="pending"
    <?= $status === 'pending'
        ? 'selected'
        : ''
    ?>
>
⏳
</option>


<option
    value="assigned"
    <?= $status === 'assigned'
        ? 'selected'
        : ''
    ?>
>
🚚
</option>


<option
    value="done"
    <?= $status === 'done'
        ? 'selected'
        : ''
    ?>
>
✅
</option>


<option
    value="cancelled"
    <?= $status === 'cancelled'
        ? 'selected'
        : ''
    ?>
>
❌
</option>

</select>


<button
    type="submit"
    name="update_order"
    value="1"
    class="btn btn-success btn-sm"
    title="حفظ"
>

<i class="fa-solid fa-check"></i>

</button>


</form>

</td>


</tr>


<?php endwhile; ?>

<?php endif; ?>


</tbody>

</table>

</div>


<!-- =====================================================
     Pagination
===================================================== -->

<?php if ($totalPages > 1): ?>

<nav class="mt-4 pb-3">

<ul class="pagination justify-content-center flex-wrap">


<li
    class="page-item
    <?= $page <= 1 ? 'disabled' : '' ?>"
>

<a
    class="page-link"
    href="?page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&filter=<?= urlencode($filter) ?>&approval_status=<?= urlencode($approval_filter) ?>"
>

« السابق

</a>

</li>


<?php

$startPage =
    max(1, $page - 2);

$endPage =
    min($totalPages, $page + 2);


for (
    $i = $startPage;
    $i <= $endPage;
    $i++
):

?>

<li
    class="page-item
    <?= $page == $i ? 'active' : '' ?>"
>

<a
    class="page-link"
    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&filter=<?= urlencode($filter) ?>&approval_status=<?= urlencode($approval_filter) ?>"
>

<?= $i ?>

</a>

</li>

<?php endfor; ?>


<li
    class="page-item
    <?= $page >= $totalPages ? 'disabled' : '' ?>"
>

<a
    class="page-link"
    href="?page=<?= min($totalPages, $page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&filter=<?= urlencode($filter) ?>&approval_status=<?= urlencode($approval_filter) ?>"
>

التالي »

</a>

</li>


</ul>

</nav>

<?php endif; ?>


</div>

</div>

</div>


<script>

if (typeof toggleDarkMode !== 'function') {

    function toggleDarkMode() {

        document.body.classList.toggle(
            'dark-mode'
        );

        localStorage.setItem(
            'darkMode',
            document.body.classList.contains(
                'dark-mode'
            )
        );
    }
}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (
            localStorage.getItem('darkMode')
            === 'true'
        ) {

            document.body.classList.add(
                'dark-mode'
            );
        }

    }
);

</script>


</body>

</html>