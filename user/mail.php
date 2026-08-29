<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendOTP($email, $otp){

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // 🔴 غيرها بإيميلك
       $mail->Username = 'ahmedhider359@gmail.com';
       $mail->Password = 'sosw hmdg ruti popb';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Admin System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your OTP Code";

        $mail->Body = "
        <div style='font-size:18px'>
            <h3>كود التحقق الخاص بك</h3>
            <p><b style='font-size:22px'>$otp</b></p>
            <p>الكود صالح لمدة 3 دقائق فقط.</p>
        </div>";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}

/* =========================================================
   📩 إرسال إشعار الموافقة على الطلب
========================================================= */

function sendOrderApprovedEmail(
    $email,
    $order
)
{
    try {

        if (empty($email)) {
            return false;
        }

        $mail = createMailer();

        $mail->addAddress($email);

        $orderNumber =
            $order['order_number']
            ?? ('#' . $order['id']);

        $customerName =
            htmlspecialchars(
                $order['full_name'] ?? 'عميلنا العزيز'
            );

        $fromCity =
            htmlspecialchars(
                $order['from_city'] ?? '-'
            );

        $toCity =
            htmlspecialchars(
                $order['to_city'] ?? '-'
            );

        $price =
            number_format(
                (float)($order['price'] ?? 0),
                2
            );

        $mail->Subject =
            "تمت الموافقة على طلبك {$orderNumber}";

        $mail->Body = "

        <div style='
            direction:rtl;
            font-family:Arial;
            background:#f5f7fb;
            padding:30px;
        '>

            <div style='
                max-width:650px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
            '>

                <h2 style='color:#198754'>
                    ✅ تمت الموافقة على طلبك
                </h2>

                <p>
                    مرحبًا <strong>{$customerName}</strong>
                </p>

                <p>
                    نود إبلاغك بأن الإدارة وافقت على طلبك.
                </p>

                <hr>

                <p>
                    <strong>رقم الطلب:</strong>
                    {$orderNumber}
                </p>

                <p>
                    <strong>من:</strong>
                    {$fromCity}
                </p>

                <p>
                    <strong>إلى:</strong>
                    {$toCity}
                </p>

                <p>
                    <strong>قيمة الطلب:</strong>
                    {$price} ر.س
                </p>

                <div style='
                    margin-top:25px;
                    padding:15px;
                    background:#e8f5e9;
                    border-radius:10px;
                '>
                    سيتم استكمال معالجة طلبك وتعيين مزود الخدمة.
                </div>

                <p style='margin-top:25px;color:#777'>
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>

        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'Order Approved Email Error: ' .
            $e->getMessage()
        );

        return false;
    }
}


/* =========================================================
   🚚 إرسال إشعار تعيين المزود / السائق
========================================================= */

function sendDriverAssignedEmail(
    $email,
    $order,
    $driverName
)
{
    try {

        if (empty($email)) {
            return false;
        }

        $mail = createMailer();

        $mail->addAddress($email);

        $orderNumber =
            $order['order_number']
            ?? ('#' . $order['id']);

        $customerName =
            htmlspecialchars(
                $order['full_name'] ?? 'عميلنا العزيز'
            );

        $fromCity =
            htmlspecialchars(
                $order['from_city'] ?? '-'
            );

        $toCity =
            htmlspecialchars(
                $order['to_city'] ?? '-'
            );

        $driverName =
            htmlspecialchars(
                $driverName
            );

        $bookingType =
            $order['booking_type'] ?? 'instant';

        if ($bookingType === 'scheduled') {

            $scheduledDate =
                htmlspecialchars(
                    $order['scheduled_date'] ?? '-'
                );

            $scheduledTime =
                htmlspecialchars(
                    $order['scheduled_time'] ?? '-'
                );

            $scheduleHtml = "

                <p>
                    <strong>تاريخ الطلب:</strong>
                    {$scheduledDate}
                </p>

                <p>
                    <strong>وقت الطلب:</strong>
                    {$scheduledTime}
                </p>

            ";

        } else {

            $scheduleHtml = "

                <p>
                    <strong>نوع الطلب:</strong>
                    🚀 فوري
                </p>

            ";
        }

        $mail->Subject =
            "🚚 تم تعيين مزود الخدمة لطلبك {$orderNumber}";

        $mail->Body = "

        <div style='
            direction:rtl;
            font-family:Arial;
            background:#f5f7fb;
            padding:30px;
        '>

            <div style='
                max-width:650px;
                margin:auto;
                background:#ffffff;
                padding:30px;
                border-radius:18px;
            '>

                <h2 style='color:#0d6efd'>
                    🚚 تم تعيين مزود الخدمة
                </h2>

                <p>
                    مرحبًا <strong>{$customerName}</strong>
                </p>

                <p>
                    تم تعيين مزود الخدمة لطلبك بنجاح.
                </p>

                <div style='
                    background:#eef6ff;
                    padding:20px;
                    border-radius:12px;
                    margin:20px 0;
                '>

                    <p>
                        <strong>رقم الطلب:</strong>
                        {$orderNumber}
                    </p>

                    <p>
                        <strong>المزود / السائق:</strong>
                        {$driverName}
                    </p>

                    <p>
                        <strong>من:</strong>
                        {$fromCity}
                    </p>

                    <p>
                        <strong>إلى:</strong>
                        {$toCity}
                    </p>

                    {$scheduleHtml}

                </div>

                <p>
                    يمكنك متابعة حالة طلبك من خلال حسابك في المنصة.
                </p>

                <p style='color:#777'>
                    شكرًا لاستخدامك منصة الشرق الذكية.
                </p>

            </div>

        </div>

        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'Driver Assigned Email Error: ' .
            $e->getMessage()
        );

        return false;
    }
}
?>