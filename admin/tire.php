<?php
session_start();
include('../include/connected.php');

// 🗑️ حذف
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($con, "DELETE FROM tires WHERE id = '$id'");
    header("Location: tire.php");
    exit();
}

// ✏️ جلب بيانات التعديل
$edit_row = null;
if(isset($_GET['edit'])){
    $id = $_GET['edit'];
    $res = mysqli_query($con, "SELECT * FROM tires WHERE id='$id'");
    $edit_row = mysqli_fetch_assoc($res);
}

// ➕ + ✏️ حفظ
if(isset($_POST['save'])){
    $id = $_POST['id'];
    $car_id = $_POST['car_id'];
    $type = $_POST['tire_type'];
    $date = $_POST['change_date'];
    $notes = $_POST['notes'];
    $driver= $_POST['driver'];
    $cost= $_POST['cost'];
    if(!empty($id)){
        // تعديل
        $query = "UPDATE tires SET
                  car_id='$car_id',
                  driver ='$driver',
                  tire_type='$type',
                  change_date='$date',
                  notes='$notes',
                  cost='$cost'
                  WHERE id='$id'";
    } else {
        // إضافة
        $query = "INSERT INTO tires (car_id, tire_type, change_date, notes, driver,cost)
                  VALUES ('$car_id','$tire_type','$change_date','$notes','$driver','$cost')";
    }

    mysqli_query($con, $query);
    header("Location: tire.php?success=1");
    exit();
}

// عرض البيانات
$result = mysqli_query($con, "SELECT * FROM tires");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>إدارة الإطارات</title>

<style>
body {
    font-family: Arial;
    background: #f4f6f9;
}

/* الفورم */
.form-container {
    width: 400px;
    margin: 30px auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px #ddd;
}

input, textarea {
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

/* الجدول */
.table-container {
    width: 90%;
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
    border-bottom: 1px solid #ddd;
}

th {
    background: #007bff;
    color: white;
}

/* أزرار */
a {
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
}

.edit {
    background: green;
    color: white;
}

.delete {
    background: red;
    color: white;
}
.success {
    text-align:center;
    color: green;
}
</style>
</head>

<body>

<div class="form-container">
<h2>إدارة الإطارات</h2>

<?php
if(isset($_GET['success'])){
    echo "<p class='success'>✔ تمت العملية بنجاح</p>";
}
?>

<form method="post">
    <input type="hidden" name="id" value="<?php echo @$edit_row['id']; ?>">

    <input type="text" name="car_id" placeholder="رقم السيارة"
    value="<?php echo @$edit_row['car_id']; ?>" required>

    <input type="text" name="driver" placeholder="المزود"
    value="<?php echo @$edit_row['driver']; ?>" required>

    <input type="text" name="tire_type" placeholder="نوع الإطار"
    value="<?php echo @$edit_row['tire_type']; ?>" required>

    <input type="date" name="change_date"
    value="<?php echo @$edit_row['change_date']; ?>" required>

    <input type="DECIMAL" name="cost"
    value="<?php echo @$edit_row['cost']; ?>" required>

    <textarea name="notes" placeholder="ملاحظات"><?php echo @$edit_row['notes']; ?></textarea>


    <button name="save">
        <?php echo $edit_row ? "تحديث" : "إضافة"; ?>
    </button>
</form>
</div>

<div class="table-container">
<table dir="rtl">
<tr>
    <th>رقم السيارة</th>
    <th>المزود</th>
    <th>نوع الإطار</th>
    <th>تاريخ التغيير</th>
    <th>ملاحظات</th>
    <th>تكلفة</th>
    <th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>
<tr>
    <td><?php echo $row['car_id']; ?></td>
    <td><?php echo $row['driver']; ?></td>
    <td><?php echo $row['tire_type']; ?></td>
    <td><?php echo $row['change_date']; ?></td>
    <td><?php echo $row['notes']; ?></td>
     <td><?php echo $row['cost']; ?></td>
    
    <td>
        <a class="edit" href="?edit=<?php echo $row['id']; ?>">تعديل</a>
        <a class="delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('متأكد من الحذف؟')">حذف</a>
    </td>
</tr>
<?php } ?>

</table>
</div>

</body>
</html>