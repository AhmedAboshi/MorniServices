<?php
session_start();
include('../include/connected.php');

// 🗑️ حذف
if(isset($_GET['delete'])){
    mysqli_query($con, "DELETE FROM oil_changes WHERE id='{$_GET['delete']}'");
    header("Location: oile.php");
    exit();
}

// ✏️ تعديل
$edit_row = null;
if(isset($_GET['edit'])){
    $res = mysqli_query($con, "SELECT * FROM oil_changes WHERE id='{$_GET['edit']}'");
    $edit_row = mysqli_fetch_assoc($res);
}

// ➕ + ✏️ حفظ
if(isset($_POST['save'])){
    $id = $_POST['id'];
    $car_id = $_POST['car_id'];
    $driver = $_POST['driver'];
    $oil_type = $_POST['oil_type'];
    $change_date = $_POST['change_date'];
    $km_change = $_POST['km_change'];
     $cost = $_POST['cost'];
     $notes = $_POST['notes'];
    // 🔢 حساب الكيلومتر القادم
    $next_km = $km_change + 5000;

    // 📅 حساب التاريخ القادم
    $daily_km = 100; // عدلها حسب الاستخدام
    $days = 5000 / $daily_km;
    $next_change = date("Y-m-d", strtotime("+$days days"));

    if(!empty($id)){
        $query = "UPDATE oil_changes SET
        car_id='$car_id',
        driver='$driver',
        oil_type='$oil_type',
        change_date='$change_date',
        km_change='$km_change',
        next_km='$next_km',
        next_change='$next_change',
        cost='$cost',
        notes='$notes'
        WHERE id='$id'";
    } else {
        $query = "INSERT INTO oil_changes 
        (car_id,driver,oil_type, change_date, km_change, next_km, next_change,cost,notes)
        VALUES 
        ('$car_id','$driver','$oil_type','$change_date','$km_change','$next_km','$next_change','$cost','$notes')";
    }

    mysqli_query($con, $query);
    header("Location: oile.php?success=1");
    exit();
}

// عرض البيانات
$result = mysqli_query($con, "SELECT * FROM oil_changes");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>نظام تغيير الزيت الذكي</title>

<style>
body {font-family: Arial; background:#f4f6f9;}

/* الفورم */
.form-container {
    width:400px; margin:30px auto; background:#fff;
    padding:20px; border-radius:10px; box-shadow:0 0 10px #ddd;
}

input {width:100%; padding:10px; margin:8px 0; border:1px solid #ccc;}

button {
    width:100%; padding:10px;
    background:#28a745; color:white; border:none;
}

button:hover {background:#218838;}

/* الجدول */
.table-container {width:95%; margin:30px auto;}

table {width:100%; border-collapse:collapse; background:#fff;}

th, td {
    padding:10px;
    text-align:center;
    border-bottom:1px solid #ddd;
}

th {background:#28a745; color:white;}

/* ألوان الحالة */
.red {background:#ff4d4d; color:white;}
.yellow {background:#ffc107;}
.green {background:#28a745; color:white;}

/* أزرار */
a {
    padding:5px 10px;
    border-radius:5px;
    color:white;
    text-decoration:none;
}

.edit {background:#007bff;}
.delete {background:red;}

.alert {padding:10px; margin:10px; text-align:center;}
.warning {background:orange; color:white;}
.danger {background:red; color:white;}
.success {text-align:center; color:green;}
</style>
</head>

<body>

<div class="form-container">
<h2>تغيير الزيت</h2>

<?php if(isset($_GET['success'])) echo "<p class='success'>✔ تمت العملية</p>"; ?>

<form method="post">
<input type="hidden" name="id" value="<?php echo @$edit_row['id']; ?>">

<input type="text" name="car_id" placeholder="رقم السيارة"
value="<?php echo @$edit_row['car_id']; ?>" required>

<input type="text" name="driver" placeholder="المزود"
value="<?php echo @$edit_row['driver']; ?>" required>

<input type="text" name="oil_type" placeholder="نوع الزيت"
value="<?php echo @$edit_row['oil_type']; ?>" required>

<input type="date" name="change_date"
value="<?php echo @$edit_row['change_date']; ?>" required>

<input type="number" name="km_change" placeholder="عداد السيارة"
value="<?php echo @$edit_row['km_change']; ?>" required>

<input type="number" name="cost" placeholder="التكلفة"
value="<?php echo @$edit_row['cost']; ?>" required>

<textarea name="notes" placeholder="ملاحظات"></textarea>


<button name="save"><?php echo $edit_row ? "تحديث" : "إضافة"; ?></button>
</form>
</div>

<div class="table-container">

<?php
$current_km = 4000; // 🔥 عدلها حسب النظام

$res_alert = mysqli_query($con, "SELECT * FROM oil_changes");
while($row = mysqli_fetch_assoc($res_alert)){

    if($current_km >= $row['next_km'] || $row['next_change'] < date("Y-m-d")){
        echo "<div class='alert danger'>❌ السيارة {$row['car_id']} تحتاج تغيير زيت</div>";
    }
    elseif($current_km >= $row['next_km'] - 500 || $row['next_change'] <= date("Y-m-d", strtotime("+7 days"))){
        echo "<div class='alert warning'>⚠️ السيارة {$row['car_id']} اقترب التغيير</div>";
    }
}
?>

<table dir="rtl">
<tr>
<th>لوحة السطحة</th>
<th>المزود</th>
<th>نوع الزيت</th>
<th>تاريخ التغيير</th>
<th>العداد</th>
<th>التغيير القادم (كم)</th>
<th>التاريخ القادم</th>
<th>الحالة</th>
<th>التكلفة</th>
<th>ملاحظات</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){

    $class = "green";
    $status = "طبيعي";

    if($current_km >= $row['next_km'] || $row['next_change'] < date("Y-m-d")){
        $class = "red";
        $status = "متأخر";
    }
    elseif($current_km >= $row['next_km'] - 500 || $row['next_change'] <= date("Y-m-d", strtotime("+7 days"))){
        $class = "yellow";
        $status = "قريب";
    }

?>
<tr class="<?php echo $class; ?>">
<td><?php echo $row['car_id']; ?></td>
<td><?php echo $row['driver']; ?></td>
<td><?php echo $row['oil_type']; ?></td>
<td><?php echo $row['change_date']; ?></td>
<td><?php echo $row['km_change']; ?></td>
<td><?php echo $row['next_km']; ?></td>
<td><?php echo $row['next_change']; ?></td>
<td><?php echo $status; ?></td>
<td><?php echo $row['cost']; ?></td>
<td><?php echo $row['notes']; ?></td>
<td>
<a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>
<a class="delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('حذف؟')">حذف</a>
</td>
</tr>
<?php } ?>

</table>
</div>

</body>
</html>