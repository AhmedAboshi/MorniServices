<?php
session_start();
include('../include/connected.php');



/* =========================
   🗑️ حذف
========================= */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM tires WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: tire.php");
    exit();
}

/* =========================
   ✏️ جلب للتعديل
========================= */
$edit_row = null;

if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM tires WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit_row = $stmt->get_result()->fetch_assoc();
}

/* =========================
   💾 حفظ (إضافة / تعديل)
========================= */
if(isset($_POST['save'])){

    $id = (int) ($_POST['id'] ?? 0);
    $driver_id = (int)$_POST['driver_id'];
    $car_id = trim($_POST['car_id']);
    $tire_type = trim($_POST['tire_type']);
    $change_date = $_POST['change_date'];
    $notes = trim($_POST['notes']);
    $cost = (float) $_POST['cost'];

    if($id > 0){

        // تحديث
        $stmt = $con->prepare("UPDATE tires SET 
            driver_id=?,
            car_id=?,
            tire_type=?,
            change_date=?,
            notes=?,
            cost=?
            WHERE id=?");

        $stmt->bind_param(
            "issssdi",
            $driver_id,
            $car_id,
            $tire_type,
            $change_date,
            $notes,
            $cost,
            $id
        );

    } else {

        // إضافة
        $stmt = $con->prepare("INSERT INTO tires 
        (driver_id, car_id, tire_type, change_date, notes, cost)
        VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "issssd",
            $driver_id,
            $car_id,
            $tire_type,
            $change_date,
            $notes,
            $cost
        );
    }

    $stmt->execute();

    header("Location: tire.php?success=1");
    exit();
}

/* =========================
   👤 جلب السائقين
========================= */
$drivers = mysqli_query($con, "SELECT id, name FROM drivers");

/* =========================
   📊 عرض (JOIN)
========================= */
$result = mysqli_query($con, "
SELECT tires.*, drivers.name AS driver_name
FROM tires
LEFT JOIN drivers ON tires.driver_id = drivers.id
ORDER BY tires.id DESC
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة الإطارات</title>

<style>
body {
    font-family: Arial;
    background: #f4f6f9;
}

.form-container {
    width: 400px;
    margin: 30px auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}

input, select, textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    width: 100%;
    padding: 10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #0056b3;
}

.table-container {
    width: 95%;
    margin: 30px auto;
}

table {
    width: 100%;
    background: white;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

th {
    background: #007bff;
    color: white;
}

.edit {
    background: green;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
}

.delete {
    background: red;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
}

.success {
    text-align:center;
    color: green;
    font-weight:bold;
}
</style>

</head>
<body>

<div class="form-container">

<h2>🛞 إدارة الإطارات</h2>

<?php if(isset($_GET['success'])): ?>
<p class="success">✔ تمت العملية بنجاح</p>
<?php endif; ?>

<form method="post">

<input type="hidden" name="id" value="<?php echo $edit_row['id'] ?? 0; ?>">

<select name="driver_id" required>
    <option value="">-- اختر السائق --</option>
    <?php while($d = mysqli_fetch_assoc($drivers)){ ?>
        <option value="<?= $d['id'] ?>"
        <?= (isset($edit_row['driver_id']) && $edit_row['driver_id']==$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?>
        </option>
    <?php } ?>
</select>

<input type="text" name="car_id" placeholder="رقم السيارة"
value="<?php echo htmlspecialchars($edit_row['car_id'] ?? ''); ?>" required>

<input type="text" name="tire_type" placeholder="نوع الإطار"
value="<?php echo htmlspecialchars($edit_row['tire_type'] ?? ''); ?>" required>

<input type="date" name="change_date"
value="<?php echo $edit_row['change_date'] ?? ''; ?>" required>

<input type="number" step="0.01" name="cost" placeholder="التكلفة"
value="<?php echo $edit_row['cost'] ?? ''; ?>" required>

<textarea name="notes" placeholder="ملاحظات"><?php echo htmlspecialchars($edit_row['notes'] ?? ''); ?></textarea>

<button name="save">
<?php echo $edit_row ? "تحديث" : "إضافة"; ?>
</button>

</form>

</div>

<div class="table-container">

<table>
<tr>
    <th>السائق</th>
    <th>السيارة</th>
    <th>نوع الإطار</th>
    <th>التاريخ</th>
    <th>التكلفة</th>
    <th>ملاحظات</th>
    <th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
    <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
    <td><?php echo htmlspecialchars($row['car_id']); ?></td>
    <td><?php echo htmlspecialchars($row['tire_type']); ?></td>
    <td><?php echo htmlspecialchars($row['change_date']); ?></td>
    <td><?php echo htmlspecialchars($row['cost']); ?></td>
    <td><?php echo htmlspecialchars($row['notes']); ?></td>

    <td>
        <a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>

        <form method="post" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
            <button class="delete" onclick="return confirm('متأكد من الحذف؟')">حذف</button>
        </form>
    </td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>