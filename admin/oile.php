<?php
session_start();
include(__DIR__ . '/../include/connected.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* حذف */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM oil_changes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: oile.php");
    exit();
}

/* تعديل */
$edit = null;
if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM oil_changes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

/* حفظ */
if(isset($_POST['save'])){

    $id = (int)($_POST['id'] ?? 0);

    $driver_id = (int)$_POST['driver_id'];
    $car_id = trim($_POST['car_id']);
    $oil_type = trim($_POST['oil_type']);
    $change_date = $_POST['change_date'];
    $km_change = (int)$_POST['km_change'];
    $cost = (float)$_POST['cost'];
    $notes = trim($_POST['notes']);

    $next_km = $km_change + 5000;
    $next_change = date("Y-m-d", strtotime("+50 days"));

    if($id > 0){
        $stmt = $con->prepare("UPDATE oil_changes SET driver_id=?, car_id=?, oil_type=?, change_date=?, km_change=?, next_km=?, next_change=?, cost=?, notes=? WHERE id=?");
        $stmt->bind_param("issssiidsi",
            $driver_id,$car_id,$oil_type,$change_date,
            $km_change,$next_km,$next_change,$cost,$notes,$id
        );
    } else {
        $stmt = $con->prepare("INSERT INTO oil_changes (driver_id, car_id, oil_type, change_date, km_change, next_km, next_change, cost, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("issssiids",
            $driver_id,$car_id,$oil_type,$change_date,
            $km_change,$next_km,$next_change,$cost,$notes
        );
    }

    $stmt->execute();
    header("Location: oile.php?success=1");
    exit();
}

/* عرض مع ربط السائق */
$result = mysqli_query($con,
"SELECT oil_changes.*, drivers.name AS driver_name
FROM oil_changes
LEFT JOIN drivers ON drivers.id = oil_changes.driver_id
ORDER BY oil_changes.id DESC"
);

/* جلب السائقين للفورم */
$drivers = mysqli_query($con, "SELECT id, name FROM drivers");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>نظام تغيير الزيت</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.form{width:420px;margin:30px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 0 10px #ddd}
input,select,textarea{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:5px}
button{width:100%;padding:10px;background:#28a745;color:#fff;border:none;border-radius:5px;cursor:pointer}
.table{width:95%;margin:auto}
table{width:100%;background:#fff;border-collapse:collapse}
th,td{padding:10px;border:1px solid #ddd;text-align:center}
th{background:#28a745;color:#fff}
.edit{background:#007bff;color:#fff;padding:5px 10px;border-radius:5px;text-decoration:none}
.delete{background:red;color:#fff;padding:5px 10px;border-radius:5px;border:none}
.success{text-align:center;color:green;font-weight:bold}
.green{background:#e6ffe6}
.orange{background:#fff4e6}
.red{background:#ffe6e6}
</style>
</head>
<body>

<div class="form">
<h2>🔧 تغيير الزيت</h2>

<?php if(isset($_GET['success'])): ?>
<p class="success">✔ تم الحفظ بنجاح</p>
<?php endif; ?>

<form method="post">
<input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

<select name="driver_id" required>
<option value="">-- اختر السائق --</option>
<?php while($d = mysqli_fetch_assoc($drivers)){ ?>
<option value="<?= $d['id'] ?>"
<?= (isset($edit['driver_id']) && $edit['driver_id']==$d['id'])?'selected':'' ?>>
<?= $d['name'] ?>
</option>
<?php } ?>
</select>

<input type="text" name="car_id" placeholder="رقم السيارة" value="<?= htmlspecialchars($edit['car_id'] ?? '') ?>" required>
<input type="text" name="oil_type" placeholder="نوع الزيت" value="<?= htmlspecialchars($edit['oil_type'] ?? '') ?>" required>
<input type="date" name="change_date" value="<?= $edit['change_date'] ?? '' ?>" required>
<input type="number" name="km_change" placeholder="العداد" value="<?= $edit['km_change'] ?? '' ?>" required>
<input type="number" name="cost" placeholder="التكلفة" value="<?= $edit['cost'] ?? '' ?>" required>
<textarea name="notes" placeholder="ملاحظات"><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea>

<button name="save"><?= $edit ? "تحديث" : "إضافة" ?></button>
</form>
</div>

<div class="table">
<table>
<tr>
<th>السائق</th>
<th>السيارة</th>
<th>الزيت</th>
<th>العداد</th>
<th>القادم</th>
<th>التاريخ</th>
<th>التكلفة</th>
<th>الحالة</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){

$today = date("Y-m-d");
$days_left = (strtotime($row['next_change']) - strtotime($today)) / 86400;
$km_left = $row['next_km'] - $row['km_change'];

if($days_left <= 0 || $km_left <= 0){
$class="red"; $status="متأخر ⚠️";
}elseif($days_left <= 5 || $km_left <= 500){
$class="red"; $status="غير الزيت";
}elseif($days_left <= 20 || $km_left <= 2000){
$class="orange"; $status="قريب";
}else{
$class="green"; $status="ممتاز";
}
?>

<tr class="<?= $class ?>">
<td><?= $row['driver_name'] ?></td>
<td><?= $row['car_id'] ?></td>
<td><?= $row['oil_type'] ?></td>
<td><?= $row['km_change'] ?></td>
<td><?= $row['next_km'] ?></td>
<td><?= $row['change_date'] ?></td>
<td><?= $row['cost'] ?></td>
<td><?= $status ?></td>
<td>
<a class="edit" href="?edit=<?= $row['id'] ?>">تعديل</a>
<form method="post" style="display:inline;">
<input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
<button class="delete" onclick="return confirm('حذف؟')">حذف</button>
</form>
</td>
</tr>

<?php } ?>

</table>
</div>

</body>
</html>
