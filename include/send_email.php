
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/* =========================================================
   إنشاء Mailer
========================================================= */

function createMailer()
{
    $mail = new PHPMailer(true);

    // SMTP
    $mail->isSMTP();

    // Gmail
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // حساب Gmail الذي سيرسل الرسائل
    $mail->Username = 'ahmedhider359@gmail.com';

    /*
     * مهم جداً:
     * ضع هنا App Password الخاص بحساب Gmail
     *
     * ليس كلمة مرور Gmail العادية.
     *
     * مثال:
     * 1234567890123456
     */
    $mail->Password = 'sosw hmdg ruti popb';

    // STARTTLS
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // الترميز
    $mail->CharSet = 'UTF-8';

    // المرسل
    $mail->setFrom(
        'ahmedhider359@gmail.com',
        'منصة الشرق الذكية'
    );

    // HTML
    $mail->isHTML(true);

    // منع مشاكل الاتصال
    $mail->Timeout = 30;

    return $mail;
}


/* =========================================================
   إرسال إيميل الموافقة
========================================================= */

function sendOrderApprovedEmail(
    $email,
    $order_id,
    $full_name = '',
    $driver_name = ''
) {

    if (empty($email)) {
        error_log(
            'Order approved email: customer email is empty'
        );

        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $email,
            $full_name
        );

        $mail->Subject =
            'تمت الموافقة على طلبك #' .
            $order_id;

        $driverText = '';

        if (!empty($driver_name)) {

            $driverText = '
                <p>
                    <strong>
                        مزود الخدمة / السائق:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $driver_name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                '
                </p>
            ';
        }

        $safeName = htmlspecialchars(
            $full_name,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeOrderId = (int)$order_id;

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:600px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:15px;
                border:1px solid #eee;
            ">

                <h2 style="color:#198754;">
                    ✅ تمت الموافقة على طلبك
                </h2>

                <p>
                    مرحبًا
                    <strong>' .
                    $safeName .
                    '</strong>
                </p>

                <p>
                    نود إبلاغك بأن الإدارة قامت
                    بالموافقة على طلبك بنجاح.
                </p>

                <hr>

                <p>
                    <strong>رقم الطلب:</strong>
                    #' .
                    $safeOrderId .
                '</p>

                ' .
                $driverText .
                '

                <p>
                    يمكنك متابعة حالة الطلب من
                    خلال حسابك في منصة الشرق الذكية.
                </p>

                <br>

                <p style="color:#777;">
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';

        $mail->AltBody =
            "تمت الموافقة على طلبك رقم #" .
            $safeOrderId;

        $mail->send();

        error_log(
            'Order approved email sent successfully to: ' .
            $email
        );

        return true;

    } catch (Exception $e) {

        error_log(
            'Order approved email error: ' .
            $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   إرسال إيميل عند تعيين السائق
========================================================= */

function sendOrderAssignedEmail(
    $email,
    $order_id,
    $full_name = '',
    $driver_name = ''
) {

    if (empty($email)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $email,
            $full_name
        );

        $mail->Subject =
            'تم تعيين مزود خدمة لطلبك #' .
            $order_id;

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:600px;
                margin:auto;
                background:#fff;
                padding:30px;
                border-radius:15px;
            ">

                <h2 style="color:#0d6efd;">
                    🚚 تم تعيين مزود الخدمة
                </h2>

                <p>
                    مرحبًا
                    <strong>' .
                    htmlspecialchars(
                        $full_name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                    '</strong>
                </p>

                <p>
                    تم تعيين مزود خدمة لطلبك.
                </p>

                <hr>

                <p>
                    <strong>رقم الطلب:</strong>
                    #' .
                    (int)$order_id .
                '</p>

                <p>
                    <strong>
                        مزود الخدمة / السائق:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $driver_name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                '</p>

                <p>
                    حالة الطلب الآن:
                    <strong>تم التعيين</strong>
                </p>

                <br>

                <p style="color:#777;">
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';

        $mail->AltBody =
            'تم تعيين مزود الخدمة لطلبك رقم #' .
            (int)$order_id;

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'Order assigned email error: ' .
            $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   OTP
========================================================= */

function sendOTP($email, $otp)
{
    if (empty($email)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress($email);

        $mail->Subject =
            'كود التحقق - منصة الشرق الذكية';

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial;
        ">

            <h3>
                كود التحقق الخاص بك
            </h3>

            <p>
                <b style="font-size:22px;">
                    ' .
                    htmlspecialchars(
                        $otp,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                '
                </b>
            </p>

            <p>
                الكود صالح لمدة 3 دقائق فقط.
            </p>

        </div>
        ';

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'OTP email error: ' .
            $e->getMessage()
        );

        return false;
    }
}

/* =========================================================
   إيميل طلب سطحة جديد للإدارة
========================================================= */

function sendNewTowOrderAdminEmail(
    $adminEmail,
    $order_id,
    $full_name,
    $phone,
    $from_city,
    $to_city,
    $car_type,
    $price,
    $payment_method,
    $booking_type,
    $scheduled_date = null,
    $scheduled_time = null
) {

    if (empty($adminEmail)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $adminEmail,
            'إدارة منصة الشرق'
        );

        $mail->Subject =
            '🚚 طلب سطحة جديد #' . (int)$order_id;

        /* ================================================
           نوع السطحة
        ================================================ */

        switch ($car_type) {

            case 'normal':
                $carName = 'سطحة عادية';
                break;

            case 'hydraulic':
                $carName = 'سطحة هيدروليك';
                break;

            case 'covered':
                $carName = 'سطحة مغطاة';
                break;

            default:
                $carName = $car_type;
        }


        /* ================================================
           طريقة الدفع
        ================================================ */

        switch ($payment_method) {

            case 'cash':
                $paymentName = 'كاش عند الاستلام';
                break;

            case 'card':
                $paymentName = 'بطاقة بنكية';
                break;

            case 'bank':
                $paymentName = 'تحويل بنكي';
                break;

            default:
                $paymentName = $payment_method;
        }


        /* ================================================
           نوع الحجز
        ================================================ */

        if ($booking_type === 'scheduled') {

            $bookingName =
                '📅 مجدول';

            $bookingDate =
                !empty($scheduled_date)
                ? htmlspecialchars(
                    $scheduled_date,
                    ENT_QUOTES,
                    'UTF-8'
                )
                : '';

            $bookingTime =
                !empty($scheduled_time)
                ? htmlspecialchars(
                    $scheduled_time,
                    ENT_QUOTES,
                    'UTF-8'
                )
                : '';

        } else {

            $bookingName =
                '🚀 فوري';

            $bookingDate = '';
            $bookingTime = '';
        }


        /* ================================================
           حماية البيانات
        ================================================ */

        $safeName =
            htmlspecialchars(
                $full_name,
                ENT_QUOTES,
                'UTF-8'
            );

        $safePhone =
            htmlspecialchars(
                $phone,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeFrom =
            htmlspecialchars(
                $from_city,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeTo =
            htmlspecialchars(
                $to_city,
                ENT_QUOTES,
                'UTF-8'
            );


        /* ================================================
           جسم الرسالة
        ================================================ */

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:650px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#0d6efd;
                    margin-top:0;
                ">
                    🚚 طلب سطحة جديد
                </h2>

                <p>
                    تم إنشاء طلب سطحة جديد
                    ويحتاج إلى مراجعة الإدارة.
                </p>

                <div style="
                    background:#eef6ff;
                    padding:15px;
                    border-radius:12px;
                    margin:20px 0;
                ">

                    <strong>
                        رقم الطلب:
                    </strong>

                    <span style="
                        font-size:20px;
                        color:#0d6efd;
                    ">
                        #' . (int)$order_id . '
                    </span>

                </div>


                <h3>
                    👤 بيانات العميل
                </h3>

                <p>
                    <strong>الاسم:</strong>
                    ' . $safeName . '
                </p>

                <p>
                    <strong>الجوال:</strong>
                    ' . $safePhone . '
                </p>


                <hr>


                <h3>
                    📍 تفاصيل الرحلة
                </h3>

                <p>
                    <strong>مدينة التحميل:</strong>
                    ' . $safeFrom . '
                </p>

                <p>
                    <strong>مدينة التنزيل:</strong>
                    ' . $safeTo . '
                </p>


                <hr>


                <h3>
                    🚚 تفاصيل الخدمة
                </h3>

                <p>
                    <strong>نوع السطحة:</strong>
                    ' . htmlspecialchars(
                        $carName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) . '
                </p>

                <p>
                    <strong>نوع الحجز:</strong>
                    ' . $bookingName . '
                </p>';

        if ($booking_type === 'scheduled') {

            $mail->Body .= '

                <p>
                    <strong>تاريخ الحجز:</strong>
                    ' . $bookingDate . '
                </p>

                <p>
                    <strong>وقت الحجز:</strong>
                    ' . $bookingTime . '
                </p>';

        }


        $mail->Body .= '

                <p>
                    <strong>طريقة الدفع:</strong>
                    ' .
                    htmlspecialchars(
                        $paymentName,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '
                </p>


                <div style="
                    background:#f0fff4;
                    border:1px solid #c6f6d5;
                    padding:20px;
                    border-radius:14px;
                    text-align:center;
                    margin:25px 0;
                ">

                    <div style="
                        color:#555;
                        margin-bottom:8px;
                    ">
                        قيمة الخدمة
                    </div>

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:#198754;
                    ">
                        ' .
                        number_format(
                            (float)$price,
                            2
                        )
                        . '
                        ريال
                    </div>

                </div>


                <div style="
                    background:#fff3cd;
                    color:#856404;
                    padding:15px;
                    border-radius:12px;
                ">

                    ⏳ حالة الطلب:
                    <strong>
                        بانتظار موافقة الإدارة
                    </strong>

                </div>


                <br>

                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    هذه رسالة آلية من منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';


        $mail->AltBody =
            'طلب سطحة جديد رقم #'
            . (int)$order_id
            . "\n"
            . 'العميل: '
            . $full_name
            . "\n"
            . 'من: '
            . $from_city
            . "\n"
            . 'إلى: '
            . $to_city
            . "\n"
            . 'السعر: '
            . number_format(
                (float)$price,
                2
            )
            . ' ريال';


        $mail->send();

        return true;


    } catch (Exception $e) {

        error_log(
            'New tow admin email error: '
            . $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   إيميل استلام طلب السطحة للعميل
========================================================= */

function sendNewTowOrderCustomerEmail(
    $email,
    $order_id,
    $full_name,
    $from_city,
    $to_city,
    $car_type,
    $price,
    $payment_method,
    $booking_type,
    $scheduled_date = null,
    $scheduled_time = null
) {

    if (empty($email)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $email,
            $full_name
        );

        $mail->Subject =
            '🚚 تم استلام طلب السطحة #' .
            (int)$order_id;


        /* ================================================
           نوع السطحة
        ================================================ */

        switch ($car_type) {

            case 'normal':
                $carName = 'سطحة عادية';
                break;

            case 'hydraulic':
                $carName = 'سطحة هيدروليك';
                break;

            case 'covered':
                $carName = 'سطحة مغطاة';
                break;

            default:
                $carName = $car_type;
        }


        /* ================================================
           طريقة الدفع
        ================================================ */

        switch ($payment_method) {

            case 'cash':
                $paymentName = 'كاش عند الاستلام';
                break;

            case 'card':
                $paymentName = 'بطاقة بنكية';
                break;

            case 'bank':
                $paymentName = 'تحويل بنكي';
                break;

            default:
                $paymentName = $payment_method;
        }


        /* ================================================
           نوع الحجز
        ================================================ */

        if ($booking_type === 'scheduled') {

            $bookingName =
                '📅 حجز مجدول';

        } else {

            $bookingName =
                '🚀 طلب فوري';
        }


        $safeName =
            htmlspecialchars(
                $full_name,
                ENT_QUOTES,
                'UTF-8'
            );


        $safeFrom =
            htmlspecialchars(
                $from_city,
                ENT_QUOTES,
                'UTF-8'
            );


        $safeTo =
            htmlspecialchars(
                $to_city,
                ENT_QUOTES,
                'UTF-8'
            );


        /* ================================================
           الرسالة
        ================================================ */

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:600px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#0d6efd;
                    margin-top:0;
                ">
                    🚚 تم استلام طلبك
                </h2>

                <p>
                    مرحبًا
                    <strong>' .
                    $safeName .
                    '</strong>
                </p>

                <p>
                    تم استلام طلب السطحة الخاص بك
                    بنجاح، وهو الآن بانتظار مراجعة
                    وموافقة الإدارة.
                </p>


                <div style="
                    background:#eef6ff;
                    padding:18px;
                    border-radius:13px;
                    text-align:center;
                    margin:20px 0;
                ">

                    <div>
                        رقم الطلب
                    </div>

                    <strong style="
                        font-size:25px;
                        color:#0d6efd;
                    ">
                        #' .
                        (int)$order_id .
                    '</strong>

                </div>


                <h3>
                    📍 تفاصيل الرحلة
                </h3>

                <p>
                    <strong>
                        من:
                    </strong>
                    ' .
                    $safeFrom .
                    '
                </p>

                <p>
                    <strong>
                        إلى:
                    </strong>
                    ' .
                    $safeTo .
                    '
                </p>


                <hr>


                <h3>
                    🚚 تفاصيل الخدمة
                </h3>

                <p>
                    <strong>
                        نوع السطحة:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $carName,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '
                </p>

                <p>
                    <strong>
                        نوع الحجز:
                    </strong>
                    ' .
                    $bookingName .
                    '
                </p>';

        if ($booking_type === 'scheduled') {

            $mail->Body .= '

                <p>
                    <strong>
                        تاريخ الحجز:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $scheduled_date ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '
                </p>

                <p>
                    <strong>
                        وقت الحجز:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $scheduled_time ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '
                </p>';

        }


        $mail->Body .= '

                <p>
                    <strong>
                        طريقة الدفع:
                    </strong>
                    ' .
                    htmlspecialchars(
                        $paymentName,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '
                </p>


                <div style="
                    background:#f0fff4;
                    border:1px solid #c6f6d5;
                    padding:20px;
                    border-radius:14px;
                    text-align:center;
                    margin:25px 0;
                ">

                    <div style="
                        color:#555;
                    ">
                        قيمة الخدمة
                    </div>

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:#198754;
                        margin-top:7px;
                    ">
                        ' .
                        number_format(
                            (float)$price,
                            2
                        )
                        . '
                        ريال
                    </div>

                </div>


                <div style="
                    background:#fff3cd;
                    color:#856404;
                    padding:15px;
                    border-radius:12px;
                ">

                    ⏳ حالة الطلب:
                    <strong>
                        بانتظار موافقة الإدارة
                    </strong>

                </div>


                <br>

                <p>
                    يمكنك متابعة حالة طلبك
                    من خلال حسابك في منصة
                    الشرق الذكية.
                </p>


                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';


        $mail->AltBody =
            'تم استلام طلب السطحة رقم #'
            . (int)$order_id
            . "\n"
            . 'من: '
            . $from_city
            . "\n"
            . 'إلى: '
            . $to_city
            . "\n"
            . 'السعر: '
            . number_format(
                (float)$price,
                2
            )
            . ' ريال';


        $mail->send();

        return true;


    } catch (Exception $e) {

        error_log(
            'New tow customer email error: '
            . $e->getMessage()
        );

        return false;
    }
}

/* =========================================================
   إيميل تذكرة جديدة للإدارة
========================================================= */

function sendNewTicketAdminEmail(
    $adminEmail,
    $ticketId,
    $name,
    $email,
    $phone,
    $subject,
    $message
) {

    if (empty($adminEmail)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $adminEmail,
            'إدارة منصة الشرق'
        );

        $mail->Subject =
            '🎫 تذكرة جديدة #' . (int)$ticketId;

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeEmail = htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePhone = htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeSubject = htmlspecialchars(
            $subject,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeMessage = nl2br(
            htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
        );

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:650px;
                margin:auto;
                background:#fff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#0b1f3a;
                    margin-top:0;
                ">
                    🎫 تذكرة دعم جديدة
                </h2>

                <p>
                    تم استلام تذكرة جديدة من أحد العملاء.
                </p>

                <div style="
                    background:#eef6ff;
                    padding:18px;
                    border-radius:13px;
                    margin:20px 0;
                ">

                    <strong>
                        رقم التذكرة:
                    </strong>

                    <span style="
                        font-size:22px;
                        color:#0d6efd;
                        font-weight:bold;
                    ">
                        #' . (int)$ticketId . '
                    </span>

                </div>

                <h3>👤 بيانات العميل</h3>

                <p>
                    <strong>الاسم:</strong>
                    ' . $safeName . '
                </p>

                <p>
                    <strong>البريد:</strong>
                    ' . $safeEmail . '
                </p>

                <p>
                    <strong>الجوال:</strong>
                    ' . $safePhone . '
                </p>

                <hr>

                <h3>📝 تفاصيل التذكرة</h3>

                <p>
                    <strong>الموضوع:</strong>
                    ' . $safeSubject . '
                </p>

                <div style="
                    background:#f8fafc;
                    border:1px solid #e5e7eb;
                    padding:18px;
                    border-radius:12px;
                    line-height:2;
                ">
                    ' . $safeMessage . '
                </div>

                <br>

                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    هذه رسالة آلية من منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';

        $mail->AltBody =
            'تذكرة دعم جديدة رقم #' .
            (int)$ticketId .
            "\n" .
            'الاسم: ' . $name .
            "\n" .
            'البريد: ' . $email .
            "\n" .
            'الجوال: ' . $phone .
            "\n" .
            'الموضوع: ' . $subject .
            "\n\n" .
            $message;

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'New ticket admin email error: ' .
            $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   إيميل استلام التذكرة للعميل
========================================================= */

function sendNewTicketCustomerEmail(
    $email,
    $ticketId,
    $name,
    $subject,
    $message
) {

    if (empty($email)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $email,
            $name
        );

        $mail->Subject =
            '🎫 تم استلام تذكرتك #' .
            (int)$ticketId;

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeSubject = htmlspecialchars(
            $subject,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeMessage = nl2br(
            htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
        );

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:600px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#198754;
                    margin-top:0;
                ">
                    ✅ تم استلام تذكرتك
                </h2>

                <p>
                    مرحبًا
                    <strong>' . $safeName . '</strong>
                </p>

                <p>
                    تم استلام رسالتك بنجاح،
                    وسيقوم فريق الدعم بمراجعتها
                    والرد عليك في أقرب وقت.
                </p>

                <div style="
                    background:#eef6ff;
                    padding:18px;
                    border-radius:13px;
                    text-align:center;
                    margin:20px 0;
                ">

                    <div>
                        رقم التذكرة
                    </div>

                    <strong style="
                        font-size:25px;
                        color:#0d6efd;
                    ">
                        #' . (int)$ticketId . '
                    </strong>

                </div>

                <p>
                    <strong>
                        الموضوع:
                    </strong>
                    ' . $safeSubject . '
                </p>

                <div style="
                    background:#f8fafc;
                    border:1px solid #e5e7eb;
                    padding:18px;
                    border-radius:12px;
                    line-height:2;
                ">
                    ' . $safeMessage . '
                </div>

                <br>

                <p>
                    يمكنك الاحتفاظ برقم التذكرة
                    لمتابعة طلب الدعم.
                </p>

                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    شكرًا لتواصلك مع منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';

        $mail->AltBody =
            'تم استلام تذكرتك رقم #' .
            (int)$ticketId .
            "\n" .
            'الموضوع: ' .
            $subject .
            "\n\n" .
            $message;

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'New ticket customer email error: ' .
            $e->getMessage()
        );

        return false;
    }
}

/* =========================================================
   ✉️ إيميل إنشاء طلب منتجات للعميل
========================================================= */

function sendNewProductOrderCustomerEmail(
    $email,
    $order_id,
    $full_name,
    $phone,
    $city,
    $address,
    $total,
    $payment_method
) {

    if (empty($email)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $email,
            $full_name
        );

        $mail->Subject =
            '🛒 تم استلام طلبك #' . (int)$order_id;


        /* =========================
           طريقة الدفع
        ========================= */

        switch ($payment_method) {

            case 'cash':
                $paymentName = 'كاش عند الاستلام';
                break;

            case 'card':
                $paymentName = 'بطاقة بنكية';
                break;

            case 'bank':
                $paymentName = 'تحويل بنكي';
                break;

            default:
                $paymentName = $payment_method;
        }


        /* =========================
           حماية البيانات
        ========================= */

        $safeName = htmlspecialchars(
            $full_name,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePhone = htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeCity = htmlspecialchars(
            $city,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeAddress = htmlspecialchars(
            $address,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePayment = htmlspecialchars(
            $paymentName,
            ENT_QUOTES,
            'UTF-8'
        );


        /* =========================
           جسم الإيميل
        ========================= */

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:650px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#198754;
                    margin-top:0;
                ">
                    ✅ تم استلام طلبك
                </h2>

                <p>
                    مرحبًا
                    <strong>' . $safeName . '</strong>
                </p>

                <p>
                    تم استلام طلبك بنجاح،
                    وهو الآن بانتظار مراجعة الإدارة.
                </p>


                <div style="
                    background:#eef6ff;
                    padding:18px;
                    border-radius:13px;
                    text-align:center;
                    margin:20px 0;
                ">

                    <div>
                        رقم الطلب
                    </div>

                    <strong style="
                        font-size:28px;
                        color:#0d6efd;
                    ">
                        #' . (int)$order_id . '
                    </strong>

                </div>


                <h3>
                    👤 بيانات العميل
                </h3>

                <p>
                    <strong>الاسم:</strong>
                    ' . $safeName . '
                </p>

                <p>
                    <strong>الجوال:</strong>
                    ' . $safePhone . '
                </p>


                <hr>


                <h3>
                    📍 بيانات التوصيل
                </h3>

                <p>
                    <strong>المدينة:</strong>
                    ' . $safeCity . '
                </p>

                <p>
                    <strong>العنوان:</strong>
                    ' . $safeAddress . '
                </p>


                <hr>


                <h3>
                    💳 طريقة الدفع
                </h3>

                <p>
                    ' . $safePayment . '
                </p>


                <div style="
                    background:#f0fff4;
                    border:1px solid #c6f6d5;
                    padding:20px;
                    border-radius:14px;
                    text-align:center;
                    margin:25px 0;
                ">

                    <div style="
                        color:#555;
                        margin-bottom:8px;
                    ">
                        إجمالي الطلب
                    </div>

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:#198754;
                    ">
                        ' .
                        number_format(
                            (float)$total,
                            2
                        )
                        . '
                        ريال
                    </div>

                </div>


                <div style="
                    background:#fff3cd;
                    color:#856404;
                    padding:15px;
                    border-radius:12px;
                ">

                    ⏳ حالة الطلب:
                    <strong>
                        بانتظار مراجعة الإدارة
                    </strong>

                </div>


                <br>

                <p>
                    يمكنك متابعة حالة طلبك
                    من خلال حسابك في منصة الشرق الذكية.
                </p>

                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';


        $mail->AltBody =
            'تم استلام طلبك رقم #' .
            (int)$order_id .
            "\n" .
            'الاسم: ' .
            $full_name .
            "\n" .
            'الجوال: ' .
            $phone .
            "\n" .
            'المدينة: ' .
            $city .
            "\n" .
            'الإجمالي: ' .
            number_format(
                (float)$total,
                2
            ) .
            ' ريال';


        $mail->send();

        error_log(
            'New product order customer email sent: ' .
            $email
        );

        return true;


    } catch (Exception $e) {

        error_log(
            'New product order customer email error: ' .
            $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   ✉️ إيميل إنشاء طلب منتجات للإدارة
========================================================= */

function sendNewProductOrderAdminEmail(
    $adminEmail,
    $order_id,
    $full_name,
    $email,
    $phone,
    $city,
    $address,
    $total,
    $payment_method
) {

    if (empty($adminEmail)) {
        return false;
    }

    try {

        $mail = createMailer();

        $mail->addAddress(
            $adminEmail,
            'إدارة منصة الشرق'
        );

        $mail->Subject =
            '🛒 طلب منتجات جديد #' .
            (int)$order_id;


        /* =========================
           طريقة الدفع
        ========================= */

        switch ($payment_method) {

            case 'cash':
                $paymentName = 'كاش عند الاستلام';
                break;

            case 'card':
                $paymentName = 'بطاقة بنكية';
                break;

            case 'bank':
                $paymentName = 'تحويل بنكي';
                break;

            default:
                $paymentName = $payment_method;
        }


        /* =========================
           حماية البيانات
        ========================= */

        $safeName = htmlspecialchars(
            $full_name,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeEmail = htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePhone = htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeCity = htmlspecialchars(
            $city,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeAddress = htmlspecialchars(
            $address,
            ENT_QUOTES,
            'UTF-8'
        );

        $safePayment = htmlspecialchars(
            $paymentName,
            ENT_QUOTES,
            'UTF-8'
        );


        /* =========================
           جسم الرسالة
        ========================= */

        $mail->Body = '

        <div style="
            direction:rtl;
            text-align:right;
            font-family:Arial,Tahoma,sans-serif;
            background:#f5f7fb;
            padding:30px;
        ">

            <div style="
                max-width:700px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
                border:1px solid #e5e7eb;
            ">

                <h2 style="
                    color:#0d6efd;
                    margin-top:0;
                ">
                    🛒 طلب منتجات جديد
                </h2>

                <p>
                    تم إنشاء طلب جديد من أحد العملاء
                    ويحتاج إلى مراجعة الإدارة.
                </p>


                <div style="
                    background:#eef6ff;
                    padding:18px;
                    border-radius:13px;
                    margin:20px 0;
                    text-align:center;
                ">

                    <div>
                        رقم الطلب
                    </div>

                    <strong style="
                        font-size:28px;
                        color:#0d6efd;
                    ">
                        #' . (int)$order_id . '
                    </strong>

                </div>


                <h3>
                    👤 بيانات العميل
                </h3>

                <p>
                    <strong>الاسم:</strong>
                    ' . $safeName . '
                </p>

                <p>
                    <strong>البريد الإلكتروني:</strong>
                    ' . $safeEmail . '
                </p>

                <p>
                    <strong>الجوال:</strong>
                    ' . $safePhone . '
                </p>


                <hr>


                <h3>
                    📍 بيانات التوصيل
                </h3>

                <p>
                    <strong>المدينة:</strong>
                    ' . $safeCity . '
                </p>

                <p>
                    <strong>العنوان:</strong>
                    ' . $safeAddress . '
                </p>


                <hr>


                <h3>
                    💳 طريقة الدفع
                </h3>

                <p>
                    ' . $safePayment . '
                </p>


                <div style="
                    background:#f0fff4;
                    border:1px solid #c6f6d5;
                    padding:20px;
                    border-radius:14px;
                    text-align:center;
                    margin:25px 0;
                ">

                    <div style="
                        color:#555;
                        margin-bottom:8px;
                    ">
                        إجمالي الطلب
                    </div>

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:#198754;
                    ">
                        ' .
                        number_format(
                            (float)$total,
                            2
                        )
                        . '
                        ريال
                    </div>

                </div>


                <div style="
                    background:#fff3cd;
                    color:#856404;
                    padding:15px;
                    border-radius:12px;
                ">

                    ⏳ حالة الطلب:
                    <strong>
                        بانتظار موافقة الإدارة
                    </strong>

                </div>


                <br>

                <p style="
                    color:#777;
                    font-size:13px;
                ">
                    هذه رسالة آلية من منصة الشرق الذكية.
                </p>

            </div>

        </div>
        ';


        $mail->AltBody =
            'طلب منتجات جديد رقم #' .
            (int)$order_id .
            "\n" .
            'العميل: ' .
            $full_name .
            "\n" .
            'البريد: ' .
            $email .
            "\n" .
            'الجوال: ' .
            $phone .
            "\n" .
            'المدينة: ' .
            $city .
            "\n" .
            'الإجمالي: ' .
            number_format(
                (float)$total,
                2
            ) .
            ' ريال';


        $mail->send();

        error_log(
            'New product order admin email sent: ' .
            $adminEmail
        );

        return true;


    } catch (Exception $e) {

        error_log(
            'New product order admin email error: ' .
            $e->getMessage()
        );

        return false;
    }
}