
<?php

session_start();

include('../include/connected.php');

/* =========================================================
   حماية الدخول
========================================================= */

if (!isset($_SESSION['admin_id'])) {
    header("Location: welcome.php");
    exit();
}

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

/* =========================================================
   الوضع الليلي
========================================================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$dark = (int)($_SESSION['theme'] ?? 0);

/* =========================================================
   رقم الحادث
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: accidents.php?lang=" . urlencode($lang));
    exit();
}

/* =========================================================
   النصوص
========================================================= */

$words = [

    'ar' => [

        'title'          => 'تعديل الحادث',
        'subtitle'       => 'تعديل بيانات وتحديث حالة الحادث',

        'accident_info'  => 'بيانات الحادث',

        'vehicle'        => 'المركبة',
        'driver'         => 'السائق',
        'date'           => 'تاريخ الحادث',
        'location'       => 'الموقع',
        'description'   => 'الوصف',

        'status'         => 'حالة الحادث',
        'cost'           => 'تكلفة الأضرار',

        'open'           => 'مفتوح',
        'progress'       => 'قيد المعالجة',
        'closed'         => 'مغلق',

        'save'            => 'حفظ التعديلات',
        'back'            => 'العودة للحوادث',

        'success'         => 'تم تحديث بيانات الحادث بنجاح.',
        'error'           => 'حدث خطأ أثناء تحديث الحادث.',
        'not_found'       => 'الحادث غير موجود.',

        'sar'             => 'ريال',
        'id'              => 'رقم الحادث',

        'light'           => 'الوضع النهاري',
        'dark'            => 'الوضع الليلي'

    ],

    'en' => [

        'title'          => 'Edit Accident',
        'subtitle'       => 'Edit accident information and update its status',

        'accident_info'  => 'Accident Information',

        'vehicle'        => 'Vehicle',
        'driver'         => 'Driver',
        'date'           => 'Accident Date',
        'location'       => 'Location',
        'description'   => 'Description',

        'status'         => 'Accident Status',
        'cost'           => 'Damage Cost',

        'open'           => 'Open',
        'progress'       => 'In Progress',
        'closed'         => 'Closed',

        'save'            => 'Save Changes',
        'back'            => 'Back to Accidents',

        'success'         => 'Accident updated successfully.',
        'error'           => 'An error occurred while updating the accident.',
        'not_found'       => 'Accident not found.',

        'sar'             => 'SAR',
        'id'              => 'Accident ID',

        'light'           => 'Light Mode',
        'dark'            => 'Dark Mode'

    ]

];

$t = $words[$lang];

/* =========================================================
   جلب بيانات الحادث
========================================================= */

$stmt = $con->prepare("
    SELECT
        a.*,
        f.plate,
        f.model,
        d.name AS driver_name
    FROM accidents a

    LEFT JOIN fleet f
        ON a.vehicle_id = f.id

    LEFT JOIN drivers d
        ON a.driver_id = d.id

    WHERE a.id = ?
    LIMIT 1
");

if (!$stmt) {
    die("SQL Error: " . htmlspecialchars($con->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$stmt->close();

if (!$data) {
    header(
        "Location: accidents.php?lang=" .
        urlencode($lang) .
        "&error=not_found"
    );
    exit();
}

/* =========================================================
   القيم الحالية
========================================================= */

$currentStatus = $data['status'] ?? 'Open';

$currentCost = isset($data['damage_cost'])
    ? (float)$data['damage_cost']
    : 0;

/* =========================================================
   حفظ التعديل
========================================================= */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status = trim($_POST['status'] ?? '');

    $costRaw = trim($_POST['damage_cost'] ?? '0');

    /*
     * السماح فقط بالحالات الموجودة في النظام
     */

    $allowedStatuses = [
        'Open',
        'In Progress',
        'Closed'
    ];

    if (!in_array($status, $allowedStatuses, true)) {

        $error = $lang === 'ar'
            ? 'حالة الحادث غير صحيحة.'
            : 'Invalid accident status.';

    } else {

        /*
         * تنظيف التكلفة
         */

        $costRaw = str_replace(',', '', $costRaw);

        $cost = is_numeric($costRaw)
            ? (float)$costRaw
            : 0;

        if ($cost < 0) {
            $cost = 0;
        }

        /*
         * تحديث الحادث
         */

        $update = $con->prepare("
            UPDATE accidents
            SET
                status = ?,
                damage_cost = ?
            WHERE id = ?
        ");

        if (!$update) {

            $error = $lang === 'ar'
                ? 'تعذر تجهيز عملية التحديث.'
                : 'Unable to prepare update.';

        } else {

            $update->bind_param(
                "sdi",
                $status,
                $cost,
                $id
            );

            if ($update->execute()) {

                $update->close();

                /*
                 * العودة مع الاحتفاظ باللغة
                 */

                header(
                    "Location: accidents.php?lang=" .
                    urlencode($lang) .
                    "&updated=1"
                );

                exit();

            } else {

                $error = $lang === 'ar'
                    ? 'حدث خطأ أثناء حفظ التعديلات.'
                    : 'An error occurred while saving changes.';

                $update->close();
            }
        }
    }

    /*
     * إذا حدث خطأ نعرض القيمة التي أدخلها المستخدم
     */

    $currentStatus = $status;
    $currentCost   = is_numeric($costRaw) ? (float)$costRaw : 0;
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
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<style>

/* =========================================================
   GENERAL
========================================================= */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Tahoma,
        Arial,
        sans-serif;

    background:
        <?= $dark ? '#0f172a' : '#f4f6f9' ?>;

    color:
        <?= $dark ? '#f8fafc' : '#1f2937' ?>;

    transition: .25s;
}

.page-container {

    max-width: 1100px;

    margin: 35px auto;

    padding: 0 18px;
}

/* =========================================================
   HEADER
========================================================= */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 25px;

}

.page-title {

    display: flex;

    align-items: center;

    gap: 15px;

}

.title-icon {

    width: 58px;

    height: 58px;

    border-radius: 16px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: linear-gradient(
        135deg,
        #dc3545,
        #b02a37
    );

    color: #fff;

    font-size: 25px;

    box-shadow:
        0 8px 20px rgba(220,53,69,.25);

}

.page-title h2 {

    margin: 0;

    font-size: 26px;

    font-weight: 800;

}

.page-title p {

    margin: 6px 0 0;

    color:
        <?= $dark ? '#94a3b8' : '#6b7280' ?>;

    font-size: 14px;

}

/* =========================================================
   HEADER BUTTONS
========================================================= */

.header-actions {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-wrap: wrap;

}

.header-actions .btn {

    border-radius: 9px;

}

/* =========================================================
   MAIN CARD
========================================================= */

.main-card {

    background:
        <?= $dark ? '#1e293b' : '#ffffff' ?>;

    border-radius: 18px;

    box-shadow:
        0 8px 30px rgba(0,0,0,.08);

    overflow: hidden;

    border:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

}

/* =========================================================
   CARD HEADER
========================================================= */

.card-header-custom {

    padding: 20px 24px;

    border-bottom:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

    display: flex;

    align-items: center;

    justify-content: space-between;

}

.card-header-title {

    display: flex;

    align-items: center;

    gap: 10px;

    font-weight: 700;

    font-size: 17px;

}

.card-header-title i {

    color: #dc3545;

}

/* =========================================================
   ACCIDENT INFORMATION
========================================================= */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    padding: 22px 24px;

}

.info-item {

    background:
        <?= $dark ? '#273449' : '#f8fafc' ?>;

    border:
        1px solid
        <?= $dark ? '#3b4a60' : '#e5e7eb' ?>;

    border-radius: 12px;

    padding: 15px;

}

.info-label {

    color:
        <?= $dark ? '#94a3b8' : '#6b7280' ?>;

    font-size: 12px;

    margin-bottom: 7px;

}

.info-value {

    font-size: 14px;

    font-weight: 700;

}

.info-value i {

    margin-left: 5px;

    color: #0d6efd;

}

/* =========================================================
   FORM
========================================================= */

.form-section {

    padding: 0 24px 25px;

}

.form-label {

    font-weight: 700;

    font-size: 13px;

    margin-bottom: 7px;

}

.form-control,
.form-select {

    min-height: 45px;

    border-radius: 10px;

    border:
        1px solid
        <?= $dark ? '#475569' : '#d7dce2' ?>;

    background:
        <?= $dark ? '#172033' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#1f2937' ?>;

}

.form-control:focus,
.form-select:focus {

    border-color: #0d6efd;

    box-shadow:
        0 0 0 .2rem rgba(13,110,253,.12);

    background:
        <?= $dark ? '#172033' : '#fff' ?>;

    color:
        <?= $dark ? '#fff' : '#1f2937' ?>;

}

.cost-input {

    font-size: 18px;

    font-weight: 700;

}

/* =========================================================
   STATUS
========================================================= */

.status-box {

    padding: 14px;

    border-radius: 12px;

    background:
        <?= $dark ? '#172033' : '#f8fafc' ?>;

    border:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

}

.status-help {

    margin-top: 7px;

    font-size: 12px;

    color:
        <?= $dark ? '#94a3b8' : '#6b7280' ?>;

}

/* =========================================================
   ACTIONS
========================================================= */

.form-actions {

    margin-top: 25px;

    padding-top: 20px;

    border-top:
        1px solid
        <?= $dark ? '#334155' : '#e5e7eb' ?>;

    display: flex;

    justify-content: space-between;

    gap: 10px;

}

.form-actions .btn {

    min-height: 45px;

    border-radius: 10px;

    padding: 0 20px;

    font-weight: 700;

}

/* =========================================================
   ERROR
========================================================= */

.error-alert {

    margin: 0 24px 20px;

    border-radius: 11px;

}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 900px) {

    .info-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}

@media(max-width: 600px) {

    .page-container {

        margin-top: 20px;

    }

    .page-header {

        flex-direction: column;

        align-items: flex-start;

    }

    .header-actions {

        width: 100%;

    }

    .info-grid {

        grid-template-columns: 1fr;

    }

    .form-actions {

        flex-direction: column-reverse;

    }

    .form-actions .btn {

        width: 100%;

    }

}

</style>

</head>

<body>

<div class="page-container">

<!-- =====================================================
     HEADER
===================================================== -->

<div class="page-header">

    <div class="page-title">

        <div class="title-icon">

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
            class="btn btn-outline-primary"
        >

            <i class="bi bi-arrow-right"></i>

            <?= htmlspecialchars($t['back']) ?>

        </a>


        <?php if ($lang === 'ar'): ?>

            <a
                href="?id=<?= $id ?>&lang=en&theme=<?= $dark ?>"
                class="btn btn-outline-secondary"
            >
                EN
            </a>

        <?php else: ?>

            <a
                href="?id=<?= $id ?>&lang=ar&theme=<?= $dark ?>"
                class="btn btn-outline-secondary"
            >
                AR
            </a>

        <?php endif; ?>


        <?php if ($dark): ?>

            <a
                href="?id=<?= $id ?>&lang=<?= urlencode($lang) ?>&theme=0"
                class="btn btn-light"
                title="<?= htmlspecialchars($t['light']) ?>"
            >
                <i class="bi bi-sun"></i>
            </a>

        <?php else: ?>

            <a
                href="?id=<?= $id ?>&lang=<?= urlencode($lang) ?>&theme=1"
                class="btn btn-dark"
                title="<?= htmlspecialchars($t['dark']) ?>"
            >
                <i class="bi bi-moon-stars"></i>
            </a>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     MAIN CARD
===================================================== -->

<div class="main-card">


    <div class="card-header-custom">

        <div class="card-header-title">

            <i class="bi bi-info-circle-fill"></i>

            <?= htmlspecialchars($t['accident_info']) ?>

        </div>


        <span class="badge bg-secondary">

            <?= htmlspecialchars($t['id']) ?>:

            #<?= (int)$data['id'] ?>

        </span>

    </div>


    <!-- =================================================
         EXISTING INFORMATION
    ================================================== -->

    <div class="info-grid">


        <div class="info-item">

            <div class="info-label">
                <?= htmlspecialchars($t['vehicle']) ?>
            </div>

            <div class="info-value">

                <i class="bi bi-car-front"></i>

                <?= htmlspecialchars(
                    $data['plate'] ?? '-'
                ) ?>

                <?php if (!empty($data['model'])): ?>

                    <small class="text-muted">

                        -
                        <?= htmlspecialchars($data['model']) ?>

                    </small>

                <?php endif; ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                <?= htmlspecialchars($t['driver']) ?>
            </div>

            <div class="info-value">

                <i class="bi bi-person-fill"></i>

                <?= htmlspecialchars(
                    $data['driver_name'] ?? '-'
                ) ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                <?= htmlspecialchars($t['date']) ?>
            </div>

            <div class="info-value">

                <i class="bi bi-calendar-event"></i>

                <?= htmlspecialchars(
                    $data['accident_date'] ?? '-'
                ) ?>

            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                <?= htmlspecialchars($t['location']) ?>
            </div>

            <div class="info-value">

                <i class="bi bi-geo-alt-fill"></i>

                <?= htmlspecialchars(
                    $data['location'] ?? '-'
                ) ?>

            </div>

        </div>


    </div>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger error-alert">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         EDIT FORM
    ================================================== -->

    <div class="form-section">

        <form method="POST" action="?id=<?= $id ?>&lang=<?= urlencode($lang) ?>">

            <div class="row g-4">


                <!-- STATUS -->

                <div class="col-md-6">

                    <label class="form-label">

                        <i class="bi bi-flag-fill text-danger"></i>

                        <?= htmlspecialchars($t['status']) ?>

                    </label>

                    <div class="status-box">

                        <select
                            name="status"
                            class="form-select"
                            required
                        >

                            <option
                                value="Open"
                                <?= $currentStatus === 'Open' ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($t['open']) ?>
                            </option>

                            <option
                                value="In Progress"
                                <?= $currentStatus === 'In Progress' ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($t['progress']) ?>
                            </option>

                            <option
                                value="Closed"
                                <?= $currentStatus === 'Closed' ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($t['closed']) ?>
                            </option>

                        </select>


                        <div class="status-help">

                            <i class="bi bi-info-circle"></i>

                            <?= $lang === 'ar'
                                ? 'اختر الحالة الحالية للحادث.'
                                : 'Select the current accident status.'
                            ?>

                        </div>

                    </div>

                </div>


                <!-- COST -->

                <div class="col-md-6">

                    <label class="form-label">

                        <i class="bi bi-cash-stack text-success"></i>

                        <?= htmlspecialchars($t['cost']) ?>

                    </label>

                    <div class="input-group">

                        <input
                            type="number"
                            name="damage_cost"
                            class="form-control cost-input"
                            value="<?= htmlspecialchars(
                                number_format($currentCost, 2, '.', '')
                            ) ?>"
                            min="0"
                            step="0.01"
                            required
                        >

                        <span class="input-group-text">

                            <?= htmlspecialchars($t['sar']) ?>

                        </span>

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="col-12">

                    <label class="form-label">

                        <i class="bi bi-card-text text-primary"></i>

                        <?= htmlspecialchars($t['description']) ?>

                    </label>

                    <textarea
                        class="form-control"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars(
                        $data['description'] ?? ''
                    ) ?></textarea>

                </div>


            </div>


            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="form-actions">


                <a
                    href="accidents.php?lang=<?= urlencode($lang) ?>"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-x-circle"></i>

                    <?= htmlspecialchars($t['back']) ?>

                </a>


                <button
                    type="submit"
                    name="update"
                    value="1"
                    class="btn btn-primary"
                >

                    <i class="bi bi-check2-circle"></i>

                    <?= htmlspecialchars($t['save']) ?>

                </button>


            </div>


        </form>

    </div>

</div>

</div>

</body>

</html>

