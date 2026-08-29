<?php

/* =========================================================
   CONTACT PAGE - AL SHARQ PLATFORM
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

/* =========================================================
   ERROR REPORTING - DEVELOPMENT
========================================================= */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

/* =========================================================
   SETTINGS
========================================================= */

require_once __DIR__ . '/include/settings.php';

/* =========================================================
   EMAIL
========================================================= */

$emailFile = __DIR__ . '/include/send_email.php';

if (file_exists($emailFile)) {
    require_once $emailFile;
}

/* =========================================================
   NOTIFICATIONS
========================================================= */

$notificationsFile = __DIR__ . '/include/notifications.php';

if (file_exists($notificationsFile)) {
    require_once $notificationsFile;
}

/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? setting('default_language', 'ar'));

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

$_SESSION['lang'] = $lang;

$isArabic = ($lang === 'ar');

/* =========================================================
   COMPANY SETTINGS
========================================================= */

$systemName = setting(
    'system_name',
    'Al-Sharq Smart Platform for Services and Fleet Management'
);

$companyName = setting(
    'company_name',
    'منصة الشرق الذكية للخدمات وإدارة الأسطول'
);

$companyLogo = setting('company_logo', '');

$companyPhone = setting(
    'company_phone',
    '0544954837'
);

$companyEmail = setting(
    'company_email',
    'ahmed@alsharqksa.com'
);

$companyAddress = setting(
    'company_address',
    'الرياض - النرجس'
);

$companyWebsite = setting(
    'company_website',
    'المملكة العربية السعودية'
);

$companyCity = setting(
    'company_city',
    'الرياض'
);

$companyCountry = setting(
    'company_country',
    'Saudi Arabia'
);

/* =========================================================
   WHATSAPP
========================================================= */

$whatsappNumber = '966550186105';

/* =========================================================
   USER
========================================================= */

$userId = (int)($_SESSION['user_id'] ?? 0);

/* =========================================================
   بيانات العميل المسجل دخول
========================================================= */

$userName  = '';
$userEmail = '';
$userPhone = '';

if ($userId > 0) {

    $userStmt = $con->prepare("
        SELECT username, email, phone
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($userStmt) {

        $userStmt->bind_param("i", $userId);
        $userStmt->execute();

        $userResult = $userStmt->get_result();

        if ($userResult && $userResult->num_rows > 0) {

            $loggedUser = $userResult->fetch_assoc();

            $userName  = trim($loggedUser['username'] ?? '');
            $userEmail = trim($loggedUser['email'] ?? '');
            $userPhone = trim((string)($loggedUser['phone'] ?? ''));
        }

        $userStmt->close();
    }
}

/* =========================================================
   MESSAGES
========================================================= */

$error = '';
$success = '';

/* =========================================================
   DEBUG
========================================================= */

$debugMessages = [];

/* =========================================================
   TICKET FORM
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $debugMessages[] = 'POST received';

    /* =====================================================
       READ FORM
    ===================================================== */

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $debugMessages[] = 'Name: ' . $name;
    $debugMessages[] = 'Email: ' . $email;
    $debugMessages[] = 'Subject: ' . $subject;

    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $name === '' ||
        $email === '' ||
        $subject === '' ||
        $message === ''
    ) {

        $error = $isArabic
            ? 'يرجى تعبئة جميع الحقول المطلوبة.'
            : 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = $isArabic
            ? 'يرجى إدخال بريد إلكتروني صحيح.'
            : 'Please enter a valid email address.';

    } else {

        /* =================================================
           DATABASE
        ================================================= */

        try {

            $sql = "
                INSERT INTO tickets
                (
                    name,
                    email,
                    phone,
                    subject,
                    message
                )
                VALUES
                (?, ?, ?, ?, ?)
            ";

            $stmt = $con->prepare($sql);

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $phone,
                $subject,
                $message
            );

            $stmt->execute();

            $ticketId = (int)$con->insert_id;

            $stmt->close();

            $debugMessages[] =
                'Ticket inserted successfully. ID = ' . $ticketId;

        } catch (Throwable $e) {

            error_log(
                'CONTACT DATABASE ERROR: ' .
                $e->getMessage()
            );

            $error = $isArabic
                ? 'تعذر حفظ التذكرة في قاعدة البيانات.'
                : 'Unable to save the ticket to the database.';

            $debugMessages[] =
                'DATABASE ERROR: ' . $e->getMessage();
        }

        /* =================================================
           CONTINUE ONLY IF TICKET WAS CREATED
        ================================================= */

        if (!empty($ticketId)) {

            /* =================================================
               SUCCESS IMMEDIATELY
            ================================================= */

            $success = $isArabic
                ? "تم إرسال التذكرة رقم #{$ticketId} بنجاح، وسيتم التواصل معك قريبًا."
                : "Ticket #{$ticketId} has been sent successfully. We will contact you soon.";

            /*
             * نحفظ النجاح في Session أيضًا
             * في حالة إعادة تحميل الصفحة
             */

            $_SESSION['contact_success'] = $success;

            /* =================================================
               ADMIN EMAIL
            ================================================= */

            try {

                $adminEmail = setting(
                    'company_email',
                    'ahmed@alsharqksa.com'
                );

                if (
                    function_exists('sendNewTicketAdminEmail')
                    && !empty($adminEmail)
                ) {

                    $emailResult = sendNewTicketAdminEmail(
                        $adminEmail,
                        $ticketId,
                        $name,
                        $email,
                        $phone,
                        $subject,
                        $message
                    );

                    $debugMessages[] =
                        'Admin email function executed. Result: ' .
                        var_export($emailResult, true);

                } else {

                    $debugMessages[] =
                        'sendNewTicketAdminEmail() does not exist.';
                }

            } catch (Throwable $e) {

                error_log(
                    'CONTACT ADMIN EMAIL ERROR: ' .
                    $e->getMessage()
                );

                $debugMessages[] =
                    'ADMIN EMAIL ERROR: ' .
                    $e->getMessage();
            }

            /* =================================================
               CUSTOMER EMAIL
            ================================================= */

            try {

                if (
                    function_exists('sendNewTicketCustomerEmail')
                    && !empty($email)
                ) {

                    $emailResult = sendNewTicketCustomerEmail(
                        $email,
                        $ticketId,
                        $name,
                        $subject,
                        $message
                    );

                    $debugMessages[] =
                        'Customer email function executed. Result: ' .
                        var_export($emailResult, true);

                } else {

                    $debugMessages[] =
                        'sendNewTicketCustomerEmail() does not exist.';
                }

            } catch (Throwable $e) {

                error_log(
                    'CONTACT CUSTOMER EMAIL ERROR: ' .
                    $e->getMessage()
                );

                $debugMessages[] =
                    'CUSTOMER EMAIL ERROR: ' .
                    $e->getMessage();
            }

            /* =================================================
               ADMIN NOTIFICATION
            ================================================= */

            try {

                if (function_exists('addNotification')) {

                    $adminId = 1;

                    $notificationTitle = $isArabic
                        ? '🎫 تذكرة جديدة'
                        : '🎫 New Ticket';

                    $notificationMessage = $isArabic
                        ? "تم إنشاء تذكرة جديدة من العميل: {$name}"
                        : "A new ticket was created by customer: {$name}";

                    $notificationResult = addNotification(
                        $con,
                        $adminId,
                        $notificationTitle,
                        $notificationMessage,
                        'ticket',
                        $ticketId
                    );

                    $debugMessages[] =
                        'Admin notification executed. Result: ' .
                        var_export($notificationResult, true);

                } else {

                    $debugMessages[] =
                        'addNotification() does not exist.';
                }

            } catch (Throwable $e) {

                error_log(
                    'CONTACT ADMIN NOTIFICATION ERROR: ' .
                    $e->getMessage()
                );

                $debugMessages[] =
                    'ADMIN NOTIFICATION ERROR: ' .
                    $e->getMessage();
            }

            /* =================================================
               CUSTOMER NOTIFICATION
            ================================================= */

            try {

                if (
                    $userId > 0 &&
                    function_exists('addNotification')
                ) {

                    $customerNotificationTitle = $isArabic
                        ? '🎫 تم استلام تذكرتك'
                        : '🎫 Your Ticket Has Been Received';

                    $customerNotificationMessage = $isArabic
                        ? "تم استلام تذكرتك رقم #{$ticketId} بنجاح."
                        : "Your ticket #{$ticketId} has been received successfully.";

                    $notificationResult = addNotification(
                        $con,
                        $userId,
                        $customerNotificationTitle,
                        $customerNotificationMessage,
                        'ticket',
                        $ticketId
                    );

                    $debugMessages[] =
                        'Customer notification executed. Result: ' .
                        var_export($notificationResult, true);
                }

            } catch (Throwable $e) {

                error_log(
                    'CONTACT CUSTOMER NOTIFICATION ERROR: ' .
                    $e->getMessage()
                );

                $debugMessages[] =
                    'CUSTOMER NOTIFICATION ERROR: ' .
                    $e->getMessage();
            }

            /* =================================================
               CLEAR FORM VALUES
            ================================================= */

            $_POST = [];

            /*
             * لا نستخدم redirect الآن أثناء التشخيص.
             * نريد أن نرى النتيجة مباشرة.
             */
        }
    }
}

/* =========================================================
   SUCCESS FROM SESSION
========================================================= */

if ($success === '') {

    $success = $_SESSION['contact_success'] ?? '';

    if ($success !== '') {
        unset($_SESSION['contact_success']);
    }
}

/* =========================================================
   LOGO
========================================================= */

$logoPath = '';

if (!empty($companyLogo)) {

    $logoPath = 'uploads/logo/' . $companyLogo;

} elseif (file_exists(__DIR__ . '/img/logo.jpg')) {

    $logoPath = 'img/logo.jpg';
}

/* =========================================================
   PHONE CLEAN
========================================================= */

$phoneClean = preg_replace(
    '/[^0-9+]/',
    '',
    $companyPhone
);

/* =========================================================
   EMAIL
========================================================= */

$emailSafe = htmlspecialchars(
    $companyEmail,
    ENT_QUOTES,
    'UTF-8'
);

/* =========================================================
   LOGO
========================================================= */

$logoPath = '';

if (!empty($companyLogo)) {

    $logoPath = 'uploads/logo/' . $companyLogo;

} elseif (file_exists(__DIR__ . '/img/logo.jpg')) {

    $logoPath = 'img/logo.jpg';
}


/* =========================================================
   PHONE CLEAN
========================================================= */

$phoneClean = preg_replace('/[^0-9+]/', '', $companyPhone);


/* =========================================================
   EMAIL
========================================================= */

$emailSafe = htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8');


/* =========================================================
   PAGE TEXT
========================================================= */

if ($isArabic) {

    $pageTitle = 'تواصل معنا | ' . $companyName;

    $heroBadge = 'نحن هنا لخدمتك';

    $heroTitle = 'تواصل معنا';

    $heroText =
        'يسعدنا تواصلك معنا. أرسل استفسارك أو طلبك وسيتولى فريقنا متابعة طلبك والرد عليك في أقرب وقت.';

    $emailTitle = 'البريد الإلكتروني';

    $phoneTitle = 'اتصل بنا';

    $locationTitle = 'موقعنا';

    $whatsappTitle = 'واتساب';

    $supportTitle = 'الدعم والتواصل';

    $formTitle = 'أرسل لنا رسالة';

    $formDescription =
        'قم بتعبئة النموذج وسنقوم بمتابعة طلبك والرد عليك.';

    $nameLabel = 'الاسم';

    $namePlaceholder = 'أدخل اسمك';

    $emailLabel = 'البريد الإلكتروني';

    $phoneLabel = 'رقم الجوال';

    $phonePlaceholder = '05xxxxxxxx';

    $subjectLabel = 'الموضوع';

    $subjectPlaceholder = 'موضوع الرسالة';

    $messageLabel = 'الرسالة';

    $messagePlaceholder =
        'اكتب رسالتك أو استفسارك هنا...';

    $sendText = 'إرسال التذكرة';

    $sendingText = 'جاري الإرسال...';

    $contactOptions = 'طرق التواصل';

    $customerService = 'خدمة العملاء';

    $emailText = 'البريد الإلكتروني';

    $whatsappText = 'واتساب';

    $whatsappBoxTitle = 'تواصل معنا عبر واتساب';

    $whatsappBoxText =
        'للاستفسارات السريعة يمكنك التواصل معنا مباشرة عبر واتساب.';

    $openWhatsapp = 'فتح واتساب';

    $mapTitle = 'موقعنا';

    $confidential =
        'سيتم التعامل مع بياناتك بسرية تامة.';

} else {

    $pageTitle = 'Contact Us | ' . $systemName;

    $heroBadge = 'We are here to help';

    $heroTitle = 'Contact Us';

    $heroText =
        'We are happy to hear from you. Send us your inquiry or request and our team will get back to you as soon as possible.';

    $emailTitle = 'Email';

    $phoneTitle = 'Call Us';

    $locationTitle = 'Location';

    $whatsappTitle = 'WhatsApp';

    $supportTitle = 'Support & Contact';

    $formTitle = 'Send us a message';

    $formDescription =
        'Fill in the form and we will follow up on your request.';

    $nameLabel = 'Name';

    $namePlaceholder = 'Enter your name';

    $emailLabel = 'Email';

    $phoneLabel = 'Phone';

    $phonePlaceholder = 'Phone number';

    $subjectLabel = 'Subject';

    $subjectPlaceholder = 'Message subject';

    $messageLabel = 'Message';

    $messagePlaceholder =
        'Write your message or inquiry here...';

    $sendText = 'Send Ticket';

    $sendingText = 'Sending...';

    $contactOptions = 'Contact Options';

    $customerService = 'Customer Service';

    $emailText = 'Email';

    $whatsappText = 'WhatsApp';

    $whatsappBoxTitle = 'Chat with us on WhatsApp';

    $whatsappBoxText =
        'For quick inquiries, contact us directly through WhatsApp.';

    $openWhatsapp = 'Open WhatsApp';

    $mapTitle = 'Our Location';

    $confidential =
        'Your information will be treated confidentially.';
}

?>
<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $isArabic ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="description"
    content="<?= htmlspecialchars($heroText) ?>"
>

<title>
    <?= htmlspecialchars($pageTitle) ?>
</title>


<!-- FAVICON -->

<?php

$favicon = setting('company_favicon', '');

if (!empty($favicon)):

?>

<link
    rel="icon"
    href="uploads/logo/<?= htmlspecialchars($favicon) ?>"
>

<?php endif; ?>


<!-- FONT -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
>


<style>

/* =========================================================
   CONTACT PAGE ONLY
   NO DEPENDENCY ON style.css
========================================================= */

:root{
    --contact-primary:#0b1f3a;
    --contact-secondary:#174777;
    --contact-light:#edf4fb;
    --contact-bg:#f4f7fb;
    --contact-border:#e4e9f0;
    --contact-text:#172033;
    --contact-muted:#667085;
    --contact-white:#ffffff;
}

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body.contact-page{
    margin:0;
    padding:0;
    min-height:100vh;
    font-family:"Cairo",Arial,sans-serif;
    background:var(--contact-bg);
    color:var(--contact-text);
}

/* =========================================================
   TOP NAVIGATION LINKS
========================================================= */

.contact-main-nav{
    width:100%;
    min-height:46px;

    background:#ffffff;

    border-bottom:1px solid #e8edf3;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:6px 20px;

    box-shadow:0 2px 10px rgba(20,40,70,.04);

    position:relative;
    z-index:50;
}

.contact-main-nav-inner{
    width:min(1180px,100%);

    display:flex;
    align-items:center;
    justify-content:center;

    gap:8px;
}

.contact-main-nav a{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    gap:7px;

    min-height:34px;

    padding:6px 18px;

    border-radius:9px;

    color:#344054;

    text-decoration:none;

    font-size:12px;

    font-weight:700;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
}

.contact-main-nav a i{
    font-size:12px;
}

.contact-main-nav a:hover{
    background:#edf4fb;

    color:var(--contact-primary);

    transform:translateY(-1px);
}

.contact-main-nav a.active{
    background:var(--contact-primary);

    color:#fff;

    box-shadow:
        0 4px 12px rgba(11,31,58,.15);
}


/* =========================================================
   NAV MOBILE
========================================================= */

@media(max-width:600px){

    .contact-main-nav{
        min-height:42px;

        padding:5px 8px;
    }

    .contact-main-nav-inner{
        gap:4px;
    }

    .contact-main-nav a{
        flex:1;

        min-height:32px;

        padding:5px 6px;

        font-size:10px;

        gap:4px;

        white-space:nowrap;
    }

    .contact-main-nav a i{
        font-size:10px;
    }

}

/* =========================================================
   TOP BAR
========================================================= */

.contact-topbar{
    width:100%;
    min-height:72px;

    background:
        linear-gradient(
            135deg,
            #071a33,
            #0b1f3a 55%,
            #20527e
        );

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:12px 5%;

    color:#fff;
}

.contact-brand{
    display:flex;
    align-items:center;
    gap:12px;

    min-width:0;
}

.contact-brand img{
    width:48px;
    height:48px;

    border-radius:12px;

    object-fit:cover;

    background:#fff;

    padding:3px;
}

.contact-brand-text{
    min-width:0;
}

.contact-brand-text strong{
    display:block;

    font-size:15px;
    font-weight:800;

    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.contact-brand-text span{
    display:block;

    margin-top:2px;

    font-size:11px;

    color:rgba(255,255,255,.70);
}

.contact-language{
    display:flex;
    align-items:center;
    gap:6px;
}

.contact-language a{
    display:inline-flex;
    align-items:center;

    padding:7px 13px;

    border-radius:20px;

    color:#fff;
    text-decoration:none;

    font-size:12px;

    background:rgba(255,255,255,.08);

    border:1px solid rgba(255,255,255,.12);

    transition:.25s;
}

.contact-language a:hover,
.contact-language a.active{
    background:#fff;
    color:var(--contact-primary);
}


/* =========================================================
   HERO
========================================================= */

.contact-hero{
    position:relative;

    overflow:hidden;

    background:
        linear-gradient(
            135deg,
            #071a33 0%,
            #0b1f3a 55%,
            #20527e 100%
        );

    color:#fff;

    text-align:center;

    padding:65px 20px 115px;
}

.contact-hero::before{
    content:"";

    position:absolute;

    width:430px;
    height:430px;

    border-radius:50%;

    background:rgba(255,255,255,.035);

    top:-240px;
    left:-150px;
}

.contact-hero::after{
    content:"";

    position:absolute;

    width:400px;
    height:400px;

    border-radius:50%;

    background:rgba(255,255,255,.035);

    right:-160px;
    bottom:-250px;
}

.contact-hero-content{
    position:relative;
    z-index:2;

    max-width:850px;

    margin:auto;
}

.contact-hero-badge{
    display:inline-flex;

    align-items:center;
    gap:8px;

    padding:7px 18px;

    border-radius:30px;

    background:rgba(255,255,255,.10);

    border:1px solid rgba(255,255,255,.15);

    font-size:13px;
}

.contact-hero h1{
    margin:16px 0 8px;

    font-size:46px;

    line-height:1.3;

    font-weight:800;
}

.contact-hero p{
    max-width:720px;

    margin:0 auto;

    color:rgba(255,255,255,.82);

    font-size:16px;

    line-height:2;
}


/* =========================================================
   MAIN
========================================================= */

.contact-main{
    position:relative;
    z-index:10;

    width:min(1180px, calc(100% - 32px));

    margin:-65px auto 60px;
}


/* =========================================================
   INFO CARDS
========================================================= */

.contact-info-grid{
    display:grid;

    grid-template-columns:
        repeat(4, minmax(0,1fr));

    gap:16px;

    margin-bottom:22px;
}

.contact-info-card{
    min-width:0;

    background:#fff;

    border:1px solid var(--contact-border);

    border-radius:18px;

    padding:22px 14px;

    text-align:center;

    box-shadow:
        0 10px 35px rgba(20,40,70,.08);

    transition:.25s;
}

.contact-info-card:hover{
    transform:translateY(-4px);

    box-shadow:
        0 15px 40px rgba(20,40,70,.13);
}

.contact-info-icon{
    width:54px;
    height:54px;

    margin:0 auto 12px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:15px;

    background:var(--contact-light);

    color:var(--contact-primary);

    font-size:21px;
}

.contact-info-card h3{
    margin:0 0 6px;

    font-size:15px;

    color:var(--contact-primary);
}

.contact-info-card p{
    margin:0;

    font-size:12px;

    color:var(--contact-muted);

    line-height:1.8;

    overflow-wrap:anywhere;
}

.contact-info-card a{
    color:var(--contact-muted);

    text-decoration:none;
}

.contact-info-card a:hover{
    color:var(--contact-secondary);
}


/* =========================================================
   CONTENT GRID
========================================================= */

.contact-content-grid{
    display:grid;

    grid-template-columns:
        minmax(0, 1.55fr)
        minmax(300px, .75fr);

    gap:22px;

    align-items:start;
}


/* =========================================================
   FORM CARD
========================================================= */

.contact-form-card{
    min-width:0;

    background:#fff;

    border:1px solid var(--contact-border);

    border-radius:18px;

    padding:30px;

    box-shadow:
        0 8px 30px rgba(20,40,70,.06);
}

.contact-form-heading{
    margin-bottom:24px;
}

.contact-form-heading small{
    display:block;

    color:#50749c;

    font-size:12px;

    font-weight:700;
}

.contact-form-heading h2{
    margin:4px 0 5px;

    color:var(--contact-primary);

    font-size:26px;

    font-weight:800;
}

.contact-form-heading p{
    margin:0;

    color:#7a8494;

    font-size:13px;
}


/* =========================================================
   ALERT
========================================================= */

.contact-alert{
    padding:13px 15px;

    border-radius:10px;

    margin-bottom:20px;

    font-size:13px;

    font-weight:600;

    line-height:1.8;
}

.contact-alert-success{
    background:#ecfdf3;

    color:#087443;

    border:1px solid #b7ebcc;
}

.contact-alert-error{
    background:#fff1f2;

    color:#b42318;

    border:1px solid #fecdd3;
}


/* =========================================================
   FORM
========================================================= */

.contact-form-row{
    display:grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap:16px;
}

.contact-form-group{
    margin-bottom:17px;
}

.contact-form-group label{
    display:block;

    margin-bottom:7px;

    color:#344054;

    font-size:13px;

    font-weight:700;
}

.contact-required{
    color:#e53935;
}

.contact-input-wrap{
    position:relative;
}

.contact-input-wrap i{
    position:absolute;

    top:50%;

    transform:translateY(-50%);

    color:#98a2b3;

    pointer-events:none;
}

html[dir="rtl"] .contact-input-wrap i{
    right:14px;
}

html[dir="ltr"] .contact-input-wrap i{
    left:14px;
}

.contact-input,
.contact-textarea{
    display:block;

    width:100%;

    border:1px solid #d9e0e8;

    background:#fff;

    color:#172033;

    border-radius:10px;

    outline:none;

    font-family:"Cairo",Arial,sans-serif;

    font-size:13px;

    transition:.2s;
}

.contact-input{
    height:49px;

    padding:0 42px;
}

.contact-textarea{
    min-height:145px;

    resize:vertical;

    padding:13px;
}

.contact-input:focus,
.contact-textarea:focus{
    border-color:#50749c;

    box-shadow:
        0 0 0 3px rgba(80,116,156,.10);
}

.contact-input::placeholder,
.contact-textarea::placeholder{
    color:#a0a8b5;
}


/* =========================================================
   SEND BUTTON
========================================================= */

.contact-send-btn{
    width:100%;

    height:51px;

    border:0;

    border-radius:10px;

    background:
        linear-gradient(
            135deg,
            #0b1f3a,
            #174777
        );

    color:#fff;

    font-family:"Cairo",Arial,sans-serif;

    font-size:14px;

    font-weight:700;

    cursor:pointer;

    display:flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    transition:.25s;
}

.contact-send-btn:hover{
    transform:translateY(-2px);

    box-shadow:
        0 8px 22px rgba(11,31,58,.20);
}

.contact-send-btn:disabled{
    opacity:.65;

    cursor:not-allowed;

    transform:none;
}

.contact-form-note{
    margin-top:11px;

    color:#98a2b3;

    font-size:11px;

    text-align:center;
}


/* =========================================================
   SIDE
========================================================= */

.contact-side{
    min-width:0;

    display:flex;

    flex-direction:column;

    gap:18px;
}

.contact-side-card{
    background:#fff;

    border:1px solid var(--contact-border);

    border-radius:18px;

    padding:23px;

    box-shadow:
        0 8px 30px rgba(20,40,70,.06);
}

.contact-side-card h3{
    margin:0 0 17px;

    color:var(--contact-primary);

    font-size:18px;
}

.contact-quick{
    display:flex;

    flex-direction:column;

    gap:9px;
}

.contact-quick-link{
    width:100%;

    min-width:0;

    display:flex;

    align-items:center;

    gap:11px;

    padding:11px;

    border-radius:10px;

    background:#f7f9fc;

    color:#344054;

    text-decoration:none;

    transition:.2s;
}

.contact-quick-link:hover{
    background:#edf3f9;

    transform:translateY(-2px);
}

.contact-quick-icon{
    flex:none;

    width:40px;
    height:40px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:10px;

    background:#eaf1f8;

    color:var(--contact-primary);
}

.contact-quick-text{
    min-width:0;
}

.contact-quick-text strong{
    display:block;

    font-size:12px;

    margin-bottom:2px;
}

.contact-quick-text span{
    display:block;

    font-size:11px;

    color:#7b8494;

    overflow-wrap:anywhere;
}


/* =========================================================
   WHATSAPP
========================================================= */

.contact-whatsapp{
    background:
        linear-gradient(
            135deg,
            #0b1f3a,
            #174777
        );

    color:#fff;

    border-radius:18px;

    padding:23px;
}

.contact-whatsapp h3{
    margin:0 0 7px;

    color:#fff;

    font-size:17px;
}

.contact-whatsapp p{
    margin:0 0 17px;

    color:rgba(255,255,255,.78);

    font-size:12px;

    line-height:1.8;
}

.contact-whatsapp-btn{
    width:100%;

    min-height:45px;

    display:flex;

    align-items:center;
    justify-content:center;

    gap:8px;

    border-radius:10px;

    background:#fff;

    color:var(--contact-primary);

    text-decoration:none;

    font-size:13px;

    font-weight:800;

    transition:.2s;
}

.contact-whatsapp-btn:hover{
    transform:translateY(-2px);
}


/* =========================================================
   MAP
========================================================= */

.contact-map-card{
    margin-top:22px;

    background:#fff;

    border:1px solid var(--contact-border);

    border-radius:18px;

    overflow:hidden;

    box-shadow:
        0 8px 30px rgba(20,40,70,.06);
}

.contact-map-header{
    min-height:62px;

    padding:14px 20px;

    display:flex;

    align-items:center;
    justify-content:space-between;

    gap:15px;

    border-bottom:1px solid #edf0f4;
}

.contact-map-header h3{
    margin:0;

    color:var(--contact-primary);

    font-size:17px;
}

.contact-map-header span{
    color:#7a8494;

    font-size:12px;
}

.contact-map-frame{
    width:100%;
    height:350px;
}

.contact-map-frame iframe{
    display:block;

    width:100%;
    height:100%;

    border:0;
}


/* =========================================================
   FOOTER
========================================================= */

.contact-footer{
    width:100%;

    background:#071a33;

    color:#fff;

    padding:28px 20px;

    text-align:center;
}

.contact-footer-brand{
    font-size:16px;

    font-weight:800;

    margin-bottom:5px;
}

.contact-footer p{
    margin:0;

    color:rgba(255,255,255,.65);

    font-size:11px;
}

.contact-footer-links{
    margin-top:14px;

    display:flex;

    justify-content:center;

    align-items:center;

    gap:12px;

    flex-wrap:wrap;
}

.contact-footer-links a{
    color:rgba(255,255,255,.75);

    text-decoration:none;

    font-size:11px;
}

.contact-footer-links a:hover{
    color:#fff;
}


/* =========================================================
   TABLET
========================================================= */

@media(max-width:950px){

    .contact-info-grid{
        grid-template-columns:
            repeat(2, minmax(0,1fr));
    }

    .contact-content-grid{
        grid-template-columns:1fr;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:600px){

    .contact-topbar{
        padding:10px 14px;
    }

    .contact-brand-text strong{
        max-width:190px;

        font-size:12px;
    }

    .contact-brand-text span{
        font-size:9px;
    }

    .contact-brand img{
        width:42px;
        height:42px;
    }

    .contact-language a{
        padding:6px 8px;

        font-size:10px;
    }

    .contact-hero{
        padding:48px 16px 95px;
    }

    .contact-hero h1{
        font-size:33px;
    }

    .contact-hero p{
        font-size:13px;

        line-height:1.9;
    }

    .contact-main{
        width:calc(100% - 20px);

        margin-top:-52px;
    }

    .contact-info-grid{
        grid-template-columns:
            repeat(2, minmax(0,1fr));

        gap:10px;
    }

    .contact-info-card{
        padding:17px 8px;

        border-radius:14px;
    }

    .contact-info-icon{
        width:44px;
        height:44px;

        font-size:18px;
    }

    .contact-info-card h3{
        font-size:13px;
    }

    .contact-info-card p{
        font-size:10px;
    }

    .contact-form-card{
        padding:21px 15px;

        border-radius:15px;
    }

    .contact-form-row{
        grid-template-columns:1fr;

        gap:0;
    }

    .contact-form-heading h2{
        font-size:22px;
    }

    .contact-side-card,
    .contact-whatsapp{
        border-radius:15px;

        padding:20px;
    }

    .contact-map-header{
        flex-direction:column;

        align-items:flex-start;
    }

    .contact-map-frame{
        height:280px;
    }

}


/* =========================================================
   VERY SMALL
========================================================= */

@media(max-width:380px){

    .contact-info-grid{
        grid-template-columns:1fr;
    }

    .contact-language{
        flex-direction:column;
        gap:3px;
    }

    .contact-hero h1{
        font-size:29px;
    }

}

</style>

</head>


<body class="contact-page">


<!-- =========================================================
     MAIN NAVIGATION
========================================================= -->

<nav class="contact-main-nav">

    <div class="contact-main-nav-inner">

        <a href="index.php?lang=<?= urlencode($lang) ?>">
            <i class="fa-solid fa-house"></i>
            <?= $isArabic ? 'الرئيسية' : 'Home' ?>
        </a>

        <a href="about.php?lang=<?= urlencode($lang) ?>">
            <i class="fa-solid fa-circle-info"></i>
            <?= $isArabic ? 'من نحن' : 'About Us' ?>
        </a>

        <a
            href="contact.php?lang=<?= urlencode($lang) ?>"
            class="active"
        >
            <i class="fa-solid fa-headset"></i>
            <?= $isArabic ? 'تواصل معنا' : 'Contact Us' ?>
        </a>

    </div>

</nav>


<!-- =========================================================
     TOP BAR
========================================================= -->

<header class="contact-topbar">

    <!-- BRAND -->

    <div class="contact-brand">

        <?php if ($logoPath !== ''): ?>

            <img
                src="<?= htmlspecialchars($logoPath) ?>"
                alt="<?= htmlspecialchars($companyName) ?>"
            >

        <?php endif; ?>

        <div class="contact-brand-text">

            <strong>
                <?= htmlspecialchars($systemName) ?>
            </strong>

            <span>
                <?= htmlspecialchars($companyName) ?>
            </span>

        </div>

    </div>


    <!-- LANGUAGE -->

    <div class="contact-language">

        <a
            href="contact.php?lang=ar"
            class="<?= $isArabic ? 'active' : '' ?>"
        >
            🇸🇦 عربي
        </a>

        <a
            href="contact.php?lang=en"
            class="<?= !$isArabic ? 'active' : '' ?>"
        >
            🇬🇧 English
        </a>

    </div>

</header>

<!-- =========================================================
     HERO
========================================================= -->

<section class="contact-hero">

    <div class="contact-hero-content">

        <div class="contact-hero-badge">

            <i class="fa-solid fa-headset"></i>

            <?= htmlspecialchars($heroBadge) ?>

        </div>


        <h1>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>


        <p>
            <?= htmlspecialchars($heroText) ?>
        </p>

    </div>

</section>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="contact-main">


    <!-- =====================================================
         CONTACT INFO
    ====================================================== -->

    <section class="contact-info-grid">


        <!-- EMAIL -->

        <div class="contact-info-card">

            <div class="contact-info-icon">

                <i class="fa-solid fa-envelope"></i>

            </div>

            <h3>
                <?= htmlspecialchars($emailTitle) ?>
            </h3>

            <p>

                <a href="mailto:<?= $emailSafe ?>">
                    <?= $emailSafe ?>
                </a>

            </p>

        </div>


        <!-- PHONE -->

        <div class="contact-info-card">

            <div class="contact-info-icon">

                <i class="fa-solid fa-phone"></i>

            </div>

            <h3>
                <?= htmlspecialchars($phoneTitle) ?>
            </h3>

            <p>

                <a href="tel:<?= htmlspecialchars($phoneClean) ?>">
                    <?= htmlspecialchars($companyPhone) ?>
                </a>

            </p>

        </div>


        <!-- LOCATION -->

        <div class="contact-info-card">

            <div class="contact-info-icon">

                <i class="fa-solid fa-location-dot"></i>

            </div>

            <h3>
                <?= htmlspecialchars($locationTitle) ?>
            </h3>

            <p>
                <?= htmlspecialchars($companyAddress) ?>
            </p>

        </div>


        <!-- WHATSAPP -->

        <div class="contact-info-card">

            <div class="contact-info-icon">

                <i class="fa-brands fa-whatsapp"></i>

            </div>

            <h3>
                <?= htmlspecialchars($whatsappTitle) ?>
            </h3>

            <p>

                <a
                    href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>"
                    target="_blank"
                    rel="noopener"
                >
                    +966 550186105
                </a>

            </p>

        </div>


    </section>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <section class="contact-content-grid">


        <!-- =================================================
             FORM
        ================================================== -->

        <div class="contact-form-card">


            <div class="contact-form-heading">

                <small>
                    <?= htmlspecialchars($supportTitle) ?>
                </small>

                <h2>

                    <i class="fa-regular fa-paper-plane"></i>

                    <?= htmlspecialchars($formTitle) ?>

                </h2>

                <p>
                    <?= htmlspecialchars($formDescription) ?>
                </p>

            </div>


            <!-- SUCCESS -->

            <?php if ($success !== ''): ?>

                <div class="contact-alert contact-alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div class="contact-alert contact-alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="post"
                id="contactTicketForm"
                autocomplete="on"
            >


                <!-- NAME / EMAIL -->

                <div class="contact-form-row">


                    <div class="contact-form-group">

                        <label>

                            <?= htmlspecialchars($nameLabel) ?>

                            <span class="contact-required">*</span>

                        </label>


                        <div class="contact-input-wrap">

                            <i class="fa-regular fa-user"></i>

                          <input
    class="contact-input"
    type="text"
    name="name"
    maxlength="100"
    required
    autocomplete="name"
    value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>"
    placeholder="<?= htmlspecialchars($namePlaceholder) ?>"
>

                        </div>

                    </div>


                    <div class="contact-form-group">

                        <label>

                            <?= htmlspecialchars($emailLabel) ?>

                            <span class="contact-required">*</span>

                        </label>


                        <div class="contact-input-wrap">

                            <i class="fa-regular fa-envelope"></i>

                           <input
    class="contact-input"
    type="email"
    name="email"
    maxlength="150"
    required
    autocomplete="email"
    value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>"
    placeholder="example@email.com"
>

                        </div>

                    </div>


                </div>


                <!-- PHONE / SUBJECT -->

                <div class="contact-form-row">


                    <div class="contact-form-group">

                        <label>
                            <?= htmlspecialchars($phoneLabel) ?>
                        </label>


                        <div class="contact-input-wrap">

                            <i class="fa-solid fa-mobile-screen"></i>

                            <input
    class="contact-input"
    type="text"
    name="phone"
    maxlength="30"
    autocomplete="tel"
    value="<?= htmlspecialchars($userPhone, ENT_QUOTES, 'UTF-8') ?>"
    placeholder="<?= htmlspecialchars($phonePlaceholder) ?>"
>

                        </div>

                    </div>


                    <div class="contact-form-group">

                        <label>

                            <?= htmlspecialchars($subjectLabel) ?>

                            <span class="contact-required">*</span>

                        </label>


                        <div class="contact-input-wrap">

                            <i class="fa-regular fa-bookmark"></i>

                            <input
                                class="contact-input"
                                type="text"
                                name="subject"
                                maxlength="200"
                                required
                                placeholder="<?= htmlspecialchars($subjectPlaceholder) ?>"
                            >

                        </div>

                    </div>


                </div>


                <!-- MESSAGE -->

                <div class="contact-form-group">

                    <label>

                        <?= htmlspecialchars($messageLabel) ?>

                        <span class="contact-required">*</span>

                    </label>


                    <textarea
                        class="contact-textarea"
                        name="message"
                        maxlength="5000"
                        required
                        placeholder="<?= htmlspecialchars($messagePlaceholder) ?>"
                    ></textarea>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    name="send"
                    class="contact-send-btn"
                    id="contactSendButton"
                >

                    <i class="fa-regular fa-paper-plane"></i>

                    <span id="contactSendText">
                        <?= htmlspecialchars($sendText) ?>
                    </span>

                </button>


                <div class="contact-form-note">

                    <i class="fa-solid fa-shield-halved"></i>

                    <?= htmlspecialchars($confidential) ?>

                </div>


            </form>


        </div>


        <!-- =================================================
             SIDE
        ================================================== -->

        <aside class="contact-side">


            <!-- QUICK CONTACT -->

            <div class="contact-side-card">

                <h3>

                    <i class="fa-solid fa-headset"></i>

                    <?= htmlspecialchars($contactOptions) ?>

                </h3>


                <div class="contact-quick">


                    <!-- PHONE -->

                    <a
                        href="tel:<?= htmlspecialchars($phoneClean) ?>"
                        class="contact-quick-link"
                    >

                        <div class="contact-quick-icon">

                            <i class="fa-solid fa-phone"></i>

                        </div>

                        <div class="contact-quick-text">

                            <strong>
                                <?= htmlspecialchars($customerService) ?>
                            </strong>

                            <span>
                                <?= htmlspecialchars($companyPhone) ?>
                            </span>

                        </div>

                    </a>


                    <!-- EMAIL -->

                    <a
                        href="mailto:<?= $emailSafe ?>"
                        class="contact-quick-link"
                    >

                        <div class="contact-quick-icon">

                            <i class="fa-solid fa-envelope"></i>

                        </div>

                        <div class="contact-quick-text">

                            <strong>
                                <?= htmlspecialchars($emailText) ?>
                            </strong>

                            <span>
                                <?= $emailSafe ?>
                            </span>

                        </div>

                    </a>


                    <!-- WEBSITE / COUNTRY -->

                    <div class="contact-quick-link">

                        <div class="contact-quick-icon">

                            <i class="fa-solid fa-globe"></i>

                        </div>

                        <div class="contact-quick-text">

                            <strong>
                                <?= $isArabic ? 'الموقع الإلكتروني' : 'Website' ?>
                            </strong>

                            <span>
                                <?= htmlspecialchars($companyWebsite) ?>
                            </span>

                        </div>

                    </div>


                    <!-- WHATSAPP -->

                    <a
                        href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>"
                        target="_blank"
                        rel="noopener"
                        class="contact-quick-link"
                    >

                        <div class="contact-quick-icon">

                            <i class="fa-brands fa-whatsapp"></i>

                        </div>

                        <div class="contact-quick-text">

                            <strong>
                                <?= htmlspecialchars($whatsappText) ?>
                            </strong>

                            <span>
                                +966 550186105
                            </span>

                        </div>

                    </a>


                </div>

            </div>


            <!-- WHATSAPP BOX -->

            <div class="contact-whatsapp">

                <h3>

                    <i class="fa-brands fa-whatsapp"></i>

                    <?= htmlspecialchars($whatsappBoxTitle) ?>

                </h3>


                <p>
                    <?= htmlspecialchars($whatsappBoxText) ?>
                </p>


                <a
                    href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>"
                    target="_blank"
                    rel="noopener"
                    class="contact-whatsapp-btn"
                >

                    <i class="fa-brands fa-whatsapp"></i>

                    <?= htmlspecialchars($openWhatsapp) ?>

                </a>

            </div>


        </aside>


    </section>


    <!-- =====================================================
         MAP
    ====================================================== -->

    <section class="contact-map-card">


        <div class="contact-map-header">

            <h3>

                <i class="fa-solid fa-location-dot"></i>

                <?= htmlspecialchars($mapTitle) ?>

            </h3>


            <span>

                <?= htmlspecialchars($companyAddress) ?>

            </span>

        </div>


        <div class="contact-map-frame">

            <iframe
                src="https://www.google.com/maps?q=Morni%20corporate%20office%20Riyadh&output=embed"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>

        </div>


    </section>


</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="contact-footer">

    <div class="contact-footer-brand">

        <?= htmlspecialchars($companyName) ?>

    </div>


    <p>

        <?= $isArabic
            ? 'جميع الحقوق محفوظة © ' . date('Y')
            : 'All rights reserved © ' . date('Y')
        ?>

    </p>


    <div class="contact-footer-links">

        <a href="index.php?lang=<?= urlencode($lang) ?>">
            <?= $isArabic ? 'الرئيسية' : 'Home' ?>
        </a>

        <a href="about.php?lang=<?= urlencode($lang) ?>">
            <?= $isArabic ? 'من نحن' : 'About Us' ?>
        </a>

        <a href="contact.php?lang=<?= urlencode($lang) ?>">
            <?= $isArabic ? 'تواصل معنا' : 'Contact Us' ?>
        </a>

    </div>

</footer>


<script>

/* =========================================================
   SEND BUTTON
========================================================= */

const contactForm =
    document.getElementById('contactTicketForm');

const contactSendButton =
    document.getElementById('contactSendButton');

const contactSendText =
    document.getElementById('contactSendText');


if (contactForm) {

    contactForm.addEventListener('submit', function () {

        if (contactSendButton) {
            contactSendButton.disabled = true;
        }

        if (contactSendText) {

            contactSendText.textContent =
                <?= json_encode($sendingText, JSON_UNESCAPED_UNICODE) ?>;

        }

    });

}

</script>


</body>
</html>