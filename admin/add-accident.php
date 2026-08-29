<?php

session_start();

include('../include/connected.php');
require_once 'mail.php';

/* =========================================================
   حماية الصفحة
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: welcome.php");
    exit();
}

$admin_id = (int)$_SESSION['admin_id'];

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang'])) {

    $newLang = $_GET['lang'];

    if (in_array($newLang, ['ar', 'en'], true)) {
        $_SESSION['lang'] = $newLang;
    }

    header("Location: add-accident.php");
    exit();
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   النصوص
========================================================= */

$words = [

    'ar' => [

        'title'              => 'تسجيل حادث جديد',
        'subtitle'           => 'إضافة وتسجيل حادث للمركبة والسائق',

        'vehicle'            => 'المركبة',
        'select_vehicle'     => 'اختر المركبة',

        'plate'              => 'رقم اللوحة',
        'model'              => 'الموديل',

        'driver'             => 'السائق',
        'select_driver'      => 'اختر السائق',

        'date'               => 'تاريخ ووقت الحادث',
        'location'           => 'موقع الحادث',
        'location_placeholder' => 'أدخل موقع الحادث',

        'desc'               => 'وصف الحادث',
        'desc_placeholder'   => 'اكتب تفاصيل الحادث',

        'cost'               => 'تكلفة الأضرار',
        'cost_placeholder'   => '0.00',

        'save'               => 'حفظ الحادث',
        'cancel'             => 'إلغاء',

        'back'               => 'العودة للحوادث',

        'success'            => 'تم تسجيل الحادث بنجاح',

        'required_vehicle'   => 'يرجى اختيار المركبة',
        'required_driver'    => 'يرجى اختيار السائق',
        'required_date'      => 'يرجى تحديد تاريخ الحادث',
        'invalid_vehicle'    => 'المركبة المحددة غير صحيحة',
        'invalid_driver'     => 'السائق المحدد غير صحيح',
        'invalid_cost'       => 'تكلفة الأضرار غير صحيحة',

        'error'              => 'حدث خطأ أثناء حفظ الحادث',

        'admin'              => 'المسؤول',
        'no_driver'          => 'لا يوجد سائق مرتبط بالمركبة',

        'accident_info'      => 'بيانات الحادث',
        'vehicle_info'       => 'بيانات المركبة',

        'sar'                => 'ريال',

        'ar'                 => 'العربية',
        'en'                 => 'English'
    ],

    'en' => [

        'title'              => 'Register New Accident',
        'subtitle'           => 'Add and register an accident for a vehicle and driver',

        'vehicle'            => 'Vehicle',
        'select_vehicle'     => 'Select Vehicle',

        'plate'              => 'Plate Number',
        'model'              => 'Model',

        'driver'             => 'Driver',
        'select_driver'      => 'Select Driver',

        'date'               => 'Accident Date & Time',
        'location'           => 'Accident Location',
        'location_placeholder' => 'Enter accident location',

        'desc'               => 'Accident Description',
        'desc_placeholder'   => 'Enter accident details',

        'cost'               => 'Damage Cost',
        'cost_placeholder'   => '0.00',

        'save'               => 'Save Accident',
        'cancel'             => 'Cancel',

        'back'               => 'Back to Accidents',

        'success'            => 'Accident registered successfully',

        'required_vehicle'   => 'Please select a vehicle',
        'required_driver'    => 'Please select a driver',
        'required_date'      => 'Please select accident date',
        'invalid_vehicle'    => 'Invalid vehicle selected',
        'invalid_driver'     => 'Invalid driver selected',
        'invalid_cost'       => 'Invalid damage cost',

        'error'              => 'An error occurred while saving the accident',

        'admin'              => 'Administrator',
        'no_driver'          => 'No driver is linked to this vehicle',

        'accident_info'      => 'Accident Information',
        'vehicle_info'       => 'Vehicle Information',

        'sar'                => 'SAR',

        'ar'                 => 'العربية',
        'en'                 => 'English'
    ]
];

$t = $words[$lang];

$dir = ($lang === 'ar') ? 'rtl' : 'ltr';

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['accident_csrf'])) {
    $_SESSION['accident_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['accident_csrf'];

/* =========================================================
   متغيرات النموذج
========================================================= */

$vehicle_id = 0;
$driver_id = 0;
$accident_date = '';
$location = '';
$description = '';
$damage_cost = '0';

$error = '';
$success = '';

/* =========================================================
   جلب بيانات المسؤول
========================================================= */

$adminName = '';
$adminEmail = '';

$stmtAdmin = $con->prepare("
    SELECT name, email
    FROM admin
    WHERE id = ?
    LIMIT 1
");

if ($stmtAdmin) {

    $stmtAdmin->bind_param(
        "i",
        $admin_id
    );

    $stmtAdmin->execute();

    $adminData = $stmtAdmin
        ->get_result()
        ->fetch_assoc();

    if ($adminData) {

        $adminName = $adminData['name'] ?? '';
        $adminEmail = $adminData['email'] ?? '';
    }

    $stmtAdmin->close();
}

/* =========================================================
   معرفة هل fleet يحتوي driver_id
========================================================= */

$fleetHasDriverId = false;

$checkColumn = $con->query("
    SHOW COLUMNS FROM fleet LIKE 'driver_id'
");

if ($checkColumn && $checkColumn->num_rows > 0) {
    $fleetHasDriverId = true;
}

/* =========================================================
   جلب المركبات
========================================================= */

$fleet = [];

if ($fleetHasDriverId) {

    $fleetResult = $con->query("
        SELECT
            f.id,
            f.plate,
            f.model,
            f.driver_id
        FROM fleet f
        ORDER BY f.plate ASC
    ");

} else {

    $fleetResult = $con->query("
        SELECT
            f.id,
            f.plate,
            f.model
        FROM fleet f
        ORDER BY f.plate ASC
    ");
}

if ($fleetResult) {

    while ($row = $fleetResult->fetch_assoc()) {
        $fleet[] = $row;
    }
}

/* =========================================================
   جلب السائقين
========================================================= */

$drivers = [];

$driversResult = $con->query("
    SELECT
        id,
        name
    FROM drivers
    ORDER BY name ASC
");

if ($driversResult) {

    while ($row = $driversResult->fetch_assoc()) {
        $drivers[] = $row;
    }
}

/* =========================================================
   حفظ الحادث
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* -----------------------------------------------------
       CSRF
    ----------------------------------------------------- */

    $postedCsrf = $_POST['csrf_token'] ?? '';

    if (
        empty($postedCsrf) ||
        !hash_equals($csrf, $postedCsrf)
    ) {

        $error = $lang === 'ar'
            ? 'جلسة النموذج غير صالحة، يرجى إعادة تحميل الصفحة.'
            : 'Invalid form session. Please reload the page.';

    } else {

        /* -------------------------------------------------
           استقبال البيانات
        ------------------------------------------------- */

        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

        $driver_id = (int)($_POST['driver_id'] ?? 0);

        $accident_date = trim(
            $_POST['accident_date'] ?? ''
        );

        $location = trim(
            $_POST['location'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $damage_cost = trim(
            $_POST['damage_cost'] ?? '0'
        );

        /* -------------------------------------------------
           التحقق
        ------------------------------------------------- */

        if ($vehicle_id <= 0) {

            $error = $t['required_vehicle'];

        } elseif ($driver_id <= 0) {

            $error = $t['required_driver'];

        } elseif ($accident_date === '') {

            $error = $t['required_date'];

        } elseif (
            $damage_cost === '' ||
            !is_numeric($damage_cost) ||
            (float)$damage_cost < 0
        ) {

            $error = $t['invalid_cost'];

        } else {

            /* ---------------------------------------------
               التأكد من المركبة
            --------------------------------------------- */

            $vehicleData = null;

            if ($fleetHasDriverId) {

                $stmtVehicle = $con->prepare("
                    SELECT
                        id,
                        plate,
                        model,
                        driver_id
                    FROM fleet
                    WHERE id = ?
                    LIMIT 1
                ");

            } else {

                $stmtVehicle = $con->prepare("
                    SELECT
                        id,
                        plate,
                        model
                    FROM fleet
                    WHERE id = ?
                    LIMIT 1
                ");
            }

            if ($stmtVehicle) {

                $stmtVehicle->bind_param(
                    "i",
                    $vehicle_id
                );

                $stmtVehicle->execute();

                $vehicleData = $stmtVehicle
                    ->get_result()
                    ->fetch_assoc();

                $stmtVehicle->close();
            }

            if (!$vehicleData) {

                $error = $t['invalid_vehicle'];

            } else {

                /* -----------------------------------------
                   التأكد من السائق
                ----------------------------------------- */

                $driverData = null;

                $stmtDriver = $con->prepare("
                    SELECT
                        id,
                        name
                    FROM drivers
                    WHERE id = ?
                    LIMIT 1
                ");

                if ($stmtDriver) {

                    $stmtDriver->bind_param(
                        "i",
                        $driver_id
                    );

                    $stmtDriver->execute();

                    $driverData = $stmtDriver
                        ->get_result()
                        ->fetch_assoc();

                    $stmtDriver->close();
                }

                if (!$driverData) {

                    $error = $t['invalid_driver'];

                } else {

                    /* -------------------------------------
                       تحويل التاريخ
                    ------------------------------------- */

                    $accidentDateDb = str_replace(
                        'T',
                        ' ',
                        $accident_date
                    );

                    /* -------------------------------------
                       تحويل التكلفة
                    ------------------------------------- */

                    $damageCost = (float)$damage_cost;

                    /* -------------------------------------
                       حفظ
                    ------------------------------------- */

                    $stmt = $con->prepare("
                        INSERT INTO accidents
                        (
                            vehicle_id,
                            driver_id,
                            accident_date,
                            location,
                            description,
                            damage_cost,
                            created_by,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, 'Open')
                    ");

                    if (!$stmt) {

                        $error = $t['error']
                            . ' - '
                            . $con->error;

                    } else {

                        $stmt->bind_param(
                            "iisssdi",
                            $vehicle_id,
                            $driver_id,
                            $accidentDateDb,
                            $location,
                            $description,
                            $damageCost,
                            $admin_id
                        );

                        if ($stmt->execute()) {

                            $accident_id =
                                (int)$con->insert_id;

                            $stmt->close();

                            /* ---------------------------------
                               إشعار النظام
                            --------------------------------- */

                            $notificationTitle =
                                $lang === 'ar'
                                    ? '🚨 حادث جديد'
                                    : '🚨 New Accident';

                            $notificationMessage =
                                $lang === 'ar'
                                    ? 'تم تسجيل حادث جديد بواسطة '
                                      . $adminName
                                    : 'A new accident was registered by '
                                      . $adminName;

                            $notificationType =
                                'accident';

                            $notif = $con->prepare("
                                INSERT INTO notifications
                                (
                                    title,
                                    message,
                                    type,
                                    ref_id
                                )
                                VALUES (?, ?, ?, ?)
                            ");

                            if ($notif) {

                                $notif->bind_param(
                                    "sssi",
                                    $notificationTitle,
                                    $notificationMessage,
                                    $notificationType,
                                    $accident_id
                                );

                                $notif->execute();

                                $notif->close();
                            }

                            /* ---------------------------------
                               إرسال البريد
                            --------------------------------- */

                            if (
                                !empty($adminEmail) &&
                                function_exists('sendMail')
                            ) {

                                $subject =
                                    $lang === 'ar'
                                        ? '🚨 New Accident'
                                        : '🚨 New Accident';

                                $vehiclePlate =
                                    htmlspecialchars(
                                        $vehicleData['plate'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $driverName =
                                    htmlspecialchars(
                                        $driverData['name'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $safeLocation =
                                    htmlspecialchars(
                                        $location,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $safeDescription =
                                    nl2br(
                                        htmlspecialchars(
                                            $description,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    );

                                $body = "

                                <div dir='rtl'
                                     style='font-family:Tahoma,Arial'>

                                    <h2>
                                        🚨 تسجيل حادث جديد
                                    </h2>

                                    <p>
                                        مرحباً
                                        {$adminName}
                                    </p>

                                    <hr>

                                    <p>
                                        <strong>رقم الحادث:</strong>
                                        {$accident_id}
                                    </p>

                                    <p>
                                        <strong>المركبة:</strong>
                                        {$vehiclePlate}
                                    </p>

                                    <p>
                                        <strong>السائق:</strong>
                                        {$driverName}
                                    </p>

                                    <p>
                                        <strong>التاريخ:</strong>
                                        {$accidentDateDb}
                                    </p>

                                    <p>
                                        <strong>الموقع:</strong>
                                        {$safeLocation}
                                    </p>

                                    <p>
                                        <strong>التكلفة:</strong>
                                        {$damageCost}
                                        ريال
                                    </p>

                                    <p>
                                        <strong>الوصف:</strong>
                                        <br>
                                        {$safeDescription}
                                    </p>

                                </div>
                                ";

                                try {

                                    sendMail(
                                        $adminEmail,
                                        $subject,
                                        $body
                                    );

                                } catch (Throwable $mailError) {

                                    /* لا نوقف حفظ الحادث
                                       بسبب مشكلة البريد */
                                }
                            }

                            /* ---------------------------------
                               تحويل بعد النجاح
                            --------------------------------- */

                            header(
                                "Location: accidents.php?lang="
                                . urlencode($lang)
                                . "&success=1"
                            );

                            exit();

                        } else {

                            $error =
                                $t['error']
                                . ' - '
                                . $stmt->error;

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
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
    <?= htmlspecialchars($t['title']) ?>
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<style>

:root {

    --primary:#2563eb;
    --success:#198754;
    --danger:#dc3545;
    --bg:#f4f6f9;
    --card:#ffffff;
    --text:#1f2937;
    --muted:#6b7280;
    --border:#e5e7eb;

}

body {

    margin:0;

    background:var(--bg);

    color:var(--text);

    font-family:
        Tahoma,
        Arial,
        sans-serif;

}

.page-wrapper {

    max-width:1100px;

    margin:40px auto;

    padding:0 20px;

}

.page-header {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-bottom:25px;

}

.page-title {

    display:flex;

    align-items:center;

    gap:15px;

}

.page-title-icon {

    width:55px;

    height:55px;

    border-radius:15px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#fee2e2;

    color:#dc2626;

    font-size:27px;

}

.page-title h2 {

    margin:0;

    font-size:27px;

    font-weight:700;

}

.page-title p {

    margin:5px 0 0;

    color:var(--muted);

    font-size:14px;

}

.header-actions {

    display:flex;

    gap:8px;

    flex-wrap:wrap;

}

.main-card {

    background:var(--card);

    border-radius:18px;

    box-shadow:
        0 8px 30px rgba(0,0,0,.07);

    border:1px solid var(--border);

    overflow:hidden;

}

.card-header-custom {

    padding:20px 25px;

    border-bottom:1px solid var(--border);

    font-size:17px;

    font-weight:bold;

}

.card-body-custom {

    padding:25px;

}

.form-label {

    font-weight:600;

    margin-bottom:7px;

}

.form-control,
.form-select {

    min-height:45px;

    border-radius:9px;

    border:1px solid #d1d5db;

}

.form-control:focus,
.form-select:focus {

    border-color:var(--primary);

    box-shadow:
        0 0 0 .2rem rgba(37,99,235,.12);

}

.vehicle-info {

    display:none;

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-radius:10px;

    padding:12px 15px;

    margin-top:10px;

}

.vehicle-info-item {

    display:inline-flex;

    align-items:center;

    gap:6px;

    margin-left:18px;

    margin-bottom:4px;

    font-size:13px;

}

textarea.form-control {

    min-height:120px;

    resize:vertical;

}

.save-btn {

    min-width:170px;

    min-height:45px;

}

@media(max-width:768px) {

    .page-header {

        flex-direction:column;

        align-items:flex-start;

    }

    .header-actions {

        width:100%;

    }

    .header-actions .btn {

        flex:1;

    }

    .page-title h2 {

        font-size:22px;

    }

}

</style>

</head>

<body>

<div class="page-wrapper">

    <!-- =================================================
         Header
    ================================================= -->

    <div class="page-header">

        <div class="page-title">

            <div class="page-title-icon">

                <i class="bi bi-car-front-fill"></i>

            </div>

            <div>

                <h2>
                    <?= htmlspecialchars($t['title']) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($t['subtitle']) ?>
                </p>

            </div>

        </div>

        <div class="header-actions">

            <a
                href="accidents.php?lang=<?= urlencode($lang) ?>"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-arrow-right"></i>

                <?= htmlspecialchars($t['back']) ?>

            </a>

            <a
                href="?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"
                class="btn btn-outline-primary"
            >

                <?= $lang === 'ar'
                    ? 'English'
                    : 'العربية'
                ?>

            </a>

        </div>

    </div>

    <!-- =================================================
         Error
    ================================================= -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= htmlspecialchars($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <!-- =================================================
         Form
    ================================================= -->

    <form
        method="POST"
        id="accidentForm"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrf) ?>"
        >

        <div class="main-card">

            <div class="card-header-custom">

                <i class="bi bi-clipboard2-pulse text-danger"></i>

                <?= htmlspecialchars($t['accident_info']) ?>

            </div>

            <div class="card-body-custom">

                <div class="row g-4">

                    <!-- =================================
                         Vehicle
                    ================================== -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <?= htmlspecialchars($t['vehicle']) ?>

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="vehicle_id"
                            id="vehicle_id"
                            class="form-select"
                            required
                        >

                            <option value="">

                                <?= htmlspecialchars(
                                    $t['select_vehicle']
                                ) ?>

                            </option>

                            <?php foreach ($fleet as $f): ?>

                                <option
                                    value="<?= (int)$f['id'] ?>"
                                    data-plate="<?= htmlspecialchars(
                                        $f['plate'] ?? '',
                                        ENT_QUOTES
                                    ) ?>"
                                    data-model="<?= htmlspecialchars(
                                        $f['model'] ?? '',
                                        ENT_QUOTES
                                    ) ?>"
                                    data-driver="<?= $fleetHasDriverId
                                        ? (int)($f['driver_id'] ?? 0)
                                        : 0 ?>"
                                    <?= $vehicle_id == $f['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $f['plate'] ?? '-'
                                    ) ?>

                                    <?php if (!empty($f['model'])): ?>

                                        -
                                        <?= htmlspecialchars(
                                            $f['model']
                                        ) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div
                            class="vehicle-info"
                            id="vehicleInfo"
                        >

                            <div class="vehicle-info-item">

                                <i class="bi bi-credit-card"></i>

                                <strong>
                                    <?= htmlspecialchars($t['plate']) ?>:
                                </strong>

                                <span id="vehiclePlate">-</span>

                            </div>

                            <div class="vehicle-info-item">

                                <i class="bi bi-car-front"></i>

                                <strong>
                                    <?= htmlspecialchars($t['model']) ?>:
                                </strong>

                                <span id="vehicleModel">-</span>

                            </div>

                        </div>

                    </div>

                    <!-- =================================
                         Driver
                    ================================== -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <?= htmlspecialchars($t['driver']) ?>

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="driver_id"
                            id="driver_id"
                            class="form-select"
                            required
                        >

                            <option value="">

                                <?= htmlspecialchars(
                                    $t['select_driver']
                                ) ?>

                            </option>

                            <?php foreach ($drivers as $d): ?>

                                <option
                                    value="<?= (int)$d['id'] ?>"
                                    <?= $driver_id == $d['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $d['name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (!$fleetHasDriverId): ?>

                            <div class="form-text">

                                <i class="bi bi-info-circle"></i>

                                <?= $lang === 'ar'
                                    ? 'يمكنك اختيار السائق المسؤول عن المركبة.'
                                    : 'Select the driver responsible for the vehicle.'
                                ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- =================================
                         Date
                    ================================== -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <?= htmlspecialchars($t['date']) ?>

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="datetime-local"
                            name="accident_date"
                            value="<?= htmlspecialchars(
                                $accident_date
                            ) ?>"
                            class="form-control"
                            required
                        >

                    </div>

                    <!-- =================================
                         Location
                    ================================== -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <?= htmlspecialchars($t['location']) ?>

                        </label>

                        <input
                            type="text"
                            name="location"
                            value="<?= htmlspecialchars(
                                $location
                            ) ?>"
                            class="form-control"
                            placeholder="<?= htmlspecialchars(
                                $t['location_placeholder']
                            ) ?>"
                        >

                    </div>

                    <!-- =================================
                         Cost
                    ================================== -->

                    <div class="col-md-6">

                        <label class="form-label">

                            <?= htmlspecialchars($t['cost']) ?>

                        </label>

                        <div class="input-group">

                            <input
                                type="number"
                                name="damage_cost"
                                value="<?= htmlspecialchars(
                                    $damage_cost
                                ) ?>"
                                class="form-control"
                                min="0"
                                step="0.01"
                                placeholder="<?= htmlspecialchars(
                                    $t['cost_placeholder']
                                ) ?>"
                            >

                            <span class="input-group-text">

                                <?= htmlspecialchars($t['sar']) ?>

                            </span>

                        </div>

                    </div>

                    <!-- =================================
                         Description
                    ================================== -->

                    <div class="col-12">

                        <label class="form-label">

                            <?= htmlspecialchars($t['desc']) ?>

                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            placeholder="<?= htmlspecialchars(
                                $t['desc_placeholder']
                            ) ?>"
                        ><?= htmlspecialchars(
                            $description
                        ) ?></textarea>

                    </div>

                </div>

            </div>

        </div>

        <!-- =============================================
             Buttons
        ============================================== -->

        <div class="d-flex justify-content-between align-items-center mt-4">

            <a
                href="accidents.php?lang=<?= urlencode($lang) ?>"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-x-circle"></i>

                <?= htmlspecialchars($t['cancel']) ?>

            </a>

            <button
                type="submit"
                name="save"
                id="saveButton"
                class="btn btn-success save-btn"
            >

                <i class="bi bi-check-circle"></i>

                <?= htmlspecialchars($t['save']) ?>

            </button>

        </div>

    </form>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>

/* =========================================================
   بيانات المركبة
========================================================= */

const vehicleSelect =
    document.getElementById('vehicle_id');

const driverSelect =
    document.getElementById('driver_id');

const vehicleInfo =
    document.getElementById('vehicleInfo');

const vehiclePlate =
    document.getElementById('vehiclePlate');

const vehicleModel =
    document.getElementById('vehicleModel');


function updateVehicleInfo() {

    const option =
        vehicleSelect.options[
            vehicleSelect.selectedIndex
        ];

    if (
        !option ||
        !option.value
    ) {

        vehicleInfo.style.display = 'none';

        return;
    }

    const plate =
        option.dataset.plate || '-';

    const model =
        option.dataset.model || '-';

    const driver =
        parseInt(
            option.dataset.driver || '0',
            10
        );

    vehiclePlate.textContent = plate;

    vehicleModel.textContent = model;

    vehicleInfo.style.display = 'block';

    /*
     * إذا كان للمركبة سائق مرتبط
     * يتم اختياره تلقائياً.
     */

    if (
        driver > 0 &&
        driverSelect.querySelector(
            'option[value="' + driver + '"]'
        )
    ) {

        driverSelect.value = driver;

    }

}


/* عند تغيير المركبة */

vehicleSelect.addEventListener(
    'change',
    updateVehicleInfo
);


/* عند فتح الصفحة */

updateVehicleInfo();


/* =========================================================
   منع الضغط مرتين على حفظ
========================================================= */

const accidentForm =
    document.getElementById('accidentForm');

const saveButton =
    document.getElementById('saveButton');

accidentForm.addEventListener(
    'submit',
    function () {

        if (!accidentForm.checkValidity()) {
            return;
        }

        saveButton.disabled = true;

        saveButton.innerHTML =
            '<span class="spinner-border spinner-border-sm"></span> '
            + '<?= $lang === 'ar'
                ? 'جاري الحفظ...'
                : 'Saving...' ?>';

    }
);

</script>

</body>

</html>