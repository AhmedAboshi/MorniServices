
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

/* =========================================================
   رقم السائق
========================================================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    die('رقم السائق غير صحيح');
}

/* =========================================================
   بيانات السائق
========================================================= */

$stmt = $con->prepare("
    SELECT
        id,
        name,
        work_area,
        imagedriver,
        qr_code
    FROM drivers
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param(
    'i',
    $id
);

$stmt->execute();

$driver = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$driver) {
    die('السائق غير موجود');
}

/* =========================================================
   صورة السائق
========================================================= */

$imageName =
    trim(
        (string)(
            $driver['imagedriver']
            ?? ''
        )
    );

$imagePath =
    __DIR__ .
    '/../uploads/' .
    basename($imageName);

if (
    $imageName !== '' &&
    is_file($imagePath)
) {

    $imagedriver =
        '../uploads/' .
        basename($imageName);

} else {

    $imagedriver =
        '../uploads/default-user.png';
}

/* =========================================================
   QR للسائق
========================================================= */

/*
 * مهم:
 * نستخدم دائمًا DRIVER_ID
 *
 * مثال:
 * DRIVER_35
 */

$qr_code =
    'DRIVER_' .
    (int)$driver['id'];

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
    بطاقة السائق -
    <?= htmlspecialchars($driver['name']) ?>
</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    min-height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;

    background:
        linear-gradient(
            135deg,
            #eef3f8,
            #dfe7f1
        );

    font-family:
        Tahoma,
        Arial,
        sans-serif;
}

/* =========================================================
   CARD
========================================================= */

.driver-card{

    width:
        370px;

    max-width:
        92%;

    background:#fff;

    border-radius:
        22px;

    padding:
        24px;

    text-align:
        center;

    box-shadow:
        0 12px 35px
        rgba(0,0,0,.12);

    border:
        1px solid
        #e5e7eb;
}

/* =========================================================
   HEADER
========================================================= */

.card-header{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:#fff;

    margin:
        -24px -24px 20px;

    padding:
        18px;

    border-radius:
        22px 22px 0 0;
}

.company{

    font-size:
        13px;

    opacity:
        .9;
}

.card-title{

    font-size:
        20px;

    font-weight:
        800;

    margin-top:
        5px;
}

/* =========================================================
   PHOTO
========================================================= */

.avatar{

    width:
        120px;

    height:
        120px;

    border-radius:
        50%;

    object-fit:
        cover;

    border:
        4px solid
        #fff;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.12);

    margin-top:
        5px;
}

/* =========================================================
   DRIVER NAME
========================================================= */

.driver-name{

    font-size:
        23px;

    font-weight:
        800;

    color:
        #1f2937;

    margin:
        12px 0 5px;
}

.driver-id{

    display:
        inline-block;

    background:
        #eef5ff;

    color:
        #084298;

    padding:
        6px 13px;

    border-radius:
        20px;

    font-weight:
        700;

    font-size:
        13px;
}

.work-area{

    color:
        #6b7280;

    font-size:
        13px;

    margin-top:
        10px;
}

/* =========================================================
   QR
========================================================= */

.qr-box{

    margin-top:
        20px;

    padding:
        15px;

    background:
        #f8fafc;

    border:
        1px solid
        #e5e7eb;

    border-radius:
        15px;
}

.qr-title{

    font-weight:
        800;

    font-size:
        14px;

    margin-bottom:
        10px;
}

.qr-image{

    width:
        190px;

    height:
        190px;

    object-fit:
        contain;

    display:
        block;

    margin:
        0 auto;
}

.qr-code-text{

    margin-top:
        10px;

    font-family:
        monospace;

    font-size:
        16px;

    font-weight:
        800;

    color:
        #0d6efd;

    background:
        #fff;

    border:
        1px dashed
        #0d6efd;

    padding:
        7px;

    border-radius:
        8px;
}

/* =========================================================
   PRINT
========================================================= */

.print-button{

    margin-top:
        15px;

    width:
        100%;

    padding:
        11px;

    border:
        0;

    border-radius:
        10px;

    background:
        #198754;

    color:#fff;

    font-size:
        14px;

    font-weight:
        700;

    cursor:
        pointer;
}

@media print{

    body{

        background:#fff;
    }

    .print-button{

        display:none;
    }

    .driver-card{

        box-shadow:none;

        border:none;
    }
}

</style>

</head>

<body>

<div class="driver-card">

<div class="card-header">

<div class="company">
    شركة الشرق لخدمات السيارات
</div>

<div class="card-title">
    بطاقة السائق
</div>

</div>

<img
    src="<?= htmlspecialchars($imagedriver) ?>"
    class="avatar"
    alt="صورة السائق"
>

<div class="driver-name">

<?= htmlspecialchars(
    $driver['name']
) ?>

</div>

<div class="driver-id">

رقم السائق:
<?= (int)$driver['id'] ?>

</div>

<div class="work-area">

منطقة العمل:
<strong>
<?= htmlspecialchars(
    $driver['work_area'] ?? '-'
) ?>
</strong>

</div>

<div class="qr-box">

<div class="qr-title">

📱 رمز QR للحضور والانصراف

</div>

<img
    src="../generate_qr.php?text=<?= urlencode($qr_code) ?>"
    class="qr-image"
    alt="Driver QR"
>

<div class="qr-code-text">

<?= htmlspecialchars(
    $qr_code
) ?>

</div>

</div>

<button
    type="button"
    class="print-button"
    onclick="window.print()"
>

🖨️ طباعة البطاقة

</button>

</div>

</body>

</html>

