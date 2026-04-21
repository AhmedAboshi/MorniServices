<?php
session_start();
include('../include/connected.php');

// ✅ نفذ فقط عند الضغط على زر الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehicle_name = trim($_POST['vehicle_name']);
    $plate_number = trim($_POST['plate_number']);
    $driver= trim($_POST['driver']);
    $maintenance_type = trim($_POST['maintenance_type']);
    $cost = $_POST['cost'];
    $notes = trim($_POST['notes']);
    $maintenance_date = $_POST['maintenance_date'];

    	

    // ✅ منع الإدخال الفارغ
    if (!empty($vehicle_name) && !empty($plate_number) && !empty($maintenance_type) && !empty($maintenance_date)) {

        $query = "INSERT INTO maintenance 
        (vehicle_name,plate_number,driver,maintenance_type, cost, notes, maintenance_date)
        VALUES 
        ('$vehicle_name','$plate_number','$driver','$maintenance_type','$cost','$notes','$maintenance_date')";

        $result = mysqli_query($con, $query);

        if ($result) {

            echo '<script>alert ("تم اضافة صيانة المركبة بنجاح");</script>';
            header("Location: maintenance.php");
    exit();

        } else {
            echo '<script>alert ("خطأ في الإدخال");</script>';
        }

    } else {
        echo '<script>alert ("يرجى تعبئة الحقول المطلوبة");</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>نظام صيانة المركبات</title>
<style>
body { font-family: Arial; direction: rtl; text-align: right; }
form { width: 400px; margin: auto; }
input, textarea { width: 100%; margin-bottom: 10px; padding: 8px; }
button { padding: 10px; background: green; color: white; border: none; }
</style>
</head>
<body>

<h2 align="center">إدخال صيانة مركبة</h2>
<img src="img/logo.jpg" alt="">

<form action="maintenance.php" method="POST">
    
    <input type="text" name="vehicle_name" placeholder="اسم الورشة" required>
    <input type="text" name="plate_number" placeholder="رقم اللوحة" required>
    <input type="text" name="driver" placeholder="المزود" required>
    <input type="text" name="maintenance_type" placeholder="نوع الصيانة" required>
    <input type="number" name="cost" placeholder="التكلفة" step="0.01">
    <textarea name="notes" placeholder="ملاحظات"></textarea>
    <input type="date" name="maintenance_date" required>
    <button type="submit">حفظ</button>
    
</form>


</body>
</html>