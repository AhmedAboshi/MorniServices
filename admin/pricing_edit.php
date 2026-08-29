<?php
session_start();
include('../include/connected.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم غير صحيح");
}

/* جلب البيانات */
$stmt = $con->prepare("
    SELECT *
    FROM transport_pricing
    WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows == 0){
    die("السجل غير موجود");
}

$row = $result->fetch_assoc();

$msg = '';

/* حفظ التعديل */
if(isset($_POST['save'])){

    $regular_customer   = (float)$_POST['regular_customer'];
    $hydraulic_customer = (float)$_POST['hydraulic_customer'];
    $covered_customer   = (float)$_POST['covered_customer'];

    $regular_driver     = (float)$_POST['regular_driver'];
    $hydraulic_driver   = (float)$_POST['hydraulic_driver'];
    $covered_driver     = (float)$_POST['covered_driver'];

    $update = $con->prepare("
        UPDATE transport_pricing SET

        regular_customer=?,
        hydraulic_customer=?,
        covered_customer=?,

        regular_driver=?,
        hydraulic_driver=?,
        covered_driver=?

        WHERE id=?
    ");

    $update->bind_param(
        "ddddddi",
        $regular_customer,
        $hydraulic_customer,
        $covered_customer,
        $regular_driver,
        $hydraulic_driver,
        $covered_driver,
        $id
    );

    if($update->execute()){

        header("Location: pricing.php?updated=1");
        exit;

    }else{

        $msg = "حدث خطأ أثناء الحفظ";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>

<meta charset="utf-8">

<title>تعديل الأسعار</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f5f6fa;
}

.card{
    border:none;
    border-radius:15px;
}

.form-control{
    border-radius:10px;
}

</style>

</head>

<body>

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-header bg-warning">

            <h4 class="mb-0">
                تعديل السعر
            </h4>

        </div>

        <div class="card-body">

            <?php if($msg){ ?>

                <div class="alert alert-danger">
                    <?= $msg ?>
                </div>

            <?php } ?>

            <div class="row mb-4">

                <div class="col-md-6">

                    <label class="form-label">
                        من مدينة
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($row['from_city']) ?>"
                        readonly>

                </div>

                <div class="col-md-6">

                    <label class="form-label">
                        إلى مدينة
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($row['to_city']) ?>"
                        readonly>

                </div>

            </div>

            <form method="post">

                <h5 class="mb-3">
                    أسعار العميل
                </h5>

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label>عادي</label>

                        <input
                            type="number"
                            step="0.01"
                            name="regular_customer"
                            value="<?= $row['regular_customer'] ?>"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>هيدروليك</label>

                        <input
                            type="number"
                            step="0.01"
                            name="hydraulic_customer"
                            value="<?= $row['hydraulic_customer'] ?>"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>مغطى</label>

                        <input
                            type="number"
                            step="0.01"
                            name="covered_customer"
                            value="<?= $row['covered_customer'] ?>"
                            class="form-control"
                            required>

                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    أسعار السائق
                </h5>

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label>عادي</label>

                        <input
                            type="number"
                            step="0.01"
                            name="regular_driver"
                            value="<?= $row['regular_driver'] ?>"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>هيدروليك</label>

                        <input
                            type="number"
                            step="0.01"
                            name="hydraulic_driver"
                            value="<?= $row['hydraulic_driver'] ?>"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>مغطى</label>

                        <input
                            type="number"
                            step="0.01"
                            name="covered_driver"
                            value="<?= $row['covered_driver'] ?>"
                            class="form-control"
                            required>

                    </div>

                </div>

                <hr>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        name="save"
                        class="btn btn-success">

                        حفظ التعديلات

                    </button>

                    <a href="pricing.php"
                       class="btn btn-secondary">

                       رجوع

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>