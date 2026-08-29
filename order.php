<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Riyadh');


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

/* =========================================================
   EMAIL FUNCTIONS
========================================================= */

require_once __DIR__ . '/include/send_email.php';


if (!isset($con) || !($con instanceof mysqli)) {
    die('خطأ في الاتصال بقاعدة البيانات.');
}

$con->set_charset('utf8mb4');


/* =========================================================
   OPTIONAL FILES
========================================================= */

if (file_exists(__DIR__ . '/admin/notifications_helper.php')) {
    require_once __DIR__ . '/admin/notifications_helper.php';
}

// if (file_exists(__DIR__ . '/admin/mail.php')) {
//     require_once __DIR__ . '/admin/mail.php';
// }

if (file_exists(__DIR__ . '/include/settings.php')) {
    require_once __DIR__ . '/include/settings.php';
}


/* =========================================================
   HELPER
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   LOGIN
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    (int)$_SESSION['user_id'] <= 0
) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];


/* =========================================================
   MESSAGE
========================================================= */

$error = '';

$success = '';


/* =========================================================
   GET USER
========================================================= */

try {

    $stmt = $con->prepare("
        SELECT
            id,
            username,
            phone,
            email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception(
            'لم يتم العثور على بيانات العميل.'
        );
    }

    $user = $result->fetch_assoc();

    $stmt->close();

} catch (Throwable $e) {

    die(
        'خطأ في جلب بيانات العميل: ' .
        h($e->getMessage())
    );
}


/* =========================================================
   DEFAULT USER DATA
========================================================= */

$default_name =
    trim($user['username'] ?? '');

$default_phone =
    trim($user['phone'] ?? '');

$default_email =
    trim($user['email'] ?? '');


/* =========================================================
   GET CART
========================================================= */

$cart_data = [];

$total = 0;


try {

    $stmt = $con->prepare("
        SELECT *
        FROM cart
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $product_id =
            (int)($row['product_id'] ?? 0);

        $quantity =
            (int)($row['quantity'] ?? 0);

        $price =
            (float)($row['price'] ?? 0);

        if (
            $product_id <= 0 ||
            $quantity <= 0
        ) {
            continue;
        }

        $total +=
            $price * $quantity;

        $cart_data[] = $row;
    }


    $stmt->close();


} catch (Throwable $e) {

    die(
        'خطأ في قراءة السلة: ' .
        h($e->getMessage())
    );
}


/* =========================================================
   EMPTY CART
========================================================= */

if (empty($cart_data)) {

    header("Location: cart.php");
    exit;
}


/* =========================================================
   PROCESS ORDER
=========================================================

   مهم:
   نعتمد على hidden input اسمه checkout_submit
   وليس على اسم الزر.
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['checkout_submit'])
) {

    try {

        /* =================================================
           FORM DATA
        ================================================= */

        $full_name =
            trim($_POST['full_name'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $phone =
            trim($_POST['phone'] ?? '');

        $city =
            trim($_POST['city'] ?? '');

        $address =
            trim($_POST['address'] ?? '');

        $payment_method =
            trim($_POST['payment_method'] ?? '');


        /* =================================================
           VALIDATION
        ================================================= */

        if ($full_name === '') {
            throw new Exception(
                'الرجاء إدخال اسم العميل.'
            );
        }

        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new Exception(
                'البريد الإلكتروني غير صحيح.'
            );
        }

        if ($phone === '') {
            throw new Exception(
                'الرجاء إدخال رقم الجوال.'
            );
        }

        if ($city === '') {
            throw new Exception(
                'الرجاء إدخال المدينة.'
            );
        }

        if ($address === '') {
            throw new Exception(
                'الرجاء إدخال العنوان.'
            );
        }

        if ($payment_method === '') {
            throw new Exception(
                'الرجاء اختيار طريقة الدفع.'
            );
        }

        if ($total <= 0) {
            throw new Exception(
                'إجمالي الطلب غير صحيح.'
            );
        }


        /* =================================================
           TRANSACTION
        ================================================= */

        $con->begin_transaction();


        /* =================================================
           ORDER NUMBER
        ================================================= */

        $order_number =
            'ORD-' .
            date('YmdHis') .
            '-' .
            random_int(100, 999);


        /* =================================================
           INSERT ORDER
        =================================================

           الحقول الموجودة فعلياً في جدول orders
        ================================================= */

        $stmt = $con->prepare("
            INSERT INTO orders
            (
                order_number,
                full_name,
                email,
                phone,
                city,
                address,
                user_id,
                payment_method,
                approval_status,
                status,
                price,
                order_type,
                service_type,
                booking_type
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'pending',
                'pending',
                ?,
                'cart',
                'intercity',
                'instant'
            )
        ");


        $stmt->bind_param(
            "ssssssisd",
            $order_number,
            $full_name,
            $email,
            $phone,
            $city,
            $address,
            $user_id,
            $payment_method,
            $total
        );


        $stmt->execute();


        $order_id =
            (int)$stmt->insert_id;


        $stmt->close();


        if ($order_id <= 0) {

            throw new Exception(
                'لم يتم إنشاء رقم الطلب.'
            );
        }


        /* =================================================
           INSERT ORDER DETAILS
        ================================================= */

        $detailStmt = $con->prepare("
            INSERT INTO order_details
            (
                order_id,
                product_id,
                quantity,
                price,
                img
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        $details_count = 0;


        foreach ($cart_data as $item) {

            $product_id =
                (int)($item['product_id'] ?? 0);

            $quantity =
                (int)($item['quantity'] ?? 0);

            $price =
                (float)($item['price'] ?? 0);

            $img =
                (string)($item['img'] ?? '');


            if (
                $product_id <= 0 ||
                $quantity <= 0
            ) {
                continue;
            }


            $detailStmt->bind_param(
                "iiids",
                $order_id,
                $product_id,
                $quantity,
                $price,
                $img
            );


            $detailStmt->execute();

            $details_count++;
        }


        $detailStmt->close();


        if ($details_count <= 0) {

            throw new Exception(
                'لم يتم حفظ منتجات الطلب.'
            );
        }


        /* =================================================
           DELETE CART
        ================================================= */

        $stmt = $con->prepare("
            DELETE FROM cart
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $stmt->close();


        /* =================================================
           COMMIT
        ================================================= */

        $con->commit();


                /* =================================================
           EMAIL NOTIFICATIONS
        ================================================= */

        $customerEmailSent = false;
        $adminEmailSent    = false;


        /* =================================================
           1. EMAIL TO CUSTOMER
        ================================================= */

        if (function_exists('sendNewProductOrderCustomerEmail')) {

            try {

                $customerEmailSent =
                    sendNewProductOrderCustomerEmail(
                        $email,
                        $order_id,
                        $full_name,
                        $phone,
                        $city,
                        $address,
                        $total,
                        $payment_method
                    );

                error_log(
                    'Customer order email result for order #' .
                    $order_id .
                    ': ' .
                    ($customerEmailSent ? 'SUCCESS' : 'FAILED')
                );

            } catch (Throwable $emailError) {

                error_log(
                    'Customer order email exception for order #' .
                    $order_id .
                    ': ' .
                    $emailError->getMessage()
                );
            }

        } else {

            error_log(
                'ERROR: sendNewProductOrderCustomerEmail() not found.'
            );
        }


        /* =================================================
           2. GET ADMIN EMAIL
        ================================================= */

        $adminEmail = '';


        if (
            isset($settings) &&
            is_array($settings)
        ) {

            $adminEmail =
                trim(
                    $settings['company_email'] ?? ''
                );
        }


        /*
         * إذا لم يوجد البريد داخل settings
         * نستخدم بريد الإدارة الرئيسي مؤقتاً.
         *
         * غيّره إذا كان لديك بريد مختلف للإدارة.
         */

        if ($adminEmail === '') {

            $adminEmail =
                'ahmedhider359@gmail.com';
        }


        /* =================================================
           3. EMAIL TO ADMIN
        ================================================= */

        if (
            $adminEmail !== '' &&
            function_exists('sendNewProductOrderAdminEmail')
        ) {

            try {

                $adminEmailSent =
                    sendNewProductOrderAdminEmail(
                        $adminEmail,
                        $order_id,
                        $full_name,
                        $email,
                        $phone,
                        $city,
                        $address,
                        $total,
                        $payment_method
                    );

                error_log(
                    'Admin order email result for order #' .
                    $order_id .
                    ' to ' .
                    $adminEmail .
                    ': ' .
                    ($adminEmailSent ? 'SUCCESS' : 'FAILED')
                );

            } catch (Throwable $emailError) {

                error_log(
                    'Admin order email exception for order #' .
                    $order_id .
                    ': ' .
                    $emailError->getMessage()
                );
            }

        } else {

            error_log(
                'ERROR: Admin email function unavailable or admin email empty.'
            );
        }


        /* =================================================
           NOTIFICATION
        ================================================= */

        if (function_exists('addNotification')) {

            try {

                addNotification(
                    $con,
                    "تم استلام طلبك",
                    "تم استلام طلبك رقم #{$order_id} بنجاح.",
                    "order",
                    $user_id,
                    $order_id
                );

            } catch (Throwable $notificationError) {

                error_log(
                    'Order notification error for #' .
                    $order_id .
                    ': ' .
                    $notificationError->getMessage()
                );
            }
        }


        /* =================================================
           REDIRECT
        ================================================= */

        header(
            "Location: myorderdetails.php?id=" .
            $order_id
        );

        exit;

        /* =================================================
           NOTIFICATION
        ================================================= */

        if (function_exists('addNotification')) {

            try {

                addNotification(
                    $con,
                    "تم استلام طلبك",
                    "تم استلام طلبك رقم #{$order_id} بنجاح.",
                    "order",
                    $user_id,
                    $order_id
                );

            } catch (Throwable $notificationError) {

                // لا نوقف الطلب
            }
        }


        /* =================================================
           REDIRECT
        ================================================= */

        header(
            "Location: myorderdetails.php?id=" .
            $order_id
        );

        exit;


    } catch (Throwable $e) {


        /* =================================================
           ROLLBACK
        ================================================= */

        try {
            $con->rollback();
        } catch (Throwable $rollbackError) {
        }


        $error =
            $e->getMessage();
    }
}

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>إتمام الطلب</title>


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#f4f7fb;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    color:#1f2937;
}


.container{

    width:94%;

    max-width:1150px;

    margin:35px auto 60px;
}


.page-title{

    text-align:center;

    color:#173b82;

    font-size:32px;

    margin-bottom:30px;
}


/* =========================================================
   ERROR
========================================================= */

.error-box{

    background:#fff1f2;

    border:1px solid #fecdd3;

    color:#be123c;

    padding:18px;

    border-radius:14px;

    margin-bottom:25px;

    font-weight:bold;

    line-height:1.8;
}


/* =========================================================
   GRID
========================================================= */

.checkout-grid{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:25px;
}


/* =========================================================
   CARD
========================================================= */

.card{

    background:#fff;

    padding:25px;

    border-radius:20px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.07);
}


.card h2{

    margin:
        0 0 20px;

    color:#173b82;

    font-size:21px;
}


/* =========================================================
   PRODUCTS
========================================================= */

.product-box{

    display:flex;

    align-items:center;

    gap:15px;

    background:#f8fafc;

    border:1px solid #e5e7eb;

    border-radius:14px;

    padding:14px;

    margin-bottom:12px;
}


.product-box img{

    width:70px;

    height:70px;

    object-fit:cover;

    border-radius:10px;

    border:1px solid #ddd;
}


.product-info{

    flex:1;
}


.product-info p{

    margin:
        5px 0;

    font-size:14px;
}


.product-price{

    color:#2563eb;

    font-weight:bold;
}


/* =========================================================
   TOTAL
========================================================= */

.total{

    margin-top:20px;

    padding:18px;

    background:#eff6ff;

    border-radius:14px;

    text-align:center;

    color:#1d4ed8;

    font-size:23px;

    font-weight:bold;
}


/* =========================================================
   INFO
========================================================= */

.info{

    background:#ecfdf5;

    color:#047857;

    padding:12px;

    border-radius:12px;

    margin-bottom:20px;

    font-size:13px;

    line-height:1.8;
}


/* =========================================================
   FORM
========================================================= */

.form-group{

    margin-bottom:16px;
}


.form-group label{

    display:block;

    margin-bottom:7px;

    font-weight:bold;
}


.form-group input,
.form-group select{

    width:100%;

    padding:13px;

    border:1px solid #d1d5db;

    border-radius:10px;

    font-family:inherit;

    font-size:15px;

    outline:none;

    background:#fff;
}


.form-group input:focus,
.form-group select:focus{

    border-color:#2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.1);
}


/* =========================================================
   BUTTON
========================================================= */

.order-button{

    width:100%;

    padding:16px;

    margin-top:8px;

    border:0;

    border-radius:12px;

    background:#2563eb;

    color:#fff;

    font-family:inherit;

    font-size:18px;

    font-weight:bold;

    cursor:pointer;

    display:block;
}


.order-button:hover{

    background:#1d4ed8;
}


@media(max-width:800px){

    .checkout-grid{

        grid-template-columns:1fr;
    }

    .container{

        width:92%;
    }

    .page-title{

        font-size:26px;
    }

}

</style>

</head>


<body>


<div class="container">


    <h1 class="page-title">
        إتمام الطلب
    </h1>


    <?php if ($error !== ''): ?>

        <div class="error-box">

            ⚠️ لم يتم تأكيد الطلب

            <br>

            <?= h($error) ?>

        </div>

    <?php endif; ?>


    <div class="checkout-grid">


        <!-- =================================================
             ORDER SUMMARY
        ================================================= -->

        <div class="card">

            <h2>
                🛒 ملخص الطلب
            </h2>


            <?php foreach ($cart_data as $item): ?>

                <?php

                $product_id =
                    (int)($item['product_id'] ?? 0);

                $quantity =
                    (int)($item['quantity'] ?? 0);

                $price =
                    (float)($item['price'] ?? 0);

                $img =
                    trim($item['img'] ?? '');

                ?>


                <div class="product-box">


                    <?php if ($img !== ''): ?>

                        <img
                            src="uploads/img/<?= h($img) ?>"
                            alt="المنتج"
                            onerror="this.style.display='none';"
                        >

                    <?php endif; ?>


                    <div class="product-info">

                        <strong>
                            المنتج #<?= $product_id ?>
                        </strong>


                        <p>
                            الكمية:
                            <?= $quantity ?>
                        </p>


                        <p class="product-price">

                            السعر:

                            <?= number_format(
                                $price,
                                2
                            ) ?>

                        </p>

                    </div>

                </div>

            <?php endforeach; ?>


            <div class="total">

                الإجمالي:

                <?= number_format(
                    $total,
                    2
                ) ?>

            </div>

        </div>


        <!-- =================================================
             CUSTOMER
        ================================================= -->

        <div class="card">

            <h2>
                👤 بيانات العميل
            </h2>


            <div class="info">

                ✓ بيانات الاسم والبريد والجوال تم جلبها
                من حسابك تلقائياً.

            </div>


            <!-- =================================================
                 FORM
            ================================================= -->

            <form
                method="POST"
                action="<?= h($_SERVER['PHP_SELF']) ?>"
            >

                <!-- مهم جداً -->
                <input
                    type="hidden"
                    name="checkout_submit"
                    value="1"
                >


                <div class="form-group">

                    <label>
                        الاسم الكامل
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        value="<?= h(
                            $_POST['full_name']
                            ?? $default_name
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        البريد الإلكتروني
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= h(
                            $_POST['email']
                            ?? $default_email
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        رقم الجوال
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?= h(
                            $_POST['phone']
                            ?? $default_phone
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        المدينة
                    </label>

                    <input
                        type="text"
                        name="city"
                        value="<?= h(
                            $_POST['city']
                            ?? ''
                        ) ?>"
                        placeholder="مثال: الرياض"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        العنوان
                    </label>

                    <input
                        type="text"
                        name="address"
                        value="<?= h(
                            $_POST['address']
                            ?? ''
                        ) ?>"
                        placeholder="العنوان"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        طريقة الدفع
                    </label>

                    <select
                        name="payment_method"
                        required
                    >

                        <option value="">
                            اختر طريقة الدفع
                        </option>


                        <option
                            value="cash"
                            <?= (
                                ($_POST['payment_method'] ?? '')
                                === 'cash'
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >
                            كاش
                        </option>


                        <option
                            value="card"
                            <?= (
                                ($_POST['payment_method'] ?? '')
                                === 'card'
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >
                            بطاقة
                        </option>


                        <option
                            value="bank"
                            <?= (
                                ($_POST['payment_method'] ?? '')
                                === 'bank'
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >
                            تحويل بنكي
                        </option>

                    </select>

                </div>


                <!-- =================================================
                     زر التأكيد
                ================================================= -->

                <button
                    type="submit"
                    class="order-button"
                >

                    ✓ تأكيد الطلب

                </button>


            </form>


        </div>

    </div>

</div>

</body>

</html>