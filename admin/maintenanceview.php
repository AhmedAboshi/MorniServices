<?php
session_start();
include('../include/connected.php');

/* =========================
   🗑️ حذف آمن (POST)
========================= */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM maintenance WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: maintenance.php");
    exit();
}

/* =========================
   📊 جلب البيانات
========================= */
$query = "SELECT * FROM maintenance ORDER BY id DESC";
$result = mysqli_query($con, $query);

/* 💰 إجمالي التكلفة */
$total_cost = 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>صيانة المركبات</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:0;}

.container{
    width:95%;
    margin:auto;
}

table{
    width:100%;
    margin-top:20px;
    background:#fff;
    border-collapse:collapse;
}

th,td{
    padding:10px;
    border:1px solid #ddd;
    text-align:center;
}

th{
    background:#28a745;
    color:#fff;
}

.delete{
    background:red;
    color:#fff;
    padding:5px 10px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

.total{
    margin-top:15px;
    padding:10px;
    background:#fff;
    font-size:18px;
}
</style>

</head>
<body>

<div class="container">

<h2>🔧 سجل صيانة المركبات</h2>

<!-- =========================
     📋 الجدول
========================= -->
<table>

<tr>
<th>رقم العملية</th>
<th>اسم الورشة</th>
<th>لوحة السطحة</th>
<th>المزود</th>
<th>نوع الصيانة</th>
<th>التكلفة</th>
<th>ملاحظة</th>
<th>تاريخ الصيانة</th>
<th>إجراء</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ 

$total_cost += (float)$row['cost'];
?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['vehicle_name']); ?></td>
<td><?php echo htmlspecialchars($row['plate_number']); ?></td>
<td><?php echo htmlspecialchars($row['driver']); ?></td>
<td><?php echo htmlspecialchars($row['maintenance_type']); ?></td>
<td><?php echo htmlspecialchars($row['cost']); ?> ريال</td>
<td><?php echo htmlspecialchars($row['notes']); ?></td>
<td><?php echo htmlspecialchars($row['maintenance_date']); ?></td>

<td>
<form method="post" onsubmit="return confirm('هل تريد حذف السجل؟')">
    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
    <button class="delete">حذف</button>
</form>
</td>

</tr>

<?php } ?>

</table>

<!-- =========================
     💰 الإجمالي
========================= -->
<div class="total">
    💰 إجمالي تكلفة الصيانة: <b><?php echo $total_cost; ?> ريال</b>
</div>

</div>

</body>
</html>