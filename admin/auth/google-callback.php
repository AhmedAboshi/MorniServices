<?php

session_start();

include('../../include/connected.php');
require_once 'google-config.php';
require_once '../mail.php';

/* =========================================================
   التأكد من وجود Google Code
========================================================= */

if (!isset($_GET['code']) || empty($_GET['code'])) {

    header("Location: ../admin.php?google_error=1");
    exit;
}


/* =========================================================
   الحصول على Access Token
========================================================= */

$token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);


/* =========================================================
   التحقق من نجاح Google
========================================================= */

if (
    isset($token['error']) ||
    !isset($token['access_token'])
) {

    header("Location: ../admin.php?google_error=2");
    exit;
}


/* =========================================================
   وضع Access Token
========================================================= */

$google_client->setAccessToken($token['access_token']);


/* =========================================================
   جلب بيانات المستخدم من Google
========================================================= */

try {

    $google_service = new Google\Service\Oauth2($google_client);

    $data = $google_service->userinfo->get();

} catch (Exception $e) {

    error_log("Google Login Error: " . $e->getMessage());

    header("Location: ../admin.php?google_error=3");
    exit;
}


/* =========================================================
   بيانات Google
========================================================= */

$email = trim($data->getEmail());
$name  = trim($data->getName());

$picture = '';

if (method_exists($data, 'getPicture')) {
    $picture = $data->getPicture();
}


/* =========================================================
   التحقق من البريد
========================================================= */

if (empty($email)) {

    header("Location: ../admin.php?google_error=4");
    exit;
}


/* =========================================================
   البحث عن المدير
========================================================= */

$check = $con->prepare("
    SELECT
        id,
        name,
        email
    FROM admin
    WHERE email = ?
    LIMIT 1
");

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();


/* =========================================================
   ❌ الحساب غير موجود
========================================================= */

if (!$result || $result->num_rows === 0) {

    header("Location: ../admin.php?google_error=5");
    exit;
}


/* =========================================================
   بيانات المدير
========================================================= */

$admin = $result->fetch_assoc();

$admin_id    = (int)$admin['id'];
$admin_name  = $admin['name'];
$admin_email = $admin['email'];


/* =========================================================
   حماية الجلسة
========================================================= */

session_regenerate_id(true);


/* =========================================================
   إنشاء جلسة المدير
========================================================= */

$_SESSION['admin_id']    = $admin_id;
$_SESSION['admin_name']  = $admin_name;
$_SESSION['admin_email'] = $admin_email;
$_SESSION['admin_image'] = $picture;

$_SESSION['login_method'] = 'google';

$_SESSION['logged_in'] = true;


/* =========================================================
   📊 حساب الوثائق المنتهية
========================================================= */

$iqama = 0;
$license = 0;
$card = 0;
$fleet = 0;


/* الإقامات */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM drivers
    WHERE iqama_expiry_date IS NOT NULL
    AND iqama_expiry_date <> ''
    AND iqama_expiry_date < CURDATE()
");

$stmt->execute();

$iqama = (int)$stmt->get_result()->fetch_assoc()['total'];


/* الرخص */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM drivers
    WHERE license_expiry_date IS NOT NULL
    AND license_expiry_date <> ''
    AND license_expiry_date < CURDATE()
");

$stmt->execute();

$license = (int)$stmt->get_result()->fetch_assoc()['total'];


/* بطاقات السائق */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM drivers
    WHERE driver_card_expiration_date IS NOT NULL
    AND driver_card_expiration_date <> ''
    AND driver_card_expiration_date < CURDATE()
");

$stmt->execute();

$card = (int)$stmt->get_result()->fetch_assoc()['total'];


/* الفحص الدوري */

$stmt = $con->prepare("
    SELECT COUNT(*) AS total
    FROM fleet
    WHERE inspection_expiry IS NOT NULL
    AND inspection_expiry <> ''
    AND inspection_expiry < CURDATE()
");

$stmt->execute();

$fleet = (int)$stmt->get_result()->fetch_assoc()['total'];


/* =========================================================
   🔔 إشعار داخل النظام
========================================================= */

$notification_title = "تسجيل دخول Google";

$notification_message =
    "تم تسجيل دخول المدير عبر Google | " .
    "إقامات منتهية: $iqama | " .
    "رخص منتهية: $license | " .
    "بطاقات منتهية: $card | " .
    "فحص دوري منتهي: $fleet";


$stmt = $con->prepare("
    INSERT INTO notifications
    (
        title,
        message,
        type
    )
    VALUES
    (
        ?,
        ?,
        'success'
    )
");

$stmt->bind_param(
    "ss",
    $notification_title,
    $notification_message
);

$stmt->execute();


/* =========================================================
   📧 إرسال إشعار بالبريد
========================================================= */

$message = "

<h2>مرحباً {$admin_name}</h2>

<p>
تم تسجيل الدخول إلى لوحة التحكم بنجاح باستخدام حساب Google.
</p>

<hr>

<h3>حالة الوثائق:</h3>

<ul>

<li>
🪪 الإقامات المنتهية:
<strong>{$iqama}</strong>
</li>

<li>
🚗 الرخص المنتهية:
<strong>{$license}</strong>
</li>

<li>
🆔 بطاقات السائق:
<strong>{$card}</strong>
</li>

<li>
🚛 الفحص الدوري المنتهي:
<strong>{$fleet}</strong>
</li>

</ul>

<hr>

<p>
📧 البريد:
{$admin_email}
</p>

<p>
🔐 طريقة الدخول:
Google
</p>

<p>
🕐 التاريخ:
" . date('Y-m-d H:i:s') . "
</p>

";


/* إرسال البريد */

sendMail(
    $admin_email,
    "Google Login Alert - AlSharqPlatform",
    $message
);


/* =========================================================
   🚀 الانتقال إلى لوحة التحكم
========================================================= */

header("Location: ../newadmin.php");

exit;