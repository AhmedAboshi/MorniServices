<?php

session_start();

include('../include/connected.php');

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];

    if (in_array($newLang, ['ar', 'en'], true)) {
        $_SESSION['lang'] = $newLang;
    }
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================
   الوضع الليلي
========================= */

if (isset($_GET['theme'])) {
    $_SESSION['theme'] = (int)$_GET['theme'];
}

$dark = (int)($_SESSION['theme'] ?? 0);
/* =========================================================
   رقم الحادث
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die(
        $lang === 'ar'
            ? 'رقم الحادث غير صحيح'
            : 'Invalid accident ID'
    );
}

/* =========================================================
   جلب بيانات الحادث
========================================================= */

$stmt = $con->prepare("
    SELECT
    a.*,
    f.plate,
    f.model,
    f.imgfleet,
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

if (!$stmt->execute()) {
    die("Execute Error: " . htmlspecialchars($stmt->error));
}

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$stmt->close();

/* =========================================================
   التأكد من وجود الحادث
========================================================= */

if (!$data) {
    die(
        $lang === 'ar'
            ? 'الحادث غير موجود'
            : 'Accident not found'
    );
}

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'       => 'تفاصيل الحادث',
        'vehicle'     => 'المركبة',
        'plate'       => 'رقم اللوحة',
        'model'       => 'الموديل',
        'driver'      => 'السائق',
        'date'        => 'تاريخ الحادث',
        'location'    => 'الموقع',
        'description' => 'الوصف',
        'cost'        => 'تكلفة الأضرار',
        'status'      => 'الحالة',
        'created'     => 'تاريخ التسجيل',
        'back'        => 'العودة إلى الحوادث',
        'edit'        => 'تعديل',
        'print'       => 'طباعة',
        'sar'         => 'ريال',
        'open'        => 'مفتوح',
        'closed'      => 'مغلق',
        'pending'     => 'قيد المراجعة',
        'unknown'     => 'غير محدد',
        'not_found'   => 'غير موجود'

    ],

    'en' => [

        'title'       => 'Accident Details',
        'vehicle'     => 'Vehicle',
        'plate'       => 'Plate Number',
        'model'       => 'Model',
        'driver'      => 'Driver',
        'date'        => 'Accident Date',
        'location'    => 'Location',
        'description' => 'Description',
        'cost'        => 'Damage Cost',
        'status'      => 'Status',
        'created'     => 'Created At',
        'back'        => 'Back to Accidents',
        'edit'        => 'Edit',
        'print'       => 'Print',
        'sar'         => 'SAR',
        'open'        => 'Open',
        'closed'      => 'Closed',
        'pending'     => 'Pending',
        'unknown'     => 'Unknown',
        'not_found'   => 'Not found'

    ]

];

$t = $text[$lang];

/* =========================================================
   القيم
========================================================= */

$plate = $data['plate'] ?? $t['not_found'];
$model = $data['model'] ?? $t['not_found'];

$driverName = $data['driver_name'] ?? '';

if ($driverName === '') {
    $driverName = $t['not_found'];
}

$location = $data['location'] ?? '';

if ($location === '') {
    $location = $t['not_found'];
}

$description = $data['description'] ?? '';

if ($description === '') {
    $description = $t['not_found'];
}

$accidentDate = $data['accident_date'] ?? '';

if ($accidentDate === '') {
    $accidentDate = $t['not_found'];
}

$createdAt = $data['created_at'] ?? '';

if ($createdAt === '') {
    $createdAt = $t['not_found'];
}

$cost = (float)($data['damage_cost'] ?? 0);

$status = $data['status'] ?? '';

/* =========================================================
   ترجمة الحالة
========================================================= */

$statusClass = 'secondary';

if (strtolower($status) === 'open') {

    $statusText = $t['open'];
    $statusClass = 'danger';

} elseif (strtolower($status) === 'closed') {

    $statusText = $t['closed'];
    $statusClass = 'success';

} elseif (
    strtolower($status) === 'pending' ||
    strtolower($status) === 'قيد المراجعة'
) {

    $statusText = $t['pending'];
    $statusClass = 'warning';

} else {

    $statusText = $status !== ''
        ? $status
        : $t['unknown'];
}

/* =========================================================
   رابط اللغة
   مهم جدًا: نحافظ على id
========================================================= */

$languageUrl =
    '?id=' . urlencode($id) .
    '&lang=' . ($lang === 'ar' ? 'en' : 'ar');

/* =========================================================
   رابط التعديل
========================================================= */

$editUrl =
    'accident_edit.php?id=' .
    urlencode($id) .
    '&lang=' .
    urlencode($lang);

/* =========================================================
   رابط العودة
========================================================= */

$backUrl =
    'accidents.php?lang=' .
    urlencode($lang);


    $vehicleImage = trim($data['imgfleet'] ?? '');

if ($vehicleImage !== '') {

    $imagePath = '../fleetimg/img/' . $vehicleImage;

    if (!file_exists(__DIR__ . '/../fleetimg/img/' . $vehicleImage)) {
        $imagePath = '../assets/img/no-car.png';
    }

} else {

    $imagePath = '../assets/img/no-car.png';
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

body {
    background: #f4f6f9;
    font-family: Tahoma, Arial, sans-serif;
}

.page-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 15px;
}

.page-header {
    background: #fff;
    border-radius: 18px;
    padding: 22px 25px;
    margin-bottom: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

.page-header h2 {
    margin: 0;
    font-weight: bold;
}

.page-header p {
    margin: 7px 0 0;
    color: #777;
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.details-card {
    background: #fff;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

.detail-box {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 16px;
    height: 100%;
    background: #fafafa;
}

.detail-label {
    color: #777;
    font-size: 13px;
    margin-bottom: 7px;
}

.detail-value {
    font-size: 16px;
    font-weight: bold;
    color: #212529;
}

.description-box {
    min-height: 120px;
    white-space: pre-wrap;
    line-height: 1.8;
}

.cost-box {
    color: #198754;
    font-size: 22px;
    font-weight: bold;
}

.accident-icon {
    width: 55px;
    height: 55px;
    background: #fee2e2;
    color: #dc3545;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
}

@media(max-width: 700px) {

    .page-header {
        padding: 18px;
    }

    .actions {
        margin-top: 15px;
    }

}

@media print {

    .no-print {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    .page-container {
        margin: 0;
        max-width: 100%;
    }

    .page-header,
    .details-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }

}

.vehicle-hero {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 25px;
    padding: 20px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}

.vehicle-image {
    width: 250px;
    height: 170px;
    flex: 0 0 250px;
    border-radius: 14px;
    overflow: hidden;
    background: #e9ecef;
}

.vehicle-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.vehicle-summary h3 {
    margin: 8px 0 4px;
    font-size: 24px;
    font-weight: 800;
}

.vehicle-summary p {
    margin: 0 0 15px;
    color: #6b7280;
}

.vehicle-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #dc3545;
    font-size: 13px;
    font-weight: 700;
}

.vehicle-meta {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    font-size: 13px;
}

.vehicle-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

@media (max-width: 700px) {

    .vehicle-hero {
        flex-direction: column;
        align-items: stretch;
    }

    .vehicle-image {
        width: 100%;
        height: 220px;
        flex-basis: auto;
    }
}
</style>

</head>

<body>

<div class="page-container">

    <!-- =====================================================
         Header
    ====================================================== -->

    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="accident-icon">
                    <i class="bi bi-car-front-fill"></i>
                </div>

                <div>

                    <h2>
                        <?= htmlspecialchars($t['title']) ?>
                    </h2>

                    <p>
                        #<?= (int)$data['id'] ?>
                    </p>

                    <div class="vehicle-hero">

    <div class="vehicle-image">

        <img
            src="<?= htmlspecialchars($imagePath) ?>"
            alt="<?= htmlspecialchars($plate) ?>"
        >

    </div>

    <div class="vehicle-summary">

        <div class="vehicle-badge">
            <i class="bi bi-car-front-fill"></i>
            <?= htmlspecialchars($t['vehicle']) ?>
        </div>

        <h3>
            <?= htmlspecialchars($plate) ?>
        </h3>

        <p>
            <?= htmlspecialchars($model) ?>
        </p>

        <div class="vehicle-meta">

            <span>
                <i class="bi bi-person-fill"></i>
                <?= htmlspecialchars($driverName) ?>
            </span>

            <span>
                <i class="bi bi-calendar-event"></i>
                <?= htmlspecialchars($accidentDate) ?>
            </span>

        </div>

    </div>

</div>
                </div>

            </div>

            <div class="actions no-print">

                <!-- العودة -->

                <a
                    href="<?= htmlspecialchars($backUrl) ?>"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-arrow-right"></i>

                    <?= htmlspecialchars($t['back']) ?>

                </a>

                <!-- تعديل -->

              <a
    href="/AlSharqPlatform/admin/edit-accident.php?id=<?= (int)$data['id'] ?>&lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
    class="btn btn-warning"
>
    <i class="bi bi-pencil"></i>
    <?= htmlspecialchars($t['edit']) ?>
</a>

                <!-- طباعة -->

                <button
                    type="button"
                    onclick="window.print()"
                    class="btn btn-primary"
                >

                    <i class="bi bi-printer"></i>

                    <?= htmlspecialchars($t['print']) ?>

                </button>

                <!-- اللغة -->

                <a
                    href="<?= htmlspecialchars($languageUrl) ?>"
                    class="btn btn-outline-dark"
                >

                    <?= $lang === 'ar' ? 'EN 🇺🇸' : 'AR 🇸🇦' ?>

                </a>

            </div>

        </div>

    </div>


    <!-- =====================================================
         Details
    ====================================================== -->

    <div class="details-card">

        <div class="row g-3">

            <!-- المركبة -->

            <div class="col-md-4">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-car-front"></i>

                        <?= htmlspecialchars($t['vehicle']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($plate) ?>

                    </div>

                </div>

            </div>


            <!-- اللوحة -->

            <div class="col-md-4">

                <div class="detail-box">

                    <div class="detail-label">

                        <?= htmlspecialchars($t['plate']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($plate) ?>

                    </div>

                </div>

            </div>


            <!-- الموديل -->

            <div class="col-md-4">

                <div class="detail-box">

                    <div class="detail-label">

                        <?= htmlspecialchars($t['model']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($model) ?>

                    </div>

                </div>

            </div>


            <!-- السائق -->

            <div class="col-md-6">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-person"></i>

                        <?= htmlspecialchars($t['driver']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($driverName) ?>

                    </div>

                </div>

            </div>


            <!-- التاريخ -->

            <div class="col-md-6">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-calendar-event"></i>

                        <?= htmlspecialchars($t['date']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($accidentDate) ?>

                    </div>

                </div>

            </div>


            <!-- الموقع -->

            <div class="col-md-12">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-geo-alt"></i>

                        <?= htmlspecialchars($t['location']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($location) ?>

                    </div>

                </div>

            </div>


            <!-- الوصف -->

            <div class="col-md-12">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-card-text"></i>

                        <?= htmlspecialchars($t['description']) ?>

                    </div>

                    <div class="detail-value description-box">

                        <?= htmlspecialchars($description) ?>

                    </div>

                </div>

            </div>


            <!-- التكلفة -->

            <div class="col-md-6">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-cash-stack"></i>

                        <?= htmlspecialchars($t['cost']) ?>

                    </div>

                    <div class="cost-box">

                        <?= number_format($cost, 2) ?>

                        <?= htmlspecialchars($t['sar']) ?>

                    </div>

                </div>

            </div>


            <!-- الحالة -->

            <div class="col-md-6">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-info-circle"></i>

                        <?= htmlspecialchars($t['status']) ?>

                    </div>

                    <div>

                        <span class="badge bg-<?= htmlspecialchars($statusClass) ?> fs-6">

                            <?= htmlspecialchars($statusText) ?>

                        </span>

                    </div>

                </div>

            </div>


            <!-- تاريخ التسجيل -->

            <div class="col-md-12">

                <div class="detail-box">

                    <div class="detail-label">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars($t['created']) ?>

                    </div>

                    <div class="detail-value">

                        <?= htmlspecialchars($createdAt) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>