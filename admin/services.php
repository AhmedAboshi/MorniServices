
<?php
session_start();
include('../include/connected.php');

/* 🔍 البحث */
$search = $_GET['search'] ?? '';

if ($search != '') {
    $search_safe = mysqli_real_escape_string($con, $search);
    $query = "SELECT * FROM product 
              WHERE proname LIKE '%$search_safe%' 
              OR prosection LIKE '%$search_safe%'";
} else {
    $query = "SELECT * FROM product";
}

$result = mysqli_query($con, $query);

/* 🗑️ حذف */
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($con, "DELETE FROM product WHERE id=$id");
    header("Location: services.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إدارة الخدمات</title>

<style>
body {
    font-family: 'Cairo', sans-serif;
    background: #f4f6f9;
    margin: 0;
}

/* 🔍 البحث */
.search-box {
    text-align: center;
    margin: 20px;
}

.search-box input {
    width: 300px;
    padding: 10px;
    border-radius: 25px;
    border: 1px solid #ccc;
}

.search-box button {
    padding: 10px 15px;
    border: none;
    background: #3498db;
    color: white;
    border-radius: 20px;
    cursor: pointer;
}

/* 📊 الجدول */
table {
    width: 95%;
    margin: auto;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

th {
    background: #2c3e50;
    color: white;
    padding: 12px;
}

td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:nth-child(even){
    background: #f9f9f9;
}

tr:hover{
    background: #eef5ff;
}

/* 🖼️ الصور */
img {
    width: 70px;
    height: 70px;
    border-radius: 6px;
    object-fit: cover;
}

/* 🔘 الأزرار */
.delete {
    background: #e74c3c;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.update {
    background: #27ae60;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>
</head>

<body>

<!-- 🔍 البحث -->
<div class="search-box">
<form method="GET">
<input type="text" name="search" placeholder="ابحث عن خدمة..." value="<?php echo htmlspecialchars($search); ?>">
<button type="submit">بحث</button>
</form>
</div>

<!-- 📊 الجدول -->
<table dir="rtl">
<thead>
<tr>
<th>رقم</th>
<th>الصورة</th>
<th>الاسم</th>
<th>السعر</th>
<th>القسم</th>
<th>التفاصيل</th>
<th>الحالة</th>
<th>حذف</th>
<th>تعديل</th>
</tr>
</thead>

<tbody>
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<tr>

<td><?php echo $row['id']; ?></td>

<td>
<img src="../uploads/img/<?php echo $row['proimg']; ?>">
</td>

<td><?php echo $row['proname']; ?></td>
<td><?php echo $row['proprice']; ?></td>
<td><?php echo $row['prosection']; ?></td>
<td><?php echo $row['prodescrip']; ?></td>
<td><?php echo $row['prounv']; ?></td>

<td>
<a href="services.php?id=<?php echo $row['id']; ?>" 
onclick="return confirm('هل أنت متأكد من الحذف؟')">
<button class="delete">حذف</button>
</a>
</td>

<td>
<a href="update.php?id=<?php echo $row['id']; ?>">
<button class="update">تعديل</button>
</a>
</td>

</tr>
<?php } ?>
</tbody>

</table>

</body>
</html>
```
