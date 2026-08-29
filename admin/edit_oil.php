<?php

session_start();

include('../include/connected.php');

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

/* =========================================================
   الوضع الليلي
========================================================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = (int)$_GET['theme'];
}

$dark = (int)($_SESSION['theme'] ?? 0);

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'          => 'تعديل تغيير الزيت',
        'back'           => 'العودة',
        'cancel'         => 'إلغاء',
        'update'         => 'تحديث السجل',

        'vehicle'        => 'المركبة',
        'driver'         => 'السائق',

        'oil_type'       => 'نوع الزيت',
        'change_date'    => 'تاريخ تغيير الزيت',
        'current_km'     => 'العداد الحالي KM',
        'cost'           => 'التكلفة',
        'notes'          => 'الملاحظات',

        'next_change'    => 'التغيير القادم',
        'next_km'        => 'العداد القادم',

        'next_change_help' =>
            'يتم حسابه تلقائياً بعد 30 يوم',

        'next_km_help' =>
            'يتم حسابه تلقائياً +5000 KM',

        'invalid_id' =>
            'رقم السجل غير صحيح',

        'not_found' =>
            'سجل تغيير الزيت غير موجود',

        'sql_error' =>
            'حدث خطأ في قاعدة البيانات',

        'update_error' =>
            'حدث خطأ أثناء تحديث السجل',

        'update_success' =>
            'تم تحديث سجل تغيير الزيت بنجاح'

    ],

    'en' => [

        'title'          => 'Edit Oil Change',
        'back'           => 'Back',
        'cancel'         => 'Cancel',
        'update'         => 'Update Record',

        'vehicle'        => 'Vehicle',
        'driver'         => 'Driver',

        'oil_type'       => 'Oil Type',
        'change_date'    => 'Oil Change Date',
        'current_km'     => 'Current KM',
        'cost'           => 'Cost',
        'notes'          => 'Notes',

        'next_change'    => 'Next Change',
        'next_km'        => 'Next KM',

        'next_change_help' =>
            'Automatically calculated after 30 days',

        'next_km_help' =>
            'Automatically calculated +5000 KM',

        'invalid_id' =>
            'Invalid record ID',

        'not_found' =>
            'Oil change record not found',

        'sql_error' =>
            'Database error',

        'update_error' =>
            'An error occurred while updating the record',

        'update_success' =>
            'Oil change record updated successfully'

    ]

];

$t = $text[$lang];

/* =========================================================
   ID
========================================================= */

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    die($t['invalid_id']);
}

/* =========================================================
   تحديث السجل
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $oil_type = trim($_POST['oil_type'] ?? '');

    $change_date = trim($_POST['change_date'] ?? '');

    $current_km = (int)($_POST['current_km'] ?? 0);

    $cost = (float)($_POST['cost'] ?? 0);

    $notes = trim($_POST['notes'] ?? '');

    /* -----------------------------------------------------
       التحقق
    ----------------------------------------------------- */

    if ($oil_type === '') {
        die('يرجى إدخال نوع الزيت');
    }

    if ($change_date === '') {
        die('يرجى اختيار تاريخ تغيير الزيت');
    }

    if ($current_km < 0) {
        die('العداد الحالي غير صحيح');
    }

    if ($cost < 0) {
        die('التكلفة غير صحيحة');
    }

    /* -----------------------------------------------------
       حساب التغيير القادم
       30 يوم
    ----------------------------------------------------- */

    $next_change = date(
        'Y-m-d',
        strtotime($change_date . ' +30 days')
    );

    /* -----------------------------------------------------
       حساب العداد القادم
       +5000 KM
    ----------------------------------------------------- */

    $next_km = $current_km + 5000;

    /* -----------------------------------------------------
       UPDATE
    ----------------------------------------------------- */

    $updateSql = "
        UPDATE oil_changes
        SET
            oil_type = ?,
            change_date = ?,
            next_change = ?,
            current_km = ?,
            next_km = ?,
            cost = ?,
            notes = ?
        WHERE id = ?
        LIMIT 1
    ";

    $updateStmt = $con->prepare($updateSql);

    if (!$updateStmt) {

        die(
            $t['sql_error'] .
            ': ' .
            htmlspecialchars($con->error)
        );
    }

    /*
       8 متغيرات:

       oil_type     = s
       change_date  = s
       next_change  = s
       current_km   = i
       next_km      = i
       cost         = d
       notes        = s
       id           = i
    */

    $updateStmt->bind_param(
        "sssiidsi",
        $oil_type,
        $change_date,
        $next_change,
        $current_km,
        $next_km,
        $cost,
        $notes,
        $id
    );

    if (!$updateStmt->execute()) {

        $error = $updateStmt->error;

        $updateStmt->close();

        die(
            $t['update_error'] .
            ': ' .
            htmlspecialchars($error)
        );
    }

    $updateStmt->close();

    /* -----------------------------------------------------
       الرجوع إلى صفحة سجلات الزيت
    ----------------------------------------------------- */

    header(
        'Location: oile.php?lang=' .
        urlencode($lang) .
        '&theme=' .
        (int)$dark .
        '&updated=1'
    );

    exit;
}

/* =========================================================
   جلب السجل
========================================================= */

$sql = "
    SELECT
        o.*,

        f.plate AS vehicle_plate,

        d.name AS driver_name

    FROM oil_changes o

    LEFT JOIN fleet f
        ON o.car_id = f.id

    LEFT JOIN drivers d
        ON o.driver_id = d.id

    WHERE o.id = ?

    LIMIT 1
";

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        $t['sql_error'] .
        ': ' .
        htmlspecialchars($con->error)
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$stmt->close();

if (!$row) {
    die($t['not_found']);
}

/* =========================================================
   قيم العرض
========================================================= */

$vehiclePlate = $row['vehicle_plate'] ?? '-';

$driverName =
    !empty($row['driver_name'])
        ? $row['driver_name']
        : ($row['driver'] ?? '-');

$nextChange = $row['next_change'] ?? '-';

$nextKm = (int)($row['next_km'] ?? 0);

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

.container {

    max-width: 900px;

}

.card {

    background:
        <?= $dark ? '#1f1f1f' : '#fff' ?>;

    border: none;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.08);

}

.page-title {

    font-weight: bold;

}

.info-box {

    background:
        <?= $dark ? '#292929' : '#f8fafc' ?>;

    border-radius: 10px;

    padding: 18px;

    margin-bottom: 20px;

}

.info-label {

    color:
        <?= $dark ? '#aaa' : '#6c757d' ?>;

    font-size: 13px;

    margin-bottom: 4px;

}

.info-value {

    font-weight: bold;

    font-size: 17px;

}

.form-label {

    font-weight: bold;

}

.form-control {

    min-height: 44px;

    border-radius: 9px;

}

.form-control {

    background:
        <?= $dark ? '#2a2a2a' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#212529' ?>;

    border-color:
        <?= $dark ? '#555' : '#ced4da' ?>;
}

.form-control:focus {

    background:
        <?= $dark ? '#2a2a2a' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#212529' ?>;
}

.btn {

    border-radius: 9px;

}

.help-text {

    color:
        <?= $dark ? '#aaa' : '#6c757d' ?>;

    font-size: 12px;

}

</style>

</head>

<body>

<div class="container mt-5">

    <!-- =====================================================
         العنوان
    ====================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <h2 class="page-title mb-0">

            <i class="bi bi-pencil-square"></i>

            <?= htmlspecialchars($t['title']) ?>

        </h2>

        <a
            href="oile.php?lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-right"></i>

            <?= htmlspecialchars($t['back']) ?>

        </a>

    </div>


    <!-- =====================================================
         البطاقة
    ====================================================== -->

    <div class="card p-4">

        <!-- =================================================
             معلومات المركبة والسائق
        ================================================== -->

        <div class="info-box">

            <div class="row g-3">

                <div class="col-md-6">

                    <div class="info-label">
                        <?= htmlspecialchars($t['vehicle']) ?>
                    </div>

                    <div class="info-value">

                        <i class="bi bi-car-front"></i>

                        <?= htmlspecialchars($vehiclePlate) ?>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="info-label">
                        <?= htmlspecialchars($t['driver']) ?>
                    </div>

                    <div class="info-value">

                        <i class="bi bi-person"></i>

                        <?= htmlspecialchars($driverName) ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             النموذج
        ================================================== -->

        <form method="POST">

            <input
                type="hidden"
                name="id"
                value="<?= (int)$id ?>"
            >

            <input
                type="hidden"
                name="lang"
                value="<?= htmlspecialchars($lang) ?>"
            >


            <div class="row g-3">

                <!-- =========================================
                     نوع الزيت
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['oil_type']) ?>

                    </label>

                    <input
                        type="text"
                        name="oil_type"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $row['oil_type'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- =========================================
                     تاريخ تغيير الزيت
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['change_date']) ?>

                    </label>

                    <input
                        type="date"
                        name="change_date"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $row['change_date'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- =========================================
                     العداد الحالي
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['current_km']) ?>

                    </label>

                    <input
                        type="number"
                        name="current_km"
                        class="form-control"
                        value="<?= (int)(
                            $row['current_km'] ?? 0
                        ) ?>"
                        min="0"
                        required
                    >

                </div>


                <!-- =========================================
                     التكلفة
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['cost']) ?>

                    </label>

                    <input
                        type="number"
                        name="cost"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $row['cost'] ?? 0
                        ) ?>"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <!-- =========================================
                     الملاحظات
                ========================================== -->

                <div class="col-12">

                    <label class="form-label">

                        <?= htmlspecialchars($t['notes']) ?>

                    </label>

                    <textarea
                        name="notes"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars(
                        $row['notes'] ?? ''
                    ) ?></textarea>

                </div>


                <!-- =========================================
                     التغيير القادم
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['next_change']) ?>

                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($nextChange) ?>"
                        readonly
                    >

                    <div class="help-text mt-1">

                        <?= htmlspecialchars(
                            $t['next_change_help']
                        ) ?>

                    </div>

                </div>


                <!-- =========================================
                     العداد القادم
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        <?= htmlspecialchars($t['next_km']) ?>

                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= number_format($nextKm) ?>"
                        readonly
                    >

                    <div class="help-text mt-1">

                        <?= htmlspecialchars(
                            $t['next_km_help']
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 الأزرار
            ================================================== -->

            <div
                class="d-flex justify-content-center gap-2 mt-4"
            >

                <button
                    type="submit"
                    name="update"
                    class="btn btn-warning px-4"
                >

                    <i class="bi bi-check-circle"></i>

                    <?= htmlspecialchars($t['update']) ?>

                </button>


                <a
                    href="oile.php?lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
                    class="btn btn-secondary px-4"
                >

                    <i class="bi bi-x-circle"></i>

                    <?= htmlspecialchars($t['cancel']) ?>

                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>