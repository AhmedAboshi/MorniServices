
<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');
include('mail.php');

require_once '../vendor/autoload.php';

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

$isArabic = ($lang === 'ar');
$dir = $isArabic ? 'rtl' : 'ltr';


/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'title'          => 'تسجيل دخول الأدمن',
        'welcome'        => 'مرحباً بك',
        'subtitle'       => 'قم بتسجيل الدخول للوصول إلى لوحة التحكم',

        'email'          => 'البريد الإلكتروني',
        'password'       => 'كلمة المرور',

        'email_placeholder' =>
            'أدخل البريد الإلكتروني',

        'password_placeholder' =>
            'أدخل كلمة المرور',

        'login' =>
            'تسجيل الدخول',

        'google' =>
            'الدخول باستخدام Google',

        'biometric' =>
            'الدخول بالبصمة',

        'forgot' =>
            'نسيت كلمة المرور؟',

        'remember' =>
            'تذكرني',

        'empty' =>
            'الرجاء تعبئة جميع الحقول',

        'wrong_pass' =>
            'كلمة المرور غير صحيحة',

        'user_not_found' =>
            'المستخدم غير موجود',

        'or' =>
            'أو',

        'secure_login' =>
            'تسجيل دخول آمن',

        'copyright' =>
            'جميع الحقوق محفوظة',

    ],

    'en' => [

        'title'          => 'Admin Login',
        'welcome'        => 'Welcome Back',
        'subtitle'       => 'Sign in to access your control panel',

        'email'          => 'Email Address',
        'password'       => 'Password',

        'email_placeholder' =>
            'Enter your email address',

        'password_placeholder' =>
            'Enter your password',

        'login' =>
            'Sign In',

        'google' =>
            'Continue with Google',

        'biometric' =>
            'Login with Biometrics',

        'forgot' =>
            'Forgot Password?',

        'remember' =>
            'Remember me',

        'empty' =>
            'Please fill in all fields',

        'wrong_pass' =>
            'Incorrect password',

        'user_not_found' =>
            'User not found',

        'or' =>
            'OR',

        'secure_login' =>
            'Secure Login',

        'copyright' =>
            'All rights reserved',

    ]

];


/* =========================================================
   اسم النظام والشركة
========================================================= */

$systemName = setting('system_name');

$companyName = setting('company_name');

$logo = setting('company_logo');


/* =========================================================
   Remember Me
========================================================= */

$saved_email = $_COOKIE['admin_email'] ?? '';


/* =========================================================
   LOGIN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    $password = trim($_POST['password'] ?? '');

    $remember = isset($_POST['remember']);


    /* -----------------------------------------------------
       التحقق من البيانات
    ----------------------------------------------------- */

    if (empty($email) || empty($password)) {

        echo '<script>
            alert(' . json_encode($t[$lang]['empty'], JSON_UNESCAPED_UNICODE) . ');
            history.back();
        </script>';

        exit;
    }


    /* -----------------------------------------------------
       جلب الأدمن
    ----------------------------------------------------- */

    $stmt = $con->prepare("
        SELECT
            id,
            name,
            email,
            password
        FROM admin
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();


    /* -----------------------------------------------------
       المستخدم غير موجود
    ----------------------------------------------------- */

    if (!$result || $result->num_rows === 0) {

        echo '<script>
            alert(' . json_encode($t[$lang]['user_not_found'], JSON_UNESCAPED_UNICODE) . ');
            history.back();
        </script>';

        exit;
    }


    $admin = $result->fetch_assoc();


    /* -----------------------------------------------------
       التحقق من كلمة المرور
       
       ملاحظة:
       النظام الحالي يستخدم كلمة المرور كنص عادي.
       سنقوم بتحويلها إلى password_hash لاحقاً
       بعد التأكد من جميع الحسابات.
    ----------------------------------------------------- */

    if (trim($password) !== trim($admin['password'])) {

        echo '<script>
            alert(' . json_encode($t[$lang]['wrong_pass'], JSON_UNESCAPED_UNICODE) . ');
            history.back();
        </script>';

        exit;
    }


    /* -----------------------------------------------------
       Remember Me
    ----------------------------------------------------- */

    if ($remember) {

        setcookie(
            "admin_email",
            $email,
            [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

    } else {

        setcookie(
            "admin_email",
            "",
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }


    /* -----------------------------------------------------
       Session Security
    ----------------------------------------------------- */

    session_regenerate_id(true);


    /* -----------------------------------------------------
       إنشاء OTP
    ----------------------------------------------------- */

    $otp = random_int(100000, 999999);

    $expire = date(
        'Y-m-d H:i:s',
        strtotime('+5 minutes')
    );


    /* -----------------------------------------------------
       حفظ OTP
    ----------------------------------------------------- */

    $update = $con->prepare("
        UPDATE admin
        SET
            otp_code = ?,
            otp_expire = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssi",
        $otp,
        $expire,
        $admin['id']
    );

    $update->execute();


    /* -----------------------------------------------------
       إرسال OTP
    ----------------------------------------------------- */

    sendOTP(
        $admin['email'],
        $otp
    );


    /* -----------------------------------------------------
       Session OTP
    ----------------------------------------------------- */

    $_SESSION['otp_type'] = 'login';

    $_SESSION['otp_mode'] = 'login';

    $_SESSION['otp_admin_id'] = $admin['id'];

    $_SESSION['otp_admin_name'] = $admin['name'];

    $_SESSION['otp_email'] = $admin['email'];


    /* -----------------------------------------------------
       الانتقال إلى OTP
    ----------------------------------------------------- */

    header("Location: verify-otp.php");

    exit;
}

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $dir ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($t[$lang]['title']) ?>
</title>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<style>

/* =========================================================
   RESET
========================================================= */

*{
    box-sizing:border-box;
}


/* =========================================================
   BODY
========================================================= */

body{

    margin:0;

    min-height:100vh;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #eff6ff 0%,
            #f8fafc 45%,
            #e0e7ff 100%
        );

    color:#1e293b;

}


/* =========================================================
   MAIN
========================================================= */

.login-page{

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:30px 15px;

}


/* =========================================================
   CONTAINER
========================================================= */

.login-container{

    width:100%;

    max-width:1050px;

    display:grid;

    grid-template-columns:1fr 1fr;

    background:#ffffff;

    border-radius:28px;

    overflow:hidden;

    box-shadow:
        0 25px 70px rgba(15,23,42,.15);

}


/* =========================================================
   BRAND SIDE
========================================================= */

.brand-side{

    background:
        linear-gradient(
            145deg,
            #1e40af,
            #2563eb,
            #1d4ed8
        );

    color:white;

    padding:55px 45px;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

    text-align:center;

}


.brand-logo{

    width:130px;

    height:130px;

    object-fit:contain;

    background:#fff;

    padding:10px;

    border-radius:25px;

    margin-bottom:25px;

    box-shadow:
        0 15px 35px rgba(0,0,0,.20);

}


.brand-side h1{

    margin:0 0 10px;

    font-size:30px;

    font-weight:800;

}


.brand-side p{

    margin:0;

    font-size:16px;

    line-height:1.8;

    opacity:.9;

    max-width:380px;

}


.security-badge{

    margin-top:35px;

    display:flex;

    align-items:center;

    gap:10px;

    background:rgba(255,255,255,.12);

    padding:12px 18px;

    border-radius:30px;

    font-size:14px;

}


/* =========================================================
   FORM SIDE
========================================================= */

.form-side{

    padding:45px;

    display:flex;

    flex-direction:column;

    justify-content:center;

}


.form-header{

    text-align:center;

    margin-bottom:30px;

}


.form-header h2{

    margin:0 0 8px;

    font-size:27px;

    color:#0f172a;

}


.form-header p{

    margin:0;

    color:#64748b;

    font-size:14px;

}


/* =========================================================
   FORM GROUP
========================================================= */

.form-group{

    margin-bottom:18px;

}


.form-group label{

    display:block;

    margin-bottom:8px;

    font-size:14px;

    font-weight:700;

    color:#334155;

}


.input-box{

    position:relative;

}


.input-box i{

    position:absolute;

    top:50%;

    transform:translateY(-50%);

    color:#94a3b8;

    font-size:16px;

}


html[dir="rtl"] .input-box i{

    right:15px;

}


html[dir="ltr"] .input-box i{

    left:15px;

}


.input-box input{

    width:100%;

    height:52px;

    border:1px solid #dbe3ef;

    border-radius:13px;

    outline:none;

    background:#f8fafc;

    font-size:15px;

    transition:.2s;

}


html[dir="rtl"] .input-box input{

    padding:
        0 45px 0 15px;

}


html[dir="ltr"] .input-box input{

    padding:
        0 15px 0 45px;

}


.input-box input:focus{

    border-color:#2563eb;

    background:#fff;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.10);

}


/* =========================================================
   PASSWORD TOGGLE
========================================================= */

.password-toggle{

    position:absolute;

    top:50%;

    transform:translateY(-50%);

    cursor:pointer;

    color:#64748b;

    font-size:17px;

}


html[dir="rtl"] .password-toggle{

    left:15px;

}


html[dir="ltr"] .password-toggle{

    right:15px;

}


/* =========================================================
   OPTIONS
========================================================= */

.form-options{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    margin:5px 0 20px;

}


.remember{

    display:flex;

    align-items:center;

    gap:7px;

    font-size:13px;

    color:#475569;

    cursor:pointer;

}


.remember input{

    width:16px;

    height:16px;

    accent-color:#2563eb;

}


.forgot{

    color:#2563eb;

    text-decoration:none;

    font-size:13px;

    font-weight:600;

}


.forgot:hover{

    text-decoration:underline;

}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-btn{

    width:100%;

    height:52px;

    border:0;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color:white;

    font-size:16px;

    font-weight:700;

    cursor:pointer;

    transition:.2s;

    box-shadow:
        0 8px 20px rgba(37,99,235,.20);

}


.login-btn:hover{

    transform:translateY(-1px);

    box-shadow:
        0 12px 25px rgba(37,99,235,.30);

}


/* =========================================================
   DIVIDER
========================================================= */

.divider{

    display:flex;

    align-items:center;

    gap:12px;

    margin:22px 0;

    color:#94a3b8;

    font-size:12px;

}


.divider::before,
.divider::after{

    content:"";

    flex:1;

    height:1px;

    background:#e2e8f0;

}


/* =========================================================
   GOOGLE
========================================================= */

.google-btn{

    width:100%;

    height:50px;

    border:1px solid #dbe3ef;

    border-radius:13px;

    background:#fff;

    color:#334155;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:12px;

    text-decoration:none;

    font-size:14px;

    font-weight:700;

    transition:.2s;

}


.google-btn:hover{

    background:#f8fafc;

    border-color:#cbd5e1;

    transform:translateY(-1px);

}


.google-btn i{

    font-size:18px;

    color:#4285f4;

}


/* =========================================================
   BIOMETRIC
========================================================= */

.biometric-btn{

    width:100%;

    height:50px;

    margin-top:12px;

    border:1px solid #dbe3ef;

    border-radius:13px;

    background:#f8fafc;

    color:#334155;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:10px;

    text-decoration:none;

    font-size:14px;

    font-weight:700;

    transition:.2s;

}


.biometric-btn:hover{

    background:#eef2ff;

    border-color:#c7d2fe;

}


/* =========================================================
   FOOTER
========================================================= */

.login-footer{

    text-align:center;

    margin-top:28px;

    color:#94a3b8;

    font-size:12px;

}


.login-footer strong{

    color:#64748b;

}


/* =========================================================
   LANGUAGE
========================================================= */

.language-switch{

    position:fixed;

    top:20px;

    display:flex;

    gap:5px;

    background:white;

    padding:5px;

    border-radius:10px;

    box-shadow:
        0 5px 20px rgba(15,23,42,.08);

}


html[dir="rtl"] .language-switch{

    left:20px;

}


html[dir="ltr"] .language-switch{

    right:20px;

}


.language-switch a{

    text-decoration:none;

    color:#64748b;

    font-size:12px;

    font-weight:700;

    padding:7px 10px;

    border-radius:7px;

}


.language-switch a.active{

    background:#2563eb;

    color:white;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:800px){

    .login-container{

        grid-template-columns:1fr;

        max-width:500px;

    }


    .brand-side{

        padding:35px 25px;

    }


    .brand-logo{

        width:90px;

        height:90px;

        border-radius:18px;

    }


    .brand-side h1{

        font-size:24px;

    }


    .brand-side p{

        font-size:14px;

    }


    .security-badge{

        margin-top:20px;

    }


    .form-side{

        padding:30px 25px;

    }

}


@media(max-width:450px){

    .login-page{

        padding:15px;

    }


    .form-side{

        padding:25px 18px;

    }


    .form-options{

        align-items:flex-start;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     LANGUAGE
===================================================== -->

<div class="language-switch">

    <a
        href="?lang=ar"
        class="<?= $lang === 'ar' ? 'active' : '' ?>"
    >
        AR
    </a>

    <a
        href="?lang=en"
        class="<?= $lang === 'en' ? 'active' : '' ?>"
    >
        EN
    </a>

</div>


<!-- =====================================================
     PAGE
===================================================== -->

<div class="login-page">


<div class="login-container">


<!-- =====================================================
     BRAND
===================================================== -->

<div class="brand-side">


<?php

$logoPath = '';

if (
    !empty($logo)
    &&
    file_exists("../uploads/logo/" . $logo)
) {

    $logoPath =
        "../uploads/logo/" .
        rawurlencode($logo);

} else {

    $logoPath = "../img/logo.jpg";

}

?>


<img
    src="<?= htmlspecialchars($logoPath) ?>"
    class="brand-logo"
    alt="Logo"
>


<h1>
    <?= htmlspecialchars($systemName) ?>
</h1>


<p>
    <?= htmlspecialchars($companyName) ?>
</p>


<div class="security-badge">

    <i class="fa-solid fa-shield-halved"></i>

    <?= htmlspecialchars($t[$lang]['secure_login']) ?>

</div>


</div>


<!-- =====================================================
     FORM
===================================================== -->

<div class="form-side">


<div class="form-header">

    <h2>
        <?= htmlspecialchars($t[$lang]['welcome']) ?>
    </h2>

    <p>
        <?= htmlspecialchars($t[$lang]['subtitle']) ?>
    </p>

</div>


<form method="POST" autocomplete="on">


<!-- EMAIL -->

<div class="form-group">

    <label for="email">

        <?= htmlspecialchars($t[$lang]['email']) ?>

    </label>


    <div class="input-box">

        <i class="fa-solid fa-envelope"></i>

        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($saved_email) ?>"
            placeholder="<?= htmlspecialchars($t[$lang]['email_placeholder']) ?>"
            autocomplete="username"
            required
        >

    </div>

</div>


<!-- PASSWORD -->

<div class="form-group">

    <label for="password">

        <?= htmlspecialchars($t[$lang]['password']) ?>

    </label>


    <div class="input-box">

        <i class="fa-solid fa-lock"></i>


        <input
            type="password"
            id="password"
            name="password"
            placeholder="<?= htmlspecialchars($t[$lang]['password_placeholder']) ?>"
            autocomplete="current-password"
            required
        >


        <span
            class="password-toggle"
            onclick="togglePassword()"
            title="<?= htmlspecialchars($t[$lang]['password']) ?>"
        >

            <i
                id="passwordIcon"
                class="fa-solid fa-eye"
            ></i>

        </span>

    </div>

</div>


<!-- OPTIONS -->

<div class="form-options">


<label class="remember">

    <input
        type="checkbox"
        name="remember"
        <?= !empty($saved_email) ? 'checked' : '' ?>
    >

    <?= htmlspecialchars($t[$lang]['remember']) ?>

</label>


<a
    href="forgot-password.php"
    class="forgot"
>
    <?= htmlspecialchars($t[$lang]['forgot']) ?>
</a>


</div>


<!-- LOGIN -->

<button
    class="login-btn"
    type="submit"
>

    <i class="fa-solid fa-right-to-bracket"></i>

    <?= htmlspecialchars($t[$lang]['login']) ?>

</button>


</form>


<!-- DIVIDER -->

<div class="divider">

    <?= htmlspecialchars($t[$lang]['or']) ?>

</div>


<!-- GOOGLE -->

<a
    href="auth/google-login.php"
    class="google-btn"
>

    <i class="fa-brands fa-google"></i>

    <?= htmlspecialchars($t[$lang]['google']) ?>

</a>


<!-- BIOMETRIC -->

<a
    href="biometric-login.php"
    class="biometric-btn"
>

    <i class="fa-solid fa-fingerprint"></i>

    <?= htmlspecialchars($t[$lang]['biometric']) ?>

</a>


<!-- FOOTER -->

<div class="login-footer">

    <strong>
        <?= htmlspecialchars($systemName) ?>
    </strong>

    <br>

    <?= date('Y') ?>
    -
    <?= htmlspecialchars($t[$lang]['copyright']) ?>

</div>


</div>


</div>

</div>


<script>

/* =========================================================
   Toggle Password
========================================================= */

function togglePassword(){

    const password =
        document.getElementById('password');

    const icon =
        document.getElementById('passwordIcon');


    if(password.type === 'password'){

        password.type = 'text';

        icon.classList.remove(
            'fa-eye'
        );

        icon.classList.add(
            'fa-eye-slash'
        );

    }else{

        password.type = 'password';

        icon.classList.remove(
            'fa-eye-slash'
        );

        icon.classList.add(
            'fa-eye'
        );

    }

}


/* =========================================================
   Login Loading
========================================================= */

document
    .querySelector('form')
    .addEventListener('submit', function(){

        const button =
            this.querySelector('.login-btn');

        button.disabled = true;

        button.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> ' +
            '<?= $isArabic ? "جاري تسجيل الدخول..." : "Signing in..." ?>';

    });

</script>


</body>

</html>
