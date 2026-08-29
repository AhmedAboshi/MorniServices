<?php

session_start();

include('../include/connected.php');


/* =========================
   المركبة من الرابط
========================= */

$car_id = (int)($_GET['car_id'] ?? $_POST['car_id'] ?? 0);

$error = '';



/* =========================
   حفظ الإطار
========================= */

if (isset($_POST['save'])) {

    $car_id = (int)($_POST['car_id'] ?? 0);

    $tire_type = trim($_POST['tire_type'] ?? '');

    $tire_position = trim($_POST['tire_position'] ?? '');

    $change_date = $_POST['change_date'] ?? '';

    $cost = (float)($_POST['cost'] ?? 0);

    $notes = trim($_POST['notes'] ?? '');


    /* =========================
       التحقق
    ========================= */

    if ($car_id <= 0) {

        $error = 'يرجى اختيار المركبة.';

    } elseif ($tire_type === '') {

        $error = 'يرجى إدخال نوع الإطار.';

    } elseif ($tire_position === '') {

        $error = 'يرجى اختيار مكان الإطار.';

    } elseif ($change_date === '') {

        $error = 'يرجى اختيار تاريخ التغيير.';

    }


    /* =========================
       جلب بيانات المركبة
    ========================= */

    if ($error === '') {

        $stmt = $con->prepare("
            SELECT
                id,
                plate,
                driver
            FROM fleet
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $car_id);

        $stmt->execute();

        $fleet = $stmt->get_result()->fetch_assoc();

        $stmt->close();


        if (!$fleet) {

            $error = 'المركبة المحددة غير موجودة.';

        }

    }


    /* =========================
       بيانات المركبة والعداد
    ========================= */

    if ($error === '') {

        $driver = $fleet['driver'] ?? '';


        /* =========================
           آخر عداد للمركبة
        ========================= */

        $current_km = 0;

        $stmt = $con->prepare("
            SELECT current_km
            FROM oil_changes
            WHERE car_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param("i", $car_id);

        $stmt->execute();

        $oil = $stmt->get_result()->fetch_assoc();

        $stmt->close();


        if ($oil) {

            $current_km = (int)($oil['current_km'] ?? 0);

        }


        /* =========================
           الحساب التلقائي
        ========================= */

        $next_km = $current_km + 40000;


        $next_change = date(
            'Y-m-d',
            strtotime($change_date . ' +365 days')
        );


        /* =========================
           حفظ الإطار
        ========================= */

        $stmt = $con->prepare("
            INSERT INTO tires
            (
                car_id,
                driver,
                tire_type,
                tire_position,
                change_date,
                current_km,
                next_km,
                next_change,
                notes,
                cost
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");


        $stmt->bind_param(
            "issssiissd",
            $car_id,
            $driver,
            $tire_type,
            $tire_position,
            $change_date,
            $current_km,
            $next_km,
            $next_change,
            $notes,
            $cost
        );


        if ($stmt->execute()) {

            $stmt->close();

            header("Location: tire.php?success=1");

            exit;

        } else {

            $error = 'حدث خطأ أثناء حفظ سجل الإطار.';

            $stmt->close();

        }

    }

}



/* =========================
   تحميل المركبات
========================= */

$vehicles = [];

$result = $con->query("
    SELECT
        id,
        plate,
        driver
    FROM fleet
    ORDER BY plate ASC
");


while ($row = $result->fetch_assoc()) {

    $vehicles[] = $row;

}



/* =========================
   المركبة المحددة
========================= */

$selected_vehicle = null;


if ($car_id > 0) {

    foreach ($vehicles as $vehicle) {

        if ((int)$vehicle['id'] === $car_id) {

            $selected_vehicle = $vehicle;

            break;

        }

    }

}

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>إضافة إطارات</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body{

    background:#f4f6f9;

}


.card{

    border:none;

    border-radius:15px;

    box-shadow:0 5px 20px rgba(0,0,0,.1);

}


.vehicle-info{

    border-radius:12px;

}


.form-label{

    font-weight:bold;

    color:#444;

}

</style>

</head>


<body>


<div class="container mt-5">

<div class="card p-4">


<h3 class="mb-4">

    🛞 إضافة تغيير إطارات

</h3>


<?php if ($error !== ''): ?>

    <div class="alert alert-danger">

        ⚠️ <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<form method="POST">


<!-- =========================
     المركبة
========================= -->

<div class="mb-3">

    <label class="form-label">

        المركبة

    </label>


    <select
        name="car_id"
        class="form-select"
        required
        onchange="showVehicle(this)"
    >

        <option value="">

            -- اختر المركبة --

        </option>


        <?php foreach ($vehicles as $vehicle): ?>

            <option
                value="<?= (int)$vehicle['id'] ?>"
                data-driver="<?= htmlspecialchars(
                    $vehicle['driver'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                <?= $car_id == $vehicle['id'] ? 'selected' : '' ?>
            >

                <?= htmlspecialchars($vehicle['plate']) ?>

                -

                <?= htmlspecialchars(
                    $vehicle['driver'] ?: 'بدون سائق'
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>

</div>



<!-- =========================
     بيانات المركبة
========================= -->

<?php if ($selected_vehicle): ?>

<div class="alert alert-info vehicle-info">

    🚗

    <strong>
        المركبة:
    </strong>

    <?= htmlspecialchars($selected_vehicle['plate']) ?>


    <br>


    👤

    <strong>
        السائق:
    </strong>

    <?= htmlspecialchars(
        $selected_vehicle['driver'] ?: 'بدون سائق'
    ) ?>

</div>

<?php endif; ?>



<!-- =========================
     نوع الإطار
========================= -->

<div class="mb-3">

    <label class="form-label">

        نوع الإطار

    </label>


    <input
        type="text"
        name="tire_type"
        class="form-control"
        placeholder="مثال: ميشلان 265/65R17"
        value="<?= htmlspecialchars(
            $_POST['tire_type'] ?? ''
        ) ?>"
        required
    >

</div>



<!-- =========================
     مكان الإطار
========================= -->

<div class="mb-3">

    <label class="form-label">

        مكان الإطار

    </label>


    <select
        name="tire_position"
        class="form-select"
        required
    >

        <option value="">

            -- اختر مكان الإطار --

        </option>


        <option
            value="أمامي يمين"
            <?= ($_POST['tire_position'] ?? '') === 'أمامي يمين'
                ? 'selected'
                : '' ?>
        >

            أمامي يمين

        </option>


        <option
            value="أمامي يسار"
            <?= ($_POST['tire_position'] ?? '') === 'أمامي يسار'
                ? 'selected'
                : '' ?>
        >

            أمامي يسار

        </option>


        <option
            value="خلفي يمين"
            <?= ($_POST['tire_position'] ?? '') === 'خلفي يمين'
                ? 'selected'
                : '' ?>
        >

            خلفي يمين

        </option>


        <option
            value="خلفي يسار"
            <?= ($_POST['tire_position'] ?? '') === 'خلفي يسار'
                ? 'selected'
                : '' ?>
        >

            خلفي يسار

        </option>


        <option
            value="احتياطي"
            <?= ($_POST['tire_position'] ?? '') === 'احتياطي'
                ? 'selected'
                : '' ?>
        >

            احتياطي

        </option>

    </select>

</div>



<!-- =========================
     تاريخ التغيير
========================= -->

<div class="mb-3">

    <label class="form-label">

        تاريخ التغيير

    </label>


    <input
        type="date"
        name="change_date"
        class="form-control"
        value="<?= htmlspecialchars(
            $_POST['change_date'] ?? ''
        ) ?>"
        required
    >

</div>



<!-- =========================
     التكلفة
========================= -->

<div class="mb-3">

    <label class="form-label">

        التكلفة

    </label>


    <input
        type="number"
        step="0.01"
        min="0"
        name="cost"
        class="form-control"
        placeholder="0.00"
        value="<?= htmlspecialchars(
            $_POST['cost'] ?? ''
        ) ?>"
    >

</div>



<!-- =========================
     الملاحظات
========================= -->

<div class="mb-3">

    <label class="form-label">

        ملاحظات

    </label>


    <textarea
        name="notes"
        class="form-control"
        rows="4"
        placeholder="اكتب أي ملاحظات عن تغيير الإطارات..."
    ><?= htmlspecialchars(
        $_POST['notes'] ?? ''
    ) ?></textarea>

</div>



<!-- =========================
     الأزرار
========================= -->

<button
    type="submit"
    name="save"
    class="btn btn-success"
>

    💾 حفظ

</button>


<a
    href="tire.php"
    class="btn btn-secondary"
>

    ↩️ رجوع

</a>


</form>


</div>

</div>


<script>

/* =========================
   عرض بيانات المركبة
========================= */

function showVehicle(select){

    const option =
        select.options[select.selectedIndex];

    const driver =
        option.getAttribute('data-driver');

}

</script>


</body>

</html>