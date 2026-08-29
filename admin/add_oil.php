<?php

session_start();

require_once __DIR__ . '/../include/connected.php';


/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}


/* =========================================================
   الوضع الليلي
========================================================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$dark = $_SESSION['theme'] ?? 0;


/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title' => 'إضافة تغيير زيت',

        'driver' => 'السائق',
        'select_driver' => 'اختر السائق',

        'vehicle' => 'المركبة',
        'plate' => 'رقم اللوحة',
        'vehicle_type' => 'نوع المركبة',
        'vehicle_model' => 'الموديل',

        'select_driver_first' =>
            'اختر السائق أولاً',

        'vehicle_loading' =>
            'جاري البحث عن المركبة...',

        'vehicle_found' =>
            'تم تحديد المركبة',

        'vehicle_not_found' =>
            'لا توجد مركبة مرتبطة بهذا السائق',

        'oil_type' => 'نوع الزيت',
        'oil_type_placeholder' =>
            'مثال: زيت ماكينه 5W-30',

        'change_date' => 'تاريخ تغيير الزيت',

        'current_km' => 'العداد الحالي',

        'next_change' => 'التغيير القادم',

        'next_km' => 'العداد القادم',

        'cost' => 'التكلفة',

        'notes' => 'الملاحظات',

        'save' => 'حفظ',

        'cancel' => 'إلغاء',

        'back' => 'العودة لسجلات الزيوت',

        'required' =>
            'يرجى تعبئة جميع الحقول المطلوبة',

        'driver_required' =>
            'يرجى اختيار السائق',

        'vehicle_required' =>
            'يرجى اختيار سائق لديه مركبة مرتبطة به',

        'invalid_date' =>
            'تاريخ تغيير الزيت غير صحيح',

        'save_success' =>
            'تم حفظ سجل تغيير الزيت بنجاح',

        'save_error' =>
            'حدث خطأ أثناء حفظ سجل تغيير الزيت',

        'sar' => 'ريال',

        'day' => 'يوم',

        'automatic' =>
            'يتم حساب التغيير القادم والعداد القادم تلقائياً',

    ],

    'en' => [

        'title' => 'Add Oil Change',

        'driver' => 'Driver',
        'select_driver' => 'Select Driver',

        'vehicle' => 'Vehicle',
        'plate' => 'Plate',

        'vehicle_type' => 'Vehicle Type',
        'vehicle_model' => 'Model',

        'select_driver_first' =>
            'Select driver first',

        'vehicle_loading' =>
            'Searching for vehicle...',

        'vehicle_found' =>
            'Vehicle selected',

        'vehicle_not_found' =>
            'No vehicle is linked to this driver',

        'oil_type' => 'Oil Type',

        'oil_type_placeholder' =>
            'Example: Engine Oil 5W-30',

        'change_date' => 'Oil Change Date',

        'current_km' => 'Current KM',

        'next_change' => 'Next Change',

        'next_km' => 'Next KM',

        'cost' => 'Cost',

        'notes' => 'Notes',

        'save' => 'Save',

        'cancel' => 'Cancel',

        'back' => 'Back to Oil Records',

        'required' =>
            'Please fill all required fields',

        'driver_required' =>
            'Please select a driver',

        'vehicle_required' =>
            'Please select a driver with a linked vehicle',

        'invalid_date' =>
            'Invalid oil change date',

        'save_success' =>
            'Oil change record saved successfully',

        'save_error' =>
            'An error occurred while saving the oil change record',

        'sar' => 'SAR',

        'day' => 'Day',

        'automatic' =>
            'Next change date and next KM are calculated automatically',

    ]

];

$t = $text[$lang];


/* =========================================================
   العودة إلى صفحة السجلات
========================================================= */

$backUrl = '../oile.php?lang=' . urlencode($lang);


/* =========================================================
   AJAX
   جلب المركبة حسب السائق
========================================================= */

if (isset($_GET['get_vehicle'])) {

    header('Content-Type: application/json; charset=utf-8');

    $driverId = (int)$_GET['get_vehicle'];

    if ($driverId <= 0) {

        echo json_encode([
            'success' => false,
            'message' => $t['vehicle_not_found']
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /* -----------------------------------------------------
       جلب اسم السائق
    ----------------------------------------------------- */

    $stmt = $con->prepare("
        SELECT
            id,
            name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Database Error: ' . $con->error
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt->bind_param('i', $driverId);

    $stmt->execute();

    $result = $stmt->get_result();

    $driverRow = $result->fetch_assoc();

    $stmt->close();


    if (!$driverRow) {

        echo json_encode([
            'success' => false,
            'message' => $t['vehicle_not_found']
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $driverName = trim($driverRow['name']);


    /* -----------------------------------------------------
       البحث عن المركبة في fleet

       حسب هيكل جدول fleet الذي أرسلته:
       fleet.driver = اسم السائق
    ----------------------------------------------------- */

    $stmt = $con->prepare("
        SELECT
            id,
            driver,
            plate,
            typefleet,
            model,
            colorfleet
        FROM fleet
        WHERE TRIM(driver) = TRIM(?)
        LIMIT 1
    ");

    if (!$stmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Database Error: ' . $con->error
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $stmt->bind_param('s', $driverName);

    $stmt->execute();

    $result = $stmt->get_result();

    $fleetRow = $result->fetch_assoc();

    $stmt->close();


    if (!$fleetRow) {

        echo json_encode([
            'success' => false,
            'message' => $t['vehicle_not_found']
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /* -----------------------------------------------------
       إرجاع بيانات المركبة
    ----------------------------------------------------- */

    echo json_encode([

        'success' => true,

        'vehicle' => [

            'id' =>
                (int)$fleetRow['id'],

            'driver' =>
                $fleetRow['driver'],

            'plate' =>
                $fleetRow['plate'],

            'typefleet' =>
                $fleetRow['typefleet'],

            'model' =>
                $fleetRow['model'],

            'colorfleet' =>
                $fleetRow['colorfleet']

        ]

    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =========================================================
   المتغيرات
========================================================= */

$error = '';

$oldDriverId = 0;

$oldCarId = 0;

$oldOilType = '';

$oldChangeDate = date('Y-m-d');

$oldCurrentKm = '';

$oldCost = '';

$oldNotes = '';

$vehicle = null;


/* =========================================================
   حفظ السجل
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save'])) {


    /* -----------------------------------------------------
       استقبال البيانات
    ----------------------------------------------------- */

    $oldDriverId =
        (int)($_POST['driver_id'] ?? 0);

    $oldCarId =
        (int)($_POST['car_id'] ?? 0);

    $oldOilType =
        trim($_POST['oil_type'] ?? '');

    $oldChangeDate =
        trim($_POST['change_date'] ?? '');

    $oldCurrentKm =
        (int)($_POST['current_km'] ?? 0);

    $oldCost =
        (float)($_POST['cost'] ?? 0);

    $oldNotes =
        trim($_POST['notes'] ?? '');


    /* -----------------------------------------------------
       التحقق من السائق
    ----------------------------------------------------- */

    if ($oldDriverId <= 0) {

        $error = $t['driver_required'];

    }


    /* -----------------------------------------------------
       التحقق من المركبة
    ----------------------------------------------------- */

    elseif ($oldCarId <= 0) {

        $error = $t['vehicle_required'];

    }


    /* -----------------------------------------------------
       التحقق من التاريخ
    ----------------------------------------------------- */

    elseif ($oldChangeDate === '') {

        $error = $t['invalid_date'];

    }


    /* -----------------------------------------------------
       التحقق من العداد
    ----------------------------------------------------- */

    elseif ($oldCurrentKm < 0) {

        $error = $t['required'];

    }


    else {


        /* =================================================
           جلب السائق
        ================================================= */

        $stmtDriver = $con->prepare("
            SELECT
                id,
                name
            FROM drivers
            WHERE id = ?
            LIMIT 1
        ");


        if (!$stmtDriver) {

            $error = $con->error;

        } else {


            $stmtDriver->bind_param(
                'i',
                $oldDriverId
            );

            $stmtDriver->execute();

            $resultDriver =
                $stmtDriver->get_result();

            $driverRow =
                $resultDriver->fetch_assoc();

            $stmtDriver->close();


            if (!$driverRow) {

                $error = $t['driver_required'];

            } else {


                $driverName =
                    trim($driverRow['name']);


                /* =================================================
                   التأكد من أن المركبة تخص السائق
                ================================================= */

                $stmtFleet = $con->prepare("
                    SELECT
                        id,
                        driver,
                        plate,
                        typefleet,
                        model,
                        colorfleet
                    FROM fleet
                    WHERE id = ?
                    AND TRIM(driver) = TRIM(?)
                    LIMIT 1
                ");


                if (!$stmtFleet) {

                    $error = $con->error;

                } else {


                    $stmtFleet->bind_param(
                        'is',
                        $oldCarId,
                        $driverName
                    );

                    $stmtFleet->execute();

                    $resultFleet =
                        $stmtFleet->get_result();

                    $vehicle =
                        $resultFleet->fetch_assoc();

                    $stmtFleet->close();


                    if (!$vehicle) {

                        $error = $t['vehicle_required'];

                    } else {


                        /* =================================================
                           الحسابات التلقائية
                        ================================================= */

                        $nextChange =
                            date(
                                'Y-m-d',
                                strtotime(
                                    $oldChangeDate .
                                    ' +30 days'
                                )
                            );


                        $nextKm =
                            $oldCurrentKm + 5000;


                        /* =================================================
                           حفظ البيانات
                        ================================================= */

                        $stmtInsert = $con->prepare("
                            INSERT INTO oil_changes
                            (
                                car_id,
                                driver,
                                oil_type,
                                change_date,
                                next_change,
                                notes,
                                km_change,
                                cost,
                                next_km,
                                driver_id,
                                current_km
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )
                        ");


                        if (!$stmtInsert) {

                            $error =
                                $con->error;

                        } else {


                            /*
                             * عدد المتغيرات = 11
                             *
                             * 1  car_id       i
                             * 2  driver       s
                             * 3  oil_type     s
                             * 4  change_date  s
                             * 5  next_change  s
                             * 6  notes        s
                             * 7  km_change    i
                             * 8  cost         d
                             * 9  next_km      i
                             * 10 driver_id    i
                             * 11 current_km   i
                             *
                             * type string:
                             *
                             * isssssidi ii
                             *
                             * بدون مسافات:
                             *
                             * isssssidiii
                             */

                            $stmtInsert->bind_param(
                                'isssssidiii',
                                $oldCarId,
                                $driverName,
                                $oldOilType,
                                $oldChangeDate,
                                $nextChange,
                                $oldNotes,
                                $oldCurrentKm,
                                $oldCost,
                                $nextKm,
                                $oldDriverId,
                                $oldCurrentKm
                            );


                            if ($stmtInsert->execute()) {

                                $stmtInsert->close();


                                /* =================================================
                                   نجاح الحفظ
                                ================================================= */

                                header(
                                    'Location: ' .
                                    $backUrl
                                );

                                exit;

                            } else {

                                $error =
                                    $stmtInsert->error;

                                $stmtInsert->close();
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   جلب السائقين
========================================================= */

$drivers = [];

$resultDrivers = $con->query("
    SELECT
        id,
        name
    FROM drivers
    ORDER BY name ASC
");


if ($resultDrivers) {

    while ($driver = $resultDrivers->fetch_assoc()) {

        $drivers[] = $driver;
    }
}

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
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
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background:
                <?= $dark ? '#121212' : '#f4f6f9' ?>;

            color:
                <?= $dark ? '#fff' : '#212529' ?>;

            font-family:
                Arial,
                Tahoma,
                sans-serif;
        }


        .page-wrapper {

            max-width: 900px;

            margin:
                40px auto;

            padding:
                0 15px;
        }


        .main-card {

            background:
                <?= $dark ? '#1f1f1f' : '#fff' ?>;

            border-radius:
                18px;

            border:
                none;

            box-shadow:
                0 8px 30px
                rgba(0,0,0,.08);

            overflow:
                hidden;
        }


        .card-header-custom {

            padding:
                22px 25px;

            border-bottom:
                1px solid
                <?= $dark ? '#333' : '#eee' ?>;
        }


        .form-section {

            padding:
                25px;
        }


        .form-label {

            font-weight:
                600;

            margin-bottom:
                8px;
        }


        .form-control,
        .form-select {

            min-height:
                46px;

            border-radius:
                10px;
        }


        .vehicle-box {

            border:
                1px solid
                <?= $dark ? '#444' : '#dfe3e8' ?>;

            background:
                <?= $dark ? '#292929' : '#f8f9fa' ?>;

            border-radius:
                14px;

            padding:
                18px;

            margin-top:
                10px;

            display:
                none;
        }


        .vehicle-box.show {

            display:
                block;
        }


        .vehicle-plate {

            display:
                inline-block;

            background:
                <?= $dark ? '#343434' : '#e9ecef' ?>;

            border-radius:
                8px;

            padding:
                8px 15px;

            font-size:
                18px;

            font-weight:
                bold;
        }


        .vehicle-status {

            margin-top:
                10px;
        }


        .automatic-box {

            background:
                <?= $dark ? '#252525' : '#eef7ff' ?>;

            border-radius:
                10px;

            padding:
                12px 15px;

            margin-top:
                8px;

            font-size:
                14px;
        }


        .actions {

            padding-top:
                20px;

            margin-top:
                20px;

            border-top:
                1px solid
                <?= $dark ? '#333' : '#eee' ?>;
        }


        .loading {

            display:
                none;

            font-size:
                14px;
        }


        .required-star {

            color:
                #dc3545;
        }

    </style>

</head>


<body>


<div class="page-wrapper">


    <!-- =====================================================
         العنوان
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

        <h2 class="fw-bold mb-0">

            <i class="bi bi-droplet-half"></i>

            <?= htmlspecialchars($t['title']) ?>

        </h2>


        <div class="d-flex gap-2">

            <!-- اللغة -->

            <a
                href="?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"
                class="btn btn-outline-secondary btn-sm"
            >

                <?= $lang === 'ar' ? 'EN' : 'AR' ?>

            </a>


            <!-- الوضع الليلي -->

            <?php if ($dark): ?>

                <a
                    href="?lang=<?= urlencode($lang) ?>&theme=0"
                    class="btn btn-light btn-sm"
                >

                    <i class="bi bi-sun"></i>

                </a>

            <?php else: ?>

                <a
                    href="?lang=<?= urlencode($lang) ?>&theme=1"
                    class="btn btn-dark btn-sm"
                >

                    <i class="bi bi-moon"></i>

                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         البطاقة الرئيسية
    ====================================================== -->

    <div class="main-card">


        <div class="card-header-custom">

            <h5 class="mb-1 fw-bold">

                <i class="bi bi-pencil-square"></i>

                <?= htmlspecialchars($t['title']) ?>

            </h5>

        </div>


        <div class="form-section">


            <!-- =================================================
                 رسالة الخطأ
            ================================================== -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <i class="bi bi-exclamation-triangle"></i>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 النموذج
            ================================================== -->

            <form
                method="POST"
                action=""
                id="oilForm"
            >


                <!-- =================================================
                     السائق
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['driver'] ?>

                        <span class="required-star">*</span>

                    </label>


                    <select
                        name="driver_id"
                        id="driver_id"
                        class="form-select"
                        required
                    >

                        <option value="">

                            <?= $t['select_driver'] ?>

                        </option>


                        <?php foreach ($drivers as $driver): ?>

                            <option
                                value="<?= (int)$driver['id'] ?>"
                                <?= $oldDriverId == $driver['id'] ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $driver['name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <div
                        id="vehicleLoading"
                        class="loading text-primary mt-2"
                    >

                        <i class="bi bi-hourglass-split"></i>

                        <?= $t['vehicle_loading'] ?>

                    </div>

                </div>


                <!-- =================================================
                     السيارة المخفية
                ================================================== -->

                <input
                    type="hidden"
                    name="car_id"
                    id="car_id"
                    value="<?= (int)$oldCarId ?>"
                >


                <!-- =================================================
                     معلومات المركبة
                ================================================== -->

                <div
                    id="vehicleBox"
                    class="vehicle-box <?= $vehicle ? 'show' : '' ?>"
                >

                    <div class="row g-3">


                        <div class="col-md-6">

                            <div class="text-muted mb-1">

                                <?= $t['vehicle'] ?>

                            </div>

                            <div
                                id="vehiclePlate"
                                class="vehicle-plate"
                            >

                                <?= $vehicle
                                    ? htmlspecialchars($vehicle['plate'])
                                    : '-'
                                ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted mb-1">

                                <?= $t['driver'] ?>

                            </div>

                            <strong id="vehicleDriver">

                                -

                            </strong>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted mb-1">

                                <?= $t['vehicle_type'] ?>

                            </div>

                            <strong id="vehicleType">

                                -

                            </strong>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted mb-1">

                                <?= $t['vehicle_model'] ?>

                            </div>

                            <strong id="vehicleModel">

                                -

                            </strong>

                        </div>


                    </div>


                    <div
                        id="vehicleSuccess"
                        class="vehicle-status text-success"
                    >

                        <i class="bi bi-check-circle"></i>

                        <?= $t['vehicle_found'] ?>

                    </div>

                </div>


                <!-- =================================================
                     نوع الزيت
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['oil_type'] ?>

                    </label>


                    <input
                        type="text"
                        name="oil_type"
                        class="form-control"
                        value="<?= htmlspecialchars($oldOilType) ?>"
                        placeholder="<?= htmlspecialchars($t['oil_type_placeholder']) ?>"
                    >

                </div>


                <!-- =================================================
                     تاريخ تغيير الزيت
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['change_date'] ?>

                        <span class="required-star">*</span>

                    </label>


                    <input
                        type="date"
                        name="change_date"
                        id="change_date"
                        class="form-control"
                        value="<?= htmlspecialchars($oldChangeDate) ?>"
                        required
                    >

                </div>


                <!-- =================================================
                     العداد
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['current_km'] ?>

                        <span class="required-star">*</span>

                    </label>


                    <input
                        type="number"
                        name="current_km"
                        id="current_km"
                        class="form-control"
                        min="0"
                        step="1"
                        value="<?= htmlspecialchars((string)$oldCurrentKm) ?>"
                        required
                    >


                    <div class="automatic-box">

                        <i class="bi bi-info-circle"></i>

                        <?= $t['automatic'] ?>

                    </div>

                </div>


                <!-- =================================================
                     التكلفة
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['cost'] ?>

                    </label>


                    <div class="input-group">

                        <input
                            type="number"
                            name="cost"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= htmlspecialchars((string)$oldCost) ?>"
                        >

                        <span class="input-group-text">

                            <?= $t['sar'] ?>

                        </span>

                    </div>

                </div>


                <!-- =================================================
                     الملاحظات
                ================================================== -->

                <div class="mb-4">

                    <label class="form-label">

                        <?= $t['notes'] ?>

                    </label>


                    <textarea
                        name="notes"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($oldNotes) ?></textarea>

                </div>


                <!-- =================================================
                     الأزرار
                ================================================== -->

                <div class="actions d-flex gap-2 flex-wrap">


                    <button
                        type="submit"
                        name="save"
                        id="saveButton"
                        class="btn btn-success px-4"
                    >

                        <i class="bi bi-check-circle"></i>

                        <?= $t['save'] ?>

                    </button>


                    <a
                        href="<?= htmlspecialchars($backUrl) ?>"
                        class="btn btn-secondary px-4"
                    >

                        <i class="bi bi-arrow-right"></i>

                        <?= $t['cancel'] ?>

                    </a>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     JavaScript
========================================================= -->

<script>

const driverSelect =
    document.getElementById('driver_id');

const carIdInput =
    document.getElementById('car_id');

const vehicleBox =
    document.getElementById('vehicleBox');

const vehiclePlate =
    document.getElementById('vehiclePlate');

const vehicleDriver =
    document.getElementById('vehicleDriver');

const vehicleType =
    document.getElementById('vehicleType');

const vehicleModel =
    document.getElementById('vehicleModel');

const vehicleLoading =
    document.getElementById('vehicleLoading');

const saveButton =
    document.getElementById('saveButton');


/* =========================================================
   البحث عن المركبة
========================================================= */

async function loadVehicle(driverId) {


    /* -----------------------------------------------------
       عدم اختيار سائق
    ----------------------------------------------------- */

    if (!driverId) {

        carIdInput.value = '';

        vehicleBox.classList.remove('show');

        vehiclePlate.textContent = '-';

        vehicleDriver.textContent = '-';

        vehicleType.textContent = '-';

        vehicleModel.textContent = '-';

        return;
    }


    /* -----------------------------------------------------
       إظهار التحميل
    ----------------------------------------------------- */

    vehicleLoading.style.display = 'block';

    vehicleBox.classList.remove('show');

    carIdInput.value = '';


    try {


        /*
         * نفس ملف add_oil.php
         */

        const url =
            'add_oil.php?get_vehicle='
            + encodeURIComponent(driverId)
            + '&lang='
            + encodeURIComponent(
                '<?= $lang ?>'
            );


        const response =
            await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });


        const data =
            await response.json();


        vehicleLoading.style.display =
            'none';


        /* -------------------------------------------------
           المركبة موجودة
        ------------------------------------------------- */

        if (data.success && data.vehicle) {


            const vehicle =
                data.vehicle;


            /*
             * وضع ID المركبة في الحقل المخفي
             */

            carIdInput.value =
                vehicle.id;


            /*
             * عرض البيانات
             */

            vehiclePlate.textContent =
                vehicle.plate || '-';


            vehicleDriver.textContent =
                vehicle.driver || '-';


            vehicleType.textContent =
                vehicle.typefleet || '-';


            vehicleModel.textContent =
                vehicle.model || '-';


            vehicleBox.classList.add('show');


        } else {


            carIdInput.value = '';

            vehicleBox.classList.remove('show');


            alert(
                data.message ||
                '<?= htmlspecialchars($t['vehicle_not_found'], ENT_QUOTES) ?>'
            );
        }


    } catch (error) {


        vehicleLoading.style.display =
            'none';


        carIdInput.value = '';

        vehicleBox.classList.remove('show');


        console.error(error);


        alert(
            'حدث خطأ أثناء البحث عن المركبة'
        );
    }

}


/* =========================================================
   عند اختيار السائق
========================================================= */

driverSelect.addEventListener(
    'change',
    function () {

        loadVehicle(this.value);

    }
);


/* =========================================================
   عند فتح الصفحة
   إذا كان هناك سائق محدد مسبقاً
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (driverSelect.value) {

            loadVehicle(
                driverSelect.value
            );

        }

    }
);


/* =========================================================
   منع الحفظ بدون مركبة
========================================================= */

document.getElementById('oilForm')
.addEventListener(
    'submit',
    function (event) {


        if (!driverSelect.value) {

            event.preventDefault();

            alert(
                '<?= htmlspecialchars(
                    $t['driver_required'],
                    ENT_QUOTES
                ) ?>'
            );

            return;
        }


        if (!carIdInput.value) {

            event.preventDefault();

            alert(
                '<?= htmlspecialchars(
                    $t['vehicle_required'],
                    ENT_QUOTES
                ) ?>'
            );

            return;
        }


        /*
         * منع الضغط مرتين
         */

        saveButton.disabled = true;

        saveButton.innerHTML =
            '<span class="spinner-border spinner-border-sm"></span> '
            + '<?= htmlspecialchars($t['save'], ENT_QUOTES) ?>';

    }
);

</script>


</body>

</html>