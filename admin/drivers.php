<?php
include('../include/connected.php');

/* =========================
   🗑️ حذف آمن (POST)
========================= */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM drivers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: drivers.php");
    exit();
}

/* =========================
   ✏️ تعديل (عرض بيانات)
========================= */
$edit = null;

if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM drivers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
}

/* =========================
   💾 حفظ (إضافة / تعديل)
========================= */
if(isset($_POST['save'])){

    $id = (int) ($_POST['id'] ?? 0);

    $name = trim($_POST['name']);
    $national_id = trim($_POST['national_id']);
    $phone = trim($_POST['phone']);
    $truck_type = trim($_POST['truck_type']);
    $plate_number = trim($_POST['plate_number']);
    $work_area = trim($_POST['work_area']);

    if($id > 0){

        $stmt = $con->prepare("UPDATE drivers SET 
            name=?,
            national_id=?,
            phone=?,
            truck_type=?,
            plate_number=?,
            work_area=?
            WHERE id=?");

        $stmt->bind_param(
            "ssssssi",
            $name,
            $national_id,
            $phone,
            $truck_type,
            $plate_number,
            $work_area,
            $id
        );

    } else {

        $stmt = $con->prepare("INSERT INTO drivers 
        (name, national_id, phone, truck_type, plate_number, work_area)
        VALUES (?,?,?,?,?,?)");

        $stmt->bind_param(
            "ssssss",
            $name,
            $national_id,
            $phone,
            $truck_type,
            $plate_number,
            $work_area
        );
    }

    $stmt->execute();

    header("Location: drivers.php");
    exit();
}

/* =========================
   📊 عرض البيانات
========================= */
$result = mysqli_query($con, "SELECT * FROM drivers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة السائقين</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:0;}
.container{width:95%;margin:auto;}

h2{margin-top:20px;}

.form-box{
    background:#fff;
    padding:15px;
    margin-top:20px;
    border-radius:10px;
}

input{
    width:100%;
    padding:10px;
    margin:6px 0;
    border:1px solid #ddd;
    border-radius:5px;
}

button{
    width:100%;
    padding:10px;
    background:#28a745;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
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

a{
    padding:5px 10px;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    display:inline-block;
}

.edit{background:#007bff;}
.delete{background:red;}
</style>

</head>
<body>

<div class="container">

<h2>🚗 إدارة السائقين</h2>

<!-- =========================
     ➕ الفورم
========================= -->
<div class="form-box">

<form method="post">

<input type="hidden" name="id" value="<?php echo $edit['id'] ?? 0; ?>">

<input type="text" name="name" placeholder="اسم السائق"
value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required>

<input type="text" name="national_id" placeholder="رقم الهوية"
value="<?php echo htmlspecialchars($edit['national_id'] ?? ''); ?>">

<input type="text" name="phone" placeholder="رقم الجوال"
value="<?php echo htmlspecialchars($edit['phone'] ?? ''); ?>">

<input type="text" name="truck_type" placeholder="نوع السطحة"
value="<?php echo htmlspecialchars($edit['truck_type'] ?? ''); ?>">

<input type="text" name="plate_number" placeholder="لوحة السطحة"
value="<?php echo htmlspecialchars($edit['plate_number'] ?? ''); ?>">

<input type="text" name="work_area" placeholder="منطقة العمل"
value="<?php echo htmlspecialchars($edit['work_area'] ?? ''); ?>">

<button name="save">
<?php echo $edit ? "تحديث" : "إضافة"; ?>
</button>

</form>

</div>

<!-- =========================
     📋 الجدول
========================= -->
<table>

<tr>
<th>الرقم</th>
<th>الاسم</th>
<th>الهوية</th>
<th>الجوال</th>
<th>نوع السطحة</th>
<th>لوحة السطحة</th>
<th>منطقة العمل</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['name']); ?></td>
<td><?php echo htmlspecialchars($row['national_id']); ?></td>
<td><?php echo htmlspecialchars($row['phone']); ?></td>
<td><?php echo htmlspecialchars($row['truck_type']); ?></td>
<td><?php echo htmlspecialchars($row['plate_number']); ?></td>
<td><?php echo htmlspecialchars($row['work_area']); ?></td>

<td>
<a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>

<form method="post" style="display:inline;">
    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
    <button class="delete" onclick="return confirm('هل تريد حذف السائق؟')" style="border:none;cursor:pointer;">
        حذف
    </button>
</form>

</td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>