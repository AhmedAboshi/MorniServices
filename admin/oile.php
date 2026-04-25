<?php
session_start();
include(__DIR__ . '/../include/connected.php');

/* 🔥 إظهار الأخطاء */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* =========================
   🔌 فحص الاتصال
========================= */
if(!isset($con) || !$con){
    die("❌ خطأ في الاتصال بقاعدة البيانات");
}

/* =========================
   🗑️ حذف
========================= */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM oil_changes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: oile.php");
    exit();
}

/* =========================
   ✏️ تعديل
========================= */
$edit = null;

if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM oil_changes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
}

/* =========================
   💾 حفظ (إضافة / تعديل)
========================= */
if(isset($_POST['save'])){

    $id = (int)($_POST['id'] ?? 0);

    $car_id = trim($_POST['car_id']);
    $driver = trim($_POST['driver']);
    $oil_type = trim($_POST['oil_type']);
    $change_date = $_POST['change_date'];
    $km_change = (int)$_POST['km_change'];
    $cost = (float)$_POST['cost'];
    $notes = trim($_POST['notes']);

    /* 🔢 حسابات */
    $next_km = $km_change + 5000;
    $next_change = date("Y-m-d", strtotime("+50 days"));

    if($id > 0){

        $stmt = $con->prepare("UPDATE oil_changes SET 
            car_id=?,
            driver=?,
            oil_type=?,
            change_date=?,
            km_change=?,
            next_km=?,
            next_change=?,
            cost=?,
            notes=?
            WHERE id=?");

        $stmt->bind_param(
            "ssssiisdsi",
            $car_id,
            $driver,
            $oil_type,
            $change_date,
            $km_change,
            $next_km,
            $next_change,
            $cost,
            $notes,
            $id
        );

    } else {

        $stmt = $con->prepare("INSERT INTO oil_changes
        (car_id, driver, oil_type, change_date, km_change, next_km, next_change, cost, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "ssssiisds",
            $car_id,
            $driver,
            $oil_type,
            $change_date,
            $km_change,
            $next_km,
            $next_change,
            $cost,
            $notes
        );
    }

    $stmt->execute();
    header("Location: oile.php?success=1");
    exit();
}

/* =========================
   📊 عرض البيانات
========================= */
$result = mysqli_query($con, "SELECT * FROM oil_changes ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>نظام تغيير الزيت</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0}

/* الفورم */
.form{
    width:420px;
    margin:30px auto;
    background:#fff;
    padding:20px;
    border-radius:10px;
    box-shadow:0 0 10px #ddd;
}

input,textarea{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:5px;
}

button{
    width:100%;
    padding:10px;
    background:#28a745;
    color:#fff;
    border:none;
    border-radius:5px;
}

/* الجدول */
.table{
    width:95%;
    margin:auto;
}

table{
    width:100%;
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

/* أزرار */
.edit{
    background:#007bff;
    color:#fff;
    padding:5px 10px;
    border-radius:5px;
    text-decoration:none;
}

.delete{
    background:red;
    color:#fff;
    padding:5px 10px;
    border:none;
    border-radius:5px;
}

.success{
    text-align:center;
    color:green;
}
</style>

</head>
<body>

<!-- =========================
     FORM
========================= -->
<div class="form">

<h2>🔧 تغيير الزيت</h2>

<?php if(isset($_GET['success'])): ?>
<p class="success">✔ تم الحفظ بنجاح</p>
<?php endif; ?>

<form method="post">

<input type="hidden" name="id" value="<?php echo $edit['id'] ?? 0; ?>">

<input type="text" name="car_id" placeholder="رقم السيارة"
value="<?php echo htmlspecialchars($edit['car_id'] ?? ''); ?>" required>

<input type="text" name="driver" placeholder="المزود"
value="<?php echo htmlspecialchars($edit['driver'] ?? ''); ?>" required>

<input type="text" name="oil_type" placeholder="نوع الزيت"
value="<?php echo htmlspecialchars($edit['oil_type'] ?? ''); ?>" required>

<input type="date" name="change_date"
value="<?php echo $edit['change_date'] ?? ''; ?>" required>

<input type="number" name="km_change" placeholder="العداد"
value="<?php echo $edit['km_change'] ?? ''; ?>" required>

<input type="number" name="cost" placeholder="التكلفة"
value="<?php echo $edit['cost'] ?? ''; ?>" required>

<textarea name="notes" placeholder="ملاحظات"><?php echo htmlspecialchars($edit['notes'] ?? ''); ?></textarea>

<button name="save">
<?php echo $edit ? "تحديث" : "إضافة"; ?>
</button>

</form>

</div>

<!-- =========================
     TABLE
========================= -->
<div class="table">

<table>

<tr>
<th>السيارة</th>
<th>المزود</th>
<th>الزيت</th>
<th>العداد</th>
<th>القادم</th>
<th>التكلفة</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
<td><?php echo htmlspecialchars($row['car_id']); ?></td>
<td><?php echo htmlspecialchars($row['driver']); ?></td>
<td><?php echo htmlspecialchars($row['oil_type']); ?></td>
<td><?php echo $row['km_change']; ?></td>
<td><?php echo $row['next_km']; ?></td>
<td><?php echo $row['cost']; ?></td>

<td>
<a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>

<form method="post" style="display:inline;">
<input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
<button class="delete" onclick="return confirm('حذف؟')">حذف</button>
</form>

</td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>