<?php
session_start();
include('../include/connected.php');
require_once 'mail.php';
header('Content-Type: text/html; charset=utf-8');

/* =========================================================
   التحقق من جلسة OTP
========================================================= */

$otp_type = $_SESSION['otp_type'] ?? '';

if ($otp_type === 'login') {

    if (!isset($_SESSION['otp_admin_id'], $_SESSION['otp_email'])) {
        header("Location: admin.php");
        exit();
    }

    $email = $_SESSION['otp_email'];

} elseif ($otp_type === 'reset') {

    if (!isset($_SESSION['otp_email'])) {
        header("Location: forgot-password.php");
        exit();
    }

    $email = $_SESSION['otp_email'];

} else {

    header("Location: admin.php");
    exit();
}

$email = $_SESSION['otp_email'] ?? $_SESSION['reset_email'];

if(isset($_POST['verify'])){

    $otp = trim($_POST['otp']);

    $stmt = $con->prepare("SELECT * FROM admin WHERE email=? AND otp_code=? LIMIT 1");
    $stmt->bind_param("ss",$email,$otp);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){

        if(($_SESSION['otp_type'] ?? '') == 'login'){

            $_SESSION['admin_id'] = $_SESSION['otp_admin_id'];
$_SESSION['admin_name'] = $_SESSION['otp_admin_name'];
$admin_id = $_SESSION['otp_admin_id'];

/* تسجيل الدخول في الجدول */
/* تحديث حالة + آخر دخول + عدد مرات الدخول */
$update = $con->prepare("
    UPDATE admin 
    SET 
        status = 'Active',
        last_login = NOW(),
        login_count = login_count + 1
    WHERE id = ?
");

$update->bind_param("i", $admin_id);
$update->execute();



$emailAdmin = $email;

/* =========================
   🧾 تسجيل الدخول في السجل
========================= */
$log = $con->prepare("
    INSERT INTO audit_log (user, action, details)
    VALUES (?, ?, ?)
");

$user = $_SESSION['admin_name'] ?? 'Admin';
$action = "تسجيل دخول";
$details = "تم تسجيل الدخول بنجاح عبر OTP";

$log->bind_param("sss", $user, $action, $details);
$log->execute();



/* =========================
   📊 حساب الوثائق المنتهية
========================= */

/* =====================================================
   🔔 نظام الإشعارات الاحترافي بعد تسجيل الدخول OTP
   - يتم إنشاء إشعار لكل سجل منتهي
   - كل إشعار يحتوي على ref_id صحيح
   ===================================================== */


/* =========================
   🚙 الفحص الدوري للمركبات
========================= */
$expiredFleet = $con->query("
SELECT id FROM fleet
WHERE inspection_expiry < CURDATE()
");

while($row = $expiredFleet->fetch_assoc()){

    $fleet_id = $row['id'];

    $check = $con->prepare("
        SELECT id FROM notifications 
        WHERE type = ? AND ref_id = ?
    ");

    $type = "fleet";
    $check->bind_param("si", $type, $fleet_id);
    $check->execute();

    if($check->get_result()->num_rows == 0){

        $stmt = $con->prepare("
            INSERT INTO notifications(title,message,type,ref_id)
            VALUES (?,?,?,?)
        ");

        $title = "فحص دوري منتهي";
        $message = "يوجد مركبة تحتاج فحص دوري";

        $stmt->bind_param("sssi", $title, $message, $type, $fleet_id);
        $stmt->execute();
    }
}


/* =========================
   📋 كرت التشغيل للمركبات
========================= */
$expiredOperation = $con->query("
SELECT id FROM fleet
WHERE operation_expiry < CURDATE()
");

while($row = $expiredOperation->fetch_assoc()){

    $fleet_id = $row['id'];

    $check = $con->prepare("
        SELECT id FROM notifications 
        WHERE type = ? AND ref_id = ?
    ");

    $type = "operation";
    $check->bind_param("si", $type, $fleet_id);
    $check->execute();

    if($check->get_result()->num_rows == 0){

        $stmt = $con->prepare("
            INSERT INTO notifications(title,message,type,ref_id)
            VALUES (?,?,?,?)
        ");

        $title = "كرت تشغيل منتهي";
        $message = "يوجد مركبة كرت التشغيل منتهي";

        $stmt->bind_param("sssi", $title, $message, $type, $fleet_id);
        $stmt->execute();
    }
}

/* =========================
   🪪 الإقامات للسائقين
========================= */
$expiredIqama = $con->query("
SELECT id FROM drivers
WHERE iqama_expiry_date < CURDATE()
");

while($row = $expiredIqama->fetch_assoc()){

    $driver_id = $row['id'];

    $stmt = $con->prepare("
        INSERT INTO notifications(title,message,type,ref_id)
        VALUES (?,?,?,?)
    ");

    $title = "إقامة منتهية";
    $message = "يوجد سائق إقامته منتهية";
    $type = "iqama";

    $stmt->bind_param("sssi", $title, $message, $type, $driver_id);
    $stmt->execute();
}


/* =========================
   🚗 رخص القيادة
========================= */
$expiredLicense = $con->query("
SELECT id 
FROM drivers
WHERE license_expiry_date IS NOT NULL
AND license_expiry_date < CURDATE()
");

while($row = $expiredLicense->fetch_assoc()){

    $driver_id = $row['id'];

    // 🔥 تحقق إذا الإشعار موجود مسبقًا
    $check = $con->prepare("
        SELECT id FROM notifications 
        WHERE type = ? AND ref_id = ?
    ");

    $type = "license";
    $check->bind_param("si", $type, $driver_id);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        continue; // موجود مسبقًا
    }

    // 🔔 إضافة إشعار جديد
    $stmt = $con->prepare("
        INSERT INTO notifications(title,message,type,ref_id)
        VALUES (?,?,?,?)
    ");

    $title = "رخصة قيادة منتهية";
    $message = "يوجد سائق رخصته منتهية";

    $stmt->bind_param("sssi", $title, $message, $type, $driver_id);
    $stmt->execute();
}

/* =========================
   🆔 بطاقات السائق
========================= */
$expiredCard = $con->query("
SELECT id FROM drivers
WHERE driver_card_expiration_date < CURDATE()
");

while($row = $expiredCard->fetch_assoc()){

    $driver_id = $row['id'];

    $stmt = $con->prepare("
        INSERT INTO notifications(title,message,type,ref_id)
        VALUES (?,?,?,?)
    ");

    $title = "بطاقة سائق منتهية";
    $message = "يوجد سائق بطاقته منتهية";
    $type = "card";

    $stmt->bind_param("sssi", $title, $message, $type, $driver_id);
    $stmt->execute();
}



	
/* =========================
   🔔 إشعار داخل النظام
========================= */
// $con->query("
// INSERT INTO notifications(title,message,type)
// VALUES (
// 'تسجيل دخول OTP',
// 'إقامة:$iqama | رخص:$license | بطاقات:$card | فحص:$fleet | كرت تشغيل:$operation_expiry', 
// 'success'
// )
// ");
/* السائقين */
$driversData = $con->query("
SELECT
SUM(iqama_expiry_date < CURDATE()) AS iqama,
SUM(license_expiry_date < CURDATE()) AS license,
SUM(driver_card_expiration_date < CURDATE()) AS card
FROM drivers
")->fetch_assoc();

/* المركبات */
$fleetData = $con->query("
SELECT
SUM(inspection_expiry < CURDATE()) AS fleet,
SUM(operation_expiry < CURDATE()) AS operation
FROM fleet
")->fetch_assoc();

$iqama = $driversData['iqama'] ?? 0;
$license = $driversData['license'] ?? 0;
$card = $driversData['card'] ?? 0;

$fleet = $fleetData['fleet'] ?? 0;
$operation_expiry = $fleetData['operation'] ?? 0;

/* =========================
   📧 إرسال إيميل
========================= */
$message = "
<h2>مرحباً بك $emailAdmin</h2>

<p>تم تسجيل الدخول بنجاح عبر نظام ادارة اسطول الشرق  نتمني الاهتمام والعناية ومتابعة التالي

</p>

<ul>
    <li>🪪 الإقامات المنتهية: $iqama</li>
    <li>🚗 الرخص المنتهية: $license</li>
    <li>🆔 بطاقات السائق: $card</li>
    <li>🚛 الفحص الدوري: $fleet</li>
    <li>🪪 كرت التشغيل: $operation_expiry</li>
</ul>

<p>التاريخ: " . date('Y-m-d H:i:s') . "</p>
";

sendMail($emailAdmin, "Login Alert", $message);

/* =========================
   تنظيف الجلسة
========================= */
unset($_SESSION['otp_admin_id']);
unset($_SESSION['otp_admin_name']);
unset($_SESSION['otp_email']);
unset($_SESSION['otp_type']);


header("Location: newadmin.php");
exit();

        } elseif ($otp_type === 'reset') {

    $_SESSION['otp_verified'] = true;
    $_SESSION['reset_email'] = $email;

    header("Location: reset-password.php");
    exit();
}

    } else {
        $error = "OTP غير صحيح";
    }
}

if(isset($_POST['resend'])){

    $otp = rand(100000,999999);
    $expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $up = $con->prepare("UPDATE admin SET otp_code=?, otp_expire=? WHERE email=?");
    $up->bind_param("sss",$otp,$expire,$email);
    $up->execute();

    sendOTP($email,$otp);

    $success = "تم إعادة إرسال OTP";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>OTP Verification</title>

<style>

body{
    margin:0;
    font-family:Tahoma;
    background:linear-gradient(135deg,#0f172a,#1e3a8a);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.card{
    width:420px;
    background:#fff;
    padding:35px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
}

.logo{
    width:70px;
    margin-bottom:10px;
}

h2{
    color:#1e40af;
}

p{
    color:#6b7280;
    font-size:14px;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid #ddd;
    text-align:center;
    font-size:18px;
    letter-spacing:5px;
    margin-bottom:10px;
}

input:focus{
    outline:none;
    border-color:#2563eb;
}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-size:15px;
}

.btn-primary{
    background:#2563eb;
    color:#fff;
}

.btn-primary:hover{
    background:#1e40af;
}

.btn-resend{
    background:#16a34a;
    color:#fff;
    margin-top:10px;
}

.btn-resend:disabled{
    background:#94a3b8;
    cursor:not-allowed;
}

.timer{
    margin-top:15px;
    background:#eff6ff;
    padding:10px;
    border-radius:10px;
    color:#1e40af;
    font-weight:bold;
}

.error{color:red;}
.success{color:green;}

</style>
</head>

<body>

<div class="card">

    <img src="../img/logo.jpg" class="logo">

    <h2>رمز التحقق</h2>
    <p>أدخل الرمز المرسل إلى بريدك الإلكتروني</p>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>

    <form method="POST">
        <input type="text" name="otp" maxlength="6" placeholder="------" required>

        <button class="btn-primary" type="submit" name="verify">
            تحقق
        </button>
    </form>

    <form method="POST">
        <button id="resendBtn" class="btn-resend" type="submit" name="resend" disabled>
            إعادة إرسال OTP
        </button>
    </form>

    <div class="timer">
        الوقت المتبقي: <span id="countdown">03:00</span>
    </div>

</div>

<script>

let time = 180;
let btn = document.getElementById("resendBtn");
let timer;

function startTimer(){

    clearInterval(timer);

    time = 180;
    btn.disabled = true;

    timer = setInterval(function(){

        let m = Math.floor(time / 60);
        let s = time % 60;

        if(s < 10) s = "0" + s;

        document.getElementById("countdown").innerHTML = m + ":" + s;

        time--;

        if(time < 0){
            clearInterval(timer);
            document.getElementById("countdown").innerHTML = "يمكنك إعادة الإرسال";
            btn.disabled = false;
        }

    },1000);

}

startTimer();

btn.addEventListener("click", function(){

    setTimeout(() => {
        startTimer();
    }, 500);

});

</script>

</body>
</html>