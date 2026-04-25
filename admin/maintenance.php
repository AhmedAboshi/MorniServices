<?php
session_start();
include('../include/connected.php');

/* =========================
   💾 حفظ البيانات (آمن)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehicle_name = trim($_POST['vehicle_name']);
    $plate_number = trim($_POST['plate_number']);
    $driver = trim($_POST['driver']);
    $maintenance_type = trim($_POST['maintenance_type']);
    $cost = (float) $_POST['cost'];
    $notes = trim($_POST['notes']);
    $maintenance_date = $_POST['maintenance_date'];

    /* ✅ تحقق من البيانات */
    if (
        !empty($vehicle_name) &&
        !empty($plate_number) &&
        !empty($maintenance_type) &&
        !empty($maintenance_date)
    ) {

        /* 🔐 Prepared Statement */
     $stmt = $con->prepare("
    INSERT INTO maintenance 
    (vehicle_name, plate_number, driver, maintenance_type, cost, notes, maintenance_date)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssdss",
    $vehicle_name,
    $plate_number,
    $driver,
    $maintenance_type,
    $cost,
    $notes,
    $maintenance_date
);

$stmt->execute();

        if ($stmt->execute()) {

            header("Location: maintenance.php?success=1");
            exit();

        } else {
            $error = "خطأ في الإدخال";
        }

    } else {
        $error = "يرجى تعبئة الحقول المطلوبة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>نظام صيانة المركبات</title>

<style>
body {
    font-family: Arial;
    background:#f4f6f9;
}

.container{
    width:400px;
    margin:auto;
    margin-top:50px;
    background:#fff;
    padding:20px;
    border-radius:10px;
}

input, textarea {
    width:100%;
    margin-bottom:10px;
    padding:10px;
    border:1px solid #ddd;
    border-radius:5px;
}

button {
    width:100%;
    padding:10px;
    background:green;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

.alert {
    padding:10px;
    margin-bottom:10px;
    background:#d4edda;
    color:#155724;
}

.error {
    padding:10px;
    margin-bottom:10px;
    background:#f8d7da;
    color:#721c24;
}
</style>

</head>
<body>

<div class="container">

<h2>🔧 إدخال صيانة مركبة</h2>

<!-- ✅ نجاح -->
<?php if(isset($_GET['success'])): ?>
    <div class="alert">تم إضافة الصيانة بنجاح</div>
<?php endif; ?>

<!-- ❌ خطأ -->
<?php if(isset($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

    <input type="text" name="vehicle_name" placeholder="اسم الورشة" required>

    <input type="text" name="plate_number" placeholder="رقم اللوحة" required>

    <input type="text" name="driver" placeholder="المزود" required>

    <input type="text" name="maintenance_type" placeholder="نوع الصيانة" required>

    <input type="number" name="cost" placeholder="التكلفة" step="0.01">

    <textarea name="notes" placeholder="ملاحظات"></textarea>

    <input type="date" name="maintenance_date" required>

    <button type="submit">حفظ</button>

</form>

</div>

</body>
</html>