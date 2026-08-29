<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/../../include/connected.php';

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   GOOGLE
========================================================= */

require_once __DIR__ . '/google-config.php';


/* =========================================================
   التحقق من Google Code
========================================================= */

if (
    !isset($_GET['code']) ||
    empty($_GET['code'])
) {

    header('Location: ../login.php?google_error=1');

    exit;
}


/* =========================================================
   الحصول على Access Token
========================================================= */

try {

    $token = $google_client->fetchAccessTokenWithAuthCode(
        $_GET['code']
    );

} catch (Exception $e) {

    error_log(
        'Google Customer Login Token Error: ' .
        $e->getMessage()
    );

    header('Location: ../login.php?google_error=2');

    exit;
}


/* =========================================================
   التحقق من Token
========================================================= */

if (
    isset($token['error']) ||
    empty($token['access_token'])
) {

    error_log(
        'Google Customer Login Token Error: ' .
        json_encode($token, JSON_UNESCAPED_UNICODE)
    );

    header('Location: ../login.php?google_error=3');

    exit;
}


/* =========================================================
   وضع Access Token
========================================================= */

$google_client->setAccessToken(
    $token['access_token']
);


/* =========================================================
   الحصول على بيانات المستخدم
========================================================= */

try {

    $google_service =
        new Google\Service\Oauth2($google_client);

    $google_user =
        $google_service->userinfo->get();

} catch (Exception $e) {

    error_log(
        'Google Customer Userinfo Error: ' .
        $e->getMessage()
    );

    header('Location: ../login.php?google_error=4');

    exit;
}


/* =========================================================
   بيانات Google
========================================================= */

$googleId =
    trim((string)$google_user->getId());

$email =
    trim((string)$google_user->getEmail());

$name =
    trim((string)$google_user->getName());

$picture = '';

if (
    method_exists($google_user, 'getPicture')
) {

    $picture =
        trim((string)$google_user->getPicture());
}


/* =========================================================
   التحقق من البريد
========================================================= */

if ($email === '') {

    header('Location: ../login.php?google_error=5');

    exit;
}


/* =========================================================
   البحث عن المستخدم
========================================================= */

$stmt = $con->prepare("
    SELECT
        id,
        username,
        phone,
        email,
        password,
        login_type,
        google_id,
        image
    FROM users
    WHERE email = ?
    LIMIT 1
");

$stmt->bind_param(
    's',
    $email
);

$stmt->execute();

$result =
    $stmt->get_result();


/* =========================================================
   المستخدم موجود
========================================================= */

if ($result->num_rows > 0) {

    $user =
        $result->fetch_assoc();

    $userId =
        (int)$user['id'];

    $username =
        trim($user['username']);

    /* -----------------------------------------------------
       تحديث بيانات Google
    ----------------------------------------------------- */

    $update = $con->prepare("
        UPDATE users
        SET
            google_id = ?,
            login_type = 'google',
            image = ?
        WHERE id = ?
        LIMIT 1
    ");

    $update->bind_param(
        'ssi',
        $googleId,
        $picture,
        $userId
    );

    $update->execute();

}


/* =========================================================
   المستخدم غير موجود
========================================================= */

else {

    /*
     * إنشاء حساب جديد تلقائياً
     */

    $username =
        $name !== ''
            ? $name
            : 'Google User';


    /*
     * كلمة مرور عشوائية
     * لأن الدخول سيكون بواسطة Google
     */

    $randomPassword =
        password_hash(
            bin2hex(random_bytes(16)),
            PASSWORD_DEFAULT
        );


    $loginType =
        'google';


    $phone = null;


    $insert = $con->prepare("
        INSERT INTO users
        (
            username,
            phone,
            email,
            password,
            login_type,
            google_id,
            image
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $insert->bind_param(
        'sssssss',
        $username,
        $phone,
        $email,
        $randomPassword,
        $loginType,
        $googleId,
        $picture
    );

    $insert->execute();


    $userId =
        (int)$con->insert_id;

}


/* =========================================================
   حماية الجلسة
========================================================= */

session_regenerate_id(true);


/* =========================================================
   إنشاء جلسة العميل
========================================================= */

$_SESSION['user_id'] =
    $userId;

$_SESSION['username'] =
    $username;

$_SESSION['user_name'] =
    $username;

$_SESSION['name'] =
    $username;

$_SESSION['user_email'] =
    $email;

$_SESSION['email'] =
    $email;

$_SESSION['user_image'] =
    $picture;

$_SESSION['google_id'] =
    $googleId;

$_SESSION['login_method'] =
    'google';

$_SESSION['logged_in'] =
    true;


/* =========================================================
   الانتقال بعد تسجيل الدخول
========================================================= */

header('Location: ../../index.php');
exit;

