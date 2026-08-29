<?php

session_start();

if(!isset($_SESSION['user_id'])){
    echo '<script>
    alert("يرجى تسجيل الدخول أولاً");
    window.location.href="user/login.php";
    </script>';
    exit();
}
ob_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   SETTINGS
========================================================= */

$settings = [];

$settingsQuery = $con->query("
    SELECT setting_key, setting_value
    FROM settings
");

while ($setting = $settingsQuery->fetch_assoc()) {

    $settings[
        $setting['setting_key']
    ] = $setting['setting_value'];

}

$companyEmail =
    trim($settings['company_email'] ?? '');

$companyName =
    trim($settings['company_name'] ?? '');

$companyPhone =
    trim($settings['company_phone'] ?? '');


/* =========================================================
   EMAIL FUNCTIONS
========================================================= */

require_once __DIR__ . '/include/send_email.php';


/* =========================================================
   LOGIN PROTECTION
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo '
    <div class="tow-message error">
        يجب تسجيل الدخول أولاً
    </div>
    ';

    exit;
}

$user_id =
    (int)$_SESSION['user_id'];


/* =========================================================
   CUSTOMER DATA
========================================================= */

$customer_name  = '';
$customer_phone = '';
$customer_email = '';

$userStmt = $con->prepare("
    SELECT username, phone, email
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->bind_param(
    "i",
    $user_id
);

$userStmt->execute();

$userResult =
    $userStmt->get_result();

if ($userRow =
    $userResult->fetch_assoc()
) {

    $customer_name =
        trim($userRow['username'] ?? '');

    $customer_phone =
        trim($userRow['phone'] ?? '');

    $customer_email =
        trim($userRow['email'] ?? '');
}

$userStmt->close();


/* =========================================================
   AJAX - GET TO CITIES
========================================================= */

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'get_to_cities'
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    $fromCity =
        trim($_GET['from_city'] ?? '');

    $cities = [];

    if ($fromCity !== '') {

        $stmt = $con->prepare("
            SELECT DISTINCT to_city
            FROM transport_pricing
            WHERE from_city = ?
              AND to_city IS NOT NULL
              AND to_city <> ''
            ORDER BY to_city ASC
        ");

        $stmt->bind_param(
            "s",
            $fromCity
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        while ($row =
            $result->fetch_assoc()
        ) {

            $cities[] =
                $row['to_city'];
        }

        $stmt->close();
    }

    echo json_encode(
        $cities,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   AJAX - GET PRICE
========================================================= */

if (
    isset($_GET['action']) &&
    $_GET['action'] === 'get_price'
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    $fromCity =
        trim($_GET['from_city'] ?? '');

    $toCity =
        trim($_GET['to_city'] ?? '');

    $carType =
        trim($_GET['car_type'] ?? '');

    $priceColumns = [

        'normal' =>
            'regular_customer',

        'hydraulic' =>
            'hydraulic_customer',

        'covered' =>
            'covered_customer'

    ];

    $price = null;

    if (
        $fromCity !== '' &&
        $toCity !== '' &&
        isset($priceColumns[$carType])
    ) {

        $priceColumn =
            $priceColumns[$carType];

        $stmt = $con->prepare("
            SELECT `$priceColumn` AS price
            FROM transport_pricing
            WHERE from_city = ?
              AND to_city = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ss",
            $fromCity,
            $toCity
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        if ($row =
            $result->fetch_assoc()
        ) {

            if (
                $row['price'] !== null &&
                $row['price'] !== ''
            ) {

                $price =
                    (float)$row['price'];
            }
        }

        $stmt->close();
    }

    echo json_encode(
        [
            'success' =>
                $price !== null,

            'price' =>
                $price
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   LOAD FROM CITIES
========================================================= */

$cities = [];

$stmt = $con->prepare("
    SELECT DISTINCT from_city
    FROM transport_pricing
    WHERE from_city IS NOT NULL
      AND from_city <> ''
    ORDER BY from_city ASC
");

$stmt->execute();

$result =
    $stmt->get_result();

while ($row =
    $result->fetch_assoc()
) {

    $cities[] =
        $row['from_city'];
}

$stmt->close();


/* =========================================================
   CREATE ORDER
   مهم:
   هذا هو المكان الوحيد الذي يتم فيه INSERT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['orderadd'])
) {

    try {

        /* =====================================================
           CUSTOMER
        ===================================================== */

        $full_name =
            trim($customer_name);

        $phone =
            trim($customer_phone);


        /* =====================================================
           TRIP
        ===================================================== */

        $from_city =
            trim($_POST['from_city'] ?? '');

        $to_city =
            trim($_POST['to_city'] ?? '');


        /* =====================================================
           MAP
        ===================================================== */

        $from_lat =
            trim($_POST['from_lat'] ?? '');

        $from_lng =
            trim($_POST['from_lng'] ?? '');

        $to_lat =
            trim($_POST['to_lat'] ?? '');

        $to_lng =
            trim($_POST['to_lng'] ?? '');


        /* =====================================================
           CAR TYPE
        ===================================================== */

        $car_type =
            trim($_POST['car_type'] ?? '');


        /* =====================================================
           VEHICLE
        ===================================================== */

        $vehicle_make =
            trim($_POST['vehicle_make'] ?? '');

        $vehicle_model =
            trim($_POST['vehicle_model'] ?? '');

        $vehicle_year =
            trim($_POST['vehicle_year'] ?? '');

        $plate_number =
            trim($_POST['plate_number'] ?? '');

        $body_type =
            trim($_POST['body_type'] ?? '');

        $vehicle_color =
            trim($_POST['vehicle_color'] ?? '');


        /* =====================================================
           BOOKING
        ===================================================== */

        $booking_type =
            trim(
                $_POST['booking_type'] ??
                'instant'
            );

        $scheduled_date =
            !empty($_POST['scheduled_date'])
                ? $_POST['scheduled_date']
                : null;

        $scheduled_time =
            !empty($_POST['scheduled_time'])
                ? $_POST['scheduled_time']
                : null;


        /* =====================================================
           PAYMENT
        ===================================================== */

        $payment_method =
            trim(
                $_POST['payment_method'] ??
                'cash'
            );


        /* =====================================================
           VALIDATION
        ===================================================== */

        if ($full_name === '') {

            throw new Exception(
                'اسم العميل غير موجود'
            );
        }

        if ($phone === '') {

            throw new Exception(
                'رقم الجوال غير موجود'
            );
        }

        if (
            $from_city === '' ||
            $to_city === ''
        ) {

            throw new Exception(
                'يرجى اختيار مدينة التحميل والتنزيل'
            );
        }

        if ($car_type === '') {

            throw new Exception(
                'يرجى اختيار نوع السطحة'
            );
        }

        if (
            $vehicle_make === '' ||
            $vehicle_model === '' ||
            $vehicle_year === '' ||
            $plate_number === '' ||
            $body_type === '' ||
            $vehicle_color === ''
        ) {

            throw new Exception(
                'يرجى تعبئة جميع بيانات السيارة'
            );
        }

        if (
            $from_lat === '' ||
            $from_lng === '' ||
            $to_lat === '' ||
            $to_lng === ''
        ) {

            throw new Exception(
                'يجب تحديد موقع التحميل والتنزيل من الخريطة'
            );
        }


        /* =====================================================
           BOOKING VALIDATION
        ===================================================== */

        if ($booking_type === 'scheduled') {

            if (
                empty($scheduled_date) ||
                empty($scheduled_time)
            ) {

                throw new Exception(
                    'يرجى تحديد تاريخ ووقت الحجز'
                );
            }

        } else {

            $booking_type =
                'instant';

            $scheduled_date =
                null;

            $scheduled_time =
                null;
        }


        /* =====================================================
           DISTANCE
        ===================================================== */

        $R = 6371;

        $dLat = deg2rad(
            (float)$to_lat -
            (float)$from_lat
        );

        $dLon = deg2rad(
            (float)$to_lng -
            (float)$from_lng
        );

        $a =
            sin($dLat / 2) ** 2
            +
            cos(
                deg2rad(
                    (float)$from_lat
                )
            )
            *
            cos(
                deg2rad(
                    (float)$to_lat
                )
            )
            *
            sin($dLon / 2) ** 2;

        $distance =
            $R *
            (
                2 *
                atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                )
            );


        /* =====================================================
           PRICE COLUMN
        ===================================================== */

        $priceColumns = [

            'normal' =>
                'regular_customer',

            'hydraulic' =>
                'hydraulic_customer',

            'covered' =>
                'covered_customer'

        ];

        if (
            !isset(
                $priceColumns[$car_type]
            )
        ) {

            throw new Exception(
                'نوع السطحة غير صحيح'
            );
        }

        $priceColumn =
            $priceColumns[$car_type];


        /* =====================================================
           GET PRICE
        ===================================================== */

        $price = null;

        $priceStmt =
            $con->prepare("
                SELECT `$priceColumn` AS price
                FROM transport_pricing
                WHERE from_city = ?
                  AND to_city = ?
                LIMIT 1
            ");

        $priceStmt->bind_param(
            "ss",
            $from_city,
            $to_city
        );

        $priceStmt->execute();

        $priceResult =
            $priceStmt->get_result();

        if ($priceRow =
            $priceResult->fetch_assoc()
        ) {

            if (
                $priceRow['price'] !== null &&
                $priceRow['price'] !== ''
            ) {

                $price =
                    (float)$priceRow['price'];
            }
        }

        $priceStmt->close();


        if ($price === null) {

            throw new Exception(
                'لا يوجد سعر مسجل لهذا المسار ونوع السطحة'
            );
        }

        if ($price < 0) {

            throw new Exception(
                'قيمة السعر غير صحيحة'
            );
        }


        /* =====================================================
           ORDER DATA
        ===================================================== */

        $order_type =
            'tow';

        $service_type =
            'tow';

        $status =
            'pending';

        $created_by =
            'customer';

        $approval_status =
            'pending';


        /* =====================================================
           INSERT ORDER
        ===================================================== */

        $orderStmt =
            $con->prepare("

                INSERT INTO orders
                (
                    full_name,
                    phone,

                    from_city,
                    to_city,

                    pickup_lat,
                    pickup_lng,

                    delivery_lat,
                    delivery_lng,

                    car_type,

                    vehicle_make,
                    vehicle_model,
                    vehicle_year,
                    plate_number,
                    body_type,
                    vehicle_color,

                    distance,
                    price,

                    order_type,
                    service_type,

                    status,
                    user_id,
                    payment_method,

                    booking_type,
                    scheduled_date,
                    scheduled_time,

                    created_by,
                    approval_status
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

                    ?,

                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,

                    ?,
                    ?,

                    ?,
                    ?,

                    ?,
                    ?,
                    ?,

                    ?,
                    ?,
                    ?,

                    ?,
                    ?
                )
            ");


        /*
         * 27 قيمة:
         *
         * 1-15  = string
         * 16-17 = double
         * 18-20 = string
         * 21    = integer
         * 22-27 = string
         */

        $orderStmt->bind_param(

            "sssssssssssssssddsssissssss",

            $full_name,
            $phone,

            $from_city,
            $to_city,

            $from_lat,
            $from_lng,

            $to_lat,
            $to_lng,

            $car_type,

            $vehicle_make,
            $vehicle_model,
            $vehicle_year,
            $plate_number,
            $body_type,
            $vehicle_color,

            $distance,
            $price,

            $order_type,
            $service_type,

            $status,
            $user_id,
            $payment_method,

            $booking_type,
            $scheduled_date,
            $scheduled_time,

            $created_by,
            $approval_status
        );


        /* =====================================================
   EXECUTE - DEBUG
===================================================== */

try {

    $orderStmt->execute();

    $order_id = (int)$orderStmt->insert_id;

    $orderStmt->close();

    if ($order_id <= 0) {
        throw new Exception(
            'تم تنفيذ الحفظ ولكن لم يتم الحصول على رقم الطلب'
        );
    }

} catch (Throwable $e) {

    if (isset($orderStmt) && $orderStmt instanceof mysqli_stmt) {
        $orderStmt->close();
    }

    echo '<div style="
        direction:rtl;
        max-width:800px;
        margin:40px auto;
        padding:25px;
        background:#fff1f2;
        border:2px solid #dc3545;
        border-radius:15px;
        font-family:Arial;
        color:#842029;
    ">';

    echo '<h2>❌ فشل حفظ الطلب</h2>';

    echo '<p><strong>الخطأ:</strong></p>';

    echo '<pre style="
        white-space:pre-wrap;
        background:#fff;
        padding:15px;
        border-radius:10px;
        direction:ltr;
        text-align:left;
    ">';

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    echo '</pre>';

    echo '<p><strong>رقم الخطأ:</strong> '
        . (int)$e->getCode()
        . '</p>';

    echo '</div>';

    exit;
}


        /* =====================================================
           CHECK ORDER
        ===================================================== */

        if ($order_id <= 0) {

            throw new Exception(
                'تم تنفيذ الحفظ ولكن لم يتم الحصول على رقم الطلب'
            );
        }


        /* =====================================================
           CUSTOMER NOTIFICATION
        ===================================================== */

        if (
            function_exists(
                'addNotification'
            )
        ) {

            try {

                addNotification(

                    $con,

                    'تم استلام طلبك',

                    "تم استلام طلب السطحة رقم #$order_id وهو الآن بانتظار موافقة الإدارة.",

                    'order',

                    $user_id,

                    $order_id
                );

            } catch (Throwable $e) {

                error_log(
                    'Customer notification error: ' .
                    $e->getMessage()
                );
            }
        }


        /* =====================================================
           ADMIN NOTIFICATION
        ===================================================== */

        if (
            function_exists(
                'addAdminNotification'
            )
        ) {

            try {

                addAdminNotification(

                    $con,

                    'طلب سطحة جديد',

                    "تم إنشاء طلب سطحة جديد رقم #$order_id من العميل $full_name.",

                    'order',

                    $order_id
                );

            } catch (Throwable $e) {

                error_log(
                    'Admin notification error: ' .
                    $e->getMessage()
                );
            }
        }


        /* =====================================================
           ADMIN EMAIL
        ===================================================== */

        if (
            $companyEmail !== '' &&
            function_exists(
                'sendNewTowOrderAdminEmail'
            )
        ) {

            try {

                sendNewTowOrderAdminEmail(

                    $companyEmail,

                    $order_id,

                    $full_name,

                    $phone,

                    $from_city,

                    $to_city,

                    $car_type,

                    $price,

                    $payment_method,

                    $booking_type,

                    $scheduled_date,

                    $scheduled_time
                );

            } catch (Throwable $e) {

                error_log(
                    'Admin email error: ' .
                    $e->getMessage()
                );
            }
        }


        /* =====================================================
           CUSTOMER EMAIL
        ===================================================== */

        if (
            $customer_email !== '' &&
            function_exists(
                'sendNewTowOrderCustomerEmail'
            )
        ) {

            try {

                sendNewTowOrderCustomerEmail(

                    $customer_email,

                    $order_id,

                    $full_name,

                    $from_city,

                    $to_city,

                    $car_type,

                    $price,

                    $payment_method,

                    $booking_type,

                    $scheduled_date,

                    $scheduled_time
                );

            } catch (Throwable $e) {

                error_log(
                    'Customer email error: ' .
                    $e->getMessage()
                );
            }
        }


        /* =====================================================
           SUCCESS REDIRECT
        ===================================================== */

        header(
            'Location: myorderdetails.php?id=' .
            $order_id
        );

        exit;


    } catch (Throwable $e) {

        error_log(
            'Tow Order Error: ' .
            $e->getMessage()
        );

        echo '

        <div style="
            max-width:900px;
            margin:40px auto;
            padding:25px;
            background:#fff1f2;
            border:2px solid #dc3545;
            border-radius:15px;
            direction:rtl;
            font-family:Arial;
            color:#842029;
        ">

            <h3 style="margin-top:0;">
                ❌ حدث خطأ أثناء إنشاء الطلب
            </h3>

            <div style="
                background:#fff;
                padding:15px;
                border-radius:10px;
                line-height:2;
            ">

                ' .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '

            </div>

            <div style="
                margin-top:15px;
                color:#666;
                font-size:13px;
            ">
                يمكنك إرسال رسالة الخطأ هذه لي حتى نكمل الإصلاح.
            </div>

        </div>

        ';

        exit;
    }
}


/* =========================================================
   PAGE HEADER
========================================================= */

include('file/header.php');

?>

<style>

.tow-page {
    width:100%;
    background:#f5f7fb;
    padding:35px 15px 50px;
    direction:rtl;
}

.tow-wrapper {
    width:100%;
    max-width:900px;
    margin:0 auto;
}

.tow-card {
    background:#fff;
    border-radius:22px;
    padding:30px;
    box-shadow:0 12px 35px rgba(0,0,0,.08);
}

.tow-title {
    text-align:center;
    margin:0;
    color:#0d6efd;
    font-size:30px;
}

.tow-subtitle {
    text-align:center;
    color:#6b7280;
    margin:10px 0 30px;
}

.tow-section {
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:17px;
    padding:22px;
    margin-top:20px;
}

.tow-section-title {
    display:flex;
    align-items:center;
    gap:10px;
    margin:0 0 20px;
    color:#1f2937;
    font-size:19px;
}

.tow-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

.tow-field {
    display:flex;
    flex-direction:column;
    gap:7px;
}

.tow-field label {
    font-weight:700;
    color:#374151;
}

.tow-field input,
.tow-field select {
    width:100%;
    min-height:46px;
    padding:11px 13px;
    border:1px solid #d1d5db;
    border-radius:11px;
    background:#fff;
    font-size:15px;
    font-family:inherit;
}

.tow-field input:focus,
.tow-field select:focus {
    outline:none;
    border-color:#0d6efd;
    box-shadow:0 0 0 3px rgba(13,110,253,.10);
}

.tow-field input[readonly] {
    background:#eef1f4;
}

.tow-map-help {
    background:#eef6ff;
    border:1px solid #d7e9ff;
    color:#315b87;
    border-radius:11px;
    padding:12px 14px;
    margin-top:18px;
    line-height:1.7;
}

#towMap {
    height:380px;
    margin-top:14px;
    border-radius:16px;
    overflow:hidden;
    border:1px solid #d1d5db;
}

.tow-price {
    margin-top:20px;
    padding:22px;
    border-radius:16px;
    text-align:center;
    background:#f0fff4;
    border:1px solid #c6f6d5;
}

.tow-price-title {
    color:#6b7280;
    margin-bottom:7px;
}

.tow-price-value {
    font-size:34px;
    font-weight:800;
    color:#198754;
}

.tow-price-loading {
    color:#6b7280;
}

.tow-price-error {
    color:#dc3545;
    font-weight:700;
}

.tow-submit {
    width:100%;
    margin-top:25px;
    min-height:56px;
    border:0;
    border-radius:14px;
    background:#0d6efd;
    color:#fff;
    font-size:18px;
    font-weight:800;
    cursor:pointer;
}

.tow-submit:hover {
    background:#0b5ed7;
}

.tow-submit:disabled {
    opacity:.7;
    cursor:not-allowed;
}

.tow-message {
    max-width:700px;
    margin:40px auto;
    padding:20px;
    border-radius:14px;
    text-align:center;
    background:#fff;
}

.tow-message.error {
    color:#b42318;
    border:1px solid #f0b4ae;
}

@media(max-width:650px) {

    .tow-page {
        padding:20px 10px 35px;
    }

    .tow-card {
        padding:18px;
        border-radius:17px;
    }

    .tow-grid {
        grid-template-columns:1fr;
    }

    #towMap {
        height:320px;
    }

    .tow-title {
        font-size:25px;
    }
}

</style>


<div class="tow-page">

<div class="tow-wrapper">

<div class="tow-card">


<h1 class="tow-title">
    🚚 طلب سطحة
</h1>

<p class="tow-subtitle">
    أدخل بيانات السيارة وحدد موقع التحميل والتنزيل
</p>


<form
    method="POST"
    action=""
    id="towForm"
>


<!-- =====================================================
     CUSTOMER
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    👤 بيانات العميل
</h3>

<div class="tow-grid">

<div class="tow-field">

<label>
    اسم العميل
</label>

<input
    type="text"
    value="<?= htmlspecialchars(
        $customer_name,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    readonly
>

</div>


<div class="tow-field">

<label>
    رقم الجوال
</label>

<input
    type="text"
    value="<?= htmlspecialchars(
        $customer_phone,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    readonly
>

</div>

</div>

</div>


<!-- =====================================================
     VEHICLE
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    🚗 بيانات السيارة
</h3>

<div class="tow-grid">


<div class="tow-field">

<label>
    ماركة السيارة
</label>

<select
    name="vehicle_make"
    required
>

<option value="">
    اختر الماركة
</option>

<option value="تويوتا">تويوتا</option>
<option value="نيسان">نيسان</option>
<option value="هيونداي">هيونداي</option>
<option value="كيا">كيا</option>
<option value="فورد">فورد</option>
<option value="شيفروليه">شيفروليه</option>
<option value="هوندا">هوندا</option>
<option value="مرسيدس">مرسيدس</option>
<option value="BMW">BMW</option>
<option value="لكزس">لكزس</option>
<option value="جي إم سي">جي إم سي</option>
<option value="ميتسوبيشي">ميتسوبيشي</option>
<option value="مازدا">مازدا</option>
<option value="سوزوكي">سوزوكي</option>
<option value="أخرى">أخرى</option>

</select>

</div>


<div class="tow-field">

<label>
    موديل السيارة
</label>

<input
    type="text"
    name="vehicle_model"
    placeholder="مثال: كامري"
    required
>

</div>


<div class="tow-field">

<label>
    سنة الصنع
</label>

<select
    name="vehicle_year"
    required
>

<option value="">
    اختر السنة
</option>

<?php

$currentYear =
    (int)date('Y');

for (
    $year = $currentYear;
    $year >= 1980;
    $year--
) {

    echo '
    <option value="' .
    $year .
    '">' .
    $year .
    '</option>
    ';
}

?>

</select>

</div>


<div class="tow-field">

<label>
    رقم اللوحة
</label>

<input
    type="text"
    name="plate_number"
    placeholder="مثال: أ ب ج 1234"
    required
>

</div>


<div class="tow-field">

<label>
    نوع الهيكل
</label>

<select
    name="body_type"
    required
>

<option value="">
    اختر نوع الهيكل
</option>

<option value="سيدان">سيدان</option>
<option value="SUV">SUV</option>
<option value="دفع رباعي">دفع رباعي</option>
<option value="بيك أب">بيك أب</option>
<option value="فان">فان</option>
<option value="كوبيه">كوبيه</option>
<option value="هاتشباك">هاتشباك</option>
<option value="شاحنة">شاحنة</option>
<option value="أخرى">أخرى</option>

</select>

</div>


<div class="tow-field">

<label>
    لون السيارة
</label>

<select
    name="vehicle_color"
    required
>

<option value="">
    اختر اللون
</option>

<option value="أبيض">أبيض</option>
<option value="أسود">أسود</option>
<option value="فضي">فضي</option>
<option value="رمادي">رمادي</option>
<option value="أحمر">أحمر</option>
<option value="أزرق">أزرق</option>
<option value="بني">بني</option>
<option value="ذهبي">ذهبي</option>
<option value="أخضر">أخضر</option>
<option value="بيج">بيج</option>
<option value="أخرى">أخرى</option>

</select>

</div>

</div>

</div>


<!-- =====================================================
     TRIP
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    📍 تفاصيل الرحلة
</h3>


<div class="tow-grid">


<div class="tow-field">

<label>
    مدينة التحميل
</label>

<select
    name="from_city"
    id="fromCity"
    required
>

<option value="">
    اختر مدينة التحميل
</option>

<?php foreach ($cities as $city): ?>

<option
    value="<?= htmlspecialchars(
        $city,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<?= htmlspecialchars(
    $city,
    ENT_QUOTES,
    'UTF-8'
) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="tow-field">

<label>
    مدينة التنزيل
</label>

<select
    name="to_city"
    id="toCity"
    required
    disabled
>

<option value="">
    اختر مدينة التحميل أولاً
</option>

</select>

</div>

</div>


<div class="tow-map-help">

📍 اضغط على الخريطة مرة واحدة لتحديد
موقع التحميل، ثم اضغط مرة ثانية لتحديد
موقع التنزيل.

</div>


<div id="towMap"></div>


<input
    type="hidden"
    name="from_lat"
    id="from_lat"
>

<input
    type="hidden"
    name="from_lng"
    id="from_lng"
>

<input
    type="hidden"
    name="to_lat"
    id="to_lat"
>

<input
    type="hidden"
    name="to_lng"
    id="to_lng">

</div>


<!-- =====================================================
     TOW TYPE
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    🚛 نوع السطحة
</h3>

<div class="tow-field">

<label>
    نوع السطحة
</label>

<select
    name="car_type"
    id="carType"
    required
>

<option value="">
    اختر نوع السطحة
</option>

<option value="normal">
    سطحة عادية
</option>

<option value="hydraulic">
    سطحة هيدروليك
</option>

<option value="covered">
    سطحة مغطاة
</option>

</select>

</div>


<div class="tow-price">

<div class="tow-price-title">
    قيمة الخدمة
</div>

<div
    class="tow-price-loading"
    id="priceText"
>

اختر مدينة التحميل والتنزيل ونوع السطحة

</div>

</div>

</div>


<!-- =====================================================
     PAYMENT
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    💳 طريقة الدفع
</h3>

<div class="tow-field">

<label>
    طريقة الدفع
</label>

<select
    name="payment_method"
    required
>

<option value="cash">
    كاش عند الاستلام
</option>

<option value="card">
    بطاقة بنكية
</option>

<option value="bank">
    تحويل بنكي
</option>

</select>

</div>

</div>


<!-- =====================================================
     BOOKING
===================================================== -->

<div class="tow-section">

<h3 class="tow-section-title">
    📅 موعد الخدمة
</h3>


<div class="tow-field">

<label>
    نوع الحجز
</label>

<select
    name="booking_type"
    id="bookingType"
>

<option value="instant">
    🚀 فوري
</option>

<option value="scheduled">
    📅 مجدول
</option>

</select>

</div>


<div
    id="scheduleBox"
    style="display:none;margin-top:16px;"
>

<div class="tow-grid">

<div class="tow-field">

<label>
    التاريخ
</label>

<input
    type="date"
    name="scheduled_date"
    id="scheduledDate"
>

</div>


<div class="tow-field">

<label>
    الوقت
</label>

<input
    type="time"
    name="scheduled_time"
    id="scheduledTime"
>

</div>

</div>

</div>

</div>


<!-- =====================================================
     SUBMIT
===================================================== -->

<button
    type="submit"
    name="orderadd"
    value="1"
    class="tow-submit"
    id="submitBtn"
>

تأكيد طلب السطحة 🚚

</button>


</form>

</div>

</div>

</div>


<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

/* =========================================================
   MAP
========================================================= */

const towMap =
    L.map('towMap').setView(
        [24.7136, 46.6753],
        6
    );


L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        attribution:
            '© OpenStreetMap'
    }
).addTo(towMap);


/* =========================================================
   MARKERS
========================================================= */

let pickupMarker = null;
let deliveryMarker = null;

let pickupPreviewMarker = null;
let deliveryPreviewMarker = null;

let routeLine = null;

let mapStep = 'pickup';


/* =========================================================
   ELEMENTS
========================================================= */

const fromCity =
    document.getElementById(
        'fromCity'
    );

const toCity =
    document.getElementById(
        'toCity'
    );

const carType =
    document.getElementById(
        'carType'
    );

const priceText =
    document.getElementById(
        'priceText'
    );

const fromLatInput =
    document.getElementById(
        'from_lat'
    );

const fromLngInput =
    document.getElementById(
        'from_lng'
    );

const toLatInput =
    document.getElementById(
        'to_lat'
    );

const toLngInput =
    document.getElementById(
        'to_lng'
    );


/* =========================================================
   CITY COORDINATES
========================================================= */

async function getCityCoordinates(
    cityName
) {

    if (!cityName) {
        return null;
    }

    try {

        const response =
            await fetch(
                'https://nominatim.openstreetmap.org/search?' +
                'format=json' +
                '&q=' +
                encodeURIComponent(
                    cityName +
                    ', Saudi Arabia'
                ) +
                '&limit=1' +
                '&countrycodes=sa'
            );

        if (!response.ok) {

            throw new Error(
                'تعذر الاتصال بخدمة الخرائط'
            );
        }

        const data =
            await response.json();

        if (
            !data ||
            data.length === 0
        ) {

            throw new Error(
                'لم يتم العثور على موقع المدينة'
            );
        }

        return {

            lat:
                parseFloat(
                    data[0].lat
                ),

            lng:
                parseFloat(
                    data[0].lon
                )
        };

    } catch (error) {

        console.error(
            'Geocoding Error:',
            error
        );

        return null;
    }
}


/* =========================================================
   PICKUP PREVIEW
========================================================= */

function showPickupCityPreview(
    lat,
    lng,
    cityName
) {

    if (pickupPreviewMarker) {

        towMap.removeLayer(
            pickupPreviewMarker
        );

        pickupPreviewMarker =
            null;
    }

    if (pickupMarker) {
        return;
    }

    pickupPreviewMarker =
        L.marker(
            [lat, lng],
            {
                opacity:0.65
            }
        )
        .addTo(towMap)
        .bindPopup(
            '📍 مركز ' +
            cityName +
            '<br><small>' +
            'اضغط على الخريطة لتحديد موقع التحميل بدقة' +
            '</small>'
        );

    pickupPreviewMarker.openPopup();
}


/* =========================================================
   DELIVERY PREVIEW
========================================================= */

function showDeliveryCityPreview(
    lat,
    lng,
    cityName
) {

    if (deliveryPreviewMarker) {

        towMap.removeLayer(
            deliveryPreviewMarker
        );

        deliveryPreviewMarker =
            null;
    }

    if (deliveryMarker) {
        return;
    }

    deliveryPreviewMarker =
        L.marker(
            [lat, lng],
            {
                opacity:0.65
            }
        )
        .addTo(towMap)
        .bindPopup(
            '📍 مركز ' +
            cityName +
            '<br><small>' +
            'اضغط على الخريطة لتحديد موقع التنزيل بدقة' +
            '</small>'
        );

    deliveryPreviewMarker.openPopup();
}


/* =========================================================
   MOVE PICKUP CITY
========================================================= */

async function moveToPickupCity(
    cityName
) {

    const location =
        await getCityCoordinates(
            cityName
        );

    if (!location) {
        return;
    }

    towMap.setView(
        [
            location.lat,
            location.lng
        ],
        12,
        {
            animate:true
        }
    );

    showPickupCityPreview(
        location.lat,
        location.lng,
        cityName
    );
}


/* =========================================================
   MOVE DELIVERY CITY
========================================================= */

async function moveToDeliveryCity(
    cityName
) {

    const location =
        await getCityCoordinates(
            cityName
        );

    if (!location) {
        return;
    }

    towMap.setView(
        [
            location.lat,
            location.lng
        ],
        12,
        {
            animate:true
        }
    );

    showDeliveryCityPreview(
        location.lat,
        location.lng,
        cityName
    );
}


/* =========================================================
   MAP CLICK
========================================================= */

towMap.on(
    'click',
    function(e) {

        const lat =
            e.latlng.lat;

        const lng =
            e.latlng.lng;


        /* =================================================
           PICKUP
        ================================================= */

        if (
            mapStep ===
            'pickup'
        ) {

            if (pickupPreviewMarker) {

                towMap.removeLayer(
                    pickupPreviewMarker
                );

                pickupPreviewMarker =
                    null;
            }

            if (pickupMarker) {

                towMap.removeLayer(
                    pickupMarker
                );
            }

            pickupMarker =
                L.marker(
                    [lat, lng]
                )
                .addTo(towMap)
                .bindPopup(
                    '📍 موقع التحميل'
                )
                .openPopup();

            fromLatInput.value =
                lat;

            fromLngInput.value =
                lng;

            mapStep =
                'delivery';

            showMapMessage(
                'تم تحديد موقع التحميل ✓<br>' +
                'الآن اختر مدينة التنزيل ثم اضغط على الخريطة لتحديد موقع التنزيل'
            );

            return;
        }


        /* =================================================
           DELIVERY
        ================================================= */

        if (
            mapStep ===
            'delivery'
        ) {

            if (deliveryPreviewMarker) {

                towMap.removeLayer(
                    deliveryPreviewMarker
                );

                deliveryPreviewMarker =
                    null;
            }

            if (deliveryMarker) {

                towMap.removeLayer(
                    deliveryMarker
                );
            }

            deliveryMarker =
                L.marker(
                    [lat, lng]
                )
                .addTo(towMap)
                .bindPopup(
                    '📍 موقع التنزيل'
                )
                .openPopup();

            toLatInput.value =
                lat;

            toLngInput.value =
                lng;

            mapStep =
                'completed';

            drawRouteLine();

            showMapMessage(
                'تم تحديد موقع التحميل والتنزيل ✓'
            );

            return;
        }


        /* =================================================
           COMPLETED
        ================================================= */

        if (
            mapStep ===
            'completed'
        ) {

            resetMapLocations();

            pickupMarker =
                L.marker(
                    [lat, lng]
                )
                .addTo(towMap)
                .bindPopup(
                    '📍 موقع التحميل'
                )
                .openPopup();

            fromLatInput.value =
                lat;

            fromLngInput.value =
                lng;

            mapStep =
                'delivery';

            showMapMessage(
                'تم تغيير موقع التحميل ✓<br>' +
                'الآن حدد موقع التنزيل'
            );
        }

    }
);


/* =========================================================
   ROUTE
========================================================= */

function drawRouteLine() {

    if (
        !pickupMarker ||
        !deliveryMarker
    ) {

        return;
    }

    if (routeLine) {

        towMap.removeLayer(
            routeLine
        );
    }

    const pickup =
        pickupMarker.getLatLng();

    const delivery =
        deliveryMarker.getLatLng();

    routeLine =
        L.polyline(
            [
                [
                    pickup.lat,
                    pickup.lng
                ],
                [
                    delivery.lat,
                    delivery.lng
                ]
            ],
            {
                weight:4,
                dashArray:'8,8'
            }
        )
        .addTo(towMap);

    towMap.fitBounds(
        routeLine.getBounds(),
        {
            padding:[40,40]
        }
    );
}


/* =========================================================
   RESET MAP
========================================================= */

function resetMapLocations() {

    if (pickupMarker) {

        towMap.removeLayer(
            pickupMarker
        );

        pickupMarker =
            null;
    }

    if (deliveryMarker) {

        towMap.removeLayer(
            deliveryMarker
        );

        deliveryMarker =
            null;
    }

    if (pickupPreviewMarker) {

        towMap.removeLayer(
            pickupPreviewMarker
        );

        pickupPreviewMarker =
            null;
    }

    if (deliveryPreviewMarker) {

        towMap.removeLayer(
            deliveryPreviewMarker
        );

        deliveryPreviewMarker =
            null;
    }

    if (routeLine) {

        towMap.removeLayer(
            routeLine
        );

        routeLine =
            null;
    }

    fromLatInput.value =
        '';

    fromLngInput.value =
        '';

    toLatInput.value =
        '';

    toLngInput.value =
        '';

    mapStep =
        'pickup';
}


/* =========================================================
   MAP MESSAGE
========================================================= */

function showMapMessage(
    message
) {

    const help =
        document.querySelector(
            '.tow-map-help'
        );

    if (!help) {
        return;
    }

    help.innerHTML =
        '📍 ' +
        message;
}


/* =========================================================
   FROM CITY CHANGE
========================================================= */

fromCity.addEventListener(
    'change',
    async function() {

        const city =
            this.value.trim();

        resetMapLocations();

        toCity.disabled =
            true;

        toCity.innerHTML =
            '<option value="">جاري تحميل المدن...</option>';

        priceText.innerHTML =
            'اختر مدينة التنزيل ونوع السطحة';


        if (!city) {

            toCity.innerHTML =
                '<option value="">اختر مدينة التحميل أولاً</option>';

            return;
        }


        await moveToPickupCity(
            city
        );


        fetch(
            'tow_order.php?action=get_to_cities&from_city=' +
            encodeURIComponent(city)
        )

        .then(
            response => {

                if (!response.ok) {

                    throw new Error(
                        'HTTP ' +
                        response.status
                    );
                }

                return response.json();
            }
        )

        .then(
            cities => {

                toCity.innerHTML =
                    '<option value="">اختر مدينة التنزيل</option>';

                if (
                    !Array.isArray(
                        cities
                    ) ||
                    cities.length === 0
                ) {

                    toCity.innerHTML =
                        '<option value="">لا توجد وجهات متاحة</option>';

                    return;
                }

                cities.forEach(
                    cityName => {

                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            cityName;

                        option.textContent =
                            cityName;

                        toCity.appendChild(
                            option
                        );
                    }
                );

                toCity.disabled =
                    false;
            }
        )

        .catch(
            error => {

                console.error(
                    error
                );

                toCity.innerHTML =
                    '<option value="">تعذر تحميل المدن</option>';
            }
        );
    }
);


/* =========================================================
   LOAD PRICE
========================================================= */

function loadTowPrice() {

    const from =
        fromCity.value.trim();

    const to =
        toCity.value.trim();

    const car =
        carType.value.trim();


    if (
        !from ||
        !to ||
        !car
    ) {

        priceText.innerHTML =
            'اختر مدينة التحميل والتنزيل ونوع السطحة';

        return;
    }


    priceText.innerHTML =
        '<span class="tow-price-loading">' +
        'جاري حساب السعر...' +
        '</span>';


    fetch(
        'tow_order.php?action=get_price' +
        '&from_city=' +
        encodeURIComponent(from) +
        '&to_city=' +
        encodeURIComponent(to) +
        '&car_type=' +
        encodeURIComponent(car)
    )

    .then(
        response => {

            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }

            return response.json();
        }
    )

    .then(
        data => {

            if (
                data &&
                data.success
            ) {

                priceText.innerHTML =

                    '<div class="tow-price-value">' +

                    Number(
                        data.price
                    ).toLocaleString(
                        'ar-SA',
                        {
                            minimumFractionDigits:2,
                            maximumFractionDigits:2
                        }
                    ) +

                    ' ريال</div>';

            } else {

                priceText.innerHTML =

                    '<div class="tow-price-error">' +
                    'لا يوجد سعر لهذا المسار ونوع السطحة' +
                    '</div>';
            }
        }
    )

    .catch(
        error => {

            console.error(
                'Price error:',
                error
            );

            priceText.innerHTML =

                '<div class="tow-price-error">' +
                'تعذر تحميل السعر' +
                '</div>';
        }
    );
}


/* =========================================================
   TO CITY CHANGE
========================================================= */

toCity.addEventListener(
    'change',
    async function() {

        const city =
            this.value.trim();

        if (!city) {

            loadTowPrice();

            return;
        }


        if (
            !fromLatInput.value ||
            !fromLngInput.value
        ) {

            alert(
                '📍 يرجى تحديد موقع التحميل على الخريطة أولاً'
            );

            this.value =
                '';

            return;
        }


        await moveToDeliveryCity(
            city
        );

        mapStep =
            'delivery';

        showMapMessage(
            'تم الانتقال إلى مدينة التنزيل: ' +
            city +
            '<br>اضغط على الخريطة لتحديد موقع التنزيل بدقة'
        );

        loadTowPrice();
    }
);


/* =========================================================
   CAR TYPE
========================================================= */

carType.addEventListener(
    'change',
    function() {

        loadTowPrice();
    }
);


/* =========================================================
   BOOKING
========================================================= */

const bookingType =
    document.getElementById(
        'bookingType'
    );

const scheduleBox =
    document.getElementById(
        'scheduleBox'
    );

const scheduledDate =
    document.getElementById(
        'scheduledDate'
    );

const scheduledTime =
    document.getElementById(
        'scheduledTime'
    );


bookingType.addEventListener(
    'change',
    function() {

        if (
            this.value ===
            'scheduled'
        ) {

            scheduleBox.style.display =
                'block';

            scheduledDate.required =
                true;

            scheduledTime.required =
                true;

        } else {

            scheduleBox.style.display =
                'none';

            scheduledDate.required =
                false;

            scheduledTime.required =
                false;

            scheduledDate.value =
                '';

            scheduledTime.value =
                '';
        }  
    }
);




/* =========================================================
   LEAFLET SIZE
========================================================= */

setTimeout(
    function() {

        towMap.invalidateSize();

    },
    500
);

</script>