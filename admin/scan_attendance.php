<?php

session_start();

include('../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

date_default_timezone_set('Asia/Riyadh');

$message = '';
$success = false;
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

/* =========================================================
   دالة توحيد QR
========================================================= */

function extractDriverId(string $qr): int
{
    $qr = trim($qr);

    if ($qr === '') {
        return 0;
    }

    /* إزالة علامات الاقتباس */
    $qr = trim($qr, "\"' ");

    /*
     * الحالة 1:
     * DRIVER_33
     */
    if (preg_match('/DRIVER[_-](\d+)/i', $qr, $matches)) {
        return (int)$matches[1];
    }

    /*
     * الحالة 2:
     * رابط يحتوي driver=DRIVER_33
     */
    $query = parse_url($qr, PHP_URL_QUERY);

    if ($query) {

        parse_str($query, $queryParams);

        if (!empty($queryParams['driver'])) {

            $driverValue =
                trim(
                    (string)$queryParams['driver']
                );

            if (
                preg_match(
                    '/DRIVER[_-](\d+)/i',
                    $driverValue,
                    $matches
                )
            ) {
                return (int)$matches[1];
            }
        }

        if (!empty($queryParams['qr_code'])) {

            $qrValue =
                trim(
                    (string)$queryParams['qr_code']
                );

            if (
                preg_match(
                    '/DRIVER[_-](\d+)/i',
                    $qrValue,
                    $matches
                )
            ) {
                return (int)$matches[1];
            }
        }
    }

    return 0;
}

/* =========================================================
   استقبال QR
========================================================= */

$qr = '';

/*
 * من GET
 *
 * scan_attendance.php?driver=DRIVER_33
 */
if (isset($_GET['driver'])) {

    $qr =
        trim(
            (string)$_GET['driver']
        );
}

/*
 * من POST
 */
if (isset($_POST['qr_code'])) {

    $qr =
        trim(
            (string)$_POST['qr_code']
        );
}

/*
 * دعم qr أيضًا
 */
if (
    $qr === '' &&
    isset($_POST['qr'])
) {

    $qr =
        trim(
            (string)$_POST['qr']
        );
}

/* =========================================================
   معالجة QR
========================================================= */

if ($qr !== '') {

    $driver_id =
        extractDriverId($qr);

    /*
     * QR غير صالح
     */
    if ($driver_id <= 0) {

        $message =
            "❌ QR غير صحيح أو لا يحتوي على رمز سائق صالح.";

    } else {

        /* =================================================
           جلب السائق
        ================================================= */

        $stmt = $con->prepare("
            SELECT
                id,
                name
            FROM drivers
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $message =
                "❌ خطأ في استعلام السائق: " .
                $con->error;

        } else {

            $stmt->bind_param(
                "i",
                $driver_id
            );

            $stmt->execute();

            $driver =
                $stmt
                    ->get_result()
                    ->fetch_assoc();

            $stmt->close();

            if (!$driver) {

                $message =
                    "❌ السائق رقم " .
                    $driver_id .
                    " غير موجود.";

            } else {

                /* =========================================
                   التاريخ والوقت
                ========================================= */

                $today =
                    date('Y-m-d');

                $time =
                    date('H:i:s');

                /* =========================================
                   التحقق من سجل اليوم
                ========================================= */

                $check =
                    $con->prepare("
                        SELECT
                            id,
                            attendance_date,
                            check_in,
                            check_out,
                            status
                        FROM attendance
                        WHERE driver_id = ?
                        AND attendance_date = ?
                        LIMIT 1
                    ");

                if (!$check) {

                    $message =
                        "❌ خطأ في التحقق من الحضور: " .
                        $con->error;

                } else {

                    $check->bind_param(
                        "is",
                        $driver_id,
                        $today
                    );

                    $check->execute();

                    $attendance =
                        $check
                            ->get_result()
                            ->fetch_assoc();

                    $check->close();

                    /* =====================================
                       لا يوجد سجل اليوم
                    ===================================== */

                    if (!$attendance) {

                        $insert =
                            $con->prepare("
                                INSERT INTO attendance
                                (
                                    driver_id,
                                    attendance_date,
                                    check_in,
                                    status
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    'present'
                                )
                            ");

                        if (!$insert) {

                            $message =
                                "❌ خطأ في تجهيز تسجيل الحضور: " .
                                $con->error;

                        } else {

                            $insert->bind_param(
                                "iss",
                                $driver_id,
                                $today,
                                $time
                            );

                            if ($insert->execute()) {

                                $success = true;

                                $message = "
                                    <div class='success'>
                                        <h3>✅ تم تسجيل الحضور</h3>

                                        <p>
                                            السائق:
                                            <br>
                                            <b>"
                                            . htmlspecialchars(
                                                $driver['name']
                                            )
                                            ."
                                        </p>

                                        <p>
                                            رقم السائق:
                                            <br>
                                            <b>DRIVER_"
                                            . $driver_id .
                                            "</b>
                                        </p>

                                        <p>
                                            وقت الدخول:
                                            <br>
                                            <b>"
                                            . $time .
                                            "</b>
                                        </p>
                                    </div>
                                ";

                            } else {

                                $message =
                                    "❌ تعذر تسجيل الحضور: " .
                                    $insert->error;
                            }

                            $insert->close();
                        }

                    /*
                     * يوجد حضور ولا يوجد انصراف
                     */
                    } elseif (
                        empty(
                            $attendance['check_out']
                        )
                    ) {

                        $update =
                            $con->prepare("
                                UPDATE attendance
                                SET
                                    check_out = ?
                                WHERE id = ?
                                LIMIT 1
                            ");

                        if (!$update) {

                            $message =
                                "❌ خطأ في تحديث الانصراف: " .
                                $con->error;

                        } else {

                            $attendanceId =
                                (int)$attendance['id'];

                            $update->bind_param(
                                "si",
                                $time,
                                $attendanceId
                            );

                            if ($update->execute()) {

                                $success = true;

                                $message = "
                                    <div class='success checkout'>
                                        <h3>🟢 تم تسجيل الانصراف</h3>

                                        <p>
                                            السائق:
                                            <br>
                                            <b>"
                                            . htmlspecialchars(
                                                $driver['name']
                                            )
                                            ."
                                        </p>

                                        <p>
                                            وقت الخروج:
                                            <br>
                                            <b>"
                                            . $time .
                                            "</b>
                                        </p>
                                    </div>
                                ";

                            } else {

                                $message =
                                    "❌ تعذر تسجيل الانصراف: " .
                                    $update->error;
                            }

                            $update->close();
                        }

                    /*
                     * حضور وانصراف مكتملان
                     */
                    } else {

                        $message = "
                            <div class='warning'>
                                ⚠️ تم تسجيل الحضور والانصراف
                                للسائق
                                <b>"
                                . htmlspecialchars(
                                    $driver['name']
                                )
                                ."
                                لهذا اليوم.
                            </div>
                        ";
                    }
                }
            }
        }
    }
}

/* =========================================================
   AJAX RESPONSE
========================================================= */

if ($is_ajax) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        [
            'success' =>
                $success,

            'message' =>
                strip_tags(
                    $message
                ),

            'driver_id' =>
                $driver_id
                    ?? 0,

            'qr' =>
                $qr
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
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
    تسجيل حضور السائق
</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:
        #f4f6f9;

    color:
        #1f2937;
}

.box{

    width:
        92%;

    max-width:
        520px;

    margin:
        40px auto;

    background:
        #fff;

    padding:
        28px;

    border-radius:
        20px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.10);

    text-align:
        center;
}

.icon{

    font-size:
        55px;

    margin-bottom:
        5px;
}

h2{

    margin:
        0 0 8px;

    color:
        #0d6efd;
}

.subtitle{

    color:
        #6b7280;

    font-size:
        13px;

    margin-bottom:
        20px;
}

input{

    width:
        100%;

    padding:
        14px;

    font-size:
        16px;

    border:
        1px solid
        #d1d5db;

    border-radius:
        10px;

    outline:none;

    margin-bottom:
        12px;
}

input:focus{

    border-color:
        #0d6efd;

    box-shadow:
        0 0 0 3px
        rgba(13,110,253,.10);
}

button{

    width:
        100%;

    background:
        #0d6efd;

    color:#fff;

    border:none;

    padding:
        13px;

    border-radius:
        10px;

    font-size:
        16px;

    font-weight:
        bold;

    cursor:pointer;
}

button:hover{

    background:
        #084298;
}

.success{

    margin-top:
        20px;

    background:
        #e8fff0;

    border:
        2px solid
        #28a745;

    padding:
        18px;

    border-radius:
        15px;

    color:
        #146c43;
}

.checkout{

    border-color:
        #0d6efd;

    background:
        #eef5ff;

    color:
        #084298;
}

.warning{

    margin-top:
        20px;

    background:
        #fff8e1;

    border:
        2px solid
        #ffc107;

    padding:
        18px;

    border-radius:
        15px;
}

.error{

    margin-top:
        20px;

    background:
        #fff0f0;

    border:
        2px solid
        #dc3545;

    padding:
        18px;

    border-radius:
        15px;

    color:
        #b02a37;
}

.qr-debug{

    margin-top:
        15px;

    background:
        #f8fafc;

    padding:
        10px;

    border-radius:
        8px;

    font-size:
        11px;

    color:
        #6b7280;

    word-break:
        break-all;
}

</style>

</head>

<body>

<div class="box">

<div class="icon">
    📅
</div>

<h2>
    حضور السائق
</h2>

<div class="subtitle">
    امسح رمز QR الموجود في بطاقة السائق
</div>

<form method="post">

<input
    type="text"
    name="qr_code"
    placeholder="DRIVER_33"
    autocomplete="off"
    autofocus
>

<input
    type="hidden"
    name="ajax"
    value="0"
>

<button
    type="submit"
>
    تسجيل
</button>

</form>

<?php if ($message !== ''): ?>

<?= $message ?>

<?php endif; ?>

<?php if (
    $qr !== '' &&
    !$success
): ?>

<div class="qr-debug">

QR المقروء:

<br>

<?= htmlspecialchars(
    $qr
) ?>

</div>

<?php endif; ?>

</div>

<?php if (
    isset($_GET['driver']) &&
    $message !== ''
): ?>

<script>

setTimeout(function(){

    window.location.href =
        "scan_attendance.php";

},5000);

</script>

<?php endif; ?>

</body>

</html>