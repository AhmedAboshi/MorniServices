<?php


session_start();
include('../include/connected.php');

/* =========================
   اللغة
========================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
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

/* =========================
   الترجمة
========================= */

$text = [

    'ar' => [

        'title'       => 'تفاصيل تغيير الزيت',
        'car'         => 'المركبة',
        'plate'       => 'رقم اللوحة',
        'driver'      => 'السائق',
        'oil_type'    => 'نوع الزيت',
        'change_date' => 'تاريخ التغيير',
        'next_change' => 'التغيير القادم',
        'current_km'  => 'العداد الحالي',
        'next_km'     => 'العداد القادم',
        'cost'        => 'التكلفة',
        'notes'       => 'الملاحظات',
        'remaining'   => 'المتبقي',
        'status'      => 'الحالة',

        'good'        => 'ممتاز',
        'soon'        => 'قريب',
        'late'        => 'متأخر',
        'expired'     => 'منتهي',
        'day'         => 'يوم',

        'edit'        => 'تعديل',
        'delete'      => 'حذف',
        'back'        => 'رجوع',

        'sar'         => 'ريال',

        'invalid'     => 'رقم السجل غير صحيح',
        'not_found'   => 'سجل تغيير الزيت غير موجود',

        'confirm_delete' => 'هل تريد حذف سجل تغيير الزيت؟'
    ],

    'en' => [

        'title'       => 'Oil Change Details',
        'car'         => 'Vehicle',
        'plate'       => 'Plate',
        'driver'      => 'Driver',
        'oil_type'    => 'Oil Type',
        'change_date' => 'Change Date',
        'next_change' => 'Next Change',
        'current_km'  => 'Current KM',
        'next_km'     => 'Next KM',
        'cost'        => 'Cost',
        'notes'       => 'Notes',
        'remaining'   => 'Remaining',
        'status'      => 'Status',

        'good'        => 'Good',
        'soon'        => 'Soon',
        'late'        => 'Late',
        'expired'     => 'Expired',
        'day'         => 'Day',

        'edit'        => 'Edit',
        'delete'      => 'Delete',
        'back'        => 'Back',

        'sar'         => 'SAR',

        'invalid'     => 'Invalid record ID',
        'not_found'   => 'Oil change record not found',

        'confirm_delete' => 'Do you want to delete this oil change record?'
    ]

];

$t = $text[$lang];

/* =========================
   رقم سجل الزيت
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die($t['invalid']);
}

/* =========================
   جلب سجل الزيت
========================= */

$sql = "
    SELECT
        t.*,
        f.plate AS vehicle_plate,

        COALESCE(
            NULLIF(TRIM(d.name), ''),
            NULLIF(TRIM(t.driver), ''),
            '-'
        ) AS driver_name

    FROM oil_changes t

    LEFT JOIN fleet f
        ON t.car_id = f.id

    LEFT JOIN drivers d
        ON t.driver_id = d.id

    WHERE t.id = ?

    LIMIT 1
";

$stmt = $con->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . htmlspecialchars($con->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();

if (!$row) {
    die($t['not_found']);
}

/* =========================
   البيانات
========================= */

$vehiclePlate = $row['vehicle_plate'] ?? '-';
$driverName   = $row['driver_name'] ?? '-';
$oilType      = $row['oil_type'] ?? '-';
$changeDate   = $row['change_date'] ?? '-';
$nextChange   = $row['next_change'] ?? '';
$currentKm    = (int)($row['current_km'] ?? 0);
$nextKm       = (int)($row['next_km'] ?? 0);
$cost         = (float)($row['cost'] ?? 0);
$notes        = trim($row['notes'] ?? '');

/* =========================
   حساب الأيام
========================= */

$daysLeft = 0;

if ($nextChange !== '') {

    $nextTimestamp = strtotime($nextChange);
    $todayTimestamp = strtotime(date('Y-m-d'));

    if ($nextTimestamp !== false) {

        $daysLeft = ceil(
            ($nextTimestamp - $todayTimestamp) / 86400
        );
    }
}

/* =========================
   الحالة
========================= */

if ($nextChange === '') {

    $status = '-';
    $badge = 'secondary';

} elseif ($daysLeft < 0) {

    $status = $t['late'];
    $badge = 'danger';

} elseif ($daysLeft <= 30) {

    $status = $t['soon'];
    $badge = 'warning';

} else {

    $status = $t['good'];
    $badge = 'success';
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
        <?= $dark ? '#fff' : '#1f2937' ?>;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

}

.container {

    max-width: 1000px;

}

.main-card {

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

.info-row {

    display: flex;

    align-items: center;

    border-bottom: 1px solid
        <?= $dark ? '#333' : '#eee' ?>;

    padding: 15px 5px;

}

.info-row:last-child {

    border-bottom: none;

}

.info-label {

    width: 35%;

    font-weight: bold;

    color:
        <?= $dark ? '#aaa' : '#666' ?>;

}

.info-value {

    width: 65%;

    font-weight: 500;

}

.plate {

    display: inline-block;

    padding: 7px 14px;

    border-radius: 8px;

    background:
        <?= $dark ? '#333' : '#eef1f4' ?>;

    font-weight: bold;

}

.cost {

    color: #198754;

    font-size: 18px;

    font-weight: bold;

}

.notes {

    white-space: pre-line;

    line-height: 1.8;

}

</style>

</head>

<body>


<div class="container mt-4">


<!-- =========================
     Header
========================= -->

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

    <h2 class="page-title mb-0">

        💧 <?= htmlspecialchars($t['title']) ?>

    </h2>


    <div>

        <a
            href="?id=<?= $id ?>&lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>"
            class="btn btn-outline-secondary btn-sm"
        >

            <?= $lang === 'ar' ? 'EN' : 'AR' ?>

        </a>


        <?php if ($dark): ?>

            <a
                href="?id=<?= $id ?>&theme=0"
                class="btn btn-light btn-sm"
            >

                ☀️

            </a>

        <?php else: ?>

            <a
                href="?id=<?= $id ?>&theme=1"
                class="btn btn-dark btn-sm"
            >

                🌙

            </a>

        <?php endif; ?>

    </div>

</div>


<!-- =========================
     Details
========================= -->

<div class="main-card p-4">


<div class="info-row">

    <div class="info-label">

        <?= $t['car'] ?>

    </div>

    <div class="info-value">

        <span class="plate">

            <?= htmlspecialchars(
                $row['vehicle_plate'] ?? '-'
            ) ?>

        </span>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['driver'] ?>

    </div>

    <div class="info-value">

        <?= htmlspecialchars(
            $row['driver_name'] ?? '-'
        ) ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['oil_type'] ?>

    </div>

    <div class="info-value">

        <?= htmlspecialchars(
            $row['oil_type'] ?? '-'
        ) ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['change_date'] ?>

    </div>

    <div class="info-value">

        <?= htmlspecialchars(
            $row['change_date'] ?? '-'
        ) ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['next_change'] ?>

    </div>

    <div class="info-value">

        <?= htmlspecialchars(
            $row['next_change'] ?? '-'
        ) ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['current_km'] ?>

    </div>

    <div class="info-value">

        <?= number_format(
            (int)($row['current_km'] ?? 0)
        ) ?>

        KM

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['next_km'] ?>

    </div>

    <div class="info-value">

        <?= number_format(
            (int)($row['next_km'] ?? 0)
        ) ?>

        KM

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['remaining'] ?>

    </div>

    <div class="info-value">

        <?php if ($nextChange === ''): ?>

            -

        <?php elseif ($daysLeft < 0): ?>

            <span class="text-danger fw-bold">

                <?= $t['expired'] ?>

            </span>

        <?php else: ?>

            <span class="<?= $daysLeft <= 30 ? 'text-warning' : 'text-success' ?> fw-bold">

                <?= number_format($daysLeft) ?>

                <?= $t['day'] ?>

            </span>

        <?php endif; ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['status'] ?>

    </div>

    <div class="info-value">

        <span class="badge bg-<?= $badge ?>">

            <?= htmlspecialchars($status) ?>

        </span>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['cost'] ?>

    </div>

    <div class="info-value cost">

        <?= number_format(
            (float)($row['cost'] ?? 0),
            2
        ) ?>

        <?= $t['sar'] ?>

    </div>

</div>


<div class="info-row">

    <div class="info-label">

        <?= $t['notes'] ?>

    </div>

    <div class="info-value notes">

        <?= nl2br(
            htmlspecialchars(
                $row['notes'] ?? '-'
            )
        ) ?>

    </div>

</div>


<!-- =========================
     Buttons
========================= -->

<div class="text-center mt-4">


<a
    href="edit_oil.php?id=<?= $id ?>"
    class="btn btn-warning"
>

    <i class="bi bi-pencil"></i>

    <?= $t['edit'] ?>

</a>


<a
    href="oil_delete.php?id=<?= $id ?>"
    class="btn btn-danger"
    onclick="return confirm('<?= htmlspecialchars($t['confirm_delete'] ?? 'هل تريد حذف السجل؟', ENT_QUOTES) ?>');"
>

    <i class="bi bi-trash"></i>

    <?= $t['delete'] ?>

</a>


<a
    href="oile.php?lang=<?= urlencode($lang) ?>&theme=<?= (int)$dark ?>"
    class="btn btn-secondary"
>
    <i class="bi bi-arrow-right"></i>
    <?= $t['back'] ?>
</a>


</div>


</div>


</div>


</body>

</html>