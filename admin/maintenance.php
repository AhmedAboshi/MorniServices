<?php
session_start();
include('../include/connected.php');


/* =========================
   👤 جلب السائقين 
========================= */
$drivers_result = mysqli_query($con, "SELECT id, name FROM drivers");

$drivers = [];
while($row = mysqli_fetch_assoc($drivers_result)){
    $drivers[] = $row;
}

/* =========================
   🗑️ حذف
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
   ✏️ تعديل
========================= */
$edit_row = null;

if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM maintenance WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit_row = $stmt->get_result()->fetch_assoc();
}

/* =========================
   💾 حفظ
========================= */
if(isset($_POST['save'])){

    $id = (int) ($_POST['id'] ?? 0);
    $driver_id = (int)$_POST['driver_id'];
    $vehicle_name = trim($_POST['vehicle_name']);
    $plate_number = trim($_POST['plate_number']);
    $maintenance_type = trim($_POST['maintenance_type']);
    $cost = (float) $_POST['cost'];
    $notes = trim($_POST['notes']);
    $maintenance_date = $_POST['maintenance_date'];

    if($id > 0){

        $stmt = $con->prepare("UPDATE maintenance SET 
            driver_id=?,
            vehicle_name=?,
            plate_number=?,
            maintenance_type=?,
            cost=?,
            notes=?,
            maintenance_date=?
            WHERE id=?");

        $stmt->bind_param(
            "issssdsi",
            $driver_id,
            $vehicle_name,
            $plate_number,
            $maintenance_type,
            $cost,
            $notes,
            $maintenance_date,
            $id
        );

    } else {

        $stmt = $con->prepare("INSERT INTO maintenance 
        (driver_id, vehicle_name, plate_number, maintenance_type, cost, notes, maintenance_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "issssds",
            $driver_id,
            $vehicle_name,
            $plate_number,
            $maintenance_type,
            $cost,
            $notes,
            $maintenance_date
        );
    }

    $stmt->execute();

    header("Location: maintenance.php?success=1");
    exit();
}

/* =========================
   📊 عرض (JOIN)
========================= */
$result = mysqli_query($con, "
SELECT maintenance.*, drivers.name AS driver_name
FROM maintenance
LEFT JOIN drivers ON maintenance.driver_id = drivers.id
ORDER BY maintenance.id DESC
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الصيانة</title>

<style>
body {font-family:Arial;background:#f4f6f9;}

.container {
    width:95%;
    margin:20px auto;
    background:#fff;
    padding:20px;
    border-radius:10px;
}

input, select, textarea {
    width:100%;padding:10px;margin:5px 0;
    border:1px solid #ccc;border-radius:6px;
}

button {
    padding:8px;border:none;border-radius:5px;cursor:pointer;
}

.save {background:green;color:#fff;width:100%;}
.edit {background:orange;color:#fff;}
.delete {background:red;color:#fff;}

table {width:100%;border-collapse:collapse;margin-top:20px;}
th, td {padding:10px;border:1px solid #ddd;text-align:center;}
th {background:#007bff;color:#fff;}

.success {color:green;text-align:center;font-weight:bold;}
</style>

</head>
<body>

<div class="container">

<h2><?= $edit_row ? "✏️ تعديل صيانة" : "➕ إضافة صيانة" ?></h2>

<?php if(isset($_GET['success'])): ?>
<p class="success">✔ تمت العملية بنجاح</p>
<?php endif; ?>

<!-- الفورم -->
<form method="post">

<input type="hidden" name="id" value="<?= $edit_row['id'] ?? 0 ?>">

<select name="driver_id" required>
<option value="">-- اختر السائق --</option>
<?php foreach($drivers as $d){ ?>
<option value="<?= $d['id'] ?>"
<?= (isset($edit_row['driver_id']) && $edit_row['driver_id']==$d['id'])?'selected':'' ?>>
<?= htmlspecialchars($d['name']) ?>
</option>
<?php } ?>
</select>

<input type="text" name="vehicle_name" placeholder="اسم الورشة"
value="<?= $edit_row['vehicle_name'] ?? '' ?>" required>

<input type="text" name="plate_number" placeholder="رقم اللوحة"
value="<?= $edit_row['plate_number'] ?? '' ?>" required>

<input type="text" name="maintenance_type" placeholder="نوع الصيانة"
value="<?= $edit_row['maintenance_type'] ?? '' ?>" required>

<input type="number" step="0.01" name="cost" placeholder="التكلفة"
value="<?= $edit_row['cost'] ?? '' ?>">

<textarea name="notes"><?= $edit_row['notes'] ?? '' ?></textarea>

<input type="date" name="maintenance_date"
value="<?= $edit_row['maintenance_date'] ?? '' ?>" required>

<button class="save" name="save">
<?= $edit_row ? "تحديث" : "إضافة" ?>
</button>

</form>

<!-- الجدول -->
<table>
<tr>
    <th>السائق</th>
    <th>الورشة</th>
    <th>اللوحة</th>
    <th>نوع الصيانة</th>
    <th>التكلفة</th>
    <th>التاريخ</th>
    <th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>
<tr>
    <td><?= htmlspecialchars($row['driver_name']) ?></td>
    <td><?= htmlspecialchars($row['vehicle_name']) ?></td>
    <td><?= htmlspecialchars($row['plate_number']) ?></td>
    <td><?= htmlspecialchars($row['maintenance_type']) ?></td>
    <td><?= htmlspecialchars($row['cost']) ?></td>
    <td><?= htmlspecialchars($row['maintenance_date']) ?></td>

    <td>
        <a class="edit" href="?edit=<?= $row['id'] ?>">تعديل</a>

        <form method="post" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
            <button class="delete" onclick="return confirm('متأكد؟')">حذف</button>
        </form>
    </td>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>