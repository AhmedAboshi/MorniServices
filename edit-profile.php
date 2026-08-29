<?php

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/include/connected.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$con->set_charset('utf8mb4');


/* =========================================================
   LOGIN CHECK
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
   HELPERS
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
   DIRECTORIES
========================================================= */

$uploadDir = __DIR__ . '/upload/users/';

$uploadUrl = 'upload/users/';


/* =========================================================
   CREATE DIRECTORY
========================================================= */

if (!is_dir($uploadDir)) {

    @mkdir(
        $uploadDir,
        0755,
        true
    );
}


/* =========================================================
   DEFAULT IMAGE
========================================================= */

$defaultImage = 'assets/default-user.png';


/* =========================================================
   GET USER
========================================================= */

try {

    $stmt = $con->prepare("
        SELECT
            id,
            username,
            email,
            phone,
            image
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

    $user = $result->fetch_assoc();

    $stmt->close();


    if (!$user) {

        session_destroy();

        header("Location: login.php");

        exit;
    }


} catch (Throwable $e) {

    die(
        'حدث خطأ أثناء جلب بيانات المستخدم: ' .
        h($e->getMessage())
    );
}


/* =========================================================
   MESSAGE
========================================================= */

$success = '';

$error = '';


/* =========================================================
   CURRENT IMAGE
========================================================= */

$currentImage = $defaultImage;

$savedImage = trim(
    (string)($user['image'] ?? '')
);

if ($savedImage !== '') {

    $savedImage = basename($savedImage);

    $physicalImage =
        $uploadDir . $savedImage;

    if (is_file($physicalImage)) {

        $currentImage =
            $uploadUrl .
            rawurlencode($savedImage);
    }
}


/* =========================================================
   UPDATE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update'])
) {

    try {

        /* =================================================
           FORM DATA
        ================================================= */

        $username = trim(
            $_POST['username'] ?? ''
        );

        $email = trim(
            $_POST['email'] ?? ''
        );

        $phone = trim(
            $_POST['phone'] ?? ''
        );

        $password = (string)(
            $_POST['password'] ?? ''
        );


        /* =================================================
           VALIDATION
        ================================================= */

        if ($username === '') {

            throw new Exception(
                'الرجاء إدخال الاسم.'
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


        /* =================================================
           CHECK EMAIL
        ================================================= */

        $stmt = $con->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id <> ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "si",
            $email,
            $user_id
        );

        $stmt->execute();

        $emailResult = $stmt->get_result();

        if ($emailResult->num_rows > 0) {

            $stmt->close();

            throw new Exception(
                'البريد الإلكتروني مستخدم بالفعل.'
            );
        }

        $stmt->close();


        /* =================================================
           START TRANSACTION
        ================================================= */

        $con->begin_transaction();


        /* =================================================
           UPDATE BASIC DATA
        ================================================= */

        $stmt = $con->prepare("
            UPDATE users
            SET
                username = ?,
                email = ?,
                phone = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssi",
            $username,
            $email,
            $phone,
            $user_id
        );

        $stmt->execute();

        $stmt->close();


        /* =================================================
           UPDATE PASSWORD
           فقط إذا أدخل المستخدم كلمة مرور جديدة
        ================================================= */

        if ($password !== '') {

            if (strlen($password) < 6) {

                throw new Exception(
                    'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.'
                );
            }


            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $con->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $hashedPassword,
                $user_id
            );

            $stmt->execute();

            $stmt->close();
        }


        /* =================================================
           IMAGE UPLOAD
        ================================================= */

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['image']['error'] !==
                UPLOAD_ERR_OK
            ) {

                throw new Exception(
                    'حدث خطأ أثناء رفع الصورة.'
                );
            }


            /* ---------------------------------------------
               SIZE
            --------------------------------------------- */

            $maxSize = 5 * 1024 * 1024;

            if (
                (int)$_FILES['image']['size'] >
                $maxSize
            ) {

                throw new Exception(
                    'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.'
                );
            }


            /* ---------------------------------------------
               MIME TYPE
            --------------------------------------------- */

            $tmpFile =
                $_FILES['image']['tmp_name'];


            $finfo = new finfo(
                FILEINFO_MIME_TYPE
            );

            $mime =
                $finfo->file($tmpFile);


            $allowedTypes = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'image/webp' => 'webp',

                'image/gif' => 'gif'

            ];


            if (
                !isset(
                    $allowedTypes[$mime]
                )
            ) {

                throw new Exception(
                    'نوع الصورة غير مسموح. استخدم JPG أو PNG أو WEBP أو GIF.'
                );
            }


            /* ---------------------------------------------
               IMAGE VALIDATION
            --------------------------------------------- */

            if (
                @getimagesize($tmpFile) === false
            ) {

                throw new Exception(
                    'الملف المرفوع ليس صورة صحيحة.'
                );
            }


            /* ---------------------------------------------
               NEW FILE NAME
            --------------------------------------------- */

            $extension =
                $allowedTypes[$mime];


            $newFileName =
                'user_' .
                $user_id .
                '_' .
                time() .
                '_' .
                bin2hex(
                    random_bytes(4)
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDir .
                $newFileName;


            /* ---------------------------------------------
               MOVE IMAGE
            --------------------------------------------- */

            if (
                !move_uploaded_file(
                    $tmpFile,
                    $destination
                )
            ) {

                throw new Exception(
                    'تعذر حفظ الصورة على الخادم.'
                );
            }


            /* ---------------------------------------------
               UPDATE DATABASE
            --------------------------------------------- */

            $stmt = $con->prepare("
                UPDATE users
                SET image = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $newFileName,
                $user_id
            );

            $stmt->execute();

            $stmt->close();


            /* ---------------------------------------------
               DELETE OLD IMAGE
            --------------------------------------------- */

            if (
                $savedImage !== '' &&
                $savedImage !==
                $newFileName
            ) {

                $oldImage =
                    $uploadDir .
                    basename($savedImage);

                if (
                    is_file($oldImage)
                ) {

                    @unlink($oldImage);
                }
            }


            /* ---------------------------------------------
               UPDATE CURRENT IMAGE
            --------------------------------------------- */

            $savedImage =
                $newFileName;

            $currentImage =
                $uploadUrl .
                rawurlencode(
                    $newFileName
                );
        }


        /* =================================================
           COMMIT
        ================================================= */

        $con->commit();


        /* =================================================
           UPDATE SESSION
        ================================================= */

        $_SESSION['username'] =
            $username;


        /* =================================================
           SUCCESS
        ================================================= */

        $success =
            'تم تحديث بيانات الملف الشخصي بنجاح.';


        /* =================================================
           REFRESH USER DATA
        ================================================= */

        $user['username'] =
            $username;

        $user['email'] =
            $email;

        $user['phone'] =
            $phone;

        $user['image'] =
            $savedImage;


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

<html
    lang="ar"
    dir="rtl"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
تعديل الملف الشخصي
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
>

<style>

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

body{

    min-height:100vh;

    background:
        linear-gradient(
            135deg,
            #f4f7fb,
            #eaf0f8
        );

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;

    padding:35px 15px;

    color:#1f2937;
}


/* =========================================================
   CONTAINER
========================================================= */

.profile-wrapper{

    width:100%;

    max-width:650px;

    margin:auto;
}


/* =========================================================
   CARD
========================================================= */

.profile-card{

    background:#fff;

    border-radius:26px;

    overflow:hidden;

    box-shadow:
        0 20px 60px
        rgba(15,23,42,.10);
}


/* =========================================================
   HEADER
========================================================= */

.profile-header{

    background:
        linear-gradient(
            135deg,
            #173b82,
            #2563eb
        );

    padding:35px 25px 30px;

    text-align:center;

    color:#fff;

    position:relative;
}


.profile-header h1{

    font-size:27px;

    margin-bottom:6px;

    font-weight:800;
}


.profile-header p{

    opacity:.85;

    font-size:14px;
}


/* =========================================================
   AVATAR
========================================================= */

.avatar-wrapper{

    position:relative;

    width:125px;

    height:125px;

    margin:
        0 auto 20px;
}


.avatar{

    width:125px;

    height:125px;

    border-radius:50%;

    object-fit:cover;

    background:#fff;

    border:5px solid #fff;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.20);
}


.avatar-camera{

    position:absolute;

    bottom:3px;

    right:3px;

    width:38px;

    height:38px;

    border-radius:50%;

    border:3px solid #fff;

    background:#f59e0b;

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:16px;
}


/* =========================================================
   BODY
========================================================= */

.profile-body{

    padding:30px;
}


/* =========================================================
   MESSAGES
========================================================= */

.message{

    padding:15px 18px;

    border-radius:13px;

    margin-bottom:22px;

    line-height:1.8;

    font-size:14px;

    font-weight:600;
}


.message.success{

    background:#ecfdf5;

    color:#047857;

    border:
        1px solid #a7f3d0;
}


.message.error{

    background:#fff1f2;

    color:#be123c;

    border:
        1px solid #fecdd3;
}


/* =========================================================
   FORM GROUP
========================================================= */

.form-group{

    margin-bottom:20px;
}


.form-group label{

    display:block;

    margin-bottom:8px;

    font-size:14px;

    font-weight:700;

    color:#374151;
}


.form-group label i{

    color:#2563eb;

    margin-left:5px;
}


.form-control{

    width:100%;

    height:50px;

    border:
        1px solid #dbe1ea;

    border-radius:12px;

    padding:
        0 15px;

    background:#f9fafb;

    color:#1f2937;

    font-family:inherit;

    font-size:15px;

    outline:none;

    transition:.2s;
}


.form-control:focus{

    background:#fff;

    border-color:#2563eb;

    box-shadow:
        0 0 0 4px
        rgba(37,99,235,.10);
}


/* =========================================================
   FILE INPUT
========================================================= */

.file-input{

    border:
        2px dashed #cbd5e1;

    background:#f8fafc;

    padding:15px;

    height:auto;

    cursor:pointer;
}


.file-input:hover{

    border-color:#2563eb;

    background:#eff6ff;
}


/* =========================================================
   PASSWORD INFO
========================================================= */

.password-info{

    background:#eff6ff;

    border-radius:12px;

    padding:12px 15px;

    color:#1d4ed8;

    font-size:13px;

    line-height:1.8;

    margin-top:-8px;

    margin-bottom:20px;
}


/* =========================================================
   BUTTONS
========================================================= */

.buttons{

    display:flex;

    gap:12px;

    margin-top:28px;
}


.btn{

    flex:1;

    height:52px;

    border:0;

    border-radius:13px;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    text-decoration:none;

    font-family:inherit;

    font-size:15px;

    font-weight:700;

    cursor:pointer;

    transition:.2s;
}


.save-btn{

    background:#2563eb;

    color:#fff;
}


.save-btn:hover{

    background:#1d4ed8;

    transform:translateY(-2px);
}


.back-btn{

    background:#f1f5f9;

    color:#334155;

    border:
        1px solid #e2e8f0;
}


.back-btn:hover{

    background:#e2e8f0;

    transform:translateY(-2px);
}


/* =========================================================
   FOOTER
========================================================= */

.security-note{

    text-align:center;

    color:#64748b;

    font-size:12px;

    margin-top:20px;
}


.security-note i{

    color:#16a34a;

    margin-left:4px;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:600px){

    body{

        padding:15px 10px;
    }


    .profile-header{

        padding:
            30px 18px;
    }


    .profile-header h1{

        font-size:23px;
    }


    .profile-body{

        padding:22px 18px;
    }


    .buttons{

        flex-direction:column;
    }


    .btn{

        width:100%;
    }

}

</style>

</head>


<body>


<div class="profile-wrapper">


    <div class="profile-card">


        <!-- =================================================
             HEADER
        ================================================= -->

        <div class="profile-header">


            <div class="avatar-wrapper">

                <img
                    src="<?= h($currentImage) ?>"
                    class="avatar"
                    id="avatarPreview"
                    alt="صورة المستخدم"
                    onerror="
                        this.onerror=null;
                        this.src='<?= h($defaultImage) ?>';
                    "
                >

                <div class="avatar-camera">

                    <i class="fa-solid fa-camera"></i>

                </div>

            </div>


            <h1>
                تعديل الملف الشخصي
            </h1>


            <p>
                قم بتحديث بيانات حسابك الشخصية
            </p>

        </div>


        <!-- =================================================
             BODY
        ================================================= -->

        <div class="profile-body">


            <?php if ($success !== '') { ?>

                <div class="message success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= h($success) ?>

                </div>

            <?php } ?>


            <?php if ($error !== '') { ?>

                <div class="message error">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <?= h($error) ?>

                </div>

            <?php } ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =================================================
                     IMAGE
                ================================================= -->

                <div class="form-group">

                    <label>

                        <i class="fa-solid fa-image"></i>

                        صورة الملف الشخصي

                    </label>


                    <input
                        type="file"
                        name="image"
                        class="form-control file-input"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        onchange="previewImage(this)"
                    >

                </div>


                <!-- =================================================
                     USERNAME
                ================================================= -->

                <div class="form-group">

                    <label>

                        <i class="fa-solid fa-user"></i>

                        الاسم

                    </label>


                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        value="<?= h($user['username'] ?? '') ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <!-- =================================================
                     EMAIL
                ================================================= -->

                <div class="form-group">

                    <label>

                        <i class="fa-solid fa-envelope"></i>

                        البريد الإلكتروني

                    </label>


                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= h($user['email'] ?? '') ?>"
                        maxlength="150"
                        required
                    >

                </div>


                <!-- =================================================
                     PHONE
                ================================================= -->

                <div class="form-group">

                    <label>

                        <i class="fa-solid fa-phone"></i>

                        رقم الجوال

                    </label>


                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= h($user['phone'] ?? '') ?>"
                        maxlength="30"
                        required
                    >

                </div>


                <!-- =================================================
                     PASSWORD
                ================================================= -->

                <div class="form-group">

                    <label>

                        <i class="fa-solid fa-lock"></i>

                        كلمة المرور الجديدة

                    </label>


                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="اتركها فارغة إذا لا تريد تغييرها"
                        minlength="6"
                        autocomplete="new-password"
                    >

                </div>


                <div class="password-info">

                    <i class="fa-solid fa-circle-info"></i>

                    إذا كنت لا تريد تغيير كلمة المرور،
                    اترك الحقل فارغاً.

                </div>


                <!-- =================================================
                     BUTTONS
                ================================================= -->

                <div class="buttons">


                    <button
                        type="submit"
                        name="update"
                        value="1"
                        class="btn save-btn"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        حفظ التعديلات

                    </button>


                    <a
                        href="Profile.php"
                        class="btn back-btn"
                    >

                        <i class="fa-solid fa-arrow-right"></i>

                        العودة للملف

                    </a>


                </div>


            </form>


            <div class="security-note">

                <i class="fa-solid fa-shield-halved"></i>

                بياناتك محفوظة بشكل آمن

            </div>


        </div>

    </div>

</div>


<script>

/* =========================================================
   IMAGE PREVIEW
========================================================= */

function previewImage(input){

    const preview =
        document.getElementById('avatarPreview');

    if(
        !input ||
        !input.files ||
        !input.files[0] ||
        !preview
    ){
        return;
    }

    const file =
        input.files[0];


    /* التحقق من النوع */

    const allowed = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    if(!allowed.includes(file.type)){

        alert(
            'نوع الصورة غير مسموح.'
        );

        input.value = '';

        return;
    }


    /* التحقق من الحجم */

    if(file.size > 5 * 1024 * 1024){

        alert(
            'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.'
        );

        input.value = '';

        return;
    }


    const reader =
        new FileReader();


    reader.onload =
        function(event){

            preview.src =
                event.target.result;
        };


    reader.readAsDataURL(file);
}

</script>


</body>

</html>