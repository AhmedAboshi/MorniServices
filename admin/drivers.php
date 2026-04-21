<?php
include('../include/connected.php');

/* =========================
   🗑️ حذف
========================= */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($con, "DELETE FROM drivers WHERE id='$id'");
    header("Location: drivers.php");
    exit();
}

/* =========================
   ✏️ تعديل
========================= */
$edit = null;
if(isset($_GET['edit'])){
    $id = $_GET['edit'];
    $res = mysqli_query($con, "SELECT * FROM drivers WHERE id='$id'");
    $edit = mysqli_fetch_assoc($res);
}

/* =========================
   💾 حفظ (إضافة / تعديل)
========================= */
if(isset($_POST['save'])){

    $id = $_POST['id'];
    $name = $_POST['name'];
    $national_id = $_POST['national_id'];
    $phone = $_POST['phone'];
    $truck_type = $_POST['truck_type'];
    $plate_number = $_POST['plate_number'];
    $work_area = $_POST['work_area'];

    if($id){
        $sql = "UPDATE drivers SET 
name='$name',
national_id='$national_id',
phone='$phone',
truck_type='$truck_type',
plate_number='$plate_number',
work_area='$work_area'
WHERE id='$id'";
    } else {
        $sql = "INSERT INTO drivers 
(name, national_id, phone, truck_type, plate_number, work_area)
VALUES 
('$name','$national_id','$phone','$truck_type','$plate_number','$work_area')";
    }

    mysqli_query($con, $sql);
    header("Location: drivers.php");
    exit();
}

/* =========================
   📊 عرض
========================= */
$result = mysqli_query($con, "SELECT * FROM drivers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إدارة السائقين</title>

<style>
body{font-family:Arial;background:#f4f6f9;}

.container{
    width:95%;
    margin:auto;
}

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
}

button{
    width:100%;
    padding:10px;
    background:#28a745;
    color:#fff;
    border:none;
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

<input type="hidden" name="id" value="<?php echo $edit['id'] ?? ''; ?>">

<input type="text" name="name" placeholder="اسم السائق"
value="<?php echo $edit['name'] ?? ''; ?>" required>

<input type="text" name="national_id" placeholder="رقم الهوية"
value="<?php echo $edit['national_id'] ?? ''; ?>">

<input type="text" name="phone" placeholder="رقم الجوال"
value="<?php echo $edit['phone'] ?? ''; ?>">

<input type="text" name="truck_type" placeholder="نوع السطحة"
value="<?php echo $edit['truck_type'] ?? ''; ?>">

<input type="text" name="plate_number" placeholder="لوحة السطحة"
value="<?php echo $edit['plate_number'] ?? ''; ?>">

<input type="text" name="work_area" placeholder="منطقة العمل"
value="<?php echo $edit['work_area'] ?? ''; ?>">

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
    <th>تسلسل المزود</th>
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
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['national_id']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['truck_type']; ?></td>
<td><?php echo $row['plate_number']; ?></td>
<td><?php echo $row['work_area']; ?></td>
<td>
<a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>
<a class="delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('حذف السائق؟')">حذف</a>
</td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>